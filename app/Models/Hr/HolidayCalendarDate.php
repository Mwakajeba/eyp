<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolidayCalendarDate extends Model
{
    use LogsActivity;

    protected $table = 'hr_holiday_calendar_dates';

    protected $fillable = [
        'calendar_id',
        'holiday_date',
        'holiday_name',
        'holiday_type',
        'is_paid',
        'description',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'is_paid' => 'boolean',
    ];

    /**
     * Holiday type constants
     */
    const TYPE_PUBLIC = 'public';
    const TYPE_COMPANY = 'company';
    const TYPE_REGIONAL = 'regional';

    /**
     * Relationships
     */
    public function calendar(): BelongsTo
    {
        return $this->belongsTo(HolidayCalendar::class, 'calendar_id');
    }

    /**
     * Scopes
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    public function scopeForYear($query, $year)
    {
        return $query->whereYear('holiday_date', $year);
    }
}

