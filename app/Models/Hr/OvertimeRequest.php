<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vinkla\Hashids\Facades\Hashids;

class OvertimeRequest extends Model
{
    use LogsActivity;

    protected $table = 'hr_overtime_requests';

    protected $fillable = [
        'employee_id',
        'attendance_id',
        'overtime_date',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'overtime_date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * Relationships
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OvertimeRequestLine::class, 'overtime_request_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Get total overtime hours from all lines
     */
    public function getTotalOvertimeHoursAttribute(): float
    {
        return $this->lines->sum('overtime_hours');
    }

    /**
     * Calculate total overtime amount
     */
    public function getOvertimeAmountAttribute(): float
    {
        return $this->lines->sum(function ($line) {
            return $line->overtime_hours * $line->overtime_rate;
        });
    }

    /**
     * Get the hash ID for the overtime request
     *
     * @return string
     */
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the route key for the model
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'hash_id';
    }

    /**
     * Resolve the model from the route parameter
     *
     * @param string $value
     * @param string|null $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Try to decode the hash ID first
        $decoded = Hashids::decode($value);
        
        if (!empty($decoded) && isset($decoded[0])) {
            $overtimeRequest = static::where('id', $decoded[0])->first();
            if ($overtimeRequest) {
                return $overtimeRequest;
            }
        }
        
        // Fallback to regular ID lookup (in case it's a numeric ID)
        if (is_numeric($value)) {
            return static::where('id', $value)->first();
        }
        
        // If neither hash ID nor numeric ID works, return null (will trigger 404)
        return null;
    }

    /**
     * Get the route key for the model
     *
     * @return string
     */
    public function getRouteKey()
    {
        return $this->hash_id;
    }
}

