@extends('layouts.main')

@section('title', 'Edit Compliance Record')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumbs -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Employee Compliance', 'url' => route('hr.employee-compliance.index'), 'icon' => 'bx bx-check-circle'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">
                <i class="bx bx-edit me-1"></i>Edit Compliance Record
            </h6>
            <div class="d-flex gap-2">
                <a href="{{ route('hr.employee-compliance.show', $employeeCompliance->id) }}" class="btn btn-outline-info">
                    <i class="bx bx-show me-1"></i>View Details
                </a>
                <a href="{{ route('hr.employee-compliance.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to List
                </a>
            </div>
        </div>
        <hr />

        <!-- Info Alert -->
        <div class="alert alert-info border-0 bg-light-info alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="bx bx-info-circle me-2 fs-4"></i>
                <div>
                    <strong>Editing Compliance Record:</strong>
                    <ul class="mb-0 mt-2 ps-3">
                        <li>Employee and compliance type cannot be changed after creation</li>
                        <li>Update the compliance number, expiry date, or validity status as needed</li>
                        <li>Changes will affect payroll processing eligibility</li>
                        <li>Ensure all information is accurate before saving</li>
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <!-- Main Form Card -->
        <div class="card border-top border-0 border-4 border-primary">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.employee-compliance.update', $employeeCompliance->id) }}" id="complianceForm">
                    @csrf
                    @method('PUT')

                    <!-- Employee Information Section (Read-only) -->
                    <div class="mb-4">
                        <h6 class="mb-3 text-primary">
                            <i class="bx bx-user me-1"></i>Employee Information
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">
                                    Employee
                                    <i class="bx bx-info-circle text-muted ms-1" 
                                       data-bs-toggle="tooltip" 
                                       data-bs-placement="top" 
                                       title="The employee for this compliance record. This cannot be changed after creation."></i>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bx bx-user text-primary"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control bg-light" 
                                           value="{{ $employeeCompliance->employee->full_name }} ({{ $employeeCompliance->employee->employee_number }})" 
                                           readonly />
                                </div>
                                <input type="hidden" name="employee_id" value="{{ $employeeCompliance->employee_id }}" />
                                <div class="form-text">
                                    <i class="bx bx-lock me-1"></i>
                                    Employee cannot be changed. To change the employee, create a new compliance record.
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4" />

                    <!-- Compliance Details Section -->
                    <div class="mb-4">
                        <h6 class="mb-3 text-primary">
                            <i class="bx bx-file me-1"></i>Compliance Details
                        </h6>
                        <div class="row g-3">
                            <!-- Compliance Type (Read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Compliance Type
                                    <i class="bx bx-info-circle text-muted ms-1" 
                                       data-bs-toggle="tooltip" 
                                       data-bs-placement="top" 
                                       title="The type of statutory compliance. This cannot be changed after creation."></i>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bx bx-file text-info"></i>
                                    </span>
                                    @php
                                        $typeDescriptions = [
                                            'paye' => 'Tax Identification Number',
                                            'pension' => 'Social Security Fund',
                                            'nhif' => 'National Health Insurance Fund',
                                            'wcf' => 'Workers Compensation Fund',
                                            'sdl' => 'Skills Development Levy'
                                        ];
                                        $typeDescription = $typeDescriptions[$employeeCompliance->compliance_type] ?? 'Other';
                                    @endphp
                                    <input type="text" 
                                           class="form-control bg-light" 
                                           value="{{ strtoupper($employeeCompliance->compliance_type) }} - {{ $typeDescription }}" 
                                           readonly />
                                </div>
                                <input type="hidden" name="compliance_type" value="{{ $employeeCompliance->compliance_type }}" />
                                <div class="form-text">
                                    <i class="bx bx-lock me-1"></i>
                                    Compliance type cannot be changed. Each employee can only have one record per type.
                                </div>
                            </div>

                            <!-- Compliance Number (for backward compatibility) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Primary Compliance Number
                                    <i class="bx bx-info-circle text-muted ms-1" 
                                       data-bs-toggle="tooltip" 
                                       data-bs-placement="top" 
                                       title="Primary compliance number. Additional details can be added below."></i>
                                </label>
                                <input type="text" 
                                       name="compliance_number" 
                                       id="compliance_number"
                                       class="form-control @error('compliance_number') is-invalid @enderror" 
                                       value="{{ old('compliance_number', $employeeCompliance->compliance_number) }}" 
                                       placeholder="Enter compliance number (e.g., TIN, Membership No.)" />
                                <div class="form-text">
                                    <i class="bx bx-info-circle me-1"></i>
                                    Primary compliance number. Additional details with validity periods can be added below.
                                </div>
                                @error('compliance_number')
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
                            <button type="button" class="btn btn-sm btn-primary" id="add-compliance-detail">
                                <i class="bx bx-plus me-1"></i>Add Line
                            </button>
                        </div>
                        <div class="form-text mb-3">
                            <i class="bx bx-info-circle me-1"></i>
                            Add multiple compliance detail lines with their validity periods. Each line can have a different compliance number and validity period.
                        </div>
                        
                        <!-- Compliance Details Container -->
                        <div id="compliance-details-container">
                            @php
                                $existingDetails = old('compliance_details', $employeeCompliance->compliance_details ?? []);
                                // If no compliance_details exist but compliance_number exists, create a default entry
                                if (empty($existingDetails) && $employeeCompliance->compliance_number) {
                                    $existingDetails = [[
                                        'compliance_number' => $employeeCompliance->compliance_number,
                                        'validity_from' => $employeeCompliance->expiry_date ? $employeeCompliance->expiry_date->format('Y-m-d') : now()->format('Y-m-d'),
                                        'validity_to' => $employeeCompliance->expiry_date ? $employeeCompliance->expiry_date->format('Y-m-d') : null,
                                    ]];
                                }
                            @endphp
                            @if(!empty($existingDetails))
                                @foreach($existingDetails as $index => $detail)
                                    <div class="compliance-detail-row border rounded p-3 mb-3" data-index="{{ $index }}">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold">
                                                    Compliance Number <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" 
                                                       name="compliance_details[{{ $index }}][compliance_number]" 
                                                       class="form-control" 
                                                       value="{{ $detail['compliance_number'] ?? '' }}" 
                                                       placeholder="Enter compliance number" 
                                                       required />
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-semibold">
                                                    Validity From <span class="text-danger">*</span>
                                                </label>
                                                <input type="date" 
                                                       name="compliance_details[{{ $index }}][validity_from]" 
                                                       class="form-control" 
                                                       value="{{ $detail['validity_from'] ?? '' }}" 
                                                       required />
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-semibold">
                                                    Validity To
                                                </label>
                                                <input type="date" 
                                                       name="compliance_details[{{ $index }}][validity_to]" 
                                                       class="form-control" 
                                                       value="{{ $detail['validity_to'] ?? '' }}" />
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button type="button" class="btn btn-sm btn-danger remove-compliance-detail w-100">
                                                    <i class="bx bx-trash me-1"></i>Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        
                        @error('compliance_details')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @error('compliance_details.*')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr class="my-4" />

                    <!-- Validity & Expiry Section -->
                    <div class="mb-4">
                        <h6 class="mb-3 text-primary">
                            <i class="bx bx-calendar-check me-1"></i>Validity & Expiry
                        </h6>
                        <div class="row g-3">
                            <!-- Expiry Date -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Expiry Date
                                    <i class="bx bx-info-circle text-muted ms-1" 
                                       data-bs-toggle="tooltip" 
                                       data-bs-placement="top" 
                                       title="Set the date when this compliance record expires. Leave blank if it does not expire. The system will alert you when expiry is approaching."></i>
                                </label>
                                <input type="date" 
                                       name="expiry_date" 
                                       id="expiry_date"
                                       class="form-control @error('expiry_date') is-invalid @enderror" 
                                       value="{{ old('expiry_date', $employeeCompliance->expiry_date ? $employeeCompliance->expiry_date->format('Y-m-d') : '') }}" />
                                <div class="form-text">
                                    <i class="bx bx-info-circle me-1"></i>
                                    Leave blank if the compliance does not expire. The system will send alerts 30 days before expiry.
                                    @if($employeeCompliance->expiry_date)
                                        <br><span class="text-muted">
                                            @php
                                                $daysRemaining = round(now()->diffInDays($employeeCompliance->expiry_date, false));
                                            @endphp
                                            @if($employeeCompliance->expiry_date < now())
                                                <i class="bx bx-error text-danger me-1"></i>Expired {{ abs($daysRemaining) }} {{ abs($daysRemaining) == 1 ? 'day' : 'days' }} ago
                                            @elseif($daysRemaining <= 30)
                                                <i class="bx bx-time text-warning me-1"></i>Expiring in {{ $daysRemaining }} {{ $daysRemaining == 1 ? 'day' : 'days' }}
                                            @else
                                                <i class="bx bx-check text-success me-1"></i>Valid for {{ $daysRemaining }} more {{ $daysRemaining == 1 ? 'day' : 'days' }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                                @error('expiry_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Is Valid Toggle -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold d-block mb-2">
                                    Compliance Status
                                    <i class="bx bx-info-circle text-muted ms-1" 
                                       data-bs-toggle="tooltip" 
                                       data-bs-placement="top" 
                                       title="Mark this compliance record as valid if it is currently active and meets all requirements. Invalid records will block payroll processing."></i>
                                </label>
                                <div class="form-check form-switch form-switch-lg">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="is_valid" 
                                           id="is_valid" 
                                           value="1" 
                                           {{ old('is_valid', $employeeCompliance->is_valid) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-normal" for="is_valid">
                                        <span id="valid_status_text">{{ $employeeCompliance->is_valid ? 'Valid & Active' : 'Invalid or Pending' }}</span>
                                    </label>
                                </div>
                                <div class="form-text mt-2">
                                    <i class="bx bx-info-circle me-1"></i>
                                    <span id="valid_status_help">
                                        @if($employeeCompliance->is_valid)
                                            When checked, this compliance is considered valid and the employee can be included in payroll. Uncheck if the compliance is pending, expired, or invalid.
                                        @else
                                            This compliance is marked as invalid. The employee may be excluded from payroll until this is resolved.
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex gap-2 mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bx bx-save me-1"></i>Update Compliance Record
                        </button>
                        <a href="{{ route('hr.employee-compliance.show', $employeeCompliance->id) }}" class="btn btn-outline-info px-4">
                            <i class="bx bx-show me-1"></i>View Details
                        </a>
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

    .input-group-text {
        border-right: none;
    }

    .input-group .form-control:not(:first-child) {
        border-left: none;
    }

    .bg-light {
        background-color: #f8f9fa !important;
    }

    .alert-info {
        background-color: #e7f3ff;
        border-color: #b3d9ff;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let complianceDetailIndex = {{ !empty(old('compliance_details')) ? count(old('compliance_details')) : (!empty($employeeCompliance->compliance_details) ? count($employeeCompliance->compliance_details) : 0) }};

    // Add new compliance detail line
    $('#add-compliance-detail').on('click', function() {
        const rowHtml = `
            <div class="compliance-detail-row border rounded p-3 mb-3" data-index="${complianceDetailIndex}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Compliance Number <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               name="compliance_details[${complianceDetailIndex}][compliance_number]" 
                               class="form-control" 
                               placeholder="Enter compliance number" 
                               required />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            Validity From <span class="text-danger">*</span>
                        </label>
                        <input type="date" 
                               name="compliance_details[${complianceDetailIndex}][validity_from]" 
                               class="form-control" 
                               required />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            Validity To
                        </label>
                        <input type="date" 
                               name="compliance_details[${complianceDetailIndex}][validity_to]" 
                               class="form-control" />
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-danger remove-compliance-detail w-100">
                            <i class="bx bx-trash me-1"></i>Remove
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#compliance-details-container').append(rowHtml);
        complianceDetailIndex++;
        
        // Ensure at least one row exists
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

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Dynamic help text based on compliance type
    const complianceTypeHelp = {
        'paye': 'PAYE (Pay As You Earn) - Tax Identification Number issued by Tanzania Revenue Authority (TRA). Required for all employees earning above the tax threshold.',
        'pension': 'Pension - Social Security Fund membership (NSSF/PSSSF). Required for employees contributing to pension schemes. Both employee and employer contributions are tracked.',
        'nhif': 'NHIF - National Health Insurance Fund membership. Required for employees enrolled in the national health insurance scheme.',
        'wcf': 'WCF - Workers Compensation Fund. Employer contribution required for workplace injury coverage. Industry-based rates apply.',
        'sdl': 'SDL - Skills Development Levy. Employer-only contribution based on gross payroll percentage. Used for employee training and development programs.'
    };

    const complianceNumberPlaceholders = {
        'paye': 'Enter TIN (Tax Identification Number)',
        'pension': 'Enter Pension/Social Fund Membership Number',
        'nhif': 'Enter NHIF Membership Number',
        'wcf': 'Enter WCF Registration Number (if applicable)',
        'sdl': 'Enter SDL Registration Number (if applicable)'
    };

    // Set placeholder based on current compliance type
    const currentType = '{{ $employeeCompliance->compliance_type }}';
    if (complianceNumberPlaceholders[currentType]) {
        $('#compliance_number').attr('placeholder', complianceNumberPlaceholders[currentType]);
        $('#compliance_number_help').text(complianceTypeHelp[currentType] || 'Enter the official number if available. This helps with verification and reporting.');
    }

    // Toggle valid status text
    $('#is_valid').on('change', function() {
        if ($(this).is(':checked')) {
            $('#valid_status_text').text('Valid & Active');
            $('#valid_status_help').text('When checked, this compliance is considered valid and the employee can be included in payroll. Uncheck if the compliance is pending, expired, or invalid.');
        } else {
            $('#valid_status_text').text('Invalid or Pending');
            $('#valid_status_help').text('This compliance is marked as invalid. The employee may be excluded from payroll until this is resolved.');
        }
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
        const detailRows = $('.compliance-detail-row').length;

        // Validate each compliance detail row
        let hasError = false;
        $('.compliance-detail-row').each(function() {
            const row = $(this);
            const complianceNumber = row.find('input[name*="[compliance_number]"]').val();
            const validityFrom = row.find('input[name*="[validity_from]"]').val();
            const validityTo = row.find('input[name*="[validity_to]"]').val();
            
            if (!complianceNumber || !validityFrom) {
                hasError = true;
                return false;
            }
            
            if (validityTo && new Date(validityTo) < new Date(validityFrom)) {
                hasError = true;
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date Range',
                    text: 'Validity To date must be after Validity From date.',
                    confirmButtonText: 'OK'
                });
                return false;
            }
        });

        if (hasError) {
            e.preventDefault();
            return false;
        }

        // Check if expiry date is in the past
        const expiryDate = $('#expiry_date').val();
        if (expiryDate && new Date(expiryDate) < new Date()) {
            Swal.fire({
                icon: 'warning',
                title: 'Expired Date',
                text: 'The expiry date you entered is in the past. Are you sure you want to continue?',
                showCancelButton: true,
                confirmButtonText: 'Yes, Continue',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (!result.isConfirmed) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
});
</script>
@endpush
