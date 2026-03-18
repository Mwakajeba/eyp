<?php
namespace App\Http\Controllers\Production;

use App\Models\ProductionBatch;
use App\Models\Inventory\Item;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductionBatchController extends Controller
{
    // Show modal/form to assign order to batch
    public function assignOrderForm($hashid)
    {
        $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hashid);
        if (empty($decoded)) {
            return redirect()->route('production.batches.index')->withErrors(['Batch not found.']);
        }
        $batch = ProductionBatch::findOrFail($decoded[0]);
        // Get all sales orders (optionally filter by status, etc.)
        $orders = \App\Models\Sales\SalesOrder::all();
        return view('production.batches.assign_order', compact('batch', 'orders'));
    }

    // Handle assignment of order to batch
    public function assignOrder(\Illuminate\Http\Request $request, $hashid)
    {
        $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hashid);
        if (empty($decoded)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Batch not found.']);
            }
            return redirect()->route('production.batches.index')->withErrors(['Batch not found.']);
        }
        $batch = ProductionBatch::findOrFail($decoded[0]);
        $validated = $request->validate([
            'order_id' => 'required|exists:sales_orders,id',
            'assigned_quantity' => 'required|integer|min:1',
        ]);

        // Check if assigned quantity matches sales order total quantity
        $salesOrder = \App\Models\Sales\SalesOrder::find($validated['order_id']);
        $totalQty = $salesOrder ? $salesOrder->items->sum('quantity') : null;
        if ($totalQty === null || $validated['assigned_quantity'] != $totalQty) {
            $userMsg = 'Assigned quantity must match the sales order total quantity (' . $totalQty . ').';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $userMsg]);
            }
            return redirect()->route('production.batches.show', $hashid)->withErrors([$userMsg]);
        }

        try {
            $batch->orders()->attach($validated['order_id'], ['assigned_quantity' => $validated['assigned_quantity']]);
            if ($request->ajax()) {
                return response()->json(['success' => true]);
            }
            return redirect()->route('production.batches.show', $hashid)->with('success', 'Order assigned to batch successfully.');
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            // Check for duplicate entry error code (SQLSTATE[23000])
            if (str_contains($errorMsg, 'Integrity constraint violation') && str_contains($errorMsg, '1062')) {
                $userMsg = 'This order is already assigned to this batch.';
            } else {
                $userMsg = 'Failed to assign order.';
            }
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $userMsg]);
            }
            return redirect()->route('production.batches.show', $hashid)->withErrors([$userMsg]);
        }
    }
    public function index()
    {
        $batches = ProductionBatch::all();
        return view('production.batches.index', compact('batches'));
    }

    public function create()
    {
        return view('production.batches.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_number' => 'required|string|max:50|unique:production_batches,batch_number',
            'item_id' => 'required|exists:inventory_items,id',
            'quantity_planned' => 'nullable|integer',
            'quantity_produced' => 'nullable|integer',
            'quantity_defective' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'required|in:planned,in_progress,completed,cancelled',
        ]);
        $batch = ProductionBatch::create($validated);
        return redirect()->route('production.batches.index');
    }

    public function show($hashid)
    {
        $decoded = Hashids::decode($hashid);
        if (empty($decoded)) {
            return redirect()->route('production.batches.index')->withErrors(['Batch not found.']);
        }
    $batch = ProductionBatch::findOrFail($decoded[0]);
    $categories = \App\Models\Inventory\Category::active()->get();
    $items = \App\Models\Inventory\Item::all();
    return view('production.batches.show', compact('batch', 'items', 'categories'));
    }

    public function edit($hashid)
    {
        $decoded = Hashids::decode($hashid);
        if (empty($decoded)) {
            return redirect()->route('production.batches.index')->withErrors(['Batch not found.']);
        }
        $batch = ProductionBatch::findOrFail($decoded[0]);
        return view('production.batches.edit', compact('batch'));
    }

    public function update(Request $request, $hashid)
    {
        $decoded = Hashids::decode($hashid);
        if (empty($decoded)) {
            return redirect()->route('production.batches.index')->withErrors(['Batch not found.']);
        }
        $batch = ProductionBatch::findOrFail($decoded[0]);
        $validated = $request->validate([
            'batch_number' => 'required|string|max:50|unique:production_batches,batch_number,' . $batch->id,
            'item_id' => 'required|exists:inventory_items,id',
            'quantity_planned' => 'nullable|integer',
            'quantity_produced' => 'nullable|integer',
            'quantity_defective' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'required|in:planned,in_progress,completed,cancelled',
        ]);
        $batch->update($validated);
        return redirect()->route('production.batches.index');
    }

    public function destroy($hashid)
    {
        $decoded = Hashids::decode($hashid);
        if (empty($decoded)) {
            return redirect()->route('production.batches.index')->withErrors(['Batch not found.']);
        }
        $batch = ProductionBatch::findOrFail($decoded[0]);
        $batch->delete();
        return redirect()->route('production.batches.index');
    }
    /**
     * Remove an assigned order from a production batch.
     */
    public function deleteAssignedOrder(Request $request, $batchHashid, $orderHashid)
    {
        $batchDecoded = \Vinkla\Hashids\Facades\Hashids::decode($batchHashid);
        $orderDecoded = \Vinkla\Hashids\Facades\Hashids::decode($orderHashid);
        if (empty($batchDecoded) || empty($orderDecoded)) {
            return response()->json(['success' => false, 'message' => 'Batch or Order not found.'], 404);
        }
        $batch = ProductionBatch::find($batchDecoded[0]);
        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Batch not found.'], 404);
        }
        try {
            $batch->orders()->detach($orderDecoded[0]);
            return response()->json(['success' => true, 'message' => 'Order unassigned from batch.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

       /**
     * Update the assigned quantity for an order in a production batch.
     */
    public function updateAssignedOrder(Request $request, $batchHashid, $orderHashid)
    {
        $batchDecoded = \Vinkla\Hashids\Facades\Hashids::decode($batchHashid);
        $orderDecoded = \Vinkla\Hashids\Facades\Hashids::decode($orderHashid);
        if (empty($batchDecoded) || empty($orderDecoded)) {
            return response()->json(['success' => false, 'message' => 'Batch or Order not found.'], 404);
        }
        $batch = ProductionBatch::find($batchDecoded[0]);
        if (!$batch) {
            return response()->json(['success' => false, 'message' => 'Batch not found.'], 404);
        }
        $validated = $request->validate([
            'assigned_quantity' => 'required|integer|min:1',
        ]);
        try {
            // Update the pivot table for assigned quantity
            $batch->orders()->updateExistingPivot($orderDecoded[0], ['assigned_quantity' => $validated['assigned_quantity']]);
            return response()->json(['success' => true, 'message' => 'Assigned quantity updated.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
