<?php

namespace App\Models\Loan;

use App\Traits\LogsActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRateChange extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'loan_id',
        'effective_date',
        'new_rate',
        'previous_rate',
        'reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'new_rate' => 'decimal:2',
        'previous_rate' => 'decimal:2',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
