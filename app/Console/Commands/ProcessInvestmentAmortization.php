<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Investment\InvestmentMaster;
use App\Services\Investment\InvestmentAmortizationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessInvestmentAmortization extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'investments:process-amortization 
                            {--company= : Company ID (optional)}
                            {--investment= : Investment ID (optional)}
                            {--recompute : Recompute existing schedules}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate or recompute amortization schedules for investments';

    protected $amortizationService;

    public function __construct(InvestmentAmortizationService $amortizationService)
    {
        parent::__construct();
        $this->amortizationService = $amortizationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting investment amortization processing...');

        $companyId = $this->option('company');
        $investmentId = $this->option('investment');
        $recompute = $this->option('recompute');

        try {
            // Build query for investments
            $investmentsQuery = InvestmentMaster::query();

            if ($companyId) {
                $investmentsQuery->where('company_id', $companyId);
            }

            if ($investmentId) {
                $investmentsQuery->where('id', $investmentId);
            }

            // Only process active investments
            $investments = $investmentsQuery
                ->where('status', 'ACTIVE')
                ->get();

            if ($investments->isEmpty()) {
                $this->warn('No active investments found to process.');
                return 0;
            }

            $this->info("Found {$investments->count()} active investment(s) to process.");

            $processed = 0;
            $skipped = 0;
            $errors = 0;

            foreach ($investments as $investment) {
                try {
                    if ($recompute) {
                        $this->line("  Recomputing amortization for {$investment->instrument_code}...");
                        $lines = $this->amortizationService->recomputeAmortizationSchedule($investment);
                    } else {
                        // Check if schedule already exists
                        $existingLines = \App\Models\Investment\InvestmentAmortLine::where('investment_id', $investment->id)
                            ->where('period_end', '>', Carbon::now())
                            ->count();

                        if ($existingLines > 0) {
                            $this->line("  ⏭ Skipped {$investment->instrument_code} - Schedule already exists");
                            $skipped++;
                            continue;
                        }

                        $this->line("  Generating amortization for {$investment->instrument_code}...");
                        $lines = $this->amortizationService->saveAmortizationSchedule($investment);
                    }

                    $this->info("  ✓ Generated {$lines->count()} amortization lines for {$investment->instrument_code}");
                    $processed++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Error processing {$investment->instrument_code}: {$e->getMessage()}");
                    $errors++;
                    Log::error('Investment amortization error', [
                        'investment_id' => $investment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->newLine();
            $this->info("Amortization processing completed:");
            $this->line("  Processed: {$processed}");
            $this->line("  Skipped: {$skipped}");
            $this->line("  Errors: {$errors}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Fatal error: {$e->getMessage()}");
            Log::error('Investment amortization fatal error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}

