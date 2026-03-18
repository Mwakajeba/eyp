<?php

namespace App\Services\Assets\Hfs;

use App\Models\Assets\HfsRequest;
use App\Models\Assets\HfsAsset;
use App\Models\Assets\HfsValuation;
use App\Models\Assets\HfsDisposal;
use App\Models\Assets\Asset;
use Illuminate\Support\Facades\DB;

/**
 * Service for handling tax-related calculations and tracking for HFS assets
 * - Tax base tracking (unchanged on reclassification)
 * - Deferred tax computation on impairment/disposal
 * - VAT handling on sale
 */
class HfsTaxService
{
    /**
     * Get tax base for an asset (unchanged on HFS reclassification)
     * Tax base = original cost - tax depreciation
     */
    public function getTaxBase(Asset $asset): float
    {
        // Tax base is the original cost minus tax depreciation
        // This should come from the tax book if dual depreciation is enabled
        $taxBase = $asset->purchase_cost ?? 0;
        
        // If tax depreciation is tracked separately, subtract it
        // For now, we'll use the accounting book accumulated depreciation as a proxy
        // In a full implementation, this would query the tax book depreciation
        $taxDepreciation = 0;
        
        // TODO: Query tax book depreciation if dual depreciation is enabled
        // For now, return purchase cost as tax base (simplified)
        
        return max(0, $taxBase - $taxDepreciation);
    }

    /**
     * Calculate deferred tax for HFS impairment
     * Temporary difference = Carrying amount (after impairment) - Tax base
     * Deferred tax = Temporary difference × Tax rate
     */
    public function calculateDeferredTaxOnImpairment(HfsValuation $valuation): array
    {
        $hfsRequest = $valuation->hfsRequest;
        $taxRate = $this->getTaxRate();
        
        $results = [];
        
        foreach ($hfsRequest->hfsAssets as $hfsAsset) {
            $asset = $hfsAsset->asset;
            if (!$asset) continue;
            
            $taxBase = $this->getTaxBase($asset);
            $carryingAmount = $hfsAsset->carrying_amount_at_reclass - $valuation->impairment_amount;
            
            // Temporary difference (accounting carrying amount - tax base)
            $temporaryDifference = $carryingAmount - $taxBase;
            
            // Deferred tax (temporary difference × tax rate)
            $deferredTax = $temporaryDifference * ($taxRate / 100);
            
            $results[] = [
                'asset_id' => $asset->id,
                'tax_base' => $taxBase,
                'carrying_amount' => $carryingAmount,
                'temporary_difference' => $temporaryDifference,
                'deferred_tax' => $deferredTax,
                'tax_rate' => $taxRate,
            ];
        }
        
        return $results;
    }

    /**
     * Calculate deferred tax for HFS disposal
     */
    public function calculateDeferredTaxOnDisposal(HfsDisposal $disposal): array
    {
        $hfsRequest = $disposal->hfsRequest;
        $taxRate = $this->getTaxRate();
        
        $results = [];
        
        foreach ($hfsRequest->hfsAssets as $hfsAsset) {
            $asset = $hfsAsset->asset;
            if (!$asset) continue;
            
            $taxBase = $this->getTaxBase($asset);
            $carryingAmount = $disposal->carrying_amount_at_disposal;
            $saleProceeds = $disposal->sale_proceeds;
            
            // Accounting gain/loss
            $accountingGainLoss = $saleProceeds - $carryingAmount;
            
            // Tax gain/loss (proceeds - tax base)
            $taxGainLoss = $saleProceeds - $taxBase;
            
            // Temporary difference reversal
            $temporaryDifference = $carryingAmount - $taxBase;
            
            // Deferred tax movement
            $deferredTaxMovement = $temporaryDifference * ($taxRate / 100);
            
            $results[] = [
                'asset_id' => $asset->id,
                'tax_base' => $taxBase,
                'carrying_amount' => $carryingAmount,
                'sale_proceeds' => $saleProceeds,
                'accounting_gain_loss' => $accountingGainLoss,
                'tax_gain_loss' => $taxGainLoss,
                'temporary_difference' => $temporaryDifference,
                'deferred_tax_movement' => $deferredTaxMovement,
                'tax_rate' => $taxRate,
            ];
        }
        
        return $results;
    }

    /**
     * Get corporate tax rate from system settings
     */
    protected function getTaxRate(): float
    {
        return (float) (\App\Models\SystemSetting::where('key', 'asset_tax_rate_percent')->value('value') ?? 30);
    }

