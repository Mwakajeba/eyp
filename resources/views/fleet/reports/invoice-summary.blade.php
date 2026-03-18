@extends('layouts.main')

@section('title', 'Invoice Summary Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Invoice Summary Report', 'url' => '#', 'icon' => 'bx bx-receipt']
        ]" />

        <h6 class="mb-0 text-uppercase">INVOICE SUMMARY REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.invoice-summary') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from', \Carbon\Carbon::today()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to', \Carbon\Carbon::today()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="partially_paid" {{ request('status') == 'partially_paid' ? 'selected' : '' }}>Partially Paid</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                        </select>
                    </div>
                    <div class="col-md-3">
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
                    <form method="POST" action="{{ route('fleet.reports.invoice-summary.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.invoice-summary.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bx-file-blank me-1"></i> Export PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Amount</h6>
                        <h4 class="text-primary">{{ number_format($totalAmount, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Paid</h6>
                        <h4 class="text-success">{{ number_format($totalPaid, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Outstanding</h6>
                        <h4 class="text-warning">{{ number_format($totalOutstanding, 2) }}</h4>
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
                                <th>Invoice Number</th>
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Customer</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Balance Due</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                                @php
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
                                    <td>{{ $invoice->invoice_number }}</td>
                                    <td>{{ $invoice->invoice_date?->format('Y-m-d') }}</td>
                                    <td>{{ $displayVehicle->name ?? 'N/A' }}</td>
                                    <td>{{ $displayDriver->full_name ?? 'N/A' }}</td>
                                    <td>{{ $displayCustomer->name ?? 'N/A' }}</td>
                                    <td class="text-end">{{ number_format($invoice->total_amount ?? 0, 2) }}</td>
                                    <td class="text-end">{{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                                    <td class="text-end"><strong>{{ number_format($invoice->balance_due ?? 0, 2) }}</strong></td>
                                    <td>
                                        @php
                                            $statusBadge = match($invoice->status) {
                                                'paid' => 'success',
                                                'partially_paid' => 'info',
                                                'overdue' => 'danger',
                                                'sent' => 'warning',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusBadge }}">{{ ucfirst(str_replace('_', ' ', $invoice->status ?? 'N/A')) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">No invoices found for the selected criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-end">TOTAL:</th>
                                <th class="text-end">{{ number_format($totalAmount, 2) }}</th>
                                <th class="text-end">{{ number_format($totalPaid, 2) }}</th>
                                <th class="text-end">{{ number_format($totalOutstanding, 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
