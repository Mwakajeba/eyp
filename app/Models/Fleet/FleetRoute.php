<?php

namespace App\Models\Fleet;

use App\Models\Company;
use App\Models\Branch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class FleetRoute extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'fleet_routes';

    protected $fillable = [
        'company_id',
        'branch_id',
        'route_code',
        'route_name',
        'origin_location',
        'destination_location',
        'route_description',
        'distance_km',
        'estimated_duration_hours',
        'estimated_duration_minutes',
        'estimated_fuel_consumption_liters',
        'toll_costs',
        'toll_points',
        'route_type',
        'waypoints',
        'route_notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'distance_km' => 'decimal:2',
        'estimated_duration_hours' => 'decimal:2',
        'estimated_duration_minutes' => 'decimal:2',
        'estimated_fuel_consumption_liters' => 'decimal:2',
        'toll_costs' => 'decimal:2',
        'toll_points' => 'array',
        'waypoints' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function trips()
    {
        return $this->hasMany(FleetTrip::class, 'route_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, $companyId = null)
    {
        if ($companyId) {
            return $query->where('company_id', $companyId);
        }
        return $query;
    }

    public function scopeForBranch($query, $branchId = null)
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }

    // Hash ID support
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
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

    public function getRouteKey()
    {
        return $this->hash_id;
    }
}