<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\ApproveLeaveRequestRequest;
use App\Http\Requests\Leave\RejectLeaveRequestRequest;
use App\Http\Requests\Leave\StoreLeaveRequestRequest;
use App\Http\Requests\Leave\UpdateLeaveRequestRequest;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\LeaveType;
use App\Services\Leave\BalanceService;
use App\Services\Leave\LeaveRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected LeaveRequestService $leaveRequestService,
        protected BalanceService $balanceService
    ) {}

    /**
     * Display a listing of leave requests
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $user = Auth::user();
            $employee = $user->employee;

            $query = LeaveRequest::with(['leaveType', 'employee', 'segments'])
                ->where('company_id', current_company_id());

            // Filter by employee if not HR/Admin/Super-Admin
            if ($employee && !$user->hasAnyRole(['super-admin', 'admin', 'HR', 'Admin']) &&
                !$user->hasAnyPermission(['manage leave requests', 'manage hr payroll'])) {
                $query->where('employee_id', $employee->id);
            }

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('leave_type_id')) {
                $query->where('leave_type_id', $request->leave_type_id);
            }

            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            $query->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($row) {
                    return $row->employee->full_name ?? 'N/A';
                })
                ->addColumn('leave_type_name', function ($row) {
                    return $row->leaveType->name ?? 'N/A';
                })
                ->addColumn('date_range', function ($row) {
                    $segment = $row->segments->first();
                    return $segment ? $segment->date_range : 'N/A';
                })
                ->addColumn('status_badge', function ($row) {
                    return '<span class="badge bg-' . $row->status_badge . '">' . $row->status_label . '</span>';
                })
                ->addColumn('action', function ($row) use ($user, $employee) {
                    $actions = '<a href="' . route('hr.leave.requests.show', $row) . '" class="btn btn-sm btn-info">
                        <i class="bx bx-show"></i> View
                    </a>';

                    if ($row->isEditable() && $row->employee_id == $employee?->id) {
                        $actions .= ' <a href="' . route('hr.leave.requests.edit', $row) . '" class="btn btn-sm btn-warning">
                            <i class="bx bx-edit"></i> Edit
                        </a>';
                    }

                    return $actions;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        $leaveTypes = LeaveType::where('company_id', current_company_id())
            ->where('is_active', true)
            ->get();

        $employees = Employee::where('company_id', current_company_id())
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        return view('hr-payroll.leave.requests.index', compact('leaveTypes', 'employees'));
    }

    /**
     * Show the form for creating a new leave request
     */
    public function create()
    {
        $user = Auth::user();
        
        // Load employee relationship to ensure employee_id accessor works
        $user->load('employee');

        // Check permissions manually first (for super-admin/admin)
        $hasAccess = false;
        try {
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super-admin', 'admin'])) {
                $hasAccess = true;
            }
        } catch (\Exception $e) {
            // Continue to permission checks
        }

        if (!$hasAccess) {
            $hasAccess = $user->hasPermissionTo('manage leave requests') ||
                        $user->hasPermissionTo('manage hr payroll') ||
                        $user->hasPermissionTo('create leave request');
        }

        if (!$hasAccess) {
            try {
                $this->authorize('create', LeaveRequest::class);
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                return redirect()->back()->with('error', 'You are not authorized to create leave requests.');
            }
        }

        $employee = $user->employee;

        // Check if user is super-admin/admin
        $isSuperAdmin = false;
        try {
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super-admin', 'admin'])) {
                $isSuperAdmin = true;
            }
        } catch (\Exception $e) {
            // Continue
        }

        // HR/Admin can create for any employee
        $canCreateForOthers = $isSuperAdmin ||
                              $user->hasPermissionTo('manage leave requests') ||
                              $user->hasPermissionTo('manage hr payroll');

        if (!$employee && !$canCreateForOthers) {
            return redirect()->back()->with('error', 'You must be linked to an employee to request leave.');
        }

        $leaveTypes = LeaveType::where('company_id', current_company_id())
            ->where('is_active', true)
            ->get();

        // Get all employees if user can create for others
        if ($canCreateForOthers) {
            $employees = Employee::where('company_id', current_company_id())
                ->where('status', 'active')
                ->orderBy('first_name')
                ->get();
        } else {
            $employees = collect([$employee]);
        }

        $balances = $employee ? $this->balanceService->getBalanceSummary($employee) : [];

        $relievers = Employee::where('company_id', current_company_id())
            ->where('status', 'active')
            ->when($employee, function ($query) use ($employee) {
                return $query->where('id', '!=', $employee->id);
            })
            ->orderBy('first_name')
            ->get();

        $publicHolidays = \App\Models\Hr\PublicHoliday::where('company_id', current_company_id())
            ->whereYear('date', date('Y'))
            ->get();

        // Get current payroll period and cut-off date
        $currentPayrollPeriod = \App\Models\Hr\PayrollCalendar::where('company_id', current_company_id())
            ->where('calendar_year', date('Y'))
            ->where('payroll_month', date('n'))
            ->first();

        $payrollCutOffDate = $currentPayrollPeriod?->cut_off_date;
        $isPayrollLocked = $currentPayrollPeriod?->is_locked ?? false;

        // Load employee relationships if employee exists
        if ($employee) {
            $employee->load(['department', 'position']);
        }

        return view('hr-payroll.leave.requests.create', compact(
            'leaveTypes', 
            'balances', 
            'relievers', 
            'employee', 
            'employees', 
            'canCreateForOthers', 
            'publicHolidays',
            'currentPayrollPeriod',
            'payrollCutOffDate',
            'isPayrollLocked'
        ));
    }

    /**
     * Store a newly created leave request
     */
    public function store(StoreLeaveRequestRequest $request)
    {
        $user = Auth::user();
        
        // Load employee relationship to ensure employee_id accessor works
        $user->load('employee');

        // Check permissions manually first (for super-admin/admin)
        $hasAccess = false;
        try {
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['super-admin', 'admin'])) {
                $hasAccess = true;
            }
        } catch (\Exception $e) {
            // Continue to permission checks
        }

        if (!$hasAccess) {
            $hasAccess = $user->hasPermissionTo('manage leave requests') ||
                        $user->hasPermissionTo('manage hr payroll') ||
                        $user->hasPermissionTo('create leave request');
        }

        if (!$hasAccess) {
            try {
                $this->authorize('create', LeaveRequest::class);
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'You are not authorized to create leave requests.');
            }
        }

        try {
            DB::beginTransaction();
            $leaveRequest = $this->leaveRequestService->createDraft($user, $request->validated());

            DB::commit();

            return redirect()->route('hr.leave.requests.show', $leaveRequest)
                ->with('success', 'Leave request created successfully as draft.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create leave request: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified leave request
     */
    public function show(LeaveRequest $request)
    {
        $user = Auth::user();
        
        // Verify leave request belongs to user's company
        if ($user->company_id && $request->company_id && $request->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to leave request.');
        }

        // Load employee relationship for user to ensure employee_id accessor works
        $user->load('employee');
        
        // Load leave request relationships (including approvals for policy check)
        $request->load(['leaveType', 'employee', 'reliever', 'segments', 'approvals.approver', 'attachments']);

        // Check authorization
        try {
            $this->authorize('view', $request);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            // Check if user is viewing their own request
            if ($user->employee_id && $user->employee_id === $request->employee_id) {
                // Allow viewing own requests
            } else {
                // Check if user has any relevant permission
                $hasPermission = $user->hasPermissionTo('view leave requests') ||
                                $user->hasPermissionTo('manage leave requests') ||
                                $user->hasPermissionTo('view hr payroll') ||
                                $user->hasPermissionTo('view leave request details');
                
                if (!$hasPermission) {
                    // Check if user is a manager/approver
                    $isManager = $user->employee_id && 
                                $request->employee && 
                                $request->employee->reports_to === $user->employee_id;
                    
                    if (!$isManager) {
                        abort(403, 'This action is unauthorized.');
                    }
                }
            }
        }

        // Check if user can add documents (for draft requests)
        // Uses same logic as update policy: can update = can add documents
        $canAddDocument = false;
        if ($request->status === 'draft') {
            // Can add if user owns the request and has edit permission
            if ($user->employee_id && $user->employee_id == $request->employee_id) {
                $canAddDocument = $user->hasPermissionTo('edit leave request');
            }
            // Can add if has manage permissions
            if ($user->hasPermissionTo('manage leave requests') ||
                $user->hasPermissionTo('manage hr payroll')) {
                $canAddDocument = true;
            }
            // Can add if super-admin/admin
            try {
                if (method_exists($user, 'hasAnyRole') &&
                    $user->hasAnyRole(['super-admin', 'admin'])) {
                    $canAddDocument = true;
                }
            } catch (\Exception $e) {
                // Continue
            }
        }

        return view('hr-payroll.leave.requests.show', compact('request', 'canAddDocument'));
    }

    /**
     * Show the form for editing the specified leave request
     */
    public function edit(LeaveRequest $request)
    {
        $this->authorize('update', $request);

        // Load relationships
        $request->load(['segments', 'attachments']);

        $leaveTypes = LeaveType::where('company_id', current_company_id())
            ->where('is_active', true)
            ->get();

        $employee = $request->employee;
        $balances = $this->balanceService->getBalanceSummary($employee);

        $relievers = Employee::where('company_id', current_company_id())
            ->where('status', 'active')
            ->where('id', '!=', $employee->id)
            ->orderBy('first_name')
            ->get();

        return view('hr-payroll.leave.requests.edit', compact('request', 'leaveTypes', 'balances', 'relievers', 'employee'));
    }

    /**
     * Update the specified leave request
     */
    public function update(UpdateLeaveRequestRequest $request, LeaveRequest $leaveRequest)
    {
        $this->authorize('update', $leaveRequest);

        try {
            DB::beginTransaction();

            $this->leaveRequestService->updateDraft($leaveRequest, $request->validated());

            DB::commit();

            return redirect()->route('hr.leave.requests.show', $leaveRequest)
                ->with('success', 'Leave request updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update leave request: ' . $e->getMessage());
        }
    }

    /**
     * Submit leave request for approval
     */
    public function submit(LeaveRequest $request = null)
    {
        $user = Auth::user();
        $user->load('employee');

        // If route model binding didn't work, manually resolve from route parameter
        if (!$request || !$request->exists) {
            $hashId = request()->route('request');
            
            if ($hashId) {
                // Try to decode the hash ID
                $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hashId);
                
                if (!empty($decoded) && isset($decoded[0])) {
                    $request = LeaveRequest::where('id', $decoded[0])->first();
                } elseif (is_numeric($hashId)) {
                    $request = LeaveRequest::where('id', $hashId)->first();
                }
            }
        }

        // Ensure leave request is loaded
        if (!$request || !$request->exists) {
            \Log::error('Leave Request Not Found for Submit', [
                'route_param' => request()->route('request'),
                'url' => request()->fullUrl(),
                'user_id' => $user->id
            ]);
            
            return redirect()->back()
                ->with('error', 'Leave request not found. Please refresh the page and try again.');
        }

        // Ensure employee relationship is loaded on the request
        if (!$request->relationLoaded('employee')) {
            $request->load('employee');
        }

        // Check authorization with better error handling
        try {
            $this->authorize('submit', $request);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            \Log::warning('Leave Request Submit Authorization Failed', [
                'user_id' => $user->id,
                'user_employee_id' => $user->employee_id,
                'request_id' => $request->id,
                'request_employee_id' => $request->employee_id,
                'request_status' => $request->status,
                'employee_ids_match' => $user->employee_id == $request->employee_id,
                'has_submit_permission' => $user->hasPermissionTo('submit leave request'),
                'has_edit_permission' => $user->hasPermissionTo('edit leave request'),
                'has_manage_leave_permission' => $user->hasPermissionTo('manage leave requests'),
                'has_manage_hr_permission' => $user->hasPermissionTo('manage hr payroll'),
                'is_super_admin' => method_exists($user, 'hasRole') ? $user->hasRole('super-admin') : false,
                'is_admin' => method_exists($user, 'hasRole') ? $user->hasRole('admin') : false,
                'user_roles' => method_exists($user, 'roles') ? $user->roles->pluck('name')->toArray() : []
            ]);
            
            // Build a more helpful error message
            $errorMsg = 'You are not authorized to submit this leave request.';
            if ($request->status !== 'draft') {
                $errorMsg .= ' The request must be in draft status. Current status: ' . ucfirst($request->status);
            } elseif ($user->employee_id != $request->employee_id) {
                $errorMsg .= ' You can only submit your own leave requests.';
            } else {
                $errorMsg .= ' Please contact your administrator to assign the necessary permissions.';
            }
            
            return redirect()->back()->with('error', $errorMsg);
        }

        try {
            DB::beginTransaction();

            $this->leaveRequestService->submit($request);

            DB::commit();

            return redirect()->route('hr.leave.requests.show', $request)
                ->with('success', 'Leave request submitted for approval.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Leave Request Submit Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to submit leave request: ' . $e->getMessage());
        }
    }

    /**
     * Approve leave request
     */
    public function approve(ApproveLeaveRequestRequest $request, LeaveRequest $leaveRequest)
    {
        $this->authorize('approve', $leaveRequest);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $approver = $user->employee;

            if (!$approver) {
                return redirect()->back()->with('error', 'You must be linked to an employee to approve requests.');
            }

            $this->leaveRequestService->approve($leaveRequest, $approver, $request->comment);

            DB::commit();

            return redirect()->route('hr.leave.requests.show', $leaveRequest)
                ->with('success', 'Leave request approved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to approve leave request: ' . $e->getMessage());
        }
    }

    /**
     * Reject leave request
     */
    public function reject(RejectLeaveRequestRequest $request, LeaveRequest $leaveRequest)
    {
        $this->authorize('reject', $leaveRequest);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $approver = $user->employee;

            if (!$approver) {
                return redirect()->back()->with('error', 'You must be linked to an employee to reject requests.');
            }

            $this->leaveRequestService->reject($leaveRequest, $approver, $request->comment);

            DB::commit();

            return redirect()->route('hr.leave.requests.show', $leaveRequest)
                ->with('success', 'Leave request rejected.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to reject leave request: ' . $e->getMessage());
        }
    }

    /**
     * Return leave request for editing
     */
    public function returnForEdit(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorize('returnForEdit', $leaveRequest);

        $request->validate([
            'comment' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $approver = $user->employee;

            if (!$approver) {
                return redirect()->back()->with('error', 'You must be linked to an employee.');
            }

            $this->leaveRequestService->returnForEdit($leaveRequest, $approver, $request->comment);

            DB::commit();

            return redirect()->route('hr.leave.requests.show', $leaveRequest)
                ->with('success', 'Leave request returned for editing.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to return leave request: ' . $e->getMessage());
        }
    }

    /**
     * Cancel leave request
     */
    public function cancel(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorize('cancel', $leaveRequest);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $employee = $user->employee;

            $this->leaveRequestService->cancel($leaveRequest, $employee, $request->reason);

            DB::commit();

            return redirect()->route('hr.leave.requests.show', $leaveRequest)
                ->with('success', 'Leave request cancelled.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to cancel leave request: ' . $e->getMessage());
        }
    }

    /**
     * Delete the specified leave request
     */
    public function destroy(LeaveRequest $request)
    {
        $this->authorize('delete', $request);

        try {
            $request->delete();

            return redirect()->route('hr.leave.requests.index')
                ->with('success', 'Leave request deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete leave request: ' . $e->getMessage());
        }
    }

    /**
     * Add attachment to leave request
     */
    public function addAttachment(Request $request, LeaveRequest $leaveRequest = null)
    {
        $user = Auth::user();
        $user->load('employee');

        // If route model binding didn't work, manually resolve from route parameter
        if (!$leaveRequest || !$leaveRequest->exists) {
            $hashId = $request->route('request');
            
            if ($hashId) {
                // Try to decode the hash ID
                $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hashId);
                
                if (!empty($decoded) && isset($decoded[0])) {
                    $leaveRequest = LeaveRequest::where('id', $decoded[0])->first();
                } elseif (is_numeric($hashId)) {
                    $leaveRequest = LeaveRequest::where('id', $hashId)->first();
                }
            }
        }

        // Ensure leave request is loaded
        if (!$leaveRequest || !$leaveRequest->exists) {
            \Log::error('Leave Request Not Found', [
                'route_param' => $request->route('request'),
                'url' => $request->fullUrl()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Leave request not found. Please refresh the page and try again.'
                ], 404);
            }
            abort(404, 'Leave request not found.');
        }

        // Refresh the model to ensure we have latest data
        $leaveRequest->refresh();
        
        // Debug: Log the status to help diagnose issues
        \Log::info('Leave Request Status Check', [
            'leave_request_id' => $leaveRequest->id,
            'status' => $leaveRequest->status,
            'hash_id' => $leaveRequest->hash_id ?? 'N/A'
        ]);

        // Check authorization - same logic as in show() method
        $canAddDocument = false;
        
        // First check: request must be in draft status
        $status = $leaveRequest->status ?? '';
        if (empty($status)) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Leave request status is not set. Please contact support.'
                ], 403);
            }
            abort(403, 'Leave request status is not set.');
        }
        
        if ($status !== 'draft') {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documents can only be added to draft leave requests. ' .
                        'Current status: ' . ucfirst($status)
                ], 403);
            }
            abort(403, 'Documents can only be added to draft leave requests.');
        }

        // Check if user owns the request and has edit permission
        if ($user->employee_id && $user->employee_id == $leaveRequest->employee_id) {
            $canAddDocument = $user->hasPermissionTo('edit leave request');
        }
        
        // Check if user has manage permissions
        if ($user->hasPermissionTo('manage leave requests') ||
            $user->hasPermissionTo('manage hr payroll')) {
            $canAddDocument = true;
        }
        
        // Check if user is super-admin/admin
        try {
            if (method_exists($user, 'hasAnyRole') &&
                $user->hasAnyRole(['super-admin', 'admin'])) {
                $canAddDocument = true;
            }
        } catch (\Exception $e) {
            // Continue
        }

        if (!$canAddDocument) {
            // Build helpful error message
            $errorMessage = 'This action is unauthorized. ';
            
            if ($user->employee_id && $user->employee_id == $leaveRequest->employee_id) {
                $errorMessage .= 'You need "edit leave request" permission to add documents to your own requests.';
            } else {
                $errorMessage .= 'You need "edit leave request", "manage leave requests", ' .
                    'or "manage hr payroll" permission, or be a super-admin/admin to add documents.';
            }
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 403);
            }
            abort(403, $errorMessage);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $path = $file->store('leave-attachments', 'public');

            $attachment = $leaveRequest->attachments()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'type' => 'document',
                'size_kb' => round($file->getSize() / 1024, 2),
                'mime_type' => $file->getMimeType(),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document uploaded successfully.',
                    'attachment' => [
                        'id' => $attachment->id,
                        'file_name' => $attachment->original_name,
                        'file_size_human' => $attachment->formatted_size,
                        'url' => $attachment->url,
                    ]
                ]);
            }

            return redirect()->route('hr.leave.requests.show', $leaveRequest)
                ->with('success', 'Document uploaded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload document: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to upload document: ' . $e->getMessage());
        }
    }

    /**
     * Delete attachment from leave request
     */
    public function deleteAttachment(Request $request, LeaveRequest $leaveRequest, \App\Models\Hr\LeaveAttachment $attachment)
    {
        $this->authorize('update', $leaveRequest);

        if ($attachment->leave_request_id !== $leaveRequest->id) {
            abort(404, 'Attachment not found for this leave request.');
        }

        try {
            DB::beginTransaction();
            $attachment->delete();
            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document deleted successfully.'
                ]);
            }

            return redirect()->route('hr.leave.requests.show', $leaveRequest)
                ->with('success', 'Document deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete document: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to delete document: ' . $e->getMessage());
        }
    }
}

