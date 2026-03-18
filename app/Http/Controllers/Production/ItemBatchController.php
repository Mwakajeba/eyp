<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
 use App\Models\Production\ItemBatch;
 use App\Models\Inventory\Item;
 use App\Models\Production\ProductionBatch;
use Illuminate\Http\Request;

class ItemBatchController extends Controller
{
    // Show form to add item to batch
    public function create($batchId)
    {
    $batch = ProductionBatch::findOrFail($batchId);
    $items = Item::all();
    $categories = \App\Models\Inventory\Category::all();
    return view('production.batches.add_item_batch', compact('batch', 'items', 'categories'));
    }

    // Store new item-batch relation
    public function store(Request $request, $batchHashid)
    {
        $decoded = \Vinkla\Hashids\Facades\Hashids::decode($batchHashid);
        if (empty($decoded)) {
            return response()->json(['success' => false, 'message' => 'Invalid batch ID.']);
        }
        $batchId = $decoded[0];
        $batch = ProductionBatch::findOrFail($batchId);
        $validated = $request->validate([
            'category_id' => 'required|exists:inventory_categories,id',
            'item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.01',
            'cost' => 'nullable|numeric|min:0',
        ]);
        // Check for duplicate
        $exists = ItemBatch::where('item_id', $validated['item_id'])
            ->where('production_batch_id', $batch->id)
            ->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'This item is already added to this batch.']);
        }
        $itemBatch = ItemBatch::create([
            'item_id' => $validated['item_id'],
            'production_batch_id' => $batch->id,
            'quantity' => $validated['quantity'],
            'cost' => $validated['cost'],
            // Optionally store category_id if your table supports it
        ]);
        return response()->json(['success' => true]);
    }

    // Delete item from batch
    public function destroy($id)
    {
        $itemBatch = ItemBatch::findOrFail($id);
        $itemBatch->delete();
        return response()->json(['success' => true]);
    }
}
