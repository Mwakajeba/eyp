<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vinkla\Hashids\Facades\Hashids;

class AllowanceType extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_allowance_types';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'type',
        'is_taxable',
        'is_active',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns the allowance type.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    /**
     * Scope a query to only include active allowance types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include taxable allowance types.
     */
    public function scopeTaxable($query)
    {
        return $query->where('is_taxable', true);
    }

    /**
     * Get the allowances for this type.
     */
    public function allowances()
    {
        return $this->hasMany(\App\Models\Hr\Allowance::class);
    }

    /**
     * Get the encoded ID attribute.
     */
    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }
}
