@extends('layouts.main')

@section('title', 'Closed Imprest Requests')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Imprest Management', 'url' => route('imprest.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Closed Requests', 'url' => '#', 'icon' => 'bx bx-check-circle']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-primary">
                <i class="bx bx-check-circle me-2"></i>Closed Imprest Requests
            </h5>
            <a href="{{ route('imprest.index') }}" class="btn btn-outline-primary">
                <i class="bx bx-arrow-back me-1"></i> Back to Dashboard
            </a>
        </div>

        <!-- Info Alert -->
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bx bx-info-circle me-2"></i>
            <strong>Closed Requests:</strong> These are imprest requests that have been fully liquidated or closed. 
            They have completed their lifecycle in the system.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="employee" class="form-label">Employee</label>
                        <input type="text" name="employee" id="employee" class="form-control" placeholder="Search employee...">
                    </div>
                    <div class="col-md-3">
                        <label for="department" class="form-label">Department</label>
                        <select name="department" id="department" class="form-select">
                            <option value="">All Departments</option>
                            @foreach($departments ?? [] as $department)
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
            </div>
        </div>

        <!-- Data Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="closedRequestsTable" class="table table-striped table-hover" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th>Request #</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Amount Requested</th>
                                <th>Disbursed Amount</th>
                                <th>Liquidated Amount</th>
                                <th>Status</th>
                                <th>Created Date</th>
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
    // Initialize DataTable
    const table = $('#closedRequestsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('imprest.closed.index') }}",
            data: function(d) {
                d.employee = $('#employee').val();
                d.department = $('#department').val();
                d.date_from = $('#date_from').val();
                d.date_to = $('#date_to').val();
            }
        },
        columns: [
            { data: 'request_number', name: 'request_number' },
            { data: 'employee_name', name: 'employee_name' },
            { data: 'department_name', name: 'department_name' },
            { data: 'amount_formatted', name: 'amount_requested' },
            { data: 'disbursed_amount', name: 'disbursed_amount' },
            { data: 'liquidated_amount', name: 'liquidated_amount' },
            { data: 'status_badge', name: 'status' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[7, 'desc']],
        pageLength: 25,
        language: {
            processing: '<i class="bx bx-loader-alt bx-spin"></i> Loading...'
        }
    });

    // Apply filters
    $('#applyFilter').click(function() {
        table.draw();
    });

    // Clear filters
    $('#clearFilter').click(function() {
        $('#employee').val('');
        $('#department').val('');
        $('#date_from').val('');
        $('#date_to').val('');
        table.draw();
    });

    // Filter on Enter key
    $('#employee').on('keypress', function(e) {
        if (e.which == 13) {
            $('#applyFilter').click();
            return false;
        }
    });
});
</script>
@endpush
