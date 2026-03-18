<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDeferredTax extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'asset_deferred_tax';

    protected $fillable = [
        'asset_id',
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
        'posted_at' => 'datetime',
        'tax_year' => 'integer',
    ];

    /**
     * Relationships
     */
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

    public function postedJournal()
    {
        return $this->belongsTo(\App\Models\Journal::class, 'posted_journal_id');
    }

    /**
     * Scopes
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('tax_year', $year);
    }

    public function scopePosted($query)
    {
        return $query->where('is_posted', true);
    }

    public function scopeUnposted($query)
    {
        return $query->where('is_posted', false);
    }
}
