<?php

namespace App\Models\Investment;

use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentApproval extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'investment_approvals';

    protected $fillable = [
        'proposal_id',
        'approval_level',
        'approver_type',
        'approver_id',
        'approver_name',
        'status',
        'comments',
        'rejection_reason',
        'info_request',
        'approved_at',
        'rejected_at',
        'info_requested_at',
        'approval_signature',
        'is_digital_signature',
        'created_by',
    ];

    protected $casts = [
        'approval_level' => 'integer',
        'is_digital_signature' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'info_requested_at' => 'datetime',
    ];

    // Relationships
    public function proposal()
    {
        return $this->belongsTo(InvestmentProposal::class, 'proposal_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('approval_level', $level);
    }

    public function scopeByApprover($query, $approverId)
    {
        return $query->where('approver_id', $approverId);
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isInfoRequested(): bool
    {
        return $this->status === 'requested_info';
    }
}

