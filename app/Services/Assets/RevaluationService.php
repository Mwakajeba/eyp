<?php

namespace App\Services\Assets;

use App\Models\Assets\Asset;
use App\Models\Assets\AssetRevaluation;
use App\Models\Assets\AssetDepreciation;
use App\Models\Assets\RevaluationReserve;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RevaluationService
{
    /**
     * Process revaluation and create journal entries
     */
    public function processRevaluation(AssetRevaluation $revaluation, array $data): array
    {
        try {
            DB::beginTransaction();

            $asset = $revaluation->asset;
            
            // Get current carrying amount and accumulated depreciation
            $carryingAmountBefore = AssetDepreciation::getCurrentBookValue($asset->id, $revaluation->revaluation_date);
            $accumulatedDepreciationBefore = AssetDepreciation::getAccumulatedDepreciation($asset->id, $revaluation->revaluation_date);
            
            if (!$carryingAmountBefore) {
                $carryingAmountBefore = $asset->purchase_cost - $accumulatedDepreciationBefore;
            }

            // Calculate revaluation difference
            $fairValue = $data['fair_value'];
            $revaluationDifference = $fairValue - $carryingAmountBefore;
            
            // Update revaluation record
            $revaluation->carrying_amount_before = $carryingAmountBefore;
            $revaluation->accumulated_depreciation_before = $accumulatedDepreciationBefore;
            $revaluation->fair_value = $fairValue;
            $revaluation->carrying_amount_after = $fairValue;
            
            if ($revaluationDifference > 0) {
                $revaluation->revaluation_increase = $revaluationDifference;
                $revaluation->revaluation_decrease = 0;
            } else {
                $revaluation->revaluation_increase = 0;
                $revaluation->revaluation_decrease = abs($revaluationDifference);
            }

            // Update useful life and residual value if provided
            if (isset($data['useful_life_after'])) {
                $revaluation->useful_life_after = $data['useful_life_after'];
            }
            if (isset($data['residual_value_after'])) {
                $revaluation->residual_value_after = $data['residual_value_after'];
            }

            $revaluation->save();

            // Create journal entries if approved and ready to post
            if ($revaluation->status === 'approved' && !$revaluation->gl_posted) {
                $this->createJournalEntries($revaluation);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Revaluation processed successfully',
                'revaluation' => $revaluation
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Revaluation processing error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to process revaluation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create journal entries for revaluation
     */
    public function createJournalEntries(AssetRevaluation $revaluation): void
    {
        // Check if period is locked
        $companyId = $revaluation->company_id ?? ($revaluation->branch->company_id ?? null);
        if ($companyId) {
            $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
            try {
                $periodLockService->validateTransactionDate($revaluation->revaluation_date, $companyId, 'asset revaluation');
            } catch (\Exception $e) {
                \Log::warning('AssetRevaluation - Cannot post: Period is locked', [
                    'revaluation_id' => $revaluation->id,
                    'revaluation_date' => $revaluation->revaluation_date,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        $asset = $revaluation->asset;
        $branchId = session('branch_id') ?? $revaluation->branch_id;
        
        // Get asset account from category
        $assetAccountId = $asset->category->asset_account_id ?? null;
        if (!$assetAccountId) {
            throw new \Exception('Asset account not configured for this asset category');
        }

        // Get revaluation reserve account
        $reserveAccountId = $revaluation->revaluation_reserve_account_id 
            ?? $asset->revaluation_reserve_account_id 
            ?? null;
        
        if (!$reserveAccountId) {
            throw new \Exception('Revaluation reserve account not configured');
        }

        // Create journal
        $journal = Journal::create([
            'branch_id' => $branchId,
            'date' => $revaluation->revaluation_date,
            'reference' => $revaluation->revaluation_number,
            'reference_type' => 'Asset Revaluation',
            'description' => "Asset Revaluation - {$asset->name} ({$asset->code})",
            'user_id' => auth()->id(),
        ]);

        $revaluation->journal_id = $journal->id;
        $revaluation->save();

        // Handle upward revaluation
        if ($revaluation->revaluation_increase > 0) {
            // Calculate prior revaluation losses charged to P&L
            $priorRevaluationLossBalance = $this->calculatePriorRevaluationLossBalance($asset, $revaluation->revaluation_date);
            
            // Check if reversing previous impairment
            $previousImpairment = $asset->impairments()
                ->where('impairment_date', '<=', $revaluation->revaluation_date)
                ->where('gl_posted', true)
                ->orderBy('impairment_date', 'desc')
                ->first();

            $priorImpairmentLoss = $previousImpairment && $previousImpairment->impairment_loss > 0 
                ? $previousImpairment->impairment_loss 
                : 0;

            // Total prior losses to reverse (revaluation losses + impairment losses)
            $totalPriorLossBalance = $priorRevaluationLossBalance + $priorImpairmentLoss;

            // Always debit asset for the full current gain (IFRS requirement - no netting)
            $currentGain = $revaluation->revaluation_increase;

            // Calculate reversal amounts
            $revaluationReversalAmount = min($currentGain, $priorRevaluationLossBalance);
            $impairmentReversalAmount = 0;
            $remainingGainForReversal = $currentGain - $revaluationReversalAmount;

            if ($remainingGainForReversal > 0 && $priorImpairmentLoss > 0) {
                $impairmentReversalAmount = min($remainingGainForReversal, $priorImpairmentLoss);
                $remainingGainForReversal -= $impairmentReversalAmount;
            }

            // Remaining gain goes to OCI (Revaluation Surplus)
            $surplusAmount = $remainingGainForReversal;

            // Get revaluation loss account for reversal (P&L credit)
            $revaluationLossAccountId = $asset->category->revaluation_loss_account_id ?? null;
            if (!$revaluationLossAccountId && $revaluationReversalAmount > 0) {
                throw new \Exception('Revaluation loss account not configured for this asset category. Please configure it in Revaluation & Impairment Settings.');
            }

            // Get impairment reversal account
            $impairmentReversalAccountId = $revaluation->impairment_reversal_account_id 
                ?? ($previousImpairment ? $previousImpairment->impairment_reversal_account_id : null)
                ?? null;

            // 1. Always debit asset for full current gain
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $assetAccountId,
                'amount' => $currentGain,
                'nature' => 'debit',
                'description' => "Revaluation increase (full amount)"
            ]);

            // 2. Credit P&L reversal for prior revaluation losses (up to current gain)
            if ($revaluationReversalAmount > 0) {
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $revaluationLossAccountId,
                    'amount' => $revaluationReversalAmount,
                    'nature' => 'credit',
                    'description' => "Reversal of prior revaluation loss (P&L)"
                ]);
            }

            // 3. Credit P&L reversal for prior impairment losses (if any remaining gain)
            if ($impairmentReversalAmount > 0 && $impairmentReversalAccountId) {
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $impairmentReversalAccountId,
                    'amount' => $impairmentReversalAmount,
                    'nature' => 'credit',
                    'description' => "Reversal of impairment loss (P&L)"
                ]);
            }

            // 4. Credit OCI (Revaluation Surplus) for remaining gain
            if ($surplusAmount > 0) {
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $reserveAccountId,
                    'amount' => $surplusAmount,
                    'nature' => 'credit',
                    'description' => "Revaluation increase (OCI - Revaluation Surplus)"
                ]);
            }
        }

        // Handle downward revaluation
        // IFRS Rule: Charge decrease to revaluation reserve (OCI) first, then to P&L if reserve is insufficient
        if ($revaluation->revaluation_decrease > 0) {
            // Get current reserve balance (prior gains in OCI)
            $currentReserveBalance = RevaluationReserve::getCurrentBalance(
                $asset->id,
                $revaluation->revaluation_date,
                $revaluation->company_id
            );

            // Always credit asset for the full decrease (no netting)
            $currentLoss = $revaluation->revaluation_decrease;

            if ($currentReserveBalance >= $currentLoss) {
                // Reserve is sufficient: Charge entire decrease to reserve (OCI)
                // Journal: Dr. Revaluation Reserve (OCI), Cr. Asset
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $reserveAccountId,
                    'amount' => $currentLoss,
                    'nature' => 'debit',
                    'description' => "Revaluation decrease (from reserve - OCI)"
                ]);

                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $assetAccountId,
                    'amount' => $currentLoss,
                    'nature' => 'credit',
                    'description' => "Revaluation decrease"
                ]);
            } else {
                // Reserve is insufficient: Charge reserve first, then P&L
                // Split: Reserve portion (up to reserve balance) + P&L portion (excess)
                $reserveCharge = $currentReserveBalance; // Charge to reserve up to its balance
                $plCharge = $currentLoss - $reserveCharge; // Remaining goes to P&L

                // 1. Charge reserve portion to OCI (if any)
                if ($reserveCharge > 0) {
                    JournalItem::create([
                        'journal_id' => $journal->id,
                        'chart_account_id' => $reserveAccountId,
                        'amount' => $reserveCharge,
                        'nature' => 'debit',
                        'description' => "Revaluation decrease (from reserve - OCI)"
                    ]);
                }

                // 2. Charge excess to P&L (revaluation loss)
                if ($plCharge > 0) {
                    // Get revaluation loss account (for P&L charge when reserve is insufficient)
                    $revaluationLossAccountId = $asset->category->revaluation_loss_account_id ?? null;
                    if (!$revaluationLossAccountId) {
                        throw new \Exception('Revaluation loss account not configured for this asset category. Please configure it in Revaluation & Impairment Settings.');
                    }

                    JournalItem::create([
                        'journal_id' => $journal->id,
                        'chart_account_id' => $revaluationLossAccountId,
                        'amount' => $plCharge,
                        'nature' => 'debit',
                        'description' => "Revaluation decrease (revaluation loss - P&L)"
                    ]);
                }

                // 3. Credit asset for full decrease (reserveCharge + plCharge = currentLoss)
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $assetAccountId,
                    'amount' => $currentLoss,
                    'nature' => 'credit',
                    'description' => "Revaluation decrease"
                ]);
            }
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

        // Update revaluation reserve
        $this->updateRevaluationReserve($revaluation);

        // Mark as posted
        $revaluation->gl_posted = true;
        $revaluation->gl_posted_at = now();
        $revaluation->status = 'posted';
        $revaluation->save();

        // Update asset
        $asset->revalued_carrying_amount = $revaluation->carrying_amount_after;
        $asset->last_revaluation_date = $revaluation->revaluation_date;
        if ($revaluation->useful_life_after) {
            // Update useful life if changed
        }
        $asset->save();
    }

    /**
     * Calculate cumulative prior revaluation losses charged to P&L
     * This represents losses that were not absorbed by revaluation reserve
     * and were charged directly to P&L (revaluation_loss_account_id)
     * 
     * IFRS requires that prior revaluation losses charged to P&L must be
     * fully reversed before any gain can be posted to OCI (Revaluation Surplus)
     */
    protected function calculatePriorRevaluationLossBalance(Asset $asset, $asOfDate): float
    {
        // Get all prior posted revaluations with decreases, ordered chronologically
        $priorRevaluations = AssetRevaluation::where('asset_id', $asset->id)
            ->where('revaluation_date', '<', $asOfDate)
            ->where('gl_posted', true)
            ->where('revaluation_decrease', '>', 0)
            ->orderBy('revaluation_date', 'asc')
            ->get();

        $totalPriorLossBalance = 0;
        $runningReserveBalance = 0;

        foreach ($priorRevaluations as $priorReval) {
            // Get reserve balance BEFORE this revaluation (exclude this revaluation's date)
            $reserveBalanceBefore = $this->getReserveBalanceBeforeDate(
                $asset->id,
                $priorReval->revaluation_date,
                $priorReval->company_id
            );

            // Calculate how much of the decrease went to P&L vs reserve
            // IFRS rule: Charge to reserve first, then P&L
            $decreaseAmount = $priorReval->revaluation_decrease;
            $reserveCharge = min($decreaseAmount, $reserveBalanceBefore);
            $plCharge = $decreaseAmount - $reserveCharge;

            // Accumulate P&L charges (these are the losses that need to be reversed)
            $totalPriorLossBalance += $plCharge;

            // Update running reserve balance for next iteration
            // Reserve decreases by the reserve charge portion
            $runningReserveBalance = $reserveBalanceBefore - $reserveCharge;
        }

        return $totalPriorLossBalance;
    }

    /**
     * Get revaluation reserve balance before a specific date
     * (excludes movements on the specified date)
     */
    protected function getReserveBalanceBeforeDate($assetId, $beforeDate, $companyId): float
    {
        $latest = RevaluationReserve::where('asset_id', $assetId)
            ->where('company_id', $companyId)
            ->where('movement_date', '<', $beforeDate)
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
        
        return $latest ? $latest->balance_after : 0;
    }

    /**
     * Update revaluation reserve balance
     * Only the surplus amount (OCI portion) should be added to reserve,
     * not the full increase if there were prior losses reversed
     */
    protected function updateRevaluationReserve(AssetRevaluation $revaluation): void
    {
        $asset = $revaluation->asset;
        $reserveAccountId = $revaluation->revaluation_reserve_account_id 
            ?? $asset->revaluation_reserve_account_id;

        if (!$reserveAccountId) {
            return;
        }

        $currentBalance = RevaluationReserve::getCurrentBalance(
            $asset->id,
            $revaluation->revaluation_date,
            $revaluation->company_id
        );

        $newBalance = $currentBalance;

        if ($revaluation->revaluation_increase > 0) {
            // Calculate how much actually went to reserve (surplus amount)
            // This is the portion that goes to OCI, not the P&L reversal portion
            $priorRevaluationLossBalance = $this->calculatePriorRevaluationLossBalance($asset, $revaluation->revaluation_date);
            
            // Check for prior impairment losses
            $previousImpairment = $asset->impairments()
                ->where('impairment_date', '<=', $revaluation->revaluation_date)
                ->where('gl_posted', true)
                ->orderBy('impairment_date', 'desc')
                ->first();
            $priorImpairmentLoss = $previousImpairment && $previousImpairment->impairment_loss > 0 
                ? $previousImpairment->impairment_loss 
                : 0;

            $totalPriorLossBalance = $priorRevaluationLossBalance + $priorImpairmentLoss;
            
            // Surplus amount = current gain - prior losses reversed
            // This is the amount that goes to OCI (Revaluation Surplus)
            $surplusAmount = max(0, $revaluation->revaluation_increase - $totalPriorLossBalance);
            
            $newBalance += $surplusAmount;
            $movementType = 'revaluation_increase';
            $amount = $surplusAmount; // Only record the surplus amount, not the full increase
        } elseif ($revaluation->revaluation_decrease > 0) {
            $newBalance -= min($revaluation->revaluation_decrease, $currentBalance);
            $movementType = 'revaluation_decrease';
            $amount = -min($revaluation->revaluation_decrease, $currentBalance);
        } else {
            return;
        }

        RevaluationReserve::create([
            'company_id' => $revaluation->company_id,
            'branch_id' => $revaluation->branch_id,
            'asset_id' => $asset->id,
            'revaluation_id' => $revaluation->id,
            'reserve_account_id' => $reserveAccountId,
            'movement_date' => $revaluation->revaluation_date,
            'movement_type' => $movementType,
            'amount' => $amount,
            'balance_after' => $newBalance,
            'reference_number' => $revaluation->revaluation_number,
            'description' => "Revaluation: {$revaluation->reason}",
            'journal_id' => $revaluation->journal_id,
            'created_by' => auth()->id(),
        ]);

        // Update asset reserve balance
        $asset->revaluation_reserve_balance = $newBalance;
        $asset->save();
    }
}

