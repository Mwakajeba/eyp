<?php

namespace App\Services\RentalEventEquipment;

use App\Models\Inventory\Movement;
use App\Models\RentalEventEquipment\RentalDispatch;
use App\Models\RentalEventEquipment\RentalReturn;
use App\Models\RentalEventEquipment\RentalInventorySetting;
use App\Services\InventoryStockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RentalInventoryService
{
    public function __construct(
        protected InventoryStockService $stockService
    ) {}

    /**
     * When dispatch is confirmed: move inventory from store to "Out on Rent" for each item linked to equipment.
     */
    public function issueFromDispatch(RentalDispatch $dispatch): void
    {
        $companyId = (int) $dispatch->company_id;
        $branchId = $dispatch->branch_id ? (int) $dispatch->branch_id : null;
        $settings = RentalInventorySetting::forBranch($companyId, $branchId);
        if (! $settings) {
            return;
        }

        $storageLocationId = (int) $settings->default_storage_location_id;
        $outOnRentLocationId = (int) $settings->out_on_rent_location_id;
        $branchIdForMovement = $branchId ?? $dispatch->contract->branch_id;
        if (! $branchIdForMovement) {
            return;
        }
        $branchIdForMovement = (int) $branchIdForMovement;
        $userId = Auth::id();
        $movementDate = $dispatch->dispatch_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $referenceNumber = $dispatch->dispatch_number;
        $notes = 'Rental dispatch: ' . $referenceNumber . ' – ' . ($dispatch->contract->contract_number ?? '');

        foreach ($dispatch->items as $dispatchItem) {
            $equipment = $dispatchItem->equipment;
            if (! $equipment || ! $equipment->item_id) {
                continue;
            }

            $item = $equipment->item;
            if (! $item) {
                continue;
            }

            $qty = (int) $dispatchItem->quantity;
            if ($qty <= 0) {
                continue;
            }

            $available = $this->stockService->getItemStockAtLocation($item->id, $storageLocationId);
            if ($available < $qty) {
                throw new \RuntimeException(
                    "Insufficient stock for {$item->name} (code: {$item->code}). Required: {$qty}, available at store: {$available}."
                );
            }

            $unitCost = (float) ($item->cost_price ?? 0);
            $totalCost = $unitCost * $qty;

            $balanceBeforeStorage = $available;
            $balanceAfterStorage = $available - $qty;
            $balanceBeforeOut = $this->stockService->getItemStockAtLocation($item->id, $outOnRentLocationId);
            $balanceAfterOut = $balanceBeforeOut + $qty;

            Movement::create([
                'branch_id' => $branchIdForMovement,
                'location_id' => $storageLocationId,
                'item_id' => $item->id,
                'user_id' => $userId,
                'movement_type' => 'transfer_out',
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'balance_before' => $balanceBeforeStorage,
                'balance_after' => $balanceAfterStorage,
                'reason' => 'Rental issue',
                'reference_number' => $referenceNumber,
                'reference_type' => 'rental_dispatch',
                'reference_id' => $dispatch->id,
                'notes' => $notes,
                'movement_date' => $movementDate,
            ]);

            Movement::create([
                'branch_id' => $branchIdForMovement,
                'location_id' => $outOnRentLocationId,
                'item_id' => $item->id,
                'user_id' => $userId,
                'movement_type' => 'transfer_in',
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'balance_before' => $balanceBeforeOut,
                'balance_after' => $balanceAfterOut,
                'reason' => 'Rental issue',
                'reference_number' => $referenceNumber,
                'reference_type' => 'rental_dispatch',
                'reference_id' => $dispatch->id,
                'notes' => $notes,
                'movement_date' => $movementDate,
            ]);
        }
    }

    /**
     * When return is recorded: move inventory back from "Out on Rent" to store (good/damaged); write off lost.
     */
    public function returnFromReturn(RentalReturn $return): void
    {
        $companyId = (int) $return->company_id;
        $branchId = $return->branch_id ? (int) $return->branch_id : null;
        $settings = RentalInventorySetting::forBranch($companyId, $branchId);
        if (! $settings) {
            return;
        }

        $storageLocationId = (int) $settings->default_storage_location_id;
        $outOnRentLocationId = (int) $settings->out_on_rent_location_id;
        $branchIdForMovement = $branchId ?? $return->contract->branch_id;
        if (! $branchIdForMovement) {
            return;
        }
        $branchIdForMovement = (int) $branchIdForMovement;
        $userId = Auth::id();
        $movementDate = $return->return_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $referenceNumber = $return->return_number;
        $notes = 'Rental return: ' . $referenceNumber . ' – ' . ($return->dispatch->dispatch_number ?? '');

        foreach ($return->items as $returnItem) {
            $equipment = $returnItem->equipment;
            if (! $equipment || ! $equipment->item_id) {
                continue;
            }

            $item = $equipment->item;
            if (! $item) {
                continue;
            }

            $qtyReturned = (int) $returnItem->quantity_returned;
            if ($qtyReturned <= 0) {
                continue;
            }

            $unitCost = (float) ($item->cost_price ?? 0);
            $totalCost = $unitCost * $qtyReturned;
            $condition = $returnItem->condition ?? 'good';

            if ($condition === 'lost') {
                $outBalanceBefore = $this->stockService->getItemStockAtLocation($item->id, $outOnRentLocationId);
                if ($outBalanceBefore >= $qtyReturned) {
                    $balanceAfter = $outBalanceBefore - $qtyReturned;
                    Movement::create([
                        'branch_id' => $branchIdForMovement,
                        'location_id' => $outOnRentLocationId,
                        'item_id' => $item->id,
                        'user_id' => $userId,
                        'movement_type' => 'write_off',
                        'quantity' => $qtyReturned,
                        'unit_cost' => $unitCost,
                        'total_cost' => $totalCost,
                        'balance_before' => $outBalanceBefore,
                        'balance_after' => $balanceAfter,
                        'reason' => 'Rental loss (not returned)',
                        'reference_number' => $referenceNumber,
                        'reference_type' => 'rental_return',
                        'reference_id' => $return->id,
                        'notes' => $notes,
                        'movement_date' => $movementDate,
                    ]);
                }
                continue;
            }

            // good or damaged: transfer from Out on Rent back to Store
            $outBalanceBefore = $this->stockService->getItemStockAtLocation($item->id, $outOnRentLocationId);
            if ($outBalanceBefore < $qtyReturned) {
                continue;
            }
            $outBalanceAfter = $outBalanceBefore - $qtyReturned;
            $storageBalanceBefore = $this->stockService->getItemStockAtLocation($item->id, $storageLocationId);
            $storageBalanceAfter = $storageBalanceBefore + $qtyReturned;

            Movement::create([
                'branch_id' => $branchIdForMovement,
                'location_id' => $outOnRentLocationId,
                'item_id' => $item->id,
                'user_id' => $userId,
                'movement_type' => 'transfer_out',
                'quantity' => $qtyReturned,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'balance_before' => $outBalanceBefore,
                'balance_after' => $outBalanceAfter,
                'reason' => 'Rental return',
                'reference_number' => $referenceNumber,
                'reference_type' => 'rental_return',
                'reference_id' => $return->id,
                'notes' => $notes,
                'movement_date' => $movementDate,
            ]);

            Movement::create([
                'branch_id' => $branchIdForMovement,
                'location_id' => $storageLocationId,
                'item_id' => $item->id,
                'user_id' => $userId,
                'movement_type' => 'transfer_in',
                'quantity' => $qtyReturned,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'balance_before' => $storageBalanceBefore,
                'balance_after' => $storageBalanceAfter,
                'reason' => 'Rental return',
                'reference_number' => $referenceNumber,
                'reference_type' => 'rental_return',
                'reference_id' => $return->id,
                'notes' => $notes,
                'movement_date' => $movementDate,
            ]);
        }
    }
}
