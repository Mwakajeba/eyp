<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetWorkflowApprover extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'user_id',
        'approval_order',
        'max_approval_amount',
        'can_approve_all',
        'is_active',
    ];

    protected $casts = [
        'max_approval_amount' => 'decimal:2',
        'can_approve_all' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the workflow this approver belongs to
     */
    public function workflow()
    {
        return $this->belongsTo(FleetApprovalWorkflow::class, 'workflow_id');
    }

    /**
     * Get the user who is the approver
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Scope to get active approvers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by approval order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('approval_order');
    }
}