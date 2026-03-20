@extends('layouts.main')

@section('title', 'Projects')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Project Management', 'url' => route('projects.index'), 'icon' => 'bx bx-briefcase'],
            ['label' => 'Projects', 'url' => '#', 'icon' => 'bx bx-folder']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">Projects</h6>
            <div class="d-flex gap-2">
                <a href="{{ route('projects.donor-assignments.create') }}" class="btn btn-info">
                    <i class="bx bx-link me-1"></i>Assign Donor
                </a>
                <a href="{{ route('projects.project.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>New Project
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
                    <table class="table table-striped table-bordered" id="projects-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Project Code</th>
                                <th>Project Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Budget</th>
                                <th>Donor Customers</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projects as $index => $project)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $project->project_code }}</td>
                                <td>{{ $project->name }}</td>
                                <td><span class="badge bg-dark">{{ ucfirst(strtolower($project->type)) }}</span></td>
                                <td><span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $project->status)) }}</span></td>
                                <td>{{ number_format((float) $project->budget_total, 2) }} {{ $project->currency_code }}</td>
                                <td>
                                    @if($project->donors->count())
                                        @foreach($project->donors as $donor)
                                            <span class="badge bg-secondary me-1">{{ $donor->name }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">No donor customer assigned</span>
                                    @endif
                                </td>
                                <td>{{ optional($project->created_at)->format('d M Y') }}</td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button type="button"
                                                class="btn btn-sm btn-info text-nowrap"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#project-activities-{{ $project->id }}"
                                                aria-expanded="false"
                                                {{ $project->activities->isEmpty() ? 'disabled' : '' }}>
                                            <i class="bx bx-chevron-right me-1 transition-icon"></i>
                                            View Activities
                                        </button>
                                        <a href="{{ route('projects.project.edit', $project->id) }}" class="btn btn-sm btn-warning">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('projects.project.destroy', $project->id) }}" onsubmit="return confirm('Delete this project?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr id="project-activities-{{ $project->id }}" class="collapse bg-light" data-bs-parent="#projects-table tbody">
                                <td colspan="9">
                                    <div class="p-2">
                                        @if($project->activities->isEmpty())
                                            <span class="text-muted">No activities found for this project.</span>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 180px;">Activity Code</th>
                                                            <th>Description</th>
                                                            <th style="width: 160px;" class="text-end">Sub Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($project->activities as $activity)
                                                            <tr class="table-secondary">
                                                                <td class="fw-semibold">{{ $activity->activity_code }}</td>
                                                                <td>{{ $activity->description }}</td>
                                                                <td class="text-end">{{ number_format((float) $activity->subActivities->sum('amount'), 2) }}</td>
                                                            </tr>
                                                            @forelse($activity->subActivities as $subActivity)
                                                                <tr>
                                                                    <td></td>
                                                                    <td>
                                                                        <span class="text-muted me-2">&#8627;</span>
                                                                        {{ $subActivity->sub_activity_name }}
                                                                    </td>
                                                                    <td class="text-end">{{ number_format((float) $subActivity->amount, 2) }}</td>
                                                                </tr>
                                                            @empty
                                                                <tr>
                                                                    <td></td>
                                                                    <td class="text-muted">No sub activities</td>
                                                                    <td class="text-end">0.00</td>
                                                                </tr>
                                                            @endforelse
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No projects created yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .transition-icon {
        transition: transform 0.2s ease;
    }

    [aria-expanded="true"] .transition-icon {
        transform: rotate(90deg);
    }
</style>
@endpush
