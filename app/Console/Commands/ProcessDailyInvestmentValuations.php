<?php

namespace App\Console\Commands;

use App\Models\Investment\InvestmentMaster;
use App\Models\Investment\InvestmentValuation;
use App\Models\Investment\InvestmentMarketPriceHistory;
use App\Services\Investment\InvestmentValuationService;
use App\Services\Investment\InvestmentRevaluationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessDailyInvestmentValuations extends Command
{
    protected $signature = 'investments:process-daily-valuations {--date=} {--company=}';
    protected $description = 'Process daily valuations for FVPL and FVOCI investments';

    protected $valuationService;
    protected $revaluationService;

    public function __construct()
    {
        parent::__construct();
        $this->valuationService = app(\App\Services\Investment\InvestmentValuationService::class);
        $this->revaluationService = app(\App\Services\Investment\InvestmentRevaluationService::class);
    }

    public function handle()
    {
        $valuationDate = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $companyId = $this->option('company');

        $this->info("Processing daily valuations for date: {$valuationDate->format('Y-m-d')}");

        $query = InvestmentMaster::whereIn('status', ['ACTIVE'])
            ->whereIn('accounting_class', ['FVPL', 'FVOCI']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $investments = $query->get();
        $this->info("Found {$investments->count()} investments to value");

        $processed = 0;
        $errors = 0;

        foreach ($investments as $investment) {
            try {
                // Check if valuation already exists for this date
                $existingValuation = InvestmentValuation::where('investment_id', $investment->id)
                    ->where('valuation_date', $valuationDate->format('Y-m-d'))
                    ->first();

                if ($existingValuation) {
                    $this->warn("Valuation already exists for {$investment->instrument_code} on {$valuationDate->format('Y-m-d')}");
                    continue;
                }

                // Get latest market price
                $latestPrice = InvestmentMarketPriceHistory::where('investment_id', $investment->id)
                    ->where('price_date', '<=', $valuationDate->format('Y-m-d'))
                    ->latest('price_date')
                    ->first();

                if (!$latestPrice) {
                    $this->warn("No market price found for {$investment->instrument_code}");
                    continue;
                }

                // Create valuation
                $valuationData = [
                    'valuation_date' => $valuationDate->format('Y-m-d'),
                    'valuation_level' => $investment->valuation_level ?? 1,
                    'valuation_method' => 'MARKET_PRICE',
                    'fair_value_per_unit' => $latestPrice->market_price,
                    'units' => $investment->units,
                    'price_source' => $latestPrice->price_source,
                    'price_reference' => $latestPrice->source_reference,
                    'price_date' => $latestPrice->price_date->format('Y-m-d'),
                ];

                // Use system user or first admin user
                $systemUser = \App\Models\User::where('company_id', $investment->company_id)
                    ->whereHas('roles', function($q) {
                        $q->whereIn('name', ['admin', 'super_admin']);
                    })
                    ->first() ?? \App\Models\User::where('company_id', $investment->company_id)->first();

                if (!$systemUser) {
                    $this->error("No user found for company {$investment->company_id}");
                    continue;
                }

                $valuation = $this->valuationService->createValuation($investment, $valuationData, $systemUser);

                // Auto-approve Level 1 and 2, Level 3 requires manual approval
                if ($valuation->valuation_level < 3) {
                    $valuation->status = 'APPROVED';
                    $valuation->approved_by = $systemUser->id;
                    $valuation->approved_at = Carbon::now();
                    $valuation->save();
                }

                $processed++;
                $this->info("✓ Created valuation for {$investment->instrument_code}");

            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Failed to process {$investment->instrument_code}: {$e->getMessage()}");
                Log::error('Daily valuation processing failed', [
                    'investment_id' => $investment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("\nCompleted: {$processed} processed, {$errors} errors");
        return 0;
    }
}
