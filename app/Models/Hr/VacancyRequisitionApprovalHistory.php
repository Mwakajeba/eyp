<?php

namespace App\Models\Hr;

use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacancyRequisitionApprovalHistory extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_vacancy_requisition_approvals';

    protected $fillable = [
        'vacancy_requisition_id',
        'approval_level_id',
        'approver_id',
        'action',
        'action_at',
        'comments',
    ];

    protected $casts = [
        'action_at' => 'datetime',
        'approval_level_id' => 'integer',
    ];

    public function vacancyRequisition()
    {
        return $this->belongsTo(VacancyRequisition::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function approvalLevel()
    {
        return $this->belongsTo(\App\Models\ApprovalLevel::class, 'approval_level_id');
    }
}
