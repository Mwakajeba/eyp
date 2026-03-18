<?php

namespace App\Services\Hr;

use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeCompliance;
use App\Models\Hr\StatutoryRule;
use Carbon\Carbon;

class StatutoryComplianceValidationService
{
    /**
     * Validate employee compliance for all statutory requirements
     * 
     * @param Employee $employee
     * @param Carbon|null $date
     * @return array
     */
    public function validateEmployeeCompliance(Employee $employee, ?Carbon $date = null): array
    {
        $date = $date ?? now();
        $companyId = $employee->company_id;
        $violations = [];
        $warnings = [];
        $info = [];

        // Validate PAYE compliance (mandatory for all employees)
        $payeCompliance = $this->validatePAYE($employee, $date);
        if ($payeCompliance['status'] === 'error') {
            $violations[] = $payeCompliance;
        } elseif ($payeCompliance['status'] === 'warning') {
            $warnings[] = $payeCompliance;
        }

        // Validate NHIF compliance (always check, but severity depends on if enabled)
        $nhifCompliance = $this->validateNHIF($employee, $date);
        if ($nhifCompliance['status'] === 'error') {
            if ($employee->has_nhif) {
                // NHIF is enabled - missing member number is a violation
                $violations[] = $nhifCompliance;
            } else {
                // NHIF is not enabled - missing member number is informational
                $nhifCompliance['severity'] = 'low';
                $nhifCompliance['message'] = 'NHIF member number is missing. NHIF is not currently enabled for this employee.';
                $nhifCompliance['action_required'] = 'Add NHIF member number and enable NHIF if employee should have NHIF deductions';
                $info[] = $nhifCompliance;
            }
        } elseif ($nhifCompliance['status'] === 'warning') {
            if ($employee->has_nhif) {
                $warnings[] = $nhifCompliance;
            } else {
                // NHIF not enabled - warnings are informational
                $nhifCompliance['severity'] = 'low';
                $info[] = $nhifCompliance;
            }
        }

        // Validate Pension compliance (always check)
        $pensionCompliance = $this->validatePension($employee, $date);
        if ($pensionCompliance['status'] === 'error') {
            if ($employee->has_pension) {
                // Pension is enabled - missing social fund number is a violation
                $violations[] = $pensionCompliance;
            } else {
                // Pension is not enabled - missing social fund number is informational
                $pensionCompliance['severity'] = 'low';
                $pensionCompliance['message'] = 'Social fund number is missing. Pension is not currently enabled for this employee.';
                $pensionCompliance['action_required'] = 'Add social fund number and enable pension if employee should have pension deductions';
                $info[] = $pensionCompliance;
            }
        } elseif ($pensionCompliance['status'] === 'warning') {
            if ($employee->has_pension) {
                $warnings[] = $pensionCompliance;
            } else {
                // Pension not enabled - warnings are informational
                $pensionCompliance['severity'] = 'low';
                $info[] = $pensionCompliance;
            }
        }

        // Validate WCF compliance (always check)
        $wcfCompliance = $this->validateWCF($employee, $date);
        if ($wcfCompliance['status'] === 'warning') {
            if ($employee->has_wcf) {
                // WCF is enabled - missing compliance record is a warning
                $warnings[] = $wcfCompliance;
            } else {
                // WCF is not enabled - missing compliance record is informational
                $wcfCompliance['severity'] = 'low';
                $info[] = $wcfCompliance;
            }
        }

        // Validate SDL compliance (always check)
        $sdlCompliance = $this->validateSDL($employee, $date, $companyId);
        if ($sdlCompliance['status'] === 'warning') {
            if ($employee->has_sdl) {
                // SDL is enabled - missing compliance record is a warning
                $warnings[] = $sdlCompliance;
            } else {
                // SDL is not enabled - missing compliance record is informational
                $sdlCompliance['severity'] = 'low';
                $info[] = $sdlCompliance;
            }
        }

        // Validate HESLB compliance
        $heslbCompliance = $this->validateHESLB($employee, $date);
        if ($employee->has_heslb) {
            if ($heslbCompliance['status'] === 'error') {
                $violations[] = $heslbCompliance;
            } elseif ($heslbCompliance['status'] === 'warning') {
                $warnings[] = $heslbCompliance;
            }
        } else {
            // HESLB is not enabled, but check if loan exists
            $activeLoan = \App\Models\Hr\HeslbLoan::getActiveLoan($employee->id);
            if ($activeLoan) {
                $info[] = [
                    'type' => 'HESLB',
                    'status' => 'info',
                    'message' => 'HESLB loan exists but HESLB is not enabled for this employee.',
                    'severity' => 'low',
                    'action_required' => 'Enable HESLB flag if employee should have HESLB deductions',
                ];
            }
        }

        // Tanzania-specific validations
        $tanzaniaValidations = $this->validateTanzaniaSpecific($employee, $date);
        $violations = array_merge($violations, $tanzaniaValidations['violations']);
        $warnings = array_merge($warnings, $tanzaniaValidations['warnings']);
        $info = array_merge($info, $tanzaniaValidations['info']);

        return [
            'is_compliant' => empty($violations),
            'violations' => $violations,
            'warnings' => $warnings,
            'info' => $info,
            'compliance_score' => $this->calculateComplianceScore($violations, $warnings),
        ];
    }

