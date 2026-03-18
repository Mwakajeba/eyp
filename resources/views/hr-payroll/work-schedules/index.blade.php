@extends('layouts.main')

@section('title', 'Work Schedules Management')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Work Schedules', 'url' => '#', 'icon' => 'bx bx-calendar-week']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase">
                    <i class="bx bx-calendar-week me-1"></i>Work Schedules Management
                </h6>
                <a href="{{ route('hr.work-schedules.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Add Work Schedule
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="workSchedulesTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Schedule Code</th>
                                    <th>Schedule Name</th>
                                    <th>Weekly Pattern</th>
                                    <th>Daily Hours</th>
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
    let table = $('#workSchedulesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.work-schedules.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'schedule_code', name: 'schedule_code'},
            {data: 'schedule_name', name: 'schedule_name'},
            {data: 'weekly_pattern_display', name: 'weekly_pattern_display', orderable: false, searchable: false},
            {data: 'standard_daily_hours', name: 'standard_daily_hours'},
            {data: 'status_badge', name: 'is_active', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        pageLength: 25,
        responsive: true,
        order: [[1, 'asc']]
    });

    $(document).on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        
        Swal.fire({
            title: 'Delete Work Schedule',
            text: `Are you sure you want to delete "${name}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ route('hr.work-schedules.index') }}/${id}`,
                    type: 'DELETE',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            Swal.fire({icon: 'success', title: 'Deleted!', text: response.message, timer: 3000, showConfirmButton: false});
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Something went wrong.'});
                    }
                });
            }
        });
    });
});
</script>
@endpush

