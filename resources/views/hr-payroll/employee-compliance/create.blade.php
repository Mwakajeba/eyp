@extends('layouts.main')

@section('title', 'Create Compliance Record')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumbs -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Employee Compliance', 'url' => route('hr.employee-compliance.index'), 'icon' => 'bx bx-check-circle'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">
                <i class="bx bx-check-circle me-1"></i>Create Compliance Record
            </h6>
            <a href="{{ route('hr.employee-compliance.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i>Back to List
            </a>
        </div>
        <hr />

        <!-- Info Alert -->
        <div class="alert alert-info border-0 bg-light-info alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="bx bx-info-circle me-2 fs-4"></i>
                <div>
                    <strong>Why Compliance Records Matter:</strong>
                    <ul class="mb-0 mt-2 ps-3">
                        <li>Ensures employees meet statutory requirements for payroll processing</li>
                        <li>Prevents payroll errors and compliance violations</li>
                        <li>Tracks expiry dates to avoid service interruptions</li>
                        <li>Required for accurate tax and contribution calculations</li>
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>


        <!-- Main Form Card -->
        <div class="card border-top border-0 border-4 border-primary">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.employee-compliance.store') }}" id="complianceForm">
                    @csrf

                    <!-- Employee Selection Section -->
                    <div class="mb-4">
                        <h6 class="mb-3 text-primary">
                            <i class="bx bx-user me-1"></i>Employee Information
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">
                                    Employee <span class="text-danger">*</span>
                                    <i class="bx bx-info-circle text-muted ms-1" 
                                       data-bs-toggle="tooltip" 
                                       data-bs-placement="top" 
                                       title="Select the employee for whom you are creating this compliance record. Each employee can have multiple compliance records for different types (PAYE, Pension, NHIF, etc.)."></i>
                                </label>
                                <select name="employee_id" 
                                        id="employee_id" 
                                        class="form-select select2-single @error('employee_id') is-invalid @enderror" 
                                        required>
                                    <option value="">-- Search and Select Employee --</option>
                                    @foreach($employees ?? [] as $employee)
                                        <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->full_name }} ({{ $employee->employee_number }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">
                                    <i class="bx bx-info-circle me-1"></i>
                                    Start typing to search by name or employee number. This compliance record will be linked to the selected employee.
                                </div>
                                @error('employee_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4" />

                    <!-- Compliance Details Section -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-primary">
                                <i class="bx bx-list-ul me-1"></i>Compliance Details
                            </h6>
                        </div>
                        <div class="form-text mb-3">
                            <i class="bx bx-info-circle me-1"></i>
                            Add multiple compliance records. Each line can have a different compliance type, number, validity period, and status.
                            <br>
                            <small class="text-muted">
                                <strong>Note:</strong> Each compliance type can only have one record per employee. If you add the same compliance type twice, the existing record will be updated. Validity From date is required, while Validity To is optional (leave blank for ongoing validity).
                            </small>
                        </div>
                        
                        <!-- Compliance Details Container -->
                        <div id="compliance-details-container">
                            <!-- Default row will be added by JavaScript if empty -->
                            @if(old('compliance_details'))
                                @foreach(old('compliance_details') as $index => $detail)
                                    <div class="compliance-detail-row border rounded p-3 mb-3 bg-light" data-index="{{ $index }}">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label fw-semibold small">
                                                    Compliance Type <span class="text-danger">*</span>
                                                </label>
                                                <select name="compliance_details[{{ $index }}][compliance_type]" 
                                                        class="form-select form-select-sm compliance-type-select" 
                                                        required>
                                                    <option value="">-- Select Type --</option>
                                                    <option value="paye" {{ ($detail['compliance_type'] ?? '') == 'paye' ? 'selected' : '' }}>
                                                        PAYE (TIN)
                                                    </option>
                                                    <option value="pension" {{ ($detail['compliance_type'] ?? '') == 'pension' ? 'selected' : '' }}>
                                                        Pension
                                                    </option>
                                                    <option value="nhif" {{ ($detail['compliance_type'] ?? '') == 'nhif' ? 'selected' : '' }}>
                                                        NHIF
                                                    </option>
                                                    <option value="wcf" {{ ($detail['compliance_type'] ?? '') == 'wcf' ? 'selected' : '' }}>
                                                        WCF
                                                    </option>
                                                    <option value="sdl" {{ ($detail['compliance_type'] ?? '') == 'sdl' ? 'selected' : '' }}>
                                                        SDL
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-semibold small">
                                                    Compliance Number <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" 
                                                       name="compliance_details[{{ $index }}][compliance_number]" 
                                                       class="form-control form-control-sm" 
                                                       value="{{ $detail['compliance_number'] ?? '' }}" 
                                                       placeholder="Enter number" 
                                                       required />
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label fw-semibold small">
                                                    Validity From <span class="text-danger">*</span>
                                                </label>
                                                <input type="date" 
                                                       name="compliance_details[{{ $index }}][validity_from]" 
                                                       class="form-control form-control-sm" 
                                                       value="{{ $detail['validity_from'] ?? '' }}" 
                                                       required />
                                                <small class="form-text text-muted" style="font-size: 0.75rem;">
                                                    Start date
                                                </small>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label fw-semibold small">
                                                    Validity To
                                                </label>
                                                <input type="date" 
                                                       name="compliance_details[{{ $index }}][validity_to]" 
                                                       class="form-control form-control-sm" 
                                                       value="{{ $detail['validity_to'] ?? '' }}" />
                                                <small class="form-text text-muted" style="font-size: 0.75rem;">
                                                    End date (optional)
                                                </small>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label fw-semibold small d-block mb-1">
                                                    Status
                                                </label>
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           name="compliance_details[{{ $index }}][is_valid]" 
                                                           value="1" 
                                                           {{ ($detail['is_valid'] ?? true) ? 'checked' : '' }}>
                                                    <label class="form-check-label small">
                                                        Valid
                                                    </label>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-danger remove-compliance-detail w-100" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                                    <i class="bx bx-trash me-1"></i>Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <!-- Empty state - will be populated by JavaScript -->
                            @endif
                        </div>
                        
                            <button type="button" class="btn btn-sm btn-primary" id="add-compliance-detail">
                                <i class="bx bx-plus me-1"></i>Add Line
                            </button>
                        @error('compliance_details')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @error('compliance_details.*')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex gap-2 mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bx bx-save me-1"></i>Save Compliance Record
                        </button>
                        <a href="{{ route('hr.employee-compliance.index') }}" class="btn btn-outline-secondary px-4">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                    </div>
                </form>
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

    .border-primary {
        border-color: #0d6efd !important;
    }

    .form-label {
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .form-text {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }

    .form-switch-lg .form-check-input {
        width: 3rem;
        height: 1.5rem;
    }

    .form-switch-lg .form-check-label {
        padding-left: 0.75rem;
        font-size: 1rem;
    }

    .select2-container {
        width: 100% !important;
    }

    .alert-info {
        background-color: #e7f3ff;
        border-color: #b3d9ff;
    }

    .compliance-detail-row {
        background-color: #f8f9fa;
        transition: all 0.2s ease;
    }

    .compliance-detail-row:hover {
        background-color: #e9ecef;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .compliance-detail-row .form-label.small {
        font-size: 0.875rem;
        margin-bottom: 0.35rem;
    }

    .compliance-detail-row .form-select-sm,
    .compliance-detail-row .form-control-sm {
        font-size: 0.875rem;
        padding: 0.35rem 0.75rem;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let complianceDetailIndex = {{ old('compliance_details') ? count(old('compliance_details')) : 0 }};

    // Add new compliance detail line
    $('#add-compliance-detail').on('click', function() {
        const rowHtml = `
            <div class="compliance-detail-row border rounded p-3 mb-3 bg-light" data-index="${complianceDetailIndex}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">
                            Compliance Type <span class="text-danger">*</span>
                        </label>
                        <select name="compliance_details[${complianceDetailIndex}][compliance_type]" 
                                class="form-select form-select-sm compliance-type-select" 
                                required>
                            <option value="">-- Select Type --</option>
                            <option value="paye">PAYE (TIN)</option>
                            <option value="pension">Pension</option>
                            <option value="nhif">NHIF</option>
                            <option value="wcf">WCF</option>
                            <option value="sdl">SDL</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">
                            Compliance Number <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               name="compliance_details[${complianceDetailIndex}][compliance_number]" 
                               class="form-control form-control-sm" 
                               placeholder="Enter number" 
                               required />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">
                            Validity From <span class="text-danger">*</span>
                        </label>
                        <input type="date" 
                               name="compliance_details[${complianceDetailIndex}][validity_from]" 
                               class="form-control form-control-sm" 
                               required />
                        <small class="form-text text-muted" style="font-size: 0.75rem;">
                            Start date
                        </small>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">
                            Validity To
                        </label>
                        <input type="date" 
                               name="compliance_details[${complianceDetailIndex}][validity_to]" 
                               class="form-control form-control-sm" />
                        <small class="form-text text-muted" style="font-size: 0.75rem;">
                            End date (optional)
                        </small>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small d-block mb-1">
                            Status
                        </label>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="compliance_details[${complianceDetailIndex}][is_valid]" 
                                   value="1" 
                                   checked>
                            <label class="form-check-label small">
                                Valid
                            </label>
                        </div>
                        <button type="button" class="btn btn-sm btn-danger remove-compliance-detail w-100" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                            <i class="bx bx-trash me-1"></i>Remove
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#compliance-details-container').append(rowHtml);
        complianceDetailIndex++;
        
        // If this is the first row, ensure at least one row exists
        checkComplianceDetailsCount();
    });

    // Remove compliance detail line
    $(document).on('click', '.remove-compliance-detail', function() {
        $(this).closest('.compliance-detail-row').remove();
        checkComplianceDetailsCount();
    });

    // Ensure at least one compliance detail line exists
    function checkComplianceDetailsCount() {
        const count = $('.compliance-detail-row').length;
        if (count === 0) {
            $('#add-compliance-detail').trigger('click');
        }
    }

    // Initialize with at least one row if empty
    if ($('.compliance-detail-row').length === 0) {
        $('#add-compliance-detail').trigger('click');
    }

    // Initialize Select2 for employee selection
    if ($('#employee_id').length && typeof $.fn.select2 !== 'undefined') {
        $('#employee_id').select2({
            placeholder: 'Search and select employee...',
            allowClear: true,
            width: '100%',
            theme: 'bootstrap-5'
        });
    }


    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });


    // Validate compliance details validity dates
    $(document).on('change', 'input[name*="[validity_from]"], input[name*="[validity_to]"]', function() {
        const row = $(this).closest('.compliance-detail-row');
        const validityFrom = row.find('input[name*="[validity_from]"]').val();
        const validityTo = row.find('input[name*="[validity_to]"]').val();
        
        if (validityFrom && validityTo && new Date(validityTo) < new Date(validityFrom)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Date Range',
                text: 'Validity To date must be after Validity From date.',
                confirmButtonText: 'OK'
            });
            row.find('input[name*="[validity_to]"]').val('');
        }
    });

    // Form validation
    $('#complianceForm').on('submit', function(e) {
        const employeeId = $('#employee_id').val();
        const detailRows = $('.compliance-detail-row').length;

        if (!employeeId) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please select an employee.',
                confirmButtonText: 'OK'
            });
            $('#employee_id').focus();
            return false;
        }

        if (detailRows === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please add at least one compliance detail line.',
                confirmButtonText: 'OK'
            });
            return false;
        }

        // Validate each compliance detail row
        let hasError = false;
        $('.compliance-detail-row').each(function(index) {
            const row = $(this);
            const complianceType = row.find('select[name*="[compliance_type]"]').val();
            const complianceNumber = row.find('input[name*="[compliance_number]"]').val();
            const validityFrom = row.find('input[name*="[validity_from]"]').val();
            const validityTo = row.find('input[name*="[validity_to]"]').val();
            
            if (!complianceType) {
                hasError = true;
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: `Please select a compliance type for line ${index + 1}.`,
                    confirmButtonText: 'OK'
                });
                return false;
            }
            
            if (!complianceNumber || !validityFrom) {
                hasError = true;
                return false;
            }
            
            if (validityTo && new Date(validityTo) < new Date(validityFrom)) {
                hasError = true;
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date Range',
                    text: `Line ${index + 1}: Validity To date must be after Validity From date.`,
                    confirmButtonText: 'OK'
                });
                return false;
            }
        });

        if (hasError) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endpush
