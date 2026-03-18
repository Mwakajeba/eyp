@extends('layouts.main')

@section('title', 'Fuel Consumption Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Fuel Consumption Report', 'url' => '#', 'icon' => 'bx bxs-gas-pump']
        ]" />

        <h6 class="mb-0 text-uppercase">FUEL CONSUMPTION REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.fuel-consumption') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from', \Carbon\Carbon::today()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4">
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
                    <form method="POST" action="{{ route('fleet.reports.fuel-consumption.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <input type="hidden" name="vehicle_id" value="{{ request('vehicle_id') }}">
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.fuel-consumption.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <input type="hidden" name="vehicle_id" value="{{ request('vehicle_id') }}">
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bxs-file-pdf me-1"></i> Export PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="row">
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Liters</h6>
                        <h4 class="text-primary">{{ number_format($totalLiters, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Cost</h6>
                        <h4 class="text-danger">{{ number_format($totalCost, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Fill-ups</h6>
                        <h4 class="text-info">{{ $fuelLogs->count() }}</h4>
                    </div>
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
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Registration</th>
                                <th>Quantity (L)</th>
                                <th>Cost per Liter</th>
                                <th>Total Cost</th>
                                <th>Odometer</th>
                                <th>Fuel Station</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fuelLogs as $log)
                                <tr>
                                    <td>{{ $log->date_filled?->format('Y-m-d') ?? 'N/A' }}</td>
                                    <td>{{ $log->vehicle->name ?? 'N/A' }}</td>
                                    <td>{{ $log->vehicle->registration_number ?? 'N/A' }}</td>
                                    <td class="text-end">{{ number_format($log->liters_filled ?? 0, 2) }}</td>
                                    <td class="text-end">{{ number_format($log->cost_per_liter ?? 0, 2) }}</td>
                                    <td class="text-end"><strong>{{ number_format($log->total_cost ?? 0, 2) }}</strong></td>
                                    <td class="text-end">{{ number_format($log->odometer_reading ?? 0, 2) }}</td>
                                    <td>{{ $log->fuel_station ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">No fuel consumption data found for the selected criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">TOTAL:</th>
                                <th class="text-end">{{ number_format($totalLiters, 2) }}</th>
                                <th></th>
                                <th class="text-end">{{ number_format($totalCost, 2) }}</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
