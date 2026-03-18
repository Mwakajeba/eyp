<?php

namespace App\Models\Loan;

use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class Loan extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'loan_number',
        'reference_no',
        'bank_account_id',
        'bank_name',
        'bank_contact',
        'lender_id',
        'lender_name',
        'facility_name',
        'facility_type',
        'principal_amount',
        'disbursed_amount',
        'disbursement_date',
        'start_date',
        'maturity_date',
        'interest_rate',
        'rate_type',
        'base_rate_source',
        'spread',
        'calculation_basis',
        'payment_frequency',
        'term_months',
        'first_payment_date',
        'amortization_method',
        'repayment_method',
        'grace_period_months',
        'effective_interest_rate',
        'eir_locked',
        'eir_locked_at',
        'eir_locked_by',
        'initial_amortised_cost',
        'current_amortised_cost',
        'capitalized_fees',
        'directly_attributable_costs',
        'fees_amount',
        'capitalise_fees',
        'capitalise_interest',
        'capitalisation_end_date',
        'prepayment_allowed',
        'prepayment_penalty_rate',
        'status',
        'loan_payable_account_id',
        'interest_expense_account_id',
        'interest_payable_account_id',
        'deferred_loan_costs_account_id',
        'bank_charges_account_id',
        'loan_processing_fee_account_id',
        'capitalised_interest_account_id',
        'cash_deposit_account_id',
        'currency_id',
        'total_interest_paid',
        'total_principal_paid',
        'outstanding_principal',
        'accrued_interest',
        'notes',
        'attachments',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'disbursement_date' => 'date',
        'start_date' => 'date',
        'maturity_date' => 'date',
        'first_payment_date' => 'date',
        'principal_amount' => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'spread' => 'decimal:2',
        'effective_interest_rate' => 'decimal:2',
        'eir_locked' => 'boolean',
        'eir_locked_at' => 'date',
        'initial_amortised_cost' => 'decimal:2',
        'current_amortised_cost' => 'decimal:2',
        'capitalized_fees' => 'decimal:2',
        'directly_attributable_costs' => 'decimal:2',
        'fees_amount' => 'decimal:2',
        'prepayment_penalty_rate' => 'decimal:2',
        'total_interest_paid' => 'decimal:2',
        'total_principal_paid' => 'decimal:2',
        'outstanding_principal' => 'decimal:2',
        'accrued_interest' => 'decimal:2',
        'term_months' => 'integer',
        'grace_period_months' => 'integer',
        'capitalise_fees' => 'boolean',
        'capitalise_interest' => 'boolean',
        'capitalisation_end_date' => 'date',
        'prepayment_allowed' => 'boolean',
        'attachments' => 'array',
    ];

    /**
     * Get the encoded ID for this loan
     */
    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Resolve route binding using encoded ID
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $decoded = Hashids::decode($value);
        
        if (!empty($decoded)) {
            return static::where('id', $decoded[0])->first();
        }
        
        return static::where('id', $value)->first();
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKey()
    {
        return $this->encoded_id;
    }

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function loanPayableAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'loan_payable_account_id');
    }

    public function interestExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'interest_expense_account_id');
    }

    public function interestPayableAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'interest_payable_account_id');
    }

    public function capitalisedInterestAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'capitalised_interest_account_id');
    }

    /**
     * Contractual cash schedules (for payments, customer statements)
     */
    public function cashSchedules(): HasMany
    {
        return $this->hasMany(LoanCashSchedule::class);
    }

    /**
     * IFRS 9 amortised cost schedules (for accounting, GL)
     */
    public function ifrsSchedules(): HasMany
    {
        return $this->hasMany(LoanIfrsSchedule::class);
    }

    /**
     * Legacy alias for backward compatibility
     * @deprecated Use cashSchedules() instead
     */
    public function schedules(): HasMany
    {
        return $this->cashSchedules();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(LoanFee::class);
    }

    public function rateChanges(): HasMany
    {
        return $this->hasMany(LoanRateChange::class);
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(LoanDisbursement::class);
    }

    public function accruals(): HasMany
    {
        return $this->hasMany(LoanAccrual::class);
    }

    public function restructureHistory(): HasMany
    {
        return $this->hasMany(LoanRestructureHistory::class);
    }

    public function covenants(): HasMany
    {
        return $this->hasMany(LoanCovenant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['disbursed', 'active']);
    }

    /**
     * Generate loan number
     */
    public static function generateLoanNumber($companyId = null)
    {
        $prefix = 'LOAN';
        $year = date('Y');
        
        $lastLoan = static::where('company_id', $companyId ?? auth()->user()->company_id)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastLoan ? (int) substr($lastLoan->loan_number, -4) + 1 : 1;
        
        return $prefix . $year . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($loan) {
            if (empty($loan->loan_number)) {
                $loan->loan_number = self::generateLoanNumber($loan->company_id);
            }
            
            if (empty($loan->company_id)) {
                $loan->company_id = auth()->user()->company_id ?? null;
            }
            
            if (empty($loan->branch_id)) {
                $loan->branch_id = auth()->user()->branch_id ?? session('branch_id');
            }
            
            if (empty($loan->created_by)) {
                $loan->created_by = auth()->id();
            }
            
            if (empty($loan->outstanding_principal)) {
                $loan->outstanding_principal = $loan->principal_amount;
            }
        });

        static::updating(function ($loan) {
            if (empty($loan->updated_by)) {
                $loan->updated_by = auth()->id();
            }
        });
    }
}
