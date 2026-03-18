<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HfsAsset extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'hfs_id',
        'asset_id',
        'asset_type',
        'asset_reference',
        'original_account_id',
        'carrying_amount_at_reclass',
        'accumulated_depreciation_at_reclass',
        'accumulated_impairment_at_reclass',
        'asset_cost_at_reclass',
        'depreciation_stopped',
        'reclassified_date',
        'book_currency',
        'book_currency_amount',
        'local_currency',
        'book_local_amount',
        'book_fx_rate',
        'current_carrying_amount',
        'status',
        'is_pledged',
        'pledge_details',
        'bank_consent_obtained',
        'bank_consent_date',
        'bank_consent_ref',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'carrying_amount_at_reclass' => 'decimal:2',
        'accumulated_depreciation_at_reclass' => 'decimal:2',
        'accumulated_impairment_at_reclass' => 'decimal:2',
        'asset_cost_at_reclass' => 'decimal:2',
        'depreciation_stopped' => 'boolean',
        'reclassified_date' => 'date',
        'book_currency_amount' => 'decimal:2',
        'book_local_amount' => 'decimal:2',
        'book_fx_rate' => 'decimal:6',
        'current_carrying_amount' => 'decimal:2',
        'is_pledged' => 'boolean',
        'bank_consent_obtained' => 'boolean',
        'bank_consent_date' => 'date',
    ];

    // Relationships
    public function hfsRequest()
    {
        return $this->belongsTo(HfsRequest::class, 'hfs_id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function originalAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'original_account_id');
    }

    // Scopes
    public function scopeClassified($query)
    {
        return $query->where('status', 'classified');
    }

    public function scopeSold($query)
    {
        return $query->where('status', 'sold');
    }

    // Helper methods
    public function getNetBookValueAtReclassAttribute()
    {
        return $this->carrying_amount_at_reclass;
    }
}
