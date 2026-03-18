<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreReturn extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'store_issue_id',
        'store_requisition_id',
        'voucher_no',
        'return_date',
        'returned_by',
        'received_by',
        'total_amount',
        'reason',
        'description',
        'remarks',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'return_date' => 'date',
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

    public function storeIssue(): BelongsTo
    {
        return $this->belongsTo(StoreIssue::class);
    }

    public function storeRequisition(): BelongsTo
    {
        return $this->belongsTo(StoreRequisition::class);
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'returned_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'returned' => '<span class="badge bg-success">Returned</span>',
            'processed' => '<span class="badge bg-info">Processed</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>'
        ];

        return $badges[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function canBeProcessed(): bool
    {
        return $this->status === 'returned';
    }
}
