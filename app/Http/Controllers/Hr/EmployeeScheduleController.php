<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\EmployeeSchedule;
use App\Models\Hr\Employee;
use App\Models\Hr\WorkSchedule;
use App\Models\Hr\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EmployeeScheduleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $schedules = EmployeeSchedule::whereHas('employee', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with(['employee', 'schedule', 'shift'])
            ->orderBy('hr_employee_schedules.effective_date', 'desc');

            return DataTables::of($schedules)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($schedule) {
                    return $schedule->employee->full_name;
                })
                ->addColumn('employee_number', function ($schedule) {
                    return $schedule->employee->employee_number;
                })
                ->addColumn('schedule_name', function ($schedule) {
                    return $schedule->schedule ? $schedule->schedule->schedule_name : '-';
                })
                ->addColumn('shift_name', function ($schedule) {
                    return $schedule->shift ? $schedule->shift->shift_name : '-';
                })
                ->addColumn('date_range', function ($schedule) {
                    $start = $schedule->effective_date->format('d M Y');
                    $end = $schedule->end_date ? $schedule->end_date->format('d M Y') : 'Ongoing';
                    return $start . ' - ' . $end;
                })
                ->addColumn('status', function ($schedule) {
                    if ($schedule->isActive()) {
                        return '<span class="badge bg-success">Active</span>';
                    }
                    return '<span class="badge bg-secondary">Inactive</span>';
                })
                ->addColumn('action', function ($schedule) {
                    $editBtn = '<a href="' . route('hr.employee-schedules.edit', $schedule->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $schedule->id . '" data-name="' . $schedule->employee->full_name . '"><i class="bx bx-trash"></i></button>';
                    return $editBtn . $deleteBtn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('hr-payroll.employee-schedules.index');
    }

    public function create(Request $request)
    {
        $employeeId = $request->employee_id;
        $employee = null;
        
        // Support both old single employee_id and new employee_ids[] for backward compatibility
        if ($employeeId) {
            $employee = Employee::where('company_id', current_company_id())
                ->find($employeeId);
        }

        $employees = Employee::where('company_id', current_company_id())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $workSchedules = WorkSchedule::where('company_id', current_company_id())
            ->where('is_active', true)
            ->orderBy('schedule_name')
            ->get();

        $shifts = Shift::where('company_id', current_company_id())
            ->where('is_active', true)
            ->orderBy('shift_name')
            ->get();

        return view('hr-payroll.employee-schedules.create', compact('employee', 'employees', 'workSchedules', 'shifts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|exists:hr_employees,id',
            'schedule_id' => 'nullable|exists:hr_work_schedules,id',
            'shift_id' => 'nullable|exists:hr_shifts,id',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after:effective_date',
        ]);

        // Verify all employees belong to company
        $employeeIds = $validated['employee_ids'];
        $employees = Employee::where('company_id', current_company_id())
            ->whereIn('id', $employeeIds)
            ->get();

        if ($employees->count() !== count($employeeIds)) {
            return back()->withInput()->withErrors(['employee_ids' => 'One or more selected employees do not belong to your company.']);
        }

        DB::beginTransaction();
        try {
            $created = 0;
            $skipped = 0;

            foreach ($validated['employee_ids'] as $employeeId) {
                // Check if schedule already exists for this employee with overlapping dates
                $existing = EmployeeSchedule::where('employee_id', $employeeId)
                    ->where(function ($q) use ($validated) {
                        $q->where(function ($query) use ($validated) {
                            // Check for overlapping date ranges
                            $query->whereBetween('effective_date', [$validated['effective_date'], $validated['end_date'] ?? '9999-12-31'])
                                  ->orWhereBetween('end_date', [$validated['effective_date'], $validated['end_date'] ?? '9999-12-31'])
                                  ->orWhere(function ($q2) use ($validated) {
                                      $q2->where('effective_date', '<=', $validated['effective_date'])
                                         ->where(function ($q3) use ($validated) {
                                             $q3->whereNull('end_date')
                                                ->orWhere('end_date', '>=', $validated['effective_date']);
                                         });
                                  });
                        });
                    })
                    ->first();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                EmployeeSchedule::create([
                    'employee_id' => $employeeId,
                    'schedule_id' => $validated['schedule_id'] ?? null,
                    'shift_id' => $validated['shift_id'] ?? null,
                    'effective_date' => $validated['effective_date'],
                    'end_date' => $validated['end_date'] ?? null,
                ]);
                $created++;
            }

            DB::commit();

            $message = "Schedule assigned to {$created} employee(s)";
            if ($skipped > 0) {
                $message .= ". {$skipped} employee(s) skipped due to existing overlapping schedules.";
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }

            return redirect()->route('hr.employee-schedules.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to assign schedule: ' . $e->getMessage()]);
        }
    }

    public function edit(EmployeeSchedule $employeeSchedule)
    {
        if ($employeeSchedule->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $employees = Employee::where('company_id', current_company_id())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $workSchedules = WorkSchedule::where('company_id', current_company_id())
            ->where('is_active', true)
            ->orderBy('schedule_name')
            ->get();

        $shifts = Shift::where('company_id', current_company_id())
            ->where('is_active', true)
            ->orderBy('shift_name')
            ->get();

        return view('hr-payroll.employee-schedules.edit', compact('employeeSchedule', 'employees', 'workSchedules', 'shifts'));
    }

    public function update(Request $request, EmployeeSchedule $employeeSchedule)
    {
        if ($employeeSchedule->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'schedule_id' => 'nullable|exists:hr_work_schedules,id',
            'shift_id' => 'nullable|exists:hr_shifts,id',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after:effective_date',
        ]);

        // Verify employee belongs to company
        $employee = Employee::where('company_id', current_company_id())
            ->findOrFail($validated['employee_id']);

        DB::beginTransaction();
        try {
            $employeeSchedule->update($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Employee schedule updated successfully.'
                ]);
            }

            return redirect()->route('hr.employee-schedules.index')
                ->with('success', 'Employee schedule updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update schedule: ' . $e->getMessage()]);
        }
    }

    public function destroy(EmployeeSchedule $employeeSchedule)
    {
        if ($employeeSchedule->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $employeeSchedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee schedule deleted successfully.'
        ]);
    }
}

