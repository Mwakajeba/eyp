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

class MaintenanceHistory extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'maintenance_history';

    protected $fillable = [
        'company_id',
        'branch_id',
        'asset_id',
        'work_order_id',
        'maintenance_request_id',
        'maintenance_type_id',
        'maintenance_type',
        'maintenance_date',
        'completion_date',
        'total_cost',
        'material_cost',
        'labor_cost',
        'other_cost',
        'cost_classification',
        'capitalized_amount',
        'downtime_hours',
        'vendor_id',
        'technician_id',
        'technician_name',
        'work_performed',
        'notes',
        'life_extension_months',
        'next_maintenance_date',
        'gl_posted',
        'gl_journal_id',
        'gl_posted_at',
        'attachments',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'completion_date' => 'date',
        'next_maintenance_date' => 'date',
        'total_cost' => 'decimal:2',
        'material_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'capitalized_amount' => 'decimal:2',
        'gl_posted' => 'boolean',
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

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function maintenanceRequest()
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function maintenanceType()
    {
        return $this->belongsTo(MaintenanceType::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function glJournal()
    {
        return $this->belongsTo(Journal::class, 'gl_journal_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
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

    public function scopeExpensed($query)
    {
        return $query->where('cost_classification', 'expense');
    }

    public function scopeCapitalized($query)
    {
        return $query->where('cost_classification', 'capitalized');
    }
}
