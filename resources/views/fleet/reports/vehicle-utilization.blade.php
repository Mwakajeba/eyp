@extends('layouts.main')

@section('title', 'Vehicle Utilization Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Vehicle Utilization Report', 'url' => '#', 'icon' => 'bx bx-tachometer']
        ]" />

        <h6 class="mb-0 text-uppercase">VEHICLE UTILIZATION REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.vehicle-utilization') }}" class="row g-3">
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
                    <form method="POST" action="{{ route('fleet.reports.vehicle-utilization.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.vehicle-utilization.export-pdf') }}" style="display: inline;">
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

        <!-- Report Data -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Registration</th>
                                <th>Total Trips</th>
                                <th>Active Days</th>
                                <th>Total Days</th>
                                <th>Utilization Rate (%)</th>
                                <th>Distance (km)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($utilizationData as $data)
                                @php
                                    $badgeColor = $data['utilization_rate'] >= 75 ? 'success' : ($data['utilization_rate'] >= 50 ? 'warning' : 'danger');
                                @endphp
                                <tr>
                                    <td>{{ $data['vehicle']->name }}</td>
                                    <td>{{ $data['vehicle']->registration_number ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $data['total_trips'] }}</td>
                                    <td class="text-center">{{ $data['active_days'] }}</td>
                                    <td class="text-center">{{ $data['total_days'] }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $badgeColor }}">{{ number_format($data['utilization_rate'], 2) }}%</span>
                                    </td>
                                    <td class="text-end">{{ number_format($data['distance'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No utilization data found for the selected criteria.</td>
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
