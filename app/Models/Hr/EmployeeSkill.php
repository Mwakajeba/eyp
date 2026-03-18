<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSkill extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_employee_skills';

    protected $fillable = [
        'employee_id',
        'skill_name',
        'skill_level',
        'certification_name',
        'certification_expiry',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'certification_expiry' => 'date',
        'verified_at' => 'datetime',
    ];

    /**
     * Skill level values
     */
    const LEVEL_BEGINNER = 'beginner';
    const LEVEL_INTERMEDIATE = 'intermediate';
    const LEVEL_ADVANCED = 'advanced';
    const LEVEL_EXPERT = 'expert';

    /**
     * Relationships
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function verifier()
    {
        return $this->belongsTo(\App\Models\User::class, 'verified_by');
    }

    /**
     * Check if certification is expired
     */
    public function isCertificationExpired(): bool
    {
        if (!$this->certification_expiry) {
            return false;
        }
        return $this->certification_expiry < now();
    }

    /**
     * Check if certification is expiring soon
     */
    public function isCertificationExpiringSoon(int $days = 30): bool
    {
        if (!$this->certification_expiry) {
            return false;
        }
        return $this->certification_expiry->isFuture()
            && $this->certification_expiry->diffInDays(now()) <= $days;
    }
}

