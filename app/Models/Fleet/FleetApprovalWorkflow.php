<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FleetApprovalWorkflow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'workflow_type',
        'description',
        'min_amount',
        'max_amount',
        'requires_multiple_approvers',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'requires_multiple_approvers' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns this workflow
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    /**
     * Get the approvers for this workflow
     */
    public function approvers()
    {
        return $this->hasMany(FleetWorkflowApprover::class, 'workflow_id')->ordered();
    }

    /**
     * Get the user who created this workflow
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this workflow
     */
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Scope to get active workflows
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by company
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter by workflow type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('workflow_type', $type);
    }

    /**
     * Check if an amount requires approval
     */
    public function requiresApproval($amount)
    {
        if ($this->min_amount && $amount < $this->min_amount) {
            return false;
        }

        if ($this->max_amount && $amount > $this->max_amount) {
            return false;
        }

        return true;
    }

    /**
     * Get workflow type options
     */
    public static function getWorkflowTypeOptions()
    {
        return [
            'trip_request' => 'Trip Request',
            'maintenance' => 'Maintenance Request',
            'fuel_request' => 'Fuel Request',
            'vehicle_assignment' => 'Vehicle Assignment',
            'cost_approval' => 'Cost Approval',
        ];
    }
}