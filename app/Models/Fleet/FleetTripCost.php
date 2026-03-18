<?php

namespace App\Models\Fleet;

use App\Models\Company;
use App\Models\Branch;
use App\Models\ChartAccount;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class FleetTripCost extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'fleet_trip_costs';

    protected $fillable = [
        'company_id',
        'branch_id',
        'trip_id',
        'vehicle_id',
        'cost_category_id',
        'cost_type',
        'amount',
        'currency',
        'description',
        'date_incurred',
        'time_incurred',
        'fuel_liters',
        'fuel_price_per_liter',
        'fuel_site',
        'fuel_card_number',
        'odometer_reading',
        'driver_allowance_amount',
        'overtime_hours',
        'overtime_rate',
        'toll_point_name',
        'toll_receipt_number',
        'gl_account_id',
        'is_posted_to_gl',
        'gl_journal_id',
        'gl_posted_date',
        'is_billable_to_customer',
        'billable_amount',
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'receipt_number',
        'receipt_attachment',
        'attachments',
        'notes',
        'paid_from_bank_account_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'billable_amount' => 'decimal:2',
        'date_incurred' => 'date',
        'gl_posted_date' => 'date',
        'fuel_liters' => 'decimal:2',
        'fuel_price_per_liter' => 'decimal:2',
        'odometer_reading' => 'decimal:2',
        'driver_allowance_amount' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'is_billable_to_customer' => 'boolean',
        'is_posted_to_gl' => 'boolean',
        'approved_at' => 'datetime',
        'attachments' => 'array',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function trip()
    {
        return $this->belongsTo(FleetTrip::class, 'trip_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(\App\Models\Assets\Asset::class, 'vehicle_id');
    }

    public function costCategory()
    {
        return $this->belongsTo(FleetCostCategory::class, 'cost_category_id');
    }

    public function glAccount()
    {
        return $this->belongsTo(ChartAccount::class, 'gl_account_id');
    }

    public function paidFromBankAccount()
    {
        return $this->belongsTo(\App\Models\BankAccount::class, 'paid_from_bank_account_id');
    }

    public function glJournal()
    {
        return $this->belongsTo(\App\Models\Journal::class, 'gl_journal_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeBillable($query)
    {
        return $query->where('is_billable_to_customer', true);
    }

    public function scopeForCompany($query, $companyId = null)
    {
        if ($companyId) {
            return $query->where('company_id', $companyId);
        }
        return $query;
    }

    public function scopeForBranch($query, $branchId = null)
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }

    public function scopeByCostType($query, $costType)
    {
        return $query->where('cost_type', $costType);
    }

    // Hash ID support
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function getRouteKeyName()
    {
        return 'hash_id';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $decoded = Hashids::decode($value);
        
        if (!empty($decoded)) {
            return static::where('id', $decoded[0])->first();
        }
        
        return static::where('id', $value)->first();
    }

    public function getRouteKey()
    {
        return $this->hash_id;
    }
}