<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\EmployeePromotion;
use App\Models\Hr\Employee;
use App\Models\Hr\JobGrade;
use App\Models\Hr\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class EmployeePromotionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $promotions = EmployeePromotion::whereHas('employee', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with(['employee', 'fromJobGrade', 'toJobGrade', 'fromPosition', 'toPosition'])
            ->orderBy('created_at', 'desc');

            return DataTables::of($promotions)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($promotion) {
                    return $promotion->employee->full_name;
                })
                ->addColumn('employee_number', function ($promotion) {
                    return $promotion->employee->employee_number;
                })
                ->addColumn('promotion_details', function ($promotion) {
                    $details = [];
                    if ($promotion->from_job_grade_id && $promotion->to_job_grade_id) {
                        $details[] = ($promotion->fromJobGrade->grade_name ?? 'N/A') . ' → ' . ($promotion->toJobGrade->grade_name ?? 'N/A');
                    }
                    if ($promotion->from_salary && $promotion->to_salary) {
                        $details[] = number_format($promotion->from_salary, 2) . ' → ' . number_format($promotion->to_salary, 2);
                    }
                    return implode('<br>', $details);
                })
                ->addColumn('increment_display', function ($promotion) {
                    if ($promotion->salary_increment) {
                        $increment = number_format($promotion->salary_increment, 2);
                        $percent = $promotion->increment_percentage ? '(' . number_format($promotion->increment_percentage, 2) . '%)' : '';
                        return $increment . ' ' . $percent;
                    }
                    return 'N/A';
                })
                ->addColumn('status_badge', function ($promotion) {
                    $badges = [
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'completed' => 'info',
                    ];
                    $badge = $badges[$promotion->status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($promotion->status) . '</span>';
                })
                ->addColumn('action', function ($promotion) {
                    $viewBtn = '<a href="' . route('hr.employee-promotions.show', $promotion->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.employee-promotions.edit', $promotion->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    return $viewBtn . $editBtn;
                })
                ->rawColumns(['promotion_details', 'increment_display', 'status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.lifecycle.employee-promotions.index');
    }

    public function create(Request $request)
    {
        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();
        
        $jobGrades = JobGrade::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('grade_code')
            ->get();
        
        $positions = Position::where('company_id', $companyId)
            ->where('status', 'approved')
            ->orderBy('title')
            ->get();

        $employeeId = $request->get('employee_id');

        return view('hr-payroll.lifecycle.employee-promotions.create', compact('employees', 'jobGrades', 'positions', 'employeeId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'from_job_grade_id' => 'nullable|exists:hr_job_grades,id',
            'to_job_grade_id' => 'required|exists:hr_job_grades,id',
            'from_position_id' => 'nullable|exists:hr_positions,id',
            'to_position_id' => 'nullable|exists:hr_positions,id',
            'from_salary' => 'nullable|numeric|min:0',
            'to_salary' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
            'promotion_reason' => 'nullable|string',
            'status' => 'required|in:pending,approved,rejected,completed',
            'retroactive_from_date' => 'nullable|date|before_or_equal:effective_date',
        ]);

        DB::beginTransaction();
        try {
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($validated['employee_id']);

            // Get current salary if not provided
            if (empty($validated['from_salary'])) {
                $validated['from_salary'] = $employee->basic_salary;
            }

            // Generate promotion number
            $count = EmployeePromotion::whereHas('employee', function($q) {
                $q->where('company_id', current_company_id());
            })->count() + 1;
            $promotionNumber = 'PRM-' . str_pad($count, 6, '0', STR_PAD_LEFT);

            $promotion = EmployeePromotion::create(array_merge($validated, [
                'promotion_number' => $promotionNumber,
                'requested_by' => auth()->id(),
            ]));

            // Calculate increment
            $promotion->calculateIncrement();
            $promotion->save();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Employee promotion created successfully.'
                ]);
            }

            return redirect()->route('hr.employee-promotions.index')
                ->with('success', 'Employee promotion created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create employee promotion: ' . $e->getMessage()]);
        }
    }

    public function show(EmployeePromotion $employeePromotion)
    {
        if ($employeePromotion->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $employeePromotion->load(['employee', 'fromJobGrade', 'toJobGrade', 'fromPosition', 'toPosition', 'requestedByUser', 'approvedByUser']);
        return view('hr-payroll.lifecycle.employee-promotions.show', compact('employeePromotion'));
    }

    public function edit(EmployeePromotion $employeePromotion)
    {
        if ($employeePromotion->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();
        
        $jobGrades = JobGrade::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('grade_code')
            ->get();
        
        $positions = Position::where('company_id', $companyId)
            ->where('status', 'approved')
            ->orderBy('title')
            ->get();

        return view('hr-payroll.lifecycle.employee-promotions.edit', compact('employeePromotion', 'employees', 'jobGrades', 'positions'));
    }

    public function update(Request $request, EmployeePromotion $employeePromotion)
    {
        if ($employeePromotion->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'from_job_grade_id' => 'nullable|exists:hr_job_grades,id',
            'to_job_grade_id' => 'required|exists:hr_job_grades,id',
            'from_position_id' => 'nullable|exists:hr_positions,id',
            'to_position_id' => 'nullable|exists:hr_positions,id',
            'from_salary' => 'nullable|numeric|min:0',
            'to_salary' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
            'promotion_reason' => 'nullable|string',
            'status' => 'required|in:pending,approved,rejected,completed',
            'approval_notes' => 'nullable|string',
            'retroactive_from_date' => 'nullable|date|before_or_equal:effective_date',
        ]);

        DB::beginTransaction();
        try {
            // Handle approval
            if ($validated['status'] === 'approved' && $employeePromotion->status !== 'approved') {
                $validated['approved_by'] = auth()->id();
                $validated['approved_at'] = now();
            }

            $employeePromotion->update($validated);
            $employeePromotion->calculateIncrement();
            $employeePromotion->save();

            // Apply salary update if approved and effective date passed
            if ($employeePromotion->status === 'approved' && $employeePromotion->isEffective() && !$employeePromotion->salary_updated) {
                $employee = $employeePromotion->employee;
                
                // Update employee salary and position
                $employee->update([
                    'basic_salary' => $employeePromotion->to_salary,
                    'position_id' => $employeePromotion->to_position_id ?? $employee->position_id,
                ]);
                
                $employeePromotion->update(['salary_updated' => true, 'status' => 'completed']);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Employee promotion updated successfully.'
                ]);
            }

            return redirect()->route('hr.employee-promotions.index')
                ->with('success', 'Employee promotion updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update employee promotion: ' . $e->getMessage()]);
        }
    }

    public function destroy(EmployeePromotion $employeePromotion)
    {
        if ($employeePromotion->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            $employeePromotion->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee promotion deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete employee promotion: ' . $e->getMessage()
            ], 500);
        }
    }
}
