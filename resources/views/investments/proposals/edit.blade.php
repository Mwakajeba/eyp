@extends('layouts.main')

@section('title', 'Edit Investment Proposal')

@push('css')
<style>
    .card-header.bg-gradient {
        background: linear-gradient(135deg, var(--bs-primary) 0%, #0056b3 100%);
    }
    .card-header.bg-info.bg-gradient {
        background: linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%);
    }
    .card-header.bg-success.bg-gradient {
        background: linear-gradient(135deg, #198754 0%, #146c43 100%);
    }
    .card-header.bg-warning.bg-gradient {
        background: linear-gradient(135deg, #ffc107 0%, #cc9a06 100%);
    }
    .form-label .bx-info-circle {
        cursor: help;
    }
    .sticky-top {
        z-index: 1020;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Investment Management', 'url' => route('investments.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Proposals', 'url' => route('investments.proposals.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Edit Proposal', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">EDIT PROPOSAL: {{ $proposal->proposal_number }}</h6>
            <span class="badge bg-{{ $proposal->status == 'DRAFT' ? 'secondary' : ($proposal->status == 'APPROVED' ? 'success' : 'warning') }}">
                {{ $proposal->status }}
            </span>
        </div>
        <hr />

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <form action="{{ route('investments.proposals.update', $proposal->hash_id) }}" method="POST" id="proposal-form">
            @csrf
            @method('PUT')

            <div class="row">
                <!-- Main Form -->
                <div class="col-lg-8">
                    <!-- Investment Instrument Details -->
                    <div class="card mb-3">
                        <div class="card-header bg-primary bg-gradient text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-package me-2"></i>Investment Instrument Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Instrument Type <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Type of investment instrument. T-BILL: Treasury Bill (short-term), T-BOND: Treasury Bond (long-term), FIXED_DEPOSIT: Bank fixed deposit, CORP_BOND: Corporate bond, EQUITY: Stock/share, MMF: Money Market Fund, OTHER: Other investment types"></i>
                                    </label>
                                    <select name="instrument_type" id="instrument_type" class="form-select select2-single" required>
                                        <option value="">Select Instrument Type</option>
                                        <option value="T_BILL" {{ old('instrument_type', $proposal->instrument_type) == 'T_BILL' ? 'selected' : '' }}>T-Bill (Treasury Bill)</option>
                                        <option value="T_BOND" {{ old('instrument_type', $proposal->instrument_type) == 'T_BOND' ? 'selected' : '' }}>T-Bond (Treasury Bond)</option>
                                        <option value="FIXED_DEPOSIT" {{ old('instrument_type', $proposal->instrument_type) == 'FIXED_DEPOSIT' ? 'selected' : '' }}>Fixed Deposit</option>
                                        <option value="CORP_BOND" {{ old('instrument_type', $proposal->instrument_type) == 'CORP_BOND' ? 'selected' : '' }}>Corporate Bond</option>
                                        <option value="EQUITY" {{ old('instrument_type', $proposal->instrument_type) == 'EQUITY' ? 'selected' : '' }}>Equity (Stock/Share)</option>
                                        <option value="MMF" {{ old('instrument_type', $proposal->instrument_type) == 'MMF' ? 'selected' : '' }}>Money Market Fund</option>
                                        <option value="OTHER" {{ old('instrument_type', $proposal->instrument_type) == 'OTHER' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">Type of investment instrument being proposed</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Issuer
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Name of the entity issuing the investment. For T-Bills/Bonds: Government, For Corporate Bonds: Company name, For Fixed Deposits: Bank name, For Equity: Company name"></i>
                                    </label>
                                    <input type="text" name="issuer" id="issuer" class="form-control" value="{{ old('issuer', $proposal->issuer) }}" placeholder="e.g., Bank of Tanzania, ABC Corporation">
                                    <small class="text-muted d-block mt-1">Name of the issuer (government, bank, or company)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Details -->
                    <div class="card mb-3">
                        <div class="card-header bg-success bg-gradient text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-dollar me-2"></i>Financial Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Proposed Amount (TZS) <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Total amount of money proposed to invest. This is the principal amount that will be invested in the instrument."></i>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">TZS</span>
                                        <input type="number" name="proposed_amount" id="proposed_amount" class="form-control" step="0.01" min="0.01" value="{{ old('proposed_amount', $proposal->proposed_amount) }}" required placeholder="0.00">
                                    </div>
                                    <small class="text-muted d-block mt-1">Total investment amount in Tanzanian Shillings</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Expected Yield (%)
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Expected annual return or interest rate. For bonds, this is the coupon rate. For fixed deposits, this is the interest rate. Expressed as a percentage (e.g., 5.5 for 5.5%)."></i>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" name="expected_yield" id="expected_yield" class="form-control" step="0.01" min="0" max="100" value="{{ old('expected_yield', $proposal->expected_yield) }}" placeholder="0.00">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <small class="text-muted d-block mt-1">Expected annual return percentage</small>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">
                                        Risk Rating
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Risk assessment of the investment. LOW: Government securities, high-grade bonds. MEDIUM: Corporate bonds, blue-chip equity. HIGH: High-yield bonds, speculative equity."></i>
                                    </label>
                                    <select name="risk_rating" id="risk_rating" class="form-select select2-single">
                                        <option value="">Select Risk Rating</option>
                                        <option value="LOW" {{ old('risk_rating', $proposal->risk_rating) == 'LOW' ? 'selected' : '' }}>Low Risk</option>
                                        <option value="MEDIUM" {{ old('risk_rating', $proposal->risk_rating) == 'MEDIUM' ? 'selected' : '' }}>Medium Risk</option>
                                        <option value="HIGH" {{ old('risk_rating', $proposal->risk_rating) == 'HIGH' ? 'selected' : '' }}>High Risk</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">Investment risk classification</small>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">
                                        Tenor
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Investment period. For T-Bills: typically 91, 182, or 364 days. For T-Bonds: typically 2, 5, 10, or 20 years. For Fixed Deposits: varies by bank."></i>
                                    </label>
                                    <div class="input-group">
                                        @php
                                            // Try to detect if existing value is in years or days
                                            $tenorDays = old('tenor_days', $proposal->tenor_days ?? 0);
                                            
                                            // Smart detection: Check if value is divisible by 365 (likely was entered as years)
                                            // For T-Bonds and Corporate Bonds, if the value is a multiple of 365, assume it was years
                                            $isBondType = in_array($proposal->instrument_type, ['T_BOND', 'CORP_BOND']);
                                            $yearsValue = $tenorDays > 0 ? round($tenorDays / 365, 4) : 0; // Use 4 decimals for precision
                                            
                                            // Check if the value is a clean multiple of 365 (within 0.5 days tolerance)
                                            $remainder = $tenorDays > 0 ? abs($tenorDays % 365) : 999;
                                            $isMultipleOf365 = $remainder < 0.5 || $remainder > 364.5;
                                            
                                            // If it's a bond type and the value is a multiple of 365, treat as years
                                            // Also if the value is less than 100 and it's a bond, likely years
                                            $isLikelyYears = false;
                                            if ($isBondType && $tenorDays > 0) {
                                                if ($isMultipleOf365 && $yearsValue > 0 && $yearsValue <= 50) {
                                                    // Value is a clean multiple of 365, likely was entered as years
                                                    // Example: 730 days = 2 years, 1825 days = 5 years
                                                    $isLikelyYears = true;
                                                } elseif ($tenorDays < 100 && $tenorDays > 0) {
                                                    // Small value for a bond (e.g., 2, 5, 10), likely years not days
                                                    $isLikelyYears = true;
                                                }
                                            }
                                            
                                            // Use old() for form resubmission, otherwise use detected value
                                            $tenorValue = old('tenor_value', $isLikelyYears ? $yearsValue : $tenorDays);
                                            $tenorUnit = old('tenor_unit', $isLikelyYears ? 'years' : 'days');
                                        @endphp
                                        <input type="number" name="tenor_value" id="tenor_value" class="form-control" step="0.01" min="0.01" value="{{ old('tenor_value', $tenorValue) }}" placeholder="Enter value">
                                        <select name="tenor_unit" id="tenor_unit" class="form-select" style="max-width: 100px;">
                                            <option value="days" {{ $tenorUnit == 'days' ? 'selected' : '' }}>Days</option>
                                            <option value="years" {{ $tenorUnit == 'years' ? 'selected' : '' }}>Years</option>
                                        </select>
                                        <input type="hidden" name="tenor_days" id="tenor_days" value="{{ old('tenor_days', $proposal->tenor_days) }}">
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        <span id="tenor_hint">Enter period in days or years (auto-converts to days)</span>
                                        <span id="tenor_converted" class="text-primary fw-semibold ms-2" style="display: none;"></span>
                                    </small>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">
                                        Accounting Classification <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="IFRS 9 classification: AMORTISED_COST: Held to maturity, measured at amortized cost. FVOCI: Fair value through other comprehensive income. FVPL: Fair value through profit or loss."></i>
                                    </label>
                                    <select name="proposed_accounting_class" id="proposed_accounting_class" class="form-select select2-single" required>
                                        <option value="AMORTISED_COST" {{ old('proposed_accounting_class', $proposal->proposed_accounting_class) == 'AMORTISED_COST' ? 'selected' : '' }}>Amortized Cost</option>
                                        <option value="FVOCI" {{ old('proposed_accounting_class', $proposal->proposed_accounting_class) == 'FVOCI' ? 'selected' : '' }}>FVOCI (Fair Value OCI)</option>
                                        <option value="FVPL" {{ old('proposed_accounting_class', $proposal->proposed_accounting_class) == 'FVPL' ? 'selected' : '' }}>FVPL (Fair Value P&L)</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">IFRS 9 accounting classification</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Proposal Description -->
                    <div class="card mb-3">
                        <div class="card-header bg-info bg-gradient text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-file-blank me-2"></i>Proposal Description & Rationale
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-semibold">
                                        Description
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Brief description of the investment proposal. Include key details like instrument features, terms, and conditions."></i>
                                    </label>
                                    <textarea name="description" id="description" class="form-control" rows="4" placeholder="Describe the investment instrument, its features, and key terms...">{{ old('description', $proposal->description) }}</textarea>
                                    <small class="text-muted d-block mt-1">Provide a clear description of the investment proposal</small>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-semibold">
                                        Rationale <span class="text-muted">(Why this investment?)</span>
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Explain the business rationale for this investment. Include: investment objectives, expected benefits, risk assessment, alignment with company strategy, and any other relevant justification."></i>
                                    </label>
                                    <textarea name="rationale" id="rationale" class="form-control" rows="5" placeholder="Explain why this investment is being proposed. Include investment objectives, expected benefits, risk considerations, and strategic alignment...">{{ old('rationale', $proposal->rationale) }}</textarea>
                                    <small class="text-muted d-block mt-1">
                                        <strong>Important:</strong> This rationale will be reviewed by approvers. Provide a comprehensive justification for the investment.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Information Sidebar -->
                <div class="col-lg-4">
                    <div class="card sticky-top" style="top: 20px;">
                        <div class="card-header bg-primary bg-gradient text-white">
                            <h6 class="mb-0">
                                <i class="bx bx-info-circle me-2"></i>Proposal Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="text-muted small mb-2">Proposal Number</h6>
                                <p class="fw-bold mb-0">{{ $proposal->proposal_number }}</p>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-muted small mb-2">Current Status</h6>
                                <span class="badge bg-{{ $proposal->status == 'DRAFT' ? 'secondary' : ($proposal->status == 'APPROVED' ? 'success' : ($proposal->status == 'REJECTED' ? 'danger' : 'warning')) }} mb-0">
                                    {{ $proposal->status }}
                                </span>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-muted small mb-2">Created</h6>
                                <p class="mb-0 small">{{ $proposal->created_at->format('M d, Y h:i A') }}</p>
                                @if($proposal->creator)
                                <p class="mb-0 small text-muted">by {{ $proposal->creator->name }}</p>
                                @endif
                            </div>

                            @if($proposal->updated_at != $proposal->created_at)
                            <div class="mb-3">
                                <h6 class="text-muted small mb-2">Last Updated</h6>
                                <p class="mb-0 small">{{ $proposal->updated_at->format('M d, Y h:i A') }}</p>
                            </div>
                            @endif

                            <hr>

                            <div class="alert alert-info mb-3">
                                <h6 class="alert-heading">
                                    <i class="bx bx-bulb me-1"></i>Quick Tips
                                </h6>
                                <ul class="mb-0 small">
                                    <li>Only DRAFT and REJECTED proposals can be edited</li>
                                    <li>Update financial details accurately</li>
                                    <li>Ensure rationale is comprehensive</li>
                                    <li>Changes will be tracked in audit log</li>
                                </ul>
                            </div>

                            <div class="alert alert-warning mb-3">
                                <h6 class="alert-heading">
                                    <i class="bx bx-error-circle me-1"></i>Required Fields
                                </h6>
                                <ul class="mb-0 small">
                                    <li><span class="text-danger">*</span> Instrument Type</li>
                                    <li><span class="text-danger">*</span> Proposed Amount</li>
                                    <li><span class="text-danger">*</span> Accounting Classification</li>
                                </ul>
                            </div>

                            <div class="alert alert-success">
                                <h6 class="alert-heading">
                                    <i class="bx bx-check-circle me-1"></i>Editing Rules
                                </h6>
                                <ul class="mb-0 small">
                                    <li>All changes are logged</li>
                                    <li>Proposal number cannot be changed</li>
                                    <li>Status changes require approval workflow</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="card mt-3 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">
                                <i class="bx bx-info-circle"></i> 
                                Changes will be saved to this proposal.
                            </small>
                        </div>
                        <div class="btn-group">
                            <a href="{{ route('investments.proposals.show', $proposal->hash_id) }}" class="btn btn-secondary">
                                <i class="bx bx-x"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save"></i> Update Proposal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize Select2 for all select elements
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: function() {
            return $(this).data('placeholder') || 'Select an option';
        }
    });

    // Tenor conversion logic
    const instrumentTypeSelect = document.getElementById('instrument_type') || document.querySelector('select[name="instrument_type"]');
    const tenorValueInput = document.getElementById('tenor_value');
    const tenorUnitSelect = document.getElementById('tenor_unit');
    const tenorDaysHidden = document.getElementById('tenor_days');
    const tenorHint = document.getElementById('tenor_hint');
    const tenorConverted = document.getElementById('tenor_converted');

    // Function to convert tenor to days
    function convertTenorToDays() {
        if (!tenorValueInput || !tenorUnitSelect || !tenorDaysHidden) return;
        
        const value = parseFloat(tenorValueInput.value);
        const unit = tenorUnitSelect.value;
        
        if (!value || isNaN(value) || value <= 0) {
            tenorDaysHidden.value = '';
            if (tenorConverted) tenorConverted.style.display = 'none';
            return;
        }

        let days;
        if (unit === 'years') {
            days = Math.round(value * 365);
        } else {
            days = Math.round(value);
        }

        tenorDaysHidden.value = days;
        
        if (tenorConverted) {
            if (unit === 'years') {
                tenorConverted.textContent = `= ${days.toLocaleString()} days`;
                tenorConverted.style.display = 'inline';
            } else {
                tenorConverted.style.display = 'none';
            }
        }
    }

    // Function to set default unit based on instrument type
    function setDefaultTenorUnit() {
        if (!instrumentTypeSelect || !tenorUnitSelect || !tenorHint) return;
        
        const instrumentType = instrumentTypeSelect.value;
        
        if (instrumentType === 'T_BOND' || instrumentType === 'CORP_BOND') {
            tenorUnitSelect.value = 'years';
            tenorHint.textContent = 'Enter period in years (auto-converts to days)';
        } else {
            tenorUnitSelect.value = 'days';
            tenorHint.textContent = 'Enter period in days or years (auto-converts to days)';
        }
        
        convertTenorToDays();
    }

    if (instrumentTypeSelect) {
        instrumentTypeSelect.addEventListener('change', setDefaultTenorUnit);
    }
    
    if (tenorValueInput) {
        tenorValueInput.addEventListener('input', convertTenorToDays);
        tenorValueInput.addEventListener('blur', convertTenorToDays);
    }
    if (tenorUnitSelect) {
        tenorUnitSelect.addEventListener('change', convertTenorToDays);
    }

    // Initialize conversion
    if (instrumentTypeSelect && instrumentTypeSelect.value) {
        setDefaultTenorUnit();
    } else if (tenorValueInput && tenorValueInput.value) {
        convertTenorToDays();
    }

    // Convert on form submit
    const proposalForm = document.getElementById('proposal-form');
    if (proposalForm) {
        proposalForm.addEventListener('submit', function(e) {
            if (tenorValueInput && tenorDaysHidden) {
                convertTenorToDays();
            }
        });
    }
});
</script>
@endpush
