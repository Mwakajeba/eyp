<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\WorkSchedule;
use App\Models\Hr\Shift;
use App\Services\Hr\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $attendance = Attendance::whereHas('employee', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with(['employee', 'schedule', 'shift'])
            ->orderBy('hr_attendance.attendance_date', 'desc');

            // Apply filters
            if ($request->has('employee_id') && $request->employee_id) {
                $attendance->where('employee_id', $request->employee_id);
            }

            if ($request->has('start_date') && $request->start_date) {
                $attendance->where('attendance_date', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date) {
                $attendance->where('attendance_date', '<=', $request->end_date);
            }

            if ($request->has('status') && $request->status) {
                $attendance->where('status', $request->status);
            }

            return DataTables::of($attendance)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($attendance) {
                    return $attendance->employee->full_name;
                })
                ->addColumn('employee_number', function ($attendance) {
                    return $attendance->employee->employee_number;
                })
                ->editColumn('attendance_date', function ($attendance) {
                    // Format date in East Africa Time (GMT+3)
                    return Carbon::parse($attendance->attendance_date)->format('d M Y');
                })
                ->addColumn('clock_in_out', function ($attendance) {
                    $clockIn = $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '-';
                    $clockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '-';
                    return $clockIn . ' / ' . $clockOut;
                })
                ->addColumn('overtime_in_out', function ($attendance) {
                    $otIn = $attendance->overtime_clock_in ? Carbon::parse($attendance->overtime_clock_in)->format('H:i') : '-';
                    $otOut = $attendance->overtime_clock_out ? Carbon::parse($attendance->overtime_clock_out)->format('H:i') : '-';
                    if ($otIn === '-' && $otOut === '-') {
                        return '-';
                    }
                    return $otIn . ' / ' . $otOut;
                })
                ->addColumn('hours', function ($attendance) {
                    // Format actual hours worked (total time at work)
                    $actualHours = $attendance->actual_hours ?? 0;
                    $actualH = floor($actualHours);
                    $actualM = round(($actualHours - $actualH) * 60);
                    $actualFormatted = $actualH . 'h ' . $actualM . 'm';

                    // Format overtime hours
                    $otHours = $attendance->overtime_hours ?? 0;
                    if ($otHours > 0) {
                        $otH = floor($otHours);
                        $otM = round(($otHours - $otH) * 60);
                        $otFormatted = '<span class="text-success">+' . $otH . 'h ' . $otM . 'm OT</span>';
                        return $actualFormatted . '<br>' . $otFormatted;
                    }

                    return $actualFormatted;
                })
                ->addColumn('status_badge', function ($attendance) {
                    $badges = [
                        'present' => 'success',
                        'absent' => 'danger',
                        'late' => 'warning',
                        'early_exit' => 'warning',
                        'on_leave' => 'info',
                    ];
                    $badge = $badges[$attendance->status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst(str_replace('_', ' ', $attendance->status)) . '</span>';
                })
                ->addColumn('approval_badge', function ($attendance) {
                    if ($attendance->is_approved) {
                        return '<span class="badge bg-success">Approved</span>';
                    }
                    return '<span class="badge bg-warning">Pending</span>';
                })
                ->addColumn('action', function ($attendance) {
                    if (!$attendance->is_approved) {
                        return '<button class="btn btn-sm btn-outline-success approve-btn" data-id="' . $attendance->id . '"><i class="bx bx-check"></i> Approve</button>';
                    }
                    return '<span class="text-muted">-</span>';
                })
                ->rawColumns(['hours', 'status_badge', 'approval_badge', 'action'])
                ->make(true);
        }

        $employees = Employee::where('company_id', current_company_id())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('hr-payroll.attendance.index', compact('employees'));
    }

    public function create(Request $request)
    {
        $employeeId = $request->employee_id;
        $date = $request->date ?? now()->format('Y-m-d');
        
        $employee = null;
        if ($employeeId) {
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($employeeId);
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

        return view('hr-payroll.attendance.create', compact('employee', 'employees', 'workSchedules', 'shifts', 'date'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'attendance_date' => 'required|date',
            'schedule_id' => 'nullable|exists:hr_work_schedules,id',
            'shift_id' => 'nullable|exists:hr_shifts,id',
            'clock_in' => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i|after:clock_in',
            'expected_hours' => 'nullable|numeric|min:0|max:24',
            'actual_hours' => 'nullable|numeric|min:0|max:24',
            'normal_hours' => 'nullable|numeric|min:0|max:24',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
            'late_minutes' => 'nullable|integer|min:0',
            'early_exit_minutes' => 'nullable|integer|min:0',
            'status' => 'required|in:present,absent,late,early_exit,on_leave',
            'exception_type' => 'nullable|in:late,early_exit,missing_punch,absent',
            'exception_reason' => 'nullable|string',
        ]);

        // Verify employee belongs to company
        $employee = Employee::where('company_id', current_company_id())
            ->findOrFail($validated['employee_id']);

        // Check if attendance already exists for this date
        $existing = Attendance::where('employee_id', $validated['employee_id'])
            ->where('attendance_date', $validated['attendance_date'])
            ->first();

        if ($existing) {
            return back()->withInput()->withErrors(['error' => 'Attendance record already exists for this date.']);
        }

        DB::beginTransaction();
        try {
            $attendance = Attendance::create($validated);
            
            // Process attendance using service to calculate fields
            $attendance = $this->attendanceService->processAttendance($attendance);
            $attendance->save();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Attendance record created successfully.'
                ]);
            }

            return redirect()->route('hr.attendance.index')
                ->with('success', 'Attendance record created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create attendance record: ' . $e->getMessage()]);
        }
    }

    public function edit(Attendance $attendance)
    {
        if ($attendance->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $workSchedules = WorkSchedule::where('company_id', current_company_id())
            ->where('is_active', true)
            ->orderBy('schedule_name')
            ->get();

        $shifts = Shift::where('company_id', current_company_id())
            ->where('is_active', true)
            ->orderBy('shift_name')
            ->get();

        return view('hr-payroll.attendance.edit', compact('attendance', 'workSchedules', 'shifts'));
    }

    public function update(Request $request, Attendance $attendance)
    {
        if ($attendance->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'schedule_id' => 'nullable|exists:hr_work_schedules,id',
            'shift_id' => 'nullable|exists:hr_shifts,id',
            'clock_in' => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i|after:clock_in',
            'expected_hours' => 'nullable|numeric|min:0|max:24',
            'actual_hours' => 'nullable|numeric|min:0|max:24',
            'normal_hours' => 'nullable|numeric|min:0|max:24',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
            'late_minutes' => 'nullable|integer|min:0',
            'early_exit_minutes' => 'nullable|integer|min:0',
            'status' => 'required|in:present,absent,late,early_exit,on_leave',
            'exception_type' => 'nullable|in:late,early_exit,missing_punch,absent',
            'exception_reason' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $attendance->update($validated);
            
            // Re-process attendance to recalculate fields
            $attendance = $this->attendanceService->processAttendance($attendance);
            $attendance->save();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Attendance record updated successfully.'
                ]);
            }

            return redirect()->route('hr.attendance.index')
                ->with('success', 'Attendance record updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update attendance record: ' . $e->getMessage()]);
        }
    }

    public function approve(Attendance $attendance)
    {
        if ($attendance->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $attendance->update([
            'is_approved' => true,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance approved successfully.'
        ]);
    }

    public function destroy(Attendance $attendance)
    {
        if ($attendance->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance record deleted successfully.'
        ]);
    }
}

