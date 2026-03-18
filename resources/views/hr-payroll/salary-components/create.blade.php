@extends('layouts.main')

@section('title', 'Create Salary Component')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Salary Components', 'url' => route('hr.salary-components.index'), 'icon' => 'bx bx-calculator'],
                ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0"><i class="bx bx-calculator me-2"></i>Create Salary Component</h5>
                    <p class="mb-0 text-muted">Define a new salary component for earnings or deductions</p>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('hr.salary-components.store') }}" method="POST">
                                @csrf

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="component_code" class="form-label">Component Code <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('component_code') is-invalid @enderror" 
                                                id="component_code" name="component_code" 
                                                value="{{ old('component_code') }}" 
                                                placeholder="e.g., BASIC_SALARY, HOUSE_ALLOWANCE" required>
                                            @error('component_code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Unique code for this component (uppercase, underscores)</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="component_name" class="form-label">Component Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('component_name') is-invalid @enderror" 
                                                id="component_name" name="component_name" 
                                                value="{{ old('component_name') }}" 
                                                placeholder="e.g., Basic Salary, House Allowance" required>
                                            @error('component_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="component_type" class="form-label">Component Type <span class="text-danger">*</span></label>
                                            <select class="form-select @error('component_type') is-invalid @enderror" 
                                                id="component_type" name="component_type" required>
                                                <option value="">Select Type</option>
                                                <option value="earning" {{ old('component_type') == 'earning' ? 'selected' : '' }}>Earning</option>
                                                <option value="deduction" {{ old('component_type') == 'deduction' ? 'selected' : '' }}>Deduction</option>
                                            </select>
                                            @error('component_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="calculation_type" class="form-label">Calculation Type <span class="text-danger">*</span></label>
                                            <select class="form-select @error('calculation_type') is-invalid @enderror" 
                                                id="calculation_type" name="calculation_type" required>
                                                <option value="">Select Calculation Type</option>
                                                <option value="fixed" {{ old('calculation_type') == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                                <option value="percentage" {{ old('calculation_type') == 'percentage' ? 'selected' : '' }}>Percentage of Base</option>
                                                <option value="formula" {{ old('calculation_type') == 'formula' ? 'selected' : '' }}>Formula-Based</option>
                                            </select>
                                            @error('calculation_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">How this component is calculated</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3" id="formula_field" style="display: none;">
                                    <label for="calculation_formula" class="form-label">Calculation Formula</label>
                                    <input type="text" class="form-control @error('calculation_formula') is-invalid @enderror" 
                                        id="calculation_formula" name="calculation_formula" 
                                        value="{{ old('calculation_formula') }}" 
                                        placeholder="e.g., {base} * 0.1 + {amount}">
                                    @error('calculation_formula')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">
                                        <strong>Available Placeholders:</strong><br>
                                        • <code>{base}</code> - Base salary amount (contract or employee basic salary)<br>
                                        • <code>{amount}</code> - Fixed amount from employee structure (e.g., 50000)<br>
                                        • <code>{percentage}</code> - Percentage value from employee structure (e.g., 10 for 10%)<br>
                                        <strong>Examples:</strong><br>
                                        • <code>{base} * 0.15 + {amount}</code> - 15% of base + fixed amount<br>
                                        • <code>{base} * ({percentage} / 100)</code> - Percentage of base (if percentage = 10, calculates 10%)<br>
                                        • <code>{base} * 0.1 + {amount} * 0.5</code> - Complex calculation
                                    </small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="floor_amount" class="form-label">Minimum Amount (Floor)</label>
                                            <input type="number" step="0.01" min="0" 
                                                class="form-control @error('floor_amount') is-invalid @enderror" 
                                                id="floor_amount" name="floor_amount" 
                                                value="{{ old('floor_amount') }}" 
                                                placeholder="0.00">
                                            @error('floor_amount')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Minimum value for this component</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="ceiling_amount" class="form-label">Maximum Amount (Ceiling)</label>
                                            <input type="number" step="0.01" min="0" 
                                                class="form-control @error('ceiling_amount') is-invalid @enderror" 
                                                id="ceiling_amount" name="ceiling_amount" 
                                                value="{{ old('ceiling_amount') }}" 
                                                placeholder="0.00">
                                            @error('ceiling_amount')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Maximum value for this component</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                        id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_taxable" 
                                                    id="is_taxable" value="1" 
                                                    {{ old('is_taxable', true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_taxable">
                                                    Taxable
                                                </label>
                                            </div>
                                            <small class="text-muted">Subject to PAYE tax</small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_pensionable" 
                                                    id="is_pensionable" value="1" 
                                                    {{ old('is_pensionable', false) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_pensionable">
                                                    Pensionable
                                                </label>
                                            </div>
                                            <small class="text-muted">Subject to pension contribution</small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_nhif_applicable" 
                                                    id="is_nhif_applicable" value="1" 
                                                    {{ old('is_nhif_applicable', true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_nhif_applicable">
                                                    NHIF Applicable
                                                </label>
                                            </div>
                                            <small class="text-muted">Subject to NHIF contribution</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="display_order" class="form-label">Display Order</label>
                                            <input type="number" min="0" 
                                                class="form-control @error('display_order') is-invalid @enderror" 
                                                id="display_order" name="display_order" 
                                                value="{{ old('display_order', 0) }}">
                                            @error('display_order')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Order for display (lower numbers appear first)</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check form-switch mt-4">
                                                <input class="form-check-input" type="checkbox" name="is_active" 
                                                    id="is_active" value="1" 
                                                    {{ old('is_active', true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_active">
                                                    Active
                                                </label>
                                            </div>
                                            <small class="text-muted">Inactive components cannot be assigned</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('hr.salary-components.index') }}" class="btn btn-secondary">
                                        <i class="bx bx-x me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i>Create Component
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('#calculation_type').on('change', function() {
        if ($(this).val() === 'formula') {
            $('#formula_field').show();
        } else {
            $('#formula_field').hide();
        }
    });

    // Trigger on page load if value exists
    if ($('#calculation_type').val() === 'formula') {
        $('#formula_field').show();
    }
});
</script>
@endpush

