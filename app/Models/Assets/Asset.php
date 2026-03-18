<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class Asset extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'asset_category_id',
        'tax_class_id',
        'code',
        'name',
        'model',
        'manufacturer',
        'description',
        'purchase_date',
        'capitalization_date',
        'purchase_cost',
        'supplier_id',
        'location',
        'building_reference',
        'gps_lat',
        'gps_lng',
        'serial_number',
        'salvage_value',
        'current_nbv',
        'tax_value_opening',
        'accumulated_tax_dep',
        'current_tax_wdv',
        'tax_method',
        'tax_rate',
        'deferred_tax_diff',
        'deferred_tax_liability',
        'department_id',
        'custodian_user_id',
        'tag',
        'barcode',
        'status',
        'hfs_status',
        'depreciation_stopped',
        'depreciation_stopped_date',
        'depreciation_stopped_reason',
        'current_hfs_id',
        'warranty_months',
        'warranty_expiry_date',
        'insurance_policy_no',
        'insured_value',
        'insurance_expiry_date',
        'attachments',
        // Fleet Management specific fields
        'registration_number',
        'ownership_type', // owned, leased, rented
        'fuel_type', // petrol, diesel, electric, hybrid
        'capacity_tons',
        'capacity_volume',
        'capacity_passengers',
        'license_expiry_date',
        'inspection_expiry_date',
        'operational_status', // available, assigned, in_repair, retired
        'gps_device_id',
        'current_location',
        'created_by',
        'updated_by',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'capitalization_date' => 'date',
        'warranty_expiry_date' => 'date',
        'insurance_expiry_date' => 'date',
        'depreciation_stopped_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'current_nbv' => 'decimal:2',
        'tax_value_opening' => 'decimal:2',
        'accumulated_tax_dep' => 'decimal:2',
        'current_tax_wdv' => 'decimal:2',
        'tax_rate' => 'decimal:6',
        'deferred_tax_diff' => 'decimal:2',
        'deferred_tax_liability' => 'decimal:2',
        'insured_value' => 'decimal:2',
        'depreciation_stopped' => 'boolean',
        // Fleet Management specific casts
        'license_expiry_date' => 'date',
        'inspection_expiry_date' => 'date',
        'capacity_tons' => 'decimal:2',
        'capacity_volume' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function assignedDriver()
    {
        return $this->hasOne(\App\Models\Fleet\FleetDriver::class, 'assigned_vehicle_id')
                    ->where('status', 'active')
                    ->where(function($q) {
                        $q->whereNull('assignment_end_date')
                          ->orWhere('assignment_end_date', '>=', now());
                    });
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Hr\Department::class);
    }

    public function custodian()
    {
        return $this->belongsTo(\App\Models\User::class, 'custodian_user_id');
    }

    public function depreciations()
    {
        return $this->hasMany(AssetDepreciation::class);
    }

    public function bookDepreciations()
    {
        return $this->hasMany(AssetDepreciation::class)->where('depreciation_type', 'book');
    }

    public function taxDepreciations()
    {
        return $this->hasMany(AssetDepreciation::class)->where('depreciation_type', 'tax');
    }

    public function taxClass()
    {
        return $this->belongsTo(TaxDepreciationClass::class, 'tax_class_id');
    }

    public function deferredTaxes()
    {
        return $this->hasMany(AssetDeferredTax::class);
    }

    public function openings()
    {
        return $this->hasMany(AssetOpening::class);
    }

    public function purchaseInvoiceItems()
    {
        return $this->hasMany(\App\Models\Purchase\PurchaseInvoiceItem::class, 'asset_id');
    }

    public function glTransactions()
    {
        return $this->hasMany(\App\Models\GlTransaction::class, 'asset_id');
    }

    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function maintenanceHistory()
    {
        return $this->hasMany(MaintenanceHistory::class);
    }

    public function revaluations()
    {
        return $this->hasMany(AssetRevaluation::class);
    }

    public function latestRevaluation()
    {
        return $this->hasOne(AssetRevaluation::class)->latestOfMany();
    }

    public function impairments()
    {
        return $this->hasMany(AssetImpairment::class);
    }

    public function latestImpairment()
    {
        return $this->hasOne(AssetImpairment::class)->latestOfMany();
    }

    public function revaluationReserves()
    {
        return $this->hasMany(RevaluationReserve::class);
    }

    public function disposals()
    {
        return $this->hasMany(\App\Models\Assets\AssetDisposal::class);
    }

    public function latestDisposal()
    {
        return $this->hasOne(\App\Models\Assets\AssetDisposal::class)->latestOfMany();
    }

    // HFS Relationships
    public function currentHfsRequest()
    {
        return $this->belongsTo(HfsRequest::class, 'current_hfs_id');
    }

    public function hfsAssets()
    {
        return $this->hasMany(HfsAsset::class, 'asset_id');
    }

    public function activeHfsAsset()
    {
        return $this->hasOne(HfsAsset::class, 'asset_id')
            ->where('status', 'classified')
            ->whereHas('hfsRequest', function($query) {
                $query->whereIn('status', ['approved', 'in_review']);
            });
    }

    /**
     * Check if asset can be disposed
     */
    public function canBeDisposed()
    {
        return $this->status === 'active' && !$this->disposals()->where('status', '!=', 'cancelled')->exists();
    }

    /**
     * Check if asset can be classified as HFS
     */
    public function canBeClassifiedAsHfs(): bool
    {
        // Asset must be active
        if ($this->status !== 'active') {
            return false;
        }

        // Asset must not already be classified as HFS
        if (in_array($this->hfs_status, ['pending', 'classified'])) {
            return false;
        }

        // Asset must not already be disposed
        if ($this->disposals()->where('status', '!=', 'cancelled')->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Check if depreciation is stopped
     */
    public function isDepreciationStopped(): bool
    {
        return $this->depreciation_stopped === true;
    }

    /**
     * Stop depreciation (for HFS classification)
     */
    public function stopDepreciation(string $reason = null): void
    {
        $this->update([
            'depreciation_stopped' => true,
            'depreciation_stopped_date' => now(),
            'depreciation_stopped_reason' => $reason ?? 'Classified as Held for Sale',
        ]);
    }

    /**
     * Resume depreciation (for HFS cancellation)
     */
    public function resumeDepreciation(): void
    {
        $this->update([
            'depreciation_stopped' => false,
            'depreciation_stopped_date' => null,
            'depreciation_stopped_reason' => null,
        ]);
    }

    /**
     * Get current carrying amount (considering revaluations and impairments)
     */
    public function getCurrentCarryingAmount()
    {
        if ($this->revalued_carrying_amount !== null) {
            return $this->revalued_carrying_amount;
        }

        $bookValue = AssetDepreciation::getCurrentBookValue($this->id);
        if ($bookValue !== null) {
            return $bookValue;
        }

        return $this->purchase_cost - AssetDepreciation::getAccumulatedDepreciation($this->id);
    }

    /**
     * Get current revaluation reserve balance
     */
    public function getCurrentReserveBalance()
    {
        return RevaluationReserve::getCurrentBalance($this->id, null, $this->company_id);
    }

    /**
     * Get the encoded ID attribute.
     */
    public function getEncodedIdAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'hash_id';
    }

    /**
     * Resolve the model instance for the given hash ID.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Try to decode the hash ID first
        $decoded = Hashids::decode($value);

        if (!empty($decoded)) {
            return static::where('id', $decoded[0])->first();
        }

        // Fallback to regular ID lookup
        return static::where('id', $value)->first();
    }

    /**
     * Get the hash ID for this model.
     */
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the hash ID for routing.
     */
    public function getRouteKey()
    {
        return Hashids::encode($this->id);
    }

    // Scopes
    public function scopeForBranch($query, $branchId)
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }

    public function scopeRevalued($query)
    {
        return $query->where('valuation_model', 'revaluation');
    }

    public function scopeImpaired($query)
    {
        return $query->where('is_impaired', true);
    }

    public function scopeHfsClassified($query)
    {
        return $query->where('hfs_status', 'classified');
    }

    public function scopeHfsPending($query)
    {
        return $query->where('hfs_status', 'pending');
    }

    public function scopeDepreciationStopped($query)
    {
        return $query->where('depreciation_stopped', true);
    }
}


