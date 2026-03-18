<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisposalApproval extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'disposal_id',
        'approval_level',
        'status',
        'comments',
        'rejection_reason',
        'approver_id',
        'approved_at',
        'rejected_at',
        'created_by',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // Relationships
    public function disposal()
    {
        return $this->belongsTo(AssetDisposal::class, 'disposal_id');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approver_id');
    }
}
