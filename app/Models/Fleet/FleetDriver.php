<?php

namespace App\Models\Fleet;

use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class FleetDriver extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'fleet_drivers';

    protected $fillable = [
        'company_id',
        'branch_id',
        'user_id', // Links to User model
        'employee_id',
        'driver_code',
        'full_name',
        'license_number',
        'license_class',
        'license_expiry_date',
        'license_issuing_authority',
        'employment_type', // employee, contractor
        'daily_allowance_rate',
        'overtime_rate',
        'salary',
        'phone_number',
        'email',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'training_records',
        'compliance_documents',
        'last_training_date',
        'next_training_due_date',
        'assigned_vehicle_id',
        'fuel_card_bank_account_id',
        'assignment_start_date',
        'assignment_end_date',
        'status', // active, inactive, suspended, terminated
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'license_expiry_date' => 'date',
        'last_training_date' => 'date',
        'next_training_due_date' => 'date',
        'assignment_start_date' => 'date',
        'assignment_end_date' => 'date',
        'daily_allowance_rate' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'salary' => 'decimal:2',
        'training_records' => 'array',
        'compliance_documents' => 'array',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedVehicle()
    {
        return $this->belongsTo(\App\Models\Assets\Asset::class, 'assigned_vehicle_id');
    }

    /** Bank account (nature=card) assigned to this driver for fuel payments */
    public function fuelCardAccount()
    {
        return $this->belongsTo(\App\Models\BankAccount::class, 'fuel_card_bank_account_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Relationship with Trips
    public function trips()
    {
        return $this->hasMany(FleetTrip::class, 'driver_id');
    }

    // Relationship with Vehicle assignments
    public function vehicleAssignments()
    {
        return $this->hasMany(FleetDriverAssignment::class);
    }

    // Current vehicle assignment
    public function currentVehicle()
    {
        return $this->hasOne(FleetDriverAssignment::class)->whereNull('end_date');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLicenseValid($query)
    {
        return $query->where('license_expiry_date', '>', now());
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