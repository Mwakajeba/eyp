<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BiometricDevice extends Model
{
    use LogsActivity;

    protected $table = 'hr_biometric_devices';

    protected $fillable = [
        'company_id',
        'branch_id',
        'device_code',
        'device_name',
        'device_type',
        'device_model',
        'serial_number',
        'ip_address',
        'port',
        'api_key',
        'api_secret',
        'connection_type',
        'connection_config',
        'timezone',
        'auto_sync',
        'sync_interval_minutes',
        'last_sync_at',
        'last_successful_sync_at',
        'sync_failure_count',
        'last_error',
        'is_active',
        'description',
    ];

    protected $casts = [
        'connection_config' => 'array',
        'auto_sync' => 'boolean',
        'sync_interval_minutes' => 'integer',
        'sync_failure_count' => 'integer',
        'last_sync_at' => 'datetime',
        'last_successful_sync_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'api_secret',
    ];

    /**
     * Device type constants
     */
    const TYPE_FINGERPRINT = 'fingerprint';
    const TYPE_FACE = 'face';
    const TYPE_CARD = 'card';
    const TYPE_PALM = 'palm';

    /**
     * Connection type constants
     */
    const CONNECTION_API = 'api';
    const CONNECTION_TCP = 'tcp';
    const CONNECTION_UDP = 'udp';
    const CONNECTION_FILE_IMPORT = 'file_import';

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BiometricLog::class, 'device_id');
    }

    public function employeeMappings(): HasMany
    {
        return $this->hasMany(BiometricEmployeeMapping::class, 'device_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNeedsSync($query)
    {
        return $query->where('is_active', true)
            ->where('auto_sync', true)
            ->where(function ($q) {
                $q->whereNull('last_sync_at')
                    ->orWhereRaw('TIMESTAMPDIFF(MINUTE, last_sync_at, NOW()) >= sync_interval_minutes');
            });
    }

    /**
     * Generate API key for device
     */
    public function generateApiKey(): string
    {
        $this->api_key = Str::random(32);
        $this->api_secret = Str::random(64);
        $this->save();

        return $this->api_key;
    }

    /**
     * Verify API credentials
     */
    public function verifyApiCredentials($apiKey, $apiSecret): bool
    {
        return hash_equals($this->api_key, $apiKey) &&
            hash_equals($this->api_secret, $apiSecret);
    }

    /**
     * Check if device needs sync
     */
    public function needsSync(): bool
    {
        if (!$this->is_active || !$this->auto_sync) {
            return false;
        }

        if (!$this->last_sync_at) {
            return true;
        }

        $minutesSinceLastSync = $this->last_sync_at->diffInMinutes(now());
        return $minutesSinceLastSync >= $this->sync_interval_minutes;
    }

    /**
     * Mark sync as successful
     */
    public function markSyncSuccess()
    {
        $this->update([
            'last_sync_at' => now(),
            'last_successful_sync_at' => now(),
            'sync_failure_count' => 0,
            'last_error' => null,
        ]);
    }

    /**
     * Mark sync as failed
     */
    public function markSyncFailure($error)
    {
        $this->update([
            'last_sync_at' => now(),
            'sync_failure_count' => $this->sync_failure_count + 1,
            'last_error' => $error,
        ]);
    }

    /**
     * Check if device is ZKTeco device
     */
    public function isZKTecoDevice(): bool
    {
        return $this->connection_type === self::CONNECTION_TCP
            && !empty($this->ip_address);
    }

    /**
     * Get current connection status
     */
    public function getConnectionStatus(): bool
    {
        if (!$this->isZKTecoDevice()) {
            return false;
        }

        $zktecoService = app(\App\Services\Hr\ZKTecoService::class);
        return $zktecoService->isConnected($this);
    }
}
