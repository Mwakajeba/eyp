<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetImpairment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'asset_id',
        'impairment_number',
        'impairment_date',
        'impairment_type',
        'cgu_id',
        'indicator_physical_damage',
        'indicator_obsolescence',
        'indicator_technological_change',
        'indicator_idle_asset',
        'indicator_market_decline',
        'indicator_legal_regulatory',
        'other_indicators',
        'carrying_amount',
        'fair_value_less_costs',
        'value_in_use',
        'recoverable_amount',
        'impairment_loss',
        'discount_rate',
        'cash_flow_projections',
        'projection_years',
        'is_reversal',
        'original_impairment_id',
        'reversal_amount',
        'reversal_date',
        'impairment_loss_account_id',
        'impairment_reversal_account_id',
        'accumulated_impairment_account_id',
        'revaluation_reserve_account_id',
        'journal_id',
        'gl_posted',
        'gl_posted_at',
        'carrying_amount_after',
        'useful_life_before',
        'useful_life_after',
        'residual_value_before',
        'residual_value_after',
        'status',
        'current_approval_level',
        'submitted_by',
        'submitted_at',
        'prepared_by',
        'finance_manager_id',
        'cfo_approver_id',
        'approved_at',
        'approved_by',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'approval_notes',
        'impairment_test_report_path',
        'attachments',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'impairment_date' => 'date',
        'reversal_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'gl_posted_at' => 'datetime',
        'carrying_amount' => 'decimal:2',
        'fair_value_less_costs' => 'decimal:2',
        'value_in_use' => 'decimal:2',
        'recoverable_amount' => 'decimal:2',
        'impairment_loss' => 'decimal:2',
        'reversal_amount' => 'decimal:2',
        'carrying_amount_after' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'residual_value_before' => 'decimal:2',
        'residual_value_after' => 'decimal:2',
        'indicator_physical_damage' => 'boolean',
        'indicator_obsolescence' => 'boolean',
        'indicator_technological_change' => 'boolean',
        'indicator_idle_asset' => 'boolean',
        'indicator_market_decline' => 'boolean',
        'indicator_legal_regulatory' => 'boolean',
        'is_reversal' => 'boolean',
        'gl_posted' => 'boolean',
        'cash_flow_projections' => 'array',
        'attachments' => 'array',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function journal()
    {
        return $this->belongsTo(\App\Models\Journal::class);
    }

    public function originalImpairment()
    {
        return $this->belongsTo(AssetImpairment::class, 'original_impairment_id');
    }

    public function reversals()
    {
        return $this->hasMany(AssetImpairment::class, 'original_impairment_id')->where('is_reversal', true);
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

    public function revaluationReserveAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'revaluation_reserve_account_id');
    }

    public function preparedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'prepared_by');
    }

    public function financeManager()
    {
        return $this->belongsTo(\App\Models\User::class, 'finance_manager_id');
    }

    public function cfoApprover()
    {
        return $this->belongsTo(\App\Models\User::class, 'cfo_approver_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function submittedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'submitted_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'rejected_by');
    }

    /**
     * Check if impairment can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending_approval';
    }

    /**
     * Check if impairment can be posted to GL
     */
    public function canBePosted(): bool
    {
        return $this->status === 'approved' && !$this->gl_posted;
    }

    /**
     * Check if reversal is allowed
     */
    public function canBeReversed(): bool
    {
        return !$this->is_reversal && $this->status === 'posted' && $this->impairment_loss > 0;
    }

    /**
     * Get total reversals amount
     */
    public function getTotalReversalsAttribute(): float
    {
        return $this->reversals()->sum('reversal_amount');
    }

    /**
     * Get remaining impairment that can be reversed
     */
    public function getRemainingReversibleAmountAttribute(): float
    {
        return max(0, $this->impairment_loss - $this->total_reversals);
    }

    /**
     * Check if any impairment indicators are set
     */
    public function hasIndicators(): bool
    {
        return $this->indicator_physical_damage ||
               $this->indicator_obsolescence ||
               $this->indicator_technological_change ||
               $this->indicator_idle_asset ||
               $this->indicator_market_decline ||
               $this->indicator_legal_regulatory ||
               !empty($this->other_indicators);
    }

    /**
     * Calculate value in use from cash flow projections
     */
    public function calculateValueInUse(): float
    {
        if (empty($this->cash_flow_projections) || !$this->discount_rate) {
            return 0;
        }

        $valueInUse = 0;
        $year = 1;

        foreach ($this->cash_flow_projections as $cashFlow) {
            $pv = $cashFlow / pow(1 + ($this->discount_rate / 100), $year);
            $valueInUse += $pv;
            $year++;
        }

        return round($valueInUse, 2);
    }

    /**
     * Scope for approved impairments
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for posted impairments
     */
    public function scopePosted($query)
    {
        return $query->where('gl_posted', true);
    }

    /**
     * Scope for reversals
     */
    public function scopeReversals($query)
    {
        return $query->where('is_reversal', true);
    }
}
