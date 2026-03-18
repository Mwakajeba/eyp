<?php

namespace App\Services\Investment;

use App\Models\Investment\InvestmentMaster;
use App\Models\Investment\InvestmentAmortLine;
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
 * Investment Accrual Service
 * 
 * Handles periodic interest accrual for investments
 */
class InvestmentAccrualService
{
    protected $journalService;
    protected $amortizationService;

    public function __construct(
        InvestmentJournalService $journalService,
        InvestmentAmortizationService $amortizationService
    ) {
        $this->journalService = $journalService;
        $this->amortizationService = $amortizationService;
    }

    /**
     * Accrue interest for a specific investment for a given period
     */
    public function accrueInterest(InvestmentMaster $investment, Carbon $accrualDate, User $user): ?Journal
    {
        if (!$investment->eir_rate) {
            throw new Exception('EIR not calculated for investment');
        }

        if (!$investment->gl_asset_account || !$investment->gl_accrued_interest_account || !$investment->gl_interest_income_account) {
            throw new Exception('GL accounts not configured for investment');
        }

        // Get or create amortization line for this period
        $amortLine = $this->amortizationService->getNextAmortizationLine($investment, $accrualDate->copy()->subDay());

        if (!$amortLine) {
            // Generate amortization schedule if not exists
            $this->amortizationService->saveAmortizationSchedule($investment, $accrualDate);
            $amortLine = $this->amortizationService->getNextAmortizationLine($investment, $accrualDate->copy()->subDay());
        }

        if (!$amortLine || $amortLine->isPosted()) {
            Log::info('No pending amortization line found for accrual', [
                'investment_id' => $investment->id,
                'accrual_date' => $accrualDate->format('Y-m-d'),
            ]);
            return null;
        }

        // Check if already accrued for this period
        if ($amortLine->isPosted()) {
            Log::info('Interest already accrued for this period', [
                'investment_id' => $investment->id,
                'period_end' => $amortLine->period_end->format('Y-m-d'),
            ]);
            return $amortLine->journal;
        }

        // Check period locking
        if (class_exists(\App\Services\PeriodClosing\PeriodLockService::class)) {
            try {
                $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
                $periodLockService->validateTransactionDate($accrualDate->format('Y-m-d'), $user->company_id, 'investment accrual');
            } catch (\Exception $e) {
                Log::warning('Investment accrual - Cannot post: Period is locked', [
                    'investment_id' => $investment->id,
                    'accrual_date' => $accrualDate->format('Y-m-d'),
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        DB::beginTransaction();
        try {
            // Calculate accrual amount (proportional if partial period)
            $accrualAmount = $this->calculateAccrualAmount($amortLine, $accrualDate);

            // Generate journal reference
            $nextId = Journal::max('id') + 1;
            $reference = 'INV-ACCR-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

            // Create journal
            $journal = Journal::create([
                'date' => $accrualDate,
                'reference' => $reference,
                'reference_type' => 'investment_accrual',
                'description' => "Interest Accrual: {$investment->instrument_code} - Period {$amortLine->period_start->format('Y-m-d')} to {$amortLine->period_end->format('Y-m-d')}",
                'branch_id' => $investment->company->branches()->first()?->id ?? null,
                'user_id' => $user->id,
                'approved' => false,
            ]);

            // Debit: Accrued Interest Asset
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $investment->gl_accrued_interest_account,
                'amount' => $accrualAmount,
                'nature' => 'debit',
                'description' => "Interest accrual: {$investment->instrument_code}",
            ]);

            GlTransaction::create([
                'chart_account_id' => $investment->gl_accrued_interest_account,
                'amount' => $accrualAmount,
                'nature' => 'debit',
                'transaction_id' => $journal->id,
                'transaction_type' => 'journal',
                'date' => $accrualDate,
                'description' => "Interest accrual: {$investment->instrument_code}",
                'branch_id' => $journal->branch_id,
                'user_id' => $user->id,
            ]);

            // Credit: Interest Income
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $investment->gl_interest_income_account,
                'amount' => $accrualAmount,
                'nature' => 'credit',
                'description' => "Interest income: {$investment->instrument_code}",
            ]);

            GlTransaction::create([
                'chart_account_id' => $investment->gl_interest_income_account,
                'amount' => $accrualAmount,
                'nature' => 'credit',
                'transaction_id' => $journal->id,
                'transaction_type' => 'journal',
                'date' => $accrualDate,
                'description' => "Interest income: {$investment->instrument_code}",
                'branch_id' => $journal->branch_id,
                'user_id' => $user->id,
            ]);

            // Initialize approval workflow
            $journal->initializeApprovalWorkflow();

            // Mark amortization line as posted
            $this->amortizationService->markAsPosted($amortLine, $journal->id);

            DB::commit();

            Log::info('Interest accrued successfully', [
                'investment_id' => $investment->id,
                'journal_id' => $journal->id,
                'accrual_amount' => $accrualAmount,
                'period_end' => $amortLine->period_end->format('Y-m-d'),
            ]);

            return $journal->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to accrue interest', [
                'investment_id' => $investment->id,
                'accrual_date' => $accrualDate->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate accrual amount (proportional if partial period)
     */
    protected function calculateAccrualAmount(InvestmentAmortLine $amortLine, Carbon $accrualDate): float
    {
        // If accrual date is before period end, calculate proportional amount
        if ($accrualDate < $amortLine->period_end) {
            $totalDays = $amortLine->period_start->diffInDays($amortLine->period_end);
            $accruedDays = $amortLine->period_start->diffInDays($accrualDate);
            
            if ($totalDays > 0) {
                return ($amortLine->interest_income / $totalDays) * $accruedDays;
            }
        }

        // Full period amount
        return $amortLine->interest_income;
    }

    /**
     * Accrue interest for all active investments for a given period
     */
    public function accrueInterestForAll(int $companyId, Carbon $accrualDate, User $user): array
    {
        $investments = InvestmentMaster::where('company_id', $companyId)
            ->where('status', 'ACTIVE')
            ->whereNotNull('eir_rate')
            ->whereNotNull('gl_asset_account')
            ->whereNotNull('gl_accrued_interest_account')
            ->whereNotNull('gl_interest_income_account')
            ->get();

        $results = [
            'processed' => 0,
            'skipped' => 0,
            'errors' => 0,
            'journals' => [],
        ];

        foreach ($investments as $investment) {
            try {
                // Check if already accrued for this period
                $existingAccrual = InvestmentAmortLine::where('investment_id', $investment->id)
                    ->where('period_end', '<=', $accrualDate)
                    ->where('posted', true)
                    ->whereDate('posted_at', $accrualDate)
                    ->first();

                if ($existingAccrual) {
                    $results['skipped']++;
                    continue;
                }

                $journal = $this->accrueInterest($investment, $accrualDate, $user);
                if ($journal) {
                    $results['processed']++;
                    $results['journals'][] = $journal->id;
                } else {
                    $results['skipped']++;
                }
            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Failed to accrue interest for investment', [
                    'investment_id' => $investment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }
}

