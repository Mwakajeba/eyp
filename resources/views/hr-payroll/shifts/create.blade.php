@extends('layouts.main')

@section('title', 'Create Shift')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Shifts', 'url' => route('hr.shifts.index'), 'icon' => 'bx bx-time-five'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">Create Shift</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.shifts.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Shift Code <span class="text-danger">*</span></label>
                            <input type="text" name="shift_code" class="form-control @error('shift_code') is-invalid @enderror" 
                                   value="{{ old('shift_code') }}" required placeholder="e.g., DAY, NIGHT, MORNING" />
                            @error('shift_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Unique code for this shift</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Shift Name <span class="text-danger">*</span></label>
                            <input type="text" name="shift_name" class="form-control @error('shift_name') is-invalid @enderror" 
                                   value="{{ old('shift_name') }}" required placeholder="e.g., Day Shift, Night Shift" />
                            @error('shift_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" 
                                   value="{{ old('start_time', '08:00') }}" required />
                            @error('start_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" 
                                   value="{{ old('end_time', '17:00') }}" required />
                            @error('end_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Crosses Midnight</label>
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="crosses_midnight" id="crosses_midnight" 
                                       value="1" {{ old('crosses_midnight') ? 'checked' : '' }}>
                                <label class="form-check-label" for="crosses_midnight">
                                    Shift crosses midnight (e.g., 22:00 - 06:00)
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Shift Differential (%)</label>
                            <input type="number" name="shift_differential_percent" step="0.01" min="0" max="100" 
                                   class="form-control @error('shift_differential_percent') is-invalid @enderror" 
                                   value="{{ old('shift_differential_percent', 0) }}" placeholder="0.00" />
                            @error('shift_differential_percent')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Additional percentage for shift premium (e.g., 10 for 10% extra)</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Active</label>
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                       value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                      rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Save Shift
                        </button>
                        <a href="{{ route('hr.shifts.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

