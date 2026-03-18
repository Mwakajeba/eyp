<?php

namespace App\Services\Assets\Hfs;

use App\Models\Assets\HfsRequest;
use App\Models\Assets\HfsValuation;
use App\Models\Assets\HfsAsset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HfsMeasurementService
{
    /**
     * Calculate fair value less costs to sell
     */
    public function calculateFvLessCosts(float $fairValue, float $costsToSell): float
    {
        return $fairValue - $costsToSell;
    }

    /**
     * Calculate impairment amount
     * Impairment = Carrying Amount - FV Less Costs (if FV Less Costs < Carrying)
     */
    public function calculateImpairment(float $carryingAmount, float $fvLessCosts): float
    {
        if ($fvLessCosts < $carryingAmount) {
            return $carryingAmount - $fvLessCosts;
        }
        return 0;
    }

    /**
     * Calculate reversal amount (limited by original carrying before HFS impairment)
     */
    public function calculateReversal(
        float $currentCarryingAmount,
        float $fvLessCosts,
        float $originalCarryingBeforeImpairment
    ): float {
        // Reversal can only bring carrying amount back to original (before HFS impairment)
        $maxCarryingAmount = $originalCarryingBeforeImpairment;
        
        if ($fvLessCosts > $currentCarryingAmount && $fvLessCosts <= $maxCarryingAmount) {
            // Reversal is the increase, but limited
            return min($fvLessCosts - $currentCarryingAmount, $maxCarryingAmount - $currentCarryingAmount);
        }
        
        return 0;
    }

    /**
     * Validate measurement rules
     * - FV less costs must be ≤ carrying amount (impairment if not)
     * - Reversals limited to original carrying before HFS impairment
     * - Subsequent valuations must have justification
     */
    public function validateMeasurementRules(
        HfsRequest $hfsRequest,
        float $fairValue,
        float $costsToSell,
        float $currentCarryingAmount,
        bool $isSubsequentValuation = false,
        string $justification = null
    ): array {
        $errors = [];
        $warnings = [];

        // Calculate FV less costs
        $fvLessCosts = $this->calculateFvLessCosts($fairValue, $costsToSell);

        // Rule 1: FV less costs should not exceed carrying amount (would indicate reversal)
        // This is allowed but must be validated against original carrying
        $originalCarryingBeforeImpairment = $hfsRequest->hfsAssets->sum('carrying_amount_at_reclass');
        
        if ($fvLessCosts > $originalCarryingBeforeImpairment) {
            $errors[] = "Fair value less costs to sell ({$fvLessCosts}) cannot exceed original carrying amount before HFS impairment ({$originalCarryingBeforeImpairment}).";
        }

        // Rule 2: Reversals limited to original carrying before HFS impairment
        if ($fvLessCosts > $currentCarryingAmount) {
            // This is a reversal - check if it's within limits
            $maxReversal = $originalCarryingBeforeImpairment - $currentCarryingAmount;
            $proposedReversal = $fvLessCosts - $currentCarryingAmount;
            
            if ($proposedReversal > $maxReversal) {
                $errors[] = "Reversal amount ({$proposedReversal}) exceeds maximum allowed reversal ({$maxReversal}). Reversal is limited to original carrying amount before HFS impairment.";
            }
        }

        // Rule 3: Subsequent valuations must have justification
        if ($isSubsequentValuation && empty($justification)) {
            $errors[] = "Justification is required for subsequent valuations.";
        }

        // Warning if FV less costs is significantly different from current carrying
        $difference = abs($fvLessCosts - $currentCarryingAmount);
        $percentageDifference = $currentCarryingAmount > 0 ? ($difference / $currentCarryingAmount) * 100 : 0;
        
        if ($percentageDifference > 20 && empty($justification)) {
            $warnings[] = "Significant difference ({$percentageDifference}%) between fair value less costs and current carrying amount. Please provide justification.";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Measure HFS asset and determine if impairment is needed
     * 
     * @param HfsRequest $hfsRequest
     * @param array $valuationData ['fair_value', 'costs_to_sell', 'valuation_date', ...]
     * @return array ['valuation' => HfsValuation, 'impairment_amount' => float, 'is_reversal' => bool]
     */
    public function measureHfs(HfsRequest $hfsRequest, array $valuationData): array
    {
        DB::beginTransaction();
        try {
            // Get current carrying amount (sum of all assets in the request)
            $currentCarryingAmount = $hfsRequest->current_total_carrying_amount;
            
            // Calculate FV less costs
            $fairValue = $valuationData['fair_value'];
            $costsToSell = $valuationData['costs_to_sell'] ?? 0;
            $fvLessCosts = $this->calculateFvLessCosts($fairValue, $costsToSell);

            // Get original carrying amount before any HFS impairments
            $originalCarryingBeforeImpairment = $hfsRequest->hfsAssets->sum('carrying_amount_at_reclass');

            // Check if this is a reversal (FV less costs increased)
            $latestValuation = $hfsRequest->latestValuation;
            $isReversal = false;
            $impairmentAmount = 0;

            if ($latestValuation && $latestValuation->fv_less_costs < $fvLessCosts) {
                // This is a potential reversal
                $isReversal = true;
                $impairmentAmount = $this->calculateReversal(
                    $currentCarryingAmount,
                    $fvLessCosts,
                    $originalCarryingBeforeImpairment
                );
            } else {
                // This is a new impairment or update
                $impairmentAmount = $this->calculateImpairment($currentCarryingAmount, $fvLessCosts);
            }

            // Create valuation record
            $valuation = HfsValuation::create([
                'hfs_id' => $hfsRequest->id,
                'valuation_date' => $valuationData['valuation_date'] ?? now(),
                'fair_value' => $fairValue,
                'costs_to_sell' => $costsToSell,
                'fv_less_costs' => $fvLessCosts,
                'carrying_amount' => $currentCarryingAmount,
                'impairment_amount' => $impairmentAmount,
                'is_reversal' => $isReversal,
                'original_carrying_before_impairment' => $originalCarryingBeforeImpairment,
                'valuator_name' => $valuationData['valuator_name'] ?? null,
                'valuator_license' => $valuationData['valuator_license'] ?? null,
                'valuator_company' => $valuationData['valuator_company'] ?? null,
                'report_ref' => $valuationData['report_ref'] ?? null,
                'valuation_report_path' => $valuationData['valuation_report_path'] ?? null,
                'is_override' => $valuationData['is_override'] ?? false,
                'override_reason' => $valuationData['override_reason'] ?? null,
                'override_approved_by' => $valuationData['override_approved_by'] ?? null,
                'override_approved_at' => $valuationData['override_approved_at'] ?? null,
                'notes' => $valuationData['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Update current carrying amounts for all assets
            if ($impairmentAmount > 0) {
                $this->updateAssetCarryingAmounts($hfsRequest, $fvLessCosts, $isReversal);
            }

            DB::commit();

            return [
                'valuation' => $valuation,
                'impairment_amount' => $impairmentAmount,
                'is_reversal' => $isReversal,
                'fv_less_costs' => $fvLessCosts,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('HFS Measurement error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update carrying amounts for all assets in the HFS request
     */
    protected function updateAssetCarryingAmounts(
        HfsRequest $hfsRequest,
        float $newFvLessCosts,
        bool $isReversal
    ): void {
        $totalOriginalCarrying = $hfsRequest->hfsAssets->sum('carrying_amount_at_reclass');
        
        if ($totalOriginalCarrying <= 0) {
            return;
        }

        // Distribute the new carrying amount proportionally
        foreach ($hfsRequest->hfsAssets as $hfsAsset) {
            $proportion = $hfsAsset->carrying_amount_at_reclass / $totalOriginalCarrying;
            $newCarryingAmount = $newFvLessCosts * $proportion;
            
            $hfsAsset->current_carrying_amount = $newCarryingAmount;
            $hfsAsset->save();
        }
    }

    /**
     * Get current total carrying amount for HFS request
     */
    public function getCurrentCarryingAmount(HfsRequest $hfsRequest): float
    {
        return $hfsRequest->hfsAssets->sum('current_carrying_amount');
    }

    /**
     * Get original carrying amount before any impairments
     */
    public function getOriginalCarryingAmount(HfsRequest $hfsRequest): float
    {
        return $hfsRequest->hfsAssets->sum('carrying_amount_at_reclass');
    }
}

