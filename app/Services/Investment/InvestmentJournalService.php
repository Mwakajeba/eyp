<?php

namespace App\Services\Investment;

use App\Models\Investment\InvestmentMaster;
use App\Models\Investment\InvestmentTrade;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvestmentJournalService
{
    /**
     * Generate and preview journal entry for purchase trade
     */
    public function previewPurchaseJournal(InvestmentTrade $trade, InvestmentMaster $investment): array
    {
        $journalEntries = [];

        // Calculate investment amount (gross + fees if capitalized)
        $investmentAmount = $trade->gross_amount;
        if ($this->shouldCapitalizeFees($investment) && ($trade->fees ?? 0) > 0) {
            $investmentAmount += $trade->fees;
        }

        // Debit: Investment Asset Account
        $journalEntries[] = [
            'chart_account_id' => $investment->gl_asset_account,
            'nature' => 'debit',
            'amount' => $investmentAmount,
            'description' => "Investment purchase: {$investment->instrument_code}",
        ];

        // Credit: Bank/Cash Account (net amount after fees and tax)
        $netAmount = $trade->gross_amount - ($trade->fees ?? 0) - ($trade->tax_withheld ?? 0);
        $journalEntries[] = [
            'chart_account_id' => $this->getBankAccountId($trade), // Will need to be passed or determined
            'nature' => 'credit',
            'amount' => $netAmount,
            'description' => "Payment for investment: {$investment->instrument_code}",
        ];

        // Debit: Fees Expense (if fees not capitalized)
        if (($trade->fees ?? 0) > 0 && !$this->shouldCapitalizeFees($investment)) {
            $journalEntries[] = [
                'chart_account_id' => $this->getFeesExpenseAccountId(), // Configurable
                'nature' => 'debit',
                'amount' => $trade->fees,
                'description' => "Investment fees: {$investment->instrument_code}",
            ];
        }

        // Credit: Tax Withheld Payable (if applicable)
        if (($trade->tax_withheld ?? 0) > 0) {
            $journalEntries[] = [
                'chart_account_id' => $this->getTaxWithheldAccountId(), // Configurable
                'nature' => 'credit',
                'amount' => $trade->tax_withheld,
                'description' => "Tax withheld on investment: {$investment->instrument_code}",
            ];
        }

        // Calculate totals
        $totalDebit = collect($journalEntries)->where('nature', 'debit')->sum('amount');
        $totalCredit = collect($journalEntries)->where('nature', 'credit')->sum('amount');

        return [
            'entries' => $journalEntries,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'balanced' => abs($totalDebit - $totalCredit) < 0.01, // Allow small rounding differences
        ];
    }

    /**
     * Post initial recognition journal for purchase trade
     */
    public function postPurchaseJournal(InvestmentTrade $trade, InvestmentMaster $investment, User $user, ?int $bankAccountId = null): Journal
    {
        // Check period locking (if service exists)
        if (class_exists(\App\Services\PeriodClosing\PeriodLockService::class)) {
            try {
                $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
                $periodLockService->validateTransactionDate($trade->trade_date->format('Y-m-d'), $user->company_id, 'investment trade');
            } catch (\Exception $e) {
                Log::warning('Investment trade - Cannot post: Period is locked', [
                    'trade_id' => $trade->trade_id,
                    'trade_date' => $trade->trade_date->format('Y-m-d'),
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        DB::beginTransaction();
        try {
            // Generate journal reference
            $nextId = Journal::max('id') + 1;
            $reference = 'INV-TRADE-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

            // Create journal
            $journal = Journal::create([
                'date' => $trade->trade_date,
                'reference' => $reference,
                'reference_type' => 'investment_trade',
                'description' => "Investment Purchase: {$investment->instrument_code}",
                'branch_id' => $trade->branch_id,
                'user_id' => $user->id,
                'approved' => false, // Will go through approval workflow
            ]);

            // Get preview entries
            $preview = $this->previewPurchaseJournal($trade, $investment);
            
            // Override bank account if provided
            if ($bankAccountId) {
                foreach ($preview['entries'] as &$entry) {
                    if ($entry['nature'] === 'credit' && str_contains($entry['description'], 'Payment for investment')) {
                        $entry['chart_account_id'] = $bankAccountId;
                    }
                }
            }

            // Create journal items
            foreach ($preview['entries'] as $entry) {
                $journalItem = JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $entry['chart_account_id'],
                    'amount' => $entry['amount'],
                    'nature' => $entry['nature'],
                    'description' => $entry['description'],
                ]);

                // Create GL transaction
                GlTransaction::create([
                    'chart_account_id' => $entry['chart_account_id'],
                    'amount' => $entry['amount'],
                    'nature' => $entry['nature'],
                    'transaction_id' => $journal->id,
                    'transaction_type' => 'journal',
                    'date' => $trade->trade_date,
                    'description' => $entry['description'],
                    'branch_id' => $trade->branch_id,
                    'user_id' => $user->id,
                ]);
            }

            // Link journal to trade
            $trade->posted_journal_id = $journal->id;
            $trade->save();

            // Initialize approval workflow
            $journal->initializeApprovalWorkflow();

            DB::commit();
            return $journal->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to post purchase journal', [
                'trade_id' => $trade->trade_id,
                'investment_id' => $investment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get bank account ID (placeholder - should be passed or from investment settings)
     */
    protected function getBankAccountId(InvestmentTrade $trade): int
    {
        // TODO: Get from investment settings or trade data
        // For now, return a default or throw exception
        $defaultBankAccount = \App\Models\BankAccount::where('company_id', $trade->company_id)
            ->first();
        
        if (!$defaultBankAccount) {
            throw new \Exception('No active bank account found. Please configure bank account for investments.');
        }

        return $defaultBankAccount->chart_account_id;
    }

    /**
     * Check if fees should be capitalized
     */
    protected function shouldCapitalizeFees(InvestmentMaster $investment): bool
    {
        // Default: capitalize fees for amortized cost investments
        return $investment->accounting_class === 'AMORTISED_COST';
    }

    /**
     * Get fees expense account ID
     */
    protected function getFeesExpenseAccountId(): int
    {
        // TODO: Get from system settings or investment configuration
        $account = \App\Models\ChartAccount::where('account_name', 'like', '%Investment%Fee%')
            ->orWhere('account_name', 'like', '%Brokerage%Fee%')
            ->first();
        
        if (!$account) {
            throw new \Exception('Investment fees expense account not configured');
        }

        return $account->id;
    }

    /**
     * Get tax withheld payable account ID
     */
    protected function getTaxWithheldAccountId(): int
    {
        // TODO: Get from system settings
        $account = \App\Models\ChartAccount::where('account_name', 'like', '%Tax%Withheld%')
            ->orWhere('account_name', 'like', '%WHT%Payable%')
            ->first();
        
        if (!$account) {
            throw new \Exception('Tax withheld payable account not configured');
        }

        return $account->id;
    }
}

