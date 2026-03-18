@extends('layouts.main')

@section('title', 'Job Grades Management')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <!-- Breadcrumbs -->
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Job Grades', 'url' => '#', 'icon' => 'bx bx-layer']
            ]" />

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase">
                    <i class="bx bx-layer me-1"></i>Job Grades Management
                </h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.job-grades.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>Add Job Grade
                    </a>
                </div>
            </div>
            <hr />

            <!-- Job Grades Table Card -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="jobGradesTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Grade Code</th>
                                    <th>Grade Name</th>
                                    <th>Salary Range</th>
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

@push('css')
<style>
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }

    .table thead th {
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        color: #495057;
    }

    .btn-outline-primary:hover,
    .btn-outline-danger:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize DataTables
    let table = $('#jobGradesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.job-grades.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'grade_code', name: 'grade_code'},
            {data: 'grade_name', name: 'grade_name'},
            {data: 'salary_range', name: 'salary_range', orderable: false, searchable: false},
            {data: 'status_badge', name: 'is_active', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        language: {
            lengthMenu: "Show _MENU_ entries",
            search: "Search job grades:",
            searchPlaceholder: "Type to search...",
            processing: '<div class="d-flex justify-content-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>'
        },
        pageLength: 25,
        responsive: true,
        order: [[1, 'asc']]
    });

    // Delete Job Grade
    $(document).on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        
        Swal.fire({
            title: 'Delete Job Grade',
            text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ route('hr.job-grades.index') }}/${id}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message,
                                timer: 3000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = 'Something went wrong. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message
                        });
                    }
                });
            }
        });
    });
});
</script>
@endpush

