@extends('layouts.main')

@section('title', 'Create Donor Project')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Project Management', 'url' => route('projects.index'), 'icon' => 'bx bx-briefcase'],
            ['label' => 'Donor Projects', 'url' => route('projects.donor-projects.index'), 'icon' => 'bx bx-folder'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <h6 class="mb-0 text-uppercase">Create Donor Project</h6>
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
                <form method="POST" action="{{ route('projects.donor-projects.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Project Code</label>
                            <input type="text" class="form-control" name="project_code" value="{{ old('project_code') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="draft" @selected(old('status') === 'draft')>Draft</option>
                                <option value="active" @selected(old('status') === 'active')>Active</option>
                                <option value="on_hold" @selected(old('status') === 'on_hold')>On Hold</option>
                                <option value="closed" @selected(old('status') === 'closed')>Closed</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Currency</label>
                            <input type="text" class="form-control" name="currency_code" value="{{ old('currency_code', 'TZS') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Project Name</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="{{ old('start_date') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" value="{{ old('end_date') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Budget Total</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="budget_total" value="{{ old('budget_total', 0) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="4" name="description">{{ old('description') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Create Project</button>
                        <a href="{{ route('projects.donor-projects.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
