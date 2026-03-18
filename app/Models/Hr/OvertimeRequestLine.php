<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRequestLine extends Model
{
    use LogsActivity;

    protected $table = 'hr_overtime_request_lines';

    protected $fillable = [
        'overtime_request_id',
        'overtime_hours',
        'day_type',
        'overtime_rate',
    ];

    protected $casts = [
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function overtimeRequest(): BelongsTo
    {
        return $this->belongsTo(OvertimeRequest::class, 'overtime_request_id');
    }

    /**
     * Calculate overtime amount for this line
     */
    public function getOvertimeAmountAttribute(): float
    {
        return $this->overtime_hours * $this->overtime_rate;
    }
}

