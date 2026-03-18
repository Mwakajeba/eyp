@extends('layouts.main')

@section('title', 'Edit Overtime Rule')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Overtime Rules', 'url' => route('hr.overtime-rules.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">Edit Overtime Rule</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.overtime-rules.update', $overtimeRule->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Job Grade</label>
                            <select name="grade_id" class="form-select @error('grade_id') is-invalid @enderror">
                                <option value="">All Grades</option>
                                @foreach($jobGrades as $grade)
                                    <option value="{{ $grade->id }}" {{ old('grade_id', $overtimeRule->grade_id) == $grade->id ? 'selected' : '' }}>
                                        {{ $grade->grade_name }} ({{ $grade->grade_code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('grade_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Day Type <span class="text-danger">*</span></label>
                            <select name="day_type" class="form-select @error('day_type') is-invalid @enderror" required>
                                <option value="weekday" {{ old('day_type', $overtimeRule->day_type) == 'weekday' ? 'selected' : '' }}>Weekday</option>
                                <option value="weekend" {{ old('day_type', $overtimeRule->day_type) == 'weekend' ? 'selected' : '' }}>Weekend</option>
                                <option value="holiday" {{ old('day_type', $overtimeRule->day_type) == 'holiday' ? 'selected' : '' }}>Holiday</option>
                            </select>
                            @error('day_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Overtime Rate <span class="text-danger">*</span></label>
                            <input type="number" name="overtime_rate" step="0.01" min="1" max="5" 
                                   class="form-control @error('overtime_rate') is-invalid @enderror" 
                                   value="{{ old('overtime_rate', $overtimeRule->overtime_rate) }}" required />
                            @error('overtime_rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Max Hours Per Day</label>
                            <input type="number" name="max_hours_per_day" step="0.01" min="0" max="24" 
                                   class="form-control @error('max_hours_per_day') is-invalid @enderror" 
                                   value="{{ old('max_hours_per_day', $overtimeRule->max_hours_per_day) }}" />
                            @error('max_hours_per_day')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="requires_approval" id="requires_approval" 
                                       value="1" {{ old('requires_approval', $overtimeRule->requires_approval) ? 'checked' : '' }}>
                                <label class="form-check-label" for="requires_approval">
                                    Requires Approval
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                      rows="3">{{ old('description', $overtimeRule->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                       value="1" {{ old('is_active', $overtimeRule->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Update Overtime Rule
                        </button>
                        <a href="{{ route('hr.overtime-rules.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

