@extends('layouts.main')

@section('title', 'Bulk Assign Salary Structure')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Employee Salary Structures', 'url' => route('hr.employee-salary-structure.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Bulk Assign', 'url' => '#', 'icon' => 'bx bx-group']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0"><i class="bx bx-group me-2"></i>Bulk Assign Salary Structure</h5>
                <p class="mb-0 text-muted">Assign the same salary structure to multiple employees</p>
            </div>
            <a href="{{ route('hr.employee-salary-structure.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i>Back to List
            </a>
        </div>
        <hr />

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('bulk_errors'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Some assignments failed:</strong>
            <ul class="mb-0 mt-2">
                @foreach(session('bulk_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <form method="POST" action="{{ route('hr.employee-salary-structure.bulk-assign') }}" id="bulkAssignForm">
            @csrf

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="bx bx-user me-1"></i>Select Employees</h6>
                            
                            <div class="mb-3">
                                <label for="employee_ids" class="form-label">Employees <span class="text-danger">*</span></label>
                                <select class="form-select select2-multiple" id="employee_ids" name="employee_ids[]" multiple required>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->employee_number ?? 'N/A' }})</option>
                                    @endforeach
                                </select>
                                @error('employee_ids')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Select one or more employees to assign the salary structure to.</small>
                            </div>

                            <hr>

                            <h6 class="mb-3"><i class="bx bx-calculator me-1"></i>Salary Components</h6>
                            
                            <div id="components-container">
                                <div class="component-row mb-3 p-3 border rounded" data-template="true">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label">Component <span class="text-danger">*</span></label>
                                            <select class="form-select component-select" name="components[0][component_id]" required>
                                                <option value="">Select Component</option>
                                                @foreach($components as $component)
                                                    <option value="{{ $component->id }}" 
                                                        data-type="{{ $component->calculation_type }}"
                                                        data-code="{{ strtolower($component->component_code) }}">
                                                        {{ $component->component_code }} - {{ $component->component_name }}
                                                        ({{ ucfirst($component->component_type) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Amount</label>
                                            <input type="number" class="form-control amount-input" name="components[0][amount]" step="0.01" min="0" placeholder="0.00">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Percentage (%)</label>
                                            <input type="number" class="form-control percentage-input" name="components[0][percentage]" step="0.01" min="0" max="100" placeholder="0.00">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-danger w-100 remove-component" style="display: none;">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-outline-primary" id="add-component">
                                <i class="bx bx-plus me-1"></i>Add Component
                            </button>

                            <hr>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="effective_date" class="form-label">Effective Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('effective_date') is-invalid @enderror" 
                                            id="effective_date" name="effective_date" 
                                            value="{{ old('effective_date', date('Y-m-d')) }}" required>
                                        @error('effective_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date (Optional)</label>
                                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                                            id="end_date" name="end_date" value="{{ old('end_date') }}">
                                        @error('end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes (Optional)</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                    id="notes" name="notes" rows="3" 
                                    placeholder="Add any notes about this bulk assignment...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="mb-3">
                                <i class="bx bx-help-circle text-primary me-1"></i>Bulk Assignment Guide
                            </h6>
                            <hr>
                            
                            <div class="mb-3">
                                <h6 class="text-primary"><i class="bx bx-list-ul me-1"></i>Steps</h6>
                                <ol class="small text-muted mb-0">
                                    <li>Select multiple employees</li>
                                    <li>Add salary components</li>
                                    <li>Set amounts or percentages</li>
                                    <li>Set effective date</li>
                                    <li>Submit to assign to all</li>
                                </ol>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-primary"><i class="bx bx-info-circle me-1"></i>Important</h6>
                                <ul class="small text-muted mb-0">
                                    <li>At least one Basic Salary component required</li>
                                    <li>Fixed components need amounts</li>
                                    <li>Percentage components need percentages</li>
                                    <li>Existing structures will be ended</li>
                                </ul>
                            </div>

                            <div class="alert alert-warning mb-0">
                                <small>
                                    <i class="bx bx-shield text-warning me-1"></i>
                                    <strong>Note:</strong> This will assign the same structure to all selected employees. Individual adjustments should be made separately.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bx bx-check me-1"></i>Bulk Assign Structure
                </button>
                <a href="{{ route('hr.employee-salary-structure.index') }}" class="btn btn-secondary">
                    <i class="bx bx-x me-1"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Store the original HTML template BEFORE Select2 initialization
    const firstRow = $('.component-row[data-template="true"]').first();
    let originalRowHTML;
    
    // Get clean HTML before Select2 transforms it
    if (firstRow.length) {
        originalRowHTML = firstRow[0].outerHTML;
    }
    
    // Initialize Select2 for multiple employee selection
    $('.select2-multiple').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select employees...',
        allowClear: true
    });

    // Ensure all input fields are enabled by default BEFORE Select2 initialization
    $('.amount-input, .percentage-input').prop('disabled', false).removeAttr('disabled');
    
    // Initialize Select2 for component selects
    $('.component-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select component...'
    });
    
    // Double-check all inputs are enabled after Select2 initialization
    setTimeout(function() {
        $('.amount-input, .percentage-input').prop('disabled', false).removeAttr('disabled');
    }, 50);

    let componentIndex = 1;

    // Add component row
    $('#add-component').on('click', function() {
        if (!originalRowHTML) {
            // Fallback: destroy Select2, clone, then reinitialize
            const firstRow = $('.component-row').first();
            const originalSelect = firstRow.find('.component-select');
            const wasSelect2 = originalSelect.hasClass('select2-hidden-accessible');
            
            if (wasSelect2) {
                originalSelect.select2('destroy');
            }
            
            originalRowHTML = firstRow[0].outerHTML;
            
            if (wasSelect2) {
                originalSelect.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'Select component...'
                });
            }
        }
        
        // Create new row from original HTML template
        const newRow = $(originalRowHTML);
        
        // Remove template attribute
        newRow.removeAttr('data-template');
        
        // Update attributes
        const clonedSelect = newRow.find('.component-select');
        clonedSelect.attr('name', `components[${componentIndex}][component_id]`).val('').removeAttr('id');
        const $newAmountInput = newRow.find('.amount-input');
        const $newPercentageInput = newRow.find('.percentage-input');
        
        $newAmountInput.attr('name', `components[${componentIndex}][amount]`).val('').prop('disabled', false);
        $newPercentageInput.attr('name', `components[${componentIndex}][percentage]`).val('').prop('disabled', false);
        newRow.find('.remove-component').show();
        
        // Append to container
        $('#components-container').append(newRow);
        
        // Initialize Select2 on the new select
        clonedSelect.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select component...'
        });
        
        componentIndex++;
    });

    // Remove component row
    $(document).on('click', '.remove-component', function() {
        if ($('.component-row').length > 1) {
            $(this).closest('.component-row').remove();
        }
    });

    // Handle component type changes
    $(document).on('change', '.component-select', function() {
        const $select = $(this);
        const $row = $select.closest('.component-row');
        const selectedOption = $select.find('option:selected');
        const calcType = selectedOption.data('type');
        const $amountInput = $row.find('.amount-input');
        const $percentageInput = $row.find('.percentage-input');
        const selectedValue = $select.val();

        // Always enable both fields first
        $amountInput.prop('disabled', false).removeAttr('disabled').prop('required', false);
        $percentageInput.prop('disabled', false).removeAttr('disabled').prop('required', false);

        // Only disable/enable if a component is actually selected (not empty)
        if (selectedValue && selectedValue !== '' && calcType) {
            if (calcType === 'fixed') {
                // Fixed calculation: require amount, disable percentage
                $amountInput.prop('required', true);
                $percentageInput.prop('disabled', true).val('').attr('disabled', 'disabled');
            } else if (calcType === 'percentage') {
                // Percentage calculation: require percentage, disable amount
                $percentageInput.prop('required', true);
                $amountInput.prop('disabled', true).val('').attr('disabled', 'disabled');
            } else if (calcType === 'formula') {
                // Formula calculation: both can be used (for base values)
                // Keep both enabled but not required
            }
        }
        // If no component selected (empty value), both remain enabled and editable
    });

    // Initialize fields on page load
    $('.component-select').each(function() {
        const $select = $(this);
        const $row = $select.closest('.component-row');
        const $amountInput = $row.find('.amount-input');
        const $percentageInput = $row.find('.percentage-input');
        
        // Always ensure inputs are enabled first (before checking selection)
        $amountInput.prop('disabled', false).removeAttr('disabled');
        $percentageInput.prop('disabled', false).removeAttr('disabled');
        
        // Only trigger change handler if a component is actually selected
        const selectedValue = $select.val();
        if (selectedValue && selectedValue !== '') {
            // Small delay to ensure Select2 is ready, then trigger change
            setTimeout(function() {
                $select.trigger('change');
            }, 100);
        }
        // If no component selected, fields remain enabled (already set above)
    });

    // Form validation
    $('#bulkAssignForm').on('submit', function(e) {
        let hasBasicSalary = false;
        let basicSalaryComponent = null;
        
        $('.component-select').each(function() {
            const selectedOption = $(this).find('option:selected');
            const code = selectedOption.data('code');
            const componentName = selectedOption.text();
            
            if (code && (code.includes('basic') || code.includes('BASIC'))) {
                hasBasicSalary = true;
                basicSalaryComponent = componentName;
                return false;
            }
        });

        if (!hasBasicSalary) {
            e.preventDefault();
            
            // Find Basic Salary component option
            let basicSalaryOption = null;
            $('.component-select').first().find('option').each(function() {
                const code = $(this).data('code');
                if (code && (code.includes('basic') || code.includes('BASIC'))) {
                    basicSalaryOption = $(this);
                    return false;
                }
            });
            
            // Show SweetAlert with helpful message
            Swal.fire({
                icon: 'warning',
                title: 'Basic Salary Required',
                html: `
                    <p>At least one <strong>Basic Salary</strong> component must be included in the salary structure.</p>
                    ${basicSalaryOption ? '<p class="text-muted">Tip: Select "BASIC_SALARY - Basic Salary" from the component dropdown.</p>' : ''}
                `,
                confirmButtonText: 'OK',
                confirmButtonColor: '#0d6efd'
            });
            
            // Highlight first component row to draw attention
            $('.component-row').first().addClass('border-warning').css('border-width', '2px');
            setTimeout(function() {
                $('.component-row').first().removeClass('border-warning').css('border-width', '');
            }, 3000);
            
            // Focus on first component select
            $('.component-select').first().focus();
            
            return false;
        }
    });
    
    // Real-time validation indicator
    function checkBasicSalary() {
        let hasBasic = false;
        $('.component-select').each(function() {
            const selectedOption = $(this).find('option:selected');
            const code = selectedOption.data('code');
            if (code && (code.includes('basic') || code.includes('BASIC'))) {
                hasBasic = true;
                return false;
            }
        });
        
        // Show/hide warning badge
        if (!hasBasic && $('.component-select').first().val()) {
            if ($('#basic-salary-warning').length === 0) {
                $('#components-container').before(`
                    <div id="basic-salary-warning" class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Warning:</strong> At least one Basic Salary component is required. Please add a Basic Salary component before submitting.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
            }
        } else {
            $('#basic-salary-warning').remove();
        }
    }
    
    // Check on component selection change
    $(document).on('change', '.component-select', function() {
        checkBasicSalary();
    });
});
</script>
@endpush

