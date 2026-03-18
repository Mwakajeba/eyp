@extends('layouts.main')

@section('title', 'New Position')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Positions', 'url' => route('hr.positions.index'), 'icon' => 'bx bx-briefcase'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">Create Position</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.positions.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}" required />
                        @error('title')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select select2-single">
                            <option value="">-- Select Department --</option>
                            @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id')==$department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                        @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    
                    <hr class="my-4">
                    <h6 class="mb-3"><i class="bx bx-layer me-1"></i>Job Grade & Salary Information</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Job Grade</label>
                        <select name="grade_id" id="grade_id" class="form-select select2-single">
                            <option value="">-- Select Job Grade --</option>
                            @foreach($jobGrades as $grade)
                            <option value="{{ $grade->id }}" 
                                    data-min="{{ $grade->minimum_salary ?? '' }}" 
                                    data-max="{{ $grade->maximum_salary ?? '' }}"
                                    @selected(old('grade_id')==$grade->id)>
                                {{ $grade->grade_code }} - {{ $grade->grade_name }}
                                @if($grade->minimum_salary || $grade->maximum_salary)
                                    ({{ $grade->salary_range }})
                                @endif
                            </option>
                            @endforeach
                        </select>
                        @error('grade_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        <small class="text-muted">Select the job grade for this position. This determines the salary range.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Budgeted Salary <span class="text-muted">(Optional - For Planning Only)</span></label>
                        <div class="input-group">
                            <span class="input-group-text">{{ auth()->user()->company->currency ?? 'TZS' }}</span>
                            <input type="number" name="budgeted_salary" id="budgeted_salary" class="form-control" 
                                   value="{{ old('budgeted_salary') }}" step="0.01" min="0" placeholder="0.00">
                        </div>
                        @error('budgeted_salary')<div class="text-danger small">{{ $message }}</div>@enderror
                        <div class="alert alert-info mt-2">
                            <i class="bx bx-info-circle me-1"></i>
                            <small><strong>Note:</strong> This is for budgeting and planning purposes only. Actual employee salaries are set individually when creating employees or contracts. This field helps estimate position costs for financial planning.</small>
                        </div>
                        <small class="text-muted" id="salary_range_hint">If provided, must be within the selected grade's salary range.</small>
                        <div id="salary_validation_alert" class="alert alert-warning mt-2" style="display: none;">
                            <i class="bx bx-error-circle me-1"></i><span id="salary_validation_message"></span>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Approved Headcount</label>
                            <input type="number" name="approved_headcount" class="form-control" 
                                   value="{{ old('approved_headcount', 1) }}" min="1" required>
                            @error('approved_headcount')<div class="text-danger small">{{ $message }}</div>@enderror
                            <small class="text-muted">Number of employees that can be assigned to this position</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select select2-single">
                                <option value="approved" @selected(old('status', 'approved')=='approved')>Approved</option>
                                <option value="frozen" @selected(old('status')=='frozen')>Frozen</option>
                                <option value="cancelled" @selected(old('status')=='cancelled')>Cancelled</option>
                            </select>
                            @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Effective Date</label>
                            <input type="date" name="effective_date" class="form-control" value="{{ old('effective_date') }}">
                            @error('effective_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
                            @error('end_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary"><i class="bx bx-save me-1"></i>Save</button>
                        <a href="{{ route('hr.positions.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for all select fields
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: function() {
            return $(this).data('placeholder') || '-- Select --';
        },
        allowClear: true
    });
    
    // Update salary range hint when grade changes
    $('#grade_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const minSalary = selectedOption.data('min');
        const maxSalary = selectedOption.data('max');
        const budgetedSalary = parseFloat($('#budgeted_salary').val()) || 0;
        
        if (selectedOption.val() && (minSalary || maxSalary)) {
            const min = minSalary ? new Intl.NumberFormat().format(minSalary) : 'N/A';
            const max = maxSalary ? new Intl.NumberFormat().format(maxSalary) : 'N/A';
            $('#salary_range_hint').html(`<strong>Grade Salary Range:</strong> ${min} - ${max}`);
            
            // Validate budgeted salary if entered
            if (budgetedSalary > 0) {
                validateSalaryAgainstGrade(budgetedSalary, minSalary, maxSalary);
            }
        } else {
            $('#salary_range_hint').text('If provided, must be within the selected grade\'s salary range.');
            $('#salary_validation_alert').hide();
        }
    });
    
    // Validate salary when budgeted_salary changes
    $('#budgeted_salary').on('blur', function() {
        const budgetedSalary = parseFloat($(this).val()) || 0;
        const selectedOption = $('#grade_id').find('option:selected');
        const minSalary = selectedOption.data('min');
        const maxSalary = selectedOption.data('max');
        
        if (selectedOption.val() && budgetedSalary > 0) {
            validateSalaryAgainstGrade(budgetedSalary, minSalary, maxSalary);
        } else {
            $('#salary_validation_alert').hide();
        }
    });
    
    function validateSalaryAgainstGrade(salary, minSalary, maxSalary) {
        const alert = $('#salary_validation_alert');
        const message = $('#salary_validation_message');
        
        let isValid = true;
        let errorMsg = '';
        
        if (minSalary && salary < parseFloat(minSalary)) {
            isValid = false;
            errorMsg = `Salary (${new Intl.NumberFormat().format(salary)}) is below the minimum (${new Intl.NumberFormat().format(minSalary)}) for this grade.`;
        } else if (maxSalary && salary > parseFloat(maxSalary)) {
            isValid = false;
            errorMsg = `Salary (${new Intl.NumberFormat().format(salary)}) exceeds the maximum (${new Intl.NumberFormat().format(maxSalary)}) for this grade.`;
        }
        
        if (!isValid) {
            alert.removeClass('alert-success').addClass('alert-warning');
            message.html(errorMsg);
            alert.show();
        } else {
            alert.removeClass('alert-warning').addClass('alert-success');
            message.html('<i class="bx bx-check-circle me-1"></i>Salary is within the acceptable range for this grade.');
            alert.show();
        }
    }
});
</script>
@endpush
@endsection
