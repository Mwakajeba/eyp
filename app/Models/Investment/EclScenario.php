<?php

namespace App\Models\Investment;

use App\Models\Company;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EclScenario extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'ecl_scenarios';

    protected $fillable = [
        'company_id',
        'scenario_name',
        'scenario_type',
        'weight',
        'gdp_growth',
        'inflation_rate',
        'interest_rate',
        'unemployment_rate',
        'macro_factors',
        'pd_multiplier',
        'credit_indicators',
        'as_of_date',
        'forecast_period_start',
        'forecast_period_end',
        'is_active',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'weight' => 'decimal:4',
        'gdp_growth' => 'decimal:6',
        'inflation_rate' => 'decimal:6',
        'interest_rate' => 'decimal:6',
        'unemployment_rate' => 'decimal:6',
        'macro_factors' => 'array',
        'pd_multiplier' => 'decimal:6',
        'credit_indicators' => 'array',
        'as_of_date' => 'date',
        'forecast_period_start' => 'date',
        'forecast_period_end' => 'date',
        'is_active' => 'boolean',
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

    public function scopeByType($query, $type)
    {
        return $query->where('scenario_type', $type);
    }

    public function scopeAsOfDate($query, $date)
    {
        return $query->where('as_of_date', $date);
    }
}
