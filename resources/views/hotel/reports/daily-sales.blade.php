@extends('layouts.main')

@section('title', 'Daily Sales / Revenue Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel Reports', 'url' => route('hotel.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Daily Sales / Revenue Report', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />

        <h6 class="mb-0 text-uppercase">DAILY SALES / REVENUE REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('hotel.reports.daily-sales') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ $date }}">
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
                    <form method="POST" action="{{ route('hotel.reports.daily-sales.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date" value="{{ request('date', $date) }}">
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bx-file-blank me-1"></i> Export PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-12">
                        <h4 class="text-success">Total Amount: TZS {{ number_format($totalAmount, 2) }}</h4>
                        <p class="text-muted mb-0">Date: {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</p>
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
                                <th>Date</th>
                                <th>Invoice No</th>
                                <th>Guest Name</th>
                                <th>Room No</th>
                                <th>Payment Method</th>
                                <th>Amount Paid</th>
                                <th>Received By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($salesData as $sale)
                                <tr>
                                    <td>{{ $sale['receipt']->date->format('M d, Y') }}</td>
                                    <td>{{ $sale['receipt']->reference_number ?? 'N/A' }}</td>
                                    <td>{{ $sale['guest_name'] }}</td>
                                    <td>{{ $sale['room_no'] }}</td>
                                    <td>{{ $sale['payment_method'] }}</td>
                                    <td class="text-end"><strong>TZS {{ number_format($sale['amount'], 2) }}</strong></td>
                                    <td>{{ $sale['received_by'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No sales data found for the selected date.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-end">TOTAL:</th>
                                <th class="text-end">TZS {{ number_format($totalAmount, 2) }}</th>
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
