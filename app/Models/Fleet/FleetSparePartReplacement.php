<?php

namespace App\Models\Fleet;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Assets\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class FleetSparePartReplacement extends Model
{
    use HasFactory;

    protected $table = 'fleet_spare_part_replacements';

    protected $fillable = [
        'company_id',
        'branch_id',
        'vehicle_id',
        'spare_part_category_id',
        'replaced_at',
        'odometer_at_replacement',
        'cost',
        'reason',
        'attachments',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'replaced_at' => 'date',
        'odometer_at_replacement' => 'decimal:2',
        'cost' => 'decimal:2',
        'attachments' => 'array',
        'approved_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

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

    public function sparePartCategory()
    {
        return $this->belongsTo(FleetSparePartCategory::class, 'spare_part_category_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
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
