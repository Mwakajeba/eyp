<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetDepreciation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'asset_id',
        'asset_opening_id',
        'type',
        'depreciation_type',
        'tax_class_id',
        'depreciation_date',
        'depreciation_amount',
        'accumulated_depreciation',
        'book_value_before',
        'book_value_after',
        'tax_wdv_before',
        'tax_wdv_after',
        'accumulated_tax_depreciation',
        'description',
        'gl_transaction_id',
        'gl_posted',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'depreciation_date' => 'date',
        'depreciation_amount' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'book_value_before' => 'decimal:2',
        'book_value_after' => 'decimal:2',
        'tax_wdv_before' => 'decimal:2',
        'tax_wdv_after' => 'decimal:2',
        'accumulated_tax_depreciation' => 'decimal:2',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function assetOpening()
    {
        return $this->belongsTo(AssetOpening::class, 'asset_opening_id');
    }

    public function taxClass()
    {
        return $this->belongsTo(TaxDepreciationClass::class, 'tax_class_id');
    }

    public function glTransactions()
    {
        return $this->hasMany(\App\Models\GlTransaction::class, 'transaction_id')
            ->where('transaction_type', 'asset_depreciation');
    }

    /**
     * Scope for book depreciation entries
     */
    public function scopeBook($query)
    {
        return $query->where('depreciation_type', 'book');
    }

    /**
     * Scope for tax depreciation entries
     */
    public function scopeTax($query)
    {
        return $query->where('depreciation_type', 'tax');
    }

    /**
     * Get current book value for an asset
     * This calculates the book value after all depreciations including opening balances
     */
    public static function getCurrentBookValue($assetId, $asOfDate = null, $companyId = null)
    {
        $companyId = $companyId ?? (auth()->user()->company_id ?? 0);
        
        $query = static::where('asset_id', $assetId)
            ->where('company_id', $companyId)
            ->where(function ($q) {
                $q->whereNull('depreciation_type')
                  ->orWhere('depreciation_type', 'book');
            });
        
        if ($asOfDate) {
            $query->where('depreciation_date', '<=', $asOfDate);
        }
        
        $latest = $query->orderBy('depreciation_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
        
        return $latest ? $latest->book_value_after : null;
    }

    /**
     * Get accumulated depreciation for an asset (book basis)
     */
    public static function getAccumulatedDepreciation($assetId, $asOfDate = null, $companyId = null)
    {
        $companyId = $companyId ?? (auth()->user()->company_id ?? 0);
        
        $query = static::where('asset_id', $assetId)
            ->where('company_id', $companyId)
            ->where(function ($q) {
                $q->whereNull('depreciation_type')
                  ->orWhere('depreciation_type', 'book');
            });
        
        if ($asOfDate) {
            $query->where('depreciation_date', '<=', $asOfDate);
        }
        
        $latest = $query->orderBy('depreciation_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
        
        return $latest ? $latest->accumulated_depreciation : 0;
    }

    /**
     * Get current tax WDV for an asset
     */
    public static function getCurrentTaxWdv($assetId, $asOfDate = null, $companyId = null)
    {
        $companyId = $companyId ?? (auth()->user()->company_id ?? 0);
        
        $query = static::where('asset_id', $assetId)
            ->where('company_id', $companyId)
            ->where('depreciation_type', 'tax');
        
        if ($asOfDate) {
            $query->where('depreciation_date', '<=', $asOfDate);
        }
        
        $latest = $query->orderBy('depreciation_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
        
        return $latest ? $latest->tax_wdv_after : null;
    }

    /**
     * Get accumulated tax depreciation for an asset
     */
    public static function getAccumulatedTaxDepreciation($assetId, $asOfDate = null, $companyId = null)
    {
        $companyId = $companyId ?? (auth()->user()->company_id ?? 0);
        
        $query = static::where('asset_id', $assetId)
            ->where('company_id', $companyId)
            ->where('depreciation_type', 'tax');
        
        if ($asOfDate) {
            $query->where('depreciation_date', '<=', $asOfDate);
        }
        
        $latest = $query->orderBy('depreciation_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
        
        return $latest ? $latest->accumulated_tax_depreciation : 0;
    }
}
