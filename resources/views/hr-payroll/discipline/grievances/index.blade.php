@extends('layouts.main')

@section('title', 'Grievances')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Discipline', 'url' => '#', 'icon' => 'bx bx-shield-quarter'],
                ['label' => 'Grievances', 'url' => '#', 'icon' => 'bx bx-error']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-error me-1"></i>Grievances</h6>
                <a href="{{ route('hr.grievances.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>New Grievance
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="grievancesTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Grievance #</th>
                                    <th>Employee</th>
                                    <th>Employee #</th>
                                    <th>Complaint Type</th>
                                    <th>Priority</th>
                                    <th>Assigned To</th>
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
    $('#grievancesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.grievances.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'grievance_number', name: 'grievance_number'},
            {data: 'employee_name', name: 'employee_name'},
            {data: 'employee_number', name: 'employee_number'},
            {data: 'complaint_type_badge', name: 'complaint_type', orderable: false},
            {data: 'priority_badge', name: 'priority', orderable: false},
            {data: 'assigned_to_name', name: 'assigned_to_name'},
            {data: 'status_badge', name: 'status', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        order: [[1, 'desc']]
    });
});
</script>
@endpush

