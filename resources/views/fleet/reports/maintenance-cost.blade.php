@extends('layouts.main')

@section('title', 'Maintenance & Repair Cost Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Maintenance & Repair Cost Report', 'url' => '#', 'icon' => 'bx bx-wrench']
        ]" />

        <h6 class="mb-0 text-uppercase">MAINTENANCE & REPAIR COST REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.maintenance-cost') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from', \Carbon\Carbon::today()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to', \Carbon\Carbon::today()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4">
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
                    <form method="POST" action="{{ route('fleet.reports.maintenance-cost.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.maintenance-cost.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bx-file-blank me-1"></i> Export PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="row">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Maintenance Cost</h6>
                        <h4 class="text-primary">{{ number_format($totalCost, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Vehicles with Maintenance</h6>
                        <h4 class="text-success">{{ $maintenanceData->count() }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Work Orders</h6>
                        <h4 class="text-info">{{ $maintenanceData->sum('count') }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Average Cost per Vehicle</h6>
                        <h4 class="text-warning">{{ $maintenanceData->count() > 0 ? number_format($totalCost / $maintenanceData->count(), 2) : '0.00' }}</h4>
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
                                <th>Work Orders</th>
                                <th>Labor Cost</th>
                                <th>Material Cost</th>
                                <th>Other Cost</th>
                                <th>Total Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($maintenanceData as $data)
                                <tr>
                                    <td>{{ $data['vehicle']->name }}</td>
                                    <td>{{ $data['vehicle']->registration_number ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $data['count'] }}</td>
                                    <td class="text-end">{{ number_format($data['labor_cost'], 2) }}</td>
                                    <td class="text-end">{{ number_format($data['material_cost'], 2) }}</td>
                                    <td class="text-end">{{ number_format($data['other_cost'], 2) }}</td>
                                    <td class="text-end"><strong>{{ number_format($data['total_cost'], 2) }}</strong></td>
                                </tr>
                                @if($data['work_orders']->count() > 0)
                                    <tr class="table-light">
                                        <td colspan="7">
                                            <details>
                                                <summary class="text-primary">View Work Orders ({{ $data['work_orders']->count() }})</summary>
                                                <table class="table table-sm mt-2">
                                                    <thead>
                                                        <tr>
                                                            <th>Work Order</th>
                                                            <th>Type</th>
                                                            <th>Date</th>
                                                            <th>Labor</th>
                                                            <th>Material</th>
                                                            <th>Other</th>
                                                            <th>Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($data['work_orders'] as $wo)
                                                            <tr>
                                                                <td>{{ $wo['work_order_number'] }}</td>
                                                                <td>{{ ucfirst(str_replace('_', ' ', $wo['maintenance_type'] ?? 'N/A')) }}</td>
                                                                <td>{{ $wo['completion_date']?->format('Y-m-d') }}</td>
                                                                <td class="text-end">{{ number_format($wo['labor_cost'], 2) }}</td>
                                                                <td class="text-end">{{ number_format($wo['material_cost'], 2) }}</td>
                                                                <td class="text-end">{{ number_format($wo['other_cost'], 2) }}</td>
                                                                <td class="text-end"><strong>{{ number_format($wo['total_cost'], 2) }}</strong></td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </details>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No maintenance costs found for the selected criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">TOTAL:</th>
                                <th class="text-end">{{ number_format($maintenanceData->sum('labor_cost'), 2) }}</th>
                                <th class="text-end">{{ number_format($maintenanceData->sum('material_cost'), 2) }}</th>
                                <th class="text-end">{{ number_format($maintenanceData->sum('other_cost'), 2) }}</th>
                                <th class="text-end">{{ number_format($totalCost, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
