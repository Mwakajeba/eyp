<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\JobGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;

class JobGradeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $grades = JobGrade::where('company_id', current_company_id())
                ->orderBy('grade_code');

            return DataTables::of($grades)
                ->addIndexColumn()
                ->addColumn('salary_range', function ($grade) {
                    return $grade->salary_range;
                })
                ->addColumn('status_badge', function ($grade) {
                    $badge = $grade->is_active ? 'success' : 'secondary';
                    $text = $grade->is_active ? 'Active' : 'Inactive';
                    return '<span class="badge bg-' . $badge . '">' . $text . '</span>';
                })
                ->addColumn('action', function ($grade) {
                    $editBtn = '<a href="' . route('hr.job-grades.edit', $grade->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $grade->id . '" data-name="' . $grade->grade_name . '"><i class="bx bx-trash"></i></button>';
                    return $editBtn . $deleteBtn;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.job-grades.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('hr-payroll.job-grades.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'grade_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('hr_job_grades')->where(function ($query) {
                    return $query->where('company_id', current_company_id());
                })
            ],
            'grade_name' => 'required|string|max:100',
            'minimum_salary' => 'nullable|numeric|min:0',
            'midpoint_salary' => 'nullable|numeric|min:0',
            'maximum_salary' => 'nullable|numeric|min:0|gte:minimum_salary',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            JobGrade::create(array_merge($validated, [
                'company_id' => current_company_id(),
            ]));

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Job grade created successfully.'
                ]);
            }

            return redirect()->route('hr.job-grades.index')
                ->with('success', 'Job grade created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create job grade: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(JobGrade $jobGrade)
    {
        if ($jobGrade->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        return view('hr-payroll.job-grades.edit', compact('jobGrade'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, JobGrade $jobGrade)
    {
        if ($jobGrade->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'grade_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('hr_job_grades')->ignore($jobGrade->id)->where(function ($query) {
                    return $query->where('company_id', current_company_id());
                })
            ],
            'grade_name' => 'required|string|max:100',
            'minimum_salary' => 'nullable|numeric|min:0',
            'midpoint_salary' => 'nullable|numeric|min:0',
            'maximum_salary' => 'nullable|numeric|min:0|gte:minimum_salary',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $jobGrade->update($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Job grade updated successfully.'
                ]);
            }

            return redirect()->route('hr.job-grades.index')
                ->with('success', 'Job grade updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update job grade: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(JobGrade $jobGrade)
    {
        if ($jobGrade->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        // Check if grade has positions
        $positionCount = $jobGrade->positions()->count();
        if ($positionCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete job grade because it has ' . $positionCount . ' position(s) assigned.'
            ], 400);
        }

        $jobGrade->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job grade deleted successfully.'
        ]);
    }
}
