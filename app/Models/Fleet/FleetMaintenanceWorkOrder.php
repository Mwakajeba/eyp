<?php

namespace App\Models\Fleet;

use App\Models\Company;
use App\Models\Branch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class FleetMaintenanceWorkOrder extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'fleet_maintenance_work_orders';

    protected $fillable = [
        'company_id',
        'branch_id',
        'wo_number',
        'maintenance_schedule_id',
        'vehicle_id',
        'maintenance_type',
        'maintenance_category',
        'execution_type',
        'vendor_id',
        'assigned_technician_id',
        'scheduled_date',
        'estimated_start_date',
        'estimated_completion_date',
        'actual_start_date',
        'actual_completion_date',
        'estimated_cost',
        'estimated_labor_cost',
        'estimated_material_cost',
        'estimated_other_cost',
        'actual_cost',
        'actual_labor_cost',
        'actual_material_cost',
        'actual_other_cost',
        'estimated_downtime_hours',
        'actual_downtime_hours',
        'downtime_start',
        'downtime_end',
        'work_description',
        'work_performed',
        'technician_notes',
        'parts_used',
        'status',
        'priority',
        'cost_classification',
        'is_capital_improvement',
        'capitalization_threshold',
        'life_extension_months',
        'approved_by',
        'approved_at',
        'completed_by',
        'completed_at',
        'gl_posted',
        'gl_journal_id',
        'gl_posted_at',
        'attachments',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'estimated_start_date' => 'datetime',
        'estimated_completion_date' => 'datetime',
        'actual_start_date' => 'datetime',
        'actual_completion_date' => 'datetime',
        'downtime_start' => 'datetime',
        'downtime_end' => 'datetime',
        'estimated_cost' => 'decimal:2',
        'estimated_labor_cost' => 'decimal:2',
        'estimated_material_cost' => 'decimal:2',
        'estimated_other_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'actual_labor_cost' => 'decimal:2',
        'actual_material_cost' => 'decimal:2',
        'actual_other_cost' => 'decimal:2',
        'estimated_downtime_hours' => 'decimal:2',
        'actual_downtime_hours' => 'decimal:2',
        'capitalization_threshold' => 'decimal:2',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'gl_posted_at' => 'datetime',
        'is_capital_improvement' => 'boolean',
        'gl_posted' => 'boolean',
        'attachments' => 'array',
        'parts_used' => 'array',
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

    public function maintenanceSchedule()
    {
        return $this->belongsTo(FleetMaintenanceSchedule::class, 'maintenance_schedule_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(\App\Models\Assets\Asset::class, 'vehicle_id');
    }

    public function vendor()
    {
        return $this->belongsTo(\App\Models\Supplier::class, 'vendor_id');
    }

    public function assignedTechnician()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_technician_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'completed_by');
    }

    public function glJournal()
    {
        return $this->belongsTo(\App\Models\Journal::class, 'gl_journal_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function costs()
    {
        return $this->hasMany(FleetMaintenanceWorkOrderCost::class, 'work_order_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('scheduled_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled']);
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
    public function calculateDowntime()
    {
        if ($this->downtime_start && $this->downtime_end) {
            $hours = $this->downtime_start->diffInHours($this->downtime_end);
            $this->actual_downtime_hours = $hours;
            $this->save();
        }
        return $this;
    }
}
