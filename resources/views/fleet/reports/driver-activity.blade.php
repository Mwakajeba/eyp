@extends('layouts.main')

@section('title', 'Driver Activity Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Driver Activity Report', 'url' => '#', 'icon' => 'bx bx-user-check']
        ]" />

        <h6 class="mb-0 text-uppercase">DRIVER ACTIVITY REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.driver-activity') }}" class="row g-3">
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
                    <form method="POST" action="{{ route('fleet.reports.driver-activity.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.driver-activity.export-pdf') }}" style="display: inline;">
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
                                <th>Driver Code</th>
                                <th>Driver Name</th>
                                <th>Total Trips</th>
                                <th>Completed Trips</th>
                                <th>In Progress</th>
                                <th>Distance (km)</th>
                                <th>Completion Rate (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activityData as $data)
                                <tr>
                                    <td>{{ $data['driver']->driver_code ?? 'N/A' }}</td>
                                    <td>{{ $data['driver']->full_name }}</td>
                                    <td class="text-center">{{ $data['total_trips'] }}</td>
                                    <td class="text-center">{{ $data['completed_trips'] }}</td>
                                    <td class="text-center">{{ $data['in_progress_trips'] }}</td>
                                    <td class="text-end">{{ number_format($data['distance'], 2) }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-{{ $data['completion_rate'] >= 80 ? 'success' : ($data['completion_rate'] >= 50 ? 'warning' : 'danger') }}">
                                            {{ number_format($data['completion_rate'], 2) }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No driver activity data found for the selected criteria.</td>
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
