@extends('layouts.main')

@section('title', 'Payment Collection Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Payment Collection Report', 'url' => '#', 'icon' => 'bx bx-credit-card']
        ]" />

        <h6 class="mb-0 text-uppercase">PAYMENT COLLECTION REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.payment-collection') }}" class="row g-3">
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
                    <form method="POST" action="{{ route('fleet.reports.payment-collection.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.payment-collection.export-pdf') }}" style="display: inline;">
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
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Collection</h6>
                        <h4 class="text-success">{{ number_format($totalCollection, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Payments</h6>
                        <h4 class="text-info">{{ $payments->count() }}</h4>
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
                                <th>Payment Date</th>
                                <th>Invoice Number</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                @php
                                    $invoice = $payment->fleetInvoice;
                                    // Get from invoice first, then fallback to first item's trip
                                    $displayVehicle = $invoice->vehicle ?? null;
                                    $displayDriver = $invoice->driver ?? null;
                                    $displayCustomer = $invoice->customer ?? null;
                                    
                                    if (!$displayVehicle || !$displayDriver || !$displayCustomer) {
                                        $firstItem = $invoice->items->first();
                                        if ($firstItem && $firstItem->trip) {
                                            $displayVehicle = $displayVehicle ?? $firstItem->trip->vehicle;
                                            $displayDriver = $displayDriver ?? $firstItem->trip->driver;
                                            // Customer might not be on trip, so keep invoice customer
                                            $displayCustomer = $displayCustomer ?? $firstItem->trip->customer;
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $payment->payment_date?->format('Y-m-d') }}</td>
                                    <td>{{ $invoice->invoice_number ?? 'N/A' }}</td>
                                    <td>{{ $displayVehicle->name ?? 'N/A' }}</td>
                                    <td>{{ $displayDriver->full_name ?? 'N/A' }}</td>
                                    <td>{{ $displayCustomer->name ?? 'N/A' }}</td>
                                    <td class="text-end"><strong>{{ number_format($payment->amount ?? 0, 2) }}</strong></td>
                                    <td>Bank Transfer</td>
                                    <td>{{ $payment->reference_number ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">No payments found for the selected criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-end">TOTAL:</th>
                                <th class="text-end">{{ number_format($totalCollection, 2) }}</th>
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
