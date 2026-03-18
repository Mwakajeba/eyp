@extends('layouts.main')

@section('title', 'Operating Cost Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Operating Cost Report', 'url' => '#', 'icon' => 'bx bx-dollar-circle']
        ]" />

        <h6 class="mb-0 text-uppercase">OPERATING COST REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.operating-cost') }}" class="row g-3">
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
                    <form method="POST" action="{{ route('fleet.reports.operating-cost.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.operating-cost.export-pdf') }}" style="display: inline;">
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
            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Operating Cost</h6>
                        <h4 class="text-danger">{{ number_format($totalCost, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Vehicles with Costs</h6>
                        <h4 class="text-info">{{ $operatingData->count() }}</h4>
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
                                <th>Vehicle</th>
                                <th>Registration</th>
                                <th>Maintenance Cost</th>
                                <th>Fuel Cost</th>
                                <th>Trip Cost</th>
                                <th>Total Cost</th>
                                <th>Distance (km)</th>
                                <th>Cost per km</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($operatingData as $data)
                                <tr>
                                    <td>{{ $data['vehicle']->name }}</td>
                                    <td>{{ $data['vehicle']->registration_number ?? 'N/A' }}</td>
                                    <td class="text-end">{{ number_format($data['maintenance_cost'], 2) }}</td>
                                    <td class="text-end">{{ number_format($data['fuel_cost'], 2) }}</td>
                                    <td class="text-end">{{ number_format($data['trip_cost'], 2) }}</td>
                                    <td class="text-end"><strong>{{ number_format($data['total_cost'], 2) }}</strong></td>
                                    <td class="text-end">{{ number_format($data['distance'], 2) }}</td>
                                    <td class="text-end">{{ number_format($data['cost_per_km'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">No operating cost data found for the selected criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-end">TOTAL:</th>
                                <th class="text-end">{{ number_format($operatingData->sum('maintenance_cost'), 2) }}</th>
                                <th class="text-end">{{ number_format($operatingData->sum('fuel_cost'), 2) }}</th>
                                <th class="text-end">{{ number_format($operatingData->sum('trip_cost'), 2) }}</th>
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
