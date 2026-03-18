@extends('layouts.main')

@section('title', 'Create Overtime Rule')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Overtime Rules', 'url' => route('hr.overtime-rules.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">Create Overtime Rule</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.overtime-rules.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Job Grade</label>
                            <select name="grade_id" class="form-select @error('grade_id') is-invalid @enderror">
                                <option value="">All Grades</option>
                                @foreach($jobGrades as $grade)
                                    <option value="{{ $grade->id }}" {{ old('grade_id') == $grade->id ? 'selected' : '' }}>
                                        {{ $grade->grade_name }} ({{ $grade->grade_code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('grade_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Leave blank to apply to all grades</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Day Type <span class="text-danger">*</span></label>
                            <select name="day_type" class="form-select @error('day_type') is-invalid @enderror" required>
                                <option value="">Select Day Type</option>
                                <option value="weekday" {{ old('day_type') == 'weekday' ? 'selected' : '' }}>Weekday (Monday - Friday)</option>
                                <option value="weekend" {{ old('day_type') == 'weekend' ? 'selected' : '' }}>Weekend (Saturday - Sunday)</option>
                                <option value="holiday" {{ old('day_type') == 'holiday' ? 'selected' : '' }}>Holiday</option>
                            </select>
                            @error('day_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Overtime Rate <span class="text-danger">*</span></label>
                            <input type="number" name="overtime_rate" step="0.01" min="1" max="5" 
                                   class="form-control @error('overtime_rate') is-invalid @enderror" 
                                   value="{{ old('overtime_rate', 1.50) }}" required placeholder="1.50" />
                            @error('overtime_rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Multiplier for hourly rate (e.g., 1.50 = 1.5x, 2.00 = 2x)</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Max Hours Per Day</label>
                            <input type="number" name="max_hours_per_day" step="0.01" min="0" max="24" 
                                   class="form-control @error('max_hours_per_day') is-invalid @enderror" 
                                   value="{{ old('max_hours_per_day') }}" placeholder="Leave blank for no limit" />
                            @error('max_hours_per_day')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Maximum overtime hours allowed per day</div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="requires_approval" id="requires_approval" 
                                       value="1" {{ old('requires_approval', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="requires_approval">
                                    Requires Approval
                                </label>
                            </div>
                            <div class="form-text">Overtime requests must be approved before being processed</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                      rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                       value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Save Overtime Rule
                        </button>
                        <a href="{{ route('hr.overtime-rules.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

