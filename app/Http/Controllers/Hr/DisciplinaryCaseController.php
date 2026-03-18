<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\DisciplinaryCase;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class DisciplinaryCaseController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $cases = DisciplinaryCase::whereHas('employee', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with(['employee', 'reportedByUser', 'investigatedByUser', 'resolvedByUser'])
            ->orderBy('created_at', 'desc');

            return DataTables::of($cases)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($case) {
                    return $case->employee->full_name;
                })
                ->addColumn('employee_number', function ($case) {
                    return $case->employee->employee_number;
                })
                ->addColumn('case_category_badge', function ($case) {
                    $badges = [
                        'misconduct' => 'danger',
                        'absenteeism' => 'warning',
                        'performance' => 'info',
                    ];
                    $badge = $badges[$case->case_category] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($case->case_category) . '</span>';
                })
                ->addColumn('status_badge', function ($case) {
                    $badges = [
                        'open' => 'secondary',
                        'investigating' => 'primary',
                        'resolved' => 'success',
                        'closed' => 'dark',
                    ];
                    $badge = $badges[$case->status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($case->status) . '</span>';
                })
                ->addColumn('outcome_badge', function ($case) {
                    if (!$case->outcome) {
                        return '-';
                    }
                    $badges = [
                        'verbal_warning' => 'info',
                        'written_warning' => 'warning',
                        'suspension' => 'danger',
                        'termination' => 'dark',
                    ];
                    $badge = $badges[$case->outcome] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst(str_replace('_', ' ', $case->outcome)) . '</span>';
                })
                ->addColumn('action', function ($case) {
                    $viewBtn = '<a href="' . route('hr.disciplinary-cases.show', $case->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.disciplinary-cases.edit', $case->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    return $viewBtn . $editBtn;
                })
                ->rawColumns(['case_category_badge', 'status_badge', 'outcome_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.discipline.disciplinary-cases.index');
    }

    public function create(Request $request)
    {
        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $employeeId = $request->get('employee_id');

        return view('hr-payroll.discipline.disciplinary-cases.create', compact('employees', 'employeeId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'case_category' => 'required|in:misconduct,absenteeism,performance',
            'incident_date' => 'required|date',
            'description' => 'required|string',
            'status' => 'required|in:open,investigating,resolved,closed',
        ]);

        DB::beginTransaction();
        try {
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($validated['employee_id']);

            // Generate case number
            $count = DisciplinaryCase::whereHas('employee', function($q) {
                $q->where('company_id', current_company_id());
            })->count() + 1;
            $caseNumber = 'DC-' . str_pad($count, 6, '0', STR_PAD_LEFT);

            DisciplinaryCase::create(array_merge($validated, [
                'case_number' => $caseNumber,
                'reported_by' => auth()->id(),
            ]));

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Disciplinary case created successfully.'
                ]);
            }

            return redirect()->route('hr.disciplinary-cases.index')
                ->with('success', 'Disciplinary case created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create disciplinary case: ' . $e->getMessage()]);
        }
    }

    public function show(DisciplinaryCase $disciplinaryCase)
    {
        if ($disciplinaryCase->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $disciplinaryCase->load(['employee', 'reportedByUser', 'investigatedByUser', 'resolvedByUser']);
        return view('hr-payroll.discipline.disciplinary-cases.show', compact('disciplinaryCase'));
    }

    public function edit(DisciplinaryCase $disciplinaryCase)
    {
        if ($disciplinaryCase->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        return view('hr-payroll.discipline.disciplinary-cases.edit', compact('disciplinaryCase', 'employees'));
    }

    public function update(Request $request, DisciplinaryCase $disciplinaryCase)
    {
        if ($disciplinaryCase->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'case_category' => 'required|in:misconduct,absenteeism,performance',
            'incident_date' => 'required|date',
            'description' => 'required|string',
            'status' => 'required|in:open,investigating,resolved,closed',
            'outcome' => 'nullable|in:verbal_warning,written_warning,suspension,termination',
            'outcome_date' => 'nullable|date|required_if:outcome,!=,null',
            'payroll_impact' => 'nullable|array',
            'resolution_notes' => 'nullable|string',
            'investigated_by' => 'nullable|exists:users,id',
            'resolved_by' => 'nullable|exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            // Handle status changes
            if ($validated['status'] === 'investigating' && $disciplinaryCase->status !== 'investigating') {
                $validated['investigated_by'] = auth()->id();
            }

            if ($validated['status'] === 'resolved' && $disciplinaryCase->status !== 'resolved') {
                $validated['resolved_by'] = auth()->id();
                $validated['resolved_at'] = now();
            }

            $disciplinaryCase->update($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Disciplinary case updated successfully.'
                ]);
            }

            return redirect()->route('hr.disciplinary-cases.index')
                ->with('success', 'Disciplinary case updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update disciplinary case: ' . $e->getMessage()]);
        }
    }

    public function destroy(DisciplinaryCase $disciplinaryCase)
    {
        if ($disciplinaryCase->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            $disciplinaryCase->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Disciplinary case deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete disciplinary case: ' . $e->getMessage()
            ], 500);
        }
    }
}
