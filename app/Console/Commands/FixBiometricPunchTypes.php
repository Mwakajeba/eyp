<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Hr\BiometricService;
use App\Models\Hr\BiometricDevice;

class FixBiometricPunchTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'biometric:fix-punch-types
                            {--device-id= : Fix punch types for specific device}
                            {--recalculate : Also recalculate attendance after fixing}
                            {--start-date= : Start date for recalculation (default: 30 days ago)}
                            {--end-date= : End date for recalculation (default: today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix incorrectly categorized punch types from raw data and optionally recalculate attendance';

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
        $recalculate = $this->option('recalculate');
        $startDate = $this->option('start-date') ?? now()->subDays(30)->format('Y-m-d');
        $endDate = $this->option('end-date') ?? now()->format('Y-m-d');

        $this->info('Fixing punch types from raw data...');

        $result = $this->biometricService->fixPunchTypesFromRawData($deviceId);

        $this->info("Checked: {$result['total_checked']} logs");
        $this->info("Fixed: {$result['fixed']} punch types");

        if ($recalculate) {
            $this->info('');
            $this->info("Recalculating attendance from {$startDate} to {$endDate}...");

            $recalcResult = $this->biometricService->recalculateAttendanceFromLogs(
                $startDate,
                $endDate,
                null,
                $deviceId
            );

            $this->info("Recalculated: {$recalcResult['recalculated']} / {$recalcResult['total_combinations']} attendance records");

            if (!empty($recalcResult['errors'])) {
                $this->warn('Errors occurred:');
                foreach ($recalcResult['errors'] as $error) {
                    $this->error("  - {$error}");
                }
            }
        }

        $this->info('');
        $this->info('Done!');

        return Command::SUCCESS;
    }
}
