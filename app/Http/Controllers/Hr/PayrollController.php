<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollEmployee;
use App\Models\PayrollApproval;
use App\Models\PayrollApprovalSettings;
use App\Models\PayrollPaymentApproval;
use App\Models\PayrollPaymentApprovalSettings;
use App\Models\Company;
use App\Models\Hr\Employee;
use App\Models\Hr\Allowance;
use App\Models\Hr\SalaryAdvance;
use App\Models\Hr\ExternalLoan;
use App\Models\Hr\PayrollChartAccount;
use App\Models\Hr\PayrollCalendar;
use App\Models\Hr\PayGroup;
use App\Models\Hr\Attendance;
use App\Models\Hr\OvertimeRequest;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\LeaveSegment;
use App\Services\Hr\AttendanceService;
use App\Services\Hr\PayrollCalculationService;
use Carbon\Carbon;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use App\Models\ChartAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollController extends Controller
{
    protected $attendanceService;
    protected $payrollCalculationService;

    public function __construct(AttendanceService $attendanceService, PayrollCalculationService $payrollCalculationService)
    {
        $this->attendanceService = $attendanceService;
        $this->payrollCalculationService = $payrollCalculationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $payrolls = Payroll::with(['creator', 'company'])
                ->where('company_id', current_company_id())
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc');

            return DataTables::of($payrolls)
                ->addIndexColumn()
                ->addColumn('month_name', function ($payroll) {
                    return $payroll->month_name;
                })
                ->addColumn('total_gross_pay', function ($payroll) {
                    return number_format($payroll->total_gross_pay, 2);
                })
                ->addColumn('total_deductions', function ($payroll) {
                    return number_format($payroll->total_deductions, 2);
                })
                ->addColumn('net_pay', function ($payroll) {
                    return number_format($payroll->net_pay, 2);
                })
                ->addColumn('status_badge', function ($payroll) {
                    $badgeClass = match($payroll->status) {
                        'draft' => 'bg-danger',
                        'processing' => 'bg-warning',
                        'completed' => 'bg-success',
                        'cancelled' => 'bg-secondary',
                        'paid' => 'bg-primary',
                        default => 'bg-secondary'
                    };
                    
                    $icon = match($payroll->status) {
                        'draft' => 'bx bx-edit-alt',
                        'processing' => 'bx bx-time-five',
                        'completed' => 'bx bx-check-circle',
                        'cancelled' => 'bx bx-x-circle',
                        'paid' => 'bx bx-money',
                        default => 'bx bx-question-mark'
                    };
                    
                    $statusHtml = '<span class="badge ' . $badgeClass . ' d-flex align-items-center gap-1">
                                        <i class="' . $icon . '"></i>' . ucfirst($payroll->status) . '
                                   </span>';
                    
                    // Add payment status for completed payrolls
                    if ($payroll->status === 'completed') {
                        $paymentBadgeClass = $payroll->payment_status === 'paid' ? 'bg-success' : 'bg-warning';
                        $paymentIcon = $payroll->payment_status === 'paid' ? 'bx bx-check-double' : 'bx bx-credit-card';
                        $statusHtml .= '<br><span class="badge ' . $paymentBadgeClass . ' d-flex align-items-center gap-1 mt-1" style="font-size: 0.75em;">
                                            <i class="' . $paymentIcon . '"></i>Payment ' . ucfirst($payroll->payment_status) . '
                                        </span>';
                    }
                    
                    return $statusHtml;
                })
                ->addColumn('action', function ($payroll) {
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('hr.payrolls.show', $payroll->hash_id) . '" class="btn btn-sm btn-info" title="View">
                                    <i class="bx bx-show"></i>
                                 </a>';

                    if ($payroll->status === 'draft') {
                        $actions .= '<a href="' . route('hr.payrolls.edit', $payroll->hash_id) . '" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bx bx-edit"></i>
                                     </a>';
                        $actions .= '<button type="button" class="btn btn-sm btn-success" onclick="processPayroll(\'' . $payroll->hash_id . '\')" title="Process">
                                        <i class="bx bx-play"></i>
                                     </button>';
                        $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="deletePayroll(\'' . $payroll->hash_id . '\')" title="Delete">
                                        <i class="bx bx-trash"></i>
                                     </button>';
                    } elseif ($payroll->status === 'processing') {
                        $actions .= '<button type="button" class="btn btn-sm btn-primary" onclick="approvePayroll(\'' . $payroll->hash_id . '\')" title="Approve">
                                        <i class="bx bx-check"></i>
                                     </button>';
                        $actions .= '<button type="button" class="btn btn-sm btn-secondary" onclick="rejectPayroll(\'' . $payroll->hash_id . '\')" title="Reject">
                                        <i class="bx bx-x"></i>
                                     </button>';
                        $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="deletePayroll(\'' . $payroll->hash_id . '\')" title="Delete">
                                        <i class="bx bx-trash"></i>
                                     </button>';
                    } elseif ($payroll->status === 'completed') {
                        if ($payroll->payment_status === 'pending') {
                            $actions .= '<a href="' . route('hr.payrolls.payment', $payroll->hash_id) . '" class="btn btn-sm btn-success" title="Process Payment">
                                            <i class="bx bx-credit-card"></i>
                                         </a>';
                        } else {
                            $actions .= '<span class="btn btn-sm btn-success disabled" title="Payment Completed">
                                            <i class="bx bx-check-double"></i>
                                         </span>';
                        }
                    } elseif ($payroll->status === 'paid') {
                        $actions .= '<span class="btn btn-sm btn-success disabled" title="Paid">
                                        <i class="bx bx-check-double"></i>
                                     </span>';
                    } elseif ($payroll->status === 'cancelled') {
                        $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="deletePayroll(\'' . $payroll->hash_id . '\')" title="Delete">
                                        <i class="bx bx-trash"></i>
                                     </button>';
                    }

                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        // Dashboard statistics
        $companyId = current_company_id();
        $today = Carbon::today();
        $thisYear = $today->year;
        $thisMonth = $today->month;

        $stats = [
            'total' => Payroll::where('company_id', $companyId)->count(),
            'draft' => Payroll::where('company_id', $companyId)->where('status', 'draft')->count(),
            'processing' => Payroll::where('company_id', $companyId)->where('status', 'processing')->count(),
            'completed' => Payroll::where('company_id', $companyId)->where('status', 'completed')->count(),
            'paid' => Payroll::where('company_id', $companyId)->where('status', 'paid')->count(),
            'rejected' => Payroll::where('company_id', $companyId)->where('status', 'cancelled')->count(),
            'this_month' => Payroll::where('company_id', $companyId)
                ->where('year', $thisYear)
                ->where('month', $thisMonth)
                ->count(),
            'total_gross' => Payroll::where('company_id', $companyId)
                ->where('status', '!=', 'cancelled')
                ->selectRaw('SUM(total_salary + total_allowance) as total')
                ->value('total') ?? 0,
            'total_net' => Payroll::where('company_id', $companyId)
                ->where('status', '!=', 'cancelled')
                ->selectRaw('SUM((total_salary + total_allowance) - (total_nhif_employee + total_pension_employee + total_payee + total_salary_advance_paid + total_external_loan_paid + total_trade_union)) as total')
                ->value('total') ?? 0,
        ];

        return view('hr-payroll.payrolls.index', compact('stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companyId = current_company_id();
        
        // Get available payroll calendars (unlocked ones)
        $payrollCalendars = PayrollCalendar::where('company_id', $companyId)
            ->where('is_locked', false)
            ->orderBy('calendar_year', 'desc')
            ->orderBy('payroll_month', 'desc')
            ->get()
            ->map(function ($calendar) {
                return [
                    'id' => $calendar->id,
                    'label' => $calendar->period_label . ' (Cut-off: ' . $calendar->cut_off_date->format('M d, Y') . ', Pay: ' . $calendar->pay_date->format('M d, Y') . ')',
                    'year' => $calendar->calendar_year,
                    'month' => $calendar->payroll_month,
                    'cut_off_date' => $calendar->cut_off_date,
                    'pay_date' => $calendar->pay_date,
                ];
            });

        // Get active pay groups
        $payGroups = PayGroup::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('pay_group_code')
            ->get();

        // Keep backward compatibility - show year/month if no calendars exist
        $currentYear = date('Y');
        $currentMonth = date('n');
        $years = range($currentYear, $currentYear - 5);
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return view('hr-payroll.payrolls.create', compact('payrollCalendars', 'payGroups', 'years', 'months', 'currentYear', 'currentMonth'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'payroll_calendar_id' => 'required_without_all:year,month|exists:hr_payroll_calendars,id',
            'year' => 'required_without:payroll_calendar_id|integer|min:2020|max:' . (date('Y') + 1),
            'month' => 'required_without:payroll_calendar_id|integer|min:1|max:12',
            'pay_group_id' => 'nullable|exists:hr_pay_groups,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Ensure company_id is available
        $companyId = current_company_id();
        if (!$companyId) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Company context not found. Please refresh the page and try again.');
        }

        $payrollCalendar = null;
        $payGroup = null;
        $year = null;
        $month = null;

        // If payroll_calendar_id is provided, use it
        if ($request->payroll_calendar_id) {
            $payrollCalendar = PayrollCalendar::where('company_id', $companyId)
                ->where('id', $request->payroll_calendar_id)
                ->first();

            if (!$payrollCalendar) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Selected payroll calendar not found.');
            }

            if ($payrollCalendar->is_locked) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Cannot create payroll for a locked calendar period.');
            }

            $year = $payrollCalendar->calendar_year;
            $month = $payrollCalendar->payroll_month;
        } else {
            // Fallback to year/month (backward compatibility)
            $year = $request->year;
            $month = $request->month;
        }

        // If pay_group_id is provided, validate and get pay group
        if ($request->pay_group_id) {
            $payGroup = PayGroup::where('company_id', $companyId)
                ->where('id', $request->pay_group_id)
                ->where('is_active', true)
                ->first();

            if (!$payGroup) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Selected pay group not found or inactive.');
            }

            // If no payroll calendar selected, use pay group dates
            if (!$payrollCalendar && $payGroup) {
                $payrollDate = Carbon::create($year, $month, 1);
                $cutOffDate = $payGroup->calculateCutOffDate($year, $month);
                $payDate = $payGroup->calculatePayDate($year, $month);
            }
        }

        // Check if payroll already exists for this month/year/pay_group
        $existingPayrollQuery = Payroll::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month);
        
        if ($request->pay_group_id) {
            $existingPayrollQuery->where('pay_group_id', $request->pay_group_id);
        } else {
            $existingPayrollQuery->whereNull('pay_group_id');
        }
        
        $existingPayroll = $existingPayrollQuery->first();

        if ($existingPayroll) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'A payroll for ' . date('F', mktime(0, 0, 0, $month, 1)) . ' ' . $year . ' already exists.');
        }

        try {
            DB::beginTransaction();

            // Generate a temporary reference
            $tempReference = "PAY-{$year}-{$month}-TEMP-" . time();

            $payroll = Payroll::create([
                'reference' => $tempReference,
                'year' => $year,
                'month' => $month,
                'payroll_calendar_id' => $payrollCalendar ? $payrollCalendar->id : null,
                'pay_group_id' => $request->pay_group_id,
                'status' => 'draft',
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'company_id' => $companyId,
            ]);

            // Update with proper reference using the ID
            $finalReference = "PAY-{$year}-{$month}-" . str_pad($payroll->id, 4, '0', STR_PAD_LEFT);
            $payroll->update(['reference' => $finalReference]);

            DB::commit();

            $successMessage = 'Payroll created successfully for ' . $payroll->month_name . ' ' . $payroll->year . '.';
            if ($payrollCalendar) {
                $successMessage .= ' Cut-off: ' . $payrollCalendar->cut_off_date->format('M d, Y') . ', Pay Date: ' . $payrollCalendar->pay_date->format('M d, Y');
            }

            return redirect()->route('hr.payrolls.index')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create payroll: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Payroll $payroll)
    {
        $payroll->load([
            'creator', 
            'company', 
            'payrollEmployees.employee', 
            'approvedBy', 
            'rejectedBy', 
            'paidBy',
            'approvals.approver',
            'paymentApprovals.approver',
            'paymentSubmittedBy',
            'payrollCalendar',
            'payGroup'
        ]);
        
        // Check if current user can approve payment
        $canApprovePayment = false;
        if ($payroll->requires_payment_approval && !$payroll->is_payment_fully_approved) {
            $canApprovePayment = $this->canUserApprovePayment($payroll, Auth::id());
        }
        
        return view('hr-payroll.payrolls.show', compact('payroll', 'canApprovePayment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payroll $payroll)
    {
        if ($payroll->status !== 'draft') {
            return redirect()->route('hr.payrolls.index')
                ->with('error', 'Only draft payrolls can be edited.');
        }

        $currentYear = date('Y');
        $currentMonth = date('n');

        $years = range($currentYear, $currentYear - 5);

        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return view('hr-payroll.payrolls.edit', compact('payroll', 'years', 'months', 'currentYear', 'currentMonth'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payroll $payroll)
    {
        if ($payroll->status !== 'draft') {
            return redirect()->route('hr.payrolls.index')
                ->with('error', 'Only draft payrolls can be edited.');
        }

        $request->validate([
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'month' => 'required|integer|min:1|max:12',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check if another payroll exists for this month/year (excluding current payroll)
        $existingPayroll = Payroll::where('company_id', current_company_id())
            ->where('year', $request->year)
            ->where('month', $request->month)
            ->where('id', '!=', $payroll->id)
            ->first();

        if ($existingPayroll) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'A payroll for ' . date('F', mktime(0, 0, 0, $request->month, 1)) . ' ' . $request->year . ' already exists.');
        }

        try {
            $payroll->update([
                'year' => $request->year,
                'month' => $request->month,
                'notes' => $request->notes,
            ]);

            return redirect()->route('hr.payrolls.index')
                ->with('success', 'Payroll updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update payroll: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payroll $payroll)
    {
        if ($payroll->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Completed payrolls cannot be deleted.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Delete all related payroll employees first (due to foreign key constraints)
            PayrollEmployee::where('payroll_id', $payroll->id)->delete();

            // Delete the payroll
            $payroll->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payroll and all related data deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process payroll for the specified payroll
     */
    public function process(Payroll $payroll)
    {
        if ($payroll->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft payrolls can be processed.'
            ], 400);
        }

        try {
            // Set longer execution time for large payrolls
            set_time_limit(config('payroll.max_execution_time', 300));
            ini_set('memory_limit', config('payroll.memory_limit', '512M'));

            DB::beginTransaction();

            // Get employee count first
            $employeeCount = Employee::where('company_id', current_company_id())
                ->where('include_in_payroll', true)
                ->where('status', 'active')
                ->count();

            if ($employeeCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active employees found for payroll processing.'
                ], 400);
            }

            // Clear existing payroll employees for this payroll
            PayrollEmployee::where('payroll_id', $payroll->id)->delete();

            // Initialize totals
            $totals = [
                'totalBasicSalary' => 0,
                'totalAllowance' => 0,
                'totalOtherAllowances' => 0,
                'totalPaye' => 0,
                'totalPension' => 0,
                'totalInsurance' => 0,
                'totalSalaryAdvance' => 0,
                'totalLoans' => 0,
                'totalTradeUnion' => 0,
                'totalSdl' => 0,
                'totalWcf' => 0,
                'totalHeslb' => 0,
                'totalOtherDeductions' => 0,
                'totalNhifEmployer' => 0,
                'totalPensionEmployer' => 0,
            ];

            // Track compliance warnings
            $complianceWarnings = [];

            // Process employees in batches to prevent memory issues
            $batchSize = config('payroll.batch_size', 100);
            $processedCount = 0;

            // Build employee query
            $employeeQuery = Employee::where('company_id', current_company_id())
                ->where('include_in_payroll', true)
                ->where('status', 'active');

            // Filter by pay group if specified
            if ($payroll->pay_group_id) {
                $payrollDate = Carbon::create($payroll->year, $payroll->month, 1);
                $startOfMonth = $payrollDate->copy()->startOfMonth();
                $endOfMonth = $payrollDate->copy()->endOfMonth();

                $employeeQuery->whereHas('payGroupAssignments', function ($q) use ($payroll, $startOfMonth, $endOfMonth) {
                    $q->where('pay_group_id', $payroll->pay_group_id)
                      ->where('effective_date', '<=', $endOfMonth)
                      ->where(function($q2) use ($startOfMonth) {
                          $q2->whereNull('end_date')
                             ->orWhere('end_date', '>=', $startOfMonth);
                      });
                });
            }

            $employeeQuery->with(['allowances', 'externalLoans', 'salaryAdvances', 'complianceRecords'])
                ->chunk($batchSize, function ($employees) use ($payroll, &$totals, &$processedCount, &$complianceWarnings) {
                    $payrollEmployeeData = [];

                    foreach ($employees as $employee) {
                        $calculation = $this->calculateEmployeePayroll($employee, $payroll);

                        // Collect compliance warnings
                        if (!empty($calculation['compliance_warnings'])) {
                            foreach ($calculation['compliance_warnings'] as $warning) {
                                $complianceWarnings[] = [
                                    'employee_id' => $employee->id,
                                    'employee_name' => $employee->full_name,
                                    'type' => $warning['type'],
                                    'message' => $warning['message'],
                                    'severity' => $warning['severity'],
                                ];
                            }
                        }

                        // Prepare data for batch insert
                        $payrollEmployeeData[] = [
                            'payroll_id' => $payroll->id,
                            'employee_id' => $employee->id,
                            'basic_salary' => $calculation['basic_salary'],
                            'allowance' => $calculation['allowance'],
                            'other_allowances' => $calculation['other_allowances'],
                            'overtime' => $calculation['overtime'] ?? 0,
                            'overtime_hours' => $calculation['overtime_hours'] ?? 0,
                            'paye' => $calculation['paye'],
                            'pension' => $calculation['pension'],
                            'insurance' => $calculation['insurance'],
                            'salary_advance' => $calculation['salary_advance'],
                            'loans' => $calculation['loans'],
                            'trade_union' => $calculation['trade_union'],
                            'sdl' => $calculation['sdl'],
                            'wcf' => $calculation['wcf'],
                            'heslb' => $calculation['heslb'],
                            'other_deductions' => $calculation['other_deductions'],
                            'gross_salary' => $calculation['gross_salary'],
                            'total_deductions' => $calculation['total_deductions'],
                            'net_salary' => $calculation['net_salary'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        // Add to totals
                        $totals['totalBasicSalary'] += $calculation['basic_salary'];
                        $totals['totalAllowance'] += $calculation['allowance'];
                        $totals['totalOtherAllowances'] += $calculation['other_allowances'];
                        $totals['totalPaye'] += $calculation['paye'];
                        $totals['totalPension'] += $calculation['pension'];
                        $totals['totalInsurance'] += $calculation['insurance'];
                        $totals['totalSalaryAdvance'] += $calculation['salary_advance'];
                        $totals['totalLoans'] += $calculation['loans'];
                        $totals['totalTradeUnion'] += $calculation['trade_union'];
                        $totals['totalSdl'] += $calculation['sdl'];
                        $totals['totalWcf'] += $calculation['wcf'];
                        $totals['totalHeslb'] += $calculation['heslb'];
                        $totals['totalOtherDeductions'] += $calculation['other_deductions'];
                        
                        // Add employer contributions
                        if (isset($calculation['employer_contributions'])) {
                            $empContrib = $calculation['employer_contributions'];
                            $totals['totalNhifEmployer'] += $empContrib['nhif_employer'] ?? 0;
                            $totals['totalPensionEmployer'] += $empContrib['pension_employer'] ?? 0;
                        }

                        $processedCount++;
                    }

                    // Batch insert payroll employees
                    if (!empty($payrollEmployeeData)) {
                        PayrollEmployee::insert($payrollEmployeeData);
                    }
                });

            // Record HESLB repayments for employees with HESLB deductions
            $this->recordHeslbRepayments($payroll);

            // Record salary advance and loan repayments
            $this->recordAdvanceAndLoanRepayments($payroll);

            // Update payroll totals
            $payroll->update([
                'total_salary' => $totals['totalBasicSalary'],
                'total_allowance' => $totals['totalAllowance'] + $totals['totalOtherAllowances'],
                'total_nhif_employee' => $totals['totalInsurance'],
                'total_nhif_employer' => $totals['totalNhifEmployer'],
                'total_pension_employee' => $totals['totalPension'],
                'total_pension_employer' => $totals['totalPensionEmployer'],
                'total_wcf' => $totals['totalWcf'],
                'total_sdl' => $totals['totalSdl'],
                'total_heslb' => $totals['totalHeslb'],
                'total_trade_union' => $totals['totalTradeUnion'],
                'total_payee' => $totals['totalPaye'],
                'total_salary_advance_paid' => $totals['totalSalaryAdvance'],
                'total_external_loan_paid' => $totals['totalLoans'],
                'status' => 'processing',
            ]);

            // Initialize approval workflow
            $this->initializeApprovalWorkflow($payroll);

            // Log audit trail
            $payroll->logAudit('processed', 'status', 'draft', 'processing', 
                "Payroll processed successfully. {$processedCount} employees processed.", 
                null, Auth::id(), ['processed_count' => $processedCount, 'compliance_warnings' => $complianceWarnings]);

            // Check if no employees were found (especially for pay group filtering)
            if ($processedCount === 0) {
                DB::rollBack();
                $errorMessage = 'No employees found for payroll processing.';
                if ($payroll->pay_group_id) {
                    $payGroup = $payroll->payGroup;
                    $errorMessage = "No employees found in pay group '{$payGroup->pay_group_name}' for {$payroll->month_name} {$payroll->year}.";
                }
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 400);
            }

            DB::commit();

            // Prepare response message
            $message = "Payroll processed successfully for {$payroll->month_name} {$payroll->year}. Processed {$processedCount} employees.";
            if ($payroll->pay_group_id && $payroll->payGroup) {
                $message .= " Pay Group: {$payroll->payGroup->pay_group_name}.";
            }
            if (!empty($complianceWarnings)) {
                $warningCount = count($complianceWarnings);
                $message .= " {$warningCount} compliance warning(s) found.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'processed_count' => $processedCount,
                'compliance_warnings' => $complianceWarnings,
                'has_warnings' => !empty($complianceWarnings),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate payroll for a single employee
     * Uses PayrollCalculationService which follows priority order:
     * 1. Salary Structure → 2. Contract → 3. Employee basic_salary
     */
    private function calculateEmployeePayroll(Employee $employee, Payroll $payroll)
    {
        // Always use PayrollCalculationService (handles all cases: structure, contract, or basic salary)
        try {
            $result = $this->payrollCalculationService->calculateEmployeePayroll(
                $employee,
                $payroll->year,
                $payroll->month,
                $payroll->company_id
            );

            // Map to expected format
            return [
                'basic_salary' => $result['basic_salary'],
                'allowance' => $result['allowance'],
                'other_allowances' => $result['other_allowances'],
                'overtime' => $result['overtime'] ?? 0,
                'overtime_hours' => $result['overtime_hours'] ?? 0,
                'paye' => $result['paye'],
                'pension' => $result['pension'],
                'insurance' => $result['insurance'],
                'salary_advance' => $result['salary_advance'],
                'loans' => $result['loans'],
                'trade_union' => $result['trade_union'],
                'sdl' => $result['sdl'],
                'wcf' => $result['wcf'],
                'heslb' => $result['heslb'],
                'other_deductions' => $result['other_deductions'],
                'gross_salary' => $result['gross_salary'],
                'total_deductions' => $result['total_deductions'],
                'net_salary' => $result['net_salary'],
                'employer_contributions' => $result['employer_contributions'] ?? [],
                'compliance_warnings' => $result['compliance_warnings'] ?? [],
            ];
        } catch (\Exception $e) {
            // Fallback to legacy calculation if service fails
            \Log::warning('PayrollCalculationService failed, using legacy calculation: ' . $e->getMessage());
            
            // Legacy calculation with priority order: Contract → Employee basic_salary
            $basicSalary = $this->getBaseSalaryLegacy($employee, Carbon::create($payroll->year, $payroll->month, 1));

            // Get allowances for the payroll month/year (optimized query)
            $allowances = $employee->allowances
                ->where('date.year', $payroll->year)
                ->where('date.month', $payroll->month)
                ->where('is_active', true)
                ->sum('amount');

            // Get salary advances for the payroll period
            // Best Practice: Deduct all active advances where date <= payroll date
            // This allows ongoing monthly deductions until fully repaid
            $payrollDate = Carbon::create($payroll->year, $payroll->month, 1)->endOfMonth();
            $salaryAdvances = $employee->salaryAdvances
                ->where('is_active', true)
                ->filter(function ($advance) use ($payrollDate) {
                    return $advance->date <= $payrollDate;
                })
                ->sum('monthly_deduction');

            // Get external loans for the payroll period
            // Best Practice: Deduct all active loans where:
            // - date <= payroll date (loan was issued before/on payroll date)
            // - date_end_of_loan is null OR >= payroll date (loan hasn't ended yet)
            // - Handle both fixed amount and percentage-based deductions
            // Note: Percentage-based loans use basic salary (consistent with HESLB loans)
            $externalLoans = 0;
            $eligibleLoans = $employee->externalLoans
                ->where('is_active', true)
                ->filter(function ($loan) use ($payrollDate) {
                    return $loan->date <= $payrollDate
                        && ($loan->date_end_of_loan === null || $loan->date_end_of_loan >= $payrollDate);
                });
            
            foreach ($eligibleLoans as $loan) {
                $deductionType = $loan->deduction_type ?? 'fixed';
                
                if ($deductionType === 'percentage') {
                    // Calculate percentage of basic salary (consistent with HESLB loans)
                    if ($basicSalary > 0) {
                        $percentage = (float) $loan->monthly_deduction;
                        $externalLoans += $basicSalary * ($percentage / 100);
                    }
                } else {
                    // Fixed amount
                    $externalLoans += (float) $loan->monthly_deduction;
                }
            }

            // Get attendance data for the payroll period
            $startDate = Carbon::create($payroll->year, $payroll->month, 1)->startOfMonth();
            $endDate = Carbon::create($payroll->year, $payroll->month, 1)->endOfMonth();
            
            // Get approved attendance records
            $attendanceSummary = $this->attendanceService->getAttendanceSummary($employee, $startDate, $endDate);
            
            // Calculate overtime earnings using priority order for base salary
            // Priority: Contract → Employee basic_salary
            // Note: In legacy fallback, we use Contract → Employee basic_salary (salary structure handled by service)
            $overtimeHours = $this->attendanceService->getApprovedOvertimeHours($employee, $startDate, $endDate);
            // Use the same base salary priority for overtime hourly rate calculation
            $hourlyRate = $basicSalary / 22 / 8; // Assuming 22 working days, 8 hours per day
            $overtimeRate = $this->attendanceService->getOvertimeRate($employee, $startDate, $companyId = current_company_id());
            $overtimeEarnings = $overtimeHours * $hourlyRate * $overtimeRate;
            
            // Get unpaid leave days for the payroll period
            $unpaidLeaveDays = LeaveSegment::whereHas('leaveRequest', function ($q) use ($employee, $payroll) {
                    $q->where('employee_id', $employee->id)
                      ->where('company_id', current_company_id())
                      ->where('status', 'approved')
                      ->whereYear('requested_at', $payroll->year)
                      ->whereMonth('requested_at', $payroll->month);
                })
                ->whereHas('leaveRequest.leaveType', function ($q) {
                    $q->where('is_paid', false); // Unpaid leave types
                })
                ->sum('days_equivalent');
            
            // Calculate deductions for unpaid leave/absence
            $absentDays = $attendanceSummary['absent_days'] ?? 0;
            $dailySalary = $basicSalary / 22; // Assuming 22 working days per month
            $absenceDeduction = $absentDays * $dailySalary;
            $unpaidLeaveDeduction = $unpaidLeaveDays * $dailySalary;

            // Calculate gross salary (including overtime, minus absence and unpaid leave)
            $grossSalary = $basicSalary + $allowances + $overtimeEarnings - $absenceDeduction - $unpaidLeaveDeduction;

            // Calculate pension (on gross salary)
            $pension = 0;
            if ($employee->has_pension && $employee->pension_employee_percent) {
                $pension = $grossSalary * ($employee->pension_employee_percent / 100);
            }

            // Calculate NHIF (on basic salary)
            $insurance = 0;
            if ($employee->has_nhif && $employee->nhif_employee_percent) {
                $insurance = $basicSalary * ($employee->nhif_employee_percent / 100);
            }

            // Calculate HESLB (on basic salary)
            $heslbAmount = 0;
            if ($employee->has_heslb) {
                // Assuming 5% of basic salary for HESLB
                $heslbAmount = $basicSalary * 0.05;
            }

            // Calculate WCF (on gross salary)
            $wcf = 0;
            if ($employee->has_wcf) {
                // Assuming 1% of gross salary for WCF
                $wcf = $grossSalary * 0.01;
            }

            // Calculate SDL (on gross salary)
            $sdl = 0;
            if ($employee->has_sdl) {
                // Assuming 0.5% of gross salary for SDL
                $sdl = $grossSalary * 0.005;
            }

            // Calculate Trade Union
            $tradeUnionDeduction = 0;
            if ($employee->has_trade_union) {
                if ($employee->trade_union_amount) {
                    $tradeUnionDeduction = $employee->trade_union_amount;
                } elseif ($employee->trade_union_percent) {
                    $tradeUnionDeduction = $grossSalary * ($employee->trade_union_percent / 100);
                }
            }

            // Calculate PAYE (Progressive Brackets)
            $taxableIncome = $grossSalary - $pension;
            $paye = 0;
            if ($taxableIncome > 1000000) {
                $paye = ($taxableIncome - 1000000) * 0.30 + 128000;
            } elseif ($taxableIncome > 760000) {
                $paye = ($taxableIncome - 760000) * 0.25 + 68000;
            } elseif ($taxableIncome > 520000) {
                $paye = ($taxableIncome - 520000) * 0.20 + 20000;
            } elseif ($taxableIncome > 270000) {
                $paye = ($taxableIncome - 270000) * 0.08;
            }

            // Calculate total deductions
            $totalDeductions = $paye + $pension + $insurance + $salaryAdvances + $externalLoans +
                              $tradeUnionDeduction + $sdl + $wcf + $heslbAmount;

            // Calculate net salary
            $netSalary = $grossSalary - $totalDeductions;

            return [
                'basic_salary' => $basicSalary,
                'allowance' => $allowances,
                'other_allowances' => 0, // For future use
                'paye' => $paye,
                'pension' => $pension,
                'insurance' => $insurance,
                'salary_advance' => $salaryAdvances,
                'loans' => $externalLoans,
                'trade_union' => $tradeUnionDeduction,
                'sdl' => $sdl,
                'wcf' => $wcf,
                'heslb' => $heslbAmount,
                'other_deductions' => 0, // For future use
                'gross_salary' => $grossSalary,
                'total_deductions' => $totalDeductions,
                'net_salary' => $netSalary,
                'overtime' => $overtimeEarnings,
                'overtime_hours' => $overtimeHours,
                'compliance_warnings' => [],
            ];
        }
    }

    /**
     * Get base salary for legacy calculation following priority order:
     * 1. Active Contract salary (if exists)
     * 2. Employee basic_salary (fallback)
     * 
     * Note: Salary Structure is handled by PayrollCalculationService
     */
    private function getBaseSalaryLegacy(Employee $employee, Carbon $date)
    {
        // Priority 1: Check active contract salary
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

        // Priority 2: Fallback to employee basic_salary
        return $employee->basic_salary ?? 0;
    }

    /**
     * Get employees for a specific payroll (for DataTables)
     */
    public function getEmployees(Request $request, Payroll $payroll)
    {
        if ($request->ajax()) {
            $payrollEmployees = PayrollEmployee::with(['employee.department', 'employee.position'])
                ->where('payroll_id', $payroll->id)
                ->orderBy('employee_id');

            return DataTables::of($payrollEmployees)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($payrollEmployee) {
                    return $payrollEmployee->employee->full_name;
                })
                ->addColumn('employee_id', function ($payrollEmployee) {
                    return $payrollEmployee->employee->employee_id ?? 'N/A';
                })
                ->addColumn('department', function ($payrollEmployee) {
                    return $payrollEmployee->employee->department->name ?? 'N/A';
                })
                ->addColumn('position', function ($payrollEmployee) {
                    return $payrollEmployee->employee->position->title ?? 'N/A';
                })
                ->addColumn('basic_salary', function ($payrollEmployee) {
                    return number_format($payrollEmployee->basic_salary, 2);
                })
                ->addColumn('allowance', function ($payrollEmployee) {
                    return number_format($payrollEmployee->allowance, 2);
                })
                ->addColumn('overtime', function ($payrollEmployee) {
                    return number_format($payrollEmployee->overtime ?? 0, 2);
                })
                ->addColumn('gross_salary', function ($payrollEmployee) {
                    return number_format($payrollEmployee->gross_salary, 2);
                })
                ->addColumn('total_deductions', function ($payrollEmployee) {
                    return number_format($payrollEmployee->total_deductions, 2);
                })
                ->addColumn('net_salary', function ($payrollEmployee) {
                    return number_format($payrollEmployee->net_salary, 2);
                })
                ->addColumn('action', function ($payrollEmployee) {
                    $payroll = Payroll::find($payrollEmployee->payroll_id);
                    return '<div class="btn-group" role="group">
                        <a href="' . route('hr.payrolls.slip', ['payroll' => $payroll->hash_id ?? $payrollEmployee->payroll_id, 'employee' => $payrollEmployee->hash_id]) . '"
                           class="btn btn-sm btn-primary" title="View Slip">
                            <i class="bx bx-show"></i>
                        </a>
                        <a href="' . route('hr.payrolls.slip.pdf', ['payroll' => $payroll->hash_id ?? $payrollEmployee->payroll_id, 'employee' => $payrollEmployee->hash_id]) . '"
                           class="btn btn-sm btn-success" title="Download PDF" target="_blank">
                            <i class="bx bx-download"></i>
                        </a>
                    </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return response()->json(['error' => 'Invalid request'], 400);
    }

    /**
     * Display payroll slip for an employee
     */
    public function slip(Payroll $payroll, PayrollEmployee $employee)
    {
        // Verify the employee belongs to this payroll
        if ($employee->payroll_id !== $payroll->id) {
            abort(404, 'Employee not found in this payroll.');
        }

        // Load the employee with relationships
        $employee->load(['employee.department', 'employee.position']);

        // Get company details
        $company = Company::find($payroll->company_id);

        return view('hr-payroll.payrolls.slip', compact('payroll', 'employee', 'company'));
    }

    /**
     * Display print view for payroll slip
     */
    public function slipPrint(Payroll $payroll, PayrollEmployee $employee)
    {
        // Verify the employee belongs to this payroll
        if ($employee->payroll_id !== $payroll->id) {
            abort(404, 'Employee not found in this payroll.');
        }

        // Load the employee with relationships
        $employee->load(['employee.department', 'employee.position']);

        // Get company details
        $company = Company::find($payroll->company_id);

        return view('hr-payroll.payrolls.print', compact('payroll', 'employee', 'company'));
    }

    /**
     * Generate PDF for payroll slip
     */
    public function slipPdf(Payroll $payroll, PayrollEmployee $employee)
    {
        // Verify the employee belongs to this payroll
        if ($employee->payroll_id !== $payroll->id) {
            abort(404, 'Employee not found in this payroll.');
        }

        // Load the employee with relationships
        $employee->load(['employee.department', 'employee.position']);

        // Get company details
        $company = Company::find($payroll->company_id);

        // Generate PDF using PDF view
        $pdf = Pdf::loadView('hr-payroll.payrolls.pdf', compact('payroll', 'employee', 'company'));
        
        // Use A4 paper size
        $pageSize = 'A4';
        $orientation = 'portrait';
        
        // Get margins from settings and convert cm to mm for dompdf
        $marginTopStr = \App\Models\SystemSetting::getValue('document_margin_top', '2cm');
        $marginRightStr = \App\Models\SystemSetting::getValue('document_margin_right', '1.5cm');
        $marginBottomStr = \App\Models\SystemSetting::getValue('document_margin_bottom', '2cm');
        $marginLeftStr = \App\Models\SystemSetting::getValue('document_margin_left', '1.5cm');
        
        // Convert cm to mm (dompdf expects mm)
        $convertToMm = function($value) {
            if (is_numeric($value)) {
                return (float) $value; // Assume already in mm
            }
            // Remove 'cm' and convert to mm
            $numeric = (float) str_replace(['cm', 'mm', 'pt', 'px', 'in'], '', $value);
            if (strpos($value, 'cm') !== false) {
                return $numeric * 10; // Convert cm to mm
            }
            return $numeric; // Already in mm or other unit
        };
        
        $marginTop = $convertToMm($marginTopStr);
        $marginRight = $convertToMm($marginRightStr);
        $marginBottom = $convertToMm($marginBottomStr);
        $marginLeft = $convertToMm($marginLeftStr);
        
        $pdf->setPaper($pageSize, $orientation);
        
        // Set margins programmatically using setOptions (dompdf expects numeric values in mm)
        $pdf->setOptions([
            'margin-top' => $marginTop,
            'margin-right' => $marginRight,
            'margin-bottom' => $marginBottom,
            'margin-left' => $marginLeft,
        ]);

        // Clean employee name for filename
        $employeeName = $employee->employee->full_name ?? $employee->employee_name ?? 'employee';
        // Remove parentheses and their contents, then clean special characters
        $employeeName = preg_replace('/\s*\([^)]*\)\s*/', '', $employeeName); // Remove (Super Admin) type text
        $employeeName = preg_replace('/[^A-Za-z0-9\s]/', '', $employeeName); // Remove special characters
        $employeeName = preg_replace('/\s+/', '_', trim($employeeName)); // Replace spaces with single underscore
        $employeeName = preg_replace('/_+/', '_', $employeeName); // Remove multiple underscores
        $employeeName = trim($employeeName, '_'); // Remove leading/trailing underscores
        
        $filename = 'payroll_slip_' . $payroll->year . '_' . str_pad($payroll->month, 2, '0', STR_PAD_LEFT) . '_' . $employeeName . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export all payroll slips as PDF
     */
    public function exportAllSlips(Payroll $payroll)
    {
        if ($payroll->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Only completed payrolls can be exported.'
            ], 400);
        }

        $payrollEmployees = PayrollEmployee::with(['employee.department', 'employee.position'])
            ->where('payroll_id', $payroll->id)
            ->get();

        if ($payrollEmployees->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No employees found in this payroll.'
            ], 400);
        }

        $company = Company::find($payroll->company_id);

        // Generate PDF for all employees
        $pdf = Pdf::loadView('hr-payroll.payrolls.all-slips', compact('payroll', 'payrollEmployees', 'company'));
        $pdf->setPaper('A4', 'portrait');

        $filename = 'all_payroll_slips_' . $payroll->year . '_' . str_pad($payroll->month, 2, '0', STR_PAD_LEFT) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Approve payroll
     */
    public function approve(Request $request, Payroll $payroll)
    {
        if ($payroll->status !== 'processing') {
            return response()->json([
                'success' => false,
                'message' => 'Only processing payrolls can be approved.'
            ], 400);
        }

        // Check if current user can approve at current level
        if (!$this->canUserApprove($payroll, Auth::id())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to approve this payroll at the current level.'
            ], 403);
        }

        $request->validate([
            'remarks' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            // Process the approval
            $fullyApproved = $this->processApproval($payroll, Auth::id(), $request->remarks);

            // Create audit trail (if activity package is available)
            if (function_exists('activity')) {
                $action = $fullyApproved ? 'fully_approved' : 'level_approved';
                $message = $fullyApproved ? 'Payroll fully approved' : "Payroll approved at level {$payroll->current_approval_level}";
                
                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($payroll)
                    ->withProperties([
                        'action' => $action,
                        'remarks' => $request->remarks,
                        'approval_level' => $payroll->current_approval_level,
                        'fully_approved' => $fullyApproved
                    ])
                    ->log($message);
            }

            // Log custom audit trail
            $payroll->logAudit('approved', 'status', 'processing', $payroll->fresh()->status, 
                $fullyApproved ? 'Payroll fully approved' : "Payroll approved at level {$payroll->current_approval_level}", 
                $request->remarks, Auth::id(), [
                    'approval_level' => $payroll->current_approval_level,
                    'fully_approved' => $fullyApproved
                ]);

            DB::commit();

            $message = $fullyApproved ? 
                'Payroll approved successfully and is ready for payment.' :
                "Payroll approved at level {$payroll->current_approval_level}. Waiting for next level approval.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $payroll->fresh()->status,
                'fully_approved' => $fullyApproved
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject payroll
     */
    public function reject(Request $request, Payroll $payroll)
    {
        if ($payroll->status !== 'processing') {
            return response()->json([
                'success' => false,
                'message' => 'Only processing payrolls can be rejected.'
            ], 400);
        }

        // Check if current user can approve/reject at current level
        if (!$this->canUserApprove($payroll, Auth::id())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to reject this payroll at the current level.'
            ], 403);
        }

        $request->validate([
            'remarks' => 'required|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            // Reject all pending approvals (rejection at any level cancels the whole process)
            PayrollApproval::where('payroll_id', $payroll->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'approved_at' => now(),
                    'remarks' => $request->remarks
                ]);

            // Update payroll status
            $payroll->update([
                'status' => 'cancelled',
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
                'rejection_remarks' => $request->remarks
            ]);

            // Create audit trail (if activity package is available)
            if (function_exists('activity')) {
                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($payroll)
                    ->withProperties([
                        'action' => 'rejected',
                        'remarks' => $request->remarks,
                        'rejection_level' => $payroll->current_approval_level,
                        'previous_status' => 'processing',
                        'new_status' => 'cancelled'
                    ])
                    ->log("Payroll rejected at level {$payroll->current_approval_level}");
            }

            // Log custom audit trail
            $payroll->logAudit('rejected', 'status', 'processing', 'cancelled', 
                "Payroll rejected at level {$payroll->current_approval_level}", 
                $request->remarks, Auth::id(), [
                    'approval_level' => $payroll->current_approval_level
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payroll rejected successfully.',
                'status' => 'cancelled'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lock payroll
     */
    public function lock(Request $request, Payroll $payroll)
    {
        if ($payroll->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll is already locked.'
            ], 400);
        }

        if (!$payroll->canBeLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll cannot be locked. Only completed or approved payrolls can be locked.'
            ], 400);
        }

        $request->validate([
            'lock_reason' => 'nullable|string|max:500'
        ]);

        try {
            $payroll->lock(Auth::id(), $request->lock_reason);

            // Audit logging is handled in the lock() method

            return response()->json([
                'success' => true,
                'message' => 'Payroll locked successfully.',
                'is_locked' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to lock payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unlock payroll
     */
    public function unlock(Request $request, Payroll $payroll)
    {
        if (!$payroll->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll is not locked.'
            ], 400);
        }

        $request->validate([
            'unlock_reason' => 'nullable|string|max:500'
        ]);

        try {
            $payroll->unlock(Auth::id(), $request->unlock_reason);

            // Audit logging is handled in the unlock() method

            return response()->json([
                'success' => true,
                'message' => 'Payroll unlocked successfully.',
                'is_locked' => false
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reverse payroll
     */
    public function reverse(Request $request, Payroll $payroll)
    {
        if ($payroll->reversed_at) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll has already been reversed.'
            ], 400);
        }

        if (!$payroll->can_be_reversed) {
            return response()->json([
                'success' => false,
                'message' => 'This payroll cannot be reversed.'
            ], 400);
        }

        $request->validate([
            'reversal_reason' => 'required|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $payroll->reverse(Auth::id(), $request->reversal_reason);

            // Audit logging is handled in the reverse() method

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payroll reversed successfully.',
                'status' => $payroll->status
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reverse payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initialize approval workflow for a payroll
     */
    private function initializeApprovalWorkflow(Payroll $payroll)
    {
        // Get approval settings for the company/branch
        // Use branch from payroll creator or current user
        $branchId = $payroll->creator->branch_id ?? auth()->user()->branch_id ?? null;
        $approvalSettings = PayrollApprovalSettings::getSettingsForCompany(
            $payroll->company_id,
            $branchId
        );

        // Always require approval verification - even if settings don't require it
        // This ensures payrolls are reviewed before being marked as completed
        $totalAmount = $payroll->total_gross_pay;
        $requiredApprovals = [];

        if ($approvalSettings && $approvalSettings->approval_required) {
            // Use configured approval settings
            $requiredApprovals = $approvalSettings->getRequiredApprovalsForAmount($totalAmount);
        }

        // If no approval settings or no required approvals, create a default single-level approval
        // Assign to super admin users or the payroll creator
        if (empty($requiredApprovals)) {
            // Find super admin users who can approve
            $defaultApprovers = [];
            
            // Get all users with super-admin or admin role
            $adminUsers = \App\Models\User::where('company_id', $payroll->company_id)
                ->get()
                ->filter(function($user) {
                    return $user->hasAnyRole(['super-admin', 'admin', 'Super Admin', 'Admin']);
                })
                ->pluck('id')
                ->toArray();

            if (!empty($adminUsers)) {
                $defaultApprovers = $adminUsers;
            } else {
                // If no admin found, use the payroll creator
                $defaultApprovers = [$payroll->created_by];
            }

            // Create a default single-level approval
            $requiredApprovals = [
                [
                    'level' => 1,
                    'approvers' => $defaultApprovers
                ]
            ];
        }

        // Set payroll as requiring approval
        $payroll->update([
            'requires_approval' => true,
            'current_approval_level' => 1,
            'is_fully_approved' => false
        ]);

        // Create approval records for each required level
        foreach ($requiredApprovals as $approval) {
            foreach ($approval['approvers'] as $approverId) {
                PayrollApproval::create([
                    'payroll_id' => $payroll->id,
                    'approval_level' => $approval['level'],
                    'approver_id' => $approverId,
                    'status' => 'pending',
                    'amount_at_approval' => $totalAmount
                ]);
            }
        }
    }

    /**
     * Check if user can approve at current level
     */
    private function canUserApprove(Payroll $payroll, $userId)
    {
        $user = \App\Models\User::find($userId);
        
        // Allow super admins to approve at any level
        if ($user && ($user->hasRole('super-admin') || $user->hasRole('Super Admin') || $user->is_admin)) {
            return true;
        }
        
        // Get approval settings to verify user is authorized
        $branchId = $payroll->creator->branch_id ?? auth()->user()->branch_id ?? null;
        $approvalSettings = PayrollApprovalSettings::getSettingsForCompany(
            $payroll->company_id,
            $branchId
        );
        
        // If no settings or approval not required, user cannot approve
        if (!$approvalSettings || !$approvalSettings->approval_required) {
            return false;
        }
        
        // Verify user is assigned to the current approval level in settings
        if (!$approvalSettings->canUserApproveAtLevel($userId, $payroll->current_approval_level)) {
            return false;
        }
        
        // Check if there's a pending approval for this user at the current level
        return PayrollApproval::where('payroll_id', $payroll->id)
            ->where('approver_id', $userId)
            ->where('approval_level', $payroll->current_approval_level)
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Process approval and check if payroll is fully approved
     */
    private function processApproval(Payroll $payroll, $userId, $remarks)
    {
        $user = \App\Models\User::find($userId);
        $isSuperAdmin = $user && ($user->hasRole('super-admin') || $user->hasRole('Super Admin') || $user->is_admin);
        
        // If super admin, they can approve all levels at once
        if ($isSuperAdmin) {
            // Mark all pending approvals as approved by super admin
            $pendingApprovals = PayrollApproval::where('payroll_id', $payroll->id)
                ->where('status', 'pending')
                ->get();
                
            foreach ($pendingApprovals as $pendingApproval) {
                $pendingApproval->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'remarks' => "Approved by Super Admin ({$user->name}): " . $remarks
                ]);
            }
            
            // Check if super admin needs their own approval records for any level
            $allLevels = PayrollApproval::where('payroll_id', $payroll->id)
                ->distinct()
                ->pluck('approval_level');
                
            foreach ($allLevels as $level) {
                $superAdminApprovalExists = PayrollApproval::where('payroll_id', $payroll->id)
                    ->where('approval_level', $level)
                    ->where('approver_id', $userId)
                    ->exists();
                    
                if (!$superAdminApprovalExists) {
                    // Create super admin approval record for this level
                    PayrollApproval::create([
                        'payroll_id' => $payroll->id,
                        'approval_level' => $level,
                        'approver_id' => $userId,
                        'status' => 'approved',
                        'approved_at' => now(),
                        'remarks' => "Super Admin approval: " . $remarks,
                        'amount_at_approval' => $payroll->total_gross_pay
                    ]);
                }
            }
            
            // Mark payroll as fully approved
            $payroll->update([
                'status' => 'completed',
                'is_fully_approved' => true,
                'approved_by' => $userId,
                'approved_at' => now(),
                'approval_remarks' => $remarks
            ]);

            // Create accrual journal entry for salary and deductions
            $this->createPayrollAccrualJournalEntry($payroll);
            
            return true; // Fully approved
        } else {
            // Regular user approval process
            $approval = PayrollApproval::where('payroll_id', $payroll->id)
                ->where('approver_id', $userId)
                ->where('approval_level', $payroll->current_approval_level)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                throw new \Exception('No pending approval found for this user at the current level.');
            }

            $approval->update([
                'status' => 'approved',
                'approved_at' => now(),
                'remarks' => $remarks
            ]);

            // Check if all approvals for current level are completed
            $pendingApprovalsCurrentLevel = PayrollApproval::where('payroll_id', $payroll->id)
                ->where('approval_level', $payroll->current_approval_level)
                ->where('status', 'pending')
                ->count();

            if ($pendingApprovalsCurrentLevel === 0) {
                // Current level is fully approved, check if there are more levels
                $nextLevelApprovals = PayrollApproval::where('payroll_id', $payroll->id)
                    ->where('approval_level', '>', $payroll->current_approval_level)
                    ->exists();

                if ($nextLevelApprovals) {
                    // Move to next level
                    $nextLevel = PayrollApproval::where('payroll_id', $payroll->id)
                        ->where('approval_level', '>', $payroll->current_approval_level)
                        ->min('approval_level');
                    
                    $payroll->update([
                        'current_approval_level' => $nextLevel
                    ]);
                    
                    return false; // Not fully approved yet
                } else {
                    // All levels approved
                    $payroll->update([
                        'status' => 'completed',
                        'is_fully_approved' => true,
                        'approved_by' => $userId,
                        'approved_at' => now(),
                        'approval_remarks' => $remarks
                    ]);

                    // Create accrual journal entry for salary and deductions
                    $this->createPayrollAccrualJournalEntry($payroll);
                    
                    return true; // Fully approved
                }
            }

            return false; // Not fully approved yet
        }
    }

    /**
     * Create accrual journal entry for approved payroll
     */
    private function createPayrollAccrualJournalEntry(Payroll $payroll)
    {
        // Use payroll's company_id instead of current_company_id() to ensure we get the right chart accounts
        $chartAccounts = PayrollChartAccount::where('company_id', $payroll->company_id)->first();
        
        if (!$chartAccounts) {
            throw new \Exception('Payroll chart accounts not configured for company ID ' . $payroll->company_id . '. Please configure chart accounts first.');
        }

        // Get branch ID with fallback
        $branchId = auth()->user()->branch_id ?? 1; // Fallback to branch ID 1 if null

        // Create the journal entry
        $journal = Journal::create([
            'date' => now(),
            'reference' => 'PAYROLL-' . $payroll->reference,
            'reference_type' => 'payroll_accrual',
            'description' => "Payroll accrual for {$payroll->month}/{$payroll->year}",
            'branch_id' => $branchId,
            'user_id' => Auth::id(),
        ]);

        $journalItems = [];
        $glTransactions = [];
        $companyId = $payroll->company_id; // Use payroll's company_id
        $userId = Auth::id();
        $journalDate = now();

        // Calculate totals from payroll employees
        // Note: We need to recalculate employer contributions to ensure accuracy
        $totals = $payroll->payrollEmployees->reduce(function ($carry, $payrollEmployee) use ($payroll, $companyId) {
            $employee = $payrollEmployee->employee;
            if (!$employee) {
                return $carry;
            }
            
            $grossSalary = $payrollEmployee->gross_salary;
            $date = Carbon::create($payroll->year, $payroll->month, 1);
            
            // Calculate employer pension contribution (matching PayrollCalculationService logic)
            $employerPension = 0;
            $pensionEmployee = $payrollEmployee->pension ?? 0;
            $pensionRule = \App\Models\Hr\StatutoryRule::getActiveRuleForEmployee($companyId, \App\Models\Hr\StatutoryRule::TYPE_PENSION, $employee, $date)
                ?? \App\Models\Hr\StatutoryRule::getActiveRule($companyId, \App\Models\Hr\StatutoryRule::TYPE_PENSION, $date);
            
            if ($pensionRule && ($pensionRule->apply_to_all_employees || $employee->has_pension)) {
                $percent = $employee->pension_employer_percent ?? $pensionRule->pension_employer_percent ?? 0;
                if ($percent > 0) {
                    $base = $grossSalary;
                    if ($pensionRule->pension_ceiling) {
                        $base = min($base, $pensionRule->pension_ceiling);
                    }
                    $employerPension = $base * ($percent / 100);
                } elseif ($pensionRule->apply_to_all_employees && $pensionEmployee > 0) {
                    // Fallback: 10% of employee contribution if no employer percent set
                    $employerPension = $pensionEmployee * 0.1;
                } elseif (!$pensionRule->apply_to_all_employees && $pensionEmployee > 0) {
                    // Fallback for employee-specific rules
                    $employerPension = $pensionEmployee * 0.1;
                }
            }
            
            // Calculate employer NHIF contribution (matching PayrollCalculationService logic)
            $employerNhif = 0;
            $nhifEmployee = $payrollEmployee->insurance ?? 0;
            $nhifRule = \App\Models\Hr\StatutoryRule::getActiveRuleForEmployee($companyId, \App\Models\Hr\StatutoryRule::TYPE_NHIF, $employee, $date)
                ?? \App\Models\Hr\StatutoryRule::getActiveRule($companyId, \App\Models\Hr\StatutoryRule::TYPE_NHIF, $date);
            
            if ($nhifRule && ($nhifRule->apply_to_all_employees || $employee->has_nhif)) {
                $percent = $employee->nhif_employer_percent ?? $nhifRule->nhif_employer_percent ?? 0;
                if ($percent > 0) {
                    $base = $payrollEmployee->basic_salary ?: $grossSalary;
                    if ($nhifRule->nhif_ceiling) {
                        $base = min($base, $nhifRule->nhif_ceiling);
                    }
                    $employerNhif = $base * ($percent / 100);
                } elseif ($nhifRule->apply_to_all_employees && $nhifEmployee > 0) {
                    // Fallback: 10% of employee contribution if no employer percent set
                    $employerNhif = $nhifEmployee * 0.1;
                } elseif (!$nhifRule->apply_to_all_employees && $nhifEmployee > 0) {
                    // Fallback for employee-specific rules
                    $employerNhif = $nhifEmployee * 0.1;
                }
            }
            
            // WCF and SDL are employer contributions (stored in employee wcf/sdl fields)
            // The employee wcf/sdl in payroll_employees table represents the employer contribution
            // Employee deductions for WCF/SDL would be separate if they exist
            $wcfEmployer = $payrollEmployee->wcf ?? 0; // This is employer WCF
            $sdlEmployer = $payrollEmployee->sdl ?? 0; // This is employer SDL
            
            return [
                'basic_salary' => $carry['basic_salary'] + $payrollEmployee->basic_salary,
                'allowance' => $carry['allowance'] + $payrollEmployee->allowance,
                'other_allowances' => $carry['other_allowances'] + $payrollEmployee->other_allowances,
                'paye' => $carry['paye'] + $payrollEmployee->paye,
                'pension' => $carry['pension'] + $payrollEmployee->pension,
                'employer_pension' => $carry['employer_pension'] + $employerPension,
                'insurance' => $carry['insurance'] + $payrollEmployee->insurance,
                'employer_nhif' => $carry['employer_nhif'] + $employerNhif,
                'salary_advance' => $carry['salary_advance'] + $payrollEmployee->salary_advance,
                'loans' => $carry['loans'] + $payrollEmployee->loans,
                'trade_union' => $carry['trade_union'] + $payrollEmployee->trade_union,
                'sdl' => $carry['sdl'] + $sdlEmployer, // Employer SDL
                'wcf' => $carry['wcf'] + $wcfEmployer, // Employer WCF
                'heslb' => $carry['heslb'] + $payrollEmployee->heslb,
                'other_deductions' => $carry['other_deductions'] + $payrollEmployee->other_deductions,
            ];
        }, array_fill_keys([
            'basic_salary', 'allowance', 'other_allowances', 'paye', 'pension', 'employer_pension',
            'insurance', 'employer_nhif', 'salary_advance', 'loans', 'trade_union', 'sdl', 
            'wcf', 'heslb', 'other_deductions'
        ], 0));

        // ====================================================================
        // CORRECT DOUBLE ENTRY ACCOUNTING FOR PAYROLL
        // ====================================================================
        
        // 1. DR Salary Expense Account (Basic Salary only)
        if ($totals['basic_salary'] > 0 && $chartAccounts->salary_expense_account_id) {
            $this->addJournalItemAndGLTransaction(
                $journal, $journalItems, $glTransactions,
                $chartAccounts->salary_expense_account_id,
                $totals['basic_salary'], 'debit',
                'Salary expense for ' . $payroll->month . '/' . $payroll->year,
                $companyId, $branchId, $userId, $journalDate, $payroll->id, 'payroll_accrual'
            );
        }

        // 2. DR Allowance Expense Account (Allowances)
        if (($totals['allowance'] + $totals['other_allowances']) > 0 && $chartAccounts->allowance_expense_account_id) {
            $this->addJournalItemAndGLTransaction(
                $journal, $journalItems, $glTransactions,
                $chartAccounts->allowance_expense_account_id,
                $totals['allowance'] + $totals['other_allowances'], 'debit',
                'Allowance expense for ' . $payroll->month . '/' . $payroll->year,
                $companyId, $branchId, $userId, $journalDate, $payroll->id, 'payroll_accrual'
            );
        }

        // Calculate gross salary and net salary
        // Net salary should be calculated from actual payroll employees' net_salary field
        // to ensure accuracy (WCF and SDL are employer contributions, not employee deductions)
        $grossSalary = $totals['basic_salary'] + $totals['allowance'] + $totals['other_allowances'];
        
        // Always recalculate net salary to ensure accuracy
        // WCF and SDL are employer-only contributions, so they should NOT be deducted from employee net salary
        // Employee deductions: PAYE, Pension (employee), NHIF (employee), Salary Advance, Loans, Trade Union, HESLB, Other Deductions
        $netSalary = $grossSalary - ($totals['paye'] + $totals['pension'] + $totals['insurance'] + 
                     $totals['salary_advance'] + $totals['loans'] + $totals['trade_union'] + 
                     $totals['heslb'] + $totals['other_deductions']);
        
        // Round to 2 decimal places for accuracy
        $netSalary = round($netSalary, 2);
        
        // 3. DR NHIF Expense (Employer portion) - Add to debit side BEFORE credit entries
        $nhifEmployer = $totals['employer_nhif'] ?? $payroll->total_nhif_employer ?? 0;
        if ($nhifEmployer > 0) {
            // Use insurance_expense_account_id if configured, otherwise use a fallback account
            $nhifExpenseAccountId = $chartAccounts->insurance_expense_account_id 
                ?? $chartAccounts->salary_expense_account_id; // Fallback to salary expense if NHIF expense not configured
            if ($nhifExpenseAccountId) {
                $this->addJournalItemAndGLTransaction(
                    $journal, $journalItems, $glTransactions,
                    $nhifExpenseAccountId,
                    $nhifEmployer, 'debit',
                    'NHIF expense (Employer) for ' . $payroll->month . '/' . $payroll->year,
                    $companyId, $branchId, $userId, $journalDate, $payroll->id, 'payroll_accrual'
                );
            }
        }

        // 4. CR Salary Payable Account (Net amount to pay to employees)
        // Use the exact net salary amount: 7,376,020.50 or calculated value
        if ($netSalary > 0 && $chartAccounts->salary_payable_account_id) {
            $this->addJournalItemAndGLTransaction(
                $journal, $journalItems, $glTransactions,
                $chartAccounts->salary_payable_account_id,
                $netSalary, 'credit',
                'Salary payable for ' . $payroll->month . '/' . $payroll->year,
                $companyId, $branchId, $userId, $journalDate, $payroll->id, 'payroll_accrual'
            );
        }

        // 5. CR Employee Deduction Payables (amounts deducted from employee salaries)
        // This includes salary advance receivable (credit)
        $this->addEmployeeDeductionPayables($journal, $journalItems, $glTransactions, $chartAccounts, $totals, $payroll, $companyId, $branchId, $userId, $journalDate);

        // 6. DR & CR Employer Statutory Contributions (separate transactions)
        // Note: NHIF Employer Expense is already debited above, so we skip it here
        $this->addEmployerStatutoryContributions($journal, $journalItems, $glTransactions, $chartAccounts, $totals, $payroll, $companyId, $branchId, $userId, $journalDate);

        // Other Deductions Payable (Credit)
        if ($totals['other_deductions'] > 0 && $chartAccounts->other_payable_account_id) {
            $this->addJournalItemAndGLTransaction(
                $journal, $journalItems, $glTransactions,
                $chartAccounts->other_payable_account_id,
                $totals['other_deductions'], 'credit',
                'Other deductions payable for ' . $payroll->month . '/' . $payroll->year,
                $companyId, $branchId, $userId, $journalDate, $payroll->id, 'payroll_accrual'
            );
        }

        // Insert all journal items and GL transactions
        if (!empty($journalItems)) {
            JournalItem::insert($journalItems);
        }
        if (!empty($glTransactions)) {
            GlTransaction::insert($glTransactions);
        }

        // Store the journal reference in payroll for tracking
        $payroll->update(['journal_reference' => $journal->reference]);
    }

    /**
     * Add employee deduction payables (amounts deducted from employee salaries)
     * These are CREDITS only - no expense entries
     */
    private function addEmployeeDeductionPayables($journal, &$journalItems, &$glTransactions, $chartAccounts, $totals, $payroll, $companyId, $branchId, $userId, $journalDate)
    {
        $deductionPayables = [
            ['amount' => $totals['paye'], 'account_id' => $chartAccounts->payee_payable_account_id, 'description' => 'PAYE payable'],
            // Note: Pension, NHIF, WCF, SDL Payables are handled in addEmployerStatutoryContributions() to credit with TOTAL (Employee + Employer)
            ['amount' => $totals['trade_union'], 'account_id' => $chartAccounts->trade_union_payable_account_id, 'description' => 'Trade union payable'],
            ['amount' => $totals['heslb'], 'account_id' => $chartAccounts->heslb_payable_account_id, 'description' => 'HESLB payable'],
        ];

        foreach ($deductionPayables as $payable) {
            if ($payable['amount'] > 0 && $payable['account_id']) {
                $this->addJournalItemAndGLTransaction(
                    $journal, $journalItems, $glTransactions,
                    $payable['account_id'], $payable['amount'], 'credit',
                    $payable['description'] . ' for ' . $payroll->month . '/' . $payroll->year,
                    $companyId, $branchId, $userId, $journalDate, $payroll->id, 'payroll_accrual'
                );
            }
        }
        
        // Salary Advance Receivable (Credit) - this is a reduction in receivables
        if ($totals['salary_advance'] > 0 && $chartAccounts->salary_advance_receivable_account_id) {
            $this->addJournalItemAndGLTransaction(
                $journal, $journalItems, $glTransactions,
                $chartAccounts->salary_advance_receivable_account_id,
                $totals['salary_advance'], 'credit',
                'Salary advance recovery for ' . $payroll->month . '/' . $payroll->year,
                $companyId, $branchId, $userId, $journalDate, $payroll->id, 'payroll_accrual'
            );
        }

        // External Loans Payable (Credit) - this is a liability for external loan repayments
        // Use dedicated external loan payable account if available, otherwise fallback to salary advance receivable or other payable account
        if ($totals['loans'] > 0) {
            $loansAccountId = $chartAccounts->external_loan_payable_account_id 
                              ?? $chartAccounts->salary_advance_receivable_account_id 
                              ?? $chartAccounts->other_payable_account_id;
            if ($loansAccountId) {
                $this->addJournalItemAndGLTransaction(
                    $journal, $journalItems, $glTransactions,
                    $loansAccountId,
                    $totals['loans'], 'credit',
                    'External loan recovery for ' . $payroll->month . '/' . $payroll->year,
                    $companyId, $branchId, $userId, $journalDate, $payroll->id, 'payroll_accrual'
                );
            }
        }
    }

    /**
     * Add employer statutory contributions (separate DR/CR pairs)
     * Example:
     * DR Pension Expense Account ... 150,000
     *    CR Pension Payable Account ... 150,000
     */
    private function addEmployerStatutoryContributions($journal, &$journalItems, &$glTransactions, $chartAccounts, $totals, $payroll, $companyId, $branchId, $userId, $journalDate)
    {
        // Calculate total pension (employee + employer)
        $totalPension = $totals['pension'] + ($totals['employer_pension'] ?? 0);
        
        // Pension: Employee portion (already deducted from salary) + Employer portion (company expense)
        if ($totalPension > 0 && $chartAccounts->pension_payable_account_id) {
            // CR Pension Payable with TOTAL (Employee + Employer)
            $this->addJournalItemAndGLTransaction(
                $journal, $journalItems, $glTransactions,
                $chartAccounts->pension_payable_account_id,
                $totalPension, 'credit',
                'Pension payable (Total: Employee + Employer) for ' . $payroll->month . '/' . $payroll->year,
                $companyId, $branchId, $userId, $journalDate, $payroll->id, 'payroll_accrual'
            );
        }
        
        // Employer Pension Expense (only the employer portion)
        if (($totals['employer_pension'] ?? 0) > 0 && $chartAccounts->pension_expense_account_id) {
            // DR Pension Expense (Employer portion only)
            $this->addJournalItemAndGLTransaction(
                $journal, $journalItems, $glTransactions,
                $chartAccounts->pension_expense_account_id,
                $totals['employer_pension'], 'debit',
                'Pension expense (Employer) for ' . $payroll->month . '/' . $payroll->year,
                $companyId, $branchId, $userId, $journalDate, $payroll->id, 'payroll_accrual'
            );
        }

        // WCF (Workers Compensation Fund) - Employer contribution only
        // Note: In Tanzania, WCF is typically employer-only. The $totals['wcf'] is the employer WCF.
        // If there are employee WCF deductions, they would be tracked separately, but typically WCF is employer-only.
        $wcfEmployer = $totals['wcf'] ?? 0; // Employer WCF contribution (from payroll_employees.wcf)
        $totalWcf = $wcfEmployer; // WCF is typically employer-only, so total equals employer portion
        
        // DR WCF Expense (Employer portion)
        if ($wcfEmployer > 0 && $chartAccounts->wcf_expense_account_id) {
            $this->addJournalItemAndGLTransaction(
                $journal, $journalItems, $glTransactions,
                $chartAccounts->wcf_expense_account_id,
                $wcfEmployer, 'debit',
                'WCF expense (Employer) for ' . $payroll->month . '/' . $payroll->year,
                $companyId, $branchId, $userId, $journalDate, $payroll->id, 'payroll_accrual'
            );
        }
        
        // CR WCF Payable (Total: Employer only, since WCF is typically employer-only)
        if ($totalWcf > 0 && $chartAccounts->wcf_payable_account_id) {
            $this->addJournalItemAndGLTransaction(
                $journal, $journalItems, $glTransactions,
                $chartAccounts->wcf_payable_account_id,
                $totalWcf, 'credit',
                'WCF payable (Employer) for ' . $payroll->month . '/' . $payroll->year,
                $companyId, $branchId, $userId, $journalDate, $payroll->id, 'payroll_accrual'
            );
        }

        // SDL (Skills Development Levy) - Employer contribution only
        // Note: SDL is typically employer-only in Tanzania, so $totals['sdl'] and $payroll->total_sdl are the same (employer SDL)
        $sdlEmployer = $payroll->total_sdl ?? $totals['sdl'] ?? 0; // Employer SDL contribution
        $totalSdl = $sdlEmployer; // SDL is employer-only, so total equals employer portion
        
        // DR SDL Expense (Employer portion)
        if ($sdlEmployer > 0 && $chartAccounts->sdl_expense_account_id) {
            $this->addJournalItemAndGLTransaction(
                $journal, $journalItems, $glTransactions,
                $chartAccounts->sdl_expense_account_id,
                $sdlEmployer, 'debit',
                'SDL expense (Employer) for ' . $payroll->month . '/' . $payroll->year,
                $companyId, $branchId, $userId, $journalDate, $payroll->id, 'payroll_accrual'
            );
        }
        
        // CR SDL Payable (Total: Employer only, since SDL is employer-only)
        if ($totalSdl > 0 && $chartAccounts->sdl_payable_account_id) {
            $this->addJournalItemAndGLTransaction(
                $journal, $journalItems, $glTransactions,
                $chartAccounts->sdl_payable_account_id,
                $totalSdl, 'credit',
                'SDL payable (Employer) for ' . $payroll->month . '/' . $payroll->year,
                $companyId, $branchId, $userId, $journalDate, $payroll->id, 'payroll_accrual'
            );
        }
        
        // NHIF (National Health Insurance Fund) - Employee deductions + Employer contribution
        // Note: NHIF Employer Expense is already debited in the main debit section above
        $nhifEmployee = $totals['insurance'] ?? 0; // Employee NHIF deductions
        $nhifEmployer = $totals['employer_nhif'] ?? $payroll->total_nhif_employer ?? 0; // Employer NHIF contribution
        $totalNhif = $nhifEmployee + $nhifEmployer;
        
        // CR NHIF Payable (Total: Employee + Employer)
        if ($totalNhif > 0 && $chartAccounts->insurance_payable_account_id) {
            $this->addJournalItemAndGLTransaction(
                $journal, $journalItems, $glTransactions,
                $chartAccounts->insurance_payable_account_id,
                $totalNhif, 'credit',
                'NHIF payable (Total: Employee + Employer) for ' . $payroll->month . '/' . $payroll->year,
                $companyId, $branchId, $userId, $journalDate, $payroll->id, 'payroll_accrual'
            );
        }
    }

    /**
     * @deprecated - No longer used. Employee deductions go directly to payables, not expenses
     */
    private function addStatutoryExpenses($journal, &$journalItems, &$glTransactions, $chartAccounts, $totals, $payroll, $companyId, $branchId, $userId, $journalDate)
    {
        // This method is deprecated and should not be used
        // Employee deductions are not expenses - they go directly to payables
    }

    /**
     * @deprecated - Replaced by addEmployeeDeductionPayables
     */
    private function addStatutoryPayables($journal, &$journalItems, &$glTransactions, $chartAccounts, $totals, $payroll, $companyId, $branchId, $userId, $journalDate)
    {
        // This method is deprecated
        // Use addEmployeeDeductionPayables instead
    }

    /**
     * Helper method to add journal item and GL transaction
     */
    private function addJournalItemAndGLTransaction($journal, &$journalItems, &$glTransactions, $accountId, $amount, $nature, $description, $companyId, $branchId, $userId, $date, $transactionId, $transactionType)
    {
        if ($amount <= 0 || !$accountId) return;

        // Journal Item
        $journalItems[] = [
            'journal_id' => $journal->id,
            'chart_account_id' => $accountId,
            'amount' => $amount,
            'description' => $description,
            'nature' => $nature,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // GL Transaction
        $glTransactions[] = [
            'chart_account_id' => $accountId,
            'amount' => $amount,
            'nature' => $nature,
            'transaction_id' => $transactionId,
            'transaction_type' => $transactionType,
            'date' => $date,
            'description' => $description,
            'branch_id' => $branchId,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Show payment form for approved payroll
     */
    public function showPaymentForm(Payroll $payroll)
    {
        if ($payroll->status !== 'completed') {
            return redirect()->route('hr.payrolls.show', $payroll)
                ->with('error', 'Only completed payrolls can be paid.');
        }

        if ($payroll->payment_status === 'paid') {
            return redirect()->route('hr.payrolls.show', $payroll)
                ->with('info', 'This payroll has already been paid.');
        }

        // Get chart accounts
        $chartAccounts = PayrollChartAccount::where('company_id', current_company_id())->first();
        
        if (!$chartAccounts || !$chartAccounts->salary_payable_account_id) {
            return redirect()->route('hr.payrolls.show', $payroll)
                ->with('error', 'Salary payable account not configured. Please configure chart accounts first.');
        }

        // Get bank accounts (filter by company through account class group relationship)
        $bankAccounts = ChartAccount::whereHas('accountClassGroup', function($query) {
                $query->where('company_id', current_company_id());
            })
            ->whereHas('accountClassGroup.accountClass', function($query) {
                $query->where('name', 'Assets');
            })
            ->where(function($q) {
                $q->where('account_name', 'like', '%bank%')
                  ->orWhere('account_name', 'like', '%cash%');
            })
            ->get();

        // Calculate net salary payable
        $netSalary = $payroll->payrollEmployees->sum(function($employee) {
            return $employee->basic_salary + $employee->allowance + $employee->other_allowances 
                 - ($employee->paye + $employee->pension + $employee->insurance + $employee->salary_advance 
                  + $employee->loans + $employee->trade_union + $employee->sdl + $employee->wcf 
                  + $employee->heslb + $employee->other_deductions);
        });

        return view('hr-payroll.payrolls.payment-form', compact('payroll', 'bankAccounts', 'chartAccounts', 'netSalary'));
    }

    /**
     * Request payment approval for a payroll
     */
    public function requestPaymentApproval(Request $request, Payroll $payroll)
    {
        if ($payroll->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Only completed payrolls can request payment approval.'
            ], 400);
        }

        if ($payroll->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'This payroll has already been paid.'
            ], 400);
        }

        if ($payroll->requires_payment_approval && !$payroll->is_payment_fully_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Payment approval is already in progress.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Initialize payment approval workflow
            $this->initializePaymentApprovalWorkflow($payroll);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $payroll->fresh()->requires_payment_approval 
                    ? 'Payment approval requested. Waiting for approvers.'
                    : 'Payment approval not required. You can proceed with payment.',
                'requires_approval' => $payroll->fresh()->requires_payment_approval
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to request payment approval: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve payment
     */
    public function approvePayment(Request $request, Payroll $payroll)
    {
        if ($payroll->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Only completed payrolls can have payment approved.'
            ], 400);
        }

        if ($payroll->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'This payroll has already been paid.'
            ], 400);
        }

        // Check if payment approval is required by settings
        $approvalSettings = PayrollPaymentApprovalSettings::getSettingsForCompany(
            $payroll->company_id,
            auth()->user()->branch_id ?? null
        );
        
        if (!$approvalSettings || !$approvalSettings->payment_approval_required) {
            return response()->json([
                'success' => false,
                'message' => 'Payment approval is not required for this payroll.'
            ], 400);
        }
        
        // Initialize payment approval workflow if not already initialized
        if (!$payroll->requires_payment_approval) {
            try {
                DB::beginTransaction();
                $this->initializePaymentApprovalWorkflow($payroll);
                DB::commit();
                $payroll->refresh();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to initialize payment approval workflow: ' . $e->getMessage()
                ], 500);
            }
        }

        // Check if current user can approve at current level
        // Refresh payroll to ensure we have latest data
        $payroll->refresh();
        if (!$this->canUserApprovePayment($payroll, Auth::id())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to approve this payment at the current level.'
            ], 403);
        }

        $request->validate([
            'remarks' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            // Process the payment approval
            $fullyApproved = $this->processPaymentApproval($payroll, Auth::id(), $request->remarks);
            
            // Refresh payroll to get latest state
            $payroll->refresh();

            // Create audit trail (if activity package is available)
            if (function_exists('activity')) {
                $action = $fullyApproved ? 'payment_fully_approved' : 'payment_level_approved';
                $message = $fullyApproved ? 'Payment fully approved' : "Payment approved at level {$payroll->current_payment_approval_level}";
                
                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($payroll)
                    ->withProperties([
                        'action' => $action,
                        'remarks' => $request->remarks,
                        'approval_level' => $payroll->current_payment_approval_level,
                        'fully_approved' => $fullyApproved
                    ])
                    ->log($message);
            }

            DB::commit();

            $message = $fullyApproved ? 
                'Payment approved successfully. You can now process the payment.' :
                "Payment approved at level {$payroll->current_payment_approval_level}. Waiting for next level approval.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'is_payment_fully_approved' => $fullyApproved,
                'requires_payment_approval' => $payroll->requires_payment_approval,
                'current_payment_approval_level' => $payroll->current_payment_approval_level
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject payment
     */
    public function rejectPayment(Request $request, Payroll $payroll)
    {
        if ($payroll->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Only completed payrolls can have payment rejected.'
            ], 400);
        }

        if ($payroll->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'This payroll has already been paid.'
            ], 400);
        }

        // Check if payment approval is required by settings
        $approvalSettings = PayrollPaymentApprovalSettings::getSettingsForCompany(
            $payroll->company_id,
            auth()->user()->branch_id ?? null
        );
        
        if (!$approvalSettings || !$approvalSettings->payment_approval_required) {
            return response()->json([
                'success' => false,
                'message' => 'Payment approval is not required for this payroll.'
            ], 400);
        }
        
        // Initialize payment approval workflow if not already initialized
        if (!$payroll->requires_payment_approval) {
            try {
                $this->initializePaymentApprovalWorkflow($payroll);
                $payroll->refresh();
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to initialize payment approval workflow: ' . $e->getMessage()
                ], 500);
            }
        }

        // Check if current user can approve/reject at current level
        if (!$this->canUserApprovePayment($payroll, Auth::id())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to reject this payment at the current level.'
            ], 403);
        }

        $request->validate([
            'remarks' => 'required|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            // Reject all pending payment approvals (rejection at any level cancels the whole process)
            PayrollPaymentApproval::where('payroll_id', $payroll->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'approved_at' => now(),
                    'remarks' => $request->remarks
                ]);

            // Update payroll payment approval status
            $payroll->update([
                'requires_payment_approval' => false,
                'is_payment_fully_approved' => false,
                'payment_rejected_by' => Auth::id(),
                'payment_rejected_at' => now(),
                'payment_rejection_remarks' => $request->remarks
            ]);

            // Create audit trail (if activity package is available)
            if (function_exists('activity')) {
                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($payroll)
                    ->withProperties([
                        'action' => 'payment_rejected',
                        'remarks' => $request->remarks,
                        'rejection_level' => $payroll->current_payment_approval_level,
                    ])
                    ->log("Payment rejected at level {$payroll->current_payment_approval_level}");
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment rejected successfully.',
                'is_payment_fully_approved' => false
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process salary payment
     */
    public function processPayment(Request $request, Payroll $payroll)
    {
        $request->validate([
            'bank_account_id' => 'required|exists:chart_accounts,id',
            'payment_date' => 'required|date',
            'payment_reference' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:500'
        ]);

        if ($payroll->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Only completed payrolls can be paid.'
            ], 400);
        }

        if ($payroll->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'This payroll has already been paid.'
            ], 400);
        }

        // Check if payment approval is required according to settings
        $approvalSettings = PayrollPaymentApprovalSettings::getSettingsForCompany(
            $payroll->company_id,
            auth()->user()->branch_id ?? null
        );
        
        // If payment approval is required by settings, check if workflow is initialized
        if ($approvalSettings && $approvalSettings->payment_approval_required) {
            // Check if approval workflow needs to be initialized
            if (!$payroll->requires_payment_approval) {
                // Initialize payment approval workflow first
                try {
                    DB::beginTransaction();
                    $this->initializePaymentApprovalWorkflow($payroll);
                    
                    // Store payment submission info (bank account and submitter)
                    $bankAccount = \App\Models\BankAccount::where('chart_account_id', $request->bank_account_id)->first();
                    $chartAccount = \App\Models\ChartAccount::find($request->bank_account_id);
                    
                    $payroll->update([
                        'payment_submitted_by' => Auth::id(),
                        'payment_submitted_at' => now(),
                        'payment_bank_account_id' => $bankAccount ? $bankAccount->id : null,
                        'payment_chart_account_id' => $request->bank_account_id,
                        'payment_date' => $request->payment_date,
                        'payment_reference' => $request->payment_reference,
                        'payment_remarks' => $request->remarks,
                    ]);
                    
                    DB::commit();
                    $payroll->refresh();
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Payment approval workflow has been initiated. Please wait for all required approvers to approve before processing payment.',
                        'requires_approval' => true,
                        'current_approval_level' => $payroll->current_payment_approval_level ?? 1,
                        'redirect' => route('hr.payrolls.show', $payroll)
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment approval is required but could not be initialized: ' . $e->getMessage()
                    ], 500);
                }
            }
            
            // Payment approval workflow is initialized, check if fully approved
            if (!$payroll->is_payment_fully_approved) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment approval is required and has not been fully approved yet. Current approval level: ' . ($payroll->current_payment_approval_level ?? 1) . '. Please wait for all required approvers to approve.',
                    'requires_approval' => true,
                    'current_approval_level' => $payroll->current_payment_approval_level ?? 1
                ], 400);
            }
            // Payment approval is required and fully approved, proceed with payment
        }

        try {
            DB::beginTransaction();

            // Get chart accounts
            $chartAccounts = PayrollChartAccount::where('company_id', current_company_id())->first();
            
            if (!$chartAccounts || !$chartAccounts->salary_payable_account_id) {
                throw new \Exception('Salary payable account not configured.');
            }

            // Get bank account
            // The form sends chart_account_id as bank_account_id
            // We need to find the BankAccount that links to this chart account
            $bankAccount = \App\Models\BankAccount::where('chart_account_id', $request->bank_account_id)->first();
            
            // If no BankAccount record exists, we can use the chart account directly
            if (!$bankAccount) {
                // Verify the chart account exists
                $chartAccount = \App\Models\ChartAccount::find($request->bank_account_id);
                if (!$chartAccount) {
                    throw new \Exception('Bank account not found.');
                }
                
                // Create a temporary bank account record or handle without BankAccount
                // For now, we'll use the chart account ID directly in the payment
                $bankAccountId = null; // We'll handle this below
                $chartAccountId = $request->bank_account_id;
            } else {
                $bankAccountId = $bankAccount->id;
                $chartAccountId = $bankAccount->chart_account_id;
            }

            // Calculate net salary
            $netSalary = $payroll->payrollEmployees->sum(function($employee) {
                return $employee->basic_salary + $employee->allowance + $employee->other_allowances 
                     - ($employee->paye + $employee->pension + $employee->insurance + $employee->salary_advance 
                      + $employee->loans + $employee->trade_union + $employee->sdl + $employee->wcf 
                      + $employee->heslb + $employee->other_deductions);
            });

            // Get branch ID with fallback
            $branchId = auth()->user()->branch_id ?? 1;

            // Create payment record
            $payment = \App\Models\Payment::create([
                'reference' => $request->payment_reference,
                'reference_type' => 'payroll_salary',
                'reference_number' => $payroll->reference,
                'amount' => $netSalary,
                'wht_treatment' => 'NONE',
                'wht_rate' => 0,
                'wht_amount' => 0,
                'net_payable' => $netSalary,
                'total_cost' => $netSalary,
                'vat_mode' => 'NONE',
                'vat_amount' => 0,
                'base_amount' => $netSalary,
                'date' => $request->payment_date,
                'description' => "Salary payment for {$payroll->month}/{$payroll->year}" . ($request->remarks ? " - {$request->remarks}" : ""),
                'bank_account_id' => $bankAccountId,
                'payee_type' => 'other',
                'payee_name' => 'Employees',
                'branch_id' => $branchId,
                'user_id' => Auth::id(),
                'approved' => true,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Create payment item for salary payable
            \App\Models\PaymentItem::create([
                'payment_id' => $payment->id,
                'chart_account_id' => $chartAccounts->salary_payable_account_id,
                'amount' => $netSalary,
                'wht_treatment' => 'NONE',
                'wht_rate' => 0,
                'wht_amount' => 0,
                'base_amount' => $netSalary,
                'net_payable' => $netSalary,
                'total_cost' => $netSalary,
                'vat_mode' => 'NONE',
                'vat_amount' => 0,
                'description' => "Payment of salary payable for {$payroll->month}/{$payroll->year}",
            ]);

            // Create GL transactions manually since we need to specify the exact chart account
            // We can't rely on Payment model's automatic GL creation because it might not use our selected account
            
            // Create journal entry first
            $journalReference = 'PAY-' . $payroll->reference . '-' . date('Ymd');
            
            $journal = \App\Models\Journal::create([
                'date' => $request->payment_date,
                'reference' => $journalReference,
                'reference_type' => 'payroll_payment',
                'description' => "Salary payment for {$payroll->month}/{$payroll->year}",
                'branch_id' => $branchId,
                'user_id' => Auth::id(),
            ]);

            // 1. DEBIT: Salary Payable Account (clear the liability)
            \App\Models\GlTransaction::create([
                'chart_account_id' => $chartAccounts->salary_payable_account_id,
                'amount' => $netSalary,
                'nature' => 'debit',
                'transaction_id' => $payroll->id,
                'transaction_type' => 'payroll_payment',
                'date' => $request->payment_date,
                'description' => "Payment of salary payable for {$payroll->month}/{$payroll->year}",
                'branch_id' => $branchId,
                'user_id' => Auth::id(),
            ]);

            // 2. CREDIT: Selected Bank/Cash Account (cash outflow)
            \App\Models\GlTransaction::create([
                'chart_account_id' => $chartAccountId,
                'amount' => $netSalary,
                'nature' => 'credit',
                'transaction_id' => $payroll->id,
                'transaction_type' => 'payroll_payment',
                'date' => $request->payment_date,
                'description' => "Payment of salary via bank for {$payroll->month}/{$payroll->year}",
                'branch_id' => $branchId,
                'user_id' => Auth::id(),
            ]);

            // Update payroll payment status
            // Ensure payment_bank_account_id is set - use the bankAccountId we found or created
            $payroll->update([
                'payment_status' => 'paid',
                'paid_by' => Auth::id(),
                'paid_at' => $request->payment_date,
                'payment_reference' => $request->payment_reference,
                'payment_remarks' => $request->remarks,
                'payment_id' => $payment->id,
                'payment_journal_reference' => $journalReference,
                'payment_bank_account_id' => $bankAccountId,
                'payment_chart_account_id' => $chartAccountId,
                'payment_date' => $request->payment_date,
            ]);

            // Create audit trail
            if (function_exists('activity')) {
                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($payroll)
                    ->withProperties([
                        'action' => 'salary_paid',
                        'payment_id' => $payment->id,
                        'payment_amount' => $netSalary,
                        'bank_account_id' => $request->bank_account_id,
                        'payment_reference' => $request->payment_reference,
                        'remarks' => $request->remarks
                    ])
                    ->log("Salary payment processed for payroll {$payroll->reference}");
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Salary payment processed successfully.',
                'payment_amount' => number_format($netSalary, 2),
                'payment_id' => $payment->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Record HESLB repayments for all employees with HESLB deductions
     *
     * @param Payroll $payroll
     * @return void
     */
    private function recordHeslbRepayments(Payroll $payroll)
    {
        // Get all payroll employees with HESLB deductions
        $payrollEmployees = PayrollEmployee::where('payroll_id', $payroll->id)
            ->where('heslb', '>', 0)
            ->with('employee')
            ->get();

        // Get payroll date (use end of month for the payroll period)
        $repaymentDate = Carbon::create($payroll->year, $payroll->month, 1)->endOfMonth();

        foreach ($payrollEmployees as $payrollEmployee) {
            if ($payrollEmployee->employee && $payrollEmployee->heslb > 0) {
                try {
                    PayrollCalculationService::recordHeslbRepayment(
                        $payrollEmployee->employee,
                        (float) $payrollEmployee->heslb,
                        $repaymentDate,
                        $payroll->id,
                        $payrollEmployee->id
                    );
                } catch (\Exception $e) {
                    // Log error but don't fail the entire payroll processing
                    \Log::error('Failed to record HESLB repayment for employee ' . $payrollEmployee->employee_id . ': ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Record salary advance and loan repayments and auto-deactivate when fully repaid
     * 
     * Best Practice: Checks balance for each advance/loan and auto-deactivates when balance reaches 0
     */
    private function recordAdvanceAndLoanRepayments(Payroll $payroll)
    {
        $repaymentDate = Carbon::create($payroll->year, $payroll->month, 1)->endOfMonth();

        // Get all payroll employees with salary advance or loan deductions
        $payrollEmployees = PayrollEmployee::where('payroll_id', $payroll->id)
            ->where(function ($q) {
                $q->where('salary_advance', '>', 0)
                  ->orWhere('loans', '>', 0);
            })
            ->with('employee')
            ->get();

        foreach ($payrollEmployees as $payrollEmployee) {
            if (!$payrollEmployee->employee) {
                continue;
            }

            $employee = $payrollEmployee->employee;

            // Process salary advances
            if ($payrollEmployee->salary_advance > 0) {
                try {
                    // Get all active advances that allow payroll deductions
                    $advances = \App\Models\Hr\SalaryAdvance::where('employee_id', $employee->id)
                        ->where('company_id', $payroll->company_id)
                        ->where('is_active', true)
                        ->whereIn('repayment_type', ['payroll', 'both'])
                        ->where('date', '<=', $repaymentDate)
                        ->get();

                    foreach ($advances as $advance) {
                        $remaining = $advance->remaining_balance;
                        if ($remaining <= 0) {
                            continue;
                        }

                        // Determine how much to record as repayment
                        // Usually it's the monthly_deduction, but capped at remaining balance
                        $deductionAmount = min($remaining, (float) $advance->monthly_deduction);

                        // Record explicit repayment in the new table
                        $advance->recordRepayment(
                            $deductionAmount,
                            $repaymentDate,
                            $payroll->id,
                            'payroll',
                            null,
                            "Automatic deduction from payroll {$payroll->reference}"
                        );
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to process salary advance repayments for employee ' . $employee->id . ': ' . $e->getMessage());
                }
            }

            // Process external loans
            if ($payrollEmployee->loans > 0) {
                try {
                    // Get all active loans that should be deducted in this payroll
                    $loans = \App\Models\Hr\ExternalLoan::where('employee_id', $employee->id)
                        ->where('company_id', $payroll->company_id)
                        ->where('is_active', true)
                        ->where('date', '<=', $repaymentDate)
                        ->where(function ($q) use ($repaymentDate) {
                            $q->whereNull('date_end_of_loan')
                              ->orWhere('date_end_of_loan', '>=', $repaymentDate);
                        })
                        ->get();

                    foreach ($loans as $loan) {
                        $deductionType = $loan->deduction_type ?? 'fixed';
                        
                        // Calculate total deductions made for this specific loan
                        if ($deductionType === 'percentage') {
                            // For percentage-based loans, sum actual deductions from each payroll period
                            // since the amount varies based on gross salary each period
                            $totalDeductions = \App\Models\PayrollEmployee::whereHas('payroll', function ($q) use ($payroll, $employee) {
                                    $q->where('company_id', $payroll->company_id)
                                      ->where('id', '<=', $payroll->id);
                                })
                                ->where('employee_id', $employee->id)
                                ->where('loans', '>', 0)
                                ->get()
                                ->filter(function ($pe) use ($loan, $repaymentDate) {
                                    $pDate = Carbon::create($pe->payroll->year, $pe->payroll->month, 1)->endOfMonth();
                                    return $loan->date <= $pDate
                                        && ($loan->date_end_of_loan === null || $loan->date_end_of_loan >= $pDate);
                                })
                                ->sum('loans');
                            
                            // For percentage loans, we estimate based on average or use a conservative approach
                            // Since we can't know the exact amount per period without recalculating, we use the sum
                            // Note: This is an approximation - actual amount may vary per period
                        } else {
                            // For fixed amount loans, calculate based on number of periods
                            $payrollPeriods = \App\Models\Payroll::where('company_id', $payroll->company_id)
                                ->where('id', '<=', $payroll->id)
                                ->whereHas('payrollEmployees', function ($q) use ($employee) {
                                    $q->where('employee_id', $employee->id)
                                      ->where('loans', '>', 0);
                                })
                                ->get()
                                ->filter(function ($p) use ($loan, $repaymentDate) {
                                    $pDate = Carbon::create($p->year, $p->month, 1)->endOfMonth();
                                    return $loan->date <= $pDate
                                        && ($loan->date_end_of_loan === null || $loan->date_end_of_loan >= $pDate);
                                })
                                ->count();

                            // Calculate total deductions = number of periods * monthly deduction
                            $totalDeductions = $payrollPeriods * $loan->monthly_deduction;
                        }

                        // Calculate remaining balance
                        $remainingBalance = max(0, $loan->total_loan - $totalDeductions);

                        // Auto-deactivate if balance is 0 or less
                        if ($remainingBalance <= 0) {
                            $loan->is_active = false;
                            $loan->save();
                            \Log::info("Auto-deactivated external loan ID {$loan->id} for employee {$employee->id} - Balance reached 0 (Original: {$loan->total_loan}, Deducted: {$totalDeductions}, Type: {$deductionType})");
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to process external loan repayments for employee ' . $employee->id . ': ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Initialize payment approval workflow for a payroll
     */
    private function initializePaymentApprovalWorkflow(Payroll $payroll)
    {
        // Get payment approval settings for the company/branch
        $approvalSettings = PayrollPaymentApprovalSettings::getSettingsForCompany(
            $payroll->company_id,
            auth()->user()->branch_id ?? null
        );

        if (!$approvalSettings || !$approvalSettings->payment_approval_required) {
            // No payment approval required
            $payroll->update([
                'requires_payment_approval' => false,
                'is_payment_fully_approved' => true
            ]);
            return;
        }

        // Calculate net payment amount
        $netPaymentAmount = $payroll->payrollEmployees->sum(function($employee) {
            return $employee->basic_salary + $employee->allowance + $employee->other_allowances 
                 - ($employee->paye + $employee->pension + $employee->insurance + $employee->salary_advance 
                  + $employee->loans + $employee->trade_union + $employee->sdl + $employee->wcf 
                  + $employee->heslb + $employee->other_deductions);
        });

        // Get required approvals based on the payment amount
        $requiredApprovals = $approvalSettings->getRequiredApprovalsForAmount($netPaymentAmount);

        if (empty($requiredApprovals)) {
            // No approvals needed for this amount
            $payroll->update([
                'requires_payment_approval' => false,
                'is_payment_fully_approved' => true
            ]);
            return;
        }

        // Set payroll as requiring payment approval
        $payroll->update([
            'requires_payment_approval' => true,
            'current_payment_approval_level' => 1,
            'is_payment_fully_approved' => false
        ]);

        // Create payment approval records for each required level
        foreach ($requiredApprovals as $approval) {
            foreach ($approval['approvers'] as $approverId) {
                PayrollPaymentApproval::create([
                    'payroll_id' => $payroll->id,
                    'approval_level' => $approval['level'],
                    'approver_id' => $approverId,
                    'status' => 'pending',
                    'amount_at_approval' => $netPaymentAmount
                ]);
            }
        }
    }

    /**
     * Check if user can approve payment at current level
     */
    private function canUserApprovePayment(Payroll $payroll, $userId)
    {
        $user = \App\Models\User::find($userId);
        
        // Allow super admins to approve at any level
        if ($user && ($user->hasRole('super-admin') || $user->hasRole('Super Admin') || $user->is_admin)) {
            return true;
        }
        
        // Get payment approval settings to verify user is authorized
        $approvalSettings = PayrollPaymentApprovalSettings::getSettingsForCompany(
            $payroll->company_id,
            auth()->user()->branch_id ?? null
        );
        
        // If no settings or payment approval not required, user cannot approve
        if (!$approvalSettings || !$approvalSettings->payment_approval_required) {
            return false;
        }
        
        // Verify user is assigned to the current payment approval level in settings
        if (!$approvalSettings->canUserApproveAtLevel($userId, $payroll->current_payment_approval_level)) {
            return false;
        }
        
        // Check if there's a pending payment approval for this user at the current level
        return PayrollPaymentApproval::where('payroll_id', $payroll->id)
            ->where('approver_id', $userId)
            ->where('approval_level', $payroll->current_payment_approval_level)
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Process payment approval and check if payment is fully approved
     */
    private function processPaymentApproval(Payroll $payroll, $userId, $remarks)
    {
        $user = \App\Models\User::find($userId);
        $isSuperAdmin = $user && ($user->hasRole('super-admin') || $user->hasRole('Super Admin') || $user->is_admin);
        
        // If super admin, they can approve all levels at once
        if ($isSuperAdmin) {
            // Mark all pending payment approvals as approved by super admin
            $pendingApprovals = PayrollPaymentApproval::where('payroll_id', $payroll->id)
                ->where('status', 'pending')
                ->get();
                
            foreach ($pendingApprovals as $pendingApproval) {
                $pendingApproval->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'remarks' => "Approved by Super Admin ({$user->name}): " . $remarks
                ]);
            }
            
            // Get approval settings to determine all required levels
            $approvalSettings = PayrollPaymentApprovalSettings::getSettingsForCompany(
                $payroll->company_id,
                auth()->user()->branch_id ?? null
            );
            
            if ($approvalSettings) {
                // Calculate net payment amount
                $netPaymentAmount = $payroll->payrollEmployees->sum(function($employee) {
                    return $employee->basic_salary + $employee->allowance + $employee->other_allowances 
                         - ($employee->paye + $employee->pension + $employee->insurance + $employee->salary_advance 
                          + $employee->loans + $employee->trade_union + $employee->sdl + $employee->wcf 
                          + $employee->heslb + $employee->other_deductions);
                });
                
                // Ensure super admin has approval records for all required levels
                for ($level = 1; $level <= $approvalSettings->payment_approval_levels; $level++) {
                    $superAdminApprovalExists = PayrollPaymentApproval::where('payroll_id', $payroll->id)
                        ->where('approval_level', $level)
                        ->where('approver_id', $userId)
                        ->exists();
                        
                    if (!$superAdminApprovalExists) {
                        // Create super admin approval record for this level
                        PayrollPaymentApproval::create([
                            'payroll_id' => $payroll->id,
                            'approval_level' => $level,
                            'approver_id' => $userId,
                            'status' => 'approved',
                            'approved_at' => now(),
                            'remarks' => "Super Admin approval: " . ($remarks ?: 'No remarks'),
                            'amount_at_approval' => $netPaymentAmount
                        ]);
                    }
                }
            }
            
            // Mark payment as fully approved
            $payroll->update([
                'is_payment_fully_approved' => true,
                'payment_approved_by' => $userId,
                'payment_approved_at' => now(),
                'payment_approval_remarks' => $remarks
            ]);
            
            return true; // Fully approved
        } else {
            // Regular user approval process
            $approval = PayrollPaymentApproval::where('payroll_id', $payroll->id)
                ->where('approver_id', $userId)
                ->where('approval_level', $payroll->current_payment_approval_level)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                throw new \Exception('No pending payment approval found for this user at the current level.');
            }

            $approval->update([
                'status' => 'approved',
                'approved_at' => now(),
                'remarks' => $remarks
            ]);

            // Check if all approvals for current level are completed
            $pendingApprovalsCurrentLevel = PayrollPaymentApproval::where('payroll_id', $payroll->id)
                ->where('approval_level', $payroll->current_payment_approval_level)
                ->where('status', 'pending')
                ->count();

            if ($pendingApprovalsCurrentLevel === 0) {
                // Current level is fully approved, check if there are more levels
                $nextLevelApprovals = PayrollPaymentApproval::where('payroll_id', $payroll->id)
                    ->where('approval_level', '>', $payroll->current_payment_approval_level)
                    ->exists();

                if ($nextLevelApprovals) {
                    // Move to next level
                    $nextLevel = PayrollPaymentApproval::where('payroll_id', $payroll->id)
                        ->where('approval_level', '>', $payroll->current_payment_approval_level)
                        ->min('approval_level');
                    
                    $payroll->update([
                        'current_payment_approval_level' => $nextLevel
                    ]);
                    
                    return false; // Not fully approved yet
                } else {
                    // All levels approved
                    $payroll->update([
                        'is_payment_fully_approved' => true,
                        'payment_approved_by' => $userId,
                        'payment_approved_at' => now(),
                        'payment_approval_remarks' => $remarks
                    ]);
                    
                    return true; // Fully approved
                }
            }

            return false; // Not fully approved yet
        }
    }

    /**
     * Recalculate employer contributions for an existing payroll
     */
    public function recalculateEmployerContributions(Payroll $payroll)
    {
        $totals = [
            'totalNhifEmployer' => 0,
            'totalPensionEmployer' => 0,
        ];

        $payrollEmployees = PayrollEmployee::where('payroll_id', $payroll->id)
            ->with('employee')
            ->get();

        foreach ($payrollEmployees as $payrollEmployee) {
            if (!$payrollEmployee->employee) {
                continue;
            }

            try {
                // Use PayrollCalculationService directly to get employer contributions
                $result = $this->payrollCalculationService->calculateEmployeePayroll(
                    $payrollEmployee->employee,
                    $payroll->year,
                    $payroll->month,
                    $payroll->company_id
                );

                if (isset($result['employer_contributions'])) {
                    $empContrib = $result['employer_contributions'];
                    $totals['totalNhifEmployer'] += $empContrib['nhif_employer'] ?? 0;
                    $totals['totalPensionEmployer'] += $empContrib['pension_employer'] ?? 0;
                }
            } catch (\Exception $e) {
                \Log::error('Error recalculating employer contributions for employee ' . $payrollEmployee->employee_id . ': ' . $e->getMessage());
                continue;
            }
        }

        // Update payroll totals
        $payroll->update([
            'total_nhif_employer' => $totals['totalNhifEmployer'],
            'total_pension_employer' => $totals['totalPensionEmployer'],
        ]);

        return $totals;
    }

    /**
     * Show audit logs for a payroll
     */
    public function auditLogs(Payroll $payroll)
    {
        $auditLogs = $payroll->auditLogs()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('hr-payroll.payrolls.audit-logs', compact('payroll', 'auditLogs'));
    }

    /**
     * Show reversal form
     */
    public function showReverseForm(Payroll $payroll)
    {
        if (!$payroll->canBeReversed()) {
            return redirect()->route('hr.payrolls.show', $payroll->hash_id)
                ->with('error', 'This payroll cannot be reversed.');
        }

        return view('hr-payroll.payrolls.reverse', compact('payroll'));
    }
}
