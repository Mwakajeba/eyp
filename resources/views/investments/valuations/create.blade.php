@extends('layouts.main')

@section('title', 'Create Investment Valuation')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Investment Management', 'url' => route('investments.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Valuations', 'url' => route('investments.valuations.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'Create Valuation', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">CREATE INVESTMENT VALUATION</h6>
            <a href="{{ route('investments.valuations.index') }}" class="btn btn-secondary">
                <i class="bx bx-arrow-back"></i> Back to List
            </a>
        </div>
        <hr />

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <form action="{{ route('investments.valuations.store') }}" method="POST" id="valuation-form">
            @csrf

            <div class="row">
                <div class="col-lg-8">
                    <!-- Basic Information -->
                    <div class="card mb-3">
                        <div class="card-header bg-primary bg-gradient text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-info-circle me-2"></i>Basic Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Investment <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Select the investment to value. Only FVPL and FVOCI investments are shown."></i>
                                    </label>
                                    <select name="investment_id" id="investment_id" class="form-select select2-single" required>
                                        <option value="">Select Investment</option>
                                        @foreach($investments as $inv)
                                        <option value="{{ $inv->hash_id }}" {{ ($investment && $investment->id == $inv->id) ? 'selected' : '' }}>
                                            {{ $inv->instrument_code }} - {{ $inv->issuer }} ({{ $inv->accounting_class }})
                                        </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="investment_id_decoded" id="investment_id_decoded" value="{{ $investment ? $investment->id : '' }}">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Valuation Date <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Date as of which the valuation is performed"></i>
                                    </label>
                                    <input type="date" name="valuation_date" id="valuation_date" class="form-control" value="{{ old('valuation_date', date('Y-m-d')) }}" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Valuation Level <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="IFRS 13 Fair Value Hierarchy: Level 1 (Market prices), Level 2 (Observable inputs), Level 3 (Unobservable inputs)"></i>
                                    </label>
                                    <select name="valuation_level" id="valuation_level" class="form-select select2-single" required>
                                        <option value="">Select Level</option>
                                        <option value="1" {{ old('valuation_level') == '1' ? 'selected' : '' }}>Level 1 - Quoted Prices (Active Markets)</option>
                                        <option value="2" {{ old('valuation_level') == '2' ? 'selected' : '' }}>Level 2 - Observable Inputs</option>
                                        <option value="3" {{ old('valuation_level') == '3' ? 'selected' : '' }}>Level 3 - Unobservable Inputs</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">Level 3 valuations require approval</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Valuation Method <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Method used to determine fair value"></i>
                                    </label>
                                    <select name="valuation_method" id="valuation_method" class="form-select select2-single" required>
                                        <option value="">Select Method</option>
                                        <option value="MARKET_PRICE" {{ old('valuation_method') == 'MARKET_PRICE' ? 'selected' : '' }}>Market Price</option>
                                        <option value="YIELD_CURVE" {{ old('valuation_method') == 'YIELD_CURVE' ? 'selected' : '' }}>Yield Curve</option>
                                        <option value="DCF" {{ old('valuation_method') == 'DCF' ? 'selected' : '' }}>Discounted Cash Flow (DCF)</option>
                                        <option value="NAV" {{ old('valuation_method') == 'NAV' ? 'selected' : '' }}>Net Asset Value (NAV)</option>
                                        <option value="BANK_VALUATION" {{ old('valuation_method') == 'BANK_VALUATION' ? 'selected' : '' }}>Bank Valuation</option>
                                        <option value="MANUAL" {{ old('valuation_method') == 'MANUAL' ? 'selected' : '' }}>Manual Entry</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fair Value Inputs (Dynamic based on Level and Method) -->
                    <div class="card mb-3">
                        <div class="card-header bg-success bg-gradient text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-calculator me-2"></i>Fair Value Calculation
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Level 1: Market Price -->
                            <div id="level1_fields" class="valuation-level-fields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">
                                            Market Price per Unit <span class="text-danger">*</span>
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Quoted market price from active market"></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="fair_value_per_unit" id="fair_value_per_unit" class="form-control" step="0.000001" min="0" value="{{ old('fair_value_per_unit') }}" placeholder="0.000000">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">
                                            Price Source
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Source of the market price (BOT, DSE, Bloomberg, etc.)"></i>
                                        </label>
                                        <select name="price_source" id="price_source" class="form-select select2-single">
                                            <option value="">Select Source</option>
                                            <option value="BOT" {{ old('price_source') == 'BOT' ? 'selected' : '' }}>BOT (Bank of Tanzania)</option>
                                            <option value="DSE" {{ old('price_source') == 'DSE' ? 'selected' : '' }}>DSE (Dar es Salaam Stock Exchange)</option>
                                            <option value="BLOOMBERG" {{ old('price_source') == 'BLOOMBERG' ? 'selected' : '' }}>Bloomberg</option>
                                            <option value="REUTERS" {{ old('price_source') == 'REUTERS' ? 'selected' : '' }}>Reuters</option>
                                            <option value="MANUAL" {{ old('price_source') == 'MANUAL' ? 'selected' : '' }}>Manual Entry</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">
                                            Price Reference
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Reference number or identifier from the source"></i>
                                        </label>
                                        <input type="text" name="price_reference" id="price_reference" class="form-control" value="{{ old('price_reference') }}" placeholder="e.g., Auction number, ticker">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">
                                            Price Date
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Date of the market price"></i>
                                        </label>
                                        <input type="date" name="price_date" id="price_date" class="form-control" value="{{ old('price_date', date('Y-m-d')) }}">
                                    </div>
                                </div>
                            </div>

                            <!-- Level 2: Observable Inputs -->
                            <div id="level2_fields" class="valuation-level-fields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 mb-3" id="yield_curve_fields" style="display: none;">
                                        <label class="form-label fw-semibold">
                                            Yield Rate (%) <span class="text-danger">*</span>
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Yield rate for discounting cash flows"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="yield_rate" id="yield_rate" class="form-control" step="0.000001" min="0" max="100" value="{{ old('yield_rate') }}" placeholder="0.000000">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3" id="nav_fields" style="display: none;">
                                        <label class="form-label fw-semibold">
                                            NAV Price per Unit <span class="text-danger">*</span>
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Net Asset Value per unit"></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="fair_value_per_unit" id="fair_value_per_unit_nav" class="form-control" step="0.000001" min="0" value="{{ old('fair_value_per_unit') }}" placeholder="0.000000">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Level 3: Unobservable Inputs -->
                            <div id="level3_fields" class="valuation-level-fields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 mb-3" id="dcf_fields" style="display: none;">
                                        <label class="form-label fw-semibold">
                                            Discount Rate (%) <span class="text-danger">*</span>
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Discount rate for DCF calculation"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="discount_rate" id="discount_rate" class="form-control" step="0.000001" min="0" max="100" value="{{ old('discount_rate') }}" placeholder="0.000000">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mb-3" id="cash_flows_fields" style="display: none;">
                                        <label class="form-label fw-semibold">
                                            Cash Flows (JSON) <span class="text-danger">*</span>
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Expected cash flows in JSON format: [{'date': 'YYYY-MM-DD', 'amount': 1000}, ...]"></i>
                                        </label>
                                        <textarea name="cash_flows" id="cash_flows" class="form-control" rows="4" placeholder='[{"date": "2025-12-31", "amount": 1000}, {"date": "2026-12-31", "amount": 1000}]'>{{ old('cash_flows') }}</textarea>
                                        <small class="text-muted d-block mt-1">Enter cash flows as JSON array</small>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-semibold">
                                            Valuation Assumptions
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Document key assumptions used in Level 3 valuation"></i>
                                        </label>
                                        <textarea name="valuation_assumptions" id="valuation_assumptions" class="form-control" rows="3" placeholder="Document assumptions, methodologies, and key inputs...">{{ old('valuation_assumptions') }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Common Fields -->
                            <div class="row mt-3">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Units at Valuation Date
                                        <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Number of units held as of valuation date (defaults to investment units)"></i>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" name="units" id="units" class="form-control" step="0.000001" min="0" value="{{ old('units') }}" placeholder="Auto-filled from investment">
                                        <span class="input-group-text">units</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Total Fair Value
                                        <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Auto-calculated: Fair Value per Unit × Units"></i>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-success text-white fw-bold">TZS</span>
                                        <input type="text" id="total_fair_value" class="form-control bg-light fw-bold" readonly value="0.00">
                                    </div>
                                    <small class="text-success d-block mt-1">
                                        <i class="bx bx-calculator"></i> Auto-calculated
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Information Sidebar -->
                <div class="col-lg-4">
                    <div class="card sticky-top" style="top: 20px;">
                        <div class="card-header bg-info">
                            <h6 class="mb-0 text-white">
                                <i class="bx bx-info-circle me-1"></i>Valuation Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning mb-3">
                                <h6 class="alert-heading">
                                    <i class="bx bx-error-circle me-1"></i>Required Fields
                                </h6>
                                <ul class="mb-0 small">
                                    <li><span class="text-danger">*</span> Investment</li>
                                    <li><span class="text-danger">*</span> Valuation Date</li>
                                    <li><span class="text-danger">*</span> Valuation Level</li>
                                    <li><span class="text-danger">*</span> Valuation Method</li>
                                    <li><span class="text-danger">*</span> Fair Value per Unit</li>
                                </ul>
                            </div>

                            <div class="alert alert-info">
                                <h6 class="alert-heading">
                                    <i class="bx bx-check-circle me-1"></i>Fair Value Hierarchy (IFRS 13)
                                </h6>
                                <ul class="mb-0 small">
                                    <li><strong>Level 1:</strong> Quoted prices in active markets</li>
                                    <li><strong>Level 2:</strong> Observable inputs (yield curves, NAV)</li>
                                    <li><strong>Level 3:</strong> Unobservable inputs (DCF, models)</li>
                                </ul>
                            </div>

                            <div class="alert alert-warning">
                                <h6 class="alert-heading">
                                    <i class="bx bx-shield-alt-2 me-1"></i>Approval Required
                                </h6>
                                <p class="mb-0 small">Level 3 valuations require approval before posting to GL.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="card mt-3 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('investments.valuations.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i> Create Valuation
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

    const investmentSelect = document.getElementById('investment_id');
    const valuationLevelSelect = document.getElementById('valuation_level');
    const valuationMethodSelect = document.getElementById('valuation_method');
    const fairValuePerUnit = document.getElementById('fair_value_per_unit');
    const fairValuePerUnitNav = document.getElementById('fair_value_per_unit_nav');
    const unitsInput = document.getElementById('units');
    const totalFairValue = document.getElementById('total_fair_value');

    // Handle investment selection - decode hash ID
    investmentSelect.addEventListener('change', function() {
        const hashId = this.value;
        if (hashId) {
            // Store hash ID for form submission, but decode for fetching investment details
            document.getElementById('investment_id_decoded').value = hashId;
            
            // Fetch investment details to pre-fill units
            fetch(`/investments/trades/get-investment-details/${hashId}`)
                .then(response => response.json())
                .then(data => {
                    if (data && !unitsInput.value) {
                        // Pre-fill units if not already set
                        // Note: This uses the trade endpoint, we might need a dedicated endpoint
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    });

    // Handle valuation level change
    function toggleValuationLevelFields() {
        const level = valuationLevelSelect.value;
        const method = valuationMethodSelect.value;
        
        // Hide all level-specific fields
        document.querySelectorAll('.valuation-level-fields').forEach(field => {
            field.style.display = 'none';
        });
        
        // Show relevant fields based on level
        if (level === '1') {
            document.getElementById('level1_fields').style.display = 'block';
        } else if (level === '2') {
            document.getElementById('level2_fields').style.display = 'block';
            if (method === 'YIELD_CURVE') {
                document.getElementById('yield_curve_fields').style.display = 'block';
            } else if (method === 'NAV') {
                document.getElementById('nav_fields').style.display = 'block';
            }
        } else if (level === '3') {
            document.getElementById('level3_fields').style.display = 'block';
            if (method === 'DCF') {
                document.getElementById('dcf_fields').style.display = 'block';
                document.getElementById('cash_flows_fields').style.display = 'block';
            }
        }
    }

    // Handle valuation method change
    function toggleValuationMethodFields() {
        const level = valuationLevelSelect.value;
        const method = valuationMethodSelect.value;
        
        if (level === '2') {
            document.getElementById('yield_curve_fields').style.display = (method === 'YIELD_CURVE') ? 'block' : 'none';
            document.getElementById('nav_fields').style.display = (method === 'NAV') ? 'block' : 'none';
        } else if (level === '3') {
            document.getElementById('dcf_fields').style.display = (method === 'DCF') ? 'block' : 'none';
            document.getElementById('cash_flows_fields').style.display = (method === 'DCF') ? 'block' : 'none';
        }
    }

    valuationLevelSelect.addEventListener('change', function() {
        toggleValuationLevelFields();
        toggleValuationMethodFields();
    });

    valuationMethodSelect.addEventListener('change', function() {
        toggleValuationMethodFields();
    });

    // Calculate total fair value
    function calculateTotalFairValue() {
        const fvPerUnit = parseFloat(fairValuePerUnit?.value || fairValuePerUnitNav?.value || 0);
        const units = parseFloat(unitsInput.value || 0);
        const total = fvPerUnit * units;
        totalFairValue.value = total.toFixed(2);
    }

    if (fairValuePerUnit) fairValuePerUnit.addEventListener('input', calculateTotalFairValue);
    if (fairValuePerUnitNav) fairValuePerUnitNav.addEventListener('input', calculateTotalFairValue);
    if (unitsInput) unitsInput.addEventListener('input', calculateTotalFairValue);

    // Handle form submission - decode investment_id
    document.getElementById('valuation-form').addEventListener('submit', function(e) {
        const hashId = investmentSelect.value;
        if (hashId) {
            // Create hidden input with decoded ID
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'investment_id';
            hiddenInput.value = hashId; // Controller will decode this
            this.appendChild(hiddenInput);
        }
    });

    // Initial setup
    toggleValuationLevelFields();
    toggleValuationMethodFields();
});
</script>
@endpush

