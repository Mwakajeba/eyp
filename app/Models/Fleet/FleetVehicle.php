<?php

namespace App\Models\Fleet;

use App\Models\Assets\Asset;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FleetVehicle extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'asset_id', // Links to Asset model
        'registration_number',
        'ownership_type', // owned, leased, rented
        'fuel_type',
        'capacity_tons',
        'capacity_volume',
        'capacity_passengers',
        'insurance_policy_number',
        'insurance_expiry_date',
        'license_expiry_date',
        'inspection_expiry_date',
        'operational_status', // available, assigned, in_repair, retired
        'gps_device_id',
        'current_location',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'insurance_expiry_date' => 'date',
        'license_expiry_date' => 'date',
        'inspection_expiry_date' => 'date',
        'capacity_tons' => 'decimal:2',
        'capacity_volume' => 'decimal:2',
    ];

    // Relationship with Asset
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    // Relationship with Trips
    public function trips()
    {
        return $this->hasMany(FleetTrip::class);
    }

    // Relationship with Driver assignments
    public function driverAssignments()
    {
        return $this->hasMany(FleetDriverAssignment::class);
    }

    // Current driver
    public function currentDriver()
    {
        return $this->hasOne(FleetDriverAssignment::class)->whereNull('end_date');
    }

    // Maintenance relationships
    public function maintenanceWorkOrders()
    {
        return $this->hasMany(FleetMaintenanceWorkOrder::class, 'vehicle_id');
    }

    public function maintenanceSchedules()
    {
        return $this->hasMany(FleetMaintenanceSchedule::class, 'vehicle_id');
    }

    // Fuel logs
    public function fuelLogs()
    {
        return $this->hasMany(FleetFuelLog::class, 'vehicle_id');
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('operational_status', 'available');
    }

    public function scopeAssigned($query)
    {
        return $query->where('operational_status', 'assigned');
    }

    public function scopeInRepair($query)
    {
        return $query->where('operational_status', 'in_repair');
    }
}