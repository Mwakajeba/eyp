<?php

namespace App\Traits\Fleet;

use App\Models\Fleet\FleetSystemSetting;
use App\Models\Fleet\FleetApprovalSettings;
use Illuminate\Support\Facades\Auth;

trait HasFleetSettings
{
    /**
     * Get fleet setting value with default
     */
    protected function getFleetSetting($key, $default = null)
    {
        $companyId = Auth::user()->company_id;
        return FleetSystemSetting::getSetting($companyId, $key, $default);
    }

    /**
     * Get all fleet settings for current company
     */
    protected function getAllFleetSettings()
    {
        $companyId = Auth::user()->company_id;
        return FleetSystemSetting::getAllSettings($companyId);
    }

    /**
     * Check if a boolean fleet setting is enabled
     */
    protected function isFleetSettingEnabled($key, $default = false)
    {
        $value = $this->getFleetSetting($key, $default ? '1' : '0');
        return $value === '1' || $value === 1 || $value === true;
    }

    /**
     * Get default currency from fleet settings
     */
    protected function getDefaultCurrency()
    {
        return $this->getFleetSetting('default_currency', 'TZS');
    }

    /**
     * Get default trip status from fleet settings
     * Valid statuses: planned, dispatched, in_progress, completed, cancelled
     */
    protected function getDefaultTripStatus()
    {
        $status = $this->getFleetSetting('default_trip_status', 'planned');
        // Validate status - only allow valid trip statuses
        $validStatuses = ['planned', 'dispatched', 'in_progress', 'completed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return 'planned'; // Default to 'planned' if invalid
        }
        return $status;
    }

    /**
     * Check if trip approval is required.
     * Uses Fleet Approval Settings (fleet/approval-settings) when present;
     * when "Enable Approval System" is off, trips are auto-approved.
     * Falls back to FleetSystemSetting default_trip_approval_required when no approval settings exist.
     */
    protected function isTripApprovalRequired()
    {
        $user = Auth::user();
        $settings = FleetApprovalSettings::getSettingsForCompany($user->company_id, $user->branch_id ?? session('branch_id'));

        if ($settings !== null) {
            return (bool) $settings->approval_required;
        }

        return $this->isFleetSettingEnabled('default_trip_approval_required', false);
    }

    /**
     * Check if cost approval is required
     */
    protected function isCostApprovalRequired($amount = null)
    {
        if (!$this->isFleetSettingEnabled('enable_cost_approval', false)) {
            return false;
        }

        if ($amount !== null) {
            $threshold = $this->getFleetSetting('cost_approval_threshold', 0);
            return $amount > $threshold;
        }

        return true;
    }

    /**
     * Get trip number prefix from settings
     */
    protected function getTripNumberPrefix()
    {
        return $this->getFleetSetting('trip_number_prefix', 'TRIP-');
    }

    /**
     * Get work order number prefix from settings
     */
    protected function getWorkOrderNumberPrefix()
    {
        return $this->getFleetSetting('work_order_number_prefix', 'WO-');
    }
}
