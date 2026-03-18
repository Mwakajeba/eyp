<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApproval extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'leave_request_id',
        'approver_id',
        'step',
        'decision',
        'comment',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }

    /**
     * Check if approval is pending
     */
    public function isPending()
    {
        return $this->decision === 'pending';
    }

    /**
     * Get decision badge color
     */
    public function getDecisionBadgeAttribute()
    {
        return match($this->decision) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'returned' => 'info',
            default => 'secondary',
        };
    }
}

