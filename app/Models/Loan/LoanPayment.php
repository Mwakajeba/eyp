<?php

namespace App\Models\Loan;

use App\Models\BankAccount;
use App\Models\Journal;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanPayment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'loan_id',
        'loan_schedule_id',
        'payment_date',
        'amount',
        'allocation_interest',
        'allocation_principal',
        'allocation_fees',
        'allocation_penalty',
        'bank_account_id',
        'payment_method',
        'reference',
        'payment_ref',
        'journal_id',
        'approved_by',
        'posted_flag',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'allocation_interest' => 'decimal:2',
        'allocation_principal' => 'decimal:2',
        'allocation_fees' => 'decimal:2',
        'allocation_penalty' => 'decimal:2',
        'posted_flag' => 'boolean',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(LoanSchedule::class, 'loan_schedule_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