    /**
     * Validate PAYE compliance
     */
    protected function validatePAYE(Employee $employee, Carbon $date): array
    {
        // PAYE is mandatory for all employees in Tanzania
        // Check if employee has TIN (Tax Identification Number)
        // TIN can be stored in two places:
        // 1. employee->tin (direct field)
        // 2. employee->complianceRecords()->where('compliance_type', 'paye')->compliance_number
        
        $compliance = $employee->complianceRecords()
            ->where('compliance_type', EmployeeCompliance::TYPE_PAYE)
            ->first();
        
        $tin = $employee->tin;
        $complianceNumber = $compliance ? $compliance->compliance_number : null;
        
        // Check if TIN exists in either location
        if (empty($tin) && empty($complianceNumber)) {
            return [
                'type' => 'PAYE',
                'status' => 'error',
                'message' => 'Employee missing TIN (Tax Identification Number). PAYE cannot be processed without TIN. Add TIN to employee record or PAYE compliance record.',
                'severity' => 'high',
                'action_required' => 'Add TIN to employee record or PAYE compliance record',
            ];
        }

        // If TIN exists in compliance record but not in employee record, suggest syncing
        if (!empty($complianceNumber) && empty($tin)) {
            return [
                'type' => 'PAYE',
                'status' => 'warning',
                'message' => 'TIN found in compliance record (' . $complianceNumber . ') but not in employee record. Consider syncing TIN to employee record for consistency.',
                'severity' => 'low',
                'action_required' => 'Sync TIN from compliance record to employee record (optional)',
            ];
        }

        // Check compliance record validity
        // If TIN exists, compliance record is optional (documentation)
        // Only warn if TIN exists but compliance record is missing/invalid
        if (!$compliance) {
            // If TIN exists, compliance record is optional - just a low severity warning
            if (!empty($tin) || !empty($complianceNumber)) {
                return [
                    'type' => 'PAYE',
                    'status' => 'warning',
                    'message' => 'PAYE compliance record not found. TIN exists, but compliance documentation is recommended.',
                    'severity' => 'low',
                    'action_required' => 'Add PAYE compliance record (optional)',
                ];
            }
            // If no TIN, this was already handled above as an error
        } elseif (!$compliance->isValid()) {
            // Compliance record exists but is invalid
            return [
                'type' => 'PAYE',
                'status' => 'warning',
                'message' => 'PAYE compliance record is invalid or expired.',
                'severity' => 'low',
                'action_required' => 'Update PAYE compliance record',
            ];
        }

        return [
            'type' => 'PAYE',
            'status' => 'ok',
            'message' => 'PAYE compliance verified',
        ];
    }

    /**
     * Validate NHIF compliance
     */
    protected function validateNHIF(Employee $employee, Carbon $date): array
    {
        // Check if member number is provided
        if (empty($employee->nhif_member_number)) {
            return [
                'type' => 'NHIF',
                'status' => 'error',
                'message' => 'NHIF member number is missing. Cannot process NHIF deductions without member number.',
                'severity' => 'high',
                'action_required' => 'Add NHIF member number to employee record',
            ];
        }

        // Check compliance record
        $compliance = $employee->complianceRecords()
            ->where('compliance_type', EmployeeCompliance::TYPE_NHIF)
            ->first();

        if (!$compliance) {
            return [
                'type' => 'NHIF',
                'status' => 'warning',
                'message' => 'NHIF compliance record not found.',
                'severity' => 'medium',
                'action_required' => 'Add NHIF compliance record',
            ];
        }

        if (!$compliance->isValid()) {
            return [
                'type' => 'NHIF',
                'status' => 'warning',
                'message' => 'NHIF compliance record is invalid or expired.',
                'severity' => 'medium',
                'action_required' => 'Update NHIF compliance record',
            ];
        }

        return [
            'type' => 'NHIF',
            'status' => 'ok',
            'message' => 'NHIF compliance verified',
        ];
    }

