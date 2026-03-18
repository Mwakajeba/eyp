<?php

namespace App\Http\Controllers;

use App\Models\StoreRequisitionApprovalSettings;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreRequisitionApprovalSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the approval settings page
     */
    public function index()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        // Get or create approval settings
        $settings = StoreRequisitionApprovalSettings::where('company_id', $companyId)->first();
        
        if (!$settings) {
            $settings = new StoreRequisitionApprovalSettings([
                'company_id' => $companyId,
                'level_1_enabled' => false,
                'level_2_enabled' => false,
                'level_3_enabled' => false,
                'level_4_enabled' => false,
                'level_5_enabled' => false,
            ]);
        }

        // Get users and roles for dropdowns
        $users = User::where('status', 'active')
            ->when($user->branch_id, function ($query) use ($user) {
                return $query->where('branch_id', $user->branch_id);
            })
            ->orderBy('name')
            ->get();

        $roles = Role::all(); // Assuming roles are global

        return view('store_requisitions.approval_settings.index', compact('settings', 'users', 'roles'));
    }

    /**
     * Store or update approval settings
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $request->validate([
            'level_1_enabled' => 'nullable|in:0,1',
            'level_1_user_id' => 'nullable|exists:users,id',
            'level_1_role_id' => 'nullable|exists:roles,id',
            'level_2_enabled' => 'nullable|in:0,1',
            'level_2_user_id' => 'nullable|exists:users,id',
            'level_2_role_id' => 'nullable|exists:roles,id',
            'level_3_enabled' => 'nullable|in:0,1',
            'level_3_user_id' => 'nullable|exists:users,id',
            'level_3_role_id' => 'nullable|exists:roles,id',
            'level_4_enabled' => 'nullable|in:0,1',
            'level_4_user_id' => 'nullable|exists:users,id',
            'level_4_role_id' => 'nullable|exists:roles,id',
            'level_5_enabled' => 'nullable|in:0,1',
            'level_5_user_id' => 'nullable|exists:users,id',
            'level_5_role_id' => 'nullable|exists:roles,id',
        ]);

        // Validate that enabled levels have either user or role assigned
        for ($level = 1; $level <= 5; $level++) {
            if ($request->get("level_{$level}_enabled")) {
                $userId = $request->get("level_{$level}_user_id");
                $roleId = $request->get("level_{$level}_role_id");
                
                if (!$userId && !$roleId) {
                    return back()->withErrors([
                        "level_{$level}_user_id" => "Level {$level} must have either a user or role assigned when enabled."
                    ])->withInput();
                }
            }
        }

        try {
            DB::beginTransaction();

            // Get existing settings or create new
            $settings = StoreRequisitionApprovalSettings::where('company_id', $companyId)->first();

            $data = [
                'company_id' => $companyId,
                'level_1_enabled' => $request->boolean('level_1_enabled'),
                'level_1_user_id' => $request->level_1_enabled ? $request->level_1_user_id : null,
                'level_1_role_id' => $request->level_1_enabled ? $request->level_1_role_id : null,
                'level_2_enabled' => $request->boolean('level_2_enabled'),
                'level_2_user_id' => $request->level_2_enabled ? $request->level_2_user_id : null,
                'level_2_role_id' => $request->level_2_enabled ? $request->level_2_role_id : null,
                'level_3_enabled' => $request->boolean('level_3_enabled'),
                'level_3_user_id' => $request->level_3_enabled ? $request->level_3_user_id : null,
                'level_3_role_id' => $request->level_3_enabled ? $request->level_3_role_id : null,
                'level_4_enabled' => $request->boolean('level_4_enabled'),
                'level_4_user_id' => $request->level_4_enabled ? $request->level_4_user_id : null,
                'level_4_role_id' => $request->level_4_enabled ? $request->level_4_role_id : null,
                'level_5_enabled' => $request->boolean('level_5_enabled'),
                'level_5_user_id' => $request->level_5_enabled ? $request->level_5_user_id : null,
                'level_5_role_id' => $request->level_5_enabled ? $request->level_5_role_id : null,
                'updated_by' => $user->id,
            ];

            if ($settings) {
                $settings->update($data);
                $message = 'Store requisition approval settings updated successfully.';
            } else {
                $data['created_by'] = $user->id;
                StoreRequisitionApprovalSettings::create($data);
                $message = 'Store requisition approval settings created successfully.';
            }

            DB::commit();

            return redirect()->route('store-requisitions.approval-settings.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Store Requisition Approval Settings Save Failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to save approval settings. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Get approval summary for dashboard
     */
    public function getSummary()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $settings = StoreRequisitionApprovalSettings::where('company_id', $companyId)->first();

        if (!$settings) {
            return response()->json([
                'success' => true,
                'data' => [
                    'configured' => false,
                    'enabled_levels' => 0,
                    'max_level' => 0,
                    'levels' => []
                ]
            ]);
        }

        $enabledLevels = [];
        $maxLevel = 0;

        for ($level = 1; $level <= 5; $level++) {
            if ($settings->{"level_{$level}_enabled"}) {
                $maxLevel = $level;
                $user = $settings->{"level{$level}User"};
                $role = $settings->{"level{$level}Role"};
                
                $enabledLevels[] = [
                    'level' => $level,
                    'user' => $user ? $user->name : null,
                    'role' => $role ? $role->name : null,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'configured' => true,
                'enabled_levels' => count($enabledLevels),
                'max_level' => $maxLevel,
                'levels' => $enabledLevels
            ]
        ]);
    }

    /**
     * Reset approval settings
     */
    public function reset()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        try {
            DB::beginTransaction();

            $settings = StoreRequisitionApprovalSettings::where('company_id', $companyId)->first();

            if ($settings) {
                $settings->update([
                    'level_1_enabled' => false,
                    'level_1_user_id' => null,
                    'level_1_role_id' => null,
                    'level_2_enabled' => false,
                    'level_2_user_id' => null,
                    'level_2_role_id' => null,
                    'level_3_enabled' => false,
                    'level_3_user_id' => null,
                    'level_3_role_id' => null,
                    'level_4_enabled' => false,
                    'level_4_user_id' => null,
                    'level_4_role_id' => null,
                    'level_5_enabled' => false,
                    'level_5_user_id' => null,
                    'level_5_role_id' => null,
                    'updated_by' => $user->id,
                ]);
            }

            DB::commit();

            return redirect()->route('store-requisitions.approval-settings.index')
                ->with('success', 'Store requisition approval settings reset successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Store Requisition Approval Settings Reset Failed: ' . $e->getMessage());
            
            return back()->withErrors(['error' => 'Failed to reset approval settings. Please try again.']);
        }
    }

    /**
     * Get users by role for AJAX
     */
    public function getUsersByRole(Request $request)
    {
        $roleId = $request->get('role_id');
        $companyId = Auth::user()->company_id;

        if (!$roleId) {
            return response()->json([
                'success' => false,
                'message' => 'Role ID is required.'
            ], 400);
        }

        $users = User::where('status', 'active')
            ->when(Auth::user()->branch_id, function ($query) {
                return $query->where('branch_id', Auth::user()->branch_id);
            })
            ->whereHas('roles', function ($q) use ($roleId) {
                $q->where('roles.id', $roleId);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }

    /**
     * Test approval configuration
     */
    public function testConfiguration()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $settings = StoreRequisitionApprovalSettings::where('company_id', $companyId)->first();

        if (!$settings) {
            return response()->json([
                'success' => false,
                'message' => 'No approval settings configured.'
            ], 400);
        }

        $errors = [];
        $warnings = [];

        // Check each enabled level
        for ($level = 1; $level <= 5; $level++) {
            if ($settings->{"level_{$level}_enabled"}) {
                $userId = $settings->{"level_{$level}_user_id"};
                $roleId = $settings->{"level_{$level}_role_id"};

                if (!$userId && !$roleId) {
                    $errors[] = "Level {$level} is enabled but has no user or role assigned.";
                }

                if ($userId) {
                    $userExists = User::where('id', $userId)
                        ->where('status', 'active')
                        ->exists();
                    
                    if (!$userExists) {
                        $errors[] = "Level {$level} user is inactive or not found.";
                    }
                }

                if ($roleId) {
                    $roleExists = Role::where('id', $roleId)->exists();
                    
                    if (!$roleExists) {
                        $errors[] = "Level {$level} role not found.";
                    }
                }
            }
        }

        // Check for gaps in approval levels
        $previousLevelEnabled = false;
        for ($level = 1; $level <= 5; $level++) {
            $currentLevelEnabled = $settings->{"level_{$level}_enabled"};
            
            if ($currentLevelEnabled && $level > 1 && !$previousLevelEnabled) {
                $warnings[] = "Level {$level} is enabled but previous levels are disabled. This may cause workflow issues.";
            }
            
            $previousLevelEnabled = $currentLevelEnabled;
        }

        return response()->json([
            'success' => count($errors) === 0,
            'message' => count($errors) === 0 ? 'Configuration is valid.' : 'Configuration has errors.',
            'errors' => $errors,
            'warnings' => $warnings
        ]);
    }
}
