<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantEligibilityCheck extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_applicant_eligibility_checks';

    protected $fillable = [
        'applicant_id',
        'eligibility_rule_id',
        'vacancy_requisition_id',
        'passed',
        'reason',
        'checked_value',
        'expected_value',
        'checked_at',
    ];

    protected $casts = [
        'passed' => 'boolean',
        'checked_value' => 'array',
        'expected_value' => 'array',
        'checked_at' => 'datetime',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function eligibilityRule(): BelongsTo
    {
        return $this->belongsTo(EligibilityRule::class);
    }

    public function vacancyRequisition(): BelongsTo
    {
        return $this->belongsTo(VacancyRequisition::class);
    }

    /**
     * Scope for passed checks
     */
    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    /**
     * Scope for failed checks
     */
    public function scopeFailed($query)
    {
        return $query->where('passed', false);
    }

    /**
     * Scope for mandatory failed checks
     */
    public function scopeMandatoryFailed($query)
    {
        return $query->where('passed', false)
            ->whereHas('eligibilityRule', function ($q) {
                $q->where('is_mandatory', true);
            });
    }
}
