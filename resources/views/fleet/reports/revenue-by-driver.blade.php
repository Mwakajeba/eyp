@extends('layouts.main')

@section('title', 'Revenue by Driver Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Revenue by Driver Report', 'url' => '#', 'icon' => 'bx bx-user']
        ]" />

        <h6 class="mb-0 text-uppercase">REVENUE BY DRIVER REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.revenue-by-driver') }}" class="row g-3">
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
                    <form method="POST" action="{{ route('fleet.reports.revenue-by-driver.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.revenue-by-driver.export-pdf') }}" style="display: inline;">
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
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Revenue</h6>
                        <h4 class="text-primary">{{ number_format($totalRevenue, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Active Drivers</h6>
                        <h4 class="text-success">{{ $revenueData->count() }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Average Revenue per Driver</h6>
                        <h4 class="text-info">{{ $revenueData->count() > 0 ? number_format($totalRevenue / $revenueData->count(), 2) : '0.00' }}</h4>
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
                                <th>Driver Code</th>
                                <th>Driver Name</th>
                                <th>License Number</th>
                                <th>Trips</th>
                                <th>Distance (km)</th>
                                <th>Revenue</th>
                                <th>Avg Revenue/Trip</th>
                                <th>Revenue/km</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($revenueData as $data)
                                <tr>
                                    <td>{{ $data['driver']->driver_code ?? 'N/A' }}</td>
                                    <td>{{ $data['driver']->full_name }}</td>
                                    <td>{{ $data['driver']->license_number ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $data['trip_count'] }}</td>
                                    <td class="text-end">{{ number_format($data['distance'], 2) }}</td>
                                    <td class="text-end"><strong>{{ number_format($data['revenue'], 2) }}</strong></td>
                                    <td class="text-end">{{ number_format($data['avg_revenue_per_trip'], 2) }}</td>
                                    <td class="text-end">{{ number_format($data['revenue_per_km'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">No revenue data found for the selected criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">TOTAL:</th>
                                <th class="text-center">{{ $revenueData->sum('trip_count') }}</th>
                                <th class="text-end">{{ number_format($revenueData->sum('distance'), 2) }}</th>
                                <th class="text-end">{{ number_format($totalRevenue, 2) }}</th>
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
