<?php

namespace App\Services\Investment;

use App\Models\Investment\InvestmentMaster;
use App\Models\Investment\InvestmentTrade;
use App\Models\Investment\EclCalc;
use App\Models\Investment\EclInput;
use App\Models\Investment\EclScenario;
use App\Models\Investment\EclModelParam;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EclCalculationService
{
    protected $stagingService;

    public function __construct(EclStagingService $stagingService)
    {
        $this->stagingService = $stagingService;
    }

    /**
     * Calculate ECL for an investment using forward-looking information
     * Creates EclCalc and EclInput records
     * 
     * @param InvestmentMaster $investment
     * @param Carbon $calculationDate
     * @param array $forwardLookingInfo Optional: ['macro_economic_factors' => [], 'credit_indicators' => []]
     * @param string $calculationRunId Optional: Run ID for batch calculations
     * @return EclCalc
     */
    public function calculateEcl(
        InvestmentMaster $investment,
        Carbon $calculationDate = null,
        array $forwardLookingInfo = [],
        string $calculationRunId = null
    ): EclCalc {
        DB::beginTransaction();
        try {
            $calculationDate = $calculationDate ?? Carbon::today();
            $calculationRunId = $calculationRunId ?? 'ECL-' . $calculationDate->format('Ymd') . '-' . uniqid();

            // Step 1: Determine stage using staging service
            $stagingResult = $this->stagingService->determineStage($investment, $forwardLookingInfo);
            $stage = $stagingResult['stage'];
            $stageReason = $stagingResult['reason'];
            $stageAssignedDate = $stagingResult['stage_assigned_date'];

            // Step 2: Get model parameters
            $modelParams = $this->getModelParams($investment, $stage);

            // Step 3: Get base PD, LGD, EAD
            $basePd = $this->getBasePd($investment, $modelParams, $stage);
            $lgd = $this->getLgd($investment, $modelParams, $stage);
            $ead = $this->getEad($investment);

            // Step 4: Get active scenarios
            $scenarios = $this->getActiveScenarios($investment->company_id, $calculationDate);

            // Step 5: Calculate ECL for each scenario
            $scenarioResults = [];
            $pdAdjustments = [];

            foreach ($scenarios as $scenario) {
                $adjustedPd = $this->adjustPdForScenario($basePd, $stage, $scenario, $forwardLookingInfo);
                $pdAdjustments[$scenario->scenario_name] = $adjustedPd - $basePd;

                // Calculate ECL: PD * LGD * EAD
                $ecl12Month = ($adjustedPd / 100) * ($lgd / 100) * $ead;
                $eclLifetime = $stage >= 2 ? $ecl12Month : 0; // Lifetime ECL for Stage 2 and 3

                $scenarioResults[$scenario->scenario_name] = [
                    'pd' => $adjustedPd,
                    'lgd' => $lgd,
                    'ead' => $ead,
                    'ecl_12_month' => $ecl12Month,
                    'ecl_lifetime' => $eclLifetime,
                ];
            }

            // Step 6: Calculate weighted average ECL
            $weightedEcl = $this->calculateWeightedEcl($scenarioResults, $scenarios, $stage);

            // Step 7: Create ECL input snapshot
            $eclInput = $this->stagingService->createEclInput(
                $investment,
                $calculationDate,
                $stage,
                $basePd,
                $lgd,
                $ead,
                [
                    'credit_rating' => $forwardLookingInfo['credit_indicators']['rating'] ?? null,
                    'credit_grade' => $forwardLookingInfo['credit_indicators']['grade'] ?? null,
                ]
            );

            // Step 8: Create ECL calculation record
            $eclCalc = EclCalc::create([
                'company_id' => $investment->company_id,
                'branch_id' => $investment->branch_id,
                'investment_id' => $investment->id,
                'ecl_input_id' => $eclInput->id,
                'calculation_date' => $calculationDate,
                'calculation_run_id' => $calculationRunId,
                'calculation_type' => $stage >= 2 ? 'LIFETIME' : '12_MONTH',
                'stage' => $stage,
                'stage_assigned_date' => $stageAssignedDate,
                'stage_reason' => $stageReason,
                'pd' => $basePd,
                'lgd' => $lgd,
                'ead' => $ead,
                'ccf' => $modelParams['ccf'] ?? 100,
                'ecl_12_month' => $stage >= 2 ? 0 : $weightedEcl,
                'ecl_lifetime' => $stage >= 2 ? $weightedEcl : 0,
                'ecl_amount' => $weightedEcl,
                'scenario_ecl' => $scenarioResults,
                'weighted_ecl' => $weightedEcl,
                'forward_looking_adjustments' => $forwardLookingInfo,
                'pd_adjustment' => array_sum($pdAdjustments) / count($pdAdjustments),
                'forward_looking_applied' => !empty($forwardLookingInfo),
                'model_name' => $modelParams['model_name'] ?? 'DEFAULT',
                'model_version' => $modelParams['model_version'] ?? '1.0',
                'status' => 'CALCULATED',
                'created_by' => auth()->id(),
            ]);

            DB::commit();
            return $eclCalc;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to calculate ECL', [
                'investment_id' => $investment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get model parameters for investment
     */
    protected function getModelParams(InvestmentMaster $investment, int $stage): array
    {
        $modelParam = EclModelParam::where('company_id', $investment->company_id)
            ->where(function($q) use ($investment) {
                $q->where('instrument_type', $investment->instrument_type)
                  ->orWhere('instrument_type', 'ALL');
            })
            ->where(function($q) use ($stage) {
                $q->where('stage', $stage)
                  ->orWhereNull('stage');
            })
            ->where('is_active', true)
            ->effectiveAsOf(Carbon::today())
            ->first();

        if ($modelParam) {
            return [
                'model_name' => $modelParam->model_name,
                'model_version' => $modelParam->model_version,
                'base_pd' => $modelParam->base_pd,
                'base_lgd' => $modelParam->base_lgd,
                'ccf' => $modelParam->ccf,
                'staging_rules' => $modelParam->staging_rules ?? [],
            ];
        }

        // Default parameters
        return [
            'model_name' => 'DEFAULT',
            'model_version' => '1.0',
            'base_pd' => 0.5, // 0.5%
            'base_lgd' => 45, // 45%
            'ccf' => 100, // 100%
            'staging_rules' => [],
        ];
    }

    /**
     * Get base PD for investment
     */
    protected function getBasePd(InvestmentMaster $investment, array $modelParams, int $stage): float
    {
        // Check if investment has PD from trade
        $latestTrade = InvestmentTrade::where('investment_id', $investment->id)
            ->latest('trade_date')
            ->first();

        if ($latestTrade && $latestTrade->pd) {
            return $latestTrade->pd;
        }

        // Use model parameter base PD
        $basePd = $modelParams['base_pd'] ?? 0.5;

        // Adjust for stage
        if ($stage === 2) {
            $basePd *= 1.5; // Increase for Stage 2
        } elseif ($stage === 3) {
            $basePd *= 3.0; // Increase for Stage 3
        }

        return $basePd;
    }

    /**
     * Get LGD for investment
     */
    protected function getLgd(InvestmentMaster $investment, array $modelParams, int $stage): float
    {
        $latestTrade = InvestmentTrade::where('investment_id', $investment->id)
            ->latest('trade_date')
            ->first();

        if ($latestTrade && $latestTrade->lgd) {
            return $latestTrade->lgd;
        }

        return $modelParams['base_lgd'] ?? 45;
    }

    /**
     * Get EAD for investment
     */
    protected function getEad(InvestmentMaster $investment): float
    {
        $latestTrade = InvestmentTrade::where('investment_id', $investment->id)
            ->latest('trade_date')
            ->first();

        if ($latestTrade && $latestTrade->ead) {
            return $latestTrade->ead;
        }

        return $investment->carrying_amount ?? $investment->nominal_amount ?? 0;
    }

    /**
     * Get active scenarios for company
     */
    protected function getActiveScenarios(int $companyId, Carbon $date)
    {
        $scenarios = EclScenario::where('company_id', $companyId)
            ->where('is_active', true)
            ->asOfDate($date)
            ->get();

        if ($scenarios->isEmpty()) {
            // Return default scenario objects if none exist
            return $this->createDefaultScenarios($companyId, $date);
        }

        return $scenarios;
    }

    /**
     * Create default scenarios
     */
    protected function createDefaultScenarios(int $companyId, Carbon $date)
    {
        // Return default scenario-like objects for calculation
        // In production, these should be created via seeder or admin interface
        return collect([
            (object)[
                'scenario_name' => 'BASE',
                'scenario_type' => 'BASE',
                'weight' => 0.5,
                'pd_multiplier' => 1.0,
            ],
            (object)[
                'scenario_name' => 'OPTIMISTIC',
                'scenario_type' => 'OPTIMISTIC',
                'weight' => 0.2,
                'pd_multiplier' => 0.8,
            ],
            (object)[
                'scenario_name' => 'PESSIMISTIC',
                'scenario_type' => 'PESSIMISTIC',
                'weight' => 0.3,
                'pd_multiplier' => 1.2,
            ],
        ]);
    }

    /**
     * Adjust PD for scenario
     */
    protected function adjustPdForScenario(
        float $basePd,
        int $stage,
        $scenario,
        array $forwardLookingInfo
    ): float {
        $adjustedPd = $basePd;

        // Apply scenario PD multiplier
        if (is_object($scenario) && isset($scenario->pd_multiplier)) {
            $adjustedPd *= $scenario->pd_multiplier;
        } elseif (is_array($scenario) && isset($scenario['pd_multiplier'])) {
            $adjustedPd *= $scenario['pd_multiplier'];
        }

        // Apply forward-looking adjustments (from existing method)
        $adjustedPd = $this->adjustPdForForwardLooking($adjustedPd, $stage, $forwardLookingInfo, []);

        return max(0, min(100, $adjustedPd));
    }

    /**
     * Calculate weighted average ECL
     */
    protected function calculateWeightedEcl(array $scenarioResults, $scenarios, int $stage): float
    {
        $weightedEcl = 0;
        $totalWeight = 0;

        foreach ($scenarios as $scenario) {
            $scenarioName = is_object($scenario) ? $scenario->scenario_name : (is_array($scenario) ? $scenario['scenario_name'] : $scenario);
            $weight = is_object($scenario) ? $scenario->weight : (is_array($scenario) ? ($scenario['weight'] ?? 0.33) : 0.33);

            if (isset($scenarioResults[$scenarioName])) {
                $result = $scenarioResults[$scenarioName];
                $ecl = $stage >= 2 ? $result['ecl_lifetime'] : $result['ecl_12_month'];
                $weightedEcl += $ecl * $weight;
                $totalWeight += $weight;
            }
        }

        // Normalize if weights don't sum to 1
        if ($totalWeight > 0 && $totalWeight != 1) {
            $weightedEcl = $weightedEcl / $totalWeight;
        }

        return round($weightedEcl, 2);
    }

    /**
     * Adjust PD based on forward-looking information
     */
    protected function adjustPdForForwardLooking(
        float $basePd,
        int $stage,
        array $forwardLookingInfo,
        array $scenarios
    ): float {
        $adjustedPd = $basePd;

        // Apply macro-economic factors
        if (isset($forwardLookingInfo['macro_economic_factors'])) {
            $macroFactors = $forwardLookingInfo['macro_economic_factors'];
            
            // GDP growth impact
            if (isset($macroFactors['gdp_growth'])) {
                $gdpGrowth = $macroFactors['gdp_growth'];
                // Negative GDP growth increases PD
                if ($gdpGrowth < 0) {
                    $adjustedPd *= (1 + abs($gdpGrowth) / 100);
                } else {
                    $adjustedPd *= (1 - min($gdpGrowth / 200, 0.1)); // Cap reduction at 10%
                }
            }

            // Inflation impact
            if (isset($macroFactors['inflation_rate'])) {
                $inflation = $macroFactors['inflation_rate'];
                // High inflation increases PD
                if ($inflation > 5) {
                    $adjustedPd *= (1 + ($inflation - 5) / 100);
                }
            }

            // Interest rate impact
            if (isset($macroFactors['interest_rate'])) {
                $interestRate = $macroFactors['interest_rate'];
                // High interest rates increase PD
                if ($interestRate > 10) {
                    $adjustedPd *= (1 + ($interestRate - 10) / 200);
                }
            }
        }

        // Apply credit indicators
        if (isset($forwardLookingInfo['credit_indicators'])) {
            $creditIndicators = $forwardLookingInfo['credit_indicators'];
            
            // Credit rating changes
            if (isset($creditIndicators['rating_change'])) {
                $ratingChange = $creditIndicators['rating_change'];
                // Downgrade increases PD
                if ($ratingChange < 0) {
                    $adjustedPd *= (1 + abs($ratingChange) * 0.1);
                } else {
                    $adjustedPd *= (1 - min($ratingChange * 0.05, 0.15)); // Cap reduction at 15%
                }
            }

            // Payment delays
            if (isset($creditIndicators['payment_delays'])) {
                $paymentDelays = $creditIndicators['payment_delays'];
                if ($paymentDelays > 0) {
                    $adjustedPd *= (1 + $paymentDelays * 0.05); // 5% increase per delay
                }
            }
        }

        // Stage-based adjustments
        if ($stage === 2) {
            // Stage 2: Underperforming - increase PD by 50%
            $adjustedPd *= 1.5;
        } elseif ($stage === 3) {
            // Stage 3: Non-performing - increase PD by 200%
            $adjustedPd *= 3.0;
        }

        return max(0, min(100, $adjustedPd)); // Ensure PD is between 0 and 100%
    }

    /**
     * Recalculate ECL for all investments in a company
     */
    public function recalculateAllEcl(int $companyId, array $forwardLookingInfo = []): array
    {
        $investments = InvestmentMaster::where('company_id', $companyId)
            ->whereIn('status', ['ACTIVE'])
            ->whereIn('instrument_type', ['T_BOND', 'T_BILL', 'FIXED_DEPOSIT', 'CORP_BOND', 'COMMERCIAL_PAPER'])
            ->get();

        $results = [];
        $totalEcl = 0;

        foreach ($investments as $investment) {
            try {
                $eclResult = $this->calculateEcl($investment, [], $forwardLookingInfo);
                
                // Update the latest trade with new ECL
                $latestTrade = InvestmentTrade::where('investment_id', $investment->id)
                    ->where('trade_type', 'PURCHASE')
                    ->latest('trade_date')
                    ->first();

                if ($latestTrade) {
                    $oldEcl = $latestTrade->ecl_amount;
                    $oldPd = $latestTrade->pd;
                    
                    $latestTrade->ecl_amount = $eclResult['weighted_ecl'];
                    $latestTrade->pd = $eclResult['adjusted_pd'];
                    $latestTrade->save();

                    // Log the ECL calculation with detailed audit trail
                    $latestTrade->logActivity('ecl_recalculated', 
                        "ECL Recalculated for Investment {$investment->instrument_code}",
                        [
                            'previous_ecl' => number_format($oldEcl ?? 0, 2),
                            'new_ecl' => number_format($eclResult['weighted_ecl'], 2),
                            'previous_pd' => number_format($oldPd ?? 0, 6) . '%',
                            'new_pd' => number_format($eclResult['adjusted_pd'], 6) . '%',
                            'stage' => $eclResult['stage'],
                            'lgd' => number_format($eclResult['lgd'], 6) . '%',
                            'ead' => number_format($eclResult['ead'], 2),
                            'forward_looking_applied' => $eclResult['forward_looking_applied'] ? 'Yes' : 'No',
                            'calculation_date' => $eclResult['calculation_date'],
                        ]
                    );

                    Log::info('ECL recalculated for investment', [
                        'investment_id' => $investment->id,
                        'instrument_code' => $investment->instrument_code,
                        'ecl_amount' => $eclResult['weighted_ecl'],
                        'pd' => $eclResult['adjusted_pd'],
                        'stage' => $eclResult['stage'],
                    ]);
                }

                $results[] = $eclResult;
                $totalEcl += $eclResult['weighted_ecl'];
            } catch (\Exception $e) {
                Log::error('Failed to calculate ECL for investment', [
                    'investment_id' => $investment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'total_investments' => count($investments),
            'processed' => count($results),
            'total_ecl' => $totalEcl,
            'results' => $results,
            'calculation_date' => now()->toDateString(),
        ];
    }

    /**
     * Get ECL summary by stage
     */
    public function getEclSummaryByStage(int $companyId): array
    {
        $trades = InvestmentTrade::whereHas('investment', function($query) use ($companyId) {
            $query->where('company_id', $companyId)
                  ->whereIn('status', ['ACTIVE']);
        })
        ->where('trade_type', 'PURCHASE')
        ->whereNotNull('ecl_amount')
        ->get();

        $summary = [
            'stage_1' => ['count' => 0, 'total_ecl' => 0, 'total_ead' => 0],
            'stage_2' => ['count' => 0, 'total_ecl' => 0, 'total_ead' => 0],
            'stage_3' => ['count' => 0, 'total_ecl' => 0, 'total_ead' => 0],
        ];

        foreach ($trades as $trade) {
            $stage = $trade->stage ?? 1;
            $stageKey = "stage_{$stage}";
            
            if (isset($summary[$stageKey])) {
                $summary[$stageKey]['count']++;
                $summary[$stageKey]['total_ecl'] += $trade->ecl_amount ?? 0;
                $summary[$stageKey]['total_ead'] += $trade->ead ?? 0;
            }
        }

        return $summary;
    }
}

