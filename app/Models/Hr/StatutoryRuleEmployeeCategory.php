<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatutoryRuleEmployeeCategory extends Model
{
    protected $table = 'hr_statutory_rule_employee_categories';

    protected $fillable = [
        'statutory_rule_id',
        'category_type',
        'category_value',
        'category_label',
    ];

    const TYPE_EMPLOYMENT_TYPE = 'employment_type';
    const TYPE_POSITION = 'position';
    const TYPE_DEPARTMENT = 'department';
    const TYPE_GRADE = 'grade';
    const TYPE_CUSTOM = 'custom';

    /**
     * Relationships
     */
    public function statutoryRule(): BelongsTo
    {
        return $this->belongsTo(StatutoryRule::class, 'statutory_rule_id');
    }

    /**
     * Check if employee matches this category
     */
    public function matchesEmployee(Employee $employee): bool
    {
        switch ($this->category_type) {
            case self::TYPE_EMPLOYMENT_TYPE:
                return $employee->employment_type === $this->category_value;
            
            case self::TYPE_POSITION:
                return $employee->position_id == $this->category_value;
            
            case self::TYPE_DEPARTMENT:
                return $employee->department_id == $this->category_value;
            
            case self::TYPE_GRADE:
                if ($employee->position && $employee->position->grade_id) {
                    return $employee->position->grade_id == $this->category_value;
                }
                return false;
            
            case self::TYPE_CUSTOM:
                // Custom logic can be extended
                return false;
            
            default:
                return false;
        }
    }
}

