<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetTrip;
use App\Models\Fleet\FleetInvoice;
use App\Models\Fleet\FleetInvoicePayment;
use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetRoute;
use App\Models\Fleet\FleetTripCost;
use App\Models\Fleet\FleetFuelLog;
use App\Models\Assets\Asset;
use App\Models\Assets\WorkOrder;
use App\Models\Assets\WorkOrderCost;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class FleetReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index()
    {
        return view('fleet.reports.index');
    }

    /**
     * Get actual revenue for a trip from invoices and invoice items
     */
    private function getTripRevenue($tripId)
    {
        // Get invoices with direct trip_id relationship
        $directInvoiceIds = FleetInvoice::where('trip_id', $tripId)->pluck('id');
        
        // Get invoices from invoice items that reference this trip
        $itemInvoiceIds = \App\Models\Fleet\FleetInvoiceItem::where('trip_id', $tripId)
            ->distinct()
            ->pluck('fleet_invoice_id');
        
        // Combine both (avoid double counting)
        $allInvoiceIds = $directInvoiceIds->merge($itemInvoiceIds)->unique()->filter();
        
        if ($allInvoiceIds->count() > 0) {
            return FleetInvoice::whereIn('id', $allInvoiceIds)->sum('total_amount');
        }
        
        return 0;
    }

    /**
     * Get paid amount for a trip from invoices and invoice items
     */
    private function getTripPaidAmount($tripId)
    {
        // Get invoices with direct trip_id relationship
        $directInvoiceIds = FleetInvoice::where('trip_id', $tripId)->pluck('id');
        
        // Get invoices from invoice items that reference this trip
        $itemInvoiceIds = \App\Models\Fleet\FleetInvoiceItem::where('trip_id', $tripId)
            ->distinct()
            ->pluck('fleet_invoice_id');
        
        // Combine both (avoid double counting)
        $allInvoiceIds = $directInvoiceIds->merge($itemInvoiceIds)->unique()->filter();
        
        if ($allInvoiceIds->count() > 0) {
            return FleetInvoice::whereIn('id', $allInvoiceIds)->sum('paid_amount');
        }
        
        return 0;
    }

    /**
     * Calculate revenue for a collection of trips
     */
    private function calculateTripsRevenue($trips)
    {
        $totalRevenue = 0;
        foreach ($trips as $trip) {
            $tripRevenue = $this->getTripRevenue($trip->id);
            $totalRevenue += $tripRevenue > 0 ? $tripRevenue : ($trip->planned_revenue ?? 0);
        }
        return $totalRevenue;
    }

    /**
     * Get maintenance cost from asset management for a vehicle
     */
    private function getMaintenanceCost($vehicleId, $dateFrom = null, $dateTo = null)
    {
        $query = WorkOrder::where('asset_id', $vehicleId)
            ->where('status', 'completed');

        if ($dateFrom) {
            $query->where('actual_completion_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('actual_completion_date', '<=', $dateTo);
        }

        return $query->sum('actual_cost') ?? 0;
    }

    /**
     * Get maintenance cost details for a vehicle
     */
    private function getMaintenanceCostDetails($vehicleId, $dateFrom = null, $dateTo = null)
    {
        $workOrders = WorkOrder::where('asset_id', $vehicleId)
            ->where('status', 'completed')
            ->when($dateFrom, fn($q) => $q->where('actual_completion_date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('actual_completion_date', '<=', $dateTo))
            ->get();

        return $workOrders->map(function ($wo) {
            return [
                'work_order_number' => $wo->wo_number,
                'maintenance_type' => $wo->maintenance_type,
                'completion_date' => $wo->actual_completion_date,
                'total_cost' => $wo->actual_cost ?? 0,
                'labor_cost' => $wo->actual_labor_cost ?? 0,
                'material_cost' => $wo->actual_material_cost ?? 0,
                'other_cost' => $wo->actual_other_cost ?? 0,
            ];
        });
    }

    /**
     * Report 1: Trip Revenue Report
     */
    public function tripRevenue(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $query = FleetTrip::with(['vehicle', 'driver', 'route', 'customer'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        // Apply filters
        if ($request->filled('date_from')) {
            $query->where('planned_start_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('planned_start_date', '<=', $request->date_to);
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }
        if ($request->filled('route_id')) {
            $query->where('route_id', $request->route_id);
        }

        $trips = $query->orderBy('planned_start_date', 'desc')->get();
        
        // Calculate actual revenue from invoices for each trip
        $trips = $trips->map(function($trip) {
            $trip->actual_revenue_calculated = $this->getTripRevenue($trip->id);
            return $trip;
        });
        
        $totalRevenue = $trips->sum('actual_revenue_calculated') > 0 
            ? $trips->sum('actual_revenue_calculated') 
            : $trips->sum('planned_revenue') ?? 0;

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get(['id', 'name', 'registration_number']);

        $drivers = FleetDriver::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get(['id', 'full_name', 'driver_code']);

        $routes = FleetRoute::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get(['id', 'route_name', 'route_code']);

        return view('fleet.reports.trip-revenue', compact('trips', 'totalRevenue', 'vehicles', 'drivers', 'routes'));
    }

    public function tripRevenueExportExcel(Request $request)
    {
        // Similar query as tripRevenue
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $query = FleetTrip::with(['vehicle', 'driver', 'route', 'customer'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        if ($request->filled('date_from')) {
            $query->where('planned_start_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('planned_start_date', '<=', $request->date_to);
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }
        if ($request->filled('route_id')) {
            $query->where('route_id', $request->route_id);
        }

        $trips = $query->orderBy('planned_start_date', 'desc')->get();
        
        // Calculate actual revenue from invoices for each trip
        $trips = $trips->map(function($trip) {
            $trip->actual_revenue_calculated = $this->getTripRevenue($trip->id);
            return $trip;
        });
        
        $totalRevenue = $trips->sum('actual_revenue_calculated') > 0 
            ? $trips->sum('actual_revenue_calculated') 
            : $trips->sum('planned_revenue') ?? 0;

        $data = $trips->map(function ($trip) {
            return [
                'Trip Number' => $trip->trip_number,
                'Date' => $trip->planned_start_date?->format('Y-m-d'),
                'Vehicle' => $trip->vehicle->name ?? 'N/A',
                'Registration' => $trip->vehicle->registration_number ?? 'N/A',
                'Driver' => $trip->driver->full_name ?? 'N/A',
                'Route' => $trip->route->route_name ?? 'N/A',
                'Customer' => $trip->customer->name ?? 'N/A',
                'Planned Revenue' => number_format($trip->planned_revenue ?? 0, 2),
                'Actual Revenue' => number_format($trip->actual_revenue_calculated ?? 0, 2),
                'Distance (km)' => number_format($trip->actual_distance_km ?? $trip->planned_distance_km ?? 0, 2),
                'Status' => ucfirst($trip->status ?? 'N/A'),
            ];
        })->toArray();

        // Add totals row
        $data[] = [
            'Trip Number' => 'TOTAL',
            'Date' => '',
            'Vehicle' => '',
            'Registration' => '',
            'Driver' => '',
            'Route' => '',
            'Customer' => '',
            'Planned Revenue' => number_format($trips->sum('planned_revenue') ?? 0, 2),
            'Actual Revenue' => number_format($totalRevenue, 2),
            'Distance (km)' => number_format($trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0), 2),
            'Status' => '',
        ];

        // Extract headings from data if available
        $headings = !empty($data) ? array_keys($data[0]) : [];
        
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Trip Revenue Report', $headings), 'trip_revenue_report_' . date('Y-m-d') . '.xlsx');
    }

    public function tripRevenueExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $query = FleetTrip::with(['vehicle', 'driver', 'route', 'customer'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        if ($request->filled('date_from')) {
            $query->where('planned_start_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('planned_start_date', '<=', $request->date_to);
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }
        if ($request->filled('route_id')) {
            $query->where('route_id', $request->route_id);
        }

        $trips = $query->orderBy('planned_start_date', 'desc')->get();
        
        // Calculate actual revenue from invoices for each trip
        $trips = $trips->map(function($trip) {
            $trip->actual_revenue_calculated = $this->getTripRevenue($trip->id);
            return $trip;
        });
        
        $totalRevenue = $trips->sum('actual_revenue_calculated') > 0 
            ? $trips->sum('actual_revenue_calculated') 
            : $trips->sum('planned_revenue') ?? 0;
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;
        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.trip-revenue', compact(
            'trips', 'totalRevenue', 'company', 'branch', 'dateFrom', 'dateTo', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('trip_revenue_report_' . date('Y-m-d') . '.pdf');
    }

    // Continue with other reports... (Due to length, I'll add key reports)
    // Similar pattern for all 22 reports

    /**
     * Report 11: Maintenance & Repair Cost Report
     */
    public function maintenanceCost(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;

        // Get vehicles
        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $maintenanceData = $vehicles->map(function ($vehicle) use ($dateFrom, $dateTo) {
            $maintenanceCosts = $this->getMaintenanceCostDetails($vehicle->id, $dateFrom, $dateTo);
            
            return [
                'vehicle' => $vehicle,
                'total_cost' => $maintenanceCosts->sum('total_cost'),
                'labor_cost' => $maintenanceCosts->sum('labor_cost'),
                'material_cost' => $maintenanceCosts->sum('material_cost'),
                'other_cost' => $maintenanceCosts->sum('other_cost'),
                'work_orders' => $maintenanceCosts,
                'count' => $maintenanceCosts->count(),
            ];
        })->filter(fn($item) => $item['count'] > 0);

        $totalCost = $maintenanceData->sum('total_cost');

        return view('fleet.reports.maintenance-cost', compact('maintenanceData', 'totalCost', 'dateFrom', 'dateTo'));
    }

    public function maintenanceCostExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $data = [];
        foreach ($vehicles as $vehicle) {
            $maintenanceCosts = $this->getMaintenanceCostDetails($vehicle->id, $dateFrom, $dateTo);
            
            if ($maintenanceCosts->count() > 0) {
                foreach ($maintenanceCosts as $cost) {
                    $data[] = [
                        'Vehicle' => $vehicle->name,
                        'Registration' => $vehicle->registration_number ?? 'N/A',
                        'Work Order' => $cost['work_order_number'],
                        'Maintenance Type' => ucfirst(str_replace('_', ' ', $cost['maintenance_type'] ?? 'N/A')),
                        'Completion Date' => $cost['completion_date']?->format('Y-m-d'),
                        'Labor Cost' => number_format($cost['labor_cost'], 2),
                        'Material Cost' => number_format($cost['material_cost'], 2),
                        'Other Cost' => number_format($cost['other_cost'], 2),
                        'Total Cost' => number_format($cost['total_cost'], 2),
                    ];
                }
            }
        }

        // Add totals
        $totalLabor = collect($data)->sum(fn($row) => (float) str_replace(',', '', $row['Labor Cost'] ?? 0));
        $totalMaterial = collect($data)->sum(fn($row) => (float) str_replace(',', '', $row['Material Cost'] ?? 0));
        $totalOther = collect($data)->sum(fn($row) => (float) str_replace(',', '', $row['Other Cost'] ?? 0));
        $totalCost = collect($data)->sum(fn($row) => (float) str_replace(',', '', $row['Total Cost'] ?? 0));

        $data[] = [
            'Vehicle' => 'TOTAL',
            'Registration' => '',
            'Work Order' => '',
            'Maintenance Type' => '',
            'Completion Date' => '',
            'Labor Cost' => number_format($totalLabor, 2),
            'Material Cost' => number_format($totalMaterial, 2),
            'Other Cost' => number_format($totalOther, 2),
            'Total Cost' => number_format($totalCost, 2),
        ];

        // Extract headings from data if available
        $headings = !empty($data) ? array_keys($data[0]) : [];
        
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Maintenance & Repair Cost Report', $headings), 'maintenance_cost_report_' . date('Y-m-d') . '.xlsx');
    }

    public function maintenanceCostExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;
        $generatedAt = now();

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $maintenanceData = $vehicles->map(function ($vehicle) use ($dateFrom, $dateTo) {
            $maintenanceCosts = $this->getMaintenanceCostDetails($vehicle->id, $dateFrom, $dateTo);
            
            return [
                'vehicle' => $vehicle,
                'total_cost' => $maintenanceCosts->sum('total_cost'),
                'labor_cost' => $maintenanceCosts->sum('labor_cost'),
                'material_cost' => $maintenanceCosts->sum('material_cost'),
                'other_cost' => $maintenanceCosts->sum('other_cost'),
                'work_orders' => $maintenanceCosts,
                'count' => $maintenanceCosts->count(),
            ];
        })->filter(fn($item) => $item['count'] > 0);

        $totalCost = $maintenanceData->sum('total_cost');
        $totalLabor = $maintenanceData->sum('labor_cost');
        $totalMaterial = $maintenanceData->sum('material_cost');
        $totalOther = $maintenanceData->sum('other_cost');

        $pdf = Pdf::loadView('fleet.reports.pdf.maintenance-cost', compact(
            'maintenanceData', 'totalCost', 'totalLabor', 'totalMaterial', 'totalOther',
            'company', 'branch', 'dateFrom', 'dateTo', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('maintenance_cost_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Report 2: Invoice Summary Report
     */
    public function invoiceSummary(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $query = FleetInvoice::with([
            'vehicle', 
            'driver', 
            'route', 
            'customer',
            'items.trip.vehicle',
            'items.trip.driver',
            'items.trip.route',
            'items.trip.customer'
        ])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        if ($request->filled('date_from')) {
            $query->where('invoice_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('invoice_date', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();
        $totalAmount = $invoices->sum('total_amount');
        $totalPaid = $invoices->sum('paid_amount');
        $totalOutstanding = $invoices->sum('balance_due');

        return view('fleet.reports.invoice-summary', compact('invoices', 'totalAmount', 'totalPaid', 'totalOutstanding'));
    }

    public function invoiceSummaryExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $query = FleetInvoice::with([
            'vehicle', 
            'driver', 
            'route', 
            'customer',
            'items.trip.vehicle',
            'items.trip.driver',
            'items.trip.route'
        ])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        if ($request->filled('date_from')) {
            $query->where('invoice_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('invoice_date', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();

        $data = $invoices->map(function ($invoice) {
            // Get from invoice first, then fallback to first item's trip
            $displayVehicle = $invoice->vehicle ?? null;
            $displayDriver = $invoice->driver ?? null;
            
            if (!$displayVehicle || !$displayDriver) {
                $firstItem = $invoice->items->first();
                if ($firstItem && $firstItem->trip) {
                    $displayVehicle = $displayVehicle ?? $firstItem->trip->vehicle;
                    $displayDriver = $displayDriver ?? $firstItem->trip->driver;
                }
            }
            
            return [
                'Invoice Number' => $invoice->invoice_number,
                'Date' => $invoice->invoice_date?->format('Y-m-d'),
                'Vehicle' => $displayVehicle->name ?? 'N/A',
                'Driver' => $displayDriver->full_name ?? 'N/A',
                'Customer' => $invoice->customer->name ?? 'N/A',
                'Total Amount' => number_format($invoice->total_amount ?? 0, 2),
                'Paid Amount' => number_format($invoice->paid_amount ?? 0, 2),
                'Balance Due' => number_format($invoice->balance_due ?? 0, 2),
                'Status' => ucfirst(str_replace('_', ' ', $invoice->status ?? 'N/A')),
            ];
        })->toArray();

        $data[] = [
            'Invoice Number' => 'TOTAL',
            'Date' => '',
            'Vehicle' => '',
            'Driver' => '',
            'Customer' => '',
            'Total Amount' => number_format($invoices->sum('total_amount') ?? 0, 2),
            'Paid Amount' => number_format($invoices->sum('paid_amount') ?? 0, 2),
            'Balance Due' => number_format($invoices->sum('balance_due') ?? 0, 2),
            'Status' => '',
        ];

        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Invoice Summary Report', $headings), 'invoice_summary_report_' . date('Y-m-d') . '.xlsx');
    }

    public function invoiceSummaryExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $query = FleetInvoice::with([
            'vehicle', 
            'driver', 
            'route', 
            'customer',
            'items.trip.vehicle',
            'items.trip.driver',
            'items.trip.route'
        ])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        if ($request->filled('date_from')) {
            $query->where('invoice_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('invoice_date', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();
        $totalAmount = $invoices->sum('total_amount');
        $totalPaid = $invoices->sum('paid_amount');
        $totalOutstanding = $invoices->sum('balance_due');
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;
        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.invoice-summary', compact(
            'invoices', 'totalAmount', 'totalPaid', 'totalOutstanding', 'company', 'branch', 'dateFrom', 'dateTo', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('invoice_summary_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Report 3: Payment Collection Report
     */
    public function paymentCollection(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $query = FleetInvoicePayment::with([
            'fleetInvoice.vehicle', 
            'fleetInvoice.driver', 
            'fleetInvoice.customer', 
            'fleetInvoice.items.trip.vehicle',
            'fleetInvoice.items.trip.driver',
            'fleetInvoice.items.trip.route',
            'bankAccount'
        ])
            ->whereHas('fleetInvoice', fn($q) => $q->where('company_id', $companyId)
                ->when($branchId, fn($q2) => $q2->where('branch_id', $branchId)));

        if ($request->filled('date_from')) {
            $query->where('payment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('payment_date', '<=', $request->date_to);
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();
        $totalCollection = $payments->sum('amount');

        return view('fleet.reports.payment-collection', compact('payments', 'totalCollection'));
    }

    public function paymentCollectionExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $query = FleetInvoicePayment::with([
            'fleetInvoice.vehicle', 
            'fleetInvoice.driver', 
            'fleetInvoice.customer',
            'fleetInvoice.items.trip.vehicle',
            'fleetInvoice.items.trip.driver',
            'fleetInvoice.items.trip.route'
        ])
            ->whereHas('fleetInvoice', fn($q) => $q->where('company_id', $companyId)
                ->when($branchId, fn($q2) => $q2->where('branch_id', $branchId)));

        if ($request->filled('date_from')) {
            $query->where('payment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('payment_date', '<=', $request->date_to);
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();

        $data = $payments->map(function ($payment) {
            $invoice = $payment->fleetInvoice;
            // Get from invoice first, then fallback to first item's trip
            $displayVehicle = $invoice->vehicle ?? null;
            $displayDriver = $invoice->driver ?? null;
            
            if (!$displayVehicle || !$displayDriver) {
                $firstItem = $invoice->items->first();
                if ($firstItem && $firstItem->trip) {
                    $displayVehicle = $displayVehicle ?? $firstItem->trip->vehicle;
                    $displayDriver = $displayDriver ?? $firstItem->trip->driver;
                }
            }
            
            return [
                'Payment Date' => $payment->payment_date?->format('Y-m-d'),
                'Invoice Number' => $invoice->invoice_number ?? 'N/A',
                'Vehicle' => $displayVehicle->name ?? 'N/A',
                'Driver' => $displayDriver->full_name ?? 'N/A',
                'Customer' => $invoice->customer->name ?? 'N/A',
                'Amount' => number_format($payment->amount ?? 0, 2),
                'Payment Method' => 'Bank Transfer',
                'Reference' => $payment->reference_number ?? 'N/A',
            ];
        })->toArray();

        $data[] = [
            'Payment Date' => 'TOTAL',
            'Invoice Number' => '',
            'Vehicle' => '',
            'Driver' => '',
            'Customer' => '',
            'Amount' => number_format($payments->sum('amount'), 2),
            'Payment Method' => '',
            'Reference' => '',
        ];

        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Payment Collection Report', $headings), 'payment_collection_report_' . date('Y-m-d') . '.xlsx');
    }

    public function paymentCollectionExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $query = FleetInvoicePayment::with([
            'fleetInvoice.vehicle', 
            'fleetInvoice.driver', 
            'fleetInvoice.customer',
            'fleetInvoice.items.trip.vehicle',
            'fleetInvoice.items.trip.driver',
            'fleetInvoice.items.trip.route'
        ])
            ->whereHas('fleetInvoice', fn($q) => $q->where('company_id', $companyId)
                ->when($branchId, fn($q2) => $q2->where('branch_id', $branchId)));

        if ($request->filled('date_from')) {
            $query->where('payment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('payment_date', '<=', $request->date_to);
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();
        $totalCollection = $payments->sum('amount');
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;
        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.payment-collection', compact(
            'payments', 'totalCollection', 'company', 'branch', 'dateFrom', 'dateTo', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('payment_collection_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Report 4: Outstanding Receivables Report
     */
    public function outstandingReceivables(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $query = FleetInvoice::with([
            'vehicle', 
            'driver', 
            'customer',
            'items.trip.vehicle',
            'items.trip.driver',
            'items.trip.route'
        ])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('balance_due', '>', 0);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $receivables = $query->orderBy('due_date', 'asc')->get();
        
        // Calculate aging
        $receivables = $receivables->map(function ($invoice) {
            $daysOverdue = $invoice->due_date ? now()->diffInDays($invoice->due_date, false) : 0;
            return [
                'invoice' => $invoice,
                'days_overdue' => $daysOverdue,
                'aging_category' => $daysOverdue > 90 ? '90+' : ($daysOverdue > 60 ? '60-90' : ($daysOverdue > 30 ? '30-60' : ($daysOverdue > 0 ? '1-30' : 'Current'))),
            ];
        });

        $totalOutstanding = $receivables->sum(fn($r) => $r['invoice']->balance_due);

        $customers = \App\Models\Customer::where('company_id', $companyId)->get(['id', 'name']);

        return view('fleet.reports.outstanding-receivables', compact('receivables', 'totalOutstanding', 'customers'));
    }

    public function outstandingReceivablesExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $query = FleetInvoice::with([
            'vehicle', 
            'driver', 
            'customer',
            'items.trip.vehicle',
            'items.trip.driver',
            'items.trip.route'
        ])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('balance_due', '>', 0);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $receivables = $query->orderBy('due_date', 'asc')->get();

        // Check if any invoice has a customer
        $hasCustomer = $receivables->contains(fn($invoice) => $invoice->customer);

        $data = $receivables->map(function ($invoice) use ($hasCustomer) {
            $daysOverdue = $invoice->due_date ? now()->diffInDays($invoice->due_date, false) : 0;
            // Get from invoice first, then fallback to first item's trip
            $displayVehicle = $invoice->vehicle;
            $displayDriver = $invoice->driver;
            
            if (!$displayVehicle || !$displayDriver) {
                $firstItem = $invoice->items->first();
                if ($firstItem && $firstItem->trip) {
                    $displayVehicle = $displayVehicle ?? $firstItem->trip->vehicle;
                    $displayDriver = $displayDriver ?? $firstItem->trip->driver;
                }
            }
            
            $row = [
                'Invoice Number' => $invoice->invoice_number,
                'Invoice Date' => $invoice->invoice_date?->format('Y-m-d'),
                'Due Date' => $invoice->due_date?->format('Y-m-d'),
                'Days Overdue' => $daysOverdue > 0 ? $daysOverdue : 0,
            ];
            
            if ($hasCustomer) {
                $row['Customer'] = $invoice->customer->name ?? 'N/A';
            }
            
            $row['Vehicle'] = ($displayVehicle->name ?? 'N/A') . ($displayVehicle && $displayVehicle->registration_number ? ' (' . $displayVehicle->registration_number . ')' : '');
            $row['Driver'] = $displayDriver->full_name ?? $displayDriver->name ?? 'N/A';
            $row['Total Amount'] = number_format($invoice->total_amount ?? 0, 2);
            $row['Paid Amount'] = number_format($invoice->paid_amount ?? 0, 2);
            $row['Balance Due'] = number_format($invoice->balance_due ?? 0, 2);
            
            return $row;
        })->toArray();
        
        $totalRow = [
            'Invoice Number' => 'TOTAL',
            'Invoice Date' => '',
            'Due Date' => '',
            'Days Overdue' => '',
        ];
        
        if ($hasCustomer) {
            $totalRow['Customer'] = '';
        }
        
        $totalRow['Vehicle'] = '';
        $totalRow['Driver'] = '';
        $totalRow['Total Amount'] = number_format($receivables->sum('total_amount'), 2);
        $totalRow['Paid Amount'] = number_format($receivables->sum('paid_amount'), 2);
        $totalRow['Balance Due'] = number_format($receivables->sum('balance_due'), 2);
        
        $data[] = $totalRow;

        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Outstanding Receivables Report', $headings), 'outstanding_receivables_report_' . date('Y-m-d') . '.xlsx');
    }

    public function outstandingReceivablesExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $query = FleetInvoice::with([
            'vehicle', 
            'driver', 
            'customer',
            'items.trip.vehicle',
            'items.trip.driver',
            'items.trip.route'
        ])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('balance_due', '>', 0);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $receivables = $query->orderBy('due_date', 'asc')->get();
        $receivables = $receivables->map(function ($invoice) {
            $daysOverdue = $invoice->due_date ? now()->diffInDays($invoice->due_date, false) : 0;
            return [
                'invoice' => $invoice,
                'days_overdue' => $daysOverdue,
                'aging_category' => $daysOverdue > 90 ? '90+' : ($daysOverdue > 60 ? '60-90' : ($daysOverdue > 30 ? '30-60' : ($daysOverdue > 0 ? '1-30' : 'Current'))),
            ];
        });
        
        // Ensure invoices have items loaded for vehicle/driver fallback
        $receivables->each(function ($item) {
            $item['invoice']->load('items.trip.vehicle', 'items.trip.driver');
        });

        $totalOutstanding = $receivables->sum(fn($r) => $r['invoice']->balance_due);
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;
        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.outstanding-receivables', compact(
            'receivables', 'totalOutstanding', 'company', 'branch', 'dateFrom', 'dateTo', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('outstanding_receivables_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Report 5: Revenue by Vehicle Report
     */
    public function revenueByVehicle(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $revenueData = $vehicles->map(function ($vehicle) use ($dateFrom, $dateTo) {
            $query = FleetTrip::where('vehicle_id', $vehicle->id)
                ->where('status', 'completed');
            
            if ($dateFrom) {
                $query->where('actual_start_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->where('actual_start_date', '<=', $dateTo);
            }

            $trips = $query->get();
            $revenue = $this->calculateTripsRevenue($trips);
            $tripCount = $trips->count();
            $distance = $trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0);

            return [
                'vehicle' => $vehicle,
                'revenue' => $revenue,
                'trip_count' => $tripCount,
                'distance' => $distance,
                'avg_revenue_per_trip' => $tripCount > 0 ? $revenue / $tripCount : 0,
                'revenue_per_km' => $distance > 0 ? $revenue / $distance : 0,
            ];
        })->filter(fn($item) => $item['revenue'] > 0)->sortByDesc('revenue');

        $totalRevenue = $revenueData->sum('revenue');

        return view('fleet.reports.revenue-by-vehicle', compact('revenueData', 'totalRevenue', 'dateFrom', 'dateTo'));
    }

    public function revenueByVehicleExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $revenueData = $vehicles->map(function ($vehicle) use ($dateFrom, $dateTo) {
            $query = FleetTrip::where('vehicle_id', $vehicle->id)
                ->where('status', 'completed');
            
            if ($dateFrom) {
                $query->where('actual_start_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->where('actual_start_date', '<=', $dateTo);
            }

            $trips = $query->get();
            $revenue = $this->calculateTripsRevenue($trips);
            $tripCount = $trips->count();
            $distance = $trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0);

            return [
                'Vehicle' => $vehicle->name,
                'Registration' => $vehicle->registration_number ?? 'N/A',
                'Trips' => $tripCount,
                'Distance (km)' => number_format($distance, 2),
                'Revenue' => number_format($revenue, 2),
                'Avg Revenue per Trip' => number_format($tripCount > 0 ? $revenue / $tripCount : 0, 2),
                'Revenue per km' => number_format($distance > 0 ? $revenue / $distance : 0, 2),
            ];
        })->filter(fn($item) => (float) str_replace(',', '', $item['Revenue'] ?? 0) > 0)->toArray();

        $totalRevenue = collect($revenueData)->sum(fn($row) => (float) str_replace(',', '', $row['Revenue'] ?? 0));

        $data[] = [
            'Vehicle' => 'TOTAL',
            'Registration' => '',
            'Trips' => collect($revenueData)->sum(fn($row) => $row['Trips'] ?? 0),
            'Distance (km)' => number_format(collect($revenueData)->sum(fn($row) => (float) str_replace(',', '', $row['Distance (km)'] ?? 0)), 2),
            'Revenue' => number_format($totalRevenue, 2),
            'Avg Revenue per Trip' => '',
            'Revenue per km' => '',
        ];

        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Revenue by Vehicle Report', $headings), 'revenue_by_vehicle_report_' . date('Y-m-d') . '.xlsx');
    }

    public function revenueByVehicleExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;
        $generatedAt = now();

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $revenueData = $vehicles->map(function ($vehicle) use ($dateFrom, $dateTo) {
            $query = FleetTrip::where('vehicle_id', $vehicle->id)
                ->where('status', 'completed');
            
            if ($dateFrom) {
                $query->where('actual_start_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->where('actual_start_date', '<=', $dateTo);
            }

            $trips = $query->get();
            $revenue = $this->calculateTripsRevenue($trips);
            $tripCount = $trips->count();
            $distance = $trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0);

            return [
                'vehicle' => $vehicle,
                'revenue' => $revenue,
                'trip_count' => $tripCount,
                'distance' => $distance,
                'avg_revenue_per_trip' => $tripCount > 0 ? $revenue / $tripCount : 0,
                'revenue_per_km' => $distance > 0 ? $revenue / $distance : 0,
            ];
        })->filter(fn($item) => $item['revenue'] > 0)->sortByDesc('revenue');

        $totalRevenue = $revenueData->sum('revenue');

        $pdf = Pdf::loadView('fleet.reports.pdf.revenue-by-vehicle', compact(
            'revenueData', 'totalRevenue', 'company', 'branch', 'dateFrom', 'dateTo', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('revenue_by_vehicle_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Report 7: Revenue by Driver Report
     */
    public function revenueByDriver(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $drivers = FleetDriver::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $revenueData = $drivers->map(function ($driver) use ($dateFrom, $dateTo) {
            $trips = FleetTrip::where('driver_id', $driver->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->get();

            $revenue = $this->calculateTripsRevenue($trips);
            $distance = $trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0);

            return [
                'driver' => $driver,
                'trip_count' => $trips->count(),
                'distance' => $distance,
                'revenue' => $revenue,
                'avg_revenue_per_trip' => $trips->count() > 0 ? $revenue / $trips->count() : 0,
                'revenue_per_km' => $distance > 0 ? $revenue / $distance : 0,
            ];
        })->filter(fn($item) => $item['trip_count'] > 0);

        $totalRevenue = $revenueData->sum('revenue');

        return view('fleet.reports.revenue-by-driver', compact('revenueData', 'totalRevenue', 'dateFrom', 'dateTo'));
    }

    public function revenueByDriverExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $drivers = FleetDriver::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $data = [];
        foreach ($drivers as $driver) {
            $trips = FleetTrip::where('driver_id', $driver->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->get();

            if ($trips->count() > 0) {
                $revenue = $this->calculateTripsRevenue($trips);
                $distance = $trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0);

                $data[] = [
                    'Driver Code' => $driver->driver_code ?? 'N/A',
                    'Driver Name' => $driver->full_name,
                    'License Number' => $driver->license_number ?? 'N/A',
                    'Trips' => $trips->count(),
                    'Distance (km)' => number_format($distance, 2),
                    'Revenue' => number_format($revenue, 2),
                    'Avg Revenue per Trip' => number_format($trips->count() > 0 ? $revenue / $trips->count() : 0, 2),
                    'Revenue per km' => number_format($distance > 0 ? $revenue / $distance : 0, 2),
                ];
            }
        }

        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Revenue by Driver Report', $headings), 'revenue_by_driver_report_' . date('Y-m-d') . '.xlsx');
    }

    public function revenueByDriverExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $drivers = FleetDriver::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $revenueData = $drivers->map(function ($driver) use ($dateFrom, $dateTo) {
            $trips = FleetTrip::where('driver_id', $driver->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->get();

            $revenue = $this->calculateTripsRevenue($trips);
            $distance = $trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0);

            return [
                'driver' => $driver,
                'trip_count' => $trips->count(),
                'distance' => $distance,
                'revenue' => $revenue,
                'avg_revenue_per_trip' => $trips->count() > 0 ? $revenue / $trips->count() : 0,
                'revenue_per_km' => $distance > 0 ? $revenue / $distance : 0,
            ];
        })->filter(fn($item) => $item['trip_count'] > 0);

        $totalRevenue = $revenueData->sum('revenue');
        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.revenue-by-driver', compact(
            'revenueData', 'totalRevenue', 'company', 'branch', 'dateFrom', 'dateTo', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('revenue_by_driver_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Report 8: Route Revenue Report
     */
    public function routeRevenue(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $routes = FleetRoute::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $revenueData = $routes->map(function ($route) use ($dateFrom, $dateTo) {
            $trips = FleetTrip::where('route_id', $route->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->get();

            $revenue = $this->calculateTripsRevenue($trips);
            $distance = $trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0);

            return [
                'route' => $route,
                'trip_count' => $trips->count(),
                'distance' => $distance,
                'revenue' => $revenue,
                'avg_revenue_per_trip' => $trips->count() > 0 ? $revenue / $trips->count() : 0,
                'revenue_per_km' => $distance > 0 ? $revenue / $distance : 0,
            ];
        })->filter(fn($item) => $item['trip_count'] > 0);

        $totalRevenue = $revenueData->sum('revenue');

        return view('fleet.reports.route-revenue', compact('revenueData', 'totalRevenue', 'dateFrom', 'dateTo'));
    }

    public function routeRevenueExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $routes = FleetRoute::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $data = [];
        foreach ($routes as $route) {
            $trips = FleetTrip::where('route_id', $route->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->get();

            if ($trips->count() > 0) {
                $revenue = $this->calculateTripsRevenue($trips);
                $distance = $trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0);

                $data[] = [
                    'Route Code' => $route->route_code ?? 'N/A',
                    'Route Name' => $route->route_name,
                    'Origin' => $route->origin_location ?? 'N/A',
                    'Destination' => $route->destination_location ?? 'N/A',
                    'Trips' => $trips->count(),
                    'Distance (km)' => number_format($distance, 2),
                    'Revenue' => number_format($revenue, 2),
                    'Avg Revenue per Trip' => number_format($trips->count() > 0 ? $revenue / $trips->count() : 0, 2),
                    'Revenue per km' => number_format($distance > 0 ? $revenue / $distance : 0, 2),
                ];
            }
        }

        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Route Revenue Report', $headings), 'route_revenue_report_' . date('Y-m-d') . '.xlsx');
    }

    public function routeRevenueExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $routes = FleetRoute::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $revenueData = $routes->map(function ($route) use ($dateFrom, $dateTo) {
            $trips = FleetTrip::where('route_id', $route->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->get();

            $revenue = $this->calculateTripsRevenue($trips);
            $distance = $trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0);

            return [
                'route' => $route,
                'trip_count' => $trips->count(),
                'distance' => $distance,
                'revenue' => $revenue,
                'avg_revenue_per_trip' => $trips->count() > 0 ? $revenue / $trips->count() : 0,
                'revenue_per_km' => $distance > 0 ? $revenue / $distance : 0,
            ];
        })->filter(fn($item) => $item['trip_count'] > 0);

        $totalRevenue = $revenueData->sum('revenue');
        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.route-revenue', compact(
            'revenueData', 'totalRevenue', 'company', 'branch', 'dateFrom', 'dateTo', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('route_revenue_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Report 9: Profit & Loss Report
     */
    public function profitLoss(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        // Calculate Revenue from trips
        $trips = FleetTrip::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
            ->get();
        
        $tripRevenue = $this->calculateTripsRevenue($trips);

        $invoiceRevenue = FleetInvoice::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->sum('total_amount') ?? 0;

        $totalRevenue = max($tripRevenue, $invoiceRevenue);

        // Calculate Expenses
        // Maintenance Costs
        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->pluck('id');

        $maintenanceCost = WorkOrder::whereIn('asset_id', $vehicles)
            ->where('status', 'completed')
            ->whereBetween('actual_completion_date', [$dateFrom, $dateTo])
            ->sum('actual_cost') ?? 0;

        // Fuel Costs
        $fuelCost = FleetFuelLog::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('date_filled', [$dateFrom, $dateTo])
            ->sum('total_cost') ?? 0;

        // Trip Costs
        $tripCost = FleetTripCost::whereHas('trip', function($q) use ($companyId, $branchId, $dateFrom, $dateTo) {
            $q->where('company_id', $companyId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo]);
        })->sum('amount') ?? 0;

        $totalExpenses = $maintenanceCost + $fuelCost + $tripCost;
        $netProfit = $totalRevenue - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        $summary = [
            'total_revenue' => $totalRevenue,
            'maintenance_cost' => $maintenanceCost,
            'fuel_cost' => $fuelCost,
            'trip_cost' => $tripCost,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'profit_margin' => $profitMargin,
        ];

        return view('fleet.reports.profit-loss', compact('summary', 'dateFrom', 'dateTo'));
    }

    public function profitLossExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        // Reuse profit-loss calculation logic
        $trips = FleetTrip::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
            ->get();
        
        $tripRevenue = $this->calculateTripsRevenue($trips);

        $invoiceRevenue = FleetInvoice::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->sum('total_amount') ?? 0;

        $totalRevenue = max($tripRevenue, $invoiceRevenue);

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->pluck('id');

        $maintenanceCost = WorkOrder::whereIn('asset_id', $vehicles)
            ->where('status', 'completed')
            ->whereBetween('actual_completion_date', [$dateFrom, $dateTo])
            ->sum('actual_cost') ?? 0;

        $fuelCost = FleetFuelLog::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('date_filled', [$dateFrom, $dateTo])
            ->sum('total_cost') ?? 0;

        $tripCost = FleetTripCost::whereHas('trip', function($q) use ($companyId, $branchId, $dateFrom, $dateTo) {
            $q->where('company_id', $companyId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo]);
        })->sum('amount') ?? 0;

        $totalExpenses = $maintenanceCost + $fuelCost + $tripCost;
        $netProfit = $totalRevenue - $totalExpenses;

        $data = [
            ['Category', 'Amount'],
            ['Total Revenue', number_format($totalRevenue, 2)],
            ['', ''],
            ['Expenses:', ''],
            ['Maintenance Cost', number_format($maintenanceCost, 2)],
            ['Fuel Cost', number_format($fuelCost, 2)],
            ['Trip Cost', number_format($tripCost, 2)],
            ['Total Expenses', number_format($totalExpenses, 2)],
            ['', ''],
            ['Net Profit / Loss', number_format($netProfit, 2)],
        ];

        $headings = ['Category', 'Amount'];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Profit & Loss Report', $headings), 'profit_loss_report_' . date('Y-m-d') . '.xlsx');
    }

    public function profitLossExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        // Reuse profit-loss calculation logic
        $trips = FleetTrip::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
            ->get();
        
        $tripRevenue = $this->calculateTripsRevenue($trips);

        $invoiceRevenue = FleetInvoice::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->sum('total_amount') ?? 0;

        $totalRevenue = max($tripRevenue, $invoiceRevenue);

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->pluck('id');

        $maintenanceCost = WorkOrder::whereIn('asset_id', $vehicles)
            ->where('status', 'completed')
            ->whereBetween('actual_completion_date', [$dateFrom, $dateTo])
            ->sum('actual_cost') ?? 0;

        $fuelCost = FleetFuelLog::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('date_filled', [$dateFrom, $dateTo])
            ->sum('total_cost') ?? 0;

        $tripCost = FleetTripCost::whereHas('trip', function($q) use ($companyId, $branchId, $dateFrom, $dateTo) {
            $q->where('company_id', $companyId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo]);
        })->sum('amount') ?? 0;

        $totalExpenses = $maintenanceCost + $fuelCost + $tripCost;
        $netProfit = $totalRevenue - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        $summary = [
            'total_revenue' => $totalRevenue,
            'maintenance_cost' => $maintenanceCost,
            'fuel_cost' => $fuelCost,
            'trip_cost' => $tripCost,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'profit_margin' => $profitMargin,
        ];

        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.profit-loss', compact(
            'summary', 'company', 'branch', 'dateFrom', 'dateTo', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('profit_loss_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Report 10: Fuel Consumption Report
     */
    public function fuelConsumption(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $fuelLogs = FleetFuelLog::with(['vehicle'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('date_filled', [$dateFrom, $dateTo])
            ->when($request->filled('vehicle_id'), fn($q) => $q->where('vehicle_id', $request->vehicle_id))
            ->orderBy('date_filled', 'desc')
            ->get();

        $totalLiters = $fuelLogs->sum('liters_filled') ?? 0;
        $totalCost = $fuelLogs->sum('total_cost') ?? 0;
        $totalDistance = $fuelLogs->sum('odometer_reading') ?? 0;

        // Calculate efficiency per vehicle
        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get(['id', 'name', 'registration_number']);

        return view('fleet.reports.fuel-consumption', compact('fuelLogs', 'totalLiters', 'totalCost', 'totalDistance', 'vehicles', 'dateFrom', 'dateTo'));
    }

    public function fuelConsumptionExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $fuelLogs = FleetFuelLog::with(['vehicle'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('date_filled', [$dateFrom, $dateTo])
            ->when($request->filled('vehicle_id'), fn($q) => $q->where('vehicle_id', $request->vehicle_id))
            ->orderBy('date_filled', 'desc')
            ->get();

        $data = $fuelLogs->map(function ($log) {
            return [
                'Date' => $log->date_filled?->format('Y-m-d') ?? 'N/A',
                'Vehicle' => $log->vehicle->name ?? 'N/A',
                'Registration' => $log->vehicle->registration_number ?? 'N/A',
                'Quantity (L)' => number_format($log->liters_filled ?? 0, 2),
                'Cost per Liter' => number_format($log->cost_per_liter ?? 0, 2),
                'Total Cost' => number_format($log->total_cost ?? 0, 2),
                'Odometer' => number_format($log->odometer_reading ?? 0, 2),
                'Fuel Station' => $log->fuel_station ?? 'N/A',
            ];
        })->toArray();

        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Fuel Consumption Report', $headings), 'fuel_consumption_report_' . date('Y-m-d') . '.xlsx');
    }

    public function fuelConsumptionExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $fuelLogs = FleetFuelLog::with(['vehicle'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('date_filled', [$dateFrom, $dateTo])
            ->when($request->filled('vehicle_id'), fn($q) => $q->where('vehicle_id', $request->vehicle_id))
            ->orderBy('date_filled', 'desc')
            ->get();

        $totalLiters = $fuelLogs->sum('liters_filled') ?? 0;
        $totalCost = $fuelLogs->sum('total_cost') ?? 0;
        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.fuel-consumption', compact(
            'fuelLogs', 'totalLiters', 'totalCost', 'company', 'branch', 'dateFrom', 'dateTo', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('fuel_consumption_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Report 11: Fuel Cost Report
     */
    public function fuelCost(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $fuelCostData = $vehicles->map(function ($vehicle) use ($dateFrom, $dateTo) {
            $fuelLogs = FleetFuelLog::where('vehicle_id', $vehicle->id)
                ->whereBetween('date_filled', [$dateFrom, $dateTo])
                ->get();

            return [
                'vehicle' => $vehicle,
                'total_liters' => $fuelLogs->sum('liters_filled') ?? 0,
                'total_cost' => $fuelLogs->sum('total_cost') ?? 0,
                'fill_count' => $fuelLogs->count(),
                'avg_cost_per_liter' => $fuelLogs->count() > 0 ? ($fuelLogs->sum('total_cost') / $fuelLogs->sum('liters_filled')) : 0,
            ];
        })->filter(fn($item) => $item['fill_count'] > 0);

        $totalCost = $fuelCostData->sum('total_cost');

        return view('fleet.reports.fuel-cost', compact('fuelCostData', 'totalCost', 'dateFrom', 'dateTo'));
    }

    public function fuelCostExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $data = [];
        foreach ($vehicles as $vehicle) {
            $fuelLogs = FleetFuelLog::where('vehicle_id', $vehicle->id)
                ->whereBetween('date_filled', [$dateFrom, $dateTo])
                ->get();

            if ($fuelLogs->count() > 0) {
                $totalLiters = $fuelLogs->sum('liters_filled') ?? 0;
                $totalCost = $fuelLogs->sum('total_cost') ?? 0;

                $data[] = [
                    'Vehicle' => $vehicle->name,
                    'Registration' => $vehicle->registration_number ?? 'N/A',
                    'Fill-ups' => $fuelLogs->count(),
                    'Total Liters' => number_format($totalLiters, 2),
                    'Total Cost' => number_format($totalCost, 2),
                    'Avg Cost per Liter' => number_format($totalLiters > 0 ? $totalCost / $totalLiters : 0, 2),
                ];
            }
        }

        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Fuel Cost Report', $headings), 'fuel_cost_report_' . date('Y-m-d') . '.xlsx');
    }

    public function fuelCostExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $fuelCostData = $vehicles->map(function ($vehicle) use ($dateFrom, $dateTo) {
            $fuelLogs = FleetFuelLog::where('vehicle_id', $vehicle->id)
                ->whereBetween('date_filled', [$dateFrom, $dateTo])
                ->get();

            return [
                'vehicle' => $vehicle,
                'total_liters' => $fuelLogs->sum('liters_filled') ?? 0,
                'total_cost' => $fuelLogs->sum('total_cost') ?? 0,
                'fill_count' => $fuelLogs->count(),
                'avg_cost_per_liter' => $fuelLogs->count() > 0 ? ($fuelLogs->sum('total_cost') / $fuelLogs->sum('liters_filled')) : 0,
            ];
        })->filter(fn($item) => $item['fill_count'] > 0);

        $totalCost = $fuelCostData->sum('total_cost');
        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.fuel-cost', compact(
            'fuelCostData', 'totalCost', 'company', 'branch', 'dateFrom', 'dateTo', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('fuel_cost_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Report 12: Operating Cost Report
     */
    public function operatingCost(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $operatingData = $vehicles->map(function ($vehicle) use ($dateFrom, $dateTo) {
            // Maintenance costs
            $maintenanceCost = WorkOrder::where('asset_id', $vehicle->id)
                ->where('status', 'completed')
                ->whereBetween('actual_completion_date', [$dateFrom, $dateTo])
                ->sum('actual_cost') ?? 0;

            // Fuel costs
            $fuelCost = FleetFuelLog::where('vehicle_id', $vehicle->id)
                ->whereBetween('date_filled', [$dateFrom, $dateTo])
                ->sum('total_cost') ?? 0;

            // Trip costs
            $tripCost = FleetTripCost::whereHas('trip', function($q) use ($vehicle, $dateFrom, $dateTo) {
                $q->where('vehicle_id', $vehicle->id)
                    ->whereBetween('planned_start_date', [$dateFrom, $dateTo]);
            })->sum('amount') ?? 0;

            // Distance
            $distance = FleetTrip::where('vehicle_id', $vehicle->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->sum('actual_distance_km') ?? 0;

            $totalCost = $maintenanceCost + $fuelCost + $tripCost;

            return [
                'vehicle' => $vehicle,
                'maintenance_cost' => $maintenanceCost,
                'fuel_cost' => $fuelCost,
                'trip_cost' => $tripCost,
                'total_cost' => $totalCost,
                'distance' => $distance,
                'cost_per_km' => $distance > 0 ? $totalCost / $distance : 0,
            ];
        })->filter(fn($item) => $item['total_cost'] > 0);

        $totalCost = $operatingData->sum('total_cost');

        return view('fleet.reports.operating-cost', compact('operatingData', 'totalCost', 'dateFrom', 'dateTo'));
    }

    public function operatingCostExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $data = [];
        foreach ($vehicles as $vehicle) {
            $maintenanceCost = WorkOrder::where('asset_id', $vehicle->id)
                ->where('status', 'completed')
                ->whereBetween('actual_completion_date', [$dateFrom, $dateTo])
                ->sum('actual_cost') ?? 0;

            $fuelCost = FleetFuelLog::where('vehicle_id', $vehicle->id)
                ->whereBetween('date_filled', [$dateFrom, $dateTo])
                ->sum('total_cost') ?? 0;

            $tripCost = FleetTripCost::whereHas('trip', function($q) use ($vehicle, $dateFrom, $dateTo) {
                $q->where('vehicle_id', $vehicle->id)
                    ->whereBetween('planned_start_date', [$dateFrom, $dateTo]);
            })->sum('amount') ?? 0;

            $distance = FleetTrip::where('vehicle_id', $vehicle->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->sum('actual_distance_km') ?? 0;

            $totalCost = $maintenanceCost + $fuelCost + $tripCost;

            if ($totalCost > 0) {
                $data[] = [
                    'Vehicle' => $vehicle->name,
                    'Registration' => $vehicle->registration_number ?? 'N/A',
                    'Maintenance Cost' => number_format($maintenanceCost, 2),
                    'Fuel Cost' => number_format($fuelCost, 2),
                    'Trip Cost' => number_format($tripCost, 2),
                    'Total Cost' => number_format($totalCost, 2),
                    'Distance (km)' => number_format($distance, 2),
                    'Cost per km' => number_format($distance > 0 ? $totalCost / $distance : 0, 2),
                ];
            }
        }

        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Operating Cost Report', $headings), 'operating_cost_report_' . date('Y-m-d') . '.xlsx');
    }

    public function operatingCostExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $operatingData = $vehicles->map(function ($vehicle) use ($dateFrom, $dateTo) {
            $maintenanceCost = WorkOrder::where('asset_id', $vehicle->id)
                ->where('status', 'completed')
                ->whereBetween('actual_completion_date', [$dateFrom, $dateTo])
                ->sum('actual_cost') ?? 0;

            $fuelCost = FleetFuelLog::where('vehicle_id', $vehicle->id)
                ->whereBetween('date_filled', [$dateFrom, $dateTo])
                ->sum('total_cost') ?? 0;

            $tripCost = FleetTripCost::whereHas('trip', function($q) use ($vehicle, $dateFrom, $dateTo) {
                $q->where('vehicle_id', $vehicle->id)
                    ->whereBetween('planned_start_date', [$dateFrom, $dateTo]);
            })->sum('amount') ?? 0;

            $distance = FleetTrip::where('vehicle_id', $vehicle->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->sum('actual_distance_km') ?? 0;

            $totalCost = $maintenanceCost + $fuelCost + $tripCost;

            return [
                'vehicle' => $vehicle,
                'maintenance_cost' => $maintenanceCost,
                'fuel_cost' => $fuelCost,
                'trip_cost' => $tripCost,
                'total_cost' => $totalCost,
                'distance' => $distance,
                'cost_per_km' => $distance > 0 ? $totalCost / $distance : 0,
            ];
        })->filter(fn($item) => $item['total_cost'] > 0);

        $totalCost = $operatingData->sum('total_cost');
        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.operating-cost', compact(
            'operatingData', 'totalCost', 'company', 'branch', 'dateFrom', 'dateTo', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('operating_cost_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Report 13: Driver Activity Report
     */
    public function driverActivity(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $drivers = FleetDriver::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $activityData = $drivers->map(function ($driver) use ($dateFrom, $dateTo) {
            $trips = FleetTrip::where('driver_id', $driver->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->get();

            $completedTrips = $trips->where('status', 'completed')->count();
            $inProgressTrips = $trips->where('status', 'in_progress')->count();
            $distance = $trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0);

            return [
                'driver' => $driver,
                'total_trips' => $trips->count(),
                'completed_trips' => $completedTrips,
                'in_progress_trips' => $inProgressTrips,
                'distance' => $distance,
                'completion_rate' => $trips->count() > 0 ? ($completedTrips / $trips->count()) * 100 : 0,
            ];
        })->filter(fn($item) => $item['total_trips'] > 0);

        return view('fleet.reports.driver-activity', compact('activityData', 'dateFrom', 'dateTo'));
    }

    public function driverActivityExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $drivers = FleetDriver::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $data = [];
        foreach ($drivers as $driver) {
            $trips = FleetTrip::where('driver_id', $driver->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->get();

            if ($trips->count() > 0) {
                $completedTrips = $trips->where('status', 'completed')->count();
                $distance = $trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0);

                $data[] = [
                    'Driver Code' => $driver->driver_code ?? 'N/A',
                    'Driver Name' => $driver->full_name,
                    'Total Trips' => $trips->count(),
                    'Completed Trips' => $completedTrips,
                    'In Progress' => $trips->where('status', 'in_progress')->count(),
                    'Distance (km)' => number_format($distance, 2),
                    'Completion Rate (%)' => number_format(($completedTrips / $trips->count()) * 100, 2),
                ];
            }
        }

        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Driver Activity Report', $headings), 'driver_activity_report_' . date('Y-m-d') . '.xlsx');
    }

    public function driverActivityExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $drivers = FleetDriver::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $activityData = $drivers->map(function ($driver) use ($dateFrom, $dateTo) {
            $trips = FleetTrip::where('driver_id', $driver->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->get();

            $completedTrips = $trips->where('status', 'completed')->count();
            $distance = $trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0);

            return [
                'driver' => $driver,
                'total_trips' => $trips->count(),
                'completed_trips' => $completedTrips,
                'in_progress_trips' => $trips->where('status', 'in_progress')->count(),
                'distance' => $distance,
                'completion_rate' => $trips->count() > 0 ? ($completedTrips / $trips->count()) * 100 : 0,
            ];
        })->filter(fn($item) => $item['total_trips'] > 0);

        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.driver-activity', compact(
            'activityData', 'company', 'branch', 'dateFrom', 'dateTo', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('driver_activity_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Report 14: Driver Collection Report
     */
    public function driverCollection(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $drivers = FleetDriver::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $collectionData = $drivers->map(function ($driver) use ($dateFrom, $dateTo) {
            // Get trips for this driver
            $trips = FleetTrip::where('driver_id', $driver->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->get();

            $revenue = $this->calculateTripsRevenue($trips);

            return [
                'driver' => $driver,
                'trips' => $trips->count(),
                'revenue_collected' => $revenue,
            ];
        })->filter(fn($item) => $item['trips'] > 0);

        $totalCollection = $collectionData->sum('revenue_collected');

        return view('fleet.reports.driver-collection', compact('collectionData', 'totalCollection', 'dateFrom', 'dateTo'));
    }

    public function driverCollectionExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $drivers = FleetDriver::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $data = [];
        foreach ($drivers as $driver) {
            $trips = FleetTrip::where('driver_id', $driver->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->get();

            if ($trips->count() > 0) {
                $revenue = $this->calculateTripsRevenue($trips);

                $data[] = [
                    'Driver Code' => $driver->driver_code ?? 'N/A',
                    'Driver Name' => $driver->full_name,
                    'Trips' => $trips->count(),
                    'Revenue Collected' => number_format($revenue, 2),
                ];
            }
        }

        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Driver Collection Report', $headings), 'driver_collection_report_' . date('Y-m-d') . '.xlsx');
    }

    public function driverCollectionExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $drivers = FleetDriver::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $collectionData = $drivers->map(function ($driver) use ($dateFrom, $dateTo) {
            $trips = FleetTrip::where('driver_id', $driver->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->get();

            $revenue = $this->calculateTripsRevenue($trips);

            return [
                'driver' => $driver,
                'trips' => $trips->count(),
                'revenue_collected' => $revenue,
            ];
        })->filter(fn($item) => $item['trips'] > 0);

        $totalCollection = $collectionData->sum('revenue_collected');
        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.driver-collection', compact(
            'collectionData', 'totalCollection', 'company', 'branch', 'dateFrom', 'dateTo', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('driver_collection_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Report 15: Driver Outstanding Report
     */
    public function driverOutstanding(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $drivers = FleetDriver::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $outstandingData = $drivers->map(function ($driver) {
            // Get unpaid invoices for trips by this driver
            $invoices = FleetInvoice::where('driver_id', $driver->id)
                ->where('status', '!=', 'paid')
                ->get();

            $totalOutstanding = $invoices->sum('balance_due') ?? 0;

            return [
                'driver' => $driver,
                'invoice_count' => $invoices->count(),
                'total_outstanding' => $totalOutstanding,
                'invoices' => $invoices, // Include invoices for detail view
            ];
        })->filter(fn($item) => $item['invoice_count'] > 0);

        $totalOutstanding = $outstandingData->sum('total_outstanding');

        return view('fleet.reports.driver-outstanding', compact('outstandingData', 'totalOutstanding'));
    }

    public function driverOutstandingExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $drivers = FleetDriver::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $data = [];
        foreach ($drivers as $driver) {
            $invoices = FleetInvoice::where('driver_id', $driver->id)
                ->where('status', '!=', 'paid')
                ->get();

            if ($invoices->count() > 0) {
                $data[] = [
                    'Driver Code' => $driver->driver_code ?? 'N/A',
                    'Driver Name' => $driver->full_name,
                    'Outstanding Invoices' => $invoices->count(),
                    'Total Outstanding' => number_format($invoices->sum('balance_due'), 2),
                ];
            }
        }

        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Driver Outstanding Report', $headings), 'driver_outstanding_report_' . date('Y-m-d') . '.xlsx');
    }

    public function driverOutstandingExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $drivers = FleetDriver::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $outstandingData = $drivers->map(function ($driver) {
            $invoices = FleetInvoice::where('driver_id', $driver->id)
                ->where('status', '!=', 'paid')
                ->get();

            $totalOutstanding = $invoices->sum('balance_due') ?? 0;

            return [
                'driver' => $driver,
                'invoice_count' => $invoices->count(),
                'total_outstanding' => $totalOutstanding,
                'invoices' => $invoices, // Include invoices for detail view
            ];
        })->filter(fn($item) => $item['invoice_count'] > 0);

        $totalOutstanding = $outstandingData->sum('total_outstanding');
        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.driver-outstanding', compact(
            'outstandingData', 'totalOutstanding', 'company', 'branch', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('driver_outstanding_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Report 16: Vehicle Utilization Report
     */
    public function vehicleUtilization(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $utilizationData = $vehicles->map(function ($vehicle) use ($dateFrom, $dateTo) {
            $trips = FleetTrip::where('vehicle_id', $vehicle->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->get();

            $totalDays = $dateFrom->diffInDays($dateTo) + 1;
            $activeDays = $trips->pluck('planned_start_date')->map(fn($d) => $d?->format('Y-m-d'))->unique()->count();
            $utilizationRate = $totalDays > 0 ? ($activeDays / $totalDays) * 100 : 0;

            return [
                'vehicle' => $vehicle,
                'total_trips' => $trips->count(),
                'active_days' => $activeDays,
                'total_days' => $totalDays,
                'utilization_rate' => $utilizationRate,
                'distance' => $trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0),
            ];
        });

        return view('fleet.reports.vehicle-utilization', compact('utilizationData', 'dateFrom', 'dateTo'));
    }

    public function vehicleUtilizationExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $data = [];
        $totalDays = $dateFrom->diffInDays($dateTo) + 1;

        foreach ($vehicles as $vehicle) {
            $trips = FleetTrip::where('vehicle_id', $vehicle->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->get();

            $activeDays = $trips->pluck('planned_start_date')->map(fn($d) => $d?->format('Y-m-d'))->unique()->count();

            $data[] = [
                'Vehicle' => $vehicle->name,
                'Registration' => $vehicle->registration_number ?? 'N/A',
                'Total Trips' => $trips->count(),
                'Active Days' => $activeDays,
                'Total Days' => $totalDays,
                'Utilization Rate (%)' => number_format(($activeDays / $totalDays) * 100, 2),
                'Distance (km)' => number_format($trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0), 2),
            ];
        }

        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Vehicle Utilization Report', $headings), 'vehicle_utilization_report_' . date('Y-m-d') . '.xlsx');
    }

    public function vehicleUtilizationExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $utilizationData = $vehicles->map(function ($vehicle) use ($dateFrom, $dateTo) {
            $trips = FleetTrip::where('vehicle_id', $vehicle->id)
                ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
                ->get();

            $totalDays = $dateFrom->diffInDays($dateTo) + 1;
            $activeDays = $trips->pluck('planned_start_date')->map(fn($d) => $d?->format('Y-m-d'))->unique()->count();

            return [
                'vehicle' => $vehicle,
                'total_trips' => $trips->count(),
                'active_days' => $activeDays,
                'total_days' => $totalDays,
                'utilization_rate' => $totalDays > 0 ? ($activeDays / $totalDays) * 100 : 0,
                'distance' => $trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0),
            ];
        });

        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.vehicle-utilization', compact(
            'utilizationData', 'company', 'branch', 'dateFrom', 'dateTo', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('vehicle_utilization_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Report 17: Dispatch Efficiency Report
     */
    public function dispatchEfficiency(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $trips = FleetTrip::with(['vehicle', 'driver', 'route'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
            ->get();

        $efficiencyData = $trips->map(function ($trip) {
            $onTime = false;
            $delayed = false;

            if ($trip->actual_start_time && $trip->planned_start_time) {
                $onTime = $trip->actual_start_time <= $trip->planned_start_time->addMinutes(15);
                $delayed = !$onTime;
            }

            return [
                'trip' => $trip,
                'on_time' => $onTime,
                'delayed' => $delayed,
                'delay_minutes' => $trip->actual_start_time && $trip->planned_start_time ? 
                    $trip->actual_start_time->diffInMinutes($trip->planned_start_time) : 0,
            ];
        });

        $onTimeCount = $efficiencyData->filter(fn($item) => $item['on_time'])->count();
        $delayedCount = $efficiencyData->filter(fn($item) => $item['delayed'])->count();
        $onTimeRate = $trips->count() > 0 ? ($onTimeCount / $trips->count()) * 100 : 0;

        return view('fleet.reports.dispatch-efficiency', compact('efficiencyData', 'onTimeCount', 'delayedCount', 'onTimeRate', 'dateFrom', 'dateTo'));
    }

    public function dispatchEfficiencyExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $trips = FleetTrip::with(['vehicle', 'driver', 'route'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
            ->get();

        $data = $trips->map(function ($trip) {
            $onTime = false;
            $delayMinutes = 0;

            if ($trip->actual_start_date && $trip->planned_start_date) {
                $plannedTime = Carbon::parse($trip->planned_start_date);
                $actualTime = Carbon::parse($trip->actual_start_date);
                $delayMinutes = $actualTime->diffInMinutes($plannedTime, false);
                $onTime = $delayMinutes <= 15 && $delayMinutes >= -15;
            }

            return [
                'Trip Number' => $trip->trip_number,
                'Date' => $trip->planned_start_date?->format('Y-m-d') ?? 'N/A',
                'Vehicle' => $trip->vehicle->name ?? 'N/A',
                'Driver' => $trip->driver->full_name ?? 'N/A',
                'Route' => $trip->route->route_name ?? ($trip->origin_location && $trip->destination_location ? $trip->origin_location . ' - ' . $trip->destination_location : 'N/A'),
                'Planned Start' => $trip->planned_start_date?->format('Y-m-d H:i') ?? 'N/A',
                'Actual Start' => $trip->actual_start_date?->format('Y-m-d H:i') ?? 'N/A',
                'Delay (mins)' => $delayMinutes,
                'Status' => $onTime ? 'On Time' : 'Delayed',
            ];
        })->toArray();

        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Dispatch Efficiency Report', $headings), 'dispatch_efficiency_report_' . date('Y-m-d') . '.xlsx');
    }

    public function dispatchEfficiencyExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $trips = FleetTrip::with(['vehicle', 'driver', 'route'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
            ->get();

        $efficiencyData = $trips->map(function ($trip) {
            $onTime = false;
            $delayed = false;
            $delayMinutes = 0;

            if ($trip->actual_start_date && $trip->planned_start_date) {
                $plannedTime = Carbon::parse($trip->planned_start_date);
                $actualTime = Carbon::parse($trip->actual_start_date);
                $delayMinutes = $actualTime->diffInMinutes($plannedTime, false);
                $onTime = $delayMinutes <= 15 && $delayMinutes >= -15;
                $delayed = !$onTime;
            }

            return [
                'trip' => $trip,
                'on_time' => $onTime,
                'delayed' => $delayed,
                'delay_minutes' => $delayMinutes,
            ];
        });

        $onTimeCount = $efficiencyData->filter(fn($item) => $item['on_time'])->count();
        $delayedCount = $efficiencyData->filter(fn($item) => $item['delayed'])->count();
        $onTimeRate = $trips->count() > 0 ? ($onTimeCount / $trips->count()) * 100 : 0;
        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.dispatch-efficiency', compact(
            'efficiencyData', 'onTimeCount', 'delayedCount', 'onTimeRate', 'company', 'branch', 'dateFrom', 'dateTo', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('dispatch_efficiency_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Report 18: Insurance Expiry Report
     */
    public function insuranceExpiry(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $expiryData = $vehicles->map(function ($vehicle) {
            $insuranceExpiry = $vehicle->insurance_expiry_date;
            $daysToExpiry = $insuranceExpiry ? Carbon::parse($insuranceExpiry)->diffInDays(Carbon::today(), false) : null;
            
            $status = 'Active';
            if ($daysToExpiry !== null) {
                if ($daysToExpiry < 0) {
                    $status = 'Expired';
                } elseif ($daysToExpiry <= 30) {
                    $status = 'Expiring Soon';
                }
            }

            return [
                'vehicle' => $vehicle,
                'insurance_expiry' => $insuranceExpiry,
                'days_to_expiry' => $daysToExpiry,
                'status' => $status,
            ];
        });

        $expired = $expiryData->filter(fn($item) => $item['status'] == 'Expired');
        $expiringSoon = $expiryData->filter(fn($item) => $item['status'] == 'Expiring Soon');

        return view('fleet.reports.insurance-expiry', compact('expiryData', 'expired', 'expiringSoon'));
    }

    public function insuranceExpiryExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $data = $vehicles->map(function ($vehicle) {
            $insuranceExpiry = $vehicle->insurance_expiry_date;
            $daysToExpiry = $insuranceExpiry ? Carbon::parse($insuranceExpiry)->diffInDays(Carbon::today(), false) : null;
            
            $status = 'Active';
            if ($daysToExpiry !== null) {
                if ($daysToExpiry < 0) {
                    $status = 'Expired';
                } elseif ($daysToExpiry <= 30) {
                    $status = 'Expiring Soon';
                }
            }

            return [
                'Vehicle' => $vehicle->name,
                'Registration' => $vehicle->registration_number ?? 'N/A',
                'Insurance Expiry Date' => $insuranceExpiry ? Carbon::parse($insuranceExpiry)->format('Y-m-d') : 'N/A',
                'Days to Expiry' => $daysToExpiry ?? 'N/A',
                'Status' => $status,
            ];
        })->toArray();

        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Insurance Expiry Report', $headings), 'insurance_expiry_report_' . date('Y-m-d') . '.xlsx');
    }

    public function insuranceExpiryExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $expiryData = $vehicles->map(function ($vehicle) {
            $insuranceExpiry = $vehicle->insurance_expiry_date;
            $daysToExpiry = $insuranceExpiry ? Carbon::parse($insuranceExpiry)->diffInDays(Carbon::today(), false) : null;
            
            $status = 'Active';
            if ($daysToExpiry !== null) {
                if ($daysToExpiry < 0) {
                    $status = 'Expired';
                } elseif ($daysToExpiry <= 30) {
                    $status = 'Expiring Soon';
                }
            }

            return [
                'vehicle' => $vehicle,
                'insurance_expiry' => $insuranceExpiry,
                'days_to_expiry' => $daysToExpiry,
                'status' => $status,
            ];
        });

        $expired = $expiryData->filter(fn($item) => $item['status'] == 'Expired');
        $expiringSoon = $expiryData->filter(fn($item) => $item['status'] == 'Expiring Soon');
        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.insurance-expiry', compact(
            'expiryData', 'expired', 'expiringSoon', 'company', 'branch', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('insurance_expiry_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Report 19: Monthly Performance Report
     */
    public function monthlyPerformance(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $month = $request->filled('month') ? Carbon::parse($request->month) : Carbon::today();
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        // Revenue
        $trips = FleetTrip::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$startOfMonth, $endOfMonth])
            ->get();
        
        $revenue = $this->calculateTripsRevenue($trips);

        // Trips
        $totalTrips = FleetTrip::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$startOfMonth, $endOfMonth])
            ->count();

        $completedTrips = FleetTrip::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$startOfMonth, $endOfMonth])
            ->where('status', 'completed')
            ->count();

        // Distance
        $distance = FleetTrip::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$startOfMonth, $endOfMonth])
            ->sum('actual_distance_km') ?? 0;

        // Expenses
        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->pluck('id');

        $maintenanceCost = WorkOrder::whereIn('asset_id', $vehicles)
            ->where('status', 'completed')
            ->whereBetween('actual_completion_date', [$startOfMonth, $endOfMonth])
            ->sum('actual_cost') ?? 0;

        $fuelCost = FleetFuelLog::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('date_filled', [$startOfMonth, $endOfMonth])
            ->sum('total_cost') ?? 0;

        $totalExpenses = $maintenanceCost + $fuelCost;
        $netProfit = $revenue - $totalExpenses;

        $summary = [
            'revenue' => $revenue,
            'total_trips' => $totalTrips,
            'completed_trips' => $completedTrips,
            'distance' => $distance,
            'maintenance_cost' => $maintenanceCost,
            'fuel_cost' => $fuelCost,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'completion_rate' => $totalTrips > 0 ? ($completedTrips / $totalTrips) * 100 : 0,
        ];

        return view('fleet.reports.monthly-performance', compact('summary', 'month'));
    }

    public function monthlyPerformanceExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $month = $request->filled('month') ? Carbon::parse($request->month) : Carbon::today();
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $trips = FleetTrip::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$startOfMonth, $endOfMonth])
            ->get();
        
        $revenue = $this->calculateTripsRevenue($trips);

        $totalTrips = FleetTrip::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$startOfMonth, $endOfMonth])
            ->count();

        $completedTrips = FleetTrip::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$startOfMonth, $endOfMonth])
            ->where('status', 'completed')
            ->count();

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->pluck('id');

        $maintenanceCost = WorkOrder::whereIn('asset_id', $vehicles)
            ->where('status', 'completed')
            ->whereBetween('actual_completion_date', [$startOfMonth, $endOfMonth])
            ->sum('actual_cost') ?? 0;

        $fuelCost = FleetFuelLog::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('date_filled', [$startOfMonth, $endOfMonth])
            ->sum('total_cost') ?? 0;

        $data = [
            ['Metric', 'Value'],
            ['Month', $month->format('F Y')],
            ['Total Revenue', number_format($revenue, 2)],
            ['Total Trips', $totalTrips],
            ['Completed Trips', $completedTrips],
            ['Completion Rate (%)', number_format(($completedTrips / max($totalTrips, 1)) * 100, 2)],
            ['Maintenance Cost', number_format($maintenanceCost, 2)],
            ['Fuel Cost', number_format($fuelCost, 2)],
            ['Total Expenses', number_format($maintenanceCost + $fuelCost, 2)],
            ['Net Profit', number_format($revenue - ($maintenanceCost + $fuelCost), 2)],
        ];

        $headings = ['Metric', 'Value'];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Monthly Performance Report', $headings), 'monthly_performance_report_' . date('Y-m-d') . '.xlsx');
    }

    public function monthlyPerformanceExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $month = $request->filled('month') ? Carbon::parse($request->month) : Carbon::today();
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $trips = FleetTrip::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$startOfMonth, $endOfMonth])
            ->get();
        
        $revenue = $this->calculateTripsRevenue($trips);

        $totalTrips = FleetTrip::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$startOfMonth, $endOfMonth])
            ->count();

        $completedTrips = FleetTrip::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$startOfMonth, $endOfMonth])
            ->where('status', 'completed')
            ->count();

        $distance = FleetTrip::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$startOfMonth, $endOfMonth])
            ->sum('actual_distance_km') ?? 0;

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->pluck('id');

        $maintenanceCost = WorkOrder::whereIn('asset_id', $vehicles)
            ->where('status', 'completed')
            ->whereBetween('actual_completion_date', [$startOfMonth, $endOfMonth])
            ->sum('actual_cost') ?? 0;

        $fuelCost = FleetFuelLog::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('date_filled', [$startOfMonth, $endOfMonth])
            ->sum('total_cost') ?? 0;

        $totalExpenses = $maintenanceCost + $fuelCost;
        $netProfit = $revenue - $totalExpenses;

        $summary = [
            'revenue' => $revenue,
            'total_trips' => $totalTrips,
            'completed_trips' => $completedTrips,
            'distance' => $distance,
            'maintenance_cost' => $maintenanceCost,
            'fuel_cost' => $fuelCost,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'completion_rate' => $totalTrips > 0 ? ($completedTrips / $totalTrips) * 100 : 0,
        ];

        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.monthly-performance', compact(
            'summary', 'month', 'company', 'branch', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('monthly_performance_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Report 20: Missing Trip Invoice Report
     */
    public function missingTripInvoice(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        // Get completed trips without invoices (check both direct invoices and invoice items)
        $allTrips = FleetTrip::with(['vehicle', 'driver', 'customer'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->orderBy('planned_start_date', 'desc')
            ->get();
        
        // Filter trips that don't have invoices (direct or via items)
        $tripsWithoutInvoices = $allTrips->filter(function($trip) {
            $directInvoiceIds = FleetInvoice::where('trip_id', $trip->id)->pluck('id');
            $itemInvoiceIds = \App\Models\Fleet\FleetInvoiceItem::where('trip_id', $trip->id)
                ->distinct()
                ->pluck('fleet_invoice_id');
            $allInvoiceIds = $directInvoiceIds->merge($itemInvoiceIds)->unique()->filter();
            return $allInvoiceIds->count() == 0;
        });

        $totalRevenue = $tripsWithoutInvoices->sum('planned_revenue') ?? 0;

        return view('fleet.reports.missing-trip-invoice', compact('tripsWithoutInvoices', 'totalRevenue', 'dateFrom', 'dateTo'));
    }

    public function missingTripInvoiceExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $allTrips = FleetTrip::with(['vehicle', 'driver', 'customer'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->orderBy('planned_start_date', 'desc')
            ->get();
        
        // Filter trips that don't have invoices (direct or via items)
        $tripsWithoutInvoices = $allTrips->filter(function($trip) {
            $directInvoiceIds = FleetInvoice::where('trip_id', $trip->id)->pluck('id');
            $itemInvoiceIds = \App\Models\Fleet\FleetInvoiceItem::where('trip_id', $trip->id)
                ->distinct()
                ->pluck('fleet_invoice_id');
            $allInvoiceIds = $directInvoiceIds->merge($itemInvoiceIds)->unique()->filter();
            return $allInvoiceIds->count() == 0;
        });

        $data = $tripsWithoutInvoices->map(function ($trip) {
            return [
                'Trip Number' => $trip->trip_number,
                'Date' => $trip->planned_start_date?->format('Y-m-d') ?? 'N/A',
                'Vehicle' => $trip->vehicle->name ?? 'N/A',
                'Driver' => $trip->driver->full_name ?? 'N/A',
                'Customer' => $trip->customer->name ?? 'N/A',
                'Revenue' => number_format($trip->actual_revenue ?? $trip->planned_revenue ?? 0, 2),
                'Distance (km)' => number_format($trip->actual_distance_km ?? $trip->planned_distance_km ?? 0, 2),
            ];
        })->toArray();

        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Missing Trip Invoice Report', $headings), 'missing_trip_invoice_report_' . date('Y-m-d') . '.xlsx');
    }

    public function missingTripInvoiceExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::today();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::today();

        $allTrips = FleetTrip::with(['vehicle', 'driver', 'customer'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('planned_start_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->orderBy('planned_start_date', 'desc')
            ->get();
        
        // Filter trips that don't have invoices (direct or via items)
        $tripsWithoutInvoices = $allTrips->filter(function($trip) {
            $directInvoiceIds = FleetInvoice::where('trip_id', $trip->id)->pluck('id');
            $itemInvoiceIds = \App\Models\Fleet\FleetInvoiceItem::where('trip_id', $trip->id)
                ->distinct()
                ->pluck('fleet_invoice_id');
            $allInvoiceIds = $directInvoiceIds->merge($itemInvoiceIds)->unique()->filter();
            return $allInvoiceIds->count() == 0;
        });

        $totalRevenue = $tripsWithoutInvoices->sum('planned_revenue') ?? 0;
        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.missing-trip-invoice', compact(
            'tripsWithoutInvoices', 'totalRevenue', 'company', 'branch', 'dateFrom', 'dateTo', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('missing_trip_invoice_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Report 21: Vehicle Replacement Report
     */
    public function vehicleReplacement(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $replacementData = $vehicles->map(function ($vehicle) {
            // Calculate vehicle age
            $purchaseDate = $vehicle->purchase_date ? Carbon::parse($vehicle->purchase_date) : null;
            $ageYears = $purchaseDate ? $purchaseDate->diffInYears(Carbon::today()) : 0;

            // Get mileage (odometer reading from latest fuel log)
            $latestFuelLog = FleetFuelLog::where('vehicle_id', $vehicle->id)
                ->orderBy('date_filled', 'desc')
                ->first();
            $mileage = $latestFuelLog->odometer_reading ?? 0;

            // Calculate maintenance cost (last 12 months)
            $maintenanceCost = WorkOrder::where('asset_id', $vehicle->id)
                ->where('status', 'completed')
                ->where('actual_completion_date', '>=', Carbon::today()->subMonths(12))
                ->sum('actual_cost') ?? 0;

            // Replacement recommendation
            $recommendation = 'Good Condition';
            if ($ageYears > 10 || $mileage > 500000 || $maintenanceCost > 50000) {
                $recommendation = 'Consider Replacement';
            } elseif ($ageYears > 7 || $mileage > 300000 || $maintenanceCost > 30000) {
                $recommendation = 'Monitor Closely';
            }

            return [
                'vehicle' => $vehicle,
                'age_years' => $ageYears,
                'mileage' => $mileage,
                'maintenance_cost_12m' => $maintenanceCost,
                'recommendation' => $recommendation,
            ];
        });

        return view('fleet.reports.vehicle-replacement', compact('replacementData'));
    }

    public function vehicleReplacementExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $data = [];
        foreach ($vehicles as $vehicle) {
            $purchaseDate = $vehicle->purchase_date ? Carbon::parse($vehicle->purchase_date) : null;
            $ageYears = $purchaseDate ? $purchaseDate->diffInYears(Carbon::today()) : 0;

            $latestFuelLog = FleetFuelLog::where('vehicle_id', $vehicle->id)
                ->orderBy('date_filled', 'desc')
                ->first();
            $mileage = $latestFuelLog->odometer_reading ?? 0;

            $maintenanceCost = WorkOrder::where('asset_id', $vehicle->id)
                ->where('status', 'completed')
                ->where('actual_completion_date', '>=', Carbon::today()->subMonths(12))
                ->sum('actual_cost') ?? 0;

            $recommendation = 'Good Condition';
            if ($ageYears > 10 || $mileage > 500000 || $maintenanceCost > 50000) {
                $recommendation = 'Consider Replacement';
            } elseif ($ageYears > 7 || $mileage > 300000 || $maintenanceCost > 30000) {
                $recommendation = 'Monitor Closely';
            }

            $data[] = [
                'Vehicle' => $vehicle->name,
                'Registration' => $vehicle->registration_number ?? 'N/A',
                'Age (Years)' => $ageYears,
                'Mileage' => number_format($mileage, 0),
                'Maintenance Cost (12m)' => number_format($maintenanceCost, 2),
                'Recommendation' => $recommendation,
            ];
        }

        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Vehicle Replacement Report', $headings), 'vehicle_replacement_report_' . date('Y-m-d') . '.xlsx');
    }

    public function vehicleReplacementExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $replacementData = $vehicles->map(function ($vehicle) {
            $purchaseDate = $vehicle->purchase_date ? Carbon::parse($vehicle->purchase_date) : null;
            $ageYears = $purchaseDate ? $purchaseDate->diffInYears(Carbon::today()) : 0;

            $latestFuelLog = FleetFuelLog::where('vehicle_id', $vehicle->id)
                ->orderBy('date_filled', 'desc')
                ->first();
            $mileage = $latestFuelLog->odometer_reading ?? 0;

            $maintenanceCost = WorkOrder::where('asset_id', $vehicle->id)
                ->where('status', 'completed')
                ->where('actual_completion_date', '>=', Carbon::today()->subMonths(12))
                ->sum('actual_cost') ?? 0;

            $recommendation = 'Good Condition';
            if ($ageYears > 10 || $mileage > 500000 || $maintenanceCost > 50000) {
                $recommendation = 'Consider Replacement';
            } elseif ($ageYears > 7 || $mileage > 300000 || $maintenanceCost > 30000) {
                $recommendation = 'Monitor Closely';
            }

            return [
                'vehicle' => $vehicle,
                'age_years' => $ageYears,
                'mileage' => $mileage,
                'maintenance_cost_12m' => $maintenanceCost,
                'recommendation' => $recommendation,
            ];
        });

        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.vehicle-replacement', compact(
            'replacementData', 'company', 'branch', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('vehicle_replacement_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Report 22: Alerts Report
     */
    public function alerts(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $alerts = [];

        // Check for expired/expiring insurance
        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        foreach ($vehicles as $vehicle) {
            if ($vehicle->insurance_expiry_date) {
                $daysToExpiry = Carbon::parse($vehicle->insurance_expiry_date)->diffInDays(Carbon::today(), false);
                if ($daysToExpiry < 0) {
                    $alerts[] = [
                        'type' => 'Insurance Expired',
                        'severity' => 'critical',
                        'vehicle' => $vehicle->name,
                        'vehicle_id' => $vehicle->id,
                        'message' => 'Insurance expired ' . abs($daysToExpiry) . ' days ago',
                        'insurance_expiry_date' => $vehicle->insurance_expiry_date,
                        'days_to_expiry' => $daysToExpiry,
                    ];
                } elseif ($daysToExpiry <= 30) {
                    $alerts[] = [
                        'type' => 'Insurance Expiring',
                        'severity' => 'warning',
                        'vehicle' => $vehicle->name,
                        'vehicle_id' => $vehicle->id,
                        'message' => 'Insurance expires in ' . $daysToExpiry . ' days',
                        'insurance_expiry_date' => $vehicle->insurance_expiry_date,
                        'days_to_expiry' => $daysToExpiry,
                    ];
                }
            }
        }

        // Check for overdue maintenance
        foreach ($vehicles as $vehicle) {
            $overdueWorkOrders = WorkOrder::where('asset_id', $vehicle->id)
                ->where('status', 'pending')
                ->where('estimated_start_date', '<', Carbon::today())
                ->get();

            if ($overdueWorkOrders->count() > 0) {
                $alerts[] = [
                    'type' => 'Overdue Maintenance',
                    'severity' => 'warning',
                    'vehicle' => $vehicle->name,
                    'vehicle_id' => $vehicle->id,
                    'message' => $overdueWorkOrders->count() . ' overdue maintenance work order(s)',
                    'details' => $overdueWorkOrders, // Include work orders for detail view
                    'detail_type' => 'work_orders',
                ];
            }
        }

        // Check for trips without invoices
        $tripsWithoutInvoices = FleetTrip::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'completed')
            ->where('planned_start_date', '>=', Carbon::today()->subDays(30))
            ->whereDoesntHave('invoices')
            ->with(['vehicle', 'driver', 'customer'])
            ->get();

        if ($tripsWithoutInvoices->count() > 0) {
            $alerts[] = [
                'type' => 'Missing Invoices',
                'severity' => 'warning',
                'vehicle' => 'N/A',
                'message' => $tripsWithoutInvoices->count() . ' completed trips without invoices in the last 30 days',
                'details' => $tripsWithoutInvoices, // Include trips for detail view
                'detail_type' => 'trips',
            ];
        }

        // Check for overdue invoices
        $overdueInvoices = FleetInvoice::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('due_date', '<', Carbon::today())
            ->where('status', '!=', 'paid')
            ->with(['vehicle', 'driver', 'customer'])
            ->get();

        if ($overdueInvoices->count() > 0) {
            $alerts[] = [
                'type' => 'Overdue Invoices',
                'severity' => 'critical',
                'vehicle' => 'N/A',
                'message' => $overdueInvoices->count() . ' overdue invoice(s)',
                'details' => $overdueInvoices, // Include invoices for detail view
                'detail_type' => 'invoices',
            ];
        }

        return view('fleet.reports.alerts', compact('alerts'));
    }

    public function alertsExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $alerts = [];

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        foreach ($vehicles as $vehicle) {
            if ($vehicle->insurance_expiry_date) {
                $daysToExpiry = Carbon::parse($vehicle->insurance_expiry_date)->diffInDays(Carbon::today(), false);
                if ($daysToExpiry < 0) {
                    $alerts[] = [
                        'Type' => 'Insurance Expired',
                        'Severity' => 'Critical',
                        'Vehicle' => $vehicle->name,
                        'Message' => 'Insurance expired ' . abs($daysToExpiry) . ' days ago',
                    ];
                } elseif ($daysToExpiry <= 30) {
                    $alerts[] = [
                        'Type' => 'Insurance Expiring',
                        'Severity' => 'Warning',
                        'Vehicle' => $vehicle->name,
                        'Message' => 'Insurance expires in ' . $daysToExpiry . ' days',
                    ];
                }
            }
        }

        foreach ($vehicles as $vehicle) {
            $overdueWorkOrders = WorkOrder::where('asset_id', $vehicle->id)
                ->where('status', 'pending')
                ->where('estimated_start_date', '<', Carbon::today())
                ->count();

            if ($overdueWorkOrders > 0) {
                $alerts[] = [
                    'Type' => 'Overdue Maintenance',
                    'Severity' => 'Warning',
                    'Vehicle' => $vehicle->name,
                    'Message' => $overdueWorkOrders . ' overdue maintenance work order(s)',
                ];
            }
        }

        $headings = !empty($alerts) ? array_keys($alerts[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($alerts, 'Alerts Report', $headings), 'alerts_report_' . date('Y-m-d') . '.xlsx');
    }

    public function alertsExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $alerts = [];

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        foreach ($vehicles as $vehicle) {
            if ($vehicle->insurance_expiry_date) {
                $daysToExpiry = Carbon::parse($vehicle->insurance_expiry_date)->diffInDays(Carbon::today(), false);
                if ($daysToExpiry < 0) {
                    $alerts[] = [
                        'type' => 'Insurance Expired',
                        'severity' => 'critical',
                        'vehicle' => $vehicle->name,
                        'message' => 'Insurance expired ' . abs($daysToExpiry) . ' days ago',
                    ];
                } elseif ($daysToExpiry <= 30) {
                    $alerts[] = [
                        'type' => 'Insurance Expiring',
                        'severity' => 'warning',
                        'vehicle' => $vehicle->name,
                        'message' => 'Insurance expires in ' . $daysToExpiry . ' days',
                    ];
                }
            }
        }

        foreach ($vehicles as $vehicle) {
            $overdueWorkOrders = WorkOrder::where('asset_id', $vehicle->id)
                ->where('status', 'pending')
                ->where('estimated_start_date', '<', Carbon::today())
                ->count();

            if ($overdueWorkOrders > 0) {
                $alerts[] = [
                    'type' => 'Overdue Maintenance',
                    'severity' => 'warning',
                    'vehicle' => $vehicle->name,
                    'message' => $overdueWorkOrders . ' overdue maintenance work order(s)',
                ];
            }
        }

        $tripsWithoutInvoices = FleetTrip::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'completed')
            ->where('planned_start_date', '>=', Carbon::today()->subDays(30))
            ->whereDoesntHave('invoices')
            ->count();

        if ($tripsWithoutInvoices > 0) {
            $alerts[] = [
                'type' => 'Missing Invoices',
                'severity' => 'warning',
                'vehicle' => 'N/A',
                'message' => $tripsWithoutInvoices . ' completed trips without invoices in the last 30 days',
            ];
        }

        $overdueInvoices = FleetInvoice::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('due_date', '<', Carbon::today())
            ->where('status', '!=', 'paid')
            ->count();

        if ($overdueInvoices > 0) {
            $alerts[] = [
                'type' => 'Overdue Invoices',
                'severity' => 'critical',
                'vehicle' => 'N/A',
                'message' => $overdueInvoices . ' overdue invoice(s)',
            ];
        }

        $generatedAt = now();

        $pdf = Pdf::loadView('fleet.reports.pdf.alerts', compact(
            'alerts', 'company', 'branch', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('alerts_report_' . date('Y-m-d') . '.pdf');
    }

    // ===========================
    // TYRE & SPARE PARTS REPORTS
    // ===========================

    /**
     * Tyre Performance & Lifespan Report
     */
    public function tyrePerformance(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $dateFrom = $request->filled('date_from') ? $request->date_from : Carbon::now()->subMonths(12)->toDateString();
        $dateTo = $request->filled('date_to') ? $request->date_to : Carbon::now()->toDateString();

        $tyreQuery = \App\Models\Fleet\FleetTyre::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['installations.vehicle', 'installations.tyrePosition'])
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->orderBy('purchase_date', 'desc');

        $tyres = $tyreQuery->get()->map(function ($tyre) {
            $latestInstallation = $tyre->installations()->latest('installed_at')->first();
            $kmCovered = 0;
            $req = \App\Models\Fleet\FleetTyreReplacementRequest::where('current_tyre_id', $tyre->id)->where('status', 'approved')->latest()->first();
            if ($req && $req->tyre_mileage_used) {
                $kmCovered = (float) $req->tyre_mileage_used;
            }
            return (object) [
                'serial_number' => $tyre->tyre_serial ?? 'N/A',
                'brand' => $tyre->brand ?? 'N/A',
                'model' => $tyre->model ?? 'N/A',
                'size' => $tyre->tyre_size ?? 'N/A',
                'purchase_date' => $tyre->purchase_date ? $tyre->purchase_date->format('Y-m-d') : 'N/A',
                'purchase_cost' => (float) ($tyre->purchase_cost ?? 0),
                'expected_lifespan_km' => (float) ($tyre->expected_lifespan_km ?? 0),
                'km_covered' => $kmCovered,
                'remaining_km' => max(0, (float)($tyre->expected_lifespan_km ?? 0) - $kmCovered),
                'status' => $tyre->status ?? 'new',
                'vehicle' => $latestInstallation && $latestInstallation->vehicle ? $latestInstallation->vehicle->name : 'Not Installed',
                'position' => $latestInstallation && $latestInstallation->tyrePosition ? $latestInstallation->tyrePosition->name : 'N/A',
            ];
        });

        $totalCost = $tyres->sum('purchase_cost');
        return view('fleet.reports.tyre-performance', compact('tyres', 'dateFrom', 'dateTo', 'totalCost'));
    }

    public function tyrePerformanceExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $dateFrom = $request->filled('date_from') ? $request->date_from : Carbon::now()->subMonths(12)->toDateString();
        $dateTo = $request->filled('date_to') ? $request->date_to : Carbon::now()->toDateString();

        $tyres = \App\Models\Fleet\FleetTyre::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['installations.vehicle', 'installations.tyrePosition'])
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->orderBy('purchase_date', 'desc')
            ->get();

        $data = $tyres->map(function ($tyre) {
            $latestInstallation = $tyre->installations()->latest('installed_at')->first();
            $kmCovered = 0;
            $req = \App\Models\Fleet\FleetTyreReplacementRequest::where('current_tyre_id', $tyre->id)->where('status', 'approved')->latest()->first();
            if ($req && $req->tyre_mileage_used) {
                $kmCovered = (float) $req->tyre_mileage_used;
            }
            $remaining = max(0, (float)($tyre->expected_lifespan_km ?? 0) - $kmCovered);
            return [
                'Serial Number' => $tyre->tyre_serial ?? 'N/A',
                'Brand' => $tyre->brand ?? 'N/A',
                'Model' => $tyre->model ?? 'N/A',
                'Size' => $tyre->tyre_size ?? 'N/A',
                'Purchase Date' => $tyre->purchase_date ? $tyre->purchase_date->format('Y-m-d') : 'N/A',
                'Purchase Cost' => number_format($tyre->purchase_cost ?? 0, 2),
                'Expected KM' => number_format($tyre->expected_lifespan_km ?? 0, 0),
                'KM Covered' => number_format($kmCovered, 0),
                'Remaining KM' => number_format($remaining, 0),
                'Status' => $tyre->status ?? 'N/A',
                'Current Vehicle' => $latestInstallation && $latestInstallation->vehicle ? $latestInstallation->vehicle->name : 'Not Installed',
                'Position' => $latestInstallation && $latestInstallation->tyrePosition ? $latestInstallation->tyrePosition->name : 'N/A',
            ];
        })->toArray();
        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Tyre Performance Report', $headings), 'tyre_performance_report_' . date('Y-m-d') . '.xlsx');
    }

    public function tyrePerformanceExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::now()->subMonths(12);
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::now();

        $tyres = \App\Models\Fleet\FleetTyre::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['installations.vehicle', 'installations.tyrePosition'])
            ->whereBetween('purchase_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->orderBy('purchase_date', 'desc')
            ->get()
            ->map(function ($tyre) {
                $latestInstallation = $tyre->installations()->latest('installed_at')->first();
                $kmCovered = 0;
                $req = \App\Models\Fleet\FleetTyreReplacementRequest::where('current_tyre_id', $tyre->id)->where('status', 'approved')->latest()->first();
                if ($req && $req->tyre_mileage_used) {
                    $kmCovered = (float) $req->tyre_mileage_used;
                }
                return (object) [
                    'serial_number' => $tyre->tyre_serial ?? 'N/A',
                    'brand' => $tyre->brand ?? 'N/A',
                    'model' => $tyre->model ?? 'N/A',
                    'size' => $tyre->tyre_size ?? 'N/A',
                    'purchase_date' => $tyre->purchase_date ? $tyre->purchase_date->format('Y-m-d') : 'N/A',
                    'purchase_cost' => (float) ($tyre->purchase_cost ?? 0),
                    'expected_lifespan_km' => (float) ($tyre->expected_lifespan_km ?? 0),
                    'km_covered' => $kmCovered,
                    'remaining_km' => max(0, (float)($tyre->expected_lifespan_km ?? 0) - $kmCovered),
                    'status' => $tyre->status ?? 'N/A',
                    'vehicle' => $latestInstallation && $latestInstallation->vehicle ? $latestInstallation->vehicle->name : 'Not Installed',
                    'position' => $latestInstallation && $latestInstallation->tyrePosition ? $latestInstallation->tyrePosition->name : 'N/A',
                ];
            });
        $totalCost = $tyres->sum('purchase_cost');
        $generatedAt = now();
        $pdf = Pdf::loadView('fleet.reports.pdf.tyre-performance', compact('tyres', 'dateFrom', 'dateTo', 'totalCost', 'company', 'branch', 'generatedAt'));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('tyre_performance_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Tyre Cost & Efficiency Report
     */
    public function tyreCostEfficiency(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $dateFrom = $request->filled('date_from') ? $request->date_from : Carbon::now()->subMonths(12)->toDateString();
        $dateTo = $request->filled('date_to') ? $request->date_to : Carbon::now()->toDateString();

        $tyreData = \App\Models\Fleet\FleetTyre::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['installations'])
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->orderBy('purchase_date', 'desc')
            ->get()
            ->map(function ($tyre) {
                $totalKm = 0;
                foreach (\App\Models\Fleet\FleetTyreReplacementRequest::where('current_tyre_id', $tyre->id)->where('status', 'approved')->get() as $req) {
                    $totalKm += (float) ($req->tyre_mileage_used ?? 0);
                }
                $cost = (float) ($tyre->purchase_cost ?? 0);
                $costPerKm = $totalKm > 0 ? $cost / $totalKm : 0;
                $expected = (float) ($tyre->expected_lifespan_km ?? 0);
                $efficiency = $totalKm > 0 && $expected > 0 ? round(($totalKm / $expected) * 100, 2) : 0;
                return (object) [
                    'serial_number' => $tyre->tyre_serial ?? 'N/A',
                    'brand' => $tyre->brand ?? 'N/A',
                    'purchase_cost' => $cost,
                    'total_km' => $totalKm,
                    'cost_per_km' => round($costPerKm, 2),
                    'expected_lifespan_km' => $expected,
                    'efficiency_rating' => $efficiency,
                ];
            });

        $totalCost = $tyreData->sum('purchase_cost');
        $totalKm = $tyreData->sum('total_km');
        return view('fleet.reports.tyre-cost-efficiency', compact('tyreData', 'dateFrom', 'dateTo', 'totalCost', 'totalKm'));
    }

    public function tyreCostEfficiencyExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $dateFrom = $request->filled('date_from') ? $request->date_from : Carbon::now()->subMonths(12)->toDateString();
        $dateTo = $request->filled('date_to') ? $request->date_to : Carbon::now()->toDateString();

        $tyreData = \App\Models\Fleet\FleetTyre::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->orderBy('purchase_date', 'desc')
            ->get()
            ->map(function ($tyre) {
                $totalKm = 0;
                foreach (\App\Models\Fleet\FleetTyreReplacementRequest::where('current_tyre_id', $tyre->id)->where('status', 'approved')->get() as $req) {
                    $totalKm += (float) ($req->tyre_mileage_used ?? 0);
                }
                $cost = (float) ($tyre->purchase_cost ?? 0);
                $costPerKm = $totalKm > 0 ? $cost / $totalKm : 0;
                $expected = (float) ($tyre->expected_lifespan_km ?? 0);
                $efficiency = $totalKm > 0 && $expected > 0 ? round(($totalKm / $expected) * 100, 2) : 0;
                return [
                    'Serial Number' => $tyre->tyre_serial ?? 'N/A',
                    'Brand' => $tyre->brand ?? 'N/A',
                    'Purchase Cost' => number_format($cost, 2),
                    'Total KM' => number_format($totalKm, 0),
                    'Cost Per KM' => number_format($costPerKm, 2),
                    'Expected Lifespan (KM)' => number_format($expected, 0),
                    'Efficiency Rating (%)' => $efficiency,
                ];
            })->toArray();
        $headings = !empty($tyreData) ? array_keys($tyreData[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($tyreData, 'Tyre Cost & Efficiency Report', $headings), 'tyre_cost_efficiency_report_' . date('Y-m-d') . '.xlsx');
    }

    public function tyreCostEfficiencyExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::now()->subMonths(12);
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::now();

        $tyreData = \App\Models\Fleet\FleetTyre::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('purchase_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->orderBy('purchase_date', 'desc')
            ->get()
            ->map(function ($tyre) {
                $totalKm = 0;
                foreach (\App\Models\Fleet\FleetTyreReplacementRequest::where('current_tyre_id', $tyre->id)->where('status', 'approved')->get() as $req) {
                    $totalKm += (float) ($req->tyre_mileage_used ?? 0);
                }
                $cost = (float) ($tyre->purchase_cost ?? 0);
                $costPerKm = $totalKm > 0 ? $cost / $totalKm : 0;
                $expected = (float) ($tyre->expected_lifespan_km ?? 0);
                $efficiency = $totalKm > 0 && $expected > 0 ? round(($totalKm / $expected) * 100, 2) : 0;
                return (object) [
                    'serial_number' => $tyre->tyre_serial ?? 'N/A',
                    'brand' => $tyre->brand ?? 'N/A',
                    'purchase_cost' => $cost,
                    'total_km' => $totalKm,
                    'cost_per_km' => round($costPerKm, 2),
                    'expected_lifespan_km' => $expected,
                    'efficiency_rating' => $efficiency,
                ];
            });
        $totalCost = $tyreData->sum('purchase_cost');
        $totalKm = $tyreData->sum('total_km');
        $generatedAt = now();
        $pdf = Pdf::loadView('fleet.reports.pdf.tyre-cost-efficiency', compact('tyreData', 'dateFrom', 'dateTo', 'totalCost', 'totalKm', 'company', 'branch', 'generatedAt'));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('tyre_cost_efficiency_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Tyre Abuse & Warranty Report
     */
    public function tyreAbuseWarranty(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $abuseData = \App\Models\Fleet\FleetTyreReplacementRequest::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['currentTyre', 'vehicle', 'requestedBy'])
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($request) {
                $tyre = $request->currentTyre;
                $kmCovered = (float) ($request->tyre_mileage_used ?? 0);
                $expectedKm = (float) ($tyre->expected_lifespan_km ?? 0);
                $prematureFailure = $expectedKm > 0 && $kmCovered > 0 && $kmCovered < ($expectedKm * 0.5);
                $failurePct = $expectedKm > 0 && $kmCovered > 0 ? round(($kmCovered / $expectedKm) * 100, 1) : 0;
                $warrantyStatus = 'N/A';
                if ($tyre && $tyre->warranty_type) {
                    $warrantyStatus = $tyre->warranty_limit_value ? 'Valid' : 'N/A';
                }
                return (object) [
                    'tyre_serial' => $tyre ? ($tyre->tyre_serial ?? 'N/A') : 'N/A',
                    'vehicle' => $request->vehicle ? $request->vehicle->name : 'N/A',
                    'requested_by' => $request->requestedBy ? $request->requestedBy->name : 'N/A',
                    'km_covered' => $kmCovered,
                    'expected_km' => $expectedKm,
                    'failure_pct' => $failurePct,
                    'premature_failure' => $prematureFailure,
                    'reason' => $request->reason ?? 'N/A',
                    'warranty_status' => $warrantyStatus,
                    'request_date' => $request->created_at->format('Y-m-d'),
                ];
            })
            ->filter(fn($item) => $item->premature_failure)
            ->values();

        return view('fleet.reports.tyre-abuse-warranty', compact('abuseData'));
    }

    public function tyreAbuseWarrantyExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $list = \App\Models\Fleet\FleetTyreReplacementRequest::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['currentTyre', 'vehicle', 'requestedBy'])
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($req) {
                $tyre = $req->currentTyre;
                $kmCovered = (float) ($req->tyre_mileage_used ?? 0);
                $expectedKm = (float) ($tyre->expected_lifespan_km ?? 0);
                return $expectedKm > 0 && $kmCovered > 0 && $kmCovered < ($expectedKm * 0.5);
            })
            ->map(function ($request) {
                $tyre = $request->currentTyre;
                $kmCovered = (float) ($request->tyre_mileage_used ?? 0);
                $expectedKm = (float) ($tyre->expected_lifespan_km ?? 0);
                $failurePct = $expectedKm > 0 && $kmCovered > 0 ? round(($kmCovered / $expectedKm) * 100, 1) : 0;
                return [
                    'Tyre Serial' => $tyre ? ($tyre->tyre_serial ?? 'N/A') : 'N/A',
                    'Vehicle' => $request->vehicle ? $request->vehicle->name : 'N/A',
                    'Requested By' => $request->requestedBy ? $request->requestedBy->name : 'N/A',
                    'KM Covered' => number_format($kmCovered, 0),
                    'Expected KM' => number_format($expectedKm, 0),
                    'Failure %' => $failurePct . '%',
                    'Reason' => $request->reason ?? 'N/A',
                    'Request Date' => $request->created_at->format('Y-m-d'),
                ];
            })->values()->toArray();
        $headings = !empty($list) ? array_keys($list[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($list, 'Tyre Abuse & Warranty Report', $headings), 'tyre_abuse_warranty_report_' . date('Y-m-d') . '.xlsx');
    }

    public function tyreAbuseWarrantyExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;

        $abuseData = \App\Models\Fleet\FleetTyreReplacementRequest::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['currentTyre', 'vehicle', 'requestedBy'])
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($request) {
                $tyre = $request->currentTyre;
                $kmCovered = (float) ($request->tyre_mileage_used ?? 0);
                $expectedKm = (float) ($tyre->expected_lifespan_km ?? 0);
                $prematureFailure = $expectedKm > 0 && $kmCovered > 0 && $kmCovered < ($expectedKm * 0.5);
                $failurePct = $expectedKm > 0 && $kmCovered > 0 ? round(($kmCovered / $expectedKm) * 100, 1) : 0;
                return (object) [
                    'tyre_serial' => $tyre ? ($tyre->tyre_serial ?? 'N/A') : 'N/A',
                    'vehicle' => $request->vehicle ? $request->vehicle->name : 'N/A',
                    'requested_by' => $request->requestedBy ? $request->requestedBy->name : 'N/A',
                    'km_covered' => $kmCovered,
                    'expected_km' => $expectedKm,
                    'failure_pct' => $failurePct,
                    'premature_failure' => $prematureFailure,
                    'reason' => $request->reason ?? 'N/A',
                    'request_date' => $request->created_at->format('Y-m-d'),
                ];
            })
            ->filter(fn($item) => $item->premature_failure)
            ->values();
        $generatedAt = now();
        $pdf = Pdf::loadView('fleet.reports.pdf.tyre-abuse-warranty', compact('abuseData', 'company', 'branch', 'generatedAt'));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('tyre_abuse_warranty_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Spare Parts Replacement History Report
     */
    public function sparePartsHistory(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $dateFrom = $request->filled('date_from') ? $request->date_from : Carbon::now()->subMonths(12)->toDateString();
        $dateTo = $request->filled('date_to') ? $request->date_to : Carbon::now()->toDateString();

        $replacements = \App\Models\Fleet\FleetSparePartReplacement::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['vehicle', 'sparePartCategory', 'approvedBy'])
            ->whereBetween('replaced_at', [$dateFrom, $dateTo])
            ->orderBy('replaced_at', 'desc')
            ->get();

        $totalCost = $replacements->sum('cost');
        return view('fleet.reports.spare-parts-history', compact('replacements', 'dateFrom', 'dateTo', 'totalCost'));
    }

    public function sparePartsHistoryExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $dateFrom = $request->filled('date_from') ? $request->date_from : Carbon::now()->subMonths(12)->toDateString();
        $dateTo = $request->filled('date_to') ? $request->date_to : Carbon::now()->toDateString();

        $replacements = \App\Models\Fleet\FleetSparePartReplacement::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['vehicle', 'sparePartCategory', 'approvedBy'])
            ->whereBetween('replaced_at', [$dateFrom, $dateTo])
            ->orderBy('replaced_at', 'desc')
            ->get();

        $data = $replacements->map(function ($r) {
            return [
                'Date' => $r->replaced_at ? $r->replaced_at->format('Y-m-d') : 'N/A',
                'Vehicle' => $r->vehicle ? $r->vehicle->name : 'N/A',
                'Spare Part' => $r->sparePartCategory ? $r->sparePartCategory->name : 'N/A',
                'Cost (TZS)' => number_format($r->cost ?? 0, 2),
                'Odometer' => $r->odometer_at_replacement ? number_format($r->odometer_at_replacement, 0) : 'N/A',
                'Reason' => $r->reason ?? 'N/A',
                'Approved By' => $r->approvedBy ? $r->approvedBy->name : 'N/A',
                'Status' => $r->status ?? 'N/A',
            ];
        })->toArray();
        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Spare Parts Replacement History', $headings), 'spare_parts_history_' . date('Y-m-d') . '.xlsx');
    }

    public function sparePartsHistoryExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::now()->subMonths(12);
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::now();

        $replacements = \App\Models\Fleet\FleetSparePartReplacement::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['vehicle', 'sparePartCategory', 'approvedBy'])
            ->whereBetween('replaced_at', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->orderBy('replaced_at', 'desc')
            ->get();
        $totalCost = $replacements->sum('cost');
        $generatedAt = now();
        $pdf = Pdf::loadView('fleet.reports.pdf.spare-parts-history', compact('replacements', 'dateFrom', 'dateTo', 'totalCost', 'company', 'branch', 'generatedAt'));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('spare_parts_history_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Spare Parts Cost Analysis Report
     */
    public function sparePartsCostAnalysis(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $dateFrom = $request->filled('date_from') ? $request->date_from : Carbon::now()->subMonths(12)->toDateString();
        $dateTo = $request->filled('date_to') ? $request->date_to : Carbon::now()->toDateString();

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $costData = $vehicles->map(function ($vehicle) use ($dateFrom, $dateTo) {
            $replacements = \App\Models\Fleet\FleetSparePartReplacement::where('vehicle_id', $vehicle->id)
                ->whereBetween('replaced_at', [$dateFrom, $dateTo])
                ->get();
            $totalCost = $replacements->sum('cost');
            $count = $replacements->count();
            return (object) [
                'vehicle' => $vehicle->name,
                'total_cost' => $totalCost,
                'replacement_count' => $count,
                'avg_cost_per_replacement' => $count > 0 ? round($totalCost / $count, 2) : 0,
            ];
        })->filter(fn($item) => $item->replacement_count > 0)->values();

        $totalCost = $costData->sum('total_cost');
        $totalReplacements = $costData->sum('replacement_count');
        return view('fleet.reports.spare-parts-cost-analysis', compact('costData', 'dateFrom', 'dateTo', 'totalCost', 'totalReplacements'));
    }

    public function sparePartsCostAnalysisExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $dateFrom = $request->filled('date_from') ? $request->date_from : Carbon::now()->subMonths(12)->toDateString();
        $dateTo = $request->filled('date_to') ? $request->date_to : Carbon::now()->toDateString();

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $data = $vehicles->map(function ($vehicle) use ($dateFrom, $dateTo) {
            $replacements = \App\Models\Fleet\FleetSparePartReplacement::where('vehicle_id', $vehicle->id)
                ->whereBetween('replaced_at', [$dateFrom, $dateTo])
                ->get();
            $totalCost = $replacements->sum('cost');
            $count = $replacements->count();
            if ($count === 0) return null;
            return [
                'Vehicle' => $vehicle->name,
                'Total Cost (TZS)' => number_format($totalCost, 2),
                'Replacement Count' => $count,
                'Avg Cost per Replacement (TZS)' => number_format($totalCost / $count, 2),
            ];
        })->filter()->values()->toArray();
        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Spare Parts Cost Analysis', $headings), 'spare_parts_cost_analysis_' . date('Y-m-d') . '.xlsx');
    }

    public function sparePartsCostAnalysisExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::now()->subMonths(12);
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::now();

        $vehicles = Asset::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->get();

        $costData = $vehicles->map(function ($vehicle) use ($dateFrom, $dateTo) {
            $replacements = \App\Models\Fleet\FleetSparePartReplacement::where('vehicle_id', $vehicle->id)
                ->whereBetween('replaced_at', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->get();
            $totalCost = $replacements->sum('cost');
            $count = $replacements->count();
            if ($count === 0) return null;
            return (object) [
                'vehicle' => $vehicle->name,
                'total_cost' => $totalCost,
                'replacement_count' => $count,
                'avg_cost_per_replacement' => round($totalCost / $count, 2),
            ];
        })->filter()->values();
        $totalCost = $costData->sum('total_cost');
        $totalReplacements = $costData->sum('replacement_count');
        $generatedAt = now();
        $pdf = Pdf::loadView('fleet.reports.pdf.spare-parts-cost-analysis', compact('costData', 'dateFrom', 'dateTo', 'totalCost', 'totalReplacements', 'company', 'branch', 'generatedAt'));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('spare_parts_cost_analysis_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Tyre Installation & Removal Log Report
     */
    public function tyreInstallationLog(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $dateFrom = $request->filled('date_from') ? $request->date_from : Carbon::now()->subMonths(12)->toDateString();
        $dateTo = $request->filled('date_to') ? $request->date_to : Carbon::now()->toDateString();

        $installations = \App\Models\Fleet\FleetTyreInstallation::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['tyre', 'vehicle', 'tyrePosition'])
            ->whereBetween('installed_at', [$dateFrom, $dateTo])
            ->orderBy('installed_at', 'desc')
            ->get();

        return view('fleet.reports.tyre-installation-log', compact('installations', 'dateFrom', 'dateTo'));
    }

    public function tyreInstallationLogExportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $dateFrom = $request->filled('date_from') ? $request->date_from : Carbon::now()->subMonths(12)->toDateString();
        $dateTo = $request->filled('date_to') ? $request->date_to : Carbon::now()->toDateString();

        $installations = \App\Models\Fleet\FleetTyreInstallation::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['tyre', 'vehicle', 'tyrePosition'])
            ->whereBetween('installed_at', [$dateFrom, $dateTo])
            ->orderBy('installed_at', 'desc')
            ->get();

        $data = $installations->map(function ($i) {
            return [
                'Installation Date' => $i->installed_at ? $i->installed_at->format('Y-m-d') : 'N/A',
                'Tyre Serial' => $i->tyre ? $i->tyre->tyre_serial : 'N/A',
                'Vehicle' => $i->vehicle ? $i->vehicle->name : 'N/A',
                'Position' => $i->tyrePosition ? $i->tyrePosition->name : 'N/A',
                'Odometer at Install' => $i->odometer_at_install ? number_format($i->odometer_at_install, 0) : 'N/A',
                'Installer' => $i->installer_name ?? 'N/A',
            ];
        })->toArray();
        $headings = !empty($data) ? array_keys($data[0]) : [];
        return Excel::download(new \App\Exports\FleetReportExport($data, 'Tyre Installation Log', $headings), 'tyre_installation_log_' . date('Y-m-d') . '.xlsx');
    }

    public function tyreInstallationLogExportPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::now()->subMonths(12);
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::now();

        $installations = \App\Models\Fleet\FleetTyreInstallation::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['tyre', 'vehicle', 'tyrePosition'])
            ->whereBetween('installed_at', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->orderBy('installed_at', 'desc')
            ->get();
        $generatedAt = now();
        $pdf = Pdf::loadView('fleet.reports.pdf.tyre-installation-log', compact('installations', 'dateFrom', 'dateTo', 'company', 'branch', 'generatedAt'));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('tyre_installation_log_' . date('Y-m-d') . '.pdf');
    }
}
