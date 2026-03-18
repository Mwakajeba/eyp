<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Production\WorkOrder;
use App\Models\Production\WorkOrderBom;
use App\Models\Production\WorkOrderProcess;
use App\Models\Production\MaterialIssue;
use App\Models\Production\ProductionRecord;
use App\Models\Production\QualityCheck;
use App\Models\Production\PackagingRecord;
use App\Models\Customer;
use App\Models\Inventory\Item;
use App\Models\ProductionMachine;
use App\Models\User;
use App\Models\Branch;
use App\Models\InventoryLocation;
use App\Services\InventoryCostService;
use App\Services\InventoryStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Vinkla\Hashids\Facades\Hashids;

class WorkOrderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            try {
                $query = WorkOrder::with(['customer', 'inventoryLocation', 'createdBy']);

                // Handle branch filtering - if no session branch_id, show all branches for the company
                $branchId = session('branch_id');
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
                // If no branch_id in session, don't filter by branch (show all for company)

                $workOrders = $query->select('work_orders.*');

                // Debug: Log query info
                \Log::info('Work Orders Index AJAX', [
                    'user_company_id' => Auth::user()->company_id,
                    'session_branch_id' => session('branch_id', 'null'),
                    'total_work_orders' => WorkOrder::count(),
                    'company_work_orders' => WorkOrder::where('company_id', Auth::user()->company_id)->count(),
                ]);

