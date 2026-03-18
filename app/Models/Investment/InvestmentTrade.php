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

class InvestmentTrade extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'investment_trade';
    protected $primaryKey = 'trade_id';

    protected $fillable = [
        'investment_id',
        'company_id',
        'branch_id',
        'trade_type',
        'trade_date',
        'settlement_date',
        'trade_price',
        'trade_units',
        'gross_amount',
        'fees',
        'tax_withheld',
        'bank_ref',
        'settlement_status',
        'posted_journal_id',
        'created_by',
        // Bond fields (T-Bonds, Corporate Bonds)
        'coupon_rate',
        'coupon_frequency',
        'yield_to_maturity',
        'accrued_coupon_at_purchase',
        'premium_discount',
        'fair_value_source',
        'fair_value',
        'benchmark',
        'credit_risk_grade',
        'counterparty',
        // BOT Required Fields for T-Bonds
        'auction_no',
        'auction_date',
        'bond_type',
        'bond_price',
        // BOT Required Fields for T-Bills
        'tbill_price',
        'tbill_type',
        // T-Bills specific
        'discount_rate',
        'yield_rate',
        'maturity_days',
        // Fixed Deposits specific
        'fd_reference_no',
        'bank_name',
        'branch',
        'interest_computation_method',
        'payout_frequency',
        'expected_interest',
        'collateral_flag',
        'rollover_option',
        'premature_withdrawal_penalty',
        // Corporate Bonds specific
        'issuer_name',
        'sector',
        'credit_rating',
        'credit_spread',
        'fair_value_method',
        'impairment_override_reason',
        'counterparty_broker',
        // Equity specific
        'ticker_symbol',
        'company_name',
        'number_of_shares',
        'purchase_price_per_share',
        'dividend_rate',
        'dividend_tax_rate',
        'country',
        'exchange_rate',
        'impairment_indicator',
        'ecl_not_applicable_flag',
        // Money Market Funds specific
        'fund_name',
        'fund_manager',
        'units_purchased',
        'unit_price',
        'nav_price',
        'distribution_rate',
        'risk_class',
        // Commercial Papers specific
        'issuer',
        // IFRS 9 ECL fields
        'stage',
        'pd',
        'lgd',
        'ead',
        'ecl_amount',
        // Disposal fields
        'disposal_date',
        'realized_gain_loss',
        // Tax fields
        'tax_withholding_rate',
        // Additional metadata
        'contractual_cashflows',
        'expected_cashflows',
    ];

    protected $casts = [
        'trade_date' => 'date',
        'settlement_date' => 'date',
        'auction_date' => 'date',
        'disposal_date' => 'date',
        'trade_price' => 'decimal:6',
        'trade_units' => 'decimal:6',
        'gross_amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'tax_withheld' => 'decimal:2',
        'coupon_rate' => 'decimal:6',
        'yield_to_maturity' => 'decimal:6',
        'accrued_coupon_at_purchase' => 'decimal:2',
        'premium_discount' => 'decimal:2',
        'fair_value' => 'decimal:2',
        'discount_rate' => 'decimal:6',
        'yield_rate' => 'decimal:6',
        'expected_interest' => 'decimal:2',
        'premature_withdrawal_penalty' => 'decimal:2',
        'credit_spread' => 'decimal:6',
        'number_of_shares' => 'decimal:6',
        'purchase_price_per_share' => 'decimal:6',
        'dividend_rate' => 'decimal:6',
        'dividend_tax_rate' => 'decimal:6',
        'exchange_rate' => 'decimal:6',
        'units_purchased' => 'decimal:6',
        'unit_price' => 'decimal:6',
        'nav_price' => 'decimal:6',
        'distribution_rate' => 'decimal:6',
        'bond_price' => 'decimal:6',
        'tbill_price' => 'decimal:6',
        'pd' => 'decimal:6',
        'lgd' => 'decimal:6',
        'ead' => 'decimal:2',
        'ecl_amount' => 'decimal:2',
        'realized_gain_loss' => 'decimal:2',
        'tax_withholding_rate' => 'decimal:6',
        'collateral_flag' => 'boolean',
        'rollover_option' => 'boolean',
        'impairment_indicator' => 'boolean',
        'ecl_not_applicable_flag' => 'boolean',
        'contractual_cashflows' => 'array',
        'expected_cashflows' => 'array',
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

    public function scopeByTradeType($query, $type)
    {
        return $query->where('trade_type', $type);
    }

    public function scopePendingSettlement($query)
    {
        return $query->where('settlement_status', 'PENDING');
    }

    public function scopeSettled($query)
    {
        return $query->where('settlement_status', 'SETTLED');
    }

    // Helper methods
    public function getNetAmountAttribute()
    {
        return $this->gross_amount - $this->fees - $this->tax_withheld;
    }

    public function isSettled(): bool
    {
        return $this->settlement_status === 'SETTLED';
    }

    public function isPending(): bool
    {
        return $this->settlement_status === 'PENDING';
    }

    /**
     * Get the hash ID for the trade (using trade_id as primary key)
     */
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->trade_id);
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName()
    {
        return 'trade_id';
    }

    /**
     * Resolve the model from the route parameter
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Try to decode the hash ID first
        $decoded = Hashids::decode($value);
        
        if (!empty($decoded)) {
            return static::where('trade_id', $decoded[0])->first();
        }
        
        // Fallback to regular ID lookup
        return static::where('trade_id', $value)->first();
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKey()
    {
        return $this->hash_id;
    }
}

