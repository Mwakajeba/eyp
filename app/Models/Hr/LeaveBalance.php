<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'opening_days',
        'carried_over_days',
        'accrued_days',
        'taken_days',
        'pending_hold_days',
        'expired_days',
        'adjusted_days',
        'as_of',
    ];

    protected $casts = [
        'opening_days' => 'decimal:2',
        'carried_over_days' => 'decimal:2',
        'accrued_days' => 'decimal:2',
        'taken_days' => 'decimal:2',
        'pending_hold_days' => 'decimal:2',
        'expired_days' => 'decimal:2',
        'adjusted_days' => 'decimal:2',
        'as_of' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    /**
     * Calculate total available days
     */
    public function getAvailableDaysAttribute()
    {
        return $this->opening_days
            + $this->carried_over_days
            + $this->accrued_days
            + $this->adjusted_days
            - $this->taken_days
            - $this->pending_hold_days
            - $this->expired_days;
    }

    /**
     * Get the latest balance for an employee and leave type
     */
    public static function getLatestBalance($employeeId, $leaveTypeId)
    {
        return static::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->orderBy('as_of', 'desc')
            ->first();
    }
}

