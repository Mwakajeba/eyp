@extends('layouts.main')

@section('title', 'Insurance Expiry Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Insurance Expiry Report', 'url' => '#', 'icon' => 'bx bx-shield']
        ]" />

        <h6 class="mb-0 text-uppercase">INSURANCE EXPIRY REPORT</h6>
        <hr />

        <!-- Export Buttons -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end gap-2">
                    <form method="POST" action="{{ route('fleet.reports.insurance-expiry.export-excel') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.insurance-expiry.export-pdf') }}" style="display: inline;">
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
                        <h6 class="text-muted">Expired</h6>
                        <h4 class="text-danger">{{ $expired->count() }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Expiring Soon (30 days)</h6>
                        <h4 class="text-warning">{{ $expiringSoon->count() }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Active</h6>
                        <h4 class="text-success">{{ $expiryData->filter(fn($d) => $d['status'] == 'Active')->count() }}</h4>
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
                                <th>Insurance Expiry Date</th>
                                <th>Days to Expiry</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expiryData as $data)
                                @php
                                    $badgeColor = match($data['status']) {
                                        'Expired' => 'danger',
                                        'Expiring Soon' => 'warning',
                                        default => 'success'
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $data['vehicle']->name }}</td>
                                    <td>{{ $data['vehicle']->registration_number ?? 'N/A' }}</td>
                                    <td>{{ $data['insurance_expiry'] ? \Carbon\Carbon::parse($data['insurance_expiry'])->format('Y-m-d') : 'N/A' }}</td>
                                    <td class="text-center">
                                        @if($data['days_to_expiry'] !== null)
                                            <span class="badge bg-{{ $badgeColor }}">{{ abs($data['days_to_expiry']) }} days {{ $data['days_to_expiry'] < 0 ? 'overdue' : 'remaining' }}</span>
                                        @else
                                            <span class="badge bg-secondary">N/A</span>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-{{ $badgeColor }}">{{ $data['status'] }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No vehicles found.</td>
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
