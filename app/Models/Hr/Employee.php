<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vinkla\Hashids\Facades\Hashids;

class Employee extends Model
{
    use LogsActivity;

    protected $table = 'hr_employees';

    protected $fillable = [
        'company_id',
        'branch_id',
        'department_id',
        'position_id',
        'trade_union_id',
        'user_id',
        'employee_number',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender',
        'marital_status',
        'country',
        'region',
        'district',
        'current_physical_location',
        'email',
        'phone_number',
        'basic_salary',
        'identity_document_type',
        'identity_number',
        'employment_type',
        'date_of_employment',
        'designation',
        'tin',
        'bank_name',
        'bank_account_number',
        'status',
        'include_in_payroll',
        'has_nhif',
        'nhif_employee_percent',
        'nhif_employer_percent',
        'nhif_member_number',
        'has_pension',
        'social_fund_type',
        'social_fund_number',
        'pension_employee_percent',
        'pension_employer_percent',
        'has_trade_union',
        'trade_union_category',
        'trade_union_amount',
        'trade_union_percent',
        'has_wcf',
        'wcf_employee_percent',
        'wcf_employer_percent',
        'has_heslb',
        'heslb_employee_percent',
        'heslb_employer_percent',
        'has_sdl',
        'sdl_employee_percent',
        'sdl_employer_percent',
        'biometric_device_id',
        'biometric_device_user_id',
        'biometric_device_user_name',
        'biometric_synced_at'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_employment' => 'date',
        'basic_salary' => 'decimal:2',
        'nhif_employee_percent' => 'decimal:2',
        'nhif_employer_percent' => 'decimal:2',
        'pension_employee_percent' => 'decimal:2',
        'pension_employer_percent' => 'decimal:2',
        'trade_union_amount' => 'decimal:2',
        'trade_union_percent' => 'decimal:2',
        'wcf_employee_percent' => 'decimal:2',
        'wcf_employer_percent' => 'decimal:2',
        'heslb_employee_percent' => 'decimal:2',
        'heslb_employer_percent' => 'decimal:2',
        'sdl_employee_percent' => 'decimal:2',
        'sdl_employer_percent' => 'decimal:2',
        'has_nhif' => 'boolean',
        'has_pension' => 'boolean',
        'has_trade_union' => 'boolean',
        'has_wcf' => 'boolean',
        'has_heslb' => 'boolean',
        'has_sdl' => 'boolean',
        'include_in_payroll' => 'boolean',
        'biometric_synced_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function tradeUnion(): BelongsTo
    {
        return $this->belongsTo(TradeUnion::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function allowances(): HasMany
    {
        return $this->hasMany(\App\Models\Hr\Allowance::class);
    }

    public function externalLoans(): HasMany
    {
        return $this->hasMany(\App\Models\Hr\ExternalLoan::class);
    }

    public function salaryAdvances(): HasMany
    {
        return $this->hasMany(\App\Models\Hr\SalaryAdvance::class);
    }

    public function heslbLoans(): HasMany
    {
        return $this->hasMany(HeslbLoan::class);
    }

    public function activeHeslbLoan()
    {
        return HeslbLoan::getActiveLoan($this->id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    // Phase 1: Enhanced HR relationships
    public function employmentStatusHistory(): HasMany
    {
        return $this->hasMany(EmploymentStatusHistory::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function activeContract()
    {
        return $this->hasOne(Contract::class)->where('status', 'active')->latest('start_date');
    }

    public function positionAssignments(): HasMany
    {
        return $this->hasMany(PositionAssignment::class);
    }

    public function currentPositionAssignment()
    {
        return $this->hasOne(PositionAssignment::class)
            ->where('effective_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->latest('effective_date');
    }

    public function complianceRecords(): HasMany
    {
        return $this->hasMany(EmployeeCompliance::class);
    }

    // Phase 2: Time, Attendance & Leave relationships
    public function employeeSchedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function currentSchedule()
    {
        return $this->hasOne(EmployeeSchedule::class)
            ->where('effective_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->latest('effective_date');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function overtimeRequests(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class);
    }

    // Biometric device relationships
    public function biometricDevice(): BelongsTo
    {
        return $this->belongsTo(BiometricDevice::class, 'biometric_device_id');
    }

    /**
     * @deprecated Use biometricDevice relationship and biometric_device_user_id column instead
     */
    public function biometricMappings(): HasMany
    {
        return $this->hasMany(BiometricEmployeeMapping::class);
    }

    public function biometricLogs(): HasMany
    {
        return $this->hasMany(BiometricLog::class);
    }

    public function payGroupAssignments(): HasMany
    {
        return $this->hasMany(EmployeePayGroup::class);
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function salaryStructures(): HasMany
    {
        return $this->hasMany(EmployeeSalaryStructure::class);
    }

    // Phase 5: Performance & Training relationships
    public function appraisals(): HasMany
    {
        return $this->hasMany(Appraisal::class);
    }

    public function trainingAttendance(): HasMany
    {
        return $this->hasMany(TrainingAttendance::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(EmployeeSkill::class);
    }

    public function trainingBonds(): HasMany
    {
        return $this->hasMany(TrainingBond::class);
    }

    // Phase 6: Employment Lifecycle Management relationships
    public function onboardingRecords(): HasMany
    {
        return $this->hasMany(OnboardingRecord::class);
    }

    public function confirmationRequests(): HasMany
    {
        return $this->hasMany(ConfirmationRequest::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(EmployeeTransfer::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(EmployeePromotion::class);
    }

    // Phase 7: Discipline, Grievance & Exit relationships
    public function disciplinaryCases(): HasMany
    {
        return $this->hasMany(DisciplinaryCase::class);
    }

    public function grievances(): HasMany
    {
        return $this->hasMany(Grievance::class);
    }

    public function exits(): HasMany
    {
        return $this->hasMany(EmployeeExit::class);
    }

    /**
     * Get current pay group for employee
     */
    public function getCurrentPayGroupAttribute(): ?PayGroup
    {
        return EmployeePayGroup::getCurrentPayGroup($this->id);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name);
    }

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth ? $this->date_of_birth->diffInYears(now()) : 0;
    }

    /**
     * Get the hash ID for the employee
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
            $employee = static::where('id', $decoded[0])->first();
            if ($employee) {
                return $employee;
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

    /**
     * Phase 1: Get current employment status
     */
    public function getCurrentEmploymentStatus()
    {
        return $this->employmentStatusHistory()
            ->where('effective_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->latest('effective_date')
            ->first();
    }

    /**
     * Phase 1: Check if employee is compliant for payroll
     */
    public function isCompliantForPayroll(): bool
    {
        $requiredCompliance = [
            EmployeeCompliance::TYPE_PAYE,
            EmployeeCompliance::TYPE_PENSION,
            EmployeeCompliance::TYPE_NHIF,
        ];

        foreach ($requiredCompliance as $type) {
            $compliance = $this->complianceRecords()
                ->where('compliance_type', $type)
                ->first();

            if (!$compliance || !$compliance->isValid()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Phase 1: Get compliance score (percentage)
     */
    public function getComplianceScoreAttribute(): float
    {
        $allCompliance = [
            EmployeeCompliance::TYPE_PAYE,
            EmployeeCompliance::TYPE_PENSION,
            EmployeeCompliance::TYPE_NHIF,
            EmployeeCompliance::TYPE_WCF,
            EmployeeCompliance::TYPE_SDL,
        ];

        $validCount = 0;
        foreach ($allCompliance as $type) {
            $compliance = $this->complianceRecords()
                ->where('compliance_type', $type)
                ->first();

            if ($compliance && $compliance->isValid()) {
                $validCount++;
            }
        }

        return ($validCount / count($allCompliance)) * 100;
    }
}
