<?php

namespace App\Models\Fleet;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Assets\Asset;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;
use Carbon\Carbon;

class FleetComplianceRecord extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'fleet_compliance_records';

    protected $fillable = [
        'company_id',
        'branch_id',
        'record_number',
        'compliance_type',
        'vehicle_id',
        'driver_id',
        'document_number',
        'issuer_name',
        'issue_date',
        'expiry_date',
        'renewal_reminder_date',
        'status',
        'compliance_status',
        'premium_amount',
        'currency',
        'payment_frequency',
        'description',
        'terms_conditions',
        'notes',
        'attachments',
        'parent_record_id',
        'auto_renewal_enabled',
        'reminder_sent',
        'last_reminder_sent_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'renewal_reminder_date' => 'date',
        'premium_amount' => 'decimal:2',
        'attachments' => 'array',
        'auto_renewal_enabled' => 'boolean',
        'reminder_sent' => 'boolean',
        'last_reminder_sent_at' => 'datetime',
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
        return $this->belongsTo(Asset::class, 'vehicle_id');
    }

    public function driver()
    {
        return $this->belongsTo(FleetDriver::class, 'driver_id');
    }

    public function parentRecord()
    {
        return $this->belongsTo(FleetComplianceRecord::class, 'parent_record_id');
    }

    public function renewalRecords()
    {
        return $this->hasMany(FleetComplianceRecord::class, 'parent_record_id');
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
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now())
            ->where('status', '!=', 'renewed');
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>=', now())
            ->whereIn('status', ['active', 'pending_renewal']);
    }

    public function scopeForVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeForDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('compliance_type', $type);
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
    public function isExpired()
    {
        return $this->expiry_date < now() && $this->status !== 'renewed';
    }

    public function isExpiringSoon($days = 30)
    {
        return $this->expiry_date <= now()->addDays($days) 
            && $this->expiry_date >= now()
            && in_array($this->status, ['active', 'pending_renewal']);
    }

    public function daysUntilExpiry()
    {
        return (int) round(now()->diffInDays($this->expiry_date, false));
    }

    public function getComplianceStatusColor()
    {
        return match($this->compliance_status) {
            'compliant' => 'success',
            'warning' => 'warning',
            'non_compliant' => 'danger',
            'critical' => 'dark',
            default => 'secondary',
        };
    }

    public function getStatusColor()
    {
        return match($this->status) {
            'active' => 'success',
            'pending_renewal' => 'warning',
            'expired' => 'danger',
            'renewed' => 'info',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }

    public static function generateRecordNumber($type = 'COMP')
    {
        $prefix = $type . '-';
        $date = date('Ymd');

        $lastRecord = static::where('record_number', 'like', $prefix . $date . '%')
            ->orderBy('record_number', 'desc')
            ->first();

        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->record_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
