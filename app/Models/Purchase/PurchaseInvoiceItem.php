<?php

namespace App\Models\Purchase;

use App\Models\Inventory\Item as InventoryItem;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetCategory;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'purchase_invoice_id','item_type','inventory_item_id','asset_id','asset_category_id','asset_name','asset_code','asset_description','grn_item_id','description','quantity','unit_cost','vat_type','vat_rate','vat_amount','line_total','expiry_date','batch_number'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function invoice(): BelongsTo { return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id'); }
    public function inventoryItem(): BelongsTo { return $this->belongsTo(InventoryItem::class, 'inventory_item_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class, 'asset_id'); }
    public function assetCategory(): BelongsTo { return $this->belongsTo(AssetCategory::class, 'asset_category_id'); }
    public function grnItem(): BelongsTo { return $this->belongsTo(GoodsReceiptItem::class, 'grn_item_id'); }
    
    /**
     * Check if this item is an asset
     */
    public function isAsset(): bool
    {
        return $this->item_type === 'asset';
    }
    
    /**
     * Check if this item is inventory
     */
    public function isInventory(): bool
    {
        return $this->item_type === 'inventory' || ($this->item_type === null && $this->inventory_item_id !== null);
    }
}
