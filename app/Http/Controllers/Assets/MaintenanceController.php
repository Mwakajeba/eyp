<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Assets\MaintenanceRequest;
use App\Models\Assets\WorkOrder;
use App\Models\Assets\MaintenanceHistory;
use App\Models\Assets\MaintenanceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    /**
     * Maintenance Dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        // Get KPIs
        $totalRequests = MaintenanceRequest::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->count();

        $pendingRequests = MaintenanceRequest::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->where('status', 'pending')
            ->count();

        $openWorkOrders = WorkOrder::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->whereIn('status', ['approved', 'in_progress', 'on_hold'])
            ->count();

        $completedThisMonth = WorkOrder::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->where('status', 'completed')
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->count();

        // Maintenance costs
        $totalCostYtd = MaintenanceHistory::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->whereYear('maintenance_date', now()->year)
            ->sum('total_cost');

        $expensedCostYtd = MaintenanceHistory::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->whereYear('maintenance_date', now()->year)
            ->expensed()
            ->sum('total_cost');

        $capitalizedCostYtd = MaintenanceHistory::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->whereYear('maintenance_date', now()->year)
            ->capitalized()
            ->sum('total_cost');

        // Upcoming maintenance (preventive)
        $upcomingMaintenance = MaintenanceRequest::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->where('trigger_type', 'preventive')
            ->where('status', 'approved')
            ->whereDate('preferred_start_date', '>=', now())
            ->whereDate('preferred_start_date', '<=', now()->addDays(30))
            ->with(['asset', 'maintenanceType'])
            ->orderBy('preferred_start_date')
            ->limit(10)
            ->get();

        // Recent work orders
        $recentWorkOrders = WorkOrder::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->with(['asset', 'maintenanceType'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('assets.maintenance.index', compact(
            'totalRequests',
            'pendingRequests',
            'openWorkOrders',
            'completedThisMonth',
            'totalCostYtd',
            'expensedCostYtd',
            'capitalizedCostYtd',
            'upcomingMaintenance',
            'recentWorkOrders'
        ));
    }

    /**
     * Maintenance Settings
     */
    public function settings()
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $settings = MaintenanceSetting::where('company_id', $companyId)
            ->where(function($query) use ($branchId) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $branchId);
            })
            ->orderBy('branch_id') // Branch-specific first
            ->get()
            ->keyBy('setting_key');

        // Get chart accounts for dropdowns
        $chartAccounts = \App\Models\ChartAccount::whereHas('accountClassGroup', function($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->orderBy('account_name')->get(['id', 'account_code', 'account_name']);

        return view('assets.maintenance.settings', compact('settings', 'chartAccounts'));
    }

    /**
     * Update Maintenance Settings
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $validated = $request->validate([
            'maintenance_expense_account' => 'nullable|exists:chart_accounts,id',
            'maintenance_wip_account' => 'nullable|exists:chart_accounts,id',
            'asset_capitalization_account' => 'nullable|exists:chart_accounts,id',
            'capitalization_threshold_amount' => 'nullable|numeric|min:0',
            'capitalization_life_extension_months' => 'nullable|integer|min:0',
        ]);

        foreach ($validated as $key => $value) {
            MaintenanceSetting::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'setting_key' => $key,
                ],
                [
                    'setting_name' => str_replace('_', ' ', ucwords($key, '_')),
                    'setting_value' => $value,
                    'setting_type' => in_array($key, ['maintenance_expense_account', 'maintenance_wip_account', 'asset_capitalization_account']) 
                        ? 'chart_account_id' 
                        : (str_contains($key, 'amount') ? 'decimal' : 'number'),
                    'updated_by' => $user->id,
                ]
            );
        }

        return redirect()->route('assets.maintenance.settings')
            ->with('success', 'Maintenance settings updated successfully.');
    }
}
