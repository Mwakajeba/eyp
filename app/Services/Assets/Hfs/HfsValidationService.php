<?php

namespace App\Services\Assets\Hfs;

use App\Models\Assets\Asset;
use App\Models\Assets\HfsRequest;
use Illuminate\Support\Collection;

class HfsValidationService
{
    /**
     * Validate IFRS 5 criteria for HFS classification
     * 
     * @param HfsRequest $hfsRequest
     * @return array ['valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public function validateIfrs5Criteria(HfsRequest $hfsRequest): array
    {
        $errors = [];
        $warnings = [];

        // 1. Asset available for immediate sale in present condition
        if (!$this->isAvailableForImmediateSale($hfsRequest)) {
            $errors[] = 'Asset(s) must be available for immediate sale in present condition.';
        }

        // 2. Management commitment verification
        if (!$this->hasManagementCommitment($hfsRequest)) {
            $errors[] = 'Management commitment evidence is required (attach management minutes or approval).';
        }

        // 3. Active program to locate buyer
        if (!$this->hasActiveProgramToLocateBuyer($hfsRequest)) {
            $errors[] = 'Active program to locate buyer must be documented (marketing actions required).';
        }

        // 4. Reasonable price verification
        if (!$this->hasReasonablePrice($hfsRequest)) {
            $warnings[] = 'Sale price range should be reasonable and market-based.';
        }

        // 5. 12-month timeline validation
        $timelineValidation = $this->validateTimeline($hfsRequest);
        if (!$timelineValidation['valid']) {
            if ($timelineValidation['requires_approval']) {
                $errors[] = 'Sale expected beyond 12 months requires board approval and justification.';
            } else {
                $warnings[] = $timelineValidation['message'];
            }
        }

        // 6. Sale is highly probable
        if (!$this->isSaleHighlyProbable($hfsRequest)) {
            $errors[] = 'Sale must be highly probable (probability should be > 75%).';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Check if asset is available for immediate sale
     */
    protected function isAvailableForImmediateSale(HfsRequest $hfsRequest): bool
    {
        // Check all assets in the request
        foreach ($hfsRequest->hfsAssets as $hfsAsset) {
            if ($hfsAsset->asset_id) {
                $asset = $hfsAsset->asset;
                if (!$asset) {
                    return false;
                }

                // Asset must be active
                if ($asset->status !== 'active') {
                    return false;
                }

                // Asset must not be under construction
                if ($asset->status === 'under_construction') {
                    return false;
                }

                // Asset must not be under major repair that prevents sale
                if ($asset->status === 'under_repair') {
                    // Could be allowed if repair doesn't prevent sale
                    // This is a judgment call - for now, we'll allow with warning
                }
            }
        }

        return true;
    }

