<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Vinkla\Hashids\Facades\Hashids;

class StatutoryRule extends Model
{
    use LogsActivity;

    protected $table = 'hr_statutory_rules';

    protected $fillable = [
        'company_id',
        'rule_type',
        'rule_name',
        'description',
        'paye_brackets',
        'paye_tax_relief',
        'nhif_employee_percent',
        'nhif_employer_percent',
        'nhif_ceiling',
        'pension_employee_percent',
        'pension_employer_percent',
        'pension_ceiling',
        'pension_scheme_type',
        'wcf_employer_percent',
        'industry_type',
        'sdl_employer_percent',
        'sdl_threshold',
        'sdl_min_employees',
        'heslb_percent',
        'heslb_ceiling',
        'effective_from',
        'effective_to',
        'is_active',
        'apply_to_all_employees',
        'category_name',
        'category_description',
    ];

    protected $casts = [
        'paye_brackets' => 'array',
        'paye_tax_relief' => 'decimal:2',
        'nhif_employee_percent' => 'decimal:2',
        'nhif_employer_percent' => 'decimal:2',
        'nhif_ceiling' => 'decimal:2',
        'pension_employee_percent' => 'decimal:2',
        'pension_employer_percent' => 'decimal:2',
        'pension_ceiling' => 'decimal:2',
        'wcf_employer_percent' => 'decimal:2',
        'sdl_employer_percent' => 'decimal:2',
        'sdl_threshold' => 'decimal:2',
        'sdl_min_employees' => 'integer',
        'heslb_percent' => 'decimal:2',
        'heslb_ceiling' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
        'apply_to_all_employees' => 'boolean',
    ];

    const TYPE_PAYE = 'paye';
    const TYPE_NHIF = 'nhif';
    const TYPE_PENSION = 'pension';
    const TYPE_WCF = 'wcf';
    const TYPE_SDL = 'sdl';
    const TYPE_HESLB = 'heslb';

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function employeeCategories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StatutoryRuleEmployeeCategory::class, 'statutory_rule_id');
    }

    public function employees(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'hr_statutory_rule_employees', 'statutory_rule_id', 'employee_id')
            ->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForType($query, $type)
    {
        return $query->where('rule_type', $type);
    }

    public function scopeEffectiveForDate($query, $date = null)
    {
        $date = $date ?? now();
        return $query->where('effective_from', '<=', $date)
            ->where(function($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $date);
            });
    }

    /**
     * Get active rule for a type and date
     */
    public static function getActiveRule($companyId, $ruleType, $date = null): ?self
    {
        // Ensure date is in proper format for comparison
        if ($date instanceof \Carbon\Carbon) {
            $date = $date->format('Y-m-d');
        } elseif (is_string($date)) {
            // Already a string, use as is
        } else {
            $date = now()->format('Y-m-d');
        }
        return self::where('company_id', $companyId)
            ->where('rule_type', $ruleType)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(function($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhereDate('effective_to', '>=', $date);
            })
            ->orderBy('effective_from', 'desc')
            ->first();
    }

    /**
     * Get active rule for a type, date, and employee (with category matching and employee selection)
     */
    public static function getActiveRuleForEmployee($companyId, $ruleType, Employee $employee, $date = null): ?self
    {
        // Ensure date is in proper format for comparison
        if ($date instanceof \Carbon\Carbon) {
            $date = $date->format('Y-m-d');
        } elseif (is_string($date)) {
            // Already a string, use as is
        } else {
            $date = now()->format('Y-m-d');
        }
        
        // First, try to find rules with employee selection (apply_to_all_employees = false)
        $employeeSpecificRules = self::where('company_id', $companyId)
            ->where('rule_type', $ruleType)
            ->where('is_active', true)
            ->where('apply_to_all_employees', false)
            ->whereDate('effective_from', '<=', $date)
            ->where(function($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhereDate('effective_to', '>=', $date);
            })
            ->with(['employeeCategories', 'employees'])
            ->orderBy('effective_from', 'desc')
            ->get();

        // Check if employee is directly selected or matches any category
        foreach ($employeeSpecificRules as $rule) {
            // Check if employee is directly selected
            $isSelected = $rule->employees->contains('id', $employee->id);
            if ($isSelected) {
                return $rule;
            }
            
            // Check if employee matches any category
            foreach ($rule->employeeCategories as $category) {
                if ($category->matchesEmployee($employee)) {
                    return $rule;
                }
            }
        }

        // Fallback to universal rule (apply_to_all_employees = true)
        return self::where('company_id', $companyId)
            ->where('rule_type', $ruleType)
            ->where('is_active', true)
            ->where('apply_to_all_employees', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(function($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhereDate('effective_to', '>=', $date);
            })
            ->orderBy('effective_from', 'desc')
            ->first();
    }

    /**
     * Check if rule applies to employee
     */
    public function appliesToEmployee(Employee $employee): bool
    {
        // If applies to all employees, return true
        if ($this->apply_to_all_employees) {
            return true;
        }

        // Check if employee matches any category
        foreach ($this->employeeCategories as $category) {
            if ($category->matchesEmployee($employee)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate PAYE based on brackets
     * Formula: base_amount + (income_in_excess_of_threshold * rate / 100)
     */
    public function calculatePAYE($taxableIncome): float
    {
        if ($this->rule_type !== self::TYPE_PAYE || !$this->paye_brackets) {
            return 0;
        }

        // Sort brackets by threshold (ascending)
        $brackets = collect($this->paye_brackets)->sortBy('threshold');

        // Find the applicable bracket (highest threshold that income exceeds)
        $applicableBracket = null;
        foreach ($brackets->reverse() as $bracket) {
            $threshold = $bracket['threshold'] ?? 0;
            if ($taxableIncome > $threshold) {
                $applicableBracket = $bracket;
                break;
            }
        }

        // If no bracket found, income is below first threshold (no tax)
        if (!$applicableBracket) {
            return 0;
        }

        $threshold = $applicableBracket['threshold'] ?? 0;
        $rate = $applicableBracket['rate'] ?? 0;
        $baseAmount = $applicableBracket['base_amount'] ?? 0;

        // Calculate tax: base_amount + (income - threshold) * rate / 100
        $incomeInExcess = $taxableIncome - $threshold;
        $tax = $baseAmount + ($incomeInExcess * ($rate / 100));

        // Apply tax relief
        if ($this->paye_tax_relief) {
            $tax = max(0, $tax - $this->paye_tax_relief);
        }

        return round($tax, 2);
    }

    /**
     * Get the hash ID for the statutory rule
     *
     * @return string
     */
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the route key for the model
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
        // Try to decode the hash ID first
        $decoded = Hashids::decode($value);
        
        if (!empty($decoded) && isset($decoded[0])) {
            $rule = static::where('id', $decoded[0])->first();
            if ($rule) {
                return $rule;
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


