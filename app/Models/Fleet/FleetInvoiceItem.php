<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetInvoiceItem extends Model
{
    use HasFactory;

    protected $table = 'fleet_invoice_items';

    protected $fillable = [
        'fleet_invoice_id',
        'trip_id',
        'gl_account_id',
        'description',
        'quantity',
        'unit',
        'unit_rate',
        'amount',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(FleetInvoice::class, 'fleet_invoice_id');
    }

    public function trip()
    {
        return $this->belongsTo(FleetTrip::class);
    }

    public function glAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'gl_account_id');
    }
}
