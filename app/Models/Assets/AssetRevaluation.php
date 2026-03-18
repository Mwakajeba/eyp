<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetRevaluation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'batch_id',
        'company_id',
        'branch_id',
        'asset_id',
        'revaluation_number',
        'revaluation_date',
        'valuation_model',
        'valuer_name',
        'valuer_license',
        'valuer_company',
        'valuation_report_ref',
        'valuation_report_path',
        'reason',
        'carrying_amount_before',
        'accumulated_depreciation_before',
        'fair_value',
        'revaluation_increase',
        'revaluation_decrease',
        'carrying_amount_after',
        'useful_life_before',
        'useful_life_after',
        'residual_value_before',
        'residual_value_after',
        'revaluation_reserve_account_id',
        'impairment_reversal_account_id',
        'journal_id',
        'gl_posted',
        'gl_posted_at',
        'status',
        'current_approval_level',
        'submitted_by',
        'submitted_at',
        'valuer_user_id',
        'finance_manager_id',
        'cfo_approver_id',
        'approved_at',
        'approved_by',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'approval_notes',
        'attachments',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'revaluation_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'gl_posted_at' => 'datetime',
        'carrying_amount_before' => 'decimal:2',
        'accumulated_depreciation_before' => 'decimal:2',
        'fair_value' => 'decimal:2',
        'revaluation_increase' => 'decimal:2',
        'revaluation_decrease' => 'decimal:2',
        'carrying_amount_after' => 'decimal:2',
        'residual_value_before' => 'decimal:2',
        'residual_value_after' => 'decimal:2',
        'gl_posted' => 'boolean',
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

    public function revaluationReserveAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'revaluation_reserve_account_id');
    }

    public function impairmentReversalAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'impairment_reversal_account_id');
    }

    public function valuer()
    {
        return $this->belongsTo(\App\Models\User::class, 'valuer_user_id');
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

    public function revaluationReserves()
    {
        return $this->hasMany(RevaluationReserve::class, 'revaluation_id');
    }

    public function batch()
    {
        return $this->belongsTo(RevaluationBatch::class, 'batch_id');
    }

    /**
     * Check if revaluation can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending_approval';
    }

    /**
     * Check if revaluation can be posted to GL
     */
    public function canBePosted(): bool
    {
        return $this->status === 'approved' && !$this->gl_posted;
    }

    /**
     * Get net revaluation amount (increase - decrease)
     */
    public function getNetRevaluationAttribute(): float
    {
        return $this->revaluation_increase - $this->revaluation_decrease;
    }

    /**
     * Scope for approved revaluations
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for posted revaluations
     */
    public function scopePosted($query)
    {
        return $query->where('gl_posted', true);
    }
}
