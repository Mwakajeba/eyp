@extends('layouts.main')

@section('title', 'Attendance Management')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Attendance', 'url' => '#', 'icon' => 'bx bx-time']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase">
                    <i class="bx bx-time me-1"></i>Attendance Management
                </h6>
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
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="late">Late</option>
                                <option value="early_exit">Early Exit</option>
                                <option value="on_leave">On Leave</option>
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
                        <table id="attendanceTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Employee</th>
                                    <th>Date</th>
                                    <th>Clock In/Out</th>
                                    <th>OT In/Out</th>
                                    <th>Hours</th>
                                    <th>Status</th>
                                    <th>Approval</th>
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
    let table = $('#attendanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('hr.attendance.index') }}",
            data: function(d) {
                d.employee_id = $('#employee_id').val();
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.status = $('#status').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'employee_name', name: 'employee_name'},
            {data: 'attendance_date', name: 'attendance_date'},
            {data: 'clock_in_out', name: 'clock_in_out', orderable: false, searchable: false},
            {data: 'overtime_in_out', name: 'overtime_in_out', orderable: false, searchable: false},
            {data: 'hours', name: 'hours', orderable: false, searchable: false},
            {data: 'status_badge', name: 'status', orderable: false, searchable: false},
            {data: 'approval_badge', name: 'is_approved', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        pageLength: 25,
        responsive: true,
        order: [[2, 'desc']]
    });

    $('#applyFilters').click(function() {
        table.ajax.reload();
    });

    $('#clearFilters').click(function() {
        $('#filterForm')[0].reset();
        table.ajax.reload();
    });

    $(document).on('click', '.approve-btn', function() {
        let id = $(this).data('id');
        $.ajax({
            url: `{{ route('hr.attendance.index') }}/${id}/approve`,
            type: 'POST',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            success: function(response) {
                if (response.success) {
                    table.ajax.reload();
                    Swal.fire({icon: 'success', title: 'Approved!', text: response.message, timer: 3000, showConfirmButton: false});
                }
            },
            error: function(xhr) {
                Swal.fire({icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Something went wrong.'});
            }
        });
    });
});
</script>
@endpush

