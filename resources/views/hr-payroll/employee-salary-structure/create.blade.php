@extends('layouts.main')

@section('title', 'Assign Salary Structure')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Employee Salary Structures', 'url' => route('hr.employee-salary-structure.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Assign Structure', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0"><i class="bx bx-money me-2"></i>Assign Salary Structure</h5>
                <p class="mb-0 text-muted">Assign salary components to an employee</p>
            </div>
            <a href="{{ route('hr.employee-salary-structure.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i>Back to List
            </a>
        </div>
        <hr />

        <!-- Info Alert -->
        <div class="alert alert-info border-0 bg-light-info alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="bx bx-info-circle me-2 fs-4"></i>
                <div>
                    <strong>Salary Structure Assignment:</strong>
                    <ul class="mb-0 mt-2 ps-3">
                        <li>Select an employee and assign one or more salary components</li>
                        <li>Each component can be fixed amount, percentage, or formula-based</li>
                        <li>Set effective dates to control when the structure becomes active</li>
                        <li>At least one Basic Salary component must be included</li>
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

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

        <form method="POST" action="{{ route('hr.employee-salary-structure.store') }}" id="structureForm">
            @csrf

            <!-- Employee Selection -->
            <div class="card border-top border-0 border-4 border-primary mb-4">
                <div class="card-body">
                    <h6 class="mb-3 text-primary">
                        <i class="bx bx-user me-1"></i>Employee Selection
                    </h6>
                    <div class="row">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">
                                Employee <span class="text-danger">*</span>
                                <i class="bx bx-info-circle text-muted ms-1" 
                                   data-bs-toggle="tooltip" 
                                   title="Select the employee for whom you are assigning salary components"></i>
                            </label>
                            <select name="employee_id" 
                                    id="employee_id" 
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
                                <i class="bx bx-search me-1"></i>Type employee name or number to search
                            </div>
                        </div>
                    </div>

                    @if(isset($employee))
                    <div class="mt-3 p-3 bg-light rounded">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Current Basic Salary:</strong> {{ number_format($employee->basic_salary ?? 0, 2) }} TZS
                            </div>
                            <div class="col-md-6">
                                <strong>Department:</strong> {{ $employee->department->name ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Existing Structures Warning -->
            @if(isset($existingStructures) && $existingStructures->count() > 0)
            <div class="alert alert-warning border-0 bg-light-warning alert-dismissible fade show mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bx bx-error-circle me-2 fs-4"></i>
                    <div>
                        <strong>Existing Active Structure Found:</strong>
                        <p class="mb-1 mt-2">
                            This employee already has {{ $existingStructures->count() }} active component(s). 
                            Creating a new structure will end the existing one on the day before the new effective date.
                        </p>
                        <div class="small">
                            <strong>Current Components:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($existingStructures as $structure)
                                    <li>{{ $structure->component->component_name }} 
                                        @if($structure->amount)
                                            - {{ number_format($structure->amount, 2) }} TZS
                                        @elseif($structure->percentage)
                                            - {{ $structure->percentage }}%
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <!-- Components Assignment -->
            <div class="card border-top border-0 border-4 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 text-success">
                            <i class="bx bx-calculator me-1"></i>Salary Components
                        </h6>
                        <button type="button" class="btn btn-sm btn-success" id="addComponentBtn">
                            <i class="bx bx-plus me-1"></i>Add Component
                        </button>
                    </div>

                    <div id="componentsContainer">
                        <!-- Components will be added here dynamically -->
                    </div>

                    <div class="alert alert-warning mt-3" id="noComponentsAlert" style="display: none;">
                        <i class="bx bx-info-circle me-2"></i>
                        Please add at least one component. At least one Basic Salary component is required.
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('hr.employee-salary-structure.index') }}" class="btn btn-secondary">
                    <i class="bx bx-x me-1"></i>Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-save me-1"></i>Save Structure
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let componentIndex = 0;
    const components = @json($components ?? []);
    const earnings = components.filter(c => c.component_type === 'earning');
    const deductions = components.filter(c => c.component_type === 'deduction');

    // Initialize Select2 for employee with Bootstrap 5 theme
    $('#employee_id').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Search and Select Employee --',
        allowClear: true,
        width: '100%'
    });

    // Add component button
    $('#addComponentBtn').on('click', function() {
        addComponentRow();
    });

    function addComponentRow(componentData = null) {
        const index = componentIndex++;
        const isEdit = componentData !== null;
        const component = isEdit ? componentData.component : null;
        const componentId = isEdit ? component.id : '';
        const amount = isEdit ? componentData.amount : '';
        const percentage = isEdit ? componentData.percentage : '';
        const effectiveDate = isEdit ? componentData.effective_date : '';
        const endDate = isEdit ? (componentData.end_date || '') : '';
        const notes = isEdit ? (componentData.notes || '') : '';

        const allComponents = [...earnings, ...deductions];
        const selectedComponent = allComponents.find(c => c.id == componentId);

        let componentOptions = '<option value="">-- Select Component --</option>';
        allComponents.forEach(comp => {
            componentOptions += `<option value="${comp.id}" 
                data-type="${comp.component_type}"
                data-calculation="${comp.calculation_type}"
                ${comp.id == componentId ? 'selected' : ''}>${comp.component_name} (${comp.component_code})</option>`;
        });

        const row = `
            <div class="card mb-3 component-row" data-index="${index}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Component #${index + 1}</h6>
                        <button type="button" class="btn btn-sm btn-danger remove-component" title="Remove Component">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Component <span class="text-danger">*</span></label>
                            <select name="components[${index}][component_id]" 
                                    class="form-select component-select" 
                                    required>
                                ${componentOptions}
                            </select>
                            <div class="form-text component-info"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Effective Date <span class="text-danger">*</span></label>
                            <input type="date" 
                                   name="components[${index}][effective_date]" 
                                   class="form-control effective-date" 
                                   value="${effectiveDate || '{{ date('Y-m-d') }}'}"
                                   required>
                        </div>
                        <div class="col-md-6 amount-field" style="display: none;">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   name="components[${index}][amount]" 
                                   class="form-control component-amount" 
                                   value="${amount}"
                                   placeholder="0.00">
                            <div class="form-text">Fixed amount for this component</div>
                        </div>
                        <div class="col-md-6 percentage-field" style="display: none;">
                            <label class="form-label">Percentage <span class="text-danger">*</span></label>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   max="100" 
                                   name="components[${index}][percentage]" 
                                   class="form-control component-percentage" 
                                   value="${percentage}"
                                   placeholder="0.00">
                            <div class="form-text">Percentage of base amount</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date (Optional)</label>
                            <input type="date" 
                                   name="components[${index}][end_date]" 
                                   class="form-control end-date" 
                                   value="${endDate}">
                            <div class="form-text">Leave blank for ongoing structure</div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Notes (Optional)</label>
                            <textarea name="components[${index}][notes]" 
                                      class="form-control" 
                                      rows="2" 
                                      placeholder="Additional notes...">${notes}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#componentsContainer').append(row);
        updateComponentFields(index, selectedComponent);
        
        // Initialize Select2 with Bootstrap 5 theme for the component select
        $(`.component-row[data-index="${index}"] .component-select`).select2({
            theme: 'bootstrap-5',
            placeholder: '-- Select Component --',
            width: '100%'
        });
        
        // Show/hide no components alert
        updateNoComponentsAlert();
    }

    // Handle component selection change
    $(document).on('change', '.component-select', function() {
        const row = $(this).closest('.component-row');
        const index = row.data('index');
        const componentId = $(this).val();
        const allComponents = [...earnings, ...deductions];
        const component = allComponents.find(c => c.id == componentId);
        
        updateComponentFields(index, component);
    });

    function updateComponentFields(index, component) {
        const row = $(`.component-row[data-index="${index}"]`);
        const amountField = row.find('.amount-field');
        const percentageField = row.find('.percentage-field');
        const infoDiv = row.find('.component-info');
        
        if (!component) {
            amountField.hide();
            percentageField.hide();
            infoDiv.html('');
            return;
        }

        // Show component info
        let info = `<small class="text-muted">
            <i class="bx bx-info-circle"></i> 
            Type: <span class="badge bg-${component.component_type === 'earning' ? 'success' : 'danger'}">${component.component_type}</span> | 
            Calculation: ${component.calculation_type}
        </small>`;
        infoDiv.html(info);

        // Show/hide fields based on calculation type
        if (component.calculation_type === 'fixed') {
            amountField.show();
            percentageField.hide();
            row.find('.component-amount').prop('required', true);
            row.find('.component-percentage').prop('required', false);
        } else if (component.calculation_type === 'percentage') {
            amountField.hide();
            percentageField.show();
            row.find('.component-amount').prop('required', false);
            row.find('.component-percentage').prop('required', true);
        } else {
            // Formula - both optional
            amountField.show();
            percentageField.show();
            row.find('.component-amount').prop('required', false);
            row.find('.component-percentage').prop('required', false);
        }
    }

    // Remove component
    $(document).on('click', '.remove-component', function() {
        $(this).closest('.component-row').remove();
        updateNoComponentsAlert();
    });

    function updateNoComponentsAlert() {
        const count = $('.component-row').length;
        if (count === 0) {
            $('#noComponentsAlert').show();
        } else {
            $('#noComponentsAlert').hide();
        }
    }

    // Form validation
    $('#structureForm').on('submit', function(e) {
        const componentCount = $('.component-row').length;
        if (componentCount === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'No Components',
                text: 'Please add at least one salary component.'
            });
            return false;
        }

        // Check for Basic Salary component
        let hasBasicSalary = false;
        $('.component-select').each(function() {
            const option = $(this).find('option:selected');
            const code = option.text().toLowerCase();
            if (code.includes('basic')) {
                hasBasicSalary = true;
            }
        });

        if (!hasBasicSalary) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Missing Basic Salary',
                text: 'At least one Basic Salary component must be included in the structure.'
            });
            return false;
        }
    });

    // Add initial component if editing
    @if(isset($existingStructures) && $existingStructures->count() > 0)
        @foreach($existingStructures as $structure)
            addComponentRow({
                component: @json($structure->component),
                amount: {{ $structure->amount ?? 'null' }},
                percentage: {{ $structure->percentage ?? 'null' }},
                effective_date: '{{ $structure->effective_date->format('Y-m-d') }}',
                end_date: {{ $structure->end_date ? "'" . $structure->end_date->format('Y-m-d') . "'" : 'null' }},
                notes: {{ $structure->notes ? "'" . addslashes($structure->notes) . "'" : 'null' }}
            });
        @endforeach
    @else
        // Add one empty row to start
        addComponentRow();
    @endif

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
@endpush

