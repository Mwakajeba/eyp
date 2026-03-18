@extends('layouts.main')

@section('title', 'Employee Promotions')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Transfers & Promotions', 'url' => '#', 'icon' => 'bx bx-transfer'],
                ['label' => 'Promotions', 'url' => '#', 'icon' => 'bx bx-trophy']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-trophy me-1"></i>Employee Promotions</h6>
                <a href="{{ route('hr.employee-promotions.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>New Promotion
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="employeePromotionsTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Promotion #</th>
                                    <th>Employee</th>
                                    <th>Employee #</th>
                                    <th>Promotion Details</th>
                                    <th>Salary Increment</th>
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
    $('#employeePromotionsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.employee-promotions.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'promotion_number', name: 'promotion_number'},
            {data: 'employee_name', name: 'employee_name'},
            {data: 'employee_number', name: 'employee_number'},
            {data: 'promotion_details', name: 'promotion_details', orderable: false},
            {data: 'increment_display', name: 'salary_increment', orderable: false},
            {data: 'effective_date', name: 'effective_date'},
            {data: 'status_badge', name: 'status', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        order: [[1, 'desc']]
    });
});
</script>
@endpush

