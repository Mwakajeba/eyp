<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Qualification extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_qualifications';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'level',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Qualification levels
    const LEVEL_CERTIFICATE = 'certificate';
    const LEVEL_DIPLOMA = 'diploma';
    const LEVEL_DEGREE = 'degree';
    const LEVEL_MASTERS = 'masters';
    const LEVEL_PHD = 'phd';
    const LEVEL_PROFESSIONAL = 'professional';
    const LEVEL_OTHER = 'other';

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function requiredDocuments(): HasMany
    {
        return $this->hasMany(QualificationDocument::class, 'qualification_id');
    }

    public function requiredDocumentsList(): HasMany
    {
        return $this->hasMany(QualificationDocument::class, 'qualification_id')
            ->where('is_required', true)
            ->orderBy('sort_order');
    }

    /**
     * Scope for active qualifications
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for company-specific or global qualifications
     */
    public function scopeForCompany($query, $companyId = null)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->whereNull('company_id')
              ->orWhere('company_id', $companyId);
        });
    }
}
