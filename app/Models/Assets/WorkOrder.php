<?php

namespace App\Models\Assets;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Supplier;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'asset_maintenance_work_orders';

    protected $fillable = [
        'company_id',
        'branch_id',
        'wo_number',
        'maintenance_request_id',
        'asset_id',
        'maintenance_type_id',
        'maintenance_type',
        'execution_type',
        'vendor_id',
        'assigned_technician_id',
        'estimated_start_date',
        'estimated_completion_date',
        'actual_start_date',
        'actual_completion_date',
        'estimated_cost',
        'estimated_labor_cost',
        'estimated_material_cost',
        'estimated_other_cost',
        'actual_cost',
        'actual_labor_cost',
        'actual_material_cost',
        'actual_other_cost',
        'estimated_downtime_hours',
        'actual_downtime_hours',
        'cost_center_id',
        'budget_reference_id',
        'status',
        'work_description',
        'work_performed',
        'technician_notes',
        'cost_classification',
        'is_capital_improvement',
        'capitalization_threshold',
        'life_extension_months',
        'approved_by',
        'approved_at',
        'completed_by',
        'completed_at',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'gl_posted',
        'gl_journal_id',
        'gl_posted_at',
        'attachments',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'estimated_start_date' => 'date',
        'estimated_completion_date' => 'date',
        'actual_start_date' => 'date',
        'actual_completion_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'estimated_labor_cost' => 'decimal:2',
        'estimated_material_cost' => 'decimal:2',
        'estimated_other_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'actual_labor_cost' => 'decimal:2',
        'actual_material_cost' => 'decimal:2',
        'actual_other_cost' => 'decimal:2',
        'capitalization_threshold' => 'decimal:2',
        'is_capital_improvement' => 'boolean',
        'gl_posted' => 'boolean',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'gl_posted_at' => 'datetime',
        'attachments' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function maintenanceRequest()
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function maintenanceType()
    {
        return $this->belongsTo(MaintenanceType::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }

    public function assignedTechnician()
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function glJournal()
    {
        return $this->belongsTo(Journal::class, 'gl_journal_id');
    }

    public function costs()
    {
        return $this->hasMany(WorkOrderCost::class);
    }

    public function materialCosts()
    {
        return $this->hasMany(WorkOrderCost::class)->where('cost_type', 'material');
    }

    public function laborCosts()
    {
        return $this->hasMany(WorkOrderCost::class)->where('cost_type', 'labor');
    }

    public function otherCosts()
    {
        return $this->hasMany(WorkOrderCost::class)->where('cost_type', 'other');
    }

    public function maintenanceHistory()
    {
        return $this->hasOne(MaintenanceHistory::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Computed attributes
    public function getTotalEstimatedCostAttribute()
    {
        return $this->estimated_labor_cost + $this->estimated_material_cost + $this->estimated_other_cost;
    }

    public function getTotalActualCostAttribute()
    {
        return $this->actual_labor_cost + $this->actual_material_cost + $this->actual_other_cost;
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForBranch($query, $branchId)
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePendingReview($query)
    {
        return $query->where('cost_classification', 'pending_review');
    }
}
