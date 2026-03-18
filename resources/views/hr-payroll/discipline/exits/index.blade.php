@extends('layouts.main')

@section('title', 'Exit Management')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Discipline', 'url' => '#', 'icon' => 'bx bx-shield-quarter'],
                ['label' => 'Exit Management', 'url' => '#', 'icon' => 'bx bx-log-out']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-log-out me-1"></i>Exit Management</h6>
                <a href="{{ route('hr.exits.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>New Exit
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="exitsTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Exit #</th>
                                    <th>Employee</th>
                                    <th>Employee #</th>
                                    <th>Exit Type</th>
                                    <th>Effective Date</th>
                                    <th>Clearance Status</th>
                                    <th>Final Pay Status</th>
                                    <th>Final Pay Amount</th>
                                    <th>Clearance Progress</th>
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
    $('#exitsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.exits.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'exit_number', name: 'exit_number'},
            {data: 'employee_name', name: 'employee_name'},
            {data: 'employee_number', name: 'employee_number'},
            {data: 'exit_type_badge', name: 'exit_type', orderable: false},
            {data: 'effective_date', name: 'effective_date'},
            {data: 'clearance_status_badge', name: 'clearance_status', orderable: false},
            {data: 'final_pay_status_badge', name: 'final_pay_status', orderable: false},
            {data: 'final_pay_display', name: 'final_pay_amount'},
            {data: 'clearance_progress', name: 'clearance_progress', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        order: [[1, 'desc']]
    });
});
</script>
@endpush

