<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Applicant extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_applicants';

    protected $fillable = [
        'company_id',
        'vacancy_requisition_id',
        'application_number',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone_number',
        'date_of_birth',
        'gender',
        'address',
        'qualification',
        'qualifications',
        'qualification_documents',
        'years_of_experience',
        'cover_letter',
        'resume_path',
        'cv_path',
        'status',
        'is_shortlisted',
        'shortlisted_at',
        'shortlisted_by',
        'submission_source',
        'total_eligibility_score',
        'converted_to_employee_id',
        'converted_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'converted_at' => 'datetime',
        'qualifications' => 'array',
        'qualification_documents' => 'array',
    ];

    const STATUS_APPLIED = 'applied';
    const STATUS_ELIGIBLE = 'eligible'; // Validated – Eligible
    const STATUS_INVITED = 'invited';   // Interview Invited
    const STATUS_SCREENING = 'screening';
    const STATUS_INTERVIEW = 'interview';
    const STATUS_OFFERED = 'offered';
    const STATUS_HIRED = 'hired';
    const STATUS_REJECTED = 'rejected';
    const STATUS_WITHDRAWN = 'withdrawn';

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function vacancyRequisition()
    {
        return $this->belongsTo(VacancyRequisition::class);
    }

    public function eligibilityChecks()
    {
        return $this->hasMany(ApplicantEligibilityCheck::class);
    }

    public function normalizedProfile()
    {
        return $this->hasOne(ApplicantNormalizedProfile::class);
    }

    public function eligibilityChecksForVacancy($vacancyRequisitionId)
    {
        return $this->hasMany(ApplicantEligibilityCheck::class)
            ->where('vacancy_requisition_id', $vacancyRequisitionId);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'converted_to_employee_id');
    }

    public function interviews()
    {
        return $this->hasMany(InterviewRecord::class);
    }

    public function offerLetters()
    {
        return $this->hasMany(OfferLetter::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name);
    }

    public function isConverted(): bool
    {
        return !is_null($this->converted_to_employee_id);
    }

    /**
     * Get the hash ID for the applicant
     *
     * @return string
     */
    public function getHashIdAttribute()
    {
        return \Vinkla\Hashids\Facades\Hashids::encode($this->id);
    }

    /**
     * Get the route key for the model
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'hash_id';
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
        $decoded = \Vinkla\Hashids\Facades\Hashids::decode($value);
        
        if (!empty($decoded) && isset($decoded[0])) {
            return static::where('id', $decoded[0])->first();
        }
        
        // Fallback to regular ID lookup
        if (is_numeric($value)) {
            return static::where('id', $value)->first();
        }
        
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

