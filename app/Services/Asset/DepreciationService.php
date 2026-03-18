<?php

namespace App\Services\Asset;

use App\Models\Assets\Asset;
use App\Models\Assets\AssetDepreciation;
use App\Models\Assets\AssetCategory;
use App\Models\SystemSetting;
use App\Models\GlTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepreciationService
{
    /**
     * Calculate depreciation for an asset based on method
     */
    public function calculateDepreciation(Asset $asset, Carbon $asOfDate, $method = null, $bookType = 'accounting', $overrideBookValue = null, $overrideAccumDepr = null)
    {
        $method = $method ?? $asset->category?->default_depreciation_method ?? 'straight_line';
        $category = $asset->category;
        
        // Get current book value and accumulated depreciation
        if ($overrideBookValue !== null && $overrideAccumDepr !== null) {
            // Use provided values (for forecast calculations)
            $bookValue = $overrideBookValue;
            $accumDepr = $overrideAccumDepr;
        } else {
            // Get from database
            $currentNBV = AssetDepreciation::getCurrentBookValue($asset->id, $asOfDate->copy()->subDay(), $asset->company_id);
            $currentAccumDepr = AssetDepreciation::getAccumulatedDepreciation($asset->id, $asOfDate->copy()->subDay(), $asset->company_id);
            
            // If no previous depreciation, use opening values
            $bookValue = $currentNBV ?? $asset->current_nbv ?? $asset->purchase_cost;
            $accumDepr = $currentAccumDepr;
        }
        $cost = $asset->purchase_cost;
        $salvageValue = $asset->salvage_value ?? 0;
        $depreciableAmount = $cost - $salvageValue;
        
        // Get useful life from category or asset
        $usefulLifeMonths = $category?->default_useful_life_months ?? 60;
        $depreciationRate = $category?->default_depreciation_rate ?? 0;
        
        // Check if asset is disposed or retired
        if (in_array($asset->status, ['disposed', 'retired'])) {
            return [
                'depreciation_amount' => 0,
                'book_value_before' => $bookValue,
                'book_value_after' => $bookValue,
                'accumulated_depreciation' => $accumDepr,
            ];
        }
        
        // Calculate based on method
        switch ($method) {
            case 'straight_line':
                return $this->straightLine($cost, $salvageValue, $usefulLifeMonths, $bookValue, $accumDepr, $asOfDate, $asset);
            
            case 'declining_balance':
                return $this->decliningBalance($cost, $depreciationRate, $bookValue, $accumDepr, $asOfDate, $asset);
            
            case 'syd':
                return $this->sumOfYearsDigits($cost, $salvageValue, $usefulLifeMonths, $bookValue, $accumDepr, $asOfDate, $asset);
            
            case 'units':
                return $this->unitsOfProduction($cost, $salvageValue, $bookValue, $accumDepr, $asOfDate, $asset);
            
            default:
                return $this->straightLine($cost, $salvageValue, $usefulLifeMonths, $bookValue, $accumDepr, $asOfDate, $asset);
        }
    }
    
    /**
     * Straight Line Depreciation
     */
    private function straightLine($cost, $salvageValue, $usefulLifeMonths, $bookValue, $accumDepr, Carbon $asOfDate, Asset $asset)
    {
        $depreciableAmount = $cost - $salvageValue;
        $monthlyDepreciation = $depreciableAmount / $usefulLifeMonths;
        
        // Apply convention
        $convention = $asset->category?->depreciation_convention ?? 'monthly_prorata';
        $daysInMonth = $asOfDate->daysInMonth;
        $daysDepreciated = $this->getDaysDepreciated($asOfDate, $asset, $convention);
        $proratedAmount = $monthlyDepreciation * ($daysDepreciated / $daysInMonth);
        
        $newAccumDepr = $accumDepr + $proratedAmount;
        $newBookValue = max($cost - $newAccumDepr, $salvageValue);
        
        return [
            'depreciation_amount' => $proratedAmount,
            'book_value_before' => $bookValue,
            'book_value_after' => $newBookValue,
            'accumulated_depreciation' => $newAccumDepr,
        ];
    }
    
    /**
     * Declining Balance Depreciation
     */
    private function decliningBalance($cost, $rate, $bookValue, $accumDepr, Carbon $asOfDate, Asset $asset)
    {
        if ($rate <= 0) {
            $rate = 20; // Default 20% if not set
        }
        
        $monthlyRate = $rate / 12 / 100;
        $depreciationAmount = $bookValue * $monthlyRate;
        
        $newAccumDepr = $accumDepr + $depreciationAmount;
        $newBookValue = max($bookValue - $depreciationAmount, 0);
        
        return [
            'depreciation_amount' => $depreciationAmount,
            'book_value_before' => $bookValue,
            'book_value_after' => $newBookValue,
            'accumulated_depreciation' => $newAccumDepr,
        ];
    }
    
    /**
     * Sum of Years' Digits Depreciation
     */
    private function sumOfYearsDigits($cost, $salvageValue, $usefulLifeMonths, $bookValue, $accumDepr, Carbon $asOfDate, Asset $asset)
    {
        $usefulLifeYears = ceil($usefulLifeMonths / 12);
        $sumOfYears = ($usefulLifeYears * ($usefulLifeYears + 1)) / 2;
        
        // Calculate which year we're in
        $capitalizationDate = $asset->capitalization_date ?? $asset->purchase_date ?? now();
        
        // Ensure capitalizationDate is a Carbon instance
        if (!$capitalizationDate instanceof Carbon) {
            $capitalizationDate = Carbon::parse($capitalizationDate);
        }
        
        $yearsElapsed = $capitalizationDate->diffInYears($asOfDate) + 1;
        $remainingYears = max($usefulLifeYears - $yearsElapsed + 1, 1);
        
        $depreciableAmount = $cost - $salvageValue;
        $annualDepreciation = ($remainingYears / $sumOfYears) * $depreciableAmount;
        $monthlyDepreciation = $annualDepreciation / 12;
        
        $newAccumDepr = $accumDepr + $monthlyDepreciation;
        $newBookValue = max($cost - $newAccumDepr, $salvageValue);
        
        return [
            'depreciation_amount' => $monthlyDepreciation,
            'book_value_before' => $bookValue,
            'book_value_after' => $newBookValue,
            'accumulated_depreciation' => $newAccumDepr,
        ];
    }
    
    /**
     * Units of Production Depreciation
     */
    private function unitsOfProduction($cost, $salvageValue, $bookValue, $accumDepr, Carbon $asOfDate, Asset $asset)
    {
        // This requires units data - for now return 0 if not implemented
        // TODO: Add units tracking to assets table
        return [
            'depreciation_amount' => 0,
            'book_value_before' => $bookValue,
            'book_value_after' => $bookValue,
            'accumulated_depreciation' => $accumDepr,
        ];
    }
    
    /**
     * Get days depreciated based on convention
     */
    private function getDaysDepreciated(Carbon $asOfDate, Asset $asset, $convention)
    {
        $capitalizationDate = $asset->capitalization_date ?? $asset->purchase_date ?? now();
        
        // Ensure capitalizationDate is a Carbon instance
        if (!$capitalizationDate instanceof Carbon) {
            $capitalizationDate = Carbon::parse($capitalizationDate);
        }
        
        switch ($convention) {
            case 'full_month':
                return $asOfDate->daysInMonth;
            
            case 'mid_month':
                // If capitalization is before 15th, full month; otherwise half month
                if ($capitalizationDate->day <= 15) {
                    return $asOfDate->daysInMonth;
                }
                return ceil($asOfDate->daysInMonth / 2);
            
            case 'monthly_prorata':
            default:
                // Prorate based on actual days
                if ($asOfDate->format('Y-m') == $capitalizationDate->format('Y-m')) {
                    // First month - prorate from capitalization date
                    return $asOfDate->diffInDays($capitalizationDate) + 1;
                }
                return $asOfDate->daysInMonth;
        }
    }
    
    /**
     * Process depreciation for a period
     */
    public function processDepreciation($periodDate = null, $companyId = null, $branchId = null, $postToGL = true)
    {
        $periodDate = $periodDate ?? now();
        if (!$periodDate instanceof Carbon) {
            $periodDate = Carbon::parse($periodDate);
        }
        
        $processed = [];
        $errors = [];
        
        // Get assets that need depreciation
        // Only select assets that have opening balance OR are purchased via purchase invoice
        // Exclude manually created assets (those without opening balance or purchase invoice reference)
        // Note: Cash purchases don't directly link to assets, so they're not included here
        // Exclude assets where depreciation is stopped (e.g., Held for Sale)
        $query = Asset::where('status', 'active')
            ->where('depreciation_stopped', false) // Skip assets where depreciation is stopped
            ->where(function ($q) {
                // Assets with opening balance
                $q->whereHas('openings', function ($openingQ) {
                    $openingQ->where('opening_cost', '>', 0);
                })
                // OR assets purchased via purchase invoice
                ->orWhereHas('purchaseInvoiceItems', function ($itemQ) {
                    $itemQ->where('item_type', 'asset');
                });
            });
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $assets = $query->with('category')->get();
        
        foreach ($assets as $asset) {
            try {
                // Skip if depreciation is stopped (e.g., Held for Sale)
                if ($asset->depreciation_stopped) {
                    continue;
                }

                // Check if already depreciated for this period
                $existing = AssetDepreciation::where('asset_id', $asset->id)
                    ->where('type', 'depreciation')
                    ->whereYear('depreciation_date', $periodDate->year)
                    ->whereMonth('depreciation_date', $periodDate->month)
                    ->first();
                
                if ($existing) {
                    continue; // Skip if already processed
                }
                
                // Calculate depreciation
                $result = $this->calculateDepreciation($asset, $periodDate);
                
                if ($result['depreciation_amount'] <= 0) {
                    continue; // Skip if no depreciation
                }
                
                // Resolve branch_id for depreciation entry
                $deprBranchId = $asset->branch_id 
                    ?? ($branchId ?? null)
                    ?? (auth()->user() ? auth()->user()->branch_id : null)
                    ?? (session()->has('branch_id') ? session('branch_id') : null);
                
                // Create depreciation entry (book basis)
                $depreciation = AssetDepreciation::create([
                    'company_id' => $asset->company_id,
                    'branch_id' => $deprBranchId,
                    'asset_id' => $asset->id,
                    'type' => 'depreciation',
                    'depreciation_type' => 'book', // Explicitly mark as book depreciation
                    'depreciation_date' => $periodDate,
                    'depreciation_amount' => $result['depreciation_amount'],
                    'accumulated_depreciation' => $result['accumulated_depreciation'],
                    'book_value_before' => $result['book_value_before'],
                    'book_value_after' => $result['book_value_after'],
                    'description' => 'Book Depreciation - ' . $asset->name,
                    'gl_posted' => false,
                    'created_by' => auth()->id() ?? 1,
                ]);
                
                // Post to GL if enabled
                if ($postToGL) {
                    $this->postToGL($depreciation, $asset);
                }
                
                // Update asset current NBV
                $asset->update(['current_nbv' => $result['book_value_after']]);
                
                $processed[] = [
                    'asset_id' => $asset->id,
                    'asset_name' => $asset->name,
                    'depreciation_amount' => $result['depreciation_amount'],
                ];
                
            } catch (\Exception $e) {
                Log::error('Depreciation processing failed for asset ' . $asset->id, [
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
     * Post depreciation to GL
     */
    private function postToGL(AssetDepreciation $depreciation, Asset $asset)
    {
        $category = $asset->category;
        $user = auth()->user();
        
        // Get accounts
        $deprExpenseAccountId = (int) ($category?->depr_expense_account_id
            ?: (SystemSetting::where('key', 'asset_default_depreciation_expense_account')->value('value')
                ?? SystemSetting::where('key', 'asset_default_depr_expense_account')->value('value') ?? 0));
        
        $accumDeprAccountId = (int) ($category?->accum_depr_account_id
            ?: (SystemSetting::where('key', 'asset_default_accumulated_depreciation_account')->value('value')
                ?? SystemSetting::where('key', 'asset_default_accum_depr_account')->value('value') ?? 0));
        
        if (!$deprExpenseAccountId || !$accumDeprAccountId) {
            throw new \Exception('Depreciation accounts are not configured.');
        }
        
        $date = $depreciation->depreciation_date;
        $description = $depreciation->description;
        $amount = $depreciation->depreciation_amount;
        
        // Resolve branch_id with multiple fallbacks
        $branchId = $depreciation->branch_id 
            ?? $asset->branch_id 
            ?? ($user ? $user->branch_id : null)
            ?? (session()->has('branch_id') ? session('branch_id') : null);
        
        if (!$branchId) {
            throw new \Exception('Branch ID is required for GL posting. Please ensure the asset has a branch assigned or you are logged in with a branch.');
        }
        
        $userId = $user ? $user->id : 1;
        
        // Create GL transactions
        GlTransaction::create([
            'chart_account_id' => $deprExpenseAccountId,
            'asset_id' => $asset->id,
            'amount' => $amount,
            'nature' => 'debit',
            'transaction_id' => $depreciation->id,
            'transaction_type' => 'asset_depreciation',
            'date' => $date,
            'description' => $description,
            'branch_id' => $branchId,
            'user_id' => $userId,
        ]);
        
        GlTransaction::create([
            'chart_account_id' => $accumDeprAccountId,
            'asset_id' => $asset->id,
            'amount' => $amount,
            'nature' => 'credit',
            'transaction_id' => $depreciation->id,
            'transaction_type' => 'asset_depreciation',
            'date' => $date,
            'description' => $description,
            'branch_id' => $branchId,
            'user_id' => $userId,
        ]);
        
        $depreciation->update(['gl_posted' => true]);
    }
    
    /**
     * Forecast depreciation for future periods
     */
    public function forecastDepreciation(Asset $asset, $periods = 12, $startDate = null)
    {
        $startDate = $startDate ?? now();
        if (!$startDate instanceof Carbon) {
            $startDate = Carbon::parse($startDate);
        }
        
        $forecast = [];
        $currentNBV = AssetDepreciation::getCurrentBookValue($asset->id, null, $asset->company_id) ?? $asset->current_nbv ?? $asset->purchase_cost;
        $currentAccumDepr = AssetDepreciation::getAccumulatedDepreciation($asset->id, null, $asset->company_id);
        
        for ($i = 0; $i < $periods; $i++) {
            $periodDate = $startDate->copy()->addMonths($i);
            
            // Calculate depreciation using current book value and accumulated depreciation
            $result = $this->calculateDepreciation($asset, $periodDate, null, 'accounting', $currentNBV, $currentAccumDepr);
            
            $forecast[] = [
                'period' => $periodDate->format('Y-m'),
                'date' => $periodDate->format('Y-m-d'),
                'depreciation_amount' => $result['depreciation_amount'],
                'book_value_before' => $result['book_value_before'],
                'book_value_after' => $result['book_value_after'],
                'accumulated_depreciation' => $result['accumulated_depreciation'],
            ];
            
            // Update for next iteration
            $currentNBV = $result['book_value_after'];
            $currentAccumDepr = $result['accumulated_depreciation'];
        }
        
        return $forecast;
    }
}

