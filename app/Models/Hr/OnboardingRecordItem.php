<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingRecordItem extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_onboarding_record_items';

    protected $fillable = [
        'onboarding_record_id',
        'checklist_item_id',
        'is_completed',
        'completed_at',
        'completed_by',
        'notes',
        'document_path',
        'acknowledged_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function onboardingRecord()
    {
        return $this->belongsTo(OnboardingRecord::class);
    }

    public function checklistItem()
    {
        return $this->belongsTo(OnboardingChecklistItem::class, 'checklist_item_id');
    }

    public function completedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'completed_by');
    }

    public function markCompleted($userId = null): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'completed_by' => $userId ?? auth()->id(),
        ]);
        
        $this->onboardingRecord->updateProgress();
    }
}

