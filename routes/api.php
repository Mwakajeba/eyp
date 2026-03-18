<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HrAuthController;
use App\Http\Controllers\Api\HrMobileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// ==================== PUBLIC API ROUTES (No Authentication) ====================

// Public Room API (for website)
Route::prefix('rooms')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\RoomApiController::class, 'index']);
    Route::get('/{id}', [App\Http\Controllers\Api\RoomApiController::class, 'show']);
});

// Public Bookings API (for website)
Route::prefix('bookings')->group(function () {
    Route::get('/available-rooms', [App\Http\Controllers\Hotel\BookingController::class, 'availableRoomsApi']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [App\Http\Controllers\Hotel\BookingController::class, 'createOnlineBookingApi']);
        Route::get('/', [App\Http\Controllers\Hotel\BookingController::class, 'getMyBookingsApi']);
        Route::get('/{booking}', [App\Http\Controllers\Hotel\BookingController::class, 'getMyBookingByIdApi']);
        Route::get('/{booking}/receipt', [App\Http\Controllers\Hotel\BookingController::class, 'downloadReceiptApi']);
        Route::post('/{booking}/cancel', [App\Http\Controllers\Hotel\BookingController::class, 'cancelBookingApi']);
    });
});

// Company Settings API (for website)
Route::prefix('settings')->group(function () {
    Route::get('/company', [App\Http\Controllers\SettingsController::class, 'getCompanySettingsApi']);
});

// Guest Authentication API (for website)
Route::prefix('guest')->group(function () {
    Route::post('/register', [App\Http\Controllers\Api\GuestApiController::class, 'register']);
    Route::post('/login', [App\Http\Controllers\Api\GuestApiController::class, 'login']);
    Route::get('/bank-accounts', [App\Http\Controllers\Api\GuestApiController::class, 'getBankAccounts']); // Public endpoint for bank accounts
    Route::get('/branches', [App\Http\Controllers\Api\GuestApiController::class, 'getBranches']); // Public endpoint for branches
    Route::post('/messages', [App\Http\Controllers\Api\GuestApiController::class, 'sendMessage']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [App\Http\Controllers\Api\GuestApiController::class, 'me']);
        Route::put('/profile', [App\Http\Controllers\Api\GuestApiController::class, 'updateProfile']);
        Route::get('/messages', [App\Http\Controllers\Api\GuestApiController::class, 'getMyMessages']);
        Route::post('/logout', [App\Http\Controllers\Api\GuestApiController::class, 'logout']);
    });
});

// HR Employee Authentication
Route::prefix('hr')->group(function () {
    Route::post('/login', [HrAuthController::class, 'login'])->middleware('throttle.api');
    Route::post('/forgot-password', [HrAuthController::class, 'forgotPassword'])->middleware('throttle.api');
    Route::post('/verify-otp', [HrAuthController::class, 'verifyOtp'])->middleware('throttle.api');
    Route::post('/reset-password', [HrAuthController::class, 'resetPassword'])->middleware('throttle.api');
});

// Driver Portal Authentication (Tanzania phone + password from /users/create)
Route::prefix('driver')->group(function () {
    Route::post('/login', [App\Http\Controllers\Api\DriverAuthController::class, 'login'])->middleware('throttle.api');
    Route::post('/forgot-password', [App\Http\Controllers\Api\DriverAuthController::class, 'forgotPassword'])->middleware('throttle.api');
    Route::post('/verify-otp', [App\Http\Controllers\Api\DriverAuthController::class, 'verifyOtp'])->middleware('throttle.api');
    Route::post('/reset-password', [App\Http\Controllers\Api\DriverAuthController::class, 'resetPassword'])->middleware('throttle.api');
});

// ==================== PROTECTED API ROUTES (Require Authentication) ====================

