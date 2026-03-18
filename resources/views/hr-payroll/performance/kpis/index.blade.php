@extends('layouts.main')

@section('title', 'KPIs Management')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <!-- Breadcrumbs -->
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Performance', 'url' => '#', 'icon' => 'bx bx-trophy'],
                ['label' => 'KPIs', 'url' => '#', 'icon' => 'bx bx-target-lock']
            ]" />

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase">
                    <i class="bx bx-target-lock me-1"></i>KPIs Management
                </h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.kpis.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>Add KPI
                    </a>
                </div>
            </div>
            <hr />

            <!-- KPIs Table Card -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="kpisTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>KPI Code</th>
                                    <th>KPI Name</th>
                                    <th>Weight (%)</th>
                                    <th>Target Value</th>
                                    <th>Scoring Method</th>
                                    <th>Applicable To</th>
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
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let table = $('#kpisTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.kpis.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'kpi_code', name: 'kpi_code'},
            {data: 'kpi_name', name: 'kpi_name'},
            {data: 'weight_display', name: 'weight_percent', orderable: false, searchable: false},
            {data: 'target_display', name: 'target_value', orderable: false, searchable: false},
            {data: 'scoring_method_badge', name: 'scoring_method', orderable: false, searchable: false},
            {data: 'applicable_to_badge', name: 'applicable_to', orderable: false, searchable: false},
            {data: 'status_badge', name: 'is_active', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        language: {
            lengthMenu: "Show _MENU_ entries",
            search: "Search KPIs:",
            processing: '<div class="d-flex justify-content-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>'
        },
        pageLength: 25,
        responsive: true,
        order: [[1, 'asc']]
    });

    // Delete handler
    $(document).on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        
        Swal.fire({
            title: 'Are you sure?',
            text: `You want to delete KPI: ${name}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/hr/kpis/${id}`,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        let message = xhr.responseJSON?.message || 'Failed to delete KPI';
                        Swal.fire('Error!', message, 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush

