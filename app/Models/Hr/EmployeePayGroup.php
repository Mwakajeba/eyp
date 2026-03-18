<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayGroup extends Model
{
    use LogsActivity;

    protected $table = 'hr_employee_pay_groups';

    protected $fillable = [
        'employee_id',
        'pay_group_id',
        'effective_date',
        'end_date',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payGroup(): BelongsTo
    {
        return $this->belongsTo(PayGroup::class);
    }

    /**
     * Get current pay group for an employee
     */
    public static function getCurrentPayGroup($employeeId): ?PayGroup
    {
        $assignment = self::where('employee_id', $employeeId)
            ->where('effective_date', '<=', now())
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->with('payGroup')
            ->first();

        return $assignment?->payGroup;
    }
}

