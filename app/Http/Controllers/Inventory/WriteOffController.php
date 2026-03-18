<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Movement;
use App\Models\Inventory\Item;
use App\Models\GlTransaction;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Yajra\DataTables\Facades\DataTables;
use App\Services\InventoryCostService;
use App\Services\InventoryStockService;

class WriteOffController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of write-offs.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view inventory movements') && !auth()->user()->hasPermissionTo('view inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }

        if ($request->ajax()) {
            $loginLocationId = session('location_id');
            $writeOffs = Movement::with(['item', 'user', 'location'])
                ->where('movement_type', 'write_off')
                ->whereHas('item', function($query) {
                    $query->where('company_id', Auth::user()->company_id);
                });
            
            // Filter by location if a specific location is selected
            if ($loginLocationId) {
                $writeOffs->where('location_id', $loginLocationId);
            }
            
            $writeOffs->select('inventory_movements.*');

            return DataTables::of($writeOffs)
                ->addColumn('item_name', function ($movement) {
                    return '<div>
                                <span class="fw-bold">' . $movement->item->name . '</span><br>
                                <small class="text-muted">' . $movement->item->code . '</small>
                            </div>';
                })
                ->addColumn('movement_type_badge', function ($movement) {
                    return '<span class="badge bg-dark">Write Off</span>';
                })
                ->addColumn('quantity_formatted', function ($movement) {
                    return '<span class="fw-bold">' . number_format($movement->quantity, 2) . '</span><br>
                            <small class="text-muted">' . $movement->item->unit_of_measure . '</small>';
                })
                ->addColumn('unit_cost_formatted', function ($movement) {
                    return number_format($movement->unit_cost, 2);
                })
                ->addColumn('total_cost_formatted', function ($movement) {
                    return '<span class="fw-bold">' . number_format($movement->total_cost, 2) . '</span>';
                })
                ->addColumn('balance_after_formatted', function ($movement) {
                    return '<span class="fw-bold">' . number_format($movement->balance_after, 2) . '</span>';
                })
                ->addColumn('user_name', function ($movement) {
                    return $movement->user->name ?? 'N/A';
                })
                ->addColumn('location_name', function ($movement) {
                    if ($movement->location) {
                        return '<div>
                                    <span class="fw-bold">' . $movement->location->name . '</span><br>
                                    <small class="text-muted">' . ($movement->location->branch->name ?? 'N/A') . '</small>
                                </div>';
                    }
                    return '<span class="text-muted">N/A</span>';
                })
                ->addColumn('actions', function ($movement) {
                    $actions = '<div class="d-flex gap-1">';
                    
                    // View button
                    if (auth()->user()->hasPermissionTo('view inventory movements')) {
                        $actions .= '<a href="' . route('inventory.write-offs.show', $movement->hash_id) . '" class="btn btn-sm btn-outline-info" title="View">
                                    <i class="bx bx-show"></i>
                                    </a>';
                    }
                    
                    // Edit and Delete buttons
                    if (auth()->user()->hasPermissionTo('manage inventory movements')) {
                        $actions .= '<a href="' . route('inventory.write-offs.edit', $movement->hash_id) . '" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bx bx-edit"></i>
                                    </a>';
                        
                        $actions .= '<form method="POST" action="' . route('inventory.write-offs.destroy', $movement->hash_id) . '" class="d-inline">
                                    ' . csrf_field() . method_field('DELETE') . '
                                    <button type="submit" class="btn btn-sm btn-outline-danger delete-write-off" data-reference="' . ($movement->reference ?? 'REF-' . $movement->id) . '" title="Delete">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                    </form>';
                    }
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->editColumn('movement_date', function ($movement) {
                    return format_date($movement->movement_date, 'M d, Y');
                })
                ->editColumn('reference', function ($movement) {
                    return $movement->reference 
                        ? '<span class="badge bg-light text-dark">' . $movement->reference . '</span>'
                        : '<span class="text-muted">N/A</span>';
                })
                ->rawColumns(['item_name', 'movement_type_badge', 'quantity_formatted', 'total_cost_formatted', 'balance_after_formatted', 'location_name', 'actions', 'reference'])
                ->make(true);
        }

        // Get statistics
        $loginLocationId = session('location_id');
        $baseQuery = Movement::where('movement_type', 'write_off')
            ->whereHas('item', function($query) {
                $query->where('company_id', Auth::user()->company_id);
            });
        
        if ($loginLocationId) {
            $baseQuery->where('location_id', $loginLocationId);
        }

        $statistics = [
            'total_write_offs' => (clone $baseQuery)->count(),
            'total_value' => (clone $baseQuery)->sum('total_cost'),
        ];

        return view('inventory.write-offs.index', compact('statistics'));
    }

    /**
     * Show the form for creating a new write-off.
     */
    public function create(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('create inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }

        $loginLocationId = session('location_id');
        
        if (!$loginLocationId) {
            return redirect()->route('inventory.index')
                ->with('error', 'Please select a location first.');
        }

        $stockService = new InventoryStockService();
        
        // Get all items with stock > 0 at the login location for the modal dropdown
        $items = $stockService->getItemsWithStockAtLocation(
            Auth::user()->company_id,
            $loginLocationId
        );

        // Get stock information for all items (for display in modal)
        $locationStocks = [];
        foreach ($items as $item) {
            $stock = $stockService->getItemStockAtLocation($item->id, $loginLocationId);
            $locationStocks[$item->id] = $stock;
        }

        return view('inventory.write-offs.create', compact('items', 'locationStocks', 'loginLocationId'));
    }

    /**
     * Store a newly created write-off.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('create inventory adjustments')) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'reference' => 'nullable|string|max:255',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'movement_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $loginLocationId = session('location_id');
        
        if (!$loginLocationId) {
            return redirect()->route('inventory.write-offs.create')
                ->with('error', 'Please select a location first.');
        }

        $stockService = new InventoryStockService();
        $costService = new InventoryCostService();
        
        // Get write-off expense account from settings
        $writeOffExpenseAccountId = (int) (SystemSetting::where('key', 'inventory_default_cost_account')->value('value') ?? 173);
        $inventoryAccountId = (int) (SystemSetting::where('key', 'inventory_default_inventory_account')->value('value') ?? 185);

        if (!$inventoryAccountId) {
            return redirect()->route('inventory.write-offs.create')
                ->with('error', 'Inventory account not configured. Please configure it in inventory settings.');
        }

        $branchId = session('branch_id') ?? Auth::user()->branch_id;
        $movements = [];
        $totalWriteOffValue = 0;

        DB::transaction(function () use ($request, $loginLocationId, $branchId, $stockService, $costService, $writeOffExpenseAccountId, $inventoryAccountId, &$movements, &$totalWriteOffValue) {
            foreach ($request->items as $itemData) {
                $item = Item::findOrFail($itemData['item_id']);
                
                // Verify item belongs to user's company
                if ($item->company_id !== Auth::user()->company_id) {
                    abort(403, 'Unauthorized access to item: ' . $item->name);
                }

                // Get current stock
                $currentStock = $stockService->getItemStockAtLocation($item->id, $loginLocationId);
                $writeOffQuantity = (float) $itemData['quantity'];
                
                // Validate quantity doesn't exceed available stock
                if ($writeOffQuantity > $currentStock) {
                    throw new \Exception('Insufficient stock for ' . $item->name . '. Available: ' . number_format($currentStock, 2) . ', Requested: ' . number_format($writeOffQuantity, 2));
                }

                if ($writeOffQuantity <= 0) {
                    continue; // Skip items with no quantity
                }

                // Remove from cost layers and get actual cost
                $costInfo = $costService->removeInventory(
                    $item->id,
                    $writeOffQuantity,
                    'write_off',
                    $request->reference,
                    $request->movement_date
                );

                $totalCost = $costInfo['total_cost'];
                $averageUnitCost = $writeOffQuantity > 0 ? $totalCost / $writeOffQuantity : 0;
                $totalWriteOffValue += $totalCost;
                $newStock = $currentStock - $writeOffQuantity;

                // Create movement record
                $movement = Movement::create([
                    'branch_id' => $branchId,
                    'location_id' => $loginLocationId,
                    'item_id' => $item->id,
                    'user_id' => Auth::id(),
                    'movement_type' => 'write_off',
                    'quantity' => $writeOffQuantity,
                    'unit_cost' => $averageUnitCost,
                    'total_cost' => $totalCost,
                    'reference' => $request->reference,
                    'reason' => $request->reason,
                    'notes' => $request->notes,
                    'movement_date' => $request->movement_date,
                    'balance_before' => $currentStock,
                    'balance_after' => $newStock,
                ]);

                $movements[] = $movement;

                // Create GL transactions for write-off
                // Debit: Write-off Expense Account (Expense increases)
                GlTransaction::create([
                    'chart_account_id' => $writeOffExpenseAccountId,
                    'amount' => $totalCost,
                    'nature' => 'debit',
                    'transaction_id' => $movement->id,
                    'transaction_type' => 'inventory_write_off',
                    'date' => $request->movement_date,
                    'description' => "Write-off: {$item->name} - {$request->reason}",
                    'branch_id' => $branchId,
                    'user_id' => Auth::id(),
                ]);

                // Credit: Inventory Account (Asset decreases)
                GlTransaction::create([
                    'chart_account_id' => $inventoryAccountId,
                    'amount' => $totalCost,
                    'nature' => 'credit',
                    'transaction_id' => $movement->id,
                    'transaction_type' => 'inventory_write_off',
                    'date' => $request->movement_date,
                    'description' => "Write-off: {$item->name} - {$request->reason}",
                    'branch_id' => $branchId,
                    'user_id' => Auth::id(),
                ]);
            }
        });

        $itemsCount = count($movements);
        
        return redirect()->route('inventory.write-offs.index')
            ->with('success', "Successfully wrote off {$itemsCount} item(s) with total value of " . number_format($totalWriteOffValue, 2) . ".");
    }

    /**
     * Display the specified write-off.
     */
    public function show(Movement $movement)
    {
        if ($movement->movement_type !== 'write_off') {
            abort(404, 'Movement is not a write-off.');
        }

        if ((!auth()->user()->hasPermissionTo('view inventory movements') && !auth()->user()->hasPermissionTo('view inventory adjustments')) || 
            $movement->item->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access.');
        }
        
        $movement->load(['item', 'user', 'location']);

        return view('inventory.write-offs.show', compact('movement'));
    }

    /**
     * Show the form for editing the specified write-off.
     */
    public function edit(Movement $movement)
    {
        if ($movement->movement_type !== 'write_off') {
            abort(404, 'Movement is not a write-off.');
        }

        if ((!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('edit inventory adjustments')) ||
            $movement->item->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        return view('inventory.write-offs.edit', compact('movement'));
    }

    /**
     * Update the specified write-off.
     */
    public function update(Request $request, Movement $movement)
    {
        if ($movement->movement_type !== 'write_off') {
            abort(404, 'Movement is not a write-off.');
        }

        if ((!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('edit inventory adjustments')) ||
            $movement->item->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'reference' => 'nullable|string|max:255',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'movement_date' => 'required|date',
        ]);

        DB::transaction(function () use ($request, $movement) {
            $movement->update([
                'reference' => $request->reference,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'movement_date' => $request->movement_date,
            ]);

            // Update GL transaction descriptions
            GlTransaction::where('transaction_type', 'inventory_write_off')
                ->where('transaction_id', $movement->id)
                ->update([
                    'description' => "Write-off: {$movement->item->name} - {$request->reason}",
                    'date' => $request->movement_date,
                ]);
        });

        return redirect()->route('inventory.write-offs.index')
            ->with('success', 'Write-off updated successfully.');
    }

    /**
     * Remove the specified write-off.
     */
    public function destroy(Movement $movement)
    {
        if ($movement->movement_type !== 'write_off') {
            abort(404, 'Movement is not a write-off.');
        }

        if ((!auth()->user()->hasPermissionTo('manage inventory movements') && !auth()->user()->hasPermissionTo('delete inventory adjustments')) ||
            $movement->item->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        DB::transaction(function () use ($movement) {
            // Delete GL transactions
            GlTransaction::where('transaction_type', 'inventory_write_off')
                ->where('transaction_id', $movement->id)
                ->delete();

            // Note: We don't reverse the cost layers here as it's complex
            // The user should be aware that deleting a write-off doesn't restore inventory
            // For proper reversal, they should create a new adjustment_in movement

            $movement->delete();
        });

        return redirect()->route('inventory.write-offs.index')
            ->with('success', 'Write-off deleted successfully.');
    }
}

