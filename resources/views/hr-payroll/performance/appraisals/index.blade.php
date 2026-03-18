@extends('layouts.main')

@section('title', 'Appraisals Management')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Performance', 'url' => '#', 'icon' => 'bx bx-trophy'],
                ['label' => 'Appraisals', 'url' => '#', 'icon' => 'bx bx-clipboard']
            ]" />
            
            @if(isset($stats))
            <!-- Statistics Cards -->
            <div class="row row-cols-1 row-cols-md-4 g-3 mb-4">
                <div class="col">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1">Total Appraisals</h6>
                                    <h4 class="mb-0">{{ $stats['total'] }}</h4>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="bx bx-clipboard fs-1 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1">Draft</h6>
                                    <h4 class="mb-0">{{ $stats['draft'] }}</h4>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="bx bx-edit fs-1 text-secondary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1">Submitted</h6>
                                    <h4 class="mb-0">{{ $stats['submitted'] }}</h4>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="bx bx-paper-plane fs-1 text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-muted mb-1">Approved</h6>
                                    <h4 class="mb-0">{{ $stats['approved'] }}</h4>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="bx bx-check-circle fs-1 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase">
                    <i class="bx bx-clipboard me-1"></i>Appraisals Management
                </h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.appraisals.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>Add Appraisal
                    </a>
                </div>
            </div>
            <hr />
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="appraisalsTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Employee</th>
                                    <th>Employee #</th>
                                    <th>Cycle</th>
                                    <th>Appraiser</th>
                                    <th>Final Score</th>
                                    <th>Rating</th>
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
    let table = $('#appraisalsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.appraisals.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'employee_name', name: 'employee_name', orderable: false},
            {data: 'employee_number', name: 'employee_number', orderable: false},
            {data: 'cycle_name', name: 'cycle_name', orderable: false},
            {data: 'appraiser_name', name: 'appraiser_name', orderable: false},
            {data: 'final_score_display', name: 'final_score', orderable: false, searchable: false},
            {data: 'rating_badge', name: 'rating', orderable: false, searchable: false},
            {data: 'status_badge', name: 'status', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        language: {
            lengthMenu: "Show _MENU_ entries",
            search: "Search appraisals:",
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
            text: 'You want to delete this appraisal?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/hr/appraisals/${id}`,
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
                        let message = xhr.responseJSON?.message || 'Failed to delete appraisal';
                        Swal.fire('Error!', message, 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush

