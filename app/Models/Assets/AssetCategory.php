<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'code',
        'name',
        'default_depreciation_method',
        'default_useful_life_months',
        'default_depreciation_rate',
        'depreciation_convention',
        'capitalization_threshold',
        'residual_value_percent',
        'ifrs_reference',
        'notes',
        'asset_account_id',
        'accum_depr_account_id',
        'depr_expense_account_id',
        'gain_on_disposal_account_id',
        'loss_on_disposal_account_id',
        'revaluation_reserve_account_id',
        'revaluation_loss_account_id',
        'default_valuation_model',
        'revaluation_frequency',
        'revaluation_interval_years',
        'impairment_loss_account_id',
        'impairment_reversal_account_id',
        'accumulated_impairment_account_id',
        'hfs_account_id',
        'require_valuation_report',
        'require_approval',
        'min_approval_levels',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'require_valuation_report' => 'boolean',
        'require_approval' => 'boolean',
    ];

    public function scopeForBranch($query, $branchId)
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }

    public function scopeForCompany($query, $companyId)
    {
        if ($companyId) {
            return $query->where('company_id', $companyId);
        }
        return $query;
    }

    // Asset Relationship
    public function assets()
    {
        return $this->hasMany(Asset::class, 'asset_category_id');
    }

    // Chart Account Relationships
    public function revaluationReserveAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'revaluation_reserve_account_id');
    }

    public function revaluationLossAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'revaluation_loss_account_id');
    }

    public function impairmentLossAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'impairment_loss_account_id');
    }

    public function impairmentReversalAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'impairment_reversal_account_id');
    }

    public function accumulatedImpairmentAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'accumulated_impairment_account_id');
    }

    public function hfsAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'hfs_account_id');
    }
}


