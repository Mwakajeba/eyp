@extends('layouts.main')

@section('title', 'Imprest Requests')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Imprest Management', 'url' => route('imprest.index'), 'icon' => 'bx bx-money'],
            ['label' => 'All Requests', 'url' => '#', 'icon' => 'bx bx-list-ul']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-primary">Imprest Requests</h5>
            <a href="{{ route('imprest.requests.create') }}" class="btn btn-primary">
                <i class="bx bx-plus-circle me-1"></i> New Request
            </a>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="checked">Checked</option>
                                <option value="approved">Approved</option>
                                <option value="disbursed">Disbursed</option>
                                <option value="liquidated">Liquidated</option>
                                <option value="closed">Closed</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="department" class="form-label">Department</label>
                            <select name="department" id="department" class="form-select">
                                <option value="">All Departments</option>
                                @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" name="date_from" id="date_from" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" name="date_to" id="date_to" class="form-control">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="button" id="applyFilter" class="btn btn-primary">
                                <i class="bx bx-search me-1"></i> Filter
                            </button>
                            <button type="button" id="clearFilter" class="btn btn-outline-secondary">
                                <i class="bx bx-refresh me-1"></i> Clear
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="requestsTable" class="table table-striped table-hover" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th>Request #</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Project</th>
                                <th>Activity</th>
                                <th>Purpose</th>
                                <th>Amount</th>
                                <th>Date Required</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
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
    let table = $('#requestsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("imprest.requests.index") }}',
            data: function (d) {
                d.status = $('#status').val();
                d.department = $('#department').val();
                d.date_from = $('#date_from').val();
                d.date_to = $('#date_to').val();
            }
        },
        columns: [
            {data: 'request_number', name: 'request_number'},
            {data: 'employee_name', name: 'employee.name'},
            {data: 'department_name', name: 'department.name'},
            {data: 'project_name', name: 'project.name', orderable: false},
            {data: 'activity_name', name: 'projectActivity.description', orderable: false},
            {data: 'purpose', name: 'purpose'},
            {data: 'amount_formatted', name: 'amount_requested', className: 'text-end'},
            {data: 'date_required', name: 'date_required'},
            {data: 'status_badge', name: 'status', orderable: false},
            {data: 'created_at', name: 'created_at'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[7, 'desc']],
        lengthMenu: [[10, 25, 50, 100, 500, -1], [10, 25, 50, 100, 500, 'All']],
        pageLength: 25,
        responsive: true,
        dom: 'lfrtip'
    });

    // Filter functionality
    $('#applyFilter').click(function() {
        table.draw();
    });

    $('#clearFilter').click(function() {
        $('#filterForm')[0].reset();
        table.draw();
    });

    // Delete functionality
    $(document).on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("imprest.requests.destroy", ":id") }}'.replace(':id', id),
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.success, 'success');
                            table.draw();
                        } else {
                            Swal.fire('Error!', response.error, 'error');
                        }
                    },
                    error: function(xhr) {
                        let message = 'An error occurred while deleting.';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            message = xhr.responseJSON.error;
                        }
                        Swal.fire('Error!', message, 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush