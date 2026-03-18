<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class VacancyRequisition extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_vacancy_requisitions';

    protected $fillable = [
        'company_id',
        'position_id',
        'department_id',
        'requisition_number',
        'job_title',
        'job_description',
        'requirements',
        'number_of_positions',
        'budgeted_salary_min',
        'budgeted_salary_max',
        'opening_date',
        'closing_date',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejection_reason',
        // Blueprint enhancements
        'hiring_justification',
        'cost_center_id',
        'budget_line_id',
        'project_grant_code',
        'contract_period_months',
        'recruitment_type',
        'is_publicly_posted',
        'posting_start_date',
        'posting_end_date',
        'published_to_portal',
        'published_at',
        'public_slug',
    ];

    protected $casts = [
        'budgeted_salary_min' => 'decimal:2',
        'budgeted_salary_max' => 'decimal:2',
        'opening_date' => 'date',
        'closing_date' => 'date',
        'approved_at' => 'datetime',
        'is_publicly_posted' => 'boolean',
        'posting_start_date' => 'date',
        'posting_end_date' => 'date',
        'contract_period_months' => 'integer',
        'published_to_portal' => 'boolean',
        'published_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CLOSED = 'closed';
    const STATUS_FILLED = 'filled';

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(Department::class, 'cost_center_id');
    }

    public function budgetLine()
    {
        return $this->belongsTo(\App\Models\BudgetLine::class);
    }

    public function requestedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class, 'vacancy_requisition_id');
    }

    public function eligibilityRules()
    {
        return $this->hasMany(EligibilityRule::class, 'vacancy_requisition_id');
    }

    public function approvalHistory()
    {
        return $this->morphMany(\App\Models\ApprovalHistory::class, 'approvable');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_CLOSED]);
    }

    public function scopePublished($query)
    {
        return $query->where('published_to_portal', true)
            ->where('status', self::STATUS_APPROVED);
    }

    public function scopeCurrentlyAcceptingApplications($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('closing_date')
              ->orWhere('closing_date', '>=', now()->toDateString());
        });
    }

    /**
     * Get the hash ID for the vacancy requisition
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
        \Log::info('Resolving route binding for VacancyRequisition: ' . $value);
        
        // Try to decode the hash ID first
        $decoded = Hashids::decode($value);
        \Log::info('Decoded hash ID: ' . json_encode($decoded));
        
        if (!empty($decoded) && isset($decoded[0])) {
            $requisition = static::where('id', $decoded[0])->first();
            if ($requisition) {
                \Log::info('Found requisition by ID: ' . $requisition->id);
                return $requisition;
            }
        }
        
        // Fallback to regular ID lookup (in case it's a numeric ID)
        if (is_numeric($value)) {
            $requisition = static::where('id', $value)->first();
            if ($requisition) {
                \Log::info('Found requisition by numeric ID: ' . $requisition->id);
                return $requisition;
            }
        }
        
        \Log::warning('Failed to resolve route binding for: ' . $value);
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

