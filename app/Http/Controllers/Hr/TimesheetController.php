<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Timesheet;
use App\Models\Hr\Employee;
use App\Models\Hr\Department;
use App\Models\TimesheetApprovalSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class TimesheetController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $timesheets = Timesheet::whereHas('employee', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with(['employee', 'department'])
            ->orderBy('timesheet_date', 'desc')
            ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('employee_id') && $request->employee_id) {
                $timesheets->where('employee_id', $request->employee_id);
            }

            if ($request->has('start_date') && $request->start_date) {
                $timesheets->where('timesheet_date', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date) {
                $timesheets->where('timesheet_date', '<=', $request->end_date);
            }

            if ($request->has('status') && $request->status) {
                $timesheets->where('status', $request->status);
            }

            if ($request->has('department_id') && $request->department_id) {
                $timesheets->where('department_id', $request->department_id);
            }

            if ($request->has('activity_type') && $request->activity_type) {
                $timesheets->where('activity_type', $request->activity_type);
            }

            return DataTables::of($timesheets)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($timesheet) {
                    return $timesheet->employee->full_name;
                })
                ->addColumn('employee_number', function ($timesheet) {
                    return $timesheet->employee->employee_number;
                })
                ->addColumn('department_name', function ($timesheet) {
                    return $timesheet->department->name ?? 'N/A';
                })
                ->addColumn('total_hours', function ($timesheet) {
                    $normal = number_format($timesheet->normal_hours, 2);
                    $ot = $timesheet->overtime_hours > 0 ? ' (+' . number_format($timesheet->overtime_hours, 2) . ' OT)' : '';
                    return $normal . $ot;
                })
                ->addColumn('activity_type_badge', function ($timesheet) {
                    $badges = [
                        'work' => 'primary',
                        'training' => 'info',
                        'meeting' => 'warning',
                        'conference' => 'success',
                        'project' => 'secondary',
                        'other' => 'dark',
                    ];
                    $badge = $badges[$timesheet->activity_type] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . $timesheet->activity_type_label . '</span>';
                })
                ->addColumn('status_badge', function ($timesheet) {
                    $badge = $timesheet->status_badge;
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($timesheet->status) . '</span>';
                })
                ->addColumn('action', function ($timesheet) {
                    $viewBtn = '<a href="' . route('hr.timesheets.show', $timesheet->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    
                    if ($timesheet->canBeEdited()) {
                        $editBtn = '<a href="' . route('hr.timesheets.edit', $timesheet->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                        return $viewBtn . $editBtn;
                    }
                    
                    return $viewBtn;
                })
                ->rawColumns(['activity_type_badge', 'status_badge', 'action'])
                ->make(true);
        }

        $employees = Employee::where('company_id', current_company_id())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $departments = Department::where('company_id', current_company_id())
            ->orderBy('name')
            ->get();

        return view('hr-payroll.timesheets.index', compact('employees', 'departments'));
    }

    public function create(Request $request)
    {
        $companyId = current_company_id();
        $employeeId = $request->get('employee_id');
        $date = $request->get('date', now()->format('Y-m-d'));

        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $departments = Department::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $employee = $employeeId ? Employee::where('company_id', $companyId)->find($employeeId) : null;

        return view('hr-payroll.timesheets.create', compact('employees', 'departments', 'employee', 'date'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'timesheet_date' => 'required|date',
            'department_id' => 'nullable|exists:hr_departments,id',
            'activity_type' => 'required|in:work,training,meeting,conference,project,other',
            'project_reference' => 'nullable|string|max:255',
            'normal_hours' => 'required|numeric|min:0|max:24',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
            'description' => 'nullable|string',
            'priorities' => 'nullable|string',
            'achievements' => 'nullable|string',
            'status' => 'required|in:draft,submitted',
        ]);

        // Verify employee belongs to company
        $employee = Employee::where('company_id', current_company_id())
            ->findOrFail($validated['employee_id']);

        // Check for duplicate timesheet for same employee and date
        $existing = Timesheet::where('employee_id', $validated['employee_id'])
            ->where('timesheet_date', $validated['timesheet_date'])
            ->where('status', '!=', Timesheet::STATUS_REJECTED)
            ->first();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['timesheet_date' => 'A timesheet already exists for this employee on this date.']);
        }

        $validated['company_id'] = current_company_id();
        
        if ($validated['status'] === Timesheet::STATUS_SUBMITTED) {
            $validated['submitted_by'] = auth()->id();
            $validated['submitted_at'] = now();
        }

        DB::beginTransaction();
        try {
            $timesheet = Timesheet::create($validated);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Timesheet created successfully.',
                    'redirect' => route('hr.timesheets.show', $timesheet->id)
                ]);
            }

            return redirect()->route('hr.timesheets.show', $timesheet->id)
                ->with('success', 'Timesheet created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create timesheet: ' . $e->getMessage()
                ], 422);
            }

            return back()->withInput()->withErrors(['error' => 'Failed to create timesheet: ' . $e->getMessage()]);
        }
    }

    public function show(Timesheet $timesheet)
    {
        // Verify timesheet belongs to company
        if ($timesheet->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $timesheet->load(['employee', 'department', 'submittedBy', 'approvedBy', 'rejectedBy']);

        return view('hr-payroll.timesheets.show', compact('timesheet'));
    }

    public function edit(Timesheet $timesheet)
    {
        // Verify timesheet belongs to company
        if ($timesheet->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!$timesheet->canBeEdited()) {
            return redirect()->route('hr.timesheets.show', $timesheet->id)
                ->with('error', 'This timesheet cannot be edited in its current status.');
        }

        $companyId = current_company_id();
        $departments = Department::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('hr-payroll.timesheets.edit', compact('timesheet', 'departments'));
    }

    public function update(Request $request, Timesheet $timesheet)
    {
        // Verify timesheet belongs to company
        if ($timesheet->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!$timesheet->canBeEdited()) {
            return redirect()->route('hr.timesheets.show', $timesheet->id)
                ->with('error', 'This timesheet cannot be edited in its current status.');
        }

        $validated = $request->validate([
            'department_id' => 'nullable|exists:hr_departments,id',
            'activity_type' => 'required|in:work,training,meeting,conference,project,other',
            'project_reference' => 'nullable|string|max:255',
            'normal_hours' => 'required|numeric|min:0|max:24',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
            'description' => 'nullable|string',
            'priorities' => 'nullable|string',
            'achievements' => 'nullable|string',
            'status' => 'required|in:draft,submitted',
        ]);

        // Check for duplicate timesheet if date is being changed
        if ($request->has('timesheet_date') && $request->timesheet_date != $timesheet->timesheet_date) {
            $existing = Timesheet::where('employee_id', $timesheet->employee_id)
                ->where('timesheet_date', $request->timesheet_date)
                ->where('id', '!=', $timesheet->id)
                ->where('status', '!=', Timesheet::STATUS_REJECTED)
                ->first();

            if ($existing) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['timesheet_date' => 'A timesheet already exists for this employee on this date.']);
            }
        }

        if ($validated['status'] === Timesheet::STATUS_SUBMITTED && $timesheet->status !== Timesheet::STATUS_SUBMITTED) {
            $validated['submitted_by'] = auth()->id();
            $validated['submitted_at'] = now();
        }

        DB::beginTransaction();
        try {
            $timesheet->update($validated);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Timesheet updated successfully.',
                    'redirect' => route('hr.timesheets.show', $timesheet->id)
                ]);
            }

            return redirect()->route('hr.timesheets.show', $timesheet->id)
                ->with('success', 'Timesheet updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update timesheet: ' . $e->getMessage()
                ], 422);
            }

            return back()->withInput()->withErrors(['error' => 'Failed to update timesheet: ' . $e->getMessage()]);
        }
    }

    public function destroy(Timesheet $timesheet)
    {
        // Verify timesheet belongs to company
        if ($timesheet->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!$timesheet->canBeEdited()) {
            return redirect()->route('hr.timesheets.index')
                ->with('error', 'This timesheet cannot be deleted in its current status.');
        }

        DB::beginTransaction();
        try {
            $timesheet->delete();

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Timesheet deleted successfully.'
                ]);
            }

            return redirect()->route('hr.timesheets.index')
                ->with('success', 'Timesheet deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete timesheet: ' . $e->getMessage()
                ], 422);
            }

            return back()->withErrors(['error' => 'Failed to delete timesheet: ' . $e->getMessage()]);
        }
    }

    public function submit(Timesheet $timesheet)
    {
        if ($timesheet->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!$timesheet->canBeSubmitted()) {
            return redirect()->back()
                ->with('error', 'This timesheet cannot be submitted in its current status.');
        }

        $timesheet->update([
            'status' => Timesheet::STATUS_SUBMITTED,
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
        ]);

        return redirect()->route('hr.timesheets.show', $timesheet->id)
            ->with('success', 'Timesheet submitted successfully.');
    }

    public function approve(Request $request, Timesheet $timesheet)
    {
        if ($timesheet->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!$timesheet->canBeApproved()) {
            return redirect()->back()
                ->with('error', 'This timesheet cannot be approved in its current status.');
        }

        // Check approval settings
        $settings = TimesheetApprovalSettings::getSettingsForCompany(
            current_company_id(),
            auth()->user()->branch_id ?? null
        );

        if ($settings && $settings->approval_required) {
            if (!$settings->canUserApprove(auth()->id())) {
                abort(403, 'You are not allowed to approve timesheets.');
            }
        }

        $validated = $request->validate([
            'approval_remarks' => 'nullable|string',
        ]);

        $timesheet->update([
            'status' => Timesheet::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_remarks' => $validated['approval_remarks'] ?? null,
        ]);

        return redirect()->route('hr.timesheets.show', $timesheet->id)
            ->with('success', 'Timesheet approved successfully.');
    }

    public function reject(Request $request, Timesheet $timesheet)
    {
        if ($timesheet->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!$timesheet->canBeApproved()) {
            return redirect()->back()
                ->with('error', 'This timesheet cannot be rejected in its current status.');
        }

        // Check approval settings
        $settings = TimesheetApprovalSettings::getSettingsForCompany(
            current_company_id(),
            auth()->user()->branch_id ?? null
        );

        if ($settings && $settings->approval_required) {
            if (!$settings->canUserApprove(auth()->id())) {
                abort(403, 'You are not allowed to reject timesheets.');
            }
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $timesheet->update([
            'status' => Timesheet::STATUS_REJECTED,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return redirect()->route('hr.timesheets.show', $timesheet->id)
            ->with('success', 'Timesheet rejected.');
    }
}
