@extends('layouts.main')

@section('title', 'Assign Employee Schedule')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Employee Schedules', 'url' => route('hr.employee-schedules.index'), 'icon' => 'bx bx-user-check'],
            ['label' => 'Assign', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">Assign Employee Schedule</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.employee-schedules.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Employees <span class="text-danger">*</span></label>
                            <select name="employee_ids[]" id="employee_ids" class="form-select select2-multiple @error('employee_ids') is-invalid @enderror" multiple required>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ (is_array(old('employee_ids')) && in_array($emp->id, old('employee_ids'))) || (isset($employee) && $employee->id == $emp->id) ? 'selected' : '' }}>
                                        {{ $emp->full_name }} ({{ $emp->employee_number }})
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_ids')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Select one or more employees to assign the same schedule</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Work Schedule</label>
                            <select name="schedule_id" id="schedule_id" class="form-select select2-single @error('schedule_id') is-invalid @enderror">
                                <option value="">No Schedule</option>
                                @foreach($workSchedules as $schedule)
                                    <option value="{{ $schedule->id }}" {{ old('schedule_id') == $schedule->id ? 'selected' : '' }}>
                                        {{ $schedule->schedule_name }} ({{ $schedule->schedule_code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('schedule_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Optional: Assign a work schedule</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Shift</label>
                            <select name="shift_id" id="shift_id" class="form-select select2-single @error('shift_id') is-invalid @enderror">
                                <option value="">No Shift</option>
                                @foreach($shifts as $shift)
                                    <option value="{{ $shift->id }}" {{ old('shift_id') == $shift->id ? 'selected' : '' }}>
                                        {{ $shift->shift_name }} ({{ date('H:i', strtotime($shift->start_time)) }} - {{ date('H:i', strtotime($shift->end_time)) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('shift_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Optional: Assign a shift</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Effective Date <span class="text-danger">*</span></label>
                            <input type="date" name="effective_date" class="form-control @error('effective_date') is-invalid @enderror" 
                                   value="{{ old('effective_date', date('Y-m-d')) }}" required />
                            @error('effective_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Date when this schedule assignment becomes effective</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" 
                                   value="{{ old('end_date') }}" />
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Leave blank for ongoing assignment</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Assign Schedule
                        </button>
                        <a href="{{ route('hr.employee-schedules.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for multiple employee selection
    $('#employee_ids').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Search and select employees...',
        allowClear: true,
        closeOnSelect: false
    });
    
    // Initialize Select2 for other single select fields
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: function() {
            return $(this).data('placeholder') || '-- Select --';
        },
        allowClear: true
    });
});
</script>
@endpush

