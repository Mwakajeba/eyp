@extends('layouts.main')

@section('title', 'View Trip: ' . $trip->trip_number . ' - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Trip Planning', 'url' => route('fleet.trips.index'), 'icon' => 'bx bx-trip'],
            ['label' => 'View: ' . $trip->trip_number, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-trip me-2"></i>{{ $trip->trip_number }}</h6>
                        <div class="d-flex flex-wrap gap-1 align-items-center">
                            @if(!$approvalRequired)
                                <span class="badge bg-success me-1">Approved (Auto)</span>
                            @elseif($trip->approval_status === 'pending')
                                <span class="badge bg-warning text-dark me-1">Pending approval</span>
                                <form action="{{ route('fleet.trips.approve', $trip->hash_id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm"><i class="bx bx-check me-1"></i>Approve</button>
                                </form>
                                <form action="{{ route('fleet.trips.reject', $trip->hash_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Reject this trip request?');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bx bx-x me-1"></i>Reject</button>
                                </form>
                            @elseif($trip->approval_status === 'approved')
                                <span class="badge bg-success me-1">Approved</span>
                            @elseif($trip->approval_status === 'rejected')
                                <span class="badge bg-danger me-1">Rejected</span>
                            @endif
                            @if(in_array($trip->status, ['planned', 'dispatched']))
                                <a href="{{ route('fleet.trips.edit', $trip->hash_id) }}" class="btn btn-light btn-sm">
                                    <i class="bx bx-edit me-1"></i>Edit
                                </a>
                            @endif
                            @php
                                $canDispatch = $trip->status == 'planned' && (!$approvalRequired || ($trip->approval_status !== 'pending' && $trip->approval_status !== 'rejected'));
                            @endphp
                            @if($canDispatch)
                                <a href="{{ route('fleet.trips.dispatch', $trip->hash_id) }}" class="btn btn-success btn-sm">
                                    <i class="bx bx-send me-1"></i>Dispatch
                                </a>
                            @endif
                            @if(in_array($trip->status, ['dispatched', 'in_progress']))
                                <a href="{{ route('fleet.trips.complete', $trip->hash_id) }}" class="btn btn-primary btn-sm">
                                    <i class="bx bx-check me-1"></i>Complete
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Status</label>
                                <p class="mb-0">
                                    @php
                                        $statusColors = ['planned' => 'secondary', 'dispatched' => 'info', 'in_progress' => 'primary', 'completed' => 'success', 'cancelled' => 'danger'];
                                        $color = $statusColors[$trip->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">{{ ucfirst(str_replace('_', ' ', $trip->status)) }}</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Trip Type</label>
                                <p class="mb-0"><span class="badge bg-info">{{ ucfirst($trip->trip_type) }}</span></p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Vehicle</label>
                                <p class="mb-0">{{ $trip->vehicle ? $trip->vehicle->name . ' (' . ($trip->vehicle->registration_number ?? 'N/A') . ')' : 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Driver</label>
                                <p class="mb-0">{{ $trip->driver ? $trip->driver->full_name : 'Not Assigned' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Route</label>
                                <p class="mb-0">
                                    @if($trip->route)
                                        {{ $trip->route->route_name }} ({{ number_format($trip->route->distance_km, 2) }} km)
                                    @else
                                        {{ $trip->origin_location }} → {{ $trip->destination_location }}
                                    @endif
                                </p>
                            </div>
                            @if($trip->cargo_description)
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Cargo Description</label>
                                <p class="mb-0">{{ $trip->cargo_description }}</p>
                            </div>
                            @endif
                        </div>

                        <hr class="my-4">
                        <h6 class="text-warning mb-3"><i class="bx bx-calendar me-2"></i>Timeline</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Planned Start</label>
                                <p class="mb-0">{{ $trip->planned_start_date ? $trip->planned_start_date->format('Y-m-d H:i') : 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Planned End</label>
                                <p class="mb-0">{{ $trip->planned_end_date ? $trip->planned_end_date->format('Y-m-d H:i') : 'N/A' }}</p>
                            </div>
                            @if($trip->actual_start_date)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Actual Start</label>
                                <p class="mb-0">{{ $trip->actual_start_date->format('Y-m-d H:i') }}</p>
                            </div>
                            @endif
                            @if($trip->actual_end_date)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Actual End</label>
                                <p class="mb-0">{{ $trip->actual_end_date->format('Y-m-d H:i') }}</p>
                            </div>
                            @endif
                        </div>

                        <hr class="my-4">
                        <h6 class="text-warning mb-3"><i class="bx bx-money me-2"></i>Financial Summary</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Total Revenue (from invoices)</label>
                                <p class="mb-0">
                                    <span class="text-success fw-bold">{{ number_format($actualRevenue ?? 0, 2) }} TZS</span>
                                    @if(($actualRevenue ?? 0) > 0)
                                        @php
                                            $directInvoiceIds = \App\Models\Fleet\FleetInvoice::where('trip_id', $trip->id)->pluck('id');
                                            $itemInvoiceIds = \App\Models\Fleet\FleetInvoiceItem::where('trip_id', $trip->id)->distinct()->pluck('fleet_invoice_id');
                                            $allInvoiceIds = $directInvoiceIds->merge($itemInvoiceIds)->unique()->filter();
                                            $invoiceCount = $allInvoiceIds->count();
                                        @endphp
                                        <br><small class="text-muted">From {{ $invoiceCount }} invoice(s)</small>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Paid Amount</label>
                                <p class="mb-0">
                                    <span class="text-primary fw-bold">{{ number_format($paidAmount ?? 0, 2) }} TZS</span>
                                    @if($actualRevenue > 0)
                                        <br><small class="text-muted">{{ number_format(($paidAmount / $actualRevenue) * 100, 1) }}% collected</small>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Total Costs</label>
                                <p class="mb-0">{{ number_format($trip->total_costs, 2) }} TZS</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Variable Costs</label>
                                <p class="mb-0">{{ number_format($trip->variable_costs, 2) }} TZS</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Fixed Costs</label>
                                <p class="mb-0">{{ number_format($trip->fixed_costs_allocated, 2) }} TZS</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Profit/Loss</label>
                                <p class="mb-0">
                                    @php
                                        $revenue = $actualRevenue ?? 0;
                                        $profit = $revenue - $trip->total_costs;
                                    @endphp
                                    <span class="badge bg-{{ $profit >= 0 ? 'success' : 'danger' }} fs-6">
                                        {{ number_format($profit, 2) }} TZS
                                    </span>
                                    <br><small class="text-muted">Based on total revenue from invoices</small>
                                </p>
                            </div>
                        </div>

                        @if($trip->notes)
                        <hr class="my-4">
                        <h6 class="text-warning mb-3"><i class="bx bx-note me-2"></i>Notes</h6>
                        <p>{{ $trip->notes }}</p>
                        @endif
                    </div>
                </div>

                <!-- Costs Section -->
                <div class="card mt-3">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-money me-2"></i>Trip Costs</h6>
                        <a href="{{ route('fleet.trip-costs.create', ['trip_id' => $trip->hash_id]) }}" class="btn btn-light btn-sm">
                            <i class="bx bx-plus me-1"></i>Add Cost
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle" id="trip-costs-table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>S/N</th>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    const tripCostsTable = $('#trip-costs-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("fleet.trip-costs.data") }}',
            data: function(d) {
                d.trip_id = '{{ $trip->hash_id }}';
            }
        },
        columns: [
            {
                data: null,
                name: 'serial',
                orderable: false,
                searchable: false,
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'date_incurred', name: 'date_incurred' },
            { data: 'cost_type_display', name: 'cost_type', orderable: false },
            { data: 'description', name: 'description' },
            { data: 'amount_display', name: 'amount', orderable: false },
            { data: 'approval_status_display', name: 'approval_status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']],
        pageLength: 10,
        responsive: true,
        language: {
            emptyTable: 'No costs recorded yet'
        }
    });
});
</script>
@endpush
