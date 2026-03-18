<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\TrainingAttendance;
use App\Models\Hr\TrainingProgram;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TrainingAttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $attendance = TrainingAttendance::whereHas('employee', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with(['employee', 'program'])
            ->orderBy('created_at', 'desc');

            return DataTables::of($attendance)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($attendance) {
                    return $attendance->employee->full_name;
                })
                ->addColumn('employee_number', function ($attendance) {
                    return $attendance->employee->employee_number;
                })
                ->addColumn('program_name', function ($attendance) {
                    return $attendance->program->program_name;
                })
                ->addColumn('program_code', function ($attendance) {
                    return $attendance->program->program_code;
                })
                ->addColumn('completion_date_display', function ($attendance) {
                    return $attendance->completion_date ? $attendance->completion_date->format('d M Y') : '-';
                })
                ->addColumn('evaluation_score_display', function ($attendance) {
                    return $attendance->evaluation_score ? number_format($attendance->evaluation_score, 2) : '-';
                })
                ->addColumn('certification_badge', function ($attendance) {
                    if ($attendance->certification_received) {
                        return '<span class="badge bg-success"><i class="bx bx-check"></i> Yes</span>';
                    }
                    return '<span class="badge bg-secondary">No</span>';
                })
                ->addColumn('status_badge', function ($attendance) {
                    $badges = [
                        'registered' => 'secondary',
                        'attended' => 'info',
                        'completed' => 'success',
                        'absent' => 'danger',
                    ];
                    $badge = $badges[$attendance->attendance_status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($attendance->attendance_status) . '</span>';
                })
                ->addColumn('action', function ($attendance) {
                    $editBtn = '<a href="' . route('hr.training-attendance.edit', $attendance->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $attendance->id . '"><i class="bx bx-trash"></i></button>';
                    return $editBtn . $deleteBtn;
                })
                ->rawColumns(['certification_badge', 'status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.training.attendance.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();
        
        $programs = TrainingProgram::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('program_name')
            ->get();

        $programId = $request->get('program_id');
        $employeeId = $request->get('employee_id');

        return view('hr-payroll.training.attendance.create', compact(
            'employees', 'programs', 'programId', 'employeeId'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:hr_training_programs,id',
            'employee_id' => 'required|exists:hr_employees,id',
            'attendance_status' => 'required|in:registered,attended,completed,absent',
            'completion_date' => 'nullable|date',
            'certification_received' => 'boolean',
            'evaluation_score' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            // Verify employee belongs to company
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($validated['employee_id']);

            // Check if attendance already exists
            $existingAttendance = TrainingAttendance::where('program_id', $validated['program_id'])
                ->where('employee_id', $validated['employee_id'])
                ->first();

            if ($existingAttendance) {
                return back()->withInput()->withErrors([
                    'error' => 'Training attendance already exists for this employee and program.'
                ]);
            }

            TrainingAttendance::create($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Training attendance created successfully.'
                ]);
            }

            return redirect()->route('hr.training-attendance.index')
                ->with('success', 'Training attendance created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create training attendance: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TrainingAttendance $trainingAttendance)
    {
        if ($trainingAttendance->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();
        
        $programs = TrainingProgram::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('program_name')
            ->get();

        return view('hr-payroll.training.attendance.edit', compact(
            'trainingAttendance', 'employees', 'programs'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TrainingAttendance $trainingAttendance)
    {
        if ($trainingAttendance->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'program_id' => 'required|exists:hr_training_programs,id',
            'employee_id' => 'required|exists:hr_employees,id',
            'attendance_status' => 'required|in:registered,attended,completed,absent',
            'completion_date' => 'nullable|date',
            'certification_received' => 'boolean',
            'evaluation_score' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            // Verify employee belongs to company
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($validated['employee_id']);

            // Check if attendance already exists for different record
            $existingAttendance = TrainingAttendance::where('program_id', $validated['program_id'])
                ->where('employee_id', $validated['employee_id'])
                ->where('id', '!=', $trainingAttendance->id)
                ->first();

            if ($existingAttendance) {
                return back()->withInput()->withErrors([
                    'error' => 'Training attendance already exists for this employee and program.'
                ]);
            }

            $trainingAttendance->update($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Training attendance updated successfully.'
                ]);
            }

            return redirect()->route('hr.training-attendance.index')
                ->with('success', 'Training attendance updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update training attendance: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TrainingAttendance $trainingAttendance)
    {
        if ($trainingAttendance->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            $trainingAttendance->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Training attendance deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete training attendance: ' . $e->getMessage()
            ], 500);
        }
    }
}
