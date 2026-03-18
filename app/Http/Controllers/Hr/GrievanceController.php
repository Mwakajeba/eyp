<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Grievance;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class GrievanceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $grievances = Grievance::whereHas('employee', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with(['employee', 'assignedToUser', 'resolvedByUser'])
            ->orderBy('created_at', 'desc');

            return DataTables::of($grievances)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($grievance) {
                    return $grievance->employee->full_name;
                })
                ->addColumn('employee_number', function ($grievance) {
                    return $grievance->employee->employee_number;
                })
                ->addColumn('complaint_type_badge', function ($grievance) {
                    $badges = [
                        'harassment' => 'danger',
                        'discrimination' => 'warning',
                        'workplace' => 'info',
                        'salary' => 'primary',
                        'other' => 'secondary',
                    ];
                    $badge = $badges[$grievance->complaint_type] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($grievance->complaint_type) . '</span>';
                })
                ->addColumn('priority_badge', function ($grievance) {
                    $badges = [
                        'low' => 'secondary',
                        'medium' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                    ];
                    $badge = $badges[$grievance->priority] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($grievance->priority) . '</span>';
                })
                ->addColumn('status_badge', function ($grievance) {
                    $badges = [
                        'open' => 'secondary',
                        'investigating' => 'primary',
                        'resolved' => 'success',
                        'closed' => 'dark',
                    ];
                    $badge = $badges[$grievance->status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($grievance->status) . '</span>';
                })
                ->addColumn('assigned_to_name', function ($grievance) {
                    return $grievance->assignedToUser->name ?? 'Unassigned';
                })
                ->addColumn('action', function ($grievance) {
                    $viewBtn = '<a href="' . route('hr.grievances.show', $grievance->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.grievances.edit', $grievance->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    return $viewBtn . $editBtn;
                })
                ->rawColumns(['complaint_type_badge', 'priority_badge', 'status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.discipline.grievances.index');
    }

    public function create(Request $request)
    {
        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $employeeId = $request->get('employee_id');

        return view('hr-payroll.discipline.grievances.create', compact('employees', 'employeeId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'complaint_type' => 'required|in:harassment,discrimination,workplace,salary,other',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:open,investigating,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($validated['employee_id']);

            // Generate grievance number
            $count = Grievance::whereHas('employee', function($q) {
                $q->where('company_id', current_company_id());
            })->count() + 1;
            $grievanceNumber = 'GRV-' . str_pad($count, 6, '0', STR_PAD_LEFT);

            Grievance::create(array_merge($validated, [
                'grievance_number' => $grievanceNumber,
            ]));

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Grievance created successfully.'
                ]);
            }

            return redirect()->route('hr.grievances.index')
                ->with('success', 'Grievance created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create grievance: ' . $e->getMessage()]);
        }
    }

    public function show(Grievance $grievance)
    {
        if ($grievance->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $grievance->load(['employee', 'assignedToUser', 'resolvedByUser']);
        return view('hr-payroll.discipline.grievances.show', compact('grievance'));
    }

    public function edit(Grievance $grievance)
    {
        if ($grievance->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        return view('hr-payroll.discipline.grievances.edit', compact('grievance', 'employees'));
    }

    public function update(Request $request, Grievance $grievance)
    {
        if ($grievance->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'complaint_type' => 'required|in:harassment,discrimination,workplace,salary,other',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:open,investigating,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
            'resolution' => 'nullable|string',
            'investigation_notes' => 'nullable|string',
            'resolved_by' => 'nullable|exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            // Handle status changes
            if ($validated['status'] === 'resolved' && $grievance->status !== 'resolved') {
                $validated['resolved_by'] = auth()->id();
                $validated['resolved_at'] = now();
            }

            $grievance->update($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Grievance updated successfully.'
                ]);
            }

            return redirect()->route('hr.grievances.index')
                ->with('success', 'Grievance updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update grievance: ' . $e->getMessage()]);
        }
    }

    public function destroy(Grievance $grievance)
    {
        if ($grievance->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            $grievance->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Grievance deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete grievance: ' . $e->getMessage()
            ], 500);
        }
    }
}
