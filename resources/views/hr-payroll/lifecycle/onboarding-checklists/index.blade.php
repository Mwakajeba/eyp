@extends('layouts.main')

@section('title', 'Onboarding Checklists')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Onboarding', 'url' => '#', 'icon' => 'bx bx-list-check'],
                ['label' => 'Checklists', 'url' => '#', 'icon' => 'bx bx-check-square']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-list-check me-1"></i>Onboarding Checklists</h6>
                <a href="{{ route('hr.onboarding-checklists.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>New Checklist
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="onboardingChecklistsTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Checklist Name</th>
                                    <th>Applicable To</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Items</th>
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
    $('#onboardingChecklistsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.onboarding-checklists.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'checklist_name', name: 'checklist_name'},
            {data: 'applicable_to_badge', name: 'applicable_to', orderable: false},
            {data: 'department_name', name: 'department_name'},
            {data: 'position_name', name: 'position_name'},
            {data: 'items_count', name: 'items_count', orderable: false},
            {data: 'status_badge', name: 'status', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        order: [[1, 'asc']]
    });
});
</script>
@endpush

