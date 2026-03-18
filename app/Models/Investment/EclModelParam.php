<?php

namespace App\Models\Investment;

use App\Models\Company;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EclModelParam extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'ecl_model_params';

    protected $fillable = [
        'company_id',
        'model_name',
        'model_version',
        'instrument_type',
        'stage',
        'base_pd',
        'pd_adjustment_factors',
        'pd_rating_matrix',
        'base_lgd',
        'lgd_adjustment_factors',
        'collateral_haircut',
        'ccf',
        'ead_adjustment_rules',
        'staging_rules',
        'scenario_weights',
        'is_active',
        'effective_from',
        'effective_to',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'base_pd' => 'decimal:6',
        'pd_adjustment_factors' => 'array',
        'pd_rating_matrix' => 'array',
        'base_lgd' => 'decimal:6',
        'lgd_adjustment_factors' => 'array',
        'collateral_haircut' => 'decimal:6',
        'ccf' => 'decimal:6',
        'ead_adjustment_rules' => 'array',
        'staging_rules' => 'array',
        'scenario_weights' => 'array',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'stage' => 'integer',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByInstrumentType($query, $instrumentType)
    {
        return $query->where(function($q) use ($instrumentType) {
            $q->where('instrument_type', $instrumentType)
              ->orWhere('instrument_type', 'ALL');
        });
    }

    public function scopeByStage($query, $stage)
    {
        return $query->where(function($q) use ($stage) {
            $q->where('stage', $stage)
              ->orWhereNull('stage');
        });
    }

    public function scopeEffectiveAsOf($query, $date)
    {
        return $query->where(function($q) use ($date) {
            $q->whereNull('effective_from')
              ->orWhere('effective_from', '<=', $date);
        })
        ->where(function($q) use ($date) {
            $q->whereNull('effective_to')
              ->orWhere('effective_to', '>=', $date);
        });
    }
}
