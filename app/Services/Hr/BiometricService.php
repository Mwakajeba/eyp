<?php

namespace App\Services\Hr;

use App\Models\Hr\BiometricDevice;
use App\Models\Hr\BiometricLog;
use App\Models\Hr\Employee;
use App\Models\Hr\Attendance;
use App\Services\Hr\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BiometricService
{
    protected $attendanceService;
    protected $zktecoService;

    public function __construct(AttendanceService $attendanceService, ZKTecoService $zktecoService = null)
    {
        $this->attendanceService = $attendanceService;
        $this->zktecoService = $zktecoService ?? app(ZKTecoService::class);
    }

    /**
     * Process biometric log and create/update attendance record
     */
    public function processBiometricLog(BiometricLog $log): bool
    {
        try {
            DB::beginTransaction();

            // Find employee by device_user_id
            $employee = Employee::where('biometric_device_id', $log->device_id)
                ->where('biometric_device_user_id', $log->device_user_id)
                ->first();

            if (!$employee) {
                $log->markAsFailed('Employee not found for device_user_id: ' . $log->device_user_id);
                DB::commit();
                return false;
            }

            $deviceTimezone = $log->device->timezone ?? config('app.timezone');
            $punchDateTime = Carbon::parse($log->punch_time)->setTimezone($deviceTimezone);
            // Use toDateString() to get plain date without timezone conversion issues
            $punchDate = $punchDateTime->toDateString();
            $punchTime = $punchDateTime->format('H:i:s');

            // Check for duplicate log (exact same punch time and type)
            $duplicate = BiometricLog::where('device_id', $log->device_id)
                ->where('device_user_id', $log->device_user_id)
                ->where('punch_time', $log->punch_time)
                ->where('punch_type', $log->punch_type)
                ->where('id', '!=', $log->id)
                ->where('status', BiometricLog::STATUS_PROCESSED)
                ->first();

            if ($duplicate) {
                $log->markAsDuplicate();
                DB::commit();
                return false;
            }

            // Check for near-duplicate (same type within 1 minute - device may send multiple punches)
            $nearDuplicate = BiometricLog::where('device_id', $log->device_id)
                ->where('device_user_id', $log->device_user_id)
                ->where('punch_type', $log->punch_type)
                ->where('id', '!=', $log->id)
                ->where('status', BiometricLog::STATUS_PROCESSED)
                ->whereBetween('punch_time', [
                    $punchDateTime->copy()->subMinute()->format('Y-m-d H:i:s'),
                    $punchDateTime->copy()->addMinute()->format('Y-m-d H:i:s'),
                ])
                ->first();

            if ($nearDuplicate) {
                $log->markAsDuplicate();
                DB::commit();
                return false;
            }

            // Get or create attendance record for the date
            $attendance = Attendance::firstOrNew([
                'employee_id' => $employee->id,
                'attendance_date' => $punchDate,
            ]);

            // Set device and schedule info
            $employeeSchedule = $this->attendanceService->getEmployeeScheduleForDate($employee, Carbon::parse($punchDate));
            if ($employeeSchedule) {
                if ($employeeSchedule->schedule_id) {
                    $attendance->schedule_id = $employeeSchedule->schedule_id;
                }
                if ($employeeSchedule->shift_id) {
                    $attendance->shift_id = $employeeSchedule->shift_id;
                }
            }

            // Update attendance based on punch type
            $this->updateAttendanceFromPunch($attendance, $log->punch_type, $punchTime);

            // Process attendance to calculate all fields
            $attendance = $this->attendanceService->processAttendance($attendance);

            // Auto-approve attendance from biometric device (no approval needed)
            $attendance->is_approved = true;
            $attendance->approved_at = now();

            $attendance->save();

            // Link log to attendance
            $log->employee_id = $employee->id;
            $log->markAsProcessed($attendance->id);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Biometric log processing failed', [
                'log_id' => $log->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $log->markAsFailed($e->getMessage());
            return false;
        }
    }

    /**
     * Update attendance record based on punch type
     */
    protected function updateAttendanceFromPunch(Attendance $attendance, string $punchType, string $punchTime): void
    {
        // Helper to get time string from attendance field (handles Carbon objects)
        $getTimeString = function ($value) {
            if (!$value) {
                return null;
            }
            if ($value instanceof \Carbon\Carbon) {
                return $value->format('H:i:s');
            }
            // If it's already a string, extract just the time part
            if (is_string($value) && strlen($value) > 8) {
                return substr($value, -8); // Get last 8 chars (HH:MM:SS)
            }
            return $value;
        };

        switch ($punchType) {
            case BiometricLog::PUNCH_CHECK_IN:
                // First check-in of the day (earliest)
                $currentClockIn = $getTimeString($attendance->clock_in);
                if (!$currentClockIn || $punchTime < $currentClockIn) {
                    $attendance->clock_in = $punchTime;
                }
                break;

            case BiometricLog::PUNCH_CHECK_OUT:
                // Last check-out of the day (latest)
                $currentClockOut = $getTimeString($attendance->clock_out);
                if (!$currentClockOut || $punchTime > $currentClockOut) {
                    $attendance->clock_out = $punchTime;
                }
                break;

            case BiometricLog::PUNCH_OVERTIME_IN:
                // First overtime start (earliest)
                $currentOtIn = $getTimeString($attendance->overtime_clock_in);
                if (!$currentOtIn || $punchTime < $currentOtIn) {
                    $attendance->overtime_clock_in = $punchTime;
                }
                break;

            case BiometricLog::PUNCH_OVERTIME_OUT:
                // Last overtime end (latest)
                $currentOtOut = $getTimeString($attendance->overtime_clock_out);
                if (!$currentOtOut || $punchTime > $currentOtOut) {
                    $attendance->overtime_clock_out = $punchTime;
                }
                break;

            case BiometricLog::PUNCH_BREAK_OUT:
                // First break start (earliest)
                $currentBreakStart = $getTimeString($attendance->break_start);
                if (!$currentBreakStart || $punchTime < $currentBreakStart) {
                    $attendance->break_start = $punchTime;
                }
                break;

            case BiometricLog::PUNCH_BREAK_IN:
                // Last break end (latest)
                $currentBreakEnd = $getTimeString($attendance->break_end);
                if (!$currentBreakEnd || $punchTime > $currentBreakEnd) {
                    $attendance->break_end = $punchTime;
                }
                break;
        }
    }

    /**
     * Process pending biometric logs
     */
    public function processPendingLogs($deviceId = null, $limit = 100): array
    {
        $query = BiometricLog::pending()->orderBy('punch_time');

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        $logs = $query->limit($limit)->get();
        $processed = 0;
        $failed = 0;

        foreach ($logs as $log) {
            if ($this->processBiometricLog($log)) {
                $processed++;
            } else {
                $failed++;
            }
        }

        return [
            'total' => $logs->count(),
            'processed' => $processed,
            'failed' => $failed,
        ];
    }

    /**
     * Sync data from biometric device
     */
    public function syncDevice(BiometricDevice $device): array
    {
        try {
            // Check if device is ZKTeco (TCP connection with IP address)
            if ($device->isZKTecoDevice()) {
                return $this->syncZKTecoDevice($device);
            }

            // Fall back to existing logic for other connection types
            $device->markSyncSuccess();

            return [
                'success' => true,
                'message' => 'Device synced successfully',
                'logs_processed' => 0,
            ];
        } catch (\Exception $e) {
            $device->markSyncFailure($e->getMessage());

            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sync ZKTeco device
     */
    protected function syncZKTecoDevice(BiometricDevice $device): array
    {
        try {
            // Connect to device
            $connectResult = $this->zktecoService->connect($device);
            if (!$connectResult['success']) {
                $device->markSyncFailure($connectResult['message']);
                return $connectResult;
            }

            $logsProcessed = 0;
            $usersSynced = 0;

            // Get users from device
            $usersResult = $this->zktecoService->getUsers($device);
            if ($usersResult['success'] && !empty($usersResult['users'])) {
                // Store users (for reference, actual mapping is done separately)
                $usersSynced = count($usersResult['users']);
            }

            // Get attendance logs from device
            $logsResult = $this->zktecoService->getAttendanceLogs($device);
            if ($logsResult['success'] && !empty($logsResult['logs'])) {
                // Store logs in database and process immediately
                foreach ($logsResult['logs'] as $logData) {
                    // Check if log already exists
                    $existingLog = BiometricLog::where('device_id', $device->id)
                        ->where('device_user_id', $logData['device_user_id'])
                        ->where('punch_time', $logData['punch_time'])
                        ->first();

                    if (!$existingLog) {
                        $log = BiometricLog::create([
                            'device_id' => $device->id,
                            'device_user_id' => $logData['device_user_id'],
                            'punch_time' => $logData['punch_time'],
                            'punch_type' => $logData['punch_type'] ?? BiometricLog::PUNCH_CHECK_IN,
                            'punch_mode' => $logData['punch_mode'] ?? 'biometric',
                            'status' => BiometricLog::STATUS_PENDING,
                            'raw_data' => $logData,
                        ]);

                        // Process log immediately
                        $this->processBiometricLog($log);
                        $logsProcessed++;
                    }
                }
            }

            // Disconnect from device
            $this->zktecoService->disconnect($device);

            // Mark sync as successful
            $device->markSyncSuccess();

            return [
                'success' => true,
                'message' => 'Device synced successfully. Retrieved ' . $usersSynced . ' users and ' . $logsProcessed . ' new logs.',
                'logs_processed' => $logsProcessed,
                'users_synced' => $usersSynced,
            ];
        } catch (\Exception $e) {
            // Try to disconnect on error
            try {
                $this->zktecoService->disconnect($device);
            } catch (\Exception $disconnectError) {
                // Ignore disconnect errors
            }

            $device->markSyncFailure($e->getMessage());

            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Map employee to device user
     */
    public function mapEmployeeToDevice(Employee $employee, BiometricDevice $device, string $deviceUserId, string $deviceUserName = null): Employee
    {
        $employee->update([
            'biometric_device_id' => $device->id,
            'biometric_device_user_id' => $deviceUserId,
            'biometric_device_user_name' => $deviceUserName ?? $employee->full_name,
            'biometric_synced_at' => now(),
        ]);

        return $employee->fresh();
    }

    /**
     * Unmap employee from device
     */
    public function unmapEmployeeFromDevice(Employee $employee, BiometricDevice $device): bool
    {
        if ($employee->biometric_device_id == $device->id) {
            $employee->update([
                'biometric_device_id' => null,
                'biometric_device_user_id' => null,
                'biometric_device_user_name' => null,
                'biometric_synced_at' => null,
            ]);
            return true;
        }
        return false;
    }

    /**
     * Get employee mapping for device (check if employee is mapped to device)
     */
    public function getEmployeeMapping(Employee $employee, BiometricDevice $device): ?Employee
    {
        if ($employee->biometric_device_id == $device->id && $employee->biometric_device_user_id) {
            return $employee;
        }
        return null;
    }

    /**
     * Recalculate attendance from biometric logs for a date range
     * Useful for fixing historical data after processing logic changes
     */
    public function recalculateAttendanceFromLogs($startDate, $endDate, $employeeId = null, $deviceId = null): array
    {
        $query = BiometricLog::where('status', BiometricLog::STATUS_PROCESSED)
            ->whereBetween('punch_time', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        // Get unique employee + date combinations
        $logs = $query->orderBy('punch_time')->get();

        $attendancesByEmployeeDate = [];
        foreach ($logs as $log) {
            if (!$log->employee_id) {
                continue;
            }

            $deviceTimezone = $log->device->timezone ?? config('app.timezone');
            $punchDateTime = Carbon::parse($log->punch_time)->setTimezone($deviceTimezone);
            $date = $punchDateTime->toDateString();

            $key = "{$log->employee_id}_{$date}";

            if (!isset($attendancesByEmployeeDate[$key])) {
                $attendancesByEmployeeDate[$key] = [
                    'employee_id' => $log->employee_id,
                    'date' => $date,
                    'logs' => [],
                ];
            }

            $attendancesByEmployeeDate[$key]['logs'][] = $log;
        }

        $recalculated = 0;
        $errors = [];

        foreach ($attendancesByEmployeeDate as $key => $data) {
            try {
                DB::beginTransaction();

                $employee = Employee::find($data['employee_id']);
                if (!$employee) {
                    $errors[] = "Employee ID {$data['employee_id']} not found";
                    DB::rollBack();
                    continue;
                }

                // Get or create attendance record
                $attendance = Attendance::firstOrNew([
                    'employee_id' => $data['employee_id'],
                    'attendance_date' => $data['date'],
                ]);

                // Reset punch times
                $attendance->clock_in = null;
                $attendance->clock_out = null;
                $attendance->overtime_clock_in = null;
                $attendance->overtime_clock_out = null;
                $attendance->break_start = null;
                $attendance->break_end = null;

                // Reprocess all logs for this day
                foreach ($data['logs'] as $log) {
                    $deviceTimezone = $log->device->timezone ?? config('app.timezone');
                    $punchDateTime = Carbon::parse($log->punch_time)->setTimezone($deviceTimezone);
                    $punchTime = $punchDateTime->format('H:i:s');

                    $this->updateAttendanceFromPunch($attendance, $log->punch_type, $punchTime);
                }

                // Process attendance to recalculate all fields
                $attendance = $this->attendanceService->processAttendance($attendance);
                $attendance->is_approved = true;
                $attendance->approved_at = now();
                $attendance->save();

                $recalculated++;
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = "Error processing {$key}: " . $e->getMessage();
                Log::error('Attendance recalculation error', [
                    'employee_id' => $data['employee_id'],
                    'date' => $data['date'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'success' => $recalculated > 0,
            'recalculated' => $recalculated,
            'total_combinations' => count($attendancesByEmployeeDate),
            'errors' => $errors,
        ];
    }

    /**
     * Reprocess logs with updated punch types from raw_data
     * Useful for fixing logs that were incorrectly categorized
     */
    public function fixPunchTypesFromRawData($deviceId = null): array
    {
        $query = BiometricLog::whereNotNull('raw_data');

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        $logs = $query->get();
        $fixed = 0;

        foreach ($logs as $log) {
            $rawData = $log->raw_data;
            if (!is_array($rawData) || !isset($rawData['raw_type'])) {
                continue;
            }

            $rawType = (int)$rawData['raw_type'];
            $correctPunchType = BiometricLog::getPunchTypeFromZKTeco($rawType);

            if ($log->punch_type !== $correctPunchType) {
                $log->update(['punch_type' => $correctPunchType]);
                $fixed++;
            }
        }

        return [
            'success' => true,
            'total_checked' => $logs->count(),
            'fixed' => $fixed,
        ];
    }
}
