<?php

namespace App\Http\Controllers;

use App\Models\StoreIssue;
use App\Models\StoreRequisition;
use App\Models\StoreRequisitionItem;
use App\Models\Product;
use App\Models\Inventory\Movement;
use App\Models\GlTransaction;
use App\Models\SystemSetting;
use App\Models\Inventory\Item;
use App\Services\InventoryStockService;
use App\Services\InventoryCostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use DataTables;
use Carbon\Carbon;

class StoreIssueController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of store issues
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getIssuesDataTable($request);
        }

        return view('store_requisitions.issues.index');
    }

    /**
     * DataTable for store issues
     */
    private function getIssuesDataTable(Request $request)
    {
        $query = StoreIssue::with(['storeRequisition', 'issuedTo', 'issuedBy', 'branch'])
            ->where('company_id', Auth::user()->company_id);

        // Apply filters
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('issue_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('issue_date', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addColumn('action', function ($issue) {
                $actions = '<div class="btn-group" role="group">';
                
                $actions .= '<a href="' . route('store-issues.show', $issue->id) . '" class="btn btn-sm btn-info" title="View Details">
                    <i class="bx bx-show"></i>
                </a>';

                $actions .= '</div>';
                return $actions;
            })
            ->addColumn('status_badge', function ($issue) {
                return $issue->status_badge;
            })
            ->addColumn('requisition_number', function ($issue) {
                return $issue->storeRequisition ? $issue->storeRequisition->requisition_number : 'N/A';
            })
            ->addColumn('issued_to_name', function ($issue) {
                return $issue->issuedTo ? $issue->issuedTo->name : 'N/A';
            })
            ->addColumn('issued_by_name', function ($issue) {
                return $issue->issuedBy ? $issue->issuedBy->name : 'N/A';
            })
            ->editColumn('issue_date', function ($issue) {
                return $issue->issue_date ? $issue->issue_date->format('Y-m-d') : '';
            })
            ->editColumn('total_amount', function ($issue) {
                return 'TZS ' . number_format($issue->total_amount, 2);
            })
            ->rawColumns(['action', 'status_badge'])
            ->make(true);
    }

    /**
     * Show the form for creating a new store issue
     */
    public function create(Request $request)
    {
        $requisitionId = $request->get('requisition');
        
        if (!$requisitionId) {
            return redirect()->route('store-requisitions.requisitions.index')
                ->with('error', 'Please select a requisition to issue items for.');
        }

        // Handle both hash_id and regular id
        try {
            if (is_numeric($requisitionId)) {
                $requisition = StoreRequisition::with(['items.product', 'requestedBy'])
                    ->where('company_id', Auth::user()->company_id)
                    ->findOrFail($requisitionId);
            } else {
                // Assuming it's a hash_id
                $requisition = StoreRequisition::with(['items.product', 'requestedBy'])
                    ->where('company_id', Auth::user()->company_id)
                    ->get()
                    ->first(function ($item) use ($requisitionId) {
                        return $item->hash_id === $requisitionId;
                    });
                
                if (!$requisition) {
                    throw new \Exception('Requisition not found');
                }
            }
        } catch (\Exception $e) {
            return redirect()->route('store-requisitions.requisitions.index')
                ->with('error', 'Requisition not found.');
        }

        if (!$requisition->canBeIssued()) {
            return redirect()->route('store-requisitions.requisitions.show', $requisition->hash_id)
                ->with('error', 'This requisition cannot be issued. Status: ' . $requisition->status);
        }

        return view('store_requisitions.issues.create', compact('requisition'));
    }

    /**
     * Get user assigned locations
     */
    public function getUserAssignedLocations()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;
        
        $locations = \DB::table('inventory_locations')
            ->join('branches', 'inventory_locations.branch_id', '=', 'branches.id')
            ->where('inventory_locations.company_id', $user->company_id)
            ->where('inventory_locations.branch_id', $branchId)
            ->select(
                'inventory_locations.id',
                'inventory_locations.name',
                'inventory_locations.branch_id',
                'branches.name as branch_name'
            )
            ->get();

        return response()->json($locations);
    }

    /**
     * Get item stock at specific location
     */
    public function getItemStockAtLocation(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer',
            'location_id' => 'required|integer'
        ]);

        try {
            $stockService = app(InventoryStockService::class);
            $stock = $stockService->getItemStockAtLocation($request->item_id, $request->location_id);
            
            return response()->json([
                'stock' => $stock,
                'item_id' => $request->item_id,
                'location_id' => $request->location_id
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get item stock at location', [
                'item_id' => $request->item_id,
                'location_id' => $request->location_id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'stock' => 0,
                'error' => 'Failed to get stock information'
            ], 500);
        }
    }

    /**
     * Test the form submission
     */
    public function test(Request $request)
    {
        \Log::info('Test method called', ['request_data' => $request->all()]);
        return response()->json(['status' => 'success', 'message' => 'Form submission test successful']);
    }

    /**
     * Store a newly created store issue
     */
    public function store(Request $request)
    {
        \Log::info('Store Issue Creation Started', ['request_data' => $request->all()]);
        
        // Add a simple check to confirm we're getting data
        if (!$request->has('store_requisition_id')) {
            \Log::error('No store_requisition_id in request');
            return back()->withInput()->withErrors(['error' => 'Missing requisition ID in request.']);
        }

        try {
            $request->validate([
                'store_requisition_id' => 'required|exists:store_requisitions,id',
                'issued_to' => 'required|exists:users,id',
                'location_id' => 'required|exists:inventory_locations,id',
                'issue_date' => 'required|date|before_or_equal:today',
                'description' => 'nullable|string|max:500',
                'remarks' => 'nullable|string|max:500',
                'items' => 'required|array|min:1',
                'items.*.requisition_item_id' => 'required|exists:store_requisition_items,id',
                'items.*.quantity_issued' => 'nullable|numeric|min:0.01',
            ], [
                'location_id.required' => 'Please select a location from which to issue items.',
                'location_id.exists' => 'Selected location is invalid.',
                'items.required' => 'At least one item must be issued.',
                'items.min' => 'At least one item must be issued.',
                'issued_to.required' => 'Issued to field is required.',
                'issued_to.exists' => 'Selected employee is invalid.',
                'items.*.requisition_item_id.required' => 'Item ID is required.',
                'items.*.quantity_issued.numeric' => 'Quantity issued must be a valid number.',
                'items.*.quantity_issued.min' => 'Quantity issued must be greater than 0.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed', ['errors' => $e->errors()]);
            return back()->withInput()->withErrors($e->errors());
        }

        \Log::info('Store Issue Validation Passed');

        $user = Auth::user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);
        
        \Log::info('User and branch info', ['user_id' => $user->id, 'branch_id' => $branchId]);
        
        if (!$branchId) {
            \Log::error('No branch ID found for store issue creation');
            return back()->withInput()->withErrors(['error' => 'Please select a branch before creating a store issue.']);
        }

        try {
            $requisition = StoreRequisition::with(['items', 'requestedBy'])
                ->where('company_id', $user->company_id)
                ->findOrFail($request->store_requisition_id);
        } catch (\Exception $e) {
            \Log::error('Failed to find requisition', ['requisition_id' => $request->store_requisition_id, 'error' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Requisition not found.']);
        }

        if (!$requisition->canBeIssued()) {
            \Log::error('Requisition cannot be issued', ['requisition_id' => $requisition->id, 'status' => $requisition->status]);
            return back()->withInput()->withErrors(['error' => 'This requisition cannot be issued. Status: ' . $requisition->status]);
        }

        // Validate quantities don't exceed requested amounts and check stock availability
        $selectedLocationId = $request->location_id;
        
        // Get all locations assigned to the user
        $userLocations = \DB::table('inventory_locations')
            ->where('company_id', $user->company_id)
            ->where('branch_id', $branchId)
            ->get(['id', 'name']);
        
        $userLocationIds = $userLocations->pluck('id')->toArray();
        
        \Log::info('User assigned locations', [
            'user_id' => $user->id,
            'locations' => $userLocationIds,
            'selected_location_id' => $selectedLocationId
        ]);
        
        // Verify the location belongs to user's assigned locations
        if (!in_array($selectedLocationId, $userLocationIds)) {
            \Log::error('Invalid location selected for store issue', [
                'location_id' => $selectedLocationId,
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'user_locations' => $userLocationIds
            ]);
            return back()->withInput()->withErrors([
                'error' => 'Selected location is not assigned to your user account.'
            ]);
        }
        
        // Get the selected location details
        $location = \DB::table('inventory_locations')
            ->where('id', $selectedLocationId)
            ->where('company_id', $user->company_id)
            ->where('branch_id', $branchId)
            ->first();
        
        if (!$location) {
            \Log::error('Location not found', [
                'location_id' => $selectedLocationId,
                'user_id' => $user->id,
                'branch_id' => $branchId
            ]);
            return back()->withInput()->withErrors([
                'error' => 'Selected location is not valid for your branch.'
            ]);
        }

        $stockService = app(InventoryStockService::class);

        // Separate items into issued and pending
        $itemsToIssue = [];
        $itemsWithInsufficientStock = [];

        foreach ($request->items as $item) {
            // Skip items with no quantity
            if (empty($item['quantity_issued']) || $item['quantity_issued'] == 0) {
                continue;
            }

            $requisitionItem = StoreRequisitionItem::with('product')->find($item['requisition_item_id']);
            
            if (!$requisitionItem || $requisitionItem->store_requisition_id != $requisition->id) {
                \Log::error('Invalid item selected', ['item_id' => $item['requisition_item_id']]);
                return back()->withInput()->withErrors(['error' => 'Invalid item selected.']);
            }

            $remainingQuantity = $requisitionItem->quantity_approved - $requisitionItem->quantity_issued;
            
            if ($item['quantity_issued'] > $remainingQuantity) {
                \Log::error('Quantity issued exceeds remaining quantity', [
                    'item_id' => $requisitionItem->id,
                    'quantity_issued' => $item['quantity_issued'],
                    'remaining_quantity' => $remainingQuantity
                ]);
                return back()->withInput()->withErrors([
                    'error' => "Quantity issued for {$requisitionItem->product->name} cannot exceed remaining quantity: {$remainingQuantity}"
                ]);
            }

            // Check stock availability at selected location
            try {
                $currentStock = $stockService->getItemStockAtLocation($requisitionItem->inventory_item_id, $selectedLocationId);
                
                if ($currentStock >= $item['quantity_issued']) {
                    // Stock available - mark for issuance
                    $itemsToIssue[] = [
                        'requisition_item' => $requisitionItem,
                        'quantity_issued' => $item['quantity_issued'],
                        'available_stock' => $currentStock
                    ];
                    
                    \Log::info('Item marked for issuance', [
                        'item_name' => $requisitionItem->product->name,
                        'quantity_to_issue' => $item['quantity_issued'],
                        'available_stock' => $currentStock
                    ]);
                } else {
                    // Insufficient stock - mark for pending
                    $itemsWithInsufficientStock[] = [
                        'requisition_item' => $requisitionItem,
                        'quantity_requested' => $item['quantity_issued'],
                        'available_stock' => $currentStock,
                        'shortfall' => $item['quantity_issued'] - $currentStock
                    ];
                    
                    \Log::warning('Item marked as insufficient stock', [
                        'item_name' => $requisitionItem->product->name,
                        'quantity_requested' => $item['quantity_issued'],
                        'available_stock' => $currentStock,
                        'shortfall' => $item['quantity_issued'] - $currentStock
                    ]);
                }

            } catch (\Exception $e) {
                \Log::error('Failed to validate stock', [
                    'item_id' => $requisitionItem->id,
                    'location_id' => $selectedLocationId,
                    'error' => $e->getMessage()
                ]);
                return back()->withInput()->withErrors([
                    'error' => "Unable to verify stock availability for {$requisitionItem->product->name}. Please try again."
                ]);
            }
        }

        // Check if at least one item has a quantity > 0
        if (empty($itemsToIssue) && empty($itemsWithInsufficientStock)) {
            \Log::warning('No items with quantities provided for store issue');
            return back()->withInput()->withErrors([
                'error' => 'Please enter at least one item quantity to issue.'
            ]);
        }

        // Proceed if there are items to issue (even if some are insufficient)
        if (empty($itemsToIssue) && !empty($itemsWithInsufficientStock)) {
            $itemNames = implode(', ', array_map(fn($item) => $item['requisition_item']->product->name, $itemsWithInsufficientStock));
            \Log::warning('No items available to issue', ['items' => $itemNames]);
            return back()->withInput()->withErrors([
                'error' => "Insufficient stock for: {$itemNames}. No items can be issued."
            ]);
        }

        try {
            DB::beginTransaction();

            // Generate voucher number
            $voucherNo = $this->generateVoucherNumber($user->company_id, $branchId);
            \Log::info('Generated voucher number', ['voucher_no' => $voucherNo]);

            // Calculate total amount for items being issued
            $totalAmount = 0;
            foreach ($itemsToIssue as $item) {
                $totalAmount += $item['requisition_item']->unit_cost * $item['quantity_issued'];
            }

            \Log::info('Calculated total amount', ['total_amount' => $totalAmount]);

            // Create store issue (even if partial)
            $storeIssue = StoreIssue::create([
                'company_id' => $user->company_id,
                'branch_id' => $branchId,
                'store_requisition_id' => $requisition->id,
                'voucher_no' => $voucherNo,
                'issue_date' => $request->issue_date,
                'issued_to' => $request->issued_to,
                'issued_by' => $user->id,
                'total_amount' => $totalAmount,
                'description' => $request->description,
                'remarks' => $request->remarks,
                'status' => 'issued',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            \Log::info('Store issue created successfully', ['store_issue_id' => $storeIssue->id]);

            // Process items that have sufficient stock
            $allItemsFullyIssued = true;
            foreach ($itemsToIssue as $item) {
                $requisitionItem = $item['requisition_item'];
                $quantityIssued = $item['quantity_issued'];
                
                $newQuantityIssued = $requisitionItem->quantity_issued + $quantityIssued;
                
                // Determine status: fully_issued if all approved quantity has been issued, otherwise partially_issued
                $itemStatus = ($newQuantityIssued >= $requisitionItem->quantity_approved) ? 'fully_issued' : 'partially_issued';
                
                $requisitionItem->update([
                    'quantity_issued' => $newQuantityIssued,
                    'status' => $itemStatus
                ]);

                // Check if item is fully issued
                if ($newQuantityIssued < $requisitionItem->quantity_approved) {
                    $allItemsFullyIssued = false;
                }

                // Create inventory movement for adjustment_out
                try {
                    $this->createInventoryMovement(
                        $requisitionItem,
                        $quantityIssued,
                        $storeIssue,
                        $user,
                        $branchId
                    );
                } catch (\Exception $e) {
                    \Log::error("Failed to create inventory movement for item {$requisitionItem->id}: " . $e->getMessage());
                    // Continue processing other items even if one fails
                }
            }

            // Mark items with insufficient stock as pending (keep as pending for later issuance)
            foreach ($itemsWithInsufficientStock as $item) {
                $requisitionItem = $item['requisition_item'];
                
                // Mark status as insufficient_stock to indicate why item is pending
                $requisitionItem->update([
                    'status' => 'insufficient_stock'
                ]);
                
                \Log::warning('Item marked as insufficient_stock for later issuance', [
                    'item_id' => $requisitionItem->id,
                    'item_name' => $requisitionItem->product->name,
                    'available_stock' => $item['available_stock'],
                    'requested_quantity' => $item['quantity_requested'],
                    'shortfall' => $item['shortfall']
                ]);
                
                $allItemsFullyIssued = false;
            }

            // Determine requisition status
            if ($allItemsFullyIssued && empty($itemsWithInsufficientStock)) {
                // All items fully issued
                $newRequisitionStatus = 'fully_issued';
            } elseif (!empty($itemsToIssue)) {
                // Partial issuance (some issued, some insufficient)
                $newRequisitionStatus = 'partially_issued';
            } else {
                // No items issued (shouldn't reach here due to earlier check)
                $newRequisitionStatus = 'approved';
            }

            // Update requisition status
            $requisition->update([
                'status' => $newRequisitionStatus,
                'issue_voucher_id' => $storeIssue->id,
                'updated_by' => $user->id,
            ]);

            DB::commit();

            // Prepare success message
            $message = "Store issue created successfully. Voucher No: {$voucherNo}";
            if (!empty($itemsWithInsufficientStock)) {
                $insufficientItems = implode(', ', array_map(fn($item) => 
                    "{$item['requisition_item']->product->name} (Need: {$item['quantity_requested']}, Have: {$item['available_stock']}, Short: {$item['shortfall']})",
                    $itemsWithInsufficientStock
                ));
                $message .= "\n\nNote: The following items have insufficient stock and are marked for later issuance:\n{$insufficientItems}";
            }

            \Log::info('Store issue creation completed successfully', [
                'store_issue_id' => $storeIssue->id,
                'voucher_no' => $voucherNo,
                'items_issued' => count($itemsToIssue),
                'items_insufficient' => count($itemsWithInsufficientStock)
            ]);

            return redirect()->route('store-issues.show', $storeIssue->id)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Store Issue Creation Failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->withErrors(['error' => 'Failed to create store issue. Please try again. Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified store issue
     */
    public function show(StoreIssue $storeIssue)
    {
        $storeIssue->load([
            'storeRequisition.items.product', 
            'storeRequisition.requestedBy',
            'issuedTo', 
            'issuedBy', 
            'branch',
            'returns',
            'movements.item'
        ]);

        return view('store_requisitions.issues.show', compact('storeIssue'));
    }

    /**
     * Show the form for editing the specified resource
     */
    public function edit(StoreIssue $storeIssue)
    {
        if ($storeIssue->status !== 'issued') {
            return redirect()->route('store-issues.show', $storeIssue->id)
                ->with('error', 'Only issued vouchers can be edited.');
        }

        $storeIssue->load(['storeRequisition.items.product', 'storeRequisition.requestedBy']);

        return view('store_requisitions.issues.edit', compact('storeIssue'));
    }

    /**
     * Update the specified store issue
     */
    public function update(Request $request, StoreIssue $storeIssue)
    {
        if ($storeIssue->status !== 'issued') {
            return redirect()->route('store-issues.show', $storeIssue->id)
                ->with('error', 'Only issued vouchers can be updated.');
        }

        $request->validate([
            'issue_date' => 'required|date|before_or_equal:today',
            'description' => 'nullable|string|max:500',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $storeIssue->update([
                'issue_date' => $request->issue_date,
                'description' => $request->description,
                'remarks' => $request->remarks,
                'updated_by' => Auth::id(),
            ]);

            return redirect()->route('store-issues.show', $storeIssue->id)
                ->with('success', 'Store issue updated successfully.');

        } catch (\Exception $e) {
            \Log::error('Store Issue Update Failed: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Failed to update store issue. Please try again.']);
        }
    }

    /**
     * Cancel a store issue
     */
    public function cancel(Request $request, StoreIssue $storeIssue)
    {
        if ($storeIssue->status !== 'issued') {
            return response()->json([
                'success' => false,
                'message' => 'Only issued vouchers can be cancelled.'
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|min:5|max:500'
        ]);

        try {
            DB::beginTransaction();

            // Reverse the issued quantities on requisition items
            $requisitionItems = $storeIssue->storeRequisition->items;
            foreach ($requisitionItems as $item) {
                // Note: In a real implementation, you'd need to track which specific quantities 
                // were issued in this voucher to reverse them accurately
                $item->update([
                    'quantity_issued' => 0 // Simplified - should be more precise
                ]);
            }

            // Update requisition status back to approved
            $storeIssue->storeRequisition->update([
                'status' => 'approved',
                'issue_voucher_id' => null,
                'updated_by' => Auth::id(),
            ]);

            // Cancel the issue
            $storeIssue->update([
                'status' => 'cancelled',
                'remarks' => $request->reason,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Store issue cancelled successfully.',
                'status' => $storeIssue->status
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Store Issue Cancellation Failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel store issue. Please try again.'
            ], 500);
        }
    }

    /**
     * Generate voucher number for store issue
     */
    private function generateVoucherNumber($companyId, $branchId)
    {
        $prefix = 'SI-' . date('Y') . '-';
        $lastIssue = StoreIssue::where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->where('voucher_no', 'like', $prefix . '%')
            ->orderBy('voucher_no', 'desc')
            ->first();

        if ($lastIssue) {
            $lastNumber = (int) substr($lastIssue->voucher_no, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get store issues statistics
     */
    public function getStatistics()
    {
        try {
            $companyId = Auth::user()->company_id;
            
            $statistics = [
                'total' => StoreIssue::where('company_id', $companyId)->count(),
                'pending' => StoreIssue::where('company_id', $companyId)->where('status', 'pending')->count(),
                'issued' => StoreIssue::where('company_id', $companyId)->where('status', 'issued')->count(),
                'cancelled' => StoreIssue::where('company_id', $companyId)->where('status', 'cancelled')->count(),
            ];

            return response()->json($statistics);
        } catch (\Exception $e) {
            \Log::error('Failed to get store issues statistics: ' . $e->getMessage());
            return response()->json([
                'total' => 0,
                'pending' => 0,
                'issued' => 0,
                'cancelled' => 0,
            ], 500);
        }
    }

    /**
     * Create inventory movement for store issue
     */
    private function createInventoryMovement($requisitionItem, $quantityIssued, $storeIssue, $user, $branchId)
    {
        try {
            // Get the inventory item
            $inventoryItem = Item::find($requisitionItem->inventory_item_id);
            if (!$inventoryItem) {
                \Log::warning("Inventory item not found for requisition item: {$requisitionItem->id}");
                return;
            }

            // Verify item belongs to user's company
            if ($inventoryItem->company_id !== $user->company_id) {
                \Log::error('Company mismatch for item access', [
                    'item_company_id' => $inventoryItem->company_id,
                    'user_company_id' => $user->company_id,
                    'item_name' => $inventoryItem->name
                ]);
                throw new \Exception('Unauthorized access to item: ' . $inventoryItem->name);
            }

            // Get current stock using the stock service
            $stockService = new InventoryStockService();
            $locationId = session('location_id');
            
            if (!$locationId) {
                // Find the main warehouse for user's branch as default
                $defaultLocation = \DB::table('inventory_locations')
                    ->where('company_id', $user->company_id)
                    ->where('branch_id', $branchId)
                    ->where('name', 'LIKE', '%Main%')
                    ->first();
                
                if (!$defaultLocation) {
                    // Fallback to any location in the user's branch
                    $defaultLocation = \DB::table('inventory_locations')
                        ->where('company_id', $user->company_id)
                        ->where('branch_id', $branchId)
                        ->first();
                }
                
                if (!$defaultLocation) {
                    \Log::error('No location available for inventory movement', [
                        'user_id' => $user->id,
                        'branch_id' => $branchId,
                        'item_name' => $inventoryItem->name
                    ]);
                    throw new \Exception('No inventory location available for your branch. Please contact your administrator.');
                }
                
                $locationId = $defaultLocation->id;
            }
            
            \Log::info('Getting current stock for store issue', [
                'item_id' => $inventoryItem->id,
                'location_id' => $locationId,
                'quantity_to_issue' => $quantityIssued
            ]);

            $currentStock = $stockService->getItemStockAtLocation($inventoryItem->id, $locationId);

            // Check if sufficient stock is available at the specific location
            if ($currentStock < $quantityIssued) {
                \Log::error('Insufficient stock for store issue at location', [
                    'item_name' => $inventoryItem->name,
                    'location_id' => $locationId,
                    'available_stock' => $currentStock,
                    'required_quantity' => $quantityIssued
                ]);
                throw new \Exception('Insufficient stock for ' . $inventoryItem->name . ' at location ID ' . $locationId . '. Available: ' . number_format($currentStock, 3) . ', Required: ' . number_format($quantityIssued, 3));
            }

            // Calculate new stock after issue
            $newStock = $currentStock - $quantityIssued;

            // Get cost information using the cost service
            \Log::info('Getting cost information from cost service');
            $costService = new InventoryCostService();
            $costInfo = $costService->removeInventory(
                $inventoryItem->id,
                $quantityIssued,
                'adjustment_out',
                $storeIssue->voucher_no,
                $storeIssue->issue_date->toDateString()
            );

            \Log::info('Cost information retrieved for store issue', [
                'total_cost' => $costInfo['total_cost'],
                'average_unit_cost' => $costInfo['average_unit_cost']
            ]);

            // Create the movement record
            $movement = Movement::create([
                'branch_id' => $branchId,
                'location_id' => $locationId,
                'item_id' => $inventoryItem->id,
                'user_id' => $user->id,
                'movement_type' => 'adjustment_out',
                'quantity' => $quantityIssued,
                'unit_cost' => $costInfo['average_unit_cost'],
                'total_cost' => $costInfo['total_cost'],
                'balance_before' => $currentStock,
                'balance_after' => $newStock,
                'reason' => 'Store Issue',
                'reference_number' => $storeIssue->voucher_no,
                'reference_type' => 'store_issue',
                'reference_id' => $storeIssue->id,
                'reference' => "Store Issue: {$storeIssue->voucher_no}",
                'notes' => "Items issued for store requisition: {$storeIssue->storeRequisition->requisition_number}",
                'movement_date' => $storeIssue->issue_date,
            ]);

            // Create GL transactions for the inventory movement
            $this->createAdjustmentTransactions($movement, $inventoryItem, $storeIssue, $user, $branchId);

            \Log::info("Inventory movement created for store issue", [
                'movement_id' => $movement->id,
                'item_id' => $inventoryItem->id,
                'quantity' => $quantityIssued,
                'unit_cost' => $costInfo['average_unit_cost'],
                'total_cost' => $costInfo['total_cost']
            ]);

            return $movement;

        } catch (\Exception $e) {
            \Log::error("Failed to create inventory movement: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create double entry transactions for inventory adjustments
     */
    private function createAdjustmentTransactions($movement, $item, $storeIssue, $user, $branchId)
    {
        try {
            // Get default accounts from system settings
            $inventoryAccountId = SystemSetting::getValue('inventory_default_inventory_account');
            $costAccountId = SystemSetting::getValue('inventory_default_cost_account');

            if (!$inventoryAccountId || !$costAccountId) {
                \Log::warning('Default inventory account or cost account not configured in inventory settings. Skipping GL transactions.');
                return;
            }

            // Use movement ID as the numeric transaction id to match schema
            $transactionId = $movement->id;

            // Calculate total value from movement
            $totalValue = $movement->total_cost;

            if ($totalValue <= 0) {
                \Log::warning("Zero or negative cost for store issue. Skipping GL transactions.");
                return;
            }

            $description = "Store Issue: {$storeIssue->voucher_no} - {$item->name} - Items issued for requisition: {$storeIssue->storeRequisition->requisition_number}";

            if ($movement->movement_type === 'adjustment_out') {
                // Store Issue: Debit Cost Account, Credit Inventory Account
                
                // Debit: Cost of Goods Sold Account (Expense increases)
                GlTransaction::create([
                    'chart_account_id' => $costAccountId,
                    'amount' => $totalValue,
                    'nature' => 'debit',
                    'transaction_id' => $transactionId,
                    'transaction_type' => 'store_issue',
                    'date' => $movement->movement_date,
                    'description' => $description,
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                ]);

                // Credit: Inventory Account (Asset decreases)
                GlTransaction::create([
                    'chart_account_id' => $inventoryAccountId,
                    'amount' => $totalValue,
                    'nature' => 'credit',
                    'transaction_id' => $transactionId,
                    'transaction_type' => 'store_issue',
                    'date' => $movement->movement_date,
                    'description' => $description,
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                ]);

                \Log::info('GL transactions created for store issue', [
                    'movement_id' => $movement->id,
                    'total_value' => $totalValue,
                    'inventory_account_id' => $inventoryAccountId,
                    'cost_account_id' => $costAccountId,
                    'transaction_type' => 'store_issue'
                ]);
            }

        } catch (\Exception $e) {
            \Log::error("Failed to create GL transactions: " . $e->getMessage());
        }
    }

}
