<?php

namespace App\Models\Loan;

use App\Models\Journal;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Loan IFRS 9 Amortised Cost Schedule
 * 
 * Represents the accounting schedule based on Effective Interest Rate (EIR).
 * Used for: General Ledger, Financial Statements, Audit & Compliance.
 * 
 * This schedule is system-generated only and cannot be manually edited.
 */
class LoanIfrsSchedule extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'loan_ifrs_schedules';

    protected $fillable = [
        'loan_id',
        'cash_schedule_id',
        'period_no',
        'period_start',
        'period_end',
        'due_date',
        'opening_amortised_cost',
        'ifrs_interest_expense',
        'cash_paid',
        'closing_amortised_cost',
        'cash_interest_paid',
        'cash_principal_paid',
        'deferred_costs_amortized', // Deprecated: Transaction costs amortised implicitly via EIR (kept for backward compatibility)
        'effective_interest_rate',
        'posted_to_gl',
        'journal_id',
        'posted_date',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'posted_date' => 'date',
        'opening_amortised_cost' => 'decimal:2',
        'ifrs_interest_expense' => 'decimal:2',
        'cash_paid' => 'decimal:2',
        'closing_amortised_cost' => 'decimal:2',
        'cash_interest_paid' => 'decimal:2',
        'cash_principal_paid' => 'decimal:2',
        'deferred_costs_amortized' => 'decimal:2',
        'effective_interest_rate' => 'decimal:2',
        'posted_to_gl' => 'boolean',
        'period_no' => 'integer',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function cashSchedule(): BelongsTo
    {
        return $this->belongsTo(LoanCashSchedule::class, 'cash_schedule_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}

