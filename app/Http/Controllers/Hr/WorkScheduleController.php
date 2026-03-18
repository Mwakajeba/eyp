<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;

class WorkScheduleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $schedules = WorkSchedule::where('company_id', current_company_id())
                ->orderBy('schedule_code');

            return DataTables::of($schedules)
                ->addIndexColumn()
                ->addColumn('weekly_pattern_display', function ($schedule) {
                    if (!$schedule->weekly_pattern) {
                        return 'Not set';
                    }
                    $days = [];
                    $dayNames = ['monday' => 'Mon', 'tuesday' => 'Tue', 'wednesday' => 'Wed', 'thursday' => 'Thu', 'friday' => 'Fri', 'saturday' => 'Sat', 'sunday' => 'Sun'];
                    foreach ($dayNames as $key => $name) {
                        if (isset($schedule->weekly_pattern[$key]) && $schedule->weekly_pattern[$key]) {
                            $days[] = $name;
                        }
                    }
                    return !empty($days) ? implode(', ', $days) : 'None';
                })
                ->addColumn('status_badge', function ($schedule) {
                    $badge = $schedule->is_active ? 'success' : 'secondary';
                    $text = $schedule->is_active ? 'Active' : 'Inactive';
                    return '<span class="badge bg-' . $badge . '">' . $text . '</span>';
                })
                ->addColumn('action', function ($schedule) {
                    $editBtn = '<a href="' . route('hr.work-schedules.edit', $schedule->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $schedule->id . '" data-name="' . $schedule->schedule_name . '"><i class="bx bx-trash"></i></button>';
                    return $editBtn . $deleteBtn;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.work-schedules.index');
    }

    public function create()
    {
        return view('hr-payroll.work-schedules.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('hr_work_schedules')->where(function ($query) {
                    return $query->where('company_id', current_company_id());
                })
            ],
            'schedule_name' => 'required|string|max:200',
            'weekly_pattern' => 'nullable|array',
            'weekly_pattern.monday' => 'nullable|boolean',
            'weekly_pattern.tuesday' => 'nullable|boolean',
            'weekly_pattern.wednesday' => 'nullable|boolean',
            'weekly_pattern.thursday' => 'nullable|boolean',
            'weekly_pattern.friday' => 'nullable|boolean',
            'weekly_pattern.saturday' => 'nullable|boolean',
            'weekly_pattern.sunday' => 'nullable|boolean',
            'standard_daily_hours' => 'required|numeric|min:0|max:24',
            'break_duration_minutes' => 'nullable|integer|min:0',
            'overtime_eligible' => 'boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            WorkSchedule::create(array_merge($validated, [
                'company_id' => current_company_id(),
            ]));

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Work schedule created successfully.'
                ]);
            }

            return redirect()->route('hr.work-schedules.index')
                ->with('success', 'Work schedule created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create work schedule: ' . $e->getMessage()]);
        }
    }

    public function edit(WorkSchedule $workSchedule)
    {
        if ($workSchedule->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        return view('hr-payroll.work-schedules.edit', compact('workSchedule'));
    }

    public function update(Request $request, WorkSchedule $workSchedule)
    {
        if ($workSchedule->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'schedule_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('hr_work_schedules')->ignore($workSchedule->id)->where(function ($query) {
                    return $query->where('company_id', current_company_id());
                })
            ],
            'schedule_name' => 'required|string|max:200',
            'weekly_pattern' => 'nullable|array',
            'weekly_pattern.monday' => 'nullable|boolean',
            'weekly_pattern.tuesday' => 'nullable|boolean',
            'weekly_pattern.wednesday' => 'nullable|boolean',
            'weekly_pattern.thursday' => 'nullable|boolean',
            'weekly_pattern.friday' => 'nullable|boolean',
            'weekly_pattern.saturday' => 'nullable|boolean',
            'weekly_pattern.sunday' => 'nullable|boolean',
            'standard_daily_hours' => 'required|numeric|min:0|max:24',
            'break_duration_minutes' => 'nullable|integer|min:0',
            'overtime_eligible' => 'boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $workSchedule->update($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Work schedule updated successfully.'
                ]);
            }

            return redirect()->route('hr.work-schedules.index')
                ->with('success', 'Work schedule updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update work schedule: ' . $e->getMessage()]);
        }
    }

    public function destroy(WorkSchedule $workSchedule)
    {
        if ($workSchedule->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        // Check if schedule has employee assignments
        $assignmentCount = $workSchedule->employeeSchedules()->count();
        if ($assignmentCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete work schedule because it has ' . $assignmentCount . ' employee assignment(s).'
            ], 400);
        }

        $workSchedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Work schedule deleted successfully.'
        ]);
    }
}

