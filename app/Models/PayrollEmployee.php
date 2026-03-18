<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Hr\Employee;
use App\Traits\LogsActivity;
use Vinkla\Hashids\Facades\Hashids;

class PayrollEmployee extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'basic_salary',
        'allowance',
        'other_allowances',
        'overtime',
        'overtime_hours',
        'paye',
        'pension',
        'insurance',
        'salary_advance',
        'loans',
        'trade_union',
        'sdl',
        'wcf',
        'heslb',
        'other_deductions',
        'gross_salary',
        'total_deductions',
        'net_salary',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'allowance' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'overtime' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'paye' => 'decimal:2',
        'pension' => 'decimal:2',
        'insurance' => 'decimal:2',
        'salary_advance' => 'decimal:2',
        'loans' => 'decimal:2',
        'trade_union' => 'decimal:2',
        'sdl' => 'decimal:2',
        'wcf' => 'decimal:2',
        'heslb' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Accessors
     */
    public function getFormattedBasicSalaryAttribute(): string
    {
        return number_format($this->basic_salary, 2);
    }

    public function getFormattedAllowanceAttribute(): string
    {
        return number_format($this->allowance, 2);
    }

    public function getFormattedGrossSalaryAttribute(): string
    {
        return number_format($this->gross_salary, 2);
    }

    public function getFormattedTotalDeductionsAttribute(): string
    {
        return number_format($this->total_deductions, 2);
    }

    public function getFormattedNetSalaryAttribute(): string
    {
        return number_format($this->net_salary, 2);
    }

    /**
     * Calculate gross salary
     */
    public function calculateGrossSalary(): float
    {
        return $this->basic_salary + $this->allowance + $this->other_allowances + ($this->overtime ?? 0);
    }

    /**
     * Calculate total deductions
     */
    public function calculateTotalDeductions(): float
    {
        return $this->paye + 
               $this->pension + 
               $this->insurance + 
               $this->salary_advance + 
               $this->loans + 
               $this->trade_union + 
               $this->sdl + 
               $this->wcf + 
               $this->heslb + 
               $this->other_deductions;
    }

    /**
     * Calculate net salary
     */
    public function calculateNetSalary(): float
    {
        return $this->calculateGrossSalary() - $this->calculateTotalDeductions();
    }

    /**
     * Get the hash ID for the payroll employee
     *
     * @return string
     */
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the route key for the model
     * Return 'id' to prevent Laravel from trying to query 'hash_id' as a column
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'id';
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
        // Always try to decode hash ID first (regardless of field parameter)
        $decoded = Hashids::decode($value);
        
        if (!empty($decoded) && isset($decoded[0])) {
            $payrollEmployee = static::where('id', $decoded[0])->first();
            if ($payrollEmployee) {
                return $payrollEmployee;
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