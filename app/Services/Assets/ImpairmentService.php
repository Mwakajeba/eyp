<?php

namespace App\Services\Assets;

use App\Models\Assets\Asset;
use App\Models\Assets\AssetImpairment;
use App\Models\Assets\AssetDepreciation;
use App\Models\Assets\RevaluationReserve;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImpairmentService
{
    /**
     * Process impairment and create journal entries
     */
    public function processImpairment(AssetImpairment $impairment, array $data): array
    {
        try {
            DB::beginTransaction();

            $asset = $impairment->asset;
            
            // Get current carrying amount
            $carryingAmount = AssetDepreciation::getCurrentBookValue($asset->id, $impairment->impairment_date);
            if (!$carryingAmount) {
                $accumulatedDepreciation = AssetDepreciation::getAccumulatedDepreciation($asset->id, $impairment->impairment_date);
                $carryingAmount = $asset->purchase_cost - $accumulatedDepreciation;
            }

            $impairment->carrying_amount = $carryingAmount;

            // Calculate recoverable amount
            $recoverableAmount = $this->calculateRecoverableAmount($impairment, $data);
            $impairment->recoverable_amount = $recoverableAmount;

            // Calculate impairment loss
            $impairmentLoss = max(0, $carryingAmount - $recoverableAmount);
            $impairment->impairment_loss = $impairmentLoss;
            $impairment->carrying_amount_after = $recoverableAmount;

            // Update useful life and residual value if provided
            if (isset($data['useful_life_after'])) {
                $impairment->useful_life_after = $data['useful_life_after'];
            }
            if (isset($data['residual_value_after'])) {
                $impairment->residual_value_after = $data['residual_value_after'];
            }

            $impairment->save();

            // Create journal entries if approved and ready to post
            if ($impairment->status === 'approved' && !$impairment->gl_posted && $impairmentLoss > 0) {
                $this->createJournalEntries($impairment);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Impairment processed successfully',
                'impairment' => $impairment
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Impairment processing error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to process impairment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate recoverable amount (higher of fair value less costs or value in use)
     */
    public function calculateRecoverableAmount(AssetImpairment $impairment, array $data): float
    {
        $fairValueLessCosts = $data['fair_value_less_costs'] ?? null;
        $valueInUse = null;

        // Calculate value in use if cash flow projections provided
        if (!empty($data['cash_flow_projections']) && isset($data['discount_rate'])) {
            $impairment->cash_flow_projections = $data['cash_flow_projections'];
            $impairment->discount_rate = $data['discount_rate'];
            $impairment->projection_years = count($data['cash_flow_projections']);
            $valueInUse = $impairment->calculateValueInUse();
            $impairment->value_in_use = $valueInUse;
        } elseif (isset($data['value_in_use'])) {
            $valueInUse = $data['value_in_use'];
            $impairment->value_in_use = $valueInUse;
        }

        // Store fair value less costs
        if ($fairValueLessCosts !== null) {
            $impairment->fair_value_less_costs = $fairValueLessCosts;
        }

        // Recoverable amount is higher of the two
        if ($fairValueLessCosts !== null && $valueInUse !== null) {
            return max($fairValueLessCosts, $valueInUse);
        } elseif ($fairValueLessCosts !== null) {
            return $fairValueLessCosts;
        } elseif ($valueInUse !== null) {
            return $valueInUse;
        }

        throw new \Exception('Either fair value less costs or value in use must be provided');
    }

    /**
     * Create journal entries for impairment
     */
    public function createJournalEntries(AssetImpairment $impairment): void
    {
        // Check if period is locked
        $companyId = $impairment->company_id ?? ($impairment->branch->company_id ?? null);
        if ($companyId) {
            $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
            try {
                $periodLockService->validateTransactionDate($impairment->impairment_date, $companyId, 'asset impairment');
            } catch (\Exception $e) {
                \Log::warning('AssetImpairment - Cannot post: Period is locked', [
                    'impairment_id' => $impairment->id,
                    'impairment_date' => $impairment->impairment_date,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        $asset = $impairment->asset;
        $branchId = session('branch_id') ?? $impairment->branch_id;
        
        // Get asset account from category
        $assetAccountId = $asset->category->asset_account_id ?? null;
        if (!$assetAccountId) {
            throw new \Exception('Asset account not configured for this asset category');
        }

        // Get impairment loss account
        $impairmentLossAccountId = $impairment->impairment_loss_account_id 
            ?? $asset->category->impairment_loss_account_id
            ?? null;
        
        if (!$impairmentLossAccountId) {
            throw new \Exception('Impairment loss account not configured');
        }

        // Get accumulated impairment account (contra-asset account)
        $accumulatedImpairmentAccountId = $impairment->accumulated_impairment_account_id 
            ?? $asset->category->accumulated_impairment_account_id
            ?? null;
        
        if (!$accumulatedImpairmentAccountId) {
            throw new \Exception('Accumulated impairment account not configured');
        }

        // Check if asset uses revaluation model
        $isRevaluedAsset = $asset->valuation_model === 'revaluation';
        $reserveAccountId = null;

        if ($isRevaluedAsset) {
            $reserveAccountId = $impairment->revaluation_reserve_account_id 
                ?? $asset->revaluation_reserve_account_id;
        }

        // Create journal
        $journal = Journal::create([
            'branch_id' => $branchId,
            'date' => $impairment->impairment_date,
            'reference' => $impairment->impairment_number,
            'reference_type' => 'Asset Impairment',
            'description' => "Asset Impairment - {$asset->name} ({$asset->code})",
            'user_id' => auth()->id(),
            'approved' => true,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $impairment->journal_id = $journal->id;
        $impairment->save();

        // For revalued assets, charge to revaluation reserve first
        if ($isRevaluedAsset && $reserveAccountId) {
            $currentReserveBalance = RevaluationReserve::getCurrentBalance(
                $asset->id,
                $impairment->impairment_date,
                $impairment->company_id
            );

            if ($currentReserveBalance > 0) {
                $reserveCharge = min($impairment->impairment_loss, $currentReserveBalance);
                $plCharge = $impairment->impairment_loss - $reserveCharge;

                if ($reserveCharge > 0) {
                    // Dr. Revaluation Reserve, Cr. Accumulated Impairment
                    // IAS 36: Impairment loss first reduces revaluation reserve (equity)
                    JournalItem::create([
                        'journal_id' => $journal->id,
                        'chart_account_id' => $reserveAccountId,
                        'amount' => $reserveCharge,
                        'nature' => 'debit',
                        'description' => "Impairment loss (from revaluation reserve)"
                    ]);

                    JournalItem::create([
                        'journal_id' => $journal->id,
                        'chart_account_id' => $accumulatedImpairmentAccountId,
                        'amount' => $reserveCharge,
                        'nature' => 'credit',
                        'description' => "Impairment loss (from revaluation reserve)"
                    ]);

                    // Update revaluation reserve
                    $this->updateRevaluationReserve($impairment, -$reserveCharge);
                }

                if ($plCharge > 0) {
                    // Dr. Impairment Loss (P&L), Cr. Accumulated Impairment
                    // IAS 36: Remaining impairment loss (after reserve) goes to P&L
                    JournalItem::create([
                        'journal_id' => $journal->id,
                        'chart_account_id' => $impairmentLossAccountId,
                        'amount' => $plCharge,
                        'nature' => 'debit',
                        'description' => "Impairment loss (P&L)"
                    ]);

                    JournalItem::create([
                        'journal_id' => $journal->id,
                        'chart_account_id' => $accumulatedImpairmentAccountId,
                        'amount' => $plCharge,
                        'nature' => 'credit',
                        'description' => "Impairment loss (P&L)"
                    ]);
                }
            } else {
                // No reserve, charge to P&L
                // Dr. Impairment Loss (P&L), Cr. Accumulated Impairment
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $impairmentLossAccountId,
                    'amount' => $impairment->impairment_loss,
                    'nature' => 'debit',
                    'description' => "Impairment loss"
                ]);

                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $accumulatedImpairmentAccountId,
                    'amount' => $impairment->impairment_loss,
                    'nature' => 'credit',
                    'description' => "Impairment loss"
                ]);
            }
        } else {
            // Cost model - charge to P&L
            // Dr. Impairment Loss (P&L), Cr. Accumulated Impairment
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $impairmentLossAccountId,
                'amount' => $impairment->impairment_loss,
                'nature' => 'debit',
                'description' => "Impairment loss"
            ]);

            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $accumulatedImpairmentAccountId,
                'amount' => $impairment->impairment_loss,
                'nature' => 'credit',
                'description' => "Impairment loss"
            ]);
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

        // Mark as posted
        $impairment->gl_posted = true;
        $impairment->gl_posted_at = now();
        $impairment->status = 'posted';
        $impairment->save();

        // Update asset
        $asset->is_impaired = true;
        $asset->last_impairment_date = $impairment->impairment_date;
        $asset->accumulated_impairment += $impairment->impairment_loss;
        $asset->revalued_carrying_amount = $impairment->carrying_amount_after;
        if ($impairment->useful_life_after) {
            // Update useful life if changed
        }
        $asset->save();
    }

    /**
     * Process impairment reversal
     */
    public function processReversal(AssetImpairment $originalImpairment, AssetImpairment $reversal, array $data): array
    {
        try {
            DB::beginTransaction();

            $asset = $originalImpairment->asset;
            $maxReversible = $originalImpairment->remaining_reversible_amount;

            if ($data['reversal_amount'] > $maxReversible) {
                throw new \Exception("Reversal amount cannot exceed {$maxReversible}");
            }

            $reversal->impairment_loss = $data['reversal_amount'];
            $reversal->reversal_amount = $data['reversal_amount'];
            $reversal->carrying_amount_after = $asset->revalued_carrying_amount + $data['reversal_amount'];
            $reversal->save();

            // Create journal entries if approved
            if ($reversal->status === 'approved' && !$reversal->gl_posted) {
                $this->createReversalJournalEntries($reversal, $originalImpairment);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Impairment reversal processed successfully',
                'reversal' => $reversal
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Impairment reversal error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to process reversal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create journal entries for impairment reversal
     */
    protected function createReversalJournalEntries(AssetImpairment $reversal, AssetImpairment $original): void
    {
        $asset = $reversal->asset;
        $branchId = session('branch_id') ?? $reversal->branch_id;
        
        $assetAccountId = $asset->category->asset_account_id ?? null;
        $impairmentReversalAccountId = $reversal->impairment_reversal_account_id 
            ?? $asset->category->impairment_reversal_account_id
            ?? null;

        if (!$impairmentReversalAccountId) {
            throw new \Exception('Impairment reversal account not configured');
        }

        // Get accumulated impairment account (contra-asset account)
        $accumulatedImpairmentAccountId = $reversal->accumulated_impairment_account_id 
            ?? $asset->category->accumulated_impairment_account_id
            ?? null;
        
        if (!$accumulatedImpairmentAccountId) {
            throw new \Exception('Accumulated impairment account not configured');
        }

        $isRevaluedAsset = $asset->valuation_model === 'revaluation';
        $reserveAccountId = null;

        if ($isRevaluedAsset) {
            $reserveAccountId = $reversal->revaluation_reserve_account_id 
                ?? $asset->revaluation_reserve_account_id;
        }

        $journal = Journal::create([
            'branch_id' => $branchId,
            'date' => $reversal->reversal_date ?? $reversal->impairment_date,
            'reference' => $reversal->impairment_number,
            'reference_type' => 'Asset Impairment Reversal',
            'description' => "Impairment Reversal - {$asset->name} ({$asset->code})",
            'user_id' => auth()->id(),
            'approved' => true,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $reversal->journal_id = $journal->id;
        $reversal->save();

        if ($isRevaluedAsset && $reserveAccountId) {
            // For revalued assets, credit to revaluation reserve
            // Dr. Accumulated Impairment, Cr. Revaluation Reserve
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $accumulatedImpairmentAccountId,
                'amount' => $reversal->reversal_amount,
                'nature' => 'debit',
                'description' => "Impairment reversal"
            ]);

            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $reserveAccountId,
                'amount' => $reversal->reversal_amount,
                'nature' => 'credit',
                'description' => "Impairment reversal"
            ]);

            $this->updateRevaluationReserve($reversal, $reversal->reversal_amount);
        } else {
            // For cost model, credit to P&L
            // Dr. Accumulated Impairment, Cr. Impairment Reversal (P&L)
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $accumulatedImpairmentAccountId,
                'amount' => $reversal->reversal_amount,
                'nature' => 'debit',
                'description' => "Impairment reversal"
            ]);

            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $impairmentReversalAccountId,
                'amount' => $reversal->reversal_amount,
                'nature' => 'credit',
                'description' => "Impairment reversal"
            ]);
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

        $reversal->gl_posted = true;
        $reversal->gl_posted_at = now();
        $reversal->status = 'posted';
        $reversal->save();

        // Update asset
        $asset->accumulated_impairment -= $reversal->reversal_amount;
        $asset->revalued_carrying_amount = $reversal->carrying_amount_after;
        if ($asset->accumulated_impairment <= 0) {
            $asset->is_impaired = false;
        }
        $asset->save();
    }

    /**
     * Update revaluation reserve for impairment
     */
    protected function updateRevaluationReserve(AssetImpairment $impairment, float $amount): void
    {
        $asset = $impairment->asset;
        $reserveAccountId = $impairment->revaluation_reserve_account_id 
            ?? $asset->revaluation_reserve_account_id;

        if (!$reserveAccountId) {
            return;
        }

        $currentBalance = RevaluationReserve::getCurrentBalance(
            $asset->id,
            $impairment->impairment_date,
            $impairment->company_id
        );

        $newBalance = $currentBalance + $amount;
        $movementType = $amount > 0 ? 'impairment_reversal' : 'impairment_charge';

        RevaluationReserve::create([
            'company_id' => $impairment->company_id,
            'branch_id' => $impairment->branch_id,
            'asset_id' => $asset->id,
            'impairment_id' => $impairment->id,
            'reserve_account_id' => $reserveAccountId,
            'movement_date' => $impairment->impairment_date,
            'movement_type' => $movementType,
            'amount' => $amount,
            'balance_after' => $newBalance,
            'reference_number' => $impairment->impairment_number,
            'description' => $amount > 0 ? "Impairment reversal" : "Impairment charge",
            'journal_id' => $impairment->journal_id,
            'created_by' => auth()->id(),
        ]);

        $asset->revaluation_reserve_balance = $newBalance;
        $asset->save();
    }
}

