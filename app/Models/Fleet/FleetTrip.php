<?php

namespace App\Models\Fleet;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Hr\Department;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class FleetTrip extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'fleet_trips';

    protected $fillable = [
        'company_id',
        'branch_id',
        'trip_number',
        'vehicle_id', // Links to Asset (truck)
        'driver_id',
        'route_id',
        'customer_id',
        'department_id',
        'status', // planned, dispatched, in_progress, completed, cancelled
        'trip_type', // delivery, pickup, service, transport, other
        'cargo_description',
        'origin_location',
        'destination_location',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'start_latitude',
        'start_longitude',
        'start_location_name',
        'last_location_lat',
        'last_location_lng',
        'last_location_at',
        'last_location_name',
        'actual_end_date',
        'planned_distance_km',
        'actual_distance_km',
        'start_odometer',
        'end_odometer',
        'planned_fuel_consumption_liters',
        'actual_fuel_consumption_liters',
        'start_fuel_level',
        'end_fuel_level',
        'planned_revenue',
        'actual_revenue',
        'revenue_model',
        'revenue_rate',
        'total_costs',
        'variable_costs',
        'fixed_costs_allocated',
        'profit_loss',
        'approval_status',
        'approved_by',
        'approved_at',
        'is_completed',
        'completed_at',
        'completed_by',
        'notes',
        'attachments',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'planned_start_date' => 'datetime',
        'planned_end_date' => 'datetime',
        'actual_start_date' => 'datetime',
        'actual_end_date' => 'datetime',
        'last_location_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'planned_distance_km' => 'decimal:2',
        'actual_distance_km' => 'decimal:2',
        'start_odometer' => 'decimal:2',
        'end_odometer' => 'decimal:2',
        'planned_fuel_consumption_liters' => 'decimal:2',
        'actual_fuel_consumption_liters' => 'decimal:2',
        'start_fuel_level' => 'decimal:2',
        'end_fuel_level' => 'decimal:2',
        'planned_revenue' => 'decimal:2',
        'actual_revenue' => 'decimal:2',
        'revenue_rate' => 'decimal:2',
        'total_costs' => 'decimal:2',
        'variable_costs' => 'decimal:2',
        'fixed_costs_allocated' => 'decimal:2',
        'profit_loss' => 'decimal:2',
        'is_completed' => 'boolean',
        'attachments' => 'array',
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

    public function vehicle()
    {
        return $this->belongsTo(\App\Models\Assets\Asset::class, 'vehicle_id');
    }

    public function driver()
    {
        return $this->belongsTo(FleetDriver::class);
    }

    public function route()
    {
        return $this->belongsTo(FleetRoute::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'completed_by');
    }

    // Trip costs
    public function costs()
    {
        return $this->hasMany(FleetTripCost::class, 'trip_id');
    }

    // Fuel logs
    public function fuelLogs()
    {
        return $this->hasMany(FleetFuelLog::class, 'trip_id');
    }

    // Invoices
    public function invoices()
    {
        return $this->hasMany(FleetInvoice::class, 'trip_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['dispatched', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePlanned($query)
    {
        return $query->where('status', 'planned');
    }

    public function scopeForVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeForDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
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

    // Helper methods
    public function calculateTotalCosts()
    {
        return $this->costs()->sum('amount');
    }

    /**
     * Recalculate total_costs (trip costs + approved fuel logs) and profit_loss. Used by Trip Planning / Dispatch.
     */
    public function recalculateTotalCosts()
    {
        $costsSum = (float) $this->costs()->sum('amount');
        $approvedFuelSum = (float) $this->fuelLogs()->where('approval_status', 'approved')->sum('total_cost');
        $totalCosts = $costsSum + $approvedFuelSum;
        $variableCosts = (float) $this->costs()->where('cost_type', '!=', 'insurance')->sum('amount') + $approvedFuelSum;
        $fixedCosts = (float) $this->costs()->where('cost_type', 'insurance')->sum('amount');
        $profitLoss = (float) $this->actual_revenue - $totalCosts;
        $this->update([
            'total_costs' => $totalCosts,
            'variable_costs' => $variableCosts,
            'fixed_costs_allocated' => $fixedCosts,
            'profit_loss' => $profitLoss,
        ]);
    }

    public function calculateProfitLoss()
    {
        return $this->actual_revenue - $this->total_costs;
    }
}