<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreRequisitionApproval extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'store_requisition_id',
        'approval_level',
        'approver_id',
        'action',
        'action_date',
        'comments',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'action_date' => 'datetime'
    ];

    public function storeRequisition(): BelongsTo
    {
        return $this->belongsTo(StoreRequisition::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getActionBadgeAttribute(): string
    {
        $badges = [
            'approved' => '<span class="badge bg-success">Approved</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>',
            'pending' => '<span class="badge bg-warning">Pending</span>'
        ];

        return $badges[$this->action] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    public function isApproved(): bool
    {
        return $this->action === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->action === 'rejected';
    }

    public function isPending(): bool
    {
        return $this->action === 'pending';
    }
}