                return DataTables::of($workOrders)
                    ->addColumn('name', function ($workOrder) {
                        if ($workOrder->work_order_type == 'customer') {
                            return $workOrder->customer->name ?? 'N/A';
                        } elseif ($workOrder->work_order_type == 'inventory_location') {
                            return $workOrder->inventoryLocation->name ?? 'N/A';
                        } else {
                            return 'N/A';
                        }
                    })
                    ->addColumn('status_badge', function ($workOrder) {
                        return $workOrder->status_badge;
                    })
                    ->addColumn('total_quantity', function ($workOrder) {
                        return $workOrder->total_quantity;
                    })
                    ->addColumn('progress_bar', function ($workOrder) {
                        $percentage = $workOrder->getProgressPercentage();
                        return '<div class="progress" style="height: 20px;">
                            <div class="progress-bar" role="progressbar" style="width: ' . $percentage . '%;"
                                 aria-valuenow="' . $percentage . '" aria-valuemin="0" aria-valuemax="100">
                                ' . $percentage . '%
                            </div>
                        </div>';
                    })
                    ->addColumn('actions', function ($workOrder) {
                        $actions = '<div class="btn-group" role="group">';
                        $actions .= '<a href="' . route('production.work-orders.show', $workOrder->encoded_id) . '" class="btn btn-sm btn-info">View</a>';
                        $actions .= '<a href="' . route('production.work-orders.edit', $workOrder->encoded_id) . '" class="btn btn-sm btn-warning">Edit</a>';
                        if ($workOrder->canAdvanceToNextStage()) {
                            $actions .= '<button type="button" class="btn btn-sm btn-success advance-stage" data-id="' . $workOrder->encoded_id . '">Advance</button>';
                        }
                        $actions .= '</div>';
                        return $actions;
                    })
                    ->editColumn('due_date', function ($workOrder) {
                        return $workOrder->due_date->format('M d, Y');
                    })
                    ->rawColumns(['status_badge', 'progress_bar', 'actions'])
                    ->make(true);
            } catch (\Exception $e) {
                \Log::error('Work Orders Index AJAX Error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'error' => 'Failed to load work orders: ' . $e->getMessage()
                ], 500);
            }
        }

        return view('production.work-orders.index');
    }

    public function create()
    {
        $user = Auth::user();

        if (!$user || !$user->company_id) {
            return redirect()->route('dashboard')
                ->with('error', 'Company information not found. Please contact your administrator.');
        }

        $customers = Customer::where('company_id', $user->company_id)->get();
        $materials = Item::where('company_id', $user->company_id)
            ->where('item_type', 'product')
            ->where('is_active', true)
            ->get();
        $inventoryLocations = InventoryLocation::where('company_id', $user->company_id)->get();
        $workOrderTypes = ['customer', 'inventory_location'];

        return view('production.work-orders.create', compact('customers', 'materials', 'inventoryLocations', 'workOrderTypes'));
    }

    public function store(Request $request)
    {
        // Debug: Log incoming request data
        \Log::info('Work Order Store Request', [
            'all_data' => $request->all(),
            'sizes_quantities' => $request->input('sizes_quantities'),
            'bom' => $request->input('bom'),
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id ?? 'null',
            'session_branch_id' => session('branch_id', 'null')
        ]);

        // Handle sizes_quantities JSON conversion
        $sizesQuantities = $request->input('sizes_quantities');
        if (is_string($sizesQuantities)) {
            $sizesQuantities = json_decode($sizesQuantities, true);
        }

        $request->merge(['sizes_quantities' => $sizesQuantities]);

        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'inventory_location_id' => 'nullable|exists:inventory_locations,id',
            'work_order_type' => 'required|in:customer,inventory_location',
            'require_knitting' => 'required|boolean',
            'product_name' => 'required|string|max:255',
            'style' => 'required|string|max:255',
            'sizes_quantities' => 'required|array',
            'due_date' => 'required|date|after:today',
            'requires_logo' => 'boolean',
            'notes' => 'nullable|string',
            'bom' => 'required|array',
            'bom.*.material_id' => 'required|exists:inventory_items,id',
            'bom.*.quantity' => 'required|numeric|min:0.001',
            'bom.*.unit' => 'required|string',
            'bom.*.material_type' => 'required|string',
            'bom.*.variance' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            // Generate WO number
            $woNumber = $this->generateWONumber();

            // Get branch_id - fallback to user's first branch if session is empty
            $branchId = session('branch_id');
            if (!$branchId) {
                $userBranch = Auth::user()->branch_id ?? Branch::where('company_id', Auth::user()->company_id)->first()?->id;
                $branchId = $userBranch;
            }

            if (!$branchId) {
                throw new \Exception('No branch found. Please contact administrator.');
            }

            $workOrder = WorkOrder::create([
                'company_id' => Auth::user()->company_id,
                'branch_id' => $branchId,
                'wo_number' => $woNumber,
                'customer_id' => $request->customer_id,
                'inventory_location_id' => $request->inventory_location_id,
                'work_order_type' => $request->work_order_type,
                'require_knitting' => $request->require_knitting,
                'product_name' => $request->product_name,
                'style' => $request->style,
                'sizes_quantities' => $request->sizes_quantities,
                'due_date' => $request->due_date,
                'requires_logo' => $request->boolean('requires_logo'),
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            // Create BOM entries
            foreach ($request->bom as $bomItem) {
                WorkOrderBom::create([
                    'work_order_id' => $workOrder->id,
                    'material_item_id' => $bomItem['material_id'],
                    'material_type' => $bomItem['material_type'],
                    'required_quantity' => $bomItem['quantity'],
                    'unit_of_measure' => $bomItem['unit'],
                    'variance_allowed' => $bomItem['variance'] ?? 5.0,
                ]);
            }

            // Create initial process entries for all stages
            $stages = [
                WorkOrder::STATUS_PLANNED,
                WorkOrder::STATUS_MATERIAL_ISSUED,
                WorkOrder::STATUS_CUTTING,
                WorkOrder::STATUS_JOINING,
            ];

            if ($request->boolean('require_knitting')) {
                $stages[] = WorkOrder::STATUS_KNITTING;
            }

            if ($request->boolean('requires_logo')) {
                $stages[] = WorkOrder::STATUS_EMBROIDERY;
            }

            $stages = array_merge($stages, [
                WorkOrder::STATUS_IRONING_FINISHING,
                WorkOrder::STATUS_QC,
                WorkOrder::STATUS_PACKAGING,
            ]);

            foreach ($stages as $stage) {
                WorkOrderProcess::create([
                    'work_order_id' => $workOrder->id,
                    'process_stage' => $stage,
                    'status' => $stage === WorkOrder::STATUS_PLANNED ? 'in_progress' : 'pending',
                ]);
            }

            DB::commit();

            return redirect()->route('production.work-orders.index')
                ->with('success', 'Work Order created successfully: ' . $woNumber);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to create work order: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $workOrderId = !empty($decoded) ? $decoded[0] : null;

        if (!$workOrderId) {
            return redirect()->route('production.work-orders.index')
                ->with('error', 'Invalid work order ID');
        }

        $workOrder = WorkOrder::with([
            'customer', 'bom.materialItem', 'processes', 'materialIssues',
            'productionRecords', 'qualityChecks', 'packagingRecords', 'inventoryLocation'
        ])->findOrFail($workOrderId);

        return view('production.work-orders.show', compact('workOrder'));
    }

    public function edit($encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $workOrderId = !empty($decoded) ? $decoded[0] : null;

        if (!$workOrderId) {
            return redirect()->route('production.work-orders.index')
                ->with('error', 'Invalid work order ID');
        }

        $workOrder = WorkOrder::with(['customer', 'bom.materialItem', 'processes', 'inventoryLocation'])
            ->findOrFail($workOrderId);

        // Only allow editing if work order is in PLANNED status
        if ($workOrder->status !== WorkOrder::STATUS_PLANNED) {
            return redirect()->route('production.work-orders.show', $encodedId)
                ->with('error', 'Work order can only be edited when in PLANNED status');
        }

        $user = Auth::user();
        $customers = Customer::where('company_id', $user->company_id)->get();
        $materials = Item::where('company_id', $user->company_id)
            ->where('item_type', 'product')
            ->where('is_active', true)
            ->get();
        $inventoryLocations = InventoryLocation::where('company_id', $user->company_id)->get();

        return view('production.work-orders.edit', compact('workOrder', 'customers', 'materials', 'inventoryLocations'));
    }

    public function update(Request $request, $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $workOrderId = !empty($decoded) ? $decoded[0] : null;

        if (!$workOrderId) {
            return redirect()->route('production.work-orders.index')
                ->with('error', 'Invalid work order ID');
        }

        $workOrder = WorkOrder::findOrFail($workOrderId);

        // Only allow updating if work order is in PLANNED status
        if ($workOrder->status !== WorkOrder::STATUS_PLANNED) {
            return redirect()->route('production.work-orders.show', $encodedId)
                ->with('error', 'Work order can only be updated when in PLANNED status');
        }

        // Handle sizes_quantities JSON conversion
        $sizesQuantities = $request->input('sizes_quantities');
        if (is_string($sizesQuantities)) {
            $sizesQuantities = json_decode($sizesQuantities, true);
        }

        $request->merge(['sizes_quantities' => $sizesQuantities]);

        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'inventory_location_id' => 'nullable|exists:inventory_locations,id',
            'product_name' => 'required|string|max:255',
            'style' => 'required|string|max:255',
            'sizes_quantities' => 'required|array',
            'due_date' => 'required|date|after:today',
            'requires_logo' => 'boolean',
            'notes' => 'nullable|string',
            'bom' => 'required|array',
            'bom.*.material_id' => 'required|exists:inventory_items,id',
            'bom.*.quantity' => 'required|numeric|min:0.001',
            'bom.*.unit' => 'required|string',
            'bom.*.material_type' => 'required|string',
            'bom.*.variance' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            // Update work order
            $workOrder->update([
                'customer_id' => $request->customer_id,
                'inventory_location_id' => $request->inventory_location_id,
                'product_name' => $request->product_name,
                'style' => $request->style,
                'sizes_quantities' => $request->sizes_quantities,
                'due_date' => $request->due_date,
                'requires_logo' => $request->boolean('requires_logo'),
                'notes' => $request->notes,
            ]);

            // Delete existing BOM entries and create new ones
            $workOrder->bom()->delete();
            foreach ($request->bom as $bomItem) {
                WorkOrderBom::create([
                    'work_order_id' => $workOrder->id,
                    'material_item_id' => $bomItem['material_id'],
                    'material_type' => $bomItem['material_type'],
                    'required_quantity' => $bomItem['quantity'],
                    'unit_of_measure' => $bomItem['unit'],
                    'variance_allowed' => $bomItem['variance'] ?? 5.0,
                ]);
            }

            DB::commit();

            return redirect()->route('production.work-orders.show', $encodedId)
                ->with('success', 'Work Order updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to update work order: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function advanceStage(Request $request, $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $workOrderId = !empty($decoded) ? $decoded[0] : null;

        if (!$workOrderId) {
            return response()->json(['success' => false, 'message' => 'Invalid work order ID'], 400);
        }

        $workOrder = WorkOrder::findOrFail($workOrderId);

        if (!$workOrder->canAdvanceToNextStage()) {
            return response()->json(['success' => false, 'message' => 'Cannot advance to next stage'], 400);
        }

        try {
            DB::beginTransaction();

            $currentStage = $workOrder->status;
            $nextStage = $workOrder->getNextStage();

            // Complete current process
            $currentProcess = $workOrder->getCurrentProcess();
            if ($currentProcess) {
                $currentProcess->update([
                    'completed_at' => now(),
                    'status' => 'completed',
                ]);
            }

            // Start next process
            $nextProcess = $workOrder->processes()->where('process_stage', $nextStage)->first();
            if ($nextProcess) {
                $nextProcess->update([
                    'started_at' => now(),
                    'status' => 'in_progress',
                ]);
            }

            // Update work order status
            $workOrder->update([
                'status' => $nextStage,
                'start_date' => $workOrder->start_date ?: now(),
            ]);

            if ($nextStage === WorkOrder::STATUS_DISPATCHED) {
                $workOrder->update(['completion_date' => now()]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work order advanced to ' . WorkOrder::getStatuses()[$nextStage]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function issuesMaterials(Request $request, $encodedId)
    {
        Log::info('=== MATERIAL ISSUE METHOD CALLED ===', [
            'encoded_id' => $encodedId,
            'request_method' => $request->method(),
            'request_data' => $request->all()
        ]);

        $decoded = Hashids::decode($encodedId);
        $workOrderId = !empty($decoded) ? $decoded[0] : null;

        if (!$workOrderId) {
            return redirect()->back()->with('error', 'Invalid work order ID');
        }

        $workOrder = WorkOrder::findOrFail($workOrderId);

        if ($workOrder->status !== WorkOrder::STATUS_MATERIAL_ISSUED) {
            Log::warning('Material issue attempt on work order with wrong status', [
                'work_order_id' => $workOrder->id,
                'current_status' => $workOrder->status,
                'expected_status' => WorkOrder::STATUS_MATERIAL_ISSUED
            ]);
            return redirect()->back()->with('error', 'Materials can only be issued for work orders in MATERIAL ISSUED status');
        }

        $request->validate([
            'issues' => 'required|array',
            'issues.*.material_id' => 'required|exists:inventory_items,id',
            'issues.*.quantity' => 'required|numeric|min:0.001',
            'received_by' => 'required|exists:users,id',
        ]);

        // Check if user is authenticated
        if (!Auth::check()) {
            Log::error('User not authenticated for material issue');
            return redirect()->back()->with('error', 'You must be logged in to issue materials');
        }

        Log::info('Starting material issue process', [
            'work_order_id' => $workOrder->id,
            'work_order_number' => $workOrder->work_order_number,
            'user_id' => Auth::id(),
            'issues_count' => count($request->issues),
            'issues_data' => $request->issues
        ]);

        try {
            DB::beginTransaction();
            Log::info('Database transaction started for material issue');

            foreach ($request->issues as $index => $issue) {
                Log::info("Processing issue #{$index}", [
                    'material_id' => $issue['material_id'],
                    'quantity' => $issue['quantity'],
                    'received_by' => $request->received_by
                ]);
                // Generate issue voucher number
                $voucherNumber = $this->generateIssueVoucherNumber();
                Log::info("Generated voucher number: {$voucherNumber}");

                $materialIssue = MaterialIssue::create([
                    'work_order_id' => $workOrder->id,
                    'issue_voucher_number' => $voucherNumber,
                    'material_item_id' => $issue['material_id'],
                    'lot_number' => $issue['lot_number'] ?? null,
                    'quantity_issued' => $issue['quantity'],
                    'unit_of_measure' => $issue['unit_of_measure'] ?? 'kg',
                    'issued_by' => $request->received_by,
                    'received_by' => $request->received_by,
                    'bin_location' => $issue['bin_location'] ?? null,
                    'line_location' => $issue['line_location'] ?? null,
                    'issued_at' => now(),
                    'notes' => $issue['notes'] ?? null,
                ]);

                Log::info("MaterialIssue created successfully", [
                    'material_issue_id' => $materialIssue->id,
                    'voucher_number' => $voucherNumber
                ]);

                // Create inventory movement record to reduce stock from stores
                Log::info("Creating inventory movement for material issue");
                $this->createMaterialIssueMovement($materialIssue, $issue, $request);
                Log::info("Inventory movement created successfully for issue #{$index}");
            }

            DB::commit();
            Log::info('Material issue process completed successfully', [
                'work_order_id' => $workOrder->id,
                'issues_processed' => count($request->issues)
            ]);

            return redirect()->back()->with('success', 'Materials issued successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Material issue process failed', [
                'work_order_id' => $workOrder->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return redirect()->back()->with('error', 'Failed to issue materials: ' . $e->getMessage());
        }
    }

    public function recordProduction(Request $request, $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $workOrderId = !empty($decoded) ? $decoded[0] : null;

        if (!$workOrderId) {
            return redirect()->back()->with('error', 'Invalid work order ID');
        }

        $workOrder = WorkOrder::findOrFail($workOrderId);

        $validStages = ['KNITTING', 'CUTTING', 'JOINING', 'EMBROIDERY', 'IRONING_FINISHING', 'PACKAGING'];

        if (!in_array($workOrder->status, $validStages)) {
            return redirect()->back()->with('error', 'Production can only be recorded for active production stages');
        }

        $request->validate([
            'stage' => 'required|in:' . implode(',', $validStages),
            'input_materials' => 'nullable|array',
            'output_data' => 'nullable|array',
            'wastage_data' => 'nullable|array',
            'yield_percentage' => 'nullable|numeric|min:0|max:100',
            'operator_id' => 'nullable|exists:users,id',
            'machine_id' => 'nullable|exists:production_machines,id',
            'operator_time_minutes' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        try {
            ProductionRecord::create([
                'work_order_id' => $workOrder->id,
                'stage' => $request->stage,
                'input_materials' => $request->input_materials,
                'output_data' => $request->output_data,
                'wastage_data' => $request->wastage_data,
                'yield_percentage' => $request->yield_percentage,
                'operator_id' => $request->operator_id,
                'machine_id' => $request->machine_id,
                'operator_time_minutes' => $request->operator_time_minutes,
                'notes' => $request->notes,
                'recorded_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Production record created successfully');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to record production: ' . $e->getMessage());
        }
    }

    public function qualityCheck(Request $request, $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $workOrderId = !empty($decoded) ? $decoded[0] : null;

        if (!$workOrderId) {
            return redirect()->back()->with('error', 'Invalid work order ID');
        }

        $workOrder = WorkOrder::findOrFail($workOrderId);

        if ($workOrder->status !== WorkOrder::STATUS_QC) {
            return redirect()->back()->with('error', 'Quality check can only be performed for work orders in QC status');
        }

        $request->validate([
            'result' => 'required|in:pass,fail,rework_required',
            'defect_codes' => 'nullable|array',
            'measurements' => 'nullable|array',
            'seam_strength_ok' => 'boolean',
            'logo_position_ok' => 'boolean',
            'rework_notes' => 'nullable|string',
        ]);

        try {
            QualityCheck::create([
                'work_order_id' => $workOrder->id,
                'result' => $request->result,
                'defect_codes' => $request->defect_codes,
                'measurements' => $request->measurements,
                'seam_strength_ok' => $request->boolean('seam_strength_ok', true),
                'logo_position_ok' => $request->boolean('logo_position_ok', true),
                'rework_notes' => $request->rework_notes,
                'inspector_id' => Auth::id(),
                'inspected_at' => now(),
            ]);

            if ($request->result === 'fail' || $request->result === 'rework_required') {
                // Move back to appropriate stage for rework
                $reworkStage = $request->result === 'rework_required' ? WorkOrder::STATUS_JOINING : WorkOrder::STATUS_IRONING_FINISHING;
                $workOrder->update(['status' => $reworkStage]);
            }

            return redirect()->back()->with('success', 'Quality check recorded successfully');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to record quality check: ' . $e->getMessage());
        }
    }

    public function recordPackaging(Request $request, $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $workOrderId = !empty($decoded) ? $decoded[0] : null;

        if (!$workOrderId) {
            return redirect()->back()->with('error', 'Invalid work order ID');
        }

        $workOrder = WorkOrder::findOrFail($workOrderId);

        if ($workOrder->status !== WorkOrder::STATUS_PACKAGING) {
            return redirect()->back()->with('error', 'Packaging can only be recorded for work orders in PACKAGING status');
        }

        $request->validate([
            'location_id' => 'required|exists:inventory_locations,id',
            'packaging_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.sales_price' => 'required|numeric|min:0',
            'items.*.cost_price' => 'required|numeric|min:0',
            'packed_quantities' => 'required|string',
            'carton_numbers' => 'required|string',
            'barcode_data' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Verify user has access to the selected location
            $location = auth()->user()->locations()->where('inventory_locations.id', $request->location_id)->first();
            if (!$location) {
                throw new \Exception('You do not have access to the selected location');
            }

            // Parse JSON fields
            $packedQuantities = json_decode($request->packed_quantities, true);
            $cartonNumbers = json_decode($request->carton_numbers, true);
            $barcodeData = $request->barcode_data ? json_decode($request->barcode_data, true) : null;

            if (!$packedQuantities || !$cartonNumbers) {
                throw new \Exception('Invalid JSON format for packed quantities or carton numbers');
            }

            // Create packaging record
            $packagingRecord = PackagingRecord::create([
                'work_order_id' => $workOrder->id,
                'packed_quantities' => $packedQuantities,
                'carton_numbers' => $cartonNumbers,
                'barcode_data' => $barcodeData,
                'packed_by' => Auth::id(),
                'packed_at' => $request->packaging_date,
                'notes' => $request->notes,
            ]);

            $totalInventoryValue = 0;

            // Process each item and create inventory movements
            foreach ($request->items as $itemData) {
                $item = \App\Models\Inventory\Item::findOrFail($itemData['item_id']);

                // Get current stock balance for this location
                $lastMovement = \App\Models\Inventory\Movement::where('item_id', $item->id)
                    ->where('location_id', $location->id)
                    ->orderBy('id', 'desc')
                    ->first();

                $balanceBefore = $lastMovement ? $lastMovement->balance_after : 0;
                $quantity = $itemData['quantity'];
                $balanceAfter = $balanceBefore + $quantity;
                $totalCost = $quantity * $itemData['cost_price'];
                $totalInventoryValue += $totalCost;

                // Create inventory movement record (STOCK IN)
                $movement = \App\Models\Inventory\Movement::create([
                    'branch_id' => $workOrder->branch_id,
                    'location_id' => $location->id,
                    'item_id' => $item->id,
                    'user_id' => Auth::id(),
                    'movement_type' => 'stock_in',
                    'quantity' => $quantity,
                    'unit_price' => $itemData['sales_price'],
                    'unit_cost' => $itemData['cost_price'],
                    'total_cost' => $totalCost,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'reason' => 'Production packaging completed',
                    'reference_number' => $workOrder->wo_number,
                    'reference_type' => 'work_order_packaging',
                    'reference_id' => $workOrder->id,
                    'reference' => "Work Order: {$workOrder->wo_number} - Packaging",
                    'notes' => "Packaged {$quantity} units of {$item->name} from production",
                    'movement_date' => $request->packaging_date,
                ]);

                // Update item cost and price if needed
                $item->update([
                    'cost_price' => $itemData['cost_price'],
                    'unit_price' => $itemData['sales_price'],
                ]);
            }

            // Create GL (General Ledger) transactions for inventory valuation
            $this->createPackagingGLTransactions($workOrder, $totalInventoryValue, $request->packaging_date);

            // Advance work order to next stage (DISPATCHED or COMPLETED)
            $workOrder->update(['status' => WorkOrder::STATUS_DISPATCHED]);

            DB::commit();

            return redirect()->back()->with('success', 'Packaging completed successfully! Finished goods added to inventory with proper GL entries.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to record packaging: ' . $e->getMessage());
        }
    }

    /**
     * Create GL transactions for packaging finished goods
     */
    private function createPackagingGLTransactions($workOrder, $totalInventoryValue, $packagingDate)
    {
        // Get branch_id from session with fallback
        $branchId = session('branch_id') ?? auth()->user()->branch_id ?? $workOrder->branch_id;

        // Get the inventory and WIP accounts from system settings
        $inventoryAccountId = \App\Models\SystemSetting::where('key', 'inventory_default_inventory_account')->value('value');
        $wipAccountId = \App\Models\SystemSetting::where('key', 'inventory_default_wip_account')->value('value') ??
                       \App\Models\SystemSetting::where('key', 'inventory_default_opening_balance_account')->value('value');

        if (!$inventoryAccountId || !$wipAccountId) {
            throw new \Exception('Inventory or WIP account not configured in system settings');
        }

        // Transaction 1: Debit Finished Goods Inventory (Asset increases)
        \App\Models\GlTransaction::create([
            'chart_account_id' => $inventoryAccountId,
            'amount' => $totalInventoryValue,
            'nature' => 'debit',
            'transaction_id' => $workOrder->id,
            'transaction_type' => 'production_packaging',
            'date' => $packagingDate,
            'description' => "Finished goods from production - WO: {$workOrder->wo_number}",
            'branch_id' => $branchId,
            'user_id' => Auth::id(),
        ]);

        // Transaction 2: Credit Work in Progress/Manufacturing (Asset decreases)
        \App\Models\GlTransaction::create([
            'chart_account_id' => $wipAccountId,
            'amount' => $totalInventoryValue,
            'nature' => 'credit',
            'transaction_id' => $workOrder->id,
            'transaction_type' => 'production_packaging',
            'date' => $packagingDate,
            'description' => "WIP transfer to finished goods - WO: {$workOrder->wo_number}",
            'branch_id' => $branchId,
            'user_id' => Auth::id(),
        ]);
    }

    private function generateWONumber()
    {
        $prefix = 'WO';
        $date = date('Ymd');

        $lastWO = WorkOrder::where('wo_number', 'like', $prefix . $date . '%')
            ->orderBy('wo_number', 'desc')
            ->first();

        if ($lastWO) {
            $lastNumber = intval(substr($lastWO->wo_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $date . $newNumber;
    }

    private function generateIssueVoucherNumber()
    {
        $prefix = 'IV';
        $date = date('Ymd');

        $lastIssue = MaterialIssue::where('issue_voucher_number', 'like', $prefix . $date . '%')
            ->orderBy('issue_voucher_number', 'desc')
            ->first();

        if ($lastIssue) {
            $lastNumber = intval(substr($lastIssue->issue_voucher_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $date . $newNumber;
    }

    /**
     * Create inventory movement record for material issue
     */
    private function createMaterialIssueMovement($materialIssue, $issue, $request)
    {
        Log::info('Starting createMaterialIssueMovement', [
            'material_issue_id' => $materialIssue->id,
            'material_id' => $issue['material_id'],
            'quantity' => $issue['quantity']
        ]);

        $item = Item::findOrFail($issue['material_id']);
        Log::info('Item found', [
            'item_id' => $item->id,
            'item_name' => $item->name,
            'item_company_id' => $item->company_id,
            'user_company_id' => Auth::user()->company_id
        ]);

        // Verify item belongs to user's company
        if ($item->company_id !== Auth::user()->company_id) {
            Log::error('Company mismatch for item access', [
                'item_company_id' => $item->company_id,
                'user_company_id' => Auth::user()->company_id,
                'item_name' => $item->name
            ]);
            throw new \Exception('Unauthorized access to item: ' . $item->name);
        }

        // Get current stock using the stock service
        $stockService = new InventoryStockService();
        $loginLocationId = session('location_id');
        Log::info('Getting current stock', [
            'item_id' => $item->id,
            'location_id' => $loginLocationId
        ]);

        if ($loginLocationId) {
            $currentStock = $stockService->getItemStockAtLocation($item->id, $loginLocationId);
            Log::info('Stock at specific location', [
                'location_id' => $loginLocationId,
                'current_stock' => $currentStock
            ]);
        } else {
            $currentStock = $stockService->getItemTotalStock($item->id);
            Log::info('Total stock across all locations', [
                'current_stock' => $currentStock
            ]);
        }

        // Check if sufficient stock is available
        Log::info('Stock validation', [
            'current_stock' => $currentStock,
            'required_quantity' => $issue['quantity'],
            'sufficient_stock' => $currentStock >= $issue['quantity']
        ]);

        if ($currentStock < $issue['quantity']) {
            Log::error('Insufficient stock for material issue', [
                'item_name' => $item->name,
                'available_stock' => $currentStock,
                'required_quantity' => $issue['quantity']
            ]);
            throw new \Exception('Insufficient stock for ' . $item->name . '. Available: ' . number_format($currentStock, 3) . ', Required: ' . number_format($issue['quantity'], 3));
        }

        // Calculate new stock after issue
        $newStock = $currentStock - $issue['quantity'];
        Log::info('Stock calculation', [
            'current_stock' => $currentStock,
            'quantity_to_issue' => $issue['quantity'],
            'new_stock' => $newStock
        ]);

        // Get cost information using the cost service
        Log::info('Getting cost information from cost service');
        $costService = new InventoryCostService();
        $costInfo = $costService->removeInventory(
            $item->id,
            $issue['quantity'],
            'adjustment_out',
            $materialIssue->issue_voucher_number,
            $materialIssue->issued_at->toDateString()
        );

        Log::info('Cost information retrieved', [
            'total_cost' => $costInfo['total_cost'],
            'average_unit_cost' => $costInfo['average_unit_cost']
        ]);

        // Get branch_id from session or work order
        $branchId = session('branch_id') ?? $materialIssue->workOrder->branch_id ?? Auth::user()->branch_id;

        Log::info('Creating inventory movement record', [
            'branch_id' => $branchId,
            'location_id' => $loginLocationId
        ]);

        $movement = \App\Models\Inventory\Movement::create([
            'branch_id' => $branchId,
            'location_id' => $loginLocationId,
            'item_id' => $item->id,
            'user_id' => Auth::id(),
            'movement_type' => 'adjustment_out',
            'quantity' => $issue['quantity'],
            'unit_cost' => $costInfo['average_unit_cost'],
            'total_cost' => $costInfo['total_cost'],
            'reference' => $materialIssue->issue_voucher_number,
            'reason' => 'Material issued for work order: ' . $materialIssue->workOrder->work_order_number,
            'notes' => 'Material issue - ' . ($issue['notes'] ?? ''),
            'movement_date' => $materialIssue->issued_at->toDateString(),
            'balance_before' => $currentStock,
            'balance_after' => $newStock,
        ]);

        // gl transaction to reduce the inventory value
        $this->createAdjustmentTransactions($movement, $item);

        Log::info('Inventory movement created successfully', [
            'movement_id' => $movement->id,
            'movement_type' => 'adjustment_out',
            'quantity' => $issue['quantity']
        ]);

        return $movement;
    }

    /**
     * Create double entry transactions for inventory adjustments
     */
    private function createAdjustmentTransactions($movement, $item)
    {
        // Get default accounts from system settings
        $inventoryAccountId = \App\Models\SystemSetting::where('key', 'inventory_default_inventory_account')->value('value');
        $costofgoodsSoldAccountId = \App\Models\SystemSetting::where('key', 'inventory_default_cost_account')->value('value');

        if (!$inventoryAccountId || !$costofgoodsSoldAccountId) {
            Log::warning('Default inventory account or cost of goods sold account not configured in inventory settings. Skipping GL transactions.');
            return;
        }

        // Use movement ID as the numeric transaction id to match schema
        $transactionId = $movement->id;

        // Calculate total value
        $totalValue = $movement->quantity * $movement->unit_cost;

        // Get branch_id from session or work order
        $branchId = session('branch_id') ?? $movement->branch_id ?? Auth::user()->branch_id;

        if ($movement->movement_type === 'adjustment_out') {
            // Adjustment OUT: Debit Opening Balance, Credit Inventory
            // Debit: Opening Balance Account (Equity increases)
            \App\Models\GlTransaction::create([
                'chart_account_id' => $costofgoodsSoldAccountId,
                'amount' => $totalValue,
                'nature' => 'debit',    //cost of goods sold
                'transaction_id' => $transactionId,
                'transaction_type' => 'stock_out',
                'date' => $movement->movement_date,
                'description' => "Material issued to production - {$item->name} - {$movement->reason}",
                'branch_id' => $branchId,
                'user_id' => Auth::id(),
            ]);

            // Credit: Inventory Account (Asset decreases)
            \App\Models\GlTransaction::create([
                'chart_account_id' => $inventoryAccountId,
                'amount' => $totalValue,
                'nature' => 'credit',   //inventory
                'transaction_id' => $transactionId,
                'transaction_type' => 'stock_out',
                'date' => $movement->movement_date,
                'description' => "Material issued to production - {$item->name} - {$movement->reason}",
                'branch_id' => $branchId,
                'user_id' => Auth::id(),
            ]);

            Log::info('GL transactions created for material issue', [
                'movement_id' => $movement->id,
                'total_value' => $totalValue,
                'inventory_account_id' => $inventoryAccountId,
                'costofgoodsSold_account_id' => $costofgoodsSoldAccountId
            ]);
        }
    }
}
