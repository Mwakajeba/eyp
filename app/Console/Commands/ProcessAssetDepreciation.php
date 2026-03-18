<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Asset\DepreciationService;
use App\Models\SystemSetting;
use Carbon\Carbon;

class ProcessAssetDepreciation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:process-depreciation {--date=} {--company=} {--no-gl}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process monthly asset depreciation for all active assets';

    protected $depreciationService;

    public function __construct(DepreciationService $depreciationService)
    {
        parent::__construct();
        $this->depreciationService = $depreciationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting asset depreciation processing...');

        // Get period date
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $companyId = $this->option('company');
        $postToGL = !$this->option('no-gl');

        $this->info("Processing depreciation for period: {$date->format('Y-m')}");

        try {
            $result = $this->depreciationService->processDepreciation(
                $date,
                $companyId,
                null,
                $postToGL
            );

            $this->info("✓ Processed {$result['total_processed']} assets successfully");
            
            if ($result['total_errors'] > 0) {
                $this->warn("⚠ {$result['total_errors']} errors occurred:");
                foreach ($result['errors'] as $error) {
                    $this->error("  - Asset {$error['asset_name']}: {$error['error']}");
                }
            }

            $this->info('Depreciation processing completed!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Depreciation processing failed: ' . $e->getMessage());
            return 1;
        }
    }
}
