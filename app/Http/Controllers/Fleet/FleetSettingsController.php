<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetSystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FleetSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        // Get all current settings
        $settings = FleetSystemSetting::where('company_id', $companyId)
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        // Get default settings structure
        $defaultSettings = FleetSystemSetting::getDefaultSettings();

        // Merge with current values
        foreach ($defaultSettings as $key => $config) {
            if (!isset($settings[$key])) {
                $settings[$key] = $config['value'];
            }
        }

        // Get chart accounts for income and receivable dropdowns
        $chartAccounts = \App\Models\ChartAccount::whereHas('accountClassGroup', function($q) use ($user) {
                $q->where('company_id', $user->company_id);
            })
            ->orderBy('account_name')
            ->get(['id', 'account_code', 'account_name']);

        $tripApprovalWorkflows = \App\Models\Fleet\FleetApprovalWorkflow::where('company_id', $user->company_id)
            ->where('workflow_type', 'trip_request')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $costApprovalWorkflows = \App\Models\Fleet\FleetApprovalWorkflow::where('company_id', $user->company_id)
            ->where('workflow_type', 'cost_approval')
            ->where('is_active', true)
            ->with('approvers.user')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('fleet.settings.index', compact('settings', 'defaultSettings', 'chartAccounts', 'tripApprovalWorkflows', 'costApprovalWorkflows'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $validated = $request->validate([
            'default_currency' => 'required|string|max:10',
            'default_fuel_efficiency_unit' => 'required|in:km/l,mpg',
            'enable_fuel_tracking' => 'boolean',
            'enable_trip_tracking' => 'boolean',
            'enable_maintenance_alerts' => 'boolean',
            'default_trip_approval_required' => 'boolean',
            'maintenance_reminder_days' => 'required|integer|min:0|max:365',
            'fuel_low_threshold_percentage' => 'required|numeric|min:0|max:100',
            'auto_calculate_distances' => 'boolean',
            'require_driver_license_check' => 'boolean',
            'require_vehicle_inspection' => 'boolean',
            'trip_number_prefix' => 'nullable|string|max:20',
            'work_order_number_prefix' => 'nullable|string|max:20',
            'enable_odometer_tracking' => 'boolean',
            'enable_route_optimization' => 'boolean',
            'default_trip_status' => 'required|in:planned,dispatched',
            'trip_approval_workflow_id' => 'nullable|exists:fleet_approval_workflows,id',
            'enable_cost_approval' => 'boolean',
            'cost_approval_workflow_id' => 'nullable|exists:fleet_approval_workflows,id',
            'cost_approval_threshold' => 'nullable|numeric|min:0',
            'enable_automatic_billing' => 'boolean',
            'default_payment_terms' => 'nullable|integer|min:0',
            'enable_fuel_card_integration' => 'boolean',
            'enable_gps_tracking' => 'boolean',
            'gps_update_interval_minutes' => 'nullable|integer|min:1|max:60',
            'enable_driver_behavior_tracking' => 'boolean',
            'enable_speed_alerts' => 'boolean',
            'speed_alert_threshold_kmh' => 'nullable|numeric|min:0',
            'enable_idle_time_tracking' => 'boolean',
            'idle_time_threshold_minutes' => 'nullable|integer|min:1',
            'fleet_income_chart_account_id' => 'nullable|exists:chart_accounts,id',
            'fleet_receivable_chart_account_id' => 'nullable|exists:chart_accounts,id',
            'fleet_opening_balance_chart_account_id' => 'nullable|exists:chart_accounts,id',
        ]);

        // Update settings (skip null for optional keys that might not be in form)
        foreach ($validated as $key => $value) {
            if ($value === null && in_array($key, ['trip_approval_workflow_id', 'cost_approval_workflow_id', 'cost_approval_threshold', 'default_payment_terms', 'gps_update_interval_minutes', 'speed_alert_threshold_kmh', 'idle_time_threshold_minutes', 'fleet_income_chart_account_id', 'fleet_receivable_chart_account_id', 'fleet_opening_balance_chart_account_id'])) {
                $value = '';
            }
            $boolValue = is_bool($value) ? ($value ? '1' : '0') : $value;
            FleetSystemSetting::setSetting($companyId, $key, $boolValue, $user->id);
        }

        return redirect()->route('fleet.settings.index')
            ->with('success', 'Fleet settings updated successfully.');
    }
}
