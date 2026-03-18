<?php

namespace App\Services\Investment;

use App\Models\Investment\InvestmentMaster;
use App\Models\Investment\InvestmentTrade;
use App\Models\Investment\EclInput;
use App\Models\Investment\EclCalc;
use App\Models\Investment\EclModelParam;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EclStagingService
{
    /**
     * Determine the IFRS 9 stage for an investment based on SICR (Significant Increase in Credit Risk) detection
     * 
     * @param InvestmentMaster $investment
     * @param array $forwardLookingInfo Optional forward-looking information
     * @return array ['stage' => int, 'reason' => string, 'stage_assigned_date' => Carbon]
     */
    public function determineStage(
        InvestmentMaster $investment,
        array $forwardLookingInfo = []
    ): array {
        $currentStage = $this->getCurrentStage($investment);
        $newStage = $currentStage;
        $reasons = [];
        $stageAssignedDate = Carbon::today();

        // Get model parameters for staging rules
        $modelParams = $this->getStagingRules($investment);

        // Rule 1: Days Past Due (DPD) check
        $dpdResult = $this->checkDaysPastDue($investment, $modelParams);
        if ($dpdResult['should_move']) {
            $newStage = max($newStage, $dpdResult['target_stage']);
            $reasons[] = $dpdResult['reason'];
        }

        // Rule 2: PD Increase check (SICR indicator)
        $pdIncreaseResult = $this->checkPdIncrease($investment, $modelParams, $forwardLookingInfo);
        if ($pdIncreaseResult['should_move']) {
            $newStage = max($newStage, $pdIncreaseResult['target_stage']);
            $reasons[] = $pdIncreaseResult['reason'];
        }

        // Rule 3: Credit rating downgrade
        $ratingResult = $this->checkCreditRatingDowngrade($investment, $forwardLookingInfo);
        if ($ratingResult['should_move']) {
            $newStage = max($newStage, $ratingResult['target_stage']);
            $reasons[] = $ratingResult['reason'];
        }

        // Rule 4: Payment delays or defaults
        $paymentResult = $this->checkPaymentDelays($investment, $modelParams, $forwardLookingInfo);
        if ($paymentResult['should_move']) {
            $newStage = max($newStage, $paymentResult['target_stage']);
            $reasons[] = $paymentResult['reason'];
        }

        // Rule 5: External indicators (bankruptcy, restructuring, etc.)
        $externalResult = $this->checkExternalIndicators($investment, $forwardLookingInfo);
        if ($externalResult['should_move']) {
            $newStage = max($newStage, $externalResult['target_stage']);
            $reasons[] = $externalResult['reason'];
        }

        // Stage 3: Credit-impaired (defaulted)
        if ($this->isCreditImpaired($investment, $modelParams, $forwardLookingInfo)) {
            $newStage = 3;
            $reasons[] = 'Credit-impaired: Investment has defaulted or is in default';
        }

        // If stage changed, update the assignment date
        if ($newStage > $currentStage) {
            $stageAssignedDate = Carbon::today();
        } else {
            // Keep existing stage assignment date if available
            $latestCalc = EclCalc::where('investment_id', $investment->id)
                ->latest('calculation_date')
                ->first();
            if ($latestCalc && $latestCalc->stage_assigned_date) {
                $stageAssignedDate = $latestCalc->stage_assigned_date;
            }
        }

        return [
            'stage' => $newStage,
            'reason' => implode('; ', $reasons) ?: 'No SICR indicators detected',
            'stage_assigned_date' => $stageAssignedDate,
            'previous_stage' => $currentStage,
        ];
    }

    /**
     * Get current stage of investment
     */
    protected function getCurrentStage(InvestmentMaster $investment): int
    {
        // Check latest ECL calculation
        $latestCalc = EclCalc::where('investment_id', $investment->id)
            ->latest('calculation_date')
            ->first();

        if ($latestCalc) {
            return $latestCalc->stage;
        }

        // Check latest trade
        $latestTrade = InvestmentTrade::where('investment_id', $investment->id)
            ->latest('trade_date')
            ->first();

        if ($latestTrade && isset($latestTrade->stage)) {
            return $latestTrade->stage;
        }

        // Default to Stage 1
        return 1;
    }

    /**
     * Get staging rules from model parameters
     */
    protected function getStagingRules(InvestmentMaster $investment): array
    {
        $modelParam = EclModelParam::where('company_id', $investment->company_id)
            ->where(function($q) use ($investment) {
                $q->where('instrument_type', $investment->instrument_type)
                  ->orWhere('instrument_type', 'ALL');
            })
            ->where('is_active', true)
            ->effectiveAsOf(Carbon::today())
            ->first();

        if ($modelParam && $modelParam->staging_rules) {
            return $modelParam->staging_rules;
        }

        // Default staging rules
        return [
            'dpd_stage2_threshold' => 30, // Days past due for Stage 2
            'dpd_stage3_threshold' => 90, // Days past due for Stage 3
            'pd_increase_threshold' => 0.20, // 20% increase in PD for SICR
            'rating_downgrade_threshold' => 2, // 2-notch downgrade
            'payment_delay_threshold' => 30, // 30 days payment delay
        ];
    }

    /**
     * Check days past due
     */
    protected function checkDaysPastDue(InvestmentMaster $investment, array $rules): array
    {
        $thresholdStage2 = $rules['dpd_stage2_threshold'] ?? 30;
        $thresholdStage3 = $rules['dpd_stage3_threshold'] ?? 90;

        // Calculate days past due (simplified - would need actual payment schedule)
        $daysPastDue = $this->calculateDaysPastDue($investment);

        if ($daysPastDue >= $thresholdStage3) {
            return [
                'should_move' => true,
                'target_stage' => 3,
                'reason' => "Days past due ({$daysPastDue}) exceeds Stage 3 threshold ({$thresholdStage3} days)",
            ];
        } elseif ($daysPastDue >= $thresholdStage2) {
            return [
                'should_move' => true,
                'target_stage' => 2,
                'reason' => "Days past due ({$daysPastDue}) exceeds Stage 2 threshold ({$thresholdStage2} days)",
            ];
        }

        return ['should_move' => false];
    }

    /**
     * Calculate days past due for an investment
     */
    protected function calculateDaysPastDue(InvestmentMaster $investment): int
    {
        // This is a simplified calculation
        // In a real system, you'd check against actual payment schedules
        if (!$investment->maturity_date) {
            return 0;
        }

        $today = Carbon::today();
        $maturityDate = Carbon::parse($investment->maturity_date);

        // If past maturity and not settled, it's past due
        if ($today->gt($maturityDate) && $investment->status !== 'MATURED' && $investment->status !== 'DISPOSED') {
            return $today->diffInDays($maturityDate);
        }

        return 0;
    }

    /**
     * Check PD increase (SICR indicator)
     */
    protected function checkPdIncrease(
        InvestmentMaster $investment,
        array $rules,
        array $forwardLookingInfo
    ): array {
        $threshold = $rules['pd_increase_threshold'] ?? 0.20; // 20% increase

        // Get current PD
        $currentPd = $this->getCurrentPd($investment);

        // Get historical PD (from previous calculation)
        $previousCalc = EclCalc::where('investment_id', $investment->id)
            ->where('calculation_date', '<', Carbon::today())
            ->latest('calculation_date')
            ->first();

        if (!$previousCalc) {
            return ['should_move' => false];
        }

        $previousPd = $previousCalc->pd;

        if ($previousPd > 0) {
            $pdIncrease = ($currentPd - $previousPd) / $previousPd;

            if ($pdIncrease >= $threshold) {
                return [
                    'should_move' => true,
                    'target_stage' => 2,
                    'reason' => "PD increased by " . number_format($pdIncrease * 100, 2) . "% (threshold: " . number_format($threshold * 100, 2) . "%)",
                ];
            }
        }

        return ['should_move' => false];
    }

    /**
     * Get current PD for investment
     */
    protected function getCurrentPd(InvestmentMaster $investment): float
    {
        $latestTrade = InvestmentTrade::where('investment_id', $investment->id)
            ->latest('trade_date')
            ->first();

        return $latestTrade->pd ?? 0;
    }

    /**
     * Check credit rating downgrade
     */
    protected function checkCreditRatingDowngrade(
        InvestmentMaster $investment,
        array $forwardLookingInfo
    ): array {
        if (!isset($forwardLookingInfo['credit_indicators']['rating_change'])) {
            return ['should_move' => false];
        }

        $ratingChange = $forwardLookingInfo['credit_indicators']['rating_change'];
        $threshold = 2; // 2-notch downgrade

        if ($ratingChange <= -$threshold) {
            return [
                'should_move' => true,
                'target_stage' => 2,
                'reason' => "Credit rating downgraded by {$ratingChange} notches",
            ];
        }

        return ['should_move' => false];
    }

    /**
     * Check payment delays
     */
    protected function checkPaymentDelays(InvestmentMaster $investment, array $rules, array $forwardLookingInfo = []): array
    {
        $threshold = $rules['payment_delay_threshold'] ?? 30;

        // This would check actual payment history
        // For now, simplified check
        if (isset($forwardLookingInfo['credit_indicators']['payment_delays'])) {
            $delays = $forwardLookingInfo['credit_indicators']['payment_delays'];
            if ($delays >= $threshold) {
                return [
                    'should_move' => true,
                    'target_stage' => 2,
                    'reason' => "Payment delays of {$delays} days exceed threshold ({$threshold} days)",
                ];
            }
        }

        return ['should_move' => false];
    }

    /**
     * Check external indicators (bankruptcy, restructuring, etc.)
     */
    protected function checkExternalIndicators(
        InvestmentMaster $investment,
        array $forwardLookingInfo
    ): array {
        if (isset($forwardLookingInfo['credit_indicators']['external_indicators'])) {
            $indicators = $forwardLookingInfo['credit_indicators']['external_indicators'];

            if (in_array('BANKRUPTCY', $indicators) || in_array('DEFAULT', $indicators)) {
                return [
                    'should_move' => true,
                    'target_stage' => 3,
                    'reason' => 'External indicator: ' . implode(', ', $indicators),
                ];
            } elseif (in_array('RESTRUCTURING', $indicators) || in_array('FINANCIAL_DIFFICULTY', $indicators)) {
                return [
                    'should_move' => true,
                    'target_stage' => 2,
                    'reason' => 'External indicator: ' . implode(', ', $indicators),
                ];
            }
        }

        return ['should_move' => false];
    }

    /**
     * Check if investment is credit-impaired (Stage 3)
     */
    protected function isCreditImpaired(InvestmentMaster $investment, array $rules, array $forwardLookingInfo = []): bool
    {
        // Check if investment has defaulted
        $daysPastDue = $this->calculateDaysPastDue($investment);
        $thresholdStage3 = $rules['dpd_stage3_threshold'] ?? 90;

        if ($daysPastDue >= $thresholdStage3) {
            return true;
        }

        // Check external indicators
        if (isset($forwardLookingInfo['credit_indicators']['external_indicators'])) {
            $indicators = $forwardLookingInfo['credit_indicators']['external_indicators'];
            if (in_array('BANKRUPTCY', $indicators) || in_array('DEFAULT', $indicators)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create ECL input snapshot for an investment
     */
    public function createEclInput(
        InvestmentMaster $investment,
        Carbon $snapshotDate,
        int $stage,
        float $pd,
        float $lgd,
        float $ead,
        array $additionalData = []
    ): EclInput {
        return EclInput::create([
            'company_id' => $investment->company_id,
            'branch_id' => $investment->branch_id,
            'investment_id' => $investment->id,
            'snapshot_date' => $snapshotDate,
            'snapshot_type' => 'ECL_CALCULATION',
            'exposure_amount' => $ead,
            'carrying_amount' => $investment->carrying_amount ?? $investment->nominal_amount,
            'days_past_due' => $this->calculateDaysPastDue($investment),
            'stage' => $stage,
            'pd' => $pd,
            'lgd' => $lgd,
            'credit_rating' => $additionalData['credit_rating'] ?? null,
            'credit_grade' => $additionalData['credit_grade'] ?? null,
            'additional_data' => $additionalData,
            'created_by' => auth()->id(),
        ]);
    }
}

