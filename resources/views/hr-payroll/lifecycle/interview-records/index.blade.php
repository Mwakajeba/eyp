@extends('layouts.main')

@section('title', 'Interview Records')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Interview Records', 'url' => '#', 'icon' => 'bx bx-conversation']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-conversation me-1"></i>Interview Records</h6>
                <a href="{{ route('hr.interview-records.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>New Interview
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="interviewRecordsTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Applicant</th>
                                    <th>Vacancy</th>
                                    <th>Interview Date & Time</th>
                                    <th>Type</th>
                                    <th>Round</th>
                                    <th>Score</th>
                                    <th>Recommendation</th>
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
    $('#interviewRecordsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.interview-records.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'applicant_name', name: 'applicant_name'},
            {data: 'vacancy_title', name: 'vacancy_title'},
            {data: 'interview_datetime', name: 'interview_datetime'},
            {data: 'interview_type_badge', name: 'interview_type', orderable: false},
            {data: 'round_number', name: 'round_number'},
            {data: 'overall_score', name: 'overall_score'},
            {data: 'recommendation_badge', name: 'recommendation', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        order: [[3, 'desc']]
    });
});
</script>
@endpush

