<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeTransfer extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_employee_transfers';

    protected $fillable = [
        'employee_id',
        'transfer_number',
        'transfer_type',
        'from_department_id',
        'to_department_id',
        'from_position_id',
        'to_position_id',
        'from_branch_id',
        'to_branch_id',
        'effective_date',
        'transfer_reason',
        'transfer_allowance',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'approval_notes',
        'cost_center_updated',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'transfer_allowance' => 'decimal:2',
        'cost_center_updated' => 'boolean',
        'approved_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';

    const TYPE_DEPARTMENT = 'department';
    const TYPE_BRANCH = 'branch';
    const TYPE_LOCATION = 'location';
    const TYPE_POSITION = 'position';

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function fromDepartment()
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment()
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function fromPosition()
    {
        return $this->belongsTo(Position::class, 'from_position_id');
    }

    public function toPosition()
    {
        return $this->belongsTo(Position::class, 'to_position_id');
    }

    public function fromBranch()
    {
        return $this->belongsTo(\App\Models\Branch::class, 'from_branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(\App\Models\Branch::class, 'to_branch_id');
    }

    public function requestedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function isEffective(): bool
    {
        return $this->effective_date <= now() && $this->status === self::STATUS_APPROVED;
    }
}

