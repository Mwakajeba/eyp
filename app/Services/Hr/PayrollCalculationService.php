<?php

namespace App\Services\Hr;

use App\Models\Hr\Employee;
use App\Models\Hr\SalaryComponent;
use App\Models\Hr\EmployeeSalaryStructure;
use App\Models\Hr\StatutoryRule;
use App\Models\Hr\Allowance;
use App\Models\Hr\SalaryAdvance;
use App\Models\Hr\ExternalLoan;
use App\Models\Hr\EmployeeCompliance;
use App\Services\Hr\StatutoryComplianceValidationService;
use Carbon\Carbon;

class PayrollCalculationService
{
    protected $attendanceService;
    protected $complianceValidationService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
        $this->complianceValidationService = new StatutoryComplianceValidationService();
    }

    /**
     * Calculate employee payroll using salary components and statutory rules
     */
    public function calculateEmployeePayroll(Employee $employee, $payrollYear, $payrollMonth, $companyId = null)
    {
        $companyId = $companyId ?? current_company_id();
        $startDate = Carbon::create($payrollYear, $payrollMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get employee salary structure
        $salaryStructure = EmployeeSalaryStructure::getStructureForDate($employee->id, $startDate);

        // Calculate earnings from salary components
        $earnings = $this->calculateEarnings($employee, $salaryStructure, $startDate, $companyId);
        
        // Calculate deductions from salary components
        $deductions = $this->calculateDeductions($employee, $salaryStructure, $earnings['gross'], $startDate, $companyId);

        // Calculate statutory deductions using rules
        // Pass the date as a string in Y-m-d format for proper comparison
        $statutoryDeductions = $this->calculateStatutoryDeductions($employee, $salaryStructure, $earnings['gross'], $earnings['taxable_gross'], $startDate->format('Y-m-d'), $companyId);
        
        // Run compliance validation
        $complianceValidation = $this->complianceValidationService->validateEmployeeCompliance($employee, $startDate);
        
        // Merge compliance warnings from validation service
        if (!empty($complianceValidation['violations']) || !empty($complianceValidation['warnings'])) {
            $statutoryDeductions['compliance_warnings'] = array_merge(
                $statutoryDeductions['compliance_warnings'] ?? [],
                array_merge($complianceValidation['violations'], $complianceValidation['warnings'])
            );
        }
        
        // Add compliance score
        $statutoryDeductions['compliance_score'] = $complianceValidation['compliance_score'] ?? 100;
        $statutoryDeductions['is_compliant'] = $complianceValidation['is_compliant'] ?? true;

        // Get other deductions (advances, loans)
        // Pass basic salary for percentage-based loan calculations (consistent with HESLB loans)
        $otherDeductions = $this->getOtherDeductions($employee, $payrollYear, $payrollMonth, $companyId, $earnings['basic_salary']);

        // Calculate totals
        $totalEarnings = $earnings['gross'];
        $totalDeductions = $deductions['total'] + $statutoryDeductions['total'] + $otherDeductions['total'];
        $netSalary = $totalEarnings - $totalDeductions;

        return [
            'basic_salary' => $earnings['basic_salary'],
            'allowance' => $earnings['allowances'],
            'other_allowances' => $earnings['other_allowances'],
            'overtime' => $earnings['overtime'],
            'overtime_hours' => $earnings['overtime_hours'] ?? 0,
            'overtime_breakdown' => $earnings['overtime_breakdown'] ?? [],
            'gross_salary' => $totalEarnings,
            'taxable_income' => $earnings['taxable_gross'],
            // Statutory deductions
            'paye' => $statutoryDeductions['paye'],
            'pension' => $statutoryDeductions['pension'],
            'insurance' => $statutoryDeductions['nhif'],
            'sdl' => $statutoryDeductions['sdl'],
            'wcf' => $statutoryDeductions['wcf'],
            'heslb' => $statutoryDeductions['heslb'],
            // Other deductions
            'salary_advance' => $otherDeductions['advances'],
            'loans' => $otherDeductions['loans'],
            'trade_union' => $deductions['trade_union'],
            // Employer contributions
            'employer_contributions' => $statutoryDeductions['employer_contributions'] ?? [],
            // Compliance warnings (soft enforcement)
            'compliance_warnings' => $statutoryDeductions['compliance_warnings'] ?? [],
            'other_deductions' => $deductions['other'],
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            // Component breakdown
            'earnings_breakdown' => $earnings['breakdown'],
            'deductions_breakdown' => array_merge($deductions['breakdown'], $statutoryDeductions['breakdown']),
        ];
    }

    /**
     * Calculate earnings from salary components
     */
    protected function calculateEarnings(Employee $employee, $salaryStructure, $date, $companyId)
    {
        // Priority order for basic salary:
        // 1. Salary Structure (if exists and has basic salary component)
        // 2. Active Contract salary (if exists)
        // 3. Employee basic_salary (fallback)
        
        // Get base salary for percentage calculations (contract or employee basic_salary)
        // This is used when calculating percentage-based components in salary structure
        $baseForPercentage = $this->getContractOrEmployeeSalary($employee, $date);
        
        $basicSalary = 0;
        $allowances = 0;
        $otherAllowances = 0;
        $overtime = 0;
        $breakdown = [];
        $taxableGross = 0;
        $hasBasicSalaryComponent = false;
        $hasAllowanceComponents = false;

        // Priority 1: Get earning components from Salary Structure
        if ($salaryStructure && $salaryStructure->isNotEmpty()) {
        $earningComponents = $salaryStructure->filter(function($structure) {
            return $structure->component->component_type == SalaryComponent::TYPE_EARNING 
                && $structure->component->is_active;
        });

        foreach ($earningComponents as $structure) {
            $component = $structure->component;
                $baseAmount = $baseForPercentage; // Base for percentage calculations (contract or employee salary)
            
            $amount = $component->calculateAmount($baseAmount, $structure);
            
            // Categorize
            if (str_contains(strtolower($component->component_code), 'basic')) {
                $basicSalary = $amount;
                    $hasBasicSalaryComponent = true;
            } elseif (str_contains(strtolower($component->component_code), 'allowance')) {
                    $hasAllowanceComponents = true;
                if ($component->is_taxable) {
                    $allowances += $amount;
                } else {
                    $otherAllowances += $amount;
                }
            } else {
                    // Other earning components (bonuses, commissions, etc.)
                if ($component->is_taxable) {
                    $allowances += $amount;
                } else {
                    $otherAllowances += $amount;
                }
            }

            $breakdown[] = [
                'component' => $component->component_name,
                'code' => $component->component_code,
                'amount' => $amount,
                'taxable' => $component->is_taxable,
                    'source' => 'salary_structure',
            ];

            if ($component->is_taxable) {
                $taxableGross += $amount;
            }
            }
        }

        // Priority 2 & 3: If no basic salary from structure, use contract or employee basic_salary
        if (!$hasBasicSalaryComponent) {
            $basicSalary = $this->getBaseSalary($employee, $date, $salaryStructure);
            if ($basicSalary > 0) {
                $taxableGross += $basicSalary;
                $breakdown[] = [
                    'component' => 'Basic Salary',
                    'code' => 'BASIC_SALARY',
                    'amount' => $basicSalary,
                    'taxable' => true,
                    'source' => $this->getContractOrEmployeeSalary($employee, $date) != $employee->basic_salary ? 'contract' : 'employee',
                ];
            }
        }

        // Priority 2 & 3: Get traditional allowances if no allowance components from structure
        if (!$hasAllowanceComponents) {
            // Check Contract allowances first (if contract has allowances field in future)
            // For now, check employee Allowance records
            // Note: hr_allowances table uses 'date' column, not 'effective_date' or 'end_date'
            // Filter by payroll month/year to get allowances for the specific payroll period
            $payrollYear = Carbon::parse($date)->year;
            $payrollMonth = Carbon::parse($date)->month;
            $traditionalAllowances = Allowance::where('employee_id', $employee->id)
                ->where('company_id', $companyId)
                ->whereYear('date', $payrollYear)
                ->whereMonth('date', $payrollMonth)
                ->where('is_active', true)
                ->get();
            
            foreach ($traditionalAllowances as $allowance) {
                $allowanceAmount = $allowance->amount ?? 0;
                $allowances += $allowanceAmount;
                $taxableGross += $allowanceAmount; // Assume taxable unless specified
                
                $breakdown[] = [
                    'component' => $allowance->name ?? 'Allowance',
                    'code' => 'ALLOWANCE',
                    'amount' => $allowanceAmount,
                    'taxable' => true,
                    'source' => 'employee_allowance',
                ];
            }
        }

        // Calculate overtime (from approved overtime requests)
        // Base salary priority: Salary Structure → Contract → Employee basic_salary
        $overtimeData = $this->calculateOvertime($employee, $date, $companyId);
        $overtime = $overtimeData['amount'];
        $overtimeHours = $overtimeData['hours'] ?? 0;
        $taxableGross += $overtime;

        $gross = $basicSalary + $allowances + $otherAllowances + $overtime;

        return [
            'basic_salary' => $basicSalary,
            'allowances' => $allowances,
            'other_allowances' => $otherAllowances,
            'overtime' => $overtime,
            'overtime_hours' => $overtimeHours,
            'overtime_breakdown' => $overtimeData['breakdown'] ?? [],
            'gross' => $gross,
            'taxable_gross' => $taxableGross,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate deductions from salary components
     * Priority: Salary Structure → Employee Settings
     */
    protected function calculateDeductions(Employee $employee, $salaryStructure, $grossSalary, $date, $companyId)
    {
        $total = 0;
        $tradeUnion = 0;
        $other = 0;
        $breakdown = [];
        $hasTradeUnionComponent = false;
        $hasOtherDeductionComponents = false;

        // Priority 1: Get deduction components from Salary Structure
        if ($salaryStructure && $salaryStructure->isNotEmpty()) {
        $deductionComponents = $salaryStructure->filter(function($structure) {
            return $structure->component->component_type == SalaryComponent::TYPE_DEDUCTION 
                && $structure->component->is_active;
        });

        foreach ($deductionComponents as $structure) {
            $component = $structure->component;
            $baseAmount = $grossSalary;
            
            $amount = $component->calculateAmount($baseAmount, $structure);

            // Categorize
            if (str_contains(strtolower($component->component_code), 'trade_union') || 
                str_contains(strtolower($component->component_code), 'union')) {
                $tradeUnion += $amount;
                    $hasTradeUnionComponent = true;
            } else {
                $other += $amount;
                    $hasOtherDeductionComponents = true;
            }

            $total += $amount;

            $breakdown[] = [
                'component' => $component->component_name,
                'code' => $component->component_code,
                'amount' => $amount,
                    'source' => 'salary_structure',
            ];
            }
        }

        // Priority 2: Fallback to employee's trade union information (if enabled and not in structure)
        if (!$hasTradeUnionComponent && $employee->has_trade_union) {
            $employeeTradeUnionAmount = 0;
            
            if ($employee->trade_union_category === 'amount') {
                // Fixed amount
                $employeeTradeUnionAmount = $employee->trade_union_amount ?? 0;
            } elseif ($employee->trade_union_category === 'percentage') {
                // Percentage of gross salary
                $percentage = $employee->trade_union_percent ?? 0;
                $employeeTradeUnionAmount = $grossSalary * ($percentage / 100);
            }
            
            if ($employeeTradeUnionAmount > 0) {
                $tradeUnion = $employeeTradeUnionAmount;
                $total += $tradeUnion;
                
                // Add to breakdown for transparency
                $breakdown[] = [
                    'component' => 'Trade Union Deduction',
                    'code' => 'TRADE_UNION_EMPLOYEE',
                    'amount' => round($tradeUnion, 2),
                    'source' => 'employee_settings',
                ];
            }
        }

        return [
            'total' => $total,
            'trade_union' => $tradeUnion,
            'other' => $other,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate statutory deductions using rules
     * Includes compliance checking with soft enforcement (warnings only)
     * Priority: Statutory Rules → Employee Settings
     */
    protected function calculateStatutoryDeductions(Employee $employee, $salaryStructure, $grossSalary, $taxableIncome, $date, $companyId)
    {
        $paye = 0;
        $pension = 0;
        $nhif = 0;
        $sdl = 0;
        $wcf = 0;
        $heslb = 0;
        $breakdown = [];
        $complianceWarnings = [];

        // Calculate PAYE
        if ($employee->has_pension && $employee->pension_employee_percent) {
            $pensionableAmount = $grossSalary * ($employee->pension_employee_percent / 100);
            $taxableIncome = $taxableIncome - $pensionableAmount;
        }

        // Use category-specific rule if available, otherwise fallback to universal rule
        $payeRule = StatutoryRule::getActiveRuleForEmployee($companyId, StatutoryRule::TYPE_PAYE, $employee, $date)
            ?? StatutoryRule::getActiveRule($companyId, StatutoryRule::TYPE_PAYE, $date);
        
        if ($payeRule) {
            $paye = $payeRule->calculatePAYE($taxableIncome);
        } else {
            // Fallback to hardcoded brackets
            $paye = $this->calculatePAYEFallback($taxableIncome);
        }

        // Tanzania-specific PAYE exemptions
        // Age-based exemption: Employees 60+ may have different tax treatment
        // Convert date string to Carbon object if needed
        $dateForExemption = is_string($date) ? Carbon::parse($date) : $date;
        $ageExemption = $this->calculateAgeBasedExemption($employee, $dateForExemption);
        if ($ageExemption > 0) {
            $paye = max(0, $paye - $ageExemption);
            $breakdown[] = [
                'component' => 'Age-Based Tax Exemption',
                'code' => 'AGE_EXEMPTION',
                'amount' => -round($ageExemption, 2),
                'source' => 'tanzania_specific',
            ];
        }

        // Check PAYE compliance (warning only - PAYE is universal)
        $payeCompliance = $employee->complianceRecords()
            ->where('compliance_type', EmployeeCompliance::TYPE_PAYE)
            ->first();
        if (!$payeCompliance || !$payeCompliance->isValid()) {
            $complianceWarnings[] = [
                'type' => 'PAYE',
                'message' => 'PAYE compliance record is missing or invalid',
                'severity' => 'warning',
            ];
        }

        // Calculate Pension
        // Priority: Statutory Rule → Employee Settings
        // Use category-specific rule if available
        $pensionRule = StatutoryRule::getActiveRuleForEmployee($companyId, StatutoryRule::TYPE_PENSION, $employee, $date)
            ?? StatutoryRule::getActiveRule($companyId, StatutoryRule::TYPE_PENSION, $date);
        // If rule applies to all employees, apply regardless of individual employee setting
        // Otherwise, check employee's has_pension flag
        if ($pensionRule && ($pensionRule->apply_to_all_employees || $employee->has_pension)) {
            // Use employee-specific percent if provided (overrides statutory), otherwise use statutory
            $percent = $employee->pension_employee_percent ?? $pensionRule->pension_employee_percent ?? 0;
            $base = $grossSalary;
            if ($pensionRule->pension_ceiling) {
                $base = min($base, $pensionRule->pension_ceiling);
            }
            $pension = $base * ($percent / 100);
        } elseif ($employee->has_pension && $employee->pension_employee_percent) {
            // Fallback to employee setting if no statutory rule
            $pension = $grossSalary * ($employee->pension_employee_percent / 100);
        }

        // Check Pension compliance (warning only - don't block)
        if ($employee->has_pension) {
            $pensionCompliance = $employee->complianceRecords()
                ->where('compliance_type', EmployeeCompliance::TYPE_PENSION)
                ->first();
            if (!$pensionCompliance || !$pensionCompliance->isValid()) {
                $complianceWarnings[] = [
                    'type' => 'Pension',
                    'message' => 'Pension compliance record is missing or invalid',
                    'severity' => 'warning',
                ];
            }
        }

        // Calculate NHIF
        // Priority: Statutory Rule → Employee Settings
        // Base: Use basic salary from Salary Structure → Contract → Employee basic_salary
        // Use category-specific rule if available
        $nhifRule = StatutoryRule::getActiveRuleForEmployee($companyId, StatutoryRule::TYPE_NHIF, $employee, $date)
            ?? StatutoryRule::getActiveRule($companyId, StatutoryRule::TYPE_NHIF, $date);
        // If rule applies to all employees, apply regardless of individual employee setting
        // Otherwise, check employee's has_nhif flag
        if ($nhifRule && ($nhifRule->apply_to_all_employees || $employee->has_nhif)) {
            // Use employee-specific percent if provided (overrides statutory), otherwise use statutory
            $percent = $employee->nhif_employee_percent ?? $nhifRule->nhif_employee_percent ?? 0;
            // Get base salary following priority: Structure → Contract → Employee
            $base = $this->getBaseSalary($employee, $date, $salaryStructure);
            if ($base == 0) {
                $base = $grossSalary; // Fallback to gross if no base salary found
            }
            if ($nhifRule->nhif_ceiling) {
                $base = min($base, $nhifRule->nhif_ceiling);
            }
            $nhif = $base * ($percent / 100);
        } elseif ($employee->has_nhif && $employee->nhif_employee_percent) {
            // Fallback to employee setting if no statutory rule
            $base = $this->getBaseSalary($employee, $date, $salaryStructure);
            if ($base == 0) {
                $base = $grossSalary;
            }
            $nhif = $base * ($employee->nhif_employee_percent / 100);
        }

        // Check NHIF compliance (warning only - don't block)
        if ($employee->has_nhif) {
            $nhifCompliance = $employee->complianceRecords()
                ->where('compliance_type', EmployeeCompliance::TYPE_NHIF)
                ->first();
            if (!$nhifCompliance || !$nhifCompliance->isValid()) {
                $complianceWarnings[] = [
                    'type' => 'NHIF',
                    'message' => 'NHIF compliance record is missing or invalid',
                    'severity' => 'warning',
                ];
            }
        }

        // Calculate WCF
        // Use category-specific rule if available
        $wcfRule = StatutoryRule::getActiveRuleForEmployee($companyId, StatutoryRule::TYPE_WCF, $employee, $date)
            ?? StatutoryRule::getActiveRule($companyId, StatutoryRule::TYPE_WCF, $date);
        // If rule applies to all employees, apply regardless of individual employee setting
        // Otherwise, check employee's has_wcf flag
        if ($wcfRule && ($wcfRule->apply_to_all_employees || $employee->has_wcf)) {
            $percent = $wcfRule->wcf_employer_percent ?? 0;
            $wcf = $grossSalary * ($percent / 100);
        } elseif ($employee->has_wcf) {
            $wcf = $grossSalary * 0.01; // Fallback 1%
        }

        // Check WCF compliance (documentation only - optional)
        if ($employee->has_wcf) {
            $wcfCompliance = $employee->complianceRecords()
                ->where('compliance_type', EmployeeCompliance::TYPE_WCF)
                ->first();
            if (!$wcfCompliance || !$wcfCompliance->isValid()) {
                $complianceWarnings[] = [
                    'type' => 'WCF',
                    'message' => 'WCF compliance record is missing or invalid',
                    'severity' => 'info',
                ];
            }
        }

        // Calculate SDL
        // Use category-specific rule if available
        $sdlRule = StatutoryRule::getActiveRuleForEmployee($companyId, StatutoryRule::TYPE_SDL, $employee, $date)
            ?? StatutoryRule::getActiveRule($companyId, StatutoryRule::TYPE_SDL, $date);
        // If rule applies to all employees, apply regardless of individual employee setting
        // Otherwise, check employee's has_sdl flag
        if ($sdlRule && ($sdlRule->apply_to_all_employees || $employee->has_sdl)) {
            // Check minimum employee count requirement
            $minEmployees = $sdlRule->sdl_min_employees ?? 10;
            $totalEmployees = Employee::where('company_id', $companyId)
                ->where('status', 'active')
                ->count();
            
            if ($totalEmployees >= $minEmployees) {
                $percent = $sdlRule->sdl_employer_percent ?? 0;
                $base = $grossSalary;
                
                // Check payroll threshold if set
                if ($sdlRule->sdl_threshold && $base < $sdlRule->sdl_threshold) {
                    $sdl = 0;
                } else {
                    $sdl = $base * ($percent / 100);
                }
            } else {
                // Company has fewer than minimum required employees
                $sdl = 0;
            }
        } elseif ($employee->has_sdl) {
            // Fallback: Check employee count even without rule
            $totalEmployees = Employee::where('company_id', $companyId)
                ->where('status', 'active')
                ->count();
            
            if ($totalEmployees >= 10) {
                $sdl = $grossSalary * 0.035; // Fallback 3.5% for 2025
            } else {
                $sdl = 0;
            }
        }

        // Check SDL compliance (documentation only - optional)
        if ($employee->has_sdl) {
            $sdlCompliance = $employee->complianceRecords()
                ->where('compliance_type', EmployeeCompliance::TYPE_SDL)
                ->first();
            if (!$sdlCompliance || !$sdlCompliance->isValid()) {
                $complianceWarnings[] = [
                    'type' => 'SDL',
                    'message' => 'SDL compliance record is missing or invalid',
                    'severity' => 'info',
                ];
            }
        }

        // Calculate HESLB
        // HESLB is employee-specific: only applies to employees with active loan balances
        // HESLB is calculated on basic salary (not gross salary)
        // Use category-specific rule if available
        $heslb = 0;
        $heslbRule = StatutoryRule::getActiveRuleForEmployee($companyId, StatutoryRule::TYPE_HESLB, $employee, $date)
            ?? StatutoryRule::getActiveRule($companyId, StatutoryRule::TYPE_HESLB, $date);
        
        // Check if employee has an active HESLB loan
        // This is the primary requirement - employee must have an active loan
        $activeLoan = \App\Models\Hr\HeslbLoan::getActiveLoan($employee->id);
        
        if ($activeLoan) {
            // Employee has an active loan - calculate deduction
            // Priority: Loan-specific deduction_percent → Statutory Rule → Employee setting → Default (5%)
            // Check if deduction_percent is set (not null and not zero)
            if ($activeLoan->deduction_percent !== null && $activeLoan->deduction_percent > 0) {
                // Use the loan-specific deduction percentage
                $percent = $activeLoan->deduction_percent;
            } elseif ($heslbRule && $heslbRule->heslb_percent !== null) {
                // Use statutory rule percentage
                $percent = $heslbRule->heslb_percent;
            } elseif ($employee->heslb_employee_percent !== null && $employee->heslb_employee_percent > 0) {
                // Use employee-specific percentage
                $percent = $employee->heslb_employee_percent;
            } else {
                // Default to 5%
                $percent = 5.0;
            }
            
            // Convert percentage to decimal if stored as percentage (e.g., 5.00 = 5%)
            // If percent is > 1, assume it's already a percentage and divide by 100
            if ($percent > 1) {
                $percent = $percent / 100;
            }
            
            // HESLB is calculated on basic salary, not gross salary
            // Get basic salary using priority: Salary Structure → Contract → Employee basic_salary
            $base = $this->getBaseSalary($employee, $date, $salaryStructure);
            
            // Apply ceiling if set in statutory rule
            if ($heslbRule && $heslbRule->heslb_ceiling) {
                $base = min($base, $heslbRule->heslb_ceiling);
            }
            
            // Calculate deduction amount
            $calculatedAmount = $base * $percent;
            
            // Cap deduction to outstanding balance (never deduct more than what's owed)
            $heslb = min($calculatedAmount, $activeLoan->outstanding_balance);
        }
        // If no active loan, HESLB = 0 (no deduction)

        $total = $paye + $pension + $nhif + $sdl + $wcf + $heslb;

        // Calculate employer contributions
        $employerContributions = $this->calculateEmployerContributions($employee, $salaryStructure, $grossSalary, $nhif, $pension, $wcf, $sdl, $date, $companyId);

        return [
            'paye' => round($paye, 2),
            'pension' => round($pension, 2),
            'nhif' => round($nhif, 2),
            'sdl' => round($sdl, 2),
            'wcf' => round($wcf, 2),
            'heslb' => round($heslb, 2),
            'total' => round($total, 2),
            'breakdown' => [
                ['component' => 'PAYE', 'amount' => round($paye, 2)],
                ['component' => 'Pension', 'amount' => round($pension, 2)],
                ['component' => 'NHIF', 'amount' => round($nhif, 2)],
                ['component' => 'SDL', 'amount' => round($sdl, 2)],
                ['component' => 'WCF', 'amount' => round($wcf, 2)],
                ['component' => 'HESLB', 'amount' => round($heslb, 2)],
            ],
            'employer_contributions' => $employerContributions,
            'compliance_warnings' => $complianceWarnings,
        ];
    }

    /**
     * Calculate employer contributions for statutory deductions
     */
    protected function calculateEmployerContributions(Employee $employee, $salaryStructure, $grossSalary, $nhifEmployee, $pensionEmployee, $wcf, $sdl, $date, $companyId)
    {
        $nhifEmployer = 0;
        $pensionEmployer = 0;

        // Calculate NHIF Employer Contribution
        $nhifRule = StatutoryRule::getActiveRuleForEmployee($companyId, StatutoryRule::TYPE_NHIF, $employee, $date)
            ?? StatutoryRule::getActiveRule($companyId, StatutoryRule::TYPE_NHIF, $date);
        // If rule applies to all employees, apply regardless of individual employee setting
        // Otherwise, check employee's has_nhif flag
        if ($nhifRule && ($nhifRule->apply_to_all_employees || $employee->has_nhif)) {
            // Use employee-specific employer percent if provided, otherwise use statutory
            $percent = $employee->nhif_employer_percent ?? $nhifRule->nhif_employer_percent ?? 0;
            if ($percent > 0) {
                // Use the same base as employee contribution
                $base = $this->getBaseSalary($employee, $date, $salaryStructure);
                if ($base == 0) {
                    $base = $grossSalary;
                }
                if ($nhifRule->nhif_ceiling) {
                    $base = min($base, $nhifRule->nhif_ceiling);
                }
                $nhifEmployer = $base * ($percent / 100);
            } elseif ($nhifRule->apply_to_all_employees && $nhifEmployee > 0) {
                // If rule applies to all but no employer percent, calculate based on employee contribution
                // This handles cases where employer percent is not set but employee contribution exists
                $nhifEmployer = $nhifEmployee * 0.1; // Default 10% of employee contribution
            } elseif (!$nhifRule->apply_to_all_employees && $nhifEmployee > 0) {
                // Only use fallback for employee-specific rules if employee contribution exists
                $nhifEmployer = $nhifEmployee * 0.1; // Default 10% of employee contribution
            }
        }

        // Calculate Pension Employer Contribution
        $pensionRule = StatutoryRule::getActiveRuleForEmployee($companyId, StatutoryRule::TYPE_PENSION, $employee, $date)
            ?? StatutoryRule::getActiveRule($companyId, StatutoryRule::TYPE_PENSION, $date);
        // If rule applies to all employees, apply regardless of individual employee setting
        // Otherwise, check employee's has_pension flag
        if ($pensionRule && ($pensionRule->apply_to_all_employees || $employee->has_pension)) {
            // Use employee-specific employer percent if provided, otherwise use statutory
            $percent = $employee->pension_employer_percent ?? $pensionRule->pension_employer_percent ?? 0;
            if ($percent > 0) {
                $base = $grossSalary;
                if ($pensionRule->pension_ceiling) {
                    $base = min($base, $pensionRule->pension_ceiling);
                }
                $pensionEmployer = $base * ($percent / 100);
            } elseif ($pensionRule->apply_to_all_employees && $pensionEmployee > 0) {
                // If rule applies to all but no employer percent, calculate based on employee contribution
                // This handles cases where employer percent is not set but employee contribution exists
                $pensionEmployer = $pensionEmployee * 0.1; // Default 10% of employee contribution
            } elseif (!$pensionRule->apply_to_all_employees && $pensionEmployee > 0) {
                // Only use fallback for employee-specific rules if employee contribution exists
                $pensionEmployer = $pensionEmployee * 0.1; // Default 10% of employee contribution
            }
        }

        // WCF and SDL are already employer contributions (calculated above)
        // They don't need separate calculation

        // Ensure all values are properly rounded and zero values are truly zero
        $nhifEmployer = round($nhifEmployer, 2);
        $pensionEmployer = round($pensionEmployer, 2);
        $wcf = round($wcf, 2);
        $sdl = round($sdl, 2);
        
        // Set to 0 if rounded value is effectively zero (handles floating point precision issues)
        if (abs($nhifEmployer) < 0.01) $nhifEmployer = 0;
        if (abs($pensionEmployer) < 0.01) $pensionEmployer = 0;
        if (abs($wcf) < 0.01) $wcf = 0;
        if (abs($sdl) < 0.01) $sdl = 0;
        
        return [
            'nhif_employer' => $nhifEmployer,
            'pension_employer' => $pensionEmployer,
            'wcf' => $wcf,
            'sdl' => $sdl,
            'total' => round($nhifEmployer + $pensionEmployer + $wcf + $sdl, 2),
        ];
    }

    /**
     * Calculate PAYE using fallback brackets
     */
    protected function calculatePAYEFallback($taxableIncome)
    {
        if ($taxableIncome > 1000000) {
            return ($taxableIncome - 1000000) * 0.30 + 128000;
        } elseif ($taxableIncome > 760000) {
            return ($taxableIncome - 760000) * 0.25 + 68000;
        } elseif ($taxableIncome > 520000) {
            return ($taxableIncome - 520000) * 0.20 + 20000;
        } elseif ($taxableIncome > 270000) {
            return ($taxableIncome - 270000) * 0.08;
        }
        return 0;
    }

    /**
     * Calculate overtime earnings
     * 
     * Integrates with OvertimeRequest and OvertimeRule to calculate:
     * - Approved overtime hours for the payroll period
     * - Overtime earnings based on hourly rate and overtime rate multiplier
     * 
     * Formula: Overtime Earnings = Approved Hours × Hourly Rate × Overtime Rate
     * 
     * Where:
     * - Hourly Rate = Base Salary ÷ Working Days Per Month ÷ Standard Daily Hours
     *   Base Salary Priority: Salary Structure → Contract → Employee basic_salary
     *   Working Days Per Month: Calculated from employee's Work Schedule weekly pattern (defaults to 22 if no schedule)
     *   Standard Daily Hours: From Work Schedule (defaults to 8 if not set)
     *   Note: Overtime itself is not a salary structure component, but uses base salary from structure/contract/employee
     * - Overtime Rate = From OvertimeRequestLine (stored at approval time, varies by day type)
     * 
     * @param Employee $employee
     * @param Carbon|string $date Payroll date
     * @param int $companyId
     * @return array ['amount' => float, 'hours' => float, 'breakdown' => array]
     */
    protected function calculateOvertime(Employee $employee, $date, $companyId)
    {
        // Get payroll period (start and end of month)
        $startDate = Carbon::parse($date)->startOfMonth();
        $endDate = Carbon::parse($date)->endOfMonth();
        
        // Get approved overtime requests for the payroll period with their lines
        $overtimeRequests = \App\Models\Hr\OvertimeRequest::where('employee_id', $employee->id)
            ->whereBetween('overtime_date', [$startDate, $endDate])
            ->where('status', \App\Models\Hr\OvertimeRequest::STATUS_APPROVED)
            ->with('lines')
            ->get();
        
        if ($overtimeRequests->isEmpty()) {
            return ['amount' => 0, 'hours' => 0, 'breakdown' => []];
        }
        
        // Get base salary for hourly rate calculation
        // Priority: Salary Structure → Contract → Employee basic_salary
        $salaryStructure = EmployeeSalaryStructure::getStructureForDate($employee->id, $startDate);
        $baseSalary = $this->getBaseSalary($employee, $date, $salaryStructure);
        
        if ($baseSalary <= 0) {
            // No base salary, cannot calculate overtime
            return ['amount' => 0, 'hours' => 0, 'breakdown' => []];
        }
        
        // Get employee's work schedule for the payroll period
        $employeeSchedule = $this->attendanceService->getEmployeeScheduleForDate($employee, $startDate);
        $workSchedule = $employeeSchedule && $employeeSchedule->schedule ? $employeeSchedule->schedule : null;
        
        // Calculate working days per month from work schedule
        $workingDaysPerMonth = $this->calculateWorkingDaysPerMonth($workSchedule, $startDate, $endDate);
        
        // Get standard daily hours from work schedule (default to 8 if not set)
        $standardDailyHours = $workSchedule && $workSchedule->standard_daily_hours 
            ? (float) $workSchedule->standard_daily_hours 
            : 8.0;
        
        // Calculate hourly rate: Base Salary ÷ Working Days ÷ Standard Daily Hours
        $hourlyRate = $baseSalary / $workingDaysPerMonth / $standardDailyHours;
        
        $totalOvertimeAmount = 0;
        $totalOvertimeHours = 0;
        $breakdown = [];
        
        // Calculate overtime earnings for each request and its lines
        foreach ($overtimeRequests as $request) {
            $overtimeDate = Carbon::parse($request->overtime_date);
            
            // Process each line in the request
            foreach ($request->lines as $line) {
                $overtimeHours = (float) $line->overtime_hours;
                $overtimeRate = (float) $line->overtime_rate;
            
                // Calculate earnings for this line
                $lineAmount = $overtimeHours * $hourlyRate * $overtimeRate;
            
                $totalOvertimeAmount += $lineAmount;
            $totalOvertimeHours += $overtimeHours;
            
            // Add to breakdown
            $breakdown[] = [
                'date' => $overtimeDate->format('Y-m-d'),
                'hours' => $overtimeHours,
                'hourly_rate' => $hourlyRate,
                'overtime_rate' => $overtimeRate,
                    'day_type' => $line->day_type,
                    'amount' => $lineAmount,
            ];
            }
        }
        
        return [
            'amount' => round($totalOvertimeAmount, 2),
            'hours' => round($totalOvertimeHours, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Get other deductions (advances, loans)
     *
     * Best Practice Implementation:
     * - Deducts all active advances/loans where date <= payroll date
     * - Allows ongoing monthly deductions until fully repaid
     * - Caps deduction to remaining balance (if tracked)
     * - Auto-deactivates when fully repaid
     * - Supports both fixed amount and percentage-based deductions for external loans
     *
     * Salary Advances and External Loans are linked to payroll by:
     * 1. Employee ID - Links to specific employee
     * 2. Date - Advances/loans with date <= payroll date are eligible
     * 3. is_active flag - Only active advances/loans are deducted
     * 4. monthly_deduction - The amount to deduct each month (fixed) or percentage value
     * 5. deduction_type - 'fixed' or 'percentage' for external loans
     * 6. date_end_of_loan (for loans) - Stops deduction after end date
     * 
     * Note: Percentage-based external loans are calculated on basic salary (consistent with HESLB loans)
     */
    protected function getOtherDeductions(Employee $employee, $year, $month, $companyId, $basicSalary = 0)
    {
        $payrollDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Get salary advances for the payroll period
        // Only include those that allow payroll deductions (payroll or both)
        $advances = 0;
        $eligibleAdvances = SalaryAdvance::where('employee_id', $employee->id)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereIn('repayment_type', ['payroll', 'both'])
            ->where('date', '<=', $payrollDate)
            ->get();

        foreach ($eligibleAdvances as $advance) {
            $remaining = $advance->remaining_balance;
            if ($remaining > 0) {
                // Deduct the smaller of monthly_deduction or remaining balance
                $advances += min($remaining, (float) $advance->monthly_deduction);
            }
        }

        // Get external loans for the payroll period
        // ... (rest of the logic)

        // Calculate loan deductions based on type (fixed or percentage)
        $loans = 0;
        $loanRecords = $loanQuery->get();
        
        foreach ($loanRecords as $loan) {
            $deductionType = $loan->deduction_type ?? 'fixed';
            
            if ($deductionType === 'percentage') {
                // Calculate percentage of basic salary (consistent with HESLB loans)
                if ($basicSalary > 0) {
                    $percentage = (float) $loan->monthly_deduction;
                    $loanDeduction = $basicSalary * ($percentage / 100);
                    $loans += $loanDeduction;
                }
            } else {
                // Fixed amount
                $loans += (float) $loan->monthly_deduction;
            }
        }

        return [
            'advances' => $advances,
            'loans' => round($loans, 2),
            'total' => round($advances + $loans, 2),
        ];
    }

    /**
     * Get base salary for calculations following priority order:
     * 1. Salary Structure (if exists and has basic salary component)
     * 2. Active Contract salary (if exists)
     * 3. Employee basic_salary (fallback)
     * 
     * This is used as the base for percentage calculations in salary structure
     */
    protected function getBaseSalary(Employee $employee, $date, $salaryStructure = null)
    {
        // Priority 1: Check Salary Structure for basic salary component
        if ($salaryStructure && $salaryStructure->isNotEmpty()) {
            $basicSalaryComponent = $salaryStructure->first(function($structure) {
                $component = $structure->component;
                return $component->component_type == SalaryComponent::TYPE_EARNING 
                    && $component->is_active
                    && str_contains(strtolower($component->component_code), 'basic');
            });
            
            if ($basicSalaryComponent) {
                // Use contract or employee salary as base for percentage calculations
                $baseForPercentage = $this->getContractOrEmployeeSalary($employee, $date);
                $calculatedAmount = $basicSalaryComponent->component->calculateAmount($baseForPercentage, $basicSalaryComponent);
                if ($calculatedAmount > 0) {
                    return $calculatedAmount;
                }
            }
        }

        // Priority 2: Check active contract salary
        $activeContract = \App\Models\Hr\Contract::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date);
            })
            ->latest('start_date')
            ->first();

        if ($activeContract && $activeContract->salary) {
            return (float) $activeContract->salary;
        }

        // Priority 3: Fallback to employee basic_salary
        return $employee->basic_salary ?? 0;
    }

    /**
     * Get contract salary or employee basic salary (for percentage calculations)
     * This is used as the base amount when calculating percentage-based components
     */
    protected function getContractOrEmployeeSalary(Employee $employee, $date)
    {
        // Check active contract salary first
        $activeContract = \App\Models\Hr\Contract::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date);
            })
            ->latest('start_date')
            ->first();

        if ($activeContract && $activeContract->salary) {
            return (float) $activeContract->salary;
        }

        // Fallback to employee basic_salary
        return $employee->basic_salary ?? 0;
    }

    /**
     * Calculate working days per month based on work schedule weekly pattern
     * 
     * @param WorkSchedule|null $workSchedule
     * @param Carbon $startDate Start of month
     * @param Carbon $endDate End of month
     * @return float Number of working days in the month
     */
    protected function calculateWorkingDaysPerMonth($workSchedule, Carbon $startDate, Carbon $endDate): float
    {
        // If no work schedule, default to 22 working days (Monday-Friday pattern)
        if (!$workSchedule || !$workSchedule->weekly_pattern) {
            return 22.0;
        }
        
        $weeklyPattern = $workSchedule->weekly_pattern;
        $workingDays = 0;
        
        // Iterate through each day in the month
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dayOfWeek = strtolower($currentDate->format('l')); // monday, tuesday, etc.
            
            // Check if this day is a working day according to the weekly pattern
            if (isset($weeklyPattern[$dayOfWeek]) && $weeklyPattern[$dayOfWeek]) {
                $workingDays++;
            }
            
            $currentDate->addDay();
        }
        
        return (float) $workingDays;
    }

    /**
     * Record HESLB repayment for an employee after payroll processing
     *
     * @param Employee $employee
     * @param float $amount
     * @param Carbon $repaymentDate
     * @param int|null $payrollId
     * @param int|null $payrollEmployeeId
     * @return \App\Models\Hr\HeslbRepayment|null
     */
    public static function recordHeslbRepayment(
        Employee $employee,
        float $amount,
        Carbon $repaymentDate,
        ?int $payrollId = null,
        ?int $payrollEmployeeId = null
    ): ?\App\Models\Hr\HeslbRepayment {
        if ($amount <= 0) {
            return null;
        }

        $activeLoan = \App\Models\Hr\HeslbLoan::getActiveLoan($employee->id);

        if (!$activeLoan) {
            return null;
        }

        return $activeLoan->recordRepayment(
            $amount,
            $repaymentDate,
            $payrollId,
            $payrollEmployeeId,
            'payroll',
            "Automatic deduction from payroll"
        );
    }

    /**
     * Calculate age-based tax exemption (Tanzania-specific)
     * Employees 60+ may be eligible for tax exemptions
     */
    protected function calculateAgeBasedExemption(Employee $employee, Carbon $date): float
    {
        if (!$employee->date_of_birth) {
            return 0;
        }

        $age = $employee->date_of_birth->diffInYears($date);
        
        // Tanzania: Employees 60+ may have reduced tax rates or exemptions
        // This is configurable and can be set in statutory rules
        // For now, return 0 (no exemption) - can be enhanced with rule-based configuration
        if ($age >= 60) {
            // Could be configured in statutory rules in the future
            // For now, return 0 to maintain current behavior
            return 0;
        }

        return 0;
    }

    /**
     * Calculate disability allowance exemption (Tanzania-specific)
     * Employees with disabilities may be eligible for tax exemptions
     */
    protected function calculateDisabilityExemption(Employee $employee, float $taxableIncome): float
    {
        // This would require a disability flag/field on employee model
        // For now, return 0 - can be enhanced when disability tracking is added
        return 0;
    }
}

