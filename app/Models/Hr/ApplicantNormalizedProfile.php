<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantNormalizedProfile extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_applicant_normalized_profiles';

    protected $fillable = [
        'applicant_id',
        'education_level',
        'education_field',
        'years_of_experience',
        'current_role',
        'skills',
        'certifications',
        'ai_confidence_score',
        'requires_hr_review',
        'is_manually_overridden',
        'overridden_by',
        'overridden_at',
        'override_reason',
        'normalization_log',
    ];

    protected $casts = [
        'skills' => 'array',
        'certifications' => 'array',
        'normalization_log' => 'array',
        'ai_confidence_score' => 'decimal:2',
        'years_of_experience' => 'decimal:2',
        'requires_hr_review' => 'boolean',
        'is_manually_overridden' => 'boolean',
        'overridden_at' => 'datetime',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function overrider(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'overridden_by');
    }
}
