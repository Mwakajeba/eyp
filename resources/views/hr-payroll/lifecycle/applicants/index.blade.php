@extends('layouts.main')

@section('title', 'Applicants')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Applicants', 'url' => '#', 'icon' => 'bx bx-user-plus']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="mb-0 text-uppercase">
                        <i class="bx bx-user-plus me-1"></i>
                        @if(isset($filteredVacancy))
                            Applicants for: {{ $filteredVacancy->job_title }} ({{ $filteredVacancy->requisition_number }})
                        @else
                            Applicants
                        @endif
                    </h6>
                    @if(isset($filteredVacancy))
                        <small class="text-muted">Showing only applicants for this vacancy requisition.</small>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    @if(isset($filteredVacancy))
                        <a href="{{ route('hr.applicants.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Clear Filter
                        </a>
                    @endif
                    <a href="{{ route('hr.applicants.create', isset($filteredVacancy) ? ['vacancy_id' => $filteredVacancy->id] : []) }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>New Applicant
                    </a>
                </div>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="applicantsTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Application #</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Vacancy</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                    <th>Converted</th>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let table = $('#applicantsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.applicants.index', request()->query()) }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'application_number', name: 'application_number'},
            {data: 'applicant_name', name: 'applicant_name'},
            {data: 'email', name: 'email'},
            {data: 'vacancy_title', name: 'vacancy_title'},
            {data: 'score', name: 'total_eligibility_score', className: 'text-center'},
            {data: 'status_badge', name: 'status', orderable: false},
            {data: 'converted_badge', name: 'converted_badge', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        order: [[1, 'desc']]
    });

    $(document).on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('hr-payroll/applicants') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    },
                    success: function(response) {
                        if(response.success) {
                            table.draw();
                            Swal.fire('Deleted!', response.message, 'success');
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    }
                });
            }
        });
    });

    $(document).on('click', '.shortlist-btn', function() {
        let id = $(this).data('id');
        
        Swal.fire({
            title: 'Shortlist Candidate?',
            text: "This will move the candidate to the shortlist for panel review.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, shortlist!'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Processing...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: "{{ url('hr-payroll/applicants') }}/" + id + "/shortlist",
                    type: 'POST',
                    data: {_token: '{{ csrf_token() }}'},
                    success: function(response) {
                        if(response.success) {
                            table.draw();
                            Swal.fire('Shortlisted!', response.message, 'success');
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'An unexpected error occurred.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush

