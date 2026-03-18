<?php

namespace App\Models\Investment;

use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use App\Models\ChartAccount;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;
use App\Models\Investment\InvestmentValuation;
use App\Models\Investment\InvestmentMarketPriceHistory;

class InvestmentMaster extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'investment_master';

    protected $fillable = [
        'company_id',
        'branch_id',
        'instrument_code',
        'instrument_type',
        'issuer',
        'isin',
        'purchase_date',
        'settlement_date',
        'maturity_date',
        'nominal_amount',
        'carrying_amount',
        'fvoci_reserve',
        'purchase_price',
        'units',
        'currency',
        'accounting_class',
        'eir_rate',
        'day_count',
        'coupon_rate',
        'coupon_freq',
        'coupon_schedule',
        'status',
        'valuation_level',
        'impairment_stage',
        'tax_class',
        'gl_asset_account',
        'gl_accrued_interest_account',
        'gl_interest_income_account',
        'gl_gain_loss_account',
        'gl_fvoci_reserve_account',
        'attachments',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'settlement_date' => 'date',
        'maturity_date' => 'date',
        'nominal_amount' => 'decimal:2',
        'carrying_amount' => 'decimal:2',
        'fvoci_reserve' => 'decimal:2',
        'purchase_price' => 'decimal:6',
        'units' => 'decimal:6',
        'eir_rate' => 'decimal:12',
        'coupon_rate' => 'decimal:6',
        'coupon_schedule' => 'array',
        'attachments' => 'array',
        'valuation_level' => 'integer',
        'impairment_stage' => 'integer',
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

    public function assetAccount()
    {
        return $this->belongsTo(ChartAccount::class, 'gl_asset_account');
    }

    public function accruedInterestAccount()
    {
        return $this->belongsTo(ChartAccount::class, 'gl_accrued_interest_account');
    }

    public function interestIncomeAccount()
    {
        return $this->belongsTo(ChartAccount::class, 'gl_interest_income_account');
    }

    public function gainLossAccount()
    {
        return $this->belongsTo(ChartAccount::class, 'gl_gain_loss_account');
    }

    public function trades()
    {
        return $this->hasMany(InvestmentTrade::class, 'investment_id');
    }

    public function amortizationLines()
    {
        return $this->hasMany(InvestmentAmortLine::class, 'investment_id');
    }

    public function investmentAttachments()
    {
        return $this->morphMany(InvestmentAttachment::class, 'attachable');
    }

    public function valuations()
    {
        return $this->hasMany(InvestmentValuation::class, 'investment_id');
    }

    public function marketPriceHistory()
    {
        return $this->hasMany(InvestmentMarketPriceHistory::class, 'investment_id');
    }

    public function latestValuation()
    {
        return $this->hasOne(InvestmentValuation::class, 'investment_id')->latestOfMany('valuation_date');
    }

    public function latestMarketPrice()
    {
        return $this->hasOne(InvestmentMarketPriceHistory::class, 'investment_id')->latestOfMany('price_date');
    }

    public function fvociReserveAccount()
    {
        return $this->belongsTo(ChartAccount::class, 'gl_fvoci_reserve_account');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByInstrumentType($query, $type)
    {
        return $query->where('instrument_type', $type);
    }

    public function scopeByAccountingClass($query, $class)
    {
        return $query->where('accounting_class', $class);
    }

    // Helper methods
    public function getCarryingAmountAttribute()
    {
        // Carrying amount = units * purchase_price (will be updated with amortization in Phase 3)
        return $this->units * $this->purchase_price;
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    public function isMatured(): bool
    {
        return $this->status === 'MATURED' || 
               ($this->maturity_date && $this->maturity_date->isPast() && $this->status !== 'DISPOSED');
    }

    /**
     * Get the hash ID for the investment
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

