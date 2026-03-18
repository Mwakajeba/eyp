<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Services\Leave\BalanceService;
use Illuminate\Http\Request;

class LeaveBalanceController extends Controller
{
    public function __construct(
        protected BalanceService $balanceService
    ) {}

    /**
     * Display leave balances for all employees
     */
    public function index(Request $request)
    {
        $employees = Employee::where('company_id', current_company_id())
            ->where('status', 'active')
            ->with(['user'])
            ->orderBy('first_name')
            ->paginate(20);

        return view('hr-payroll.leave.balances.index', compact('employees'));
    }

    /**
     * Display leave balance for a specific employee
     */
    public function show(Employee $employee)
    {
        $balances = $this->balanceService->getBalanceSummary($employee);

        return view('hr-payroll.leave.balances.show', compact('employee', 'balances'));
    }

    /**
     * Show form for adjusting leave balance
     */
    public function edit(Employee $employee)
    {
        $this->authorize('update', \App\Models\Hr\LeaveBalance::class);

        $balances = $this->balanceService->getBalanceSummary($employee);

        return view('hr-payroll.leave.balances.edit', compact('employee', 'balances'));
    }

    /**
     * Update leave balance (adjustment)
     */
    public function update(Request $request, Employee $employee)
    {
        $this->authorize('update', \App\Models\Hr\LeaveBalance::class);

        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'adjustment_days' => 'required|numeric',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $leaveType = \App\Models\Hr\LeaveType::findOrFail($request->leave_type_id);

            $this->balanceService->addAdjustment(
                $employee,
                $leaveType,
                $request->adjustment_days,
                $request->reason
            );

            return redirect()->route('hr.leave.balances.show', $employee->id)
                ->with('success', 'Leave balance adjusted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to adjust balance: ' . $e->getMessage());
        }
    }
}

