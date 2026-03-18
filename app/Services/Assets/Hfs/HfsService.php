<?php

namespace App\Services\Assets\Hfs;

use App\Models\Assets\Asset;
use App\Models\Assets\HfsRequest;
use App\Models\Assets\HfsAsset;
use App\Models\Assets\HfsValuation;
use App\Models\Assets\HfsDisposal;
use App\Models\Assets\HfsDiscontinuedFlag;
use App\Models\Assets\HfsAuditLog;
use App\Models\Assets\AssetDepreciation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HfsService
{
    protected $validationService;
    protected $journalService;
    protected $measurementService;
    protected $taxService;
    protected $specialAssetService;
    protected $multiCurrencyService;
    protected $partialSaleService;

    public function __construct(
        HfsValidationService $validationService,
        HfsJournalService $journalService,
        HfsMeasurementService $measurementService,
        HfsTaxService $taxService = null,
        HfsSpecialAssetService $specialAssetService = null,
        HfsMultiCurrencyService $multiCurrencyService = null,
        HfsPartialSaleService $partialSaleService = null
    ) {
        $this->validationService = $validationService;
        $this->journalService = $journalService;
        $this->measurementService = $measurementService;
        $this->taxService = $taxService ?? app(HfsTaxService::class);
        $this->specialAssetService = $specialAssetService ?? app(HfsSpecialAssetService::class);
        $this->multiCurrencyService = $multiCurrencyService ?? app(HfsMultiCurrencyService::class);
        $this->partialSaleService = $partialSaleService ?? app(HfsPartialSaleService::class);
    }

    /**
     * Create new HFS request with validation
     * 
     * @param array $data
     * @return HfsRequest
     */
    public function createHfsRequest(array $data): HfsRequest
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $companyId = $user->company_id;
            $branchId = $data['branch_id'] ?? $user->branch_id ?? session('branch_id');

            // Generate request number
            $requestNo = $this->generateRequestNumber($companyId);

            // If customer_id is provided, populate buyer fields from customer
            $buyerName = $data['buyer_name'] ?? null;
            $buyerContact = $data['buyer_contact'] ?? null;
            $buyerAddress = $data['buyer_address'] ?? null;
            
            if (!empty($data['customer_id'])) {
                $customer = \App\Models\Customer::find($data['customer_id']);
                if ($customer) {
                    $buyerName = $buyerName ?? $customer->name;
                    $buyerContact = $buyerContact ?? $customer->phone;
                    $buyerAddress = $buyerAddress ?? $customer->company_address;
                }
            }

            // Create HFS request
            $hfsRequest = HfsRequest::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'request_no' => $requestNo,
                'initiator_id' => $user->id,
                'status' => 'draft',
                'intended_sale_date' => $data['intended_sale_date'],
                'expected_close_date' => $data['expected_close_date'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'buyer_name' => $buyerName,
                'buyer_contact' => $buyerContact,
                'buyer_address' => $buyerAddress,
                'justification' => $data['justification'] ?? null,
                'expected_costs_to_sell' => $data['expected_costs_to_sell'] ?? 0,
                'expected_fair_value' => $data['expected_fair_value'] ?? 0,
                'probability_pct' => $data['probability_pct'] ?? null,
                'marketing_actions' => $data['marketing_actions'] ?? null,
                'sale_price_range' => $data['sale_price_range'] ?? null,
                'management_committed' => $data['management_committed'] ?? false,
                'management_commitment_date' => $data['management_commitment_date'] ?? null,
                'exceeds_12_months' => $data['exceeds_12_months'] ?? false,
                'extension_justification' => $data['extension_justification'] ?? null,
                'is_disposal_group' => $data['is_disposal_group'] ?? false,
                'disposal_group_description' => $data['disposal_group_description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'attachments' => $data['attachments'] ?? null,
                'created_by' => $user->id,
            ]);

            // Add assets to the request
            if (isset($data['asset_ids']) && is_array($data['asset_ids'])) {
                $this->addAssetsToRequest($hfsRequest, $data['asset_ids'], $data['asset_data'] ?? []);
            }

            // Log creation
            $this->logActivity($hfsRequest, 'created', 'HFS request created', []);

            DB::commit();

            return $hfsRequest;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('HFS Request creation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Add assets to HFS request
     */
    protected function addAssetsToRequest(HfsRequest $hfsRequest, array $assetIds, array $assetData = []): void
    {
        foreach ($assetIds as $assetId) {
            $asset = Asset::with('category')->findOrFail($assetId);

            // Validate asset eligibility
            $eligibility = $this->validationService->validateAssetEligibility($asset);
            if (!$eligibility['valid']) {
                throw new \Exception("Asset {$asset->code} is not eligible: " . implode(', ', $eligibility['errors']));
            }

            // Get original account ID - required field
            $originalAccountId = null;
            if ($asset->category) {
                $originalAccountId = $asset->category->asset_account_id;
            }
            
            // If still null, try to get from asset directly (if it has an account field)
            if (!$originalAccountId && isset($asset->asset_account_id)) {
                $originalAccountId = $asset->asset_account_id;
            }
            
            // If still null, throw an error
            if (!$originalAccountId) {
                throw new \Exception("Asset {$asset->code} does not have an asset account configured. Please configure the asset account in the asset category settings.");
            }

            // Calculate carrying amount at reclassification
            $nbv = AssetDepreciation::getCurrentBookValue($asset->id, now(), $asset->company_id);
            $accumDepr = AssetDepreciation::getAccumulatedDepreciation($asset->id, now(), $asset->company_id);
            $accumImpairment = $asset->accumulated_impairment ?? 0;
            $assetCost = $asset->purchase_cost;

            if ($nbv === null) {
                $nbv = $assetCost - $accumDepr - $accumImpairment;
            }

            // Get asset-specific data if provided
            $assetSpecificData = $assetData[$assetId] ?? [];

            // Create HFS asset record
            HfsAsset::create([
                'hfs_id' => $hfsRequest->id,
                'asset_id' => $asset->id,
                'asset_type' => 'PPE', // Default, can be extended for other types
                'original_account_id' => $originalAccountId,
                'carrying_amount_at_reclass' => max(0, $nbv),
                'accumulated_depreciation_at_reclass' => $accumDepr,
                'accumulated_impairment_at_reclass' => $accumImpairment,
                'asset_cost_at_reclass' => $assetCost,
                'current_carrying_amount' => max(0, $nbv),
                'depreciation_stopped' => false, // Will be set to true on approval
                'reclassified_date' => null, // Will be set on approval
                'is_pledged' => $assetSpecificData['is_pledged'] ?? false,
                'pledge_details' => $assetSpecificData['pledge_details'] ?? null,
                'bank_consent_obtained' => $assetSpecificData['bank_consent_obtained'] ?? false,
                'bank_consent_date' => $assetSpecificData['bank_consent_date'] ?? null,
                'bank_consent_ref' => $assetSpecificData['bank_consent_ref'] ?? null,
                'status' => 'pending_reclass',
                'created_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Approve HFS request and trigger reclassification
     */
    public function approveHfsRequest(HfsRequest $hfsRequest, string $approvalLevel, int $approverId, ?string $comments = null): array
    {
        DB::beginTransaction();
        try {
            // Validate before approval
            $validation = $this->validationService->validateForApproval($hfsRequest);
            if (!$validation['valid']) {
                throw new \Exception("Validation failed: " . implode(', ', $validation['errors']));
            }

            // Check if all required approvals are complete
            // Accept 'final' as well (used by ApprovalService when all levels are complete)
            // Also check if status is already 'approved' (set by ApprovalService)
            $shouldReclassify = in_array($approvalLevel, ['finance_manager', 'cfo', 'board', 'final']) 
                || $hfsRequest->status === 'approved';
            
            if ($shouldReclassify) {
                // Only reclassify if not already done (check if assets are already classified)
                $hfsAssets = \App\Models\Assets\HfsAsset::where('hfs_id', $hfsRequest->id)->get();
                $needsReclassification = $hfsAssets->isEmpty() || $hfsAssets->where('status', 'pending_reclass')->isNotEmpty();
                
                if ($needsReclassification) {
                    // Validate HFS account configuration before attempting reclassification
                    $this->validateHfsAccountConfiguration($hfsRequest);
                    
                    // Reclassify assets to HFS
                    $this->reclassifyToHfs($hfsRequest);
                }

                // Update request status if not already set
                if ($hfsRequest->status !== 'approved') {
                    $hfsRequest->status = 'approved';
                    $hfsRequest->approved_at = now();
                    $hfsRequest->save();
                }

                // Log approval
                $this->logActivity($hfsRequest, 'approved', "HFS request approved at level {$approvalLevel}", [
                    'approver_id' => $approverId,
                    'comments' => $comments,
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'HFS request approved and assets reclassified',
                'hfs_request' => $hfsRequest,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('HFS Approval error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reclassify assets to HFS, stop depreciation, post journals
     */
    public function reclassifyToHfs(HfsRequest $hfsRequest): void
    {
        DB::beginTransaction();
        try {
            // Create reclassification journal
            $journal = $this->journalService->createReclassificationJournal($hfsRequest);

            // Update each asset
            foreach ($hfsRequest->hfsAssets as $hfsAsset) {
                if ($hfsAsset->asset_id) {
                    $asset = $hfsAsset->asset;

                    // Check if Investment Property at FV - don't stop depreciation
                    $isInvestmentProperty = $this->specialAssetService->isInvestmentPropertyAtFv($asset);
                    
                    if (!$isInvestmentProperty) {
                        // Stop depreciation (except Investment Property at FV per IAS 40)
                        $asset->stopDepreciation('Classified as Held for Sale');
                        $hfsAsset->depreciation_stopped = true;
                    } else {
                        // Investment Property at FV continues depreciation
                        $hfsAsset->depreciation_stopped = false;
                    }

                    // Update asset status
                    $asset->hfs_status = 'classified';
                    $asset->current_hfs_id = $hfsRequest->id;
                    $asset->save();

                    // Update HFS asset record
                    $hfsAsset->reclassified_date = now();
                    $hfsAsset->status = 'classified';
                    $hfsAsset->save();
                }
            }

            // Process special asset types after reclassification
            $this->specialAssetService->processSpecialAssetsOnReclass($hfsRequest);

            // Log reclassification
            $this->logActivity($hfsRequest, 'reclassified', 'Assets reclassified to Held for Sale', [
                'journal_id' => $journal->id,
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('HFS Reclassification error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Measure HFS and post impairment if needed
     */
    public function measureHfs(HfsRequest $hfsRequest, array $valuationData): array
    {
        DB::beginTransaction();
        try {
            // Measure HFS
            $measurementResult = $this->measurementService->measureHfs($hfsRequest, $valuationData);
            $valuation = $measurementResult['valuation'];
            $impairmentAmount = $measurementResult['impairment_amount'];
            $isReversal = $measurementResult['is_reversal'];

            // Post impairment or reversal journal if needed
            if ($impairmentAmount > 0) {
                if ($isReversal) {
                    $journal = $this->journalService->createReversalJournal($valuation);
                } else {
                    $journal = $this->journalService->createImpairmentJournal($valuation);
                    
                    // Create deferred tax journal if enabled
                    if ($this->taxService) {
                        $this->taxService->createDeferredTaxJournalForImpairment($valuation);
                    }
                }

                // Log measurement
                $this->logActivity($hfsRequest, 'measured', "HFS measured - " . ($isReversal ? 'Reversal' : 'Impairment') . " of {$impairmentAmount}", [
                    'valuation_id' => $valuation->id,
                    'journal_id' => $journal->id,
                ]);
            } else {
                // Log measurement without impairment
                $this->logActivity($hfsRequest, 'measured', 'HFS measured - No impairment required', [
                    'valuation_id' => $valuation->id,
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'valuation' => $valuation,
                'impairment_amount' => $impairmentAmount,
                'is_reversal' => $isReversal,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('HFS Measurement error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process sale/disposal of HFS asset
     */
    public function processSale(HfsRequest $hfsRequest, array $saleData): array
    {
        DB::beginTransaction();
        try {
            // Get current carrying amount
            $carryingAmountAtDisposal = $this->measurementService->getCurrentCarryingAmount($hfsRequest);
            $accumulatedImpairment = $hfsRequest->hfsAssets->sum('accumulated_impairment_at_reclass');

            // Calculate gain/loss (accounting for VAT and WHT)
            $saleProceeds = $saleData['sale_proceeds'] ?? 0;
            $costsSold = $saleData['costs_sold'] ?? 0;
            $vatAmount = $saleData['vat_amount'] ?? 0;
            $withholdingTax = $saleData['withholding_tax'] ?? 0;
            $vatType = $saleData['vat_type'] ?? 'exclusive';
            
            // Net proceeds = sale proceeds - VAT (if exclusive) - WHT
            $netProceeds = $saleProceeds;
            if ($vatType === 'exclusive' && $vatAmount > 0) {
                $netProceeds = $saleProceeds - $vatAmount;
            }
            $netProceeds = $netProceeds - $withholdingTax;
            
            // Gain/Loss = Net Proceeds - Carrying Amount - Costs
            $gainLoss = $netProceeds - $carryingAmountAtDisposal - $costsSold;

            // Get functional currency for default
            $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', $hfsRequest->company->functional_currency ?? 'TZS');
            
            // Create disposal record
            $disposal = HfsDisposal::create([
                'hfs_id' => $hfsRequest->id,
                'disposal_date' => $saleData['disposal_date'] ?? now(),
                'sale_proceeds' => $saleProceeds,
                'sale_currency' => $saleData['sale_currency'] ?? $functionalCurrency,
                'currency_rate' => $saleData['currency_rate'] ?? 1,
                'costs_sold' => $costsSold,
                'carrying_amount_at_disposal' => $carryingAmountAtDisposal,
                'accumulated_impairment_at_disposal' => $accumulatedImpairment,
                'gain_loss_amount' => $gainLoss,
                'buyer_name' => $saleData['buyer_name'] ?? null,
                'buyer_contact' => $saleData['buyer_contact'] ?? null,
                'buyer_address' => $saleData['buyer_address'] ?? null,
                'invoice_number' => $saleData['invoice_number'] ?? null,
                'receipt_number' => $saleData['receipt_number'] ?? null,
                'settlement_reference' => $saleData['settlement_reference'] ?? null,
                'bank_account_id' => $saleData['bank_account_id'] ?? null,
                'vat_type' => $saleData['vat_type'] ?? 'no_vat',
                'vat_rate' => $saleData['vat_rate'] ?? 0,
                'vat_amount' => $vatAmount,
                'withholding_tax_enabled' => $saleData['withholding_tax_enabled'] ?? false,
                'withholding_tax_rate' => $saleData['withholding_tax_rate'] ?? 0,
                'withholding_tax_type' => $saleData['withholding_tax_type'] ?? 'percentage',
                'withholding_tax' => $withholdingTax,
                'is_partial_sale' => $saleData['is_partial_sale'] ?? false,
                'partial_sale_percentage' => $saleData['partial_sale_percentage'] ?? null,
                'notes' => $saleData['notes'] ?? null,
                'attachments' => $saleData['attachments'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Handle partial sale if applicable
            if (($saleData['is_partial_sale'] ?? false) && $hfsRequest->is_disposal_group) {
                return $this->partialSaleService->processPartialSale($hfsRequest, $saleData);
            }

            // Create disposal journal
            $journal = $this->journalService->createDisposalJournal($disposal);
            
            // Create deferred tax journal if enabled
            if ($this->taxService) {
                $this->taxService->createDeferredTaxJournalForDisposal($disposal);
            }

            // Handle multi-currency FX gain/loss
            if ($this->multiCurrencyService && $disposal->sale_currency) {
                $fxData = $this->multiCurrencyService->calculateFxGainLoss($disposal);
                if (abs($fxData['fx_gain_loss']) > 0.01) {
                    $fxJournal = $this->multiCurrencyService->postFxGainLoss($disposal, $fxData['fx_gain_loss']);
                    if ($fxJournal) {
                        Log::info("FX gain/loss posted for HFS disposal", [
                            'disposal_id' => $disposal->id,
                            'fx_gain_loss' => $fxData['fx_gain_loss'],
                            'fx_journal_id' => $fxJournal->id,
                        ]);
                    }
                }
            }

            // Update asset status
            foreach ($hfsRequest->hfsAssets as $hfsAsset) {
                if ($hfsAsset->asset_id) {
                    $asset = $hfsAsset->asset;
                    $asset->status = 'disposed';
                    $asset->hfs_status = 'sold';
                    $asset->save();

                    $hfsAsset->status = 'sold';
                    $hfsAsset->save();
                }
            }

            // Update HFS request status
            $hfsRequest->status = 'sold';
            $hfsRequest->save();

            // Log disposal
            $this->logActivity($hfsRequest, 'sold', "HFS asset sold - Gain/Loss: {$gainLoss}", [
                'disposal_id' => $disposal->id,
                'journal_id' => $journal->id,
            ]);

            DB::commit();

            return [
                'success' => true,
                'disposal' => $disposal,
                'journal_id' => $journal->id,
                'gain_loss' => $gainLoss,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('HFS Sale processing error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cancel HFS and reclassify back to original category
     * Handles:
     * - Reclassification back to original category
     * - Resume depreciation (except Investment Property at FV)
     * - Reverse journals appropriately
     * - Handle special asset types
     */
    public function cancelHfs(HfsRequest $hfsRequest, ?string $reason = null): array
    {
        DB::beginTransaction();
        try {
            // Create cancellation journal
            $journal = $this->journalService->createCancellationJournal($hfsRequest);

            // Resume depreciation and update assets
            foreach ($hfsRequest->hfsAssets as $hfsAsset) {
                if ($hfsAsset->asset_id) {
                    $asset = $hfsAsset->asset;

                    // Check if Investment Property at FV - don't resume depreciation if it wasn't stopped
                    $isInvestmentProperty = $this->specialAssetService->isInvestmentPropertyAtFv($asset);
                    
                    if (!$isInvestmentProperty) {
                        // Resume depreciation for non-investment property assets
                        $asset->depreciation_stopped = false;
                        $asset->depreciation_stopped_date = null;
                        $asset->depreciation_stopped_reason = null;
                    }

                    // Update asset status
                    $asset->hfs_status = 'none';
                    $asset->current_hfs_id = null;
                    $asset->save();

                    // Update HFS asset record
                    $hfsAsset->status = 'cancelled';
                    $hfsAsset->depreciation_stopped = false;
                    $hfsAsset->save();
                }
            }

            // Reverse any impairment journals if applicable
            // Get all valuations with impairment journals
            $valuations = $hfsRequest->hfsValuations()
                ->whereNotNull('impairment_journal_id')
                ->where('gl_posted', true)
                ->get();

            foreach ($valuations as $valuation) {
                // Note: In practice, you might want to reverse impairment journals
                // For now, we'll just log that they exist
                Log::info("HFS cancellation - impairment journal exists", [
                    'valuation_id' => $valuation->id,
                    'journal_id' => $valuation->impairment_journal_id,
                    'note' => 'Consider reversing impairment journal if appropriate',
                ]);
            }

            // Update HFS request status
            $hfsRequest->status = 'cancelled';
            $hfsRequest->save();

            // Log cancellation
            $this->logActivity($hfsRequest, 'cancelled', "HFS cancelled - {$reason}", [
                'journal_id' => $journal->id,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'HFS cancelled and assets reclassified',
                'journal_id' => $journal->id,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('HFS Cancellation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if disposal group meets discontinued operations criteria
     * IFRS 5.32 criteria:
     * 1. Component of entity (disposal group)
     * 2. Represents separate major line of business or geographical area
     * 3. Part of single coordinated plan
     * 4. Disposed of or classified as HFS
     */
    public function checkDiscontinuedCriteria(HfsRequest $hfsRequest): array
    {
        $criteria = [];
        $errors = [];
        $warnings = [];

        // 1. Check if disposal group represents a component of entity
        $criteria['is_component'] = $hfsRequest->is_disposal_group ?? false;
        if (!$criteria['is_component']) {
            $warnings[] = 'This is not marked as a disposal group. Discontinued operations typically apply to disposal groups.';
        }

        // 2. Verify disposal or HFS classification
        $criteria['is_disposed_or_classified_hfs'] = in_array($hfsRequest->status, ['APPROVED', 'SOLD']);
        if (!$criteria['is_disposed_or_classified_hfs']) {
            $errors[] = 'Disposal group must be classified as HFS or disposed to qualify as discontinued operation.';
        }

        // 3. Check if represents separate major line of business or geographical area
        $criteria['represents_separate_major_line'] = !empty($hfsRequest->disposal_group_description);
        if (!$criteria['represents_separate_major_line']) {
            $warnings[] = 'Disposal group description should indicate if it represents a separate major line of business or geographical area.';
        }

        // Additional check: multiple assets suggest a disposal group
        $assetCount = $hfsRequest->hfsAssets->count();
        $criteria['has_multiple_assets'] = $assetCount > 1;
        if ($assetCount === 1) {
            $warnings[] = 'Single asset disposal groups may not qualify as discontinued operations unless they represent a major component.';
        }

        // 4. Part of single coordinated plan (assumed true if disposal group)
        $criteria['is_part_of_single_plan'] = $criteria['is_component'];

        // Overall assessment
        $meetsCriteria = $criteria['is_component'] 
            && $criteria['is_disposed_or_classified_hfs']
            && ($criteria['represents_separate_major_line'] || $criteria['has_multiple_assets']);

        return [
            'meets_criteria' => $meetsCriteria,
            'criteria' => $criteria,
            'errors' => $errors,
            'warnings' => $warnings,
            'recommendation' => $meetsCriteria 
                ? 'Meets discontinued operations criteria. Can be tagged as discontinued operation.'
                : 'Does not fully meet discontinued operations criteria. Review required before tagging.',
        ];
    }

    /**
     * Tag disposal group as discontinued operation
     */
    public function tagAsDiscontinued(HfsRequest $hfsRequest, array $effectsOnPnl = []): HfsDiscontinuedFlag
    {
        // Check criteria first
        $criteriaCheck = $this->checkDiscontinuedCriteria($hfsRequest);
        if (!$criteriaCheck['meets_criteria']) {
            throw new \Exception("Disposal group does not meet discontinued operations criteria.");
        }

        // Create or update discontinued flag
        $discontinuedFlag = HfsDiscontinuedFlag::updateOrCreate(
            ['hfs_id' => $hfsRequest->id],
            [
                'is_discontinued' => true,
                'discontinued_date' => now(),
                'criteria_checked' => $criteriaCheck['criteria'],
                'component_name' => $hfsRequest->disposal_group_description,
                'component_description' => $hfsRequest->disposal_group_description,
                'effects_on_pnl' => $effectsOnPnl,
                'created_by' => auth()->id(),
            ]
        );

        // Log tagging
        $this->logActivity($hfsRequest, 'tagged_discontinued', 'Tagged as discontinued operation');

        return $discontinuedFlag;
    }

    /**
     * Generate unique request number
     */
    protected function generateRequestNumber(int $companyId): string
    {
        $prefix = 'HFS';
        $year = now()->format('Y');
        
        // Get last request number for this company and year
        $lastRequest = HfsRequest::where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastRequest && preg_match('/' . $prefix . '-' . $year . '-(\d+)/', $lastRequest->request_no, $matches)) {
            $sequence = (int) $matches[1] + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $sequence);
    }

    /**
     * Log activity to audit log
     */
    public function logActivity(
        HfsRequest $hfsRequest,
        string $action,
        string $description,
        array $metadata = []
    ): void {
        HfsAuditLog::create([
            'hfs_id' => $hfsRequest->id,
            'action' => $action,
            'action_type' => $this->getActionType($action),
            'user_id' => auth()->id(),
            'action_date' => now(),
            'description' => $description,
            'new_values' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get action type from action
     */
    protected function getActionType(string $action): string
    {
        $actionTypes = [
            'created' => 'request',
            'updated' => 'request',
            'approved' => 'approval',
            'rejected' => 'approval',
            'reclassified' => 'reclassification',
            'measured' => 'valuation',
            'sold' => 'disposal',
            'cancelled' => 'cancellation',
            'tagged_discontinued' => 'discontinued',
        ];

        return $actionTypes[$action] ?? 'general';
    }

    /**
     * Validate that HFS account is configured for all asset categories
     */
    protected function validateHfsAccountConfiguration(HfsRequest $hfsRequest): void
    {
        $missingAccounts = [];
        
        foreach ($hfsRequest->hfsAssets as $hfsAsset) {
            if ($hfsAsset->asset && $hfsAsset->asset->category) {
                $category = $hfsAsset->asset->category;
                $hfsAccountId = $category->hfs_account_id;
                
                // Check category first, then system default
                if (!$hfsAccountId) {
                    $hfsAccountId = \App\Models\SystemSetting::where('key', 'asset_default_hfs_account')->value('value');
                }
                
                if (!$hfsAccountId) {
                    $missingAccounts[] = $category->name . ' (Code: ' . $category->code . ')';
                }
            }
        }
        
        if (!empty($missingAccounts)) {
            $message = "HFS account not configured for the following asset categories: " . implode(', ', $missingAccounts);
            $message .= ". Please configure the HFS account in Asset Management → Categories → Edit Category → Default Accounts → Held for Sale (HFS) Account, or set a default in Asset Management → Settings → Default Accounts.";
            throw new \Exception($message);
        }
    }
}

