<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hr\BiometricDevice;
use App\Models\Hr\BiometricLog;
use App\Services\Hr\BiometricService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BiometricApiController extends Controller
{
    protected $biometricService;

    public function __construct(BiometricService $biometricService)
    {
        $this->biometricService = $biometricService;
    }

    /**
     * Receive attendance data from biometric device
     * POST /api/biometric/punch
     */
    public function receivePunch(Request $request)
    {
        try {
            // Authenticate device
            $device = $this->authenticateDevice($request);
            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid device credentials'
                ], 401);
            }

            // Validate request data
            $validated = $request->validate([
                'user_id' => 'required|string',
                'punch_time' => 'required|date',
                'punch_type' => 'required|in:check_in,check_out,break_in,break_out',
                'punch_mode' => 'nullable|string',
            ]);

            // Create biometric log
            $log = BiometricLog::create([
                'device_id' => $device->id,
                'device_user_id' => $validated['user_id'],
                'punch_time' => Carbon::parse($validated['punch_time'])->setTimezone($device->timezone),
                'punch_type' => $validated['punch_type'],
                'punch_mode' => $validated['punch_mode'] ?? 'fingerprint',
                'raw_data' => $request->all(),
                'status' => BiometricLog::STATUS_PENDING,
            ]);

            // Process log immediately
            $processed = $this->biometricService->processBiometricLog($log);

            return response()->json([
                'success' => true,
                'message' => 'Punch data received',
                'log_id' => $log->id,
                'processed' => $processed,
            ]);

        } catch (\Exception $e) {
            Log::error('Biometric API error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing punch data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk receive attendance data from biometric device
     * POST /api/biometric/punches
     */
    public function receiveBulkPunches(Request $request)
    {
        try {
            // Authenticate device
            $device = $this->authenticateDevice($request);
            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid device credentials'
                ], 401);
            }

            // Validate request data
            $validated = $request->validate([
                'punches' => 'required|array',
                'punches.*.user_id' => 'required|string',
                'punches.*.punch_time' => 'required|date',
                'punches.*.punch_type' => 'required|in:check_in,check_out,break_in,break_out',
                'punches.*.punch_mode' => 'nullable|string',
            ]);

            $processed = 0;
            $failed = 0;

            foreach ($validated['punches'] as $punchData) {
                try {
                    $log = BiometricLog::create([
                        'device_id' => $device->id,
                        'device_user_id' => $punchData['user_id'],
                        'punch_time' => Carbon::parse($punchData['punch_time'])->setTimezone($device->timezone),
                        'punch_type' => $punchData['punch_type'],
                        'punch_mode' => $punchData['punch_mode'] ?? 'fingerprint',
                        'raw_data' => $punchData,
                        'status' => BiometricLog::STATUS_PENDING,
                    ]);

                    if ($this->biometricService->processBiometricLog($log)) {
                        $processed++;
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::error('Failed to process punch', [
                        'punch_data' => $punchData,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk punches received',
                'processed' => $processed,
                'failed' => $failed,
                'total' => count($validated['punches']),
            ]);

        } catch (\Exception $e) {
            Log::error('Biometric API bulk error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing bulk punches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get device status
     * GET /api/biometric/status
     */
    public function getStatus(Request $request)
    {
        try {
            $device = $this->authenticateDevice($request);
            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid device credentials'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'device' => [
                    'device_code' => $device->device_code,
                    'device_name' => $device->device_name,
                    'is_active' => $device->is_active,
                    'last_sync_at' => $device->last_sync_at?->toIso8601String(),
                    'timezone' => $device->timezone,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Authenticate device using API key/secret
     */
    protected function authenticateDevice(Request $request): ?BiometricDevice
    {
        $apiKey = $request->header('X-API-Key') ?? $request->input('api_key');
        $apiSecret = $request->header('X-API-Secret') ?? $request->input('api_secret');

        if (!$apiKey || !$apiSecret) {
            return null;
        }

        $device = BiometricDevice::where('api_key', $apiKey)
            ->where('is_active', true)
            ->first();

        if ($device && $device->verifyApiCredentials($apiKey, $apiSecret)) {
            return $device;
        }

        return null;
    }
}

