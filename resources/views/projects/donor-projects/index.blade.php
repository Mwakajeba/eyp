@extends('layouts.main')

@section('title', 'Donor Projects')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Project Management', 'url' => route('projects.index'), 'icon' => 'bx bx-briefcase'],
            ['label' => 'Donor Projects', 'url' => '#', 'icon' => 'bx bx-folder']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">Donor Projects</h6>
            <div class="d-flex gap-2">
                <a href="{{ route('projects.donor-assignments.create') }}" class="btn btn-info">
                    <i class="bx bx-link me-1"></i>Assign Donor
                </a>
                <a href="{{ route('projects.donor-projects.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>New Donor Project
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
                    <table class="table table-striped table-bordered" id="donor-projects-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Project Code</th>
                                <th>Project Name</th>
                                <th>Status</th>
                                <th>Budget</th>
                                <th>Donor Customers</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($donorProjects as $index => $project)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $project->project_code }}</td>
                                <td>{{ $project->name }}</td>
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
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No donor projects created yet.</td>
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
