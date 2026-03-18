<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfirmationRequest extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_confirmation_requests';

    protected $fillable = [
        'employee_id',
        'probation_start_date',
        'probation_end_date',
        'review_date',
        'performance_summary',
        'recommendation',
        'recommendation_type',
        'extension_months',
        'status',
        'requested_by',
        'reviewed_by_manager',
        'manager_reviewed_at',
        'reviewed_by_hr',
        'hr_reviewed_at',
        'approved_by',
        'approved_at',
        'confirmation_effective_date',
        'salary_adjustment_amount',
        'confirmation_bonus',
        'salary_adjusted',
    ];

    protected $casts = [
        'probation_start_date' => 'date',
        'probation_end_date' => 'date',
        'review_date' => 'date',
        'confirmation_effective_date' => 'date',
        'salary_adjustment_amount' => 'decimal:2',
        'confirmation_bonus' => 'decimal:2',
        'salary_adjusted' => 'boolean',
        'manager_reviewed_at' => 'datetime',
        'hr_reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_MANAGER_REVIEW = 'manager_review';
    const STATUS_HR_REVIEW = 'hr_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXTENDED = 'extended';

    const RECOMMENDATION_CONFIRM = 'confirm';
    const RECOMMENDATION_EXTEND = 'extend';
    const RECOMMENDATION_TERMINATE = 'terminate';

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function requestedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    public function managerReviewer()
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by_manager');
    }

    public function hrReviewer()
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by_hr');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function isProbationExpiring(int $days = 30): bool
    {
        return $this->probation_end_date->isFuture() 
            && $this->probation_end_date->diffInDays(now()) <= $days;
    }
}

