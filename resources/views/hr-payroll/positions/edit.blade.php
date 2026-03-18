@extends('layouts.main')

@section('title', 'Edit Position')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Positions', 'url' => route('hr.positions.index'), 'icon' => 'bx bx-briefcase'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">
                <i class="bx bx-edit me-1"></i>Edit Position: {{ $position->title }}
            </h6>
        </div>
        <hr />

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bx bx-info-circle me-1"></i>Position Information
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <h6>Please correct the following errors:</h6>
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('hr.positions.update', $position) }}">
                            @csrf
                            @method('PUT')
                            <div class="row g-3">
                                <!-- Position Title -->
                                <div class="col-md-6">
                                    <label for="title" class="form-label">Position Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" 
                                           value="{{ old('title', $position->title) }}" placeholder="Enter position title" required />
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Department -->
                                <div class="col-md-6">
                                    <label for="department_id" class="form-label">Department</label>
                                    <select name="department_id" id="department_id" class="form-select select2-single @error('department_id') is-invalid @enderror">
                                        <option value="">Select Department</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" @selected(old('department_id', $position->department_id)==$department->id)>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Optional: Select the department this position belongs to</div>
                                </div>

                                <!-- Description -->
                                <div class="col-12">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" 
                                              rows="4" placeholder="Enter position description...">{{ old('description', $position->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Optional: Brief description of the position's responsibilities and requirements</div>
                                </div>
                                
                                <div class="col-12">
                                    <hr>
                                    <h6 class="mb-3"><i class="bx bx-layer me-1"></i>Job Grade & Salary Information</h6>
                                </div>
                                
                                <!-- Job Grade -->
                                <div class="col-md-6">
                                    <label for="grade_id" class="form-label">Job Grade</label>
                                    <select name="grade_id" id="grade_id" class="form-select select2-single @error('grade_id') is-invalid @enderror">
                                        <option value="">-- Select Job Grade --</option>
                                        @foreach($jobGrades as $grade)
                                        <option value="{{ $grade->id }}" 
                                                data-min="{{ $grade->minimum_salary ?? '' }}" 
                                                data-max="{{ $grade->maximum_salary ?? '' }}"
                                                @selected(old('grade_id', $position->grade_id)==$grade->id)>
                                            {{ $grade->grade_code }} - {{ $grade->grade_name }}
                                            @if($grade->minimum_salary || $grade->maximum_salary)
                                                ({{ $grade->salary_range }})
                                            @endif
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('grade_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Select the job grade for this position. This determines the salary range.</div>
                                </div>
                                
                                <!-- Budgeted Salary -->
                                <div class="col-md-6">
                                    <label for="budgeted_salary" class="form-label">Budgeted Salary <span class="text-muted">(Optional - For Planning Only)</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">{{ auth()->user()->company->currency ?? 'TZS' }}</span>
                                        <input type="number" name="budgeted_salary" id="budgeted_salary" 
                                               class="form-control @error('budgeted_salary') is-invalid @enderror" 
                                               value="{{ old('budgeted_salary', $position->budgeted_salary) }}" 
                                               step="0.01" min="0" placeholder="0.00">
                                    </div>
                                    @error('budgeted_salary')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="alert alert-info mt-2">
                                        <i class="bx bx-info-circle me-1"></i>
                                        <small><strong>Note:</strong> This is for budgeting and planning purposes only. Actual employee salaries are set individually when creating employees or contracts. This field helps estimate position costs for financial planning.</small>
                                    </div>
                                    <div class="form-text" id="salary_range_hint">If provided, must be within the selected grade's salary range.</div>
                                    <div id="salary_validation_alert" class="alert alert-warning mt-2" style="display: none;">
                                        <i class="bx bx-error-circle me-1"></i><span id="salary_validation_message"></span>
                                    </div>
                                </div>
                                
                                <!-- Approved Headcount -->
                                <div class="col-md-4">
                                    <label for="approved_headcount" class="form-label">Approved Headcount</label>
                                    <input type="number" name="approved_headcount" id="approved_headcount" 
                                           class="form-control @error('approved_headcount') is-invalid @enderror" 
                                           value="{{ old('approved_headcount', $position->approved_headcount ?? 1) }}" 
                                           min="1" required>
                                    @error('approved_headcount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Number of employees that can be assigned to this position</div>
                                </div>
                                
                                <!-- Status -->
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select select2-single @error('status') is-invalid @enderror">
                                        <option value="approved" @selected(old('status', $position->status ?? 'approved')=='approved')>Approved</option>
                                        <option value="frozen" @selected(old('status', $position->status)=='frozen')>Frozen</option>
                                        <option value="cancelled" @selected(old('status', $position->status)=='cancelled')>Cancelled</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <!-- Effective Date -->
                                <div class="col-md-4">
                                    <label for="effective_date" class="form-label">Effective Date</label>
                                    <input type="date" name="effective_date" id="effective_date" 
                                           class="form-control @error('effective_date') is-invalid @enderror" 
                                           value="{{ old('effective_date', $position->effective_date?->format('Y-m-d')) }}">
                                    @error('effective_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <!-- End Date -->
                                <div class="col-md-6">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" name="end_date" id="end_date" 
                                           class="form-control @error('end_date') is-invalid @enderror" 
                                           value="{{ old('end_date', $position->end_date?->format('Y-m-d')) }}">
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Action Buttons -->
                                <div class="col-12">
                                    <hr>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-check me-1"></i>Update Position
                                        </button>
                                        <a href="{{ route('hr.positions.index') }}" class="btn btn-outline-secondary">
                                            <i class="bx bx-x me-1"></i>Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar with information -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bx bx-info-circle me-1"></i>Position Details
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <td><strong>Created:</strong></td>
                                        <td>{{ $position->created_at->format('M d, Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Last Updated:</strong></td>
                                        <td>{{ $position->updated_at->format('M d, Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Department:</strong></td>
                                        <td>{{ $position->department->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Employees:</strong></td>
                                        <td>{{ $position->employees()->count() ?? 0 }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        @if($position->employees()->count() > 0)
                            <div class="alert alert-warning mt-3">
                                <small><i class="bx bx-info-circle me-1"></i>
                                This position has employees assigned. Changes may affect employee records.</small>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bx bx-help-circle me-1"></i>Guidelines
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6 class="alert-heading">Update Tips</h6>
                            <ul class="mb-0 small">
                                <li>Position title must be unique</li>
                                <li>Department assignment helps with organization</li>
                                <li>Description helps clarify position roles</li>
                                <li>Ensure employees are reassigned before deleting</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }

    .form-label {
        font-weight: 600;
        color: #495057;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
</style>
@endpush

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
            $('#salary_range_hint').text('Enter the budgeted salary for this position. Must be within the selected grade\'s salary range.');
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
    
    // Initialize on page load if grade is already selected
    @if($position->grade_id)
        $('#grade_id').trigger('change');
    @endif
    
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
