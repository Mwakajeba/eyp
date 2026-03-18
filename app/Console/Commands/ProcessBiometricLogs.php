<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Hr\BiometricService;
use App\Models\Hr\BiometricDevice;

class ProcessBiometricLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'biometric:process-logs {--device-id= : Process logs for specific device} {--limit=100 : Maximum number of logs to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending biometric logs and create attendance records';

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
        $limit = (int) $this->option('limit');

        $this->info('Processing biometric logs...');

        $result = $this->biometricService->processPendingLogs($deviceId, $limit);

        $this->info("Total logs: {$result['total']}");
        $this->info("Processed: {$result['processed']}");
        $this->info("Failed: {$result['failed']}");

        if ($result['failed'] > 0) {
            $this->warn("Some logs failed to process. Check the logs table for details.");
        }

        return Command::SUCCESS;
    }
}

