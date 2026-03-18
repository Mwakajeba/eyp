<?php

namespace App\Models\RentalEventEquipment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalInventorySetting extends Model
{
    protected $table = 'rental_inventory_settings';

    protected $fillable = [
        'company_id',
        'branch_id',
        'default_storage_location_id',
        'out_on_rent_location_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function defaultStorageLocation(): BelongsTo
    {
        return $this->belongsTo(\App\Models\InventoryLocation::class, 'default_storage_location_id');
    }

    public function outOnRentLocation(): BelongsTo
    {
        return $this->belongsTo(\App\Models\InventoryLocation::class, 'out_on_rent_location_id');
    }

    /**
     * Get settings for company/branch (branch can be null for company-wide).
     */
    /**
     * Get settings for company/branch. Prefers branch-specific row, then company-wide (branch_id null).
     */
    public static function forBranch(int $companyId, ?int $branchId): ?self
    {
        $query = static::query()
            ->where('company_id', $companyId)
            ->whereNotNull('default_storage_location_id')
            ->whereNotNull('out_on_rent_location_id');

        $branchSpecific = (clone $query)->where('branch_id', $branchId)->first();
        if ($branchSpecific) {
            return $branchSpecific;
        }
        return (clone $query)->whereNull('branch_id')->first();
    }
}
