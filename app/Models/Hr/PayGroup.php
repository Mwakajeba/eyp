<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PayGroup extends Model
{
    use LogsActivity;

    protected $table = 'hr_pay_groups';

    protected $fillable = [
        'company_id',
        'pay_group_code',
        'pay_group_name',
        'description',
        'payment_frequency',
        'cut_off_day',
        'pay_day',
        'auto_adjust_weekends',
        'is_active',
    ];

    protected $casts = [
        'cut_off_day' => 'integer',
        'pay_day' => 'integer',
        'auto_adjust_weekends' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Payment frequencies
     */
    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_DAILY = 'daily';
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_BI_WEEKLY = 'bi-weekly';

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function employeePayGroups(): HasMany
    {
        return $this->hasMany(EmployeePayGroup::class, 'pay_group_id');
    }

    public function employees(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'hr_employee_pay_groups', 'pay_group_id', 'employee_id')
            ->withPivot('effective_date', 'end_date')
            ->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Calculate cut-off date for a given month/year
     */
    public function calculateCutOffDate($year, $month): \Carbon\Carbon
    {
        $date = Carbon::create($year, $month, $this->cut_off_day ?? 25);
        
        if ($this->auto_adjust_weekends) {
            // Adjust to previous working day if cut-off falls on weekend
            while ($date->isWeekend()) {
                $date->subDay();
            }
        }
        
        return $date;
    }

    /**
     * Calculate pay date for a given month/year
     */
    public function calculatePayDate($year, $month): \Carbon\Carbon
    {
        $date = Carbon::create($year, $month, $this->pay_day ?? 28);
        
        if ($this->auto_adjust_weekends) {
            // Adjust to next working day if pay date falls on weekend
            while ($date->isWeekend()) {
                $date->addDay();
            }
        }
        
        return $date;
    }
}

