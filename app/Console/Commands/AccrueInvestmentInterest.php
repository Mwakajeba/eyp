<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Investment\InvestmentMaster;
use App\Services\Investment\InvestmentAccrualService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AccrueInvestmentInterest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'investments:accrue-interest 
                            {--date= : Accrual date (default: last day of previous month)}
                            {--company= : Company ID (optional)}
                            {--investment= : Investment ID (optional)}
                            {--dry-run : Preview without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Accrue interest for all active investments (runs monthly)';

    protected $accrualService;

    public function __construct(InvestmentAccrualService $accrualService)
    {
        parent::__construct();
        $this->accrualService = $accrualService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting investment interest accrual...');

        // Get accrual date (default to last day of previous month)
        $date = $this->option('date') 
            ? Carbon::parse($this->option('date')) 
            : Carbon::now()->subMonth()->endOfMonth();

        $companyId = $this->option('company');
        $investmentId = $this->option('investment');
        $dryRun = $this->option('dry-run');

        $this->info("Processing interest accrual for period: {$date->format('F Y')}");
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be saved');
        }

        try {
            // Build query for investments
            $investmentsQuery = InvestmentMaster::query();

            if ($companyId) {
                $investmentsQuery->where('company_id', $companyId);
            }

            if ($investmentId) {
                $investmentsQuery->where('id', $investmentId);
            }

            // Only process active investments with EIR
            $investments = $investmentsQuery
                ->where('status', 'ACTIVE')
                ->whereNotNull('eir_rate')
                ->whereNotNull('gl_asset_account')
                ->whereNotNull('gl_accrued_interest_account')
                ->whereNotNull('gl_interest_income_account')
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
                    if ($dryRun) {
                        $this->line("  [DRY RUN] Would accrue interest for {$investment->instrument_code}");
                        $processed++;
                        continue;
                    }

                    // Get system user or first admin user
                    $user = \App\Models\User::where('company_id', $investment->company_id)
                        ->whereHas('roles', function($q) {
                            $q->where('name', 'admin');
                        })
                        ->first() ?? \App\Models\User::where('company_id', $investment->company_id)->first();

                    if (!$user) {
                        $this->error("  ✗ No user found for company {$investment->company_id}");
                        $errors++;
                        continue;
                    }

                    $journal = $this->accrualService->accrueInterest($investment, $date, $user);

                    if ($journal) {
                        $this->info("  ✓ Accrued interest for {$investment->instrument_code} - Journal #{$journal->id}");
                        $processed++;
                    } else {
                        $this->line("  ⏭ Skipped {$investment->instrument_code} - No pending accrual");
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    $this->error("  ✗ Error processing {$investment->instrument_code}: {$e->getMessage()}");
                    $errors++;
                    Log::error('Investment accrual error', [
                        'investment_id' => $investment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->newLine();
            $this->info("Accrual completed:");
            $this->line("  Processed: {$processed}");
            $this->line("  Skipped: {$skipped}");
            $this->line("  Errors: {$errors}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Fatal error: {$e->getMessage()}");
            Log::error('Investment accrual fatal error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}

