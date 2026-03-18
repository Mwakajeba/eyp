<?php

namespace App\Models\Investment;

use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class InvestmentProposal extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'investment_proposals';

    protected $fillable = [
        'company_id',
        'branch_id',
        'proposal_number',
        'instrument_type',
        'issuer',
        'proposed_amount',
        'expected_yield',
        'risk_rating',
        'tenor_days',
        'proposed_accounting_class',
        'description',
        'rationale',
        'recommended_by',
        'status',
        'current_approval_level',
        'is_fully_approved',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'converted_to_investment_id',
        'converted_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'proposed_amount' => 'decimal:2',
        'expected_yield' => 'decimal:6',
        'tenor_days' => 'integer',
        'is_fully_approved' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function recommender()
    {
        return $this->belongsTo(User::class, 'recommended_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function approvals()
    {
        return $this->hasMany(InvestmentApproval::class, 'proposal_id');
    }

    public function investmentAttachments()
    {
        return $this->morphMany(InvestmentAttachment::class, 'attachable');
    }

    public function convertedInvestment()
    {
        return $this->belongsTo(InvestmentMaster::class, 'converted_to_investment_id');
    }

    // Scopes
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['SUBMITTED', 'IN_REVIEW']);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'DRAFT');
    }

    // Helper methods
    public function isDraft(): bool
    {
        return $this->status === 'DRAFT';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['SUBMITTED', 'IN_REVIEW']);
    }

    public function isApproved(): bool
    {
        return $this->status === 'APPROVED' && $this->is_fully_approved;
    }

    public function isRejected(): bool
    {
        return $this->status === 'REJECTED';
    }

    public function canBeConverted(): bool
    {
        return $this->isApproved() && !$this->converted_to_investment_id;
    }

    /**
     * Generate unique proposal number
     */
    public static function generateProposalNumber($companyId): string
    {
        $prefix = 'INV-PROP-';
        $year = date('Y');
        $count = self::where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->count() + 1;
        
        return $prefix . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get the hash ID for the proposal
     */
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    /**
     * Resolve the model from the route parameter
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Try to decode the hash ID first
        $decoded = Hashids::decode($value);
        
        if (!empty($decoded)) {
            return static::where('id', $decoded[0])->first();
        }
        
        // Fallback to regular ID lookup
        return static::where('id', $value)->first();
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKey()
    {
        return $this->hash_id;
    }
}

