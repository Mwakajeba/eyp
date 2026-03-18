<?php

namespace App\Http\Controllers;

use App\Models\StoreReturn;
use App\Models\StoreRequisitionReturn;
use App\Models\StoreIssue;
use App\Models\StoreRequisition;
use App\Models\StoreRequisitionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use DataTables;
use Carbon\Carbon;

class StoreReturnController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of store returns
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getReturnsDataTable($request);
        }

        return view('store_requisitions.returns.index');
    }

    /**
     * DataTable for store returns
     */
    private function getReturnsDataTable(Request $request)
    {
        $query = StoreRequisitionReturn::with(['storeRequisition', 'processedBy', 'branch', 'company'])
            ->where('company_id', Auth::user()->company_id);

        // Apply filters
        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('return_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('return_date', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addColumn('action', function ($return) {
                $actions = '<div class="btn-group" role="group">';
                
                $actions .= '<a href="' . route('store-returns.show', $return->id) . '" class="btn btn-sm btn-info" title="View Details">
                    <i class="bx bx-show"></i>
                </a>';

                $actions .= '</div>';
                return $actions;
            })
            ->addColumn('voucher_no', function ($return) {
                return $return->storeRequisition ? $return->storeRequisition->requisition_number : 'N/A';
            })
            ->addColumn('processed_by_name', function ($return) {
                return $return->processedBy ? $return->processedBy->name : 'N/A';
            })
            ->addColumn('branch_name', function ($return) {
                return $return->branch ? $return->branch->name : 'N/A';
            })
            ->addColumn('formatted_amount', function ($return) {
                return 'TZS ' . number_format($return->total_return_amount, 2);
            })
            ->addColumn('formatted_date', function ($return) {
                return $return->return_date ? $return->return_date->format('d/m/Y') : 'N/A';
            })
            ->editColumn('return_date', function ($return) {
                return $return->return_date ? $return->return_date->format('Y-m-d') : '';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new store return
     */
    public function create(Request $request)
    {
        $issueId = $request->get('issue');
        
        if (!$issueId) {
            return redirect()->route('store-issues.index')
                ->with('error', 'Please select an issue voucher to create a return for.');
        }

        $storeIssue = StoreIssue::with(['storeRequisition.items.product', 'storeRequisition.requestedBy'])
            ->where('company_id', Auth::user()->company_id)
            ->findOrFail($issueId);

        if (!$storeIssue->canBeReturned()) {
            return redirect()->route('store-issues.show', $storeIssue->id)
                ->with('error', 'This issue voucher cannot have returns created. Status: ' . $storeIssue->status);
        }

        // Get items that were actually issued (quantity_issued > 0)
        $issuedItems = $storeIssue->storeRequisition->items->filter(function ($item) {
            return $item->quantity_issued > 0;
        });

        return view('store_requisitions.returns.create', compact('storeIssue', 'issuedItems'));
    }

    /**
     * Store a newly created store return
     */
    public function store(Request $request)
    {
        \Log::info('Store Return Request Data:', $request->all());
        
        $request->validate([
            'store_issue_id' => 'required|exists:store_issues,id',
            'return_date' => 'required|date|before_or_equal:today',
            'reason' => 'required|string|max:500',
            'description' => 'nullable|string|max:500',
            'remarks' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:store_requisition_items,id',
            'items.*.quantity_returned' => 'nullable|numeric',
        ], [
            'items.required' => 'At least one item must be selected for return.',
            'items.min' => 'At least one item must be selected for return.',
            'items.*.item_id.required' => 'Item ID is required.',
            'items.*.quantity_returned.numeric' => 'Quantity returned must be a valid number.',
        ]);

        $user = Auth::user();
        $branchId = session('branch_id') ?? ($user->branch_id ?? null);
        
        if (!$branchId) {
            return back()->withInput()->withErrors(['error' => 'Please select a branch before creating a store return.']);
        }

        $storeIssue = StoreIssue::with(['storeRequisition.items'])
            ->where('company_id', $user->company_id)
            ->findOrFail($request->store_issue_id);

        if (!$storeIssue->canBeReturned()) {
            return back()->withInput()->withErrors(['error' => 'This issue voucher cannot have returns created.']);
        }

        // Filter items with quantities and validate them
        $itemsToReturn = [];
        foreach ($request->items as $item) {
            // Skip items with no quantity or empty quantity
            if (empty($item['quantity_returned']) || $item['quantity_returned'] == 0) {
                continue;
            }

            $requisitionItem = StoreRequisitionItem::find($item['item_id']);
            
            if (!$requisitionItem || $requisitionItem->store_requisition_id != $storeIssue->store_requisition_id) {
                return back()->withInput()->withErrors(['error' => 'Invalid item selected.']);
            }

            $remainingQuantity = $requisitionItem->quantity_issued - $requisitionItem->quantity_returned;
            
            if ($item['quantity_returned'] > $remainingQuantity) {
                return back()->withInput()->withErrors([
                    'error' => "Quantity returned for {$requisitionItem->product->name} cannot exceed remaining quantity: {$remainingQuantity}"
                ]);
            }

            $itemsToReturn[] = [
                'requisition_item' => $requisitionItem,
                'quantity_returned' => $item['quantity_returned']
            ];
        }

        // Check if at least one item has a quantity
        if (empty($itemsToReturn)) {
            return back()->withInput()->withErrors([
                'error' => 'Please enter valid quantities for at least one item to return.'
            ]);
        }

        try {
            DB::beginTransaction();

            // Generate voucher number
            $voucherNo = $this->generateVoucherNumber($user->company_id, $branchId);

            // Calculate total amount from items being returned
            $totalAmount = 0;
            foreach ($itemsToReturn as $item) {
                $totalAmount += $item['requisition_item']->unit_price * $item['quantity_returned'];
            }

            // Create store return
            $storeReturn = StoreReturn::create([
                'company_id' => $user->company_id,
                'branch_id' => $branchId,
                'store_issue_id' => $storeIssue->id,
                'store_requisition_id' => $storeIssue->store_requisition_id,
                'voucher_no' => $voucherNo,
                'return_date' => $request->return_date,
                'returned_by' => $storeIssue->issued_to,
                'received_by' => $user->id,
                'total_amount' => $totalAmount,
                'reason' => $request->reason,
                'description' => $request->description,
                'remarks' => $request->remarks,
                'status' => 'returned',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Update requisition items with returned quantities
            $allItemsFullyReturned = true;
            foreach ($itemsToReturn as $item) {
                $requisitionItem = $item['requisition_item'];
                $quantityReturned = $item['quantity_returned'];
                
                $newQuantityReturned = $requisitionItem->quantity_returned + $quantityReturned;
                
                $requisitionItem->update([
                    'quantity_returned' => $newQuantityReturned
                ]);

                // Check if item is fully returned
                if ($newQuantityReturned < $requisitionItem->quantity_issued) {
                    $allItemsFullyReturned = false;
                }

                // TODO: Update product inventory/stock levels here
                // This would typically increase the stock quantity back
            }

            // Update issue status
            if ($allItemsFullyReturned) {
                $storeIssue->update([
                    'status' => 'fully_returned',
                    'updated_by' => $user->id,
                ]);
            } else {
                $storeIssue->update([
                    'status' => 'partially_returned',
                    'updated_by' => $user->id,
                ]);
            }

            // Update requisition status
            if ($allItemsFullyReturned) {
                $storeIssue->storeRequisition->update([
                    'return_voucher_id' => $storeReturn->id,
                    'updated_by' => $user->id,
                ]);
            }

            DB::commit();

            return redirect()->route('store-returns.show', $storeReturn->id)
                ->with('success', 'Store return created successfully. Voucher No: ' . $voucherNo);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Store Return Creation Failed: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Failed to create store return. Please try again.']);
        }
    }

    /**
     * Display the specified store return
     */
    public function show($id)
    {
        $storeReturn = StoreRequisitionReturn::with([
            'storeRequisition.items.product',
            'storeRequisition.requestedBy',
            'processedBy', 
            'branch',
            'company'
        ])->where('company_id', Auth::user()->company_id)
          ->findOrFail($id);

        return view('store_requisitions.returns.show', compact('storeReturn'));
    }

    /**
     * Show the form for editing the specified resource
     */
    public function edit(StoreReturn $storeReturn)
    {
        if ($storeReturn->status !== 'returned') {
            return redirect()->route('store-returns.show', $storeReturn->id)
                ->with('error', 'Only returned vouchers can be edited.');
        }

        $storeReturn->load(['storeIssue.storeRequisition.items.product', 'storeRequisition.requestedBy']);

        return view('store_requisitions.returns.edit', compact('storeReturn'));
    }

    /**
     * Update the specified store return
     */
    public function update(Request $request, StoreReturn $storeReturn)
    {
        if ($storeReturn->status !== 'returned') {
            return redirect()->route('store-returns.show', $storeReturn->id)
                ->with('error', 'Only returned vouchers can be updated.');
        }

        $request->validate([
            'return_date' => 'required|date|before_or_equal:today',
            'reason' => 'required|string|max:500',
            'description' => 'nullable|string|max:500',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $storeReturn->update([
                'return_date' => $request->return_date,
                'reason' => $request->reason,
                'description' => $request->description,
                'remarks' => $request->remarks,
                'updated_by' => Auth::id(),
            ]);

            return redirect()->route('store-returns.show', $storeReturn->id)
                ->with('success', 'Store return updated successfully.');

        } catch (\Exception $e) {
            \Log::error('Store Return Update Failed: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Failed to update store return. Please try again.']);
        }
    }

    /**
     * Process a store return (mark as processed)
     */
    public function process(Request $request, StoreReturn $storeReturn)
    {
        if (!$storeReturn->canBeProcessed()) {
            return response()->json([
                'success' => false,
                'message' => 'This return cannot be processed.'
            ], 400);
        }

        $request->validate([
            'comments' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $storeReturn->update([
                'status' => 'processed',
                'remarks' => $request->comments ?: $storeReturn->remarks,
                'updated_by' => Auth::id(),
            ]);

            // TODO: Create any necessary GL transactions or inventory adjustments here

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Store return processed successfully.',
                'status' => $storeReturn->status
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Store Return Processing Failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process store return. Please try again.'
            ], 500);
        }
    }

    /**
     * Cancel a store return
     */
    public function cancel(Request $request, StoreReturn $storeReturn)
    {
        if ($storeReturn->status !== 'returned') {
            return response()->json([
                'success' => false,
                'message' => 'Only returned vouchers can be cancelled.'
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|min:5|max:500'
        ]);

        try {
            DB::beginTransaction();

            // Reverse the returned quantities on requisition items
            $requisitionItems = $storeReturn->storeRequisition->items;
            foreach ($requisitionItems as $item) {
                // Note: In a real implementation, you'd need to track which specific quantities 
                // were returned in this voucher to reverse them accurately
                $item->update([
                    'quantity_returned' => 0 // Simplified - should be more precise
                ]);
            }

            // Update issue status back to issued
            $storeReturn->storeIssue->update([
                'status' => 'issued',
                'updated_by' => Auth::id(),
            ]);

            // Update requisition status
            $storeReturn->storeRequisition->update([
                'return_voucher_id' => null,
                'updated_by' => Auth::id(),
            ]);

            // Cancel the return
            $storeReturn->update([
                'status' => 'cancelled',
                'remarks' => $request->reason,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Store return cancelled successfully.',
                'status' => $storeReturn->status
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Store Return Cancellation Failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel store return. Please try again.'
            ], 500);
        }
    }

    /**
     * Generate voucher number for store return
     */
    private function generateVoucherNumber($companyId, $branchId)
    {
        $prefix = 'SRN-' . date('Y') . '-';
        $lastReturn = StoreReturn::where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->where('voucher_no', 'like', $prefix . '%')
            ->orderBy('voucher_no', 'desc')
            ->first();

        if ($lastReturn) {
            $lastNumber = (int) substr($lastReturn->voucher_no, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
