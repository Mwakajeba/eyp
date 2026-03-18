<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\EmployeeTransfer;
use App\Models\Hr\Employee;
use App\Models\Hr\Department;
use App\Models\Hr\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class EmployeeTransferController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $transfers = EmployeeTransfer::whereHas('employee', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with(['employee', 'fromDepartment', 'toDepartment', 'fromPosition', 'toPosition'])
            ->orderBy('created_at', 'desc');

            return DataTables::of($transfers)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($transfer) {
                    return $transfer->employee->full_name;
                })
                ->addColumn('employee_number', function ($transfer) {
                    return $transfer->employee->employee_number;
                })
                ->addColumn('transfer_details', function ($transfer) {
                    $details = [];
                    if ($transfer->from_department_id && $transfer->to_department_id) {
                        $details[] = $transfer->fromDepartment->name . ' → ' . $transfer->toDepartment->name;
                    }
                    if ($transfer->from_position_id && $transfer->to_position_id) {
                        $details[] = $transfer->fromPosition->name . ' → ' . $transfer->toPosition->name;
                    }
                    return implode('<br>', $details);
                })
                ->addColumn('transfer_type_badge', function ($transfer) {
                    $badges = [
                        'department' => 'primary',
                        'branch' => 'info',
                        'location' => 'warning',
                        'position' => 'success',
                    ];
                    $badge = $badges[$transfer->transfer_type] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($transfer->transfer_type) . '</span>';
                })
                ->addColumn('status_badge', function ($transfer) {
                    $badges = [
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'completed' => 'info',
                    ];
                    $badge = $badges[$transfer->status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($transfer->status) . '</span>';
                })
                ->addColumn('action', function ($transfer) {
                    $viewBtn = '<a href="' . route('hr.employee-transfers.show', $transfer->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.employee-transfers.edit', $transfer->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    return $viewBtn . $editBtn;
                })
                ->rawColumns(['transfer_details', 'transfer_type_badge', 'status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.lifecycle.employee-transfers.index');
    }

    public function create(Request $request)
    {
        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();
        
        $departments = Department::where('company_id', $companyId)
            ->orderBy('name')
            ->get();
        
        $positions = Position::where('company_id', $companyId)
            ->where('status', 'approved')
            ->orderBy('title')
            ->get();

        $employeeId = $request->get('employee_id');

        return view('hr-payroll.lifecycle.employee-transfers.create', compact('employees', 'departments', 'positions', 'employeeId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'transfer_type' => 'required|in:department,branch,location,position',
            'from_department_id' => 'nullable|exists:hr_departments,id',
            'to_department_id' => 'nullable|required_if:transfer_type,department|exists:hr_departments,id',
            'from_position_id' => 'nullable|exists:hr_positions,id',
            'to_position_id' => 'nullable|exists:hr_positions,id',
            'effective_date' => 'required|date',
            'transfer_reason' => 'nullable|string',
            'transfer_allowance' => 'nullable|numeric|min:0',
            'status' => 'required|in:pending,approved,rejected,completed',
        ]);

        DB::beginTransaction();
        try {
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($validated['employee_id']);

            // Generate transfer number
            $count = EmployeeTransfer::whereHas('employee', function($q) {
                $q->where('company_id', current_company_id());
            })->count() + 1;
            $transferNumber = 'TRF-' . str_pad($count, 6, '0', STR_PAD_LEFT);

            EmployeeTransfer::create(array_merge($validated, [
                'transfer_number' => $transferNumber,
                'requested_by' => auth()->id(),
            ]));

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Employee transfer created successfully.'
                ]);
            }

            return redirect()->route('hr.employee-transfers.index')
                ->with('success', 'Employee transfer created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create employee transfer: ' . $e->getMessage()]);
        }
    }

    public function show(EmployeeTransfer $employeeTransfer)
    {
        if ($employeeTransfer->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $employeeTransfer->load(['employee', 'fromDepartment', 'toDepartment', 'fromPosition', 'toPosition', 'fromBranch', 'toBranch', 'requestedByUser', 'approvedByUser']);
        return view('hr-payroll.lifecycle.employee-transfers.show', compact('employeeTransfer'));
    }

    public function edit(EmployeeTransfer $employeeTransfer)
    {
        if ($employeeTransfer->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();
        
        $departments = Department::where('company_id', $companyId)
            ->orderBy('name')
            ->get();
        
        $positions = Position::where('company_id', $companyId)
            ->where('status', 'approved')
            ->orderBy('title')
            ->get();

        return view('hr-payroll.lifecycle.employee-transfers.edit', compact('employeeTransfer', 'employees', 'departments', 'positions'));
    }

    public function update(Request $request, EmployeeTransfer $employeeTransfer)
    {
        if ($employeeTransfer->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'transfer_type' => 'required|in:department,branch,location,position',
            'from_department_id' => 'nullable|exists:hr_departments,id',
            'to_department_id' => 'nullable|required_if:transfer_type,department|exists:hr_departments,id',
            'from_position_id' => 'nullable|exists:hr_positions,id',
            'to_position_id' => 'nullable|exists:hr_positions,id',
            'effective_date' => 'required|date',
            'transfer_reason' => 'nullable|string',
            'transfer_allowance' => 'nullable|numeric|min:0',
            'status' => 'required|in:pending,approved,rejected,completed',
            'approval_notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Handle approval
            if ($validated['status'] === 'approved' && $employeeTransfer->status !== 'approved') {
                $validated['approved_by'] = auth()->id();
                $validated['approved_at'] = now();
            }

            $employeeTransfer->update($validated);

            // Execute transfer if approved and effective date passed
            if ($employeeTransfer->status === 'approved' && $employeeTransfer->isEffective() && !$employeeTransfer->cost_center_updated) {
                $employee = $employeeTransfer->employee;
                
                // Update employee department/position
                if ($employeeTransfer->to_department_id) {
                    $employee->update(['department_id' => $employeeTransfer->to_department_id]);
                }
                if ($employeeTransfer->to_position_id) {
                    $employee->update(['position_id' => $employeeTransfer->to_position_id]);
                    // Create position assignment record if needed
                }
                
                $employeeTransfer->update(['cost_center_updated' => true, 'status' => 'completed']);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Employee transfer updated successfully.'
                ]);
            }

            return redirect()->route('hr.employee-transfers.index')
                ->with('success', 'Employee transfer updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update employee transfer: ' . $e->getMessage()]);
        }
    }

    public function destroy(EmployeeTransfer $employeeTransfer)
    {
        if ($employeeTransfer->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            $employeeTransfer->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee transfer deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete employee transfer: ' . $e->getMessage()
            ], 500);
        }
    }
}
