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

class LoanDisbursement extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'loan_id',
        'disb_date',
        'amount_received',
        'net_proceeds',
        'bank_account_id',
        'ref_number',
        'bank_charges',
        'narration',
        'journal_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'disb_date' => 'date',
        'amount_received' => 'decimal:2',
        'net_proceeds' => 'decimal:2',
        'bank_charges' => 'decimal:2',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
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
