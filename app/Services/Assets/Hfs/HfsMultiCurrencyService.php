<?php

namespace App\Services\Assets\Hfs;

use App\Models\Assets\HfsDisposal;
use App\Models\FxRate;
use App\Models\SystemSetting;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service for handling multi-currency in HFS disposals
 * - Foreign currency sales
 * - FX rate conversions
 * - FX gains/losses posting
 */
class HfsMultiCurrencyService
{
    /**
     * Get functional currency for company
     */
    public function getFunctionalCurrency($companyId): string
    {
        return SystemSetting::getValue('functional_currency', 'TZS') ?? 'TZS';
    }

    /**
     * Get FX rate for a specific date and currency pair
     */
    public function getFxRate(string $fromCurrency, string $toCurrency, $date, $companyId): ?float
    {
        // Try to get rate from fx_rates table
        $rate = FxRate::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->where('rate_date', $date)
            ->where('company_id', $companyId)
            ->first();

        if ($rate) {
            return (float) $rate->spot_rate;
        }

        // If no rate found, try to get the latest rate before the date
        $latestRate = FxRate::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->where('rate_date', '<=', $date)
            ->where('company_id', $companyId)
            ->orderBy('rate_date', 'desc')
            ->first();

        if ($latestRate) {
            return (float) $latestRate->spot_rate;
        }

        // If still no rate, return 1 (assume same currency)
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        Log::warning("FX rate not found for {$fromCurrency} to {$toCurrency} on {$date}", [
            'company_id' => $companyId,
        ]);

        return null;
    }

    /**
     * Convert foreign currency amount to functional currency
     */
    public function convertToFunctionalCurrency(float $amount, string $fromCurrency, string $toCurrency, $date, $companyId): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $rate = $this->getFxRate($fromCurrency, $toCurrency, $date, $companyId);
        if (!$rate) {
            throw new \Exception("FX rate not found for {$fromCurrency} to {$toCurrency} on {$date}");
        }

        return $amount * $rate;
    }

    /**
     * Calculate FX gain/loss on disposal
     * Compares carrying amount currency with sale proceeds currency
     */
    public function calculateFxGainLoss(HfsDisposal $disposal): array
    {
        $hfsRequest = $disposal->hfsRequest;
        $companyId = $hfsRequest->company_id;
        $functionalCurrency = $this->getFunctionalCurrency($companyId);
        
        $saleCurrency = $disposal->sale_currency ?? $functionalCurrency;
        $saleProceeds = $disposal->sale_proceeds;
        $disposalDate = $disposal->disposal_date;

        // Get carrying amount currency (from HFS assets)
        $firstAsset = $hfsRequest->hfsAssets->first();
        $carryingCurrency = $firstAsset ? ($firstAsset->book_currency ?? $functionalCurrency) : $functionalCurrency;
        $carryingAmount = $disposal->carrying_amount_at_disposal;

        // If both are in functional currency, no FX gain/loss
        if ($saleCurrency === $functionalCurrency && $carryingCurrency === $functionalCurrency) {
            return [
                'fx_gain_loss' => 0,
                'fx_gain_loss_lcy' => 0,
                'sale_proceeds_lcy' => $saleProceeds,
                'carrying_amount_lcy' => $carryingAmount,
            ];
        }

        // Convert sale proceeds to functional currency
        $saleProceedsLcy = $this->convertToFunctionalCurrency(
            $saleProceeds,
            $saleCurrency,
            $functionalCurrency,
            $disposalDate,
            $companyId
        );

        // Convert carrying amount to functional currency (if different)
        $carryingAmountLcy = $carryingAmount;
        if ($carryingCurrency !== $functionalCurrency) {
            $carryingAmountLcy = $this->convertToFunctionalCurrency(
                $carryingAmount,
                $carryingCurrency,
                $functionalCurrency,
                $disposalDate,
                $companyId
            );
        }

        // FX gain/loss = difference in functional currency
        // This is a simplified calculation - in practice, you'd track the original FX rate
        // when the asset was reclassified and compare with disposal rate
        $fxGainLoss = $saleProceedsLcy - ($saleProceeds / ($disposal->currency_rate ?? 1)) * ($disposal->currency_rate ?? 1);

        // More accurate: compare original carrying amount in FCY with disposal proceeds in FCY
        // then convert both to LCY at their respective rates
        // For now, we'll use the currency_rate stored in disposal
        if ($disposal->currency_rate && $disposal->currency_rate != 1) {
            // Calculate what the carrying amount would be at disposal rate
            $carryingAtDisposalRate = $carryingAmount * $disposal->currency_rate;
            $fxGainLoss = $saleProceedsLcy - $carryingAtDisposalRate;
        }

        return [
            'fx_gain_loss' => $fxGainLoss,
            'fx_gain_loss_lcy' => $fxGainLoss,
            'sale_proceeds_lcy' => $saleProceedsLcy,
            'carrying_amount_lcy' => $carryingAmountLcy,
            'sale_currency' => $saleCurrency,
            'carrying_currency' => $carryingCurrency,
            'functional_currency' => $functionalCurrency,
        ];
    }

    /**
     * Post FX gain/loss journal entry
     */
    public function postFxGainLoss(HfsDisposal $disposal, float $fxGainLoss): ?Journal
    {
        if (abs($fxGainLoss) < 0.01) {
            return null; // No significant FX gain/loss
        }

        $hfsRequest = $disposal->hfsRequest;
        $companyId = $hfsRequest->company_id;
        $branchId = $hfsRequest->branch_id ?? session('branch_id');
        $user = auth()->user();

        // Get FX gain/loss accounts from system settings
        $fxGainAccountId = (int) SystemSetting::getValue('fx_realized_gain_account_id', 0);
        $fxLossAccountId = (int) SystemSetting::getValue('fx_realized_loss_account_id', 0);

        if (!$fxGainAccountId && !$fxLossAccountId) {
            Log::warning("FX gain/loss accounts not configured, skipping FX gain/loss posting");
            return null;
        }

        // Create journal
        $journal = Journal::create([
            'branch_id' => $branchId,
            'date' => $disposal->disposal_date,
            'reference' => $hfsRequest->request_no . '-FX',
            'reference_type' => 'HFS FX Gain/Loss',
            'description' => "FX Gain/Loss on HFS Disposal - {$hfsRequest->request_no}",
            'user_id' => $user->id ?? null,
        ]);

        if ($fxGainLoss > 0) {
            // FX Gain: Dr Bank (already in disposal journal), Cr FX Gain Account
            if ($fxGainAccountId) {
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $fxGainAccountId,
                    'amount' => abs($fxGainLoss),
                    'nature' => 'credit',
                    'description' => "FX Gain on HFS Disposal"
                ]);
            }
        } else {
            // FX Loss: Dr FX Loss Account, Cr Bank (already in disposal journal)
            if ($fxLossAccountId) {
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $fxLossAccountId,
                    'amount' => abs($fxGainLoss),
                    'nature' => 'debit',
                    'description' => "FX Loss on HFS Disposal"
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
                'user_id' => $user->id ?? null,
            ]);
        }

        return $journal;
    }
}

