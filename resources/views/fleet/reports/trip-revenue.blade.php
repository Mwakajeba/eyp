@extends('layouts.main')

@section('title', 'Trip Revenue Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Trip Revenue Report', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />

        <h6 class="mb-0 text-uppercase">TRIP REVENUE REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.trip-revenue') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from', \Carbon\Carbon::today()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to', \Carbon\Carbon::today()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Vehicle</label>
                        <select name="vehicle_id" class="form-select">
                            <option value="">All Vehicles</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ request('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                    {{ $vehicle->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Driver</label>
                        <select name="driver_id" class="form-select">
                            <option value="">All Drivers</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary"><i class="bx bx-search me-1"></i> Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end gap-2">
                    <form method="POST" action="{{ route('fleet.reports.trip-revenue.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <input type="hidden" name="vehicle_id" value="{{ request('vehicle_id') }}">
                        <input type="hidden" name="driver_id" value="{{ request('driver_id') }}">
                        <input type="hidden" name="route_id" value="{{ request('route_id') }}">
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.trip-revenue.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <input type="hidden" name="vehicle_id" value="{{ request('vehicle_id') }}">
                        <input type="hidden" name="driver_id" value="{{ request('driver_id') }}">
                        <input type="hidden" name="route_id" value="{{ request('route_id') }}">
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bx-file-blank me-1"></i> Export PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Report Data -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Trip Number</th>
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Route</th>
                                <th>Customer</th>
                                <th>Planned Revenue</th>
                                <th>Actual Revenue</th>
                                <th>Distance (km)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trips as $trip)
                                <tr>
                                    <td>{{ $trip->trip_number }}</td>
                                    <td>{{ $trip->planned_start_date?->format('Y-m-d') }}</td>
                                    <td>{{ $trip->vehicle->name ?? 'N/A' }}</td>
                                    <td>{{ $trip->driver->full_name ?? 'N/A' }}</td>
                                    <td>{{ $trip->route->route_name ?? 'N/A' }}</td>
                                    <td>{{ $trip->customer->name ?? 'N/A' }}</td>
                                    <td class="text-end">{{ number_format($trip->planned_revenue ?? 0, 2) }}</td>
                                    <td class="text-end"><strong>{{ number_format($trip->actual_revenue_calculated ?? $trip->actual_revenue ?? 0, 2) }}</strong></td>
                                    <td class="text-end">{{ number_format($trip->actual_distance_km ?? $trip->planned_distance_km ?? 0, 2) }}</td>
                                    <td><span class="badge bg-{{ $trip->status == 'completed' ? 'success' : ($trip->status == 'in_progress' ? 'warning' : 'secondary') }}">{{ ucfirst($trip->status ?? 'N/A') }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">No trips found for the selected criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="6" class="text-end">TOTAL:</th>
                                <th class="text-end">{{ number_format($trips->sum('planned_revenue') ?? 0, 2) }}</th>
                                <th class="text-end">{{ number_format($totalRevenue, 2) }}</th>
                                <th class="text-end">{{ number_format($trips->sum(fn($t) => $t->actual_distance_km ?? $t->planned_distance_km ?? 0), 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
