<?php

namespace App\Services\Investment;

use App\Models\Investment\InvestmentMaster;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * EIR (Effective Interest Rate) Calculator Service
 * 
 * Implements numerical methods (Newton-Raphson) to solve for EIR
 * Supports various cash flow patterns and day count conventions
 */
class EirCalculatorService
{
    const MAX_ITERATIONS = 100;
    const TOLERANCE = 1e-10;
    const MIN_RATE = -0.99; // -99% minimum
    const MAX_RATE = 10.0;  // 1000% maximum

    /**
     * Calculate EIR for an investment
     * 
     * @param InvestmentMaster $investment
     * @param array $cashFlows Array of ['date' => Carbon, 'amount' => decimal]
     * @param float|null $initialGuess Initial guess for EIR (optional)
     * @return array ['eir' => float, 'iterations' => int, 'converged' => bool]
     */
    public function calculateEir(InvestmentMaster $investment, array $cashFlows, ?float $initialGuess = null): array
    {
        if (empty($cashFlows)) {
            throw new Exception('Cash flows cannot be empty');
        }

        // Sort cash flows by date
        usort($cashFlows, function($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        // Get initial investment amount (first cash flow should be negative - purchase)
        $initialAmount = abs($cashFlows[0]['amount']);
        if ($cashFlows[0]['amount'] > 0) {
            throw new Exception('First cash flow should be negative (purchase amount)');
        }

        // Use initial guess or calculate from coupon rate
        $guess = $initialGuess ?? $this->getInitialGuess($investment, $cashFlows);

        // Solve using Newton-Raphson method
        return $this->solveNewtonRaphson($cashFlows, $initialAmount, $guess, $investment->day_count);
    }

    /**
     * Get initial guess for EIR
     */
    protected function getInitialGuess(InvestmentMaster $investment, array $cashFlows): float
    {
        // If coupon rate is available, use it as initial guess
        if ($investment->coupon_rate) {
            return $investment->coupon_rate / 100.0;
        }

        // Otherwise, estimate from cash flows
        $totalReturn = 0;
        $initialAmount = abs($cashFlows[0]['amount']);
        $firstDate = $cashFlows[0]['date'];
        $lastDate = end($cashFlows)['date'];

        foreach ($cashFlows as $flow) {
            if ($flow['amount'] > 0) {
                $totalReturn += $flow['amount'];
            }
        }

        $days = $firstDate->diffInDays($lastDate);
        if ($days > 0) {
            $annualizedReturn = ($totalReturn / $initialAmount) * (365.0 / $days);
            return max(0.01, min(0.5, $annualizedReturn)); // Clamp between 1% and 50%
        }

        return 0.05; // Default 5%
    }

    /**
     * Solve for EIR using Newton-Raphson method
     */
    protected function solveNewtonRaphson(array $cashFlows, float $initialAmount, float $initialGuess, string $dayCount): array
    {
        $rate = max(self::MIN_RATE, min(self::MAX_RATE, $initialGuess));
        $iterations = 0;
        $converged = false;

        for ($i = 0; $i < self::MAX_ITERATIONS; $i++) {
            $iterations = $i + 1;

            // Calculate NPV and its derivative
            $npv = $this->calculateNPV($cashFlows, $rate, $dayCount);
            $npvDerivative = $this->calculateNPVDerivative($cashFlows, $rate, $dayCount);

            // Check convergence
            if (abs($npv) < self::TOLERANCE) {
                $converged = true;
                break;
            }

            // Avoid division by zero
            if (abs($npvDerivative) < 1e-15) {
                // Fallback to bisection if derivative is too small
                return $this->solveBisection($cashFlows, $initialAmount, $dayCount);
            }

            // Newton-Raphson update: rate = rate - NPV / NPV'
            $newRate = $rate - ($npv / $npvDerivative);

            // Clamp rate to valid range
            $newRate = max(self::MIN_RATE, min(self::MAX_RATE, $newRate));

            // Check if rate changed significantly
            if (abs($newRate - $rate) < self::TOLERANCE) {
                $converged = true;
                $rate = $newRate;
                break;
            }

            $rate = $newRate;
        }

        return [
            'eir' => $rate * 100, // Convert to percentage
            'iterations' => $iterations,
            'converged' => $converged,
            'method' => 'newton-raphson',
        ];
    }

    /**
     * Calculate Net Present Value for given rate
     */
    protected function calculateNPV(array $cashFlows, float $rate, string $dayCount): float
    {
        $npv = 0;
        $baseDate = $cashFlows[0]['date'];

        foreach ($cashFlows as $flow) {
            $days = $this->getDaysBetween($baseDate, $flow['date'], $dayCount);
            $years = $this->daysToYears($days, $dayCount);
            
            // NPV = CF / (1 + r)^t
            if ($years == 0) {
                $npv += $flow['amount'];
            } else {
                $discountFactor = pow(1 + $rate, $years);
                $npv += $flow['amount'] / $discountFactor;
            }
        }

        return $npv;
    }

    /**
     * Calculate derivative of NPV with respect to rate
     */
    protected function calculateNPVDerivative(array $cashFlows, float $rate, string $dayCount): float
    {
        $derivative = 0;
        $baseDate = $cashFlows[0]['date'];

        foreach ($cashFlows as $flow) {
            $days = $this->getDaysBetween($baseDate, $flow['date'], $dayCount);
            $years = $this->daysToYears($days, $dayCount);
            
            if ($years > 0) {
                // d/dr [CF / (1+r)^t] = -t * CF / (1+r)^(t+1)
                $discountFactor = pow(1 + $rate, $years + 1);
                $derivative -= $years * $flow['amount'] / $discountFactor;
            }
        }

        return $derivative;
    }

    /**
     * Fallback: Solve using bisection method
     */
    protected function solveBisection(array $cashFlows, float $initialAmount, string $dayCount): array
    {
        $low = self::MIN_RATE;
        $high = self::MAX_RATE;
        $iterations = 0;
        $converged = false;

        // Ensure NPV(low) < 0 and NPV(high) > 0
        $npvLow = $this->calculateNPV($cashFlows, $low, $dayCount);
        $npvHigh = $this->calculateNPV($cashFlows, $high, $dayCount);

        if ($npvLow * $npvHigh > 0) {
            // Same sign - no root in range, try to find valid range
            for ($i = 0; $i < 20; $i++) {
                $testRate = ($low + $high) / 2;
                $npvTest = $this->calculateNPV($cashFlows, $testRate, $dayCount);
                
                if ($npvTest * $npvLow < 0) {
                    $high = $testRate;
                    $npvHigh = $npvTest;
                } else {
                    $low = $testRate;
                    $npvLow = $npvTest;
                }
            }
        }

        for ($i = 0; $i < self::MAX_ITERATIONS; $i++) {
            $iterations = $i + 1;
            $mid = ($low + $high) / 2;
            $npvMid = $this->calculateNPV($cashFlows, $mid, $dayCount);

            if (abs($npvMid) < self::TOLERANCE || abs($high - $low) < self::TOLERANCE) {
                $converged = true;
                return [
                    'eir' => $mid * 100,
                    'iterations' => $iterations,
                    'converged' => $converged,
                    'method' => 'bisection',
                ];
            }

            if ($npvMid * $npvLow < 0) {
                $high = $mid;
                $npvHigh = $npvMid;
            } else {
                $low = $mid;
                $npvLow = $npvMid;
            }
        }

        return [
            'eir' => (($low + $high) / 2) * 100,
            'iterations' => $iterations,
            'converged' => false,
            'method' => 'bisection',
        ];
    }

    /**
     * Get days between two dates based on day count convention
     */
    protected function getDaysBetween($date1, $date2, string $dayCount): int
    {
        switch ($dayCount) {
            case 'ACT/365':
            case 'ACT/360':
            case '30/360':
                return $date1->diffInDays($date2);
            default:
                return $date1->diffInDays($date2);
        }
    }

    /**
     * Convert days to years based on day count convention
     */
    protected function daysToYears(int $days, string $dayCount): float
    {
        switch ($dayCount) {
            case 'ACT/365':
                return $days / 365.0;
            case 'ACT/360':
                return $days / 360.0;
            case '30/360':
                return $days / 360.0; // Simplified
            default:
                return $days / 365.0;
        }
    }

    /**
     * Generate cash flows from investment data
     */
    public function generateCashFlows(InvestmentMaster $investment): array
    {
        $cashFlows = [];
        $purchaseDate = $investment->purchase_date;
        $maturityDate = $investment->maturity_date;

        // Initial cash flow (purchase - negative)
        $cashFlows[] = [
            'date' => $purchaseDate,
            'amount' => -$investment->nominal_amount, // Negative for purchase
        ];

        // Coupon payments
        if ($investment->coupon_schedule && is_array($investment->coupon_schedule)) {
            foreach ($investment->coupon_schedule as $coupon) {
                $cashFlows[] = [
                    'date' => \Carbon\Carbon::parse($coupon['date']),
                    'amount' => $coupon['amount'],
                ];
            }
        } elseif ($investment->coupon_rate && $investment->coupon_freq) {
            // Generate coupon schedule if not provided
            $couponAmount = $investment->nominal_amount * ($investment->coupon_rate / 100.0) / $investment->coupon_freq;
            $periodsPerYear = $investment->coupon_freq;
            $totalPeriods = (int)($purchaseDate->diffInDays($maturityDate) / (365 / $periodsPerYear));

            $currentDate = clone $purchaseDate;
            for ($i = 1; $i <= $totalPeriods && $currentDate < $maturityDate; $i++) {
                $currentDate->addMonths(12 / $periodsPerYear);
                if ($currentDate <= $maturityDate) {
                    $cashFlows[] = [
                        'date' => clone $currentDate,
                        'amount' => $couponAmount,
                    ];
                }
            }
        }

        // Final cash flow (maturity - principal + final coupon if applicable)
        $finalAmount = $investment->nominal_amount;
        $cashFlows[] = [
            'date' => $maturityDate,
            'amount' => $finalAmount,
        ];

        return $cashFlows;
    }

    /**
     * Recalculate EIR for investment
     */
    public function recalculateEir(InvestmentMaster $investment): array
    {
        $cashFlows = $this->generateCashFlows($investment);
        $result = $this->calculateEir($investment, $cashFlows);

        // Update investment with new EIR
        $investment->eir_rate = $result['eir'];
        $investment->save();

        Log::info('EIR recalculated for investment', [
            'investment_id' => $investment->id,
            'instrument_code' => $investment->instrument_code,
            'eir' => $result['eir'],
            'iterations' => $result['iterations'],
            'converged' => $result['converged'],
        ]);

        return $result;
    }
}

