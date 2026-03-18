<?php

namespace App\Models\Fleet;

use App\Models\Company;
use App\Models\Branch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class FleetMaintenanceSchedule extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'fleet_maintenance_schedules';

    protected $fillable = [
        'company_id',
        'branch_id',
        'vehicle_id',
        'schedule_name',
        'schedule_type',
        'maintenance_category',
        'description',
        'interval_days',
        'interval_months',
        'last_performed_date',
        'next_due_date',
        'interval_km',
        'last_performed_odometer',
        'next_due_odometer',
        'alert_days_before',
        'alert_km_before',
        'block_dispatch_when_overdue',
        'is_active',
        'current_status',
        'estimated_cost',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'interval_km' => 'decimal:2',
        'last_performed_odometer' => 'decimal:2',
        'next_due_odometer' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'last_performed_date' => 'date',
        'next_due_date' => 'date',
        'block_dispatch_when_overdue' => 'boolean',
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

    public function vehicle()
    {
        return $this->belongsTo(\App\Models\Assets\Asset::class, 'vehicle_id');
    }

    public function workOrders()
    {
        return $this->hasMany(FleetMaintenanceWorkOrder::class, 'maintenance_schedule_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOverdue($query)
    {
        return $query->where('current_status', 'overdue');
    }

    public function scopeDueSoon($query)
    {
        return $query->where('current_status', 'due_soon');
    }

    public function scopeForVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
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
    public function updateStatus()
    {
        if (!$this->is_active) {
            return $this;
        }

        $now = now();
        $vehicle = $this->vehicle;
        $currentOdometer = $vehicle ? $vehicle->current_odometer ?? 0 : 0;

        $isOverdue = false;
        $isDueSoon = false;

        // Check time-based schedule
        if ($this->schedule_type === 'time_based' || $this->schedule_type === 'both') {
            if ($this->next_due_date) {
                if ($this->next_due_date->isPast()) {
                    $isOverdue = true;
                } elseif ($this->next_due_date->isBefore($now->copy()->addDays($this->alert_days_before))) {
                    $isDueSoon = true;
                }
            }
        }

        // Check mileage-based schedule
        if ($this->schedule_type === 'mileage_based' || $this->schedule_type === 'both') {
            if ($this->next_due_odometer && $currentOdometer >= $this->next_due_odometer) {
                $isOverdue = true;
            } elseif ($this->next_due_odometer && ($this->next_due_odometer - $currentOdometer) <= $this->alert_km_before) {
                $isDueSoon = true;
            }
        }

        if ($isOverdue) {
            $this->current_status = 'overdue';
        } elseif ($isDueSoon) {
            $this->current_status = 'due_soon';
        } else {
            $this->current_status = 'up_to_date';
        }

        $this->save();
        return $this;
    }
}
