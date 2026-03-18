<?php

namespace App\Services\Hr;

use App\Models\Hr\BiometricDevice;
use App\Models\Hr\BiometricLog;
use Rats\Zkteco\Lib\ZKTeco;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ZKTecoService
{
    const DEFAULT_PORT = 4370;

    /**
     * Get or create ZKTeco instance for device
     */
    protected function getZKTecoInstance(BiometricDevice $device): ?ZKTeco
    {
        $ip = $device->ip_address;
        $port = $device->port ?? self::DEFAULT_PORT;

        if (!$ip) {
            return null;
        }

        return new ZKTeco($ip, $port);
    }

    /**
     * Connect to ZKTeco device
     */
    public function connect(BiometricDevice $device): array
    {
        try {
            // Check if already connected
            if ($this->isConnected($device)) {
                return [
                    'success' => true,
                    'message' => 'Already connected to device',
                    'connected' => true,
                ];
            }

            $zk = $this->getZKTecoInstance($device);
            if (!$zk) {
                return [
                    'success' => false,
                    'message' => 'Device IP address is not configured',
                    'connected' => false,
                ];
            }

            // Connect using SDK
            $connected = $zk->connect();
            if (!$connected) {
                return [
                    'success' => false,
                    'message' => 'Failed to connect to device at ' . $device->ip_address . ':' . ($device->port ?? self::DEFAULT_PORT),
                    'connected' => false,
                ];
            }

            // Store connection in cache
            $this->storeConnection($device->id, $zk);

            Log::info('ZKTeco device connected', [
                'device_id' => $device->id,
                'ip' => $device->ip_address,
                'port' => $device->port ?? self::DEFAULT_PORT,
            ]);

            return [
                'success' => true,
                'message' => 'Successfully connected to device',
                'connected' => true,
            ];
        } catch (\Exception $e) {
            Log::error('ZKTeco connection error', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage(),
                'connected' => false,
            ];
        }
    }

    /**
     * Disconnect from ZKTeco device
     */
    public function disconnect(BiometricDevice $device): array
    {
        try {
            $zk = $this->getConnection($device->id);

            if ($zk === null) {
                return [
                    'success' => true,
                    'message' => 'Device is not connected',
                    'connected' => false,
                ];
            }

            // Disconnect using SDK
            $zk->disconnect();

            // Remove from cache
            $this->removeConnection($device->id);

            Log::info('ZKTeco device disconnected', [
                'device_id' => $device->id,
            ]);

            return [
                'success' => true,
                'message' => 'Successfully disconnected from device',
                'connected' => false,
            ];
        } catch (\Exception $e) {
            Log::error('ZKTeco disconnect error', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            // Force remove connection
            $this->removeConnection($device->id);

            return [
                'success' => false,
                'message' => 'Disconnect error: ' . $e->getMessage(),
                'connected' => false,
            ];
        }
    }

    /**
     * Restart ZKTeco device
     */
    public function restart(BiometricDevice $device): array
    {
        try {
            $zk = $this->getConnection($device->id);

            if ($zk === null) {
                // Try to connect first
                $connectResult = $this->connect($device);
                if (!$connectResult['success']) {
                    return $connectResult;
                }
                $zk = $this->getConnection($device->id);
            }

            // Send restart command using SDK
            $result = $zk->restart();

            // Close connection after restart
            $this->removeConnection($device->id);

            Log::info('ZKTeco device restart command sent', [
                'device_id' => $device->id,
            ]);

            return [
                'success' => true,
                'message' => 'Restart command sent successfully. Device will restart shortly.',
            ];
        } catch (\Exception $e) {
            Log::error('ZKTeco restart error', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            // Remove connection on error
            $this->removeConnection($device->id);

            return [
                'success' => false,
                'message' => 'Restart error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get users from ZKTeco device
     */
    public function getUsers(BiometricDevice $device): array
    {
        try {
            $zk = $this->getConnection($device->id);

            if ($zk === null) {
                $connectResult = $this->connect($device);
                if (!$connectResult['success']) {
                    return [
                        'success' => false,
                        'message' => 'Failed to connect: ' . $connectResult['message'],
                        'users' => [],
                    ];
                }
                $zk = $this->getConnection($device->id);
            }

            // Get users using SDK
            $sdkUsers = $zk->getUser();

            // Map SDK format to our format
            $users = [];
            foreach ($sdkUsers as $sdkUser) {
                $users[] = [
                    'uid' => $sdkUser['uid'] ?? 0,
                    'id' => (string)($sdkUser['userid'] ?? $sdkUser['uid'] ?? ''),
                    'name' => $sdkUser['name'] ?? '',
                    'role' => $sdkUser['role'] ?? 0,
                    'cardno' => $sdkUser['cardno'] ?? '',
                ];
            }

            Log::info('ZKTeco users retrieved', [
                'device_id' => $device->id,
                'user_count' => count($users),
            ]);

            return [
                'success' => true,
                'message' => 'Users retrieved successfully',
                'users' => $users,
                'count' => count($users),
            ];
        } catch (\Exception $e) {
            Log::error('ZKTeco get users error', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Get users error: ' . $e->getMessage(),
                'users' => [],
            ];
        }
    }

    /**
     * Get attendance logs from ZKTeco device
     */
    public function getAttendanceLogs(BiometricDevice $device, $startDate = null, $endDate = null): array
    {
        try {
            $zk = $this->getConnection($device->id);

            if ($zk === null) {
                $connectResult = $this->connect($device);
                if (!$connectResult['success']) {
                    return [
                        'success' => false,
                        'message' => 'Failed to connect: ' . $connectResult['message'],
                        'logs' => [],
                    ];
                }
                $zk = $this->getConnection($device->id);
            }

            // Get attendance logs using SDK
            $sdkLogs = $zk->getAttendance();

            // Map SDK format to our format
            $logs = [];
            foreach ($sdkLogs as $sdkLog) {
                $punchTime = Carbon::parse($sdkLog['timestamp']);

                // Determine punch type based on TYPE (not state)
                // Type: 0 = Check In, 1 = Check Out, 2 = Break Out, 3 = Break In, 4 = Overtime In, 5 = Overtime Out
                // State: 0 = Password, 1 = Fingerprint, 2 = Card (verification method)
                $rawType = (int)($sdkLog['type'] ?? 0);
                $punchType = BiometricLog::getPunchTypeFromZKTeco($rawType);

                // Determine punch mode based on verification state
                $verifyMethod = $sdkLog['state'] ?? 1;
                $punchMode = $this->mapVerifyMethodToMode($verifyMethod);

                $logs[] = [
                    'device_user_id' => (string)($sdkLog['id'] ?? $sdkLog['uid'] ?? ''),
                    'punch_time' => $punchTime->format('Y-m-d H:i:s'),
                    'punch_type' => $punchType,
                    'punch_mode' => $punchMode,
                    'raw_type' => $rawType, // Store original type for debugging
                    'verify_method' => $verifyMethod, // Verification method (fingerprint, password, card)
                ];
            }

            // Filter by date range if provided
            if ($startDate || $endDate) {
                $logs = array_filter($logs, function ($log) use ($startDate, $endDate) {
                    $logDate = Carbon::parse($log['punch_time']);
                    if ($startDate && $logDate->lt($startDate)) {
                        return false;
                    }
                    if ($endDate && $logDate->gt($endDate)) {
                        return false;
                    }
                    return true;
                });
                $logs = array_values($logs);
            }

            Log::info('ZKTeco attendance logs retrieved', [
                'device_id' => $device->id,
                'log_count' => count($logs),
            ]);

            return [
                'success' => true,
                'message' => 'Attendance logs retrieved successfully',
                'logs' => $logs,
                'count' => count($logs),
            ];
        } catch (\Exception $e) {
            Log::error('ZKTeco get attendance logs error', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Get attendance logs error: ' . $e->getMessage(),
                'logs' => [],
            ];
        }
    }

    /**
     * Check if device is connected
     */
    public function isConnected(BiometricDevice $device): bool
    {
        // Check cache for connection metadata
        $connectionData = Cache::get("zkteco_connection_{$device->id}");
        if (!$connectionData) {
            return false;
        }

        // Try to get the actual ZKTeco instance
        $zk = $this->getConnection($device->id);
        if ($zk === null) {
            return false;
        }

        // Check if connection is still valid by trying a simple operation
        // We can't directly check connection state, so we'll assume it's connected if it's in cache
        // The SDK will handle reconnection if needed
        return true;
    }

    /**
     * Test connection to device
     */
    public function testConnection(BiometricDevice $device): array
    {
        $ip = $device->ip_address;
        $port = $device->port ?? self::DEFAULT_PORT;

        if (!$ip) {
            return [
                'success' => false,
                'message' => 'Device IP address is not configured',
            ];
        }

        try {
            $zk = new ZKTeco($ip, $port);
            $connected = $zk->connect();

            if ($connected) {
                $zk->disconnect();
                return [
                    'success' => true,
                    'message' => 'Device is reachable at ' . $ip . ':' . $port,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Cannot connect to device at ' . $ip . ':' . $port . '. Check network connectivity and device power.',
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Store connection in cache
     */
    protected function storeConnection($deviceId, $zk): void
    {
        // Store connection metadata in cache
        Cache::put("zkteco_connection_{$deviceId}", [
            'connected_at' => now()->toDateTimeString(),
        ], now()->addMinutes(30));

        // Store ZKTeco instance in a static array (since we can't cache objects)
        if (!isset($GLOBALS['zkteco_connections'])) {
            $GLOBALS['zkteco_connections'] = [];
        }
        $GLOBALS['zkteco_connections'][$deviceId] = $zk;
    }

    /**
     * Get connection from storage
     */
    protected function getConnection($deviceId)
    {
        if (!isset($GLOBALS['zkteco_connections'])) {
            return null;
        }
        return $GLOBALS['zkteco_connections'][$deviceId] ?? null;
    }

    /**
     * Remove connection from storage
     */
    protected function removeConnection($deviceId): void
    {
        Cache::forget("zkteco_connection_{$deviceId}");
        if (isset($GLOBALS['zkteco_connections'][$deviceId])) {
            // Disconnect before removing
            try {
                $zk = $GLOBALS['zkteco_connections'][$deviceId];
                if ($zk instanceof ZKTeco) {
                    $zk->disconnect();
                }
            } catch (\Exception $e) {
                // Ignore disconnect errors
            }
            unset($GLOBALS['zkteco_connections'][$deviceId]);
        }
    }

    /**
     * Map ZKTeco verify method to punch mode string
     * State: 0 = Password, 1 = Fingerprint, 2 = Card
     */
    protected function mapVerifyMethodToMode(int $verifyMethod): string
    {
        return match ($verifyMethod) {
            0 => 'password',
            1 => 'fingerprint',
            2 => 'card',
            3 => 'face',
            default => 'biometric',
        };
    }

    /**
     * Sync all employees from system to ZKTeco device
     */
    public function syncAllEmployeesToDevice(BiometricDevice $device): array
    {
        try {
            // Connect to device
            $connectResult = $this->connect($device);
            if (!$connectResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Failed to connect to device: ' . $connectResult['message'],
                    'synced' => 0,
                    'failed' => 0,
                ];
            }

            $zk = $this->getConnection($device->id);
            if (!$zk) {
                return [
                    'success' => false,
                    'message' => 'Device connection lost',
                    'synced' => 0,
                    'failed' => 0,
                ];
            }

            // Get all active employees for the device's company
            $employees = \App\Models\Hr\Employee::where('company_id', $device->company_id)
                ->where('status', 'active')
                ->get();

            $synced = 0;
            $failed = 0;
            $errors = [];

            foreach ($employees as $employee) {
                try {
                    // Use employee ID as device user ID (or employee number if available)
                    $deviceUserId = $employee->employee_number ?: (string)$employee->id;
                    $deviceUserName = $employee->full_name;

                    // Limit name to 24 characters (ZKTeco limit)
                    if (strlen($deviceUserName) > 24) {
                        $deviceUserName = substr($deviceUserName, 0, 24);
                    }

                    // Use employee ID as UID (must be unique, max 65535)
                    $uid = min($employee->id, 65535);

                    // Create user on device using SDK
                    $result = $zk->setUser(
                        $uid,
                        $deviceUserId,
                        $deviceUserName,
                        '', // password (empty)
                        0,  // role (0 = user)
                        0   // cardno
                    );

                    if ($result !== false) {
                        // Update employee record
                        $employee->update([
                            'biometric_device_id' => $device->id,
                            'biometric_device_user_id' => $deviceUserId,
                            'biometric_device_user_name' => $deviceUserName,
                            'biometric_synced_at' => now(),
                        ]);
                        $synced++;
                    } else {
                        $failed++;
                        $errors[] = "Failed to sync {$employee->full_name} (ID: {$employee->id})";
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Error syncing {$employee->full_name}: " . $e->getMessage();
                    Log::error('Error syncing employee to device', [
                        'employee_id' => $employee->id,
                        'device_id' => $device->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('ZKTeco sync all employees completed', [
                'device_id' => $device->id,
                'synced' => $synced,
                'failed' => $failed,
            ]);

            $message = "Synced {$synced} employee(s) to device successfully.";
            if ($failed > 0) {
                $message .= " {$failed} employee(s) failed to sync.";
            }

            return [
                'success' => $synced > 0,
                'message' => $message,
                'synced' => $synced,
                'failed' => $failed,
                'total' => $employees->count(),
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            Log::error('ZKTeco sync all employees error', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Sync error: ' . $e->getMessage(),
                'synced' => 0,
                'failed' => 0,
            ];
        }
    }
}
