@extends('layouts.main')

@section('title', 'Store Issues')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Issues', 'url' => '#', 'icon' => 'bx bx-package']
        ]" />

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-primary">Store Issues Management</h5>
                <small class="text-muted">Track and manage store item issuances</small>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Total Issues</h6>
                                <h4 class="mb-0 text-primary" id="totalIssues">0</h4>
                            </div>
                            <div class="text-primary">
                                <i class="bx bx-package bx-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Pending Issues</h6>
                                <h4 class="mb-0 text-success" id="pendingIssues">0</h4>
                            </div>
                            <div class="text-success">
                                <i class="bx bx-time bx-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Completed Issues</h6>
                                <h4 class="mb-0 text-info" id="completedIssues">0</h4>
                            </div>
                            <div class="text-info">
                                <i class="bx bx-check-circle bx-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Partial Issues</h6>
                                <h4 class="mb-0 text-warning" id="partialIssues">0</h4>
                            </div>
                            <div class="text-warning">
                                <i class="bx bx-error bx-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bx bx-filter me-2"></i>Filters
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="statusFilter" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="partial">Partial</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" id="dateFromFilter" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" id="dateToFilter" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="button" class="btn btn-primary" onclick="applyFilters()">
                                <i class="bx bx-filter me-1"></i> Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Issues Table -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bx bx-list-ul me-2"></i>Store Issues
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="issuesTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>Issue #</th>
                                <th>Requisition #</th>
                                <th>Issue Date</th>
                                <th>Issued To</th>
                                <th>Issued By</th>
                                <th>Total Amount</th>
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

<!-- Create Issue Modal -->
<div class="modal fade" id="createIssueModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Store Issue</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createIssueForm">
                @csrf
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Requisition</label>
                            <select name="store_requisition_id" id="requisitionSelect" class="form-select" required>
                                <option value="">Select Requisition</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Issue Date</label>
                            <input type="date" name="issue_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Issued To</label>
                            <select name="issued_to" id="issuedToSelect" class="form-select" required>
                                <option value="">Select Employee</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" placeholder="Optional description">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Optional remarks"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Issue</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#issuesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('store-issues.index') }}",
            data: function (d) {
                d.status = $('#statusFilter').val();
                d.date_from = $('#dateFromFilter').val();
                d.date_to = $('#dateToFilter').val();
            }
        },
        columns: [
            {data: 'voucher_no', name: 'voucher_no'},
            {data: 'requisition_number', name: 'storeRequisition.requisition_number'},
            {data: 'issue_date', name: 'issue_date'},
            {data: 'issued_to_name', name: 'issuedTo.name'},
            {data: 'issued_by_name', name: 'issuedBy.name'},
            {data: 'total_amount', name: 'total_amount'},
            {data: 'status_badge', name: 'status', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[2, 'desc']],
        pageLength: 25,
        responsive: true,
        drawCallback: function(settings) {
            updateStatistics();
        }
    });

    // Load approved requisitions for issue creation
    loadApprovedRequisitions();
    loadEmployees();

    // Create issue form submission
    $('#createIssueForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: "{{ route('store-issues.store') }}",
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#createIssueModal').modal('hide');
                    $('#createIssueForm')[0].reset();
                    table.ajax.reload();
                    Swal.fire('Success!', response.message, 'success');
                } else {
                    Swal.fire('Error!', response.message || 'Failed to create issue.', 'error');
                }
            },
            error: function(xhr) {
                let message = 'Failed to create issue.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire('Error!', message, 'error');
            }
        });
    });
});

function applyFilters() {
    $('#issuesTable').DataTable().ajax.reload();
}

function loadApprovedRequisitions() {
    $.get("{{ route('store-requisitions.approved') }}", function(data) {
        const select = $('#requisitionSelect');
        select.empty().append('<option value="">Select Requisition</option>');
        
        data.forEach(function(requisition) {
            select.append(`<option value="${requisition.id}">${requisition.requisition_number} - ${requisition.purpose}</option>`);
        });
    });
}

function loadEmployees() {
    $.get("{{ route('users.employees') }}", function(data) {
        const select = $('#issuedToSelect');
        select.empty().append('<option value="">Select Employee</option>');
        
        data.forEach(function(employee) {
            select.append(`<option value="${employee.id}">${employee.name}</option>`);
        });
    });
}

function updateStatistics() {
    $.get("{{ route('store-issues.statistics') }}", function(data) {
        $('#totalIssues').text(data.total || 0);
        $('#pendingIssues').text(data.pending || 0);
        $('#completedIssues').text(data.completed || 0);
        $('#partialIssues').text(data.partial || 0);
    });
}

function viewIssue(id) {
    window.location.href = `{{ url('store-issues') }}/${id}`;
}

function editIssue(id) {
    window.location.href = `{{ url('store-issues') }}/${id}/edit`;
}

function deleteIssue(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ url('store-issues') }}/${id}`,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        $('#issuesTable').DataTable().ajax.reload();
                        Swal.fire('Deleted!', response.message, 'success');
                    } else {
                        Swal.fire('Error!', response.message || 'Failed to delete issue.', 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error!', 'Failed to delete issue.', 'error');
                }
            });
        }
    });
}

// Set default issue date to today
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('input[name="issue_date"]').value = today;
});
</script>
@endpush