@extends('layouts.main')

@section('title', 'Vehicle Replacement Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Vehicle Replacement Report', 'url' => '#', 'icon' => 'bx bx-refresh']
        ]" />

        <h6 class="mb-0 text-uppercase">VEHICLE REPLACEMENT REPORT</h6>
        <hr />

        <!-- Export Buttons -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end gap-2">
                    <form method="POST" action="{{ route('fleet.reports.vehicle-replacement.export-excel') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.vehicle-replacement.export-pdf') }}" style="display: inline;">
                        @csrf
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
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Consider Replacement</h6>
                        <h4 class="text-danger">{{ $replacementData->filter(fn($d) => $d['recommendation'] == 'Consider Replacement')->count() }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Monitor Closely</h6>
                        <h4 class="text-warning">{{ $replacementData->filter(fn($d) => $d['recommendation'] == 'Monitor Closely')->count() }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Good Condition</h6>
                        <h4 class="text-success">{{ $replacementData->filter(fn($d) => $d['recommendation'] == 'Good Condition')->count() }}</h4>
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
                                <th>Age (Years)</th>
                                <th>Mileage</th>
                                <th>Maintenance Cost (12m)</th>
                                <th>Recommendation</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($replacementData as $data)
                                @php
                                    $badgeColor = match($data['recommendation']) {
                                        'Consider Replacement' => 'danger',
                                        'Monitor Closely' => 'warning',
                                        default => 'success'
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $data['vehicle']->name }}</td>
                                    <td>{{ $data['vehicle']->registration_number ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $data['age_years'] }}</td>
                                    <td class="text-end">{{ number_format($data['mileage'], 0) }}</td>
                                    <td class="text-end">{{ number_format($data['maintenance_cost_12m'], 2) }}</td>
                                    <td><span class="badge bg-{{ $badgeColor }}">{{ $data['recommendation'] }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No vehicles found.</td>
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
