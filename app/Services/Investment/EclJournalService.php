<?php

namespace App\Services\Investment;

use App\Models\Investment\InvestmentMaster;
use App\Models\Investment\EclCalc;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EclJournalService
{
    /**
     * Post ECL allowance journal entry
     * 
     * @param EclCalc $eclCalc
     * @param User $user
     * @return Journal
     */
    public function postEclAllowance(EclCalc $eclCalc, User $user): Journal
    {
        DB::beginTransaction();
        try {
            $investment = $eclCalc->investment;
            $calculationDate = $eclCalc->calculation_date;
            $eclAmount = $eclCalc->ecl_amount;

            // Get previous ECL allowance balance
            $previousEclCalc = EclCalc::where('investment_id', $investment->id)
                ->where('calculation_date', '<', $calculationDate)
                ->where('is_posted', true)
                ->latest('calculation_date')
                ->first();

            $previousEclAmount = $previousEclCalc ? $previousEclCalc->ecl_amount : 0;
            $movement = $eclAmount - $previousEclAmount;

            // Create journal entry
            $journal = Journal::create([
                'company_id' => $investment->company_id,
                'branch_id' => $investment->branch_id,
                'journal_date' => $calculationDate,
                'reference_number' => 'ECL-ALLOW-' . $eclCalc->id,
                'description' => "ECL Allowance for {$investment->instrument_code} - Stage {$eclCalc->stage}",
                'total_amount' => abs($movement),
                'created_by' => $user->id,
                'status' => 'posted', // Auto-post for ECL, or can be PENDING_APPROVAL
            ]);

            // Get ECL allowance account (should be configured in investment master or chart accounts)
            // For now, using a placeholder - this should be configured per investment or instrument type
            $eclAllowanceAccount = $this->getEclAllowanceAccount($investment);
            $eclExpenseAccount = $this->getEclExpenseAccount($investment);

            if ($movement > 0) {
                // Increase in ECL allowance (expense)
                // Debit: ECL Expense Account
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $eclExpenseAccount,
                    'debit_amount' => $movement,
                    'credit_amount' => 0,
                    'description' => "ECL Expense - {$investment->instrument_code}",
                ]);

                // Credit: ECL Allowance Account (contra-asset)
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $eclAllowanceAccount,
                    'debit_amount' => 0,
                    'credit_amount' => $movement,
                    'description' => "ECL Allowance - {$investment->instrument_code}",
                ]);
            } elseif ($movement < 0) {
                // Decrease in ECL allowance (reversal/income)
                // Debit: ECL Allowance Account
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $eclAllowanceAccount,
                    'debit_amount' => abs($movement),
                    'credit_amount' => 0,
                    'description' => "ECL Allowance Reversal - {$investment->instrument_code}",
                ]);

                // Credit: ECL Expense Account (reversal)
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $eclExpenseAccount,
                    'debit_amount' => 0,
                    'credit_amount' => abs($movement),
                    'description' => "ECL Expense Reversal - {$investment->instrument_code}",
                ]);
            }

            // Update ECL calculation record
            $eclCalc->update([
                'posted_journal_id' => $journal->id,
                'is_posted' => true,
                'posted_at' => now(),
                'status' => 'POSTED',
            ]);

            DB::commit();
            Log::info('ECL allowance journal posted', [
                'ecl_calc_id' => $eclCalc->id,
                'journal_id' => $journal->id,
                'ecl_amount' => $eclAmount,
                'movement' => $movement,
            ]);

            return $journal;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to post ECL allowance journal', [
                'ecl_calc_id' => $eclCalc->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Post write-off journal entry
     * 
     * @param InvestmentMaster $investment
     * @param float $writeOffAmount
     * @param Carbon $writeOffDate
     * @param User $user
     * @param string $reason
     * @return Journal
     */
    public function postWriteOff(
        InvestmentMaster $investment,
        float $writeOffAmount,
        Carbon $writeOffDate,
        User $user,
        string $reason = ''
    ): Journal {
        DB::beginTransaction();
        try {
            // Get current ECL allowance balance
            $latestEclCalc = EclCalc::where('investment_id', $investment->id)
                ->where('is_posted', true)
                ->latest('calculation_date')
                ->first();

            $eclAllowanceBalance = $latestEclCalc ? $latestEclCalc->ecl_amount : 0;

            // Create journal entry
            $journal = Journal::create([
                'company_id' => $investment->company_id,
                'branch_id' => $investment->branch_id,
                'journal_date' => $writeOffDate,
                'reference_number' => 'ECL-WRITEOFF-' . $investment->id . '-' . now()->format('YmdHis'),
                'description' => "Investment Write-off: {$investment->instrument_code} - {$reason}",
                'total_amount' => $writeOffAmount,
                'created_by' => $user->id,
                'status' => 'posted',
            ]);

            // Get accounts
            $eclAllowanceAccount = $this->getEclAllowanceAccount($investment);
            $investmentAssetAccount = $investment->gl_asset_account;
            $writeOffExpenseAccount = $this->getWriteOffExpenseAccount($investment);

            // Debit: ECL Allowance (to reverse allowance)
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $eclAllowanceAccount,
                'debit_amount' => min($eclAllowanceBalance, $writeOffAmount),
                'credit_amount' => 0,
                'description' => "ECL Allowance Reversal - Write-off",
            ]);

            // Debit: Write-off Expense (for amount not covered by allowance)
            if ($writeOffAmount > $eclAllowanceBalance) {
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $writeOffExpenseAccount,
                    'debit_amount' => $writeOffAmount - $eclAllowanceBalance,
                    'credit_amount' => 0,
                    'description' => "Additional Write-off Expense",
                ]);
            }

            // Credit: Investment Asset Account
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $investmentAssetAccount,
                'debit_amount' => 0,
                'credit_amount' => $writeOffAmount,
                'description' => "Investment Write-off - {$investment->instrument_code}",
            ]);

            DB::commit();
            Log::info('Investment write-off journal posted', [
                'investment_id' => $investment->id,
                'journal_id' => $journal->id,
                'write_off_amount' => $writeOffAmount,
                'reason' => $reason,
            ]);

            return $journal;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to post write-off journal', [
                'investment_id' => $investment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get ECL allowance account for investment
     * TODO: This should be configured per investment or instrument type
     */
    protected function getEclAllowanceAccount(InvestmentMaster $investment): int
    {
        // Placeholder - should be configured in investment master or system settings
        // For now, return a default account ID (should be configured)
        return config('investment.ecl_allowance_account_id', 1);
    }

    /**
     * Get ECL expense account for investment
     */
    protected function getEclExpenseAccount(InvestmentMaster $investment): int
    {
        // Placeholder - should be configured
        return config('investment.ecl_expense_account_id', 1);
    }

    /**
     * Get write-off expense account
     */
    protected function getWriteOffExpenseAccount(InvestmentMaster $investment): int
    {
        // Placeholder - should be configured
        return config('investment.write_off_expense_account_id', 1);
    }

    /**
     * Calculate ECL allowance movement between two calculations
     */
    public function calculateAllowanceMovement(EclCalc $currentCalc, ?EclCalc $previousCalc = null): float
    {
        $currentAmount = $currentCalc->ecl_amount;
        $previousAmount = $previousCalc ? $previousCalc->ecl_amount : 0;

        return $currentAmount - $previousAmount;
    }
}

