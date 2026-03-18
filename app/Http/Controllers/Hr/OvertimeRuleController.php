<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\OvertimeRule;
use App\Models\Hr\JobGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OvertimeRuleController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $rules = OvertimeRule::where('company_id', current_company_id())
                ->with('grade')
                ->orderBy('day_type')
                ->orderBy('grade_id');

            return DataTables::of($rules)
                ->addIndexColumn()
                ->addColumn('grade_name', function ($rule) {
                    return $rule->grade ? $rule->grade->grade_name : 'All Grades';
                })
                ->addColumn('day_type_display', function ($rule) {
                    return ucfirst($rule->day_type);
                })
                ->addColumn('rate_display', function ($rule) {
                    return $rule->overtime_rate . 'x';
                })
                ->addColumn('status_badge', function ($rule) {
                    $badge = $rule->is_active ? 'success' : 'secondary';
                    $text = $rule->is_active ? 'Active' : 'Inactive';
                    return '<span class="badge bg-' . $badge . '">' . $text . '</span>';
                })
                ->addColumn('action', function ($rule) {
                    $editBtn = '<a href="' . route('hr.overtime-rules.edit', $rule->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $rule->id . '" data-name="' . $rule->day_type . '"><i class="bx bx-trash"></i></button>';
                    return $editBtn . $deleteBtn;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.overtime-rules.index');
    }

    public function create()
    {
        $jobGrades = JobGrade::where('company_id', current_company_id())
            ->where('is_active', true)
            ->orderBy('grade_code')
            ->get();

        return view('hr-payroll.overtime-rules.create', compact('jobGrades'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'grade_id' => 'nullable|exists:hr_job_grades,id',
            'day_type' => 'required|in:weekday,weekend,holiday',
            'overtime_rate' => 'required|numeric|min:1|max:5',
            'max_hours_per_day' => 'nullable|numeric|min:0|max:24',
            'requires_approval' => 'boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            OvertimeRule::create(array_merge($validated, [
                'company_id' => current_company_id(),
            ]));

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Overtime rule created successfully.'
                ]);
            }

            return redirect()->route('hr.overtime-rules.index')
                ->with('success', 'Overtime rule created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create overtime rule: ' . $e->getMessage()]);
        }
    }

    public function edit(OvertimeRule $overtimeRule)
    {
        if ($overtimeRule->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $jobGrades = JobGrade::where('company_id', current_company_id())
            ->where('is_active', true)
            ->orderBy('grade_code')
            ->get();

        return view('hr-payroll.overtime-rules.edit', compact('overtimeRule', 'jobGrades'));
    }

    public function update(Request $request, OvertimeRule $overtimeRule)
    {
        if ($overtimeRule->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'grade_id' => 'nullable|exists:hr_job_grades,id',
            'day_type' => 'required|in:weekday,weekend,holiday',
            'overtime_rate' => 'required|numeric|min:1|max:5',
            'max_hours_per_day' => 'nullable|numeric|min:0|max:24',
            'requires_approval' => 'boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $overtimeRule->update($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Overtime rule updated successfully.'
                ]);
            }

            return redirect()->route('hr.overtime-rules.index')
                ->with('success', 'Overtime rule updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update overtime rule: ' . $e->getMessage()]);
        }
    }

    public function destroy(OvertimeRule $overtimeRule)
    {
        if ($overtimeRule->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $overtimeRule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Overtime rule deleted successfully.'
        ]);
    }
}

