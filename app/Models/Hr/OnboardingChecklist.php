<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingChecklist extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_onboarding_checklists';

    protected $fillable = [
        'company_id',
        'checklist_name',
        'description',
        'applicable_to',
        'department_id',
        'position_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    const APPLICABLE_ALL = 'all';
    const APPLICABLE_DEPARTMENT = 'department';
    const APPLICABLE_POSITION = 'position';
    const APPLICABLE_ROLE = 'role';

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function checklistItems()
    {
        return $this->hasMany(OnboardingChecklistItem::class, 'onboarding_checklist_id')->orderBy('sequence_order');
    }

    public function onboardingRecords()
    {
        return $this->hasMany(OnboardingRecord::class, 'onboarding_checklist_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

