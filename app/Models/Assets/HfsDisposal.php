<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HfsDisposal extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'hfs_id',
        'disposal_date',
        'sale_proceeds',
        'sale_currency',
        'currency_rate',
        'costs_sold',
        'carrying_amount_at_disposal',
        'accumulated_impairment_at_disposal',
        'gain_loss_amount',
        'buyer_name',
        'buyer_contact',
        'buyer_address',
        'invoice_number',
        'receipt_number',
        'settlement_reference',
        'bank_account_id',
        'vat_type',
        'vat_rate',
        'vat_amount',
        'withholding_tax_enabled',
        'withholding_tax_rate',
        'withholding_tax_type',
        'withholding_tax',
        'journal_id',
        'gl_posted',
        'gl_posted_at',
        'is_partial_sale',
        'partial_sale_percentage',
        'notes',
        'attachments',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'disposal_date' => 'date',
        'sale_proceeds' => 'decimal:2',
        'currency_rate' => 'decimal:6',
        'costs_sold' => 'decimal:2',
        'carrying_amount_at_disposal' => 'decimal:2',
        'accumulated_impairment_at_disposal' => 'decimal:2',
        'gain_loss_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'withholding_tax_enabled' => 'boolean',
        'withholding_tax_rate' => 'decimal:2',
        'withholding_tax' => 'decimal:2',
        'gl_posted' => 'boolean',
        'gl_posted_at' => 'datetime',
        'is_partial_sale' => 'boolean',
        'partial_sale_percentage' => 'decimal:2',
        'attachments' => 'array',
    ];

    // Relationships
    public function hfsRequest()
    {
        return $this->belongsTo(HfsRequest::class, 'hfs_id');
    }

    public function journal()
    {
        return $this->belongsTo(\App\Models\Journal::class, 'journal_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(\App\Models\BankAccount::class, 'bank_account_id');
    }

    // Scopes
    public function scopePosted($query)
    {
        return $query->where('gl_posted', true);
    }

    // Helper methods
    public function calculateGainLoss(): float
    {
        return $this->sale_proceeds - $this->carrying_amount_at_disposal - $this->costs_sold;
    }

    public function isGain(): bool
    {
        return $this->gain_loss_amount >= 0;
    }
}
