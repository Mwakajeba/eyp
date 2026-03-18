<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grievance extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_grievances';

    protected $fillable = [
        'employee_id',
        'grievance_number',
        'complaint_type',
        'description',
        'priority',
        'status',
        'assigned_to',
        'resolution',
        'investigation_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    const TYPE_HARASSMENT = 'harassment';
    const TYPE_DISCRIMINATION = 'discrimination';
    const TYPE_WORKPLACE = 'workplace';
    const TYPE_SALARY = 'salary';
    const TYPE_OTHER = 'other';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    const STATUS_OPEN = 'open';
    const STATUS_INVESTIGATING = 'investigating';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignedToUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function resolvedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'resolved_by');
    }
}

