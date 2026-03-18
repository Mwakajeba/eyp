<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreIssue extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'store_requisition_id',
        'voucher_no',
        'issue_date',
        'issued_to',
        'issued_by',
        'total_amount',
        'description',
        'remarks',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'total_amount' => 'decimal:2'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function storeRequisition(): BelongsTo
    {
        return $this->belongsTo(StoreRequisition::class);
    }

    public function issuedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_to');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(StoreReturn::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(\App\Models\Inventory\Movement::class, 'reference_id')
            ->where('reference_type', 'store_issue');
    }

    public function getActualTotalAttribute(): float
    {
        $total = 0;
        
        foreach ($this->storeRequisition->items as $item) {
            // Use item unit cost with fallback to product cost
            $unitCost = $item->unit_cost > 0 ? $item->unit_cost : ($item->product->cost_price ?? $item->product->unit_price ?? 0);
            
            // Calculate net quantity (issued - returned)
            $issuedQty = $item->quantity_issued ?? 0;
            $returnedQty = $item->quantity_returned ?? 0;
            $netQty = $issuedQty - $returnedQty;
            
            $total += $netQty * $unitCost;
        }
        
        return $total;
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'issued' => '<span class="badge bg-success">Issued</span>',
            'partially_returned' => '<span class="badge bg-warning">Partially Returned</span>',
            'fully_returned' => '<span class="badge bg-secondary">Fully Returned</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>'
        ];

        return $badges[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    public function canBeReturned(): bool
    {
        return in_array($this->status, ['issued', 'partially_returned']);
    }
}
