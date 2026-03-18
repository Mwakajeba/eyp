@extends('layouts.main')

@section('title', 'Edit Job Grade')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Job Grades', 'url' => route('hr.job-grades.index'), 'icon' => 'bx bx-layer'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">Edit Job Grade</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.job-grades.update', $jobGrade->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Grade Code <span class="text-danger">*</span></label>
                            <input type="text" name="grade_code" class="form-control @error('grade_code') is-invalid @enderror" 
                                   value="{{ old('grade_code', $jobGrade->grade_code) }}" required />
                            @error('grade_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Grade Name <span class="text-danger">*</span></label>
                            <input type="text" name="grade_name" class="form-control @error('grade_name') is-invalid @enderror" 
                                   value="{{ old('grade_name', $jobGrade->grade_name) }}" required />
                            @error('grade_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Minimum Salary</label>
                            <input type="number" name="minimum_salary" step="0.01" min="0" 
                                   class="form-control @error('minimum_salary') is-invalid @enderror" 
                                   value="{{ old('minimum_salary', $jobGrade->minimum_salary) }}" />
                            @error('minimum_salary')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Midpoint Salary</label>
                            <input type="number" name="midpoint_salary" step="0.01" min="0" 
                                   class="form-control @error('midpoint_salary') is-invalid @enderror" 
                                   value="{{ old('midpoint_salary', $jobGrade->midpoint_salary) }}" />
                            @error('midpoint_salary')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Maximum Salary</label>
                            <input type="number" name="maximum_salary" step="0.01" min="0" 
                                   class="form-control @error('maximum_salary') is-invalid @enderror" 
                                   value="{{ old('maximum_salary', $jobGrade->maximum_salary) }}" />
                            @error('maximum_salary')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                       value="1" {{ old('is_active', $jobGrade->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Update Job Grade
                        </button>
                        <a href="{{ route('hr.job-grades.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

