<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollEmployee;
use App\Models\Hr\Department;
use App\Models\Hr\PayGroup;
use App\Models\Hr\Employee;
use App\Models\Hr\OvertimeRequest;
use App\Models\Hr\EmployeeCompliance;
use App\Models\PayrollAuditLog;
use App\Models\BankAccount;
use App\Services\Hr\StatutoryComplianceValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PayrollByDepartmentExport;
use App\Exports\PayrollByPayGroupExport;
use App\Exports\StatutoryComplianceExport;
use App\Exports\EmployeePayrollHistoryExport;
use App\Exports\PayrollCostAnalysisExport;
use App\Exports\PayrollAuditTrailExport;
use App\Exports\YearToDateSummaryExport;
use App\Exports\PayrollVarianceExport;
use App\Exports\BankPaymentExport;
use App\Exports\StatutoryComplianceEnhancedExport;
use App\Exports\OvertimeExport;
use App\Exports\PayrollSummaryExport;
use App\Exports\LeaveExport;
use App\Exports\PayeRemittanceExport;
use App\Exports\NssfRemittanceExport;
use App\Exports\NhifRemittanceExport;
use App\Exports\WcfRemittanceExport;
use App\Exports\SdlRemittanceExport;
use App\Exports\HeslbRemittanceExport;
use App\Exports\CombinedStatutoryRemittanceExport;

class PayrollReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    /**
     * Display the payroll reports index page
     */
    public function index()
    {
        return view('hr-payroll.reports.index');
    }

    /**
     * Payroll by Department Report
     */
    public function payrollByDepartment(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        // Get all departments
        $departments = Department::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        // Get payrolls for the selected period
        $payrolls = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', '!=', 'cancelled')
            ->with(['payrollEmployees.employee.department'])
            ->get();

        // Calculate department-wise totals
        $departmentData = $departments->map(function ($department) use ($payrolls) {
            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;
            $employeeCount = 0;
            $payrollCount = 0;

            foreach ($payrolls as $payroll) {
                $deptEmployees = $payroll->payrollEmployees->filter(function ($pe) use ($department) {
                    return $pe->employee && $pe->employee->department_id == $department->id;
                });

                if ($deptEmployees->count() > 0) {
                    $payrollCount++;
                    $employeeCount += $deptEmployees->count();
                    $totalGross += $deptEmployees->sum('gross_salary');
                    $totalDeductions += $deptEmployees->sum('total_deductions');
                    $totalNet += $deptEmployees->sum('net_salary');
                }
            }

            return [
                'department' => $department,
                'employee_count' => $employeeCount,
                'payroll_count' => $payrollCount,
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalNet,
                'average_salary' => $employeeCount > 0 ? $totalNet / $employeeCount : 0,
            ];
        })->filter(function ($data) {
            return $data['employee_count'] > 0; // Only show departments with employees
        });

        // Calculate totals
        $totals = [
            'total_employees' => $departmentData->sum('employee_count'),
            'total_gross' => $departmentData->sum('total_gross'),
            'total_deductions' => $departmentData->sum('total_deductions'),
            'total_net' => $departmentData->sum('total_net'),
        ];

        return view('hr-payroll.reports.payroll-by-department', compact(
            'departmentData',
            'totals',
            'year',
            'month'
        ));
    }

    /**
     * Payroll by Pay Group Report
     */
    public function payrollByPayGroup(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        // Get all pay groups
        $payGroups = PayGroup::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('pay_group_code')
            ->get();

        // Get payrolls for the selected period
        $payrolls = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', '!=', 'cancelled')
            ->with(['payGroup', 'payrollEmployees'])
            ->get();

        // Calculate pay group-wise totals
        $payGroupData = $payGroups->map(function ($payGroup) use ($payrolls) {
            $groupPayrolls = $payrolls->filter(function ($payroll) use ($payGroup) {
                return $payroll->pay_group_id == $payGroup->id;
            });

            $totalGross = $groupPayrolls->sum('total_salary') + $groupPayrolls->sum('total_allowance');
            $totalDeductions = $groupPayrolls->sum('total_payee') + 
                              $groupPayrolls->sum('total_nhif_employee') + 
                              $groupPayrolls->sum('total_pension_employee') +
                              $groupPayrolls->sum('total_wcf') +
                              $groupPayrolls->sum('total_sdl') +
                              $groupPayrolls->sum('total_heslb') +
                              $groupPayrolls->sum('total_trade_union') +
                              $groupPayrolls->sum('total_salary_advance_paid') +
                              $groupPayrolls->sum('total_external_loan_paid');
            
            $totalNet = $totalGross - $totalDeductions;
            $employeeCount = $groupPayrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->count();
            });

            return [
                'pay_group' => $payGroup,
                'employee_count' => $employeeCount,
                'payroll_count' => $groupPayrolls->count(),
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalNet,
                'average_salary' => $employeeCount > 0 ? $totalNet / $employeeCount : 0,
            ];
        })->filter(function ($data) {
            return $data['employee_count'] > 0; // Only show pay groups with employees
        });

        // Calculate totals
        $totals = [
            'total_employees' => $payGroupData->sum('employee_count'),
            'total_gross' => $payGroupData->sum('total_gross'),
            'total_deductions' => $payGroupData->sum('total_deductions'),
            'total_net' => $payGroupData->sum('total_net'),
        ];

        return view('hr-payroll.reports.payroll-by-pay-group', compact(
            'payGroupData',
            'totals',
            'year',
            'month'
        ));
    }

    /**
     * Statutory Compliance Report
     */
    public function statutoryCompliance(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        // Get payrolls for the selected period
        $payrolls = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', '!=', 'cancelled')
            ->get();

        // Calculate statutory totals
        $statutoryData = [
            'paye' => [
                'name' => 'PAYE',
                'employee_total' => $payrolls->sum('total_payee'),
                'employer_total' => 0, // PAYE is employee-only
                'compliance_rate' => 100, // Assuming all employees have PAYE
            ],
            'nhif' => [
                'name' => 'NHIF',
                'employee_total' => $payrolls->sum('total_nhif_employee'),
                'employer_total' => $payrolls->sum('total_nhif_employer'),
                'compliance_rate' => 100,
            ],
            'pension' => [
                'name' => 'Pension/NSSF',
                'employee_total' => $payrolls->sum('total_pension_employee'),
                'employer_total' => $payrolls->sum('total_pension_employer'),
                'compliance_rate' => 100,
            ],
            'wcf' => [
                'name' => 'WCF',
                'employee_total' => $payrolls->sum('total_wcf'),
                'employer_total' => 0, // WCF is included in total_wcf
                'compliance_rate' => 100,
            ],
            'sdl' => [
                'name' => 'SDL',
                'employee_total' => 0, // SDL is employer-only
                'employer_total' => $payrolls->sum('total_sdl'),
                'compliance_rate' => 100,
            ],
            'heslb' => [
                'name' => 'HESLB',
                'employee_total' => $payrolls->sum('total_heslb'),
                'employer_total' => 0, // HESLB is employee-only
                'compliance_rate' => 100,
            ],
        ];

        $totals = [
            'total_employee_contributions' => array_sum(array_column($statutoryData, 'employee_total')),
            'total_employer_contributions' => array_sum(array_column($statutoryData, 'employer_total')),
            'grand_total' => array_sum(array_column($statutoryData, 'employee_total')) + 
                           array_sum(array_column($statutoryData, 'employer_total')),
        ];

        return view('hr-payroll.reports.statutory-compliance', compact(
            'statutoryData',
            'totals',
            'year',
            'month'
        ));
    }

    /**
     * Employee Payroll History Report
     */
    public function employeePayrollHistory(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $employeeId = $request->get('employee_id');
        $year = $request->get('year', date('Y'));
        
        // Get employee
        $employee = null;
        if ($employeeId) {
            $employee = Employee::where('company_id', $companyId)
                ->where('id', $employeeId)
                ->with('department', 'position')
                ->first();
        }

        // Get payroll history for the employee
        $payrollHistory = collect();
        if ($employee) {
            $payrollHistory = PayrollEmployee::whereHas('payroll', function ($q) use ($companyId, $year) {
                    $q->where('company_id', $companyId)
                      ->where('year', $year)
                      ->where('status', '!=', 'cancelled');
                })
                ->where('employee_id', $employee->id)
                ->with(['payroll' => function ($q) {
                    $q->select('id', 'year', 'month', 'reference', 'status');
                }])
                ->orderBy('payroll_id')
                ->get();
        }

        // Calculate YTD totals
        $ytdTotals = [
            'gross_salary' => $payrollHistory->sum('gross_salary'),
            'total_deductions' => $payrollHistory->sum('total_deductions'),
            'net_salary' => $payrollHistory->sum('net_salary'),
            'paye' => $payrollHistory->sum('paye'),
            'nhif' => $payrollHistory->sum('insurance'),
            'pension' => $payrollHistory->sum('pension'),
        ];

        // Get all employees for dropdown
        $employees = Employee::where('company_id', $companyId)
            ->where('include_in_payroll', true)
            ->orderBy('first_name')
            ->get(['id', 'employee_number', 'first_name', 'middle_name', 'last_name']);

        return view('hr-payroll.reports.employee-payroll-history', compact(
            'employee',
            'payrollHistory',
            'ytdTotals',
            'employees',
            'year',
            'employeeId'
        ));
    }

    /**
     * Payroll Cost Analysis Report
     */
    public function payrollCostAnalysis(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        // Get payrolls for the selected period
        $payrolls = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', '!=', 'cancelled')
            ->with('payrollEmployees')
            ->get();

        // Calculate cost breakdown
        $costBreakdown = [
            'basic_salary' => $payrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->sum('basic_salary');
            }),
            'allowances' => $payrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->sum('allowance') + 
                       $payroll->payrollEmployees->sum('other_allowances');
            }),
            'overtime' => $payrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->sum('overtime');
            }),
            'gross_salary' => $payrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->sum('gross_salary');
            }),
            'statutory_deductions' => $payrolls->sum(function ($payroll) {
                return $payroll->total_payee + 
                       $payroll->total_nhif_employee + 
                       $payroll->total_pension_employee +
                       $payroll->total_wcf +
                       $payroll->total_sdl +
                       $payroll->total_heslb;
            }),
            'other_deductions' => $payrolls->sum(function ($payroll) {
                return $payroll->total_trade_union + 
                       $payroll->total_salary_advance_paid + 
                       $payroll->total_external_loan_paid;
            }),
            'net_pay' => $payrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->sum('net_salary');
            }),
            'employer_contributions' => $payrolls->sum(function ($payroll) {
                return $payroll->total_nhif_employer + 
                       $payroll->total_pension_employer +
                       $payroll->total_sdl;
            }),
        ];

        $costBreakdown['total_cost'] = $costBreakdown['gross_salary'] + $costBreakdown['employer_contributions'];

        return view('hr-payroll.reports.payroll-cost-analysis', compact(
            'costBreakdown',
            'year',
            'month'
        ));
    }

    /**
     * Payroll Audit Trail Report
     */
    public function payrollAuditTrail(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $payrollId = $request->get('payroll_id');
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        
        $query = PayrollAuditLog::whereHas('payroll', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->with(['payroll', 'user'])
            ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()]);

        if ($payrollId) {
            $query->where('payroll_id', $payrollId);
        }

        $auditLogs = $query->orderBy('created_at', 'desc')->paginate(50);

        // Get payrolls for dropdown
        $payrolls = Payroll::where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get(['id', 'reference', 'year', 'month']);

        return view('hr-payroll.reports.payroll-audit-trail', compact(
            'auditLogs',
            'payrolls',
            'payrollId',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Year-to-Date Summary Report
     */
    public function yearToDateSummary(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        
        // Get all payrolls for the year
        $payrolls = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('status', '!=', 'cancelled')
            ->with('payrollEmployees')
            ->get();

        // Calculate YTD totals
        $ytdTotals = [
            'total_payrolls' => $payrolls->count(),
            'total_employees' => $payrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->count();
            }),
            'total_gross_salary' => $payrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->sum('gross_salary');
            }),
            'total_allowances' => $payrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->sum('allowance') + 
                       $payroll->payrollEmployees->sum('other_allowances');
            }),
            'total_overtime' => $payrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->sum('overtime');
            }),
            'total_paye' => $payrolls->sum('total_payee'),
            'total_nhif_employee' => $payrolls->sum('total_nhif_employee'),
            'total_nhif_employer' => $payrolls->sum('total_nhif_employer'),
            'total_pension_employee' => $payrolls->sum('total_pension_employee'),
            'total_pension_employer' => $payrolls->sum('total_pension_employer'),
            'total_wcf' => $payrolls->sum('total_wcf'),
            'total_sdl' => $payrolls->sum('total_sdl'),
            'total_heslb' => $payrolls->sum('total_heslb'),
            'total_trade_union' => $payrolls->sum('total_trade_union'),
            'total_deductions' => $payrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->sum('total_deductions');
            }),
            'total_net_pay' => $payrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->sum('net_salary');
            }),
        ];

        // Monthly breakdown
        $monthlyBreakdown = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthPayrolls = $payrolls->filter(function ($payroll) use ($m) {
                return $payroll->month == $m;
            });

            $monthlyBreakdown[$m] = [
                'month' => Carbon::create($year, $m, 1)->format('F'),
                'payroll_count' => $monthPayrolls->count(),
                'employee_count' => $monthPayrolls->sum(function ($payroll) {
                    return $payroll->payrollEmployees->count();
                }),
                'gross_salary' => $monthPayrolls->sum(function ($payroll) {
                    return $payroll->payrollEmployees->sum('gross_salary');
                }),
                'net_pay' => $monthPayrolls->sum(function ($payroll) {
                    return $payroll->payrollEmployees->sum('net_salary');
                }),
            ];
        }

        return view('hr-payroll.reports.year-to-date-summary', compact(
            'ytdTotals',
            'monthlyBreakdown',
            'year'
        ));
    }

    /**
     * Payroll Variance Report
     */
    public function payrollVariance(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $compareMonth = $request->get('compare_month', date('m') - 1);
        $currentMonth = $request->get('current_month', date('m'));
        
        // Get payrolls for both months
        $currentPayrolls = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $currentMonth)
            ->where('status', '!=', 'cancelled')
            ->with('payrollEmployees')
            ->get();

        $comparePayrolls = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $compareMonth)
            ->where('status', '!=', 'cancelled')
            ->with('payrollEmployees')
            ->get();

        // Calculate totals for both periods
        $currentTotals = [
            'gross_salary' => $currentPayrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->sum('gross_salary');
            }),
            'net_pay' => $currentPayrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->sum('net_salary');
            }),
            'total_deductions' => $currentPayrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->sum('total_deductions');
            }),
            'employee_count' => $currentPayrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->count();
            }),
        ];

        $compareTotals = [
            'gross_salary' => $comparePayrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->sum('gross_salary');
            }),
            'net_pay' => $comparePayrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->sum('net_salary');
            }),
            'total_deductions' => $comparePayrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->sum('total_deductions');
            }),
            'employee_count' => $comparePayrolls->sum(function ($payroll) {
                return $payroll->payrollEmployees->count();
            }),
        ];

        // Calculate variances
        $variances = [];
        foreach ($currentTotals as $key => $currentValue) {
            $compareValue = $compareTotals[$key] ?? 0;
            $variance = $currentValue - $compareValue;
            $variancePercent = $compareValue != 0 ? ($variance / $compareValue) * 100 : 0;
            
            $variances[$key] = [
                'current' => $currentValue,
                'compare' => $compareValue,
                'variance' => $variance,
                'variance_percent' => $variancePercent,
            ];
        }

        return view('hr-payroll.reports.payroll-variance', compact(
            'variances',
            'year',
            'currentMonth',
            'compareMonth'
        ));
    }

    /**
     * Bank Payment Report
     */
    public function bankPayment(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $bankAccountId = $request->get('bank_account_id');
        
        // Get bank accounts for dropdown first
        // Bank accounts are linked through chart_accounts, so we check both company_id and chart_account.company_id
        $bankAccounts = BankAccount::where(function($query) use ($companyId) {
                $query->whereHas('chartAccount.accountClassGroup', function ($subQuery) use ($companyId) {
                    $subQuery->where('company_id', $companyId);
                })
                ->orWhere('company_id', $companyId);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'account_number']);

        // Get payrolls with payment information
        // Changed: Don't require payment_date, also check for payment_status or paid status
        $query = Payroll::where('company_id', $companyId)
            ->where(function ($q) use ($dateFrom, $dateTo) {
                $q->where(function ($subQ) use ($dateFrom, $dateTo) {
                    $subQ->whereNotNull('payment_date')
                         ->whereBetween('payment_date', [$dateFrom, Carbon::parse($dateTo)->endOfDay()]);
                })
                ->orWhere(function ($subQ) use ($dateFrom, $dateTo) {
                    $subQ->whereNotNull('paid_at')
                         ->whereBetween('paid_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()]);
                })
                ->orWhere(function ($subQ) use ($dateFrom, $dateTo) {
                    $subQ->whereNull('payment_date')
                         ->whereNull('paid_at')
                         ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()]);
                });
            })
            ->whereIn('status', ['paid', 'approved', 'completed'])
            ->with(['paymentSubmittedBy', 'payGroup', 'payrollEmployees', 'paymentBankAccount']);

        if ($bankAccountId) {
            $query->where('payment_bank_account_id', $bankAccountId);
        }

        $payrolls = $query->orderBy('payment_date', 'desc')
            ->orderBy('paid_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by bank account (including null for payrolls without bank account)
        $bankGroups = $payrolls->groupBy(function ($payroll) {
            return $payroll->payment_bank_account_id ?? 'no_account';
        })->map(function ($group, $bankId) use ($bankAccounts) {
            $bankAccount = null;
            if ($bankId !== 'no_account' && $bankId) {
                $bankAccount = $bankAccounts->firstWhere('id', $bankId);
            }
            
            return [
                'bank_account_id' => $bankId === 'no_account' ? null : $bankId,
                'bank_account' => $bankAccount,
                'payroll_count' => $group->count(),
                'total_amount' => $group->sum(function ($payroll) {
                    return $payroll->payrollEmployees->sum('net_salary');
                }),
                'payrolls' => $group,
            ];
        });

        return view('hr-payroll.reports.bank-payment', compact(
            'bankGroups',
            'bankAccounts',
            'dateFrom',
            'dateTo',
            'bankAccountId'
        ));
    }

    /**
     * Statutory Compliance Report (Enhanced)
     */
    public function statutoryComplianceEnhanced(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        $departmentId = $request->get('department_id');
        
        // Get payrolls for the selected period
        $payrolls = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', '!=', 'cancelled')
            ->with('payrollEmployees.employee.department')
            ->get();

        // Filter by department if specified
        if ($departmentId) {
            $payrolls = $payrolls->filter(function ($payroll) use ($departmentId) {
                return $payroll->payrollEmployees->contains(function ($pe) use ($departmentId) {
                    return $pe->employee && $pe->employee->department_id == $departmentId;
                });
            });
        }

        // Initialize compliance validation service
        $complianceService = new StatutoryComplianceValidationService();
        
        // Get all employees from payrolls
        $employeeIds = $payrolls->flatMap(function ($payroll) {
            return $payroll->payrollEmployees->pluck('employee_id');
        })->unique();

        $employees = Employee::whereIn('id', $employeeIds)
            ->with('department', 'position', 'complianceRecords')
            ->get();

        // Validate compliance for each employee
        $complianceData = [];
        $totalViolations = 0;
        $totalWarnings = 0;
        $totalCompliant = 0;

        foreach ($employees as $employee) {
            $validation = $complianceService->validateEmployeeCompliance($employee, Carbon::create($year, $month, 1));
            
            $complianceData[] = [
                'employee' => $employee,
                'compliance' => $validation,
                'payroll_data' => $payrolls->flatMap(function ($payroll) use ($employee) {
                    return $payroll->payrollEmployees->where('employee_id', $employee->id);
                })->first(),
            ];

            if ($validation['is_compliant']) {
                $totalCompliant++;
            } else {
                $totalViolations += count($validation['violations']);
                $totalWarnings += count($validation['warnings']);
            }
        }

        // Get departments for filter
        $departments = Department::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('hr-payroll.reports.statutory-compliance-enhanced', compact(
            'complianceData',
            'totalViolations',
            'totalWarnings',
            'totalCompliant',
            'departments',
            'year',
            'month',
            'departmentId'
        ));
    }

    /**
     * Overtime Report (Labour Law & Cost Control)
     */
    public function overtimeReport(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        $departmentId = $request->get('department_id');
        
        // Get payroll employees with overtime for the selected period
        $query = PayrollEmployee::whereHas('payroll', function($q) use ($companyId, $year, $month) {
                $q->where('company_id', $companyId)
                  ->where('year', $year)
                  ->where('month', $month)
                  ->where('status', '!=', 'cancelled');
            })
            ->where('overtime_hours', '>', 0)
            ->with(['employee.department', 'employee.position', 'payroll']);

        if ($departmentId) {
            $query->whereHas('employee', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $payrollEmployees = $query->get();

        // Group by employee and calculate totals
        $overtimeData = [];
        $totalHours = 0;
        $totalAmount = 0;

        foreach ($payrollEmployees as $pe) {
            if (!$pe->employee) continue;

            $employeeId = $pe->employee_id;
            $employeeName = $pe->employee->full_name ?? $pe->employee->first_name . ' ' . $pe->employee->last_name;
            $departmentName = $pe->employee->department->name ?? 'N/A';
            $hours = (float) $pe->overtime_hours;
            $amount = (float) $pe->overtime;
            
            // Calculate average rate (if hours > 0)
            $rate = $hours > 0 ? ($amount / $hours) : 0;
            // Format rate as multiplier (e.g., 1.5x, 2.0x)
            $rateMultiplier = $rate > 0 ? number_format($rate, 1) . 'x' : 'N/A';

            // Group by employee (sum if multiple payrolls in same period)
            if (!isset($overtimeData[$employeeId])) {
                $overtimeData[$employeeId] = [
                    'employee_id' => $employeeId,
                    'employee_name' => $employeeName,
                    'employee_number' => $pe->employee->employee_number ?? '',
                    'department' => $departmentName,
                    'department_id' => $pe->employee->department_id,
                    'total_hours' => 0,
                    'total_amount' => 0,
                    'avg_rate' => 0,
                    'rate_multiplier' => 'N/A',
                    'payrolls' => [],
                ];
            }

            $overtimeData[$employeeId]['total_hours'] += $hours;
            $overtimeData[$employeeId]['total_amount'] += $amount;
            $overtimeData[$employeeId]['payrolls'][] = [
                'payroll_id' => $pe->payroll_id,
                'payroll_ref' => $pe->payroll->payroll_reference ?? '',
                'hours' => $hours,
                'amount' => $amount,
            ];
        }

        // Calculate average rate for each employee
        foreach ($overtimeData as $employeeId => &$data) {
            if ($data['total_hours'] > 0) {
                $employee = Employee::find($employeeId);
                if ($employee) {
                    // Try to get actual overtime rates from OvertimeRequestLines
                    $startDate = Carbon::create($year, $month, 1)->startOfMonth();
                    $endDate = Carbon::create($year, $month, 1)->endOfMonth();
                    
                    $overtimeRequests = OvertimeRequest::where('employee_id', $employeeId)
                        ->whereBetween('overtime_date', [$startDate, $endDate])
                        ->where('status', OvertimeRequest::STATUS_APPROVED)
                        ->with('lines')
                        ->get();
                    
                    $rates = [];
                    foreach ($overtimeRequests as $request) {
                        foreach ($request->lines as $line) {
                            $rates[] = (float) $line->overtime_rate;
                        }
                    }
                    
                    if (!empty($rates)) {
                        // Use actual rates from overtime requests
                        $avgRate = array_sum($rates) / count($rates);
                        $data['avg_rate'] = $avgRate;
                        $data['rate_multiplier'] = number_format($avgRate, 1) . 'x';
                    } else {
                        // Fallback: Calculate from amount and hours
                        // Get base salary for hourly rate calculation
                        $baseSalary = $employee->basic_salary ?? 0;
                        // Estimate hourly rate (assuming 22 working days, 8 hours per day)
                        $estimatedHourlyRate = $baseSalary > 0 ? ($baseSalary / 22 / 8) : 0;
                        
                        if ($estimatedHourlyRate > 0) {
                            $avgRateMultiplier = ($data['total_amount'] / $data['total_hours']) / $estimatedHourlyRate;
                            $data['avg_rate'] = $avgRateMultiplier;
                            $data['rate_multiplier'] = number_format($avgRateMultiplier, 1) . 'x';
                        } else {
                            $data['rate_multiplier'] = 'N/A';
                        }
                    }
                }
            }
            
            $totalHours += $data['total_hours'];
            $totalAmount += $data['total_amount'];
        }

        // Sort by total amount descending
        usort($overtimeData, function($a, $b) {
            return $b['total_amount'] <=> $a['total_amount'];
        });

        // Get departments for filter
        $departments = Department::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('hr-payroll.reports.overtime', compact(
            'overtimeData',
            'totalHours',
            'totalAmount',
            'departments',
            'year',
            'month',
            'departmentId'
        ));
    }

    /**
     * Payroll Summary Report (Executive & Audit Critical)
     */
    public function payrollSummary(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $startMonth = $request->get('start_month', 1);
        $endMonth = $request->get('end_month', 12);
        
        // Get payrolls for the selected period
        $query = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->whereBetween('month', [$startMonth, $endMonth])
            ->where('status', '!=', 'cancelled')
            ->withCount('payrollEmployees')
            ->orderBy('year')
            ->orderBy('month');

        $payrolls = $query->get();

        // Prepare summary data
        $summaryData = [];
        $totalEmployees = 0;
        $totalGrossPay = 0;
        $totalDeductions = 0;
        $totalNetPay = 0;
        $totalEmployerStatutory = 0;

        foreach ($payrolls as $payroll) {
            $employeesPaid = $payroll->payroll_employees_count ?? 0;
            $grossPay = $payroll->total_gross_pay ?? 0;
            $deductions = $payroll->total_deductions ?? 0;
            $netPay = $payroll->net_pay ?? 0;
            $employerStatutory = $payroll->total_employer_contributions ?? 0;

            $summaryData[] = [
                'payroll_id' => $payroll->id,
                'period' => $payroll->formatted_period,
                'period_label' => Carbon::create($payroll->year, $payroll->month, 1)->format('M Y'),
                'year' => $payroll->year,
                'month' => $payroll->month,
                'employees_paid' => $employeesPaid,
                'gross_pay' => $grossPay,
                'total_deductions' => $deductions,
                'net_pay' => $netPay,
                'employer_statutory' => $employerStatutory,
                'status' => $payroll->status,
                'payroll_reference' => $payroll->reference,
            ];

            $totalEmployees += $employeesPaid;
            $totalGrossPay += $grossPay;
            $totalDeductions += $deductions;
            $totalNetPay += $netPay;
            $totalEmployerStatutory += $employerStatutory;
        }

        return view('hr-payroll.reports.payroll-summary', compact(
            'summaryData',
            'totalEmployees',
            'totalGrossPay',
            'totalDeductions',
            'totalNetPay',
            'totalEmployerStatutory',
            'year',
            'startMonth',
            'endMonth'
        ));
    }

    /**
     * Leave Report (Payroll Dependency)
     */
    public function leaveReport(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        $departmentId = $request->get('department_id');
        
        // Get date range for the selected period
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        
        // Get approved/taken leave requests for the period
        $query = \App\Models\Hr\LeaveRequest::where('company_id', $companyId)
            ->whereIn('status', ['approved', 'taken'])
            ->whereHas('segments', function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_at', [$startDate, $endDate->copy()->endOfDay()])
                  ->orWhereBetween('end_at', [$startDate, $endDate->copy()->endOfDay()])
                  ->orWhere(function($subQ) use ($startDate, $endDate) {
                      $subQ->where('start_at', '<=', $startDate)
                           ->where('end_at', '>=', $endDate);
                  });
            })
            ->with(['employee.department', 'leaveType', 'segments']);

        if ($departmentId) {
            $query->whereHas('employee', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $leaveRequests = $query->get();

        // Group by employee and leave type, calculate totals
        $leaveData = [];
        $totalDaysTaken = 0;
        $totalPaidDays = 0;
        $totalUnpaidDays = 0;

        foreach ($leaveRequests as $request) {
            if (!$request->employee || !$request->leaveType) continue;

            $employeeId = $request->employee_id;
            $leaveTypeId = $request->leave_type_id;
            $key = $employeeId . '_' . $leaveTypeId;

            // Calculate days for this period (from segments)
            $daysInPeriod = 0;
            foreach ($request->segments as $segment) {
                $segmentStart = Carbon::parse($segment->start_at);
                $segmentEnd = Carbon::parse($segment->end_at);
                
                // Check if segment overlaps with the period
                if ($segmentStart->lte($endDate) && $segmentEnd->gte($startDate)) {
                    // Calculate overlap days
                    $overlapStart = $segmentStart->lt($startDate) ? $startDate : $segmentStart;
                    $overlapEnd = $segmentEnd->gt($endDate) ? $endDate : $segmentEnd;
                    
                    // Calculate days equivalent for the overlap
                    $overlapDays = $overlapStart->diffInDays($overlapEnd) + 1;
                    // Adjust for half-day if needed
                    if ($segment->granularity === 'half_day') {
                        $overlapDays = $overlapDays * 0.5;
                    } elseif ($segment->granularity === 'hourly') {
                        // Convert hours to days (assuming 8 hours per day)
                        $hours = $segmentStart->diffInHours($segmentEnd);
                        $overlapDays = $hours / 8;
                    }
                    
                    $daysInPeriod += min($overlapDays, (float)$segment->days_equivalent);
                }
            }

            if ($daysInPeriod <= 0) continue;

            // Initialize employee-leave type entry
            if (!isset($leaveData[$key])) {
                $employeeName = $request->employee->full_name ?? 
                               $request->employee->first_name . ' ' . $request->employee->last_name;
                
                $leaveData[$key] = [
                    'employee_id' => $employeeId,
                    'employee_name' => $employeeName,
                    'employee_number' => $request->employee->employee_number ?? '',
                    'department' => $request->employee->department->name ?? 'N/A',
                    'leave_type' => $request->leaveType->name,
                    'leave_type_id' => $leaveTypeId,
                    'is_paid' => $request->leaveType->is_paid,
                    'days_taken' => 0,
                    'paid_days' => 0,
                    'unpaid_days' => 0,
                ];
            }

            // Add days
            $leaveData[$key]['days_taken'] += $daysInPeriod;
            
            if ($request->leaveType->is_paid) {
                $leaveData[$key]['paid_days'] += $daysInPeriod;
            } else {
                $leaveData[$key]['unpaid_days'] += $daysInPeriod;
            }

            $totalDaysTaken += $daysInPeriod;
            if ($request->leaveType->is_paid) {
                $totalPaidDays += $daysInPeriod;
            } else {
                $totalUnpaidDays += $daysInPeriod;
            }
        }

        // Get balances for each employee-leave type combination
        foreach ($leaveData as $key => &$data) {
            $balance = \App\Models\Hr\LeaveBalance::getLatestBalance($data['employee_id'], $data['leave_type_id']);
            if ($balance) {
                $data['balance'] = $balance->available_days;
            } else {
                // If no balance record, check if leave type has annual entitlement
                $leaveType = \App\Models\Hr\LeaveType::find($data['leave_type_id']);
                if ($leaveType && $leaveType->annual_entitlement) {
                    // Calculate balance: annual entitlement - days taken
                    $data['balance'] = $leaveType->annual_entitlement - $data['days_taken'];
                } else {
                    $data['balance'] = null; // Show as "—" if no balance info
                }
            }
        }

        // Sort by employee name, then leave type
        usort($leaveData, function($a, $b) {
            $nameCompare = strcmp($a['employee_name'], $b['employee_name']);
            if ($nameCompare !== 0) return $nameCompare;
            return strcmp($a['leave_type'], $b['leave_type']);
        });

        // Get departments for filter
        $departments = \App\Models\Hr\Department::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('hr-payroll.reports.leave', compact(
            'leaveData',
            'totalDaysTaken',
            'totalPaidDays',
            'totalUnpaidDays',
            'departments',
            'year',
            'month',
            'departmentId'
        ));
    }

    /**
     * PAYE Remittance Report (TRA)
     */
    public function payeRemittance(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        // Get payroll employees with PAYE for the selected period
        $payrollEmployees = PayrollEmployee::whereHas('payroll', function($q) use ($companyId, $year, $month) {
                $q->where('company_id', $companyId)
                  ->where('year', $year)
                  ->where('month', $month)
                  ->where('status', '!=', 'cancelled');
            })
            ->where('paye', '>', 0)
            ->with(['employee.complianceRecords', 'payroll'])
            ->get();

        $reportData = [];
        $totalTaxableIncome = 0;
        $totalPAYE = 0;
        $sn = 1;

        foreach ($payrollEmployees as $pe) {
            if (!$pe->employee) continue;

            // Get TIN from employee or compliance record
            $tin = $pe->employee->tin;
            if (empty($tin)) {
                $payeCompliance = $pe->employee->complianceRecords()
                    ->where('compliance_type', EmployeeCompliance::TYPE_PAYE)
                    ->first();
                $tin = $payeCompliance ? $payeCompliance->compliance_number : 'N/A';
            }

            // Calculate taxable income (gross salary - pension - nhif - other non-taxable deductions)
            // For Tanzania, taxable income = gross salary - pension - nhif
            $taxableIncome = $pe->gross_salary - ($pe->pension ?? 0) - ($pe->insurance ?? 0);

            $reportData[] = [
                'sn' => $sn++,
                'employee_number' => $pe->employee->employee_number ?? '',
                'employee_name' => $pe->employee->full_name ?? $pe->employee->first_name . ' ' . $pe->employee->last_name,
                'tin' => $tin,
                'gross_pay' => $pe->gross_salary,
                'taxable_income' => max(0, $taxableIncome),
                'paye_amount' => $pe->paye,
            ];

            $totalTaxableIncome += max(0, $taxableIncome);
            $totalPAYE += $pe->paye;
        }

        $company = \App\Models\Company::find($companyId);
        $payroll = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        return view('hr-payroll.reports.paye-remittance', compact(
            'reportData',
            'totalTaxableIncome',
            'totalPAYE',
            'year',
            'month',
            'company',
            'payroll'
        ));
    }

    /**
     * NSSF Remittance Report
     */
    public function nssfRemittance(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        // Get payroll employees with pension for the selected period
        $payrollEmployees = PayrollEmployee::whereHas('payroll', function($q) use ($companyId, $year, $month) {
                $q->where('company_id', $companyId)
                  ->where('year', $year)
                  ->where('month', $month)
                  ->where('status', '!=', 'cancelled');
            })
            ->where('pension', '>', 0)
            ->with(['employee.complianceRecords', 'payroll'])
            ->get();

        $reportData = [];
        $totalEmployee = 0;
        $totalEmployer = 0;
        $sn = 1;

        foreach ($payrollEmployees as $pe) {
            if (!$pe->employee || !$pe->employee->has_pension) continue;

            // Get NSSF number from employee or compliance record
            $nssfNumber = $pe->employee->social_fund_number;
            if (empty($nssfNumber)) {
                $pensionCompliance = $pe->employee->complianceRecords()
                    ->where('compliance_type', EmployeeCompliance::TYPE_PENSION)
                    ->first();
                $nssfNumber = $pensionCompliance ? $pensionCompliance->compliance_number : 'N/A';
            }

            // Calculate pensionable salary (usually gross salary or basic salary)
            $pensionableSalary = $pe->gross_salary;
            
            // Employee contribution (10% typically)
            $employeeContribution = $pe->pension;
            
            // Employer contribution - calculate from employee's employer percent
            if ($pe->employee->pension_employer_percent) {
                $employerContribution = $pensionableSalary * ($pe->employee->pension_employer_percent / 100);
            } else {
                // Default 10% if not set
                $employerContribution = $pensionableSalary * 0.10;
            }

            $reportData[] = [
                'sn' => $sn++,
                'employee_number' => $pe->employee->employee_number ?? '',
                'employee_name' => $pe->employee->full_name ?? $pe->employee->first_name . ' ' . $pe->employee->last_name,
                'nssf_number' => $nssfNumber,
                'pensionable_salary' => $pensionableSalary,
                'employee_contribution' => $employeeContribution,
                'employer_contribution' => $employerContribution,
                'total_contribution' => $employeeContribution + $employerContribution,
            ];

            $totalEmployee += $employeeContribution;
            $totalEmployer += $employerContribution;
        }

        $company = \App\Models\Company::find($companyId);
        $payroll = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        return view('hr-payroll.reports.nssf-remittance', compact(
            'reportData',
            'totalEmployee',
            'totalEmployer',
            'year',
            'month',
            'company',
            'payroll'
        ));
    }

    /**
     * NHIF Remittance Report
     */
    public function nhifRemittance(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        // Get payroll employees with NHIF for the selected period
        $payrollEmployees = PayrollEmployee::whereHas('payroll', function($q) use ($companyId, $year, $month) {
                $q->where('company_id', $companyId)
                  ->where('year', $year)
                  ->where('month', $month)
                  ->where('status', '!=', 'cancelled');
            })
            ->where('insurance', '>', 0)
            ->with(['employee.complianceRecords', 'payroll'])
            ->get();

        $reportData = [];
        $totalNHIF = 0;
        $sn = 1;

        foreach ($payrollEmployees as $pe) {
            if (!$pe->employee || !$pe->employee->has_nhif) continue;

            // Get NHIF number from employee or compliance record
            $nhifNumber = $pe->employee->nhif_member_number;
            if (empty($nhifNumber)) {
                $nhifCompliance = $pe->employee->complianceRecords()
                    ->where('compliance_type', EmployeeCompliance::TYPE_NHIF)
                    ->first();
                $nhifNumber = $nhifCompliance ? $nhifCompliance->compliance_number : 'N/A';
            }

            // Salary band (for NHIF calculation)
            $salaryBand = $pe->gross_salary;
            $nhifAmount = $pe->insurance;

            $reportData[] = [
                'sn' => $sn++,
                'employee_number' => $pe->employee->employee_number ?? '',
                'employee_name' => $pe->employee->full_name ?? $pe->employee->first_name . ' ' . $pe->employee->last_name,
                'nhif_number' => $nhifNumber,
                'salary_band' => $salaryBand,
                'nhif_amount' => $nhifAmount,
            ];

            $totalNHIF += $nhifAmount;
        }

        $company = \App\Models\Company::find($companyId);
        $payroll = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        return view('hr-payroll.reports.nhif-remittance', compact(
            'reportData',
            'totalNHIF',
            'year',
            'month',
            'company',
            'payroll'
        ));
    }

    /**
     * WCF Remittance Report
     */
    public function wcfRemittance(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        // Get payroll employees with WCF for the selected period
        $payrollEmployees = PayrollEmployee::whereHas('payroll', function($q) use ($companyId, $year, $month) {
                $q->where('company_id', $companyId)
                  ->where('year', $year)
                  ->where('month', $month)
                  ->where('status', '!=', 'cancelled');
            })
            ->where('wcf', '>', 0)
            ->with(['employee', 'payroll'])
            ->get();

        $reportData = [];
        $totalGross = 0;
        $totalWCF = 0;
        $sn = 1;

        foreach ($payrollEmployees as $pe) {
            if (!$pe->employee) continue;

            $grossSalary = $pe->gross_salary;
            $wcfAmount = $pe->wcf;
            $wcfRate = $grossSalary > 0 ? ($wcfAmount / $grossSalary) * 100 : 0;

            $reportData[] = [
                'sn' => $sn++,
                'employee_number' => $pe->employee->employee_number ?? '',
                'employee_name' => $pe->employee->full_name ?? $pe->employee->first_name . ' ' . $pe->employee->last_name,
                'gross_salary' => $grossSalary,
                'wcf_rate' => $wcfRate,
                'wcf_amount' => $wcfAmount,
            ];

            $totalGross += $grossSalary;
            $totalWCF += $wcfAmount;
        }

        $company = \App\Models\Company::find($companyId);
        $payroll = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        return view('hr-payroll.reports.wcf-remittance', compact(
            'reportData',
            'totalGross',
            'totalWCF',
            'year',
            'month',
            'company',
            'payroll'
        ));
    }

    /**
     * SDL Remittance Report (Skills Development Levy)
     */
    public function sdlRemittance(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        // Get payroll employees with SDL for the selected period
        $payrollEmployees = PayrollEmployee::whereHas('payroll', function($q) use ($companyId, $year, $month) {
                $q->where('company_id', $companyId)
                  ->where('year', $year)
                  ->where('month', $month)
                  ->where('status', '!=', 'cancelled');
            })
            ->where('sdl', '>', 0)
            ->with(['employee', 'payroll'])
            ->get();

        $reportData = [];
        $totalGross = 0;
        $totalSDL = 0;
        $sn = 1;

        foreach ($payrollEmployees as $pe) {
            if (!$pe->employee) continue;

            $grossSalary = $pe->gross_salary;
            $sdlAmount = $pe->sdl;
            $sdlRate = 4.0; // Standard SDL rate is 4%

            $reportData[] = [
                'sn' => $sn++,
                'employee_number' => $pe->employee->employee_number ?? '',
                'employee_name' => $pe->employee->full_name ?? $pe->employee->first_name . ' ' . $pe->employee->last_name,
                'gross_salary' => $grossSalary,
                'sdl_rate' => $sdlRate,
                'sdl_amount' => $sdlAmount,
            ];

            $totalGross += $grossSalary;
            $totalSDL += $sdlAmount;
        }

        $company = \App\Models\Company::find($companyId);
        $payroll = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        return view('hr-payroll.reports.sdl-remittance', compact(
            'reportData',
            'totalGross',
            'totalSDL',
            'year',
            'month',
            'company',
            'payroll'
        ));
    }

    /**
     * HESLB Loan Repayment Report
     */
    public function heslbRemittance(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        // Get payroll employees with HESLB for the selected period
        $payrollEmployees = PayrollEmployee::whereHas('payroll', function($q) use ($companyId, $year, $month) {
                $q->where('company_id', $companyId)
                  ->where('year', $year)
                  ->where('month', $month)
                  ->where('status', '!=', 'cancelled');
            })
            ->where('heslb', '>', 0)
            ->with(['employee.complianceRecords', 'payroll'])
            ->get();

        $reportData = [];
        $totalDeduction = 0;
        $sn = 1;

        foreach ($payrollEmployees as $pe) {
            if (!$pe->employee || !$pe->employee->has_heslb) continue;

            // Get HESLB number (usually stored in compliance records or external loans)
            $heslbNumber = 'N/A';
            $heslbCompliance = $pe->employee->complianceRecords()
                ->where('compliance_type', 'heslb')
                ->first();
            if ($heslbCompliance) {
                $heslbNumber = $heslbCompliance->compliance_number;
            }

            $grossSalary = $pe->gross_salary;
            $heslbAmount = $pe->heslb;
            $deductionRate = $grossSalary > 0 ? ($heslbAmount / $grossSalary) * 100 : 0;

            $reportData[] = [
                'sn' => $sn++,
                'employee_number' => $pe->employee->employee_number ?? '',
                'employee_name' => $pe->employee->full_name ?? $pe->employee->first_name . ' ' . $pe->employee->last_name,
                'heslb_number' => $heslbNumber,
                'gross_salary' => $grossSalary,
                'deduction_rate' => $deductionRate,
                'amount' => $heslbAmount,
            ];

            $totalDeduction += $heslbAmount;
        }

        $company = \App\Models\Company::find($companyId);
        $payroll = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        return view('hr-payroll.reports.heslb-remittance', compact(
            'reportData',
            'totalDeduction',
            'year',
            'month',
            'company',
            'payroll'
        ));
    }

    /**
     * Combined Statutory Remittance Control Report
     */
    public function combinedStatutoryRemittance(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        // Get payroll for the period
        $payroll = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', '!=', 'cancelled')
            ->first();

        if (!$payroll) {
            return redirect()->route('hr.payroll-reports.index')
                ->with('error', 'No payroll found for the selected period.');
        }

        // Calculate due dates (typically 7th of next month for TRA, end of month for others)
        $periodDate = Carbon::create($year, $month, 1);
        $nextMonth = $periodDate->copy()->addMonth();
        
        $statutoryData = [
            [
                'statutory' => 'PAYE (TRA)',
                'amount_payable' => $payroll->total_payee ?? 0,
                'due_date' => $nextMonth->copy()->day(7)->format('Y-m-d'),
                'paid_date' => $payroll->paid_at ? $payroll->paid_at->format('Y-m-d') : null,
                'control_number' => $payroll->payment_reference ?? '—',
                'status' => $payroll->status == 'paid' ? 'Paid' : ($payroll->status == 'approved' ? 'Approved' : 'Pending'),
            ],
            [
                'statutory' => 'SDL (TRA)',
                'amount_payable' => $payroll->total_sdl ?? 0,
                'due_date' => $nextMonth->copy()->day(7)->format('Y-m-d'),
                'paid_date' => $payroll->paid_at ? $payroll->paid_at->format('Y-m-d') : null,
                'control_number' => $payroll->payment_reference ?? '—',
                'status' => $payroll->status == 'paid' ? 'Paid' : ($payroll->status == 'approved' ? 'Approved' : 'Pending'),
            ],
            [
                'statutory' => 'NSSF',
                'amount_payable' => ($payroll->total_pension_employee ?? 0) + ($payroll->total_pension_employer ?? 0),
                'due_date' => $nextMonth->copy()->endOfMonth()->format('Y-m-d'),
                'paid_date' => $payroll->paid_at ? $payroll->paid_at->format('Y-m-d') : null,
                'control_number' => $payroll->payment_reference ?? '—',
                'status' => $payroll->status == 'paid' ? 'Paid' : ($payroll->status == 'approved' ? 'Approved' : 'Pending'),
            ],
            [
                'statutory' => 'NHIF',
                'amount_payable' => ($payroll->total_nhif_employee ?? 0) + ($payroll->total_nhif_employer ?? 0),
                'due_date' => $nextMonth->copy()->endOfMonth()->format('Y-m-d'),
                'paid_date' => $payroll->paid_at ? $payroll->paid_at->format('Y-m-d') : null,
                'control_number' => $payroll->payment_reference ?? '—',
                'status' => $payroll->status == 'paid' ? 'Paid' : ($payroll->status == 'approved' ? 'Approved' : 'Pending'),
            ],
            [
                'statutory' => 'WCF',
                'amount_payable' => $payroll->total_wcf ?? 0,
                'due_date' => $nextMonth->copy()->endOfMonth()->format('Y-m-d'),
                'paid_date' => $payroll->paid_at ? $payroll->paid_at->format('Y-m-d') : null,
                'control_number' => $payroll->payment_reference ?? '—',
                'status' => $payroll->status == 'paid' ? 'Paid' : ($payroll->status == 'approved' ? 'Approved' : 'Pending'),
            ],
            [
                'statutory' => 'HESLB',
                'amount_payable' => $payroll->total_heslb ?? 0,
                'due_date' => $nextMonth->copy()->endOfMonth()->format('Y-m-d'),
                'paid_date' => $payroll->paid_at ? $payroll->paid_at->format('Y-m-d') : null,
                'control_number' => $payroll->payment_reference ?? '—',
                'status' => $payroll->status == 'paid' ? 'Paid' : ($payroll->status == 'approved' ? 'Approved' : 'Pending'),
            ],
        ];

        $company = \App\Models\Company::find($companyId);
        $totalAmount = array_sum(array_column($statutoryData, 'amount_payable'));

        return view('hr-payroll.reports.combined-statutory-remittance', compact(
            'statutoryData',
            'totalAmount',
            'year',
            'month',
            'company',
            'payroll'
        ));
    }

    /**
     * Export Payroll by Department to Excel
     */
    public function payrollByDepartmentExportExcel(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        $departments = Department::where('company_id', $companyId)->orderBy('name')->get();
        $payrolls = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', '!=', 'cancelled')
            ->with(['payrollEmployees.employee.department'])
            ->get();

        $departmentData = $departments->map(function ($department) use ($payrolls) {
            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;
            $employeeCount = 0;

            foreach ($payrolls as $payroll) {
                $deptEmployees = $payroll->payrollEmployees->filter(function ($pe) use ($department) {
                    return $pe->employee && $pe->employee->department_id == $department->id;
                });

                if ($deptEmployees->count() > 0) {
                    $employeeCount += $deptEmployees->count();
                    $totalGross += $deptEmployees->sum('gross_salary');
                    $totalDeductions += $deptEmployees->sum('total_deductions');
                    $totalNet += $deptEmployees->sum('net_salary');
                }
            }

            return [
                'department' => $department,
                'employee_count' => $employeeCount,
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalNet,
                'average_salary' => $employeeCount > 0 ? $totalNet / $employeeCount : 0,
            ];
        })->filter(function ($data) {
            return $data['employee_count'] > 0;
        });

        $totals = [
            'total_employees' => $departmentData->sum('employee_count'),
            'total_gross' => $departmentData->sum('total_gross'),
            'total_deductions' => $departmentData->sum('total_deductions'),
            'total_net' => $departmentData->sum('total_net'),
        ];

        return Excel::download(
            new PayrollByDepartmentExport($departmentData, $totals, $year, $month),
            'payroll_by_department_' . $year . '_' . $month . '.xlsx'
        );
    }

    /**
     * Export Payroll by Department to PDF
     */
    public function payrollByDepartmentExportPdf(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        
        $departments = Department::where('company_id', $companyId)->orderBy('name')->get();
        $payrolls = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', '!=', 'cancelled')
            ->with(['payrollEmployees.employee.department'])
            ->get();

        $departmentData = $departments->map(function ($department) use ($payrolls) {
            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;
            $employeeCount = 0;

            foreach ($payrolls as $payroll) {
                $deptEmployees = $payroll->payrollEmployees->filter(function ($pe) use ($department) {
                    return $pe->employee && $pe->employee->department_id == $department->id;
                });

                if ($deptEmployees->count() > 0) {
                    $employeeCount += $deptEmployees->count();
                    $totalGross += $deptEmployees->sum('gross_salary');
                    $totalDeductions += $deptEmployees->sum('total_deductions');
                    $totalNet += $deptEmployees->sum('net_salary');
                }
            }

            return [
                'department' => $department,
                'employee_count' => $employeeCount,
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalNet,
                'average_salary' => $employeeCount > 0 ? $totalNet / $employeeCount : 0,
            ];
        })->filter(function ($data) {
            return $data['employee_count'] > 0;
        });

        $totals = [
            'total_employees' => $departmentData->sum('employee_count'),
            'total_gross' => $departmentData->sum('total_gross'),
            'total_deductions' => $departmentData->sum('total_deductions'),
            'total_net' => $departmentData->sum('total_net'),
        ];

        $company = \App\Models\Company::find($companyId);

        $pdf = Pdf::loadView('hr-payroll.reports.exports.payroll-by-department-pdf', compact(
            'departmentData',
            'totals',
            'year',
            'month',
            'company'
        ));

        return $pdf->download('payroll_by_department_' . $year . '_' . $month . '.pdf');
    }

    /**
     * Export Year-to-Date Summary to Excel
     */
    public function yearToDateSummaryExportExcel(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        
        $payrolls = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('status', '!=', 'cancelled')
            ->with('payrollEmployees')
            ->get();

        $ytdTotals = [
            'total_payrolls' => $payrolls->count(),
            'total_employees' => $payrolls->sum(fn($p) => $p->payrollEmployees->count()),
            'total_gross_salary' => $payrolls->sum(fn($p) => $p->payrollEmployees->sum('gross_salary')),
            'total_allowances' => $payrolls->sum(fn($p) => $p->payrollEmployees->sum('allowance') + $p->payrollEmployees->sum('other_allowances')),
            'total_overtime' => $payrolls->sum(fn($p) => $p->payrollEmployees->sum('overtime')),
            'total_paye' => $payrolls->sum('total_payee'),
            'total_nhif_employee' => $payrolls->sum('total_nhif_employee'),
            'total_nhif_employer' => $payrolls->sum('total_nhif_employer'),
            'total_pension_employee' => $payrolls->sum('total_pension_employee'),
            'total_pension_employer' => $payrolls->sum('total_pension_employer'),
            'total_wcf' => $payrolls->sum('total_wcf'),
            'total_sdl' => $payrolls->sum('total_sdl'),
            'total_heslb' => $payrolls->sum('total_heslb'),
            'total_trade_union' => $payrolls->sum('total_trade_union'),
            'total_deductions' => $payrolls->sum(fn($p) => $p->payrollEmployees->sum('total_deductions')),
            'total_net_pay' => $payrolls->sum(fn($p) => $p->payrollEmployees->sum('net_salary')),
        ];

        $monthlyBreakdown = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthPayrolls = $payrolls->filter(fn($p) => $p->month == $m);
            $monthlyBreakdown[$m] = [
                'month' => Carbon::create($year, $m, 1)->format('F'),
                'payroll_count' => $monthPayrolls->count(),
                'employee_count' => $monthPayrolls->sum(fn($p) => $p->payrollEmployees->count()),
                'gross_salary' => $monthPayrolls->sum(fn($p) => $p->payrollEmployees->sum('gross_salary')),
                'net_pay' => $monthPayrolls->sum(fn($p) => $p->payrollEmployees->sum('net_salary')),
            ];
        }

        return Excel::download(
            new YearToDateSummaryExport($ytdTotals, $monthlyBreakdown, $year),
            'year_to_date_summary_' . $year . '.xlsx'
        );
    }

    /**
     * Export Year-to-Date Summary to PDF
     */
    public function yearToDateSummaryExportPdf(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        
        $payrolls = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('status', '!=', 'cancelled')
            ->with('payrollEmployees')
            ->get();

        $ytdTotals = [
            'total_payrolls' => $payrolls->count(),
            'total_employees' => $payrolls->sum(fn($p) => $p->payrollEmployees->count()),
            'total_gross_salary' => $payrolls->sum(fn($p) => $p->payrollEmployees->sum('gross_salary')),
            'total_allowances' => $payrolls->sum(fn($p) => $p->payrollEmployees->sum('allowance') + $p->payrollEmployees->sum('other_allowances')),
            'total_overtime' => $payrolls->sum(fn($p) => $p->payrollEmployees->sum('overtime')),
            'total_paye' => $payrolls->sum('total_payee'),
            'total_nhif_employee' => $payrolls->sum('total_nhif_employee'),
            'total_nhif_employer' => $payrolls->sum('total_nhif_employer'),
            'total_pension_employee' => $payrolls->sum('total_pension_employee'),
            'total_pension_employer' => $payrolls->sum('total_pension_employer'),
            'total_wcf' => $payrolls->sum('total_wcf'),
            'total_sdl' => $payrolls->sum('total_sdl'),
            'total_heslb' => $payrolls->sum('total_heslb'),
            'total_trade_union' => $payrolls->sum('total_trade_union'),
            'total_deductions' => $payrolls->sum(fn($p) => $p->payrollEmployees->sum('total_deductions')),
            'total_net_pay' => $payrolls->sum(fn($p) => $p->payrollEmployees->sum('net_salary')),
        ];

        $monthlyBreakdown = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthPayrolls = $payrolls->filter(fn($p) => $p->month == $m);
            $monthlyBreakdown[$m] = [
                'month' => Carbon::create($year, $m, 1)->format('F'),
                'payroll_count' => $monthPayrolls->count(),
                'employee_count' => $monthPayrolls->sum(fn($p) => $p->payrollEmployees->count()),
                'gross_salary' => $monthPayrolls->sum(fn($p) => $p->payrollEmployees->sum('gross_salary')),
                'net_pay' => $monthPayrolls->sum(fn($p) => $p->payrollEmployees->sum('net_salary')),
            ];
        }

        $company = \App\Models\Company::find($companyId);

        $pdf = Pdf::loadView('hr-payroll.reports.exports.year-to-date-summary-pdf', compact(
            'ytdTotals',
            'monthlyBreakdown',
            'year',
            'company'
        ));

        return $pdf->download('year_to_date_summary_' . $year . '.pdf');
    }

    /**
     * Export Payroll Variance to Excel
     */
    public function payrollVarianceExportExcel(Request $request)
    {
        $request->merge([
            'year' => $request->get('year', date('Y')),
            'compare_month' => $request->get('compare_month', date('m') - 1),
            'current_month' => $request->get('current_month', date('m')),
        ]);
        $data = $this->getPayrollVarianceData($request);
        if (!$data) {
            return redirect()->back()->with('error', 'Unable to generate report.');
        }
        return Excel::download(
            new PayrollVarianceExport($data['variances'], $data['year'], $data['currentMonth'], $data['compareMonth']),
            'payroll_variance_' . $data['year'] . '_' . $data['currentMonth'] . '.xlsx'
        );
    }

    /**
     * Export Payroll Variance to PDF
     */
    public function payrollVarianceExportPdf(Request $request)
    {
        $request->merge([
            'year' => $request->get('year', date('Y')),
            'compare_month' => $request->get('compare_month', date('m') - 1),
            'current_month' => $request->get('current_month', date('m')),
        ]);
        $data = $this->getPayrollVarianceData($request);
        if (!$data) {
            return redirect()->back()->with('error', 'Unable to generate report.');
        }
        $company = \App\Models\Company::find(auth()->user()->company_id);
        $pdf = Pdf::loadView('hr-payroll.reports.exports.payroll-variance-pdf', array_merge($data, ['company' => $company]));
        return $pdf->download('payroll_variance_' . $data['year'] . '_' . $data['currentMonth'] . '.pdf');
    }

    protected function getPayrollVarianceData(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $compareMonth = $request->get('compare_month', date('m') - 1);
        $currentMonth = $request->get('current_month', date('m'));
        
        $currentPayrolls = Payroll::where('company_id', $companyId)
            ->where('year', $year)->where('month', $currentMonth)
            ->where('status', '!=', 'cancelled')->with('payrollEmployees')->get();
        $comparePayrolls = Payroll::where('company_id', $companyId)
            ->where('year', $year)->where('month', $compareMonth)
            ->where('status', '!=', 'cancelled')->with('payrollEmployees')->get();

        $currentTotals = [
            'gross_salary' => $currentPayrolls->sum(fn($p) => $p->payrollEmployees->sum('gross_salary')),
            'net_pay' => $currentPayrolls->sum(fn($p) => $p->payrollEmployees->sum('net_salary')),
            'total_deductions' => $currentPayrolls->sum(fn($p) => $p->payrollEmployees->sum('total_deductions')),
            'employee_count' => $currentPayrolls->sum(fn($p) => $p->payrollEmployees->count()),
        ];
        $compareTotals = [
            'gross_salary' => $comparePayrolls->sum(fn($p) => $p->payrollEmployees->sum('gross_salary')),
            'net_pay' => $comparePayrolls->sum(fn($p) => $p->payrollEmployees->sum('net_salary')),
            'total_deductions' => $comparePayrolls->sum(fn($p) => $p->payrollEmployees->sum('total_deductions')),
            'employee_count' => $comparePayrolls->sum(fn($p) => $p->payrollEmployees->count()),
        ];

        $variances = [];
        foreach ($currentTotals as $key => $currentValue) {
            $compareValue = $compareTotals[$key] ?? 0;
            $variance = $currentValue - $compareValue;
            $variances[$key] = [
                'current' => $currentValue,
                'compare' => $compareValue,
                'variance' => $variance,
                'variance_percent' => $compareValue != 0 ? ($variance / $compareValue) * 100 : 0,
            ];
        }

        return compact('variances', 'year', 'currentMonth', 'compareMonth');
    }

    /**
     * Export Bank Payment to Excel
     */
    public function bankPaymentExportExcel(Request $request)
    {
        $data = $this->getBankPaymentData($request);
        return Excel::download(
            new BankPaymentExport($data['bankGroups'], $data['dateFrom'], $data['dateTo']),
            'bank_payment_' . $data['dateFrom'] . '_' . $data['dateTo'] . '.xlsx'
        );
    }

    /**
     * Export Bank Payment to PDF
     */
    public function bankPaymentExportPdf(Request $request)
    {
        $data = $this->getBankPaymentData($request);
        $company = \App\Models\Company::find(auth()->user()->company_id);
        $pdf = Pdf::loadView('hr-payroll.reports.exports.bank-payment-pdf', array_merge($data, ['company' => $company]));
        return $pdf->download('bank_payment_' . $data['dateFrom'] . '_' . $data['dateTo'] . '.pdf');
    }

    protected function getBankPaymentData(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
        $bankAccountId = $request->get('bank_account_id');
        
        $bankAccounts = BankAccount::where(function($q) use ($companyId) {
                $q->whereHas('chartAccount.accountClassGroup', fn($sq) => $sq->where('company_id', $companyId))
                  ->orWhere('company_id', $companyId);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'account_number']);

        $query = Payroll::where('company_id', $companyId)
            ->where(function ($q) use ($dateFrom, $dateTo) {
                $q->where(function ($subQ) use ($dateFrom, $dateTo) {
                    $subQ->whereNotNull('payment_date')
                         ->whereBetween('payment_date', [$dateFrom, Carbon::parse($dateTo)->endOfDay()]);
                })
                ->orWhere(function ($subQ) use ($dateFrom, $dateTo) {
                    $subQ->whereNotNull('paid_at')
                         ->whereBetween('paid_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()]);
                })
                ->orWhere(function ($subQ) use ($dateFrom, $dateTo) {
                    $subQ->whereNull('payment_date')
                         ->whereNull('paid_at')
                         ->whereBetween('created_at', [$dateFrom, Carbon::parse($dateTo)->endOfDay()]);
                });
            })
            ->whereIn('status', ['paid', 'approved', 'completed'])
            ->with(['payrollEmployees', 'paymentBankAccount']);

        if ($bankAccountId) {
            $query->where('payment_bank_account_id', $bankAccountId);
        }

        $payrolls = $query->orderBy('payment_date', 'desc')->orderBy('paid_at', 'desc')->orderBy('created_at', 'desc')->get();

        $bankGroups = $payrolls->groupBy(function ($payroll) {
            return $payroll->payment_bank_account_id ?? 'no_account';
        })->map(function ($group, $bankId) use ($bankAccounts) {
            $bankAccount = ($bankId !== 'no_account' && $bankId) ? $bankAccounts->firstWhere('id', $bankId) : null;
            return [
                'bank_account_id' => $bankId === 'no_account' ? null : $bankId,
                'bank_account' => $bankAccount,
                'payroll_count' => $group->count(),
                'total_amount' => $group->sum(fn($p) => $p->payrollEmployees->sum('net_salary')),
                'payrolls' => $group,
            ];
        });

        return compact('bankGroups', 'dateFrom', 'dateTo');
    }
}
