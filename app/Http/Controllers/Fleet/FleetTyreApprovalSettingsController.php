<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetApprovalWorkflow;
use App\Models\Fleet\FleetSystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FleetTyreApprovalSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $settings = FleetSystemSetting::where('company_id', $companyId)
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        $workflows = FleetApprovalWorkflow::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'workflow_type']);

        $tyreWorkflowId = $settings['tyre_replacement_approval_workflow_id'] ?? null;
        $spareWorkflowId = $settings['spare_replacement_approval_workflow_id'] ?? null;
        $tyreWorkflow = $tyreWorkflowId
            ? FleetApprovalWorkflow::with(['approvers' => fn($q) => $q->where('is_active', true)->orderBy('approval_order'), 'approvers.user'])->find($tyreWorkflowId)
            : null;
        $spareWorkflow = $spareWorkflowId
            ? FleetApprovalWorkflow::with(['approvers' => fn($q) => $q->where('is_active', true)->orderBy('approval_order'), 'approvers.user'])->find($spareWorkflowId)
            : null;

        return view('fleet.tyre-approval-settings.index', compact('settings', 'workflows', 'tyreWorkflow', 'spareWorkflow'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $validated = $request->validate([
            'tyre_replacement_min_km_before_request' => 'nullable|numeric|min:0',
            'tyre_replacement_require_approval' => 'boolean',
            'tyre_replacement_approval_workflow_id' => 'nullable|exists:fleet_approval_workflows,id',
            'spare_replacement_auto_approve_max_cost' => 'nullable|numeric|min:0',
            'spare_replacement_min_interval_days' => 'nullable|integer|min:0',
            'spare_replacement_approval_workflow_id' => 'nullable|exists:fleet_approval_workflows,id',
        ]);

        foreach ($validated as $key => $value) {
            $stored = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
            if ($value === null || $value === '') {
                $stored = '';
            }
            FleetSystemSetting::setSetting($companyId, $key, $stored, $user->id);
        }

        return redirect()->route('fleet.tyre-approval-settings.index')
            ->with('success', 'Approval settings saved successfully.');
    }
}
