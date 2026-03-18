<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewRecord extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_interview_records';

    protected $fillable = [
        'applicant_id',
        'vacancy_requisition_id',
        'interview_type',
        'round_number',
        'interview_date',
        'interview_time',
        'location',
        'meeting_link',
        'interviewers',
        'overall_score',
        'detailed_scores',
        'feedback',
        'panel_comments',
        'strengths',
        'weaknesses',
        'recommendation',
        'status',
        'response_notes',
        'responded_at',
        'interviewed_by',
    ];

    protected $casts = [
        'interview_date' => 'date',
        'interviewers' => 'array',
        'overall_score' => 'decimal:2',
        'detailed_scores' => 'array',
        'panel_comments' => 'array',
        'responded_at' => 'datetime',
    ];

    const TYPE_PHONE = 'phone';
    const TYPE_VIDEO = 'video';
    const TYPE_IN_PERSON = 'in_person';
    const TYPE_PANEL = 'panel';

    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_INVITED = 'invited';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_DECLINED = 'declined';
    const STATUS_RESCHEDULED = 'rescheduled';
    const STATUS_CONDUCTED = 'conducted';
    const STATUS_CANCELLED = 'cancelled';

    const RECOMMENDATION_HIRE = 'hire';
    const RECOMMENDATION_MAYBE = 'maybe';
    const RECOMMENDATION_REJECT = 'reject';
    const RECOMMENDATION_NEXT_ROUND = 'next_round';

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function vacancyRequisition()
    {
        return $this->belongsTo(VacancyRequisition::class);
    }

    public function interviewer()
    {
        return $this->belongsTo(\App\Models\User::class, 'interviewed_by');
    }
}