    /**
     * Check if management commitment exists
     */
    protected function hasManagementCommitment(HfsRequest $hfsRequest): bool
    {
        // Must have management_committed flag and commitment date
        if (!$hfsRequest->management_committed || !$hfsRequest->management_commitment_date) {
            return false;
        }

        // Must have attachment (management minutes or approval document)
        $attachments = $hfsRequest->attachments ?? [];
        
        // Handle both array format (from file uploads) and other formats
        if (empty($attachments)) {
            return false;
        }

        // If attachments is an array with at least one item, it's valid
        if (is_array($attachments) && count($attachments) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Check if there's an active program to locate buyer
     */
    protected function hasActiveProgramToLocateBuyer(HfsRequest $hfsRequest): bool
    {
        // Must have marketing actions documented
        if (empty($hfsRequest->marketing_actions)) {
            return false;
        }

        // Should have buyer identified OR active marketing
        if (empty($hfsRequest->buyer_name) && empty($hfsRequest->marketing_actions)) {
            return false;
        }

        return true;
    }

    /**
     * Check if price is reasonable
     */
    protected function hasReasonablePrice(HfsRequest $hfsRequest): bool
    {
        // Must have expected fair value or sale price range
        if (!$hfsRequest->expected_fair_value && empty($hfsRequest->sale_price_range)) {
            return false;
        }

        // Price should be positive
        if ($hfsRequest->expected_fair_value && $hfsRequest->expected_fair_value <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Validate timeline (12-month rule)
     */
    protected function validateTimeline(HfsRequest $hfsRequest): array
    {
        if (!$hfsRequest->intended_sale_date) {
            return [
                'valid' => false,
                'requires_approval' => false,
                'message' => 'Intended sale date is required.',
            ];
        }

        $intendedDate = \Carbon\Carbon::parse($hfsRequest->intended_sale_date);
        $monthsUntilSale = now()->diffInMonths($intendedDate, false);

        // Normal case: sale within 12 months
        if ($monthsUntilSale <= 12) {
            return [
                'valid' => true,
                'requires_approval' => false,
                'message' => 'Sale expected within 12 months.',
            ];
        }

        // Exception: sale beyond 12 months
        if ($hfsRequest->exceeds_12_months) {
            // Must have extension justification and approval
            if (empty($hfsRequest->extension_justification)) {
                return [
                    'valid' => false,
                    'requires_approval' => true,
                    'message' => 'Extension justification is required for sales beyond 12 months.',
                ];
            }

            if (!$hfsRequest->extension_approved_by || !$hfsRequest->extension_approved_at) {
                return [
                    'valid' => false,
                    'requires_approval' => true,
                    'message' => 'Board approval is required for sales beyond 12 months.',
                ];
            }

            return [
                'valid' => true,
                'requires_approval' => false,
                'message' => 'Sale beyond 12 months with board approval.',
            ];
        }

        // Sale beyond 12 months but not flagged
        return [
            'valid' => false,
            'requires_approval' => true,
            'message' => 'Sale expected beyond 12 months requires board approval.',
        ];
    }

    /**
     * Check if sale is highly probable
     */
    protected function isSaleHighlyProbable(HfsRequest $hfsRequest): bool
    {
        // Probability should be > 75% for "highly probable"
        if ($hfsRequest->probability_pct && $hfsRequest->probability_pct >= 75) {
            return true;
        }

        // If probability not set, check other indicators
        if ($hfsRequest->buyer_name && $hfsRequest->management_committed) {
            return true;
        }

        return false;
    }

    /**
     * Validate asset eligibility for HFS classification
     * 
     * @param Asset|Collection $assets
     * @return array ['valid' => bool, 'errors' => array, 'ineligible_assets' => array]
     */
    public function validateAssetEligibility($assets): array
    {
        $errors = [];
        $ineligibleAssets = [];

        if (!($assets instanceof Collection)) {
            $assets = collect([$assets]);
        }

        foreach ($assets as $asset) {
            $assetErrors = [];

            // Check if asset is already disposed
            if ($asset->status === 'disposed') {
                $assetErrors[] = 'Asset is already disposed.';
            }

            // Check if asset is already classified as HFS
            if (in_array($asset->hfs_status, ['pending', 'classified'])) {
                $assetErrors[] = 'Asset is already classified as Held for Sale.';
            }

            // Check if asset has active HFS request
            if ($asset->current_hfs_id) {
                $existingHfs = $asset->currentHfsRequest;
                if ($existingHfs && in_array($existingHfs->status, ['approved', 'in_review'])) {
                    $assetErrors[] = 'Asset has an active Held for Sale request.';
                }
            }

            // Check if asset is pledged (requires bank consent)
            // This would need to be checked from hfs_assets table if asset is already in a request
            // For now, we'll check during the request creation

            if (!empty($assetErrors)) {
                $ineligibleAssets[] = [
                    'asset_id' => $asset->id,
                    'asset_code' => $asset->code,
                    'asset_name' => $asset->name,
                    'errors' => $assetErrors,
                ];
                $errors = array_merge($errors, $assetErrors);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'ineligible_assets' => $ineligibleAssets,
        ];
    }

    /**
     * Check if asset is pledged and requires bank consent
     */
    public function requiresBankConsent(Asset $asset): bool
    {
        // Check if asset is in an HFS request and marked as pledged
        $hfsAsset = $asset->hfsAssets()
            ->whereHas('hfsRequest', function($query) {
                $query->whereIn('status', ['approved', 'in_review', 'draft']);
            })
            ->where('is_pledged', true)
            ->first();

        if ($hfsAsset && !$hfsAsset->bank_consent_obtained) {
            return true;
        }

        // Check if asset has bank_consent_attachment field (from assets table)
        // This is a general check for any pledged assets
        if ($asset->is_pledged ?? false) {
            // Check if bank consent attachment exists
            $bankConsentAttachment = $asset->bank_consent_attachment ?? null;
            if (empty($bankConsentAttachment)) {
                return true; // Asset is pledged but no consent document
            }
        }

        return false;
    }

    /**
     * Validate bank consent for pledged assets
     */
    public function validateBankConsent(HfsRequest $hfsRequest): array
    {
        $errors = [];
        $warnings = [];

        foreach ($hfsRequest->hfsAssets as $hfsAsset) {
            if ($hfsAsset->is_pledged) {
                if (!$hfsAsset->bank_consent_obtained) {
                    $assetCode = $hfsAsset->asset ? $hfsAsset->asset->code : 'N/A';
                    $errors[] = "Asset {$assetCode} is pledged but bank consent has not been obtained.";
                } else {
                    // Validate consent details
                    if (empty($hfsAsset->bank_consent_date)) {
                        $warnings[] = "Bank consent obtained for asset but consent date is missing.";
                    }
                    if (empty($hfsAsset->bank_consent_ref)) {
                        $warnings[] = "Bank consent obtained for asset but consent reference is missing.";
                    }
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
     * Validate all requirements before approval
     */
    public function validateForApproval(HfsRequest $hfsRequest): array
    {
        $errors = [];
        $warnings = [];

        // IFRS 5 criteria validation
        $criteriaValidation = $this->validateIfrs5Criteria($hfsRequest);
        if (!$criteriaValidation['valid']) {
            $errors = array_merge($errors, $criteriaValidation['errors']);
        }
        $warnings = array_merge($warnings, $criteriaValidation['warnings']);

        // Asset eligibility
        $assets = $hfsRequest->hfsAssets->map(function($hfsAsset) {
            return $hfsAsset->asset;
        })->filter();

        if ($assets->isNotEmpty()) {
            $eligibilityValidation = $this->validateAssetEligibility($assets);
            if (!$eligibilityValidation['valid']) {
                $errors = array_merge($errors, $eligibilityValidation['errors']);
            }
        }

        // Check for pledged assets requiring bank consent
        $bankConsentValidation = $this->validateBankConsent($hfsRequest);
        if (!$bankConsentValidation['valid']) {
            $errors = array_merge($errors, $bankConsentValidation['errors']);
        }
        $warnings = array_merge($warnings, $bankConsentValidation['warnings']);

        // Pre-approval validations
        $preApprovalValidation = $this->validatePreApprovalRequirements($hfsRequest);
        if (!$preApprovalValidation['valid']) {
            $errors = array_merge($errors, $preApprovalValidation['errors']);
        }
        $warnings = array_merge($warnings, $preApprovalValidation['warnings']);

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate pre-approval requirements
     * - Management commitment evidence (attachment)
     * - Buyer identified or active marketing program
     * - Realistic timetable (≤12 months normally)
     * - Sale price range provided
     * - All required fields completed
     */
    public function validatePreApprovalRequirements(HfsRequest $hfsRequest): array
    {
        $errors = [];
        $warnings = [];

        // 1. Management commitment evidence required (attachment)
        if (!$this->hasManagementCommitment($hfsRequest)) {
            $errors[] = 'Management commitment evidence is required. Please attach management minutes or approval document.';
        }

        // 2. Buyer identified or active marketing program
        if (empty($hfsRequest->buyer_name) && empty($hfsRequest->marketing_actions)) {
            $errors[] = 'Either buyer must be identified or active marketing program must be documented.';
        }

        // 3. Realistic timetable (≤12 months normally)
        $timelineValidation = $this->validateTimeline($hfsRequest);
        if (!$timelineValidation['valid']) {
            if ($timelineValidation['requires_approval']) {
                $errors[] = $timelineValidation['message'];
            } else {
                $warnings[] = $timelineValidation['message'];
            }
        }

        // 4. Sale price range provided
        if (!$hfsRequest->expected_fair_value && empty($hfsRequest->sale_price_range)) {
            $errors[] = 'Expected fair value or sale price range must be provided.';
        }

        // 5. All required fields completed
        if (empty($hfsRequest->intended_sale_date)) {
            $errors[] = 'Intended sale date is required.';
        }

        if (empty($hfsRequest->expected_close_date)) {
            $warnings[] = 'Expected close date is recommended for better tracking.';
        }

        if (empty($hfsRequest->justification)) {
            $errors[] = 'Justification for HFS classification is required.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}

