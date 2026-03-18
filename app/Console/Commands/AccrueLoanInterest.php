<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Loan\Loan;
use App\Services\LoanService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccrueLoanInterest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loans:accrue-interest {--date=} {--company=} {--loan=} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically accrue interest for all active loans (runs monthly)';

    protected $loanService;

    public function __construct(LoanService $loanService)
    {
        parent::__construct();
        $this->loanService = $loanService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting automatic loan interest accrual...');

        // Get accrual date (default to last day of previous month for monthly accrual)
        // When run on 1st of month, it accrues for the previous month that just ended
        $date = $this->option('date') 
            ? Carbon::parse($this->option('date')) 
            : Carbon::now()->subMonth()->endOfMonth();
        
        $companyId = $this->option('company');
        $loanId = $this->option('loan');
        $dryRun = $this->option('dry-run');

        $this->info("Processing interest accrual for period: {$date->format('F Y')}");
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be saved');
        }

        try {
            // Build query for loans
            $loansQuery = Loan::query();
            
            if ($companyId) {
                $loansQuery->where('company_id', $companyId);
            }
            
            if ($loanId) {
                $loansQuery->where('id', $loanId);
            }
            
            // Only process active/disbursed loans
            $loans = $loansQuery->whereIn('status', ['disbursed', 'active'])
                ->whereNotNull('loan_payable_account_id')
                ->whereNotNull('interest_expense_account_id')
                ->whereNotNull('interest_payable_account_id')
                ->get();

            if ($loans->isEmpty()) {
                $this->warn('No active loans found to process.');
                return 0;
            }

            $this->info("Found {$loans->count()} active loan(s) to process.");

            $processed = 0;
            $skipped = 0;
            $errors = 0;
            $periodStart = $date->copy()->startOfMonth();
            $periodEnd = $date->copy()->endOfMonth();

            foreach ($loans as $loan) {
                try {
                    // Check if interest has already been accrued for this period
                    $existingAccrual = $loan->accruals()
                        ->whereBetween('accrual_date', [$periodStart, $periodEnd])
                        ->where('posted_flag', true)
                        ->first();

                    if ($existingAccrual) {
                        $this->line("  ⏭ Skipping {$loan->loan_number} - Interest already accrued for {$date->format('F Y')}");
                        $skipped++;
                        continue;
                    }

                    // Check if loan has outstanding principal
                    if ($loan->outstanding_principal <= 0) {
                        $this->line("  ⏭ Skipping {$loan->loan_number} - No outstanding principal");
                        $skipped++;
                        continue;
                    }

                    $this->line("  Processing {$loan->loan_number}...");

                    if (!$dryRun) {
                        DB::transaction(function () use ($loan, $date, $periodStart, $periodEnd) {
                            // Accrue interest
                            $accrual = $this->loanService->accrueInterest($loan, $date, $periodStart, $periodEnd);
                            
                            // Create GL entry
                            $this->loanService->createAccrualGlEntry($accrual, $loan);
                        });
                    } else {
                        // Dry run - just calculate
                        $openingBalance = $loan->outstanding_principal;
                        $daysInPeriod = $periodStart->diffInDays($periodEnd) + 1;
                        $interestAccrued = $this->loanService->calculateInterest(
                            $openingBalance, 
                            $loan->interest_rate, 
                            $daysInPeriod, 
                            $loan->calculation_basis
                        );
                        $this->line("    Would accrue: " . number_format($interestAccrued, 2) . " TZS");
                    }

                    $processed++;
                    $this->info("  ✓ {$loan->loan_number} processed successfully");

                } catch (\Exception $e) {
                    $errors++;
                    $this->error("  ✗ {$loan->loan_number} failed: " . $e->getMessage());
                    Log::error("Loan interest accrual failed for {$loan->loan_number}: " . $e->getMessage(), [
                        'loan_id' => $loan->id,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $this->newLine();
            $this->info("=== Summary ===");
            $this->info("Processed: {$processed}");
            $this->info("Skipped: {$skipped}");
            if ($errors > 0) {
                $this->warn("Errors: {$errors}");
            }
            
            if ($dryRun) {
                $this->warn('DRY RUN - No changes were saved');
            } else {
                $this->info('Interest accrual completed successfully!');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Interest accrual processing failed: ' . $e->getMessage());
            Log::error('Loan interest accrual command failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
