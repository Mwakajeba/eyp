<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePromotion extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_employee_promotions';

    protected $fillable = [
        'employee_id',
        'promotion_number',
        'from_job_grade_id',
        'to_job_grade_id',
        'from_position_id',
        'to_position_id',
        'from_salary',
        'to_salary',
        'salary_increment',
        'increment_percentage',
        'effective_date',
        'promotion_reason',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'approval_notes',
        'salary_updated',
        'retroactive_applied',
        'retroactive_from_date',
    ];

    protected $casts = [
        'from_salary' => 'decimal:2',
        'to_salary' => 'decimal:2',
        'salary_increment' => 'decimal:2',
        'increment_percentage' => 'decimal:2',
        'effective_date' => 'date',
        'retroactive_from_date' => 'date',
        'salary_updated' => 'boolean',
        'retroactive_applied' => 'boolean',
        'approved_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function fromJobGrade()
    {
        return $this->belongsTo(JobGrade::class, 'from_job_grade_id');
    }

    public function toJobGrade()
    {
        return $this->belongsTo(JobGrade::class, 'to_job_grade_id');
    }

    public function fromPosition()
    {
        return $this->belongsTo(Position::class, 'from_position_id');
    }

    public function toPosition()
    {
        return $this->belongsTo(Position::class, 'to_position_id');
    }

    public function requestedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function calculateIncrement(): void
    {
        if ($this->from_salary && $this->to_salary) {
            $this->salary_increment = $this->to_salary - $this->from_salary;
            if ($this->from_salary > 0) {
                $this->increment_percentage = round(($this->salary_increment / $this->from_salary) * 100, 2);
            }
        }
    }

    public function isEffective(): bool
    {
        return $this->effective_date <= now() && $this->status === self::STATUS_APPROVED;
    }
}

