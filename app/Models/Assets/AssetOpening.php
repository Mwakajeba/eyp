<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetOpening extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'asset_id',
        'asset_code',
        'asset_name',
        'asset_category_id',
        'tax_pool_class',
        'opening_date',
        'opening_cost',
        'opening_accum_depr',
        'opening_nbv',
        'notes',
        'gl_post',
        'gl_posted',
        'gl_journal_id',
        'created_by',
        'updated_by',
    ];

    public function glTransactions()
    {
        return $this->hasMany(\App\Models\GlTransaction::class, 'transaction_id')
            ->where('transaction_type', 'asset_opening');
    }

    public function depreciations()
    {
        return $this->hasMany(AssetDepreciation::class, 'asset_opening_id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}


