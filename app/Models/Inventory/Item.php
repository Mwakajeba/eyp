<?php

namespace App\Models\Inventory;

use App\Traits\LogsActivity;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ChartAccount;
use App\Models\Branch;
use App\Models\User;

class Item extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'inventory_items';

    protected $fillable = [
        'company_id',
        'category_id',
        'name',
        'code',
        'description',
        'unit_of_measure',
        'item_type',
        'cost_price',
        'unit_price',
        'minimum_stock',
        'maximum_stock',
        'reorder_level',
        'is_active',
        'track_stock',
        'track_expiry',
        'expiry_warning_days',
        'has_opening_balance',
        'opening_balance_quantity',
        'opening_balance_value',
        'has_different_sales_revenue_account',
        'sales_revenue_account_id',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'minimum_stock' => 'integer',
        'maximum_stock' => 'integer',
        'reorder_level' => 'integer',
        'is_active' => 'boolean',
        'track_stock' => 'boolean',
        'track_expiry' => 'boolean',
        'expiry_warning_days' => 'integer',
        'is_withholding_receivable' => 'boolean',
        'has_opening_balance' => 'boolean',
        'opening_balance_quantity' => 'decimal:2',
        'opening_balance_value' => 'decimal:2',
        'has_different_sales_revenue_account' => 'boolean',
    ];

    // protected $dates = ['deleted_at'];

    /**
     * Get the encoded ID for this item
     */
    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function salesRevenueAccount()
    {
        return $this->belongsTo(ChartAccount::class, 'sales_revenue_account_id');
    }

 
    public function expiryTracking()
    {
        return $this->hasMany(ExpiryTracking::class);
    }

    public function movements()
    {
        return $this->hasMany(Movement::class);
    }

    public function stockLevels()
    {
        return $this->hasMany(\App\Models\Inventory\StockLevel::class, 'item_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBranch($query, $branchId)
    {
        // FIXED: Items are now global (not branch-specific), so we filter by company instead
        // This maintains backward compatibility while fixing the column error
        // Updated to fix branch_id column not found error
        $user = auth()->user();
        if ($user && $user->company_id) {
            return $query->where('company_id', $user->company_id);
        }
        return $query;
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        return $this->is_active 
            ? '<span class="badge bg-success">Active</span>' 
            : '<span class="badge bg-danger">Inactive</span>';
    }

    public function getCategoryNameAttribute()
    {
        return $this->category ? $this->category->name : 'No Category';
    }

    public function getLocationNameAttribute()
    {
        // Since items don't have a direct location relationship anymore,
        // we'll return a generic message or get the primary location from movements
        $primaryLocation = $this->movements()
            ->with('location')
            ->latest()
            ->first();
        
        return $primaryLocation && $primaryLocation->location 
            ? $primaryLocation->location->name 
            : 'Multiple Locations';
    }

    public function getTotalStockAttribute()
    {
        // Calculate total stock from movements
        $totalStock = $this->movements()
            ->selectRaw('
                SUM(CASE 
                    WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                    THEN quantity 
                    WHEN movement_type IN ("transfer_out", "sold", "adjustment_out", "write_off") 
                    THEN -quantity 
                    ELSE 0 
                END) as total_stock
            ')
            ->value('total_stock');
        
        return (float) ($totalStock ?? 0);
    }

    /**
     * Get current stock quantity for this item at a specific location
     */
    public function getStockAtLocation($locationId)
    {
        $stock = $this->movements()
            ->where('location_id', $locationId)
            ->selectRaw('
                SUM(CASE 
                    WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                    THEN quantity 
                    WHEN movement_type IN ("transfer_out", "sold", "adjustment_out", "write_off") 
                    THEN -quantity 
                    ELSE 0 
                END) as current_stock
            ')
            ->value('current_stock');
        
        return (float) ($stock ?? 0);
    }

    /**
     * Get stock quantities for this item across all locations
     */
    public function getStockByLocation()
    {
        return $this->movements()
            ->selectRaw('
                location_id,
                SUM(CASE 
                    WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                    THEN quantity 
                    WHEN movement_type IN ("transfer_out", "sold", "adjustment_out", "write_off") 
                    THEN -quantity 
                    ELSE 0 
                END) as current_stock
            ')
            ->groupBy('location_id')
            ->having('current_stock', '>', 0)
            ->with('location:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'location_id' => $item->location_id,
                    'location_name' => $item->location->name ?? 'Unknown Location',
                    'quantity' => (float) $item->current_stock
                ];
            });
    }

    public function getActionsAttribute()
    {
        $actions = '<div class="btn-group" role="group">';
        $actions .= '<a href="' . route('inventory.items.show', $this->encoded_id) . '" class="btn btn-sm btn-outline-info" title="View"><i class="bx bx-show"></i></a>';
        $actions .= '<a href="' . route('inventory.items.edit', $this->encoded_id) . '" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>';
        $actions .= '<button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $this->encoded_id . '" data-url="' . route('inventory.items.destroy', $this->encoded_id) . '" title="Delete"><i class="bx bx-trash"></i></button>';
        $actions .= '</div>';
        
        return $actions;
    }

    public function scopeLowStock($query)
    {
        // This scope would need to be implemented differently since we can't easily
        // compare calculated stock with minimum_stock in a single query
        // For now, we'll return the query as-is and handle low stock logic in the application layer
        return $query;
    }

    public function scopeOutOfStock($query)
    {
        // This scope would need to be implemented differently since we can't easily
        // check calculated stock in a single query
        // For now, we'll return the query as-is and handle out of stock logic in the application layer
        return $query;
    }

    // Accessors
    public function getStockValueAttribute()
    {
        return $this->total_stock * $this->cost_price;
    }

    public function getIsLowStockAttribute()
    {
        return $this->total_stock <= $this->minimum_stock;
    }

    public function getIsOutOfStockAttribute()
    {
        return $this->total_stock <= 0;
    }

    public function getStockStatusAttribute()
    {
        if ($this->is_out_of_stock) {
            return 'Out of Stock';
        } elseif ($this->is_low_stock) {
            return 'Low Stock';
        } elseif ($this->maximum_stock && $this->total_stock >= $this->maximum_stock) {
            return 'Overstock';
        } else {
            return 'In Stock';
        }
    }

    public function getStockStatusClassAttribute()
    {
        if ($this->is_out_of_stock) {
            return 'danger';
        } elseif ($this->is_low_stock) {
            return 'warning';
        } elseif ($this->maximum_stock && $this->total_stock >= $this->maximum_stock) {
            return 'info';
        } else {
            return 'success';
        }
    }

    /**
     * Get current stock for the item at the session location.
     */
    public function getCurrentStockAttribute()
    {
        // Use InventoryStockService for current stock calculation
        $stockService = new \App\Services\InventoryStockService();
        $loginLocationId = session('location_id');
        
        if ($loginLocationId) {
            // Get stock for specific location
            return $stockService->getItemStockAtLocation($this->id, $loginLocationId);
        }
        
        // Get total stock across all locations
        return $stockService->getItemTotalStock($this->id);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'hash_id';
    }

    /**
     * Resolve the model instance for the given hash ID.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Try to decode the hash ID first
        $decoded = Hashids::decode($value);
        
        if (!empty($decoded)) {
            return static::where('id', $decoded[0])->first();
        }
        
        // Fallback to regular ID lookup
        return static::where('id', $value)->first();
    }

    /**
     * Get the hash ID for this model.
     */
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the hash ID for routing.
     */
    public function getRouteKey()
    {
        return Hashids::encode($this->id);
    }
}
