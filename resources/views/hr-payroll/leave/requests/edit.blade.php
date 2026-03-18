@extends('layouts.main')

@section('title', 'Edit Leave Request')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Leave Management', 'url' => route('hr.leave.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Leave Requests', 'url' => route('hr.leave.requests.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Edit Request', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
        <h6 class="mb-0 text-uppercase">EDIT LEAVE REQUEST</h6>
                <p class="text-muted mb-0 small">Update the details of your leave request</p>
            </div>
            <a href="{{ route('hr.leave.requests.show', $request) }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i> Back to Details
            </a>
        </div>
        <hr />

                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h6 class="alert-heading">
                    <i class="bx bx-error-circle me-2"></i>Please fix the following errors:
                </h6>
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

        <form action="{{ route('hr.leave.requests.update', $request) }}" method="POST" enctype="multipart/form-data" id="leaveRequestForm">
            @csrf
            @method('PUT')

            <div class="row">
                <!-- Main Form Section -->
                <div class="col-12 col-lg-8">
                    <!-- Basic Information Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-primary bg-gradient text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-info-circle me-2"></i>Basic Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                            
                            <div class="alert alert-info d-flex align-items-center mb-4">
                                <i class="bx bx-user-circle fs-4 me-2"></i>
                                <div>
                                    <strong>Employee:</strong> {{ $employee->full_name }} ({{ $employee->employee_number }})
                                </div>
                            </div>

                            <!-- Request Number (Read-only) -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="bx bx-hash me-1 text-primary"></i>Request Number
                                </label>
                                <input type="text" class="form-control form-control-lg bg-light" value="{{ $request->request_number }}" readonly>
                                <small class="text-muted">
                                    <i class="bx bx-info-circle me-1"></i>This is your unique leave request identifier
                                </small>
                            </div>

                            <!-- Leave Type -->
                            <div class="mb-4">
                                <label for="leave_type_id" class="form-label fw-semibold">
                                    <i class="bx bx-calendar-check me-1 text-primary"></i>Leave Type 
                                    <span class="text-danger">*</span>
                                </label>
                                <select name="leave_type_id" id="leave_type_id" 
                                    class="form-select select2-single @error('leave_type_id') is-invalid @enderror" 
                                    required>
                                    <option value="">Select Leave Type</option>
                                    @foreach($leaveTypes as $type)
                                        <option value="{{ $type->id }}"
                                                data-allow-half="{{ $type->allow_half_day }}"
                                                data-allow-hourly="{{ $type->allow_hourly }}"
                                                {{ old('leave_type_id', $request->leave_type_id) == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }} ({{ $type->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('leave_type_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    <i class="bx bx-info-circle me-1"></i>Choose the type of leave you're requesting
                                </small>
                            </div>
                        </div>
                            </div>

                    <!-- Leave Periods Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-info bg-gradient text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-calendar me-2"></i>Leave Period(s)
                                <span class="text-danger">*</span>
                            </h5>
                        </div>
                        <div class="card-body">
                                <div id="leavePeriods">
                                    @foreach($request->segments as $index => $segment)
                                <div class="leave-period-item mb-3">
                                    <div class="card border border-primary border-2">
                                        <div class="card-body bg-light">
                                            <div class="row g-3">
                                                <div class="col-md-5">
                                                    <label class="form-label fw-semibold">
                                                        <i class="bx bx-calendar me-1 text-info"></i>Start Date
                                                    </label>
                                                    <input type="date" 
                                                        name="segments[{{ $index }}][start_at]" 
                                                        class="form-control form-control-lg date-input" 
                                                        value="{{ $segment->start_at->format('Y-m-d') }}" 
                                                        required>
                                            </div>
                                                <div class="col-md-5">
                                                    <label class="form-label fw-semibold">
                                                        <i class="bx bx-calendar-check me-1 text-info"></i>End Date
                                                    </label>
                                                    <input type="date" 
                                                        name="segments[{{ $index }}][end_at]" 
                                                        class="form-control form-control-lg date-input" 
                                                        value="{{ $segment->end_at->format('Y-m-d') }}" 
                                                        required>
                                            </div>
                                                <div class="col-md-2">
                                                    <label class="form-label fw-semibold">
                                                        <i class="bx bx-time me-1 text-info"></i>Type
                                                    </label>
                                                    <select name="segments[{{ $index }}][granularity]" 
                                                        class="form-select select2-single period-type" 
                                                        required>
                                                    <option value="full_day" {{ $segment->granularity == 'full_day' ? 'selected' : '' }}>Full Day</option>
                                                    <option value="half_day" {{ $segment->granularity == 'half_day' ? 'selected' : '' }}>Half Day</option>
                                                    <option value="hourly" {{ $segment->granularity == 'hourly' ? 'selected' : '' }}>Hourly</option>
                                                </select>
                                            </div>
                                            </div>
                                            <div class="mt-2 text-end">
                                                @if($index > 0)
                                                <button type="button" 
                                                    class="btn btn-sm btn-danger remove-period-btn" 
                                                    onclick="removePeriod(this)">
                                                    <i class="bx bx-trash me-1"></i>Remove
                                                </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-outline-primary" onclick="addPeriod()">
                                <i class="bx bx-plus-circle me-1"></i> Add Another Period
                                </button>
                            <small class="text-muted d-block mt-2">
                                <i class="bx bx-info-circle me-1"></i>You can add multiple leave periods if your leave is split across different dates
                            </small>
                        </div>
                            </div>

                    <!-- Additional Details Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-success bg-gradient text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-detail me-2"></i>Additional Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Reason -->
                            <div class="mb-4">
                                <label for="reason" class="form-label fw-semibold">
                                    <i class="bx bx-message-detail me-1 text-success"></i>Reason for Leave
                                    <span class="text-danger">*</span>
                                </label>
                                <textarea name="reason" 
                                    id="reason" 
                                    rows="4" 
                                    class="form-control @error('reason') is-invalid @enderror"
                                    placeholder="Please provide a brief reason for your leave request..."
                                    required>{{ old('reason', $request->reason) }}</textarea>
                                @error('reason')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    <i class="bx bx-info-circle me-1"></i>Provide additional context for your leave request
                                </small>
                            </div>

                            <!-- Reliever -->
                            <div class="mb-4">
                                <label for="reliever_id" class="form-label fw-semibold">
                                    <i class="bx bx-user-check me-1 text-success"></i>Reliever (Optional)
                                </label>
                                <select name="reliever_id" id="reliever_id" 
                                    class="form-select select2-single @error('reliever_id') is-invalid @enderror">
                                    <option value="">No Reliever</option>
                                    @foreach($relievers as $reliever)
                                        <option value="{{ $reliever->id }}" {{ old('reliever_id', $request->reliever_id) == $reliever->id ? 'selected' : '' }}>
                                            {{ $reliever->full_name }} ({{ $reliever->employee_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('reliever_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    <i class="bx bx-info-circle me-1"></i>Select someone who will cover your duties during your absence
                                </small>
                            </div>

                            <!-- Attachments -->
                            <div class="mb-0">
                                <label for="attachments" class="form-label fw-semibold">
                                    <i class="bx bx-paperclip me-1 text-success"></i>Attachments
                                    @if($request->requires_doc)
                                    <span class="text-danger">*</span>
                                        <small class="text-danger">(Required for this leave type)</small>
                                    @else
                                    <small class="text-muted">(Optional)</small>
                                    @endif
                                </label>
                                <input type="file" 
                                    name="attachments[]" 
                                    id="attachments" 
                                    class="form-control form-control-lg @error('attachments.*') is-invalid @enderror" 
                                    multiple 
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                @error('attachments.*')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted d-block mt-1">
                                    <i class="bx bx-info-circle me-1"></i>Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max 5MB per file)
                                </small>
                                <div id="fileList" class="mt-2"></div>

                                <!-- Existing Attachments -->
                                @if($request->attachments->count() > 0)
                                <div class="mt-3 p-3 bg-light rounded">
                                    <strong class="d-block mb-2">
                                        <i class="bx bx-file me-1"></i>Current Attachments:
                                    </strong>
                                    <ul class="list-group list-group-flush">
                                        @foreach($request->attachments as $attachment)
                                        <li class="list-group-item d-flex justify-content-between align-items-center bg-white">
                                            <span>
                                                <i class="bx bx-file me-2 text-primary"></i>
                                                {{ $attachment->file_name }}
                                            </span>
                                            <a href="{{ Storage::url($attachment->file_path) }}" 
                                                target="_blank" 
                                                class="btn btn-sm btn-outline-info">
                                                <i class="bx bx-download me-1"></i>View
                                            </a>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Section -->
                <div class="col-12 col-lg-4">
                    <!-- Leave Balance Card -->
                    <div class="card border-0 shadow-sm mb-4 sticky-top" style="top: 20px;">
                        <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <h5 class="mb-0 text-white">
                                <i class="bx bx-bar-chart-alt-2 me-2"></i>Leave Balances
                            </h5>
                        </div>
                        <div class="card-body">
                            @if(count($balances) > 0)
                                @foreach($balances as $balanceInfo)
                                <div class="mb-3 p-3 rounded-3 border-start border-4 
                                    border-{{ $balanceInfo['available'] > 0 ? 'success' : 'danger' }} 
                                    bg-{{ $balanceInfo['available'] > 0 ? 'light' : 'danger-subtle' }}">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1 fw-bold text-dark">
                                                <i class="bx bx-calendar-check me-1"></i>
                                                {{ $balanceInfo['leave_type']->name }}
                                            </h6>
                                            <small class="text-muted">{{ $balanceInfo['leave_type']->code }}</small>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small">Available:</span>
                                            <strong class="fs-5 text-{{ $balanceInfo['available'] > 0 ? 'success' : 'danger' }}">
                                                {{ number_format($balanceInfo['available'], 1) }} 
                                                <small class="fs-6">days</small>
                                        </strong>
                                        </div>
                                        <div class="progress mb-2" style="height: 8px;">
                                            @php
                                                $total = $balanceInfo['balance']->total_days ?? 1;
                                                $available = $balanceInfo['available'];
                                                $percentage = $total > 0 ? ($available / $total) * 100 : 0;
                                            @endphp
                                            <div class="progress-bar bg-{{ $balanceInfo['available'] > 0 ? 'success' : 'danger' }}" 
                                                role="progressbar" 
                                                style="width: {{ max(0, min(100, $percentage)) }}%"
                                                aria-valuenow="{{ $percentage }}" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                        <div class="row g-2 mt-2">
                                            <div class="col-6">
                                                <small class="text-muted d-block">Taken</small>
                                                <strong class="text-dark">{{ number_format($balanceInfo['balance']->taken_days, 1) }}</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Pending</small>
                                                <strong class="text-warning">{{ number_format($balanceInfo['balance']->pending_hold_days, 1) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            @else
                                <div class="text-center py-4">
                                    <i class="bx bx-info-circle fs-1 text-muted mb-2"></i>
                                    <p class="text-muted mb-0">No leave balances available</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Action Buttons Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-send me-2"></i>Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bx bx-save me-1"></i> Update Request
                                </button>
                                <a href="{{ route('hr.leave.requests.show', $request) }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-x me-1"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Help Card -->
                    <div class="card border-0 shadow-sm border-info">
                        <div class="card-header bg-info bg-gradient text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-info-circle me-2"></i>Need Help?
                            </h5>
                        </div>
                        <div class="card-body">
                                <ul class="small mb-0">
                                <li class="mb-2">
                                    <i class="bx bx-check-circle me-1 text-success"></i>
                                    You can only edit requests in <strong>Draft</strong> status
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-calendar me-1 text-info"></i>
                                    Make sure your leave dates don't overlap with public holidays
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-bar-chart me-1 text-warning"></i>
                                    Check your leave balance before requesting
                                </li>
                                <li>
                                    <i class="bx bx-file me-1 text-primary"></i>
                                    Provide proper documentation if required
                                </li>
                                </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
    .date-input:focus,
    .select2-container--bootstrap-5 .select2-selection:focus,
    .form-select:focus,
    .form-control:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .leave-period-item {
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .card-header.bg-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }

    .form-label {
        color: #495057;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .btn-lg {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
    }

    .border-primary {
        border-color: #0d6efd !important;
    }

    .bg-light {
        background-color: #f8f9fa !important;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for all select2-single dropdowns
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: function() {
            return $(this).data('placeholder') || 'Select an option';
        },
        allowClear: true
    });
});

let periodCount = {{ count($request->segments) }};
const leaveTypeSelect = document.getElementById('leave_type_id');

// Update period type options based on selected leave type
if (leaveTypeSelect) {
    leaveTypeSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const allowHalf = selectedOption.dataset.allowHalf === '1';
        const allowHourly = selectedOption.dataset.allowHourly === '1';
        
        // Update all period type selects
        document.querySelectorAll('.period-type').forEach(select => {
            const fullDayOption = select.querySelector('option[value="full_day"]');
            const halfDayOption = select.querySelector('option[value="half_day"]');
            const hourlyOption = select.querySelector('option[value="hourly"]');
            
            if (halfDayOption) halfDayOption.style.display = allowHalf ? 'block' : 'none';
            if (hourlyOption) hourlyOption.style.display = allowHourly ? 'block' : 'none';
            
            // If current selection is not allowed, reset to full_day
            if (select.value === 'half_day' && !allowHalf) {
                select.value = 'full_day';
                $(select).trigger('change');
            }
            if (select.value === 'hourly' && !allowHourly) {
                select.value = 'full_day';
                $(select).trigger('change');
            }
        });
    });
}

function addPeriod() {
    const container = document.getElementById('leavePeriods');
    const newPeriod = document.createElement('div');
    newPeriod.className = 'leave-period-item mb-3';
    
    const selectedLeaveType = leaveTypeSelect?.options[leaveTypeSelect.selectedIndex];
    const allowHalf = selectedLeaveType?.dataset.allowHalf === '1';
    const allowHourly = selectedLeaveType?.dataset.allowHourly === '1';
    
    let granularityOptions = '<option value="full_day">Full Day</option>';
    if (allowHalf) granularityOptions += '<option value="half_day">Half Day</option>';
    if (allowHourly) granularityOptions += '<option value="hourly">Hourly</option>';
    
    newPeriod.innerHTML = `
        <div class="card border border-primary border-2">
            <div class="card-body bg-light">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">
                            <i class="bx bx-calendar me-1 text-info"></i>Start Date
                        </label>
                        <input type="date" 
                            name="segments[${periodCount}][start_at]" 
                            class="form-control form-control-lg date-input" 
                            required>
                </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">
                            <i class="bx bx-calendar-check me-1 text-info"></i>End Date
                        </label>
                        <input type="date" 
                            name="segments[${periodCount}][end_at]" 
                            class="form-control form-control-lg date-input" 
                            required>
                </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">
                            <i class="bx bx-time me-1 text-info"></i>Type
                        </label>
                        <select name="segments[${periodCount}][granularity]" 
                            class="form-select select2-single period-type" 
                            required>
                            ${granularityOptions}
                    </select>
                    </div>
                </div>
                <div class="mt-2 text-end">
                    <button type="button" 
                        class="btn btn-sm btn-danger remove-period-btn" 
                        onclick="removePeriod(this)">
                        <i class="bx bx-trash me-1"></i>Remove
                    </button>
                </div>
            </div>
        </div>
    `;
    container.appendChild(newPeriod);
    periodCount++;

    // Initialize Select2 for the newly added period type select
    $(newPeriod).find('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true
    });

    // Show delete buttons on all items
    document.querySelectorAll('.remove-period-btn').forEach(btn => {
        btn.style.display = 'block';
    });
}

function removePeriod(button) {
    const remainingItems = document.querySelectorAll('.leave-period-item');
    if (remainingItems.length <= 1) {
        alert('You must have at least one leave period');
        return;
    }
    
    const item = button.closest('.leave-period-item');
    item.style.animation = 'fadeOut 0.3s ease-out';
    setTimeout(() => {
        item.remove();
    }, 300);
}

// File input preview
document.getElementById('attachments')?.addEventListener('change', function(e) {
    const fileList = document.getElementById('fileList');
    fileList.innerHTML = '';
    
    if (this.files.length > 0) {
        const list = document.createElement('ul');
        list.className = 'list-group list-group-flush';
        
        Array.from(this.files).forEach((file, index) => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.innerHTML = `
                <span><i class="bx bx-file me-2"></i>${file.name}</span>
                <small class="text-muted">${(file.size / 1024 / 1024).toFixed(2)} MB</small>
            `;
            list.appendChild(li);
        });
        
        fileList.appendChild(list);
    }
});

// Set minimum date to today
document.querySelectorAll('.date-input').forEach(input => {
    input.min = new Date().toISOString().split('T')[0];
    
    input.addEventListener('change', function() {
        const startDate = this.name.includes('[start_at]');
        if (startDate) {
            // Set end date minimum to start date
            const periodItem = this.closest('.leave-period-item');
            const endDateInput = periodItem.querySelector('input[name*="[end_at]"]');
            if (endDateInput && this.value) {
                endDateInput.min = this.value;
            }
        }
    });
});
</script>
@endpush
@endsection
