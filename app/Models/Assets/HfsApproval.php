<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HfsApproval extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'hfs_id',
        'approval_level',
        'status',
        'approver_id',
        'approved_at',
        'rejected_at',
        'comments',
        'rejection_reason',
        'modification_request',
        'approval_signature',
        'is_digital_signature',
        'checks_performed',
        'created_by',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'is_digital_signature' => 'boolean',
        'checks_performed' => 'array',
    ];

    // Relationships
    public function hfsRequest()
    {
        return $this->belongsTo(HfsRequest::class, 'hfs_id');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approver_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('approval_level', $level);
    }

    // Helper methods
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
