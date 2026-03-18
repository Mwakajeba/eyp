<?php

namespace App\Services\Asset;

use App\Models\Assets\Asset;
use App\Models\Assets\AssetDeferredTax;
use App\Models\Assets\AssetDepreciation;
use App\Models\SystemSetting;
use App\Models\Journal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssetDeferredTaxService
{
    /**
     * Calculate deferred tax for an asset as of a specific date
     */
    public function calculateDeferredTax(Asset $asset, Carbon $asOfDate, $taxYear = null): array
    {
        $taxYear = $taxYear ?? $asOfDate->year;

        // Get book NBV
        $bookNBV = AssetDepreciation::getCurrentBookValue($asset->id, $asOfDate, $asset->company_id)
            ?? $asset->current_nbv
            ?? $asset->purchase_cost;

        // Get tax WDV
        $taxWDV = AssetDepreciation::getCurrentTaxWdv($asset->id, $asOfDate, $asset->company_id)
            ?? $asset->current_tax_wdv
            ?? ($asset->tax_value_opening ?? $asset->purchase_cost);

        // Calculate temporary difference
        $temporaryDifference = $bookNBV - $taxWDV;

        // Get tax rate
        $taxRate = $this->getTaxRate();

        // Calculate deferred tax
        $deferredTaxLiability = 0;
        $deferredTaxAsset = 0;

        if ($temporaryDifference > 0) {
            // Accounting NBV > Tax WDV → DTL (future tax expense)
            $deferredTaxLiability = $temporaryDifference * ($taxRate / 100);
        } elseif ($temporaryDifference < 0) {
            // Accounting NBV < Tax WDV → DTA (future tax benefit)
            $deferredTaxAsset = abs($temporaryDifference) * ($taxRate / 100);
        }

        $netDeferredTax = $deferredTaxLiability - $deferredTaxAsset;

        return [
            'tax_base_carrying_amount' => $taxWDV,
            'accounting_carrying_amount' => $bookNBV,
            'temporary_difference' => $temporaryDifference,
            'tax_rate' => $taxRate,
            'deferred_tax_liability' => $deferredTaxLiability,
            'deferred_tax_asset' => $deferredTaxAsset,
            'net_deferred_tax' => $netDeferredTax,
        ];
    }

    /**
     * Process deferred tax calculation for a tax period/year
     */
    public function processDeferredTax($taxYear = null, $companyId = null, $branchId = null, $postToGL = false)
    {
        $taxYear = $taxYear ?? now()->year;
        $periodStart = Carbon::create($taxYear, 1, 1);
        $periodEnd = Carbon::create($taxYear, 12, 31);

        $processed = [];
        $errors = [];

        // Get assets that have tax class assigned (and thus may have deferred tax)
        $query = Asset::whereNotNull('tax_class_id')
            ->where('status', '!=', 'disposed'); // Include active and retired assets

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $assets = $query->with(['taxClass', 'category'])->get();

        foreach ($assets as $asset) {
            try {
                // Calculate deferred tax as of period end
                $result = $this->calculateDeferredTax($asset, $periodEnd, $taxYear);

                // Get opening balance (previous period's closing balance)
                $previousPeriod = AssetDeferredTax::where('asset_id', $asset->id)
                    ->where('tax_year', $taxYear - 1)
                    ->first();

                $openingBalance = $previousPeriod ? $previousPeriod->closing_balance : 0;

                // Calculate movement
                $movement = $result['net_deferred_tax'] - $openingBalance;
                $closingBalance = $result['net_deferred_tax'];

                // Check if entry already exists for this period
                $existing = AssetDeferredTax::where('asset_id', $asset->id)
                    ->where('tax_year', $taxYear)
                    ->first();

                if ($existing) {
                    // Update existing entry
                    $existing->update([
                        'tax_period_start' => $periodStart,
                        'tax_period_end' => $periodEnd,
                        'tax_base_carrying_amount' => $result['tax_base_carrying_amount'],
                        'accounting_carrying_amount' => $result['accounting_carrying_amount'],
                        'temporary_difference' => $result['temporary_difference'],
                        'tax_rate' => $result['tax_rate'],
                        'deferred_tax_liability' => $result['deferred_tax_liability'],
                        'deferred_tax_asset' => $result['deferred_tax_asset'],
                        'net_deferred_tax' => $result['net_deferred_tax'],
                        'opening_balance' => $openingBalance,
                        'movement' => $movement,
                        'closing_balance' => $closingBalance,
                        'difference_type' => 'DEPRECIATION',
                        'updated_by' => auth()->id(),
                    ]);

                    $deferredTax = $existing;
                } else {
                    // Create new entry
                    $deferredTax = AssetDeferredTax::create([
                        'asset_id' => $asset->id,
                        'company_id' => $asset->company_id,
                        'branch_id' => $asset->branch_id,
                        'tax_period_start' => $periodStart,
                        'tax_period_end' => $periodEnd,
                        'tax_year' => $taxYear,
                        'tax_base_carrying_amount' => $result['tax_base_carrying_amount'],
                        'accounting_carrying_amount' => $result['accounting_carrying_amount'],
                        'temporary_difference' => $result['temporary_difference'],
                        'tax_rate' => $result['tax_rate'],
                        'deferred_tax_liability' => $result['deferred_tax_liability'],
                        'deferred_tax_asset' => $result['deferred_tax_asset'],
                        'net_deferred_tax' => $result['net_deferred_tax'],
                        'opening_balance' => $openingBalance,
                        'movement' => $movement,
                        'closing_balance' => $closingBalance,
                        'difference_type' => 'DEPRECIATION',
                        'difference_description' => 'Temporary difference between book depreciation (IFRS) and tax depreciation (TRA)',
                        'is_posted' => false,
                        'created_by' => auth()->id() ?? 1,
                    ]);
                }

                // Update asset deferred tax fields
                $asset->update([
                    'deferred_tax_diff' => $result['temporary_difference'],
                    'deferred_tax_liability' => $result['deferred_tax_liability'],
                ]);

                // Post to GL if requested
                if ($postToGL && abs($movement) > 0.01) {
                    $this->postToGL($deferredTax, $movement, $asset);
                }

                $processed[] = [
                    'asset_id' => $asset->id,
                    'asset_name' => $asset->name,
                    'temporary_difference' => $result['temporary_difference'],
                    'net_deferred_tax' => $result['net_deferred_tax'],
                    'movement' => $movement,
                ];

            } catch (\Exception $e) {
                Log::error('Deferred tax processing failed for asset ' . $asset->id, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errors[] = [
                    'asset_id' => $asset->id,
                    'asset_name' => $asset->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'processed' => $processed,
            'errors' => $errors,
            'total_processed' => count($processed),
            'total_errors' => count($errors),
        ];
    }

    /**
     * Post deferred tax movement to GL
     */
    private function postToGL(AssetDeferredTax $deferredTax, float $movement, Asset $asset)
    {
        if ($deferredTax->is_posted) {
            return; // Already posted
        }

        $user = auth()->user();
        if (!$user) {
            throw new \Exception('User must be authenticated to post to GL');
        }

        // Get deferred tax accounts from system settings
        $deferredTaxLiabilityAccountId = (int) (SystemSetting::where('key', 'asset_deferred_tax_liability_account')->value('value') ?? 0);
        $deferredTaxExpenseAccountId = (int) (SystemSetting::where('key', 'asset_deferred_tax_expense_account')->value('value') ?? 0);

        if (!$deferredTaxLiabilityAccountId || !$deferredTaxExpenseAccountId) {
            throw new \Exception('Deferred tax accounts are not configured in system settings.');
        }

        $branchId = $asset->branch_id
            ?? ($user->branch_id ?? null)
            ?? (session()->has('branch_id') ? session('branch_id') : null);

        if (!$branchId) {
            throw new \Exception('Branch ID is required for GL posting.');
        }

        DB::beginTransaction();
        try {
            // Create journal entry
            $journal = Journal::create([
                'company_id' => $asset->company_id,
                'branch_id' => $branchId,
                'reference' => 'DEF-TAX-' . $asset->code . '-' . $deferredTax->tax_year,
                'reference_type' => 'asset_deferred_tax',
                'journal_date' => $deferredTax->tax_period_end,
                'description' => 'Deferred Tax Movement - ' . $asset->name . ' (Year ' . $deferredTax->tax_year . ')',
                'total_amount' => abs($movement),
                'status' => 'draft',
                'created_by' => $user->id,
            ]);

            // Debit/Credit based on movement direction
            if ($movement > 0) {
                // Increase in DTL → Debit Deferred Tax Expense, Credit DTL
                \App\Models\JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $deferredTaxExpenseAccountId,
                    'amount' => abs($movement),
                    'nature' => 'debit',
                    'description' => 'Deferred Tax Expense - ' . $asset->name,
                ]);

                \App\Models\JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $deferredTaxLiabilityAccountId,
                    'amount' => abs($movement),
                    'nature' => 'credit',
                    'description' => 'Deferred Tax Liability - ' . $asset->name,
                ]);
            } else {
                // Decrease in DTL → Credit Deferred Tax Expense, Debit DTL
                \App\Models\JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $deferredTaxExpenseAccountId,
                    'amount' => abs($movement),
                    'nature' => 'credit',
                    'description' => 'Deferred Tax Benefit - ' . $asset->name,
                ]);

                \App\Models\JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $deferredTaxLiabilityAccountId,
                    'amount' => abs($movement),
                    'nature' => 'debit',
                    'description' => 'Deferred Tax Liability - ' . $asset->name,
                ]);
            }

            // Initialize approval workflow and post to GL
            $journal->initializeApprovalWorkflow();
            $journal->createGlTransactions();

            // Update deferred tax entry
            $deferredTax->update([
                'posted_journal_id' => $journal->id,
                'is_posted' => true,
                'posted_at' => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get corporate tax rate from system settings
     */
    protected function getTaxRate(): float
    {
        return (float) (SystemSetting::where('key', 'asset_tax_rate_percent')->value('value') 
            ?? SystemSetting::where('key', 'corporate_tax_rate')->value('value') 
            ?? 30);
    }
}

