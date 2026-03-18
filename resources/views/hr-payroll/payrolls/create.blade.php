@extends('layouts.main')

@section('title', 'Create Payroll')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Payrolls', 'url' => route('hr.payrolls.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
            <h6 class="mb-0 text-uppercase">Create New Payroll</h6>
            <hr />
            
            <div class="row">
                <!-- Left: Form -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="{{ route('hr.payrolls.store') }}">
                                @csrf
                                
                                @if($payrollCalendars->count() > 0)
                                    <!-- Payroll Calendar Selection (Primary Method) -->
                                    <div class="mb-3">
                                        <label for="payroll_calendar_id" class="form-label">
                                            <i class="bx bx-calendar me-1"></i>Payroll Calendar Period <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select @error('payroll_calendar_id') is-invalid @enderror" id="payroll_calendar_id" name="payroll_calendar_id">
                                            <option value="">Select Payroll Calendar Period</option>
                                            @foreach($payrollCalendars as $calendar)
                                                <option value="{{ $calendar['id'] }}" {{ old('payroll_calendar_id') == $calendar['id'] ? 'selected' : '' }}>
                                                    {{ $calendar['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('payroll_calendar_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">
                                            <i class="bx bx-info-circle me-1"></i>Select a payroll calendar period. This ensures cut-off and pay dates are properly configured.
                                        </small>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="useManualSelection" name="use_manual_selection">
                                            <label class="form-check-label" for="useManualSelection">
                                                Use manual year/month selection instead
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Manual Year/Month Selection (Fallback) -->
                                    <div class="row" id="manualSelection" style="display: none;">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="year" class="form-label">Year <span class="text-danger">*</span></label>
                                                <select class="form-select @error('year') is-invalid @enderror" id="year" name="year">
                                                    <option value="">Select Year</option>
                                                    @foreach($years as $year)
                                                        <option value="{{ $year }}" {{ old('year', $currentYear) == $year ? 'selected' : '' }}>
                                                            {{ $year }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('year')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="month" class="form-label">Month <span class="text-danger">*</span></label>
                                                <select class="form-select @error('month') is-invalid @enderror" id="month" name="month">
                                                    <option value="">Select Month</option>
                                                    @foreach($months as $monthNumber => $monthName)
                                                        <option value="{{ $monthNumber }}" {{ old('month', $currentMonth) == $monthNumber ? 'selected' : '' }}>
                                                            {{ $monthName }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('month')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <!-- Fallback: Manual Year/Month Selection if no calendars exist -->
                                    <div class="alert alert-warning mb-3">
                                        <i class="bx bx-info-circle me-2"></i>
                                        <strong>No Payroll Calendars Found:</strong> Please create payroll calendars first, or use manual year/month selection below.
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="year" class="form-label">Year <span class="text-danger">*</span></label>
                                                <select class="form-select @error('year') is-invalid @enderror" id="year" name="year" required>
                                                    <option value="">Select Year</option>
                                                    @foreach($years as $year)
                                                        <option value="{{ $year }}" {{ old('year', $currentYear) == $year ? 'selected' : '' }}>
                                                            {{ $year }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('year')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted">
                                                    <i class="bx bx-info-circle me-1"></i>Select the year for this payroll period
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="month" class="form-label">Month <span class="text-danger">*</span></label>
                                                <select class="form-select @error('month') is-invalid @enderror" id="month" name="month" required>
                                                    <option value="">Select Month</option>
                                                    @foreach($months as $monthNumber => $monthName)
                                                        <option value="{{ $monthNumber }}" {{ old('month', $currentMonth) == $monthNumber ? 'selected' : '' }}>
                                                            {{ $monthName }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('month')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted">
                                                    <i class="bx bx-info-circle me-1"></i>Select the month for salary processing
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Pay Group Selection (Optional) -->
                                @if(isset($payGroups) && $payGroups->count() > 0)
                                    <div class="mb-3">
                                        <label for="pay_group_id" class="form-label">
                                            <i class="bx bx-group me-1"></i>Pay Group (Optional)
                                        </label>
                                        <select class="form-select select2-single @error('pay_group_id') is-invalid @enderror" id="pay_group_id" name="pay_group_id" data-placeholder="All Employees (No Pay Group)">
                                            <option value="">All Employees (No Pay Group)</option>
                                            @foreach($payGroups as $payGroup)
                                                <option value="{{ $payGroup->id }}" {{ old('pay_group_id') == $payGroup->id ? 'selected' : '' }}>
                                                    {{ $payGroup->pay_group_code }} - {{ $payGroup->pay_group_name }}
                                                    @if($payGroup->payment_frequency)
                                                        ({{ ucfirst($payGroup->payment_frequency) }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('pay_group_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">
                                            <i class="bx bx-info-circle me-1"></i>Select a pay group to process payroll only for employees in that group. Leave empty to process all employees.
                                        </small>
                                    </div>
                                @endif

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3" placeholder="Enter any additional notes for this payroll...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">
                                        <i class="bx bx-info-circle me-1"></i>Optional: Add special notes or instructions for this payroll (e.g., bonuses, adjustments)
                                    </small>
                                </div>

                                <div class="alert alert-info border-info">
                                    <i class="bx bx-info-circle me-2"></i>
                                    <strong>Note:</strong> This will create a draft payroll for the selected month and year. You can add employees and calculate their salaries in the next steps.
                                </div>

                                <div class="mt-3 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i>Create Payroll
                                    </button>
                                    <a href="{{ route('hr.payrolls.index') }}" class="btn btn-secondary">
                                        <i class="bx bx-x me-1"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right: Guidelines -->
                <div class="col-lg-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="mb-3">
                                <i class="bx bx-help-circle text-primary me-1"></i>How to Create Payroll
                            </h6>
                            <hr>
                            
                            <div class="mb-3">
                                <h6 class="text-primary">
                                    <i class="bx bx-calendar me-1"></i>1. Select Period
                                </h6>
                                <p class="small mb-2">Choose the payroll calendar period or year/month for this payroll run.</p>
                                <ul class="small text-muted mb-0">
                                    <li>Payroll calendar is preferred (includes cut-off & pay dates)</li>
                                    <li>Or select year and month manually</li>
                                    <li>Cannot create duplicate payrolls for same period</li>
                                    <li>Default is current period</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-primary">
                                    <i class="bx bx-group me-1"></i>2. Select Pay Group (Optional)
                                </h6>
                                <p class="small mb-2">Choose a pay group to filter employees:</p>
                                <ul class="small text-muted mb-0">
                                    <li>Leave empty to process all employees</li>
                                    <li>Select a pay group to process only employees in that group</li>
                                    <li>Pay group dates will be used if no calendar is selected</li>
                                    <li>Each pay group can have separate payroll runs</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-primary">
                                    <i class="bx bx-note me-1"></i>3. Add Notes (Optional)
                                </h6>
                                <p class="small mb-2">Document any special information:</p>
                                <ul class="small text-muted mb-0">
                                    <li>Salary adjustments</li>
                                    <li>Special bonuses</li>
                                    <li>Policy changes</li>
                                    <li>Deduction changes</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-primary">
                                    <i class="bx bx-list-ul me-1"></i>4. After Creation
                                </h6>
                                <p class="small mb-2">Next steps in the process:</p>
                                <ul class="small text-muted mb-0">
                                    <li>Process payroll (employees will be filtered by pay group if selected)</li>
                                    <li>Calculate salaries & deductions</li>
                                    <li>Review calculations</li>
                                    <li>Finalize for approval</li>
                                </ul>
                            </div>

                            <hr>

                            <div class="alert alert-warning mb-3">
                                <small>
                                    <i class="bx bx-shield text-warning me-1"></i>
                                    @php
                                        $approvalSettings = \App\Models\PayrollApprovalSettings::where('company_id', current_company_id())->first();
                                    @endphp
                                    @if($approvalSettings && $approvalSettings->approval_required)
                                        <strong>Approval Required:</strong> This payroll will need {{ $approvalSettings->approval_levels }} level(s) of approval before payment.
                                    @else
                                        <strong>No Approval:</strong> Direct processing enabled.
                                    @endif
                                </small>
                            </div>

                            <div class="alert alert-info mb-0">
                                <small>
                                    <i class="bx bx-user text-info me-1"></i>
                                    <strong>Active Employees:</strong> {{ \App\Models\Hr\Employee::where('status', 'active')->count() }} employees ready for payroll
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('DOMContentLoaded', function() {
        const useManualCheckbox = document.getElementById('useManualSelection');
        const manualSelection = document.getElementById('manualSelection');
        const calendarSelect = document.getElementById('payroll_calendar_id');
        const yearSelect = document.getElementById('year');
        const monthSelect = document.getElementById('month');

        // Toggle manual selection visibility
        if (useManualCheckbox) {
            useManualCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    manualSelection.style.display = 'block';
                    calendarSelect.removeAttribute('required');
                    if (yearSelect) yearSelect.setAttribute('required', 'required');
                    if (monthSelect) monthSelect.setAttribute('required', 'required');
                } else {
                    manualSelection.style.display = 'none';
                    calendarSelect.setAttribute('required', 'required');
                    if (yearSelect) yearSelect.removeAttribute('required');
                    if (monthSelect) monthSelect.removeAttribute('required');
                }
            });
        }

        // Auto-select current year and month on page load (only if manual selection is visible)
        if (yearSelect && monthSelect && (!useManualCheckbox || !useManualCheckbox.checked)) {
            if (!yearSelect.value) {
                yearSelect.value = '{{ $currentYear }}';
            }
            if (!monthSelect.value) {
                monthSelect.value = '{{ $currentMonth }}';
            }
        }
    });
</script>
@endpush
