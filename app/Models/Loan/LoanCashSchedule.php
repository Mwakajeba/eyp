<?php

namespace App\Models\Loan;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Loan Cash Schedule (Contractual Schedule)
 * 
 * Represents the contractual payment obligations based on nominal interest rate.
 * Used for: payment reminders, bank reconciliation, aging, customer statements.
 */
class LoanCashSchedule extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'loan_cash_schedules';

    protected $fillable = [
        'loan_id',
        'installment_no',
        'period_no',
        'due_date',
        'period_start',
        'period_end',
        'opening_principal',
        'opening_balance',
        'principal_due',
        'principal_paid',
        'closing_principal',
        'closing_balance',
        'interest_due',
        'interest_paid',
        'interest_rate',
        'total_due',
        'installment_amount',
        'amount_paid',
        'status',
        'paid_date',
        'days_overdue',
        'notes',
        'schedule_type',
    ];

    protected $casts = [
        'due_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'paid_date' => 'date',
        'opening_principal' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'principal_due' => 'decimal:2',
        'principal_paid' => 'decimal:2',
        'closing_principal' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'interest_due' => 'decimal:2',
        'interest_paid' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'total_due' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'days_overdue' => 'integer',
        'installment_no' => 'integer',
        'period_no' => 'integer',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class, 'loan_schedule_id');
    }

    public function ifrsSchedule(): BelongsTo
    {
        return $this->belongsTo(LoanIfrsSchedule::class, 'id', 'cash_schedule_id');
    }
}

