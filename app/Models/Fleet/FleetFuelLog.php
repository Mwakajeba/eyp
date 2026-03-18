<?php

namespace App\Models\Fleet;

use App\Models\Company;
use App\Models\Branch;
use App\Models\ChartAccount;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class FleetFuelLog extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'fleet_fuel_logs';

    protected $fillable = [
        'company_id',
        'branch_id',
        'trip_id',
        'vehicle_id',
        'fuel_station',
        'fuel_type',
        'liters_filled',
        'cost_per_liter',
        'total_cost',
        'odometer_reading',
        'previous_odometer',
        'fuel_card_number',
        'fuel_card_type',
        'fuel_card_used',
        'receipt_number',
        'receipt_attachment',
        'attachments',
        'date_filled',
        'time_filled',
        'km_since_last_fill',
        'fuel_efficiency_km_per_liter',
        'cost_per_km',
        'gl_account_id',
        'is_posted_to_gl',
        'gl_journal_id',
        'gl_posted_date',
        'approval_status',
        'approved_by',
        'approved_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'liters_filled' => 'decimal:2',
        'cost_per_liter' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'odometer_reading' => 'decimal:2',
        'previous_odometer' => 'decimal:2',
        'km_since_last_fill' => 'decimal:2',
        'fuel_efficiency_km_per_liter' => 'decimal:2',
        'cost_per_km' => 'decimal:2',
        'date_filled' => 'date',
        'time_filled' => 'datetime',
        'gl_posted_date' => 'date',
        'fuel_card_used' => 'boolean',
        'is_posted_to_gl' => 'boolean',
        'approved_at' => 'datetime',
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

    public function trip()
    {
        return $this->belongsTo(FleetTrip::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(\App\Models\Assets\Asset::class, 'vehicle_id');
    }

    public function glAccount()
    {
        return $this->belongsTo(ChartAccount::class, 'gl_account_id');
    }

    public function glJournal()
    {
        return $this->belongsTo(\App\Models\Journal::class, 'gl_journal_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
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
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeForVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeForTrip($query, $tripId)
    {
        return $query->where('trip_id', $tripId);
    }

    // Calculated attributes
    public function calculateFuelEfficiency()
    {
        if ($this->previous_odometer && $this->odometer_reading && $this->liters_filled && $this->liters_filled > 0) {
            $distance = $this->odometer_reading - $this->previous_odometer;
            if ($distance > 0) {
                $this->km_since_last_fill = $distance;
                $this->fuel_efficiency_km_per_liter = $distance / $this->liters_filled;
                $this->cost_per_km = $this->total_cost / $distance;
                $this->save();
            }
        }
        return $this;
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