    /**
     * Validate Pension compliance
     */
    protected function validatePension(Employee $employee, Carbon $date): array
    {
        // Check if social fund number is provided
        if (empty($employee->social_fund_number)) {
            return [
                'type' => 'Pension',
                'status' => 'error',
                'message' => 'Social fund number is missing. Cannot process pension deductions without fund number.',
                'severity' => 'high',
                'action_required' => 'Add social fund number to employee record',
            ];
        }

        // Check compliance record
        $compliance = $employee->complianceRecords()
            ->where('compliance_type', EmployeeCompliance::TYPE_PENSION)
            ->first();

        if (!$compliance) {
            return [
                'type' => 'Pension',
                'status' => 'warning',
                'message' => 'Pension compliance record not found.',
                'severity' => 'medium',
                'action_required' => 'Add pension compliance record',
            ];
        }

        if (!$compliance->isValid()) {
            return [
                'type' => 'Pension',
                'status' => 'warning',
                'message' => 'Pension compliance record is invalid or expired.',
                'severity' => 'medium',
                'action_required' => 'Update pension compliance record',
            ];
        }

        return [
            'type' => 'Pension',
            'status' => 'ok',
            'message' => 'Pension compliance verified',
        ];
    }

    /**
     * Validate WCF compliance
     */
    protected function validateWCF(Employee $employee, Carbon $date): array
    {
        // WCF is employer-paid, so we just check documentation
        $compliance = $employee->complianceRecords()
            ->where('compliance_type', EmployeeCompliance::TYPE_WCF)
            ->first();

        if (!$compliance) {
            return [
                'type' => 'WCF',
                'status' => 'warning',
                'message' => 'WCF compliance record not found.',
                'severity' => 'low',
                'action_required' => 'Add WCF compliance record (optional)',
            ];
        }

        return [
            'type' => 'WCF',
            'status' => 'ok',
            'message' => 'WCF compliance verified',
        ];
    }

    /**
     * Validate SDL compliance
     */
    protected function validateSDL(Employee $employee, Carbon $date, int $companyId): array
    {
        // SDL is employer-paid and requires minimum employee count
        // Check compliance record for documentation
        $compliance = $employee->complianceRecords()
            ->where('compliance_type', EmployeeCompliance::TYPE_SDL)
            ->first();

        if (!$compliance) {
            return [
                'type' => 'SDL',
                'status' => 'warning',
                'message' => 'SDL compliance record not found.',
                'severity' => 'low',
                'action_required' => 'Add SDL compliance record (optional)',
            ];
        }

        if (!$compliance->isValid()) {
            return [
                'type' => 'SDL',
                'status' => 'warning',
                'message' => 'SDL compliance record is invalid or expired.',
                'severity' => 'low',
                'action_required' => 'Update SDL compliance record',
            ];
        }

        return [
            'type' => 'SDL',
            'status' => 'ok',
            'message' => 'SDL compliance verified',
        ];
    }

    /**
     * Validate HESLB compliance
     */
    protected function validateHESLB(Employee $employee, Carbon $date): array
    {
        // Check if employee has active HESLB loan
        $activeLoan = \App\Models\Hr\HeslbLoan::getActiveLoan($employee->id);

        if ($employee->has_heslb && !$activeLoan) {
            return [
                'type' => 'HESLB',
                'status' => 'warning',
                'message' => 'Employee has HESLB enabled but no active loan found.',
                'severity' => 'low',
                'action_required' => 'Verify HESLB loan status or disable HESLB flag',
            ];
        }

        if ($activeLoan && empty($activeLoan->loan_number)) {
            return [
                'type' => 'HESLB',
                'status' => 'error',
                'message' => 'HESLB loan number is missing.',
                'severity' => 'high',
                'action_required' => 'Add loan number to HESLB loan record',
            ];
        }

        return [
            'type' => 'HESLB',
            'status' => 'ok',
            'message' => 'HESLB compliance verified',
        ];
    }

