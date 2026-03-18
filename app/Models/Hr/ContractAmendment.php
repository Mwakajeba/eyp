<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractAmendment extends Model
{
    protected $table = 'hr_contract_amendments';

    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = [
        'contract_id',
        'amendment_type',
        'effective_date',
        'old_value',
        'new_value',
        'reason',
        'approved_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'old_value' => 'array',
        'new_value' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}
