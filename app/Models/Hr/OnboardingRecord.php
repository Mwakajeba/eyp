<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingRecord extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_onboarding_records';

    protected $fillable = [
        'employee_id',
        'onboarding_checklist_id',
        'start_date',
        'completion_date',
        'status',
        'total_items',
        'completed_items',
        'progress_percent',
        'payroll_eligible',
        'payroll_activated_at',
        'assigned_to',
    ];

    protected $casts = [
        'start_date' => 'date',
        'completion_date' => 'date',
        'payroll_eligible' => 'boolean',
        'payroll_activated_at' => 'datetime',
    ];

    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ON_HOLD = 'on_hold';

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function onboardingChecklist()
    {
        return $this->belongsTo(OnboardingChecklist::class, 'onboarding_checklist_id');
    }

    public function recordItems()
    {
        return $this->hasMany(OnboardingRecordItem::class);
    }

    public function assignedToUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function updateProgress(): void
    {
        $this->completed_items = $this->recordItems()->where('is_completed', true)->count();
        $this->total_items = $this->onboardingChecklist->checklistItems()->count();
        $this->progress_percent = $this->total_items > 0 
            ? round(($this->completed_items / $this->total_items) * 100) 
            : 0;
        
        if ($this->progress_percent >= 100 && $this->status !== self::STATUS_COMPLETED) {
            $this->status = self::STATUS_COMPLETED;
            $this->completion_date = now();
        }
        
        $this->save();
    }
}

