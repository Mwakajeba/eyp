<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingProgram extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_training_programs';

    protected $fillable = [
        'company_id',
        'program_code',
        'program_name',
        'provider',
        'cost',
        'duration_days',
        'funding_source',
        'description',
        'is_active',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'duration_days' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Provider types
     */
    const PROVIDER_INTERNAL = 'internal';
    const PROVIDER_EXTERNAL = 'external';

    /**
     * Funding sources
     */
    const FUNDING_SDL = 'sdl';
    const FUNDING_INTERNAL = 'internal';
    const FUNDING_DONOR = 'donor';

    /**
     * Relationships
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function attendance()
    {
        return $this->hasMany(TrainingAttendance::class, 'program_id');
    }

    public function trainingBonds()
    {
        return $this->hasMany(TrainingBond::class, 'training_program_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

