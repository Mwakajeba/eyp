<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricEmployeeMapping extends Model
{
    use LogsActivity;

    protected $table = 'hr_biometric_employee_mappings';

    protected $fillable = [
        'device_id',
        'employee_id',
        'device_user_id',
        'device_user_name',
        'is_active',
        'mapped_at',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'mapped_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class, 'device_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Update last synced timestamp
     */
    public function markSynced()
    {
        $this->update(['last_synced_at' => now()]);
    }
}

