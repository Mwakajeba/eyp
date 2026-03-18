<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeExit extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_exits';

    protected $fillable = [
        'employee_id',
        'exit_number',
        'exit_type',
        'resignation_date',
        'effective_date',
        'notice_period_days',
        'exit_reason',
        'exit_interview_notes',
        'clearance_status',
        'final_pay_status',
        'final_pay_amount',
        'final_pay_notes',
        'exit_interview_conducted',
        'initiated_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'resignation_date' => 'date',
        'effective_date' => 'date',
        'final_pay_amount' => 'decimal:2',
        'exit_interview_conducted' => 'boolean',
        'approved_at' => 'datetime',
    ];

    const TYPE_RESIGNATION = 'resignation';
    const TYPE_TERMINATION = 'termination';
    const TYPE_RETIREMENT = 'retirement';
    const TYPE_CONTRACT_EXPIRY = 'contract_expiry';
    const TYPE_REDUNDANCY = 'redundancy';

    const CLEARANCE_STATUS_PENDING = 'pending';
    const CLEARANCE_STATUS_IN_PROGRESS = 'in_progress';
    const CLEARANCE_STATUS_COMPLETED = 'completed';

    const FINAL_PAY_STATUS_PENDING = 'pending';
    const FINAL_PAY_STATUS_CALCULATED = 'calculated';
    const FINAL_PAY_STATUS_APPROVED = 'approved';
    const FINAL_PAY_STATUS_PAID = 'paid';

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function clearanceItems()
    {
        return $this->hasMany(ExitClearanceItem::class, 'exit_id');
    }

    public function initiatedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'initiated_by');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function isClearanceCompleted(): bool
    {
        return $this->clearance_status === self::CLEARANCE_STATUS_COMPLETED;
    }

    public function canProcessFinalPay(): bool
    {
        return $this->isClearanceCompleted() && $this->final_pay_status === self::FINAL_PAY_STATUS_PENDING;
    }
}

