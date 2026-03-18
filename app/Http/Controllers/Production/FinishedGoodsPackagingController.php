<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Inventory\Item;
use App\Models\Inventory\Movement;
use App\Models\GlTransaction;

class FinishedGoodsPackagingController extends Controller
{
    /**
     * Display the finished goods packaging form
     */
    public function index()
    {
        // Get user's accessible locations
        $locations = auth()->user()->locations;
        
        // Get finished goods and products only
        $items = Item::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->whereIn('item_type', ['finished_goods', 'product'])
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'cost_price', 'unit_price']);

        // Get work orders with PACKAGING status
        $workOrders = \App\Models\Production\WorkOrder::where('company_id', auth()->user()->company_id)
            ->where('status', 'PACKAGING')
            ->orderBy('wo_number')
            ->get(['id', 'wo_number', 'product_name', 'style']);

        return view('production.packaging.index', compact('locations', 'items', 'workOrders'));
    }

    /**
     * Store finished goods packaging
     */
    public function store(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:inventory_locations,id',
            'packaging_date' => 'required|date',
            'work_order_id' => 'required|exists:work_orders,id',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
            'items.*.sales_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // Verify user has access to the selected location
            $location = auth()->user()->locations()->where('inventory_locations.id', $request->location_id)->first();
            if (!$location) {
                throw new \Exception('You do not have access to the selected location');
            }

            // Get the selected work order and verify it's in PACKAGING status
            $workOrder = \App\Models\Production\WorkOrder::where('id', $request->work_order_id)
                ->where('company_id', auth()->user()->company_id)
                ->where('status', 'PACKAGING')
                ->first();

            if (!$workOrder) {
                throw new \Exception('Selected work order not found or not in PACKAGING status');
            }

            $branchId = session('branch_id') ?? auth()->user()->branch_id ?? $location->branch_id;
            $referenceNumber = $workOrder->wo_number;
            $totalInventoryValue = 0;

            // Process each item
            foreach ($request->items as $itemData) {
                $item = Item::findOrFail($itemData['item_id']);

                // Get current stock balance for this location
                $lastMovement = Movement::where('item_id', $item->id)
                    ->where('location_id', $location->id)
                    ->orderBy('id', 'desc')
                    ->first();

                $balanceBefore = $lastMovement ? $lastMovement->balance_after : 0;
                $quantity = $itemData['quantity'];
                $balanceAfter = $balanceBefore + $quantity;
                $totalCost = $quantity * $itemData['cost_price'];
                $totalInventoryValue += $totalCost;

                // Create inventory movement record (STOCK IN)
                Movement::create([
                    'branch_id' => $branchId,
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
                    'reason' => 'Work order packaging completed',
                    'reference_number' => $referenceNumber,
                    'reference_type' => 'work_order_packaging',
                    'reference_id' => $workOrder->id,
                    'reference' => "Work Order Packaging - {$referenceNumber}",
                    'notes' => $request->notes ?? "Packaged {$quantity} units of {$item->name} from WO: {$referenceNumber}",
                    'movement_date' => $request->packaging_date,
                ]);

                // Update item prices if needed
                $item->update([
                    'cost_price' => $itemData['cost_price'],
                    'unit_price' => $itemData['sales_price'],
                ]);
            }

            // Create GL transactions
            $this->createPackagingGLTransactions($totalInventoryValue, $request->packaging_date, $referenceNumber, $branchId, $workOrder->id);

            // Update work order status to DISPATCHED
            $workOrder->update(['status' => 'DISPATCHED']);

            DB::commit();

            return redirect()->back()->with('success', "Work Order {$referenceNumber} packaging completed successfully! Status updated to DISPATCHED.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to package finished goods: ' . $e->getMessage());
        }
    }

    /**
     * Create GL transactions for packaging
     */
    private function createPackagingGLTransactions($totalInventoryValue, $packagingDate, $referenceNumber, $branchId, $workOrderId = null)
    {
        // Get the inventory and manufacturing accounts from system settings
        $inventoryAccountId = \App\Models\SystemSetting::where('key', 'inventory_default_inventory_account')->value('value');
        $manufacturingAccountId = \App\Models\SystemSetting::where('key', 'inventory_default_wip_account')->value('value') ?? 
                                 \App\Models\SystemSetting::where('key', 'inventory_default_opening_balance_account')->value('value');

        if (!$inventoryAccountId || !$manufacturingAccountId) {
            throw new \Exception('Inventory or Manufacturing account not configured in system settings');
        }

        // Transaction 1: Debit Finished Goods Inventory (Asset increases)
        GlTransaction::create([
            'chart_account_id' => $inventoryAccountId,
            'amount' => $totalInventoryValue,
            'nature' => 'debit',
            'transaction_id' => $workOrderId ?? 0,
            'transaction_type' => 'work_order_packaging',
            'date' => $packagingDate,
            'description' => "Work order packaging - {$referenceNumber}",
            'branch_id' => $branchId,
            'user_id' => Auth::id(),
        ]);

        // Transaction 2: Credit Manufacturing/WIP Account (Asset decreases)
        GlTransaction::create([
            'chart_account_id' => $manufacturingAccountId,
            'amount' => $totalInventoryValue,
            'nature' => 'credit',
            'transaction_id' => $workOrderId ?? 0,
            'transaction_type' => 'work_order_packaging',
            'date' => $packagingDate,
            'description' => "WIP transfer to finished goods - {$referenceNumber}",
            'branch_id' => $branchId,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Generate packaging reference number
     */
    private function generatePackagingNumber()
    {
        $prefix = 'PKG';
        $date = date('Ymd');

        $lastPackaging = Movement::where('reference_number', 'like', $prefix . $date . '%')
            ->where('reference_type', 'finished_goods_packaging')
            ->orderBy('reference_number', 'desc')
            ->first();

        if ($lastPackaging) {
            $lastNumber = intval(substr($lastPackaging->reference_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $date . $newNumber;
    }

    /**
     * Search items via AJAX
     */
    public function searchItems(Request $request)
    {
        $term = $request->get('term', '');
        
        $items = Item::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->whereIn('item_type', ['finished_goods', 'product'])
            ->where(function($query) use ($term) {
                $query->where('name', 'like', '%' . $term . '%')
                      ->orWhere('code', 'like', '%' . $term . '%');
            })
            ->limit(10)
            ->get(['id', 'name', 'code', 'cost_price', 'unit_price']);

        return response()->json($items);
    }
}