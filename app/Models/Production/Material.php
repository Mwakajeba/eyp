<?php
namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'name',
        'type', // fabric, thread, ink, etc.
        'quantity_in_stock',
        'unit',
    ];
}
