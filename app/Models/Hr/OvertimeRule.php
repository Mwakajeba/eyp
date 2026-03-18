<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRule extends Model
{
    use LogsActivity;

    protected $table = 'hr_overtime_rules';

    protected $fillable = [
        'company_id',
        'grade_id',
        'day_type',
        'overtime_rate',
        'max_hours_per_day',
        'requires_approval',
        'description',
        'is_active',
    ];

    protected $casts = [
        'overtime_rate' => 'decimal:2',
        'max_hours_per_day' => 'decimal:2',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Day type constants
     */
    const DAY_TYPE_WEEKDAY = 'weekday';
    const DAY_TYPE_WEEKEND = 'weekend';
    const DAY_TYPE_HOLIDAY = 'holiday';

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(JobGrade::class, 'grade_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForGrade($query, $gradeId)
    {
        return $query->where(function ($q) use ($gradeId) {
            $q->whereNull('grade_id')
              ->orWhere('grade_id', $gradeId);
        });
    }

    public function scopeForDayType($query, $dayType)
    {
        return $query->where('day_type', $dayType);
    }
}

