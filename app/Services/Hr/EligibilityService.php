<?php

namespace App\Services\Hr;

use App\Models\Hr\Applicant;
use App\Models\Hr\ApplicantEligibilityCheck;
use App\Models\Hr\EligibilityRule;
use App\Models\Hr\VacancyRequisition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EligibilityService
{
    /**
     * Check applicant eligibility against all rules for a vacancy requisition
     *
     * @param Applicant $applicant
     * @param VacancyRequisition $vacancyRequisition
     * @return array ['eligible' => bool, 'passed_rules' => int,
     *                'failed_rules' => int, 'mandatory_failed' => int,
     *                'total_score' => float, 'checks' => array]
     */
    public function checkApplicantEligibility(
        Applicant $applicant,
        VacancyRequisition $vacancyRequisition
    ): array {
        // Get all active rules for this vacancy requisition or its position
        $rules = EligibilityRule::active()
            ->where('company_id', $applicant->company_id)
            ->where(function ($query) use ($vacancyRequisition) {
                $query->where('vacancy_requisition_id', $vacancyRequisition->id)
                    ->orWhere(function ($q) use ($vacancyRequisition) {
                        $q->where('position_id', $vacancyRequisition->position_id)
                            ->whereNull('vacancy_requisition_id');
                    });
            })
            ->orderBy('priority', 'desc')
            ->orderBy('is_mandatory', 'desc')
            ->get();

        if ($rules->isEmpty()) {
            return [
                'eligible' => true,
                'passed_rules' => 0,
                'failed_rules' => 0,
                'mandatory_failed' => 0,
                'total_score' => 100, // No rules means full score by default or 0? 100 makes more sense for "qualified"
                'checks' => [],
                'message' => 'No eligibility rules configured for this position.'
            ];
        }

        $checks = [];
        $passedCount = 0;
        $failedCount = 0;
        $mandatoryFailedCount = 0;
        $isEligible = true;
        $totalWeightedScore = 0;
        $totalWeightConfigured = $rules->sum('weight');

        DB::beginTransaction();
        try {
            foreach ($rules as $rule) {
                $checkResult = $this->checkRule($applicant, $rule);

                // Create or update eligibility check record
                $check = ApplicantEligibilityCheck::updateOrCreate(
                    [
                        'applicant_id' => $applicant->id,
                        'eligibility_rule_id' => $rule->id,
                        'vacancy_requisition_id' => $vacancyRequisition->id,
                    ],
                    [
                        'passed' => $checkResult['passed'],
                        'reason' => $checkResult['reason'],
                        'checked_value' => $checkResult['checked_value'],
                        'expected_value' => $checkResult['expected_value'],
                        'checked_at' => now(),
                    ]
                );

                $checks[] = [
                    'rule' => $rule,
                    'check' => $check,
                    'passed' => $checkResult['passed'],
                    'reason' => $checkResult['reason'],
                ];

                if ($checkResult['passed']) {
                    $passedCount++;
                    // Add to weighted score if passed
                    if ($rule->weight > 0) {
                        $totalWeightedScore += $rule->weight;
                    }
                } else {
                    $failedCount++;
                    // If mandatory rule failed, applicant is not eligible
                    if ($rule->is_mandatory) {
                        $mandatoryFailedCount++;
                        $isEligible = false;
                    }
                }
            }

            // Normalize score to 100 if weights were used
            $finalScore = $totalWeightConfigured > 0 
                ? ($totalWeightedScore / $totalWeightConfigured) * 100 
                : ($passedCount / $rules->count()) * 100;

            // Update applicant's total score
            $applicant->update(['total_eligibility_score' => $finalScore]);

            DB::commit();

            return [
                'eligible' => $isEligible,
                'passed_rules' => $passedCount,
                'failed_rules' => $failedCount,
                'mandatory_failed' => $mandatoryFailedCount,
                'total_score' => $finalScore,
                'total_rules' => $rules->count(),
                'checks' => $checks,
                'message' => $isEligible
                    ? "Applicant passed all mandatory eligibility checks ({$passedCount}/{$rules->count()} rules passed)."
                    : "Applicant failed {$mandatoryFailedCount} mandatory eligibility rule(s)."
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Eligibility check failed', [
                'applicant_id' => $applicant->id,
                'vacancy_requisition_id' => $vacancyRequisition->id,
                'error' => $e->getMessage()
            ]);

            return [
                'eligible' => false,
                'passed_rules' => 0,
                'failed_rules' => 0,
                'mandatory_failed' => 0,
                'total_score' => 0,
                'checks' => [],
                'message' => 'Error checking eligibility: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check a single rule against an applicant
     *
     * @param Applicant $applicant
     * @param EligibilityRule $rule
     * @return array
     */
    protected function checkRule(Applicant $applicant, EligibilityRule $rule): array
    {
        $ruleValue = $rule->rule_value ?? [];
        $operator = $rule->rule_operator;
        $ruleType = $rule->rule_type;

        // Get applicant value based on rule type
        $applicantValue = $this->getApplicantValue($applicant, $ruleType);

        // Perform comparison based on operator
        $passed = $this->compareValues($applicantValue, $operator, $ruleValue, $ruleType);

        $reason = $this->generateReason($applicantValue, $operator, $ruleValue, $ruleType, $passed);

        return [
            'passed' => $passed,
            'reason' => $reason,
            'checked_value' => $applicantValue,
            'expected_value' => $ruleValue,
        ];
    }

    /**
     * Get applicant value based on rule type
     *
     * @param Applicant $applicant
     * @param string $ruleType
     * @return mixed
     */
    protected function getApplicantValue(Applicant $applicant, string $ruleType)
    {
        $normalized = $applicant->normalizedProfile;

        switch ($ruleType) {
            case EligibilityRule::TYPE_EDUCATION:
                // Use normalized value if available
                if ($normalized && $normalized->education_level) {
                    return $normalized->education_level;
                }
                // Fallback to raw qualifications
                if (is_array($applicant->qualifications) && !empty($applicant->qualifications)) {
                    $qualNames = array_column($applicant->qualifications, 'qualification_name');
                    $qualLevels = array_column($applicant->qualifications, 'qualification_level');
                    return array_merge($qualNames, $qualLevels);
                }
                return $applicant->qualification ?? null;

            case EligibilityRule::TYPE_EXPERIENCE:
                return $normalized ? $normalized->years_of_experience : ($applicant->years_of_experience ?? 0);

            case EligibilityRule::TYPE_CERTIFICATION:
                if ($normalized && is_array($normalized->certifications) && !empty($normalized->certifications)) {
                    return $normalized->certifications;
                }
                if (is_array($applicant->qualifications) && !empty($applicant->qualifications)) {
                    $certs = [];
                    foreach ($applicant->qualifications as $q) {
                        if (isset($q['qualification_level']) && $q['qualification_level'] === 'professional') {
                            $certs[] = $q['qualification_name'];
                        }
                    }
                    if (!empty($certs)) {
                        return $certs;
                    }
                }
                return $applicant->certifications ?? [];

            case EligibilityRule::TYPE_SKILL:
                return $applicant->skills ?? [];

            case EligibilityRule::TYPE_SAFEGUARDING:
                return $applicant->safeguarding_clearance ?? false;

            case EligibilityRule::TYPE_AGE:
                if ($applicant->date_of_birth) {
                    return $applicant->date_of_birth->age;
                }
                return null;

            default:
                return null;
        }
    }

    /**
     * Compare values based on operator
     *
     * @param mixed $applicantValue
     * @param string $operator
     * @param mixed $ruleValue
     * @return bool
     */
    protected function compareValues($applicantValue, string $operator, $ruleValue, string $ruleType = 'other'): bool
    {
        if ($applicantValue === null) {
            return false;
        }

        // If applicant value is an array (e.g., multiple qualifications), 
        // we check if ANY of them satisfy the rule
        if (is_array($applicantValue)) {
            foreach ($applicantValue as $val) {
                if ($this->compareSingleValue($val, $operator, $ruleValue, $ruleType)) {
                    return true;
                }
            }
            return false;
        }

        return $this->compareSingleValue($applicantValue, $operator, $ruleValue, $ruleType);
    }

    /**
     * Compare a single value based on operator
     */
    protected function compareSingleValue($applicantValue, string $operator, $ruleValue, string $ruleType = 'other'): bool
    {
        if ($applicantValue === null || $applicantValue === '') {
            return false;
        }

        // Normalize values for string comparison to handle spelling/case/extra spaces
        $normalizedAppVal = $this->normalizeValue((string)$applicantValue);
        $normalizedRuleVal = is_array($ruleValue) 
            ? array_map([$this, 'normalizeValue'], $ruleValue) 
            : $this->normalizeValue((string)$ruleValue);

        switch ($operator) {
            case EligibilityRule::OPERATOR_EQUALS:
                return $normalizedAppVal === $normalizedRuleVal;

            case EligibilityRule::OPERATOR_GREATER_THAN:
                if (is_numeric($applicantValue) && is_numeric($ruleValue)) {
                    return (float)$applicantValue >= (float)$ruleValue;
                }
                if ($ruleType === EligibilityRule::TYPE_EDUCATION) {
                    return $this->getEducationLevelWeight($applicantValue) >= $this->getEducationLevelWeight($ruleValue);
                }
                return false;

            case EligibilityRule::OPERATOR_LESS_THAN:
                if (is_numeric($applicantValue) && is_numeric($ruleValue)) {
                    return (float)$applicantValue <= (float)$ruleValue;
                }
                if ($ruleType === EligibilityRule::TYPE_EDUCATION) {
                    return $this->getEducationLevelWeight($applicantValue) <= $this->getEducationLevelWeight($ruleValue);
                }
                return false;

            case EligibilityRule::OPERATOR_CONTAINS:
                if (is_array($ruleValue)) {
                    foreach ($normalizedRuleVal as $rv) {
                        if (str_contains($normalizedAppVal, $rv)) {
                            return true;
                        }
                    }
                    return false;
                }
                return str_contains($normalizedAppVal, $normalizedRuleVal);

            case EligibilityRule::OPERATOR_IN:
                $vals = is_array($normalizedRuleVal) ? $normalizedRuleVal : explode(',', $normalizedRuleVal);
                return in_array($normalizedAppVal, $vals);

            case EligibilityRule::OPERATOR_NOT_IN:
                $vals = is_array($normalizedRuleVal) ? $normalizedRuleVal : explode(',', $normalizedRuleVal);
                return !in_array($normalizedAppVal, $vals);

            case EligibilityRule::OPERATOR_BETWEEN:
                if (is_numeric($applicantValue)) {
                    $vals = is_array($ruleValue) ? $ruleValue : explode(',', (string)$ruleValue);
                    if (count($vals) >= 2) {
                        return (float)$applicantValue >= (float)$vals[0] && (float)$applicantValue <= (float)$vals[1];
                    }
                }
                return false;

            default:
                return false;
        }
    }

    /**
     * Normalize string values to handle spelling differences, case, and extra whitespace
     */
    protected function normalizeValue(string $value): string
    {
        // Convert to lowercase, remove punctuation (like ' in Bachelor's), and collapse spaces
        $normalized = strtolower($value);
        $normalized = str_replace(["'", '-', '_', '.', ','], '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));
        return $normalized;
    }

    /**
     * Get numeric weight for education levels to allow >= comparison
     */
    protected function getEducationLevelWeight($level): int
    {
        $normalized = $this->normalizeValue((string)$level);
        
        // Define hierarchy (higher is better)
        $weights = [
            'phd' => 100,
            'doctorate' => 100,
            'masters' => 80,
            'master' => 80,
            'postgraduate' => 70,
            'degree' => 60,
            'bachelor' => 60,
            'bachelors' => 60,
            'advanced diploma' => 50,
            'diploma' => 40,
            'certificate' => 20,
            'primary' => 5,
            'secondary' => 10,
        ];

        foreach ($weights as $key => $weight) {
            if (str_contains($normalized, $key)) {
                return $weight;
            }
        }

        return 0;
    }

    /**
     * Generate human-readable reason for pass/fail
     *
     * @param mixed $applicantValue
     * @param string $operator
     * @param mixed $ruleValue
     * @param string $ruleType
     * @param bool $passed
     * @return string
     */
    protected function generateReason($applicantValue, string $operator, $ruleValue, string $ruleType, bool $passed): string
    {
        $applicantValueStr = is_array($applicantValue)
            ? implode(', ', $applicantValue)
            : (string)$applicantValue;
        $ruleValueStr = is_array($ruleValue)
            ? implode(', ', $ruleValue)
            : (string)$ruleValue;

        $operatorText = $this->getOperatorText($operator);
        if ($passed) {
            return "Applicant meets requirement: {$applicantValueStr} "
                . "{$operatorText} {$ruleValueStr}";
        } else {
            return "Applicant does not meet requirement: {$applicantValueStr} "
                . "{$operatorText} {$ruleValueStr}";
        }
    }

    /**
     * Get human-readable operator text
     *
     * @param string $operator
     * @return string
     */
    protected function getOperatorText(string $operator): string
    {
        return match ($operator) {
            EligibilityRule::OPERATOR_EQUALS => 'equals',
            EligibilityRule::OPERATOR_GREATER_THAN => 'is greater than',
            EligibilityRule::OPERATOR_LESS_THAN => 'is less than',
            EligibilityRule::OPERATOR_CONTAINS => 'contains',
            EligibilityRule::OPERATOR_IN => 'is in',
            EligibilityRule::OPERATOR_NOT_IN => 'is not in',
            EligibilityRule::OPERATOR_BETWEEN => 'is between',
            default => 'matches',
        };
    }

    /**
     * Get eligibility status for an applicant
     *
     * @param Applicant $applicant
     * @param VacancyRequisition|null $vacancyRequisition
     * @return array
     */
    public function getEligibilityStatus(Applicant $applicant, ?VacancyRequisition $vacancyRequisition = null): array
    {
        if (!$vacancyRequisition) {
            $vacancyRequisition = $applicant->vacancyRequisition;
        }

        if (!$vacancyRequisition) {
            return [
                'eligible' => null,
                'message' => 'No vacancy requisition associated with applicant.'
            ];
        }

        $checks = ApplicantEligibilityCheck::where('applicant_id', $applicant->id)
            ->where('vacancy_requisition_id', $vacancyRequisition->id)
            ->with('eligibilityRule')
            ->get();

        if ($checks->isEmpty()) {
            return [
                'eligible' => null,
                'message' => 'Eligibility not yet checked.'
            ];
        }

        $passedCount = $checks->where('passed', true)->count();
        $failedCount = $checks->where('passed', false)->count();
        $mandatoryFailed = $checks->filter(function ($check) {
            return !$check->passed && $check->eligibilityRule->is_mandatory;
        })->count();

        $isEligible = $mandatoryFailed === 0;

        return [
            'eligible' => $isEligible,
            'passed_rules' => $passedCount,
            'failed_rules' => $failedCount,
            'mandatory_failed' => $mandatoryFailed,
            'total_rules' => $checks->count(),
            'checks' => $checks,
            'message' => $isEligible
                ? "Eligible ({$passedCount}/{$checks->count()} rules passed)"
                : "Not eligible ({$mandatoryFailed} mandatory rule(s) failed)"
        ];
    }

    /**
     * Get failed rules for an applicant
     *
     * @param Applicant $applicant
     * @param VacancyRequisition|null $vacancyRequisition
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFailedRules(Applicant $applicant, ?VacancyRequisition $vacancyRequisition = null)
    {
        if (!$vacancyRequisition) {
            $vacancyRequisition = $applicant->vacancyRequisition;
        }

        if (!$vacancyRequisition) {
            return collect();
        }

        return ApplicantEligibilityCheck::where('applicant_id', $applicant->id)
            ->where('vacancy_requisition_id', $vacancyRequisition->id)
            ->where('passed', false)
            ->with('eligibilityRule')
            ->get();
    }
}
