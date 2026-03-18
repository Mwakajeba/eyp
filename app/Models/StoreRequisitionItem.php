<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Inventory\Item;

class StoreRequisitionItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'store_requisition_id',
        'inventory_item_id',
        'quantity_requested',
        'quantity_approved',
        'quantity_issued',
        'quantity_returned',
        'unit_cost',
        'unit_of_measure',
        'item_notes',
        'issue_notes',
        'status'
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:2',
        'quantity_approved' => 'decimal:2',
        'quantity_issued' => 'decimal:2',
        'quantity_returned' => 'decimal:2',
        'unit_cost' => 'decimal:2'
    ];

    public function storeRequisition(): BelongsTo
    {
        return $this->belongsTo(StoreRequisition::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'inventory_item_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => '<span class="badge bg-warning">Pending</span>',
            'approved' => '<span class="badge bg-success">Approved</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>',
            'partially_issued' => '<span class="badge bg-info">Partially Issued</span>',
            'fully_issued' => '<span class="badge bg-primary">Fully Issued</span>',
            'completed' => '<span class="badge bg-success">Completed</span>',
            'cancelled' => '<span class="badge bg-secondary">Cancelled</span>'
        ];

        return $badges[$this->status] ?? '<span class="badge bg-secondary">' . ucfirst($this->status) . '</span>';
    }

    public function getQuantityPendingAttribute(): float
    {
        return $this->quantity_requested - $this->quantity_issued;
    }

    public function getQuantityNotReturnedAttribute(): float
    {
        return $this->quantity_issued - $this->quantity_returned;
    }

    public function isFullyIssued(): bool
    {
        return $this->quantity_issued >= $this->quantity_requested;
    }

    public function isFullyReturned(): bool
    {
        return $this->quantity_returned >= $this->quantity_issued;
    }
}
