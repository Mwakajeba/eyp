@extends('layouts.main')

@section('title', 'Create Timesheet')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Timesheets', 'url' => route('hr.timesheets.index'), 'icon' => 'bx bx-time-five'],
                ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-time-five me-1"></i>Create Timesheet</h6>
                <a href="{{ route('hr.timesheets.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr.timesheets.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="employee_id" class="form-label">Employee <span class="text-danger">*</span></label>
                                <select name="employee_id" id="employee_id" class="form-select select2-single @error('employee_id') is-invalid @enderror" required>
                                    <option value="">Select Employee</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}" {{ old('employee_id', $employee?->id) == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->full_name }} ({{ $emp->employee_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('employee_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="timesheet_date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="timesheet_date" id="timesheet_date" class="form-control @error('timesheet_date') is-invalid @enderror" value="{{ old('timesheet_date', $date) }}" required>
                                @error('timesheet_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="department_id" class="form-label">Department (Project Account)</label>
                                <select name="department_id" id="department_id" class="form-select select2-single @error('department_id') is-invalid @enderror">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Select the department/project account where time should be charged</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="activity_type" class="form-label">Activity Type <span class="text-danger">*</span></label>
                                <select name="activity_type" id="activity_type" class="form-select @error('activity_type') is-invalid @enderror" required>
                                    <option value="work" {{ old('activity_type') == 'work' ? 'selected' : '' }}>Work</option>
                                    <option value="training" {{ old('activity_type') == 'training' ? 'selected' : '' }}>Training</option>
                                    <option value="meeting" {{ old('activity_type') == 'meeting' ? 'selected' : '' }}>Meeting</option>
                                    <option value="conference" {{ old('activity_type') == 'conference' ? 'selected' : '' }}>Conference</option>
                                    <option value="project" {{ old('activity_type') == 'project' ? 'selected' : '' }}>Project</option>
                                    <option value="other" {{ old('activity_type') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('activity_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="project_reference" class="form-label">Project Reference</label>
                                <input type="text" name="project_reference" id="project_reference" class="form-control @error('project_reference') is-invalid @enderror" value="{{ old('project_reference') }}" placeholder="Optional project code/reference">
                                @error('project_reference')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="normal_hours" class="form-label">Normal Hours <span class="text-danger">*</span></label>
                                <input type="number" name="normal_hours" id="normal_hours" class="form-control @error('normal_hours') is-invalid @enderror" value="{{ old('normal_hours') }}" step="0.25" min="0" max="24" required>
                                @error('normal_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="overtime_hours" class="form-label">Overtime Hours</label>
                                <input type="number" name="overtime_hours" id="overtime_hours" class="form-control @error('overtime_hours') is-invalid @enderror" value="{{ old('overtime_hours', 0) }}" step="0.25" min="0" max="24">
                                @error('overtime_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Describe the work/activity performed">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="priorities" class="form-label">Priorities</label>
                                <textarea name="priorities" id="priorities" class="form-control @error('priorities') is-invalid @enderror" rows="3" placeholder="List priorities for the day">{{ old('priorities') }}</textarea>
                                @error('priorities')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">List the priorities/tasks planned for this day</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="achievements" class="form-label">Achievements</label>
                                <textarea name="achievements" id="achievements" class="form-control @error('achievements') is-invalid @enderror" rows="3" placeholder="List achievements/accomplishments">{{ old('achievements') }}</textarea>
                                @error('achievements')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">List what was accomplished/achieved</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="submitted" {{ old('status') == 'submitted' ? 'selected' : '' }}>Submit for Approval</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('hr.timesheets.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Save Timesheet
                            </button>
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
    // Initialize Select2 for all select2-single dropdowns
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: function() {
            return $(this).data('placeholder') || '-- Select --';
        },
        allowClear: true
    });

    // Set max date to today
    $('#timesheet_date').attr('max', new Date().toISOString().split('T')[0]);
    
    // Calculate total hours
    function updateTotalHours() {
        const normal = parseFloat($('#normal_hours').val()) || 0;
        const overtime = parseFloat($('#overtime_hours').val()) || 0;
        const total = normal + overtime;
        
        // Show total if both fields have values
        if (normal > 0 || overtime > 0) {
            if (!$('#total_hours_display').length) {
                $('#overtime_hours').after('<small class="text-muted d-block mt-1">Total: <strong id="total_hours_display">0</strong> hours</small>');
            }
            $('#total_hours_display').text(total.toFixed(2));
        }
    }
    
    $('#normal_hours, #overtime_hours').on('input', updateTotalHours);
});
</script>
@endpush

