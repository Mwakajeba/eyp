<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCompliance extends Model
{
    use LogsActivity;

    protected $table = 'hr_employee_compliance';

    protected $fillable = [
        'employee_id',
        'compliance_type',
        'compliance_number',
        'compliance_details',
        'is_valid',
        'expiry_date',
        'last_verified_at',
    ];

    protected $casts = [
        'is_valid' => 'boolean',
        'expiry_date' => 'date',
        'last_verified_at' => 'datetime',
        'compliance_details' => 'array',
    ];

    /**
     * Compliance types
     */
    const TYPE_PAYE = 'paye';
    const TYPE_PENSION = 'pension';
    const TYPE_NHIF = 'nhif';
    const TYPE_WCF = 'wcf';
    const TYPE_SDL = 'sdl';

    /**
     * Relationships
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Scopes
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid', true)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', now());
            });
    }

    public function scopeExpiring($query, $days = 30)
    {
        return $query->where('is_valid', true)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('is_valid', false)
              ->orWhere(function ($q2) {
                  $q2->whereNotNull('expiry_date')
                     ->where('expiry_date', '<', now());
              });
        });
    }

    /**
     * Check if compliance is valid
     */
    public function isValid(): bool
    {
        if (!$this->is_valid) {
            return false;
        }
        if ($this->expiry_date && $this->expiry_date < now()) {
            return false;
        }
        return true;
    }

    /**
     * Get compliance status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        if ($this->isValid()) {
            return 'success';
        }
        if ($this->expiry_date && $this->expiry_date->diffInDays(now()) <= 30) {
            return 'warning';
        }
        return 'danger';
    }
}
