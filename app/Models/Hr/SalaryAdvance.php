<?php

namespace App\Models\Hr;

use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\Hr\Employee;
use App\Models\GlTransaction;
use App\Models\Payment;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vinkla\Hashids\Facades\Hashids;

class SalaryAdvance extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_salary_advances';

    protected $fillable = [
        'company_id',
        'employee_id',
        'bank_account_id',
        'user_id',
        'branch_id',
        'reference',
        'date',
        'amount',
        'monthly_deduction',
        'repayment_type',
        'reason',
        'is_active',
        'payment_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'monthly_deduction' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(SalaryAdvanceRepayment::class, 'salary_advance_id');
    }


    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }


    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // Accessors
    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function getRouteKeyName()
    {
        return 'hash_id';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $decoded = Hashids::decode($value);
        if (!empty($decoded) && isset($decoded[0])) {
            return static::where('id', $decoded[0])->first();
        }
        return static::where('id', $value)->first();
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }

    public function getFormattedMonthlyDeductionAttribute()
    {
        return number_format($this->monthly_deduction, 2);
    }

    public function getFormattedDateAttribute()
    {
        return $this->date ? $this->date->format('M d, Y') : 'N/A';
    }


    public function getEmployeeNameAttribute()
    {
        return $this->employee ? $this->employee->full_name : 'N/A';
    }

    public function getBankAccountNameAttribute()
    {
        return $this->bankAccount ? $this->bankAccount->name : 'N/A';
    }

    /**
     * Get total deductions made (manual and payroll)
     */
    public function getTotalDeductionsAttribute()
    {
        return $this->repayments()->sum('amount');
    }

    /**
     * Get remaining balance
     */
    public function getRemainingBalanceAttribute()
    {
        $totalDeductions = $this->getTotalDeductionsAttribute();
        return max(0, $this->amount - $totalDeductions);
    }

    /**
     * Check if advance is fully repaid
     */
    public function isFullyRepaid(): bool
    {
        return $this->remaining_balance <= 0;
    }

    /**
     * Record a repayment (manual or payroll)
     */
    public function recordRepayment($amount, $repaymentDate, $payrollId = null, $type = 'payroll', $bankAccountId = null, $notes = null)
    {
        $remainingBalance = $this->remaining_balance;
        $actualRepayment = min($amount, $remainingBalance);

        if ($actualRepayment <= 0) {
            return null;
        }

        $repayment = SalaryAdvanceRepayment::create([
            'salary_advance_id' => $this->id,
            'company_id' => $this->company_id,
            'user_id' => auth()->id() ?? $this->user_id,
            'bank_account_id' => $bankAccountId,
            'payroll_id' => $payrollId,
            'date' => $repaymentDate,
            'amount' => $actualRepayment,
            'type' => $type,
            'reference' => ($type === 'manual' ? 'MAN-' : 'PAY-') . strtoupper(uniqid()),
            'notes' => $notes,
        ]);

        // Auto-deactivate if fully repaid
        // We need to refresh the model or calculate again because total_deductions is cached/dynamic
        if ($this->remaining_balance <= 0) {
            $this->is_active = false;
            $this->save();
        }

        return $repayment;
    }
}
