<?php

namespace App\Models\Loan;

use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanRestructureHistory extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'loan_restructure_history';

    protected $fillable = [
        'loan_id',
        'restructure_date',
        'reason',
        'new_terms_summary',
        'old_terms',
        'new_terms',
        'approved_by',
        'approval_notes',
        'attachments',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'restructure_date' => 'date',
        'old_terms' => 'array',
        'new_terms' => 'array',
        'attachments' => 'array',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
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