    /**
     * Create deferred tax journal entry for impairment
     * This should be called after impairment journal is created
     */
    public function createDeferredTaxJournalForImpairment(HfsValuation $valuation): ?\App\Models\Journal
    {
        $deferredTaxEnabled = (bool) (\App\Models\SystemSetting::where('key', 'asset_deferred_tax_enabled')->value('value') ?? true);
        $autoJournal = (bool) (\App\Models\SystemSetting::where('key', 'asset_deferred_tax_auto_journal')->value('value') ?? true);
        
        if (!$deferredTaxEnabled || !$autoJournal) {
            return null;
        }
        
        $deferredTaxData = $this->calculateDeferredTaxOnImpairment($valuation);
        $totalDeferredTax = array_sum(array_column($deferredTaxData, 'deferred_tax'));
        
        if (abs($totalDeferredTax) < 0.01) {
            return null; // No deferred tax to post
        }
        
        // Get deferred tax accounts
        $deferredTaxAssetAccountId = (int) (\App\Models\SystemSetting::where('key', 'asset_deferred_tax_asset')->value('value') ?? 0);
        $deferredTaxLiabilityAccountId = (int) (\App\Models\SystemSetting::where('key', 'asset_deferred_tax_liability')->value('value') ?? 0);
        
        if (!$deferredTaxAssetAccountId && !$deferredTaxLiabilityAccountId) {
            return null; // Accounts not configured
        }
        
        $hfsRequest = $valuation->hfsRequest;
        $branchId = $hfsRequest->branch_id ?? session('branch_id');
        $user = auth()->user();
        
        // Create journal
        $journal = \App\Models\Journal::create([
            'branch_id' => $branchId,
            'date' => $valuation->valuation_date,
            'reference' => $hfsRequest->request_no . '-DT-IMP',
            'reference_type' => 'HFS Deferred Tax',
            'description' => "Deferred Tax on HFS Impairment - {$hfsRequest->request_no}",
            'user_id' => $user->id ?? null,
        ]);
        
        if ($totalDeferredTax > 0) {
            // Dr. Deferred Tax Asset (if asset account exists)
            if ($deferredTaxAssetAccountId) {
                \App\Models\JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $deferredTaxAssetAccountId,
                    'amount' => abs($totalDeferredTax),
                    'nature' => 'debit',
                    'description' => "Deferred Tax Asset - HFS Impairment"
                ]);
            }
        } else {
            // Cr. Deferred Tax Liability (if liability account exists)
            if ($deferredTaxLiabilityAccountId) {
                \App\Models\JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $deferredTaxLiabilityAccountId,
                    'amount' => abs($totalDeferredTax),
                    'nature' => 'credit',
                    'description' => "Deferred Tax Liability - HFS Impairment"
                ]);
            }
        }
        
        // Create GL transactions
        $this->createGlTransactions($journal, $branchId, $user);
        
        return $journal;
    }

    /**
     * Create deferred tax journal entry for disposal
     */
    public function createDeferredTaxJournalForDisposal(HfsDisposal $disposal): ?\App\Models\Journal
    {
        $deferredTaxEnabled = (bool) (\App\Models\SystemSetting::where('key', 'asset_deferred_tax_enabled')->value('value') ?? true);
        $autoJournal = (bool) (\App\Models\SystemSetting::where('key', 'asset_deferred_tax_auto_journal')->value('value') ?? true);
        
        if (!$deferredTaxEnabled || !$autoJournal) {
            return null;
        }
        
        $deferredTaxData = $this->calculateDeferredTaxOnDisposal($disposal);
        $totalDeferredTaxMovement = array_sum(array_column($deferredTaxData, 'deferred_tax_movement'));
        
        if (abs($totalDeferredTaxMovement) < 0.01) {
            return null; // No deferred tax to post
        }
        
        // Get deferred tax accounts
        $deferredTaxAssetAccountId = (int) (\App\Models\SystemSetting::where('key', 'asset_deferred_tax_asset')->value('value') ?? 0);
        $deferredTaxLiabilityAccountId = (int) (\App\Models\SystemSetting::where('key', 'asset_deferred_tax_liability')->value('value') ?? 0);
        
        if (!$deferredTaxAssetAccountId && !$deferredTaxLiabilityAccountId) {
            return null; // Accounts not configured
        }
        
        $hfsRequest = $disposal->hfsRequest;
        $branchId = $hfsRequest->branch_id ?? session('branch_id');
        $user = auth()->user();
        
        // Create journal
        $journal = \App\Models\Journal::create([
            'branch_id' => $branchId,
            'date' => $disposal->disposal_date,
            'reference' => $hfsRequest->request_no . '-DT-SALE',
            'reference_type' => 'HFS Deferred Tax',
            'description' => "Deferred Tax on HFS Disposal - {$hfsRequest->request_no}",
            'user_id' => $user->id ?? null,
        ]);
        
        if ($totalDeferredTaxMovement > 0) {
            // Cr. Deferred Tax Asset (reversal)
            if ($deferredTaxAssetAccountId) {
                \App\Models\JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $deferredTaxAssetAccountId,
                    'amount' => abs($totalDeferredTaxMovement),
                    'nature' => 'credit',
                    'description' => "Deferred Tax Asset Reversal - HFS Disposal"
                ]);
            }
        } else {
            // Dr. Deferred Tax Liability (reversal)
            if ($deferredTaxLiabilityAccountId) {
                \App\Models\JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $deferredTaxLiabilityAccountId,
                    'amount' => abs($totalDeferredTaxMovement),
                    'nature' => 'debit',
                    'description' => "Deferred Tax Liability Reversal - HFS Disposal"
                ]);
            }
        }
        
        // Create GL transactions
        $this->createGlTransactions($journal, $branchId, $user);
        
        return $journal;
    }

    /**
     * Create GL transactions from journal items
     */
    protected function createGlTransactions(\App\Models\Journal $journal, $branchId, $user): void
    {
        foreach ($journal->items as $item) {
            \App\Models\GlTransaction::create([
                'chart_account_id' => $item->chart_account_id,
                'amount' => $item->amount,
                'nature' => $item->nature,
                'transaction_id' => $journal->id,
                'transaction_type' => 'journal',
                'date' => $journal->date,
                'description' => $item->description,
                'branch_id' => $branchId,
                'user_id' => $user->id ?? null,
            ]);
        }
    }
}

