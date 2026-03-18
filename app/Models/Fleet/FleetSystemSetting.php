<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetSystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'setting_key',
        'setting_value',
        'setting_description',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the company that owns this setting
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    /**
     * Get the user who created this setting
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this setting
     */
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Scope to filter by company
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Get setting value with default
     */
    public static function getSetting($companyId, $key, $default = null)
    {
        $setting = static::where('company_id', $companyId)
            ->where('setting_key', $key)
            ->first();

        return $setting ? $setting->setting_value : $default;
    }

    /**
     * Set setting value
     */
    public static function setSetting($companyId, $key, $value, $userId = null)
    {
        $setting = static::where('company_id', $companyId)
            ->where('setting_key', $key)
            ->first();

        if ($setting) {
            // Update existing setting
            $setting->update([
                'setting_value' => $value,
                'updated_by' => $userId,
            ]);
            return $setting;
        } else {
            // Create new setting
            return static::create([
                'company_id' => $companyId,
                'setting_key' => $key,
                'setting_value' => $value,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }

    /**
     * Get all settings for a company as key-value array
     */
    public static function getAllSettings($companyId)
    {
        return static::where('company_id', $companyId)
            ->pluck('setting_value', 'setting_key')
            ->toArray();
    }

    /**
     * Get default settings
     */
    public static function getDefaultSettings()
    {
        return [
            'default_fuel_efficiency_unit' => [
                'value' => 'km/l',
                'description' => 'Default unit for fuel efficiency (km/l or mpg)',
            ],
            'default_currency' => [
                'value' => 'TZS',
                'description' => 'Default currency for fleet costs',
            ],
            'enable_fuel_tracking' => [
                'value' => '1',
                'description' => 'Enable fuel consumption tracking',
            ],
            'enable_maintenance_alerts' => [
                'value' => '1',
                'description' => 'Enable maintenance reminder alerts',
            ],
            'enable_trip_tracking' => [
                'value' => '1',
                'description' => 'Enable GPS trip tracking',
            ],
            'default_trip_approval_required' => [
                'value' => '1',
                'description' => 'Require approval for all trip requests',
            ],
            'maintenance_reminder_days' => [
                'value' => '7',
                'description' => 'Days before maintenance due to send reminder',
            ],
            'fuel_low_threshold_percentage' => [
                'value' => '20',
                'description' => 'Fuel level percentage to trigger low fuel alert',
            ],
            'auto_calculate_distances' => [
                'value' => '0',
                'description' => 'Automatically calculate distances using GPS',
            ],
            'require_driver_license_check' => [
                'value' => '1',
                'description' => 'Require driver license validation',
            ],
            'require_vehicle_inspection' => [
                'value' => '1',
                'description' => 'Require vehicle inspection before trips',
            ],
            'fleet_income_chart_account_id' => [
                'value' => null,
                'description' => 'Chart account for fleet income/revenue',
            ],
            'fleet_receivable_chart_account_id' => [
                'value' => null,
                'description' => 'Chart account for fleet receivables',
            ],
            'fleet_opening_balance_chart_account_id' => [
                'value' => null,
                'description' => 'Chart account for fleet opening balance',
            ],
        ];
    }
}