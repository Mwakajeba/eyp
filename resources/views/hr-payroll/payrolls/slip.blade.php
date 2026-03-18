@extends('layouts.main')

@section('title', 'Payroll Slip - ' . ($employee->employee->full_name ?? $employee->employee_name ?? 'N/A'))

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Payrolls', 'url' => route('hr.payrolls.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Payroll Details', 'url' => route('hr.payrolls.show', $payroll->hash_id), 'icon' => 'bx bx-show'],
            ['label' => 'Payroll Slip', 'url' => '#', 'icon' => 'bx bx-receipt']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Payroll Slip</h4>
                    <div class="page-title-right">
                        <a href="{{ route('hr.payrolls.slip.pdf', ['payroll' => $payroll->hash_id, 'employee' => $employee->hash_id]) }}" class="btn btn-info me-1" target="_blank">
                            <i class="bx bx-download me-1"></i>Export PDF
                        </a>
                        <a href="{{ route('hr.payrolls.slip.print', ['payroll' => $payroll->hash_id, 'employee' => $employee->hash_id]) }}" class="btn btn-danger me-1" target="_blank">
                            <i class="bx bx-printer me-1"></i>Print Slip
                        </a>
                        <a href="{{ route('hr.payrolls.show', $payroll->hash_id) }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Payroll
                        </a>
                    </div>
                </div>
            </div>
                        </div>

        <!-- Payroll Slip Header -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Payroll Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="150"><strong>Payroll Period:</strong></td>
                                        <td>{{ $payroll->month_name ?? \Carbon\Carbon::parse($payroll->period_end ?? now())->format('F') }} {{ $payroll->year ?? \Carbon\Carbon::parse($payroll->period_end ?? now())->format('Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Payroll Reference:</strong></td>
                                        <td>{{ $payroll->reference ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>{!! $payroll->status_badge !!}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Pay Period:</strong></td>
                                        <td>
                                            @if($payroll->period_start && $payroll->period_end)
                                                {{ \Carbon\Carbon::parse($payroll->period_start)->format('M d') }} - {{ \Carbon\Carbon::parse($payroll->period_end)->format('M d, Y') }}
                                            @else
                                                {{ $payroll->month_name ?? 'N/A' }} {{ $payroll->year ?? 'N/A' }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Company:</strong></td>
                                        <td>{{ $company->name ?? 'N/A' }}</td>
                                    </tr>
                                </table>
                        </div>
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Employee Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="150"><strong>Employee Name:</strong></td>
                                        <td>{{ $employee->employee->full_name ?? $employee->employee_name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Employee ID:</strong></td>
                                        <td>{{ $employee->employee->employee_id ?? $employee->employee_number ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Department:</strong></td>
                                        <td>{{ $employee->employee->department->name ?? $employee->department->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Position:</strong></td>
                                        <td>{{ $employee->employee->position->title ?? $employee->designation ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Hire Date:</strong></td>
                                        <td>
                                            @if($employee->employee->hire_date ?? $employee->joining_date)
                                                {{ \Carbon\Carbon::parse($employee->employee->hire_date ?? $employee->joining_date)->format('M d, Y') }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                        </div>
                        </div>
                        </div>
                        </div>
                    </div>
                </div>

        <!-- Earnings & Deductions -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-list-ul me-2"></i>Earnings & Deductions Breakdown
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                        <!-- Earnings Column -->
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="bx bx-trending-up me-2"></i>Earnings</h6>
                            </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <tbody>
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
                                    
                                    // If no earnings, use gross salary
                                    if (empty($earnings) && ($employee->gross_salary ?? 0) > 0) {
                                        $earnings['Gross Salary'] = $employee->gross_salary;
                                        $totalEarnings = $employee->gross_salary;
                                    }

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
                                        // If calculation fails, use payroll totals (fallback)
                                        $nhifEmployer = ($payroll->total_nhif_employer ?? 0) / max($payroll->payrollEmployees->count(), 1);
                                        $pensionEmployer = ($payroll->total_pension_employer ?? 0) / max($payroll->payrollEmployees->count(), 1);
                                        $wcf = ($payroll->total_wcf ?? 0) / max($payroll->payrollEmployees->count(), 1);
                                        $sdl = ($payroll->total_sdl ?? 0) / max($payroll->payrollEmployees->count(), 1);
                                        
                                        // Only display if > 0.01 to avoid floating point precision issues
                                        if ($nhifEmployer > 0.01) {
                                            $employerContributions['NHIF (Employer)'] = $nhifEmployer;
                                            $totalEmployerContributions += $nhifEmployer;
                                        }
                                        if ($pensionEmployer > 0.01) {
                                            $employerContributions['Pension (Employer)'] = $pensionEmployer;
                                            $totalEmployerContributions += $pensionEmployer;
                                        }
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

                                @forelse($earnings as $name => $amount)
                                                    <tr>
                                                        <td><strong>{{ $name }}:</strong></td>
                                                        <td class="text-end">TZS {{ number_format($amount, 2) }}</td>
                                                    </tr>
                                                    @empty
                                                    <tr>
                                                        <td colspan="2" class="text-muted text-center">No earnings recorded</td>
                                                    </tr>
                                                    @endforelse
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-success">
                                                        <td><strong>Total Earnings:</strong></td>
                                                        <td class="text-end"><strong>TZS {{ number_format($totalEarnings, 2) }}</strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>

                                        @if(isset($employerContributions) && is_array($employerContributions) && count($employerContributions) > 0)
                                        <!-- Employer Contributions Section -->
                                        <hr class="my-3">
                                        <h6 class="mb-2 text-info"><i class="bx bx-building me-2"></i>Employer Contributions</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <tbody>
                                                    @foreach($employerContributions as $label => $amount)
                                                    <tr>
                                                        <td><strong>{{ $label }}:</strong></td>
                                                        <td class="text-end">TZS {{ number_format($amount, 2) }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-info">
                                                        <td><strong>Total Employer Contributions:</strong></td>
                                                        <td class="text-end"><strong>TZS {{ number_format($totalEmployerContributions, 2) }}</strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                    </div>
                                        @endif
                                    </div>
                            </div>
                        </div>

                        <!-- Deductions Column -->
                            <div class="col-md-6">
                                <div class="card border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0"><i class="bx bx-trending-down me-2"></i>Deductions</h6>
                            </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <tbody>
                                @php
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
                                    
                                    // If no deductions, use total_deductions
                                    if (empty($deductions) && ($employee->total_deductions ?? 0) > 0) {
                                        $deductions['Total Deductions'] = $employee->total_deductions;
                                        $totalDeductions = $employee->total_deductions;
                                    }
                                @endphp

                                @forelse($deductions as $name => $amount)
                                                    <tr>
                                                        <td><strong>{{ $name }}:</strong></td>
                                                        <td class="text-end">TZS {{ number_format($amount, 2) }}</td>
                                                    </tr>
                                                    @empty
                                                    <tr>
                                                        <td colspan="2" class="text-muted text-center">No deductions recorded</td>
                                                    </tr>
                                                    @endforelse
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-danger">
                                                        <td><strong>Total Deductions:</strong></td>
                                                        <td class="text-end"><strong>TZS {{ number_format($totalDeductions, 2) }}</strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                    </div>
                            </div>
                        </div>
                    </div>
                </div>
                        </div>
                        </div>

        <!-- Payroll Summary -->
        <div class="row">
            <div class="col-md-8">
                @if($payroll->notes)
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-note me-2"></i>Notes
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $payroll->notes }}</p>
                    </div>
                </div>
                @endif
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-calculator me-2"></i>Payroll Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Gross Salary:</span>
                            <span>TZS {{ number_format($employee->gross_salary ?? 0, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Deductions:</span>
                            <span>TZS {{ number_format($employee->total_deductions ?? 0, 2) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold text-primary">
                            <span>Net Salary:</span>
                            <span>TZS {{ number_format($employee->net_salary ?? 0, 2) }}</span>
                    </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>

@push('styles')
<style>
    @media print {
        .page-title-box,
        .page-title-right,
        .breadcrumb,
        .btn {
            display: none !important;
        }
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
        .page-content {
            padding: 0 !important;
        }
    }
</style>
@endpush

@push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
    // Print functionality
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
@endpush
@endsection
