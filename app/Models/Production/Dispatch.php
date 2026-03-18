<?php
namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;

class Dispatch extends Model
{
    protected $fillable = [
        'production_order_id',
        'dispatched_at',
        'dispatched_by',
        'destination',
        'tracking_number',
    ];

    public function order()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }
}
