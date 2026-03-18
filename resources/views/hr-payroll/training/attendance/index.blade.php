@extends('layouts.main')

@section('title', 'Training Attendance Management')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Training', 'url' => '#', 'icon' => 'bx bx-book'],
                ['label' => 'Attendance', 'url' => '#', 'icon' => 'bx bx-user-check']
            ]" />
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase">
                    <i class="bx bx-user-check me-1"></i>Training Attendance Management
                </h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.training-attendance.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>Add Attendance
                    </a>
                </div>
            </div>
            <hr />
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="attendanceTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Employee</th>
                                    <th>Employee #</th>
                                    <th>Program</th>
                                    <th>Program Code</th>
                                    <th>Status</th>
                                    <th>Completion Date</th>
                                    <th>Evaluation Score</th>
                                    <th>Certification</th>
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
        ajax: "{{ route('hr.training-attendance.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'employee_name', name: 'employee_name', orderable: false},
            {data: 'employee_number', name: 'employee_number', orderable: false},
            {data: 'program_name', name: 'program_name', orderable: false},
            {data: 'program_code', name: 'program_code', orderable: false},
            {data: 'status_badge', name: 'attendance_status', orderable: false, searchable: false},
            {data: 'completion_date_display', name: 'completion_date', orderable: false, searchable: false},
            {data: 'evaluation_score_display', name: 'evaluation_score', orderable: false, searchable: false},
            {data: 'certification_badge', name: 'certification_received', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        language: {
            lengthMenu: "Show _MENU_ entries",
            search: "Search attendance:",
            processing: '<div class="d-flex justify-content-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>'
        },
        pageLength: 25,
        responsive: true,
        order: [[0, 'desc']]
    });

    $(document).on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'You want to delete this attendance record?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/hr/training-attendance/${id}`,
                    type: 'DELETE',
                    data: {_token: '{{ csrf_token() }}'},
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        let message = xhr.responseJSON?.message || 'Failed to delete attendance';
                        Swal.fire('Error!', message, 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush

