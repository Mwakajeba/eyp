<?php

namespace App\Models\Investment;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Journal;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class EclCalc extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'ecl_calc';

    protected $fillable = [
        'company_id',
        'branch_id',
        'investment_id',
        'ecl_input_id',
        'ecl_scenario_id',
        'calculation_date',
        'calculation_run_id',
        'calculation_type',
        'stage',
        'stage_assigned_date',
        'stage_reason',
        'pd',
        'lgd',
        'ead',
        'ccf',
        'ecl_12_month',
        'ecl_lifetime',
        'ecl_amount',
        'scenario_ecl',
        'weighted_ecl',
        'forward_looking_adjustments',
        'pd_adjustment',
        'forward_looking_applied',
        'model_name',
        'model_version',
        'posted_journal_id',
        'is_posted',
        'posted_at',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'calculation_date' => 'date',
        'stage_assigned_date' => 'date',
        'posted_at' => 'datetime',
        'pd' => 'decimal:6',
        'lgd' => 'decimal:6',
        'ead' => 'decimal:2',
        'ccf' => 'decimal:6',
        'ecl_12_month' => 'decimal:2',
        'ecl_lifetime' => 'decimal:2',
        'ecl_amount' => 'decimal:2',
        'weighted_ecl' => 'decimal:2',
        'scenario_ecl' => 'array',
        'forward_looking_adjustments' => 'array',
        'pd_adjustment' => 'decimal:6',
        'forward_looking_applied' => 'boolean',
        'is_posted' => 'boolean',
        'stage' => 'integer',
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

    public function eclInput()
    {
        return $this->belongsTo(EclInput::class, 'ecl_input_id');
    }

    public function eclScenario()
    {
        return $this->belongsTo(EclScenario::class, 'ecl_scenario_id');
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class, 'posted_journal_id');
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

    public function scopeByCalculationDate($query, $date)
    {
        return $query->where('calculation_date', $date);
    }

    public function scopeByRunId($query, $runId)
    {
        return $query->where('calculation_run_id', $runId);
    }

    public function scopeByStage($query, $stage)
    {
        return $query->where('stage', $stage);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
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
}
