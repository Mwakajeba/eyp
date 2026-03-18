<?php

namespace Database\Seeders;

use App\Models\InventoryLocation;
use Illuminate\Database\Seeder;

/**
 * Creates an "Out on Rent" inventory location per branch that already has locations.
 * Run once so rental inventory integration can use it in rental_inventory_settings.
 */
class RentalOutOnRentLocationSeeder extends Seeder
{
    public function run(): void
    {
        $outOnRentName = 'Out on Rent';

        $branchIds = InventoryLocation::query()
            ->distinct()
            ->pluck('branch_id');

        foreach ($branchIds as $branchId) {
            if (! $branchId) {
                continue;
            }

            $exists = InventoryLocation::where('branch_id', $branchId)
                ->where('name', $outOnRentName)
                ->exists();

            if ($exists) {
                continue;
            }

            $first = InventoryLocation::where('branch_id', $branchId)->first();
            $companyId = $first?->company_id ?? \App\Models\Branch::find($branchId)?->company_id;
            $createdBy = $first?->created_by ?? 1;

            InventoryLocation::create([
                'name' => $outOnRentName,
                'description' => 'Stock issued to customers (rental). Used for rental-inventory integration.',
                'branch_id' => $branchId,
                'company_id' => $companyId,
                'is_active' => true,
                'created_by' => $createdBy,
            ]);
        }
    }
}
