<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Vinkla\Hashids\Facades\Hashids;

class ExternalLoan extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_external_loans';

    protected $fillable = [
        'company_id',
        'employee_id',
        'institution_name',
        'external_loan_institution_id',
        'reference_number',
        'total_loan',
        'monthly_deduction',
        'deduction_type',
        'date_end_of_loan',
        'date',
        'is_active',
        'description',
    ];

    protected $casts = [
        'total_loan' => 'decimal:2',
        'monthly_deduction' => 'decimal:2',
        'date_end_of_loan' => 'date',
        'date' => 'date',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function externalLoanInstitution()
    {
        // Try to use external_loan_institution_id if it exists, otherwise use institution_name
        if (!empty($this->external_loan_institution_id)) {
            return $this->belongsTo(ExternalLoanInstitution::class, 'external_loan_institution_id');
        }
        // Fallback: use institution_name to find institution
        return $this->belongsTo(ExternalLoanInstitution::class, 'institution_name', 'name')
            ->where('company_id', $this->company_id);
    }

    /**
     * Get institution name (from relationship or institution_name field)
     */
    public function getInstitutionDisplayNameAttribute()
    {
        if ($this->relationLoaded('externalLoanInstitution') && $this->externalLoanInstitution) {
            return $this->externalLoanInstitution->name;
        }
        return $this->attributes['institution_name'] ?? 'N/A';
    }

    public function getEncodedIdAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get total deductions made from payroll
     * 
     * Note: For percentage-based loans, this calculates based on actual deductions stored.
     * For fixed loans, it uses the monthly_deduction amount per period.
     * When multiple loans exist, it uses a proportional approach.
     */
    public function getTotalDeductionsAttribute()
    {
        $deductionType = $this->deduction_type ?? 'fixed';
        
        return \App\Models\PayrollEmployee::whereHas('payroll', function ($q) {
                $q->where('company_id', $this->company_id);
            })
            ->whereHas('employee', function ($q) {
                $q->where('id', $this->employee_id);
            })
            ->where('loans', '>', 0)
            ->get()
            ->sum(function ($payrollEmployee) use ($deductionType) {
                // Check if this loan was included in the deduction
                $payrollDate = \Carbon\Carbon::create(
                    $payrollEmployee->payroll->year,
                    $payrollEmployee->payroll->month,
                    1
                )->endOfMonth();

                if ($this->date <= $payrollDate
                    && $this->is_active
                    && ($this->date_end_of_loan === null || $this->date_end_of_loan >= $payrollDate)) {
                    
                    if ($deductionType === 'percentage') {
                        // For percentage loans, we need to calculate proportionally
                        // Get all active loans for this employee in this payroll period
                        $activeLoans = self::where('employee_id', $this->employee_id)
                            ->where('company_id', $this->company_id)
                            ->where('is_active', true)
                            ->where('date', '<=', $payrollDate)
                            ->where(function ($q) use ($payrollDate) {
                                $q->whereNull('date_end_of_loan')
                                  ->orWhere('date_end_of_loan', '>=', $payrollDate);
                            })
                            ->get();
                        
                        // Calculate total percentage for all active loans
                        $totalPercentage = $activeLoans->sum(function ($loan) {
                            return ($loan->deduction_type ?? 'fixed') === 'percentage' 
                                ? (float) $loan->monthly_deduction 
                                : 0;
                        });
                        
                        // Calculate total fixed amount for all active loans
                        $totalFixed = $activeLoans->sum(function ($loan) {
                            return ($loan->deduction_type ?? 'fixed') === 'fixed' 
                                ? (float) $loan->monthly_deduction 
                                : 0;
                        });
                        
                        // For percentage loans, we use basic salary (consistent with HESLB loans)
                        // Since we don't have it here, we'll use a proportional approach
                        // based on the percentage relative to other percentage loans
                        if ($totalPercentage > 0) {
                            $thisPercentage = (float) $this->monthly_deduction;
                            // Estimate: assume percentage loans contribute proportionally to the total
                            // This is an approximation - actual amount depends on basic salary each period
                            $percentageContribution = ($thisPercentage / $totalPercentage) * max(0, $payrollEmployee->loans - $totalFixed);
                            return $percentageContribution;
                        }
                        
                        // If no percentage loans or this is the only one, use the full amount
                        return $payrollEmployee->loans;
                    } else {
                        // For fixed loans, use the monthly deduction amount (capped by actual deduction)
                        return min((float) $this->monthly_deduction, (float) $payrollEmployee->loans);
                    }
                }
                return 0;
            });
    }

    /**
     * Get remaining balance
     */
    public function getRemainingBalanceAttribute()
    {
        $totalDeductions = $this->getTotalDeductionsAttribute();
        return max(0, $this->total_loan - $totalDeductions);
    }

    /**
     * Check if loan is fully repaid
     */
    public function isFullyRepaid(): bool
    {
        return $this->remaining_balance <= 0;
    }

    /**
     * Record a repayment from payroll
     */
    public function recordRepayment($amount, $repaymentDate, $payrollId = null, $payrollEmployeeId = null)
    {
        $remainingBalance = $this->remaining_balance;
        $actualRepayment = min($amount, $remainingBalance);

        // Auto-deactivate if fully repaid
        if ($remainingBalance - $actualRepayment <= 0) {
            $this->is_active = false;
            $this->save();
        }

        return $actualRepayment;
    }
}
