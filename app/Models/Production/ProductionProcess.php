<?php
namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;

class ProductionProcess extends Model
{
    protected $fillable = [
        'production_order_id',
        'step', // cutting, sewing, printing, etc.
        'status', // pending, completed
        'started_at',
        'finished_at',
    ];

    public function order()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }
}
