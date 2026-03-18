@extends('layouts.main')

@section('title', 'New Leave Request')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Leave Management', 'url' => route('hr.leave.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Leave Requests', 'url' => route('hr.leave.requests.index'), 'icon' => 'bx bx-file'],
            ['label' => 'New Request', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="mb-0 text-uppercase">LEAVE REQUEST FORM</h6>
                <p class="text-muted mb-0 small">Complete all sections below to submit your leave request</p>
            </div>
            <a href="{{ route('hr.leave.requests.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i> Back to List
            </a>
        </div>
        <hr />

        <form action="{{ route('hr.leave.requests.store') }}" method="POST" enctype="multipart/form-data" id="leaveRequestForm">
            @csrf

            <!-- SECTION 1: EMPLOYEE & CONTEXT (AUTO-FILLED) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bx bx-user-circle me-2"></i>SECTION 1: EMPLOYEE & CONTEXT
                        <small class="ms-2 opacity-75">(Auto-filled - Read Only)</small>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border-0 mb-4">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-info-circle fs-4 me-3"></i>
                            <div>
                                <strong>Security Notice:</strong> These fields are automatically filled from your employee record to prevent errors, impersonation, and ensure payroll accuracy. They cannot be edited.
                            </div>
                        </div>
                    </div>

                    @if($canCreateForOthers ?? false)
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label for="employee_id" class="form-label fw-semibold">
                                    <i class="bx bx-user me-1 text-primary"></i>Select Employee 
                                    <span class="text-danger">*</span>
                                </label>
                                <select name="employee_id" id="employee_id" 
                                    class="form-select select2-single @error('employee_id') is-invalid @enderror" 
                                    required>
                                    <option value="">-- Search and Select Employee --</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}" 
                                            data-employee-number="{{ $emp->employee_number }}"
                                            data-department="{{ $emp->department?->name ?? 'N/A' }}"
                                            data-position="{{ $emp->position?->title ?? 'N/A' }}"
                                            data-employment-type="{{ $emp->employment_type ?? 'N/A' }}"
                                            {{ old('employee_id', $employee->id ?? '') == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->full_name }} ({{ $emp->employee_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('employee_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @else
                        <input type="hidden" name="employee_id" id="employee_id" value="{{ $employee->id }}">
                    @endif

                    <!-- Visual Card Layout for Employee Information -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card border-primary h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar-circle bg-primary text-white me-3" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold;">
                                            {{ strtoupper(substr($employee->full_name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div>
                                            <h5 class="mb-0" id="display_employee_name">{{ $employee->full_name ?? '-' }}</h5>
                                            <small class="text-muted">Employee #: <span id="display_employee_number" class="fw-bold">{{ $employee->employee_number ?? '-' }}</span></small>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Department</small>
                                            <span class="fw-semibold" id="display_department">{{ $employee->department?->name ?? 'N/A' }}</span>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Position</small>
                                            <span class="fw-semibold" id="display_position">{{ $employee->position?->title ?? 'N/A' }}</span>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Employment Type</small>
                                            <span class="fw-semibold" id="display_employment_type">{{ ucfirst(str_replace('_', ' ', $employee->employment_type ?? 'N/A')) }}</span>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Payroll Period</small>
                                            <span class="fw-semibold" id="display_payroll_period">
                                                @if($currentPayrollPeriod)
                                                    {{ $currentPayrollPeriod->period_label }}
                                                @else
                                                    {{ date('F Y') }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <!-- Quick Stats Widget -->
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body">
                                    <h6 class="mb-3"><i class="bx bx-stats me-2"></i>Quick Stats</h6>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Total Leave Balance</small>
                                        <h4 class="mb-0 text-primary" id="total_leave_balance">-</h4>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Pending Requests</small>
                                        <h5 class="mb-0 text-warning" id="pending_requests_count">0</h5>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Last Leave</small>
                                        <small class="text-muted" id="last_leave_date">-</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contextual Warnings -->
                    <div id="contextual_warnings" class="row g-2">
                        <!-- Warnings will be dynamically added here -->
                    </div>
                </div>
            </div>

            <!-- SECTION 2: LEAVE DETAILS (CORE INPUT) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bx bx-calendar-check me-2"></i>SECTION 2: LEAVE DETAILS
                        <small class="ms-2 opacity-75">(Core Input)</small>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Leave Type - Card Based Selection -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bx bx-calendar-check me-1 text-info"></i>Leave Type 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="hidden" name="leave_type_id" id="leave_type_id" value="{{ old('leave_type_id') }}" required>
                            <div class="row g-3" id="leave_type_cards">
                                @foreach($leaveTypes as $type)
                                    @php
                                        $balancesCollection = collect($balances ?? []);
                                        $balance = $balancesCollection->firstWhere('leave_type.id', $type->id);
                                        $available = $balance['available'] ?? ($balance->available ?? 0);
                                        $cardClass = $available > 0 ? 'border-success' : ($available == 0 ? 'border-warning' : 'border-danger');
                                        $isSelected = old('leave_type_id') == $type->id;
                                    @endphp
                                    <div class="col-md-4 col-lg-3">
                                        <div class="card leave-type-card {{ $cardClass }} {{ $isSelected ? 'selected border-primary shadow' : '' }}" 
                                             data-type-id="{{ $type->id }}"
                                             data-is-paid="{{ $type->is_paid ? '1' : '0' }}"
                                             data-allow-half="{{ $type->allow_half_day ? '1' : '0' }}"
                                             data-allow-hourly="{{ $type->allow_hourly ? '1' : '0' }}"
                                             data-doc-required="{{ $type->doc_required_after_days ? '1' : '0' }}"
                                             data-doc-days="{{ $type->doc_required_after_days ?? 0 }}"
                                             style="cursor: pointer; transition: all 0.3s;">
                                            <div class="card-body text-center">
                                                <h6 class="card-title mb-2">{{ $type->name }}</h6>
                                                <small class="text-muted d-block mb-2">{{ $type->code }}</small>
                                                <div class="mb-2">
                                                    <strong class="text-{{ $available > 0 ? 'success' : ($available == 0 ? 'warning' : 'danger') }}">
                                                        Balance: {{ number_format($available, 1) }} days
                                                    </strong>
                                                </div>
                                                <span class="badge bg-{{ $type->is_paid ? 'success' : 'secondary' }}">
                                                    {{ $type->is_paid ? 'Paid' : 'Unpaid' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('leave_type_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Click on a card to select leave type. Green border indicates available balance.
                            </div>
                        </div>

                        <!-- Start Date -->
                        <div class="col-md-3">
                            <label for="start_date" class="form-label fw-semibold">
                                <i class="bx bx-calendar me-1 text-info"></i>Start Date 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                name="start_date" 
                                id="start_date" 
                                class="form-control @error('start_date') is-invalid @enderror" 
                                value="{{ old('start_date') }}"
                                min="{{ date('Y-m-d') }}"
                                required>
                            @error('start_date')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Leave start date (must be today or later)
                            </div>
                        </div>

                        <!-- End Date -->
                        <div class="col-md-3">
                            <label for="end_date" class="form-label fw-semibold">
                                <i class="bx bx-calendar-check me-1 text-info"></i>End Date 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                name="end_date" 
                                id="end_date" 
                                class="form-control @error('end_date') is-invalid @enderror" 
                                value="{{ old('end_date') }}"
                                min="{{ date('Y-m-d') }}"
                                required>
                            @error('end_date')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Leave end date (must be after start date)
                            </div>
                        </div>

                        <!-- Hidden fields for segments (backend compatibility) -->
                        <input type="hidden" name="segments[0][start_at]" id="segment_start_at">
                        <input type="hidden" name="segments[0][end_at]" id="segment_end_at">
                        <input type="hidden" name="segments[0][granularity]" id="segment_granularity" value="full_day">

                        <!-- Half Day Option -->
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" 
                                    type="checkbox" 
                                    id="is_half_day" 
                                    name="is_half_day" 
                                    value="1"
                                    {{ old('is_half_day') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_half_day">
                                    <i class="bx bx-time me-1 text-info"></i>Half Day Leave
                                </label>
                            </div>
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Check this if you are taking only half a day (morning or afternoon). Only available if the leave type allows it.
                            </div>
                        </div>

                        <!-- Reason - Enhanced with Character Counter and Quick Templates -->
                        <div class="col-md-12">
                            <label for="reason" class="form-label fw-semibold">
                                <i class="bx bx-message-detail me-1 text-info"></i>Reason for Leave
                                <span id="reason_required" class="text-danger" style="display: none;">*</span>
                            </label>
                            
                            <!-- Quick Template Buttons -->
                            <div class="mb-2" id="reason_templates" style="display: none;">
                                <small class="text-muted d-block mb-1">Quick templates:</small>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary reason-template" data-template="Medical appointment">Medical appointment</button>
                                    <button type="button" class="btn btn-outline-secondary reason-template" data-template="Family emergency">Family emergency</button>
                                    <button type="button" class="btn btn-outline-secondary reason-template" data-template="Personal matters">Personal matters</button>
                                    <button type="button" class="btn btn-outline-secondary reason-template" data-template="Vacation">Vacation</button>
                                </div>
                            </div>
                            
                            <textarea name="reason" 
                                id="reason" 
                                rows="3" 
                                maxlength="1000"
                                class="form-control @error('reason') is-invalid @enderror"
                                placeholder="Please provide a brief reason for your leave request...">{{ old('reason') }}</textarea>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <div class="form-text">
                                    <i class="bx bx-info-circle me-1"></i>
                                    <span id="reason_help">Optional: Provide additional context for your leave request.</span>
                                    <span id="reason_required_text" style="display: none;" class="text-danger">Required for special leave types.</span>
                                </div>
                                <small class="text-muted">
                                    <span id="reason_char_count">0</span>/1000 characters
                                </small>
                            </div>
                            @error('reason')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Reliever -->
                        <div class="col-md-6">
                            <label for="reliever_id" class="form-label fw-semibold">
                                <i class="bx bx-user-check me-1 text-info"></i>Reliever (Optional)
                            </label>
                            <select name="reliever_id" id="reliever_id" class="form-select select2-single">
                                <option value="">-- Select Reliever --</option>
                                @foreach($relievers as $reliever)
                                    <option value="{{ $reliever->id }}" {{ old('reliever_id') == $reliever->id ? 'selected' : '' }}>
                                        {{ $reliever->full_name }}@if($reliever->employee_number) ({{ $reliever->employee_number }})@endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Select someone who will cover your duties during your absence
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: SYSTEM-CALCULATED VALUES (READ-ONLY) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bx bx-calculator me-2"></i>SECTION 3: SYSTEM-CALCULATED VALUES
                        <small class="ms-2 opacity-75">(Read Only - For Your Information)</small>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success border-0 mb-4">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-check-circle fs-4 me-3"></i>
                            <div>
                                <strong>Transparency:</strong> These values are automatically calculated by the system to ensure accuracy and prevent errors. You can see the impact before submitting.
                            </div>
                        </div>
                    </div>

                    <!-- Visual Calculation Breakdown -->
                    <div class="calculation-breakdown mb-4">
                        <h6 class="mb-3"><i class="bx bx-calculator me-2"></i>Calculation Breakdown</h6>
                        <div class="row g-2">
                            <div class="col-md-12">
                                <div class="calculation-step d-flex justify-content-between align-items-center p-3 bg-light rounded mb-2">
                                    <div class="d-flex align-items-center">
                                        <span class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px; font-weight: bold;">1</span>
                                        <span class="step-desc">Total Calendar Days</span>
                                    </div>
                                    <span class="step-value fw-bold" id="calc_calendar_days">0 days</span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="calculation-step d-flex justify-content-between align-items-center p-3 bg-light rounded mb-2">
                                    <div class="d-flex align-items-center">
                                        <span class="step-number bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px; font-weight: bold;">2</span>
                                        <span class="step-desc">Less: Weekends</span>
                                    </div>
                                    <span class="step-value fw-bold text-info" id="calc_weekends">-0 days</span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="calculation-step d-flex justify-content-between align-items-center p-3 bg-light rounded mb-2">
                                    <div class="d-flex align-items-center">
                                        <span class="step-number bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px; font-weight: bold;">3</span>
                                        <span class="step-desc">Less: Public Holidays</span>
                                    </div>
                                    <span class="step-value fw-bold text-info" id="calc_holidays">-0 days</span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="calculation-total d-flex justify-content-between align-items-center p-3 bg-primary text-white rounded">
                                    <strong class="fs-5">Net Leave Days</strong>
                                    <strong class="fs-4" id="calculated_total_days">0.00 days</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Balance Impact Visualization -->
                    <div class="balance-impact mb-4">
                        <h6 class="mb-3"><i class="bx bx-trending-up me-2"></i>Balance Impact</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted">Leave Balance Before</label>
                                <div class="form-control-plaintext fw-bold fs-5" id="calculated_balance_before">
                                    <span id="balance_before_value">-</span> days
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Leave Balance After</label>
                                <div class="form-control-plaintext fw-bold fs-5" id="calculated_balance_after">
                                    <span id="balance_after_value">-</span> days
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="progress" style="height: 40px;">
                                    <div class="progress-bar bg-success" role="progressbar" id="balance_remaining_bar" style="width: 0%">
                                        <span id="balance_remaining_text" class="fw-bold">Remaining: 0 days</span>
                                    </div>
                                    <div class="progress-bar bg-warning" role="progressbar" id="balance_requested_bar" style="width: 0%">
                                        <span id="balance_requested_text" class="fw-bold">Requested: 0 days</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="text-muted">Before: <span id="balance_before_label">-</span> days</small>
                                    <small class="text-muted">After: <span id="balance_after_label">-</span> days</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: PAYROLL IMPACT FLAGS (VERY IMPORTANT) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bx bx-money me-2"></i>SECTION 4: PAYROLL IMPACT FLAGS
                        <small class="ms-2 opacity-75">(Auto-calculated - Critical for Payroll)</small>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning border-0 mb-4">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-error-circle fs-4 me-3"></i>
                            <div>
                                <strong>Payroll Integration:</strong> These flags are automatically set based on the leave type and company policy. They directly affect salary calculations, deductions, and statutory contributions.
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label text-muted">Paid Leave</label>
                            <div class="form-control-plaintext">
                                <span class="badge bg-{{ $leaveTypes->firstWhere('id', old('leave_type_id'))?->is_paid ? 'success' : 'danger' }}" id="payroll_paid_leave">
                                    <i class="bx bx-{{ $leaveTypes->firstWhere('id', old('leave_type_id'))?->is_paid ? 'check' : 'x' }} me-1"></i>
                                    <span id="paid_leave_text">-</span>
                                </span>
                            </div>
                            <div class="form-text small">
                                Whether salary will be paid during leave
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted">Deduct from Salary</label>
                            <div class="form-control-plaintext">
                                <span class="badge bg-secondary" id="payroll_deduct_salary">
                                    <i class="bx bx-info-circle me-1"></i>
                                    <span id="deduct_salary_text">-</span>
                                </span>
                            </div>
                            <div class="form-text small">
                                Unpaid leave will reduce salary
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted">Affect Overtime</label>
                            <div class="form-control-plaintext">
                                <span class="badge bg-info" id="payroll_affect_overtime">
                                    <i class="bx bx-info-circle me-1"></i>
                                    Yes
                                </span>
                            </div>
                            <div class="form-text small">
                                Leave days affect overtime calculations
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-muted">Affect Pension</label>
                            <div class="form-control-plaintext">
                                <span class="badge bg-info" id="payroll_affect_pension">
                                    <i class="bx bx-info-circle me-1"></i>
                                    <span id="affect_pension_text">-</span>
                                </span>
                            </div>
                            <div class="form-text small">
                                Pension contributions may be affected
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 5: ATTACHMENTS (CONDITIONAL) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="bx bx-paperclip me-2"></i>SECTION 5: ATTACHMENTS
                        <small class="ms-2 opacity-75">(Conditional - Legal & Audit Evidence)</small>
                    </h5>
                </div>
                <div class="card-body">
                    <div id="attachment_requirements" class="alert alert-info border-0 mb-3" style="display: none;">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-info-circle fs-4 me-3"></i>
                            <div>
                                <strong>Attachment Required:</strong> <span id="attachment_requirement_text"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Drag & Drop Upload Zone -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bx bx-paperclip me-1 text-secondary"></i>Upload Documents
                            <span id="attachment_required_badge" class="badge bg-danger ms-2" style="display: none;">Required</span>
                        </label>
                        <div class="upload-zone border-2 border-dashed rounded p-5 text-center" 
                             id="attachmentDropZone"
                             style="border-color: #dee2e6; background-color: #f8f9fa; cursor: pointer; transition: all 0.3s;">
                            <i class="bx bx-cloud-upload fs-1 text-muted mb-3"></i>
                            <p class="mb-2 fw-semibold">Drag and drop files here or click to browse</p>
                            <small class="text-muted">PDF, JPG, PNG, DOC, DOCX (Max 2MB each)</small>
                            <input type="file" 
                                name="attachments[]" 
                                id="attachments" 
                                class="d-none" 
                                multiple 
                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        </div>
                        <div id="fileList" class="mt-3"></div>
                    </div>

                </div>
            </div>

            <!-- Action Buttons - Sticky Footer -->
            <div class="card border-0 shadow-sm sticky-footer" style="position: sticky; bottom: 0; z-index: 1000; background: white; margin-top: 2rem;">
                <div class="card-body">
                    <div class="d-flex gap-2 justify-content-end flex-wrap">
                        <a href="{{ route('hr.leave.requests.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i> Cancel
                        </a>
                        <button type="submit" name="action" value="draft" class="btn btn-secondary">
                            <i class="bx bx-save me-1"></i> Save as Draft
                        </button>
                        <button type="submit" name="action" value="submit" class="btn btn-primary btn-lg">
                            <i class="bx bx-send me-1"></i> Submit for Approval
                        </button>
                    </div>
                    <div class="mt-3 p-3 bg-light rounded">
                        <small class="text-muted">
                            <i class="bx bx-info-circle me-1"></i>
                            <strong>Draft:</strong> Save your request and submit later. You can edit it before submission.<br>
                            <strong>Submit:</strong> Send your request for immediate approval. Once submitted, you cannot edit it.
                        </small>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
    .form-control-plaintext {
        padding: 0.375rem 0.75rem;
        border-bottom: 1px solid #dee2e6;
        min-height: 38px;
    }
    
    .card-header {
        font-weight: 600;
    }
    
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
    }
    
    /* Leave Type Cards */
    .leave-type-card {
        transition: all 0.3s ease;
    }
    
    .leave-type-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .leave-type-card.selected {
        border-width: 3px !important;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
    }
    
    /* Upload Zone */
    .upload-zone:hover {
        border-color: #0d6efd !important;
        background-color: #e7f1ff !important;
    }
    
    .upload-zone.dragover {
        border-color: #0d6efd !important;
        background-color: #cfe2ff !important;
    }
    
    /* Sticky Footer */
    .sticky-footer {
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    }
    
    /* Calculation Steps */
    .calculation-step {
        transition: background-color 0.3s;
    }
    
    .calculation-step:hover {
        background-color: #e9ecef !important;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '-- Select an option --',
        allowClear: true
    });

    // Employee selection change (for HR/Admin)
    @if($canCreateForOthers ?? false)
    $('#employee_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
            $('#display_employee_number').text(selectedOption.data('employee-number') || '-');
            $('#display_employee_name').text(selectedOption.text().split('(')[0].trim());
            $('#display_department').text(selectedOption.data('department') || 'N/A');
            $('#display_position').text(selectedOption.data('position') || 'N/A');
            $('#display_employment_type').text(selectedOption.data('employment-type') || 'N/A');
            
            // Load leave balances for selected employee
            loadLeaveBalances(selectedOption.val());
        }
    });
    @endif

    // Card-based Leave Type Selection
    $('.leave-type-card').on('click', function() {
        $('.leave-type-card').removeClass('selected border-primary shadow');
        $(this).addClass('selected border-primary shadow');
        const typeId = $(this).data('type-id');
        $('#leave_type_id').val(typeId);
        
        // Update payroll flags and other dependent fields
        updatePayrollFlags();
        updateAttachmentRequirements();
        updateHalfDayOption();
        calculateLeaveDays();
    });
    
    // Set initial selected card if value exists
    const initialTypeId = $('#leave_type_id').val();
    if (initialTypeId) {
        $(`.leave-type-card[data-type-id="${initialTypeId}"]`).addClass('selected border-primary shadow');
    }
    
    // Also handle change on hidden input for validation
    $('#leave_type_id').on('change', function() {
        const typeId = $(this).val();
        $('.leave-type-card').removeClass('selected border-primary shadow');
        if (typeId) {
            $(`.leave-type-card[data-type-id="${typeId}"]`).addClass('selected border-primary shadow');
        }
    });

    // Leave type change handler
    $('#leave_type_id').on('change', function() {
        updatePayrollFlags();
        updateAttachmentRequirements();
        updateHalfDayOption();
        calculateLeaveDays();
    });
    
    // Reason character counter
    $('#reason').on('input', function() {
        const length = $(this).val().length;
        $('#reason_char_count').text(length);
        if (length > 900) {
            $('#reason_char_count').addClass('text-danger');
        } else {
            $('#reason_char_count').removeClass('text-danger');
        }
    });
    
    // Reason template buttons
    $('.reason-template').on('click', function() {
        const template = $(this).data('template');
        $('#reason').val(template).trigger('input');
    });
    
    // Show reason templates
    $('#reason').on('focus', function() {
        $('#reason_templates').show();
    });
    
    // Drag & Drop File Upload
    const dropZone = $('#attachmentDropZone');
    const fileInput = $('#attachments');
    
    // Prevent click event from bubbling and causing recursion
    dropZone.on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        // Only trigger if click is not on the file input itself
        if (e.target !== fileInput[0] && !$(e.target).is(fileInput)) {
            fileInput[0].click();
        }
    });
    
    // Prevent file input click from bubbling to dropZone
    fileInput.on('click', function(e) {
        e.stopPropagation();
    });
    
    dropZone.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });
    
    dropZone.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });
    
    dropZone.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            fileInput[0].files = files;
            // Use native change event instead of jQuery trigger to avoid recursion
            fileInput[0].dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
    
    // File input preview handler (moved inside document.ready to prevent multiple attachments)
    fileInput.off('change').on('change', function(e) {
        e.stopPropagation();
        const fileList = $('#fileList');
        fileList.html('');
        
        if (this.files && this.files.length > 0) {
            const list = $('<ul class="list-group"></ul>');
            
            Array.from(this.files).forEach((file) => {
                const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                const li = $(`
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bx bx-file me-2"></i>${file.name}</span>
                        <small class="text-muted">${sizeMB} MB</small>
                    </li>
                `);
                list.append(li);
            });
            
            fileList.append(list);
        }
    });
    
    // Load quick stats
    loadQuickStats();

    // Date change handlers
    $('#start_date, #end_date, #is_half_day').on('change', function() {
        updateSegmentFields();
        calculateLeaveDays();
    });

    // Update hidden segment fields for backend compatibility
    function updateSegmentFields() {
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        const isHalfDay = $('#is_half_day').is(':checked');
        
        if (startDate && endDate) {
            // Format dates for backend (YYYY-MM-DD HH:MM:SS)
            $('#segment_start_at').val(startDate + ' 00:00:00');
            $('#segment_end_at').val(endDate + ' 23:59:59');
            $('#segment_granularity').val(isHalfDay ? 'half_day' : 'full_day');
        }
    }

    // Initial calculations
    updateSegmentFields();
    calculateLeaveDays();
    updatePayrollFlags();
    addContextualWarnings();
    
    // Initialize character counter
    $('#reason').trigger('input');
});

// Public holidays data
const publicHolidays = @json($publicHolidays->pluck('date')->map(function($date) {
    return $date->format('Y-m-d');
}));

// Leave balances data
const leaveBalances = @json($balances ?? []);

// Payroll cut-off date
const payrollCutOffDate = @json($payrollCutOffDate ? $payrollCutOffDate->format('Y-m-d') : null);
const isPayrollLocked = @json($isPayrollLocked ?? false);

function calculateLeaveDays() {
    const startDate = $('#start_date').val();
    const endDate = $('#end_date').val();
    const leaveTypeId = $('#leave_type_id').val();
    const isHalfDay = $('#is_half_day').is(':checked');
    
    if (!startDate || !endDate || !leaveTypeId) {
        $('#calculated_total_days').text('0.00 days');
        $('#calculated_balance_before').html('<span id="balance_before_value">-</span> days');
        $('#calculated_balance_after').html('<span id="balance_after_value">-</span> days');
        return;
    }

    const start = new Date(startDate);
    const end = new Date(endDate);
    
    if (end < start) {
        $('#calculated_total_days').text('0.00 days');
        return;
    }

    // Calculate working days (excluding weekends and public holidays)
    let totalDays = 0;
    let holidaysExcluded = 0;
    let weekendsExcluded = 0;
    const currentDate = new Date(start);
    
    while (currentDate <= end) {
        const dayOfWeek = currentDate.getDay();
        const dateStr = currentDate.toISOString().split('T')[0];
        
        // Check if it's a weekend
        if (dayOfWeek === 0 || dayOfWeek === 6) {
            weekendsExcluded++;
        } 
        // Check if it's a public holiday
        else if (publicHolidays.includes(dateStr)) {
            holidaysExcluded++;
        } 
        // It's a working day
        else {
            totalDays++;
        }
        
        currentDate.setDate(currentDate.getDate() + 1);
    }

    // Apply half day
    if (isHalfDay && totalDays > 0) {
        totalDays = 0.5;
    }

    // Calculate total calendar days
    const totalCalendarDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
    
    // Update visual calculation breakdown
    $('#calc_calendar_days').text(totalCalendarDays + ' days');
    $('#calc_weekends').text('- ' + weekendsExcluded + ' days');
    $('#calc_holidays').text('- ' + holidaysExcluded + ' days');
    $('#calculated_total_days').text(totalDays.toFixed(2) + ' days');
    
    // Update balance calculations
    const selectedLeaveType = leaveBalances.find(b => {
        const leaveTypeId = b.leave_type?.id || b.leave_type_id;
        return leaveTypeId == $('#leave_type_id').val();
    });
    if (selectedLeaveType) {
        const balanceBefore = parseFloat(selectedLeaveType.available || selectedLeaveType.balance?.available_days || 0);
        const balanceAfter = balanceBefore - totalDays;
        
        $('#balance_before_value').text(balanceBefore.toFixed(2));
        $('#balance_after_value').text(balanceAfter.toFixed(2));
        $('#balance_before_label').text(balanceBefore.toFixed(2));
        $('#balance_after_label').text(balanceAfter.toFixed(2));
        
        // Update balance visualization progress bar
        const totalBalance = balanceBefore;
        if (totalBalance > 0) {
            const remainingPercent = Math.max(0, (balanceAfter / totalBalance) * 100);
            const requestedPercent = Math.min(100, (totalDays / totalBalance) * 100);
            
            $('#balance_remaining_bar').css('width', remainingPercent + '%');
            $('#balance_requested_bar').css('width', requestedPercent + '%');
            $('#balance_remaining_text').text(`Remaining: ${Math.max(0, balanceAfter).toFixed(2)} days`);
            $('#balance_requested_text').text(`Requested: ${totalDays.toFixed(2)} days`);
        }
        
        // Color code balance after
        const balanceAfterEl = $('#balance_after_value');
        balanceAfterEl.removeClass('text-success text-danger text-warning');
        if (balanceAfter < 0) {
            balanceAfterEl.addClass('text-danger');
        } else if (balanceAfter < 1) {
            balanceAfterEl.addClass('text-warning');
        } else {
            balanceAfterEl.addClass('text-success');
        }
    } else {
        $('#balance_before_value').text('-');
        $('#balance_after_value').text('-');
        $('#balance_before_label').text('-');
        $('#balance_after_label').text('-');
    }
}

function updatePayrollFlags() {
    const leaveTypeId = $('#leave_type_id').val();
    if (!leaveTypeId) {
        $('#payroll_paid_leave').html('<i class="bx bx-info-circle me-1"></i><span id="paid_leave_text">-</span>');
        $('#payroll_deduct_salary').html('<i class="bx bx-info-circle me-1"></i><span id="deduct_salary_text">-</span>');
        $('#payroll_affect_pension').html('<i class="bx bx-info-circle me-1"></i><span id="affect_pension_text">-</span>');
        return;
    }

    // Get data from selected card or select option
    const selectedCard = $(`.leave-type-card[data-type-id="${leaveTypeId}"]`);
    const isPaid = selectedCard.length ? selectedCard.data('is-paid') == '1' : $('#leave_type_id').find('option:selected').data('is-paid') == '1';
    
    // Update Paid Leave badge
    const paidBadge = $('#payroll_paid_leave');
    paidBadge.removeClass('bg-success bg-danger').addClass(isPaid ? 'bg-success' : 'bg-danger');
    paidBadge.html(`<i class="bx bx-${isPaid ? 'check' : 'x'} me-1"></i><span id="paid_leave_text">${isPaid ? 'Yes' : 'No'}</span>`);
    
    // Update Deduct from Salary
    $('#deduct_salary_text').text(isPaid ? 'No' : 'Yes');
    
    // Update Affect Pension
    $('#affect_pension_text').text(isPaid ? 'No (Paid leave maintains contributions)' : 'Yes (Unpaid reduces base)');
}

function updateAttachmentRequirements() {
    const leaveTypeId = $('#leave_type_id').val();
    if (!leaveTypeId) {
        $('#attachment_requirements').hide();
        $('#attachment_required_badge').hide();
        return;
    }

    // Get data from selected card
    const selectedCard = $(`.leave-type-card[data-type-id="${leaveTypeId}"]`);
    const leaveTypeName = selectedCard.length ? selectedCard.find('.card-title').text() : $('#leave_type_id').find('option:selected').text().split('(')[0].trim();
    const docRequired = selectedCard.length ? selectedCard.data('doc-required') == '1' : $('#leave_type_id').find('option:selected').data('doc-required') == '1';
    const docDays = selectedCard.length ? selectedCard.data('doc-days') || 0 : $('#leave_type_id').find('option:selected').data('doc-days') || 0;

    if (docRequired) {
        $('#attachment_requirements').show();
        $('#attachment_required_badge').show();
        let requirementText = `For ${leaveTypeName}, documentation is required`;
        if (docDays > 0) {
            requirementText += ` after ${docDays} day(s)`;
        }
        $('#attachment_requirement_text').text(requirementText);
        $('#attachments').prop('required', true);
    } else {
        $('#attachment_requirements').hide();
        $('#attachment_required_badge').hide();
        $('#attachments').prop('required', false);
    }
}

function updateHalfDayOption() {
    const leaveTypeId = $('#leave_type_id').val();
    if (!leaveTypeId) {
        $('#is_half_day').prop('disabled', true);
        return;
    }

    // Get data from selected card
    const selectedCard = $(`.leave-type-card[data-type-id="${leaveTypeId}"]`);
    const allowHalf = selectedCard.length ? selectedCard.data('allow-half') == '1' : $('#leave_type_id').find('option:selected').data('allow-half') == '1';
    
    $('#is_half_day').prop('disabled', !allowHalf);
    if (!allowHalf) {
        $('#is_half_day').prop('checked', false);
    }
}


// Load Quick Stats
function loadQuickStats() {
    if (leaveBalances && leaveBalances.length > 0) {
        const totalBalance = leaveBalances.reduce((sum, b) => {
            const available = b.available || b.balance?.available_days || 0;
            return sum + parseFloat(available);
        }, 0);
        $('#total_leave_balance').text(totalBalance.toFixed(1) + ' days');
    }
    // TODO: Load pending requests count and last leave date from API
    $('#pending_requests_count').text('0');
    $('#last_leave_date').text('-');
}

// Add contextual warnings
function addContextualWarnings() {
    const warnings = $('#contextual_warnings');
    warnings.html('');
    
    // Check for low balance
    if (leaveBalances && leaveBalances.length > 0) {
        const lowBalance = leaveBalances.find(b => {
            const available = b.available || b.balance?.available_days || 0;
            return parseFloat(available) < 5;
        });
        if (lowBalance) {
            const leaveTypeName = lowBalance.leave_type?.name || 'Leave';
            const available = lowBalance.available || lowBalance.balance?.available_days || 0;
            warnings.append(`
                <div class="col-md-12">
                    <div class="alert alert-warning border-0 mb-0">
                        <i class="bx bx-error-circle me-2"></i>
                        <strong>Low Balance Warning:</strong> Your ${leaveTypeName} balance is low (${parseFloat(available).toFixed(1)} days remaining).
                    </div>
                </div>
            `);
        }
    }
}

</script>
@endpush
@endsection
