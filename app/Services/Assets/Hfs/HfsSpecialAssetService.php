<?php

namespace App\Services\Assets\Hfs;

use App\Models\Assets\Asset;
use App\Models\Assets\HfsAsset;
use App\Models\Assets\HfsRequest;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling special asset types in HFS
 * - Investment Property at FV (IAS 40)
 * - Assets Under Construction (AUC)
 * - Disposal Groups (mixed asset types)
 */
class HfsSpecialAssetService
{
    /**
     * Check if asset is Investment Property measured at fair value
     * IAS 40: Investment Property continues fair value measurement even when HFS
     */
    public function isInvestmentPropertyAtFv(Asset $asset): bool
    {
        // Check if asset category is investment property
        $category = $asset->category;
        if (!$category) {
            return false;
        }

        // Check if category type is investment property
        // This would need to be added to asset_categories table if not exists
        // For now, we'll check by category name or a flag
        $categoryName = strtolower($category->name ?? '');
        if (strpos($categoryName, 'investment property') !== false || 
            strpos($categoryName, 'investment') !== false) {
            // Check if measured at fair value
            return $asset->valuation_model === 'revaluation' || 
                   $category->valuation_model === 'revaluation';
        }

        return false;
    }

    /**
     * Check if asset is under construction (AUC)
     */
    public function isAssetUnderConstruction(Asset $asset): bool
    {
        return $asset->status === 'under_construction';
    }

    /**
     * Handle Investment Property at FV when classified as HFS
     * - Continue fair value measurement (IAS 40)
     * - Prevent double-counting
     * - Don't stop depreciation if measured at FV
     */
    public function handleInvestmentPropertyHfs(HfsAsset $hfsAsset): void
    {
        $asset = $hfsAsset->asset;
        if (!$asset || !$this->isInvestmentPropertyAtFv($asset)) {
            return;
        }

        // Investment Property at FV should continue fair value measurement
        // Don't stop depreciation if measured at FV (IAS 40 requirement)
        // However, IFRS 5.15 says stop depreciation for HFS except investment property at FV
        // So we need to NOT stop depreciation for investment property at FV
        
        if ($hfsAsset->depreciation_stopped) {
            Log::warning("Investment Property at FV should not have depreciation stopped", [
                'hfs_asset_id' => $hfsAsset->id,
                'asset_id' => $asset->id,
            ]);
            
            // Resume depreciation for investment property at FV
            $hfsAsset->depreciation_stopped = false;
            $hfsAsset->save();
            
            if ($asset) {
                $asset->depreciation_stopped = false;
                $asset->save();
            }
        }
    }

    /**
     * Handle Assets Under Construction (AUC) when classified as HFS
     * - Capitalized costs remain until sale
     * - No depreciation (already not depreciating)
     */
    public function handleAucHfs(HfsAsset $hfsAsset): void
    {
        $asset = $hfsAsset->asset;
        if (!$asset || !$this->isAssetUnderConstruction($asset)) {
            return;
        }

        // AUC assets don't depreciate, so no need to stop depreciation
        // Just ensure carrying amount is captured correctly
        // The carrying amount should be the capitalized costs to date
        
        Log::info("AUC asset classified as HFS", [
            'hfs_asset_id' => $hfsAsset->id,
            'asset_id' => $asset->id,
            'carrying_amount' => $hfsAsset->carrying_amount_at_reclass,
        ]);
    }

    /**
     * Validate disposal group composition
     * Disposal groups can include mixed asset types: PPE + Inventory + Receivables
     */
    public function validateDisposalGroup(HfsRequest $hfsRequest): array
    {
        $errors = [];
        $warnings = [];
        
        if (!$hfsRequest->is_disposal_group) {
            return [
                'valid' => true,
                'errors' => [],
                'warnings' => [],
            ];
        }

        $assetTypes = $hfsRequest->hfsAssets->pluck('asset_type')->unique()->toArray();
        
        // Check if disposal group has mixed asset types
        if (count($assetTypes) > 1) {
            $warnings[] = "Disposal group contains mixed asset types: " . implode(', ', $assetTypes);
        }

        // Validate that all assets in disposal group are eligible
        foreach ($hfsRequest->hfsAssets as $hfsAsset) {
            if ($hfsAsset->asset_type === 'INVENTORY') {
                // Inventory in disposal group - ensure it's properly valued
                if ($hfsAsset->carrying_amount_at_reclass <= 0) {
                    $errors[] = "Inventory asset in disposal group must have carrying amount > 0";
                }
            } elseif ($hfsAsset->asset_type === 'PPE') {
                // PPE assets - check if they're available for sale
                $asset = $hfsAsset->asset;
                if ($asset && $asset->status !== 'active' && $asset->status !== 'under_construction') {
                    $warnings[] = "PPE asset {$asset->code} is not in active status";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Process special asset types when reclassifying to HFS
     */
    public function processSpecialAssetsOnReclass(HfsRequest $hfsRequest): void
    {
        foreach ($hfsRequest->hfsAssets as $hfsAsset) {
            // Handle Investment Property at FV
            if ($hfsAsset->asset_type === 'INVEST_PROP') {
                $this->handleInvestmentPropertyHfs($hfsAsset);
            }
            
            // Handle AUC
            if ($hfsAsset->asset && $this->isAssetUnderConstruction($hfsAsset->asset)) {
                $this->handleAucHfs($hfsAsset);
            }
        }
    }
}

