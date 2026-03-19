@extends('layouts.main')

@section('title', 'Edit Project')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Project Management', 'url' => route('projects.index'), 'icon' => 'bx bx-briefcase'],
            ['label' => 'Projects', 'url' => route('projects.project.index'), 'icon' => 'bx bx-folder'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <h6 class="mb-0 text-uppercase">Edit Project</h6>
        <hr>

        @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('projects.project.update', $project->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Project Code</label>
                            <input type="text" class="form-control" name="project_code" value="{{ old('project_code', $project->project_code) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type" required>
                                <option value="INTERNAL" @selected(old('type', $project->type) === 'INTERNAL')>Internal</option>
                                <option value="DONOR" @selected(old('type', $project->type) === 'DONOR')>Donor</option>
                                <option value="EXTERNAL" @selected(old('type', $project->type) === 'EXTERNAL')>External</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="draft" @selected(old('status', $project->status) === 'draft')>Draft</option>
                                <option value="active" @selected(old('status', $project->status) === 'active')>Active</option>
                                <option value="on_hold" @selected(old('status', $project->status) === 'on_hold')>On Hold</option>
                                <option value="closed" @selected(old('status', $project->status) === 'closed')>Closed</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Project Name</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $project->name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="{{ old('start_date', optional($project->start_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" value="{{ old('end_date', optional($project->end_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Currency</label>
                            <input type="text" class="form-control" name="currency_code" value="{{ old('currency_code', $project->currency_code) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Budget Total</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="budget_total" value="{{ old('budget_total', $project->budget_total) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="4" name="description">{{ old('description', $project->description) }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Update Project</button>
                        <a href="{{ route('projects.project.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
