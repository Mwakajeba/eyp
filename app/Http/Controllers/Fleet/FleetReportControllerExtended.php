<?php
// This file contains implementations for all remaining fleet reports
// These methods will replace the placeholder methods in FleetReportController.php

namespace App\Http\Controllers\Fleet;

// Payment Collection Report - Report 3
public function paymentCollection(Request $request)
{
    $user = Auth::user();
    $companyId = $user->company_id;
    $branchId = session('branch_id') ?? $user->branch_id;

    $query = FleetInvoicePayment::with(['invoice.vehicle', 'invoice.driver', 'invoice.customer', 'bankAccount'])
        ->whereHas('invoice', fn($q) => $q->where('company_id', $companyId)
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

// Revenue by Vehicle Report - Report 5
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
        $revenue = $trips->sum('actual_revenue') ?? $trips->sum('planned_revenue') ?? 0;
        $tripCount = $trips->count();
        $distance = $trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0);

        return [
            'vehicle' => $vehicle,
            'revenue' => $revenue,
            'trip_count' => $tripCount,
            'distance' => $distance,
            'avg_revenue_per_trip' => $tripCount > 0 ? $revenue / $tripCount : 0,
        ];
    })->filter(fn($item) => $item['revenue'] > 0)->sortByDesc('revenue');

    $totalRevenue = $revenueData->sum('revenue');

    return view('fleet.reports.revenue-by-vehicle', compact('revenueData', 'totalRevenue', 'dateFrom', 'dateTo'));
}
