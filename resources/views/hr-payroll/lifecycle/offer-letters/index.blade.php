@extends('layouts.main')

@section('title', 'Offer Letters')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Offer Letters', 'url' => '#', 'icon' => 'bx bx-envelope']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-envelope me-1"></i>Offer Letters</h6>
                <a href="{{ route('hr.offer-letters.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>New Offer Letter
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="offerLettersTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Offer #</th>
                                    <th>Applicant</th>
                                    <th>Vacancy</th>
                                    <th>Offered Salary</th>
                                    <th>Expiry Date</th>
                                    <th>Expiry Status</th>
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
    $('#offerLettersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.offer-letters.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'offer_number', name: 'offer_number'},
            {data: 'applicant_name', name: 'applicant_name'},
            {data: 'vacancy_title', name: 'vacancy_title'},
            {data: 'offered_salary_display', name: 'offered_salary'},
            {data: 'expiry_date', name: 'expiry_date'},
            {data: 'expiry_status', name: 'expiry_status', orderable: false},
            {data: 'status_badge', name: 'status', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        order: [[1, 'desc']]
    });
});
</script>
@endpush

