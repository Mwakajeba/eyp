<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryStructure extends Model
{
    use LogsActivity;

    protected $table = 'hr_employee_salary_structure';

    protected $fillable = [
        'employee_id',
        'component_id',
        'amount',
        'percentage',
        'effective_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
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

    public function component(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class, 'component_id');
    }

    /**
     * Get current structure for an employee
     */
    public static function getCurrentStructure($employeeId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('employee_id', $employeeId)
            ->where('effective_date', '<=', now())
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->with('component')
            ->get();
    }

    /**
     * Get structure for a specific date
     */
    public static function getStructureForDate($employeeId, $date): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('employee_id', $employeeId)
            ->where('effective_date', '<=', $date)
            ->where(function($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date);
            })
            ->with('component')
            ->get();
    }
}

