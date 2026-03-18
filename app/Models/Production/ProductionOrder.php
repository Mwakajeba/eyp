<?php
namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    protected $fillable = [
        'customer_name',
        'product_type', // sweater or t-shirt
        'quantity',
        'size',
        'color',
        'logo_design_id',
        'status', // pending, in_progress, completed
        'due_date',
    ];

    public function design()
    {
        return $this->belongsTo(Design::class, 'logo_design_id');
    }
}
