<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class LeaveRequest extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'request_number',
        'status',
        'reason',
        'reliever_id',
        'requires_doc',
        'policy_version',
        'meta',
        'requested_at',
        'decision_at',
        'decided_by',
        'rejection_reason',
        'total_days',
    ];

    protected $casts = [
        'requires_doc' => 'boolean',
        'meta' => 'array',
        'requested_at' => 'datetime',
        'decision_at' => 'datetime',
        'total_days' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function reliever()
    {
        return $this->belongsTo(Employee::class, 'reliever_id');
    }

    public function decider()
    {
        return $this->belongsTo(Employee::class, 'decided_by');
    }

    public function segments()
    {
        return $this->hasMany(LeaveSegment::class);
    }

    public function approvals()
    {
        return $this->hasMany(LeaveApproval::class);
    }

    public function attachments()
    {
        return $this->hasMany(LeaveAttachment::class);
    }

    public function smsLogs()
    {
        return $this->hasMany(LeaveSmsLog::class);
    }

    /**
     * Generate unique request number
     */
    public static function generateRequestNumber($companyId)
    {
        $prefix = 'LR';
        $year = date('Y');
        $month = date('m');

        $lastRequest = static::where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastRequest ? (int)substr($lastRequest->request_number, -4) + 1 : 1;

        return sprintf('%s%s%s%04d', $prefix, $year, $month, $sequence);
    }

    /**
     * Scope for pending approvals
     */
    public function scopePendingApproval($query)
    {
        return $query->whereIn('status', ['pending_manager', 'pending_hr']);
    }

    /**
     * Scope for employee's requests
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Check if request is editable
     */
    public function isEditable()
    {
        return in_array($this->status, ['draft']);
    }

    /**
     * Check if request is cancellable
     */
    public function isCancellable()
    {
        return in_array($this->status, ['pending_manager', 'pending_hr', 'approved']);
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'draft' => 'secondary',
            'pending_manager' => 'warning',
            'pending_hr' => 'info',
            'approved' => 'success',
            'taken' => 'primary',
            'cancelled' => 'dark',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'draft' => 'Draft',
            'pending_manager' => 'Pending Manager',
            'pending_hr' => 'Pending HR',
            'approved' => 'Approved',
            'taken' => 'Taken',
            'cancelled' => 'Cancelled',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    /**
     * Get the hash ID for the leave request
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
        // Try to decode the hash ID first
        $decoded = Hashids::decode($value);
        
        if (!empty($decoded) && isset($decoded[0])) {
            $leaveRequest = static::where('id', $decoded[0])->first();
            if ($leaveRequest) {
                return $leaveRequest;
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
}

