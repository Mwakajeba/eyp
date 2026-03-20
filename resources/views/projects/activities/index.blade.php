@extends('layouts.main')

@section('title', 'Project Activities')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Project Management', 'url' => route('projects.index'), 'icon' => 'bx bx-briefcase'],
            ['label' => 'Project Activities', 'url' => '#', 'icon' => 'bx bx-task']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">Project Activities</h6>
            <div class="d-flex gap-2">
                <a href="{{ route('projects.index') }}" class="btn btn-light">Back to Dashboard</a>
                <a href="{{ route('projects.activities.create') }}" class="btn btn-success">
                    <i class="bx bx-plus me-1"></i>New Activity
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle" id="project-activities-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Project</th>
                                <th>Activity Code</th>
                                <th>Description</th>
                                <th class="text-end">Budget Amount</th>
                                <th class="text-end">Sub Activities Total</th>
                                <th>Created By</th>
                                <th>Created</th>
                                <th class="text-center">Actions</th>
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
    $('#project-activities-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('projects.activities.index') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'project_display', name: 'project.project_code' },
            { data: 'activity_code', name: 'activity_code' },
            { data: 'description', name: 'description' },
            { data: 'budget_amount_formatted', name: 'budget_amount', className: 'text-end' },
            { data: 'sub_activities_total', name: 'sub_activities_total', className: 'text-end', orderable: false, searchable: false },
            { data: 'created_by_name', name: 'creator.name' },
            { data: 'created_at_formatted', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[6, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            emptyTable: 'We have not create activity yet.<div class="mt-3"><a href="{{ route('projects.activities.create') }}" class="btn btn-success btn-sm"><i class="bx bx-plus me-1"></i>Create Now</a></div>',
            zeroRecords: 'We have not create activity yet.<div class="mt-3"><a href="{{ route('projects.activities.create') }}" class="btn btn-success btn-sm"><i class="bx bx-plus me-1"></i>Create Now</a></div>'
        }
    });
});
</script>
@endpush