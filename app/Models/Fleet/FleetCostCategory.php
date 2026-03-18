<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FleetCostCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'category_type',
        'description',
        'is_active',
        'default_amount',
        'unit_of_measure',
        'chart_account_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_amount' => 'decimal:2',
    ];

    /**
     * Get the company that owns this cost category
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    /**
     * Get the user who created this cost category
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this cost category
     */
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Get the chart account linked to this cost category
     */
    public function chartAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'chart_account_id');
    }

    /**
     * Scope to get active cost categories
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
     * Scope to filter by category type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('category_type', $type);
    }

    /**
     * Get category type options
     */
    public static function getCategoryTypeOptions()
    {
        return [
            'fuel' => 'Fuel',
            'maintenance' => 'Maintenance',
            'insurance' => 'Insurance',
            'driver_cost' => 'Driver Cost',
            'toll' => 'Toll',
            'other' => 'Other',
        ];
    }

    /**
     * Get unit of measure options
     */
    public static function getUnitOfMeasureOptions()
    {
        return [
            'liters' => 'Liters',
            'gallons' => 'Gallons',
            'kilometers' => 'Kilometers',
            'miles' => 'Miles',
            'hours' => 'Hours',
            'days' => 'Days',
            'fixed' => 'Fixed Amount',
            'percentage' => 'Percentage',
        ];
    }
}