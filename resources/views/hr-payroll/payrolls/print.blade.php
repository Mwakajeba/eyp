<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Slip - {{ $employee->employee->full_name ?? $employee->employee_name ?? 'N/A' }}</title>
    <style>
        @php
            $docPageSize = \App\Models\SystemSetting::getValue('document_page_size', 'A5');
            $docOrientation = \App\Models\SystemSetting::getValue('document_orientation', 'portrait');
            $docMarginTop = \App\Models\SystemSetting::getValue('document_margin_top', '2.54cm');
            $docMarginRight = \App\Models\SystemSetting::getValue('document_margin_right', '2.54cm');
            $docMarginBottom = \App\Models\SystemSetting::getValue('document_margin_bottom', '2.54cm');
            $docMarginLeft = \App\Models\SystemSetting::getValue('document_margin_left', '2.54cm');
            $docFontFamily = \App\Models\SystemSetting::getValue('document_font_family', 'DejaVu Sans');
            $docFontSize = (int) (\App\Models\SystemSetting::getValue('document_base_font_size', 10));
            $docLineHeight = \App\Models\SystemSetting::getValue('document_line_height', '1.4');
            $docTextColor = \App\Models\SystemSetting::getValue('document_text_color', '#000000');
            $docBgColor = \App\Models\SystemSetting::getValue('document_background_color', '#FFFFFF');
            $docHeaderColor = \App\Models\SystemSetting::getValue('document_header_color', '#000000');
            $docAccentColor = \App\Models\SystemSetting::getValue('document_accent_color', '#b22222');
            $docTableHeaderBg = \App\Models\SystemSetting::getValue('document_table_header_bg', '#f2f2f2');
            $docTableHeaderText = \App\Models\SystemSetting::getValue('document_table_header_text', '#000000');
            $pageSizeCss = $docPageSize . ' ' . $docOrientation;
        @endphp
        @page {
            size: {{ $pageSizeCss }};
            margin: {{ $docMarginTop }} {{ $docMarginRight }} {{ $docMarginBottom }} {{ $docMarginLeft }};
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: '{{ $docFontFamily }}', sans-serif;
            font-size: {{ $docFontSize }}px;
            line-height: {{ $docLineHeight }};
            color: {{ $docTextColor }};
            background-color: {{ $docBgColor }};
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100vh;
        }

        .print-container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0;
            position: relative;
            top: 0;
            left: 0;
        }

        @media print {
            @page {
                size: {{ $pageSizeCss }};
                margin: {{ $docMarginTop }} {{ $docMarginRight }} {{ $docMarginBottom }} {{ $docMarginLeft }};
            }
            
            body {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .print-container {
                margin: 0 !important;
                padding: 0 !important;
                position: relative !important;
                top: 0 !important;
                left: 0 !important;
            }
        }

        /* === HEADER === */
        .header {
            text-align: center;
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
            padding-bottom: 5px;
            margin-top: 0;
            padding-top: 0;
        }

        .company-name {
            color: {{ $docAccentColor }};
            font-size: 15px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .company-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 3px;
        }

        .company-logo img {
            width: 35px;
            height: 35px;
            margin-right: 8px;
        }

        .company-details {
            font-size: 8px;
            line-height: 1.3;
            margin-top: 2px;
        }

        /* === PAYROLL TITLE === */
        .payroll-title {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            margin: 8px 0;
            text-transform: uppercase;
        }

        /* === INFO SECTION === */
        .payroll-details {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            margin-bottom: 8px;
        }

        .employee-info {
            flex: 1;
        }

        .payroll-info {
            flex: 1;
            text-align: right;
        }

        .payroll-info table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .payroll-info td {
            border: 1px solid #000;
            padding: 2px;
        }

        .payroll-info td:first-child {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        /* === EARNINGS & DEDUCTIONS TABLE === */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 3px;
            font-size: 8px;
        }

        .items-table th {
            background-color: {{ $docTableHeaderBg }};
            font-weight: bold;
            text-align: center;
            color: {{ $docTableHeaderText }};
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* === SUMMARY === */
        .summary {
            margin-top: 5px;
            font-size: 9px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .summary-row.total {
            border-top: 1px solid #000;
            font-weight: bold;
            padding-top: 3px;
        }

        /* === FOOTER === */
        .footer {
            font-size: 8px;
            margin-top: 10px;
        }

        .signature-line {
            margin-top: 8px;
        }

        .page-info {
            text-align: center;
            font-size: 8px;
            margin-top: 8px;
        }

    </style>

</head>
<body>
    <div class="print-container">
        <div class="header">
            <div class="company-info">
                <div class="company-logo">
                    @if($company->logo ?? false)
                        <img src="{{ asset('storage/' . $company->logo) }}" alt="Logo">
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

        <div class="payroll-title">Employee Payroll Slip</div>

        <div class="payroll-details">
            <div class="employee-info">
                <div class="field-label"><strong>Employee:</strong></div>
                <div class="field-value">{{ $employee->employee->full_name ?? $employee->employee_name ?? 'N/A' }}</div>
                <div class="field-value"></div>
                <div class="field-label"><strong>Employee ID:</strong></div>
                <div class="field-value">{{ $employee->employee->employee_id ?? $employee->employee_number ?? 'N/A' }}</div>
                <div class="field-label"><strong>Department:</strong></div>
                <div class="field-value">{{ $employee->employee->department->name ?? $employee->department->name ?? 'N/A' }}</div>
                <div class="field-label"><strong>Position:</strong></div>
                <div class="field-value">{{ $employee->employee->position->title ?? $employee->designation ?? 'N/A' }}</div>
            </div>
            <div class="payroll-info">
                <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
                    <tr>
                        <td style="padding: 2px; border: 1px solid #000; font-weight: bold; width: 30%;">Payroll Period:</td>
                        <td style="padding: 2px; border: 1px solid #000; width: 70%;">{{ $payroll->month_name ?? \Carbon\Carbon::parse($payroll->period_end ?? now())->format('F') }} {{ $payroll->year ?? \Carbon\Carbon::parse($payroll->period_end ?? now())->format('Y') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">Payroll Ref:</td>
                        <td style="padding: 2px; border: 1px solid #000;">{{ $payroll->reference ?? 'N/A' }}</td>
                    </tr>
                    @if($payroll->period_start && $payroll->period_end)
                    <tr>
                        <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">Pay Period:</td>
                        <td style="padding: 2px; border: 1px solid #000;">{{ \Carbon\Carbon::parse($payroll->period_start)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($payroll->period_end)->format('d/m/Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">Date:</td>
                        <td style="padding: 2px; border: 1px solid #000;">{{ $payroll->created_at->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">Time:</td>
                        <td style="padding: 2px; border: 1px solid #000;">{{ $payroll->created_at->format('h:i:s A') }}</td>
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

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Earnings</th>
                    <th style="width: 25%;" class="text-right">Amount</th>
                    <th style="width: 25%;">Deductions</th>
                    <th style="width: 25%;" class="text-right">Amount</th>
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
                @for($i = 0; $i < $maxRows; $i++)
                <tr>
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
        <div style="margin-top: 10px; padding: 8px; border: 1px solid #000; background: #f0f8ff; font-size: 9px;">
            <div style="font-weight: bold; margin-bottom: 6px; font-size: 10px;">
                Employer Contributions
            </div>
            <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
                <tbody>
                    @foreach($employerContributions as $label => $amount)
                    <tr>
                        <td style="padding: 3px 6px; border-bottom: 1px solid #ddd; width: 70%;"><strong>{{ $label }}:</strong></td>
                        <td style="padding: 3px 6px; border-bottom: 1px solid #ddd; text-align: right; width: 30%;">TZS {{ number_format($amount, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr style="background: #e6f3ff;">
                        <td style="padding: 5px 6px; font-weight: bold; font-size: 10px;">Total Employer Contributions:</td>
                        <td style="padding: 5px 6px; text-align: right; font-weight: bold; font-size: 10px;">TZS {{ number_format($totalEmployerContributions, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        <div class="summary">
            <div class="summary-row">
                <span>Total Earnings:</span>
                <span>TZS {{ number_format($totalEarnings, 2) }}</span>
            </div>
            <div class="summary-row">
                <span>Total Deductions:</span>
                <span>TZS {{ number_format($totalDeductions, 2) }}</span>
            </div>
            <div class="summary-row total">
                <span>Net Salary:</span>
                <span>TZS {{ number_format($employee->net_salary ?? 0, 2) }}</span>
            </div>
        </div>

        <div class="footer">
            <div class="signature-line">
                <strong>Employee Signature................................................</strong>
            </div>
            <div class="signature-line" style="margin-top: 15px;">
                <strong>HR Signature................................................</strong>
            </div>

            <div class="page-info">
                <div>Payroll Period: {{ $payroll->month_name ?? 'N/A' }} {{ $payroll->year ?? 'N/A' }}</div>
                <div>Employee: {{ $employee->employee->full_name ?? $employee->employee_name ?? 'N/A' }}</div>
                <div>Generated on: {{ now()->format('d/m/Y h:i A') }}</div>
            </div>
        </div>
    </div> <!-- Close print-container -->

    <script nonce="{{ $cspNonce ?? '' }}">
        // Auto-print when page loads
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>

