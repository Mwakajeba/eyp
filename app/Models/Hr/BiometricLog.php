<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricLog extends Model
{
    use LogsActivity;

    protected $table = 'hr_biometric_logs';

    protected $fillable = [
        'device_id',
        'device_user_id',
        'employee_id',
        'punch_time',
        'punch_type',
        'punch_mode',
        'status',
        'attendance_id',
        'raw_data',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'punch_time' => 'datetime',
        'raw_data' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSED = 'processed';
    const STATUS_FAILED = 'failed';
    const STATUS_DUPLICATE = 'duplicate';

    /**
     * Punch type constants
     */
    const PUNCH_CHECK_IN = 'check_in';
    const PUNCH_CHECK_OUT = 'check_out';
    const PUNCH_BREAK_IN = 'break_in';
    const PUNCH_BREAK_OUT = 'break_out';
    const PUNCH_OVERTIME_IN = 'overtime_in';
    const PUNCH_OVERTIME_OUT = 'overtime_out';

    /**
     * ZKTeco punch type mapping
     * Type: 0 = Check In, 1 = Check Out, 4 = Overtime In, 5 = Overtime Out
     */
    const ZKTECO_TYPE_MAP = [
        0 => self::PUNCH_CHECK_IN,
        1 => self::PUNCH_CHECK_OUT,
        2 => self::PUNCH_BREAK_OUT,  // Break start
        3 => self::PUNCH_BREAK_IN,   // Break end
        4 => self::PUNCH_OVERTIME_IN,
        5 => self::PUNCH_OVERTIME_OUT,
    ];

    /**
     * Get punch type from ZKTeco raw type
     */
    public static function getPunchTypeFromZKTeco(int $rawType): string
    {
        return self::ZKTECO_TYPE_MAP[$rawType] ?? self::PUNCH_CHECK_IN;
    }

    /**
     * Check if punch type is a clock-in type
     */
    public function isClockIn(): bool
    {
        return in_array($this->punch_type, [
            self::PUNCH_CHECK_IN,
            self::PUNCH_BREAK_IN,
            self::PUNCH_OVERTIME_IN,
        ]);
    }

    /**
     * Check if punch type is a clock-out type
     */
    public function isClockOut(): bool
    {
        return in_array($this->punch_type, [
            self::PUNCH_CHECK_OUT,
            self::PUNCH_BREAK_OUT,
            self::PUNCH_OVERTIME_OUT,
        ]);
    }

    /**
     * Check if punch type is overtime related
     */
    public function isOvertimePunch(): bool
    {
        return in_array($this->punch_type, [
            self::PUNCH_OVERTIME_IN,
            self::PUNCH_OVERTIME_OUT,
        ]);
    }

    /**
     * Check if punch type is break related
     */
    public function isBreakPunch(): bool
    {
        return in_array($this->punch_type, [
            self::PUNCH_BREAK_IN,
            self::PUNCH_BREAK_OUT,
        ]);
    }

    /**
     * Relationships
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class, 'device_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', self::STATUS_PROCESSED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('punch_time', [$startDate, $endDate]);
    }

    /**
     * Mark as processed
     */
    public function markAsProcessed($attendanceId = null)
    {
        $this->update([
            'status' => self::STATUS_PROCESSED,
            'attendance_id' => $attendanceId,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark as duplicate
     */
    public function markAsDuplicate()
    {
        $this->update([
            'status' => self::STATUS_DUPLICATE,
            'processed_at' => now(),
        ]);
    }
}

