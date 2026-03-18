<?php

namespace App\Models\Loan;

use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanCovenant extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'loan_id',
        'covenant_type',
        'covenant_name',
        'description',
        'threshold_value',
        'comparison_operator',
        'actual_value',
        'period',
        'status',
        'notes',
        'next_review_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'period' => 'date',
        'next_review_date' => 'date',
        'threshold_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
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
