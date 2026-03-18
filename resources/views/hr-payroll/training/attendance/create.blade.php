@extends('layouts.main')

@section('title', 'Create Training Attendance')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Training', 'url' => '#', 'icon' => 'bx bx-book'],
            ['label' => 'Attendance', 'url' => route('hr.training-attendance.index'), 'icon' => 'bx bx-user-check'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">Create Training Attendance</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.training-attendance.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Training Program <span class="text-danger">*</span></label>
                            <select name="program_id" class="form-select select2-single @error('program_id') is-invalid @enderror" required>
                                <option value="">-- Select Program --</option>
                                @foreach($programs ?? [] as $program)
                                    <option value="{{ $program->id }}" {{ old('program_id', $programId) == $program->id ? 'selected' : '' }}>
                                        {{ $program->program_code }} - {{ $program->program_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('program_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select select2-single @error('employee_id') is-invalid @enderror" required>
                                <option value="">-- Select Employee --</option>
                                @foreach($employees ?? [] as $employee)
                                    <option value="{{ $employee->id }}" {{ old('employee_id', $employeeId) == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->full_name }} ({{ $employee->employee_number }})
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Attendance Status <span class="text-danger">*</span></label>
                            <select name="attendance_status" class="form-select @error('attendance_status') is-invalid @enderror" required>
                                <option value="">-- Select Status --</option>
                                <option value="registered" {{ old('attendance_status', 'registered') == 'registered' ? 'selected' : '' }}>Registered</option>
                                <option value="attended" {{ old('attendance_status') == 'attended' ? 'selected' : '' }}>Attended</option>
                                <option value="completed" {{ old('attendance_status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="absent" {{ old('attendance_status') == 'absent' ? 'selected' : '' }}>Absent</option>
                            </select>
                            @error('attendance_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Completion Date</label>
                            <input type="date" name="completion_date" class="form-control @error('completion_date') is-invalid @enderror" 
                                   value="{{ old('completion_date') }}" />
                            @error('completion_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Evaluation Score</label>
                            <input type="number" name="evaluation_score" step="0.01" min="0" max="100" 
                                   class="form-control @error('evaluation_score') is-invalid @enderror" 
                                   value="{{ old('evaluation_score') }}" placeholder="0.00" />
                            @error('evaluation_score')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Score out of 100</div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" name="certification_received" id="certification_received" 
                                       value="1" {{ old('certification_received') ? 'checked' : '' }}>
                                <label class="form-check-label" for="certification_received">
                                    Certification Received
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Save Attendance
                        </button>
                        <a href="{{ route('hr.training-attendance.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

