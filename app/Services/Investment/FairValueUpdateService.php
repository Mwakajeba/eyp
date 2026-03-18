<?php

namespace App\Services\Investment;

use App\Models\Investment\InvestmentMaster;
use App\Models\Investment\InvestmentTrade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FairValueUpdateService
{
    /**
     * Update fair values from market feed
     * 
     * @param InvestmentMaster $investment
     * @param array $marketData Optional: Manual market data override
     * @return array
     */
    public function updateFairValue(InvestmentMaster $investment, array $marketData = []): array
    {
        $result = [
            'investment_id' => $investment->id,
            'instrument_code' => $investment->instrument_code,
            'previous_fair_value' => null,
            'new_fair_value' => null,
            'source' => null,
            'updated' => false,
        ];

        try {
            // Get latest trade
            $latestTrade = InvestmentTrade::where('investment_id', $investment->id)
                ->where('trade_type', 'PURCHASE')
                ->latest('trade_date')
                ->first();

            if (!$latestTrade) {
                throw new \Exception('No purchase trade found');
            }

            $result['previous_fair_value'] = $latestTrade->fair_value;

            // Determine fair value based on instrument type
            switch ($investment->instrument_type) {
                case 'T_BOND':
                case 'CORP_BOND':
                    $fairValue = $this->getBondFairValue($investment, $latestTrade, $marketData);
                    break;

                case 'T_BILL':
                    $fairValue = $this->getTBillFairValue($investment, $latestTrade, $marketData);
                    break;

                case 'EQUITY':
                    $fairValue = $this->getEquityFairValue($investment, $latestTrade, $marketData);
                    break;

                case 'MMF':
                    $fairValue = $this->getMmfFairValue($investment, $latestTrade, $marketData);
                    break;

                case 'FIXED_DEPOSIT':
                    // Fixed deposits typically use amortized cost, but can have fair value
                    $fairValue = $this->getFixedDepositFairValue($investment, $latestTrade, $marketData);
                    break;

                default:
                    throw new \Exception('Unsupported instrument type for fair value update');
            }

            if ($fairValue && $fairValue['value'] > 0) {
                $oldFairValue = $latestTrade->fair_value;
                $oldSource = $latestTrade->fair_value_source;
                
                // Update trade fair value
                $latestTrade->fair_value = $fairValue['value'];
                $latestTrade->fair_value_source = $fairValue['source'];
                $latestTrade->save();

                $result['new_fair_value'] = $fairValue['value'];
                $result['source'] = $fairValue['source'];
                $result['updated'] = true;

                // Log the update with detailed audit trail
                $gainLoss = ($fairValue['value'] - ($oldFairValue ?? $fairValue['value']));
                $gainLossPercent = $oldFairValue > 0 
                    ? (($gainLoss / $oldFairValue) * 100) 
                    : 0;

                $latestTrade->logActivity('fair_value_updated',
                    "Fair Value Updated for Investment {$investment->instrument_code}",
                    [
                        'previous_value' => number_format($oldFairValue ?? 0, 2),
                        'new_value' => number_format($fairValue['value'], 2),
                        'previous_source' => $oldSource ?? 'N/A',
                        'new_source' => $fairValue['source'],
                        'gain_loss' => number_format($gainLoss, 2),
                        'gain_loss_percent' => number_format($gainLossPercent, 2) . '%',
                        'update_date' => now()->format('Y-m-d H:i:s'),
                    ]
                );

                Log::info('Fair value updated for investment', [
                    'investment_id' => $investment->id,
                    'instrument_code' => $investment->instrument_code,
                    'previous_value' => $result['previous_fair_value'],
                    'new_value' => $fairValue['value'],
                    'source' => $fairValue['source'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update fair value', [
                'investment_id' => $investment->id,
                'error' => $e->getMessage(),
            ]);
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Get bond fair value from market data or BOT feed
     */
    protected function getBondFairValue(InvestmentMaster $investment, InvestmentTrade $trade, array $marketData): ?array
    {
        // If manual market data provided, use it
        if (!empty($marketData['price'])) {
            $faceValue = $trade->trade_units;
            $price = $marketData['price'] / 100; // Convert percentage to decimal
            return [
                'value' => $faceValue * $price,
                'source' => $marketData['source'] ?? 'Manual Entry',
            ];
        }

        // Try to fetch from BOT API (placeholder - implement actual API integration)
        // For now, use a mock or return null
        try {
            // Example: $response = Http::get('https://api.bot.go.tz/bonds/' . $investment->isin);
            // This would need actual BOT API integration
            
            // For demonstration, calculate based on yield curve
            if ($trade->yield_to_maturity) {
                $faceValue = $trade->trade_units;
                $couponRate = $trade->coupon_rate ?? $investment->coupon_rate ?? 0;
                $ytm = $trade->yield_to_maturity;
                
                // Simple bond pricing (simplified - actual would use DCF)
                $price = $this->calculateBondPrice($faceValue, $couponRate, $ytm, $investment->maturity_date);
                
                return [
                    'value' => $price,
                    'source' => 'Yield Curve Calculation',
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch bond fair value from market', [
                'investment_id' => $investment->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Get T-Bill fair value
     */
    protected function getTBillFairValue(InvestmentMaster $investment, InvestmentTrade $trade, array $marketData): ?array
    {
        if (!empty($marketData['discount_rate'])) {
            $faceValue = $trade->trade_units;
            $daysToMaturity = now()->diffInDays($investment->maturity_date);
            $discountRate = $marketData['discount_rate'] / 100;
            
            $price = $faceValue * (1 - ($discountRate * $daysToMaturity / 365));
            
            return [
                'value' => $price,
                'source' => $marketData['source'] ?? 'BOT Discount Curve',
            ];
        }

        return null;
    }

    /**
     * Get equity fair value from DSE or market feed
     */
    protected function getEquityFairValue(InvestmentMaster $investment, InvestmentTrade $trade, array $marketData): ?array
    {
        if (!empty($marketData['price_per_share'])) {
            $shares = $trade->number_of_shares ?? $trade->trade_units;
            return [
                'value' => $shares * $marketData['price_per_share'],
                'source' => $marketData['source'] ?? 'DSE Market Feed',
            ];
        }

        // Try to fetch from DSE API (placeholder)
        // Example: $response = Http::get('https://api.dse.co.tz/stocks/' . $trade->ticker_symbol);
        
        return null;
    }

    /**
     * Get Money Market Fund fair value from NAV
     */
    protected function getMmfFairValue(InvestmentMaster $investment, InvestmentTrade $trade, array $marketData): ?array
    {
        if (!empty($marketData['nav_price'])) {
            $units = $trade->units_purchased ?? $trade->trade_units;
            return [
                'value' => $units * $marketData['nav_price'],
                'source' => $marketData['source'] ?? 'Fund Manager NAV',
            ];
        }

        return null;
    }

    /**
     * Get Fixed Deposit fair value (typically amortized cost)
     */
    protected function getFixedDepositFairValue(InvestmentMaster $investment, InvestmentTrade $trade, array $marketData): ?array
    {
        // Fixed deposits typically use amortized cost
        // Fair value would be based on early withdrawal penalty and current rates
        if (!empty($marketData['fair_value'])) {
            return [
                'value' => $marketData['fair_value'],
                'source' => $marketData['source'] ?? 'Bank Valuation',
            ];
        }

        return null;
    }

    /**
     * Calculate bond price using simplified DCF
     */
    protected function calculateBondPrice(float $faceValue, float $couponRate, float $ytm, $maturityDate): float
    {
        $yearsToMaturity = now()->diffInDays($maturityDate) / 365;
        $couponPayment = ($faceValue * $couponRate / 100) / 2; // Semi-annual
        $periods = $yearsToMaturity * 2;
        $discountRate = $ytm / 100 / 2;

        if ($discountRate == 0) {
            return $faceValue + ($couponPayment * $periods);
        }

        $pvCoupons = $couponPayment * ((1 - pow(1 + $discountRate, -$periods)) / $discountRate);
        $pvFace = $faceValue / pow(1 + $discountRate, $periods);

        return $pvCoupons + $pvFace;
    }

    /**
     * Update fair values for all investments in a company
     */
    public function updateAllFairValues(int $companyId, array $marketDataByInstrument = []): array
    {
        $investments = InvestmentMaster::where('company_id', $companyId)
            ->whereIn('status', ['ACTIVE'])
            ->whereIn('accounting_class', ['FVOCI', 'FVPL'])
            ->get();

        $results = [];
        $updated = 0;
        $failed = 0;

        foreach ($investments as $investment) {
            $marketData = $marketDataByInstrument[$investment->instrument_code] ?? [];
            $result = $this->updateFairValue($investment, $marketData);
            
            $results[] = $result;
            if ($result['updated']) {
                $updated++;
            } else {
                $failed++;
            }
        }

        return [
            'total' => count($investments),
            'updated' => $updated,
            'failed' => $failed,
            'results' => $results,
        ];
    }
}

