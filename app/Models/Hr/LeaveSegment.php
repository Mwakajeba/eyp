<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveSegment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'leave_request_id',
        'start_at',
        'end_at',
        'granularity',
        'days_equivalent',
        'calculation',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'days_equivalent' => 'decimal:2',
        'calculation' => 'array',
    ];

    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }

    /**
     * Get formatted date range
     */
    public function getDateRangeAttribute()
    {
        if ($this->granularity === 'hourly') {
            return $this->start_at->format('d M Y H:i') . ' - ' . $this->end_at->format('d M Y H:i');
        }

        if ($this->start_at->isSameDay($this->end_at)) {
            return $this->start_at->format('d M Y') . ($this->granularity === 'half_day' ? ' (Half Day)' : '');
        }

        return $this->start_at->format('d M Y') . ' - ' . $this->end_at->format('d M Y');
    }
}

