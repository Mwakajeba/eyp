<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisposalReasonCode extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'disposal_type',
        'is_active',
        'is_system',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function disposals()
    {
        return $this->hasMany(AssetDisposal::class, 'disposal_reason_code_id');
    }
}
