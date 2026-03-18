@extends('layouts.main')

@section('title', 'Manager Review - Imprest Requests')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Imprest Management', 'url' => route('imprest.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Manager Review', 'url' => '#', 'icon' => 'bx bx-user-check']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-primary">Manager Review - Pending Imprest Requests</h5>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="checkedRequestsTable" class="table table-striped table-hover" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th>Request #</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Purpose</th>
                                <th>Amount</th>
                                <th>Date Required</th>
                                <th>Created</th>
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
    $('#checkedRequestsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("imprest.checked.index") }}',
        columns: [
            {data: 'request_number', name: 'request_number'},
            {data: 'employee_name', name: 'employee.name'},
            {data: 'department_name', name: 'department.name'},
            {data: 'purpose', name: 'purpose'},
            {data: 'amount_formatted', name: 'amount_requested', className: 'text-end'},
            {data: 'date_required', name: 'date_required'},
            {data: 'created_at', name: 'created_at'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[6, 'desc']],
        pageLength: 25,
        responsive: true
    });
});
</script>
@endpush