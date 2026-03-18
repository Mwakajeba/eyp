@extends('layouts.main')

@section('title', 'Training Programs Management')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Training', 'url' => '#', 'icon' => 'bx bx-book'],
                ['label' => 'Programs', 'url' => '#', 'icon' => 'bx bx-book-open']
            ]" />
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase">
                    <i class="bx bx-book-open me-1"></i>Training Programs Management
                </h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.training-programs.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>Add Program
                    </a>
                </div>
            </div>
            <hr />
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="programsTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Program Code</th>
                                    <th>Program Name</th>
                                    <th>Provider</th>
                                    <th>Cost</th>
                                    <th>Duration</th>
                                    <th>Funding Source</th>
                                    <th>Attendance</th>
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
    let table = $('#programsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.training-programs.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'program_code', name: 'program_code'},
            {data: 'program_name', name: 'program_name'},
            {data: 'provider_badge', name: 'provider', orderable: false, searchable: false},
            {data: 'cost_display', name: 'cost', orderable: false, searchable: false},
            {data: 'duration_display', name: 'duration_days', orderable: false, searchable: false},
            {data: 'funding_source_badge', name: 'funding_source', orderable: false, searchable: false},
            {data: 'attendance_count', name: 'attendance_count', orderable: false, searchable: false},
            {data: 'status_badge', name: 'is_active', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        language: {
            lengthMenu: "Show _MENU_ entries",
            search: "Search programs:",
            processing: '<div class="d-flex justify-content-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>'
        },
        pageLength: 25,
        responsive: true,
        order: [[1, 'asc']]
    });

    $(document).on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        
        Swal.fire({
            title: 'Are you sure?',
            text: `You want to delete program: ${name}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/hr/training-programs/${id}`,
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
                        let message = xhr.responseJSON?.message || 'Failed to delete program';
                        Swal.fire('Error!', message, 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush

