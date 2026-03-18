@extends('layouts.main')

@section('title', 'Missing Trip Invoice Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Missing Trip Invoice Report', 'url' => '#', 'icon' => 'bx bx-error']
        ]" />

        <h6 class="mb-0 text-uppercase">MISSING TRIP INVOICE REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.missing-trip-invoice') }}" class="row g-3">
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
                    <form method="POST" action="{{ route('fleet.reports.missing-trip-invoice.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.missing-trip-invoice.export-pdf') }}" style="display: inline;">
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
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Trips Without Invoices</h6>
                        <h4 class="text-warning">{{ $tripsWithoutInvoices->count() }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Potential Lost Revenue</h6>
                        <h4 class="text-danger">{{ number_format($totalRevenue, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Data -->
        <div class="card">
            <div class="card-body">
                @if($tripsWithoutInvoices->count() > 0)
                    <div class="alert alert-warning" role="alert">
                        <i class="bx bx-error-circle me-2"></i> <strong>Warning:</strong> {{ $tripsWithoutInvoices->count() }} completed trips do not have invoices generated.
                    </div>
                @endif
                
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Trip Number</th>
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Customer</th>
                                <th>Revenue</th>
                                <th>Distance (km)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tripsWithoutInvoices as $trip)
                                <tr>
                                    <td>{{ $trip->trip_number }}</td>
                                    <td>{{ $trip->planned_start_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                    <td>{{ $trip->vehicle->name ?? 'N/A' }}</td>
                                    <td>{{ $trip->driver->full_name ?? 'N/A' }}</td>
                                    <td>{{ $trip->customer->name ?? 'N/A' }}</td>
                                    <td class="text-end"><strong>{{ number_format($trip->actual_revenue ?? $trip->planned_revenue ?? 0, 2) }}</strong></td>
                                    <td class="text-end">{{ number_format($trip->actual_distance_km ?? $trip->planned_distance_km ?? 0, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-success">
                                        <i class="bx bx-check-circle me-2"></i> All completed trips have invoices generated.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($tripsWithoutInvoices->count() > 0)
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">TOTAL:</th>
                                    <th class="text-end">{{ number_format($totalRevenue, 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
