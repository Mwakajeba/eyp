<?php

namespace App\Models\Fleet;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class FleetSparePartCategory extends Model
{
    use HasFactory;

    protected $table = 'fleet_spare_part_categories';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'expected_lifespan_km',
        'expected_lifespan_months',
        'min_replacement_interval_km',
        'min_replacement_interval_months',
        'standard_cost_min',
        'standard_cost_max',
        'approval_threshold',
        'is_active',
        'description',
        'created_by',
    ];

    protected $casts = [
        'expected_lifespan_km' => 'decimal:2',
        'min_replacement_interval_km' => 'decimal:2',
        'standard_cost_min' => 'decimal:2',
        'standard_cost_max' => 'decimal:2',
        'approval_threshold' => 'decimal:2',
        'is_active' => 'boolean',
        'expected_lifespan_months' => 'integer',
        'min_replacement_interval_months' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function replacements()
    {
        return $this->hasMany(FleetSparePartReplacement::class, 'spare_part_category_id');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName()
    {
        return 'hash_id';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $decoded = Hashids::decode($value);
        if (!empty($decoded)) {
            return static::where('id', $decoded[0])->first();
        }
        return static::where('id', $value)->first();
    }

    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function getRouteKey()
    {
        return Hashids::encode($this->id);
    }
}
