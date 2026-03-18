@extends('layouts.main')

@section('title', 'Edit Salary Structure Template')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Salary Structure Templates', 'url' => route('hr.salary-structure-templates.index'), 'icon' => 'bx bx-template'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Salary Structure Template</h5>
                <p class="mb-0 text-muted">Update template: {{ $template->template_name }}</p>
            </div>
            <a href="{{ route('hr.salary-structure-templates.index') }}" class="btn btn-outline-secondary">
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

        <form method="POST" action="{{ route('hr.salary-structure-templates.update', $template->id) }}" id="templateForm">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="bx bx-info-circle me-1"></i>Template Information</h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="template_name" class="form-label">Template Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('template_name') is-invalid @enderror" 
                                            id="template_name" name="template_name" 
                                            value="{{ old('template_name', $template->template_name) }}" required>
                                        @error('template_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="template_code" class="form-label">Template Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('template_code') is-invalid @enderror" 
                                            id="template_code" name="template_code" 
                                            value="{{ old('template_code', $template->template_code) }}" required>
                                        @error('template_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                    id="description" name="description" rows="3">{{ old('description', $template->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                        {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>

                            <hr>

                            <h6 class="mb-3"><i class="bx bx-calculator me-1"></i>Salary Components</h6>
                            
                            <div id="components-container">
                                @foreach($template->templateComponents as $index => $templateComponent)
                                <div class="component-row mb-3 p-3 border rounded">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <label class="form-label">Component <span class="text-danger">*</span></label>
                                            <select class="form-select component-select" name="components[{{ $index }}][component_id]" required>
                                                <option value="">Select Component</option>
                                                @foreach($components as $component)
                                                    <option value="{{ $component->id }}" 
                                                        data-type="{{ $component->calculation_type }}"
                                                        data-code="{{ strtolower($component->component_code) }}"
                                                        {{ $templateComponent->component_id == $component->id ? 'selected' : '' }}>
                                                        {{ $component->component_code }} - {{ $component->component_name }}
                                                        ({{ ucfirst($component->component_type) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Amount</label>
                                            <input type="number" class="form-control amount-input" 
                                                name="components[{{ $index }}][amount]" 
                                                value="{{ $templateComponent->amount }}" 
                                                step="0.01" min="0" placeholder="0.00">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Percentage (%)</label>
                                            <input type="number" class="form-control percentage-input" 
                                                name="components[{{ $index }}][percentage]" 
                                                value="{{ $templateComponent->percentage }}" 
                                                step="0.01" min="0" max="100" placeholder="0.00">
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-danger w-100 remove-component" 
                                                {{ $template->templateComponents->count() <= 1 ? 'style="display:none;"' : '' }}>
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <button type="button" class="btn btn-outline-primary" id="add-component">
                                <i class="bx bx-plus me-1"></i>Add Component
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="mb-3">
                                <i class="bx bx-help-circle text-primary me-1"></i>Edit Guide
                            </h6>
                            <hr>
                            
                            <div class="alert alert-warning mb-3">
                                <small>
                                    <i class="bx bx-shield text-warning me-1"></i>
                                    <strong>Note:</strong> Updating this template will not affect employees who already have this template applied. Only new applications will use the updated structure.
                                </small>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-primary"><i class="bx bx-info-circle me-1"></i>Important</h6>
                                <ul class="small text-muted mb-0">
                                    <li>At least one Basic Salary required</li>
                                    <li>Fixed components need amounts</li>
                                    <li>Percentage components need percentages</li>
                                    <li>Template code must be unique</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-save me-1"></i>Update Template
                </button>
                <a href="{{ route('hr.salary-structure-templates.index') }}" class="btn btn-secondary">
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
    // Initialize Select2 for component selects
    $('.component-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select component...'
    });

    let componentIndex = {{ $template->templateComponents->count() }};

    // Add component row
    $('#add-component').on('click', function() {
        const newRow = $('.component-row').first().clone();
        newRow.find('select').attr('name', `components[${componentIndex}][component_id]`).val('').trigger('change');
        newRow.find('.amount-input').attr('name', `components[${componentIndex}][amount]`).val('');
        newRow.find('.percentage-input').attr('name', `components[${componentIndex}][percentage]`).val('');
        newRow.find('.remove-component').show();
        
        // Reinitialize Select2
        newRow.find('.component-select').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select component...'
        });
        
        $('#components-container').append(newRow);
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
        const $row = $(this).closest('.component-row');
        const selectedOption = $(this).find('option:selected');
        const calcType = selectedOption.data('type');
        const $amountInput = $row.find('.amount-input');
        const $percentageInput = $row.find('.percentage-input');

        if (calcType === 'fixed') {
            $amountInput.prop('required', true);
            $percentageInput.prop('disabled', true);
        } else if (calcType === 'percentage') {
            $percentageInput.prop('required', true);
            $amountInput.prop('disabled', true);
        } else {
            $amountInput.prop('disabled', false);
            $percentageInput.prop('disabled', false);
        }
    });

    // Initialize component types on load
    $('.component-select').each(function() {
        $(this).trigger('change');
    });
});
</script>
@endpush

