<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Slip - {{ $employee->employee->full_name ?? $employee->employee_name ?? 'N/A' }}</title>
    <style>
        /* ===== PAGE SETUP ===== */
        @page {
            size: A4;
            margin: 20mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", serif;
            font-size: 10.5px;
            color: #000;
            background: #fff;
        }

        .print-container {
            width: 100%;
        }

        /* ===== HEADER ===== */
        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
            text-align: center;
        }

        .company-logo img {
            width: 55px;
            height: 55px;
            margin-bottom: 5px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #8B0000;
            letter-spacing: 0.8px;
        }

        .company-details {
            font-size: 9px;
            margin-top: 5px;
            line-height: 1.6;
        }

        /* ===== TITLE ===== */
        .payroll-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 15px 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* ===== INFO SECTIONS ===== */
        .payroll-details {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .employee-info,
        .payroll-info {
            width: 50%;
        }

        .employee-info div {
            margin-bottom: 6px;
        }

        .employee-info strong {
            display: inline-block;
            width: 110px;
        }

        .payroll-info table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }

        .payroll-info td {
            border: 1px solid #000;
            padding: 6px 8px;
        }

        .payroll-info td:first-child {
            background: #f2f2f2;
            font-weight: bold;
            width: 40%;
        }

        /* ===== EARNINGS / DEDUCTIONS ===== */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 9.5px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 6px 8px;
        }

        .items-table th {
            background: #e6e6e6;
            font-weight: bold;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* ===== SUMMARY ===== */
        .summary {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #000;
            background: #fafafa;
            font-size: 10.5px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .summary-row.total {
            border-top: 2px solid #000;
            padding-top: 8px;
            margin-top: 8px;
            font-size: 11.5px;
            font-weight: bold;
        }

        /* ===== FOOTER ===== */
        .footer {
            margin-top: 20px;
            font-size: 9px;
        }

        .signature-line {
            margin-top: 15px;
        }

        .page-info {
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            color: #555;
            border-top: 1px solid #ccc;
            padding-top: 8px;
            line-height: 1.5;
        }

    </style>

</head>
<body>
    <div class="print-container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <div class="company-logo">
                    @if($company && $company->logo)
                    <img src="{{ public_path('storage/' . $company->logo) }}" alt="Logo">
                    @endif
                    <div>
                        <h1 class="company-name">{{ $company->name ?? 'SMARTACCOUNTING' }}</h1>
                    </div>
                </div>
                <div class="company-details">
                    <div><strong>P.O. Box:</strong> {{ $company->address ?? 'P.O.BOX 00000, City, Country' }}</div>
                    <div><strong>Phone:</strong> {{ $company->phone ?? '+255 000 000 000' }}</div>
                    <div><strong>Email:</strong> {{ $company->email ?? 'company@email.com' }}</div>
                </div>
            </div>
        </div>

        <!-- Payroll Title -->
        <div class="payroll-title">Employee Payroll Slip</div>

        <!-- Employee and Payroll Information -->
        <div class="payroll-details">
            <div class="employee-info">
                <div><strong>Employee Name:</strong> {{ $employee->employee->full_name ?? 'N/A' }}</div>
                <div><strong>Employee ID:</strong> {{ $employee->employee->employee_id ?? 'N/A' }}</div>
                <div><strong>Department:</strong> {{ $employee->employee->department->name ?? 'N/A' }}</div>
                <div><strong>Position:</strong> {{ $employee->employee->position->title ?? 'N/A' }}</div>

            </div>

            <div class="payroll-info">
                <table>
                    <tr>
                        <td>Payroll Period:</td>
                        <td>{{ $payroll->month_name ?? \Carbon\Carbon::parse($payroll->period_end ?? now())->format('F') }} {{ $payroll->year ?? \Carbon\Carbon::parse($payroll->period_end ?? now())->format('Y') }}</td>
                    </tr>
                    <tr>
                        <td>Payroll Ref:</td>
                        <td>{{ $payroll->reference ?? 'N/A' }}</td>
                    </tr>
                    @if($payroll->period_start && $payroll->period_end)
                    <tr>
                        <td>Pay Period:</td>
                        <td>{{ \Carbon\Carbon::parse($payroll->period_start)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($payroll->period_end)->format('d/m/Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>Date:</td>
                        <td>{{ $payroll->created_at->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td>Time:</td>
                        <td>{{ $payroll->created_at->format('h:i:s A') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @php
        $totalEarnings = 0;
        $earnings = [];

        // Basic Salary
        $basicSalary = $employee->basic_salary ?? 0;
        if ($basicSalary > 0) {
        $earnings['Basic Salary'] = $basicSalary;
        $totalEarnings += $basicSalary;
        }

        // Allowances
        $allowance = $employee->allowance ?? 0;
        if ($allowance > 0) {
        $earnings['Allowances'] = $allowance;
        $totalEarnings += $allowance;
        }

        // Other Allowances
        $otherAllowances = $employee->other_allowances ?? 0;
        if ($otherAllowances > 0) {
        $earnings['Other Allowances'] = $otherAllowances;
        $totalEarnings += $otherAllowances;
        }

        // Overtime
        $overtime = $employee->overtime ?? 0;
        if ($overtime > 0) {
        $earnings['Overtime'] = $overtime;
        $totalEarnings += $overtime;
        }

        $totalDeductions = 0;
        $deductions = [];

        // Common deduction fields (excluding WCF and SDL as they are employer contributions)
        $deductionFields = [
        'paye' => 'PAYE Tax',
        'pension' => 'Pension',
        'insurance' => 'NHIF',
        'salary_advance' => 'Salary Advance',
        // Loans will be handled separately by institution
        'trade_union' => 'Trade Union',
        'heslb' => 'HESLB',
        ];

        foreach ($deductionFields as $field => $label) {
        $value = $employee->$field ?? 0;
        // Only display if > 0.01 to avoid floating point precision issues and hide zero values
        if ($value > 0.01) {
        $deductions[$label] = $value;
        $totalDeductions += $value;
        }
        }
        
        // Get breakdown of other deductions from salary structure
        $otherDeductionsBreakdown = [];
        $totalOtherDeductions = 0;
        
        if ($employee->employee && ($employee->other_deductions ?? 0) > 0) {
            try {
                // Get salary structure for the payroll period
                $payrollDate = \Carbon\Carbon::create($payroll->year, $payroll->month, 1);
                $salaryStructure = \App\Models\Hr\EmployeeSalaryStructure::getStructureForDate(
                    $employee->employee->id, 
                    $payrollDate
                );
                
                if ($salaryStructure && $salaryStructure->isNotEmpty()) {
                    $grossSalary = $employee->gross_salary ?? 0;
                    
                    // Get deduction components from salary structure
                    $deductionComponents = $salaryStructure->filter(function($structure) {
                        return $structure->component->component_type == \App\Models\Hr\SalaryComponent::TYPE_DEDUCTION 
                            && $structure->component->is_active;
                    });
                    
                    foreach ($deductionComponents as $structure) {
                        $component = $structure->component;
                        $componentCode = strtolower($component->component_code ?? '');
                        
                        // Skip trade union (it's shown separately)
                        $isTradeUnion = str_contains($componentCode, 'trade_union') || 
                                       str_contains($componentCode, 'union');
                        
                        if (!$isTradeUnion) {
                            // Calculate the deduction amount
                            $amount = $component->calculateAmount($grossSalary, $structure);
                            
                            if ($amount > 0) {
                                $componentName = $component->component_name;
                                $otherDeductionsBreakdown[$componentName] = ($otherDeductionsBreakdown[$componentName] ?? 0) + $amount;
                                $totalOtherDeductions += $amount;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Silently fail and fall back to showing total
            }
            
            // If no breakdown found but other_deductions exists, show as single item
            if (empty($otherDeductionsBreakdown)) {
                $otherDeductionsBreakdown['Other Deductions'] = $employee->other_deductions;
                $totalOtherDeductions = $employee->other_deductions;
            }
        }
        
        // Add other deductions breakdown to deductions array (only if > 0)
        if (!empty($otherDeductionsBreakdown)) {
            foreach ($otherDeductionsBreakdown as $componentName => $amount) {
                if ($amount > 0.01) {
                    $deductions[$componentName] = $amount;
                }
            }
        }

        // Calculate loans by institution
        $loansByInstitution = [];
        $totalLoansDeduction = 0;
        $payrollDate = \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->endOfMonth();
        
        if ($employee->employee) {
            // Get all active external loans for this employee in this payroll period
            $activeLoans = \App\Models\Hr\ExternalLoan::where('employee_id', $employee->employee->id)
                ->where('company_id', $payroll->company_id)
                ->where('is_active', true)
                ->where('date', '<=', $payrollDate)
                ->where(function ($q) use ($payrollDate) {
                    $q->whereNull('date_end_of_loan')
                      ->orWhere('date_end_of_loan', '>=', $payrollDate);
                })
                ->with('externalLoanInstitution')
                ->get();
            
            // Get basic salary for percentage calculations
            $basicSalary = $employee->basic_salary ?? 0;
            
            foreach ($activeLoans as $loan) {
                $deductionType = $loan->deduction_type ?? 'fixed';
                $loanDeduction = 0;
                
                if ($deductionType === 'percentage') {
                    // Calculate percentage of basic salary
                    if ($basicSalary > 0) {
                        $percentage = (float) $loan->monthly_deduction;
                        $loanDeduction = $basicSalary * ($percentage / 100);
                    }
                } else {
                    // Fixed amount
                    $loanDeduction = (float) $loan->monthly_deduction;
                }
                
                if ($loanDeduction > 0) {
                    // Get institution name
                    $institutionName = 'N/A';
                    if ($loan->relationLoaded('externalLoanInstitution') && $loan->externalLoanInstitution) {
                        $institutionName = $loan->externalLoanInstitution->name;
                    } elseif (!empty($loan->institution_name)) {
                        $institutionName = $loan->institution_name;
                    }
                    
                    // Group by institution
                    if (!isset($loansByInstitution[$institutionName])) {
                        $loansByInstitution[$institutionName] = 0;
                    }
                    $loansByInstitution[$institutionName] += $loanDeduction;
                    $totalLoansDeduction += $loanDeduction;
                }
            }
        }
        
        // Add loans grouped by institution to deductions (only if > 0)
        if (!empty($loansByInstitution)) {
            foreach ($loansByInstitution as $institutionName => $amount) {
                if ($amount > 0.01) {
                    $deductions['Loans: ' . $institutionName] = $amount;
                }
            }
        } elseif (($employee->loans ?? 0) > 0.01) {
            // Fallback: if we couldn't calculate by institution, show total loans
            $deductions['Loans'] = $employee->loans;
        }
        
        // Update total deductions
        $totalDeductions += $totalLoansDeduction + $totalOtherDeductions;

        // Calculate employer contributions for this employee
        $employerContributions = [];
        $totalEmployerContributions = 0;
        
        try {
            $attendanceService = new \App\Services\Hr\AttendanceService();
            $payrollCalculationService = new \App\Services\Hr\PayrollCalculationService($attendanceService);
            $date = \Carbon\Carbon::create($payroll->year, $payroll->month, 1);
            
            $result = $payrollCalculationService->calculateEmployeePayroll(
                $employee->employee,
                $payroll->year,
                $payroll->month,
                $payroll->company_id
            );
            
            if (isset($result['employer_contributions'])) {
                $empContrib = $result['employer_contributions'];
                
                // NHIF (Employer) - only display if > 0
                $nhifEmployer = $empContrib['nhif_employer'] ?? 0;
                if ($nhifEmployer > 0.01) {
                    $employerContributions['NHIF (Employer)'] = $nhifEmployer;
                    $totalEmployerContributions += $nhifEmployer;
                }
                
                // Pension (Employer) - only display if > 0
                $pensionEmployer = $empContrib['pension_employer'] ?? 0;
                if ($pensionEmployer > 0.01) {
                    $employerContributions['Pension (Employer)'] = $pensionEmployer;
                    $totalEmployerContributions += $pensionEmployer;
                }
                
                // WCF - only display if > 0
                $wcf = $empContrib['wcf'] ?? 0;
                if ($wcf > 0.01) {
                    $employerContributions['WCF'] = $wcf;
                    $totalEmployerContributions += $wcf;
                }
                
                // SDL - only display if > 0
                $sdl = $empContrib['sdl'] ?? 0;
                if ($sdl > 0.01) {
                    $employerContributions['SDL'] = $sdl;
                    $totalEmployerContributions += $sdl;
                }
            }
        } catch (\Exception $e) {
            // If calculation fails, use stored values from payroll employee
            // Note: WCF and SDL are stored in payroll_employees table
            $wcf = $employee->wcf ?? 0;
            $sdl = $employee->sdl ?? 0;
            
            // Only display if > 0.01 to avoid floating point precision issues
            if ($wcf > 0.01) {
                $employerContributions['WCF'] = $wcf;
                $totalEmployerContributions += $wcf;
            }
            if ($sdl > 0.01) {
                $employerContributions['SDL'] = $sdl;
                $totalEmployerContributions += $sdl;
            }
        }
        @endphp

        <!-- Earnings & Deductions Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Earnings</th>
                    <th style="width: 25%;" class="text-right">Amount (TZS)</th>
                    <th style="width: 25%;">Deductions</th>
                    <th style="width: 25%;" class="text-right">Amount (TZS)</th>
                </tr>
            </thead>
            <tbody>
                @php
                $maxRows = max(count($earnings), count($deductions));
                $earningsArray = array_values($earnings);
                $deductionsArray = array_values($deductions);
                $earningsKeys = array_keys($earnings);
                $deductionsKeys = array_keys($deductions);
                @endphp
                @for($i = 0; $i < $maxRows; $i++) <tr>
                    <td>{{ isset($earningsKeys[$i]) ? $earningsKeys[$i] : '' }}</td>
                    <td class="text-right">{{ isset($earningsArray[$i]) ? number_format($earningsArray[$i], 2) : '' }}</td>
                    <td>{{ isset($deductionsKeys[$i]) ? $deductionsKeys[$i] : '' }}</td>
                    <td class="text-right">{{ isset($deductionsArray[$i]) ? number_format($deductionsArray[$i], 2) : '' }}</td>
                    </tr>
                    @endfor
            </tbody>
        </table>

        @if(count($employerContributions) > 0)
        <!-- Employer Contributions Section -->
        <div style="margin-top: 12px; padding: 8px; border: 1px solid #000; background: #f0f8ff; font-size: 10px;">
            <div style="font-weight: bold; margin-bottom: 6px; font-size: 10.5px;">
                Employer Contributions
            </div>
            <table style="width: 100%; border-collapse: collapse; font-size: 9.5px;">
                <tbody>
                    @foreach($employerContributions as $label => $amount)
                    <tr>
                        <td style="padding: 4px 8px; border-bottom: 1px solid #ddd; width: 70%;"><strong>{{ $label }}:</strong></td>
                        <td style="padding: 4px 8px; border-bottom: 1px solid #ddd; text-align: right; width: 30%;">TZS {{ number_format($amount, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr style="background: #e6f3ff;">
                        <td style="padding: 5px 8px; font-weight: bold; font-size: 10px;">Total Employer Contributions:</td>
                        <td style="padding: 5px 8px; text-align: right; font-weight: bold; font-size: 10px;">TZS {{ number_format($totalEmployerContributions, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        <!-- Summary -->
        <div class="summary">
            <div class="summary-row">
                <span><strong>Total Earnings:</strong></span>
                <span><strong>TZS {{ number_format($totalEarnings, 2) }}</strong></span>
            </div>
            <div class="summary-row">
                <span><strong>Total Deductions:</strong></span>
                <span><strong>TZS {{ number_format($totalDeductions, 2) }}</strong></span>
            </div>
            <div class="summary-row total">
                <span>NET SALARY:</span>
                <span>TZS {{ number_format($employee->net_salary ?? 0, 2) }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="signature-line">
                <strong>Employee Signature: ................................................</strong>
            </div>
            <div class="signature-line">
                <strong>HR/Payroll Signature: ................................................</strong>
            </div>

            <div class="page-info">
                <div><strong>Payroll Period:</strong> {{ $payroll->month_name ?? 'N/A' }} {{ $payroll->year ?? 'N/A' }}</div>
                <div><strong>Employee:</strong> {{ $employee->employee->full_name ?? $employee->employee_name ?? 'N/A' }}</div>
                <div><strong>Generated on:</strong> {{ now()->format('d/m/Y h:i A') }}</div>
                <div style="margin-top: 5px; font-size: 7px;">
                    Payroll ID: {{ $payroll->hash_id }} | Employee ID: {{ $employee->hash_id }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
