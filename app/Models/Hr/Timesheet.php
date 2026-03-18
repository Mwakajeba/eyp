<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timesheet extends Model
{
    use LogsActivity;

    protected $table = 'hr_timesheets';

    protected $fillable = [
        'company_id',
        'employee_id',
        'timesheet_date',
        'department_id',
        'activity_type',
        'project_reference',
        'normal_hours',
        'overtime_hours',
        'description',
        'priorities',
        'achievements',
        'status',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'approval_remarks',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'timesheet_date' => 'date',
        'normal_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Activity type constants
     */
    const ACTIVITY_WORK = 'work';
    const ACTIVITY_TRAINING = 'training';
    const ACTIVITY_MEETING = 'meeting';
    const ACTIVITY_CONFERENCE = 'conference';
    const ACTIVITY_PROJECT = 'project';
    const ACTIVITY_OTHER = 'other';

    /**
     * Status constants
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'rejected_by');
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('timesheet_date', [$startDate, $endDate]);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    /**
     * Helper methods
     */
    public function getTotalHoursAttribute(): float
    {
        return (float) $this->normal_hours + (float) $this->overtime_hours;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function canBeEdited(): bool
    {
        return $this->status === self::STATUS_DRAFT || $this->status === self::STATUS_REJECTED;
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === self::STATUS_DRAFT || $this->status === self::STATUS_REJECTED;
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    /**
     * Get activity type label
     */
    public function getActivityTypeLabelAttribute(): string
    {
        return match($this->activity_type) {
            self::ACTIVITY_WORK => 'Work',
            self::ACTIVITY_TRAINING => 'Training',
            self::ACTIVITY_MEETING => 'Meeting',
            self::ACTIVITY_CONFERENCE => 'Conference',
            self::ACTIVITY_PROJECT => 'Project',
            self::ACTIVITY_OTHER => 'Other',
            default => ucfirst($this->activity_type),
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_SUBMITTED => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary',
        };
    }
}
