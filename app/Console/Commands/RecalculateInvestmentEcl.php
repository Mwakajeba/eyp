<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Investment\InvestmentMaster;
use App\Services\Investment\EclCalculationService;
use Illuminate\Support\Facades\Log;

class RecalculateInvestmentEcl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'investments:recalculate-ecl 
                            {--company= : Company ID (optional)}
                            {--investment= : Investment ID (optional)}
                            {--scenario= : Scenario to use (base, optimistic, pessimistic)}
                            {--gdp-growth= : GDP growth rate for forward-looking adjustment}
                            {--inflation= : Inflation rate for forward-looking adjustment}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate Expected Credit Loss (ECL) for investments with forward-looking information';

    protected $eclService;

    public function __construct(EclCalculationService $eclService)
    {
        parent::__construct();
        $this->eclService = $eclService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting ECL recalculation process...');

        $companyId = $this->option('company');
        $investmentId = $this->option('investment');
        
        // Build forward-looking information from options
        $forwardLookingInfo = [];
        if ($this->option('gdp-growth') !== null) {
            $forwardLookingInfo['macro_economic_factors']['gdp_growth'] = (float)$this->option('gdp-growth');
        }
        if ($this->option('inflation') !== null) {
            $forwardLookingInfo['macro_economic_factors']['inflation_rate'] = (float)$this->option('inflation');
        }

        try {
            if ($investmentId) {
                // Recalculate single investment
                $investment = InvestmentMaster::find($investmentId);
                if (!$investment) {
                    $this->error("Investment not found: {$investmentId}");
                    return 1;
                }

                $this->line("Recalculating ECL for {$investment->instrument_code}...");
                $result = $this->eclService->calculateEcl($investment, [], $forwardLookingInfo);

                $this->info("✓ ECL Calculation Results:");
                $this->line("  Stage: {$result['stage']}");
                $this->line("  Base PD: {$result['base_pd']}%");
                $this->line("  Adjusted PD: {$result['adjusted_pd']}%");
                $this->line("  LGD: {$result['lgd']}%");
                $this->line("  EAD: " . number_format($result['ead'], 2));
                $this->line("  Weighted ECL: " . number_format($result['weighted_ecl'], 2));
                
                if (!empty($result['scenarios'])) {
                    $this->line("  Scenarios:");
                    foreach ($result['scenarios'] as $scenario => $data) {
                        $this->line("    {$scenario}: ECL = " . number_format($data['ecl'], 2));
                    }
                }
            } else {
                // Recalculate all investments for company
                $query = InvestmentMaster::query();
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }

                $investments = $query->whereIn('status', ['ACTIVE'])
                    ->whereIn('instrument_type', ['T_BOND', 'T_BILL', 'FIXED_DEPOSIT', 'CORP_BOND', 'COMMERCIAL_PAPER'])
                    ->get();

                if ($investments->isEmpty()) {
                    $this->warn('No investments found to process.');
                    return 0;
                }

                $this->info("Found {$investments->count()} investment(s) to process.");

                $results = $this->eclService->recalculateAllEcl(
                    $companyId ?? $investments->first()->company_id,
                    $forwardLookingInfo
                );

                $this->newLine();
                $this->info("ECL recalculation completed:");
                $this->line("  Total Investments: {$results['total_investments']}");
                $this->line("  Processed: {$results['processed']}");
                $this->line("  Total ECL: " . number_format($results['total_ecl'], 2));

                // Show summary by stage
                $summary = $this->eclService->getEclSummaryByStage(
                    $companyId ?? $investments->first()->company_id
                );

                $this->newLine();
                $this->info("ECL Summary by Stage:");
                foreach ($summary as $stage => $data) {
                    $this->line("  {$stage}: {$data['count']} investments, ECL = " . number_format($data['total_ecl'], 2));
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Fatal error: {$e->getMessage()}");
            Log::error('ECL recalculation fatal error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}
