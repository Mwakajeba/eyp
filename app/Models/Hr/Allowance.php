<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vinkla\Hashids\Facades\Hashids;

class Allowance extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_allowances';

    protected $fillable = [
        'company_id',
        'employee_id',
        'allowance_type_id',
        'date',
        'amount',
        'description',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns the allowance.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    /**
     * Get the employee that owns the allowance.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Hr\Employee::class);
    }

    /**
     * Get the allowance type for this allowance.
     */
    public function allowanceType(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Hr\AllowanceType::class);
    }

    /**
     * Scope a query to only include active allowances.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the formatted amount.
     */
    public function getFormattedAmountAttribute()
    {
        return 'TZS ' . number_format($this->amount, 2);
    }

    /**
     * Get the encoded ID attribute.
     */
    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }
}
