@extends('layouts.main')

@section('title', 'Overtime Requests Management')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Overtime Requests', 'url' => '#', 'icon' => 'bx bx-time']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="mb-1">
                        <i class="bx bx-time text-primary me-2"></i>Overtime Requests Management
                    </h5>
                    <p class="text-muted mb-0">Manage and track employee overtime requests</p>
                </div>
                <a href="{{ route('hr.overtime-requests.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>New Overtime Request
                </a>
            </div>

            <!-- Dashboard Stats -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card radius-10 border-start border-0 border-3 border-primary">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">Total Requests</p>
                                    <h4 class="my-1 text-primary" id="statTotal">-</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-primary"><i class="bx bx-list-ul align-middle"></i> All requests</span>
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
                    <div class="card radius-10 border-start border-0 border-3 border-warning">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">Pending</p>
                                    <h4 class="my-1 text-warning" id="statPending">-</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-warning"><i class="bx bx-time-five align-middle"></i> Awaiting approval</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                    <i class="bx bx-time-five"></i>
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
                                    <h4 class="my-1 text-success" id="statApproved">-</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Approved requests</span>
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
                    <div class="card radius-10 border-start border-0 border-3 border-danger">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">Rejected</p>
                                    <h4 class="my-1 text-danger" id="statRejected">-</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-danger"><i class="bx bx-x-circle align-middle"></i> Rejected requests</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-danger text-white ms-auto">
                                    <i class="bx bx-x-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="mb-0">
                        <i class="bx bx-filter me-2"></i>Filters
                    </h6>
                </div>
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                <i class="bx bx-user me-1"></i>Employee
                            </label>
                            <select name="employee_id" id="employee_id" class="form-select select2-single">
                                <option value="">All Employees</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->employee_number }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                <i class="bx bx-info-circle me-1"></i>Status
                            </label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="button" id="applyFilters" class="btn btn-primary flex-fill">
                                    <i class="bx bx-filter me-1"></i>Apply Filters
                                </button>
                                <button type="button" id="clearFilters" class="btn btn-outline-secondary">
                                    <i class="bx bx-x me-1"></i>Clear
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="bx bx-table me-2"></i>Overtime Requests
                    </h6>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small" id="tableInfo">Loading...</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="overtimeRequestsTable" class="table table-hover align-middle" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="20%">
                                        <i class="bx bx-user me-1"></i>Employee
                                    </th>
                                    <th width="12%">
                                        <i class="bx bx-calendar me-1"></i>Date
                                    </th>
                                    <th width="18%">
                                        <i class="bx bx-time me-1"></i>Overtime Hours
                                    </th>
                                    <th width="12%">
                                        <i class="bx bx-info-circle me-1"></i>Status
                                    </th>
                                    <th width="15%">
                                        <i class="bx bx-user-check me-1"></i>Approver
                                    </th>
                                    <th width="18%" class="text-center">
                                        <i class="bx bx-cog me-1"></i>Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bx bx-x-circle me-2"></i>Reject Overtime Request
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="rejectForm">
                    <div class="modal-body">
                        <input type="hidden" id="rejectRequestId" name="request_id">
                        <div class="mb-3">
                            <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="4" required placeholder="Please provide a reason for rejecting this overtime request..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bx-x me-1"></i>Reject Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

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
    
    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #146c43) !important;
    }
    
    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
    }
    
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffb300) !important;
    }
    
    .bg-gradient-danger {
        background: linear-gradient(45deg, #dc3545, #bb2d3b) !important;
    }
    
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }
    
    #overtimeRequestsTable thead th {
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #dee2e6;
    }
    
    #overtimeRequestsTable tbody tr {
        transition: background-color 0.15s ease;
    }
    
    #overtimeRequestsTable tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .badge {
        font-weight: 500;
        padding: 0.5em 0.75em;
        font-size: 0.75rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    
    .table-responsive {
        border-radius: 0.375rem;
    }
    
    .card-header {
        padding: 1rem 1.25rem;
    }
    
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for employee filter
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select an employee',
        allowClear: true
    });

    let table = $('#overtimeRequestsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('hr.overtime-requests.index') }}",
            data: function(d) {
                d.employee_id = $('#employee_id').val();
                d.status = $('#status').val();
            }
        },
        columns: [
            {
                data: 'DT_RowIndex', 
                name: 'DT_RowIndex', 
                orderable: false, 
                searchable: false,
                className: 'text-center'
            },
            {
                data: 'employee_name', 
                name: 'employee_name',
                render: function(data, type, row) {
                    return '<div class="d-flex align-items-center">' +
                           '<div class="flex-grow-1">' +
                           '<div class="fw-semibold">' + data + '</div>' +
                           '<small class="text-muted">' + (row.employee_number || '') + '</small>' +
                           '</div></div>';
                }
            },
            {
                data: 'overtime_date', 
                name: 'overtime_date',
                render: function(data) {
                    if (!data) return '-';
                    const date = new Date(data);
                    return date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    });
                }
            },
            {
                data: 'overtime_amount', 
                name: 'overtime_amount', 
                orderable: false, 
                searchable: false,
                className: 'fw-semibold'
            },
            {
                data: 'status_badge', 
                name: 'status', 
                orderable: false, 
                searchable: false,
                className: 'text-center'
            },
            {
                data: 'approver_name', 
                name: 'approver_name',
                render: function(data) {
                    return data && data !== '-' ? '<span class="text-muted">' + data + '</span>' : '<span class="text-muted fst-italic">-</span>';
                }
            },
            {
                data: 'action', 
                name: 'action', 
                orderable: false, 
                searchable: false,
                className: 'text-center'
            }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        responsive: true,
        order: [[2, 'desc']],
        language: {
            processing: '<div class="d-flex justify-content-center align-items-center"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Loading...</div>',
            emptyTable: '<div class="text-center py-4"><i class="bx bx-inbox text-muted" style="font-size: 3rem;"></i><p class="text-muted mt-2">No overtime requests found</p></div>',
            zeroRecords: '<div class="text-center py-4"><i class="bx bx-search text-muted" style="font-size: 3rem;"></i><p class="text-muted mt-2">No matching records found</p></div>'
        },
        drawCallback: function(settings) {
            // Update table info
            const api = this.api();
            const pageInfo = api.page.info();
            const total = api.page.info().recordsTotal;
            const filtered = api.page.info().recordsDisplay;
            
            $('#tableInfo').text(
                `Showing ${pageInfo.start + 1} to ${Math.min(pageInfo.end, filtered)} of ${filtered} entries`
            );
            
            // Update statistics
            updateStatistics();
        }
    });

    // Update statistics
    function updateStatistics() {
        $.ajax({
            url: "{{ route('hr.overtime-requests.index') }}",
            data: {
                ajax: true,
                stats_only: true,
                employee_id: $('#employee_id').val(),
                status: $('#status').val()
            },
            success: function(response) {
                if (response.stats) {
                    $('#statTotal').text((response.stats.total || 0).toLocaleString());
                    $('#statPending').text((response.stats.pending || 0).toLocaleString());
                    $('#statApproved').text((response.stats.approved || 0).toLocaleString());
                    $('#statRejected').text((response.stats.rejected || 0).toLocaleString());
                }
            }
        });
    }

    // Initial statistics load
    updateStatistics();

    $('#applyFilters').click(function() {
        table.ajax.reload();
        updateStatistics();
    });

    $('#clearFilters').click(function() {
        $('#filterForm')[0].reset();
        $('.select2-single').val(null).trigger('change');
        table.ajax.reload();
        updateStatistics();
    });

    // Approve button
    $(document).on('click', '.approve-btn', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Approve Overtime Request?',
            text: 'Are you sure you want to approve this overtime request?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/hr-payroll/overtime-requests/${id}/approve`,
                    type: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            updateStatistics();
                            Swal.fire({
                                icon: 'success',
                                title: 'Approved!',
                                text: response.message || 'Overtime request approved successfully.',
                                timer: 3000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'Something went wrong. Please try again.'
                        });
                    }
                });
            }
        });
    });

    // Reject button
    let rejectRequestId = null;
    $(document).on('click', '.reject-btn', function() {
        rejectRequestId = $(this).data('id');
        $('#rejectRequestId').val(rejectRequestId);
        $('#rejection_reason').val('');
        $('#rejectModal').modal('show');
    });

    // Reject form submission
    $('#rejectForm').on('submit', function(e) {
        e.preventDefault();
        let id = $('#rejectRequestId').val();
        let reason = $('#rejection_reason').val();
        
        $.ajax({
            url: `/hr-payroll/overtime-requests/${id}/reject`,
            type: 'POST',
            data: {
                rejection_reason: reason
            },
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            beforeSend: function() {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                if (response.success) {
                    $('#rejectModal').modal('hide');
                    table.ajax.reload();
                    updateStatistics();
                    Swal.fire({
                        icon: 'success',
                        title: 'Rejected!',
                        text: response.message || 'Overtime request rejected successfully.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: xhr.responseJSON?.message || 'Something went wrong. Please try again.'
                });
            }
        });
    });
});
</script>
@endpush
