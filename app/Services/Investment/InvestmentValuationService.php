<?php

namespace App\Services\Investment;

use App\Models\Investment\InvestmentMaster;
use App\Models\Investment\InvestmentValuation;
use App\Models\Investment\InvestmentMarketPriceHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InvestmentValuationService
{
    /**
     * Calculate fair value based on valuation level and method
     * 
     * @param InvestmentMaster $investment
     * @param array $data
     * @param User $user
     * @return InvestmentValuation
     */
    public function createValuation(InvestmentMaster $investment, array $data, User $user): InvestmentValuation
    {
        DB::beginTransaction();
        try {
            // Get current carrying amount
            $carryingAmountBefore = $investment->carrying_amount ?? 0;
            
            // Calculate fair value based on level and method
            $fairValue = $this->calculateFairValue($investment, $data);
            
            // Calculate units at valuation date
            $units = $data['units'] ?? $investment->units ?? 0;
            
            // Calculate total fair value
            $totalFairValue = $fairValue * $units;
            
            // Determine if approval is required (Level 3)
            $requiresApproval = ($data['valuation_level'] ?? $investment->valuation_level ?? 1) === 3;
            
            // Create valuation record
            $valuation = InvestmentValuation::create([
                'investment_id' => $investment->id,
                'company_id' => $investment->company_id,
                'branch_id' => $investment->branch_id,
                'valuation_date' => $data['valuation_date'] ?? Carbon::today(),
                'period_start' => $data['period_start'] ?? null,
                'period_end' => $data['period_end'] ?? null,
                'valuation_level' => $data['valuation_level'] ?? $investment->valuation_level ?? 1,
                'valuation_method' => $data['valuation_method'] ?? 'MARKET_PRICE',
                'fair_value_per_unit' => $fairValue,
                'units' => $units,
                'total_fair_value' => $totalFairValue,
                'carrying_amount_before' => $carryingAmountBefore,
                'carrying_amount_after' => $totalFairValue, // Will be updated after revaluation
                'yield_rate' => $data['yield_rate'] ?? null,
                'discount_rate' => $data['discount_rate'] ?? null,
                'cash_flows' => $data['cash_flows'] ?? null,
                'valuation_inputs' => $data['valuation_inputs'] ?? null,
                'valuation_assumptions' => $data['valuation_assumptions'] ?? null,
                'price_source' => $data['price_source'] ?? 'MANUAL',
                'price_reference' => $data['price_reference'] ?? null,
                'price_date' => $data['price_date'] ?? Carbon::today(),
                'status' => $requiresApproval ? 'PENDING_APPROVAL' : 'DRAFT',
                'requires_approval' => $requiresApproval,
                'created_by' => $user->id,
            ]);
            
            DB::commit();
            return $valuation;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create investment valuation', [
                'investment_id' => $investment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate fair value based on valuation level and method (IFRS 13)
     * 
     * Level 1: Quoted prices in active markets (market price × units)
     * Level 2: Observable inputs other than quoted prices (yield curve, DCF)
     * Level 3: Unobservable inputs (internal models, assumptions)
     */
    protected function calculateFairValue(InvestmentMaster $investment, array $data): float
    {
        $valuationLevel = $data['valuation_level'] ?? $investment->valuation_level ?? 1;
        $valuationMethod = $data['valuation_method'] ?? 'MARKET_PRICE';
        
        switch ($valuationLevel) {
            case 1:
                // Level 1: Market price
                return $this->calculateLevel1FairValue($investment, $data);
                
            case 2:
                // Level 2: Observable inputs
                return $this->calculateLevel2FairValue($investment, $data);
                
            case 3:
                // Level 3: Unobservable inputs
                return $this->calculateLevel3FairValue($investment, $data);
                
            default:
                throw new \Exception("Invalid valuation level: {$valuationLevel}");
        }
    }

    /**
     * Level 1: Quoted prices in active markets
     */
    protected function calculateLevel1FairValue(InvestmentMaster $investment, array $data): float
    {
        // Use market price directly
        if (isset($data['fair_value_per_unit'])) {
            return (float) $data['fair_value_per_unit'];
        }
        
        // Try to get from market price history
        $latestPrice = InvestmentMarketPriceHistory::where('investment_id', $investment->id)
            ->where('price_date', '<=', $data['valuation_date'] ?? Carbon::today())
            ->latest('price_date')
            ->first();
            
        if ($latestPrice) {
            return (float) $latestPrice->market_price;
        }
        
        throw new \Exception("Market price not available for Level 1 valuation");
    }

    /**
     * Level 2: Observable inputs (yield curve, similar instruments)
     */
    protected function calculateLevel2FairValue(InvestmentMaster $investment, array $data): float
    {
        $method = $data['valuation_method'] ?? 'YIELD_CURVE';
        
        switch ($method) {
            case 'YIELD_CURVE':
                return $this->calculateYieldCurveValuation($investment, $data);
                
            case 'NAV':
                // For Money Market Funds
                if (isset($data['nav_price'])) {
                    return (float) $data['nav_price'];
                }
                throw new \Exception("NAV price required for NAV-based valuation");
                
            default:
                throw new \Exception("Invalid Level 2 valuation method: {$method}");
        }
    }

    /**
     * Level 3: Unobservable inputs (DCF, internal models)
     */
    protected function calculateLevel3FairValue(InvestmentMaster $investment, array $data): float
    {
        $method = $data['valuation_method'] ?? 'DCF';
        
        switch ($method) {
            case 'DCF':
                return $this->calculateDCFValuation($investment, $data);
                
            case 'BANK_VALUATION':
                if (isset($data['fair_value_per_unit'])) {
                    return (float) $data['fair_value_per_unit'];
                }
                throw new \Exception("Fair value required for bank valuation");
                
            default:
                throw new \Exception("Invalid Level 3 valuation method: {$method}");
        }
    }

    /**
     * Calculate valuation using yield curve discounting
     */
    protected function calculateYieldCurveValuation(InvestmentMaster $investment, array $data): float
    {
        $yieldRate = $data['yield_rate'] ?? null;
        if (!$yieldRate) {
            throw new \Exception("Yield rate required for yield curve valuation");
        }
        
        // For bonds: PV = sum of (coupon / (1 + yield)^t) + face_value / (1 + yield)^n
        // Simplified calculation - in production, use proper bond pricing formula
        $couponRate = $investment->coupon_rate ?? 0;
        $faceValue = $investment->nominal_amount ?? 100;
        $yearsToMaturity = $investment->maturity_date 
            ? Carbon::parse($investment->maturity_date)->diffInYears(Carbon::parse($data['valuation_date'] ?? Carbon::today()))
            : 1;
        
        // Simplified bond pricing (should use proper day count conventions)
        $yield = $yieldRate / 100;
        $couponPayment = ($faceValue * $couponRate) / 100;
        
        // Present value of coupon payments (annuity)
        $pvCoupons = 0;
        $couponFreq = $investment->coupon_freq ?? 2; // Semi-annual default
        $periods = $yearsToMaturity * $couponFreq;
        
        for ($i = 1; $i <= $periods; $i++) {
            $pvCoupons += $couponPayment / $couponFreq / pow(1 + $yield / $couponFreq, $i);
        }
        
        // Present value of face value
        $pvFace = $faceValue / pow(1 + $yield / $couponFreq, $periods);
        
        return $pvCoupons + $pvFace;
    }

    /**
     * Calculate valuation using Discounted Cash Flow (DCF)
     */
    protected function calculateDCFValuation(InvestmentMaster $investment, array $data): float
    {
        $cashFlows = $data['cash_flows'] ?? null;
        $discountRate = $data['discount_rate'] ?? null;
        
        if (!$cashFlows || !is_array($cashFlows)) {
            throw new \Exception("Cash flows array required for DCF valuation");
        }
        
        if (!$discountRate) {
            throw new \Exception("Discount rate required for DCF valuation");
        }
        
        $discountRate = $discountRate / 100; // Convert to decimal
        $presentValue = 0;
        $valuationDate = Carbon::parse($data['valuation_date'] ?? Carbon::today());
        
        foreach ($cashFlows as $cashFlow) {
            $date = Carbon::parse($cashFlow['date']);
            $amount = (float) $cashFlow['amount'];
            $years = $valuationDate->diffInDays($date) / 365.25;
            
            $presentValue += $amount / pow(1 + $discountRate, $years);
        }
        
        return $presentValue;
    }

    /**
     * Store market price in history
     */
    public function storeMarketPrice(InvestmentMaster $investment, array $data, User $user): InvestmentMarketPriceHistory
    {
        return InvestmentMarketPriceHistory::updateOrCreate(
            [
                'investment_id' => $investment->id,
                'price_date' => $data['price_date'] ?? Carbon::today(),
            ],
            [
                'company_id' => $investment->company_id,
                'market_price' => $data['market_price'],
                'bid_price' => $data['bid_price'] ?? null,
                'ask_price' => $data['ask_price'] ?? null,
                'mid_price' => $data['mid_price'] ?? ($data['bid_price'] && $data['ask_price'] ? ($data['bid_price'] + $data['ask_price']) / 2 : null),
                'price_source' => $data['price_source'] ?? 'MANUAL',
                'source_reference' => $data['source_reference'] ?? null,
                'source_url' => $data['source_url'] ?? null,
                'yield_rate' => $data['yield_rate'] ?? null,
                'volume' => $data['volume'] ?? null,
                'additional_data' => $data['additional_data'] ?? null,
                'created_by' => $user->id,
            ]
        );
    }
}

