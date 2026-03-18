<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kpi extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_kpis';

    protected $fillable = [
        'company_id',
        'kpi_code',
        'kpi_name',
        'description',
        'measurement_criteria',
        'weight_percent',
        'target_value',
        'scoring_method',
        'applicable_to',
        'is_active',
    ];

    protected $casts = [
        'weight_percent' => 'decimal:2',
        'target_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Scoring methods
     */
    const SCORING_NUMERIC = 'numeric';
    const SCORING_RATING_SCALE = 'rating_scale';

    /**
     * Applicable to types
     */
    const APPLICABLE_COMPANY = 'company';
    const APPLICABLE_DEPARTMENT = 'department';
    const APPLICABLE_POSITION = 'position';
    const APPLICABLE_INDIVIDUAL = 'individual';

    /**
     * Relationships
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function appraisalKpiScores()
    {
        return $this->hasMany(AppraisalKpiScore::class, 'kpi_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeApplicableTo($query, $type)
    {
        return $query->where('applicable_to', $type);
    }
}

