@extends('layouts.main')

@section('title', 'Held for Sale (HFS) Requests')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Held for Sale', 'url' => '#', 'icon' => 'bx bx-transfer']
        ]" />

        <!-- Dashboard Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar bg-warning bg-opacity-10 rounded">
                                    <i class="bx bx-time-five fs-4 text-warning"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0 text-muted">Pending Approvals</h6>
                                <h4 class="mb-0" id="pending-approvals-count">-</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar bg-info bg-opacity-10 rounded">
                                    <i class="bx bx-transfer fs-4 text-info"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0 text-muted">Active HFS</h6>
                                <h4 class="mb-0" id="active-hfs-count">-</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar bg-danger bg-opacity-10 rounded">
                                    <i class="bx bx-error-circle fs-4 text-danger"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0 text-muted">Overdue (>12 months)</h6>
                                <h4 class="mb-0" id="overdue-count">-</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar bg-success bg-opacity-10 rounded">
                                    <i class="bx bx-check-circle fs-4 text-success"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0 text-muted">Recently Sold</h6>
                                <h4 class="mb-0" id="sold-count">-</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-transfer me-2"></i>Held for Sale (HFS) Requests</h5>
                    <div class="text-muted">Manage assets classified as Held for Sale per IFRS 5</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('assets.hfs.requests.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>New HFS Request
                    </a>
                </div>
            </div>
            <div class="card-body pt-0">
                <!-- Filters -->
                <div class="row g-2 mb-3" id="filters-row">
                    <div class="col-md-2">
                        <label class="form-label small">Status</label>
                        <select name="status" id="filter_status" class="form-select form-select-sm select2-single">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="in_review">In Review</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="sold">Sold</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Overdue</label>
                        <select name="overdue" id="filter_overdue" class="form-select form-select-sm select2-single">
                            <option value="">All</option>
                            <option value="1">Overdue Only</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Date From</label>
                        <input type="date" name="date_from" id="filter_date_from" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Date To</label>
                        <input type="date" name="date_to" id="filter_date_to" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button type="button" id="btn-filter" class="btn btn-sm btn-primary">
                            <i class="bx bx-search me-1"></i>Filter
                        </button>
                        <button type="button" id="btn-reset" class="btn btn-sm btn-outline-secondary">
                            <i class="bx bx-refresh me-1"></i>Reset
                        </button>
                    </div>
                </div>

                <!-- HFS Requests Table -->
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="hfs-requests-table" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Request #</th>
                                <th>Assets</th>
                                <th>Intended Sale Date</th>
                                <th class="text-end">Carrying Amount</th>
                                <th>Buyer</th>
                                <th>Status</th>
                                <th>Overdue</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
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
    // Initialize Select2 for filter selects
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    const table = $('#hfs-requests-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("assets.hfs.requests.data") }}',
            data: function(d) {
                d.status = $('#filter_status').val();
                d.overdue = $('#filter_overdue').val();
                d.date_from = $('#filter_date_from').val();
                d.date_to = $('#filter_date_to').val();
            }
        },
        order: [[0, 'desc']],
        columns: [
            { data: 'request_no', name: 'request_no' },
            { data: 'asset_codes', name: 'asset_codes' },
            { data: 'intended_sale_date', name: 'intended_sale_date' },
            { data: 'total_carrying_amount', name: 'total_carrying_amount', className: 'text-end' },
            { data: 'buyer_name', name: 'buyer_name' },
            { data: 'status', name: 'status', orderable: false },
            { data: 'is_overdue', name: 'is_overdue', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<i class="bx bx-loader bx-spin"></i> Loading...',
        }
    });

    // Filter button
    $('#btn-filter').on('click', function() {
        table.ajax.reload();
    });

    // Reset button
    $('#btn-reset').on('click', function() {
        $('#filter_status').val('');
        $('#filter_overdue').val('');
        $('#filter_date_from').val('');
        $('#filter_date_to').val('');
        table.ajax.reload();
    });

    // Load dashboard counts
    loadDashboardCounts();

    // Approve HFS request
    window.approveHfs = function(encodedId) {
        Swal.fire({
            title: 'Approve HFS Request',
            html: `
                <form id="approve-form">
                    <div class="mb-3">
                        <label class="form-label">Approval Level <span class="text-danger">*</span></label>
                        <select id="approval_level" class="form-select" required>
                            <option value="">-- Select --</option>
                            <option value="asset_custodian">Asset Custodian</option>
                            <option value="finance_manager">Finance Manager</option>
                            <option value="cfo">CFO</option>
                            <option value="board">Board</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comments</label>
                        <textarea id="approval_comments" class="form-control" rows="3" placeholder="Optional comments"></textarea>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Approve',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            preConfirm: () => {
                const level = document.getElementById('approval_level').value;
                if (!level) {
                    Swal.showValidationMessage('Please select an approval level');
                    return false;
                }
                return {
                    approval_level: level,
                    comments: document.getElementById('approval_comments').value || null
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const data = result.value;
                
                $.ajax({
                    url: '{{ url("asset-management/hfs/requests") }}/' + encodedId + '/approve',
                    method: 'POST',
                    data: {
                        approval_level: data.approval_level,
                        comments: data.comments,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message || 'HFS request approved successfully',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            table.ajax.reload();
                            loadDashboardCounts();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Failed to approve HFS request'
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'Failed to approve HFS request';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMsg
                        });
                    }
                });
            }
        });
    };

    function loadDashboardCounts() {
        $.ajax({
            url: '{{ route("assets.hfs.requests.data") }}',
            data: {
                status: 'in_review',
                length: 1
            },
            success: function(response) {
                $('#pending-approvals-count').text(response.recordsTotal || 0);
            }
        });

        $.ajax({
            url: '{{ route("assets.hfs.requests.data") }}',
            data: {
                status: 'approved',
                length: 1
            },
            success: function(response) {
                $('#active-hfs-count').text(response.recordsTotal || 0);
            }
        });

        $.ajax({
            url: '{{ route("assets.hfs.requests.data") }}',
            data: {
                overdue: 1,
                length: 1
            },
            success: function(response) {
                $('#overdue-count').text(response.recordsTotal || 0);
            }
        });

        $.ajax({
            url: '{{ route("assets.hfs.requests.data") }}',
            data: {
                status: 'sold',
                length: 1
            },
            success: function(response) {
                $('#sold-count').text(response.recordsTotal || 0);
            }
        });
    }
});
</script>
@endpush

