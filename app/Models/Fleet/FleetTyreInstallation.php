<?php

namespace App\Models\Fleet;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Assets\Asset;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class FleetTyreInstallation extends Model
{
    use HasFactory;

    protected $table = 'fleet_tyre_installations';

    protected $fillable = [
        'company_id',
        'branch_id',
        'tyre_id',
        'vehicle_id',
        'tyre_position_id',
        'installed_at',
        'odometer_at_install',
        'installer_type',
        'installer_name',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'installed_at' => 'date',
        'odometer_at_install' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function tyre()
    {
        return $this->belongsTo(FleetTyre::class, 'tyre_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Asset::class, 'vehicle_id');
    }

    public function tyrePosition()
    {
        return $this->belongsTo(FleetTyrePosition::class, 'tyre_position_id');
    }

    public function replacementRequests()
    {
        return $this->hasMany(FleetTyreReplacementRequest::class, 'current_installation_id');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForBranch($query, $branchId)
    {
        if (empty($branchId)) {
            return $query;
        }
        return $query->where('branch_id', $branchId);
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
