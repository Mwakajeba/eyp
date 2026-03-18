<?php

namespace App\Models\Investment;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Journal;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentDeferredTax extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'investment_deferred_tax';

    protected $fillable = [
        'investment_id',
        'company_id',
        'branch_id',
        'tax_period_start',
        'tax_period_end',
        'tax_year',
        'tax_base_carrying_amount',
        'accounting_carrying_amount',
        'temporary_difference',
        'tax_rate',
        'deferred_tax_asset',
        'deferred_tax_liability',
        'net_deferred_tax',
        'opening_balance',
        'movement',
        'closing_balance',
        'difference_type',
        'difference_description',
        'posted_journal_id',
        'is_posted',
        'posted_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tax_period_start' => 'date',
        'tax_period_end' => 'date',
        'posted_at' => 'datetime',
        'tax_base_carrying_amount' => 'decimal:2',
        'accounting_carrying_amount' => 'decimal:2',
        'temporary_difference' => 'decimal:2',
        'tax_rate' => 'decimal:6',
        'deferred_tax_asset' => 'decimal:2',
        'deferred_tax_liability' => 'decimal:2',
        'net_deferred_tax' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'movement' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'is_posted' => 'boolean',
        'tax_year' => 'integer',
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

    public function scopeByTaxYear($query, $year)
    {
        return $query->where('tax_year', $year);
    }

    public function scopeByDifferenceType($query, $type)
    {
        return $query->where('difference_type', $type);
    }
}
