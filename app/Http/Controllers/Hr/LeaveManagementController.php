<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\LeaveType;
use App\Services\Leave\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveManagementController extends Controller
{
    public function __construct(
        protected BalanceService $balanceService
    ) {}

    /**
     * Display the main leave management dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        // Get leave balances
        $balances = [];
        if ($employee) {
            $balances = $this->balanceService->getBalanceSummary($employee);
        }

        // Get recent requests
        $recentRequests = LeaveRequest::with(['leaveType', 'employee', 'segments'])
            ->where('company_id', current_company_id())
            ->when($employee && !$user->hasRole(['HR', 'Admin']), function ($q) use ($employee) {
                $q->where('employee_id', $employee->id);
            })
            ->latest()
            ->limit(10)
            ->get();

        // Get pending approvals count
        $pendingApprovalsCount = 0;
        if ($employee) {
            $pendingApprovalsCount = LeaveRequest::whereHas('approvals', function ($q) use ($employee) {
                $q->where('approver_id', $employee->id)
                  ->where('decision', 'pending');
            })->count();
        }

        // Get leave types
        $leaveTypes = LeaveType::where('company_id', current_company_id())
            ->where('is_active', true)
            ->get();

        return view('hr-payroll.leave.index', compact(
            'balances',
            'recentRequests',
            'pendingApprovalsCount',
            'leaveTypes'
        ));
    }
}

