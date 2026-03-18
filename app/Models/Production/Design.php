<?php
namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;

class Design extends Model
{
    protected $fillable = [
        'name',
        'image_path',
        'approved',
        'notes',
    ];
}
