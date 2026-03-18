<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryComponent extends Model
{
    use LogsActivity;

    protected $table = 'hr_salary_components';

    protected $fillable = [
        'company_id',
        'component_code',
        'component_name',
        'component_type',
        'description',
        'is_taxable',
        'is_pensionable',
        'is_nhif_applicable',
        'calculation_type',
        'calculation_formula',
        'ceiling_amount',
        'floor_amount',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
        'is_pensionable' => 'boolean',
        'is_nhif_applicable' => 'boolean',
        'ceiling_amount' => 'decimal:2',
        'floor_amount' => 'decimal:2',
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    const TYPE_EARNING = 'earning';
    const TYPE_DEDUCTION = 'deduction';

    const CALC_FIXED = 'fixed';
    const CALC_FORMULA = 'formula';
    const CALC_PERCENTAGE = 'percentage';

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function employeeStructures(): HasMany
    {
        return $this->hasMany(EmployeeSalaryStructure::class, 'component_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEarnings($query)
    {
        return $query->where('component_type', self::TYPE_EARNING);
    }

    public function scopeDeductions($query)
    {
        return $query->where('component_type', self::TYPE_DEDUCTION);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('component_name');
    }

    /**
     * Calculate component amount based on type
     */
    public function calculateAmount($baseAmount = 0, $employeeStructure = null): float
    {
        switch ($this->calculation_type) {
            case self::CALC_FIXED:
                $amount = $employeeStructure?->amount ?? 0;
                break;
            
            case self::CALC_PERCENTAGE:
                $percentage = $employeeStructure?->percentage ?? 0;
                $amount = $baseAmount * ($percentage / 100);
                break;
            
            case self::CALC_FORMULA:
                // For formula-based, we'll need to evaluate the formula
                // This is a simplified version - in production, use a proper formula parser
                $amount = $this->evaluateFormula($this->calculation_formula, $baseAmount, $employeeStructure);
                break;
            
            default:
                $amount = 0;
        }

        // Apply floor and ceiling
        if ($this->floor_amount !== null && $amount < $this->floor_amount) {
            $amount = $this->floor_amount;
        }
        if ($this->ceiling_amount !== null && $amount > $this->ceiling_amount) {
            $amount = $this->ceiling_amount;
        }

        return round($amount, 2);
    }

    /**
     * Evaluate formula (simplified - in production use a proper parser)
     */
    protected function evaluateFormula($formula, $baseAmount, $employeeStructure): float
    {
        // Replace placeholders
        $formula = str_replace('{base}', $baseAmount, $formula);
        $formula = str_replace('{amount}', $employeeStructure?->amount ?? 0, $formula);
        $formula = str_replace('{percentage}', $employeeStructure?->percentage ?? 0, $formula);

        // Basic evaluation (use with caution - consider using a proper math parser library)
        try {
            $result = eval("return $formula;");
            return is_numeric($result) ? (float)$result : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}

