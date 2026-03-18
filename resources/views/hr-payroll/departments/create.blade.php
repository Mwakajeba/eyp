@extends('layouts.main')

@section('title', 'Create Department')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Departments', 'url' => route('hr.departments.index'), 'icon' => 'bx bx-buildings'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">
                <i class="bx bx-plus me-1"></i>Create New Department
            </h6>
        </div>
        <hr />

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bx bx-info-circle me-1"></i>Department Information
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <h6>Please correct the following errors:</h6>
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('hr.departments.store') }}">
                            @csrf
                            <div class="row g-3">
                                <!-- Department Name -->
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Department Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                           value="{{ old('name') }}" placeholder="Enter department name" required />
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Head of Department -->
                                <div class="col-md-6">
                                    <label for="hod" class="form-label">Head of Department</label>
                                    <input type="text" name="hod" id="hod" class="form-control @error('hod') is-invalid @enderror" 
                                           value="{{ old('hod') }}" placeholder="Enter HOD name" />
                                    @error('hod')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Optional: Name of the department head</div>
                                </div>

                                <!-- Parent Department -->
                                <div class="col-md-6">
                                    <label for="parent_id" class="form-label">Parent Department</label>
                                    <select name="parent_id" id="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                                        <option value="">Select Parent Department</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}" @selected(old('parent_id')==$dept->id)>
                                                {{ $dept->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('parent_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Optional: Select a parent department if this is a sub-department</div>
                                </div>

                                <!-- Description -->
                                <div class="col-12">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" 
                                              rows="4" placeholder="Enter department description...">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Optional: Brief description of the department's role and responsibilities</div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="col-12">
                                    <hr>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-check me-1"></i>Create Department
                                        </button>
                                        <a href="{{ route('hr.departments.index') }}" class="btn btn-outline-secondary">
                                            <i class="bx bx-x me-1"></i>Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar with tips -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bx bx-help-circle me-1"></i>Guidelines
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6 class="alert-heading">Department Setup Tips</h6>
                            <ul class="mb-0 small">
                                <li>Use clear, descriptive names</li>
                                <li>HOD field is optional but recommended</li>
                                <li>Parent departments create hierarchy</li>
                                <li>Description helps employees understand department roles</li>
                            </ul>
                        </div>
                        
                        <div class="mt-3">
                            <h6>Required Fields</h6>
                            <ul class="list-unstyled small">
                                <li><span class="text-danger">*</span> Department Name</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }

    .form-label {
        font-weight: 600;
        color: #495057;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
</style>
@endpush
