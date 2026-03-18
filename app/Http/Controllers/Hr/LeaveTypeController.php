<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreLeaveTypeRequest;
use App\Models\Hr\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LeaveTypeController extends Controller
{
    /**
     * Display a listing of leave types
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $leaveTypes = LeaveType::where('company_id', current_company_id())
                ->latest();

            return DataTables::of($leaveTypes)
                ->addIndexColumn()
                ->addColumn('is_active_badge', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-secondary">Inactive</span>';
                })
                ->addColumn('is_paid_badge', function ($row) {
                    return $row->is_paid
                        ? '<span class="badge bg-success">Paid</span>'
                        : '<span class="badge bg-warning">Unpaid</span>';
                })
                ->addColumn('action', function ($row) {
                    $hashId = \Vinkla\Hashids\Facades\Hashids::encode($row->id);
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('hr.leave.types.show', $hashId) . '" class="btn btn-sm btn-info" title="View">
                        <i class="bx bx-show"></i>
                    </a>';
                    $actions .= '<a href="' . route('hr.leave.types.edit', $hashId) . '" class="btn btn-sm btn-warning" title="Edit">
                        <i class="bx bx-edit"></i>
                    </a>';
                    $actions .= '<button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $hashId . '" data-name="' . htmlspecialchars($row->name) . '" title="Delete">
                        <i class="bx bx-trash"></i>
                    </button>';
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['is_active_badge', 'is_paid_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.leave.types.index');
    }

    /**
     * Show the form for creating a new leave type
     */
    public function create()
    {
        // Debug: Check user permissions
        $user = auth()->user();
        if (!$user) {
            abort(403, 'User not authenticated');
        }

        // Check permissions manually for better error messages
        $hasPermission = $user->hasPermissionTo('create leave type') ||
                        $user->hasPermissionTo('manage leave types') ||
                        $user->hasPermissionTo('manage hr payroll');

        if (!$hasPermission) {
            // Log for debugging
            \Log::warning('Leave Type Create - Permission Denied', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'roles' => $user->roles->pluck('name')->toArray(),
            ]);
            
            abort(403, 'This action is unauthorized. Required permissions: create leave type, manage leave types, or manage hr payroll');
        }

        return view('hr-payroll.leave.types.create');
    }

    /**
     * Store a newly created leave type
     */
    public function store(StoreLeaveTypeRequest $request)
    {
        $this->authorize('create', LeaveType::class);

        try {
            DB::beginTransaction();

            $leaveType = LeaveType::create([
                'company_id' => current_company_id(),
                ...$request->validated(),
            ]);

            DB::commit();

            return redirect()->route('hr.leave.types.index')
                ->with('success', 'Leave type created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create leave type: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified leave type
     */
    public function show(LeaveType $type)
    {
        // Debug: Check user permissions
        $user = auth()->user();
        if (!$user) {
            abort(403, 'User not authenticated');
        }

        // Check permissions manually for better error messages
        $hasPermission = $user->hasPermissionTo('view leave type details') ||
                        $user->hasPermissionTo('view leave types') ||
                        $user->hasPermissionTo('manage leave types') ||
                        $user->hasPermissionTo('manage hr payroll') ||
                        $user->hasPermissionTo('view hr payroll');

        if (!$hasPermission) {
            // Log for debugging
            \Log::warning('Leave Type Show - Permission Denied', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'leave_type_id' => $type->id,
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'roles' => $user->roles->pluck('name')->toArray(),
            ]);
            
            abort(403, 'This action is unauthorized. Required permissions: view leave type details, view leave types, manage leave types, manage hr payroll, or view hr payroll');
        }

        return view('hr-payroll.leave.types.show', compact('type'));
    }

    /**
     * Show the form for editing the specified leave type
     */
    public function edit(LeaveType $type)
    {
        // Debug: Check user permissions
        $user = auth()->user();
        if (!$user) {
            abort(403, 'User not authenticated');
        }

        // Check permissions manually for better error messages
        $hasPermission = $user->hasPermissionTo('edit leave type') ||
                        $user->hasPermissionTo('manage leave types') ||
                        $user->hasPermissionTo('manage hr payroll');

        if (!$hasPermission) {
            // Log for debugging
            \Log::warning('Leave Type Edit - Permission Denied', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'leave_type_id' => $type->id,
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'roles' => $user->roles->pluck('name')->toArray(),
            ]);
            
            abort(403, 'This action is unauthorized. Required permissions: edit leave type, manage leave types, or manage hr payroll');
        }

        return view('hr-payroll.leave.types.edit', compact('type'));
    }

    /**
     * Update the specified leave type
     */
    public function update(StoreLeaveTypeRequest $request, LeaveType $type)
    {
        // Debug: Check user permissions
        $user = auth()->user();
        if (!$user) {
            abort(403, 'User not authenticated');
        }

        // Check permissions manually for better error messages
        $hasPermission = $user->hasPermissionTo('edit leave type') ||
                        $user->hasPermissionTo('manage leave types') ||
                        $user->hasPermissionTo('manage hr payroll');

        if (!$hasPermission) {
            // Log for debugging
            \Log::warning('Leave Type Update - Permission Denied', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'leave_type_id' => $type->id,
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'roles' => $user->roles->pluck('name')->toArray(),
            ]);
            
            abort(403, 'This action is unauthorized. Required permissions: edit leave type, manage leave types, or manage hr payroll');
        }

        try {
            DB::beginTransaction();

            $type->update($request->validated());

            DB::commit();

            return redirect()->route('hr.leave.types.index')
                ->with('success', 'Leave type updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update leave type: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified leave type
     */
    public function destroy(LeaveType $type)
    {
        // Debug: Check user permissions
        $user = auth()->user();
        if (!$user) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 403);
            }
            abort(403, 'User not authenticated');
        }

        // Check permissions manually for better error messages
        $hasPermission = $user->hasPermissionTo('delete leave type') ||
                        $user->hasPermissionTo('manage leave types') ||
                        $user->hasPermissionTo('manage hr payroll');

        if (!$hasPermission) {
            // Log for debugging
            \Log::warning('Leave Type Delete - Permission Denied', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'leave_type_id' => $type->id,
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'roles' => $user->roles->pluck('name')->toArray(),
            ]);
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This action is unauthorized. Required permissions: delete leave type, manage leave types, or manage hr payroll'
                ], 403);
            }
            
            abort(403, 'This action is unauthorized. Required permissions: delete leave type, manage leave types, or manage hr payroll');
        }

        // Check if leave type has associated leave requests
        $hasRequests = $type->requests()->exists();
        if ($hasRequests) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete leave type. It has associated leave requests. Please deactivate it instead.'
            ], 422);
        }

        // Check if leave type has associated leave balances
        $hasBalances = $type->balances()->exists();
        if ($hasBalances) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete leave type. It has associated leave balances. Please deactivate it instead.'
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            $typeName = $type->name;
            $type->delete();

            DB::commit();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Leave type "' . $typeName . '" deleted successfully.'
                ]);
            }

            return redirect()->route('hr.leave.types.index')
                ->with('success', 'Leave type deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete leave type: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to delete leave type: ' . $e->getMessage());
        }
    }
}

