<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveSmsLog extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'leave_request_id',
        'recipient_id',
        'phone_number',
        'message',
        'type',
        'status',
        'error_message',
        'response',
    ];

    protected $casts = [
        'response' => 'array',
    ];

    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }

    public function recipient()
    {
        return $this->belongsTo(Employee::class, 'recipient_id');
    }

    /**
     * Mark SMS as sent
     */
    public function markSent($response = null)
    {
        $this->update([
            'status' => 'sent',
            'response' => $response,
        ]);
    }

    /**
     * Mark SMS as failed
     */
    public function markFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}

