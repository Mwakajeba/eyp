@extends('layouts.main')

@section('title', 'Disciplinary Cases')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Discipline', 'url' => '#', 'icon' => 'bx bx-shield-quarter'],
                ['label' => 'Disciplinary Cases', 'url' => '#', 'icon' => 'bx bx-file']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-file me-1"></i>Disciplinary Cases</h6>
                <a href="{{ route('hr.disciplinary-cases.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>New Case
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="disciplinaryCasesTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Case #</th>
                                    <th>Employee</th>
                                    <th>Employee #</th>
                                    <th>Category</th>
                                    <th>Incident Date</th>
                                    <th>Status</th>
                                    <th>Outcome</th>
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
    $('#disciplinaryCasesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.disciplinary-cases.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'case_number', name: 'case_number'},
            {data: 'employee_name', name: 'employee_name'},
            {data: 'employee_number', name: 'employee_number'},
            {data: 'case_category_badge', name: 'case_category', orderable: false},
            {data: 'incident_date', name: 'incident_date'},
            {data: 'status_badge', name: 'status', orderable: false},
            {data: 'outcome_badge', name: 'outcome', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        order: [[1, 'desc']]
    });
});
</script>
@endpush

