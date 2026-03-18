@extends('layouts.main')

@section('title', 'Driver Outstanding Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Driver Outstanding Report', 'url' => '#', 'icon' => 'bx bx-time']
        ]" />

        <h6 class="mb-0 text-uppercase">DRIVER OUTSTANDING REPORT</h6>
        <hr />

        <!-- Export Buttons -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end gap-2">
                    <form method="POST" action="{{ route('fleet.reports.driver-outstanding.export-excel') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.driver-outstanding.export-pdf') }}" style="display: inline;">
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
            <div class="col-md-12">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Outstanding</h6>
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
                                <th>Driver Code</th>
                                <th>Driver Name</th>
                                <th>Outstanding Invoices</th>
                                <th>Total Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($outstandingData as $data)
                                <tr>
                                    <td>{{ $data['driver']->driver_code ?? 'N/A' }}</td>
                                    <td>{{ $data['driver']->full_name }}</td>
                                    <td class="text-center">{{ $data['invoice_count'] }}</td>
                                    <td class="text-end">
                                        <a href="#" 
                                           class="text-warning text-decoration-none" 
                                           data-bs-toggle="modal" 
                                           data-bs-target="#invoiceDetailsModal{{ $data['driver']->id }}">
                                            <strong>{{ number_format($data['total_outstanding'], 2) }}</strong>
                                            <i class="bx bx-link-external ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No outstanding data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-end">TOTAL:</th>
                                <th class="text-center">{{ $outstandingData->sum('invoice_count') }}</th>
                                <th class="text-end">{{ number_format($totalOutstanding, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($outstandingData as $data)
<!-- Invoice Details Modal -->
<div class="modal fade" id="invoiceDetailsModal{{ $data['driver']->id }}" tabindex="-1" aria-labelledby="invoiceDetailsModalLabel{{ $data['driver']->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="invoiceDetailsModalLabel{{ $data['driver']->id }}">
                    Outstanding Invoices - {{ $data['driver']->full_name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Invoice Number</th>
                                <th>Invoice Date</th>
                                <th>Due Date</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Balance Due</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['invoices'] as $invoice)
                                <tr>
                                    <td>
                                        <a href="{{ route('fleet.invoices.show', $invoice->hash_id) }}" target="_blank">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td>{{ $invoice->invoice_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                    <td>{{ $invoice->due_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                    <td class="text-end">{{ number_format($invoice->total_amount ?? 0, 2) }}</td>
                                    <td class="text-end">{{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                                    <td class="text-end"><strong class="text-warning">{{ number_format($invoice->balance_due ?? 0, 2) }}</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $invoice->status == 'paid' ? 'success' : ($invoice->status == 'partial' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($invoice->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No outstanding invoices found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">TOTAL:</th>
                                <th class="text-end">{{ number_format($data['invoices']->sum('total_amount'), 2) }}</th>
                                <th class="text-end">{{ number_format($data['invoices']->sum('paid_amount'), 2) }}</th>
                                <th class="text-end"><strong class="text-warning">{{ number_format($data['total_outstanding'], 2) }}</strong></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection
