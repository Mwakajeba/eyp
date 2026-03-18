@extends('layouts.main')

@section('title', 'Dispatch Efficiency Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Dispatch Efficiency Report', 'url' => '#', 'icon' => 'bx bx-time-five']
        ]" />

        <h6 class="mb-0 text-uppercase">DISPATCH EFFICIENCY REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.dispatch-efficiency') }}" class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from', \Carbon\Carbon::today()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to', \Carbon\Carbon::today()->format('Y-m-d')) }}">
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
                    <form method="POST" action="{{ route('fleet.reports.dispatch-efficiency.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.dispatch-efficiency.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
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
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h6 class="text-muted">On-Time Trips</h6>
                        <h4 class="text-success">{{ $onTimeCount }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Delayed Trips</h6>
                        <h4 class="text-danger">{{ $delayedCount }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h6 class="text-muted">On-Time Rate</h6>
                        <h4 class="text-info">{{ number_format($onTimeRate, 2) }}%</h4>
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
                                <th>Trip Number</th>
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Route</th>
                                <th>Planned Start</th>
                                <th>Actual Start</th>
                                <th>Delay (mins)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($efficiencyData as $data)
                                <tr>
                                    <td>{{ $data['trip']->trip_number }}</td>
                                    <td>{{ $data['trip']->planned_start_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                    <td>{{ $data['trip']->vehicle->name ?? 'N/A' }}</td>
                                    <td>{{ $data['trip']->driver->full_name ?? 'N/A' }}</td>
                                    <td>{{ $data['trip']->route->route_name ?? ($data['trip']->origin_location && $data['trip']->destination_location ? $data['trip']->origin_location . ' - ' . $data['trip']->destination_location : 'N/A') }}</td>
                                    <td>{{ $data['trip']->planned_start_date?->format('Y-m-d H:i') ?? 'N/A' }}</td>
                                    <td>{{ $data['trip']->actual_start_date?->format('Y-m-d H:i') ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $data['delay_minutes'] }}</td>
                                    <td>
                                        <span class="badge bg-{{ $data['on_time'] ? 'success' : 'danger' }}">
                                            {{ $data['on_time'] ? 'On Time' : 'Delayed' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">No dispatch data found for the selected criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
