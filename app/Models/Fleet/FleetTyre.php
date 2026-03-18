<?php

namespace App\Models\Fleet;

use App\Models\Company;
use App\Models\Branch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class FleetTyre extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'fleet_tyres';

    protected $fillable = [
        'company_id',
        'branch_id',
        'tyre_serial',
        'dot_number',
        'brand',
        'model',
        'tyre_size',
        'supplier',
        'purchase_date',
        'purchase_cost',
        'warranty_type',
        'warranty_limit_value',
        'expected_lifespan_km',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'warranty_limit_value' => 'decimal:2',
        'expected_lifespan_km' => 'decimal:2',
    ];

    public const STATUS_NEW = 'new';
    public const STATUS_IN_USE = 'in_use';
    public const STATUS_REMOVED = 'removed';
    public const STATUS_UNDER_WARRANTY_CLAIM = 'under_warranty_claim';
    public const STATUS_SCRAPPED = 'scrapped';

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function installations()
    {
        return $this->hasMany(FleetTyreInstallation::class, 'tyre_id');
    }

    public function currentInstallation()
    {
        return $this->hasOne(FleetTyreInstallation::class, 'tyre_id')->latestOfMany('installed_at');
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

    protected static function booted()
    {
        static::creating(function (FleetTyre $tyre) {
            if (empty($tyre->tyre_serial)) {
                $tyre->tyre_serial = 'TYR-' . str_pad((string) (static::forCompany($tyre->company_id)->withTrashed()->count() + 1), 6, '0', STR_PAD_LEFT);
            }
        });
    }
}
