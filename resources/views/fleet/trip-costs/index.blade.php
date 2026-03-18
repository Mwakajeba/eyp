@extends('layouts.main')

@section('title', 'Cost Management - Fleet Management')

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
    .bg-gradient-danger { background: linear-gradient(45deg, #dc3545, #bb2d3b) !important; }
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
            ['label' => 'Cost Management', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-money me-2"></i>Cost Management</h5>
                                <p class="mb-0 text-muted">Track fuel, maintenance, driver costs, and other trip expenses</p>
                            </div>
                            <div>
                                <a href="{{ route('fleet.trip-costs.create', isset($trip) ? ['trip_id' => $trip->hash_id] : []) }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Add Cost
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($trip)
        <div class="alert alert-info mb-3">
            <i class="bx bx-info-circle me-2"></i>Showing costs for trip: <strong>{{ $trip->trip_number }}</strong>
            <a href="{{ route('fleet.trip-costs.index') }}" class="float-end">View All Costs</a>
        </div>
        @endif

        <!-- Dashboard Stats -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Costs</p>
                                <h4 class="my-1 text-primary">{{ number_format($totalCosts) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-list-ul align-middle"></i> All records</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-list-ul"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Amount</p>
                                <h4 class="my-1 text-danger">TZS {{ number_format($totalAmount, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-danger"><i class="bx bx-money align-middle"></i> Expenses</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-danger text-white ms-auto">
                                <i class="bx bx-money"></i>
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
                                <p class="mb-0 text-secondary">Approved</p>
                                <h4 class="my-1 text-success">{{ number_format($approvedCosts) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Verified</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
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
                                <p class="mb-0 text-secondary">Pending</p>
                                <h4 class="my-1 text-warning">{{ number_format($pendingCosts) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-time align-middle"></i> Awaiting approval</span>
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

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label small">Cost Type</label>
                        <select id="filter-cost-type" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="fuel">Fuel</option>
                            <option value="driver_allowance">Driver Allowance</option>
                            <option value="overtime">Overtime</option>
                            <option value="toll">Toll</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="insurance">Insurance</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Approval Status</label>
                        <select id="filter-approval-status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-filters">
                            <i class="bx bx-x me-1"></i>Clear Filters
                        </button>
                    </div>
                </div>
                <div class="mb-2 d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-success" id="approve-selected-btn" style="display: none;">
                        <i class="bx bx-check-circle me-1"></i>Approve selected
                    </button>
                    <span id="approve-selected-count" class="small text-muted"></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="costs-table" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width: 32px;"><input type="checkbox" id="select-all-costs" aria-label="Select all" title="Select all"></th>
                                <th>S/N</th>
                                <th>Date</th>
                                <th>Trip</th>
                                <th>Vehicle</th>
                                <th>Cost Type</th>
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
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    const costsTable = $('#costs-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("fleet.trip-costs.data") }}',
            data: function(d) {
                d.cost_type = $('#filter-cost-type').val();
                d.approval_status = $('#filter-approval-status').val();
                @if($trip)
                d.trip_id = '{{ $trip->hash_id }}';
                @endif
            }
        },
        columns: [
            { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
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
            { data: 'trip_display', name: 'trip_display', orderable: false },
            { data: 'vehicle_display', name: 'vehicle_display', orderable: false },
            { data: 'cost_type_display', name: 'cost_type', orderable: false },
            { data: 'description', name: 'description' },
            { data: 'amount_display', name: 'amount', orderable: false },
            { data: 'approval_status_display', name: 'approval_status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[2, 'desc']],
        pageLength: 25,
        responsive: true
    });

    $('#filter-cost-type, #filter-approval-status').on('change', function() {
        costsTable.ajax.reload();
    });

    $('#clear-filters').on('click', function() {
        $('#filter-cost-type, #filter-approval-status').val('');
        costsTable.ajax.reload();
    });
    
    // Select all / Approve selected
    $(document).on('change', '#select-all-costs', function() {
        var checked = $(this).prop('checked');
        $('#costs-table .cost-row-checkbox').prop('checked', checked);
        updateApproveSelectedState();
    });
    $(document).on('change', '.cost-row-checkbox', updateApproveSelectedState);
    function updateApproveSelectedState() {
        var total = 0;
        var ids = [];
        $('#costs-table .cost-row-checkbox:checked').each(function() {
            var cids = $(this).data('cost-ids');
            if (cids) {
                total++;
                ids = ids.concat(String(cids).split(','));
            }
        });
        ids = ids.filter(Boolean);
        if (ids.length > 0) {
            $('#approve-selected-btn').show().data('cost-ids', ids.join(',')).data('cost-count', ids.length);
            $('#approve-selected-count').text(ids.length + ' selected');
        } else {
            $('#approve-selected-btn').hide().removeData('cost-ids').removeData('cost-count');
            $('#approve-selected-count').text('');
        }
        $('#select-all-costs').prop('checked', false);
    }
    $('#approve-selected-btn').on('click', function() {
        var costIds = $(this).data('cost-ids');
        var costCount = $(this).data('cost-count');
        if (!costIds || !costCount) return;
        if (typeof Swal === 'undefined') {
            if (confirm('Approve ' + costCount + ' cost(s)?')) {
                var f = $('<form method="POST" action="{{ route("fleet.trip-costs.batch-approve") }}">').append(
                    $('<input type="hidden" name="_token">').val('{{ csrf_token() }}'),
                    $('<input type="hidden" name="cost_ids">').val(costIds),
                    $('<input type="hidden" name="approval_notes">').val('')
                );
                $('body').append(f);
                f.submit();
            }
            return;
        }
        Swal.fire({
            title: 'Approve selected costs?',
            html: 'Approve <strong>' + costCount + '</strong> cost(s)?<br><br><small class="text-muted">Approved costs cannot be edited or deleted.</small>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bx bx-check-circle me-1"></i>Yes, Approve',
            cancelButtonText: 'Cancel',
            input: 'textarea',
            inputPlaceholder: 'Approval notes (optional)...'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Approving...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                var currentTripId = '{{ $trip->hash_id ?? "" }}';
                $.ajax({
                    url: '{{ route("fleet.trip-costs.batch-approve") }}',
                    method: 'POST',
                    data: { cost_ids: costIds, approval_notes: result.value || '', current_trip_id: currentTripId, _token: '{{ csrf_token() }}' }
                }).done(function(response) {
                    Swal.fire({ icon: 'success', title: 'Approved!', text: response.message, timer: 2000, showConfirmButton: false }).then(function() {
                        if (response.redirect) window.location.href = response.redirect;
                        else costsTable.ajax.reload();
                    });
                }).fail(function(xhr) {
                    Swal.fire('Error', (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to approve.', 'error');
                });
            }
        });
    });
    
    // Handle delete button click
    $(document).on('click', '.delete-group-costs-btn', function(e) {
        e.preventDefault();
        const button = $(this);
        const costIds = button.data('cost-ids');
        const costCount = button.data('cost-count');
        const totalAmount = button.data('total-amount');
        
        Swal.fire({
            title: 'Delete Trip Costs?',
            html: `Are you sure you want to delete <strong>${costCount}</strong> cost(s) with a total amount of <strong>${totalAmount} TZS</strong>?<br><br>` +
                  '<small class="text-muted">This will permanently remove the cost records and associated GL transactions.</small><br><br>' +
                  '<strong class="text-danger">This action cannot be undone!</strong>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bx bx-trash me-1"></i>Yes, Delete',
            cancelButtonText: '<i class="bx bx-x me-1"></i>Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the costs.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Delete via AJAX
                $.ajax({
                    url: '{{ route("fleet.trip-costs.batch-destroy") }}',
                    method: 'DELETE',
                    data: {
                        cost_ids: costIds,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                if (response.redirect) {
                                    window.location.href = response.redirect;
                                } else {
                                    costsTable.ajax.reload();
                                }
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Failed to delete costs.', 'error');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Failed to delete costs. Please try again.';
                        Swal.fire('Error', message, 'error');
                    }
                });
            }
        });
    });

    // Handle approve button click
    $(document).on('click', '.approve-group-costs-btn', function(e) {
        e.preventDefault();
        const button = $(this);
        const costIds = button.data('cost-ids');
        const costCount = button.data('cost-count');
        
        Swal.fire({
            title: 'Approve All Costs?',
            html: `Are you sure you want to approve <strong>${costCount}</strong> cost(s)?<br><br>` +
                  '<small class="text-muted">Approved costs cannot be edited or deleted.</small><br><br>' +
                  '<strong>This action cannot be undone!</strong>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bx bx-check-circle me-1"></i>Yes, Approve All',
            cancelButtonText: '<i class="bx bx-x me-1"></i>Cancel',
            reverseButtons: true,
            input: 'textarea',
            inputPlaceholder: 'Approval notes (optional)...',
            inputAttributes: {
                'aria-label': 'Approval notes'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Approving costs...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Approve via AJAX
                $.ajax({
                    url: '{{ route("fleet.trip-costs.batch-approve") }}',
                    method: 'POST',
                    data: {
                        cost_ids: costIds,
                        approval_notes: result.value || null,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Approved!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                if (response.redirect) {
                                    window.location.href = response.redirect;
                                } else {
                                    costsTable.ajax.reload();
                                }
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Failed to approve costs.', 'error');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Failed to approve costs. Please try again.';
                        Swal.fire('Error', message, 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
