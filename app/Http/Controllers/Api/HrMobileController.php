<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\LeaveType;
use App\Models\Hr\Attendance;
use App\Models\Hr\Department;
use App\Models\PayrollEmployee;
use App\Models\ImprestRequest;
use App\Models\ImprestItem;
use App\Models\ImprestApproval;
use App\Models\StoreRequisition;
use App\Models\StoreRequisitionItem;
use App\Models\StoreRequisitionApproval;
use App\Models\ChartAccount;
use App\Models\Inventory\Item;
use App\Models\Hr\HrNotification;
use App\Models\Retirement;
use App\Models\RetirementItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HrMobileController extends Controller
{
    /**
     * Get employee dashboard data
     */
    public function dashboard(): JsonResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found',
            ], 404);
        }

        // Calculate profile completeness
        $profileCompleteness = $this->calculateProfileCompleteness($employee);

        // Get leave balances
        $leaveBalances = LeaveBalance::where('employee_id', $employee->id)
            ->with('leaveType:id,name,code')
            ->get()
            ->map(function ($balance) {
                return [
                    'leave_type' => $balance->leaveType->name ?? 'N/A',
                    'balance' => (float) $balance->balance,
                    'used' => (float) $balance->used,
                    'entitlement' => (float) $balance->entitlement,
                ];
            });

        // Get pending requests count
        $pendingRequests = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['draft', 'pending_manager', 'pending_hr'])
            ->count();

        // Get latest payslip
        $latestPayslip = PayrollEmployee::where('employee_id', $employee->id)
            ->orderBy('payroll_date', 'desc')
            ->first();

        $netPay = $latestPayslip ? (float) $latestPayslip->net_pay : 0;

        // Get pending approvals (for managers)
        $pendingApprovals = 0;
        if ($this->isManager($employee)) {
            $pendingApprovals = LeaveRequest::whereHas('approvals', function ($query) use ($employee) {
                $query->where('approver_id', $employee->id)
                    ->where('decision', 'pending');
            })->count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'profile_completeness' => $profileCompleteness,
                'leave_balances' => $leaveBalances,
                'total_leave_balance' => $leaveBalances->sum('balance'),
                'pending_requests' => $pendingRequests,
                'net_pay' => $netPay,
                'latest_payslip_date' => $latestPayslip ? $latestPayslip->payroll_date : null,
                'pending_approvals' => $pendingApprovals,
                'employment_status' => $employee->status ?? 'active',
            ],
        ]);
    }

    /**
     * Get leave balances
     */
    public function leaveBalances(): JsonResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found',
            ], 404);
        }

        $balances = LeaveBalance::where('employee_id', $employee->id)
            ->with('leaveType:id,name,code,is_paid,allow_half_day')
            ->get()
            ->map(function ($balance) {
                return [
                    'id' => $balance->id,
                    'leave_type_id' => $balance->leave_type_id,
                    'leave_type' => [
                        'id' => $balance->leaveType->id ?? null,
                        'name' => $balance->leaveType->name ?? 'N/A',
                        'code' => $balance->leaveType->code ?? null,
                        'is_paid' => $balance->leaveType->is_paid ?? false,
                        'allow_half_day' => $balance->leaveType->allow_half_day ?? false,
                    ],
                    'balance' => (float) $balance->balance,
                    'used' => (float) $balance->used,
                    'entitlement' => (float) $balance->entitlement,
                    'pending' => (float) $balance->pending ?? 0,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $balances,
        ]);
    }

    /**
     * Get leave requests
     */
    public function leaveRequests(Request $request): JsonResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found',
            ], 404);
        }

        $status = $request->query('status'); // all, pending, approved, rejected

        $query = LeaveRequest::where('employee_id', $employee->id)
            ->with(['leaveType:id,name,code', 'approvals'])
            ->orderBy('created_at', 'desc');

        if ($status && $status !== 'all') {
            if ($status === 'pending') {
                $query->whereIn('status', ['draft', 'pending_manager', 'pending_hr']);
            } else {
                $query->where('status', $status);
            }
        }

        $requests = $query->get()->map(function ($request) {
            return [
                'id' => $request->id,
                'request_number' => $request->request_number,
                'leave_type' => $request->leaveType->name ?? 'N/A',
                'status' => $request->status,
                'total_days' => (float) $request->total_days,
                'reason' => $request->reason,
                'requested_at' => $request->requested_at?->format('Y-m-d H:i:s'),
                'decision_at' => $request->decision_at?->format('Y-m-d H:i:s'),
                'rejection_reason' => $request->rejection_reason,
                'segments' => $request->segments->map(function ($segment) {
                    return [
                        'start_date' => $segment->start_at?->format('Y-m-d'),
                        'end_date' => $segment->end_at?->format('Y-m-d'),
                        'days' => (float) $segment->days_equivalent,
                        'granularity' => $segment->granularity,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Get available leave types for application
     */
    public function leaveTypes(): JsonResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found',
            ], 404);
        }

        $leaveTypes = LeaveType::where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->get(['id', 'name', 'code', 'is_paid', 'allow_half_day', 'allow_hourly'])
            ->map(function ($type) use ($employee) {
                $balance = LeaveBalance::where('employee_id', $employee->id)
                    ->where('leave_type_id', $type->id)
                    ->first();

                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'code' => $type->code,
                    'is_paid' => $type->is_paid,
                    'allow_half_day' => $type->allow_half_day,
                    'allow_hourly' => $type->allow_hourly,
                    'available_balance' => $balance ? (float) $balance->balance : 0,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $leaveTypes,
        ]);
    }

    /**
     * Apply for leave
     */
    public function applyLeave(Request $request): JsonResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found',
            ], 404);
        }

        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'reliever_id' => 'nullable|exists:hr_employees,id',
        ]);

        // Check leave balance
        $leaveType = LeaveType::findOrFail($request->leave_type_id);
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->first();

        if (!$balance || (float) $balance->balance <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient leave balance',
            ], 422);
        }

        // Calculate days
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        if ($totalDays > (float) $balance->balance) {
            return response()->json([
                'success' => false,
                'message' => "You only have {$balance->balance} days available",
            ], 422);
        }

        // Create leave request
        $leaveRequest = LeaveRequest::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'leave_type_id' => $request->leave_type_id,
            'request_number' => LeaveRequest::generateRequestNumber($employee->company_id),
            'status' => 'pending_manager',
            'reason' => $request->reason,
            'reliever_id' => $request->reliever_id,
            'total_days' => $totalDays,
            'requested_at' => now(),
            'meta' => [
                'requestor_timezone' => $request->header('X-Timezone', 'UTC'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        // Create leave segment
        $leaveRequest->segments()->create([
            'start_at' => $startDate,
            'end_at' => $endDate,
            'granularity' => 'daily',
            'days_equivalent' => $totalDays,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave request submitted successfully',
            'data' => [
                'id' => $leaveRequest->id,
                'request_number' => $leaveRequest->request_number,
                'status' => $leaveRequest->status,
            ],
        ], 201);
    }

    /**
     * Get attendance records
     */
    public function attendance(Request $request): JsonResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found',
            ], 404);
        }

        $month = $request->query('month', date('Y-m'));
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($record) {
                return [
                    'date' => $record->date->format('Y-m-d'),
                    'check_in' => $record->check_in?->format('H:i:s'),
                    'check_out' => $record->check_out?->format('H:i:s'),
                    'status' => $record->status,
                    'is_late' => $record->is_late,
                    'is_early' => $record->is_early,
                    'overtime_hours' => (float) ($record->overtime_hours ?? 0),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $attendance,
        ]);
    }

    /**
     * Get payslips
     */
    public function payslips(Request $request): JsonResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found',
            ], 404);
        }

        $year = $request->query('year', date('Y'));

        $payslips = PayrollEmployee::where('employee_id', $employee->id)
            ->whereYear('payroll_date', $year)
            ->orderBy('payroll_date', 'desc')
            ->get()
            ->map(function ($payslip) {
                return [
                    'id' => $payslip->id,
                    'payroll_date' => $payslip->payroll_date?->format('Y-m-d'),
                    'month' => $payslip->payroll_date?->format('F Y'),
                    'basic_salary' => (float) $payslip->basic_salary,
                    'gross_pay' => (float) $payslip->gross_pay,
                    'total_deductions' => (float) $payslip->total_deductions,
                    'net_pay' => (float) $payslip->net_pay,
                    'paye' => (float) ($payslip->paye ?? 0),
                    'nhif' => (float) ($payslip->nhif ?? 0),
                    'pension' => (float) ($payslip->pension ?? 0),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $payslips,
        ]);
    }

    /**
     * Get pending approvals (for managers)
     */
    public function pendingApprovals(): JsonResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found',
            ], 404);
        }

        if (!$this->isManager($employee)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view approvals',
            ], 403);
        }

        $approvals = LeaveRequest::whereHas('approvals', function ($query) use ($employee) {
            $query->where('approver_id', $employee->id)
                ->where('decision', 'pending');
        })
        ->with(['employee:id,first_name,last_name,employee_number', 'leaveType:id,name'])
        ->orderBy('requested_at', 'desc')
        ->get()
        ->map(function ($request) {
            return [
                'id' => $request->id,
                'request_number' => $request->request_number,
                'employee' => [
                    'name' => trim(($request->employee->first_name ?? '') . ' ' . ($request->employee->last_name ?? '')),
                    'employee_number' => $request->employee->employee_number,
                ],
                'leave_type' => $request->leaveType->name ?? 'N/A',
                'total_days' => (float) $request->total_days,
                'reason' => $request->reason,
                'requested_at' => $request->requested_at?->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $approvals,
        ]);
    }

    /**
     * Helper: Calculate profile completeness
     */
    private function calculateProfileCompleteness(Employee $employee): int
    {
        $fields = [
            'first_name', 'last_name', 'email', 'phone_number',
            'date_of_birth', 'gender', 'identity_number',
            'bank_name', 'bank_account_number',
            'current_physical_location',
        ];

        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($employee->$field)) {
                $filled++;
            }
        }

        return (int) (($filled / count($fields)) * 100);
    }

    /**
     * Helper: Check if employee is a manager
     */
    private function isManager(Employee $employee): bool
    {
        // Check if employee has manager role or is a department head
        // This is a simplified check - adjust based on your system
        return $employee->user && $employee->user->hasRole('manager');
    }

    // ==================== IMPREST MANAGEMENT ====================

    /**
     * Get imprest requests list
     */
    public function imprestRequests(Request $request): JsonResponse
    {
        $user = Auth::user();
        $branchId = $request->header('X-Branch-Id', $user->branch_id);

        $status = $request->query('status'); // all, pending, approved, rejected, disbursed

        $query = ImprestRequest::where('employee_id', $user->id)
            ->with(['department:id,name', 'imprestItems.chartAccount:id,name,code'])
            ->orderBy('created_at', 'desc');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->get()->map(function ($imprest) {
            return [
                'id' => $imprest->id,
                'request_number' => $imprest->request_number,
                'purpose' => $imprest->purpose,
                'description' => $imprest->description,
                'amount_requested' => (float) $imprest->amount_requested,
                'disbursed_amount' => (float) ($imprest->disbursed_amount ?? 0),
                'date_required' => $imprest->date_required?->format('Y-m-d'),
                'status' => $imprest->status,
                'department' => $imprest->department->name ?? 'N/A',
                'created_at' => $imprest->created_at?->format('Y-m-d H:i:s'),
                'checked_at' => $imprest->checked_at?->format('Y-m-d H:i:s'),
                'approved_at' => $imprest->approved_at?->format('Y-m-d H:i:s'),
                'disbursed_at' => $imprest->disbursed_at?->format('Y-m-d H:i:s'),
                'rejection_reason' => $imprest->rejection_reason,
                'items_count' => $imprest->imprestItems->count(),
                'items' => $imprest->imprestItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'account' => $item->chartAccount->name ?? 'N/A',
                        'account_code' => $item->chartAccount->code ?? '',
                        'notes' => $item->notes,
                        'amount' => (float) $item->amount,
                    ];
                }),
            ];
        });

        // Get summary stats
        $stats = [
            'total' => ImprestRequest::where('employee_id', $user->id)->count(),
            'pending' => ImprestRequest::where('employee_id', $user->id)->where('status', 'pending')->count(),
            'approved' => ImprestRequest::where('employee_id', $user->id)->where('status', 'approved')->count(),
            'disbursed' => ImprestRequest::where('employee_id', $user->id)->where('status', 'disbursed')->count(),
            'rejected' => ImprestRequest::where('employee_id', $user->id)->where('status', 'rejected')->count(),
            'total_amount' => (float) ImprestRequest::where('employee_id', $user->id)->sum('amount_requested'),
            'total_disbursed' => (float) ImprestRequest::where('employee_id', $user->id)->sum('disbursed_amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $requests,
            'stats' => $stats,
        ]);
    }

    /**
     * Get imprest request details
     */
    public function imprestDetails($id): JsonResponse
    {
        $user = Auth::user();

        $imprest = ImprestRequest::with([
            'employee:id,name,email',
            'department:id,name',
            'creator:id,name',
            'checker:id,name',
            'approver:id,name',
            'rejecter:id,name',
            'disburser:id,name',
            'imprestItems.chartAccount:id,name,code',
            'documents',
            'liquidation',
        ])->where('employee_id', $user->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $imprest->id,
                'request_number' => $imprest->request_number,
                'purpose' => $imprest->purpose,
                'description' => $imprest->description,
                'amount_requested' => (float) $imprest->amount_requested,
                'disbursed_amount' => (float) ($imprest->disbursed_amount ?? 0),
                'date_required' => $imprest->date_required?->format('Y-m-d'),
                'status' => $imprest->status,
                'department' => $imprest->department->name ?? 'N/A',
                'employee' => $imprest->employee->name ?? 'N/A',
                'created_at' => $imprest->created_at?->format('Y-m-d H:i:s'),
                'created_by' => $imprest->creator->name ?? 'N/A',
                'checked_at' => $imprest->checked_at?->format('Y-m-d H:i:s'),
                'checked_by' => $imprest->checker->name ?? null,
                'check_comments' => $imprest->check_comments,
                'approved_at' => $imprest->approved_at?->format('Y-m-d H:i:s'),
                'approved_by' => $imprest->approver->name ?? null,
                'approval_comments' => $imprest->approval_comments,
                'rejected_at' => $imprest->rejected_at?->format('Y-m-d H:i:s'),
                'rejected_by' => $imprest->rejecter->name ?? null,
                'rejection_reason' => $imprest->rejection_reason,
                'disbursed_at' => $imprest->disbursed_at?->format('Y-m-d H:i:s'),
                'disbursed_by' => $imprest->disburser->name ?? null,
                'items' => $imprest->imprestItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'account' => $item->chartAccount->name ?? 'N/A',
                        'account_code' => $item->chartAccount->code ?? '',
                        'notes' => $item->notes,
                        'amount' => (float) $item->amount,
                    ];
                }),
                'documents' => $imprest->documents->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'name' => $doc->name,
                        'file_path' => $doc->file_path,
                        'uploaded_at' => $doc->created_at?->format('Y-m-d H:i:s'),
                    ];
                }),
                'has_liquidation' => $imprest->liquidation !== null,
            ],
        ]);
    }

    /**
     * Get expense accounts for imprest
     */
    public function expenseAccounts(): JsonResponse
    {
        $user = Auth::user();

        $accounts = ChartAccount::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->whereIn('account_type', ['expense', 'cost_of_goods_sold'])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'account_type'])
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'display' => $account->code . ' - ' . $account->name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    /**
     * Get departments
     */
    public function departments(): JsonResponse
    {
        $user = Auth::user();

        $departments = Department::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json([
            'success' => true,
            'data' => $departments,
        ]);
    }

    /**
     * Create imprest request
     */
    public function createImprest(Request $request): JsonResponse
    {
        $user = Auth::user();
        $branchId = $request->header('X-Branch-Id', $user->branch_id);

        $request->validate([
            'department_id' => 'required|exists:hr_departments,id',
            'purpose' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'date_required' => 'required|date|after_or_equal:today',
            'items' => 'required|array|min:1',
            'items.*.chart_account_id' => 'required|exists:chart_accounts,id',
            'items.*.amount' => 'required|numeric|min:1',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        // Calculate total amount
        $totalAmount = collect($request->items)->sum('amount');

        DB::beginTransaction();
        try {
            // Generate request number
            $requestNumber = $this->generateImprestNumber($user->company_id);

            $imprest = ImprestRequest::create([
                'request_number' => $requestNumber,
                'employee_id' => $user->id,
                'department_id' => $request->department_id,
                'company_id' => $user->company_id,
                'branch_id' => $branchId,
                'purpose' => $request->purpose,
                'description' => $request->description,
                'amount_requested' => $totalAmount,
                'date_required' => $request->date_required,
                'status' => 'pending',
                'created_by' => $user->id,
            ]);

            // Create items
            foreach ($request->items as $item) {
                $imprest->imprestItems()->create([
                    'chart_account_id' => $item['chart_account_id'],
                    'notes' => $item['notes'] ?? null,
                    'amount' => $item['amount'],
                    'company_id' => $user->company_id,
                    'branch_id' => $branchId,
                    'created_by' => $user->id,
                ]);
            }

            // Create approval requests if needed
            if (method_exists($imprest, 'requiresApproval') && $imprest->requiresApproval()) {
                $imprest->createApprovalRequests();
                
                // Notify approvers
                $approvals = ImprestApproval::where('imprest_request_id', $imprest->id)
                    ->where('status', 'pending')
                    ->with('approver')
                    ->get();
                
                foreach ($approvals as $approval) {
                    if ($approval->approver) {
                        $this->createNotification($approval->approver_id, 'approval_requested', [
                            'type' => 'imprest',
                            'request_id' => $imprest->id,
                            'request_number' => $imprest->request_number,
                            'approval_id' => $approval->id,
                            'message' => "New imprest request {$imprest->request_number} requires your approval (Level {$approval->approval_level}).",
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Imprest request created successfully',
                'data' => [
                    'id' => $imprest->id,
                    'request_number' => $imprest->request_number,
                    'amount' => (float) $imprest->amount_requested,
                    'status' => $imprest->status,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create imprest request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate imprest request number
     */
    private function generateImprestNumber($companyId): string
    {
        $prefix = 'IMP-' . date('Y') . '-';
        $lastRequest = ImprestRequest::where('company_id', $companyId)
            ->where('request_number', 'like', $prefix . '%')
            ->orderBy('request_number', 'desc')
            ->first();

        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->request_number, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // ==================== STORE REQUISITION ====================

    /**
     * Get store requisitions list
     */
    public function storeRequisitions(Request $request): JsonResponse
    {
        $user = Auth::user();
        $branchId = $request->header('X-Branch-Id', $user->branch_id);

        $status = $request->query('status'); // all, pending, approved, rejected, issued

        $query = StoreRequisition::where('requested_by', $user->id)
            ->with(['department:id,name', 'items.product:id,name,code,unit_of_measure'])
            ->orderBy('created_at', 'desc');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $requisitions = $query->get()->map(function ($req) {
            return [
                'id' => $req->id,
                'requisition_number' => $req->requisition_number,
                'purpose' => $req->purpose,
                'notes' => $req->notes,
                'required_date' => $req->required_date?->format('Y-m-d'),
                'status' => $req->status,
                'priority' => $req->priority ?? 'normal',
                'department' => $req->department->name ?? 'N/A',
                'created_at' => $req->created_at?->format('Y-m-d H:i:s'),
                'approved_at' => $req->approved_at?->format('Y-m-d H:i:s'),
                'rejected_at' => $req->rejected_at?->format('Y-m-d H:i:s'),
                'rejection_reason' => $req->rejection_reason,
                'items_count' => $req->items->count(),
                'total_items_qty' => $req->items->sum('quantity_requested'),
                'items' => $req->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product' => $item->product->name ?? 'N/A',
                        'product_code' => $item->product->code ?? '',
                        'unit' => $item->unit_of_measure ?? $item->product->unit_of_measure ?? 'pcs',
                        'quantity_requested' => (float) $item->quantity_requested,
                        'quantity_approved' => (float) ($item->quantity_approved ?? 0),
                        'quantity_issued' => (float) ($item->quantity_issued ?? 0),
                        'notes' => $item->item_notes,
                        'status' => $item->status ?? 'pending',
                    ];
                }),
            ];
        });

        // Get summary stats
        $stats = [
            'total' => StoreRequisition::where('requested_by', $user->id)->count(),
            'pending' => StoreRequisition::where('requested_by', $user->id)->where('status', 'pending')->count(),
            'approved' => StoreRequisition::where('requested_by', $user->id)->where('status', 'approved')->count(),
            'issued' => StoreRequisition::where('requested_by', $user->id)->whereIn('status', ['partially_issued', 'fully_issued'])->count(),
            'rejected' => StoreRequisition::where('requested_by', $user->id)->where('status', 'rejected')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $requisitions,
            'stats' => $stats,
        ]);
    }

    /**
     * Get store requisition details
     */
    public function storeRequisitionDetails($id): JsonResponse
    {
        $user = Auth::user();

        $requisition = StoreRequisition::with([
            'requestedBy:id,name,email',
            'department:id,name',
            'approvedBy:id,name',
            'rejectedBy:id,name',
            'items.product:id,name,code,unit_of_measure',
            'approvals.approver:id,name',
        ])->where('requested_by', $user->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $requisition->id,
                'requisition_number' => $requisition->requisition_number,
                'purpose' => $requisition->purpose,
                'notes' => $requisition->notes,
                'required_date' => $requisition->required_date?->format('Y-m-d'),
                'status' => $requisition->status,
                'priority' => $requisition->priority ?? 'normal',
                'department' => $requisition->department->name ?? 'N/A',
                'requested_by' => $requisition->requestedBy->name ?? 'N/A',
                'created_at' => $requisition->created_at?->format('Y-m-d H:i:s'),
                'approved_at' => $requisition->approved_at?->format('Y-m-d H:i:s'),
                'approved_by' => $requisition->approvedBy->name ?? null,
                'rejected_at' => $requisition->rejected_at?->format('Y-m-d H:i:s'),
                'rejected_by' => $requisition->rejectedBy->name ?? null,
                'rejection_reason' => $requisition->rejection_reason,
                'current_approval_level' => $requisition->current_approval_level,
                'items' => $requisition->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product' => $item->product->name ?? 'N/A',
                        'product_code' => $item->product->code ?? '',
                        'unit' => $item->unit_of_measure ?? $item->product->unit_of_measure ?? 'pcs',
                        'quantity_requested' => (float) $item->quantity_requested,
                        'quantity_approved' => (float) ($item->quantity_approved ?? 0),
                        'quantity_issued' => (float) ($item->quantity_issued ?? 0),
                        'quantity_returned' => (float) ($item->quantity_returned ?? 0),
                        'notes' => $item->item_notes,
                        'status' => $item->status ?? 'pending',
                    ];
                }),
                'approvals' => $requisition->approvals->map(function ($approval) {
                    return [
                        'level' => $approval->approval_level,
                        'approver' => $approval->approver->name ?? 'N/A',
                        'action' => $approval->action,
                        'comments' => $approval->comments,
                        'action_date' => $approval->action_date?->format('Y-m-d H:i:s'),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get inventory items for requisition
     */
    public function inventoryItems(Request $request): JsonResponse
    {
        $user = Auth::user();
        $branchId = $request->header('X-Branch-Id', $user->branch_id);
        $search = $request->query('search');

        $query = Item::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $items = $query->limit(50)->get(['id', 'code', 'name', 'unit_of_measure', 'category_id'])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'unit' => $item->unit_of_measure ?? 'pcs',
                    'display' => ($item->code ? $item->code . ' - ' : '') . $item->name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Create store requisition
     */
    public function createStoreRequisition(Request $request): JsonResponse
    {
        $user = Auth::user();
        $branchId = $request->header('X-Branch-Id', $user->branch_id);

        $request->validate([
            'department_id' => 'nullable|exists:hr_departments,id',
            'purpose' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'required_date' => 'required|date|after_or_equal:today',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity_requested' => 'required|numeric|min:0.01',
            'items.*.item_notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            // Generate requisition number
            $requisitionNumber = $this->generateRequisitionNumber($user->company_id, $branchId);

            // Get employee department if not provided
            $employee = Employee::where('user_id', $user->id)->first();
            $departmentId = $request->department_id ?? ($employee ? $employee->department_id : null);

            $requisition = StoreRequisition::create([
                'requisition_number' => $requisitionNumber,
                'company_id' => $user->company_id,
                'branch_id' => $branchId,
                'department_id' => $departmentId,
                'requested_by' => $user->id,
                'purpose' => $request->purpose,
                'notes' => $request->notes,
                'required_date' => $request->required_date,
                'priority' => $request->priority ?? 'normal',
                'status' => 'pending',
                'current_approval_level' => 1,
                'submitted_at' => now(),
            ]);

            // Create items
            foreach ($request->items as $item) {
                $inventoryItem = Item::find($item['inventory_item_id']);
                
                $requisition->items()->create([
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity_requested' => $item['quantity_requested'],
                    'unit_of_measure' => $inventoryItem->unit_of_measure ?? 'pcs',
                    'item_notes' => $item['item_notes'] ?? null,
                    'status' => 'pending',
                ]);
            }

            // Notify approvers
            $approvalSettings = \App\Models\StoreRequisitionApprovalSettings::where('company_id', $user->company_id)->first();
            if ($approvalSettings && $approvalSettings->{"level_{$requisition->current_approval_level}_enabled"}) {
                $levelUserId = $approvalSettings->{"level_{$requisition->current_approval_level}_user_id"};
                $levelRoleId = $approvalSettings->{"level_{$requisition->current_approval_level}_role_id"};
                
                $approverIds = [];
                if ($levelUserId) {
                    $approverIds[] = $levelUserId;
                }
                if ($levelRoleId) {
                    $usersWithRole = User::role($levelRoleId)->where('company_id', $user->company_id)->pluck('id');
                    $approverIds = array_merge($approverIds, $usersWithRole->toArray());
                }
                
                foreach (array_unique($approverIds) as $approverId) {
                    $this->createNotification($approverId, 'approval_requested', [
                        'type' => 'requisition',
                        'request_id' => $requisition->id,
                        'request_number' => $requisition->requisition_number,
                        'message' => "New store requisition {$requisition->requisition_number} requires your approval (Level {$requisition->current_approval_level}).",
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Store requisition created successfully',
                'data' => [
                    'id' => $requisition->id,
                    'requisition_number' => $requisition->requisition_number,
                    'items_count' => count($request->items),
                    'status' => $requisition->status,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create store requisition: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate store requisition number
     */
    private function generateRequisitionNumber($companyId, $branchId): string
    {
        $prefix = 'SR-' . date('Y') . '-';
        $lastRequisition = StoreRequisition::where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->where('requisition_number', 'like', $prefix . '%')
            ->orderBy('requisition_number', 'desc')
            ->first();

        if ($lastRequisition) {
            $lastNumber = (int) substr($lastRequisition->requisition_number, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // ==================== MANAGER APPROVALS ====================

    /**
     * Get pending approvals for the logged-in user (manager)
     */
    public function getPendingApprovals(Request $request): JsonResponse
    {
        $user = Auth::user();
        $type = $request->query('type', 'all'); // all, imprest, requisition

        $imprestApprovals = [];
        $requisitionApprovals = [];

        // Get pending imprest approvals
        if ($type === 'all' || $type === 'imprest') {
            $imprestApprovals = ImprestApproval::where('approver_id', $user->id)
                ->where('status', 'pending')
                ->with([
                    'imprestRequest' => function ($query) {
                        $query->with(['employee:id,name,email', 'department:id,name']);
                    }
                ])
                ->get()
                ->map(function ($approval) {
                    $imprest = $approval->imprestRequest;
                    return [
                        'id' => $approval->id,
                        'approval_id' => $approval->id,
                        'type' => 'imprest',
                        'request_id' => $imprest->id,
                        'request_number' => $imprest->request_number,
                        'employee' => [
                            'name' => $imprest->employee->name ?? 'N/A',
                            'email' => $imprest->employee->email ?? null,
                        ],
                        'department' => $imprest->department->name ?? 'N/A',
                        'purpose' => $imprest->purpose,
                        'amount' => (float) $imprest->amount_requested,
                        'approval_level' => $approval->approval_level,
                        'date_required' => $imprest->date_required?->format('Y-m-d'),
                        'created_at' => $imprest->created_at?->format('Y-m-d H:i:s'),
                    ];
                });
        }

        // Get pending store requisition approvals
        if ($type === 'all' || $type === 'requisition') {
            $requisitionApprovals = StoreRequisition::where('status', 'pending')
                ->where('current_approval_level', '>', 0)
                ->with(['requestedBy:id,name,email', 'department:id,name', 'items'])
                ->get()
                ->filter(function ($requisition) use ($user) {
                    return $this->canUserApproveRequisition($requisition, $user);
                })
                ->map(function ($requisition) {
                    return [
                        'id' => $requisition->id,
                        'type' => 'requisition',
                        'request_id' => $requisition->id,
                        'request_number' => $requisition->requisition_number,
                        'employee' => [
                            'name' => $requisition->requestedBy->name ?? 'N/A',
                            'email' => $requisition->requestedBy->email ?? null,
                        ],
                        'department' => $requisition->department->name ?? 'N/A',
                        'purpose' => $requisition->purpose,
                        'items_count' => $requisition->items->count(),
                        'priority' => $requisition->priority ?? 'normal',
                        'approval_level' => $requisition->current_approval_level,
                        'required_date' => $requisition->required_date?->format('Y-m-d'),
                        'created_at' => $requisition->created_at?->format('Y-m-d H:i:s'),
                    ];
                });
        }

        $allApprovals = $imprestApprovals->concat($requisitionApprovals)->sortByDesc('created_at')->values();

        return response()->json([
            'success' => true,
            'data' => $allApprovals,
            'stats' => [
                'total' => $allApprovals->count(),
                'imprest' => $imprestApprovals->count(),
                'requisition' => $requisitionApprovals->count(),
            ],
        ]);
    }

    /**
     * Approve imprest request
     */
    public function approveImprest(Request $request, $approvalId): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'comments' => 'nullable|string|max:500',
        ]);

        $approval = ImprestApproval::with('imprestRequest')->findOrFail($approvalId);

        // Check authorization
        if ($approval->approver_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to approve this request.',
            ], 403);
        }

        if (!$approval->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This approval has already been processed.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Approve
            $approval->approve($request->comments);

            $imprestRequest = $approval->imprestRequest;

            // Check if fully approved
            if ($imprestRequest->isFullyApproved()) {
                $imprestRequest->update([
                    'status' => 'approved',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'approval_comments' => $request->comments ?? 'Multi-level approval completed',
                ]);

                // Notify requester
                $this->createNotification($imprestRequest->employee_id, 'imprest_approved', [
                    'type' => 'imprest',
                    'request_id' => $imprestRequest->id,
                    'request_number' => $imprestRequest->request_number,
                    'message' => "Your imprest request {$imprestRequest->request_number} has been approved and is ready for disbursement.",
                ]);

                $message = 'Request approved. All approval levels completed - ready for disbursement.';
            } else {
                // Notify requester
                $this->createNotification($imprestRequest->employee_id, 'imprest_partially_approved', [
                    'type' => 'imprest',
                    'request_id' => $imprestRequest->id,
                    'request_number' => $imprestRequest->request_number,
                    'message' => "Your imprest request {$imprestRequest->request_number} has been approved at Level {$approval->approval_level}. Waiting for remaining approvals.",
                ]);

                $message = 'Request approved for Level ' . $approval->approval_level . '. Waiting for remaining approvals.';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject imprest request
     */
    public function rejectImprest(Request $request, $approvalId): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'comments' => 'required|string|max:500',
        ]);

        $approval = ImprestApproval::with('imprestRequest')->findOrFail($approvalId);

        // Check authorization
        if ($approval->approver_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to reject this request.',
            ], 403);
        }

        if (!$approval->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This approval has already been processed.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Reject
            $approval->reject($request->comments);

            $imprestRequest = $approval->imprestRequest;
            $imprestRequest->update([
                'status' => 'rejected',
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $request->comments,
            ]);

            // Notify requester
            $this->createNotification($imprestRequest->employee_id, 'imprest_rejected', [
                'type' => 'imprest',
                'request_id' => $imprestRequest->id,
                'request_number' => $imprestRequest->request_number,
                'message' => "Your imprest request {$imprestRequest->request_number} has been rejected. Reason: {$request->comments}",
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Request rejected successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve store requisition
     */
    public function approveRequisition(Request $request, $id): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'comments' => 'nullable|string|max:500',
        ]);

        $requisition = StoreRequisition::findOrFail($id);

        if (!$this->canUserApproveRequisition($requisition, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to approve this requisition.',
            ], 403);
        }

        if (!$requisition->canBeApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'This requisition cannot be approved at this time.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $currentLevel = $requisition->current_approval_level;

            // Record approval
            StoreRequisitionApproval::create([
                'store_requisition_id' => $requisition->id,
                'approval_level' => $currentLevel,
                'approver_id' => $user->id,
                'action' => 'approved',
                'action_date' => now(),
                'comments' => $request->comments,
            ]);

            // Check if there's a next level
            $approvalSettings = \App\Models\StoreRequisitionApprovalSettings::where('company_id', $requisition->company_id)->first();
            $nextLevel = null;

            if ($approvalSettings) {
                for ($level = $currentLevel + 1; $level <= 5; $level++) {
                    if ($approvalSettings->{"level_{$level}_enabled"}) {
                        $nextLevel = $level;
                        break;
                    }
                }
            }

            if ($nextLevel) {
                // Move to next approval level
                $requisition->update(['current_approval_level' => $nextLevel]);
                $message = 'Requisition approved at Level ' . $currentLevel . '. Moved to Level ' . $nextLevel . '.';
            } else {
                // Fully approved
                $requisition->update([
                    'status' => 'approved',
                    'current_approval_level' => 0,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);

                // Notify requester
                $this->createNotification($requisition->requested_by, 'requisition_approved', [
                    'type' => 'requisition',
                    'request_id' => $requisition->id,
                    'request_number' => $requisition->requisition_number,
                    'message' => "Your store requisition {$requisition->requisition_number} has been approved and is ready for issuance.",
                ]);

                $message = 'Requisition fully approved. Ready for issuance.';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve requisition: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject store requisition
     */
    public function rejectRequisition(Request $request, $id): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'comments' => 'required|string|max:500',
        ]);

        $requisition = StoreRequisition::findOrFail($id);

        if (!$this->canUserApproveRequisition($requisition, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to reject this requisition.',
            ], 403);
        }

        if (!$requisition->canBeRejected()) {
            return response()->json([
                'success' => false,
                'message' => 'This requisition cannot be rejected at this time.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $currentLevel = $requisition->current_approval_level;

            // Record rejection
            StoreRequisitionApproval::create([
                'store_requisition_id' => $requisition->id,
                'approval_level' => $currentLevel,
                'approver_id' => $user->id,
                'action' => 'rejected',
                'action_date' => now(),
                'comments' => $request->comments,
            ]);

            // Reject requisition
            $requisition->update([
                'status' => 'rejected',
                'current_approval_level' => 0,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $request->comments,
            ]);

            // Notify requester
            $this->createNotification($requisition->requested_by, 'requisition_rejected', [
                'type' => 'requisition',
                'request_id' => $requisition->id,
                'request_number' => $requisition->requisition_number,
                'message' => "Your store requisition {$requisition->requisition_number} has been rejected. Reason: {$request->comments}",
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Requisition rejected successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject requisition: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if user can approve requisition
     */
    private function canUserApproveRequisition(StoreRequisition $requisition, $user): bool
    {
        if (!$requisition->canBeApproved()) {
            return false;
        }

        $approvalSettings = \App\Models\StoreRequisitionApprovalSettings::where('company_id', $requisition->company_id)->first();
        
        if (!$approvalSettings) {
            return false;
        }

        $currentLevel = $requisition->current_approval_level;
        
        if (!$approvalSettings->{"level_{$currentLevel}_enabled"}) {
            return false;
        }

        $levelUserId = $approvalSettings->{"level_{$currentLevel}_user_id"};
        $levelRoleId = $approvalSettings->{"level_{$currentLevel}_role_id"};
        
        if ($levelUserId && $levelUserId == $user->id) {
            return true;
        }
        
        if ($levelRoleId && $user->hasRole($levelRoleId)) {
            return true;
        }
        
        return false;
    }

    // ==================== NOTIFICATIONS ====================

    /**
     * Get notifications for the logged-in user
     */
    public function getNotifications(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = HrNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        $notifications = $query->paginate($request->get('per_page', 20));

        $notifications->getCollection()->transform(function ($notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'data' => $notification->data,
                'is_read' => $notification->is_read,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadNotificationsCount(): JsonResponse
    {
        $user = Auth::user();

        $unreadCount = HrNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $unreadCount],
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead($id): JsonResponse
    {
        $user = Auth::user();

        $notification = HrNotification::where('user_id', $user->id)
            ->findOrFail($id);

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsAsRead(): JsonResponse
    {
        $user = Auth::user();

        HrNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Create notification helper
     */
    private function createNotification($userId, $type, $data): void
    {
        try {
            $titles = [
                'imprest_approved' => 'Imprest Approved',
                'imprest_rejected' => 'Imprest Rejected',
                'imprest_partially_approved' => 'Imprest Partially Approved',
                'requisition_approved' => 'Requisition Approved',
                'requisition_rejected' => 'Requisition Rejected',
                'approval_requested' => 'Approval Required',
            ];

            HrNotification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $titles[$type] ?? 'Notification',
                'message' => $data['message'] ?? '',
                'data' => $data,
                'is_read' => false,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create notification: ' . $e->getMessage());
        }
    }

    /**
     * Get retirement requests
     */
    public function retirementRequests(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Retirement::with([
            'imprestRequest:id,request_number,purpose,disbursed_amount',
            'imprestRequest.employee:id,name',
        ])
        ->where('company_id', $user->company_id)
        ->where('submitted_by', $user->id);

        // Filter by status if provided
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $retirements = $query->orderBy('created_at', 'desc')->get();

        $stats = [
            'total' => Retirement::where('company_id', $user->company_id)
                ->where('submitted_by', $user->id)
                ->count(),
            'pending' => Retirement::where('company_id', $user->company_id)
                ->where('submitted_by', $user->id)
                ->where('status', 'pending')
                ->count(),
            'checked' => Retirement::where('company_id', $user->company_id)
                ->where('submitted_by', $user->id)
                ->where('status', 'checked')
                ->count(),
            'approved' => Retirement::where('company_id', $user->company_id)
                ->where('submitted_by', $user->id)
                ->where('status', 'approved')
                ->count(),
            'rejected' => Retirement::where('company_id', $user->company_id)
                ->where('submitted_by', $user->id)
                ->where('status', 'rejected')
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $retirements->map(function ($retirement) {
                return [
                    'id' => $retirement->id,
                    'retirement_number' => $retirement->retirement_number,
                    'imprest_request_number' => $retirement->imprestRequest->request_number ?? 'N/A',
                    'purpose' => $retirement->imprestRequest->purpose ?? 'N/A',
                    'total_amount_used' => (float) $retirement->total_amount_used,
                    'disbursed_amount' => (float) ($retirement->imprestRequest->disbursed_amount ?? 0),
                    'status' => $retirement->status,
                    'status_label' => $retirement->getStatusLabel(),
                    'submitted_at' => $retirement->submitted_at?->format('Y-m-d H:i:s'),
                    'created_at' => $retirement->created_at?->format('Y-m-d H:i:s'),
                    'is_new' => $retirement->created_at->isAfter(now()->subDays(7)),
                ];
            }),
            'stats' => $stats,
        ]);
    }

    /**
     * Get retirement details
     */
    public function retirementDetails($id): JsonResponse
    {
        $user = Auth::user();

        $retirement = Retirement::with([
            'imprestRequest.employee:id,name,email',
            'imprestRequest.department:id,name',
            'retirementItems.chartAccount:id,code,name',
            'submitter:id,name',
            'checker:id,name',
            'approver:id,name',
            'rejecter:id,name',
        ])
        ->where('company_id', $user->company_id)
        ->where('submitted_by', $user->id)
        ->findOrFail($id);

        // Build timeline
        $timeline = [];
        if ($retirement->submitted_at) {
            $timeline[] = [
                'event' => 'Submitted',
                'by' => $retirement->submitter->name ?? 'N/A',
                'at' => $retirement->submitted_at->format('Y-m-d H:i:s'),
                'comments' => null,
            ];
        }
        if ($retirement->checked_at) {
            $timeline[] = [
                'event' => 'Checked',
                'by' => $retirement->checker->name ?? 'N/A',
                'at' => $retirement->checked_at->format('Y-m-d H:i:s'),
                'comments' => $retirement->check_comments,
            ];
        }
        if ($retirement->approved_at) {
            $timeline[] = [
                'event' => 'Approved',
                'by' => $retirement->approver->name ?? 'N/A',
                'at' => $retirement->approved_at->format('Y-m-d H:i:s'),
                'comments' => $retirement->approval_comments,
            ];
        }
        if ($retirement->rejected_at) {
            $timeline[] = [
                'event' => 'Rejected',
                'by' => $retirement->rejecter->name ?? 'N/A',
                'at' => $retirement->rejected_at->format('Y-m-d H:i:s'),
                'comments' => $retirement->rejection_reason,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $retirement->id,
                'retirement_number' => $retirement->retirement_number,
                'imprest_request_id' => $retirement->imprest_request_id,
                'imprest_request_number' => $retirement->imprestRequest->request_number ?? 'N/A',
                'purpose' => $retirement->imprestRequest->purpose ?? 'N/A',
                'employee' => $retirement->imprestRequest->employee->name ?? 'N/A',
                'department' => $retirement->imprestRequest->department->name ?? 'N/A',
                'total_amount_used' => (float) $retirement->total_amount_used,
                'disbursed_amount' => (float) ($retirement->imprestRequest->disbursed_amount ?? 0),
                'remaining_balance' => (float) $retirement->remaining_balance,
                'retirement_notes' => $retirement->retirement_notes,
                'supporting_document' => $retirement->supporting_document,
                'status' => $retirement->status,
                'status_label' => $retirement->getStatusLabel(),
                'items' => $retirement->retirementItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'chart_account_id' => $item->chart_account_id,
                        'account_code' => $item->chartAccount->code ?? '',
                        'account_name' => $item->chartAccount->name ?? 'N/A',
                        'requested_amount' => (float) $item->requested_amount,
                        'actual_amount' => (float) $item->actual_amount,
                        'variance' => (float) $item->variance,
                        'description' => $item->description,
                        'notes' => $item->notes,
                    ];
                }),
                'timeline' => $timeline,
                'submitted_at' => $retirement->submitted_at?->format('Y-m-d H:i:s'),
                'created_at' => $retirement->created_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Get imprest requests eligible for retirement
     */
    public function eligibleImprestForRetirement(): JsonResponse
    {
        $user = Auth::user();

        $imprests = ImprestRequest::with([
            'employee:id,name',
            'department:id,name',
        ])
        ->where('employee_id', $user->id)
        ->where('company_id', $user->company_id)
        ->where(function ($query) {
            $query->whereNotNull('payment_id')
                  ->orWhereHas('disbursement');
        })
        ->where('status', 'disbursed')
        ->whereDoesntHave('retirement')
        ->orderBy('disbursed_at', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $imprests->map(function ($imprest) {
                return [
                    'id' => $imprest->id,
                    'request_number' => $imprest->request_number,
                    'purpose' => $imprest->purpose,
                    'department' => $imprest->department->name ?? 'N/A',
                    'disbursed_amount' => (float) ($imprest->disbursed_amount ?? 0),
                    'disbursed_at' => $imprest->disbursed_at?->format('Y-m-d'),
                    'imprest_items' => $imprest->imprestItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'chart_account_id' => $item->chartAccount->id ?? null,
                            'account_code' => $item->chartAccount->code ?? '',
                            'account_name' => $item->chartAccount->name ?? 'N/A',
                            'amount' => (float) $item->amount,
                            'notes' => $item->notes,
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Create retirement request
     */
    public function createRetirement(Request $request): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'imprest_request_id' => 'required|exists:imprest_requests,id',
            'retirement_notes' => 'nullable|string|max:2000',
            'retirement_items' => 'required|array|min:1',
            'retirement_items.*.chart_account_id' => 'required|exists:chart_accounts,id',
            'retirement_items.*.requested_amount' => 'required|numeric|min:0',
            'retirement_items.*.actual_amount' => 'required|numeric|min:0',
            'retirement_items.*.description' => 'required|string|max:500',
            'retirement_items.*.notes' => 'nullable|string|max:500',
        ]);

        $imprestRequest = ImprestRequest::findOrFail($request->imprest_request_id);

        // Check if can be retired
        if (!$imprestRequest->canBeRetired()) {
            return response()->json([
                'success' => false,
                'message' => 'This imprest request cannot be retired at this time.',
            ], 422);
        }

        // Verify ownership
        if ($imprestRequest->employee_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only retire your own imprest requests.',
            ], 403);
        }

        DB::beginTransaction();
        
        try {
            $branchId = $request->header('X-Branch-Id') ? (int) $request->header('X-Branch-Id') : ($user->branch_id ?? null);
            
            // Calculate total amount used
            $totalAmountUsed = collect($request->retirement_items)->sum('actual_amount');

            // Create retirement record
            $retirement = Retirement::create([
                'retirement_number' => Retirement::generateRetirementNumber(),
                'imprest_request_id' => $imprestRequest->id,
                'company_id' => $user->company_id,
                'branch_id' => $branchId,
                'total_amount_used' => $totalAmountUsed,
                'retirement_notes' => $request->retirement_notes,
                'status' => 'pending',
                'submitted_by' => $user->id,
                'submitted_at' => now(),
            ]);

            // Create retirement items
            foreach ($request->retirement_items as $item) {
                RetirementItem::create([
                    'retirement_id' => $retirement->id,
                    'chart_account_id' => $item['chart_account_id'],
                    'company_id' => $user->company_id,
                    'branch_id' => $branchId,
                    'requested_amount' => $item['requested_amount'],
                    'actual_amount' => $item['actual_amount'],
                    'description' => $item['description'],
                    'notes' => $item['notes'] ?? null,
                    'created_by' => $user->id,
                ]);
            }

            // Update imprest request status to liquidated
            $imprestRequest->update(['status' => 'liquidated']);

            // Create approval requests if multi-level approval is required
            if ($retirement->requiresApproval()) {
                $retirement->createApprovalRequests();
                
                // Notify approvers
                $approvalSettings = $retirement->getApprovalSettings();
                if ($approvalSettings) {
                    $requiredLevels = $retirement->getRequiredApprovalLevels();
                    foreach ($requiredLevels as $levelData) {
                        foreach ($levelData['approvers'] as $approverId) {
                            $this->createNotification($approverId, 'retirement_approval_required', [
                                'message' => "New retirement request {$retirement->retirement_number} requires your approval",
                                'retirement_id' => $retirement->id,
                                'retirement_number' => $retirement->retirement_number,
                                'amount' => $retirement->total_amount_used,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            // Notify user
            $this->createNotification($user->id, 'retirement_submitted', [
                'message' => "Retirement request {$retirement->retirement_number} submitted successfully",
                'retirement_id' => $retirement->id,
                'retirement_number' => $retirement->retirement_number,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Retirement submitted successfully and is now pending approval.',
                'data' => [
                    'id' => $retirement->id,
                    'retirement_number' => $retirement->retirement_number,
                ],
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            \Log::error('Retirement submission failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit retirement: ' . $e->getMessage()
            ], 500);
        }
    }
}

