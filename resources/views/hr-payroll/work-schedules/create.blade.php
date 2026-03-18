@extends('layouts.main')

@section('title', 'Create Work Schedule')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Work Schedules', 'url' => route('hr.work-schedules.index'), 'icon' => 'bx bx-calendar-week'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">Create Work Schedule</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.work-schedules.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Schedule Code <span class="text-danger">*</span></label>
                            <input type="text" name="schedule_code" class="form-control @error('schedule_code') is-invalid @enderror" 
                                   value="{{ old('schedule_code') }}" required placeholder="e.g., MON-FRI, SHIFT-A" />
                            @error('schedule_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Unique code for this schedule</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Schedule Name <span class="text-danger">*</span></label>
                            <input type="text" name="schedule_name" class="form-control @error('schedule_name') is-invalid @enderror" 
                                   value="{{ old('schedule_name') }}" required placeholder="e.g., Monday to Friday" />
                            @error('schedule_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Weekly Pattern <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                @php
                                    $days = ['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 
                                             'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday', 'sunday' => 'Sunday'];
                                    $oldPattern = old('weekly_pattern', ['monday' => true, 'tuesday' => true, 'wednesday' => true, 
                                                                         'thursday' => true, 'friday' => true, 'saturday' => false, 'sunday' => false]);
                                @endphp
                                @foreach($days as $key => $day)
                                <div class="col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="weekly_pattern[{{ $key }}]" 
                                               id="day_{{ $key }}" value="1" 
                                               {{ isset($oldPattern[$key]) && $oldPattern[$key] ? 'checked' : '' }}>
                                        <label class="form-check-label" for="day_{{ $key }}">{{ $day }}</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @error('weekly_pattern')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Standard Daily Hours <span class="text-danger">*</span></label>
                            <input type="number" name="standard_daily_hours" step="0.01" min="0" max="24" 
                                   class="form-control @error('standard_daily_hours') is-invalid @enderror" 
                                   value="{{ old('standard_daily_hours', 8.00) }}" required />
                            @error('standard_daily_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Hours per working day</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Break Duration (Minutes)</label>
                            <input type="number" name="break_duration_minutes" min="0" 
                                   class="form-control @error('break_duration_minutes') is-invalid @enderror" 
                                   value="{{ old('break_duration_minutes', 60) }}" />
                            @error('break_duration_minutes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Overtime Eligible</label>
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="overtime_eligible" id="overtime_eligible" 
                                       value="1" {{ old('overtime_eligible', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="overtime_eligible">
                                    Allow overtime for this schedule
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
                            <i class="bx bx-save me-1"></i>Save Work Schedule
                        </button>
                        <a href="{{ route('hr.work-schedules.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

