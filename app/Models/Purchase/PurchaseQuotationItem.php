<?php

namespace App\Models\Purchase;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Inventory\Item;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetCategory;
use App\Models\Intangible\IntangibleAssetCategory;

class PurchaseQuotationItem extends Model
{
    use LogsActivity;
    protected $fillable = [
        'purchase_id',
        'item_id',
        'item_type',
        'asset_id',
        'fixed_asset_category_id',
        'intangible_asset_category_id',
        'description',
        'unit_of_measure',
        'quantity',
        'unit_price',
        'tax_calculation_type',
        'vat_type',
        'vat_rate',
        'tax_amount',
        'total_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(PurchaseQuotation::class, 'purchase_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function fixedAssetCategory(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'fixed_asset_category_id');
    }

    public function intangibleAssetCategory(): BelongsTo
    {
        return $this->belongsTo(IntangibleAssetCategory::class, 'intangible_asset_category_id');
    }

    // Accessors
    public function getFormattedQuantityAttribute()
    {
        return number_format($this->quantity, 2);
    }

    public function getFormattedUnitPriceAttribute()
    {
        return number_format($this->unit_price, 2);
    }

    public function getFormattedTaxAmountAttribute()
    {
        return number_format($this->tax_amount, 2);
    }

    public function getFormattedTotalAmountAttribute()
    {
        return number_format($this->total_amount, 2);
    }
}
