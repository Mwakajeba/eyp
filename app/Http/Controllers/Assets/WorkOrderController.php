<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Assets\MaintenanceRequest;
use App\Models\Assets\MaintenanceType;
use App\Models\Assets\WorkOrder;
use App\Models\Assets\WorkOrderCost;
use App\Models\Assets\MaintenanceHistory;
use App\Models\Assets\MaintenanceSetting;
use App\Models\ChartAccount;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use App\Models\Supplier;
use App\Models\Inventory\Item as InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class WorkOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    /**
     * Display a listing of work orders
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $user = Auth::user();
            $companyId = $user->company_id;
            $branchId = session('branch_id') ?? $user->branch_id;

            $workOrders = WorkOrder::forCompany($companyId)
                ->when($branchId, fn($q) => $q->forBranch($branchId))
                ->with(['asset', 'maintenanceType', 'assignedTechnician', 'vendor'])
                ->latest();

            return DataTables::of($workOrders)
                ->addColumn('wo_number_link', function ($wo) {
                    $encodedId = Hashids::encode($wo->id);
                    return '<a href="' . route('assets.work-orders.show', $encodedId) . '" class="text-primary">' . $wo->wo_number . '</a>';
                })
                ->addColumn('asset_name', function ($wo) {
                    return $wo->asset ? $wo->asset->name : 'N/A';
                })
                ->addColumn('maintenance_type_name', function ($wo) {
                    return $wo->maintenanceType ? $wo->maintenanceType->name : 'N/A';
                })
                ->addColumn('assigned_to', function ($wo) {
                    if ($wo->assignedTechnician) {
                        return $wo->assignedTechnician->name;
                    }
                    if ($wo->vendor) {
                        return $wo->vendor->name;
                    }
                    return 'Unassigned';
                })
                ->addColumn('status_badge', function ($wo) {
                    $badges = [
                        'draft' => '<span class="badge bg-secondary">Draft</span>',
                        'approved' => '<span class="badge bg-success">Approved</span>',
                        'in_progress' => '<span class="badge bg-primary">In Progress</span>',
                        'on_hold' => '<span class="badge bg-warning">On Hold</span>',
                        'completed' => '<span class="badge bg-info">Completed</span>',
                        'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
                    ];
                    return $badges[$wo->status] ?? '<span class="badge bg-secondary">' . ucfirst($wo->status) . '</span>';
                })
                ->addColumn('cost_classification_badge', function ($wo) {
                    if ($wo->status !== 'completed') {
                        return '<span class="badge bg-secondary">N/A</span>';
                    }
                    $badges = [
                        'expense' => '<span class="badge bg-warning">Expense</span>',
                        'capitalized' => '<span class="badge bg-success">Capitalized</span>',
                        'pending_review' => '<span class="badge bg-info">Pending Review</span>',
                    ];
                    return $badges[$wo->cost_classification] ?? '<span class="badge bg-secondary">' . ucfirst($wo->cost_classification) . '</span>';
                })
                ->addColumn('total_cost', function ($wo) {
                    $cost = $wo->status === 'completed' ? $wo->total_actual_cost : $wo->total_estimated_cost;
                    return 'TZS ' . number_format($cost, 2);
                })
                ->addColumn('actions', function ($wo) {
                    $actions = '';
                    $encodedId = Hashids::encode($wo->id);
                    
                    if (auth()->user()->can('view work orders')) {
                        $actions .= '<a href="' . route('assets.work-orders.show', $encodedId) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    }
                    
                    if ($wo->status === 'draft' && auth()->user()->can('approve work orders')) {
                        $actions .= '<button class="btn btn-sm btn-outline-success me-1 approve-wo" data-id="' . $encodedId . '"><i class="bx bx-check"></i></button>';
                    }
                    
                    if (in_array($wo->status, ['approved', 'in_progress']) && auth()->user()->can('execute work orders')) {
                        $actions .= '<a href="' . route('assets.work-orders.execute', $encodedId) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-wrench"></i> Execute</a>';
                    }
                    
                    if ($wo->status === 'completed' && $wo->cost_classification === 'pending_review' && auth()->user()->can('review work orders')) {
                        $actions .= '<a href="' . route('assets.work-orders.review', $encodedId) . '" class="btn btn-sm btn-outline-warning me-1"><i class="bx bx-check-circle"></i> Review</a>';
                    }
                    
                    if (auth()->user()->can('edit work orders') && in_array($wo->status, ['draft', 'approved'])) {
                        $actions .= '<a href="' . route('assets.work-orders.edit', $encodedId) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    }
                    
                    return $actions;
                })
                ->rawColumns(['wo_number_link', 'status_badge', 'cost_classification_badge', 'actions'])
                ->make(true);
        }

        return view('assets.maintenance.work-orders.index');
    }

    /**
     * Show the form for creating a new work order
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $maintenanceRequestId = null;
        $maintenanceRequest = null;
        
        if ($request->has('request_id')) {
            $requestId = Hashids::decode($request->get('request_id'))[0] ?? null;
            if ($requestId) {
                $maintenanceRequest = MaintenanceRequest::with('asset')->findOrFail($requestId);
                if ($maintenanceRequest->status !== 'approved') {
                    return redirect()->route('assets.maintenance.requests.index')
                        ->with('error', 'Only approved maintenance requests can be converted to work orders.');
                }
                $maintenanceRequestId = $requestId;
            }
        }

        $assets = Asset::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->where('status', '!=', 'disposed')
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $maintenanceTypes = MaintenanceType::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->active()
            ->orderBy('name')
            ->get();

        $approvedRequests = MaintenanceRequest::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->where('status', 'approved')
            ->whereNull('work_order_id')
            ->with('asset')
            ->get();

        $vendors = Supplier::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $technicians = \App\Models\User::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('assets.maintenance.work-orders.create', compact(
            'assets',
            'maintenanceTypes',
            'approvedRequests',
            'vendors',
            'technicians',
            'maintenanceRequest',
            'maintenanceRequestId'
        ));
    }

    /**
     * Store a newly created work order
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'maintenance_request_id' => 'nullable|exists:maintenance_requests,id',
            'asset_id' => 'required|exists:assets,id',
            'maintenance_type_id' => 'required|exists:maintenance_types,id',
            'maintenance_type' => 'required|in:preventive,corrective,major_overhaul',
            'execution_type' => 'required|in:in_house,external_vendor,mixed',
            'vendor_id' => 'nullable|required_if:execution_type,external_vendor,mixed|exists:suppliers,id',
            'assigned_technician_id' => 'nullable|required_if:execution_type,in_house,mixed|exists:users,id',
            'estimated_start_date' => 'required|date',
            'estimated_completion_date' => 'required|date|after_or_equal:estimated_start_date',
            'estimated_labor_cost' => 'nullable|numeric|min:0',
            'estimated_material_cost' => 'nullable|numeric|min:0',
            'estimated_other_cost' => 'nullable|numeric|min:0',
            'estimated_downtime_hours' => 'nullable|integer|min:0',
            'work_description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);

        // Generate WO number
        $woNumber = 'WO-' . date('Y') . '-' . str_pad(
            WorkOrder::whereYear('created_at', now()->year)->count() + 1,
            5,
            '0',
            STR_PAD_LEFT
        );

        DB::beginTransaction();
        try {
            $workOrder = WorkOrder::create([
                'company_id' => $user->company_id,
                'branch_id' => $asset->branch_id,
                'wo_number' => $woNumber,
                'maintenance_request_id' => $validated['maintenance_request_id'] ?? null,
                'asset_id' => $validated['asset_id'],
                'maintenance_type_id' => $validated['maintenance_type_id'],
                'maintenance_type' => $validated['maintenance_type'],
                'execution_type' => $validated['execution_type'],
                'vendor_id' => $validated['vendor_id'] ?? null,
                'assigned_technician_id' => $validated['assigned_technician_id'] ?? null,
                'estimated_start_date' => $validated['estimated_start_date'],
                'estimated_completion_date' => $validated['estimated_completion_date'],
                'estimated_labor_cost' => $validated['estimated_labor_cost'] ?? 0,
                'estimated_material_cost' => $validated['estimated_material_cost'] ?? 0,
                'estimated_other_cost' => $validated['estimated_other_cost'] ?? 0,
                'estimated_cost' => ($validated['estimated_labor_cost'] ?? 0) + 
                                   ($validated['estimated_material_cost'] ?? 0) + 
                                   ($validated['estimated_other_cost'] ?? 0),
                'estimated_downtime_hours' => $validated['estimated_downtime_hours'] ?? 0,
                'work_description' => $validated['work_description'] ?? null,
                'status' => 'draft',
                'cost_classification' => 'pending_review',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            // Update maintenance request if linked
            if ($workOrder->maintenance_request_id) {
                MaintenanceRequest::where('id', $workOrder->maintenance_request_id)
                    ->update([
                        'status' => 'converted_to_wo',
                        'work_order_id' => $workOrder->id,
                    ]);
            }

            DB::commit();

            return redirect()->route('assets.work-orders.show', Hashids::encode($workOrder->id))
                ->with('success', 'Work order created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating work order: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create work order: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified work order
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $workOrder = WorkOrder::with([
            'asset',
            'maintenanceType',
            'maintenanceRequest',
            'vendor',
            'assignedTechnician',
            'approvedBy',
            'completedBy',
            'reviewedBy',
            'costs',
            'glJournal'
        ])->findOrFail($id);

        return view('assets.maintenance.work-orders.show', compact('workOrder', 'encodedId'));
    }

    /**
     * Show the form for editing the specified work order
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $workOrder = WorkOrder::findOrFail($id);
        
        if (!in_array($workOrder->status, ['draft', 'approved'])) {
            return redirect()->route('assets.work-orders.show', $encodedId)
                ->with('error', 'Only draft or approved work orders can be edited.');
        }

        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $assets = Asset::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $maintenanceTypes = MaintenanceType::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->active()
            ->orderBy('name')
            ->get();

        $vendors = Supplier::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $technicians = \App\Models\User::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('assets.maintenance.work-orders.edit', compact('workOrder', 'assets', 'maintenanceTypes', 'vendors', 'technicians', 'encodedId'));
    }

    /**
     * Update the specified work order
     */
    public function update(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $workOrder = WorkOrder::findOrFail($id);
        
        if (!in_array($workOrder->status, ['draft', 'approved'])) {
            return redirect()->route('assets.work-orders.show', $encodedId)
                ->with('error', 'Only draft or approved work orders can be edited.');
        }

        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'maintenance_type_id' => 'required|exists:maintenance_types,id',
            'maintenance_type' => 'required|in:preventive,corrective,major_overhaul',
            'execution_type' => 'required|in:in_house,external_vendor,mixed',
            'vendor_id' => 'nullable|required_if:execution_type,external_vendor,mixed|exists:suppliers,id',
            'assigned_technician_id' => 'nullable|required_if:execution_type,in_house,mixed|exists:users,id',
            'estimated_start_date' => 'required|date',
            'estimated_completion_date' => 'required|date|after_or_equal:estimated_start_date',
            'estimated_labor_cost' => 'nullable|numeric|min:0',
            'estimated_material_cost' => 'nullable|numeric|min:0',
            'estimated_other_cost' => 'nullable|numeric|min:0',
            'estimated_downtime_hours' => 'nullable|integer|min:0',
            'work_description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $workOrder->update([
                'asset_id' => $validated['asset_id'],
                'maintenance_type_id' => $validated['maintenance_type_id'],
                'maintenance_type' => $validated['maintenance_type'],
                'execution_type' => $validated['execution_type'],
                'vendor_id' => $validated['vendor_id'] ?? null,
                'assigned_technician_id' => $validated['assigned_technician_id'] ?? null,
                'estimated_start_date' => $validated['estimated_start_date'],
                'estimated_completion_date' => $validated['estimated_completion_date'],
                'estimated_labor_cost' => $validated['estimated_labor_cost'] ?? 0,
                'estimated_material_cost' => $validated['estimated_material_cost'] ?? 0,
                'estimated_other_cost' => $validated['estimated_other_cost'] ?? 0,
                'estimated_cost' => ($validated['estimated_labor_cost'] ?? 0) + 
                                   ($validated['estimated_material_cost'] ?? 0) + 
                                   ($validated['estimated_other_cost'] ?? 0),
                'estimated_downtime_hours' => $validated['estimated_downtime_hours'] ?? 0,
                'work_description' => $validated['work_description'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('assets.work-orders.show', $encodedId)
                ->with('success', 'Work order updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating work order: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update work order: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Approve work order
     */
    public function approve(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid work order ID'], 404);
        }

        $workOrder = WorkOrder::findOrFail($id);
        
        if ($workOrder->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'Work order is not in draft status'], 400);
        }

        $workOrder->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Work order approved successfully.'
        ]);
    }

    /**
     * Execute work order (cost capture)
     */
    public function execute($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $workOrder = WorkOrder::with(['asset', 'costs'])->findOrFail($id);
        
        if (!in_array($workOrder->status, ['approved', 'in_progress'])) {
            return redirect()->route('assets.work-orders.show', $encodedId)
                ->with('error', 'Only approved or in-progress work orders can be executed.');
        }

        // Get inventory items for material selection
        $inventoryItems = InventoryItem::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'unit']);

        // Get suppliers for external costs
        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('assets.maintenance.work-orders.execute', compact('workOrder', 'inventoryItems', 'suppliers', 'encodedId'));
    }

    /**
     * Add cost to work order
     */
    public function addCost(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid work order ID'], 404);
        }

        $workOrder = WorkOrder::findOrFail($id);
        
        if (!in_array($workOrder->status, ['approved', 'in_progress'])) {
            return response()->json(['success' => false, 'message' => 'Work order is not in executable status'], 400);
        }

        $validated = $request->validate([
            'cost_type' => 'required|in:material,labor,other',
            'description' => 'required|string',
            'inventory_item_id' => 'nullable|required_if:cost_type,material|exists:inventory_items,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'employee_id' => 'nullable|required_if:cost_type,labor|exists:users,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'nullable|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'cost_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $totalCost = ($validated['quantity'] * $validated['unit_cost']) + ($validated['tax_amount'] ?? 0);

            $cost = WorkOrderCost::create([
                'work_order_id' => $workOrder->id,
                'cost_type' => $validated['cost_type'],
                'description' => $validated['description'],
                'inventory_item_id' => $validated['inventory_item_id'] ?? null,
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'purchase_invoice_id' => $validated['purchase_invoice_id'] ?? null,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'employee_id' => $validated['employee_id'] ?? null,
                'quantity' => $validated['quantity'],
                'unit' => $validated['unit'] ?? null,
                'unit_cost' => $validated['unit_cost'],
                'total_cost' => $validated['quantity'] * $validated['unit_cost'],
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'total_with_tax' => $totalCost,
                'cost_date' => $validated['cost_date'],
                'status' => 'actual',
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Update work order costs
            $this->updateWorkOrderCosts($workOrder);

            // Update status to in_progress if not already
            if ($workOrder->status === 'approved') {
                $workOrder->update([
                    'status' => 'in_progress',
                    'actual_start_date' => $workOrder->actual_start_date ?? now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cost added successfully.',
                'cost' => $cost
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error adding cost to work order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add cost: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete work order
     */
    public function complete(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid work order ID'], 404);
        }

        $workOrder = WorkOrder::with('costs')->findOrFail($id);
        
        if (!in_array($workOrder->status, ['approved', 'in_progress'])) {
            return response()->json(['success' => false, 'message' => 'Work order is not in executable status'], 400);
        }

        $validated = $request->validate([
            'work_performed' => 'required|string',
            'technician_notes' => 'nullable|string',
            'actual_completion_date' => 'required|date',
            'actual_downtime_hours' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Update work order costs from actual costs
            $this->updateWorkOrderCosts($workOrder);

            $workOrder->update([
                'status' => 'completed',
                'work_performed' => $validated['work_performed'],
                'technician_notes' => $validated['technician_notes'] ?? null,
                'actual_completion_date' => $validated['actual_completion_date'],
                'actual_downtime_hours' => $validated['actual_downtime_hours'] ?? 0,
                'completed_by' => Auth::id(),
                'completed_at' => now(),
                'cost_classification' => 'pending_review',
            ]);

            // Create maintenance history record
            MaintenanceHistory::create([
                'company_id' => $workOrder->company_id,
                'branch_id' => $workOrder->branch_id,
                'asset_id' => $workOrder->asset_id,
                'work_order_id' => $workOrder->id,
                'maintenance_request_id' => $workOrder->maintenance_request_id,
                'maintenance_type_id' => $workOrder->maintenance_type_id,
                'maintenance_type' => $workOrder->maintenance_type,
                'maintenance_date' => $workOrder->actual_start_date ?? $workOrder->estimated_start_date,
                'completion_date' => $validated['actual_completion_date'],
                'total_cost' => $workOrder->total_actual_cost,
                'material_cost' => $workOrder->actual_material_cost,
                'labor_cost' => $workOrder->actual_labor_cost,
                'other_cost' => $workOrder->actual_other_cost,
                'cost_classification' => 'pending_review',
                'downtime_hours' => $validated['actual_downtime_hours'] ?? 0,
                'vendor_id' => $workOrder->vendor_id,
                'technician_id' => $workOrder->assigned_technician_id,
                'work_performed' => $validated['work_performed'],
                'notes' => $validated['technician_notes'],
                'gl_posted' => false,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work order completed successfully. Awaiting cost classification review.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error completing work order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete work order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Review and classify work order costs
     */
    public function review($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $workOrder = WorkOrder::with(['asset', 'costs'])->findOrFail($id);
        
        if ($workOrder->status !== 'completed' || $workOrder->cost_classification !== 'pending_review') {
            return redirect()->route('assets.work-orders.show', $encodedId)
                ->with('error', 'Work order is not pending review.');
        }

        // Get capitalization threshold from settings
        $capitalizationThreshold = MaintenanceSetting::getSetting(
            'capitalization_threshold_amount',
            $workOrder->company_id,
            $workOrder->branch_id,
            2000000
        );

        $lifeExtensionThreshold = MaintenanceSetting::getSetting(
            'capitalization_life_extension_months',
            $workOrder->company_id,
            $workOrder->branch_id,
            12
        );

        return view('assets.maintenance.work-orders.review', compact('workOrder', 'capitalizationThreshold', 'lifeExtensionThreshold', 'encodedId'));
    }

    /**
     * Classify and post work order costs
     */
    public function classify(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid work order ID'], 404);
        }

        $workOrder = WorkOrder::with(['asset', 'costs'])->findOrFail($id);
        
        if ($workOrder->status !== 'completed' || $workOrder->cost_classification !== 'pending_review') {
            return response()->json(['success' => false, 'message' => 'Work order is not pending review'], 400);
        }

        $validated = $request->validate([
            'cost_classification' => 'required|in:expense,capitalized',
            'is_capital_improvement' => 'boolean',
            'life_extension_months' => 'nullable|integer|min:0',
            'review_notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $workOrder->update([
                'cost_classification' => $validated['cost_classification'],
                'is_capital_improvement' => $validated['is_capital_improvement'] ?? false,
                'life_extension_months' => $validated['life_extension_months'] ?? null,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'review_notes' => $validated['review_notes'] ?? null,
            ]);

            // Post to GL
            $this->postToGL($workOrder);

            // Update asset if capitalized
            if ($validated['cost_classification'] === 'capitalized') {
                $this->updateAssetCost($workOrder);
            }

            // Update maintenance history
            MaintenanceHistory::where('work_order_id', $workOrder->id)
                ->update([
                    'cost_classification' => $validated['cost_classification'],
                    'capitalized_amount' => $validated['cost_classification'] === 'capitalized' ? $workOrder->total_actual_cost : 0,
                    'life_extension_months' => $validated['life_extension_months'] ?? null,
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Work order classified and posted to GL successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error classifying work order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to classify work order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update work order costs from actual costs
     */
    private function updateWorkOrderCosts(WorkOrder $workOrder)
    {
        $actualCosts = $workOrder->costs()->where('status', 'actual')->get();
        
        $laborCost = $actualCosts->where('cost_type', 'labor')->sum('total_with_tax');
        $materialCost = $actualCosts->where('cost_type', 'material')->sum('total_with_tax');
        $otherCost = $actualCosts->where('cost_type', 'other')->sum('total_with_tax');
        $totalCost = $laborCost + $materialCost + $otherCost;

        $workOrder->update([
            'actual_labor_cost' => $laborCost,
            'actual_material_cost' => $materialCost,
            'actual_other_cost' => $otherCost,
            'actual_cost' => $totalCost,
        ]);
    }

    /**
     * Post work order to GL
     */
    private function postToGL(WorkOrder $workOrder)
    {
        if ($workOrder->gl_posted) {
            return; // Already posted
        }

        $user = Auth::user();
        $companyId = $workOrder->company_id;
        $branchId = $workOrder->branch_id;

        // Get GL accounts from settings
        $expenseAccountId = MaintenanceSetting::getSetting('maintenance_expense_account', $companyId, $branchId);
        $wipAccountId = MaintenanceSetting::getSetting('maintenance_wip_account', $companyId, $branchId);
        $capitalizationAccountId = MaintenanceSetting::getSetting('asset_capitalization_account', $companyId, $branchId);

        if (!$expenseAccountId || !$wipAccountId) {
            throw new \Exception('Maintenance GL accounts are not configured. Please set up maintenance settings.');
        }

        // Create journal
        $journal = Journal::create([
            'date' => $workOrder->completed_at ?? now(),
            'reference' => $workOrder->wo_number,
            'reference_type' => 'work_order',
            'description' => 'Maintenance Work Order: ' . $workOrder->wo_number . ' - ' . ($workOrder->cost_classification === 'capitalized' ? 'Capitalized' : 'Expense'),
            'branch_id' => $branchId,
            'user_id' => $user->id,
        ]);

        if ($workOrder->cost_classification === 'capitalized') {
            // Capitalized: Dr. Asset, Cr. Maintenance WIP
            if (!$capitalizationAccountId) {
                throw new \Exception('Asset capitalization account is not configured.');
            }

            // Debit: Asset account (from asset category)
            $assetAccountId = $workOrder->asset->category->asset_account_id ?? null;
            if (!$assetAccountId) {
                throw new \Exception('Asset account is not configured for this asset category.');
            }

            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $assetAccountId,
                'amount' => $workOrder->total_actual_cost,
                'nature' => 'debit',
                'description' => 'Maintenance Capitalization - ' . $workOrder->wo_number,
            ]);

            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $wipAccountId,
                'amount' => $workOrder->total_actual_cost,
                'nature' => 'credit',
                'description' => 'Maintenance WIP - ' . $workOrder->wo_number,
            ]);

            // Create GL transactions
            GlTransaction::create([
                'chart_account_id' => $assetAccountId,
                'asset_id' => $workOrder->asset_id,
                'amount' => $workOrder->total_actual_cost,
                'nature' => 'debit',
                'transaction_id' => $journal->id,
                'transaction_type' => 'work_order',
                'date' => $journal->date,
                'description' => 'Maintenance Capitalization - ' . $workOrder->wo_number,
                'branch_id' => $branchId,
                'user_id' => $user->id,
            ]);

            GlTransaction::create([
                'chart_account_id' => $wipAccountId,
                'asset_id' => $workOrder->asset_id,
                'amount' => $workOrder->total_actual_cost,
                'nature' => 'credit',
                'transaction_id' => $journal->id,
                'transaction_type' => 'work_order',
                'date' => $journal->date,
                'description' => 'Maintenance WIP - ' . $workOrder->wo_number,
                'branch_id' => $branchId,
                'user_id' => $user->id,
            ]);
        } else {
            // Expense: Dr. Maintenance Expense, Cr. Maintenance WIP
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $expenseAccountId,
                'amount' => $workOrder->total_actual_cost,
                'nature' => 'debit',
                'description' => 'Maintenance Expense - ' . $workOrder->wo_number,
            ]);

            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $wipAccountId,
                'amount' => $workOrder->total_actual_cost,
                'nature' => 'credit',
                'description' => 'Maintenance WIP - ' . $workOrder->wo_number,
            ]);

            // Create GL transactions
            GlTransaction::create([
                'chart_account_id' => $expenseAccountId,
                'asset_id' => $workOrder->asset_id,
                'amount' => $workOrder->total_actual_cost,
                'nature' => 'debit',
                'transaction_id' => $journal->id,
                'transaction_type' => 'work_order',
                'date' => $journal->date,
                'description' => 'Maintenance Expense - ' . $workOrder->wo_number,
                'branch_id' => $branchId,
                'user_id' => $user->id,
            ]);

            GlTransaction::create([
                'chart_account_id' => $wipAccountId,
                'asset_id' => $workOrder->asset_id,
                'amount' => $workOrder->total_actual_cost,
                'nature' => 'credit',
                'transaction_type' => 'work_order',
                'transaction_id' => $journal->id,
                'date' => $journal->date,
                'description' => 'Maintenance WIP - ' . $workOrder->wo_number,
                'branch_id' => $branchId,
                'user_id' => $user->id,
            ]);
        }

        $workOrder->update([
            'gl_posted' => true,
            'gl_journal_id' => $journal->id,
            'gl_posted_at' => now(),
        ]);

        MaintenanceHistory::where('work_order_id', $workOrder->id)
            ->update([
                'gl_posted' => true,
                'gl_journal_id' => $journal->id,
                'gl_posted_at' => now(),
            ]);
    }

    /**
     * Update asset cost after capitalization
     */
    private function updateAssetCost(WorkOrder $workOrder)
    {
        $asset = $workOrder->asset;
        
        // Update asset purchase cost
        $newCost = $asset->purchase_cost + $workOrder->total_actual_cost;
        $asset->update([
            'purchase_cost' => $newCost,
        ]);

        // If life extension is specified, update useful life
        if ($workOrder->life_extension_months && $workOrder->life_extension_months > 0) {
            // This would require updating the asset category or asset-specific useful life
            // For now, we'll log it in the asset notes or create a separate tracking mechanism
            \Log::info("Asset {$asset->id} life extended by {$workOrder->life_extension_months} months due to maintenance capitalization");
        }

        // Note: Depreciation recalculation would be handled by the depreciation module
        // when the next depreciation run occurs
    }

    /**
     * Remove the specified work order
     */
    public function destroy($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $workOrder = WorkOrder::findOrFail($id);
        
        if ($workOrder->status !== 'draft') {
            return redirect()->route('assets.work-orders.index')
                ->with('error', 'Only draft work orders can be deleted.');
        }

        $workOrder->delete();

        return redirect()->route('assets.work-orders.index')
            ->with('success', 'Work order deleted successfully.');
    }
}
