@extends('layouts.main')

@section('title', 'Holiday Calendars Management')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Holiday Calendars', 'url' => '#', 'icon' => 'bx bx-calendar-heart']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase">
                    <i class="bx bx-calendar-heart me-1"></i>Holiday Calendars Management
                </h6>
                <a href="{{ route('hr.holiday-calendars.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Add Calendar
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="holidayCalendarsTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Calendar Name</th>
                                    <th>Holidays Count</th>
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
    let table = $('#holidayCalendarsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.holiday-calendars.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'calendar_name', name: 'calendar_name'},
            {data: 'holidays_count', name: 'holidays_count', orderable: false, searchable: false},
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
            title: 'Delete Holiday Calendar',
            text: `Are you sure you want to delete "${name}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ route('hr.holiday-calendars.index') }}/${id}`,
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

