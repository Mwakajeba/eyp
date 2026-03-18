<?php

namespace App\Models\Fleet;

use App\Models\Inventory\Item;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FleetMaintenanceWorkOrderCost extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'fleet_maintenance_work_order_costs';

    protected $fillable = [
        'work_order_id',
        'cost_type',
        'description',
        'inventory_item_id',
        'purchase_order_id',
        'purchase_invoice_id',
        'supplier_id',
        'employee_id',
        'quantity',
        'unit',
        'unit_cost',
        'total_cost',
        'tax_amount',
        'total_with_tax',
        'cost_date',
        'status',
        'attachments',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_with_tax' => 'decimal:2',
        'cost_date' => 'date',
        'attachments' => 'array',
    ];

    public function workOrder()
    {
        return $this->belongsTo(FleetMaintenanceWorkOrder::class, 'work_order_id');
    }

    public function inventoryItem()
    {
        return $this->belongsTo(Item::class, 'inventory_item_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
