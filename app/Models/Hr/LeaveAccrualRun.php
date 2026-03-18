<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveAccrualRun extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'period',
        'status',
        'employees_processed',
        'error_message',
        'meta',
    ];

    protected $casts = [
        'period' => 'date',
        'meta' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    /**
     * Mark run as completed
     */
    public function markCompleted($employeesProcessed)
    {
        $this->update([
            'status' => 'completed',
            'employees_processed' => $employeesProcessed,
        ]);
    }

    /**
     * Mark run as failed
     */
    public function markFailed($errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}

