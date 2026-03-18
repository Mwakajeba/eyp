<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HolidayCalendar extends Model
{
    use LogsActivity;

    protected $table = 'hr_holiday_calendars';

    protected $fillable = [
        'company_id',
        'branch_id',
        'calendar_name',
        'country',
        'region',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(HolidayCalendarDate::class, 'calendar_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if a date is a holiday
     */
    public function isHoliday($date): bool
    {
        return $this->holidays()
            ->where('holiday_date', $date)
            ->exists();
    }
}

