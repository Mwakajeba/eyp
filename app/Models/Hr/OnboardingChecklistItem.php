<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingChecklistItem extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_onboarding_checklist_items';

    protected $fillable = [
        'onboarding_checklist_id',
        'item_title',
        'item_description',
        'item_type',
        'is_mandatory',
        'sequence_order',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
    ];

    const TYPE_TASK = 'task';
    const TYPE_DOCUMENT_UPLOAD = 'document_upload';
    const TYPE_POLICY_ACKNOWLEDGMENT = 'policy_acknowledgment';
    const TYPE_SYSTEM_ACCESS = 'system_access';

    public function onboardingChecklist()
    {
        return $this->belongsTo(OnboardingChecklist::class, 'onboarding_checklist_id');
    }

    public function recordItems()
    {
        return $this->hasMany(OnboardingRecordItem::class, 'checklist_item_id');
    }
}

