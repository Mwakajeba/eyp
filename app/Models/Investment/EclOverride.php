<?php

namespace App\Models\Investment;

use App\Models\Company;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class EclOverride extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'ecl_override';

    protected $fillable = [
        'company_id',
        'investment_id',
        'ecl_calc_id',
        'override_date',
        'override_type',
        'pd_override',
        'lgd_override',
        'ead_override',
        'stage_override',
        'ecl_amount_override',
        'override_reason',
        'justification',
        'supporting_documents',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'approval_notes',
        'effective_from',
        'effective_to',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'override_date' => 'date',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'approved_at' => 'datetime',
        'pd_override' => 'decimal:6',
        'lgd_override' => 'decimal:6',
        'ead_override' => 'decimal:2',
        'ecl_amount_override' => 'decimal:2',
        'supporting_documents' => 'array',
        'is_active' => 'boolean',
        'stage_override' => 'integer',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function investment()
    {
        return $this->belongsTo(InvestmentMaster::class, 'investment_id');
    }

    public function eclCalc()
    {
        return $this->belongsTo(EclCalc::class, 'ecl_calc_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function scopeByInvestment($query, $investmentId)
    {
        return $query->where('investment_id', $investmentId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }
}
