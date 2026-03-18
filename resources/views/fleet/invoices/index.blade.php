@extends('layouts.main')

@section('title', 'Revenue & Billing - Fleet Management')

@push('styles')
<style>
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ededed;
        font-size: 27px;
    }
    .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0a58ca) !important; }
    .bg-gradient-success { background: linear-gradient(45deg, #198754, #146c43) !important; }
    .bg-gradient-info { background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important; }
    .bg-gradient-warning { background: linear-gradient(45deg, #ffc107, #ffb300) !important; }
    .card { transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
    .card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
    .radius-10 { border-radius: 10px; }
    .border-start { border-left-width: 3px !important; }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Revenue & Billing', 'url' => '#', 'icon' => 'bx bx-receipt']
        ]" />

        @if(isset($overdueCount) && $overdueCount > 0)
        <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
            <i class="bx bx-error-circle me-2 fs-4"></i>
            <div>
                <strong>Due invoices:</strong> {{ $overdueCount }} invoice(s) are past due date with an outstanding balance. <a href="{{ route('fleet.invoices.index') }}?status=overdue" class="alert-link">Filter overdue</a>
            </div>
        </div>
        @endif

        <!-- Dashboard Stats -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Invoices</p>
                                <h4 class="my-1 text-primary">{{ number_format($totalInvoices) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-receipt align-middle"></i> All invoices</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-receipt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Amount</p>
                                <h4 class="my-1 text-success">TZS {{ number_format($totalAmount, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-dollar align-middle"></i> Revenue</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Paid</p>
                                <h4 class="my-1 text-info">TZS {{ number_format($totalPaid, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-check-circle align-middle"></i> Collected</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Outstanding</p>
                                <h4 class="my-1 text-warning">TZS {{ number_format($totalOutstanding, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-time align-middle"></i> Pending</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-time"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-light border-0">
                <h6 class="mb-0"><i class="bx bx-filter me-2"></i>Filters</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="filter-status" class="form-label">Status</label>
                        <select id="filter-status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="paid">Paid</option>
                            <option value="partially_paid">Partially Paid</option>
                            <option value="overdue">Overdue</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter-vehicle" class="form-label">Vehicle</label>
                        <select id="filter-vehicle" class="form-select form-select-sm">
                            <option value="">All Vehicles</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">{{ $vehicle->name }} @if($vehicle->registration_number)({{ $vehicle->registration_number }})@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter-trip" class="form-label">Trip</label>
                        <select id="filter-trip" class="form-select form-select-sm">
                            <option value="">All Trips</option>
                            @foreach($trips as $trip)
                                <option value="{{ $trip->id }}">{{ $trip->trip_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter-date-from" class="form-label">Date From</label>
                        <input type="date" id="filter-date-from" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label for="filter-date-to" class="form-label">Date To</label>
                        <input type="date" id="filter-date-to" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" id="clear-filters" class="btn btn-sm btn-outline-secondary w-100">
                            <i class="bx bx-x me-1"></i>Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-success text-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-receipt me-2"></i>Revenue & Billing</h5>
                    <div class="text-white-50">Track revenue per vehicle, driver, route & trip; record payments; maintain GL</div>
                </div>
                <div>
                    <a href="{{ route('fleet.invoices.create') }}" class="btn btn-light">
                        <i class="bx bx-plus me-1"></i>Create Invoice
                    </a>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="invoices-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>SN</th>
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Customer (Billed To)</th>
                                <th>Driver</th>
                                <th>Trip</th>
                                <th>Due Date</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Balance Due</th>
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
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    var table = $('#invoices-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("fleet.invoices.data") }}',
            data: function(d) {
                d.status = $('#filter-status').val();
                d.vehicle_id = $('#filter-vehicle').val();
                d.trip_id = $('#filter-trip').val();
                d.date_from = $('#filter-date-from').val();
                d.date_to = $('#filter-date-to').val();
            }
        },
        initComplete: function() {
            var params = new URLSearchParams(window.location.search);
            if (params.get('status') === 'overdue') {
                $('#filter-status').val('overdue');
                table.ajax.reload();
            }
        },
        columns: [
            { 
                data: null,
                name: 'sn',
                orderable: false,
                searchable: false,
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'invoice_number', name: 'invoice_number' },
            { data: 'invoice_date_display', name: 'invoice_date' },
            { data: 'vehicle_display', name: 'vehicle_display', orderable: false },
            { data: 'customer_display', name: 'customer_display', orderable: false },
            { data: 'driver_display', name: 'driver_display', orderable: false },
            { data: 'trip_display', name: 'trip_display', orderable: false },
            { data: 'due_date_display', name: 'due_date' },
            { data: 'total_amount_display', name: 'total_amount' },
            { data: 'paid_amount_display', name: 'paid_amount' },
            { data: 'balance_due_display', name: 'balance_due' },
            { data: 'status_display', name: 'status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[2, 'desc']], // Order by Invoice Date (third column after SN and Invoice #) descending to show newest first
        pageLength: 25,
        language: {
            processing: '<div class="text-center"><i class="bx bx-loader-alt bx-spin bx-lg"></i> Loading invoices...</div>'
        }
    });

    // Apply filters on change
    $('#filter-status, #filter-vehicle, #filter-trip, #filter-date-from, #filter-date-to').on('change', function() {
        table.ajax.reload();
    });

    // Clear filters
    $('#clear-filters').on('click', function() {
        $('#filter-status, #filter-vehicle, #filter-trip, #filter-date-from, #filter-date-to').val('');
        table.ajax.reload();
    });

    // Delete invoice with SweetAlert - use event delegation on document
    $(document).on('click', '.delete-invoice-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        
        const invoiceId = $(this).data('invoice-id');
        const invoiceNumber = $(this).data('invoice-number');
        
        if (!invoiceId) {
            console.error('Invoice ID not found');
            return false;
        }
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Delete Invoice?',
                text: 'Are you sure you want to delete invoice ' + invoiceNumber + '? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create and submit form
                    const form = $('<form>', {
                        'method': 'POST',
                        'action': '{{ url("/fleet/invoices") }}/' + invoiceId
                    });
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': '_token',
                        'value': '{{ csrf_token() }}'
                    }));
                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': '_method',
                        'value': 'DELETE'
                    }));
                    $('body').append(form);
                    form.submit();
                }
            });
        } else {
            if (confirm('Are you sure you want to delete invoice ' + invoiceNumber + '?')) {
                // Create and submit form
                const form = $('<form>', {
                    'method': 'POST',
                    'action': '{{ url("/fleet/invoices") }}/' + invoiceId
                });
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_token',
                    'value': '{{ csrf_token() }}'
                }));
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_method',
                    'value': 'DELETE'
                }));
                $('body').append(form);
                form.submit();
            }
        }
        
        return false;
    });
});
</script>
@endpush
