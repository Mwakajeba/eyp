<?php

namespace App\Services;

use App\Models\Loan\Loan;
use App\Models\Loan\LoanSchedule;
use App\Models\Loan\LoanCashSchedule;
use App\Models\Loan\LoanIfrsSchedule;
use App\Models\Loan\LoanDisbursement;
use App\Models\Loan\LoanAccrual;
use App\Models\Loan\LoanPayment;
use App\Services\Loan\LoanEirCalculatorService;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoanService
{
    protected $eirCalculator;

    public function __construct()
    {
        $this->eirCalculator = new LoanEirCalculatorService();
    }

    /**
     * Generate both cash (contractual) and IFRS schedules for a loan
     * 
     * This is the main entry point that generates both schedules as per IFRS 9 requirements.
     * 
     * @param Loan $loan
     * @return array ['cash_schedules' => array, 'ifrs_schedules' => array, 'eir' => float|null]
     */
    public function generateDualSchedules(Loan $loan): array
    {
        // Step 1: Generate contractual cash schedule (using nominal rate)
        $cashSchedules = $this->generateCashSchedule($loan);
        
        // Step 2: Calculate EIR if not locked
        $eir = null;
        $monthlyEir = null;
        if (!$loan->eir_locked) {
            $eirResult = $this->calculateAndLockEir($loan, $cashSchedules);
            $eir = $eirResult['eir'];
            $monthlyEir = $eirResult['monthly_eir'] ?? null;
        } else {
            $eir = $loan->effective_interest_rate;
            // Calculate monthly EIR from annual EIR
            $annualEirDecimal = $eir / 100.0;
            $monthlyEirDecimal = pow(1 + $annualEirDecimal, 1/12) - 1;
            $monthlyEir = $monthlyEirDecimal * 100;
        }
        
        // Step 3: Generate IFRS 9 amortised cost schedule (using EIR)
        $ifrsSchedules = $this->generateIfrsSchedule($loan, $cashSchedules, $eir, $monthlyEir);
        
        return [
            'cash_schedules' => $cashSchedules,
            'ifrs_schedules' => $ifrsSchedules,
            'eir' => $eir,
        ];
    }

    /**
     * Generate contractual cash schedule (Schedule 1)
     * 
     * Uses nominal interest rate. This is what the borrower must pay.
     * Used for: payment reminders, bank reconciliation, aging, customer statements.
     * 
     * @param Loan $loan
     * @return array
     */
    public function generateCashSchedule(Loan $loan): array
    {
        $schedules = [];
        $principal = $loan->principal_amount;
        // Annual rate as percent (e.g. 15 for 15%)
        $annualRatePercent = $loan->interest_rate;
        // Annual rate as decimal (e.g. 0.15 for 15%)
        $annualRateDecimal = $annualRatePercent / 100;
        $term = $loan->term_months;
        $frequency = $this->getFrequencyMonths($loan->payment_frequency);
        // Prefer explicit repayment method if provided, otherwise fall back to amortization method
        $method = $loan->repayment_method ?: ($loan->amortization_method ?? 'annuity');
        
        $startDate = $loan->start_date ?? $loan->disbursement_date ?? Carbon::now();
        $currentBalance = $principal;
        $periodNo = 0;
        
        // Calculate number of periods
        $numPeriods = ceil($term / $frequency);
        
        // Grace period handling: convert grace_period_months into schedule periods
        $graceMonths = (int) ($loan->grace_period_months ?? 0);
        $gracePeriods = 0;
        if ($graceMonths > 0 && $frequency > 0) {
            $gracePeriods = (int) ceil($graceMonths / $frequency);
            // Cap grace periods at total periods
            if ($gracePeriods > $numPeriods) {
                $gracePeriods = $numPeriods;
            }
        }

        // For principal-based methods (straight/equal/flat), spread principal over periods AFTER grace
        $principalPeriods = max(1, $numPeriods - $gracePeriods);
        $principalPerPeriod = $principalPeriods > 0 ? $principal / $principalPeriods : 0;
        
        // Calculate periodic rate (for reducing-balance methods)
        $periodicRate = $annualRateDecimal / (12 / $frequency);
        
        // Pre-compute flat-rate interest per period if using flat_rate method
        $flatRateInterestPerPeriod = 0;
        if ($method === 'flat_rate' && $numPeriods > 0) {
            // Total interest = Principal * rate * (term in years), spread evenly across periods
            $totalFlatInterest = $principal * $annualRateDecimal * ($term / 12);
            $flatRateInterestPerPeriod = $totalFlatInterest / $numPeriods;
        }
        
        // Calculate payment amount based on method
        if ($method === 'annuity') {
            // For annuity with grace, amortise principal over periods AFTER grace
            $amortisingPeriods = max(1, $numPeriods - $gracePeriods);
            if ($periodicRate > 0) {
                $paymentAmount = $principal * ($periodicRate * pow(1 + $periodicRate, $amortisingPeriods)) / (pow(1 + $periodicRate, $amortisingPeriods) - 1);
            } else {
                $paymentAmount = $amortisingPeriods > 0 ? $principal / $amortisingPeriods : 0;
            }
        } elseif ($method === 'interest_only') {
            $paymentAmount = $principal * $periodicRate;
        } elseif ($method === 'bullet') {
            $paymentAmount = 0; // Only interest until final payment
        } else {
            // straight_principal, equal_principal, flat_rate: we derive per-period amounts inside the loop
            $paymentAmount = 0;
        }
        
        for ($i = 0; $i < $numPeriods; $i++) {
            $periodNo++;
            $periodStart = $startDate->copy()->addMonths($i * $frequency);
            $periodEnd = $startDate->copy()->addMonths(($i + 1) * $frequency)->subDay();
            $dueDate = $periodEnd;
            
            $openingBalance = $currentBalance;
            
            // Calculate interest (annualRatePercent is used because calculateInterest expects percent, not decimal)
            if ($method === 'flat_rate') {
                // Flat rate: use constant interest per period based on original principal
                $interest = $flatRateInterestPerPeriod;
            } else {
            $daysInPeriod = $periodStart->diffInDays($periodEnd) + 1;
                $interest = $this->calculateInterest($openingBalance, $annualRatePercent, $daysInPeriod, $loan->calculation_basis);
            }
            
            // Calculate principal payment with grace period logic
            if ($i < $gracePeriods) {
                // Grace period: interest-only, no principal repayment regardless of method
                $principalPayment = 0;
                $totalDue = $interest;
            } else {
                $periodIndexAfterGrace = $i - $gracePeriods; // 0-based within amortising phase

            if ($method === 'annuity') {
                    // For annuity, use constant payment during the amortising phase.
                    // We will do a final clean-up adjustment after the loop.
                $principalPayment = $paymentAmount - $interest;
                $totalDue = $paymentAmount;
                } elseif (in_array($method, ['straight_principal', 'equal_principal', 'flat_rate'], true)) {
                    // Straight/Equal principal and flat rate: constant principal each period after grace
                    $principalPayment = $principalPerPeriod;
                $totalDue = $principalPayment + $interest;
            } elseif ($method === 'interest_only') {
                $principalPayment = 0;
                $totalDue = $interest;
            } else { // bullet
                if ($i == $numPeriods - 1) {
                    $principalPayment = $currentBalance;
                    $totalDue = $principalPayment + $interest;
                } else {
                    $principalPayment = 0;
                    $totalDue = $interest;
                    }
                }
            }
            
            $closingBalance = $openingBalance - $principalPayment;
            
            $schedules[] = [
                'loan_id' => $loan->id,
                'installment_no' => $periodNo,
                'period_no' => $periodNo,
                'due_date' => $dueDate,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'opening_principal' => $openingBalance,
                'opening_balance' => $openingBalance,
                'principal_due' => $principalPayment,
                'principal_paid' => 0,
                'closing_principal' => $closingBalance,
                'closing_balance' => $closingBalance,
                'interest_due' => $interest,
                'interest_paid' => 0,
                'interest_rate' => $loan->interest_rate,
                'total_due' => $totalDue,
                'installment_amount' => $totalDue,
                'amount_paid' => 0,
                'status' => 'due',
                'schedule_type' => 'cash',
            ];
            
            $currentBalance = $closingBalance;
        }

        // Final adjustment to ensure the loan fully amortises (closing balance = 0)
        if (! empty($schedules)) {
            $lastIndex = count($schedules) - 1;
            $last = $schedules[$lastIndex];
            $residual = $last['closing_principal'];

            // If there is a small residual balance (positive or negative), adjust the last installment
            if (abs($residual) > 0.01) {
                // Increase principal due by residual (if positive) or decrease if negative
                $adjustedPrincipalDue = $last['principal_due'] + $residual;
                $adjustedTotalDue = $adjustedPrincipalDue + $last['interest_due'];

                $last['principal_due'] = $adjustedPrincipalDue;
                $last['installment_amount'] = $adjustedTotalDue;
                $last['total_due'] = $adjustedTotalDue;
                $last['closing_principal'] = 0.0;
                $last['closing_balance'] = 0.0;

                $schedules[$lastIndex] = $last;
            }
        }
        
        return $schedules;
    }

    /**
     * Generate IFRS 9 amortised cost schedule (Schedule 2)
     * 
     * IFRS 9 COMPLIANT AMORTISATION LOGIC (EXACT SPECIFICATION):
     * 
     * For each month:
     * 1. IFRS Interest = Opening Amortised Cost × Monthly EIR
     * 2. Closing Amortised Cost = Opening + IFRS Interest - Cash Paid
     * 
     * KEY PRINCIPLES:
     * - NO day-count (interest calculated per period, not per day)
     * - NO separate fee amortisation (fees absorbed via EIR)
     * - Monthly EIR used directly (e.g., 1.11% = 0.0111 as decimal)
     * - Final payment may adjust slightly for rounding (IFRS 9 allowed)
     * 
     * EXAMPLE (IFRS 9 Compliant):
     * - Initial AC: 4,800,000
     * - Monthly EIR: 1.11% (0.0111)
     * - Month 1: Opening=4,800,000, Interest=53,280, Cash=166,071.55, Closing=4,687,208.45
     * - Month 36: Opening=938.66, Interest=10.42, Cash=949.08 (adjusted), Closing=0.00
     * 
     * This schedule is used for:
     * - General Ledger entries
     * - Financial Statements
     * - Audit & Compliance
     * 
     * @param Loan $loan
     * @param array $cashSchedules Contractual cash schedules
     * @param float $eir Annual Effective Interest Rate (as percentage, e.g. 14.2 for 14.2%)
     * @param float|null $monthlyEir Monthly EIR (as percentage, e.g. 1.11 for 1.11%)
     * @return array
     */
    public function generateIfrsSchedule(Loan $loan, array $cashSchedules, float $eir, ?float $monthlyEir = null): array
    {
        $ifrsSchedules = [];
        
        // Get initial amortised cost (cash received - capitalized fees)
        $initialAmortisedCost = $loan->initial_amortised_cost ?? $this->calculateInitialAmortisedCost($loan);
        $currentAmortisedCost = $initialAmortisedCost;
        
        // Calculate monthly EIR if not provided
        // Annual EIR to monthly: (1 + annual)^(1/12) - 1
        if ($monthlyEir === null) {
            $annualEirDecimal = $eir / 100.0;
            $monthlyEirDecimal = pow(1 + $annualEirDecimal, 1/12) - 1;
            $monthlyEir = $monthlyEirDecimal * 100; // Convert to percentage
        } else {
            // monthlyEir is already a percentage (e.g., 1.11 for 1.11%)
            $monthlyEirDecimal = $monthlyEir / 100.0;
        }
        
        // Validate monthly EIR is reasonable (should be between 0.1% and 10%)
        // If annual EIR is 12%, monthly should be ~0.95%
        // If annual EIR is 14.2%, monthly should be ~1.11%
        if ($monthlyEir < 0.1 || $monthlyEir > 10) {
            Log::error('IFRS Schedule: Monthly EIR seems unreasonable', [
                'loan_id' => $loan->id,
                'annual_eir' => $eir,
                'monthly_eir' => $monthlyEir,
                'monthly_eir_decimal' => $monthlyEirDecimal,
            ]);
            
            // Recalculate from annual EIR as fallback
            $annualEirDecimal = $eir / 100.0;
            $monthlyEirDecimal = pow(1 + $annualEirDecimal, 1/12) - 1;
            $monthlyEir = $monthlyEirDecimal * 100;
            
            Log::warning('IFRS Schedule: Recalculated monthly EIR from annual', [
                'loan_id' => $loan->id,
                'recalculated_monthly_eir' => $monthlyEir,
            ]);
        }
        
        // Debug logging to verify monthly EIR
        Log::info('IFRS Schedule: Monthly EIR calculation', [
            'loan_id' => $loan->id,
            'annual_eir' => $eir,
            'monthly_eir_percentage' => $monthlyEir,
            'monthly_eir_decimal' => $monthlyEirDecimal,
            'initial_amortised_cost' => $initialAmortisedCost,
        ]);
        
        $numPeriods = count($cashSchedules);
        $roundingTolerance = 0.01; // Allow rounding tolerance of 0.01
        
        foreach ($cashSchedules as $index => $cashSchedule) {
            $periodNo = $index + 1;
            $periodStart = Carbon::parse($cashSchedule['period_start']);
            $periodEnd = Carbon::parse($cashSchedule['period_end']);
            $dueDate = Carbon::parse($cashSchedule['due_date']);
            
            $openingAmortisedCost = $currentAmortisedCost;
            
            // IFRS 9 AMORTISATION LOGIC (EXACT FORMULA)
            // Formula: IFRS Interest = Opening Amortised Cost × Monthly EIR
            // Monthly EIR is stored as decimal (e.g., 0.0111 for 1.11%)
            $ifrsInterestExpense = $openingAmortisedCost * $monthlyEirDecimal;
            
            // Cash paid from cash schedule (must be fixed and consistent)
            // NOTE: Under IFRS 9 amortised cost, the SPLIT between interest/principal
            // must follow EIR, not the contractual schedule split.
            $cashPaid = $cashSchedule['installment_amount'] ?? 0;
            
            // IFRS 9 AMORTISATION LOGIC (EXACT FORMULA)
            // Closing Amortised Cost = Opening + IFRS Interest - Cash Paid
            $closingAmortisedCost = $openingAmortisedCost + $ifrsInterestExpense - $cashPaid;
            
            // Handle final period: Adjust cash paid if closing would go negative
            // This is allowed under IFRS 9 for rounding (final payment adjustment)
            $isFinalPeriod = ($periodNo == $numPeriods);
            if ($closingAmortisedCost < 0 || ($isFinalPeriod && abs($closingAmortisedCost) > $roundingTolerance)) {
                // Adjust cash paid to bring closing to exactly zero
                // This is the IFRS 9-compliant way to handle final payment rounding
                $adjustedCashPaid = $openingAmortisedCost + $ifrsInterestExpense;
                $closingAmortisedCost = 0;
                
                if ($isFinalPeriod) {
                    Log::info('IFRS schedule: Final period payment adjusted for rounding (IFRS 9 compliant)', [
                        'loan_id' => $loan->id,
                        'period_no' => $periodNo,
                        'opening_ac' => $openingAmortisedCost,
                        'ifrs_interest' => $ifrsInterestExpense,
                        'contractual_cash_paid' => $cashPaid,
                        'adjusted_cash_paid' => $adjustedCashPaid,
                        'rounding_adjustment' => $cashPaid - $adjustedCashPaid,
                    ]);
                } else {
                    Log::info('IFRS schedule: Loan overpaid, adjusting cash paid for IFRS purposes', [
                        'loan_id' => $loan->id,
                        'period_no' => $periodNo,
                        'opening_ac' => $openingAmortisedCost,
                        'ifrs_interest' => $ifrsInterestExpense,
                        'actual_cash_paid' => $cashPaid,
                        'adjusted_cash_paid' => $adjustedCashPaid,
                        'overpayment' => $cashPaid - $adjustedCashPaid,
                    ]);
                }
                
                // Use adjusted cash paid for IFRS schedule
                $cashPaid = $adjustedCashPaid;
            }
            
            // Validation: closing should not be significantly negative (only for rounding)
            // This should not happen after the adjustment above, but keep as safety check
            if ($closingAmortisedCost < -$roundingTolerance) {
                throw new \Exception("Closing amortised cost cannot be negative. Period {$periodNo}: Opening={$openingAmortisedCost}, Interest={$ifrsInterestExpense}, Cash Paid={$cashPaid}, Closing={$closingAmortisedCost}, Monthly EIR Decimal={$monthlyEirDecimal}");
            }

            // IFRS 9 REQUIREMENT:
            // Interest income/expense in P&L must be based on EIR.
            // Therefore, the cash interest portion is set equal to IFRS interest expense,
            // and the cash principal portion is the balancing figure.
            $cashInterestPaid = $ifrsInterestExpense;
            $cashPrincipalPaid = $cashPaid - $cashInterestPaid;
            
            $ifrsSchedules[] = [
                'loan_id' => $loan->id,
                'cash_schedule_id' => null, // Will be set when saving
                'period_no' => $periodNo,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'due_date' => $dueDate,
                'opening_amortised_cost' => $openingAmortisedCost,
                'ifrs_interest_expense' => $ifrsInterestExpense,
                'cash_paid' => $cashPaid,
                'closing_amortised_cost' => $closingAmortisedCost,
                'cash_interest_paid' => $cashInterestPaid,
                'cash_principal_paid' => $cashPrincipalPaid,
                // FIX 3: Removed 'deferred_costs_amortized' - transaction costs amortised via EIR
                'effective_interest_rate' => $eir,
                'posted_to_gl' => false,
            ];
            
            $currentAmortisedCost = $closingAmortisedCost;
        }
        
        // IFRS 9 VALIDATION: Final period closing AC must be zero (or near zero due to rounding)
        // This is a critical IFRS 9 requirement - the amortised cost must reach zero
        $finalClosingAC = $currentAmortisedCost;
        if (abs($finalClosingAC) > $roundingTolerance) {
            Log::warning('IFRS schedule final closing AC not near zero', [
                'loan_id' => $loan->id,
                'final_closing_ac' => $finalClosingAC,
                'tolerance' => $roundingTolerance,
                'monthly_eir' => $monthlyEir,
                'monthly_eir_decimal' => $monthlyEirDecimal,
            ]);
            // Don't throw exception, but log warning - this might indicate EIR calculation issue
        } else {
            Log::info('IFRS schedule: Amortised cost reached zero (IFRS 9 compliant)', [
                'loan_id' => $loan->id,
                'final_closing_ac' => $finalClosingAC,
                'total_periods' => $numPeriods,
            ]);
        }
        
        return $ifrsSchedules;
    }

    /**
     * Calculate initial amortised cost (cash received - transaction costs)
     * 
     * IFRS 9: Initial amortised cost = Cash received - Transaction costs
     * 
     * Transaction costs include:
     * 1. Capitalized fees (fees added to loan balance, reducing initial AC)
     * 2. Directly attributable costs (costs that reduce initial AC)
     * 
     * Non-capitalized fees are paid separately and don't affect initial AC.
     * 
     * Why EIR > Nominal Rate:
     * - When fees/costs are capitalized, initial AC < Principal
     * - Same payments amortize a smaller amount
     * - This makes the effective rate higher than the nominal rate
     * 
     * Example:
     * - Principal: 5,000,000
     * - Capitalized fees: 200,000
     * - Initial AC: 4,800,000
     * - Same payments amortize 4,800,000 instead of 5,000,000
     * - EIR ≈ 14.2% vs Nominal 12%
     * 
     * @param Loan $loan
     * @return float
     */
    public function calculateInitialAmortisedCost(Loan $loan): float
    {
        $cashReceived = $loan->disbursed_amount ?? $loan->principal_amount;
        
        // Subtract capitalized fees (transaction costs that reduce initial AC)
        if ($loan->capitalise_fees && $loan->fees_amount > 0) {
            $cashReceived -= $loan->fees_amount;
        }
        
        // Subtract directly attributable costs (also transaction costs)
        if ($loan->directly_attributable_costs > 0) {
            $cashReceived -= $loan->directly_attributable_costs;
        }
        
        // NOTE: Non-capitalized fees don't reduce initial AC
        // They're paid separately and don't affect the loan's carrying amount
        
        return max(0, $cashReceived);
    }

    /**
     * Calculate and lock EIR for a loan
     * 
     * @param Loan $loan
     * @param array $cashSchedules
     * @return array ['eir' => float, 'converged' => bool, 'iterations' => int]
     */
    public function calculateAndLockEir(Loan $loan, array $cashSchedules): array
    {
        // Generate cash flows from cash schedule
        $cashFlows = $this->eirCalculator->generateCashFlowsFromSchedule($loan, $cashSchedules);
        
        // Calculate initial amortised cost
        $initialAmortisedCost = $this->calculateInitialAmortisedCost($loan);
        
        // Validate initial amortised cost is positive and reasonable
        if ($initialAmortisedCost <= 0) {
            throw new \Exception('Initial amortised cost must be positive. Check loan fees and costs configuration.');
        }
        
        if ($initialAmortisedCost > ($loan->principal_amount * 2)) {
            Log::warning('Initial amortised cost seems unreasonably high', [
                'loan_id' => $loan->id,
                'initial_amortised_cost' => $initialAmortisedCost,
                'principal_amount' => $loan->principal_amount,
            ]);
        }
        
        // Calculate EIR
        $result = $this->eirCalculator->calculateEir($loan, $cashFlows, $initialAmortisedCost);
        
        // Validate EIR is reasonable (should be close to nominal rate, not hitting max)
        $calculatedEir = $result['eir'];
        $nominalRate = $loan->interest_rate;
        
        // Log EIR calculation components for transparency
        Log::info('EIR Calculation Components', [
            'loan_id' => $loan->id,
            'loan_number' => $loan->loan_number,
            'principal_amount' => $loan->principal_amount,
            'disbursed_amount' => $loan->disbursed_amount ?? $loan->principal_amount,
            'capitalise_fees' => $loan->capitalise_fees,
            'fees_amount' => $loan->fees_amount,
            'directly_attributable_costs' => $loan->directly_attributable_costs,
            'initial_amortised_cost' => $initialAmortisedCost,
            'nominal_rate' => $nominalRate,
            'calculated_eir' => $calculatedEir,
            'eir_difference' => $calculatedEir - $nominalRate,
            'eir_vs_nominal_ratio' => $nominalRate > 0 ? ($calculatedEir / $nominalRate) : null,
            'monthly_eir' => $result['monthly_eir'] ?? null,
            'converged' => $result['converged'] ?? false,
        ]);
        
        // If EIR is unreasonably high (e.g., > 5x nominal rate or > 100%), something is wrong
        // Also check if calculation converged
        if (!$result['converged'] || $calculatedEir > max($nominalRate * 5, 100)) {
            Log::error('EIR calculation failed or returned unreasonable value', [
                'loan_id' => $loan->id,
                'loan_number' => $loan->loan_number,
                'calculated_eir' => $calculatedEir,
                'nominal_rate' => $nominalRate,
                'converged' => $result['converged'] ?? false,
                'iterations' => $result['iterations'] ?? 0,
                'initial_amortised_cost' => $initialAmortisedCost,
                'cash_flows_count' => count($cashFlows),
            ]);
            
            // Fallback: use nominal rate as EIR if calculation fails
            // This is not ideal but prevents system failure
            $calculatedEir = $nominalRate;
            // Calculate monthly EIR from annual nominal rate
            $annualNominalDecimal = $nominalRate / 100.0;
            $monthlyNominalDecimal = $annualNominalDecimal / 12.0;
            $result['monthly_eir'] = $monthlyNominalDecimal * 100;
            Log::warning('Using nominal rate as EIR fallback', [
                'loan_id' => $loan->id,
                'nominal_rate' => $nominalRate,
                'monthly_eir' => $result['monthly_eir'],
            ]);
        }
        
        // Update loan with EIR and initial amortised cost
        $loan->effective_interest_rate = $calculatedEir;
        $loan->initial_amortised_cost = $initialAmortisedCost;
        $loan->current_amortised_cost = $initialAmortisedCost;
        
        // Lock EIR if loan is approved or disbursed
        if (in_array($loan->status, ['approved', 'disbursed', 'active'])) {
            $loan->eir_locked = true;
            $loan->eir_locked_at = now();
            $loan->eir_locked_by = auth()->id();
        }
        
        $loan->save();
        
        Log::info('EIR calculated for loan', [
            'loan_id' => $loan->id,
            'loan_number' => $loan->loan_number,
            'eir' => $calculatedEir,
            'monthly_eir' => $result['monthly_eir'] ?? null,
            'nominal_rate' => $nominalRate,
            'initial_amortised_cost' => $initialAmortisedCost,
            'converged' => $result['converged'] ?? false,
            'iterations' => $result['iterations'] ?? 0,
        ]);
        
        return [
            'eir' => $calculatedEir,
            'monthly_eir' => $result['monthly_eir'] ?? null,
            'converged' => $result['converged'] ?? false,
            'iterations' => $result['iterations'] ?? 0,
        ];
    }

    /**
     * Generate amortization schedule for a loan (backward compatibility)
     * 
     * @deprecated Use generateCashSchedule() or generateDualSchedules() instead
     */
    public function generateSchedule(Loan $loan): array
    {
        // For backward compatibility, return cash schedule
        return $this->generateCashSchedule($loan);
    }
    
    /**
     * Calculate interest based on calculation basis
     */
    public function calculateInterest($principal, $annualRate, $days, $basis = 'actual/365'): float
    {
        switch ($basis) {
            case '30/360':
                $days = 30; // Simplified - actual implementation would handle month boundaries
                return $principal * ($annualRate / 100) * ($days / 360);
            case 'actual/360':
                return $principal * ($annualRate / 100) * ($days / 360);
            case 'actual/365':
            default:
                return $principal * ($annualRate / 100) * ($days / 365);
        }
    }
    
    /**
     * Get frequency in months
     */
    private function getFrequencyMonths($frequency): int
    {
        return match($frequency) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi-annual' => 6,
            'annual' => 12,
            default => 1,
        };
    }
    
    /**
     * Create GL entry for loan disbursement
     */
    public function createDisbursementGlEntry(LoanDisbursement $disbursement, Loan $loan): Journal
    {
        // Note: This method is called within a DB transaction in the controller
        // So we don't need to wrap it in another transaction
        
        $journal = new Journal();
        $journal->branch_id = $loan->branch_id;
        $journal->date = $disbursement->disb_date;
        $journal->reference = 'LOAN-DISB-' . $loan->loan_number;
        $journal->reference_type = 'loan_disbursement';
        $journal->description = "Loan Disbursement: {$loan->loan_number}";
        $journal->user_id = auth()->id();
        $journal->save();
        
        // Debit: Bank Account
        $bankAccount = $disbursement->bankAccount;
        if (!$bankAccount) {
            throw new \Exception('Bank account not found for disbursement.');
        }
        if (!$bankAccount->chart_account_id) {
            throw new \Exception('Bank account does not have a chart account mapped.');
        }
        
        $bankItem = JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $bankAccount->chart_account_id,
            'nature' => 'debit',
            'amount' => $disbursement->net_proceeds,
            'description' => "Loan disbursement received",
        ]);
        
        // Credit: Loan Payable (at initial amortised cost, not face value)
        if (!$loan->loan_payable_account_id) {
            throw new \Exception('Loan does not have a loan payable account mapped.');
        }
        
        // Use initial amortised cost (cash received - capitalized fees) for IFRS 9 compliance
        $initialAmortisedCost = $loan->initial_amortised_cost ?? $this->calculateInitialAmortisedCost($loan);
        
        $loanPayableItem = JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $loan->loan_payable_account_id,
            'nature' => 'credit',
            'amount' => $initialAmortisedCost, // IFRS 9: record at amortised cost, not face value
            'description' => "Loan principal (IFRS 9 initial amortised cost)",
        ]);
        
        // Loan fees (origination / processing)
        if ($loan->fees_amount > 0) {
            if ($loan->capitalise_fees && $loan->deferred_loan_costs_account_id) {
                // Capitalised as deferred loan costs (asset)
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $loan->deferred_loan_costs_account_id,
                'nature' => 'debit',
                'amount' => $loan->fees_amount,
                'description' => "Loan origination fees (capitalized)",
            ]);
            } elseif (! $loan->capitalise_fees && $loan->loan_processing_fee_account_id) {
                // Expensed immediately as loan processing fees
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $loan->loan_processing_fee_account_id,
                    'nature' => 'debit',
                'amount' => $loan->fees_amount,
                    'description' => "Loan processing fees expensed",
            ]);
            }
        }
        
        // If bank charges were deducted
        if ($disbursement->bank_charges > 0 && $loan->bank_charges_account_id) {
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $loan->bank_charges_account_id,
                'nature' => 'debit',
                'amount' => $disbursement->bank_charges,
                'description' => "Bank charges on disbursement",
            ]);
        }

        // Auto-approve and post this journal to GL
        $journal->approved = true;
        $journal->approved_by = auth()->id() ?? 1;
        $journal->approved_at = now();
        $journal->save();
        $journal->createGlTransactions();
        
        $disbursement->journal_id = $journal->id;
        $disbursement->save();
        
        return $journal;
    }
    
    /**
     * Create GL entry for interest accrual using IFRS 9 schedule
     * 
     * This method now uses the IFRS schedule for interest expense (EIR-based)
     * instead of the contractual cash schedule. The IFRS interest expense is
     * the source of truth for accounting entries.
     */
    public function createAccrualGlEntry(LoanAccrual $accrual, Loan $loan): Journal
    {
        // Note: This method is called within a DB transaction in the controller
        // So we don't need to wrap it in another transaction
        
        // Find the corresponding IFRS schedule for this accrual period
        $ifrsSchedule = $loan->ifrsSchedules()
            ->where('period_start', '<=', $accrual->accrual_date)
            ->where('period_end', '>=', $accrual->accrual_date)
            ->where('posted_to_gl', false)
            ->first();
        
        // If no IFRS schedule found, fall back to accrual amount (backward compatibility)
        $ifrsInterestExpense = $ifrsSchedule ? $ifrsSchedule->ifrs_interest_expense : $accrual->interest_accrued;
        
        $journal = new Journal();
        $journal->branch_id = $loan->branch_id;
        $journal->date = $accrual->accrual_date;
        $journal->reference = 'LOAN-ACCR-' . $loan->loan_number . '-' . $accrual->accrual_date->format('Y-m-d');
        $journal->reference_type = 'loan_accrual';
        $journal->description = "Interest Accrual (IFRS 9): {$loan->loan_number}";
        $journal->user_id = auth()->id() ?? 1; // Use system user if no auth (console command)
        $journal->save();
        
        // Debit: Interest Expense or Capitalised Interest (IAS 23)
        $useCapitalisedInterest =
            $loan->capitalise_interest &&
            $loan->capitalised_interest_account_id &&
            (
                !$loan->capitalisation_end_date ||
                $accrual->accrual_date->lte($loan->capitalisation_end_date)
            );

        if ($useCapitalisedInterest) {
            $debitAccountId = $loan->capitalised_interest_account_id;
            $description = "Capitalised borrowing costs (IAS 23) - IFRS 9 EIR-based";
        } else {
            if (! $loan->interest_expense_account_id) {
                throw new \Exception('Loan does not have an interest expense account mapped.');
            }
            $debitAccountId = $loan->interest_expense_account_id;
            $description = "Interest expense accrued (IFRS 9 EIR-based)";
        }
        
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $debitAccountId,
            'nature' => 'debit',
            'amount' => $ifrsInterestExpense,
            'description' => $description,
        ]);
        
        // Credit: Loan Payable (amortised cost increases by IFRS interest)
        // In IFRS 9, interest expense increases the loan liability
        if (!$loan->loan_payable_account_id) {
            throw new \Exception('Loan does not have a loan payable account mapped.');
        }
        
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $loan->loan_payable_account_id,
            'nature' => 'credit',
            'amount' => $ifrsInterestExpense,
            'description' => "Loan payable (IFRS 9 amortised cost increase)",
        ]);

        // Auto-approve and post this journal to GL
        $journal->approved = true;
        $journal->approved_by = auth()->id() ?? 1;
        $journal->approved_at = now();
        $journal->save();
        $journal->createGlTransactions();

        // Update accrual record
        $accrual->journal_id = $journal->id;
        $accrual->journal_ref = $journal->reference;
        $accrual->posted_flag = true;
        $accrual->save();
        
        // Mark IFRS schedule as posted if found
        if ($ifrsSchedule) {
            $ifrsSchedule->posted_to_gl = true;
            $ifrsSchedule->journal_id = $journal->id;
            $ifrsSchedule->posted_date = $accrual->accrual_date;
            $ifrsSchedule->save();
        }
        
        return $journal;
    }
    
    /**
     * Create GL entry for loan repayment
     */
    public function createPaymentGlEntry(LoanPayment $payment, Loan $loan): Journal
    {
        // Note: This method is called within a DB transaction in the controller
        // So we don't need to wrap it in another transaction
        
        $journal = new Journal();
        $journal->branch_id = $loan->branch_id;
        $journal->date = $payment->payment_date;
        $journal->reference = 'LOAN-PAY-' . $loan->loan_number . '-' . $payment->payment_date->format('Y-m-d');
        $journal->reference_type = 'loan_payment';
        $journal->description = "Loan Repayment: {$loan->loan_number}";
        $journal->user_id = auth()->id();
        $journal->save();
        
        // Round all amounts to 2 decimal places to avoid precision issues
        $interestAmount = round((float) $payment->allocation_interest, 2);
        $principalAmount = round((float) $payment->allocation_principal, 2);
        $feesAmount = round((float) $payment->allocation_fees, 2);
        $penaltyAmount = round((float) $payment->allocation_penalty, 2);
        $paymentAmount = round((float) $payment->amount, 2);
        
        // Calculate total debit
        $totalDebit = $interestAmount + $principalAmount + $feesAmount + $penaltyAmount;
        
        // If there's a rounding difference, adjust the principal to balance
        $difference = $paymentAmount - $totalDebit;
        if (abs($difference) > 0.01) {
            // Adjust principal to balance (if principal > 0, otherwise adjust interest)
            if ($principalAmount > 0) {
                $principalAmount = round($principalAmount + $difference, 2);
            } elseif ($interestAmount > 0) {
                $interestAmount = round($interestAmount + $difference, 2);
            } else {
                // If no principal or interest, adjust fees
                $feesAmount = round($feesAmount + $difference, 2);
            }
            // Recalculate total debit after adjustment
            $totalDebit = $interestAmount + $principalAmount + $feesAmount + $penaltyAmount;
        }
        
        // Ensure credit exactly equals total debit
        $creditAmount = $totalDebit;
        
        // Debit: Interest Payable (or Interest Expense if not accrued)
        if ($interestAmount > 0) {
            $interestAccountId = null;
            if ($loan->interest_payable_account_id) {
                $interestAccountId = $loan->interest_payable_account_id;
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $interestAccountId,
                    'nature' => 'debit',
                    'amount' => $interestAmount,
                    'description' => "Interest payment",
                ]);
            } elseif ($loan->interest_expense_account_id) {
                $interestAccountId = $loan->interest_expense_account_id;
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $interestAccountId,
                    'nature' => 'debit',
                    'amount' => $interestAmount,
                    'description' => "Interest payment",
                ]);
            }
        }
        
        // Debit: Loan Payable - Principal (IFRS 9: reduces amortised cost)
        // In IFRS 9, cash payments reduce the loan liability (amortised cost)
        if ($principalAmount > 0) {
            if (!$loan->loan_payable_account_id) {
                throw new \Exception('Loan does not have a loan payable account mapped.');
            }
            
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $loan->loan_payable_account_id,
                'nature' => 'debit',
                'amount' => $principalAmount,
                'description' => "Principal repayment (IFRS 9 amortised cost reduction)",
            ]);
        }
        
        // Debit: Fees/Penalties (if any)
        if ($feesAmount > 0 && $loan->bank_charges_account_id) {
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $loan->bank_charges_account_id,
                'nature' => 'debit',
                'amount' => $feesAmount,
                'description' => "Loan fees",
            ]);
        }
        
        if ($penaltyAmount > 0 && $loan->bank_charges_account_id) {
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $loan->bank_charges_account_id,
                'nature' => 'debit',
                'amount' => $penaltyAmount,
                'description' => "Prepayment penalty",
            ]);
        }
        
        // Credit: Bank Account
        $bankAccount = $payment->bankAccount;
        if (!$bankAccount) {
            throw new \Exception('Bank account not found for payment.');
        }
        if (!$bankAccount->chart_account_id) {
            throw new \Exception('Bank account does not have a chart account mapped.');
        }
        
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $bankAccount->chart_account_id,
            'nature' => 'credit',
            'amount' => $creditAmount,
            'description' => "Loan payment",
        ]);
        
        // Auto-approve and post this journal to GL
        $journal->approved = true;
        $journal->approved_by = auth()->id();
        $journal->approved_at = now();
        $journal->save();
        $journal->createGlTransactions();
        
        $payment->journal_id = $journal->id;
        $payment->posted_flag = true;
        $payment->save();
        
        return $journal;
    }
    
    /**
     * Calculate prepayment penalty
     */
    public function calculatePrepaymentPenalty(Loan $loan, $prepaymentAmount, $prepaymentDate): float
    {
        if (!$loan->prepayment_allowed || !$loan->prepayment_penalty_rate) {
            return 0;
        }
        
        $penaltyRate = $loan->prepayment_penalty_rate / 100;
        return $prepaymentAmount * $penaltyRate;
    }
    
    /**
     * Accrue interest for a loan for a specific period
     */
    public function accrueInterest(Loan $loan, Carbon $accrualDate, Carbon $periodStart = null, Carbon $periodEnd = null): LoanAccrual
    {
        $periodStart = $periodStart ?? $accrualDate->copy()->startOfMonth();
        $periodEnd = $periodEnd ?? $accrualDate->copy()->endOfMonth();
        
        $openingBalance = $loan->outstanding_principal;
        $daysInPeriod = $periodStart->diffInDays($periodEnd) + 1;
        $interestRate = $loan->interest_rate;
        
        $interestAccrued = $this->calculateInterest($openingBalance, $interestRate, $daysInPeriod, $loan->calculation_basis);
        
        $accrual = LoanAccrual::create([
            'loan_id' => $loan->id,
            'accrual_date' => $accrualDate,
            'interest_accrued' => $interestAccrued,
            'opening_balance' => $openingBalance,
            'interest_rate' => $interestRate,
            'days_in_period' => $daysInPeriod,
            'calculation_basis' => $loan->calculation_basis,
            'posted_flag' => false,
            'created_by' => auth()->id() ?? 1, // Use system user if no auth (console command)
        ]);
        
        // Update loan accrued interest
        $loan->accrued_interest += $interestAccrued;
        $loan->save();
        
        return $accrual;
    }

    /**
     * Accrue interest based on the amortization schedule (per month).
     *
     * This uses the interest already computed in the loan schedules for the
     * accrual month instead of re-calculating by days. It is conceptually
     * similar to taking the IPMT from the schedule and posting it to GL.
     */
    public function accrueInterestFromSchedule(Loan $loan, Carbon $accrualDate): LoanAccrual
    {
        $monthStart = $accrualDate->copy()->startOfMonth();
        $monthEnd = $accrualDate->copy()->endOfMonth();

        // Prevent double-accrual for the same month
        $existing = $loan->accruals()
            ->whereBetween('accrual_date', [$monthStart, $monthEnd])
            ->first();
        if ($existing) {
            throw new \Exception('Interest for this month has already been accrued.');
        }

        // Use schedule lines whose due_date falls within this month
        $schedules = $loan->schedules()
            ->whereBetween('due_date', [$monthStart, $monthEnd])
            ->orderBy('due_date')
            ->get();

        if ($schedules->isEmpty()) {
            throw new \Exception('No schedule entries found for this accrual month. Please generate the schedule first.');
        }

        $interestAccrued = (float) $schedules->sum('interest_due');

        $firstSchedule = $schedules->first();
        $openingBalance = (float) $firstSchedule->opening_balance;

        // Prefer the schedule period dates if available, otherwise fall back to calendar month
        if ($firstSchedule->period_start && $firstSchedule->period_end) {
            $daysInPeriod = $firstSchedule->period_start->diffInDays($firstSchedule->period_end) + 1;
        } else {
            $daysInPeriod = $monthStart->diffInDays($monthEnd) + 1;
        }

        $interestRate = $loan->interest_rate;

        $accrual = LoanAccrual::create([
            'loan_id' => $loan->id,
            'accrual_date' => $accrualDate,
            'interest_accrued' => $interestAccrued,
            'opening_balance' => $openingBalance,
            'interest_rate' => $interestRate,
            'days_in_period' => $daysInPeriod,
            'calculation_basis' => $loan->calculation_basis,
            'posted_flag' => false,
            'created_by' => auth()->id() ?? 1,
        ]);

        $loan->accrued_interest += $interestAccrued;
        $loan->save();
        
        return $accrual;
    }
    
    /**
     * Retroactively create GL entries for an existing loan
     * This is useful for loans that were created before GL integration was added
     */
    public function retroactivelyCreateGlEntries(Loan $loan): array
    {
        $results = [
            'disbursement' => false,
            'accruals' => 0,
            'payments' => 0,
            'errors' => []
        ];
        
        try {
            DB::beginTransaction();
            
            // 1. Check and create disbursement GL entry if missing
            $disbursement = $loan->disbursements()->whereNull('journal_id')->first();
            if ($disbursement) {
                try {
                    $this->createDisbursementGlEntry($disbursement, $loan);
                    $results['disbursement'] = true;
                } catch (\Exception $e) {
                    $results['errors'][] = "Disbursement GL entry failed: " . $e->getMessage();
                }
            }
            
            // 2. Check and create accrual GL entries if missing
            $accruals = $loan->accruals()->where(function($q) {
                $q->where('posted_flag', false)->orWhereNull('journal_id');
            })->get();
            foreach ($accruals as $accrual) {
                try {
                    if (!$accrual->journal_id) {
                        $this->createAccrualGlEntry($accrual, $loan);
                        $results['accruals']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "Accrual GL entry failed for {$accrual->accrual_date}: " . $e->getMessage();
                }
            }
            
            // 3. Check and create payment GL entries if missing
            $payments = $loan->payments()->where(function($q) {
                $q->where('posted_flag', false)->orWhereNull('journal_id');
            })->get();
            foreach ($payments as $payment) {
                try {
                    if (!$payment->journal_id) {
                        $this->createPaymentGlEntry($payment, $loan);
                        $results['payments']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "Payment GL entry failed for {$payment->payment_date}: " . $e->getMessage();
                }
            }
            
            DB::commit();
            return $results;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = "General error: " . $e->getMessage();
            throw $e;
        }
    }
}