Route::middleware('auth:sanctum')->group(function () {
    
    // ==================== TEACHER ROUTES (Future Implementation) ====================
    Route::prefix('teacher')->group(function () {
        // Will be implemented when teacher mobile app is needed
    });
    
    // ==================== HR EMPLOYEE ROUTES ====================
    Route::prefix('hr')->group(function () {
        // Authentication & Profile
        Route::post('/logout', [HrAuthController::class, 'logout']);
        Route::get('/me', [HrAuthController::class, 'me']);
        Route::put('/profile', [HrAuthController::class, 'updateProfile']);
        Route::put('/change-password', [HrAuthController::class, 'changePassword']);
        
        // Dashboard & Overview
        Route::get('/dashboard', [HrMobileController::class, 'dashboard']);
        
        // Leave Management
        Route::get('/leave/balances', [HrMobileController::class, 'leaveBalances']);
        Route::get('/leave/types', [HrMobileController::class, 'leaveTypes']);
        Route::get('/leave/requests', [HrMobileController::class, 'leaveRequests']);
        Route::post('/leave/apply', [HrMobileController::class, 'applyLeave']);
        
        // Attendance
        Route::get('/attendance', [HrMobileController::class, 'attendance']);
        
        // Payslips
        Route::get('/payslips', [HrMobileController::class, 'payslips']);
        
        // Manager Approvals
        Route::get('/approvals/pending', [HrMobileController::class, 'getPendingApprovals']);
        Route::post('/approvals/imprest/{approvalId}/approve', [HrMobileController::class, 'approveImprest']);
        Route::post('/approvals/imprest/{approvalId}/reject', [HrMobileController::class, 'rejectImprest']);
        Route::post('/approvals/requisition/{id}/approve', [HrMobileController::class, 'approveRequisition']);
        Route::post('/approvals/requisition/{id}/reject', [HrMobileController::class, 'rejectRequisition']);
        
        // Notifications
        Route::get('/notifications', [HrMobileController::class, 'getNotifications']);
        Route::get('/notifications/unread-count', [HrMobileController::class, 'getUnreadNotificationsCount']);
        Route::put('/notifications/{id}/read', [HrMobileController::class, 'markNotificationAsRead']);
        Route::put('/notifications/read-all', [HrMobileController::class, 'markAllNotificationsAsRead']);
        
        // Imprest Management
        Route::get('/imprest', [HrMobileController::class, 'imprestRequests']);
        Route::get('/imprest/{id}', [HrMobileController::class, 'imprestDetails']);
        Route::post('/imprest', [HrMobileController::class, 'createImprest']);
        Route::get('/expense-accounts', [HrMobileController::class, 'expenseAccounts']);
        Route::get('/departments', [HrMobileController::class, 'departments']);
        
        // Retirement Management
        Route::get('/retirement', [HrMobileController::class, 'retirementRequests']);
        Route::get('/retirement/{id}', [HrMobileController::class, 'retirementDetails']);
        Route::post('/retirement', [HrMobileController::class, 'createRetirement']);
        Route::get('/retirement/eligible-imprest', [HrMobileController::class, 'eligibleImprestForRetirement']);
        
        // Store Requisition
        Route::get('/store-requisitions', [HrMobileController::class, 'storeRequisitions']);
        Route::get('/store-requisitions/{id}', [HrMobileController::class, 'storeRequisitionDetails']);
        Route::post('/store-requisitions', [HrMobileController::class, 'createStoreRequisition']);
        Route::get('/inventory-items', [HrMobileController::class, 'inventoryItems']);
    });

    // ==================== DRIVER PORTAL ROUTES ====================
    Route::prefix('driver')->group(function () {
        Route::post('/logout', [App\Http\Controllers\Api\DriverAuthController::class, 'logout']);
        Route::get('/me', [App\Http\Controllers\Api\DriverAuthController::class, 'me']);
        Route::put('/change-password', [App\Http\Controllers\Api\DriverAuthController::class, 'changePassword']);
        Route::get('/fuel-options', [App\Http\Controllers\Api\DriverTripController::class, 'fuelOptions']);

        // Trip Management
        Route::prefix('trips')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\DriverTripController::class, 'index']);
            Route::get('/upcoming', [App\Http\Controllers\Api\DriverTripController::class, 'upcoming']);
            Route::get('/active', [App\Http\Controllers\Api\DriverTripController::class, 'active']);
            Route::get('/{id}', [App\Http\Controllers\Api\DriverTripController::class, 'show']);
            Route::post('/{id}/start', [App\Http\Controllers\Api\DriverTripController::class, 'start']);
            Route::post('/{id}/update-location', [App\Http\Controllers\Api\DriverTripController::class, 'updateLocation']);
            Route::post('/{id}/complete', [App\Http\Controllers\Api\DriverTripController::class, 'complete']);
            Route::post('/{id}/report-delay', [App\Http\Controllers\Api\DriverTripController::class, 'reportDelay']);
            Route::post('/{id}/log-fuel', [App\Http\Controllers\Api\DriverTripController::class, 'logFuel']);
            Route::post('/{id}/add-expense', [App\Http\Controllers\Api\DriverTripController::class, 'addExpense']);
            Route::post('/{id}/report-incident', [App\Http\Controllers\Api\DriverTripController::class, 'reportIncident']);
        });
    });
    
    // ==================== ADMIN ROUTES (Future Implementation) ====================
    Route::prefix('admin')->group(function () {
        // Will be implemented when admin mobile app is needed
    });
});

// ==================== BIOMETRIC DEVICE API ROUTES (API Key/Secret Authentication) ====================
Route::prefix('biometric')->group(function () {
    Route::post('/punch', [App\Http\Controllers\Api\BiometricApiController::class, 'receivePunch']);
    Route::post('/punches', [App\Http\Controllers\Api\BiometricApiController::class, 'receiveBulkPunches']);
    Route::get('/status', [App\Http\Controllers\Api\BiometricApiController::class, 'getStatus']);
});

// ==================== WEBHOOK ROUTES (No Authentication Required) ====================
Route::prefix('webhooks')->group(function () {
    Route::post('/lipisha', [App\Http\Controllers\WebhookController::class, 'lipisha']);
});
