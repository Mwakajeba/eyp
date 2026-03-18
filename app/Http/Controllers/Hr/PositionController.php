<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hr\Position;
use App\Models\Hr\Department;
use App\Models\Hr\JobGrade;
use App\Services\Hr\PositionService;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class PositionController extends Controller
{
    protected $positionService;

    public function __construct(PositionService $positionService)
    {
        $this->positionService = $positionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $positions = Position::query()
                ->where('company_id', current_company_id())
                ->with(['department', 'grade'])
                ->orderBy('title');

            return DataTables::of($positions)
                ->addIndexColumn()
                ->addColumn('department_name', function ($position) {
                    return $position->department ? $position->department->name : '-';
                })
                ->addColumn('grade_info', function ($position) {
                    if ($position->grade) {
                        return '<span class="badge bg-info">' . $position->grade->grade_code . '</span> ' . 
                               '<small class="text-muted">(' . $position->grade->grade_name . ')</small>';
                    }
                    return '<span class="text-muted">-</span>';
                })
                ->addColumn('salary_range', function ($position) {
                    if ($position->grade) {
                        return $position->grade->salary_range;
                    }
                    return '-';
                })
                ->addColumn('budgeted_salary', function ($position) {
                    return $position->budgeted_salary;
                })
                ->addColumn('description_display', function ($position) {
                    return $position->description ? \Str::limit($position->description, 50) : '-';
                })
                ->addColumn('action', function ($position) {
                    $editBtn = '<a href="' . route('hr.positions.edit', $position) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $position->hash_id . '" data-name="' . $position->title . '"><i class="bx bx-trash"></i></button>';
                    return $editBtn . $deleteBtn;
                })
                ->rawColumns(['action', 'grade_info'])
                ->make(true);
        }

        $branchId = current_branch_id();
        $departments = Department::where('company_id', current_company_id());
        
        if ($branchId) {
            $departments->where('branch_id', $branchId);
        } else {
            $departments->whereNull('branch_id');
        }
        
        $departments = $departments->orderBy('name')->get();
        $jobGrades = JobGrade::where('company_id', current_company_id())
            ->where('is_active', true)
            ->orderBy('grade_code')
            ->get();
        return view('hr-payroll.positions.index', compact('departments', 'jobGrades'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $branchId = current_branch_id();
        $departments = Department::where('company_id', current_company_id());
        
        if ($branchId) {
            $departments->where('branch_id', $branchId);
        } else {
            $departments->whereNull('branch_id');
        }
        
        $departments = $departments->orderBy('name')->get();
        $jobGrades = JobGrade::where('company_id', current_company_id())
            ->where('is_active', true)
            ->orderBy('grade_code')
            ->get();
        return view('hr-payroll.positions.create', compact('departments', 'jobGrades'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('hr_positions')->where(fn($q) => $q->where('company_id', current_company_id()))],
            'department_id' => ['nullable', 'exists:hr_departments,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'grade_id' => ['nullable', 'exists:hr_job_grades,id'],
            'budgeted_salary' => ['nullable', 'numeric', 'min:0'],
            'approved_headcount' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:approved,frozen,cancelled'],
            'effective_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:effective_date'],
        ]);

        // Validate budgeted_salary against grade if grade_id is provided
        if (!empty($validated['grade_id']) && !empty($validated['budgeted_salary'])) {
            $validation = $this->positionService->validateSalaryAgainstGrade(
                $validated['grade_id'],
                $validated['budgeted_salary']
            );
            
            if (!$validation['valid']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['budgeted_salary' => $validation['message']]);
            }
        }

        $position = Position::create([
            'company_id' => current_company_id(),
            'title' => $validated['title'],
            'department_id' => $validated['department_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'grade_id' => $validated['grade_id'] ?? null,
            'budgeted_salary' => $validated['budgeted_salary'] ?? null,
            'approved_headcount' => $validated['approved_headcount'] ?? 1,
            'status' => $validated['status'] ?? 'approved',
            'effective_date' => $validated['effective_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Position created successfully.'
            ]);
        }

        return redirect()->route('hr.positions.index')->with('success', 'Position created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Position $position)
    {
        // Verify position belongs to user's company
        if ($position->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access to position.');
        }
        
        $branchId = current_branch_id();
        $departments = Department::where('company_id', current_company_id());
        
        if ($branchId) {
            $departments->where('branch_id', $branchId);
        } else {
            $departments->whereNull('branch_id');
        }
        
        $departments = $departments->orderBy('name')->get();
        $jobGrades = JobGrade::where('company_id', current_company_id())
            ->where('is_active', true)
            ->orderBy('grade_code')
            ->get();
        return view('hr-payroll.positions.edit', compact('position', 'departments', 'jobGrades'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Position $position)
    {
        // Verify position belongs to user's company
        if ($position->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access to position.');
        }
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('hr_positions')->ignore($position->id)->where(fn($q) => $q->where('company_id', current_company_id()))],
            'department_id' => ['nullable', 'exists:hr_departments,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'grade_id' => ['nullable', 'exists:hr_job_grades,id'],
            'budgeted_salary' => ['nullable', 'numeric', 'min:0'],
            'approved_headcount' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:approved,frozen,cancelled'],
            'effective_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:effective_date'],
        ]);

        // Validate budgeted_salary against grade if grade_id is provided
        if (!empty($validated['grade_id']) && !empty($validated['budgeted_salary'])) {
            $validation = $this->positionService->validateSalaryAgainstGrade(
                $validated['grade_id'],
                $validated['budgeted_salary']
            );
            
            if (!$validation['valid']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['budgeted_salary' => $validation['message']]);
            }
        }

        $position->update([
            'title' => $validated['title'],
            'department_id' => $validated['department_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'grade_id' => $validated['grade_id'] ?? null,
            'budgeted_salary' => $validated['budgeted_salary'] ?? null,
            'approved_headcount' => $validated['approved_headcount'] ?? $position->approved_headcount,
            'status' => $validated['status'] ?? $position->status,
            'effective_date' => $validated['effective_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Position updated successfully.'
            ]);
        }

        return redirect()->route('hr.positions.index')->with('success', 'Position updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Position $position)
    {
        // Verify position belongs to user's company
        if ($position->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access to position.');
        }

        // Check if position has employees
        $employeeCount = $position->employees()->count();
        if ($employeeCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete position "' . $position->title . '" because it has ' . $employeeCount . ' employee(s). Please reassign employees first.'
            ], 400);
        }

        $position->delete();

        return response()->json([
            'success' => true,
            'message' => 'Position deleted successfully.'
        ]);
    }
}
