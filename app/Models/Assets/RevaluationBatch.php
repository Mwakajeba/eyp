<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RevaluationBatch extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'batch_number',
        'revaluation_date',
        'valuation_model',
        'valuer_name',
        'valuer_license',
        'valuer_company',
        'valuation_report_ref',
        'valuation_report_path',
        'reason',
        'status',
        'valuer_user_id',
        'finance_manager_id',
        'cfo_approver_id',
        'approved_at',
        'approval_notes',
        'attachments',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'revaluation_date' => 'date',
        'approved_at' => 'datetime',
        'attachments' => 'array',
    ];

    public function revaluations()
    {
        return $this->hasMany(AssetRevaluation::class, 'batch_id');
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
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

    /**
     * Check if batch can be submitted for approval
     */
    public function canBeSubmitted(): bool
    {
        return $this->status === 'draft' && $this->revaluations()->count() > 0;
    }

    /**
     * Check if batch can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending_approval';
    }

    /**
     * Check if all revaluations in batch are approved
     */
    public function allRevaluationsApproved(): bool
    {
        return $this->revaluations()
            ->where('status', '!=', 'approved')
            ->count() === 0;
    }

    /**
     * Get total fair value of all revaluations in batch
     */
    public function getTotalFairValueAttribute(): float
    {
        return $this->revaluations()->sum('fair_value');
    }

    /**
     * Get total revaluation increase
     */
    public function getTotalIncreaseAttribute(): float
    {
        return $this->revaluations()->sum('revaluation_increase');
    }

    /**
     * Get total revaluation decrease
     */
    public function getTotalDecreaseAttribute(): float
    {
        return $this->revaluations()->sum('revaluation_decrease');
    }
}

