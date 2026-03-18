<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use App\Models\ActivityLog;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\PasswordService;
use App\Rules\PasswordValidation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use App\Helpers\SmsHelper;

class HrAuthController extends Controller
{
    /**
     * HR Employee login API endpoint
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        // Check if this specific user (phone) is locked out
        if (LoginAttempt::isLockedOut($request->phone)) {
            $remainingTime = LoginAttempt::getRemainingLockoutTime($request->phone);

            ActivityLog::create([
                'user_id'     => null,
                'model'       => 'Auth',
                'action'      => 'login_failed',
                'description' => "HR login blocked - too many attempts for {$request->phone}",
                'ip_address'  => $request->ip(),
                'device'      => $request->userAgent(),
                'activity_time' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => "Account is temporarily locked. Please try again in {$remainingTime} minutes.",
            ], 429);
        }

        // Find user by phone
        $user = find_user_by_phone($request->phone);

        if (!$user) {
            LoginAttempt::record($request->phone, $request->ip(), $request->userAgent(), false);

            ActivityLog::create([
                'user_id'     => null,
                'model'       => 'Auth',
                'action'      => 'login_failed',
                'description' => "HR login failed - phone not found ({$request->phone})",
                'ip_address'  => $request->ip(),
                'device'      => $request->userAgent(),
                'activity_time' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number or password.',
            ], 401);
        }

        // Check if user is active
        if ($user->status !== 'active' || $user->is_active !== 'yes') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact administrator.',
            ], 403);
        }

        // Check if user has assigned branches (required for login)
        $assignedBranchIds = $user->branches()->pluck('branches.id')->toArray();
        if (empty($assignedBranchIds) && !$user->branch_id) {
            ActivityLog::create([
                'user_id'     => $user->id,
                'model'       => 'Auth',
                'action'      => 'login_failed',
                'description' => 'HR login failed - no branch assigned',
                'ip_address'  => $request->ip(),
                'device'      => $request->userAgent(),
                'activity_time' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No branch assigned to your account. Please contact administrator.',
            ], 403);
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            LoginAttempt::record($request->phone, $request->ip(), $request->userAgent(), false);

            ActivityLog::create([
                'user_id'     => $user->id,
                'model'       => 'Auth',
                'action'      => 'login_failed',
                'description' => 'HR login failed - wrong password',
                'ip_address'  => $request->ip(),
                'device'      => $request->userAgent(),
                'activity_time' => now(),
            ]);

            if (LoginAttempt::isLockedOut($request->phone)) {
                $remainingTime = LoginAttempt::getRemainingLockoutTime($request->phone);

                return response()->json([
                    'success' => false,
                    'message' => "Too many failed attempts. Account is locked for {$remainingTime} minutes.",
                ], 429);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number or password.',
            ], 401);
        }

        // Record successful login attempt
        LoginAttempt::record($user->phone, $request->ip(), $request->userAgent(), true);
        LoginAttempt::clearOldAttempts();

        // Create API token using Sanctum
        $token = $user->createToken('hr-api-token', ['hr'])->plainTextToken;

        // Get user roles and permissions
        $roles = $user->roles->pluck('name');
        $permissions = $user->getAllPermissions()->pluck('name');

        // Log successful login
        ActivityLog::create([
            'user_id'     => $user->id,
            'model'       => 'Auth',
            'action'      => 'login_success',
            'description' => "User (ID: {$user->id}) logged in via HR API",
            'ip_address'  => $request->ip(),
            'device'      => $request->userAgent(),
            'activity_time' => now(),
        ]);

        // Get employee data if linked
        $employee = $user->employee;

        // Get user's assigned branches and locations (qualify columns to avoid ambiguity)
        $branches = $user->branches()
            ->select('branches.id', 'branches.name', 'branches.company_id')
            ->get();
        $locations = $user->locations()
            ->select('inventory_locations.id', 'inventory_locations.name', 'inventory_locations.branch_id')
            ->with('branch:id,name')
            ->get();
        
        // Determine default branch (prefer assigned branches, fallback to user's branch_id)
        $defaultBranchId = !empty($assignedBranchIds) ? reset($assignedBranchIds) : $user->branch_id;
        $defaultBranch = $branches->firstWhere('id', $defaultBranchId) 
            ?? ($user->branch ? ['id' => $user->branch->id, 'name' => $user->branch->name] : null);

        // Get default location
        $defaultLocation = $user->defaultLocation()->first();
        if (!$defaultLocation) {
            $defaultLocation = $user->locations()->first();
        }

        // Return success response with token and user data
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'role' => 'employee',
                    'roles' => $roles,
                    'permissions' => $permissions,
                    'company_id' => $user->company_id,
                    'branch_id' => $defaultBranchId,
                    'location_id' => $defaultLocation ? $defaultLocation->id : null,
                    'branches' => $branches->map(function ($branch) {
                        return [
                            'id' => $branch->id,
                            'name' => $branch->name,
                        ];
                    }),
                    'locations' => $locations->map(function ($location) {
                        return [
                            'id' => $location->id,
                            'name' => $location->name,
                            'branch_id' => $location->branch_id,
                            'branch_name' => $location->branch ? $location->branch->name : null,
                        ];
                    }),
                    'employee' => $employee ? [
                        'id' => $employee->id,
                        'employee_number' => $employee->employee_number,
                        'department' => $employee->department ? $employee->department->name : null,
                        'position' => $employee->position ? $employee->position->name : null,
                    ] : null,
                ],
            ],
        ]);
    }

    /**
     * Logout API endpoint
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        ActivityLog::create([
            'user_id'     => $user->id,
            'model'       => 'Auth',
            'action'      => 'logout',
            'description' => "User (ID: {$user->id}) logged out via HR API",
            'ip_address'  => $request->ip(),
            'device'      => $request->userAgent(),
            'activity_time' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get current user profile
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        $roles = $user->roles->pluck('name');
        $permissions = $user->getAllPermissions()->pluck('name');

        // Get user's assigned branches and locations
        $assignedBranchIds = $user->branches()->pluck('branches.id')->toArray();
        $branches = $user->branches()
            ->select('branches.id', 'branches.name', 'branches.company_id')
            ->get();
        $locations = $user->locations()
            ->select('inventory_locations.id', 'inventory_locations.name', 'inventory_locations.branch_id')
            ->with('branch:id,name')
            ->get();
        
        // Determine default branch
        $defaultBranchId = !empty($assignedBranchIds) ? reset($assignedBranchIds) : $user->branch_id;
        
        // Get default location
        $defaultLocation = $user->defaultLocation()->first();
        if (!$defaultLocation) {
            $defaultLocation = $user->locations()->first();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => 'employee',
                'roles' => $roles,
                'permissions' => $permissions,
                'company_id' => $user->company_id,
                'branch_id' => $defaultBranchId,
                'location_id' => $defaultLocation ? $defaultLocation->id : null,
                'branches' => $branches->map(function ($branch) {
                    return [
                        'id' => $branch->id,
                        'name' => $branch->name,
                    ];
                }),
                'locations' => $locations->map(function ($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'branch_id' => $location->branch_id,
                        'branch_name' => $location->branch ? $location->branch->name : null,
                    ];
                }),
                'employee' => $employee ? [
                    'id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'department' => $employee->department ? $employee->department->name : null,
                    'position' => $employee->position ? $employee->position->name : null,
                ] : null,
            ],
        ]);
    }

    /**
     * Update user profile
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $emailRules = 'nullable|email';
        if ($request->email !== $user->email) {
            $emailRules .= '|unique:users,email,' . $user->id . ',id,company_id,' . $user->company_id;
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone,' . $user->id . ',id,company_id,' . $user->company_id,
            'email' => $emailRules,
        ]);

        $user->update([
            'name' => $request->name,
            'phone' => normalize_phone_number($request->phone),
            'email' => $request->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Change password
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'min:8', 'confirmed', new PasswordValidation($user)],
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        // Update password using PasswordService
        $passwordService = new PasswordService();
        $passwordService->updatePassword($user, $request->password);

        ActivityLog::create([
            'user_id'     => $user->id,
            'model'       => 'User',
            'action'      => 'password_changed',
            'description' => "User (ID: {$user->id}) changed password via HR API",
            'ip_address'  => $request->ip(),
            'device'      => $request->userAgent(),
            'activity_time' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Forgot password - request OTP
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        // Find user by phone
        $user = find_user_by_phone($request->phone);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number not found.',
            ], 404);
        }

        // Generate OTP
        $verification_code = rand(100000, 999999);

        OtpCode::create([
            'phone' => $user->phone,
            'code' => $verification_code,
            'expires_at' => Carbon::now()->addMinutes(5)
        ]);

        // Send SMS
        $message = 'Your OTP code is ' . $verification_code . '. Valid for 5 minutes.';
        SmsHelper::send($user->phone, $message);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your phone number',
            'data' => [
                'phone' => mask_phone_number($user->phone), // Mask phone for security
            ],
        ]);
    }

    /**
     * Verify OTP for password reset
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        // Find user by phone
        $user = find_user_by_phone($request->phone);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number not found.',
            ], 404);
        }

        $otp = OtpCode::where('phone', $user->phone)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->where('is_used', 0)
            ->latest()
            ->first();

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP code.',
            ], 422);
        }

        // Mark OTP as used
        $otp->update(['is_used' => 1]);

        // Create a temporary token for password reset (valid for 10 minutes)
        $resetToken = $user->createToken('password-reset-token', ['password-reset'], now()->addMinutes(10))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
            'data' => [
                'reset_token' => $resetToken,
            ],
        ]);
    }

    /**
     * Reset password after OTP verification
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Find user by phone
        $user = find_user_by_phone($request->phone);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        // Validate password using PasswordValidation rule
        $validator = \Validator::make($request->all(), [
            'password' => ['required', 'string', 'min:8', 'confirmed', new PasswordValidation($user)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('password'),
            ], 422);
        }

        // Check if user has password reset token
        $hasResetToken = $user->tokens()
            ->where('name', 'password-reset-token')
            ->where('expires_at', '>', now())
            ->exists();

        if (!$hasResetToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset token. Please request a new OTP.',
            ], 422);
        }

        // Update password using PasswordService
        $passwordService = new PasswordService();
        $passwordService->updatePassword($user, $request->password);

        // Revoke all password reset tokens
        $user->tokens()
            ->where('name', 'password-reset-token')
            ->delete();

        ActivityLog::create([
            'user_id'     => $user->id,
            'model'       => 'User',
            'action'      => 'password_reset',
            'description' => "User (ID: {$user->id}) reset password via HR API",
            'ip_address'  => $request->ip(),
            'device'      => $request->userAgent(),
            'activity_time' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. You can now login.',
        ]);
    }
}

