<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\BiometricDevice;
use App\Models\Hr\BiometricLog;
use App\Models\Hr\Employee;
use App\Services\Hr\BiometricService;
use App\Services\Hr\ZKTecoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class BiometricDeviceController extends Controller
{
    protected $biometricService;
    protected $zktecoService;

    public function __construct(BiometricService $biometricService, ZKTecoService $zktecoService)
    {
        $this->biometricService = $biometricService;
        $this->zktecoService = $zktecoService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $devices = BiometricDevice::where('company_id', current_company_id())
                ->with('branch')
                ->orderBy('device_code');

            return DataTables::of($devices)
                ->addIndexColumn()
                ->addColumn('branch_name', function ($device) {
                    return $device->branch ? $device->branch->name : 'All Branches';
                })
                ->addColumn('connection_info', function ($device) {
                    if ($device->ip_address) {
                        return $device->ip_address . ($device->port ? ':' . $device->port : '');
                    }
                    return $device->connection_type;
                })
                ->addColumn('sync_status', function ($device) {
                    if (!$device->last_sync_at) {
                        return '<span class="badge bg-secondary">Never Synced</span>';
                    }

                    $minutesAgo = $device->last_sync_at->diffInMinutes(now());
                    if ($minutesAgo < $device->sync_interval_minutes) {
                        return '<span class="badge bg-success">Synced ' . $minutesAgo . 'm ago</span>';
                    } else {
                        return '<span class="badge bg-warning">Sync Due</span>';
                    }
                })
                ->addColumn('status_badge', function ($device) {
                    $badge = $device->is_active ? 'success' : 'secondary';
                    $text = $device->is_active ? 'Active' : 'Inactive';
                    return '<span class="badge bg-' . $badge . '">' . $text . '</span>';
                })
                ->addColumn('action', function ($device) {
                    $viewBtn = '<a href="' . route('hr.biometric-devices.show', $device->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.biometric-devices.edit', $device->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $syncBtn = '<button class="btn btn-sm btn-outline-success sync-btn me-1" data-id="' . $device->id . '"><i class="bx bx-sync"></i></button>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $device->id . '" data-name="' . $device->device_name . '"><i class="bx bx-trash"></i></button>';
                    return $viewBtn . $editBtn . $syncBtn . $deleteBtn;
                })
                ->rawColumns(['sync_status', 'status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.biometric-devices.index');
    }

    public function create()
    {
        $branches = \App\Models\Branch::where('company_id', current_company_id())
            ->orderBy('name')
            ->get();

        return view('hr-payroll.biometric-devices.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'device_code' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('hr_biometric_devices')->where(function ($query) {
                    return $query->where('company_id', current_company_id());
                })
            ],
            'device_name' => 'required|string|max:200',
            'device_type' => 'required|in:fingerprint,face,card,palm',
            'device_model' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'ip_address' => 'nullable|ip',
            'port' => 'nullable|integer|min:1|max:65535',
            'connection_type' => 'required|in:api,tcp,udp,file_import',
            'timezone' => 'required|string|max:50',
            'auto_sync' => 'boolean',
            'sync_interval_minutes' => 'required|integer|min:1|max:1440',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $device = BiometricDevice::create(array_merge($validated, [
                'company_id' => current_company_id(),
            ]));

            // Generate API credentials
            $device->generateApiKey();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Biometric device created successfully.',
                    'redirect' => route('hr.biometric-devices.show', $device->id)
                ]);
            }

            return redirect()->route('hr.biometric-devices.show', $device->id)
                ->with('success', 'Biometric device created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create device: ' . $e->getMessage()]);
        }
    }

    public function show(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $logs = BiometricLog::where('device_id', $biometricDevice->id)
            ->orderBy('punch_time', 'desc')
            ->limit(50)
            ->with('employee')
            ->get();

        $stats = [
            'total_logs' => BiometricLog::where('device_id', $biometricDevice->id)->count(),
            'pending_logs' => BiometricLog::where('device_id', $biometricDevice->id)->where('status', 'pending')->count(),
            'processed_logs' => BiometricLog::where('device_id', $biometricDevice->id)->where('status', 'processed')->count(),
            'failed_logs' => BiometricLog::where('device_id', $biometricDevice->id)->where('status', 'failed')->count(),
        ];

        return view('hr-payroll.biometric-devices.show', compact(
            'biometricDevice',
            'logs',
            'stats'
        ));
    }

    public function edit(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $branches = \App\Models\Branch::where('company_id', current_company_id())
            ->orderBy('name')
            ->get();

        return view('hr-payroll.biometric-devices.edit', compact('biometricDevice', 'branches'));
    }

    public function update(Request $request, BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'device_code' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('hr_biometric_devices')->ignore($biometricDevice->id)->where(function ($query) {
                    return $query->where('company_id', current_company_id());
                })
            ],
            'device_name' => 'required|string|max:200',
            'device_type' => 'required|in:fingerprint,face,card,palm',
            'device_model' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'ip_address' => 'nullable|ip',
            'port' => 'nullable|integer|min:1|max:65535',
            'connection_type' => 'required|in:api,tcp,udp,file_import',
            'timezone' => 'required|string|max:50',
            'auto_sync' => 'boolean',
            'sync_interval_minutes' => 'required|integer|min:1|max:1440',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $biometricDevice->update($validated);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Biometric device updated successfully.'
                ]);
            }

            return redirect()->route('hr.biometric-devices.show', $biometricDevice->id)
                ->with('success', 'Biometric device updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update device: ' . $e->getMessage()]);
        }
    }

    public function sync(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $result = $this->biometricService->syncDevice($biometricDevice);

        // Process pending logs after sync
        $processResult = $this->biometricService->processPendingLogs($biometricDevice->id);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'logs_processed' => $processResult['processed'] ?? 0,
        ]);
    }

    public function regenerateApiKey(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $apiKey = $biometricDevice->generateApiKey();

        return response()->json([
            'success' => true,
            'message' => 'API key regenerated successfully.',
            'api_key' => $apiKey,
        ]);
    }

    public function processPendingLogs(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $result = $this->biometricService->processPendingLogs($biometricDevice->id);

        return response()->json([
            'success' => true,
            'message' => 'Pending logs processed.',
            'result' => $result,
        ]);
    }

    public function destroy(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $biometricDevice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Biometric device deleted successfully.'
        ]);
    }

    public function connect(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!$biometricDevice->isZKTecoDevice()) {
            return response()->json([
                'success' => false,
                'message' => 'This device is not configured for ZKTeco connection.',
            ], 400);
        }

        $result = $this->zktecoService->connect($biometricDevice);

        return response()->json($result);
    }

    public function disconnect(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!$biometricDevice->isZKTecoDevice()) {
            return response()->json([
                'success' => false,
                'message' => 'This device is not configured for ZKTeco connection.',
            ], 400);
        }

        $result = $this->zktecoService->disconnect($biometricDevice);

        return response()->json($result);
    }

    public function restart(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!$biometricDevice->isZKTecoDevice()) {
            return response()->json([
                'success' => false,
                'message' => 'This device is not configured for ZKTeco connection.',
            ], 400);
        }

        $result = $this->zktecoService->restart($biometricDevice);

        return response()->json($result);
    }

    public function getDeviceData(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!$biometricDevice->isZKTecoDevice()) {
            return response()->json([
                'success' => false,
                'message' => 'This device is not configured for ZKTeco connection.',
            ], 400);
        }

        // Get connection status
        $isConnected = $this->zktecoService->isConnected($biometricDevice);

        // Get users if connected
        $users = [];
        if ($isConnected) {
            $usersResult = $this->zktecoService->getUsers($biometricDevice);
            if ($usersResult['success']) {
                $users = $usersResult['users'];
            }
        }

        // Get attendance logs if connected
        $logs = [];
        if ($isConnected) {
            $logsResult = $this->zktecoService->getAttendanceLogs($biometricDevice);
            if ($logsResult['success']) {
                $logs = $logsResult['logs'];
            }
        }

        return response()->json([
            'success' => true,
            'connected' => $isConnected,
            'users' => $users,
            'users_count' => count($users),
            'logs' => $logs,
            'logs_count' => count($logs),
        ]);
    }

    public function testConnection(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!$biometricDevice->isZKTecoDevice()) {
            return response()->json([
                'success' => false,
                'message' => 'This device is not configured for ZKTeco connection.',
            ], 400);
        }

        $result = $this->zktecoService->testConnection($biometricDevice);

        return response()->json($result);
    }

    public function pullLogs(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!$biometricDevice->isZKTecoDevice()) {
            return response()->json([
                'success' => false,
                'message' => 'This device is not configured for ZKTeco connection.',
            ], 400);
        }

        $result = $this->zktecoService->getAttendanceLogs($biometricDevice);

        if ($result['success'] && !empty($result['logs'])) {
            // Store logs in database and process immediately
            $logsProcessed = 0;
            $attendanceCreated = 0;
            foreach ($result['logs'] as $logData) {
                // Check if log already exists
                $existingLog = BiometricLog::where('device_id', $biometricDevice->id)
                    ->where('device_user_id', $logData['device_user_id'])
                    ->where('punch_time', $logData['punch_time'])
                    ->first();

                if (!$existingLog) {
                    $log = BiometricLog::create([
                        'device_id' => $biometricDevice->id,
                        'device_user_id' => $logData['device_user_id'],
                        'punch_time' => $logData['punch_time'],
                        'punch_type' => $logData['punch_type'] ?? BiometricLog::PUNCH_CHECK_IN,
                        'punch_mode' => $logData['punch_mode'] ?? 'biometric',
                        'status' => BiometricLog::STATUS_PENDING,
                        'raw_data' => $logData,
                    ]);

                    // Process log immediately to create attendance
                    if ($this->biometricService->processBiometricLog($log)) {
                        $attendanceCreated++;
                    }
                    $logsProcessed++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Pulled ' . $result['count'] . ' logs from device. ' . $logsProcessed . ' new logs stored and ' . $attendanceCreated . ' attendance records created.',
                'logs_count' => $result['count'],
                'logs_processed' => $logsProcessed,
                'attendance_created' => $attendanceCreated,
            ]);
        }

        return response()->json($result);
    }

    public function deleteOrphanedLogs(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!$biometricDevice->isZKTecoDevice()) {
            return response()->json([
                'success' => false,
                'message' => 'This device is not configured for ZKTeco connection.',
            ], 400);
        }

        try {
            // Get device users
            $usersResult = $this->zktecoService->getUsers($biometricDevice);
            $deviceUserIds = [];

            if ($usersResult['success'] && !empty($usersResult['users'])) {
                $deviceUserIds = collect($usersResult['users'])->pluck('id')->toArray();
            }

            // Find logs with user IDs not on device
            $query = BiometricLog::where('device_id', $biometricDevice->id);

            if (!empty($deviceUserIds)) {
                $query->whereNotIn('device_user_id', $deviceUserIds);
            }

            $orphanedLogs = $query->get();
            $deletedCount = $orphanedLogs->count();

            // Delete orphaned logs
            $query->delete();

            return response()->json([
                'success' => true,
                'message' => "Deleted {$deletedCount} orphaned log(s) successfully.",
                'deleted_count' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Delete orphaned logs error', [
                'device_id' => $biometricDevice->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete orphaned logs: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reprocessFailedLogs(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        try {
            // Get failed logs
            $failedLogs = BiometricLog::where('device_id', $biometricDevice->id)
                ->where('status', BiometricLog::STATUS_FAILED)
                ->get();

            $processed = 0;
            $stillFailed = 0;

            foreach ($failedLogs as $log) {
                if ($this->biometricService->processBiometricLog($log)) {
                    $processed++;
                } else {
                    $stillFailed++;
                }
            }

            $message = "Reprocessed {$processed} log(s) successfully.";
            if ($stillFailed > 0) {
                $message .= " {$stillFailed} log(s) still failed (check employee mappings).";
            }

            return response()->json([
                'success' => $processed > 0,
                'message' => $message,
                'processed' => $processed,
                'still_failed' => $stillFailed,
            ]);
        } catch (\Exception $e) {
            Log::error('Reprocess failed logs error', [
                'device_id' => $biometricDevice->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reprocess logs: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function syncAllEmployees(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!$biometricDevice->isZKTecoDevice()) {
            return response()->json([
                'success' => false,
                'message' => 'This device is not configured for ZKTeco connection.',
            ], 400);
        }

        $result = $this->zktecoService->syncAllEmployeesToDevice($biometricDevice);

        return response()->json($result);
    }

    /**
     * Fix punch types from raw data for logs that were incorrectly categorized
     */
    public function fixPunchTypes(BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        try {
            $result = $this->biometricService->fixPunchTypesFromRawData($biometricDevice->id);

            $message = "Fixed {$result['fixed']} punch type(s) out of {$result['total_checked']} logs checked.";

            return response()->json([
                'success' => $result['success'],
                'message' => $message,
                'fixed' => $result['fixed'],
                'total_checked' => $result['total_checked'],
            ]);
        } catch (\Exception $e) {
            Log::error('Fix punch types error', [
                'device_id' => $biometricDevice->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fix punch types: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recalculate attendance from biometric logs for a date range
     */
    public function recalculateAttendance(Request $request, BiometricDevice $biometricDevice)
    {
        if ($biometricDevice->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'employee_id' => 'nullable|exists:hr_employees,id',
        ]);

        try {
            $result = $this->biometricService->recalculateAttendanceFromLogs(
                $validated['start_date'],
                $validated['end_date'],
                $validated['employee_id'] ?? null,
                $biometricDevice->id
            );

            $message = "Recalculated {$result['recalculated']} attendance record(s) out of {$result['total_combinations']} date-employee combinations.";

            if (!empty($result['errors'])) {
                $message .= ' Some errors occurred.';
            }

            return response()->json([
                'success' => $result['success'],
                'message' => $message,
                'recalculated' => $result['recalculated'],
                'total' => $result['total_combinations'],
                'errors' => $result['errors'],
            ]);
        } catch (\Exception $e) {
            Log::error('Recalculate attendance error', [
                'device_id' => $biometricDevice->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to recalculate attendance: ' . $e->getMessage(),
            ], 500);
        }
    }
}
