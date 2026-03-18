<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Inventory\Item;

class StoreRequisitionReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_requisition_return_id',
        'store_requisition_item_id',
        'product_id',
        'inventory_item_id',
        'quantity_returned',
        'unit_cost',
        'total_cost'
    ];

    protected $casts = [
        'quantity_returned' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2'
    ];

    public function storeRequisitionReturn(): BelongsTo
    {
        return $this->belongsTo(StoreRequisitionReturn::class);
    }

    public function storeRequisitionItem(): BelongsTo
    {
        return $this->belongsTo(StoreRequisitionItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'inventory_item_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'inventory_item_id');
    }
}
