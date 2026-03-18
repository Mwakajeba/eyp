<?php

namespace App\Models\Fleet;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Assets\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class FleetTyreReplacementRequest extends Model
{
    use HasFactory;

    protected $table = 'fleet_tyre_replacement_requests';

    protected $fillable = [
        'company_id',
        'branch_id',
        'vehicle_id',
        'tyre_position_id',
        'current_tyre_id',
        'current_installation_id',
        'reason',
        'mileage_at_request',
        'tyre_mileage_used',
        'photos',
        'risk_score',
        'status',
        'rejection_reason',
        'requested_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'mileage_at_request' => 'decimal:2',
        'tyre_mileage_used' => 'decimal:2',
        'photos' => 'array',
        'approved_at' => 'datetime',
    ];

    public const REASON_WORN_OUT = 'worn_out';
    public const REASON_BURST = 'burst';
    public const REASON_SIDE_CUT = 'side_cut';
    public const REASON_OTHER = 'other';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_INSPECTED = 'inspected';

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

    public function tyrePosition()
    {
        return $this->belongsTo(FleetTyrePosition::class, 'tyre_position_id');
    }

    public function currentTyre()
    {
        return $this->belongsTo(FleetTyre::class, 'current_tyre_id');
    }

    public function currentInstallation()
    {
        return $this->belongsTo(FleetTyreInstallation::class, 'current_installation_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
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
