@extends('layouts.main')

@section('title', 'Create Attendance Record')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Attendance', 'url' => route('hr.attendance.index'), 'icon' => 'bx bx-time'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">Create Attendance Record</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.attendance.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" id="employee_id" class="form-select select2-single @error('employee_id') is-invalid @enderror" required>
                                <option value="">-- Search and Select Employee --</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employee_id', $employee?->id) == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->full_name }}@if($emp->employee_number) ({{ $emp->employee_number }})@endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Start typing to search by employee name or employee number. Select the employee for whom you are recording attendance.
                            </div>
                            @error('employee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Attendance Date <span class="text-danger">*</span></label>
                            <input type="date" name="attendance_date" class="form-control @error('attendance_date') is-invalid @enderror" 
                                   value="{{ old('attendance_date', $date) }}" required />
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Select the date for which you are recording attendance. Format: YYYY-MM-DD (e.g., 2025-12-27).
                            </div>
                            @error('attendance_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Work Schedule</label>
                            <select name="schedule_id" class="form-select @error('schedule_id') is-invalid @enderror">
                                <option value="">No Schedule</option>
                                @foreach($workSchedules as $schedule)
                                    <option value="{{ $schedule->id }}" {{ old('schedule_id') == $schedule->id ? 'selected' : '' }}>
                                        {{ $schedule->schedule_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Optional: Select the work schedule assigned to this employee (e.g., Monday-Friday, Shift Schedule).
                            </div>
                            @error('schedule_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Shift</label>
                            <select name="shift_id" class="form-select @error('shift_id') is-invalid @enderror">
                                <option value="">No Shift</option>
                                @foreach($shifts as $shift)
                                    <option value="{{ $shift->id }}" {{ old('shift_id') == $shift->id ? 'selected' : '' }}>
                                        {{ $shift->shift_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Optional: Select the shift worked by the employee (e.g., Day Shift, Night Shift, Morning Shift).
                            </div>
                            @error('shift_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Clock In</label>
                            <input type="time" name="clock_in" class="form-control @error('clock_in') is-invalid @enderror" 
                                   value="{{ old('clock_in') }}" />
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Enter the time the employee clocked in. Format: HH:MM (24-hour format, e.g., 08:00, 14:30).
                            </div>
                            @error('clock_in')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Clock Out</label>
                            <input type="time" name="clock_out" class="form-control @error('clock_out') is-invalid @enderror" 
                                   value="{{ old('clock_out') }}" />
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Enter the time the employee clocked out. Format: HH:MM (24-hour format, e.g., 17:00, 22:30). Must be after clock in time.
                            </div>
                            @error('clock_out')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="present" {{ old('status') == 'present' ? 'selected' : '' }}>Present</option>
                                <option value="absent" {{ old('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                                <option value="late" {{ old('status') == 'late' ? 'selected' : '' }}>Late</option>
                                <option value="early_exit" {{ old('status') == 'early_exit' ? 'selected' : '' }}>Early Exit</option>
                                <option value="on_leave" {{ old('status') == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                            </select>
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Select attendance status: <strong>Present</strong> (worked full day), <strong>Absent</strong> (did not show up), <strong>Late</strong> (arrived after scheduled time), <strong>Early Exit</strong> (left before scheduled time), <strong>On Leave</strong> (on approved leave).
                            </div>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Expected Hours</label>
                            <input type="number" name="expected_hours" step="0.01" min="0" max="24" 
                                   class="form-control @error('expected_hours') is-invalid @enderror" 
                                   value="{{ old('expected_hours') }}" />
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Enter the number of hours the employee was scheduled to work (e.g., 8.0 for 8 hours, 7.5 for 7 hours 30 minutes). Range: 0-24 hours.
                            </div>
                            @error('expected_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Actual Hours</label>
                            <input type="number" name="actual_hours" step="0.01" min="0" max="24" 
                                   class="form-control @error('actual_hours') is-invalid @enderror" 
                                   value="{{ old('actual_hours') }}" />
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Enter the actual number of hours the employee worked (calculated from clock in/out times or manually entered). Range: 0-24 hours.
                            </div>
                            @error('actual_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Normal Hours</label>
                            <input type="number" name="normal_hours" step="0.01" min="0" max="24" 
                                   class="form-control @error('normal_hours') is-invalid @enderror" 
                                   value="{{ old('normal_hours', 0) }}" />
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Enter the number of regular working hours (non-overtime hours). This is typically the standard work hours (e.g., 8.0 hours). Range: 0-24 hours.
                            </div>
                            @error('normal_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Overtime Hours</label>
                            <input type="number" name="overtime_hours" step="0.01" min="0" max="24" 
                                   class="form-control @error('overtime_hours') is-invalid @enderror" 
                                   value="{{ old('overtime_hours', 0) }}" />
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Enter the number of overtime hours worked beyond the normal working hours (e.g., 2.0 for 2 hours overtime, 1.5 for 1 hour 30 minutes). Range: 0-24 hours.
                            </div>
                            @error('overtime_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Late Minutes</label>
                            <input type="number" name="late_minutes" min="0" 
                                   class="form-control @error('late_minutes') is-invalid @enderror" 
                                   value="{{ old('late_minutes', 0) }}" />
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Enter the number of minutes the employee was late (e.g., 15 for 15 minutes late, 30 for 30 minutes late). Enter 0 if not late.
                            </div>
                            @error('late_minutes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Early Exit Minutes</label>
                            <input type="number" name="early_exit_minutes" min="0" 
                                   class="form-control @error('early_exit_minutes') is-invalid @enderror" 
                                   value="{{ old('early_exit_minutes', 0) }}" />
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Enter the number of minutes the employee left early before the scheduled end time (e.g., 20 for 20 minutes early, 45 for 45 minutes early). Enter 0 if not early.
                            </div>
                            @error('early_exit_minutes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Exception Type</label>
                            <select name="exception_type" class="form-select @error('exception_type') is-invalid @enderror">
                                <option value="">No Exception</option>
                                <option value="late" {{ old('exception_type') == 'late' ? 'selected' : '' }}>Late</option>
                                <option value="early_exit" {{ old('exception_type') == 'early_exit' ? 'selected' : '' }}>Early Exit</option>
                                <option value="missing_punch" {{ old('exception_type') == 'missing_punch' ? 'selected' : '' }}>Missing Punch</option>
                                <option value="absent" {{ old('exception_type') == 'absent' ? 'selected' : '' }}>Absent</option>
                            </select>
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Optional: Select if there was an exception: <strong>Late</strong> (arrived late), <strong>Early Exit</strong> (left early), <strong>Missing Punch</strong> (forgot to clock in/out), <strong>Absent</strong> (did not show up).
                            </div>
                            @error('exception_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Exception Reason</label>
                            <textarea name="exception_reason" class="form-control @error('exception_reason') is-invalid @enderror" 
                                      rows="2" placeholder="Enter reason for exception (e.g., Traffic jam, Medical emergency, etc.)">{{ old('exception_reason') }}</textarea>
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Optional: Provide a detailed explanation for the exception (e.g., "Traffic jam caused 30-minute delay", "Medical appointment - left 2 hours early", "Forgot to clock in - arrived at 8:15 AM").
                            </div>
                            @error('exception_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Save Attendance
                        </button>
                        <a href="{{ route('hr.attendance.index') }}" class="btn btn-secondary">Cancel</a>
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
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '-- Search and Select Employee --',
        allowClear: true
    });
});
</script>
@endpush

