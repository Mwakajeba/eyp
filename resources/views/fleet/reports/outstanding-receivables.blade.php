@extends('layouts.main')

@section('title', 'Outstanding Receivables Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Outstanding Receivables Report', 'url' => '#', 'icon' => 'bx bx-hourglass']
        ]" />

        <h6 class="mb-0 text-uppercase">OUTSTANDING RECEIVABLES REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.outstanding-receivables') }}" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select">
                            <option value="">All Customers</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
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
                    <form method="POST" action="{{ route('fleet.reports.outstanding-receivables.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="customer_id" value="{{ request('customer_id') }}">
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.outstanding-receivables.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="customer_id" value="{{ request('customer_id') }}">
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bx-file-blank me-1"></i> Export PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="row">
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Outstanding</h6>
                        <h4 class="text-warning">{{ number_format($totalOutstanding, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Overdue Invoices</h6>
                        <h4 class="text-danger">{{ $receivables->filter(fn($r) => $r['days_overdue'] > 0)->count() }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Invoices</h6>
                        <h4 class="text-info">{{ $receivables->count() }}</h4>
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
                                <th>Invoice Date</th>
                                <th>Due Date</th>
                                <th>Days Overdue</th>
                                <th>Aging Category</th>
                                @php
                                    $hasCustomer = $receivables->contains(fn($r) => $r['invoice']->customer);
                                @endphp
                                @if($hasCustomer)
                                <th>Customer</th>
                                @endif
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Balance Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($receivables as $item)
                                @php
                                    $invoice = $item['invoice'];
                                    $badgeColor = match($item['aging_category']) {
                                        '90+' => 'danger',
                                        '60-90' => 'warning',
                                        '30-60' => 'info',
                                        '1-30' => 'primary',
                                        default => 'success'
                                    };
                                    
                                    // Get from invoice first, then fallback to first item's trip
                                    $displayVehicle = $invoice->vehicle;
                                    $displayDriver = $invoice->driver;
                                    
                                    if (!$displayVehicle || !$displayDriver) {
                                        $firstItem = $invoice->items->first();
                                        if ($firstItem && $firstItem->trip) {
                                            $displayVehicle = $displayVehicle ?? $firstItem->trip->vehicle;
                                            $displayDriver = $displayDriver ?? $firstItem->trip->driver;
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $invoice->invoice_number }}</td>
                                    <td>{{ $invoice->invoice_date?->format('Y-m-d') }}</td>
                                    <td>{{ $invoice->due_date?->format('Y-m-d') }}</td>
                                    <td class="text-center">
                                        @if($item['days_overdue'] > 0)
                                            <span class="badge bg-danger">{{ (int)$item['days_overdue'] }}</span>
                                        @else
                                            <span class="badge bg-success">{{ (int)abs($item['days_overdue']) }} days left</span>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-{{ $badgeColor }}">{{ $item['aging_category'] }}</span></td>
                                    @if($hasCustomer)
                                    <td>{{ $invoice->customer->name ?? 'N/A' }}</td>
                                    @endif
                                    <td>{{ $displayVehicle->name ?? 'N/A' }}@if($displayVehicle && $displayVehicle->registration_number) ({{ $displayVehicle->registration_number }})@endif</td>
                                    <td>{{ $displayDriver->full_name ?? $displayDriver->name ?? 'N/A' }}</td>
                                    <td class="text-end">{{ number_format($invoice->total_amount ?? 0, 2) }}</td>
                                    <td class="text-end">{{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                                    <td class="text-end"><strong>{{ number_format($invoice->balance_due ?? 0, 2) }}</strong></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $hasCustomer ? '11' : '10' }}" class="text-center">No outstanding receivables found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="{{ $hasCustomer ? '8' : '7' }}" class="text-end">TOTAL:</th>
                                <th class="text-end">{{ number_format($receivables->sum(fn($r) => $r['invoice']->total_amount), 2) }}</th>
                                <th class="text-end">{{ number_format($receivables->sum(fn($r) => $r['invoice']->paid_amount), 2) }}</th>
                                <th class="text-end">{{ number_format($totalOutstanding, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
