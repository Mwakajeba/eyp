<?php

namespace App\Services\Asset;

use App\Models\Assets\Asset;
use App\Models\Assets\AssetDepreciation;
use App\Models\Assets\TaxDepreciationClass;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TaxDepreciationService
{
    /**
     * Calculate tax depreciation for an asset based on TRA class
     */
    public function calculateTaxDepreciation(
        Asset $asset,
        Carbon $asOfDate,
        $overrideTaxWdv = null,
        $overrideAccumTaxDep = null
    )
    {
        // Get tax class
        $taxClass = $asset->taxClass;
        if (!$taxClass) {
            Log::warning('Asset has no tax class assigned', ['asset_id' => $asset->id]);
            return [
                'depreciation_amount' => 0,
                'tax_wdv_before' => $overrideTaxWdv
                    ?? $asset->current_tax_wdv
                    ?? $asset->purchase_cost,
                'tax_wdv_after' => $overrideTaxWdv
                    ?? $asset->current_tax_wdv
                    ?? $asset->purchase_cost,
                'accumulated_tax_depreciation' => $overrideAccumTaxDep
                    ?? $asset->accumulated_tax_dep
                    ?? 0,
            ];
        }

        // Get current tax WDV and accumulated tax depreciation
        if ($overrideTaxWdv !== null && $overrideAccumTaxDep !== null) {
            $taxWdv = $overrideTaxWdv;
            $accumTaxDep = $overrideAccumTaxDep;
        } else {
            // Get from database - latest tax depreciation entry
            $latestTaxDep = AssetDepreciation::where('asset_id', $asset->id)
                ->where('depreciation_type', 'tax')
                ->where('depreciation_date', '<=', $asOfDate->copy()->subDay())
                ->orderBy('depreciation_date', 'desc')
                ->first();

            if ($latestTaxDep) {
                $taxWdv = $latestTaxDep->tax_wdv_after ?? 0;
                $accumTaxDep = $latestTaxDep->accumulated_tax_depreciation ?? 0;
            } else {
                // Use asset opening values
                $taxWdv = $asset->current_tax_wdv
                    ?? $asset->tax_value_opening
                    ?? $asset->purchase_cost;
                $accumTaxDep = $asset->accumulated_tax_dep ?? 0;
            }
        }

        $cost = $asset->purchase_cost;

        // Check if asset is disposed or retired
        if (in_array($asset->status, ['disposed', 'retired'])) {
            return [
                'depreciation_amount' => 0,
                'tax_wdv_before' => $taxWdv,
                'tax_wdv_after' => $taxWdv,
                'accumulated_tax_depreciation' => $accumTaxDep,
            ];
        }

        // Calculate based on tax class method
        switch ($taxClass->method) {
            case 'immediate_write_off':
                return $this->immediateWriteOff($cost, $asOfDate, $asset);

            case 'straight_line':
                return $this->taxStraightLine($cost, $taxClass->rate, $taxWdv, $accumTaxDep, $asOfDate, $asset, $taxClass);

            case 'reducing_balance':
                return $this->taxReducingBalance($cost, $taxClass->rate, $taxWdv, $accumTaxDep, $asOfDate, $asset, $taxClass);

            case 'useful_life':
                return $this->taxUsefulLife($cost, $taxWdv, $accumTaxDep, $asOfDate, $asset);

            default:
                return [
                    'depreciation_amount' => 0,
                    'tax_wdv_before' => $taxWdv,
                    'tax_wdv_after' => $taxWdv,
                    'accumulated_tax_depreciation' => $accumTaxDep,
                ];
        }
    }

    /**
     * Immediate Write-Off (Class 8: 100% in first year)
     */
    private function immediateWriteOff($cost, Carbon $asOfDate, Asset $asset)
    {
        $capitalizationDate = $asset->capitalization_date ?? $asset->purchase_date ?? now();
        if (!$capitalizationDate instanceof Carbon) {
            $capitalizationDate = Carbon::parse($capitalizationDate);
        }

        // Check if this is the first year
        $isFirstYear = $capitalizationDate->format('Y') === $asOfDate->format('Y');

        if ($isFirstYear) {
            // 100% write-off in first year
            return [
                'depreciation_amount' => $cost,
                'tax_wdv_before' => $cost,
                'tax_wdv_after' => 0,
                'accumulated_tax_depreciation' => $cost,
            ];
        }

        // Already fully depreciated
        return [
            'depreciation_amount' => 0,
            'tax_wdv_before' => 0,
            'tax_wdv_after' => 0,
            'accumulated_tax_depreciation' => $cost,
        ];
    }

    /**
     * Tax Straight Line Depreciation (Class 5, Class 6)
     */
    private function taxStraightLine($cost, $rate, $taxWdv, $accumTaxDep, Carbon $asOfDate, Asset $asset, TaxDepreciationClass $taxClass)
    {
        // For straight line, rate is usually percentage per year
        // Class 5: 20% per year = 5 years
        // Class 6: 5% per year = 20 years
        $annualDepreciation = $cost * ($rate / 100);
        $monthlyDepreciation = $annualDepreciation / 12;

        // Apply proration for partial months
        $capitalizationDate = $asset->capitalization_date ?? $asset->purchase_date ?? now();
        if (!$capitalizationDate instanceof Carbon) {
            $capitalizationDate = Carbon::parse($capitalizationDate);
        }

        $daysInMonth = $asOfDate->daysInMonth;
        $daysDepreciated = $this->getDaysDepreciated($asOfDate, $asset, $capitalizationDate);
        $proratedAmount = $monthlyDepreciation * ($daysDepreciated / $daysInMonth);

        $newAccumTaxDep = $accumTaxDep + $proratedAmount;
        $newTaxWdv = max($cost - $newAccumTaxDep, 0);

        return [
            'depreciation_amount' => $proratedAmount,
            'tax_wdv_before' => $taxWdv,
            'tax_wdv_after' => $newTaxWdv,
            'accumulated_tax_depreciation' => $newAccumTaxDep,
        ];
    }

    /**
     * Tax Reducing Balance Depreciation (Class 1, 2, 3)
     * With special handling for Class 2 first-year allowance
     */
    private function taxReducingBalance($cost, $rate, $taxWdv, $accumTaxDep, Carbon $asOfDate, Asset $asset, TaxDepreciationClass $taxClass)
    {
        $capitalizationDate = $asset->capitalization_date ?? $asset->purchase_date ?? now();
        if (!$capitalizationDate instanceof Carbon) {
            $capitalizationDate = Carbon::parse($capitalizationDate);
        }

        // Check for Class 2 special condition (50% + 50% for first two years)
        $isClass2 = $taxClass->class_code === 'Class 2';
        $specialCondition = $taxClass->special_condition;
        $isManufacturingTourism = $specialCondition && (
            str_contains(strtolower($specialCondition), 'manufacturing')
            || str_contains(strtolower($specialCondition), 'tourism')
            || str_contains(strtolower($specialCondition), 'fish farming')
        );

        $capitalizationYear = (int) $capitalizationDate->format('Y');
        $currentYear = (int) $asOfDate->format('Y');
        $yearsElapsed = $currentYear - $capitalizationYear;

        // Class 2 special treatment: 50% in first year,
        // 50% in second year if manufacturing/tourism/fish farming
        if ($isClass2 && $isManufacturingTourism) {
            if ($yearsElapsed === 0) {
                // First year: 50% of cost
                $depreciationAmount = $cost * 0.50;
                $newAccumTaxDep = $accumTaxDep + $depreciationAmount;
                $newTaxWdv = max($cost - $newAccumTaxDep, 0);

                return [
                    'depreciation_amount' => $depreciationAmount,
                    'tax_wdv_before' => $taxWdv,
                    'tax_wdv_after' => $newTaxWdv,
                    'accumulated_tax_depreciation' => $newAccumTaxDep,
                ];
            } elseif ($yearsElapsed === 1 && $accumTaxDep < $cost * 0.50) {
                // Second year: remaining 50% of cost
                $depreciationAmount = ($cost * 0.50) - $accumTaxDep;
                $newAccumTaxDep = $accumTaxDep + $depreciationAmount;
                $newTaxWdv = max($cost - $newAccumTaxDep, 0);

                return [
                    'depreciation_amount' => $depreciationAmount,
                    'tax_wdv_before' => $taxWdv,
                    'tax_wdv_after' => $newTaxWdv,
                    'accumulated_tax_depreciation' => $newAccumTaxDep,
                ];
            }
            // After first two years, use normal reducing balance
        }

        // Normal reducing balance calculation
        $monthlyRate = $rate / 12 / 100;
        $depreciationAmount = $taxWdv * $monthlyRate;

        $newAccumTaxDep = $accumTaxDep + $depreciationAmount;
        $newTaxWdv = max($taxWdv - $depreciationAmount, 0);

        return [
            'depreciation_amount' => $depreciationAmount,
            'tax_wdv_before' => $taxWdv,
            'tax_wdv_after' => $newTaxWdv,
            'accumulated_tax_depreciation' => $newAccumTaxDep,
        ];
    }

    /**
     * Tax Useful Life Depreciation (Class 7: Intangible assets)
     */
    private function taxUsefulLife($cost, $taxWdv, $accumTaxDep, Carbon $asOfDate, Asset $asset)
    {
        // For intangible assets, use useful life from asset category
        $category = $asset->category;
        $usefulLifeMonths = $category?->default_useful_life_months ?? 60;

        // Round down to nearest half year
        $usefulLifeMonths = floor($usefulLifeMonths / 6) * 6; // Round to nearest 6 months

        $annualDepreciation = $cost / ($usefulLifeMonths / 12);
        $monthlyDepreciation = $annualDepreciation / 12;

        $capitalizationDate = $asset->capitalization_date ?? $asset->purchase_date ?? now();
        if (!$capitalizationDate instanceof Carbon) {
            $capitalizationDate = Carbon::parse($capitalizationDate);
        }

        $daysInMonth = $asOfDate->daysInMonth;
        $daysDepreciated = $this->getDaysDepreciated($asOfDate, $asset, $capitalizationDate);
        $proratedAmount = $monthlyDepreciation * ($daysDepreciated / $daysInMonth);

        $newAccumTaxDep = $accumTaxDep + $proratedAmount;
        $newTaxWdv = max($cost - $newAccumTaxDep, 0);

        return [
            'depreciation_amount' => $proratedAmount,
            'tax_wdv_before' => $taxWdv,
            'tax_wdv_after' => $newTaxWdv,
            'accumulated_tax_depreciation' => $newAccumTaxDep,
        ];
    }

    /**
     * Get days depreciated based on capitalization date
     */
    private function getDaysDepreciated(Carbon $asOfDate, Asset $asset, Carbon $capitalizationDate)
    {
        // If same month as capitalization, prorate from capitalization date
        if ($asOfDate->format('Y-m') === $capitalizationDate->format('Y-m')) {
            return $asOfDate->diffInDays($capitalizationDate) + 1;
        }

        // Full month
        return $asOfDate->daysInMonth;
    }

    /**
     * Process tax depreciation for a period
     */
    public function processTaxDepreciation($periodDate = null, $companyId = null, $branchId = null)
    {
        $periodDate = $periodDate ?? now();
        if (!$periodDate instanceof Carbon) {
            $periodDate = Carbon::parse($periodDate);
        }

        $processed = [];
        $errors = [];

        // Get assets that have tax class assigned
        $query = Asset::where('status', 'active')
            ->whereNotNull('tax_class_id')
            ->where('depreciation_stopped', false);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $assets = $query->with(['taxClass', 'category'])->get();

        foreach ($assets as $asset) {
            try {
                // Skip if no tax class
                if (!$asset->taxClass) {
                    continue;
                }

                // Check if already depreciated for this period
                $existing = AssetDepreciation::where('asset_id', $asset->id)
                    ->where('depreciation_type', 'tax')
                    ->whereYear('depreciation_date', $periodDate->year)
                    ->whereMonth('depreciation_date', $periodDate->month)
                    ->first();

                if ($existing) {
                    continue; // Skip if already processed
                }

                // Calculate tax depreciation
                $result = $this->calculateTaxDepreciation($asset, $periodDate);

                if ($result['depreciation_amount'] <= 0) {
                    continue; // Skip if no depreciation
                }

                // Resolve branch_id
                $deprBranchId = $asset->branch_id 
                    ?? ($branchId ?? null)
                    ?? (auth()->user() ? auth()->user()->branch_id : null)
                    ?? (session()->has('branch_id') ? session('branch_id') : null);

                // Create tax depreciation entry
                $depreciation = AssetDepreciation::create([
                    'company_id' => $asset->company_id,
                    'branch_id' => $deprBranchId,
                    'asset_id' => $asset->id,
                    'type' => 'depreciation',
                    'depreciation_type' => 'tax',
                    'tax_class_id' => $asset->tax_class_id,
                    'depreciation_date' => $periodDate,
                    'depreciation_amount' => $result['depreciation_amount'],
                    'tax_wdv_before' => $result['tax_wdv_before'],
                    'tax_wdv_after' => $result['tax_wdv_after'],
                    'accumulated_tax_depreciation' => $result['accumulated_tax_depreciation'],
                    'description' => 'Tax Depreciation - ' . $asset->name
                        . ' (' . ($asset->taxClass->class_code ?? 'N/A') . ')',
                    'gl_posted' => false, // Tax depreciation is NOT posted to GL
                    'created_by' => auth()->id() ?? 1,
                ]);

                // Update asset current tax WDV
                $asset->update([
                    'current_tax_wdv' => $result['tax_wdv_after'],
                    'accumulated_tax_dep' => $result['accumulated_tax_depreciation'],
                ]);

                $processed[] = [
                    'asset_id' => $asset->id,
                    'asset_name' => $asset->name,
                    'tax_depreciation_amount' => $result['depreciation_amount'],
                    'tax_class' => $asset->taxClass->class_code ?? 'N/A',
                ];
            } catch (\Exception $e) {
                Log::error('Tax depreciation processing failed for asset ' . $asset->id, [
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
}
