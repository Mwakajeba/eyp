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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Helpers\SmsHelper;

class DriverAuthController extends Controller
{
    /**
     * Driver login API – Tanzania phone + password (users created at /users/create).
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        if (LoginAttempt::isLockedOut($request->phone)) {
            $remainingTime = LoginAttempt::getRemainingLockoutTime($request->phone);
            ActivityLog::create([
                'user_id'     => null,
                'model'       => 'Auth',
                'action'      => 'login_failed',
                'description' => "Driver login blocked - too many attempts for {$request->phone}",
                'ip_address'  => $request->ip(),
                'device'      => $request->userAgent(),
                'activity_time' => now(),
            ]);
            return response()->json([
                'success' => false,
                'message' => "Account is temporarily locked. Please try again in {$remainingTime} minutes.",
            ], 429);
        }

        $user = find_user_by_phone($request->phone);

        if (!$user) {
            LoginAttempt::record($request->phone, $request->ip(), $request->userAgent(), false);
            ActivityLog::create([
                'user_id'     => null,
                'model'       => 'Auth',
                'action'      => 'login_failed',
                'description' => "Driver login failed - phone not found ({$request->phone})",
                'ip_address'  => $request->ip(),
                'device'      => $request->userAgent(),
                'activity_time' => now(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number or password.',
            ], 401);
        }

        if ($user->status !== 'active' || $user->is_active !== 'yes') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact administrator.',
            ], 403);
        }

        if (!Hash::check($request->password, $user->password)) {
            LoginAttempt::record($request->phone, $request->ip(), $request->userAgent(), false);
            ActivityLog::create([
                'user_id'     => $user->id,
                'model'       => 'Auth',
                'action'      => 'login_failed',
                'description' => 'Driver login failed - wrong password',
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

        LoginAttempt::record($user->phone, $request->ip(), $request->userAgent(), true);
        LoginAttempt::clearOldAttempts();

        $token = $user->createToken('driver-api-token', ['driver'])->plainTextToken;

        ActivityLog::create([
            'user_id'     => $user->id,
            'model'       => 'Auth',
            'action'      => 'login_success',
            'description' => "User (ID: {$user->id}) logged in via Driver API",
            'ip_address'  => $request->ip(),
            'device'      => $request->userAgent(),
            'activity_time' => now(),
        ]);

        $employee = $user->employee;

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
                    'role' => 'driver',
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

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();
        ActivityLog::create([
            'user_id'     => $user->id,
            'model'       => 'Auth',
            'action'      => 'logout',
            'description' => "User (ID: {$user->id}) logged out via Driver API",
            'ip_address'  => $request->ip(),
            'device'      => $request->userAgent(),
            'activity_time' => now(),
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['employee.department', 'employee.position', 'fleetDriver.assignedVehicle']);
        $employee = $user->employee;
        $fleetDriver = $user->fleetDriver;

        $vehicleDisplay = null;
        if ($fleetDriver && $fleetDriver->assignedVehicle) {
            $v = $fleetDriver->assignedVehicle;
            $vehicleDisplay = $v->registration_number ?: ($v->code ?: $v->name);
        }

        $currentTrip = null;
        if ($fleetDriver) {
            $trip = $fleetDriver->trips()
                ->whereIn('status', ['dispatched', 'in_progress'])
                ->with(['vehicle', 'route', 'customer'])
                ->orderByDesc('planned_start_date')
                ->first();
            if ($trip) {
                $currentTrip = [
                    'id' => $trip->id,
                    'trip_number' => $trip->trip_number,
                    'status' => $trip->status,
                    'trip_type' => $trip->trip_type,
                    'origin_location' => $trip->origin_location,
                    'destination_location' => $trip->destination_location,
                    'planned_start_date' => $trip->planned_start_date?->toIso8601String(),
                    'planned_end_date' => $trip->planned_end_date?->toIso8601String(),
                    'actual_start_date' => $trip->actual_start_date?->toIso8601String(),
                    'actual_end_date' => $trip->actual_end_date?->toIso8601String(),
                    'start_latitude' => $trip->start_latitude,
                    'start_longitude' => $trip->start_longitude,
                    'start_location_name' => $trip->start_location_name,
                    'last_location_lat' => $trip->last_location_lat,
                    'last_location_lng' => $trip->last_location_lng,
                    'last_location_at' => $trip->last_location_at?->toIso8601String(),
                    'last_location_name' => $trip->last_location_name,
                    'cargo_description' => $trip->cargo_description,
                    'notes' => $trip->notes,
                    'customer' => $trip->customer ? [
                        'id' => $trip->customer->id,
                        'name' => $trip->customer->name,
                    ] : null,
                    'vehicle' => $trip->vehicle ? [
                        'id' => $trip->vehicle->id,
                        'name' => $trip->vehicle->name,
                        'registration_number' => $trip->vehicle->registration_number,
                        'code' => $trip->vehicle->code,
                    ] : null,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => 'driver',
                'vehicle' => $vehicleDisplay,
                'employee' => $employee ? [
                    'id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'department' => $employee->department ? $employee->department->name : null,
                    'position' => $employee->position ? $employee->position->name : null,
                ] : null,
                'current_trip' => $currentTrip,
            ],
        ]);
    }

    /**
     * Change password (driver).
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'min:8', 'confirmed', new PasswordValidation($user)],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $passwordService = new PasswordService();
        $passwordService->updatePassword($user, $request->password);

        ActivityLog::create([
            'user_id'     => $user->id,
            'model'       => 'User',
            'action'      => 'password_changed',
            'description' => "User (ID: {$user->id}) changed password via Driver API",
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
     * Forgot password – send OTP to phone.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $user = find_user_by_phone($request->phone);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number not found.',
            ], 404);
        }

        $verification_code = rand(100000, 999999);

        OtpCode::create([
            'phone' => $user->phone,
            'code' => $verification_code,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        $message = 'Your OTP code is ' . $verification_code . '. Valid for 5 minutes.';
        SmsHelper::send($user->phone, $message);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your phone number',
            'data' => [
                'phone' => mask_phone_number($user->phone),
            ],
        ]);
    }

    /**
     * Verify OTP for password reset.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

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

        $otp->update(['is_used' => 1]);

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
     * Reset password after OTP verification.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = find_user_by_phone($request->phone);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $validator = \Validator::make($request->all(), [
            'password' => ['required', 'string', 'min:8', 'confirmed', new PasswordValidation($user)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('password'),
            ], 422);
        }

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

        $passwordService = new PasswordService();
        $passwordService->updatePassword($user, $request->password);

        $user->tokens()
            ->where('name', 'password-reset-token')
            ->delete();

        ActivityLog::create([
            'user_id'     => $user->id,
            'model'       => 'User',
            'action'      => 'password_reset',
            'description' => "User (ID: {$user->id}) reset password via Driver API",
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
