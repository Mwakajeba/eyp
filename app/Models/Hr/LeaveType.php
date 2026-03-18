<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'is_paid',
        'allow_half_day',
        'allow_hourly',
        'allow_negative',
        'min_duration_hours',
        'max_consecutive_days',
        'notice_days',
        'doc_required_after_days',
        'encashable',
        'carryover_cap_days',
        'carryover_expiry_date',
        'weekend_holiday_mode',
        'eligibility',
        'is_active',
        'annual_entitlement',
        'accrual_type',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'allow_half_day' => 'boolean',
        'allow_hourly' => 'boolean',
        'allow_negative' => 'boolean',
        'encashable' => 'boolean',
        'is_active' => 'boolean',
        'weekend_holiday_mode' => 'array',
        'eligibility' => 'array',
        'carryover_expiry_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function balances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function requests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function carryovers()
    {
        return $this->hasMany(LeaveCarryover::class);
    }

    /**
     * Scope to get active leave types only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get leave types for a specific company
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Get the hash ID for the leave type
     *
     * @return string
     */
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the route key for the model
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    /**
     * Resolve the model from the route parameter
     *
     * @param string $value
     * @param string|null $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Try to decode the hash ID first
        $decoded = Hashids::decode($value);
        
        if (!empty($decoded) && isset($decoded[0])) {
            $leaveType = static::where('id', $decoded[0])->first();
            if ($leaveType) {
                return $leaveType;
            }
        }
        
        // Fallback to regular ID lookup (in case it's a numeric ID)
        if (is_numeric($value)) {
            return static::where('id', $value)->first();
        }
        
        // If neither hash ID nor numeric ID works, return null (will trigger 404)
        return null;
    }

    /**
     * Get the route key for the model
     *
     * @return string
     */
    public function getRouteKey()
    {
        return $this->hash_id;
    }
}

