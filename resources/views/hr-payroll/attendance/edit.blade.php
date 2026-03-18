@extends('layouts.main')

@section('title', 'Edit Attendance Record')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Attendance', 'url' => route('hr.attendance.index'), 'icon' => 'bx bx-time'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">Edit Attendance Record</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Employee:</strong> {{ $attendance->employee->full_name }} ({{ $attendance->employee->employee_number }})<br>
                    <strong>Date:</strong> {{ $attendance->attendance_date->format('d M Y') }}
                </div>
                <form method="POST" action="{{ route('hr.attendance.update', $attendance->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Work Schedule</label>
                            <select name="schedule_id" class="form-select @error('schedule_id') is-invalid @enderror">
                                <option value="">No Schedule</option>
                                @foreach($workSchedules as $schedule)
                                    <option value="{{ $schedule->id }}" {{ old('schedule_id', $attendance->schedule_id) == $schedule->id ? 'selected' : '' }}>
                                        {{ $schedule->schedule_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('schedule_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Shift</label>
                            <select name="shift_id" class="form-select @error('shift_id') is-invalid @enderror">
                                <option value="">No Shift</option>
                                @foreach($shifts as $shift)
                                    <option value="{{ $shift->id }}" {{ old('shift_id', $attendance->shift_id) == $shift->id ? 'selected' : '' }}>
                                        {{ $shift->shift_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('shift_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Clock In</label>
                            <input type="time" name="clock_in" class="form-control @error('clock_in') is-invalid @enderror" 
                                   value="{{ old('clock_in', $attendance->clock_in ? date('H:i', strtotime($attendance->clock_in)) : '') }}" />
                            @error('clock_in')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Clock Out</label>
                            <input type="time" name="clock_out" class="form-control @error('clock_out') is-invalid @enderror" 
                                   value="{{ old('clock_out', $attendance->clock_out ? date('H:i', strtotime($attendance->clock_out)) : '') }}" />
                            @error('clock_out')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="present" {{ old('status', $attendance->status) == 'present' ? 'selected' : '' }}>Present</option>
                                <option value="absent" {{ old('status', $attendance->status) == 'absent' ? 'selected' : '' }}>Absent</option>
                                <option value="late" {{ old('status', $attendance->status) == 'late' ? 'selected' : '' }}>Late</option>
                                <option value="early_exit" {{ old('status', $attendance->status) == 'early_exit' ? 'selected' : '' }}>Early Exit</option>
                                <option value="on_leave" {{ old('status', $attendance->status) == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Expected Hours</label>
                            <input type="number" name="expected_hours" step="0.01" min="0" max="24" 
                                   class="form-control @error('expected_hours') is-invalid @enderror" 
                                   value="{{ old('expected_hours', $attendance->expected_hours) }}" />
                            @error('expected_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Actual Hours</label>
                            <input type="number" name="actual_hours" step="0.01" min="0" max="24" 
                                   class="form-control @error('actual_hours') is-invalid @enderror" 
                                   value="{{ old('actual_hours', $attendance->actual_hours) }}" />
                            @error('actual_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Normal Hours</label>
                            <input type="number" name="normal_hours" step="0.01" min="0" max="24" 
                                   class="form-control @error('normal_hours') is-invalid @enderror" 
                                   value="{{ old('normal_hours', $attendance->normal_hours) }}" />
                            @error('normal_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Overtime Hours</label>
                            <input type="number" name="overtime_hours" step="0.01" min="0" max="24" 
                                   class="form-control @error('overtime_hours') is-invalid @enderror" 
                                   value="{{ old('overtime_hours', $attendance->overtime_hours) }}" />
                            @error('overtime_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Late Minutes</label>
                            <input type="number" name="late_minutes" min="0" 
                                   class="form-control @error('late_minutes') is-invalid @enderror" 
                                   value="{{ old('late_minutes', $attendance->late_minutes) }}" />
                            @error('late_minutes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Early Exit Minutes</label>
                            <input type="number" name="early_exit_minutes" min="0" 
                                   class="form-control @error('early_exit_minutes') is-invalid @enderror" 
                                   value="{{ old('early_exit_minutes', $attendance->early_exit_minutes) }}" />
                            @error('early_exit_minutes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Exception Type</label>
                            <select name="exception_type" class="form-select @error('exception_type') is-invalid @enderror">
                                <option value="">No Exception</option>
                                <option value="late" {{ old('exception_type', $attendance->exception_type) == 'late' ? 'selected' : '' }}>Late</option>
                                <option value="early_exit" {{ old('exception_type', $attendance->exception_type) == 'early_exit' ? 'selected' : '' }}>Early Exit</option>
                                <option value="missing_punch" {{ old('exception_type', $attendance->exception_type) == 'missing_punch' ? 'selected' : '' }}>Missing Punch</option>
                                <option value="absent" {{ old('exception_type', $attendance->exception_type) == 'absent' ? 'selected' : '' }}>Absent</option>
                            </select>
                            @error('exception_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Exception Reason</label>
                            <textarea name="exception_reason" class="form-control @error('exception_reason') is-invalid @enderror" 
                                      rows="2">{{ old('exception_reason', $attendance->exception_reason) }}</textarea>
                            @error('exception_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Update Attendance
                        </button>
                        <a href="{{ route('hr.attendance.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

