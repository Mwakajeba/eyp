@extends('layouts.main')

@section('title', 'Employee Transfers')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Transfers & Promotions', 'url' => '#', 'icon' => 'bx bx-transfer'],
                ['label' => 'Transfers', 'url' => '#', 'icon' => 'bx bx-move']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-transfer me-1"></i>Employee Transfers</h6>
                <a href="{{ route('hr.employee-transfers.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>New Transfer
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="employeeTransfersTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Transfer #</th>
                                    <th>Employee</th>
                                    <th>Employee #</th>
                                    <th>Transfer Type</th>
                                    <th>Transfer Details</th>
                                    <th>Effective Date</th>
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
    $('#employeeTransfersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.employee-transfers.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'transfer_number', name: 'transfer_number'},
            {data: 'employee_name', name: 'employee_name'},
            {data: 'employee_number', name: 'employee_number'},
            {data: 'transfer_type_badge', name: 'transfer_type', orderable: false},
            {data: 'transfer_details', name: 'transfer_details', orderable: false},
            {data: 'effective_date', name: 'effective_date'},
            {data: 'status_badge', name: 'status', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        order: [[1, 'desc']]
    });
});
</script>
@endpush

