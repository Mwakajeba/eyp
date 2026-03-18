<?php

namespace App\Models\Investment;

use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EclInput extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'ecl_inputs';

    protected $fillable = [
        'company_id',
        'branch_id',
        'investment_id',
        'snapshot_date',
        'snapshot_type',
        'exposure_amount',
        'carrying_amount',
        'days_past_due',
        'stage',
        'pd',
        'lgd',
        'credit_rating',
        'credit_grade',
        'additional_data',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'exposure_amount' => 'decimal:2',
        'carrying_amount' => 'decimal:2',
        'days_past_due' => 'integer',
        'stage' => 'integer',
        'pd' => 'decimal:6',
        'lgd' => 'decimal:6',
        'additional_data' => 'array',
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

    public function investment()
    {
        return $this->belongsTo(InvestmentMaster::class, 'investment_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function scopeBySnapshotDate($query, $date)
    {
        return $query->where('snapshot_date', $date);
    }

    public function scopeByStage($query, $stage)
    {
        return $query->where('stage', $stage);
    }
}
