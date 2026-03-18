<?php

namespace App\Models\Loan;

use App\Models\Journal;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanAccrual extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'loan_id',
        'accrual_date',
        'interest_accrued',
        'opening_balance',
        'interest_rate',
        'days_in_period',
        'calculation_basis',
        'posted_flag',
        'journal_id',
        'journal_ref',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'accrual_date' => 'date',
        'interest_accrued' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'days_in_period' => 'integer',
        'posted_flag' => 'boolean',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
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
