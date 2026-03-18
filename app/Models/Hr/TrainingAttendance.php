<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingAttendance extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_training_attendance';

    protected $fillable = [
        'program_id',
        'employee_id',
        'attendance_status',
        'completion_date',
        'certification_received',
        'evaluation_score',
    ];

    protected $casts = [
        'completion_date' => 'date',
        'certification_received' => 'boolean',
        'evaluation_score' => 'decimal:2',
    ];

    /**
     * Attendance status values
     */
    const STATUS_REGISTERED = 'registered';
    const STATUS_ATTENDED = 'attended';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ABSENT = 'absent';

    /**
     * Relationships
     */
    public function program()
    {
        return $this->belongsTo(TrainingProgram::class, 'program_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Scopes
     */
    public function scopeCompleted($query)
    {
        return $query->where('attendance_status', self::STATUS_COMPLETED);
    }

    public function scopeAttended($query)
    {
        return $query->where('attendance_status', self::STATUS_ATTENDED);
    }
}

