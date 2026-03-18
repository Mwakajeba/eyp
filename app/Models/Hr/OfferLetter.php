<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferLetter extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_offer_letters';

    protected $fillable = [
        'applicant_id',
        'vacancy_requisition_id',
        'offer_number',
        'offer_letter_path',
        'offered_salary',
        'offer_date',
        'expiry_date',
        'proposed_start_date',
        'terms_and_conditions',
        'status',
        'prepared_by',
        'approved_by',
        'approved_at',
        'sent_at',
        'responded_at',
        'response_notes',
    ];

    protected $casts = [
        'offered_salary' => 'decimal:2',
        'offer_date' => 'date',
        'expiry_date' => 'date',
        'proposed_start_date' => 'date',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_SENT = 'sent';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';
    const STATUS_WITHDRAWN = 'withdrawn';

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function vacancyRequisition()
    {
        return $this->belongsTo(VacancyRequisition::class);
    }

    public function preparedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'prepared_by');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date < now();
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }
}

