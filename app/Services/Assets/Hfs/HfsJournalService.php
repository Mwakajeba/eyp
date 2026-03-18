<?php

namespace App\Services\Assets\Hfs;

use App\Models\Assets\HfsRequest;
use App\Models\Assets\HfsAsset;
use App\Models\Assets\HfsValuation;
use App\Models\Assets\HfsDisposal;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HfsJournalService
{
    /**
     * Create reclassification journal when HFS is approved
     * Dr. Asset Held for Sale (HFS control account) - NBV
     * Cr. Original Asset Account (PPE/Inventory) - NBV
     */
    public function createReclassificationJournal(HfsRequest $hfsRequest): Journal
    {
        // Check if period is locked
        $companyId = $hfsRequest->company_id ?? ($hfsRequest->branch->company_id ?? null);
        if ($companyId) {
            $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
            try {
                $periodLockService->validateTransactionDate(now(), $companyId, 'HFS reclassification');
            } catch (\Exception $e) {
                \Log::warning('HfsReclassification - Cannot post: Period is locked', [
                    'hfs_request_id' => $hfsRequest->id,
                    'request_no' => $hfsRequest->request_no,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        $branchId = $hfsRequest->branch_id ?? session('branch_id');
        $user = auth()->user();

        // Create journal
        $journal = Journal::create([
            'branch_id' => $branchId,
            'date' => now(),
            'reference' => $hfsRequest->request_no,
            'reference_type' => 'HFS Reclassification',
            'description' => "Reclassification to Held for Sale - {$hfsRequest->request_no}",
            'user_id' => $user->id ?? null,
        ]);

        $totalDebit = 0;
        $totalCredit = 0;

        // Process each asset in the HFS request
        foreach ($hfsRequest->hfsAssets as $hfsAsset) {
            $carryingAmount = $hfsAsset->carrying_amount_at_reclass;
            $hfsAccountId = $this->getHfsAccountId($hfsAsset);
            $originalAccountId = $hfsAsset->original_account_id;

            if (!$hfsAccountId) {
                throw new \Exception("HFS account not configured for asset category.");
            }

            if (!$originalAccountId) {
                throw new \Exception("Original asset account not found for asset.");
            }

            // Get asset code for description
            $assetCode = $hfsAsset->asset->code ?? 'N/A';

            // Dr. Asset Held for Sale
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $hfsAccountId,
                'amount' => $carryingAmount,
                'nature' => 'debit',
                'description' => "HFS Reclassification - {$assetCode}"
            ]);

            // Cr. Original Asset Account
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $originalAccountId,
                'amount' => $carryingAmount,
                'nature' => 'credit',
                'description' => "HFS Reclassification - {$assetCode}"
            ]);

            $totalDebit += $carryingAmount;
            $totalCredit += $carryingAmount;
        }

        // Verify journal balances
        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new \Exception("Journal entries are not balanced. Debit: {$totalDebit}, Credit: {$totalCredit}");
        }

        // Create GL transactions (link to first asset if single asset, otherwise null)
        $firstAssetId = $hfsRequest->hfsAssets->first()?->asset_id ?? null;
        $this->createGlTransactions($journal, $branchId, $user, $firstAssetId);

        return $journal;
    }

    /**
     * Create impairment journal when FV less costs < carrying amount
     * Dr. Impairment Loss (P&L account)
     * Cr. Asset Held for Sale (valuation reduction)
     */
    public function createImpairmentJournal(HfsValuation $valuation): Journal
    {
        // Check if period is locked
        $companyId = $valuation->hfsRequest->company_id ?? ($valuation->hfsRequest->branch->company_id ?? null);
        if ($companyId) {
            $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
            try {
                $periodLockService->validateTransactionDate($valuation->valuation_date ?? now(), $companyId, 'HFS impairment');
            } catch (\Exception $e) {
                \Log::warning('HfsImpairment - Cannot post: Period is locked', [
                    'valuation_id' => $valuation->id,
                    'valuation_date' => $valuation->valuation_date,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        $hfsRequest = $valuation->hfsRequest;
        $branchId = $hfsRequest->branch_id ?? session('branch_id');
        $user = auth()->user();

        if ($valuation->impairment_amount <= 0) {
            throw new \Exception("No impairment to record. Impairment amount must be positive.");
        }

        // Get impairment loss account from category or system settings
        $impairmentAccountId = $this->getImpairmentLossAccountId($hfsRequest);
        $hfsAccountId = $this->getHfsAccountIdForRequest($hfsRequest);

        if (!$impairmentAccountId) {
            throw new \Exception("Impairment Loss Account not configured.");
        }

        if (!$hfsAccountId) {
            throw new \Exception("HFS account not configured.");
        }

        // Create journal
        $journal = Journal::create([
            'branch_id' => $branchId,
            'date' => $valuation->valuation_date,
            'reference' => $hfsRequest->request_no . '-IMP',
            'reference_type' => 'HFS Impairment',
            'description' => "HFS Impairment - {$hfsRequest->request_no} - {$valuation->valuation_date->format('Y-m-d')}",
            'user_id' => $user->id ?? null,
        ]);

        // Dr. Impairment Loss
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $impairmentAccountId,
            'amount' => $valuation->impairment_amount,
            'nature' => 'debit',
            'description' => "HFS Impairment Loss"
        ]);

        // Cr. Asset Held for Sale
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $hfsAccountId,
            'amount' => $valuation->impairment_amount,
            'nature' => 'credit',
            'description' => "HFS Impairment - Valuation Reduction"
        ]);

        // Update valuation with journal reference
        $valuation->impairment_journal_id = $journal->id;
        $valuation->gl_posted = true;
        $valuation->gl_posted_at = now();
        $valuation->save();

        // Create GL transactions (link to first asset)
        $firstAssetId = $hfsRequest->hfsAssets->first()?->asset_id ?? null;
        $this->createGlTransactions($journal, $branchId, $user, $firstAssetId);

        return $journal;
    }

    /**
     * Create reversal journal when FV less costs increases (limited by original carrying)
     * Dr. Asset Held for Sale (increase)
     * Cr. Impairment Reversal (P&L account)
     */
    public function createReversalJournal(HfsValuation $valuation): Journal
    {
        $hfsRequest = $valuation->hfsRequest;
        $branchId = $hfsRequest->branch_id ?? session('branch_id');
        $user = auth()->user();

        if (!$valuation->is_reversal || $valuation->impairment_amount <= 0) {
            throw new \Exception("Invalid reversal. This valuation is not marked as a reversal or has no reversal amount.");
        }

        // Get impairment reversal account
        $reversalAccountId = $this->getImpairmentReversalAccountId($hfsRequest);
        $hfsAccountId = $this->getHfsAccountIdForRequest($hfsRequest);

        if (!$reversalAccountId) {
            throw new \Exception("Impairment Reversal Account not configured.");
        }

        if (!$hfsAccountId) {
            throw new \Exception("HFS account not configured.");
        }

        // Create journal
        $journal = Journal::create([
            'branch_id' => $branchId,
            'date' => $valuation->valuation_date,
            'reference' => $hfsRequest->request_no . '-REV',
            'reference_type' => 'HFS Impairment Reversal',
            'description' => "HFS Impairment Reversal - {$hfsRequest->request_no} - {$valuation->valuation_date->format('Y-m-d')}",
            'user_id' => $user->id ?? null,
        ]);

        // Dr. Asset Held for Sale
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $hfsAccountId,
            'amount' => $valuation->impairment_amount,
            'nature' => 'debit',
            'description' => "HFS Impairment Reversal - Increase"
        ]);

        // Cr. Impairment Reversal
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $reversalAccountId,
            'amount' => $valuation->impairment_amount,
            'nature' => 'credit',
            'description' => "HFS Impairment Reversal"
        ]);

        // Update valuation with journal reference
        $valuation->impairment_journal_id = $journal->id;
        $valuation->gl_posted = true;
        $valuation->gl_posted_at = now();
        $valuation->save();

        // Create GL transactions (link to first asset)
        $firstAssetId = $hfsRequest->hfsAssets->first()?->asset_id ?? null;
        $this->createGlTransactions($journal, $branchId, $user, $firstAssetId);

        return $journal;
    }

    /**
     * Create disposal journal when asset is sold
     * Dr. Bank/Cash (proceeds)
     * Dr. Disposal Costs (expense)
     * Dr. Accumulated Impairment (if stored separately)
     * Cr. Asset Held for Sale (carrying amount)
     * Cr/Dr. Gain/Loss on Disposal (P&L) - balancing figure
     */
    public function createDisposalJournal(HfsDisposal $disposal): Journal
    {
        // Check if period is locked
        $companyId = $disposal->hfsRequest->company_id ?? ($disposal->hfsRequest->branch->company_id ?? null);
        if ($companyId) {
            $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
            try {
                $periodLockService->validateTransactionDate($disposal->disposal_date, $companyId, 'HFS disposal');
            } catch (\Exception $e) {
                \Log::warning('HfsDisposal - Cannot post: Period is locked', [
                    'disposal_id' => $disposal->id,
                    'disposal_date' => $disposal->disposal_date,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        $hfsRequest = $disposal->hfsRequest;
        $branchId = $hfsRequest->branch_id ?? session('branch_id');
        $user = auth()->user();

        // Get accounts
        $hfsAccountId = $this->getHfsAccountIdForRequest($hfsRequest);
        $gainLossAccountId = $this->getGainLossAccountId($hfsRequest);
        $bankAccountId = null;

        if ($disposal->bank_account_id) {
            $bankAccount = $disposal->bankAccount;
            if ($bankAccount && $bankAccount->chart_account_id) {
                $bankAccountId = $bankAccount->chart_account_id;
            }
        }

        // Fallback to cash account if no bank account
        if (!$bankAccountId) {
            $bankAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_cash_account')->value('value') ?? 0);
        }

        if (!$hfsAccountId) {
            throw new \Exception("HFS account not configured.");
        }

        if (!$gainLossAccountId) {
            throw new \Exception("Gain/Loss on Disposal Account not configured.");
        }

        if (!$bankAccountId) {
            throw new \Exception("Bank/Cash account not configured for disposal proceeds.");
        }

        // Create journal
        $journal = Journal::create([
            'branch_id' => $branchId,
            'date' => $disposal->disposal_date,
            'reference' => $hfsRequest->request_no . '-SALE',
            'reference_type' => 'HFS Disposal',
            'description' => "HFS Disposal - {$hfsRequest->request_no} - {$disposal->disposal_date->format('Y-m-d')}",
            'user_id' => $user->id ?? null,
        ]);

        // Get VAT and WHT accounts from inventory settings
        $vatAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_vat_account')->value('value') ?? 36);
        $whtAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_withholding_tax_account')->value('value') ?? 37);
        
        $vatAmount = $disposal->vat_amount ?? 0;
        $withholdingTaxAmount = $disposal->withholding_tax ?? 0;
        
        // Get VAT type from disposal data (if stored) or default to exclusive
        $vatType = $disposal->vat_type ?? 'exclusive';
        
        // Calculate net proceeds (sale proceeds minus VAT if exclusive, minus WHT)
        $netProceeds = $disposal->sale_proceeds;
        if ($vatType === 'exclusive' && $vatAmount > 0) {
            $netProceeds = $disposal->sale_proceeds - $vatAmount;
        }
        $netProceeds = $netProceeds - $withholdingTaxAmount;

        $totalDebit = 0;
        $totalCredit = 0;

        // Dr. Bank/Cash (net proceeds after VAT and WHT)
        if ($netProceeds > 0) {
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $bankAccountId,
                'amount' => $netProceeds,
                'nature' => 'debit',
                'description' => "Sale proceeds (net)"
            ]);
            $totalDebit += $netProceeds;
        }

        // Dr. Withholding Tax Receivable (if applicable)
        if ($withholdingTaxAmount > 0) {
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $whtAccountId,
                'amount' => $withholdingTaxAmount,
                'nature' => 'debit',
                'description' => "Withholding Tax Receivable on sale"
            ]);
            $totalDebit += $withholdingTaxAmount;
        }

        // Dr. Disposal Costs (if any)
        if ($disposal->costs_sold > 0) {
            // Use disposal costs expense account (could be from system settings)
            $disposalCostsAccountId = (int) (\App\Models\SystemSetting::where('key', 'disposal_costs_account_id')->value('value') ?? 0);
            if (!$disposalCostsAccountId) {
                // Fallback to general expense account
                $disposalCostsAccountId = $gainLossAccountId; // Use same account as gain/loss
            }

            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $disposalCostsAccountId,
                'amount' => $disposal->costs_sold,
                'nature' => 'debit',
                'description' => "Disposal costs"
            ]);
            $totalDebit += $disposal->costs_sold;
        }

        // Cr. Asset Held for Sale (carrying amount)
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $hfsAccountId,
            'amount' => $disposal->carrying_amount_at_disposal,
            'nature' => 'credit',
            'description' => "HFS Asset Disposal"
        ]);
        $totalCredit += $disposal->carrying_amount_at_disposal;

        // Cr. VAT Output (if applicable)
        if ($vatAmount > 0) {
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $vatAccountId,
                'amount' => $vatAmount,
                'nature' => 'credit',
                'description' => "VAT on sale"
            ]);
            $totalCredit += $vatAmount;
        }

        // Cr/Dr. Gain/Loss on Disposal (balancing figure)
        $gainLoss = $disposal->gain_loss_amount;
        if (abs($gainLoss) > 0.01) {
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $gainLossAccountId,
                'amount' => abs($gainLoss),
                'nature' => $gainLoss >= 0 ? 'credit' : 'debit',
                'description' => $gainLoss >= 0 ? "Gain on disposal" : "Loss on disposal"
            ]);

            if ($gainLoss >= 0) {
                $totalCredit += abs($gainLoss);
            } else {
                $totalDebit += abs($gainLoss);
            }
        }

        // Verify journal balances
        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new \Exception("Journal entries are not balanced. Debit: {$totalDebit}, Credit: {$totalCredit}");
        }

        // Update disposal with journal reference
        $disposal->journal_id = $journal->id;
        $disposal->gl_posted = true;
        $disposal->gl_posted_at = now();
        $disposal->save();

        // Create GL transactions (link to first asset)
        $firstAssetId = $hfsRequest->hfsAssets->first()?->asset_id ?? null;
        $this->createGlTransactions($journal, $branchId, $user, $firstAssetId);

        return $journal;
    }

    /**
     * Create cancellation journal when HFS is cancelled
     * Dr. Original PPE account (carrying amount)
     * Cr. Asset Held for Sale
     */
    public function createCancellationJournal(HfsRequest $hfsRequest): Journal
    {
        $branchId = $hfsRequest->branch_id ?? session('branch_id');
        $user = auth()->user();

        // Create journal
        $journal = Journal::create([
            'branch_id' => $branchId,
            'date' => now(),
            'reference' => $hfsRequest->request_no . '-CANCEL',
            'reference_type' => 'HFS Cancellation',
            'description' => "HFS Cancellation - {$hfsRequest->request_no}",
            'user_id' => $user->id ?? null,
        ]);

        $totalDebit = 0;
        $totalCredit = 0;

        // Process each asset
        foreach ($hfsRequest->hfsAssets as $hfsAsset) {
            $currentCarryingAmount = $hfsAsset->current_carrying_amount ?? $hfsAsset->carrying_amount_at_reclass;
            $hfsAccountId = $this->getHfsAccountId($hfsAsset);
            $originalAccountId = $hfsAsset->original_account_id;

            if (!$hfsAccountId || !$originalAccountId) {
                throw new \Exception("Required accounts not configured for asset.");
            }

            // Get asset code for description
            $assetCode = $hfsAsset->asset->code ?? 'N/A';

            // Dr. Original Asset Account
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $originalAccountId,
                'amount' => $currentCarryingAmount,
                'nature' => 'debit',
                'description' => "HFS Cancellation - {$assetCode}"
            ]);

            // Cr. Asset Held for Sale
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $hfsAccountId,
                'amount' => $currentCarryingAmount,
                'nature' => 'credit',
                'description' => "HFS Cancellation - {$assetCode}"
            ]);

            $totalDebit += $currentCarryingAmount;
            $totalCredit += $currentCarryingAmount;
        }

        // Verify journal balances
        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new \Exception("Journal entries are not balanced. Debit: {$totalDebit}, Credit: {$totalCredit}");
        }

        // Create GL transactions (link to first asset)
        $firstAssetId = $hfsRequest->hfsAssets->first()?->asset_id ?? null;
        $this->createGlTransactions($journal, $branchId, $user, $firstAssetId);

        return $journal;
    }

    /**
     * Create GL transactions from journal items
     * Optionally link to assets if provided
     */
    protected function createGlTransactions(Journal $journal, $branchId, $user, $assetId = null): void
    {
        foreach ($journal->items as $item) {
            GlTransaction::create([
                'chart_account_id' => $item->chart_account_id,
                'amount' => $item->amount,
                'nature' => $item->nature,
                'transaction_id' => $journal->id,
                'transaction_type' => 'journal',
                'date' => $journal->date,
                'description' => $item->description,
                'branch_id' => $branchId,
                'user_id' => $user->id ?? null,
                'asset_id' => $assetId,
            ]);
        }
    }

    /**
     * Get HFS account ID for an asset
     * Falls back to system default if category doesn't have it set
     */
    protected function getHfsAccountId(HfsAsset $hfsAsset): ?int
    {
        // First, try to get from asset category
        if ($hfsAsset->asset && $hfsAsset->asset->category && $hfsAsset->asset->category->hfs_account_id) {
            return $hfsAsset->asset->category->hfs_account_id;
        }
        
        // Fallback to system default
        $defaultAccountId = \App\Models\SystemSetting::where('key', 'asset_default_hfs_account')->value('value');
        return $defaultAccountId ? (int) $defaultAccountId : null;
    }

    /**
     * Get HFS account ID for a request (from first asset's category)
     */
    protected function getHfsAccountIdForRequest(HfsRequest $hfsRequest): ?int
    {
        $firstAsset = $hfsRequest->hfsAssets->first();
        if ($firstAsset) {
            return $this->getHfsAccountId($firstAsset);
        }
        return null;
    }

    /**
     * Get impairment loss account ID
     */
    protected function getImpairmentLossAccountId(HfsRequest $hfsRequest): ?int
    {
        $firstAsset = $hfsRequest->hfsAssets->first();
        if ($firstAsset && $firstAsset->asset && $firstAsset->asset->category) {
            return $firstAsset->asset->category->impairment_loss_account_id;
        }

        // Fallback to system setting
        return (int) (\App\Models\SystemSetting::where('key', 'impairment_loss_account_id')->value('value') ?? 0);
    }

    /**
     * Get impairment reversal account ID
     */
    protected function getImpairmentReversalAccountId(HfsRequest $hfsRequest): ?int
    {
        $firstAsset = $hfsRequest->hfsAssets->first();
        if ($firstAsset && $firstAsset->asset && $firstAsset->asset->category) {
            return $firstAsset->asset->category->impairment_reversal_account_id;
        }

        // Fallback to system setting
        return (int) (\App\Models\SystemSetting::where('key', 'impairment_reversal_account_id')->value('value') ?? 0);
    }

    /**
     * Get gain/loss on disposal account ID
     */
    protected function getGainLossAccountId(HfsRequest $hfsRequest): ?int
    {
        $firstAsset = $hfsRequest->hfsAssets->first();
        if ($firstAsset && $firstAsset->asset && $firstAsset->asset->category) {
            return $firstAsset->asset->category->gain_on_disposal_account_id 
                ?? $firstAsset->asset->category->loss_on_disposal_account_id;
        }

        // Fallback to system setting
        return (int) (\App\Models\SystemSetting::where('key', 'gain_loss_on_disposal_account_id')->value('value') ?? 0);
    }
}

