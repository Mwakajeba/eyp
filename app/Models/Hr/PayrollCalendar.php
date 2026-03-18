<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PayrollCalendar extends Model
{
    use LogsActivity;

    protected $table = 'hr_payroll_calendars';

    protected $fillable = [
        'company_id',
        'calendar_year',
        'payroll_month',
        'cut_off_date',
        'pay_date',
        'is_locked',
        'locked_at',
        'locked_by',
        'notes',
    ];

    protected $casts = [
        'calendar_year' => 'integer',
        'payroll_month' => 'integer',
        'cut_off_date' => 'date',
        'pay_date' => 'date',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'locked_by');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(\App\Models\Payroll::class, 'payroll_calendar_id');
    }

    /**
     * Accessors
     */
    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->payroll_month, 1));
    }

    public function getPeriodLabelAttribute(): string
    {
        return $this->month_name . ' ' . $this->calendar_year;
    }

    /**
     * Scopes
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('calendar_year', $year);
    }

    public function scopeForMonth($query, $month)
    {
        return $query->where('payroll_month', $month);
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    public function scopeUpcoming($query)
    {
        $today = Carbon::today();
        return $query->where('pay_date', '>=', $today)
            ->orderBy('pay_date', 'asc');
    }

    /**
     * Check if calendar can be locked
     */
    public function canBeLocked(): bool
    {
        return !$this->is_locked && Carbon::today() >= $this->cut_off_date;
    }

    /**
     * Lock the calendar
     */
    public function lock($userId = null): bool
    {
        if (!$this->canBeLocked()) {
            return false;
        }

        $this->update([
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => $userId ?? auth()->id(),
        ]);

        return true;
    }

    /**
     * Unlock the calendar (requires permission)
     */
    public function unlock(): bool
    {
        $this->update([
            'is_locked' => false,
            'locked_at' => null,
            'locked_by' => null,
        ]);

        return true;
    }
}

