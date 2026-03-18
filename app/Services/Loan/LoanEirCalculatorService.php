<?php

namespace App\Services\Loan;

use App\Models\Loan\Loan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Effective Interest Rate (EIR) Calculator for Loans
 * 
 * Implements IFRS 9 requirement to calculate EIR that discounts
 * all expected future cash flows to the initial amortised cost.
 * 
 * Uses Newton-Raphson method to solve for EIR.
 */
class LoanEirCalculatorService
{
    const MAX_ITERATIONS = 100;
    const TOLERANCE = 1e-10; // For rate change detection
    const NPV_TOLERANCE = 0.01; // For NPV convergence (allow 0.01 difference)
    const MIN_RATE = -0.99; // -99% minimum
    const MAX_RATE = 10.0;  // 1000% maximum

    /**
     * Calculate EIR for a loan based on cash flows using IRR (monthly periods)
     * 
     * FIX 1: EIR MUST BE SOLVED USING IRR (NOT CONFIGURED)
     * Formula: PV = Σ(PMT / (1+r)^t) for t=1 to n
     * Where r is monthly EIR, n is number of periods
     * 
     * @param Loan $loan
     * @param array $cashFlows Array of ['date' => Carbon, 'amount' => decimal] (positive = cash received, negative = cash paid)
     * @param float $initialAmortisedCost Initial carrying amount (cash received - fees)
     * @param float|null $initialGuess Initial guess for EIR (optional)
     * @return array ['eir' => float (annual %), 'monthly_eir' => float (monthly %), 'iterations' => int, 'converged' => bool]
     */
    public function calculateEir(Loan $loan, array $cashFlows, float $initialAmortisedCost, ?float $initialGuess = null): array
    {
        if (empty($cashFlows)) {
            throw new Exception('Cash flows cannot be empty');
        }

        // Sort cash flows by date
        usort($cashFlows, function($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        // First cash flow should be positive (loan received)
        if ($cashFlows[0]['amount'] <= 0) {
            throw new Exception('First cash flow should be positive (loan amount received)');
        }

        // Extract payment amount (should be constant for equal installments)
        // Skip first cash flow (disbursement) and get payment amounts
        $paymentAmounts = [];
        for ($i = 1; $i < count($cashFlows); $i++) {
            $paymentAmounts[] = abs($cashFlows[$i]['amount']); // Make positive
        }
        
        $numPeriods = count($paymentAmounts);
        if ($numPeriods == 0) {
            throw new Exception('No payment cash flows found');
        }

        // Use initial guess: convert annual nominal rate to monthly
        // If nominal rate is 12% annual, monthly guess is ~1% (12%/12)
        // But with capitalized fees, EIR will be higher, so use a better initial guess
        $annualNominalRate = $loan->interest_rate / 100.0; // Convert to decimal
        $baseMonthlyGuess = $annualNominalRate / 12.0; // Base guess from nominal rate
        
        // Adjust initial guess based on fees (fees increase EIR)
        // If fees are 4% of principal (200k/5M), EIR will be roughly 4% higher
        $feesRatio = 0;
        if ($loan->capitalise_fees && $loan->fees_amount > 0 && $loan->principal_amount > 0) {
            $feesRatio = $loan->fees_amount / $loan->principal_amount;
        }
        // Adjust guess upward to account for fees (rough estimate: fees add ~feesRatio to EIR)
        $adjustedMonthlyGuess = $baseMonthlyGuess * (1 + $feesRatio * 0.5); // Conservative adjustment
        
        $monthlyGuess = $initialGuess ?? $adjustedMonthlyGuess;
        
        Log::info('EIR Solver: Initial guess calculation', [
            'loan_id' => $loan->id,
            'nominal_rate' => $loan->interest_rate,
            'base_monthly_guess' => $baseMonthlyGuess,
            'fees_ratio' => $feesRatio,
            'adjusted_monthly_guess' => $adjustedMonthlyGuess,
            'initial_amortised_cost' => $initialAmortisedCost,
            'payment_amount' => $paymentAmounts[0] ?? null,
            'num_periods' => $numPeriods,
        ]);

        // Solve for monthly EIR using Newton-Raphson
        // Formula: PV = PMT * (1 - (1+r)^-n) / r
        // Rearranged: PV - PMT * (1 - (1+r)^-n) / r = 0
        $result = $this->solveMonthlyEir($initialAmortisedCost, $paymentAmounts, $numPeriods, $monthlyGuess);
        
        // Convert monthly EIR to annual EIR: (1 + monthly)^12 - 1
        $monthlyEirDecimal = $result['monthly_eir'];
        $annualEirDecimal = pow(1 + $monthlyEirDecimal, 12) - 1;
        
        return [
            'eir' => $annualEirDecimal * 100, // Annual EIR as percentage
            'monthly_eir' => $monthlyEirDecimal * 100, // Monthly EIR as percentage
            'iterations' => $result['iterations'],
            'converged' => $result['converged'],
            'method' => 'irr-monthly',
        ];
    }

    /**
     * Generate cash flows from loan schedule (contractual)
     * 
     * IMPORTANT: For EIR calculation, the initial cash flow MUST equal the initial amortised cost.
     * 
     * IFRS 9 Logic:
     * - Initial Amortised Cost = Cash Received - Transaction Costs (capitalized fees + direct costs)
     * - Transaction costs reduce the carrying amount, making EIR higher than nominal rate
     * 
     * Components considered for EIR:
     * 1. Principal amount (cash received)
     * 2. Capitalized fees (reduce initial AC, increase EIR)
     * 3. Directly attributable costs (reduce initial AC, increase EIR)
     * 4. Non-capitalized fees (paid separately, don't affect loan AC or EIR)
     * 
     * @param Loan $loan
     * @param array $cashSchedule Array of cash schedule rows
     * @return array Array of ['date' => Carbon, 'amount' => decimal]
     */
    public function generateCashFlowsFromSchedule(Loan $loan, array $cashSchedule): array
    {
        $cashFlows = [];
        
        // Initial cash flow: This MUST equal the initial amortised cost
        // Initial AC = Principal - Capitalized Fees - Direct Costs
        // This is what we actually "carry" the loan at, which is less than principal
        // when fees/costs are capitalized, making EIR higher than nominal rate
        $disbursementDate = $loan->disbursement_date ?? $loan->start_date ?? Carbon::now();
        $cashReceived = $loan->disbursed_amount ?? $loan->principal_amount;
        
        // Subtract capitalized fees (these reduce initial AC, making EIR higher)
        if ($loan->capitalise_fees && $loan->fees_amount > 0) {
            $cashReceived -= $loan->fees_amount;
        }
        
        // Subtract directly attributable costs (these also reduce initial AC)
        if ($loan->directly_attributable_costs > 0) {
            $cashReceived -= $loan->directly_attributable_costs;
        }
        
        // NOTE: Non-capitalized fees are paid separately and don't affect
        // the loan's carrying amount, so they don't affect EIR calculation
        
        $cashFlows[] = [
            'date' => $disbursementDate,
            'amount' => $cashReceived, // This equals initial amortised cost
        ];

        // Future cash flows: payments (negative = cash paid)
        foreach ($cashSchedule as $schedule) {
            $cashFlows[] = [
                'date' => Carbon::parse($schedule['due_date']),
                'amount' => -$schedule['installment_amount'], // Negative = cash paid
            ];
        }

        return $cashFlows;
    }

    /**
     * Solve for monthly EIR using Newton-Raphson method
     * 
     * Formula: PV = PMT * (1 - (1+r)^-n) / r
     * Where: PV = initial amortised cost, PMT = payment amount, r = monthly EIR, n = number of periods
     * 
     * Rearranged: f(r) = PV - PMT * (1 - (1+r)^-n) / r = 0
     */
    protected function solveMonthlyEir(float $initialAmount, array $paymentAmounts, int $numPeriods, float $initialGuess): array
    {
        // For equal payments, use the first payment amount
        // If payments vary, we'll need to sum them differently
        $paymentAmount = $paymentAmounts[0];
        
        // Check if all payments are equal (within tolerance)
        $allEqual = true;
        foreach ($paymentAmounts as $pmt) {
            if (abs($pmt - $paymentAmount) > 0.01) {
                $allEqual = false;
                break;
            }
        }
        
        if ($allEqual) {
            // Equal payments: use annuity formula
            return $this->solveMonthlyEirEqualPayments($initialAmount, $paymentAmount, $numPeriods, $initialGuess);
        } else {
            // Unequal payments: use general NPV formula
            return $this->solveMonthlyEirUnequalPayments($initialAmount, $paymentAmounts, $numPeriods, $initialGuess);
        }
    }
    
    /**
     * Solve for monthly EIR with equal payments (annuity formula)
     */
    protected function solveMonthlyEirEqualPayments(float $initialAmount, float $paymentAmount, int $numPeriods, float $initialGuess): array
    {
        // Ensure initial guess is reasonable (between 0.1% and 5%)
        $rate = max(0.001, min(0.05, $initialGuess));
        $iterations = 0;
        $converged = false;
        
        // Calculate initial NPV to determine search direction
        $initialNPV = $this->calculateNPVEqualPayments($initialAmount, $paymentAmount, $numPeriods, $rate);
        
        Log::info('EIR Solver: Starting Newton-Raphson', [
            'initial_guess' => $rate,
            'initial_guess_percent' => $rate * 100,
            'initial_npv' => $initialNPV,
            'pv' => $initialAmount,
            'pmt' => $paymentAmount,
            'n' => $numPeriods,
        ]);

        for ($i = 0; $i < self::MAX_ITERATIONS; $i++) {
            $iterations = $i + 1;

            // Calculate NPV: PV - PMT * (1 - (1+r)^-n) / r
            $npv = $this->calculateNPVEqualPayments($initialAmount, $paymentAmount, $numPeriods, $rate);
            
            // Calculate derivative
            $npvDerivative = $this->calculateNPVDerivativeEqualPayments($initialAmount, $paymentAmount, $numPeriods, $rate);

            // Check convergence - NPV must be very close to zero (not just rate change)
            if (abs($npv) < self::NPV_TOLERANCE) {
                $converged = true;
                break;
            }

            // Avoid division by zero or very small derivative
            if (abs($npvDerivative) < 1e-15) {
                Log::warning('EIR Solver: Derivative too small, switching to bisection', [
                    'rate' => $rate,
                    'rate_percent' => $rate * 100,
                    'npv' => $npv,
                    'derivative' => $npvDerivative,
                ]);
                // Fallback to bisection
                return $this->solveMonthlyEirBisection($initialAmount, $paymentAmount, $numPeriods, $rate);
            }

            // Newton-Raphson update: rate = rate - NPV / NPV'
            $newRate = $rate - ($npv / $npvDerivative);

            // Clamp rate to valid range (0.1% to 5%)
            $newRate = max(0.001, min(0.05, $newRate));
            
            // Debug logging for all iterations (use INFO so it shows in logs)
            Log::info('EIR Solver: Newton-Raphson iteration', [
                'iteration' => $iterations,
                'current_rate' => $rate,
                'current_rate_percent' => $rate * 100,
                'new_rate' => $newRate,
                'new_rate_percent' => $newRate * 100,
                'npv' => $npv,
                'npv_derivative' => $npvDerivative,
                'rate_change' => abs($newRate - $rate),
            ]);

            // Check if rate changed significantly
            // Don't declare convergence just because rate stopped changing - NPV must be near zero
            if (abs($newRate - $rate) < self::TOLERANCE) {
                // Rate stopped changing, but check if NPV is actually near zero
                if (abs($npv) < self::NPV_TOLERANCE) {
                    $converged = true;
                    $rate = $newRate;
                    break;
                } else {
                    // Rate stopped changing but NPV is not zero - this is a problem
                    // Try bisection to find the correct rate
                    Log::warning('EIR Solver: Rate stopped changing but NPV not zero, switching to bisection', [
                        'rate' => $rate,
                        'rate_percent' => $rate * 100,
                        'npv' => $npv,
                        'new_rate' => $newRate,
                    ]);
                    return $this->solveMonthlyEirBisection($initialAmount, $paymentAmount, $numPeriods, $rate);
                }
            }
            
            // Also check if we're oscillating (rate going back and forth)
            if ($iterations > 10 && abs($newRate - $rate) < 0.0001 && abs($npv) > 100) {
                Log::warning('EIR Solver: Possible oscillation detected, switching to bisection', [
                    'rate' => $rate,
                    'npv' => $npv,
                ]);
                return $this->solveMonthlyEirBisection($initialAmount, $paymentAmount, $numPeriods, $rate);
            }

            $rate = $newRate;
        }

        // Validate the result
        $finalNPV = $this->calculateNPVEqualPayments($initialAmount, $paymentAmount, $numPeriods, $rate);
        
        // If not converged or NPV is too large, try bisection
        if (!$converged || abs($finalNPV) > self::NPV_TOLERANCE) {
            Log::warning('EIR Solver: Newton-Raphson did not converge properly, trying bisection', [
                'final_rate' => $rate,
                'final_rate_percent' => $rate * 100,
                'final_npv' => $finalNPV,
                'converged' => $converged,
                'iterations' => $iterations,
            ]);
            return $this->solveMonthlyEirBisection($initialAmount, $paymentAmount, $numPeriods, $rate);
        }
        
        Log::info('EIR Solver: Final result (Newton-Raphson)', [
            'monthly_eir' => $rate,
            'monthly_eir_percent' => $rate * 100,
            'iterations' => $iterations,
            'converged' => $converged,
            'final_npv' => $finalNPV,
            'npv_tolerance' => self::TOLERANCE,
        ]);
        
        return [
            'monthly_eir' => $rate,
            'iterations' => $iterations,
            'converged' => $converged,
        ];
    }
    
    /**
     * Solve for monthly EIR using bisection method (fallback)
     */
    protected function solveMonthlyEirBisection(float $initialAmount, float $paymentAmount, int $numPeriods, float $initialGuess): array
    {
        $low = 0.001;  // 0.1%
        $high = 0.05;  // 5%
        
        // Adjust bounds based on initial guess
        if ($initialGuess > 0.001 && $initialGuess < 0.05) {
            $low = max(0.001, $initialGuess * 0.5);
            $high = min(0.05, $initialGuess * 2.0);
        }
        
        $npvLow = $this->calculateNPVEqualPayments($initialAmount, $paymentAmount, $numPeriods, $low);
        $npvHigh = $this->calculateNPVEqualPayments($initialAmount, $paymentAmount, $numPeriods, $high);
        
        // Ensure we have opposite signs
        if ($npvLow * $npvHigh > 0) {
            // Same sign, expand range
            $low = 0.001;
            $high = 0.05;
            $npvLow = $this->calculateNPVEqualPayments($initialAmount, $paymentAmount, $numPeriods, $low);
            $npvHigh = $this->calculateNPVEqualPayments($initialAmount, $paymentAmount, $numPeriods, $high);
        }
        
        $iterations = 0;
        $converged = false;
        $rate = $initialGuess;
        
        for ($i = 0; $i < self::MAX_ITERATIONS; $i++) {
            $iterations = $i + 1;
            $mid = ($low + $high) / 2;
            $npvMid = $this->calculateNPVEqualPayments($initialAmount, $paymentAmount, $numPeriods, $mid);
            
            if (abs($npvMid) < self::NPV_TOLERANCE || abs($high - $low) < self::TOLERANCE) {
                $converged = true;
                $rate = $mid;
                break;
            }
            
            if ($npvMid * $npvLow < 0) {
                $high = $mid;
                $npvHigh = $npvMid;
            } else {
                $low = $mid;
                $npvLow = $npvMid;
            }
        }
        
        $finalNPV = $this->calculateNPVEqualPayments($initialAmount, $paymentAmount, $numPeriods, $rate);
        
        Log::info('EIR Solver: Final result (Bisection)', [
            'monthly_eir' => $rate,
            'monthly_eir_percent' => $rate * 100,
            'iterations' => $iterations,
            'converged' => $converged,
            'final_npv' => $finalNPV,
        ]);
        
        return [
            'monthly_eir' => $rate,
            'iterations' => $iterations,
            'converged' => $converged,
        ];
    }
    
    /**
     * Calculate NPV for equal payments: PV - PMT * (1 - (1+r)^-n) / r
     */
    protected function calculateNPVEqualPayments(float $initialAmount, float $paymentAmount, int $numPeriods, float $monthlyRate): float
    {
        if ($monthlyRate == 0) {
            // Special case: r = 0, formula becomes PV - PMT * n
            return $initialAmount - ($paymentAmount * $numPeriods);
        }
        
        // Standard annuity formula: PV = PMT * (1 - (1+r)^-n) / r
        $discountFactor = pow(1 + $monthlyRate, -$numPeriods);
        $annuityFactor = (1 - $discountFactor) / $monthlyRate;
        $presentValue = $paymentAmount * $annuityFactor;
        
        return $initialAmount - $presentValue;
    }
    
    /**
     * Calculate derivative of NPV for equal payments
     */
    protected function calculateNPVDerivativeEqualPayments(float $initialAmount, float $paymentAmount, int $numPeriods, float $monthlyRate): float
    {
        if ($monthlyRate == 0) {
            return 0;
        }
        
        // d/dr [PMT * (1 - (1+r)^-n) / r]
        // = PMT * [n * (1+r)^-(n+1) * r - (1 - (1+r)^-n)] / r^2
        $discountFactor = pow(1 + $monthlyRate, -$numPeriods);
        $discountFactorN1 = pow(1 + $monthlyRate, -($numPeriods + 1));
        
        $numerator = $numPeriods * $discountFactorN1 * $monthlyRate - (1 - $discountFactor);
        $derivative = -$paymentAmount * $numerator / ($monthlyRate * $monthlyRate);
        
        return $derivative;
    }
    
    /**
     * Solve for monthly EIR with unequal payments
     */
    protected function solveMonthlyEirUnequalPayments(float $initialAmount, array $paymentAmounts, int $numPeriods, float $initialGuess): array
    {
        $rate = max(0.0001, min(1.0, $initialGuess));
        $iterations = 0;
        $converged = false;

        for ($i = 0; $i < self::MAX_ITERATIONS; $i++) {
            $iterations = $i + 1;

            // Calculate NPV: PV - Σ(PMT_t / (1+r)^t)
            $npv = $this->calculateNPVUnequalPayments($initialAmount, $paymentAmounts, $numPeriods, $rate);
            
            // Calculate derivative
            $npvDerivative = $this->calculateNPVDerivativeUnequalPayments($initialAmount, $paymentAmounts, $numPeriods, $rate);

            // Check convergence - NPV must be near zero
            if (abs($npv) < self::NPV_TOLERANCE) {
                $converged = true;
                break;
            }

            // Avoid division by zero
            if (abs($npvDerivative) < 1e-15) {
                // Fallback to bisection
                return $this->solveMonthlyEirBisection($initialAmount, $paymentAmounts[0] ?? 0, $numPeriods, $rate);
            }

            // Newton-Raphson update
            $newRate = $rate - ($npv / $npvDerivative);
            $newRate = max(0.001, min(0.05, $newRate));

            if (abs($newRate - $rate) < self::TOLERANCE) {
                // Check if NPV is actually near zero
                if (abs($npv) < self::NPV_TOLERANCE) {
                    $converged = true;
                    $rate = $newRate;
                    break;
                } else {
                    // Fallback to bisection
                    return $this->solveMonthlyEirBisection($initialAmount, $paymentAmounts[0] ?? 0, $numPeriods, $rate);
                }
            }

            $rate = $newRate;
        }

        // Validate result
        $finalNPV = $this->calculateNPVUnequalPayments($initialAmount, $paymentAmounts, $numPeriods, $rate);
        if (abs($finalNPV) > self::NPV_TOLERANCE) {
            // Fallback to bisection
            return $this->solveMonthlyEirBisection($initialAmount, $paymentAmounts[0] ?? 0, $numPeriods, $rate);
        }

        return [
            'monthly_eir' => $rate,
            'iterations' => $iterations,
            'converged' => $converged,
        ];
    }
    
    /**
     * Calculate NPV for unequal payments: PV - Σ(PMT_t / (1+r)^t)
     */
    protected function calculateNPVUnequalPayments(float $initialAmount, array $paymentAmounts, int $numPeriods, float $monthlyRate): float
    {
        $presentValue = 0;
        for ($t = 1; $t <= $numPeriods; $t++) {
            $pmt = $paymentAmounts[$t - 1] ?? 0;
            $discountFactor = pow(1 + $monthlyRate, $t);
            $presentValue += $pmt / $discountFactor;
        }
        
        return $initialAmount - $presentValue;
    }
    
    /**
     * Calculate derivative of NPV for unequal payments
     */
    protected function calculateNPVDerivativeUnequalPayments(float $initialAmount, array $paymentAmounts, int $numPeriods, float $monthlyRate): float
    {
        $derivative = 0;
        for ($t = 1; $t <= $numPeriods; $t++) {
            $pmt = $paymentAmounts[$t - 1] ?? 0;
            // d/dr [PMT / (1+r)^t] = -t * PMT / (1+r)^(t+1)
            $discountFactor = pow(1 + $monthlyRate, $t + 1);
            $derivative -= $t * $pmt / $discountFactor;
        }
        
        return $derivative;
    }

    /**
     * Calculate Net Present Value for given rate
     * NPV = Initial Amount + Sum(CF / (1+r)^t)
     */
    protected function calculateNPV(array $cashFlows, float $rate, string $dayCount, float $initialAmount): float
    {
        $npv = -$initialAmount; // Negative because it's cash paid out
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
        $npvLow = $this->calculateNPV($cashFlows, $low, $dayCount, $initialAmount);
        $npvHigh = $this->calculateNPV($cashFlows, $high, $dayCount, $initialAmount);

        if ($npvLow * $npvHigh > 0) {
            // Same sign - no root in range, try to find valid range
            for ($i = 0; $i < 20; $i++) {
                $testRate = ($low + $high) / 2;
                $npvTest = $this->calculateNPV($cashFlows, $testRate, $dayCount, $initialAmount);
                
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
            $npvMid = $this->calculateNPV($cashFlows, $mid, $dayCount, $initialAmount);

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
            case 'actual/365':
            case 'actual/360':
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
            case 'actual/365':
                return $days / 365.0;
            case 'actual/360':
                return $days / 360.0;
            case '30/360':
                return $days / 360.0;
            default:
                return $days / 365.0;
        }
    }
}

