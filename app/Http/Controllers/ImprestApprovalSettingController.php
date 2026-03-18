<?php

namespace App\Http\Controllers;

use App\Models\ImprestApprovalSetting;
use App\Models\User;
use App\Models\Department;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use DataTables;

class ImprestApprovalSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     * DEPRECATED: Redirect to multi-level approval settings
     */
    public function index(Request $request)
    {
        // Redirect to the new multi-level approval system
        return redirect()->route('imprest.multi-approval-settings.index')
            ->with('info', 'Approval settings have been upgraded to multi-level system for better flexibility.');
    }

    /**
     * Original index method (kept for reference)
     */
    public function indexOld(Request $request)
    {
        $user = Auth::user();
        
        if ($request->ajax()) {
            $settings = ImprestApprovalSetting::with(['user', 'creator', 'updater'])
                ->forCompany($user->company_id)
                ->latest();

            return DataTables::of($settings)
                ->addIndexColumn()
                ->addColumn('user_name', function ($row) {
                    return $row->user->name ?? 'N/A';
                })
                ->addColumn('role_badge', function ($row) {
                    return '<span class="' . $row->role_badge_class . '">' . $row->role_label . '</span>';
                })
                ->addColumn('status_badge', function ($row) {
                    $class = $row->is_active ? 'badge bg-success' : 'badge bg-secondary';
                    $text = $row->is_active ? 'Active' : 'Inactive';
                    return '<span class="' . $class . '">' . $text . '</span>';
                })
                ->addColumn('amount_limit', function ($row) {
                    return $row->amount_limit ? 'TZS ' . number_format($row->amount_limit, 2) : 'No Limit';
                })
                ->addColumn('departments', function ($row) {
                    return $row->department_names;
                })
                ->addColumn('created_info', function ($row) {
                    return ($row->creator->name ?? 'N/A') . '<br><small class="text-muted">' . $row->created_at->format('M d, Y H:i') . '</small>';
                })
                ->addColumn('actions', function ($row) {
                    $actions = '';
                    
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                    onclick="editSetting(' . $row->id . ')" title="Edit">
                                    <i class="bx bx-edit"></i>
                                </button>';
                    
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="deleteSetting(' . $row->id . ')" title="Delete">
                                    <i class="bx bx-trash"></i>
                                </button>';
                    
                    return $actions;
                })
                ->rawColumns(['role_badge', 'status_badge', 'created_info', 'actions'])
                ->make(true);
        }

        try {
            $stats = [
                'total_settings' => ImprestApprovalSetting::forCompany($user->company_id)->count(),
                'active_checkers' => ImprestApprovalSetting::forCompany($user->company_id)->byRole('checker')->active()->count(),
                'active_approvers' => ImprestApprovalSetting::forCompany($user->company_id)->byRole('approver')->active()->count(),
                'active_providers' => ImprestApprovalSetting::forCompany($user->company_id)->byRole('provider')->active()->count(),
            ];
        } catch (\Exception $e) {
            \Log::error('Error calculating approval settings stats: ' . $e->getMessage());
            $stats = [
                'total_settings' => 0,
                'active_checkers' => 0,
                'active_approvers' => 0,
                'active_providers' => 0,
            ];
        }

        return view('imprest.approval-settings.index', compact('stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        
        // $users = User::where('company_id', $user->company_id)
        //     ->where('id', '!=', $user->id)
        //     ->orderBy('name')
        //     ->get();

        $users = User::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();
            
        $departments = Department::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'users' => $users,
                'departments' => $departments
            ]);
        }

        return view('imprest.approval-settings.create', compact('users', 'departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'approval_role' => 'required|in:checker,approver,provider',
            'is_active' => 'boolean',
            'amount_limit' => 'nullable|numeric|min:0',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'exists:departments,id',
        ]);

        $user = Auth::user();

        // Check if user already has this role
        $exists = ImprestApprovalSetting::where('user_id', $request->user_id)
            ->where('approval_role', $request->approval_role)
            ->where('company_id', $user->company_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'User already has this approval role assigned.'
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            ImprestApprovalSetting::create([
                'user_id' => $request->user_id,
                'approval_role' => $request->approval_role,
                'is_active' => $request->boolean('is_active', true),
                'amount_limit' => $request->amount_limit,
                'department_ids' => $request->department_ids,
                'company_id' => $user->company_id,
                'branch_id' => $user->branch_id,
                'created_by' => $user->id,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Approval setting created successfully.'
                ]);
            }

            return redirect()->route('imprest.approval-settings.index')
                ->with('success', 'Approval setting created successfully.');
                
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create approval setting.'
                ], 500);
            }
            
            return back()->withInput()->withErrors(['error' => 'Failed to create approval setting.']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $setting = ImprestApprovalSetting::with(['user', 'creator', 'updater'])
            ->findOrFail($id);
            
        return view('imprest.approval-settings.show', compact('setting'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = Auth::user();
        
        $setting = ImprestApprovalSetting::findOrFail($id);
        
        $users = User::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();
            
        $departments = Department::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'setting' => $setting,
                'users' => $users,
                'departments' => $departments
            ]);
        }

        return view('imprest.approval-settings.edit', compact('setting', 'users', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $setting = ImprestApprovalSetting::findOrFail($id);
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'approval_role' => 'required|in:checker,approver,provider',
            'is_active' => 'boolean',
            'amount_limit' => 'nullable|numeric|min:0',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'exists:departments,id',
        ]);

        $user = Auth::user();

        // Check if user already has this role (excluding current setting)
        $exists = ImprestApprovalSetting::where('user_id', $request->user_id)
            ->where('approval_role', $request->approval_role)
            ->where('company_id', $user->company_id)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'User already has this approval role assigned.'
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $setting->update([
                'user_id' => $request->user_id,
                'approval_role' => $request->approval_role,
                'is_active' => $request->boolean('is_active', true),
                'amount_limit' => $request->amount_limit,
                'department_ids' => $request->department_ids,
                'updated_by' => $user->id,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Approval setting updated successfully.'
                ]);
            }

            return redirect()->route('imprest.approval-settings.index')
                ->with('success', 'Approval setting updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update approval setting.'
                ], 500);
            }
            
            return back()->withInput()->withErrors(['error' => 'Failed to update approval setting.']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $setting = ImprestApprovalSetting::findOrFail($id);
        
        DB::beginTransaction();
        
        try {
            $setting->delete();
            
            DB::commit();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Approval setting deleted successfully.'
                ]);
            }

            return redirect()->route('imprest.approval-settings.index')
                ->with('success', 'Approval setting deleted successfully.');
                
        } catch (\Exception $e) {
            DB::rollback();
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete approval setting.'
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Failed to delete approval setting.']);
        }
    }
}
