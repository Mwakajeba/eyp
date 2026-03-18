@extends('layouts.main')

@section('title', 'Monthly Performance Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Monthly Performance Report', 'url' => '#', 'icon' => 'bx bx-calendar']
        ]" />

        <h6 class="mb-0 text-uppercase">MONTHLY PERFORMANCE REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.monthly-performance') }}" class="row g-3">
                    <div class="col-md-10">
                        <label class="form-label">Month</label>
                        <input type="month" name="month" class="form-control" value="{{ request('month', $month->format('Y-m')) }}">
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
                    <form method="POST" action="{{ route('fleet.reports.monthly-performance.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="month" value="{{ request('month', $month->format('Y-m')) }}">
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.monthly-performance.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="month" value="{{ request('month', $month->format('Y-m')) }}">
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bxs-file-pdf me-1"></i> Export PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Performance Summary -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ $month->format('F Y') }} Performance Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-success mb-3">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Total Revenue</h6>
                                <h3 class="text-success">{{ number_format($summary['revenue'], 2) }}</h3>
                            </div>
                        </div>
                        <div class="card border-primary mb-3">
                            <div class="card-body">
                                <h6 class="text-muted">Trips</h6>
                                <p class="mb-1">Total Trips: <strong>{{ $summary['total_trips'] }}</strong></p>
                                <p class="mb-1">Completed Trips: <strong class="text-success">{{ $summary['completed_trips'] }}</strong></p>
                                <p class="mb-0">Completion Rate: <strong>{{ number_format($summary['completion_rate'], 2) }}%</strong></p>
                            </div>
                        </div>
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Distance Covered</h6>
                                <h4 class="text-info">{{ number_format($summary['distance'], 2) }} km</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-danger mb-3">
                            <div class="card-body">
                                <h6 class="text-muted">Expenses</h6>
                                <p class="mb-1">Maintenance Cost: <strong class="text-danger">{{ number_format($summary['maintenance_cost'], 2) }}</strong></p>
                                <p class="mb-1">Fuel Cost: <strong class="text-danger">{{ number_format($summary['fuel_cost'], 2) }}</strong></p>
                                <p class="mb-0">Total Expenses: <strong class="text-danger">{{ number_format($summary['total_expenses'], 2) }}</strong></p>
                            </div>
                        </div>
                        <div class="card border-{{ $summary['net_profit'] >= 0 ? 'success' : 'danger' }}">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Net Profit / Loss</h6>
                                <h3 class="{{ $summary['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($summary['net_profit'], 2) }}
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
