<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HfsRequest extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'request_no',
        'initiator_id',
        'status',
        'intended_sale_date',
        'expected_close_date',
        'buyer_name',
        'customer_id',
        'buyer_contact',
        'buyer_address',
        'justification',
        'expected_costs_to_sell',
        'expected_fair_value',
        'probability_pct',
        'marketing_actions',
        'sale_price_range',
        'management_committed',
        'management_commitment_date',
        'exceeds_12_months',
        'extension_justification',
        'extension_approved_by',
        'extension_approved_at',
        'is_disposal_group',
        'disposal_group_description',
        'notes',
        'attachments',
        'current_approval_level',
        'submitted_by',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'is_overdue',
        'overdue_notified_at',
        'last_alert_sent_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'intended_sale_date' => 'date',
        'expected_close_date' => 'date',
        'management_commitment_date' => 'date',
        'expected_costs_to_sell' => 'decimal:2',
        'expected_fair_value' => 'decimal:2',
        'probability_pct' => 'decimal:2',
        'management_committed' => 'boolean',
        'exceeds_12_months' => 'boolean',
        'is_disposal_group' => 'boolean',
        'attachments' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'extension_approved_at' => 'datetime',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function initiator()
    {
        return $this->belongsTo(\App\Models\User::class, 'initiator_id');
    }

    public function extensionApprover()
    {
        return $this->belongsTo(\App\Models\User::class, 'extension_approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'rejected_by');
    }

    public function submittedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'submitted_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id');
    }

    public function hfsAssets()
    {
        return $this->hasMany(HfsAsset::class, 'hfs_id');
    }

    public function valuations()
    {
        return $this->hasMany(HfsValuation::class, 'hfs_id');
    }

    public function latestValuation()
    {
        return $this->hasOne(HfsValuation::class, 'hfs_id')->latestOfMany('valuation_date');
    }

    public function disposal()
    {
        return $this->hasOne(HfsDisposal::class, 'hfs_id');
    }

    public function discontinuedFlag()
    {
        return $this->hasOne(HfsDiscontinuedFlag::class, 'hfs_id');
    }

    public function approvals()
    {
        return $this->hasMany(HfsApproval::class, 'hfs_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(HfsAuditLog::class, 'hfs_id');
    }

    // Scopes
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'in_review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'approved')
            ->where('intended_sale_date', '<', now()->subMonths(12));
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['approved', 'in_review']);
    }

    // Helper methods
    public function isOverdue(): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }
        
        return $this->intended_sale_date->diffInMonths(now()) > 12;
    }

    public function getTotalCarryingAmountAttribute()
    {
        return $this->hfsAssets()->sum('carrying_amount_at_reclass');
    }

    public function getCurrentTotalCarryingAmountAttribute()
    {
        return $this->hfsAssets()->sum('current_carrying_amount');
    }
}
