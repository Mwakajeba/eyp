<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxDepreciationClass extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'class_code',
        'description',
        'rate',
        'method',
        'special_condition',
        'legal_reference',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get assets using this tax class
     */
    public function assets()
    {
        return $this->hasMany(Asset::class, 'tax_class_id');
    }

    /**
     * Scope for active classes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for company-specific or default classes
     */
    public function scopeForCompany($query, $companyId = null)
    {
        if ($companyId) {
            return $query->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')
                  ->orWhere('company_id', $companyId);
            });
        }
        return $query->whereNull('company_id');
    }

    /**
     * Get method label
     */
    public function getMethodLabelAttribute()
    {
        $labels = [
            'reducing_balance' => 'Reducing Balance',
            'straight_line' => 'Straight Line',
            'immediate_write_off' => 'Immediate Write-Off',
            'useful_life' => 'Useful Life',
        ];

        return $labels[$this->method] ?? $this->method;
    }
}
