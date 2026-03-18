<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HeslbLoan extends Model
{
    use LogsActivity;

    protected $table = 'hr_heslb_loans';

    protected $fillable = [
        'company_id',
        'employee_id',
        'loan_number',
        'original_loan_amount',
        'outstanding_balance',
        'deduction_percent',
        'loan_start_date',
        'loan_end_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'original_loan_amount' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'deduction_percent' => 'decimal:2',
        'loan_start_date' => 'date',
        'loan_end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(HeslbRepayment::class, 'heslb_loan_id');
    }

    /**
     * Get active loan for an employee
     */
    public static function getActiveLoan($employeeId)
    {
        return static::where('employee_id', $employeeId)
            ->where('is_active', true)
            ->where('outstanding_balance', '>', 0)
            ->first();
    }

    /**
     * Check if employee has active loan
     */
    public static function hasActiveLoan($employeeId): bool
    {
        return static::getActiveLoan($employeeId) !== null;
    }

    /**
     * Record a repayment and update balance
     */
    public function recordRepayment($amount, $repaymentDate, $payrollId = null, $payrollEmployeeId = null, $paymentMethod = 'payroll', $notes = null)
    {
        $balanceBefore = $this->outstanding_balance;
        $actualRepayment = min($amount, $balanceBefore); // Don't overpay
        $balanceAfter = max(0, $balanceBefore - $actualRepayment);

        // Create repayment record
        $repayment = HeslbRepayment::create([
            'company_id' => $this->company_id,
            'employee_id' => $this->employee_id,
            'heslb_loan_id' => $this->id,
            'payroll_id' => $payrollId,
            'payroll_employee_id' => $payrollEmployeeId,
            'amount' => $actualRepayment,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'repayment_date' => $repaymentDate,
            'payment_method' => $paymentMethod,
            'notes' => $notes,
        ]);

        // Update loan balance
        $this->outstanding_balance = $balanceAfter;
        
        // Auto-deactivate if balance is zero
        if ($balanceAfter <= 0) {
            $this->is_active = false;
        }
        
        $this->save();

        return $repayment;
    }
}
