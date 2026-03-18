<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\OvertimeRequest;
use App\Models\Hr\OvertimeRequestLine;
use App\Models\Hr\OvertimeRule;
use App\Models\Hr\Employee;
use App\Models\Hr\Attendance;
use App\Models\OvertimeApprovalSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class OvertimeRequestController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Return statistics if requested
            if ($request->has('stats_only') && $request->stats_only) {
                $baseQuery = OvertimeRequest::whereHas('employee', function ($q) {
                    $q->where('company_id', current_company_id());
                });

                // Apply filters for statistics
                if ($request->has('employee_id') && $request->employee_id) {
                    $baseQuery->where('employee_id', $request->employee_id);
                }

                if ($request->has('status') && $request->status) {
                    $baseQuery->where('status', $request->status);
                }

                return response()->json([
                    'stats' => [
                        'total' => (clone $baseQuery)->count(),
                        'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
                        'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
                        'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
                    ]
                ]);
            }

            $requests = OvertimeRequest::whereHas('employee', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with(['employee', 'attendance', 'approver', 'lines'])
            ->orderBy('hr_overtime_requests.overtime_date', 'desc');

            // Apply filters
            if ($request->has('employee_id') && $request->employee_id) {
                $requests->where('employee_id', $request->employee_id);
            }

            if ($request->has('status') && $request->status) {
                $requests->where('status', $request->status);
            }

            return DataTables::of($requests)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($request) {
                    return $request->employee->full_name;
                })
                ->addColumn('employee_number', function ($request) {
                    return $request->employee->employee_number;
                })
                ->addColumn('overtime_amount', function ($request) {
                    $totalHours = $request->total_overtime_hours ?? $request->lines->sum('overtime_hours');
                    $linesCount = $request->lines->count();
                    if ($linesCount > 1) {
                        return number_format($totalHours, 2) . ' hrs (' . $linesCount . ' entries)';
                    }
                    $line = $request->lines->first();
                    if ($line) {
                        return number_format($line->overtime_hours, 2) . ' hrs @ ' . $line->overtime_rate . 'x';
                    }
                    return '0.00 hrs';
                })
                ->addColumn('status_badge', function ($request) {
                    $badges = [
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    ];
                    $badge = $badges[$request->status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($request->status) . '</span>';
                })
                ->addColumn('approver_name', function ($request) {
                    return $request->approver ? $request->approver->name : '-';
                })
                ->addColumn('action', function ($request) {
                    $viewBtn = '<a href="' . route('hr.overtime-requests.show', $request->hash_id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    
                    if ($request->status === 'pending') {
                        // Check if user can approve based on settings
                        $canApprove = false;
                        $settings = OvertimeApprovalSettings::where('company_id', current_company_id())
                            ->where(function($query) {
                                $branchId = Auth::user()->branch_id;
                                if ($branchId) {
                                    $query->where('branch_id', $branchId);
                                } else {
                                    $query->whereNull('branch_id');
                                }
                            })
                            ->first();
                        
                        if ($settings && $settings->approval_required) {
                            // Check if user is an approver at any level
                            $totalHours = $request->total_overtime_hours ?? $request->lines->sum('overtime_hours');
                            for ($level = 1; $level <= $settings->approval_levels; $level++) {
                                $threshold = $settings->getHoursThresholdForLevel($level);
                                if ($threshold === null || $totalHours >= $threshold) {
                                    if ($settings->canUserApproveAtLevel(auth()->id(), $level)) {
                                        $canApprove = true;
                                        break;
                                    }
                                }
                            }
                        } else {
                            // Fallback to role-based if no settings or approval not required
                            $currentUser = auth()->user();
                            $isSuperAdmin = false;
                            $userRoles = $currentUser->getRoleNames();
                            foreach ($userRoles as $role) {
                                if (strtolower($role) === 'super-admin' || strtolower($role) === 'admin') {
                                    $isSuperAdmin = true;
                                    break;
                                }
                            }
                            $canApprove = $isSuperAdmin || $currentUser->hasRole(['HR', 'Admin', 'admin']);
                        }
                        
                        if ($canApprove) {
                            $approveBtn = '<button class="btn btn-sm btn-outline-success approve-btn me-1" data-id="' . $request->hash_id . '"><i class="bx bx-check"></i></button>';
                            $rejectBtn = '<button class="btn btn-sm btn-outline-danger reject-btn me-1" data-id="' . $request->hash_id . '"><i class="bx bx-x"></i></button>';
                            return $viewBtn . $approveBtn . $rejectBtn;
                        }
                    }
                    return $viewBtn;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        $employees = Employee::where('company_id', current_company_id())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('hr-payroll.overtime-requests.index', compact('employees'));
    }

    public function create(Request $request)
    {
        $employeeId = $request->employee_id;
        $date = $request->date ?? now()->format('Y-m-d');
        
        $employee = null;
        $gradeId = null;
        if ($employeeId) {
            $employee = Employee::where('company_id', current_company_id())
                ->with('position.grade')
                ->findOrFail($employeeId);
            
            if ($employee->position && $employee->position->grade_id) {
                $gradeId = $employee->position->grade_id;
            }
        }

        $employees = Employee::where('company_id', current_company_id())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Get attendance records for the employee on the selected date
        $attendance = null;
        if ($employeeId && $date) {
            $attendance = Attendance::where('employee_id', $employeeId)
                ->where('attendance_date', $date)
                ->first();
        }

        // Get overtime rules for the company
        $overtimeRules = OvertimeRule::where('company_id', current_company_id())
            ->where('is_active', true)
            ->where(function ($q) use ($gradeId) {
                $q->whereNull('grade_id')
                  ->orWhere('grade_id', $gradeId);
            })
            ->orderBy('day_type')
            ->orderBy('grade_id', 'desc')
            ->get()
            ->groupBy('day_type')
            ->map(function ($rules) {
                // Get the most specific rule (grade-specific first, then general)
                return $rules->first();
            });

        return view('hr-payroll.overtime-requests.create', compact('employee', 'employees', 'date', 'attendance', 'overtimeRules', 'gradeId'));
    }

    /**
     * Get overtime rate for a specific day type and employee grade
     */
    public function getOvertimeRate(Request $request)
    {
        $request->validate([
            'day_type' => 'required|in:weekday,weekend,holiday',
            'employee_id' => 'required|exists:hr_employees,id',
        ]);

        $employee = Employee::where('company_id', current_company_id())
            ->with('position.grade')
            ->findOrFail($request->employee_id);

        $gradeId = null;
        if ($employee->position && $employee->position->grade_id) {
            $gradeId = $employee->position->grade_id;
        }

        $rule = OvertimeRule::where('company_id', current_company_id())
            ->where('is_active', true)
            ->where('day_type', $request->day_type)
            ->where(function ($q) use ($gradeId) {
                $q->whereNull('grade_id')
                  ->orWhere('grade_id', $gradeId);
            })
            ->orderBy('grade_id', 'desc') // Prefer grade-specific rules
            ->first();

        return response()->json([
            'success' => true,
            'overtime_rate' => $rule ? $rule->overtime_rate : 1.50,
            'rule_id' => $rule ? $rule->id : null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'attendance_id' => 'nullable|exists:hr_attendance,id',
            'overtime_date' => 'required|date',
            'overtime_lines' => 'required|array|min:1',
            'overtime_lines.*.overtime_hours' => 'required|numeric|min:0.01|max:24',
            'overtime_lines.*.day_type' => 'required|in:weekday,weekend,holiday',
            'overtime_lines.*.overtime_rate' => 'required|numeric|min:1|max:5',
            'reason' => 'nullable|string',
        ]);

        // Verify employee belongs to company
        $employee = Employee::where('company_id', current_company_id())
            ->findOrFail($validated['employee_id']);

        DB::beginTransaction();
        try {
            // Create one overtime request
            $overtimeRequest = OvertimeRequest::create([
                'employee_id' => $validated['employee_id'],
                'attendance_id' => $validated['attendance_id'] ?? null,
                'overtime_date' => $validated['overtime_date'],
                'reason' => $validated['reason'] ?? null,
                'status' => 'pending',
            ]);

            // Create multiple lines for this request
            // Ensure array is properly indexed
            $lines = array_values($validated['overtime_lines']);
            
            foreach ($lines as $line) {
                if (empty($line['overtime_hours']) || empty($line['day_type']) || empty($line['overtime_rate'])) {
                    continue; // Skip invalid lines
                }
                
                OvertimeRequestLine::create([
                    'overtime_request_id' => $overtimeRequest->id,
                    'overtime_hours' => (float) $line['overtime_hours'],
                    'day_type' => $line['day_type'],
                    'overtime_rate' => (float) $line['overtime_rate'],
                ]);
            }

            // Check if at least one line was created
            if ($overtimeRequest->lines->count() === 0) {
                DB::rollBack();
                return back()->withInput()->withErrors(['error' => 'At least one valid overtime entry is required.']);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Overtime request created successfully.'
                ]);
            }

            return redirect()->route('hr.overtime-requests.index')
                ->with('success', 'Overtime request created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create overtime request: ' . $e->getMessage()]);
        }
    }

    public function show(OvertimeRequest $overtimeRequest)
    {
        if ($overtimeRequest->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $overtimeRequest->load('lines');

        // Check if user can approve and get who can approve
        $canApprove = false;
        $eligibleApprovers = [];
        $currentUserCanApprove = false;
        $settings = null;
        
        if ($overtimeRequest->status === 'pending') {
            // Get settings based on employee's branch (not current user's branch)
            $employeeBranchId = $overtimeRequest->employee->branch_id ?? null;
            $settings = OvertimeApprovalSettings::getSettingsForCompany(
                current_company_id(),
                $employeeBranchId
            );
            
            $totalHours = $overtimeRequest->total_overtime_hours ?? $overtimeRequest->lines->sum('overtime_hours');
            
            // Check if current user is super-admin (super admins can always approve)
            $user = auth()->user();
            $isSuperAdmin = false;
            $userRoles = $user->getRoleNames();
            foreach ($userRoles as $role) {
                if (strtolower($role) === 'super-admin' || strtolower($role) === 'admin') {
                    $isSuperAdmin = true;
                    break;
                }
            }
            
            if ($settings && $settings->approval_required) {
                if ($isSuperAdmin) {
                    // Super admins can always approve regardless of settings
                    $canApprove = true;
                    $currentUserCanApprove = true;
                } else {
                    // Get required approval levels for this request
                    $requiredApprovals = $settings->getRequiredApprovalsForHours($totalHours);
                    
                    // Get all eligible approvers
                    foreach ($requiredApprovals as $approval) {
                        $level = $approval['level'];
                        $approverIds = $approval['approvers'];
                        $threshold = $approval['threshold'];
                        
                        if (empty($approverIds)) {
                            continue;
                        }
                        
                        $approvers = \App\Models\User::whereIn('id', $approverIds)
                            ->where('company_id', current_company_id())
                            ->select('id', 'name', 'email')
                            ->get();
                        
                        foreach ($approvers as $approver) {
                            $eligibleApprovers[] = [
                                'user' => $approver,
                                'level' => $level,
                                'threshold' => $threshold,
                                'is_current_user' => $approver->id === auth()->id()
                            ];
                            
                            // Check if current user can approve
                            if ($approver->id === auth()->id()) {
                                $currentUserCanApprove = true;
                                $canApprove = true;
                            }
                        }
                    }
                }
            } else {
                // Fallback to role-based if no settings or approval not required
                // Check for super-admin, admin, Admin, HR roles
                $user = auth()->user();
                $canApprove = $user->hasRole(['HR', 'Admin', 'admin', 'super-admin', 'Super Admin']);
                
                // Also check if user is super admin (case-insensitive check)
                if (!$canApprove) {
                    $userRoles = $user->getRoleNames();
                    foreach ($userRoles as $role) {
                        if (strtolower($role) === 'super-admin' || strtolower($role) === 'admin') {
                            $canApprove = true;
                            break;
                        }
                    }
                }
                
                if ($canApprove) {
                    $currentUserCanApprove = true;
                }
                
                // Get HR, Admin, admin, super-admin users as eligible approvers
                $hrAdminUsers = \App\Models\User::where('company_id', current_company_id())
                    ->whereHas('roles', function($q) {
                        $q->whereIn('name', ['HR', 'Admin', 'admin', 'super-admin', 'Super Admin']);
                    })
                    ->select('id', 'name', 'email')
                    ->get();
                
                foreach ($hrAdminUsers as $user) {
                    $eligibleApprovers[] = [
                        'user' => $user,
                        'level' => null,
                        'threshold' => null,
                        'is_current_user' => $user->id === auth()->id()
                    ];
                }
            }
        }

        // Get all assigned approvers if settings exist
        $allAssignedApprovers = null;
        if ($settings) {
            $allAssignedApprovers = $settings->getAllAssignedApprovers();
        }

        return view('hr-payroll.overtime-requests.show', compact('overtimeRequest', 'canApprove', 'eligibleApprovers', 'currentUserCanApprove', 'settings', 'allAssignedApprovers'));
    }

    public function edit(OvertimeRequest $overtimeRequest)
    {
        if ($overtimeRequest->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if ($overtimeRequest->status !== 'pending') {
            return redirect()->route('hr.overtime-requests.show', $overtimeRequest->hash_id)
                ->with('error', 'Only pending requests can be edited.');
        }

        $overtimeRequest->load('lines');
        $employee = $overtimeRequest->employee;
        $gradeId = null;
        if ($employee->position && $employee->position->grade_id) {
            $gradeId = $employee->position->grade_id;
        }

        // Get overtime rules for the company
        $overtimeRules = OvertimeRule::where('company_id', current_company_id())
            ->where('is_active', true)
            ->where(function ($q) use ($gradeId) {
                $q->whereNull('grade_id')
                  ->orWhere('grade_id', $gradeId);
            })
            ->orderBy('day_type')
            ->orderBy('grade_id', 'desc')
            ->get()
            ->groupBy('day_type')
            ->map(function ($rules) {
                return $rules->first();
            });

        return view('hr-payroll.overtime-requests.edit', compact('overtimeRequest', 'overtimeRules', 'gradeId'));
    }

    public function update(Request $request, OvertimeRequest $overtimeRequest)
    {
        if ($overtimeRequest->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if ($overtimeRequest->status !== 'pending') {
            return redirect()->route('hr.overtime-requests.show', $overtimeRequest->hash_id)
                ->with('error', 'Only pending requests can be updated.');
        }

        $validated = $request->validate([
            'overtime_date' => 'required|date',
            'overtime_lines' => 'required|array|min:1',
            'overtime_lines.*.overtime_hours' => 'required|numeric|min:0.01|max:24',
            'overtime_lines.*.day_type' => 'required|in:weekday,weekend,holiday',
            'overtime_lines.*.overtime_rate' => 'required|numeric|min:1|max:5',
            'reason' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Update the main request
            $overtimeRequest->update([
                'overtime_date' => $validated['overtime_date'],
                'reason' => $validated['reason'] ?? null,
            ]);

            // Delete existing lines
            $overtimeRequest->lines()->delete();

            // Create new lines
            foreach ($validated['overtime_lines'] as $line) {
                OvertimeRequestLine::create([
                    'overtime_request_id' => $overtimeRequest->id,
                    'overtime_hours' => $line['overtime_hours'],
                    'day_type' => $line['day_type'],
                    'overtime_rate' => $line['overtime_rate'],
                ]);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Overtime request updated successfully.'
                ]);
            }

            return redirect()->route('hr.overtime-requests.show', $overtimeRequest->hash_id)
                ->with('success', 'Overtime request updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update overtime request: ' . $e->getMessage()]);
        }
    }

    public function approve(OvertimeRequest $overtimeRequest)
    {
        if ($overtimeRequest->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if ($overtimeRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be approved.'
            ], 400);
        }

        // Check if user can approve based on settings
        $settings = OvertimeApprovalSettings::where('company_id', current_company_id())
            ->where(function($query) {
                $branchId = Auth::user()->branch_id;
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                } else {
                    $query->whereNull('branch_id');
                }
            })
            ->first();

        $canApprove = false;
        $user = auth()->user();
        
        // Check if user is super-admin or admin (case-insensitive)
        $isSuperAdmin = false;
        $userRoles = $user->getRoleNames();
        foreach ($userRoles as $role) {
            if (strtolower($role) === 'super-admin' || strtolower($role) === 'admin') {
                $isSuperAdmin = true;
                break;
            }
        }
        
        if ($settings && $settings->approval_required) {
            // Super admins can always approve
            if ($isSuperAdmin) {
                $canApprove = true;
            } else {
            // Check if user is an approver at any level for this request's hours
                $totalHours = $overtimeRequest->total_overtime_hours ?? $overtimeRequest->lines->sum('overtime_hours');
            for ($level = 1; $level <= $settings->approval_levels; $level++) {
                $threshold = $settings->getHoursThresholdForLevel($level);
                    if ($threshold === null || $totalHours >= $threshold) {
                    if ($settings->canUserApproveAtLevel(auth()->id(), $level)) {
                        $canApprove = true;
                        break;
                        }
                    }
                }
            }
        } else {
            // Fallback to role-based if no settings or approval not required
            $canApprove = $isSuperAdmin || $user->hasRole(['HR', 'Admin', 'admin']);
        }

        if (!$canApprove) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to approve this overtime request.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $overtimeRequest->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Update attendance record if linked
            if ($overtimeRequest->attendance_id) {
                $attendance = Attendance::find($overtimeRequest->attendance_id);
                if ($attendance) {
                    $totalHours = $overtimeRequest->total_overtime_hours ?? $overtimeRequest->lines->sum('overtime_hours');
                    $attendance->update([
                        'overtime_hours' => $attendance->overtime_hours + $totalHours,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Overtime request approved successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve request: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reject(Request $request, OvertimeRequest $overtimeRequest)
    {
        if ($overtimeRequest->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if ($overtimeRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be rejected.'
            ], 400);
        }

        // Check if user can reject based on settings
        $settings = OvertimeApprovalSettings::where('company_id', current_company_id())
            ->where(function($query) {
                $branchId = Auth::user()->branch_id;
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                } else {
                    $query->whereNull('branch_id');
                }
            })
            ->first();

        $canReject = false;
        if ($settings && $settings->approval_required) {
            // Check if user is an approver at any level for this request's hours
            $totalHours = $overtimeRequest->total_overtime_hours ?? $overtimeRequest->lines->sum('overtime_hours');
            for ($level = 1; $level <= $settings->approval_levels; $level++) {
                $threshold = $settings->getHoursThresholdForLevel($level);
                if ($threshold === null || $totalHours >= $threshold) {
                    if ($settings->canUserApproveAtLevel(auth()->id(), $level)) {
                        $canReject = true;
                        break;
                    }
                }
            }
        } else {
            // Fallback to role-based if no settings or approval not required
            $canReject = auth()->user()->hasRole(['HR', 'Admin']);
        }

        if (!$canReject) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to reject this overtime request.'
            ], 403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $overtimeRequest->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Overtime request rejected successfully.'
        ]);
    }

    public function destroy(OvertimeRequest $overtimeRequest)
    {
        if ($overtimeRequest->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if ($overtimeRequest->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete approved overtime request.'
            ], 400);
        }

        $overtimeRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Overtime request deleted successfully.'
        ]);
    }
}

