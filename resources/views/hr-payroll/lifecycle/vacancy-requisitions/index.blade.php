@extends('layouts.main')

@section('title', 'Vacancy Requisitions')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Vacancy Requisitions', 'url' => '#', 'icon' => 'bx bx-file-blank']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-file-blank me-1"></i>Vacancy Requisitions</h6>
                <a href="{{ route('hr.vacancy-requisitions.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>New Requisition
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="vacancyRequisitionsTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Requisition #</th>
                                    <th>Job Title</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Positions</th>
                                    <th>Salary Range</th>
                                    <th>Status</th>
                                    <th>Applicants</th>
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
    let table = $('#vacancyRequisitionsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.vacancy-requisitions.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'requisition_number', name: 'requisition_number'},
            {data: 'job_title', name: 'job_title'},
            {data: 'position_name', name: 'position_name'},
            {data: 'department_name', name: 'department_name'},
            {data: 'number_of_positions', name: 'number_of_positions'},
            {data: 'salary_range', name: 'salary_range', orderable: false},
            {data: 'status_badge', name: 'status', orderable: false},
            {data: 'applicants_count', name: 'applicants_count', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        order: [[1, 'desc']]
    });

    $(document).on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        let name = $(this).data('name') || 'this item';
        if(confirm('Are you sure you want to delete ' + name + '?')) {
            $.ajax({
                url: "{{ url('hr-payroll/vacancy-requisitions') }}/" + id,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE'
                },
                success: function(response) {
                    if(response.success) {
                        table.draw();
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    toastr.error(response?.message || 'An error occurred while deleting the requisition.');
                }
            });
        }
    });
});
</script>
@endpush

