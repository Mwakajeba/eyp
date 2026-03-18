<?php

namespace App\Services\Investment;

use App\Models\Investment\InvestmentMaster;
use App\Models\Investment\InvestmentTrade;
use App\Services\Investment\InvestmentJournalService;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Coupon Payment Service
 * 
 * Handles coupon payment processing and journal entries
 */
class CouponPaymentService
{
    protected $journalService;

    public function __construct(InvestmentJournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    /**
     * Process coupon payment
     */
    public function processCouponPayment(
        InvestmentMaster $investment,
        float $couponAmount,
        Carbon $paymentDate,
        ?string $bankRef = null,
        User $user
    ): array {
        if (!$investment->gl_accrued_interest_account || !$investment->gl_interest_income_account) {
            throw new Exception('GL accounts not configured for investment');
        }

        // Check period locking
        if (class_exists(\App\Services\PeriodClosing\PeriodLockService::class)) {
            try {
                $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
                $periodLockService->validateTransactionDate($paymentDate->format('Y-m-d'), $user->company_id, 'coupon payment');
            } catch (\Exception $e) {
                Log::warning('Coupon payment - Cannot post: Period is locked', [
                    'investment_id' => $investment->id,
                    'payment_date' => $paymentDate->format('Y-m-d'),
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        DB::beginTransaction();
        try {
            // Create trade record for coupon payment
            $trade = InvestmentTrade::create([
                'investment_id' => $investment->id,
                'company_id' => $investment->company_id,
                'branch_id' => $investment->company->branches()->first()?->id ?? null,
                'trade_type' => 'COUPON',
                'trade_date' => $paymentDate,
                'settlement_date' => $paymentDate,
                'trade_price' => 0,
                'trade_units' => 0,
                'gross_amount' => $couponAmount,
                'fees' => 0,
                'tax_withheld' => 0,
                'bank_ref' => $bankRef,
                'settlement_status' => 'SETTLED',
                'created_by' => $user->id,
            ]);

            // Generate journal reference
            $nextId = Journal::max('id') + 1;
            $reference = 'INV-COUPON-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

            // Create journal
            $journal = Journal::create([
                'date' => $paymentDate,
                'reference' => $reference,
                'reference_type' => 'investment_coupon',
                'description' => "Coupon Payment: {$investment->instrument_code}",
                'branch_id' => $trade->branch_id,
                'user_id' => $user->id,
                'approved' => false,
            ]);

            // Get bank account for payment (from investment settings or default)
            $bankAccountId = $this->getBankAccountId($investment, $user);

            // Debit: Bank Account (cash received)
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $bankAccountId,
                'amount' => $couponAmount,
                'nature' => 'debit',
                'description' => "Coupon payment received: {$investment->instrument_code}",
            ]);

            GlTransaction::create([
                'chart_account_id' => $bankAccountId,
                'amount' => $couponAmount,
                'nature' => 'debit',
                'transaction_id' => $journal->id,
                'transaction_type' => 'journal',
                'date' => $paymentDate,
                'description' => "Coupon payment received: {$investment->instrument_code}",
                'branch_id' => $trade->branch_id,
                'user_id' => $user->id,
            ]);

            // Credit: Accrued Interest (reduce accrued balance)
            // If accrued interest account has balance, reduce it first
            $accruedBalance = $this->getAccruedInterestBalance($investment);
            $accruedReduction = min($couponAmount, $accruedBalance);
            $incomeCredit = $couponAmount - $accruedReduction;

            if ($accruedReduction > 0) {
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $investment->gl_accrued_interest_account,
                    'amount' => $accruedReduction,
                    'nature' => 'credit',
                    'description' => "Accrued interest reduction: {$investment->instrument_code}",
                ]);

                GlTransaction::create([
                    'chart_account_id' => $investment->gl_accrued_interest_account,
                    'amount' => $accruedReduction,
                    'nature' => 'credit',
                    'transaction_id' => $journal->id,
                    'transaction_type' => 'journal',
                    'date' => $paymentDate,
                    'description' => "Accrued interest reduction: {$investment->instrument_code}",
                    'branch_id' => $trade->branch_id,
                    'user_id' => $user->id,
                ]);
            }

            // Credit: Interest Income (remaining amount)
            if ($incomeCredit > 0) {
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $investment->gl_interest_income_account,
                    'amount' => $incomeCredit,
                    'nature' => 'credit',
                    'description' => "Interest income: {$investment->instrument_code}",
                ]);

                GlTransaction::create([
                    'chart_account_id' => $investment->gl_interest_income_account,
                    'amount' => $incomeCredit,
                    'nature' => 'credit',
                    'transaction_id' => $journal->id,
                    'transaction_type' => 'journal',
                    'date' => $paymentDate,
                    'description' => "Interest income: {$investment->instrument_code}",
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

            Log::info('Coupon payment processed successfully', [
                'investment_id' => $investment->id,
                'trade_id' => $trade->trade_id,
                'journal_id' => $journal->id,
                'coupon_amount' => $couponAmount,
            ]);

            return [
                'trade' => $trade,
                'journal' => $journal,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process coupon payment', [
                'investment_id' => $investment->id,
                'payment_date' => $paymentDate->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get accrued interest balance for investment
     */
    protected function getAccruedInterestBalance(InvestmentMaster $investment): float
    {
        // Sum all debit transactions minus credit transactions for accrued interest account
        $debits = GlTransaction::where('chart_account_id', $investment->gl_accrued_interest_account)
            ->where('nature', 'debit')
            ->sum('amount');

        $credits = GlTransaction::where('chart_account_id', $investment->gl_accrued_interest_account)
            ->where('nature', 'credit')
            ->sum('amount');

        return max(0, $debits - $credits);
    }

    /**
     * Get bank account ID for coupon payment
     */
    protected function getBankAccountId(InvestmentMaster $investment, User $user): int
    {
        // TODO: Get from investment settings or system defaults
        $defaultBankAccount = \App\Models\BankAccount::where('company_id', $investment->company_id)
            ->first();

        if (!$defaultBankAccount) {
            throw new Exception('No active bank account found. Please configure bank account for investments.');
        }

        return $defaultBankAccount->chart_account_id;
    }
}

