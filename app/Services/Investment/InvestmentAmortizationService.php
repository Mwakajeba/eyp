<?php

namespace App\Services\Investment;

use App\Models\Investment\InvestmentMaster;
use App\Models\Investment\InvestmentAmortLine;
use App\Services\Investment\EirCalculatorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Investment Amortization Service
 * 
 * Generates and manages amortization schedules for investments
 */
class InvestmentAmortizationService
{
    protected $eirCalculator;

    public function __construct(EirCalculatorService $eirCalculator)
    {
        $this->eirCalculator = $eirCalculator;
    }

    /**
     * Generate amortization schedule for an investment
     */
    public function generateAmortizationSchedule(InvestmentMaster $investment, ?Carbon $asOfDate = null): array
    {
        if (!$investment->eir_rate) {
            // Calculate EIR if not set
            $eirResult = $this->eirCalculator->recalculateEir($investment);
            if (!$eirResult['converged']) {
                throw new Exception('Failed to calculate EIR for investment');
            }
        }

        $asOfDate = $asOfDate ?? Carbon::now();
        $eirRate = $investment->eir_rate / 100.0; // Convert percentage to decimal
        $dayCount = $investment->day_count ?? 'ACT/365';

        // Get cash flows
        $cashFlows = $this->eirCalculator->generateCashFlows($investment);
        
        // Generate amortization lines
        $amortLines = [];
        $carryingAmount = $investment->nominal_amount; // Initial carrying amount
        $baseDate = $investment->purchase_date;

        // Process each cash flow period
        for ($i = 1; $i < count($cashFlows); $i++) {
            $periodStart = $i == 1 ? $baseDate : $cashFlows[$i - 1]['date'];
            $periodEnd = $cashFlows[$i]['date'];
            
            if ($periodEnd <= $asOfDate) {
                continue; // Skip past periods
            }

            // Calculate days in period
            $days = $this->getDaysBetween($periodStart, $periodEnd, $dayCount);
            $years = $this->daysToYears($days, $dayCount);

            // Calculate interest income using EIR
            $interestIncome = $carryingAmount * $eirRate * $years;

            // Calculate cash flow for this period
            $cashFlow = $cashFlows[$i]['amount'];

            // Calculate amortization (difference between interest income and cash flow)
            $amortization = $cashFlow - $interestIncome;

            // Update carrying amount
            $carryingAmount += $amortization;

            $amortLines[] = [
                'investment_id' => $investment->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'days' => $days,
                'opening_carrying_amount' => $carryingAmount - $amortization,
                'interest_income' => $interestIncome,
                'cash_flow' => $cashFlow,
                'amortization' => $amortization,
                'closing_carrying_amount' => $carryingAmount,
                'eir_rate' => $investment->eir_rate,
            ];
        }

        return $amortLines;
    }

    /**
     * Save amortization schedule to database
     */
    public function saveAmortizationSchedule(InvestmentMaster $investment, ?Carbon $asOfDate = null): array
    {
        DB::beginTransaction();
        try {
            // Delete existing future amortization lines
            $asOfDate = $asOfDate ?? Carbon::now();
            InvestmentAmortLine::where('investment_id', $investment->id)
                ->where('period_end', '>', $asOfDate)
                ->delete();

            // Generate new schedule
            $amortLines = $this->generateAmortizationSchedule($investment, $asOfDate);

            // Save to database
            $saved = [];
            foreach ($amortLines as $line) {
                $saved[] = InvestmentAmortLine::create($line);
            }

            DB::commit();

            Log::info('Amortization schedule generated', [
                'investment_id' => $investment->id,
                'lines_count' => count($saved),
            ]);

            return $saved;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to save amortization schedule', [
                'investment_id' => $investment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get next amortization line for an investment
     */
    public function getNextAmortizationLine(InvestmentMaster $investment, ?Carbon $asOfDate = null): ?InvestmentAmortLine
    {
        $asOfDate = $asOfDate ?? Carbon::now();
        
        return InvestmentAmortLine::where('investment_id', $investment->id)
            ->where('period_end', '>', $asOfDate)
            ->where('posted', false)
            ->orderBy('period_end', 'asc')
            ->first();
    }

    /**
     * Get all pending amortization lines
     */
    public function getPendingAmortizationLines(InvestmentMaster $investment, ?Carbon $asOfDate = null): \Illuminate\Database\Eloquent\Collection
    {
        $asOfDate = $asOfDate ?? Carbon::now();
        
        return InvestmentAmortLine::where('investment_id', $investment->id)
            ->where('period_end', '<=', $asOfDate)
            ->where('posted', false)
            ->orderBy('period_end', 'asc')
            ->get();
    }

    /**
     * Mark amortization line as posted
     */
    public function markAsPosted(InvestmentAmortLine $amortLine, int $journalId): void
    {
        $amortLine->update([
            'posted' => true,
            'posted_at' => Carbon::now(),
            'journal_id' => $journalId,
        ]);
    }

    /**
     * Recompute amortization schedule (when EIR changes or cash flows change)
     */
    public function recomputeAmortizationSchedule(InvestmentMaster $investment): array
    {
        // Recalculate EIR first
        $this->eirCalculator->recalculateEir($investment);
        
        // Regenerate schedule
        return $this->saveAmortizationSchedule($investment);
    }

    /**
     * Get days between two dates based on day count convention
     */
    protected function getDaysBetween($date1, $date2, string $dayCount): int
    {
        return $date1->diffInDays($date2);
    }

    /**
     * Convert days to years based on day count convention
     */
    protected function daysToYears(int $days, string $dayCount): float
    {
        switch ($dayCount) {
            case 'ACT/365':
                return $days / 365.0;
            case 'ACT/360':
                return $days / 360.0;
            case '30/360':
                return $days / 360.0;
            default:
                return $days / 365.0;
        }
    }
}

