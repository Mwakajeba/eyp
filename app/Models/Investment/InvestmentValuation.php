<?php

namespace App\Models\Investment;

use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use App\Models\Journal;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class InvestmentValuation extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'investment_valuations';

    protected $fillable = [
        'investment_id',
        'company_id',
        'branch_id',
        'valuation_date',
        'period_start',
        'period_end',
        'valuation_level',
        'valuation_method',
        'fair_value_per_unit',
        'units',
        'total_fair_value',
        'carrying_amount_before',
        'carrying_amount_after',
        'unrealized_gain_loss',
        'realized_gain_loss',
        'fvoci_reserve_change',
        'yield_rate',
        'discount_rate',
        'cash_flows',
        'valuation_inputs',
        'valuation_assumptions',
        'price_source',
        'price_reference',
        'price_date',
        'status',
        'requires_approval',
        'approved_by',
        'approved_at',
        'approval_notes',
        'posted_journal_id',
        'is_posted',
        'posted_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'valuation_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'price_date' => 'date',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'fair_value_per_unit' => 'decimal:6',
        'units' => 'decimal:6',
        'total_fair_value' => 'decimal:2',
        'carrying_amount_before' => 'decimal:2',
        'carrying_amount_after' => 'decimal:2',
        'unrealized_gain_loss' => 'decimal:2',
        'realized_gain_loss' => 'decimal:2',
        'fvoci_reserve_change' => 'decimal:2',
        'yield_rate' => 'decimal:12',
        'discount_rate' => 'decimal:12',
        'cash_flows' => 'array',
        'valuation_inputs' => 'array',
        'requires_approval' => 'boolean',
        'is_posted' => 'boolean',
        'valuation_level' => 'integer',
    ];

    // Relationships
    public function investment()
    {
        return $this->belongsTo(InvestmentMaster::class, 'investment_id');
    }

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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class, 'posted_journal_id');
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

    public function scopeByValuationLevel($query, $level)
    {
        return $query->where('valuation_level', $level);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'PENDING_APPROVAL')
            ->where('requires_approval', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopePosted($query)
    {
        return $query->where('is_posted', true);
    }

    public function scopeUnposted($query)
    {
        return $query->where('is_posted', false);
    }

    // Accessors
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function getRouteKeyName()
    {
        return 'hash_id';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $decoded = Hashids::decode($value);
        if (!empty($decoded)) {
            return static::where($this->getRouteKeyName(), $decoded[0])->first();
        }
        return parent::resolveRouteBinding($value, $field);
    }

    public function getRouteKey()
    {
        return $this->hash_id;
    }

    // Helper methods
    public function isDraft()
    {
        return $this->status === 'DRAFT';
    }

    public function isPendingApproval()
    {
        return $this->status === 'PENDING_APPROVAL';
    }

    public function isApproved()
    {
        return $this->status === 'APPROVED';
    }

    public function isPosted()
    {
        return $this->is_posted;
    }

    public function requiresApproval()
    {
        return $this->requires_approval && $this->valuation_level === 3;
    }
}
