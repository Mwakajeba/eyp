<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FleetController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index()
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $counts = [
            'vehicleCount' => 0,
            'activeVehiclesCount' => 0,
            'inMaintenanceCount' => 0,
            'driverCount' => 0,
            'plannedTripsCount' => 0,
            'activeTripsCount' => 0,
            'tripsThisMonth' => 0,
            'totalRevenueThisMonth' => 0,
            'totalCostsThisMonth' => 0,
            'avgFuelEfficiency' => 0,
            'costCount' => 0,
            'costCategoryCount' => 0,
            'fuelCount' => 0,
            'invoiceCount' => 0,
            'complianceCount' => 0,
            'tyreCount' => 0,
            'tyrePositionCount' => 0,
            'sparePartCategoryCount' => 0,
            'tyreInstallationCount' => 0,
            'tyreReplacementRequestCount' => 0,
            'sparePartReplacementCount' => 0,
        ];

        // Fleet Statistics
        try {
            if (class_exists(\App\Models\Assets\Asset::class) && class_exists(\App\Models\Assets\AssetCategory::class)) {
                $vehicleCategoryId = \App\Models\Assets\AssetCategory::where('code', 'FA04')
                    ->where('company_id', $companyId)
                    ->value('id');
                if ($vehicleCategoryId) {
                    $vehicleQuery = \App\Models\Assets\Asset::where('company_id', $companyId)
                        ->where('asset_category_id', $vehicleCategoryId)
                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId));
                    
                    $counts['vehicleCount'] = $vehicleQuery->count();
                    $counts['activeVehiclesCount'] = (clone $vehicleQuery)->where('operational_status', 'available')->count();
                    $counts['inMaintenanceCount'] = (clone $vehicleQuery)->where('operational_status', 'in_repair')->count();
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (class_exists(\App\Models\Fleet\FleetDriver::class)) {
                $counts['driverCount'] = \App\Models\Fleet\FleetDriver::where('company_id', $companyId)
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (class_exists(\App\Models\Fleet\FleetTrip::class)) {
                $tripQuery = \App\Models\Fleet\FleetTrip::where('company_id', $companyId)
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId));
                
                $counts['plannedTripsCount'] = (clone $tripQuery)->where('status', 'planned')->count();
                $counts['activeTripsCount'] = (clone $tripQuery)->whereIn('status', ['dispatched', 'in_progress'])->count();
                
                // Trips this month
                $counts['tripsThisMonth'] = (clone $tripQuery)
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count();
                
                // Revenue this month - calculate from invoices (proper revenue source)
                if (class_exists(\App\Models\Fleet\FleetInvoice::class)) {
                    $counts['totalRevenueThisMonth'] = \App\Models\Fleet\FleetInvoice::where('company_id', $companyId)
                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                        ->whereYear('invoice_date', now()->year)
                        ->whereMonth('invoice_date', now()->month)
                        ->sum('total_amount');
                } else {
                    // Fallback to trips actual_revenue if invoices not available
                    $counts['totalRevenueThisMonth'] = (clone $tripQuery)
                        ->whereYear('created_at', now()->year)
                        ->whereMonth('created_at', now()->month)
                        ->sum('actual_revenue');
                }
                
                // Costs this month
                $counts['totalCostsThisMonth'] = (clone $tripQuery)
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->sum('total_costs');
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (class_exists(\App\Models\Fleet\FleetTripCost::class)) {
                $counts['costCount'] = \App\Models\Fleet\FleetTripCost::where('company_id', $companyId)
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (class_exists(\App\Models\Fleet\FleetCostCategory::class)) {
                $counts['costCategoryCount'] = \App\Models\Fleet\FleetCostCategory::where('company_id', $companyId)->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (class_exists(\App\Models\Fleet\FleetFuelLog::class)) {
                $counts['fuelCount'] = \App\Models\Fleet\FleetFuelLog::where('company_id', $companyId)
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->count();
                
                // Calculate average fuel efficiency (km/L)
                $avgEfficiency = \App\Models\Fleet\FleetFuelLog::where('company_id', $companyId)
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->whereNotNull('fuel_efficiency_km_per_liter')
                    ->where('fuel_efficiency_km_per_liter', '>', 0)
                    ->avg('fuel_efficiency_km_per_liter');
                
                $counts['avgFuelEfficiency'] = round($avgEfficiency ?? 0, 2);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (class_exists(\App\Models\Fleet\FleetInvoice::class)) {
                $counts['invoiceCount'] = \App\Models\Fleet\FleetInvoice::where('company_id', $companyId)
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (class_exists(\App\Models\Fleet\FleetComplianceRecord::class)) {
                $counts['complianceCount'] = \App\Models\Fleet\FleetComplianceRecord::where('company_id', $companyId)
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (class_exists(\App\Models\Fleet\FleetTyre::class)) {
                $counts['tyreCount'] = \App\Models\Fleet\FleetTyre::where('company_id', $companyId)
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (class_exists(\App\Models\Fleet\FleetTyrePosition::class)) {
                $counts['tyrePositionCount'] = \App\Models\Fleet\FleetTyrePosition::where('company_id', $companyId)->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (class_exists(\App\Models\Fleet\FleetSparePartCategory::class)) {
                $counts['sparePartCategoryCount'] = \App\Models\Fleet\FleetSparePartCategory::where('company_id', $companyId)->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (class_exists(\App\Models\Fleet\FleetTyreInstallation::class)) {
                $counts['tyreInstallationCount'] = \App\Models\Fleet\FleetTyreInstallation::where('company_id', $companyId)
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (class_exists(\App\Models\Fleet\FleetTyreReplacementRequest::class)) {
                $counts['tyreReplacementRequestCount'] = \App\Models\Fleet\FleetTyreReplacementRequest::where('company_id', $companyId)
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (class_exists(\App\Models\Fleet\FleetSparePartReplacement::class)) {
                $counts['sparePartReplacementCount'] = \App\Models\Fleet\FleetSparePartReplacement::where('company_id', $companyId)
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (class_exists(\App\Models\Fleet\FleetMaintenanceWorkOrder::class)) {
                $counts['maintenanceWorkOrderCount'] = \App\Models\Fleet\FleetMaintenanceWorkOrder::where('company_id', $companyId)
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            if (class_exists(\App\Models\Fleet\FleetMaintenanceSchedule::class)) {
                $counts['maintenanceScheduleCount'] = \App\Models\Fleet\FleetMaintenanceSchedule::where('company_id', $companyId)
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return view('fleet.index', $counts);
    }
}