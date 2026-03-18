@extends('layouts.main')

@section('title', 'Confirmation Requests')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Confirmation', 'url' => '#', 'icon' => 'bx bx-check-circle'],
                ['label' => 'Requests', 'url' => '#', 'icon' => 'bx bx-file']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-check-circle me-1"></i>Confirmation Requests</h6>
                <a href="{{ route('hr.confirmation-requests.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>New Request
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="confirmationRequestsTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Employee</th>
                                    <th>Employee #</th>
                                    <th>Probation Period</th>
                                    <th>Recommendation</th>
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
    $('#confirmationRequestsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.confirmation-requests.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'employee_name', name: 'employee_name'},
            {data: 'employee_number', name: 'employee_number'},
            {data: 'probation_period', name: 'probation_period'},
            {data: 'recommendation_badge', name: 'recommendation_type', orderable: false},
            {data: 'status_badge', name: 'status', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        order: [[1, 'desc']]
    });
});
</script>
@endpush

