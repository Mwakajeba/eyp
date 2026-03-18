<?php

namespace App\Services\Assets;

use App\Models\Assets\Asset;
use App\Models\Assets\AssetDisposal;
use App\Models\Assets\AssetDepreciation;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DisposalService
{
    /**
     * Calculate Net Book Value (NBV) for an asset
     * NBV = Cost - Accumulated Depreciation - Accumulated Impairment
     */
    public function calculateNBV(Asset $asset, $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();
        
        $assetCost = $asset->purchase_cost ?? 0;
        $accumulatedDepreciation = AssetDepreciation::getAccumulatedDepreciation($asset->id, $asOfDate);
        $accumulatedImpairment = $asset->accumulated_impairment ?? 0;
        
        $nbv = $assetCost - $accumulatedDepreciation - $accumulatedImpairment;
        
        return [
            'asset_cost' => $assetCost,
            'accumulated_depreciation' => $accumulatedDepreciation,
            'accumulated_impairment' => $accumulatedImpairment,
            'net_book_value' => max(0, $nbv), // Ensure NBV doesn't go negative
        ];
    }

    /**
     * Calculate gain or loss on disposal
     * Gain/Loss = Disposal Proceeds (or Fair Value) - NBV
     */
    public function calculateGainLoss(AssetDisposal $disposal): float
    {
        $proceeds = $disposal->disposal_proceeds ?? $disposal->fair_value ?? 0;
        $vatAmount = $disposal->vat_amount ?? 0;
        $vatType = $disposal->vat_type ?? 'no_vat';
        
        // Calculate net proceeds (excluding VAT) for gain/loss calculation
        // Gain/loss should be computed on net proceeds, not VAT-inclusive amount
        $netProceeds = $proceeds;
        if ($vatType === 'inclusive' && $vatAmount > 0) {
            // If VAT is inclusive, net proceeds = proceeds - VAT
            $netProceeds = $proceeds - $vatAmount;
        } elseif ($vatType === 'exclusive' && $vatAmount > 0) {
            // If VAT is exclusive, net proceeds = proceeds (VAT is separate)
            $netProceeds = $proceeds;
        }
        
        $nbv = $disposal->net_book_value;
        
        return $netProceeds - $nbv;
    }

    /**
     * Process disposal and create journal entries
     */
    public function processDisposal(AssetDisposal $disposal): array
    {
        try {
            DB::beginTransaction();

            $asset = $disposal->asset;
            $branchId = $disposal->branch_id ?? session('branch_id');
            
            // Calculate NBV if not already set
            if (!$disposal->net_book_value || $disposal->net_book_value == 0) {
                $nbvData = $this->calculateNBV($asset, $disposal->actual_disposal_date ?? $disposal->proposed_disposal_date);
                $disposal->asset_cost = $nbvData['asset_cost'];
                $disposal->accumulated_depreciation = $nbvData['accumulated_depreciation'];
                $disposal->accumulated_impairment = $nbvData['accumulated_impairment'];
                $disposal->net_book_value = $nbvData['net_book_value'];
            }

            // Calculate gain/loss
            $disposal->gain_loss = $this->calculateGainLoss($disposal);
            $disposal->save();

            // Create journal entries based on disposal type
            $journal = $this->createJournalEntries($disposal);

            // Transfer revaluation reserve to retained earnings if applicable
            if ($asset->revaluation_reserve_balance > 0) {
                $this->transferRevaluationReserve($disposal, $journal);
            }

            // Update asset status
            $asset->status = 'disposed';
            $asset->save();

            // Mark disposal as posted
            $disposal->gl_posted = true;
            $disposal->gl_posted_at = now();
            $disposal->status = 'completed';
            $disposal->save();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Disposal processed successfully',
                'journal_id' => $journal->id,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Disposal processing error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to process disposal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create journal entries for disposal
     */
    protected function createJournalEntries(AssetDisposal $disposal): Journal
    {
        // Check if period is locked
        $companyId = $disposal->company_id ?? ($disposal->branch->company_id ?? null);
        if ($companyId) {
            $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
            $disposalDate = $disposal->actual_disposal_date ?? $disposal->proposed_disposal_date;
            try {
                $periodLockService->validateTransactionDate($disposalDate, $companyId, 'asset disposal');
            } catch (\Exception $e) {
                \Log::warning('AssetDisposal - Cannot post: Period is locked', [
                    'disposal_id' => $disposal->id,
                    'disposal_date' => $disposalDate,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        $asset = $disposal->asset;
        $branchId = $disposal->branch_id ?? session('branch_id');
        
        // Get default accounts from asset category or disposal
        $assetAccountId = $asset->category->asset_account_id ?? null;
        $accumulatedDepreciationAccountId = $disposal->accumulated_depreciation_account_id 
            ?? $asset->category->accum_depr_account_id 
            ?? null;
        
        if (!$assetAccountId || !$accumulatedDepreciationAccountId) {
            throw new \Exception('Required chart accounts not configured');
        }

        // Create journal
        $journal = Journal::create([
            'branch_id' => $branchId,
            'date' => $disposal->actual_disposal_date ?? $disposal->proposed_disposal_date,
            'reference' => $disposal->disposal_number,
            'reference_type' => 'Asset Disposal',
            'description' => "Asset Disposal - {$asset->name} ({$asset->code}) - {$disposal->disposal_type}",
            'user_id' => auth()->id(),
        ]);

        $disposal->journal_id = $journal->id;
        $disposal->save();

        // Common entries for all disposal types
        // Dr. Accumulated Depreciation
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $accumulatedDepreciationAccountId,
            'amount' => $disposal->accumulated_depreciation,
            'nature' => 'debit',
            'description' => "Accumulated depreciation on disposal"
        ]);

        // Cr. Asset Account (Cost)
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $assetAccountId,
            'amount' => $disposal->asset_cost,
            'nature' => 'credit',
            'description' => "Asset cost on disposal"
        ]);

        // Handle disposal type-specific entries
        switch ($disposal->disposal_type) {
            case 'sale':
                $this->createSaleEntries($disposal, $journal);
                break;
            case 'scrap':
            case 'write_off':
                $this->createScrapWriteOffEntries($disposal, $journal);
                break;
            case 'donation':
                $this->createDonationEntries($disposal, $journal);
                break;
            case 'loss':
                $this->createLossEntries($disposal, $journal);
                break;
        }

        // Create GL transactions
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
                'user_id' => auth()->id(),
            ]);
        }

        return $journal;
    }

    /**
     * Create journal entries for asset sale
     */
    protected function createSaleEntries(AssetDisposal $disposal, Journal $journal): void
    {
        $asset = $disposal->asset;
        $category = $asset->category;
        
        // Get disposal proceeds account (cash/receivable)
        $proceedsAccountId = $disposal->disposal_proceeds_account_id;
        if (!$proceedsAccountId && $disposal->customer_id) {
            $customer = $disposal->customer;
            if ($customer && isset($customer->receivable_account_id) && $customer->receivable_account_id) {
                $proceedsAccountId = $customer->receivable_account_id;
            }
        }
        if (!$proceedsAccountId) {
            $proceedsAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_receivable_account')->value('value') ?? 0);
        }
        if (!$proceedsAccountId) {
            $proceedsAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_cash_account')->value('value') ?? 0);
        }
        
        // Get gain/loss accounts - separate accounts for gain and loss
        // For gain: use disposal gain account, or category gain account
        $gainAccountId = $disposal->gain_loss_account_id 
            ?? $category->gain_on_disposal_account_id 
            ?? null;
        // For loss: use disposal loss account (if set), or category loss account
        $lossAccountId = $disposal->loss_account_id 
            ?? $category->loss_on_disposal_account_id 
            ?? null;
        
        // Get VAT account - from disposal, or inventory settings (same as sales invoices)
        $vatAccountId = $disposal->vat_account_id;
        if (!$vatAccountId) {
            // Use inventory default VAT account (same as sales invoices)
            $vatAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_vat_account')->value('value') ?? 36);
        }
        
        // Get WHT Receivable account - from inventory settings (same as sales invoices)
        $whtAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_withholding_tax_account')->value('value') ?? 37);

        if (!$proceedsAccountId) {
            throw new \Exception('Disposal Proceeds Account (Cash/Receivable) not configured. Please configure in system settings or asset category.');
        }

        $proceeds = $disposal->disposal_proceeds;
        $amountPaid = $disposal->amount_paid ?? 0;
        $gainLoss = $disposal->gain_loss;
        $vatAmount = $disposal->vat_amount ?? 0;
        $vatType = $disposal->vat_type ?? 'no_vat';
        $withholdingTaxAmount = $disposal->withholding_tax ?? 0;
        
        // Calculate net proceeds (excluding VAT) for gain/loss calculation
        // Gain/loss should be computed on net proceeds, not VAT-inclusive amount
        $netProceeds = $proceeds;
        if ($vatType === 'inclusive' && $vatAmount > 0) {
            // If VAT is inclusive, net proceeds = proceeds - VAT
            $netProceeds = $proceeds - $vatAmount;
        } elseif ($vatType === 'exclusive' && $vatAmount > 0) {
            // If VAT is exclusive, net proceeds = proceeds (VAT is separate)
            $netProceeds = $proceeds;
        }
        
        // Calculate total invoice amount (what customer owes)
        // If VAT inclusive: Total Invoice = proceeds (already includes VAT)
        // If VAT exclusive: Total Invoice = proceeds + VAT
        $totalInvoiceAmount = $proceeds;
        if ($vatType === 'exclusive' && $vatAmount > 0) {
            $totalInvoiceAmount = $proceeds + $vatAmount;
        }
        
        // Calculate remaining receivable
        // Receivable = Total Invoice (VAT-inclusive) - Amount Paid
        // Do not add VAT again if it's already included in proceeds
        $remainingReceivable = max(0, $totalInvoiceAmount - $amountPaid);
        
        // Get receivable account for balance (if there's a remaining receivable)
        $receivableAccountId = null;
        if ($remainingReceivable > 0) {
            $receivableAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_receivable_account')->value('value') ?? 0);
            if (!$receivableAccountId && $disposal->customer_id) {
                $customer = $disposal->customer;
                if ($customer && isset($customer->receivable_account_id) && $customer->receivable_account_id) {
                    $receivableAccountId = $customer->receivable_account_id;
                }
            }
        }

        // Get bank account chart account ID if amount paid > 0
        $bankAccountChartAccountId = null;
        if ($amountPaid > 0 && $disposal->bank_account_id) {
            $bankAccount = $disposal->bankAccount;
            if ($bankAccount && $bankAccount->chart_account_id) {
                $bankAccountChartAccountId = $bankAccount->chart_account_id;
            }
        }

        // If amount paid > 0, debit bank account; otherwise debit proceeds account
        if ($amountPaid > 0 && $bankAccountChartAccountId) {
            // Dr. Bank Account (Amount Paid)
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $bankAccountChartAccountId,
                'amount' => $amountPaid,
                'nature' => 'debit',
                'description' => "Sale proceeds - amount paid"
            ]);
            
            // If balance exists, debit receivable account
            // Receivable includes VAT portion if not fully paid
            if ($receivableAccountId && $remainingReceivable > 0) {
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $receivableAccountId,
                    'amount' => $remainingReceivable,
                    'nature' => 'debit',
                    'description' => "Sale proceeds - balance receivable (includes VAT if applicable)"
                ]);
            }
        } else {
            // Dr. Cash/Receivable (Total Invoice Amount - includes VAT if applicable)
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $proceedsAccountId,
                'amount' => $totalInvoiceAmount,
                'nature' => 'debit',
                'description' => "Sale proceeds (total invoice amount)"
            ]);
        }

        // Cr./Dr. Gain or Loss on Disposal
        if ($gainLoss >= 0) {
            // Gain - use gain account
            if (!$gainAccountId) {
                throw new \Exception('Gain on Disposal Account not configured. Please configure in asset category or disposal record.');
            }
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $gainAccountId,
                'amount' => $gainLoss,
                'nature' => 'credit',
                'description' => "Gain on disposal"
            ]);
        } else {
            // Loss - use loss account
            if (!$lossAccountId) {
                throw new \Exception('Loss on Disposal Account not configured. Please configure in asset category or disposal record.');
            }
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $lossAccountId,
                'amount' => abs($gainLoss),
                'nature' => 'debit',
                'description' => "Loss on disposal"
            ]);
        }

        // Cr. VAT Output (if applicable) - VAT must be posted if amount > 0
        if ($vatAmount > 0) {
            if (!$vatAccountId) {
                throw new \Exception('VAT Output Account not configured. Please configure in system settings or disposal record.');
            }
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $vatAccountId,
                'amount' => $vatAmount,
                'nature' => 'credit',
                'description' => "VAT on sale"
            ]);
        }

        // Dr. Withholding Tax Receivable (if applicable) - WHT must be posted if amount > 0
        if ($withholdingTaxAmount > 0) {
            if (!$whtAccountId) {
                throw new \Exception('Withholding Tax Receivable Account not configured. Please configure in system settings.');
            }
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $whtAccountId,
                'amount' => $withholdingTaxAmount,
                'nature' => 'debit',
                'description' => "Withholding Tax Receivable on sale"
            ]);
        }
    }

    /**
     * Create journal entries for scrap/write-off
     */
    protected function createScrapWriteOffEntries(AssetDisposal $disposal, Journal $journal): void
    {
        $lossAccountId = $disposal->loss_account_id ?? null;
        
        if (!$lossAccountId) {
            throw new \Exception('Loss account not configured for scrap/write-off');
        }

        // Dr. Loss on Disposal
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $lossAccountId,
            'amount' => $disposal->net_book_value,
            'nature' => 'debit',
            'description' => "Loss on disposal ({$disposal->disposal_type})"
        ]);
    }

    /**
     * Create journal entries for donation
     */
    protected function createDonationEntries(AssetDisposal $disposal, Journal $journal): void
    {
        $donationAccountId = $disposal->donation_expense_account_id ?? null;
        
        if (!$donationAccountId) {
            throw new \Exception('Donation expense account not configured');
        }

        // Dr. Donation Expense
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $donationAccountId,
            'amount' => $disposal->net_book_value,
            'nature' => 'debit',
            'description' => "Donation expense"
        ]);
    }

    /**
     * Create journal entries for loss/theft
     */
    protected function createLossEntries(AssetDisposal $disposal, Journal $journal): void
    {
        $lossAccountId = $disposal->loss_account_id ?? null;
        $insuranceRecoveryAccountId = $disposal->insurance_recovery_account_id ?? null;
        
        if (!$lossAccountId) {
            throw new \Exception('Loss account not configured');
        }

        // Dr. Loss due to Theft/Loss
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $lossAccountId,
            'amount' => $disposal->net_book_value,
            'nature' => 'debit',
            'description' => "Loss due to {$disposal->disposal_type}"
        ]);

        // If insurance recovery exists
        if ($disposal->insurance_recovery_amount > 0 && $insuranceRecoveryAccountId) {
            // Get insurance recovery income account (can be same or different)
            $recoveryIncomeAccountId = $disposal->insurance_recovery_account_id ?? $insuranceRecoveryAccountId;
            
            // Dr. Cash/Receivable (Insurance Claim)
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $insuranceRecoveryAccountId,
                'amount' => $disposal->insurance_recovery_amount,
                'nature' => 'debit',
                'description' => "Insurance recovery"
            ]);

            // Cr. Insurance Recovery Income
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $recoveryIncomeAccountId,
                'amount' => $disposal->insurance_recovery_amount,
                'nature' => 'credit',
                'description' => "Insurance recovery income"
            ]);
        }
    }

    /**
     * Transfer revaluation reserve to retained earnings
     */
    protected function transferRevaluationReserve(AssetDisposal $disposal, Journal $journal): void
    {
        $asset = $disposal->asset;
        $reserveAccountId = $asset->revaluation_reserve_account_id ?? null;
        $retainedEarningsAccountId = $disposal->retained_earnings_account_id ?? null;

        if (!$reserveAccountId || !$retainedEarningsAccountId || $asset->revaluation_reserve_balance <= 0) {
            return;
        }

        $reserveBalance = $asset->revaluation_reserve_balance;

        // Dr. Revaluation Reserve
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $reserveAccountId,
            'amount' => $reserveBalance,
            'nature' => 'debit',
            'description' => "Transfer revaluation reserve to retained earnings on disposal"
        ]);

        // Cr. Retained Earnings
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $retainedEarningsAccountId,
            'amount' => $reserveBalance,
            'nature' => 'credit',
            'description' => "Transfer revaluation reserve to retained earnings on disposal"
        ]);

        $disposal->revaluation_reserve_transferred = $reserveBalance;
        $disposal->reserve_transferred_to_retained_earnings = true;
        $disposal->save();

        // Reset asset reserve balance
        $asset->revaluation_reserve_balance = 0;
        $asset->save();
    }
}

