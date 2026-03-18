<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Payroll;
use App\Models\PayrollEmployee;
use App\Models\PayrollApproval;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use App\Models\Payment;

class CleanupPayrollData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:cleanup
                            {--force : Force deletion without confirmation}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all payroll entries, related journal entries, GL transactions, and payments (preserves settings and employees)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('==============================================');
        $this->info('  PAYROLL DATA CLEANUP UTILITY');
        $this->info('==============================================');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No data will be deleted');
            $this->newLine();
        }

        // Count records to be deleted
        $this->info('📊 Analyzing data to be deleted...');
        $this->newLine();

        $counts = [
            'payrolls' => Payroll::count(),
            'payroll_employees' => PayrollEmployee::count(),
            'payroll_approvals' => PayrollApproval::count(),
            'payroll_journals' => Journal::whereIn('reference_type', ['payroll_accrual', 'payroll_payment'])->count(),
            'payroll_journal_items' => JournalItem::whereHas('journal', function($q) {
                $q->whereIn('reference_type', ['payroll_accrual', 'payroll_payment']);
            })->count(),
            'payroll_gl_transactions' => GlTransaction::whereIn('transaction_type', ['payroll_accrual', 'payroll_payment'])->count(),
            'payroll_payments' => Payment::where('reference_type', 'payroll')->count(),
        ];

        // Display summary
        $this->table(
            ['Item', 'Count'],
            [
                ['Payroll Records', $counts['payrolls']],
                ['Payroll Employees', $counts['payroll_employees']],
                ['Payroll Approvals', $counts['payroll_approvals']],
                ['Payroll Journals', $counts['payroll_journals']],
                ['Payroll Journal Items', $counts['payroll_journal_items']],
                ['Payroll GL Transactions', $counts['payroll_gl_transactions']],
                ['Payroll Payments', $counts['payroll_payments']],
            ]
        );

        $totalRecords = array_sum($counts);
        
        if ($totalRecords === 0) {
            $this->info('✅ No payroll data found to delete.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info("Total records to be deleted: {$totalRecords}");
        $this->newLine();

        // Preserved data
        $this->info('✅ DATA THAT WILL BE PRESERVED:');
        $this->line('   - Payroll Approval Settings');
        $this->line('   - Payroll Chart Account Settings');
        $this->line('   - Employee Records');
        $this->line('   - Chart of Accounts');
        $this->line('   - All other system data');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 DRY RUN COMPLETE - No data was deleted');
            return Command::SUCCESS;
        }

        // Confirmation
        if (!$force) {
            $this->warn('⚠️  WARNING: This action cannot be undone!');
            $this->newLine();
            
            if (!$this->confirm('Are you sure you want to delete all payroll data?')) {
                $this->info('❌ Operation cancelled.');
                return Command::FAILURE;
            }

            $this->newLine();
            if (!$this->confirm('Please confirm again. Delete all payroll records?', false)) {
                $this->info('❌ Operation cancelled.');
                return Command::FAILURE;
            }
        }

        $this->newLine();
        $this->info('🗑️  Starting deletion process...');
        $this->newLine();

        DB::beginTransaction();

        try {
            $progressBar = $this->output->createProgressBar(7);
            $progressBar->setFormat('verbose');

            // Step 1: Delete payroll-related payments
            $progressBar->setMessage('Deleting payroll payments...');
            $deletedPayments = Payment::where('reference_type', 'payroll')->delete();
            $this->info("\n✓ Deleted {$deletedPayments} payroll payments");
            $progressBar->advance();

            // Step 2: Delete payroll approvals
            $progressBar->setMessage('Deleting payroll approvals...');
            $deletedApprovals = PayrollApproval::query()->delete();
            $this->info("\n✓ Deleted {$deletedApprovals} payroll approvals");
            $progressBar->advance();

            // Step 3: Delete payroll employees
            $progressBar->setMessage('Deleting payroll employees...');
            $deletedEmployees = PayrollEmployee::query()->delete();
            $this->info("\n✓ Deleted {$deletedEmployees} payroll employee records");
            $progressBar->advance();

            // Step 4: Delete payroll journal items
            $progressBar->setMessage('Deleting payroll journal items...');
            $journalIds = Journal::whereIn('reference_type', ['payroll_accrual', 'payroll_payment'])
                ->pluck('id')
                ->toArray();
            
            if (!empty($journalIds)) {
                $deletedJournalItems = JournalItem::whereIn('journal_id', $journalIds)->delete();
                $this->info("\n✓ Deleted {$deletedJournalItems} payroll journal items");
            } else {
                $this->info("\n✓ No payroll journal items to delete");
            }
            $progressBar->advance();

            // Step 5: Delete payroll GL transactions
            $progressBar->setMessage('Deleting payroll GL transactions...');
            $deletedGLTransactions = GlTransaction::whereIn('transaction_type', ['payroll_accrual', 'payroll_payment'])
                ->delete();
            $this->info("\n✓ Deleted {$deletedGLTransactions} payroll GL transactions");
            $progressBar->advance();

            // Step 6: Delete payroll journals
            $progressBar->setMessage('Deleting payroll journals...');
            $deletedJournals = Journal::whereIn('reference_type', ['payroll_accrual', 'payroll_payment'])
                ->delete();
            $this->info("\n✓ Deleted {$deletedJournals} payroll journals");
            $progressBar->advance();

            // Step 7: Delete payroll records (main table - should cascade delete related records if configured)
            $progressBar->setMessage('Deleting payroll records...');
            $deletedPayrolls = Payroll::query()->delete();
            $this->info("\n✓ Deleted {$deletedPayrolls} payroll records");
            $progressBar->advance();

            $progressBar->finish();
            $this->newLine(2);

            DB::commit();

            $this->info('==============================================');
            $this->info('✅ CLEANUP COMPLETED SUCCESSFULLY');
            $this->info('==============================================');
            $this->newLine();
            
            $this->table(
                ['Item', 'Deleted'],
                [
                    ['Payroll Records', $deletedPayrolls],
                    ['Payroll Employees', $deletedEmployees],
                    ['Payroll Approvals', $deletedApprovals],
                    ['Payroll Journals', $deletedJournals],
                    ['Payroll Journal Items', $deletedJournalItems ?? 0],
                    ['Payroll GL Transactions', $deletedGLTransactions],
                    ['Payroll Payments', $deletedPayments],
                    ['TOTAL', $deletedPayrolls + $deletedEmployees + $deletedApprovals + $deletedJournals + ($deletedJournalItems ?? 0) + $deletedGLTransactions + $deletedPayments],
                ]
            );

            $this->newLine();
            $this->info('✅ Payroll approval settings and employee records preserved.');
            $this->newLine();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();

            $this->newLine(2);
            $this->error('==============================================');
            $this->error('❌ CLEANUP FAILED');
            $this->error('==============================================');
            $this->error('Error: ' . $e->getMessage());
            $this->newLine();
            $this->error('File: ' . $e->getFile());
            $this->error('Line: ' . $e->getLine());
            $this->newLine();
            $this->warn('⚠️  Transaction rolled back. No data was deleted.');

            return Command::FAILURE;
        }
    }
}
