@extends('layouts.main')

@section('title', 'Edit Shift')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Shifts', 'url' => route('hr.shifts.index'), 'icon' => 'bx bx-time-five'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">Edit Shift</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.shifts.update', $shift->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Shift Code <span class="text-danger">*</span></label>
                            <input type="text" name="shift_code" class="form-control @error('shift_code') is-invalid @enderror" 
                                   value="{{ old('shift_code', $shift->shift_code) }}" required />
                            @error('shift_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Shift Name <span class="text-danger">*</span></label>
                            <input type="text" name="shift_name" class="form-control @error('shift_name') is-invalid @enderror" 
                                   value="{{ old('shift_name', $shift->shift_name) }}" required />
                            @error('shift_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" 
                                   value="{{ old('start_time', date('H:i', strtotime($shift->start_time))) }}" required />
                            @error('start_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" 
                                   value="{{ old('end_time', date('H:i', strtotime($shift->end_time))) }}" required />
                            @error('end_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Crosses Midnight</label>
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="crosses_midnight" id="crosses_midnight" 
                                       value="1" {{ old('crosses_midnight', $shift->crosses_midnight) ? 'checked' : '' }}>
                                <label class="form-check-label" for="crosses_midnight">
                                    Shift crosses midnight
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Shift Differential (%)</label>
                            <input type="number" name="shift_differential_percent" step="0.01" min="0" max="100" 
                                   class="form-control @error('shift_differential_percent') is-invalid @enderror" 
                                   value="{{ old('shift_differential_percent', $shift->shift_differential_percent) }}" />
                            @error('shift_differential_percent')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Active</label>
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                       value="1" {{ old('is_active', $shift->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                      rows="3">{{ old('description', $shift->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Update Shift
                        </button>
                        <a href="{{ route('hr.shifts.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

