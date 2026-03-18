@extends('layouts.main')

@section('title', 'Onboarding Records')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Onboarding', 'url' => '#', 'icon' => 'bx bx-list-check'],
                ['label' => 'Records', 'url' => '#', 'icon' => 'bx bx-user-check']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-user-check me-1"></i>Onboarding Records</h6>
                <a href="{{ route('hr.onboarding-records.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>New Record
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="onboardingRecordsTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Employee</th>
                                    <th>Employee #</th>
                                    <th>Checklist</th>
                                    <th>Progress</th>
                                    <th>Completion</th>
                                    <th>Payroll Eligible</th>
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
    $('#onboardingRecordsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.onboarding-records.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'employee_name', name: 'employee_name'},
            {data: 'employee_number', name: 'employee_number'},
            {data: 'checklist_name', name: 'checklist_name'},
            {data: 'progress_display', name: 'progress_percent', orderable: false},
            {data: 'completion_status', name: 'completion_status', orderable: false},
            {data: 'payroll_eligible_badge', name: 'payroll_eligible', orderable: false},
            {data: 'status_badge', name: 'status', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        order: [[1, 'desc']]
    });
});
</script>
@endpush

