<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\EmployeeSkill;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EmployeeSkillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $skills = EmployeeSkill::whereHas('employee', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with(['employee', 'verifier'])
            ->orderBy('created_at', 'desc');

            return DataTables::of($skills)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($skill) {
                    return $skill->employee->full_name;
                })
                ->addColumn('employee_number', function ($skill) {
                    return $skill->employee->employee_number;
                })
                ->addColumn('skill_level_badge', function ($skill) {
                    if (!$skill->skill_level) {
                        return '-';
                    }
                    $badges = [
                        'beginner' => 'secondary',
                        'intermediate' => 'info',
                        'advanced' => 'primary',
                        'expert' => 'success',
                    ];
                    $badge = $badges[$skill->skill_level] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($skill->skill_level) . '</span>';
                })
                ->addColumn('certification_expiry_display', function ($skill) {
                    if (!$skill->certification_expiry) {
                        return '-';
                    }
                    $expired = $skill->isCertificationExpired();
                    $expiringSoon = $skill->isCertificationExpiringSoon(30);
                    
                    $badge = $expired ? 'danger' : ($expiringSoon ? 'warning' : 'success');
                    $icon = $expired ? 'bx-error' : ($expiringSoon ? 'bx-time' : 'bx-check');
                    $text = $skill->certification_expiry->format('d M Y');
                    
                    return '<span class="badge bg-' . $badge . '"><i class="bx ' . $icon . '"></i> ' . $text . '</span>';
                })
                ->addColumn('verified_display', function ($skill) {
                    if ($skill->verified_at) {
                        return '<span class="badge bg-success"><i class="bx bx-check"></i> Verified</span>';
                    }
                    return '<span class="badge bg-secondary">Not Verified</span>';
                })
                ->addColumn('action', function ($skill) {
                    $editBtn = '<a href="' . route('hr.employee-skills.edit', $skill->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $skill->id . '"><i class="bx bx-trash"></i></button>';
                    return $editBtn . $deleteBtn;
                })
                ->rawColumns(['skill_level_badge', 'certification_expiry_display', 'verified_display', 'action'])
                ->make(true);
        }

        return view('hr-payroll.training.employee-skills.index');
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

        $employeeId = $request->get('employee_id');

        return view('hr-payroll.training.employee-skills.create', compact('employees', 'employeeId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'skill_name' => 'required|string|max:200',
            'skill_level' => 'nullable|in:beginner,intermediate,advanced,expert',
            'certification_name' => 'nullable|string|max:200',
            'certification_expiry' => 'nullable|date',
            'verified_by' => 'nullable|exists:users,id',
            'verified_at' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            // Verify employee belongs to company
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($validated['employee_id']);

            if (!empty($validated['verified_by'])) {
                $validated['verified_at'] = now();
            }

            EmployeeSkill::create($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Employee skill created successfully.'
                ]);
            }

            return redirect()->route('hr.employee-skills.index')
                ->with('success', 'Employee skill created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create employee skill: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeSkill $employeeSkill)
    {
        if ($employeeSkill->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        return view('hr-payroll.training.employee-skills.edit', compact('employeeSkill', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeSkill $employeeSkill)
    {
        if ($employeeSkill->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'skill_name' => 'required|string|max:200',
            'skill_level' => 'nullable|in:beginner,intermediate,advanced,expert',
            'certification_name' => 'nullable|string|max:200',
            'certification_expiry' => 'nullable|date',
            'verified_by' => 'nullable|exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            // Verify employee belongs to company
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($validated['employee_id']);

            // Handle verification
            if (!empty($validated['verified_by']) && !$employeeSkill->verified_at) {
                $validated['verified_at'] = now();
            } elseif (empty($validated['verified_by']) && $employeeSkill->verified_by) {
                $validated['verified_at'] = null;
            }

            $employeeSkill->update($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Employee skill updated successfully.'
                ]);
            }

            return redirect()->route('hr.employee-skills.index')
                ->with('success', 'Employee skill updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update employee skill: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeSkill $employeeSkill)
    {
        if ($employeeSkill->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            $employeeSkill->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee skill deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete employee skill: ' . $e->getMessage()
            ], 500);
        }
    }
}
