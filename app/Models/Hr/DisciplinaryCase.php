<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisciplinaryCase extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_disciplinary_cases';

    protected $fillable = [
        'employee_id',
        'case_number',
        'case_category',
        'incident_date',
        'reported_by',
        'description',
        'status',
        'outcome',
        'outcome_date',
        'payroll_impact',
        'resolution_notes',
        'investigated_by',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'outcome_date' => 'date',
        'payroll_impact' => 'array',
        'resolved_at' => 'datetime',
    ];

    const CATEGORY_MISCONDUCT = 'misconduct';
    const CATEGORY_ABSENTEEISM = 'absenteeism';
    const CATEGORY_PERFORMANCE = 'performance';

    const STATUS_OPEN = 'open';
    const STATUS_INVESTIGATING = 'investigating';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    const OUTCOME_VERBAL_WARNING = 'verbal_warning';
    const OUTCOME_WRITTEN_WARNING = 'written_warning';
    const OUTCOME_SUSPENSION = 'suspension';
    const OUTCOME_TERMINATION = 'termination';

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function reportedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'reported_by');
    }

    public function investigatedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'investigated_by');
    }

    public function resolvedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'resolved_by');
    }
}

