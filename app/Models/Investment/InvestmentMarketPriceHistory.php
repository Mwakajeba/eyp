<?php

namespace App\Models\Investment;

use App\Models\Company;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentMarketPriceHistory extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'investment_market_price_history';

    protected $fillable = [
        'investment_id',
        'company_id',
        'price_date',
        'market_price',
        'bid_price',
        'ask_price',
        'mid_price',
        'price_source',
        'source_reference',
        'source_url',
        'yield_rate',
        'volume',
        'additional_data',
        'created_by',
    ];

    protected $casts = [
        'price_date' => 'date',
        'market_price' => 'decimal:6',
        'bid_price' => 'decimal:6',
        'ask_price' => 'decimal:6',
        'mid_price' => 'decimal:6',
        'yield_rate' => 'decimal:12',
        'volume' => 'decimal:6',
        'additional_data' => 'array',
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

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('price_date', [$startDate, $endDate]);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('price_date', 'desc');
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('price_source', $source);
    }

    // Helper methods
    public function getLatestPriceForInvestment($investmentId, $asOfDate = null)
    {
        $query = static::where('investment_id', $investmentId);
        
        if ($asOfDate) {
            $query->where('price_date', '<=', $asOfDate);
        }
        
        return $query->latest('price_date')->first();
    }
}
