<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RevaluationReserve extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'asset_id',
        'revaluation_id',
        'impairment_id',
        'reserve_account_id',
        'movement_date',
        'movement_type',
        'amount',
        'balance_after',
        'retained_earnings_account_id',
        'transfer_amount',
        'transfer_reason',
        'reference_number',
        'description',
        'journal_id',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transfer_amount' => 'decimal:2',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function revaluation()
    {
        return $this->belongsTo(AssetRevaluation::class, 'revaluation_id');
    }

    public function impairment()
    {
        return $this->belongsTo(AssetImpairment::class, 'impairment_id');
    }

    public function reserveAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'reserve_account_id');
    }

    public function retainedEarningsAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'retained_earnings_account_id');
    }

    public function journal()
    {
        return $this->belongsTo(\App\Models\Journal::class);
    }

    /**
     * Get current reserve balance for an asset
     */
    public static function getCurrentBalance($assetId, $asOfDate = null, $companyId = null)
    {
        $companyId = $companyId ?? (auth()->user()->company_id ?? 0);
        
        $query = static::where('asset_id', $assetId)
            ->where('company_id', $companyId);
        
        if ($asOfDate) {
            $query->where('movement_date', '<=', $asOfDate);
        }
        
        $latest = $query->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
        
        return $latest ? $latest->balance_after : 0;
    }

    /**
     * Scope for increases
     */
    public function scopeIncreases($query)
    {
        return $query->whereIn('movement_type', ['revaluation_increase', 'impairment_reversal']);
    }

    /**
     * Scope for decreases
     */
    public function scopeDecreases($query)
    {
        return $query->whereIn('movement_type', ['revaluation_decrease', 'impairment_charge', 'transfer_to_retained_earnings', 'disposal_transfer']);
    }
}
