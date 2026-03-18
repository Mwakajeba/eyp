@extends('layouts.main')

@section('title', 'Timesheets')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Timesheets', 'url' => '#', 'icon' => 'bx bx-time-five']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase">
                    <i class="bx bx-time-five me-1"></i>Timesheets
                </h6>
                <a href="{{ route('hr.timesheets.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Add Timesheet
                </a>
            </div>
            <hr />

            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Employee</label>
                            <select name="employee_id" id="employee_id" class="form-select">
                                <option value="">All Employees</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Department</label>
                            <select name="department_id" id="department_id" class="form-select">
                                <option value="">All Departments</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Activity Type</label>
                            <select name="activity_type" id="activity_type" class="form-select">
                                <option value="">All Types</option>
                                <option value="work">Work</option>
                                <option value="training">Training</option>
                                <option value="meeting">Meeting</option>
                                <option value="conference">Conference</option>
                                <option value="project">Project</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All</option>
                                <option value="draft">Draft</option>
                                <option value="submitted">Submitted</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <button type="button" id="applyFilters" class="btn btn-primary">
                                <i class="bx bx-filter me-1"></i>Apply Filters
                            </button>
                            <button type="button" id="clearFilters" class="btn btn-secondary">
                                <i class="bx bx-x me-1"></i>Clear
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="timesheetsTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Employee</th>
                                    <th>Date</th>
                                    <th>Department</th>
                                    <th>Activity Type</th>
                                    <th>Hours</th>
                                    <th>Status</th>
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
    let table = $('#timesheetsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('hr.timesheets.index') }}",
            data: function(d) {
                d.employee_id = $('#employee_id').val();
                d.department_id = $('#department_id').val();
                d.activity_type = $('#activity_type').val();
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.status = $('#status').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'employee_name', name: 'employee_name' },
            { data: 'timesheet_date', name: 'timesheet_date' },
            { data: 'department_name', name: 'department_name' },
            { data: 'activity_type_badge', name: 'activity_type', orderable: false },
            { data: 'total_hours', name: 'total_hours', orderable: false },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[2, 'desc']],
        pageLength: 25,
        language: {
            processing: '<i class="bx bx-loader bx-spin"></i> Loading...'
        }
    });

    $('#applyFilters').on('click', function() {
        table.draw();
    });

    $('#clearFilters').on('click', function() {
        $('#filterForm')[0].reset();
        table.draw();
    });
});
</script>
@endpush

