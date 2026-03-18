<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EligibilityRule extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_eligibility_rules';

    protected $fillable = [
        'company_id',
        'position_id',
        'vacancy_requisition_id',
        'rule_type',
        'rule_operator',
        'rule_value',
        'rule_description',
        'is_mandatory',
        'weight',
        'applies_to',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'rule_value' => 'array',
        'is_mandatory' => 'boolean',
        'weight' => 'decimal:2',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    // Rule types
    const TYPE_EDUCATION = 'education';
    const TYPE_EXPERIENCE = 'experience';
    const TYPE_CERTIFICATION = 'certification';
    const TYPE_SKILL = 'skill';
    const TYPE_SAFEGUARDING = 'safeguarding';
    const TYPE_AGE = 'age';
    const TYPE_OTHER = 'other';

    // Operators
    const OPERATOR_EQUALS = 'equals';
    const OPERATOR_GREATER_THAN = 'greater_than';
    const OPERATOR_LESS_THAN = 'less_than';
    const OPERATOR_CONTAINS = 'contains';
    const OPERATOR_IN = 'in';
    const OPERATOR_NOT_IN = 'not_in';
    const OPERATOR_BETWEEN = 'between';

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function vacancyRequisition(): BelongsTo
    {
        return $this->belongsTo(VacancyRequisition::class);
    }

    public function eligibilityChecks(): HasMany
    {
        return $this->hasMany(ApplicantEligibilityCheck::class, 'eligibility_rule_id');
    }

    /**
     * Scope for active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for mandatory rules
     */
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    /**
     * Scope for rules by position
     */
    public function scopeForPosition($query, $positionId)
    {
        return $query->where('position_id', $positionId);
    }

    /**
     * Scope for rules by vacancy requisition
     */
    public function scopeForVacancyRequisition($query, $vacancyRequisitionId)
    {
        return $query->where('vacancy_requisition_id', $vacancyRequisitionId);
    }
}
