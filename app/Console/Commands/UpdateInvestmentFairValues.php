<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Investment\InvestmentMaster;
use App\Services\Investment\FairValueUpdateService;
use Illuminate\Support\Facades\Log;

class UpdateInvestmentFairValues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'investments:update-fair-values 
                            {--company= : Company ID (optional)}
                            {--investment= : Investment ID (optional)}
                            {--source= : Market data source (BOT, DSE, Manual)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update fair values for investments from market feeds';

    protected $fairValueService;

    public function __construct(FairValueUpdateService $fairValueService)
    {
        parent::__construct();
        $this->fairValueService = $fairValueService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting fair value update process...');

        $companyId = $this->option('company');
        $investmentId = $this->option('investment');
        $source = $this->option('source') ?? 'Market Feed';

        try {
            if ($investmentId) {
                // Update single investment
                $investment = InvestmentMaster::find($investmentId);
                if (!$investment) {
                    $this->error("Investment not found: {$investmentId}");
                    return 1;
                }

                $this->line("Updating fair value for {$investment->instrument_code}...");
                $result = $this->fairValueService->updateFairValue($investment, []);

                if ($result['updated']) {
                    $this->info("✓ Updated: {$result['previous_fair_value']} → {$result['new_fair_value']} ({$result['source']})");
                } else {
                    $this->warn("✗ Failed to update: " . ($result['error'] ?? 'Unknown error'));
                }
            } else {
                // Update all investments for company
                $query = InvestmentMaster::query();
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }

                $investments = $query->whereIn('status', ['ACTIVE'])
                    ->whereIn('accounting_class', ['FVOCI', 'FVPL'])
                    ->get();

                if ($investments->isEmpty()) {
                    $this->warn('No investments found to update.');
                    return 0;
                }

                $this->info("Found {$investments->count()} investment(s) to update.");

                $results = $this->fairValueService->updateAllFairValues(
                    $companyId ?? $investments->first()->company_id,
                    []
                );

                $this->newLine();
                $this->info("Fair value update completed:");
                $this->line("  Total: {$results['total']}");
                $this->line("  Updated: {$results['updated']}");
                $this->line("  Failed: {$results['failed']}");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Fatal error: {$e->getMessage()}");
            Log::error('Fair value update fatal error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}
