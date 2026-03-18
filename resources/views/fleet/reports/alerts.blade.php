@extends('layouts.main')

@section('title', 'Alerts Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Alerts Report', 'url' => '#', 'icon' => 'bx bx-bell']
        ]" />

        <h6 class="mb-0 text-uppercase">FLEET ALERTS REPORT</h6>
        <hr />

        <!-- Export Buttons -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end gap-2">
                    <form method="POST" action="{{ route('fleet.reports.alerts.export-excel') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.alerts.export-pdf') }}" style="display: inline;">
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
            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Critical Alerts</h6>
                        <h4 class="text-danger">{{ collect($alerts)->where('severity', 'critical')->count() }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Warning Alerts</h6>
                        <h4 class="text-warning">{{ collect($alerts)->where('severity', 'warning')->count() }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Data -->
        <div class="card">
            <div class="card-body">
                @if(empty($alerts))
                    <div class="alert alert-success text-center" role="alert">
                        <i class="bx bx-check-circle fs-1"></i>
                        <h5 class="mt-2">No Alerts</h5>
                        <p class="mb-0">All fleet operations are running smoothly.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Severity</th>
                                    <th>Vehicle</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($alerts as $alert)
                                    @php
                                        $badgeColor = $alert['severity'] == 'critical' ? 'danger' : 'warning';
                                        $iconClass = $alert['severity'] == 'critical' ? 'bx-error' : 'bx-info-circle';
                                    @endphp
                                    <tr>
                                        <td>
                                            <i class="bx {{ $iconClass }} me-1 text-{{ $badgeColor }}"></i>
                                            {{ $alert['type'] }}
                                        </td>
                                        <td><span class="badge bg-{{ $badgeColor }}">{{ ucfirst($alert['severity']) }}</span></td>
                                        <td>{{ $alert['vehicle'] }}</td>
                                        <td>
                                            @if(isset($alert['details']) || isset($alert['insurance_expiry_date']))
                                                <a href="#" 
                                                   class="text-decoration-none" 
                                                   data-bs-toggle="modal" 
                                                   data-bs-target="#alertDetailsModal{{ $loop->index }}">
                                                    {{ $alert['message'] }}
                                                    <i class="bx bx-link-external ms-1"></i>
                                                </a>
                                            @else
                                                {{ $alert['message'] }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@foreach($alerts as $index => $alert)
@if(isset($alert['details']) || isset($alert['insurance_expiry_date']))
<!-- Alert Details Modal -->
<div class="modal fade" id="alertDetailsModal{{ $index }}" tabindex="-1" aria-labelledby="alertDetailsModalLabel{{ $index }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="alertDetailsModalLabel{{ $index }}">
                    {{ $alert['type'] }} - Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if(isset($alert['insurance_expiry_date']))
                    <div class="alert alert-{{ $alert['severity'] == 'critical' ? 'danger' : 'warning' }}" role="alert">
                        <h6><strong>Vehicle:</strong> {{ $alert['vehicle'] }}</h6>
                        <p class="mb-1"><strong>Insurance Expiry Date:</strong> {{ \Carbon\Carbon::parse($alert['insurance_expiry_date'])->format('F d, Y') }}</p>
                        <p class="mb-0"><strong>Days {{ $alert['days_to_expiry'] < 0 ? 'Overdue' : 'Remaining' }}:</strong> {{ abs($alert['days_to_expiry']) }} days</p>
                    </div>
                @elseif(isset($alert['details']) && isset($alert['detail_type']))
                    @if($alert['detail_type'] == 'work_orders')
                        <h6 class="mb-3">Overdue Maintenance Work Orders</h6>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>WO Number</th>
                                        <th>Scheduled Date</th>
                                        <th>Status</th>
                                        <th>Estimated Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($alert['details'] as $wo)
                                        <tr>
                                            <td>{{ $wo->wo_number }}</td>
                                            <td>{{ $wo->estimated_start_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                            <td><span class="badge bg-warning">{{ ucfirst($wo->status) }}</span></td>
                                            <td class="text-end">{{ number_format($wo->estimated_cost ?? 0, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">No work orders found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @elseif($alert['detail_type'] == 'trips')
                        <h6 class="mb-3">Trips Without Invoices</h6>
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
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($alert['details'] as $trip)
                                        <tr>
                                            <td>{{ $trip->trip_number }}</td>
                                            <td>{{ $trip->planned_start_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                            <td>{{ $trip->vehicle->name ?? 'N/A' }}</td>
                                            <td>{{ $trip->driver->full_name ?? 'N/A' }}</td>
                                            <td>{{ $trip->customer->name ?? 'N/A' }}</td>
                                            <td class="text-end">{{ number_format($trip->actual_revenue ?? $trip->planned_revenue ?? 0, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No trips found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @elseif($alert['detail_type'] == 'invoices')
                        <h6 class="mb-3">Overdue Invoices</h6>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Invoice Number</th>
                                        <th>Invoice Date</th>
                                        <th>Due Date</th>
                                        <th>Customer</th>
                                        <th>Total Amount</th>
                                        <th>Balance Due</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($alert['details'] as $invoice)
                                        <tr>
                                            <td>
                                                <a href="{{ route('fleet.invoices.show', $invoice->hash_id) }}" target="_blank">
                                                    {{ $invoice->invoice_number }}
                                                </a>
                                            </td>
                                            <td>{{ $invoice->invoice_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                            <td>{{ $invoice->due_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                            <td>{{ $invoice->customer->name ?? 'N/A' }}</td>
                                            <td class="text-end">{{ number_format($invoice->total_amount ?? 0, 2) }}</td>
                                            <td class="text-end"><strong class="text-danger">{{ number_format($invoice->balance_due ?? 0, 2) }}</strong></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No invoices found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif
@endforeach
@endsection
