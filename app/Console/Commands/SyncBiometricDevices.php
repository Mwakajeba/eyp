<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Hr\BiometricService;
use App\Models\Hr\BiometricDevice;
use Illuminate\Support\Facades\Log;

class SyncBiometricDevices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'biometric:sync
                            {--device-id= : Sync specific device}
                            {--force : Force sync even if not due}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync attendance logs from biometric devices and update attendance records automatically';

    protected $biometricService;

    public function __construct(BiometricService $biometricService)
    {
        parent::__construct();
        $this->biometricService = $biometricService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deviceId = $this->option('device-id');
        $force = $this->option('force');

        if ($deviceId) {
            return $this->syncSingleDevice($deviceId, $force);
        }

        return $this->syncAllDevices($force);
    }

    /**
     * Sync a single device
     */
    protected function syncSingleDevice($deviceId, $force = false)
    {
        $device = BiometricDevice::find($deviceId);

        if (!$device) {
            $this->error("Device with ID {$deviceId} not found.");
            return Command::FAILURE;
        }

        if (!$force && !$device->needsSync()) {
            $this->info("Device {$device->device_name} does not need sync yet.");
            return Command::SUCCESS;
        }

        return $this->syncDevice($device);
    }

    /**
     * Sync all devices that need sync
     */
    protected function syncAllDevices($force = false)
    {
        $query = BiometricDevice::where('is_active', true)
            ->where('auto_sync', true)
            ->whereNotNull('ip_address');

        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('last_sync_at')
                    ->orWhereRaw('TIMESTAMPDIFF(MINUTE, last_sync_at, NOW()) >= sync_interval_minutes');
            });
        }

        $devices = $query->get();

        if ($devices->isEmpty()) {
            $this->info("No devices need sync at this time.");
            return Command::SUCCESS;
        }

        $this->info("Found {$devices->count()} device(s) to sync.");

        $successCount = 0;
        $failCount = 0;
        $totalLogs = 0;

        foreach ($devices as $device) {
            $result = $this->syncDevice($device);

            if ($result === Command::SUCCESS) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        $this->newLine();
        $this->info("=== Sync Summary ===");
        $this->info("Success: {$successCount}/{$devices->count()}");

        if ($failCount > 0) {
            $this->warn("Failed: {$failCount}");
        }

        return $failCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Sync a single device and process logs
     */
    protected function syncDevice(BiometricDevice $device)
    {
        $this->line("Syncing: {$device->device_name} ({$device->ip_address})...");

        try {
            // Sync device - pulls logs from device
            $result = $this->biometricService->syncDevice($device);

            if ($result['success']) {
                $logsProcessed = $result['logs_processed'] ?? 0;

                $this->info("  ✓ Success - {$logsProcessed} new log(s) processed");

                Log::channel('daily')->info('Biometric sync completed', [
                    'device_id' => $device->id,
                    'device_name' => $device->device_name,
                    'logs_processed' => $logsProcessed,
                ]);

                return Command::SUCCESS;
            } else {
                $this->error("  ✗ Failed: {$result['message']}");

                Log::channel('daily')->warning('Biometric sync failed', [
                    'device_id' => $device->id,
                    'device_name' => $device->device_name,
                    'error' => $result['message'],
                ]);

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Error: {$e->getMessage()}");

            Log::channel('daily')->error('Biometric sync error', [
                'device_id' => $device->id,
                'device_name' => $device->device_name,
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }
}

