@extends('layouts.main')

@section('title', 'Capture Investment Trade')

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
    .card-header.bg-secondary.bg-gradient {
        background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
    }
    .form-label .bx-info-circle {
        cursor: help;
    }
    .input-group-text {
        background-color: #f8f9fa;
        border-color: #ced4da;
        font-weight: 500;
    }
    .bg-light.form-control:read-only {
        background-color: #e9ecef !important;
        font-weight: 600;
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
            ['label' => 'Trades', 'url' => route('investments.trades.index'), 'icon' => 'bx bx-transfer'],
            ['label' => 'Capture Trade', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        
        <h6 class="mb-0 text-uppercase">CAPTURE INVESTMENT TRADE</h6>
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

        <form action="{{ route('investments.trades.store') }}" method="POST" id="trade-form">
            @csrf

            <div class="row">
                <!-- Main Form -->
                <div class="col-lg-8">
                    <!-- Basic Trade Information -->
                    <div class="card mb-3">
                        <div class="card-header bg-primary bg-gradient text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-info-circle me-2"></i>Basic Trade Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Investment <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Select the investment instrument for this trade. If the investment doesn't exist, create it first from Investment Master."></i>
                                    </label>
                                    <select name="investment_id" id="investment_id" class="form-select select2-single" required>
                                        <option value="">Select Investment</option>
                                        @foreach($investments as $inv)
                                        <option value="{{ $inv->id }}" {{ (old('investment_id') == $inv->id || ($investment && $investment->id == $inv->id)) ? 'selected' : '' }}>
                                            {{ $inv->instrument_code }} - {{ $inv->issuer }} ({{ $inv->instrument_type }})
                                        </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">
                                        <i class="bx bx-link-external"></i> 
                                        <a href="{{ route('investments.master.index') }}" target="_blank">Create new investment</a>
                                    </small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Trade Type <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="PURCHASE: Buying investment | SALE: Selling investment | MATURITY: Investment maturing | COUPON: Interest/coupon payment"></i>
                                    </label>
                                    <select name="trade_type" id="trade_type" class="form-select select2-single" required>
                                        <option value="">Select Type</option>
                                        @foreach($tradeTypes as $type)
                                        <option value="{{ $type }}" {{ old('trade_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">Type of transaction being recorded</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trade Dates -->
                    <div class="card mb-3">
                        <div class="card-header bg-info bg-gradient text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-calendar me-2"></i>Trade Dates
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Trade Date <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="The date when the trade was executed or agreed upon. This is the transaction date for accounting purposes."></i>
                                    </label>
                                    <input type="date" name="trade_date" id="trade_date" class="form-control" value="{{ old('trade_date', date('Y-m-d')) }}" required>
                                    <small class="text-muted d-block mt-1">Date when the trade was executed</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">
                                        Settlement Date <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="The date when cash and securities are exchanged. Settlement date must be equal to or after the trade date. Typically T+1, T+2, or T+3 depending on the instrument."></i>
                                    </label>
                                    <input type="date" name="settlement_date" id="settlement_date" class="form-control" value="{{ old('settlement_date', date('Y-m-d')) }}" required>
                                    <small class="text-muted d-block mt-1">Date when payment and delivery occur (must be ≥ Trade Date)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trade Pricing & Amounts -->
                    <div class="card mb-3">
                        <div class="card-header bg-success bg-gradient text-white">
                            <h5 class="mb-0">
                                <i class="bx bx-dollar me-2"></i>Trade Pricing & Amounts
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">
                                        Trade Price <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Price per unit of the investment instrument. For bonds, this is typically expressed as a percentage (e.g., 100.50 for 100.50%). For equities, this is the price per share."></i>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" name="trade_price" id="trade_price" class="form-control" step="0.000001" min="0" value="{{ old('trade_price') }}" required placeholder="0.000000">
                                        <span class="input-group-text">per unit</span>
                                    </div>
                                    <small class="text-muted d-block mt-1">Price per unit (supports up to 6 decimal places)</small>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">
                                        Trade Units <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="The quantity or number of units being traded. For bonds, this is the face value. For equities, this is the number of shares."></i>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" name="trade_units" id="trade_units" class="form-control" step="0.000001" min="0.000001" value="{{ old('trade_units') }}" required placeholder="0.000000">
                                        <span class="input-group-text">units</span>
                                    </div>
                                    <small class="text-muted d-block mt-1">Quantity or face value (supports up to 6 decimal places)</small>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">
                                        Gross Amount
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Automatically calculated as Trade Price × Trade Units. This is the total value before fees and taxes."></i>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">TZS</span>
                                        <input type="number" name="gross_amount" id="gross_amount" class="form-control bg-light" step="0.01" min="0" value="{{ old('gross_amount') }}" readonly>
                                    </div>
                                    <small class="text-success d-block mt-1">
                                        <i class="bx bx-calculator"></i> Auto-calculated: Price × Units
                                    </small>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">
                                        Fees
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Brokerage fees, transaction fees, or other charges associated with the trade. These may be capitalized (added to cost) or expensed depending on the accounting classification."></i>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">TZS</span>
                                        <input type="number" name="fees" id="fees" class="form-control" step="0.01" min="0" value="{{ old('fees', 0) }}" placeholder="0.00">
                                    </div>
                                    <small class="text-muted d-block mt-1">Brokerage, transaction, or other fees</small>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">
                                        Tax Withheld
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="Any tax withheld at source (e.g., withholding tax on interest payments). This reduces the net amount received."></i>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">TZS</span>
                                        <input type="number" name="tax_withheld" id="tax_withheld" class="form-control" step="0.01" min="0" value="{{ old('tax_withheld', 0) }}" placeholder="0.00">
                                    </div>
                                    <small class="text-muted d-block mt-1">Withholding tax or other taxes deducted</small>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">
                                        Net Amount
                                        <i class="bx bx-info-circle text-muted ms-1" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top"
                                           title="The final amount after deducting fees and taxes. This is the actual cash amount that will be paid or received."></i>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-primary text-white fw-bold">TZS</span>
                                        <input type="text" id="net_amount" class="form-control bg-light fw-bold" readonly value="0.00">
                                    </div>
                                    <small class="text-primary d-block mt-1 fw-semibold">
                                        <i class="bx bx-check-circle"></i> Net: Gross - Fees - Tax
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Category-Specific Fields (Dynamic based on instrument type) -->
                    <div id="category-specific-fields" style="display: none;">
                        <!-- T-Bonds & Corporate Bonds Fields -->
                        <div class="card mb-3 category-section" data-category="T_BOND,CORP_BOND" style="display: none;">
                            <div class="card-header bg-primary bg-gradient text-white">
                                <h5 class="mb-0">
                                    <i class="bx bx-certificate me-2"></i>Bond-Specific Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Coupon Rate (%)
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Annual coupon rate as a percentage"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="coupon_rate" id="coupon_rate" class="form-control" step="0.000001" min="0" max="100" value="{{ old('coupon_rate') }}" placeholder="0.000000">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Coupon Frequency
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Frequency of coupon payments"></i>
                                        </label>
                                        <select name="coupon_frequency" id="coupon_frequency" class="form-select select2-single">
                                            <option value="">Select Frequency</option>
                                            <option value="ANNUAL" {{ old('coupon_frequency') == 'ANNUAL' ? 'selected' : '' }}>Annual</option>
                                            <option value="SEMI_ANNUAL" {{ old('coupon_frequency') == 'SEMI_ANNUAL' ? 'selected' : '' }}>Semi-Annual</option>
                                            <option value="QUARTERLY" {{ old('coupon_frequency') == 'QUARTERLY' ? 'selected' : '' }}>Quarterly</option>
                                            <option value="MONTHLY" {{ old('coupon_frequency') == 'MONTHLY' ? 'selected' : '' }}>Monthly</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Yield to Maturity (%)
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Bid or purchase yield"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="yield_to_maturity" id="yield_to_maturity" class="form-control" step="0.000001" min="0" value="{{ old('yield_to_maturity') }}" placeholder="0.000000">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Accrued Coupon at Purchase
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Needed for dirty price calculation"></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="accrued_coupon_at_purchase" id="accrued_coupon_at_purchase" class="form-control" step="0.01" min="0" value="{{ old('accrued_coupon_at_purchase') }}" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Premium/Discount
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Purchase difference vs par value"></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="premium_discount" id="premium_discount" class="form-control" step="0.01" value="{{ old('premium_discount') }}" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Fair Value Source
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="BOT price, yield curve, or internal model"></i>
                                        </label>
                                        <input type="text" name="fair_value_source" id="fair_value_source" class="form-control" value="{{ old('fair_value_source') }}" placeholder="e.g., BOT price">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Fair Value
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Market value for FV assets"></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="fair_value" id="fair_value" class="form-control" step="0.01" min="0" value="{{ old('fair_value') }}" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Benchmark
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Yield curve used"></i>
                                        </label>
                                        <input type="text" name="benchmark" id="benchmark" class="form-control" value="{{ old('benchmark') }}" placeholder="e.g., T-Bond yield curve">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Credit Risk Grade
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Internal or external rating"></i>
                                        </label>
                                        <input type="text" name="credit_risk_grade" id="credit_risk_grade" class="form-control" value="{{ old('credit_risk_grade') }}" placeholder="e.g., AAA, AA+, etc.">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Counterparty
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Broker or BOT"></i>
                                        </label>
                                        <input type="text" name="counterparty" id="counterparty" class="form-control" value="{{ old('counterparty') }}" placeholder="e.g., Broker name">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Tax Withholding Rate (%)
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="For coupon WHT"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="tax_withholding_rate" id="tax_withholding_rate" class="form-control" step="0.000001" min="0" max="100" value="{{ old('tax_withholding_rate') }}" placeholder="0.000000">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- BOT Required Fields for T-Bonds -->
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h6 class="text-primary fw-bold mb-3">
                                            <i class="bx bx-check-circle me-2"></i>BOT Required Fields
                                        </h6>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-semibold">
                                            Auction No. <span class="text-danger">*</span>
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="BOT auction number for this bond issue"></i>
                                        </label>
                                        <input type="text" name="auction_no" id="auction_no" class="form-control" value="{{ old('auction_no') }}" placeholder="e.g., A001/2025" required>
                                        <small class="text-muted d-block mt-1">BOT auction reference number</small>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-semibold">
                                            Auction Date <span class="text-danger">*</span>
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Date when the BOT auction was held"></i>
                                        </label>
                                        <input type="date" name="auction_date" id="auction_date" class="form-control" value="{{ old('auction_date') }}" required>
                                        <small class="text-muted d-block mt-1">Date of BOT auction</small>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-semibold">
                                            Bond Type <span class="text-danger">*</span>
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Maturity period of the bond (2-years, 5-years, 7-years, 10-years, 15-years, 20-years, 25-years, etc.)"></i>
                                        </label>
                                        <select name="bond_type" id="bond_type" class="form-select select2-single" required>
                                            <option value="">Select Bond Type</option>
                                            <option value="2-years" {{ old('bond_type') == '2-years' ? 'selected' : '' }}>2 Years</option>
                                            <option value="5-years" {{ old('bond_type') == '5-years' ? 'selected' : '' }}>5 Years</option>
                                            <option value="7-years" {{ old('bond_type') == '7-years' ? 'selected' : '' }}>7 Years</option>
                                            <option value="10-years" {{ old('bond_type') == '10-years' ? 'selected' : '' }}>10 Years</option>
                                            <option value="15-years" {{ old('bond_type') == '15-years' ? 'selected' : '' }}>15 Years</option>
                                            <option value="20-years" {{ old('bond_type') == '20-years' ? 'selected' : '' }}>20 Years</option>
                                            <option value="25-years" {{ old('bond_type') == '25-years' ? 'selected' : '' }}>25 Years</option>
                                            <option value="OTHER" {{ old('bond_type') == 'OTHER' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        <div id="bond_type_other_container" style="display: {{ old('bond_type') == 'OTHER' ? 'block' : 'none' }};" class="mt-2">
                                            <input type="text" name="bond_type_other" id="bond_type_other" class="form-control" value="{{ old('bond_type_other') }}" placeholder="Enter custom bond type">
                                        </div>
                                        <small class="text-muted d-block mt-1">Maturity period of the bond</small>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-semibold">
                                            Bond Price <span class="text-danger">*</span>
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="BOT auction price per unit (may differ from trade price if purchased in secondary market)"></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="bond_price" id="bond_price" class="form-control" step="0.000001" min="0" value="{{ old('bond_price') }}" placeholder="0.000000" required>
                                        </div>
                                        <small class="text-muted d-block mt-1">BOT auction price per unit</small>
                                    </div>
                                </div>
                                
                                <!-- Corporate Bonds Additional Fields -->
                                <div class="row corporate-bond-only" style="display: none;">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Issuer Name
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Company issuing bond"></i>
                                        </label>
                                        <input type="text" name="issuer_name" id="issuer_name" class="form-control" value="{{ old('issuer_name') }}" placeholder="Company name">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Sector
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="For concentration risk"></i>
                                        </label>
                                        <input type="text" name="sector" id="sector" class="form-control" value="{{ old('sector') }}" placeholder="e.g., Banking, Manufacturing">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Credit Rating
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="External (Fitch/Moody's) or internal"></i>
                                        </label>
                                        <input type="text" name="credit_rating" id="credit_rating" class="form-control" value="{{ old('credit_rating') }}" placeholder="e.g., AAA, AA+">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Credit Spread (%)
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Used in valuation"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="credit_spread" id="credit_spread" class="form-control" step="0.000001" min="0" value="{{ old('credit_spread') }}" placeholder="0.000000">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Fair Value Method
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Market price or DCF"></i>
                                        </label>
                                        <select name="fair_value_method" id="fair_value_method" class="form-select select2-single">
                                            <option value="">Select Method</option>
                                            <option value="MARKET_PRICE" {{ old('fair_value_method') == 'MARKET_PRICE' ? 'selected' : '' }}>Market Price</option>
                                            <option value="DCF" {{ old('fair_value_method') == 'DCF' ? 'selected' : '' }}>DCF (Discounted Cash Flow)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Counterparty Broker
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Who executed the trade"></i>
                                        </label>
                                        <input type="text" name="counterparty_broker" id="counterparty_broker" class="form-control" value="{{ old('counterparty_broker') }}" placeholder="Broker name">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-semibold">
                                            Impairment Override Reason
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="For audit purposes"></i>
                                        </label>
                                        <textarea name="impairment_override_reason" id="impairment_override_reason" class="form-control" rows="2" placeholder="Reason for impairment override (if applicable)">{{ old('impairment_override_reason') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- T-Bills Specific Fields -->
                        <div class="card mb-3 category-section" data-category="T_BILL" style="display: none;">
                            <div class="card-header bg-info bg-gradient text-white">
                                <h5 class="mb-0">
                                    <i class="bx bx-file-blank me-2"></i>T-Bill Specific Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Discount Rate (%)
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Auction discount rate"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="discount_rate" id="discount_rate" class="form-control" step="0.000001" min="0" max="100" value="{{ old('discount_rate') }}" placeholder="0.000000">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Yield Rate (%)
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Effective yield"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="yield_rate" id="yield_rate" class="form-control" step="0.000001" min="0" value="{{ old('yield_rate') }}" placeholder="0.000000">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Maturity Days
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="91, 182, or 364 days"></i>
                                        </label>
                                        <select name="maturity_days" id="maturity_days" class="form-select select2-single">
                                            <option value="">Select Days</option>
                                            <option value="91" {{ old('maturity_days') == '91' ? 'selected' : '' }}>91 Days</option>
                                            <option value="182" {{ old('maturity_days') == '182' ? 'selected' : '' }}>182 Days</option>
                                            <option value="364" {{ old('maturity_days') == '364' ? 'selected' : '' }}>364 Days</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Fair Value Source
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="BOT discount curve"></i>
                                        </label>
                                        <input type="text" name="fair_value_source" id="fair_value_source_tbill" class="form-control" value="{{ old('fair_value_source') }}" placeholder="e.g., BOT discount curve">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Fair Value
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="For FVPL classification"></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="fair_value" id="fair_value_tbill" class="form-control" step="0.01" min="0" value="{{ old('fair_value') }}" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Benchmark
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Curve used"></i>
                                        </label>
                                        <input type="text" name="benchmark" id="benchmark_tbill" class="form-control" value="{{ old('benchmark') }}" placeholder="e.g., T-Bill yield curve">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Counterparty
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="BOT"></i>
                                        </label>
                                        <input type="text" name="counterparty" id="counterparty_tbill" class="form-control" value="{{ old('counterparty') }}" placeholder="BOT">
                                    </div>
                                </div>
                                
                                <!-- BOT Required Fields for T-Bills -->
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h6 class="text-primary fw-bold mb-3">
                                            <i class="bx bx-check-circle me-2"></i>BOT Required Fields
                                        </h6>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Auction No. <span class="text-danger">*</span>
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="BOT auction number for this T-Bill issue"></i>
                                        </label>
                                        <input type="text" name="auction_no" id="auction_no_tbill" class="form-control" value="{{ old('auction_no') }}" placeholder="e.g., A001/2025" required>
                                        <small class="text-muted d-block mt-1">BOT auction reference number</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Auction Date <span class="text-danger">*</span>
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Date when the BOT auction was held"></i>
                                        </label>
                                        <input type="date" name="auction_date" id="auction_date_tbill" class="form-control" value="{{ old('auction_date') }}" required>
                                        <small class="text-muted d-block mt-1">Date of BOT auction</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            T-Bill Type <span class="text-danger">*</span>
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Maturity period of the T-Bill (35-days, 91-days, 182-days, 364-days, etc.)"></i>
                                        </label>
                                        <select name="tbill_type" id="tbill_type" class="form-select select2-single" required>
                                            <option value="">Select T-Bill Type</option>
                                            <option value="35-days" {{ old('tbill_type') == '35-days' ? 'selected' : '' }}>35 Days</option>
                                            <option value="91-days" {{ old('tbill_type') == '91-days' ? 'selected' : '' }}>91 Days</option>
                                            <option value="182-days" {{ old('tbill_type') == '182-days' ? 'selected' : '' }}>182 Days</option>
                                            <option value="364-days" {{ old('tbill_type') == '364-days' ? 'selected' : '' }}>364 Days</option>
                                            <option value="OTHER" {{ old('tbill_type') == 'OTHER' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        <div id="tbill_type_other_container" style="display: {{ old('tbill_type') == 'OTHER' ? 'block' : 'none' }};" class="mt-2">
                                            <input type="text" name="tbill_type_other" id="tbill_type_other" class="form-control" value="{{ old('tbill_type_other') }}" placeholder="Enter custom T-Bill type">
                                        </div>
                                        <small class="text-muted d-block mt-1">Maturity period of the T-Bill</small>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            T-Bill Price <span class="text-danger">*</span>
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="BOT auction price per unit (may differ from trade price if purchased in secondary market)"></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="tbill_price" id="tbill_price" class="form-control" step="0.000001" min="0" value="{{ old('tbill_price') }}" placeholder="0.000000" required>
                                        </div>
                                        <small class="text-muted d-block mt-1">BOT auction price per unit</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fixed Deposits Specific Fields -->
                        <div class="card mb-3 category-section" data-category="FIXED_DEPOSIT" style="display: none;">
                            <div class="card-header bg-success bg-gradient text-white">
                                <h5 class="mb-0">
                                    <i class="bx bx-building me-2"></i>Fixed Deposit Specific Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            FD Reference No
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Bank deposit certificate number"></i>
                                        </label>
                                        <input type="text" name="fd_reference_no" id="fd_reference_no" class="form-control" value="{{ old('fd_reference_no') }}" placeholder="e.g., FD-2024-001">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Bank Name
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Counterparty bank"></i>
                                        </label>
                                        <input type="text" name="bank_name" id="bank_name" class="form-control" value="{{ old('bank_name') }}" placeholder="Bank name">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Branch
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Bank branch (optional)"></i>
                                        </label>
                                        <input type="text" name="branch" id="branch" class="form-control" value="{{ old('branch') }}" placeholder="Branch name">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Interest Rate (%)
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Annual interest rate"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="coupon_rate" id="coupon_rate_fd" class="form-control" step="0.000001" min="0" max="100" value="{{ old('coupon_rate') }}" placeholder="0.000000">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Interest Computation Method
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Simple or compound interest"></i>
                                        </label>
                                        <select name="interest_computation_method" id="interest_computation_method" class="form-select select2-single">
                                            <option value="">Select Method</option>
                                            <option value="SIMPLE" {{ old('interest_computation_method') == 'SIMPLE' ? 'selected' : '' }}>Simple</option>
                                            <option value="COMPOUND" {{ old('interest_computation_method') == 'COMPOUND' ? 'selected' : '' }}>Compound</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Payout Frequency
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Monthly, quarterly, or end maturity"></i>
                                        </label>
                                        <select name="payout_frequency" id="payout_frequency" class="form-select select2-single">
                                            <option value="">Select Frequency</option>
                                            <option value="MONTHLY" {{ old('payout_frequency') == 'MONTHLY' ? 'selected' : '' }}>Monthly</option>
                                            <option value="QUARTERLY" {{ old('payout_frequency') == 'QUARTERLY' ? 'selected' : '' }}>Quarterly</option>
                                            <option value="END_MATURITY" {{ old('payout_frequency') == 'END_MATURITY' ? 'selected' : '' }}>End Maturity</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Expected Interest
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Total expected interest"></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="expected_interest" id="expected_interest" class="form-control" step="0.01" min="0" value="{{ old('expected_interest') }}" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Tax Withholding Rate (%)
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="WHT on interest"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="tax_withholding_rate" id="tax_withholding_rate_fd" class="form-control" step="0.000001" min="0" max="100" value="{{ old('tax_withholding_rate') }}" placeholder="0.000000">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Credit Risk Rating
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Rating of bank"></i>
                                        </label>
                                        <input type="text" name="credit_risk_grade" id="credit_risk_grade_fd" class="form-control" value="{{ old('credit_risk_grade') }}" placeholder="e.g., AAA">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" name="collateral_flag" id="collateral_flag" value="1" {{ old('collateral_flag') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="collateral_flag">
                                                Collateralized
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" name="rollover_option" id="rollover_option" value="1" {{ old('rollover_option') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="rollover_option">
                                                Rollover Option
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">
                                            Premature Withdrawal Penalty
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="If applicable"></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="premature_withdrawal_penalty" id="premature_withdrawal_penalty" class="form-control" step="0.01" min="0" value="{{ old('premature_withdrawal_penalty') }}" placeholder="0.00">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Equity Specific Fields -->
                        <div class="card mb-3 category-section" data-category="EQUITY" style="display: none;">
                            <div class="card-header bg-warning bg-gradient text-white">
                                <h5 class="mb-0">
                                    <i class="bx bx-line-chart me-2"></i>Equity Specific Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Ticker/Symbol
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="DSE or foreign exchange ticker"></i>
                                        </label>
                                        <input type="text" name="ticker_symbol" id="ticker_symbol" class="form-control" value="{{ old('ticker_symbol') }}" placeholder="e.g., TBL, NMB">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Company Name
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Issuer company"></i>
                                        </label>
                                        <input type="text" name="company_name" id="company_name" class="form-control" value="{{ old('company_name') }}" placeholder="Company name">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Number of Shares
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Quantity held"></i>
                                        </label>
                                        <input type="number" name="number_of_shares" id="number_of_shares" class="form-control" step="0.000001" min="0" value="{{ old('number_of_shares') }}" placeholder="0.000000">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Purchase Price per Share
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Per share cost"></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="purchase_price_per_share" id="purchase_price_per_share" class="form-control" step="0.000001" min="0" value="{{ old('purchase_price_per_share') }}" placeholder="0.000000">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Fair Value
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Market price × quantity"></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="fair_value" id="fair_value_equity" class="form-control" step="0.01" min="0" value="{{ old('fair_value') }}" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Fair Value Source
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="DSE feed or manual"></i>
                                        </label>
                                        <input type="text" name="fair_value_source" id="fair_value_source_equity" class="form-control" value="{{ old('fair_value_source') }}" placeholder="e.g., DSE feed">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Dividend Rate (%)
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Expected/declared dividend"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="dividend_rate" id="dividend_rate" class="form-control" step="0.000001" min="0" max="100" value="{{ old('dividend_rate') }}" placeholder="0.000000">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Dividend Tax Rate (%)
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="WHT on dividends"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="dividend_tax_rate" id="dividend_tax_rate" class="form-control" step="0.000001" min="0" max="100" value="{{ old('dividend_tax_rate') }}" placeholder="0.000000">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Sector
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="For concentration reporting"></i>
                                        </label>
                                        <input type="text" name="sector" id="sector_equity" class="form-control" value="{{ old('sector') }}" placeholder="e.g., Banking, Manufacturing">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Country
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Country of issuer"></i>
                                        </label>
                                        <input type="text" name="country" id="country" class="form-control" value="{{ old('country', 'Tanzania') }}" placeholder="Tanzania">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Exchange Rate
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="If foreign currency"></i>
                                        </label>
                                        <input type="number" name="exchange_rate" id="exchange_rate" class="form-control" step="0.000001" min="0" value="{{ old('exchange_rate', 1) }}" placeholder="1.000000">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" name="impairment_indicator" id="impairment_indicator" value="1" {{ old('impairment_indicator') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="impairment_indicator">
                                                Impairment Indicator (Unquoted)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Money Market Funds Specific Fields -->
                        <div class="card mb-3 category-section" data-category="MMF" style="display: none;">
                            <div class="card-header bg-purple bg-gradient text-white">
                                <h5 class="mb-0">
                                    <i class="bx bx-pie-chart me-2"></i>Money Market Fund Specific Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Fund Name
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Example: UTT Liquid Fund"></i>
                                        </label>
                                        <input type="text" name="fund_name" id="fund_name" class="form-control" value="{{ old('fund_name') }}" placeholder="e.g., UTT Liquid Fund">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Fund Manager
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="UTT / Old Mutual / NMB"></i>
                                        </label>
                                        <input type="text" name="fund_manager" id="fund_manager" class="form-control" value="{{ old('fund_manager') }}" placeholder="Fund manager name">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Units Purchased
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Quantity"></i>
                                        </label>
                                        <input type="number" name="units_purchased" id="units_purchased" class="form-control" step="0.000001" min="0" value="{{ old('units_purchased') }}" placeholder="0.000000">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Unit Price (at Purchase)
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Price per unit at purchase"></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="unit_price" id="unit_price" class="form-control" step="0.000001" min="0" value="{{ old('unit_price') }}" placeholder="0.000000">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            NAV Price (Current)
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Daily NAV"></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="nav_price" id="nav_price" class="form-control" step="0.000001" min="0" value="{{ old('nav_price') }}" placeholder="0.000000">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Fair Value
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Units × NAV"></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="fair_value" id="fair_value_mmf" class="form-control" step="0.01" min="0" value="{{ old('fair_value') }}" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Distribution Rate (%)
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Income distribution"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="distribution_rate" id="distribution_rate" class="form-control" step="0.000001" min="0" max="100" value="{{ old('distribution_rate') }}" placeholder="0.000000">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Risk Class
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Low/Medium"></i>
                                        </label>
                                        <select name="risk_class" id="risk_class" class="form-select select2-single">
                                            <option value="">Select Risk Class</option>
                                            <option value="LOW" {{ old('risk_class') == 'LOW' ? 'selected' : '' }}>Low</option>
                                            <option value="MEDIUM" {{ old('risk_class') == 'MEDIUM' ? 'selected' : '' }}>Medium</option>
                                            <option value="HIGH" {{ old('risk_class') == 'HIGH' ? 'selected' : '' }}>High</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Commercial Papers Specific Fields -->
                        <div class="card mb-3 category-section" data-category="COMMERCIAL_PAPER" style="display: none;">
                            <div class="card-header bg-danger bg-gradient text-white">
                                <h5 class="mb-0">
                                    <i class="bx bx-file me-2"></i>Commercial Paper Specific Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Issuer
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Name of issuer"></i>
                                        </label>
                                        <input type="text" name="issuer" id="issuer_cp" class="form-control" value="{{ old('issuer') }}" placeholder="Issuer name">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Discount Rate (%)
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="CP discount rate"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="discount_rate" id="discount_rate_cp" class="form-control" step="0.000001" min="0" max="100" value="{{ old('discount_rate') }}" placeholder="0.000000">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Yield (%)
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="For EIR calculation"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="yield_rate" id="yield_rate_cp" class="form-control" step="0.000001" min="0" value="{{ old('yield_rate') }}" placeholder="0.000000">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Credit Rating
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Rating of issuer"></i>
                                        </label>
                                        <input type="text" name="credit_rating" id="credit_rating_cp" class="form-control" value="{{ old('credit_rating') }}" placeholder="e.g., AAA">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Fair Value
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="If FVPL"></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="fair_value" id="fair_value_cp" class="form-control" step="0.01" min="0" value="{{ old('fair_value') }}" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">
                                            Counterparty
                                            <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Broker"></i>
                                        </label>
                                        <input type="text" name="counterparty" id="counterparty_cp" class="form-control" value="{{ old('counterparty') }}" placeholder="Broker name">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- IFRS 9 ECL Fields (for applicable categories) -->
                        <div class="card mb-3 category-section ecl-section" data-category="T_BOND,CORP_BOND,T_BILL,FIXED_DEPOSIT,COMMERCIAL_PAPER" style="display: none;">
                            <div class="card-header bg-danger bg-gradient text-white">
                                <h5 class="mb-0">
                                    <i class="bx bx-shield me-2"></i>IFRS 9 Expected Credit Loss (ECL) Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-3">
                                    <i class="bx bx-info-circle me-2"></i>
                                    <strong>Note:</strong> ECL fields are required for IFRS 9 compliance. These fields are used to calculate Expected Credit Loss for financial instruments. Equity and Money Market Funds are not ECL-scoped.
                                </div>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-semibold">
                                            Stage <span class="text-danger">*</span>
                                            <i class="bx bx-info-circle text-muted ms-1" 
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="top"
                                               title="IFRS 9 Stage: 1 = Performing, 2 = Underperforming, 3 = Non-performing/Impaired"></i>
                                        </label>
                                        <select name="stage" id="stage" class="form-select select2-single" required>
                                            <option value="">Select Stage</option>
                                            <option value="1" {{ old('stage', 1) == '1' ? 'selected' : '' }}>Stage 1 - Performing</option>
                                            <option value="2" {{ old('stage') == '2' ? 'selected' : '' }}>Stage 2 - Underperforming</option>
                                            <option value="3" {{ old('stage') == '3' ? 'selected' : '' }}>Stage 3 - Non-performing/Impaired</option>
                                        </select>
                                        <small class="text-muted d-block mt-1">IFRS 9 impairment stage</small>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-semibold">
                                            PD - Probability of Default (%)
                                            <i class="bx bx-info-circle text-muted ms-1" 
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="top"
                                               title="Probability of default within 12 months (Stage 1) or lifetime (Stage 2/3)"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="pd" id="pd" class="form-control" step="0.000001" min="0" max="100" value="{{ old('pd') }}" placeholder="0.000000">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Probability of default</small>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-semibold">
                                            LGD - Loss Given Default (%)
                                            <i class="bx bx-info-circle text-muted ms-1" 
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="top"
                                               title="Percentage of exposure expected to be lost if default occurs"></i>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="lgd" id="lgd" class="form-control" step="0.000001" min="0" max="100" value="{{ old('lgd') }}" placeholder="0.000000">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Loss given default</small>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-semibold">
                                            EAD - Exposure At Default
                                            <i class="bx bx-info-circle text-muted ms-1" 
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="top"
                                               title="Expected exposure at the time of default (typically carrying amount)"></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="ead" id="ead" class="form-control" step="0.01" min="0" value="{{ old('ead') }}" placeholder="0.00">
                                        </div>
                                        <small class="text-muted d-block mt-1">Exposure at default</small>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-semibold">
                                            ECL Amount (Calculated)
                                            <i class="bx bx-info-circle text-muted ms-1" 
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="top"
                                               title="Automatically calculated as: ECL = PD × LGD × EAD. Can be manually overridden if needed."></i>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-primary text-white fw-bold">TZS</span>
                                            <input type="number" name="ecl_amount" id="ecl_amount" class="form-control bg-light" step="0.01" min="0" value="{{ old('ecl_amount') }}" placeholder="0.00" readonly>
                                        </div>
                                        <small class="text-primary d-block mt-1 fw-semibold">
                                            <i class="bx bx-calculator"></i> Auto-calculated: ECL = (PD × LGD × EAD) / 10000
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information (Common to all) -->
                        <div class="card mb-3">
                            <div class="card-header bg-secondary bg-gradient text-white">
                                <h5 class="mb-0">
                                    <i class="bx bx-file me-2"></i>Additional Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-semibold">
                                            Bank Reference
                                            <i class="bx bx-info-circle text-muted ms-1" 
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="top"
                                               title="Bank transaction reference number, payment confirmation number, or any reference that links this trade to the bank payment. Useful for reconciliation."></i>
                                        </label>
                                        <input type="text" name="bank_ref" id="bank_ref" class="form-control" value="{{ old('bank_ref') }}" placeholder="e.g., TXN-2024-001234 or Payment Confirmation #">
                                        <small class="text-muted d-block mt-1">Bank transaction reference or payment confirmation number</small>
                                    </div>
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
                                <i class="bx bx-info-circle me-2"></i>Trade Capture Guide
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-3">
                                <h6 class="alert-heading">
                                    <i class="bx bx-bulb me-1"></i>Quick Tips
                                </h6>
                                <ul class="mb-0 small">
                                    <li>Select the investment before entering trade details</li>
                                    <li>Trade date is when the transaction was agreed</li>
                                    <li>Settlement date is when payment occurs</li>
                                    <li>Gross amount is auto-calculated</li>
                                    <li>Fees may be capitalized or expensed</li>
                                </ul>
                            </div>

                            <div class="alert alert-warning mb-3">
                                <h6 class="alert-heading">
                                    <i class="bx bx-error-circle me-1"></i>Required Fields
                                </h6>
                                <ul class="mb-0 small">
                                    <li><span class="text-danger">*</span> Investment</li>
                                    <li><span class="text-danger">*</span> Trade Type</li>
                                    <li><span class="text-danger">*</span> Trade Date</li>
                                    <li><span class="text-danger">*</span> Settlement Date</li>
                                    <li><span class="text-danger">*</span> Trade Price</li>
                                    <li><span class="text-danger">*</span> Trade Units</li>
                                </ul>
                            </div>

                            <div class="alert alert-success">
                                <h6 class="alert-heading">
                                    <i class="bx bx-check-circle me-1"></i>Trade Types
                                </h6>
                                <ul class="mb-0 small">
                                    <li><strong>PURCHASE:</strong> Buying investment</li>
                                    <li><strong>SALE:</strong> Selling investment</li>
                                    <li><strong>MATURITY:</strong> Investment maturing</li>
                                    <li><strong>COUPON:</strong> Interest payment</li>
                                </ul>
                            </div>

                            <div class="mt-3">
                                <h6 class="text-muted">Calculation Formula</h6>
                                <div class="bg-light p-2 rounded small">
                                    <strong>Gross Amount</strong> = Price × Units<br>
                                    <strong>Net Amount</strong> = Gross - Fees - Tax
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GL Posting Section -->
            <div class="card mt-3">
                <div class="card-header bg-warning bg-gradient text-dark">
                    <h5 class="mb-0">
                        <i class="bx bx-book me-2"></i>General Ledger Posting (Optional)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="post_to_gl" id="post_to_gl" value="1" {{ old('post_to_gl') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="post_to_gl">
                                    Post to General Ledger immediately
                                    <i class="bx bx-info-circle text-muted ms-1" 
                                       data-bs-toggle="tooltip" 
                                       data-bs-placement="top"
                                       title="If enabled, a journal entry will be automatically created and posted to the General Ledger after trade creation. The journal will go through the approval workflow."></i>
                                </label>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="bx bx-info-circle"></i> 
                                When enabled, journal entry will be created with:
                                <ul class="small mt-1 mb-0">
                                    <li>Debit: Investment Asset Account</li>
                                    <li>Credit: Bank Account (net amount)</li>
                                    <li>Debit: Fees Expense (if not capitalized)</li>
                                    <li>Credit: Tax Withheld Payable (if applicable)</li>
                                </ul>
                            </small>
                        </div>

                        <div class="col-md-6 mb-3" id="bank_account_field" style="display: none;">
                            <label class="form-label fw-semibold">
                                Bank Account for Payment
                                <span class="text-danger">*</span>
                                <i class="bx bx-info-circle text-muted ms-1" 
                                   data-bs-toggle="tooltip" 
                                   data-bs-placement="top"
                                   title="Select the bank account from which payment will be made (for purchases) or to which payment will be received (for sales). This account will be credited/debited in the journal entry."></i>
                            </label>
                            <select name="bank_account_id" id="bank_account_id" class="form-select select2-single">
                                <option value="">Select Bank Account</option>
                                @foreach($bankAccounts as $bankAccount)
                                <option value="{{ $bankAccount->id }}" {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                    {{ $bankAccount->name }}
                                    @if($bankAccount->chartAccount)
                                        - {{ $bankAccount->chartAccount->account_name }}
                                    @endif
                                </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Bank account for payment transaction</small>
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
                                All required fields must be completed before saving
                            </small>
                        </div>
                        <div class="btn-group">
                            <a href="{{ route('investments.trades.index') }}" class="btn btn-secondary">
                                <i class="bx bx-x"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save"></i> Save Trade
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

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

    const tradePrice = document.getElementById('trade_price');
    const tradeUnits = document.getElementById('trade_units');
    const grossAmount = document.getElementById('gross_amount');
    const fees = document.getElementById('fees');
    const taxWithheld = document.getElementById('tax_withheld');
    const netAmount = document.getElementById('net_amount');
    const postToGl = document.getElementById('post_to_gl');
    const bankAccountField = document.getElementById('bank_account_field');
    const investmentSelect = document.getElementById('investment_id');
    const categoryFieldsContainer = document.getElementById('category-specific-fields');
    const investmentsData = @json($investmentsData ?? []);

    // Function to show/hide category-specific fields
    function toggleCategoryFields(instrumentType) {
        // Hide all category sections first
        document.querySelectorAll('.category-section').forEach(section => {
            section.style.display = 'none';
        });

        // Show category-specific fields container
        if (instrumentType) {
            categoryFieldsContainer.style.display = 'block';
        } else {
            categoryFieldsContainer.style.display = 'none';
            return;
        }

        // Map instrument types to category sections
        const categoryMap = {
            'T_BOND': 'T_BOND,CORP_BOND',
            'CORP_BOND': 'T_BOND,CORP_BOND',
            'T_BILL': 'T_BILL',
            'FIXED_DEPOSIT': 'FIXED_DEPOSIT',
            'EQUITY': 'EQUITY',
            'MMF': 'MMF',
            'COMMERCIAL_PAPER': 'COMMERCIAL_PAPER',
            'OTHER': '' // No specific fields for OTHER
        };

        // Show relevant section based on instrument type
        const sections = document.querySelectorAll('.category-section');
        sections.forEach(section => {
            const categories = section.getAttribute('data-category').split(',');
            if (categories.includes(instrumentType)) {
                section.style.display = 'block';
                
                // Special handling for Corporate Bonds
                if (instrumentType === 'CORP_BOND') {
                    const corporateFields = section.querySelectorAll('.corporate-bond-only');
                    corporateFields.forEach(field => {
                        field.style.display = 'block';
                    });
                } else {
                    const corporateFields = section.querySelectorAll('.corporate-bond-only');
                    corporateFields.forEach(field => {
                        field.style.display = 'none';
                    });
                }
            }
        });

        // Handle BOT required fields - required for T_BOND and T_BILL
        const botBondFields = ['auction_no', 'auction_date', 'bond_type', 'bond_price'];
        const botTbillFields = ['auction_no_tbill', 'auction_date_tbill', 'tbill_price'];
        
        // T-Bond fields
        botBondFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                if (instrumentType === 'T_BOND') {
                    field.setAttribute('required', 'required');
                } else {
                    field.removeAttribute('required');
                }
            }
        });
        
        // T-Bill fields
        botTbillFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                if (instrumentType === 'T_BILL') {
                    field.setAttribute('required', 'required');
                } else {
                    field.removeAttribute('required');
                }
            }
        });
        
        // T-Bill Type field
        const tbillTypeField = document.getElementById('tbill_type');
        if (tbillTypeField) {
            if (instrumentType === 'T_BILL') {
                tbillTypeField.setAttribute('required', 'required');
            } else {
                tbillTypeField.removeAttribute('required');
            }
        }

        // Show/hide ECL section (not for Equity and MMF)
        const eclSection = document.querySelector('.ecl-section');
        if (eclSection) {
            const eclCategories = eclSection.getAttribute('data-category').split(',');
            if (eclCategories.includes(instrumentType)) {
                eclSection.style.display = 'block';
            } else {
                eclSection.style.display = 'none';
            }
        }
    }

    // Function to calculate ECL amount
    function calculateECL() {
        const pd = parseFloat(document.getElementById('pd')?.value) || 0;
        const lgd = parseFloat(document.getElementById('lgd')?.value) || 0;
        const ead = parseFloat(document.getElementById('ead')?.value) || 0;
        
        // ECL = (PD × LGD × EAD) / 10000 (since PD and LGD are percentages)
        const ecl = (pd * lgd * ead) / 10000;
        
        const eclAmountField = document.getElementById('ecl_amount');
        if (eclAmountField) {
            eclAmountField.value = ecl.toFixed(2);
        }
    }

    // Function to handle investment selection change
    function handleInvestmentChange(investmentId) {
        if (!investmentId) {
            toggleCategoryFields(null);
            return;
        }

        // Find investment data
        const investment = investmentsData.find(inv => inv.id == investmentId);
        if (investment) {
            toggleCategoryFields(investment.instrument_type);
            
            // Fetch more details via AJAX
            fetch(`{{ route('investments.trades.investment.details', ['id' => ':id']) }}`.replace(':id', investmentId))
                .then(response => response.json())
                .then(data => {
                    // Pre-fill fields if available
                    if (data.coupon_rate) {
                        const couponRateField = document.getElementById('coupon_rate');
                        const couponRateFdField = document.getElementById('coupon_rate_fd');
                        if (couponRateField) couponRateField.value = data.coupon_rate;
                        if (couponRateFdField) couponRateFdField.value = data.coupon_rate;
                    }
                })
                .catch(error => console.error('Error fetching investment details:', error));
        }
    }

    // Handle investment selection change (for both regular and Select2)
    investmentSelect.addEventListener('change', function() {
        handleInvestmentChange(this.value);
    });

    // Also listen for Select2 change events
    $('#investment_id').on('select2:select select2:clear', function() {
        handleInvestmentChange($(this).val());
    });

    // Initialize category fields if investment is pre-selected (after Select2 is ready)
    @if($investment)
        // Wait for Select2 to be ready, then trigger change
        $('#investment_id').on('select2:ready', function() {
            const selectedValue = $(this).val();
            if (selectedValue) {
                handleInvestmentChange(selectedValue);
            } else {
                // If Select2 doesn't have the value yet, set it and trigger
                $(this).val('{{ $investment->id }}').trigger('change');
            }
        });
        
        // Fallback: trigger after a short delay if select2:ready doesn't fire
        setTimeout(function() {
            const selectedValue = $('#investment_id').val();
            if (selectedValue) {
                handleInvestmentChange(selectedValue);
            } else {
                // Set the value and trigger change
                $('#investment_id').val('{{ $investment->id }}').trigger('change');
            }
        }, 200);
    @endif

    function calculateAmounts() {
        const price = parseFloat(tradePrice.value) || 0;
        const units = parseFloat(tradeUnits.value) || 0;
        const gross = price * units;
        const feesVal = parseFloat(fees.value) || 0;
        const taxVal = parseFloat(taxWithheld.value) || 0;
        const net = gross - feesVal - taxVal;

        grossAmount.value = gross.toFixed(2);
        netAmount.value = net.toFixed(2);
    }

    tradePrice.addEventListener('input', calculateAmounts);
    tradeUnits.addEventListener('input', calculateAmounts);
    fees.addEventListener('input', calculateAmounts);
    taxWithheld.addEventListener('input', calculateAmounts);

    // ECL calculation listeners
    const pdField = document.getElementById('pd');
    const lgdField = document.getElementById('lgd');
    const eadField = document.getElementById('ead');
    
    if (pdField) pdField.addEventListener('input', calculateECL);
    if (lgdField) lgdField.addEventListener('input', calculateECL);
    if (eadField) eadField.addEventListener('input', calculateECL);

    // Handle Bond Type "Other" option
    $('#bond_type').on('select2:select select2:clear change', function() {
        const bondTypeValue = $(this).val();
        const bondTypeOtherContainer = $('#bond_type_other_container');
        const bondTypeOtherInput = $('#bond_type_other');
        
        if (bondTypeValue === 'OTHER') {
            bondTypeOtherContainer.slideDown(300);
            bondTypeOtherInput.attr('required', 'required');
        } else {
            bondTypeOtherContainer.slideUp(300);
            bondTypeOtherInput.removeAttr('required');
            bondTypeOtherInput.val('');
        }
    });

    // Handle T-Bill Type "Other" option
    $('#tbill_type').on('select2:select select2:clear change', function() {
        const tbillTypeValue = $(this).val();
        const tbillTypeOtherContainer = $('#tbill_type_other_container');
        const tbillTypeOtherInput = $('#tbill_type_other');
        
        if (tbillTypeValue === 'OTHER') {
            tbillTypeOtherContainer.slideDown(300);
            tbillTypeOtherInput.attr('required', 'required');
        } else {
            tbillTypeOtherContainer.slideUp(300);
            tbillTypeOtherInput.removeAttr('required');
            tbillTypeOtherInput.val('');
        }
    });

    // Form submission - handle custom bond type and T-Bill type
    $('#trade-form').on('submit', function(e) {
        const bondTypeValue = $('#bond_type').val();
        const bondTypeOtherValue = $('#bond_type_other').val();
        const tbillTypeValue = $('#tbill_type').val();
        const tbillTypeOtherValue = $('#tbill_type_other').val();
        
        // If "OTHER" is selected for bond type, use the custom value
        if (bondTypeValue === 'OTHER') {
            if (!bondTypeOtherValue || !bondTypeOtherValue.trim()) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Custom Bond Type Required',
                    text: 'Please enter a custom bond type or select a different option.',
                    confirmButtonText: 'OK'
                });
                $('#bond_type_other').focus();
                return false;
            }
            // Create a hidden input with the custom value
            const hiddenInput = $('<input>').attr({
                type: 'hidden',
                name: 'bond_type',
                value: bondTypeOtherValue.trim()
            });
            $(this).append(hiddenInput);
            $('#bond_type').prop('disabled', true);
        }

        // If "OTHER" is selected for T-Bill type, use the custom value
        if (tbillTypeValue === 'OTHER') {
            if (!tbillTypeOtherValue || !tbillTypeOtherValue.trim()) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Custom T-Bill Type Required',
                    text: 'Please enter a custom T-Bill type or select a different option.',
                    confirmButtonText: 'OK'
                });
                $('#tbill_type_other').focus();
                return false;
            }
            // Create a hidden input with the custom value
            const hiddenInput = $('<input>').attr({
                type: 'hidden',
                name: 'tbill_type',
                value: tbillTypeOtherValue.trim()
            });
            $(this).append(hiddenInput);
            $('#tbill_type').prop('disabled', true);
        }
    });

    postToGl.addEventListener('change', function() {
        bankAccountField.style.display = this.checked ? 'block' : 'none';
        if (this.checked) {
            document.getElementById('bank_account_id').required = true;
            // Reinitialize select2 when field is shown
            $('#bank_account_id').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        } else {
            document.getElementById('bank_account_id').required = false;
        }
    });

    // Initialize
    calculateAmounts();
    if (postToGl.checked) {
        bankAccountField.style.display = 'block';
    }
});
</script>
@endpush
@endsection