    /**
     * Validate Tanzania-specific requirements
     */
    protected function validateTanzaniaSpecific(Employee $employee, Carbon $date): array
    {
        $violations = [];
        $warnings = [];
        $info = [];

        // Age-based validations
        if ($employee->date_of_birth) {
            $age = $employee->date_of_birth->diffInYears($date);
            
            // Check if employee is below minimum working age (18 in Tanzania)
            if ($age < 18) {
                $violations[] = [
                    'type' => 'Age',
                    'status' => 'error',
                    'message' => "Employee is below minimum working age (18 years). Current age: {$age} years.",
                    'severity' => 'high',
                    'action_required' => 'Verify employee age and employment eligibility',
                ];
            }

            // Check if employee is eligible for retirement benefits (60+)
            if ($age >= 60) {
                $info[] = [
                    'type' => 'Age',
                    'status' => 'info',
                    'message' => "Employee is 60 years or older. Verify retirement benefit eligibility.",
                    'severity' => 'low',
                ];
            }
        }

        // Identity document validation
        if (empty($employee->identity_number) || empty($employee->identity_document_type)) {
            $warnings[] = [
                'type' => 'Identity',
                'status' => 'warning',
                'message' => 'Employee identity document information is incomplete.',
                'severity' => 'medium',
                'action_required' => 'Add identity document type and number',
            ];
        }

        // Bank account validation (for payroll payments)
        if ($employee->include_in_payroll && empty($employee->bank_account_number)) {
            $warnings[] = [
                'type' => 'Bank Account',
                'status' => 'warning',
                'message' => 'Employee bank account number is missing. Payments cannot be processed.',
                'severity' => 'medium',
                'action_required' => 'Add bank account number for payroll payments',
            ];
        }

        return [
            'violations' => $violations,
            'warnings' => $warnings,
            'info' => $info,
        ];
    }

    /**
     * Calculate compliance score (0-100)
     * Score is based on required compliance types only
     * Penalties are weighted based on severity and importance
     */
    protected function calculateComplianceScore(array $violations, array $warnings): int
    {
        $baseScore = 100;
        
        // Deduct points for violations (critical issues)
        // Violations are more serious and should have higher impact
        foreach ($violations as $violation) {
            $type = $violation['type'] ?? '';
            
            // PAYE violations are critical - deduct more
            if ($type === 'PAYE') {
                $baseScore -= 30; // PAYE is mandatory for all employees
            } elseif ($violation['severity'] === 'high') {
                $baseScore -= 20;
            } elseif ($violation['severity'] === 'medium') {
                $baseScore -= 10;
            } else {
                $baseScore -= 5;
            }
        }

        // Deduct points for warnings (less critical but still important)
        // Warnings have less impact than violations
        foreach ($warnings as $warning) {
            $type = $warning['type'] ?? '';
            
            // PAYE warnings are important but not critical
            if ($type === 'PAYE') {
                $baseScore -= 10;
            } elseif ($warning['severity'] === 'high') {
                $baseScore -= 8;
            } elseif ($warning['severity'] === 'medium') {
                $baseScore -= 4;
            } else {
                $baseScore -= 1; // Low severity warnings have minimal impact
            }
        }

        // Ensure score stays within bounds
        return max(0, min(100, $baseScore));
    }

    /**
     * Validate company-level statutory compliance
     */
    public function validateCompanyCompliance(int $companyId, Carbon $date = null): array
    {
        $date = $date ?? now();
        $violations = [];
        $warnings = [];

        // Check if all required statutory rules exist
        $requiredRules = [
            StatutoryRule::TYPE_PAYE => 'PAYE',
            StatutoryRule::TYPE_NHIF => 'NHIF',
            StatutoryRule::TYPE_PENSION => 'Pension',
        ];

        foreach ($requiredRules as $ruleType => $ruleName) {
            $rule = StatutoryRule::getActiveRule($companyId, $ruleType, $date);
            if (!$rule) {
                $violations[] = [
                    'type' => $ruleName,
                    'status' => 'error',
                    'message' => "No active {$ruleName} statutory rule found for the company.",
                    'severity' => 'high',
                    'action_required' => "Create and activate {$ruleName} statutory rule",
                ];
            }
        }

        // Check SDL minimum employee requirement
        $sdlRule = StatutoryRule::getActiveRule($companyId, StatutoryRule::TYPE_SDL, $date);
        if ($sdlRule) {
            $minEmployees = $sdlRule->sdl_min_employees ?? 10;
            $totalEmployees = Employee::where('company_id', $companyId)
                ->where('status', 'active')
                ->count();

            if ($totalEmployees >= $minEmployees) {
                // Company is eligible for SDL
                if (!$sdlRule->sdl_employer_percent || $sdlRule->sdl_employer_percent == 0) {
                    $warnings[] = [
                        'type' => 'SDL',
                        'status' => 'warning',
                        'message' => "Company has {$totalEmployees} employees (meets SDL threshold of {$minEmployees}), but SDL rate is not configured.",
                        'severity' => 'medium',
                        'action_required' => 'Configure SDL employer percentage',
                    ];
                }
            }
        }

        return [
            'is_compliant' => empty($violations),
            'violations' => $violations,
            'warnings' => $warnings,
        ];
    }
}

