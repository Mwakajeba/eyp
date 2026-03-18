<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vinkla\Hashids\Facades\Hashids;

class StoreRequisition extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'requisition_number',
        'company_id',
        'branch_id',
        'department_id',
        'requested_by',
        'cost_center',
        'account_code',
        'asset_number',
        'purpose',
        'project_reference',
        'vehicle_reference',
        'required_date',
        'notes',
        'status',
        'priority',
        'current_approver_id',
        'current_approval_level',
        'submitted_at',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason'
    ];

    protected $casts = [
        'required_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function currentApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_approver_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StoreRequisitionItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(StoreRequisitionApproval::class);
    }

    public function storeIssues(): HasMany
    {
        return $this->hasMany(StoreIssue::class);
    }

    public function storeReturns(): HasMany
    {
        return $this->hasMany(StoreReturn::class);
    }

    public function issueVoucher(): BelongsTo
    {
        return $this->belongsTo(StoreIssue::class, 'issue_voucher_id');
    }

    public function returnVoucher(): BelongsTo
    {
        return $this->belongsTo(StoreReturn::class, 'return_voucher_id');
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, ['pending']);
    }

    public function canBeRejected(): bool
    {
        return in_array($this->status, ['pending']);
    }

    public function canBeIssued(): bool
    {
        return $this->status === 'approved';
    }

    public function canBeReturned(): bool
    {
        return in_array($this->status, ['partially_issued', 'fully_issued']);
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

        return $badges[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    /**
     * Get the hash ID for the store requisition
     *
     * @return string
     */
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the route key for the model
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'hash_id';
    }

    /**
     * Resolve the model from the route parameter
     *
     * @param string $value
     * @return \Illuminate\Database\Eloquent\Model|null
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
     * Get the route key for the model
     *
     * @return string
     */
    public function getRouteKey()
    {
        return $this->hash_id;
    }
}
