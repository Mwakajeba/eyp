@extends('layouts.main')

@section('title', 'Create Contract')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Contracts', 'url' => route('hr.contracts.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">Create Contract</h6>
        <hr />

        <!-- Information Alert -->
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <h6 class="alert-heading mb-2">
                <i class="bx bx-info-circle me-2"></i>Contract Creation Guide
            </h6>
            <ul class="mb-0 small">
                <li><strong>Employee Selection:</strong> Search by name or employee number to find the employee</li>
                <li><strong>Contract Type:</strong> Choose the appropriate contract type (Permanent, Fixed Term, Probation, etc.)</li>
                <li><strong>Dates:</strong> Start date is required. End date is optional for permanent contracts</li>
                <li><strong>Renewal Flag:</strong> Enable if the contract requires renewal before expiry</li>
                <li><strong>Document Attachments:</strong> After creating the contract, you can upload signed contract documents, amendments, and related files from the contract details page</li>
                <li><strong>Note:</strong> Creating a new contract will automatically terminate any existing active contract for the employee</li>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.contracts.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">
                                Employee <span class="text-danger">*</span>
                                <i class="bx bx-help-circle text-muted" data-bs-toggle="tooltip" 
                                   title="Search by employee name or employee number. Type to filter the list."></i>
                            </label>
                            <select name="employee_id" id="employee_id" 
                                    class="form-select select2-single @error('employee_id') is-invalid @enderror" 
                                    required>
                                <option value="">-- Search and Select Employee --</option>
                                @foreach($employees ?? [] as $emp)
                                    <option value="{{ $emp->id }}" 
                                            {{ (old('employee_id') == $emp->id || (isset($employee) && $employee->id == $emp->id)) ? 'selected' : '' }}>
                                        {{ $emp->full_name }}@if($emp->employee_number) ({{ $emp->employee_number }})@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="bx bx-search me-1"></i>Type employee name or number to search. Only active employees are shown.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Contract Type <span class="text-danger">*</span>
                                <i class="bx bx-help-circle text-muted" data-bs-toggle="tooltip" 
                                   title="Select the type of employment contract. Permanent contracts typically don't have an end date."></i>
                            </label>
                            <select name="contract_type" class="form-select @error('contract_type') is-invalid @enderror" required>
                                <option value="">-- Select Contract Type --</option>
                                <option value="permanent" {{ old('contract_type') == 'permanent' ? 'selected' : '' }}>Permanent - Ongoing employment with no end date</option>
                                <option value="fixed_term" {{ old('contract_type') == 'fixed_term' ? 'selected' : '' }}>Fixed Term - Employment for a specific period</option>
                                <option value="probation" {{ old('contract_type') == 'probation' ? 'selected' : '' }}>Probation - Initial probationary period</option>
                                <option value="contractor" {{ old('contract_type') == 'contractor' ? 'selected' : '' }}>Contractor - Independent contractor agreement</option>
                                <option value="intern" {{ old('contract_type') == 'intern' ? 'selected' : '' }}>Intern - Internship/training contract</option>
                            </select>
                            @error('contract_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Choose the appropriate contract type based on employment terms</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Start Date <span class="text-danger">*</span>
                                <i class="bx bx-help-circle text-muted" data-bs-toggle="tooltip" 
                                   title="The date when the contract becomes effective. This is typically the employment start date."></i>
                            </label>
                            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" 
                                   value="{{ old('start_date') }}" required max="{{ date('Y-m-d') }}" />
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Contract effective start date (cannot be in the future)</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                End Date
                                <i class="bx bx-help-circle text-muted" data-bs-toggle="tooltip" 
                                   title="Optional end date for fixed-term contracts. Leave blank for permanent contracts."></i>
                            </label>
                            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" 
                                   value="{{ old('end_date') }}" id="end_date" />
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Leave blank for permanent contracts. Required for fixed-term contracts.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Working Hours per Week
                                <i class="bx bx-help-circle text-muted" data-bs-toggle="tooltip" 
                                   title="Standard working hours per week for this contract. Default is 40 hours."></i>
                            </label>
                            <input type="number" name="working_hours_per_week" min="1" max="168" 
                                   class="form-control @error('working_hours_per_week') is-invalid @enderror" 
                                   value="{{ old('working_hours_per_week', 40) }}" />
                            @error('working_hours_per_week')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Standard weekly working hours (default: 40 hours)</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Contract Salary
                                <i class="bx bx-help-circle text-muted" data-bs-toggle="tooltip" 
                                   title="Salary amount for this contract. If set, this will be used in payroll calculations instead of employee's basic salary. Leave blank to use employee's basic salary."></i>
                            </label>
                            <input type="number" name="salary" step="0.01" min="0" 
                                   class="form-control @error('salary') is-invalid @enderror" 
                                   value="{{ old('salary', isset($employee) ? $employee->basic_salary : '') }}" 
                                   placeholder="0.00" id="contract_salary" />
                            @error('salary')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                @if(isset($employee) && $employee->basic_salary)
                                    <i class="bx bx-info-circle me-1"></i>
                                    Employee's current basic salary: <strong>{{ number_format($employee->basic_salary, 2) }} TZS</strong>
                                    <br><small class="text-muted">Leave blank to use this amount, or enter a different amount for this contract.</small>
                                @else
                                    <i class="bx bx-info-circle me-1"></i>
                                    If left blank, employee's basic salary will be used in payroll calculations.
                                @endif
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="renewal_flag" id="renewal_flag" 
                                       value="1" {{ old('renewal_flag') ? 'checked' : '' }}>
                                <label class="form-check-label" for="renewal_flag">
                                    <strong>Renewal Required</strong>
                                    <i class="bx bx-help-circle text-muted ms-1" data-bs-toggle="tooltip" 
                                       title="Enable this if the contract needs to be renewed before expiry. System will send alerts when renewal is due."></i>
                                </label>
                            </div>
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>Enable to receive automatic renewal reminders before contract expiry
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Save Contract
                        </button>
                        <a href="{{ route('hr.contracts.index') }}" class="btn btn-secondary">Cancel</a>
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
    // Initialize Select2 for employee selection (will be initialized by main layout, but we can enhance it)
    $('#employee_id').on('select2:open', function() {
        $('.select2-search__field').attr('placeholder', 'Type employee name or number...');
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-hide end date requirement based on contract type
    $('select[name="contract_type"]').on('change', function() {
        const contractType = $(this).val();
        const endDateField = $('#end_date');
        const endDateLabel = endDateField.closest('.col-md-6').find('label');
        
        if (contractType === 'permanent') {
            endDateField.prop('required', false);
            endDateLabel.find('.text-danger').remove();
            endDateField.closest('.col-md-6').find('.form-text').html('<i class="bx bx-info-circle me-1"></i>Leave blank for permanent contracts');
        } else if (contractType === 'fixed_term' || contractType === 'probation') {
            endDateField.prop('required', true);
            if (!endDateLabel.find('.text-danger').length) {
                endDateLabel.append(' <span class="text-danger">*</span>');
            }
            endDateField.closest('.col-md-6').find('.form-text').html('<i class="bx bx-info-circle me-1"></i>End date is required for ' + contractType.replace('_', ' ') + ' contracts');
        } else {
            endDateField.prop('required', false);
            endDateLabel.find('.text-danger').remove();
            endDateField.closest('.col-md-6').find('.form-text').html('<i class="bx bx-info-circle me-1"></i>Optional end date');
        }
    });

    // Trigger change on page load if contract type is already selected
    if ($('select[name="contract_type"]').val()) {
        $('select[name="contract_type"]').trigger('change');
    }

    // Set max date for start date to today
    const today = new Date().toISOString().split('T')[0];
    $('input[name="start_date"]').attr('max', today);
    
    // Set min date for end date based on start date
    $('input[name="start_date"]').on('change', function() {
        const startDate = $(this).val();
        if (startDate) {
            $('#end_date').attr('min', startDate);
        }
    });
});
</script>
@endpush

