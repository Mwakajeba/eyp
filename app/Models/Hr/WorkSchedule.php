<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSchedule extends Model
{
    use LogsActivity;

    protected $table = 'hr_work_schedules';

    protected $fillable = [
        'company_id',
        'schedule_code',
        'schedule_name',
        'weekly_pattern',
        'standard_daily_hours',
        'break_duration_minutes',
        'overtime_eligible',
        'description',
        'is_active',
    ];

    protected $casts = [
        'weekly_pattern' => 'array',
        'standard_daily_hours' => 'decimal:2',
        'break_duration_minutes' => 'integer',
        'overtime_eligible' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function employeeSchedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class, 'schedule_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get default weekly pattern (Monday-Friday)
     */
    public static function getDefaultWeeklyPattern(): array
    {
        return [
            'monday' => true,
            'tuesday' => true,
            'wednesday' => true,
            'thursday' => true,
            'friday' => true,
            'saturday' => false,
            'sunday' => false,
        ];
    }
}

