<?php

namespace App\Models\Loan;

use App\Traits\LogsActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanFee extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'loan_id',
        'type',
        'name',
        'amount',
        'treatment',
        'recognized_on',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'recognized_on' => 'date',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
