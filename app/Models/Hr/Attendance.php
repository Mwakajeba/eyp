<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use LogsActivity;

    protected $table = 'hr_attendance';

    protected $fillable = [
        'employee_id',
        'attendance_date',
        'schedule_id',
        'shift_id',
        'clock_in',
        'clock_out',
        'overtime_clock_in',
        'overtime_clock_out',
        'break_start',
        'break_end',
        'break_minutes',
        'expected_hours',
        'actual_hours',
        'normal_hours',
        'overtime_hours',
        'late_minutes',
        'early_exit_minutes',
        'status',
        'exception_type',
        'exception_reason',
        'is_approved',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'clock_in' => 'datetime:H:i:s',
        'clock_out' => 'datetime:H:i:s',
        'overtime_clock_in' => 'datetime:H:i:s',
        'overtime_clock_out' => 'datetime:H:i:s',
        'break_start' => 'datetime:H:i:s',
        'break_end' => 'datetime:H:i:s',
        'break_minutes' => 'integer',
        'expected_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'normal_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'late_minutes' => 'integer',
        'early_exit_minutes' => 'integer',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_PRESENT = 'present';
    const STATUS_ABSENT = 'absent';
    const STATUS_LATE = 'late';
    const STATUS_EARLY_EXIT = 'early_exit';
    const STATUS_ON_LEAVE = 'on_leave';

    /**
     * Exception type constants
     */
    const EXCEPTION_LATE = 'late';
    const EXCEPTION_EARLY_EXIT = 'early_exit';
    const EXCEPTION_MISSING_PUNCH = 'missing_punch';
    const EXCEPTION_ABSENT = 'absent';

    /**
     * Relationships
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class, 'schedule_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function overtimeRequests(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class, 'attendance_id');
    }

    /**
     * Scopes
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('attendance_date', [$startDate, $endDate]);
    }

    public function scopeWithExceptions($query)
    {
        return $query->whereNotNull('exception_type');
    }

    /**
     * Check if attendance has exceptions
     */
    public function hasException(): bool
    {
        return !empty($this->exception_type);
    }

    /**
     * Check if attendance is approved
     */
    public function isApproved(): bool
    {
        return $this->is_approved;
    }

    /**
     * Get all biometric logs for this attendance
     */
    public function biometricLogs(): HasMany
    {
        return $this->hasMany(BiometricLog::class, 'attendance_id');
    }

    /**
     * Check if has overtime clock in/out
     */
    public function hasOvertimePunch(): bool
    {
        return !empty($this->overtime_clock_in) || !empty($this->overtime_clock_out);
    }

    /**
     * Check if has break tracking
     */
    public function hasBreakPunch(): bool
    {
        return !empty($this->break_start) || !empty($this->break_end);
    }

    /**
     * Get formatted clock in time
     */
    public function getFormattedClockInAttribute(): ?string
    {
        return $this->clock_in ? $this->clock_in->format('H:i') : null;
    }

    /**
     * Get formatted clock out time
     */
    public function getFormattedClockOutAttribute(): ?string
    {
        return $this->clock_out ? $this->clock_out->format('H:i') : null;
    }

    /**
     * Get formatted overtime clock in time
     */
    public function getFormattedOvertimeClockInAttribute(): ?string
    {
        return $this->overtime_clock_in ? $this->overtime_clock_in->format('H:i') : null;
    }

    /**
     * Get formatted overtime clock out time
     */
    public function getFormattedOvertimeClockOutAttribute(): ?string
    {
        return $this->overtime_clock_out ? $this->overtime_clock_out->format('H:i') : null;
    }
}

