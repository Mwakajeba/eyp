@extends('layouts.main')

@section('title', 'Store Requisitions')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Requisition Management', 'url' => route('store-requisitions.index'), 'icon' => 'bx bx-package'],
            ['label' => 'All Requisitions', 'url' => '#', 'icon' => 'bx bx-list-ul']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-primary">Store Requisitions</h5>
            <a href="{{ route('store-requisitions.requisitions.create') }}" class="btn btn-primary">
                <i class="bx bx-plus-circle me-1"></i> New Requisition
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
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="partially_issued">Partially Issued</option>
                                <option value="fully_issued">Fully Issued</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="branch" class="form-label">Department</label>
                            <select name="branch" id="branch" class="form-select">
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
                    <table id="requisitionsTable" class="table table-striped table-hover" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th>Voucher #</th>
                                <th>Employee</th>
                                <th>Branch</th>
                                <th>Purpose</th>
                                <th>Request Date</th>
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
    let table = $('#requisitionsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('store-requisitions.requisitions.index') }}",
            data: function(d) {
                d.status = $('#status').val();
                d.branch = $('#branch').val();
                d.date_from = $('#date_from').val();
                d.date_to = $('#date_to').val();
            }
        },
        columns: [
            { data: 'voucher_no', name: 'voucher_no' },
            { data: 'employee_name', name: 'employee_name' },
            { data: 'branch_name', name: 'branch_name' },
            { data: 'purpose', name: 'purpose' },
            { data: 'request_date', name: 'request_date' },
            { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[6, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: '<i class="bx bx-loader-alt bx-spin bx-lg"></i> Loading...',
            emptyTable: 'No store requisitions found',
            zeroRecords: 'No matching store requisitions found'
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="bx bx-download me-1"></i>Excel',
                className: 'btn btn-success btn-sm'
            },
            {
                extend: 'pdf',
                text: '<i class="bx bx-download me-1"></i>PDF',
                className: 'btn btn-danger btn-sm'
            },
            {
                extend: 'print',
                text: '<i class="bx bx-printer me-1"></i>Print',
                className: 'btn btn-info btn-sm'
            }
        ],
        responsive: true,
        autoWidth: false
    });

    // Apply filters
    $('#applyFilter').click(function() {
        table.ajax.reload();
    });

    // Clear filters
    $('#clearFilter').click(function() {
        $('#filterForm')[0].reset();
        table.ajax.reload();
    });

    // Auto-apply filter on status change from URL
    @if(request('status'))
        $('#status').val('{{ request('status') }}');
        table.ajax.reload();
    @endif

    // Delete functionality
    window.deleteRequisition = function(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('store-requisitions.requisitions.destroy', ':id') }}".replace(':id', id),
                    type: 'DELETE',
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message || 'Store requisition has been deleted.', 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message || 'Failed to delete store requisition.', 'error');
                        }
                    },
                    error: function(xhr) {
                        let message = 'Failed to delete store requisition.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire('Error!', message, 'error');
                    }
                });
            }
        });
    };
});
</script>
@endpush