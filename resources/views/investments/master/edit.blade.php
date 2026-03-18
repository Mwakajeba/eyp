@extends('layouts.main')

@section('title', 'Edit Investment')

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
            ['label' => 'Investment Master', 'url' => route('investments.master.index'), 'icon' => 'bx bx-package'],
            ['label' => $master->instrument_code, 'url' => route('investments.master.show', $master->hash_id), 'icon' => 'bx bx-show'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="mb-0 text-uppercase">EDIT INVESTMENT: {{ $master->instrument_code }}</h6>
                <small class="text-muted">{{ str_replace('_', ' ', $master->instrument_type) }} • {{ $master->issuer ?? 'N/A' }}</small>
            </div>
            <span class="badge bg-{{ $master->status == 'DRAFT' ? 'secondary' : ($master->status == 'ACTIVE' ? 'success' : 'warning') }} fs-6">
                {{ $master->status }}
            </span>
        </div>
        <hr />

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>
            <strong>Please correct the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <form action="{{ route('investments.master.update', $master->hash_id) }}" method="POST" id="investment-form">
            @csrf
            @method('PUT')

            <div class="row">
                <!-- Main Form -->
                <div class="col-lg-8">
                    <!-- Basic Information -->
                    <div class="card mb-3 border-0 shadow-sm">
                        <div class="card-header bg-primary bg-gradient text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-info-circle me-2"></i>Basic Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Instrument Code
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Unique identifier for this investment. Auto-generated and cannot be changed."></i>
                                    </label>
                                    <input type="text" class="form-control bg-light" value="{{ $master->instrument_code }}" readonly>
                                    <small class="text-muted d-block mt-1">Auto-generated code (read-only)</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Instrument Type
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Type of investment instrument. Cannot be changed after creation."></i>
                                    </label>
                                    <input type="text" class="form-control bg-light" value="{{ str_replace('_', ' ', $master->instrument_type) }}" readonly>
                                    <small class="text-muted d-block mt-1">Type (read-only)</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Issuer
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Name of the entity issuing the investment (e.g., Bank of Tanzania, ABC Corporation)"></i>
                                    </label>
                                    <input type="text" name="issuer" class="form-control" value="{{ old('issuer', $master->issuer) }}" placeholder="e.g., Bank of Tanzania">
                                    <small class="text-muted d-block mt-1">Entity issuing the investment</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        ISIN
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="International Securities Identification Number (if applicable)"></i>
                                    </label>
                                    <input type="text" name="isin" class="form-control" value="{{ old('isin', $master->isin) }}" placeholder="e.g., TZ0000000001" maxlength="50">
                                    <small class="text-muted d-block mt-1">International Securities Identification Number</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Currency <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Currency in which the investment is denominated (e.g., TZS, USD, EUR)"></i>
                                    </label>
                                    <select name="currency" class="form-select select2-single" required>
                                        <option value="TZS" {{ old('currency', $master->currency) == 'TZS' ? 'selected' : '' }}>TZS - Tanzanian Shilling</option>
                                        <option value="USD" {{ old('currency', $master->currency) == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                        <option value="EUR" {{ old('currency', $master->currency) == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                        <option value="GBP" {{ old('currency', $master->currency) == 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                                        <option value="KES" {{ old('currency', $master->currency) == 'KES' ? 'selected' : '' }}>KES - Kenyan Shilling</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">Investment currency</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Tax Class
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Tax classification for this investment. Determines how interest income, dividends, and capital gains are taxed."></i>
                                    </label>
                                    @php
                                        $taxClass = old('tax_class', $master->tax_class);
                                        $taxClassCustom = old('tax_class_custom', '');
                                        $isCustomTaxClass = $taxClass && !in_array($taxClass, ['TAXABLE', 'TAX_EXEMPT', 'WITHHOLDING_TAX', 'TAX_FREE', 'TAX_DEFERRED', 'CAPITAL_GAINS_TAX', 'INTEREST_INCOME_TAX', 'DIVIDEND_TAX', 'GOVERNMENT_SECURITIES', 'CORPORATE_BOND_TAX', 'FIXED_DEPOSIT_TAX', 'EQUITY_TAX', 'MMF_TAX', 'ZERO_RATED', 'OTHER']);
                                        // If it's a custom tax class, set the custom input value
                                        if ($isCustomTaxClass && !$taxClassCustom) {
                                            $taxClassCustom = $taxClass;
                                        }
                                    @endphp
                                    <select name="tax_class" id="tax_class" class="form-select select2-single">
                                        <option value="">Select Tax Class</option>
                                        <option value="TAXABLE" {{ $taxClass == 'TAXABLE' ? 'selected' : '' }}>Taxable</option>
                                        <option value="TAX_EXEMPT" {{ $taxClass == 'TAX_EXEMPT' ? 'selected' : '' }}>Tax-Exempt</option>
                                        <option value="WITHHOLDING_TAX" {{ $taxClass == 'WITHHOLDING_TAX' ? 'selected' : '' }}>Withholding Tax (WHT)</option>
                                        <option value="TAX_FREE" {{ $taxClass == 'TAX_FREE' ? 'selected' : '' }}>Tax-Free</option>
                                        <option value="TAX_DEFERRED" {{ $taxClass == 'TAX_DEFERRED' ? 'selected' : '' }}>Tax-Deferred</option>
                                        <option value="CAPITAL_GAINS_TAX" {{ $taxClass == 'CAPITAL_GAINS_TAX' ? 'selected' : '' }}>Capital Gains Tax</option>
                                        <option value="INTEREST_INCOME_TAX" {{ $taxClass == 'INTEREST_INCOME_TAX' ? 'selected' : '' }}>Interest Income Tax</option>
                                        <option value="DIVIDEND_TAX" {{ $taxClass == 'DIVIDEND_TAX' ? 'selected' : '' }}>Dividend Tax</option>
                                        <option value="GOVERNMENT_SECURITIES" {{ $taxClass == 'GOVERNMENT_SECURITIES' ? 'selected' : '' }}>Government Securities (Tax-Exempt)</option>
                                        <option value="CORPORATE_BOND_TAX" {{ $taxClass == 'CORPORATE_BOND_TAX' ? 'selected' : '' }}>Corporate Bond Tax</option>
                                        <option value="FIXED_DEPOSIT_TAX" {{ $taxClass == 'FIXED_DEPOSIT_TAX' ? 'selected' : '' }}>Fixed Deposit Tax</option>
                                        <option value="EQUITY_TAX" {{ $taxClass == 'EQUITY_TAX' ? 'selected' : '' }}>Equity Tax</option>
                                        <option value="MMF_TAX" {{ $taxClass == 'MMF_TAX' ? 'selected' : '' }}>Money Market Fund Tax</option>
                                        <option value="ZERO_RATED" {{ $taxClass == 'ZERO_RATED' ? 'selected' : '' }}>Zero-Rated</option>
                                        <option value="OTHER" {{ $isCustomTaxClass || $taxClass == 'OTHER' ? 'selected' : '' }}>Other (Custom)</option>
                                    </select>
                                    <div id="custom_tax_class_container" style="display: {{ $isCustomTaxClass || $taxClass == 'OTHER' ? 'block' : 'none' }};" class="mt-2">
                                        <input type="text" name="tax_class_custom" id="tax_class_custom" class="form-control" value="{{ $taxClassCustom }}" placeholder="Enter custom tax class" maxlength="50">
                                        <small class="text-muted d-block mt-1">Enter custom tax class name</small>
                                    </div>
                                    <small class="text-muted d-block mt-1">Tax classification for accounting and reporting purposes</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dates & Terms -->
                    <div class="card mb-3 border-0 shadow-sm">
                        <div class="card-header bg-success bg-gradient text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-calendar me-2"></i>Dates & Terms
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Maturity Date
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Date when the investment matures and principal is repaid"></i>
                                    </label>
                                    <input type="date" name="maturity_date" class="form-control" value="{{ old('maturity_date', $master->maturity_date ? $master->maturity_date->format('Y-m-d') : '') }}">
                                    <small class="text-muted d-block mt-1">Investment maturity date</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Day Count Convention
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Method for calculating interest accrual periods: ACT/365 (actual days/365), ACT/360 (actual days/360), 30/360 (30 days per month/360 days per year)"></i>
                                    </label>
                                    <select name="day_count" class="form-select select2-single">
                                        <option value="ACT/365" {{ old('day_count', $master->day_count) == 'ACT/365' ? 'selected' : '' }}>ACT/365 (Actual/365)</option>
                                        <option value="ACT/360" {{ old('day_count', $master->day_count) == 'ACT/360' ? 'selected' : '' }}>ACT/360 (Actual/360)</option>
                                        <option value="30/360" {{ old('day_count', $master->day_count) == '30/360' ? 'selected' : '' }}>30/360 (30 days/360 days)</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">Interest calculation method</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Interest & Coupon Details -->
                    @if(in_array($master->instrument_type, ['T_BOND', 'CORP_BOND', 'FIXED_DEPOSIT']))
                    <div class="card mb-3 border-0 shadow-sm">
                        <div class="card-header bg-info bg-gradient text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-percentage me-2"></i>Interest & Coupon Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Coupon Rate (%)
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Annual interest rate as a percentage (e.g., 5.5 for 5.5% per annum)"></i>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" name="coupon_rate" class="form-control" step="0.000001" min="0" max="100" value="{{ old('coupon_rate', $master->coupon_rate ? $master->coupon_rate * 100 : '') }}" placeholder="0.000000">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <small class="text-muted d-block mt-1">Annual interest rate percentage</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Coupon Frequency
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Number of coupon/interest payments per year (e.g., 1 = Annual, 2 = Semi-annual, 4 = Quarterly, 12 = Monthly)"></i>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" name="coupon_freq" class="form-control" min="1" max="12" value="{{ old('coupon_freq', $master->coupon_freq) }}" placeholder="e.g., 2">
                                        <span class="input-group-text">per year</span>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        Payments per year (1=Annual, 2=Semi-annual, 4=Quarterly, 12=Monthly)
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Financial Information -->
                    <div class="card mb-3 border-0 shadow-sm">
                        <div class="card-header bg-warning bg-gradient text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-money me-2"></i>Financial Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Nominal Amount
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Total face value or principal amount of the investment. This should match the proposed amount from the proposal."></i>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">TZS</span>
                                        <input type="number" name="nominal_amount" class="form-control" step="0.01" min="0" value="{{ old('nominal_amount', $master->nominal_amount) }}" placeholder="0.00">
                                    </div>
                                    @if(isset($sourceProposal) && $sourceProposal->proposed_amount > 0)
                                    <small class="text-info d-block mt-1">
                                        <i class="bx bx-info-circle"></i> 
                                        Proposed amount from proposal: <strong>TZS {{ number_format($sourceProposal->proposed_amount, 2) }}</strong>
                                        @if($master->nominal_amount == 0)
                                            <button type="button" class="btn btn-sm btn-link p-0 ms-2" onclick="document.querySelector('input[name=nominal_amount]').value='{{ $sourceProposal->proposed_amount }}'">
                                                <i class="bx bx-copy"></i> Use this value
                                            </button>
                                        @endif
                                    </small>
                                    @else
                                    <small class="text-muted d-block mt-1">Total face value/principal amount</small>
                                    @endif
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Read-only Financial Summary -->
                            <h6 class="text-muted mb-3">
                                <i class="bx bx-info-circle me-1"></i>Trade-Dependent Fields
                                <small class="text-muted">(Updated when trades are captured)</small>
                            </h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="info-item border-0">
                                        <div class="info-label">Purchase Price</div>
                                        <div class="info-value text-success">
                                            {{ $master->purchase_price > 0 ? number_format($master->purchase_price, 6) . ' per unit' : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="info-item border-0">
                                        <div class="info-label">Units</div>
                                        <div class="info-value text-info">
                                            {{ $master->units > 0 ? number_format($master->units, 6) : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="info-item border-0">
                                        <div class="info-label">Carrying Amount</div>
                                        <div class="info-value text-warning fs-5">
                                            {{ $master->carrying_amount > 0 ? 'TZS ' . number_format($master->carrying_amount, 2) : 'TZS 0.00' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="info-item border-0">
                                        <div class="info-label">EIR Rate</div>
                                        <div class="info-value">
                                            @if($master->eir_rate)
                                                <span class="badge bg-info">{{ number_format($master->eir_rate, 4) }}%</span>
                                            @else
                                                <span class="text-muted">Not calculated</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="info-item border-0">
                                        <div class="info-label">Purchase Date</div>
                                        <div class="info-value">
                                            {{ $master->purchase_date ? $master->purchase_date->format('M d, Y') : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="info-item border-0">
                                        <div class="info-label">Settlement Date</div>
                                        <div class="info-value">
                                            {{ $master->settlement_date ? $master->settlement_date->format('M d, Y') : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-info mb-0">
                                <i class="bx bx-info-circle me-2"></i>
                                <strong>Note:</strong> Purchase price, units, purchase date, and settlement date are automatically set when you capture a PURCHASE trade. The carrying amount is calculated as units × purchase price. EIR rate is calculated when you run the EIR recalculation.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Information Sidebar -->
                <div class="col-lg-4">
                    <div class="card sticky-top border-0 shadow-sm" style="top: 20px;">
                        <div class="card-header bg-primary bg-gradient text-white">
                            <h6 class="mb-0">
                                <i class="bx bx-info-circle me-2"></i>Investment Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="text-muted small mb-2">Instrument Code</h6>
                                <p class="fw-bold mb-0">{{ $master->instrument_code }}</p>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-muted small mb-2">Current Status</h6>
                                <span class="badge bg-{{ $master->status == 'DRAFT' ? 'secondary' : ($master->status == 'ACTIVE' ? 'success' : ($master->status == 'MATURED' ? 'warning' : 'dark')) }} mb-0">
                                    {{ $master->status }}
                                </span>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-muted small mb-2">Instrument Type</h6>
                                <p class="fw-bold mb-0">{{ str_replace('_', ' ', $master->instrument_type) }}</p>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-muted small mb-2">Accounting Class</h6>
                                <span class="badge bg-info mb-0">{{ str_replace('_', ' ', $master->accounting_class) }}</span>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-muted small mb-2">Created</h6>
                                <p class="mb-0 small">{{ $master->created_at->format('M d, Y') }}</p>
                                <p class="mb-0 small text-muted">by {{ $master->creator->name ?? 'N/A' }}</p>
                            </div>

                            @if($master->updated_at != $master->created_at)
                            <div class="mb-3">
                                <h6 class="text-muted small mb-2">Last Updated</h6>
                                <p class="mb-0 small">{{ $master->updated_at->format('M d, Y H:i A') }}</p>
                                @if($master->updater)
                                <p class="mb-0 small text-muted">by {{ $master->updater->name }}</p>
                                @endif
                            </div>
                            @endif

                            <hr>

                            <div class="mb-3">
                                <h6 class="text-muted small mb-2">Editing Rules</h6>
                                <ul class="small mb-0 ps-3">
                                    <li>Only DRAFT investments can be edited</li>
                                    <li>Financial values are updated via trades</li>
                                    <li>Instrument type cannot be changed</li>
                                    <li>Instrument code is auto-generated</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('investments.master.show', $master->hash_id) }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i> Update Investment
                        </button>
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
    // Initialize Select2
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select an option',
        allowClear: true
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Tax Class custom input handling - must work with Select2
    function toggleCustomTaxClass() {
        const taxClassSelect = $('#tax_class');
        const customTaxClassContainer = $('#custom_tax_class_container');
        const customTaxClassInput = $('#tax_class_custom');
        
        const selectedValue = taxClassSelect.val();
        
        if (selectedValue === 'OTHER') {
            customTaxClassContainer.show(); // Use show() instead of slideDown for immediate display
            customTaxClassInput.attr('required', 'required');
        } else {
            customTaxClassContainer.hide(); // Use hide() instead of slideUp for immediate hide
            customTaxClassInput.removeAttr('required');
            if (selectedValue !== 'OTHER') {
                customTaxClassInput.val('');
            }
        }
    }

    // Wait for Select2 to be fully initialized, then attach event handlers
    $('#tax_class').on('select2:ready', function() {
        toggleCustomTaxClass();
    });

    // Listen for Select2 change events (these fire after Select2 is initialized)
    $('#tax_class').on('select2:select select2:clear select2:close', function() {
        toggleCustomTaxClass();
    });

    // Also handle regular change event as fallback
    $('#tax_class').on('change', function() {
        toggleCustomTaxClass();
    });

    // Initialize on page load (after a small delay to ensure Select2 is ready)
    setTimeout(function() {
        toggleCustomTaxClass();
    }, 100);

    // Form submission - handle custom tax class validation
    $('#investment-form').on('submit', function(e) {
        const taxClassValue = $('#tax_class').val();
        const customTaxClassValue = $('#tax_class_custom').val();
        
        // If "OTHER" is selected, validate that custom value is provided
        if (taxClassValue === 'OTHER') {
            if (!customTaxClassValue || !customTaxClassValue.trim()) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Custom Tax Class Required',
                    text: 'Please enter a custom tax class name or select a different tax class.',
                    confirmButtonText: 'OK'
                });
                $('#tax_class_custom').focus();
                return false;
            }
        }
        // The controller will handle using tax_class_custom when tax_class is "OTHER"
    });
});
</script>
@endpush
