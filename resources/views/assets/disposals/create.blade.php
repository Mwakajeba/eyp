@extends('layouts.main')

@section('title', 'Create Asset Disposal')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Disposals', 'url' => route('assets.disposals.index'), 'icon' => 'bx bx-trash'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>Create Asset Disposal</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('assets.disposals.store') }}" enctype="multipart/form-data" id="disposal-form">
                    @csrf

                    <!-- Asset Selection -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Asset Information</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Select Asset <span class="text-danger">*</span></label>
                            <select name="asset_id" id="asset_id" class="form-select select2-single" required>
                                <option value="">Choose an asset...</option>
                                @foreach($assets as $a)
                                    <option value="{{ $a->id }}" 
                                        {{ (old('asset_id') == $a->id || ($asset && $asset->id == $a->id)) ? 'selected' : '' }}
                                        data-cost="{{ $a->purchase_cost ?? 0 }}"
                                        data-nbv="{{ $a->current_nbv ?? 0 }}">
                                        {{ $a->code }} - {{ $a->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('asset_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        @if($asset)
                        <div class="col-md-6">
                            <div class="alert alert-info mb-0">
                                <strong>Current NBV:</strong> 
                                <span id="current-nbv">{{ number_format($asset->current_nbv ?? $asset->purchase_cost, 2) }}</span>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Disposal Details -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Disposal Details</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Disposal Type <span class="text-danger">*</span></label>
                            <select name="disposal_type" id="disposal_type" class="form-select" required>
                                <option value="sale" {{ old('disposal_type') == 'sale' ? 'selected' : '' }}>Sale</option>
                                <option value="scrap" {{ old('disposal_type') == 'scrap' ? 'selected' : '' }}>Scrap</option>
                                <option value="write_off" {{ old('disposal_type') == 'write_off' ? 'selected' : '' }}>Write-off</option>
                                <option value="donation" {{ old('disposal_type') == 'donation' ? 'selected' : '' }}>Donation</option>
                                <option value="loss" {{ old('disposal_type') == 'loss' ? 'selected' : '' }}>Loss/Theft</option>
                            </select>
                            @error('disposal_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reason Code</label>
                            <div class="input-group">
                                <select name="disposal_reason_code_id" id="disposal_reason_code_id" class="form-select select2-single">
                                    <option value="">Select Reason Code</option>
                                    @foreach($reasonCodes as $code)
                                        <option value="{{ $code->id }}" {{ old('disposal_reason_code_id') == $code->id ? 'selected' : '' }}>
                                            {{ $code->code }} - {{ $code->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <a href="{{ route('assets.disposals.reason-codes.index') }}" class="btn btn-outline-secondary" target="_blank" title="Manage Reason Codes">
                                    <i class="bx bx-cog"></i>
                                </a>
                            </div>
                            <div class="form-text">
                                <a href="{{ route('assets.disposals.reason-codes.create') }}" target="_blank" class="text-decoration-none">
                                    <i class="bx bx-plus"></i> Create new reason code
                                </a>
                            </div>
                            @error('disposal_reason_code_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Proposed Disposal Date <span class="text-danger">*</span></label>
                            <input type="date" name="proposed_disposal_date" class="form-control" 
                                value="{{ old('proposed_disposal_date', date('Y-m-d')) }}" required>
                            @error('proposed_disposal_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Disposal Reason <span class="text-danger">*</span></label>
                            <textarea name="disposal_reason" class="form-control" rows="2" required>{{ old('disposal_reason') }}</textarea>
                            <div class="form-text">Describe the reason for disposal (e.g., obsolete, damaged, replaced, etc.)</div>
                            @error('disposal_reason')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Financial Information -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Financial Information</h6>
                        </div>
                        <div class="col-md-4" id="sale-proceeds-field" style="display: none;">
                            <label class="form-label">Sale Proceeds</label>
                            <input type="number" name="disposal_proceeds" id="disposal_proceeds" class="form-control" 
                                step="0.01" min="0" value="{{ old('disposal_proceeds') }}">
                            <div class="form-text">Total amount from sale</div>
                            @error('disposal_proceeds')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4" id="amount-paid-field" style="display: none;">
                            <label class="form-label">Amount Paid</label>
                            <input type="number" name="amount_paid" id="amount_paid" class="form-control" 
                                step="0.01" min="0" value="{{ old('amount_paid', 0) }}">
                            <div class="form-text">Amount received/paid now</div>
                            @error('amount_paid')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4" id="bank-account-field" style="display: none;">
                            <label class="form-label">Bank Account</label>
                            <select name="bank_account_id" id="bank_account_id" class="form-select select2-single">
                                <option value="">-- Select Bank Account --</option>
                                @foreach($bankAccounts as $bankAccount)
                                    <option value="{{ $bankAccount->id }}" {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                        {{ $bankAccount->name }} 
                                        @if($bankAccount->account_number) ({{ $bankAccount->account_number }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Select bank account if payment received</div>
                            @error('bank_account_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4" id="fair-value-field" style="display: none;">
                            <label class="form-label">Fair Value</label>
                            <input type="number" name="fair_value" id="fair_value" class="form-control" 
                                step="0.01" min="0" value="{{ old('fair_value') }}">
                            <div class="form-text">Fair value for donation/write-off</div>
                            @error('fair_value')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4" id="vat-type-field" style="display: none;">
                            <label class="form-label">VAT Type</label>
                            <select name="vat_type" id="vat_type" class="form-select">
                                <option value="no_vat" {{ old('vat_type', 'no_vat') == 'no_vat' ? 'selected' : '' }}>No VAT</option>
                                <option value="exclusive" {{ old('vat_type') == 'exclusive' ? 'selected' : '' }}>VAT Exclusive</option>
                                <option value="inclusive" {{ old('vat_type') == 'inclusive' ? 'selected' : '' }}>VAT Inclusive</option>
                            </select>
                            @error('vat_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4" id="vat-rate-field" style="display: none;">
                            <label class="form-label">VAT Rate (%)</label>
                            <input type="number" name="vat_rate" id="vat_rate" class="form-control" 
                                step="0.01" min="0" max="100" value="{{ old('vat_rate', get_default_vat_rate()) }}">
                            @error('vat_rate')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4" id="vat-amount-field" style="display: none;">
                            <label class="form-label">VAT Amount</label>
                            <input type="number" name="vat_amount" id="vat_amount" class="form-control" 
                                step="0.01" min="0" value="{{ old('vat_amount', 0) }}" readonly>
                            <div class="form-text">Calculated automatically</div>
                            @error('vat_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-12" id="withholding-tax-section" style="display: none;">
                            <div class="card border-info">
                                <div class="card-header bg-info bg-gradient text-white py-2">
                                    <div class="form-check">
                                        <input type="hidden" name="withholding_tax_enabled" value="0">
                                        <input class="form-check-input" type="checkbox" id="withholding_tax_enabled" name="withholding_tax_enabled" value="1" {{ old('withholding_tax_enabled') ? 'checked' : '' }}>
                                        <label class="form-check-label text-white" for="withholding_tax_enabled">
                                            <strong>Apply Withholding Tax</strong>
                                        </label>
                                    </div>
                                </div>
                                <div class="card-body" id="withholding-tax-fields" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label">Withholding Tax Rate (%)</label>
                                            <input type="number" name="withholding_tax_rate" id="withholding_tax_rate" class="form-control" 
                                                step="0.01" min="0" max="100" value="{{ old('withholding_tax_rate', 5) }}">
                                            @error('withholding_tax_rate')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Withholding Tax Type</label>
                                            <select name="withholding_tax_type" id="withholding_tax_type" class="form-select">
                                                <option value="percentage" {{ old('withholding_tax_type', 'percentage') == 'percentage' ? 'selected' : '' }}>Percentage of Subtotal</option>
                                                <option value="fixed" {{ old('withholding_tax_type') == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                            </select>
                                            @error('withholding_tax_type')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Withholding Tax Amount</label>
                                            <input type="number" name="withholding_tax" id="withholding_tax" class="form-control" 
                                                step="0.01" min="0" value="{{ old('withholding_tax', 0) }}" readonly>
                                            <div class="form-text">Calculated automatically</div>
                                            @error('withholding_tax')<div class="text-danger small">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="alert alert-info mb-0" id="nbv-display" style="display: none;">
                                <strong>Net Book Value (NBV):</strong> 
                                <span id="nbv-amount">0.00</span>
                                <br>
                                <small>NBV = Cost - Accumulated Depreciation - Accumulated Impairment</small>
                            </div>
                        </div>
                    </div>

                    <!-- Buyer/Recipient Information (for Sale/Donation) -->
                    <div class="row g-3 mb-4" id="buyer-info-section" style="display: none;">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Buyer/Recipient Information</h6>
                        </div>
                        
                        <!-- Customer Selection -->
                        <div class="col-md-12">
                            <div class="card border-primary border-2 shadow-sm mb-3">
                                <div class="card-header bg-primary bg-gradient text-white py-2">
                                    <h6 class="mb-0">
                                        <i class="bx bx-user-circle me-2"></i>Customer Selection
                                        <span class="small fw-normal opacity-75 ms-2">(Optional - Select existing customer or enter manually below)</span>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <label class="form-label fw-semibold mb-3">Search & Select Customer</label>
                                    <div class="input-group input-group-lg">
                                        <select class="form-select form-select-lg select2-single" id="customer_id" name="customer_id" style="width: 100% !important;">
                                            <option value="">-- Select Customer (Optional) --</option>
                                            @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}" 
                                                    data-name="{{ $customer->name }}"
                                                    data-phone="{{ $customer->phone ?? '' }}"
                                                    data-email="{{ $customer->email ?? '' }}"
                                                    data-address="{{ $customer->address ?? '' }}"
                                                    {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->name }} @if($customer->phone) - {{ $customer->phone }} @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-outline-danger" id="clear-customer-btn" title="Clear Customer Selection">
                                            <i class="bx bx-x me-1"></i>Clear
                                        </button>
                                    </div>
                                    @error('customer_id')
                                        <div class="alert alert-danger py-2 mt-2 mb-0">
                                            <i class="bx bx-error-circle me-1"></i> {{ $message }}
                                        </div>
                                    @enderror
                                    <div class="alert alert-info py-2 mt-2 mb-0">
                                        <i class="bx bx-info-circle me-2"></i>
                                        <small>Selecting a customer will automatically fill in the buyer information below</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Buyer Details (Auto-filled or Manual Entry) -->
                        <div class="col-12">
                            <div class="alert alert-info border-info mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bx bx-info-circle fs-4 me-2"></i>
                                    <div>
                                        <strong>Buyer Details:</strong> Fields below will be auto-filled if a customer is selected, or you can enter manually.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Name</label>
                            <input type="text" name="buyer_name" id="buyer_name" class="form-control" value="{{ old('buyer_name') }}">
                            @error('buyer_name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contact</label>
                            <input type="text" name="buyer_contact" id="buyer_contact" class="form-control" value="{{ old('buyer_contact') }}">
                            @error('buyer_contact')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Address</label>
                            <input type="text" name="buyer_address" id="buyer_address" class="form-control" value="{{ old('buyer_address') }}">
                            @error('buyer_address')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Invoice Number</label>
                            <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number') }}">
                            @error('invoice_number')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Receipt Number</label>
                            <input type="text" name="receipt_number" class="form-control" value="{{ old('receipt_number') }}">
                            @error('receipt_number')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Insurance Recovery (for Loss/Theft) -->
                    <div class="row g-3 mb-4" id="insurance-section" style="display: none;">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Insurance Recovery</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Recovery Amount</label>
                            <input type="number" name="insurance_recovery_amount" class="form-control" 
                                step="0.01" min="0" value="{{ old('insurance_recovery_amount', 0) }}">
                            @error('insurance_recovery_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Claim Number</label>
                            <input type="text" name="insurance_claim_number" class="form-control" value="{{ old('insurance_claim_number') }}">
                            @error('insurance_claim_number')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Recovery Date</label>
                            <input type="date" name="insurance_recovery_date" class="form-control" value="{{ old('insurance_recovery_date') }}">
                            @error('insurance_recovery_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Partial Disposal -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Partial Disposal (Optional)</h6>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_partial_disposal" id="is_partial_disposal" value="1" {{ old('is_partial_disposal') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_partial_disposal">
                                    This is a partial disposal (e.g., selling part of a land parcel)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6" id="partial-percentage-field" style="display: none;">
                            <label class="form-label">Disposal Percentage (%)</label>
                            <input type="number" name="partial_disposal_percentage" class="form-control" 
                                step="0.01" min="0" max="100" value="{{ old('partial_disposal_percentage') }}">
                            @error('partial_disposal_percentage')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" id="partial-description-field" style="display: none;">
                            <label class="form-label">Description</label>
                            <input type="text" name="partial_disposal_description" class="form-control" value="{{ old('partial_disposal_description') }}">
                            @error('partial_disposal_description')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Documents -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Supporting Documents</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valuation Report (PDF/DOC)</label>
                            <input type="file" name="valuation_report" class="form-control" accept=".pdf,.doc,.docx">
                            <div class="form-text">Upload valuation report if available</div>
                            @error('valuation_report')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Additional Attachments</label>
                            <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <div class="form-text">Board approval, police report, receipts, etc.</div>
                            @error('attachments.*')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label">Additional Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('assets.disposals.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>Create Disposal Request
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Ensure customer selection input group is properly grouped */
    .input-group-lg #customer_id + .select2-container {
        flex: 1 1 auto;
        width: 1% !important;
        min-width: 0;
    }
    
    .input-group-lg #customer_id + .select2-container .select2-selection {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        border-right: none;
        height: calc(1.5rem * 1.25 + 1rem + 2px) !important;
    }
    
    .input-group-lg #customer_id + .select2-container .select2-selection__rendered {
        line-height: calc(1.5rem * 1.25 + 1rem + 2px) !important;
    }
    
    .input-group-lg #customer_id + .select2-container .select2-selection__arrow {
        height: calc(1.5rem * 1.25 + 1rem + 2px) !important;
    }
    
    #clear-customer-btn {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for customer dropdown with better styling
    // Wait for input group to be ready
    setTimeout(function() {
        $('#customer_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Search and select a customer...',
            allowClear: false, // We have our own clear button
            dropdownParent: $('#customer_id').closest('.card-body'),
            language: {
                noResults: function() {
                    return "No customers found";
                },
                searching: function() {
                    return "Searching customers...";
                }
            }
        });
        
        // Ensure Select2 container fits within input group
        $('#customer_id').on('select2:open', function() {
            $('.select2-container').css('width', '100%');
        });
    }, 100);
    
    // Initialize other Select2 dropdowns
    $('.select2-single').not('#customer_id').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Show/hide fields based on disposal type
    function toggleFieldsByType() {
        const type = $('#disposal_type').val();
        
        // Hide all conditional fields first
        $('#sale-proceeds-field, #amount-paid-field, #bank-account-field, #fair-value-field, #vat-type-field, #vat-rate-field, #vat-amount-field, #withholding-tax-section, #buyer-info-section, #insurance-section').hide();
        
        if (type === 'sale') {
            $('#sale-proceeds-field, #amount-paid-field, #vat-type-field, #vat-rate-field, #vat-amount-field, #withholding-tax-section, #buyer-info-section').show();
            toggleBankAccountField();
            calculateVAT();
        } else if (type === 'donation') {
            $('#fair-value-field, #buyer-info-section').show();
        } else if (type === 'write_off' || type === 'scrap') {
            $('#fair-value-field').show();
        } else if (type === 'loss') {
            $('#insurance-section').show();
        }
    }

    // Calculate VAT amount based on disposal proceeds, VAT type, and VAT rate
    function calculateVAT() {
        const disposalProceeds = parseFloat($('#disposal_proceeds').val()) || 0;
        const vatType = $('#vat_type').val();
        const vatRate = parseFloat($('#vat_rate').val()) || 0;
        let vatAmount = 0;

        if (vatType === 'exclusive' && vatRate > 0) {
            // VAT Exclusive: VAT = proceeds * (rate / 100)
            vatAmount = disposalProceeds * (vatRate / 100);
        } else if (vatType === 'inclusive' && vatRate > 0) {
            // VAT Inclusive: VAT = proceeds * (rate / (100 + rate))
            vatAmount = disposalProceeds * (vatRate / (100 + vatRate));
        } else {
            // No VAT
            vatAmount = 0;
        }

        $('#vat_amount').val(vatAmount.toFixed(2));
        calculateWithholdingTax();
        updateBalance();
    }

    // Calculate Withholding Tax
    function calculateWithholdingTax() {
        const withholdingTaxEnabled = $('#withholding_tax_enabled').is(':checked');
        const withholdingTaxRate = parseFloat($('#withholding_tax_rate').val()) || 0;
        const withholdingTaxType = $('#withholding_tax_type').val();
        const disposalProceeds = parseFloat($('#disposal_proceeds').val()) || 0;
        const vatAmount = parseFloat($('#vat_amount').val()) || 0;
        
        // Subtotal = disposal proceeds - VAT (if VAT is inclusive, proceeds already includes VAT)
        const vatType = $('#vat_type').val();
        let subtotal = disposalProceeds;
        if (vatType === 'inclusive') {
            subtotal = disposalProceeds - vatAmount;
        } else if (vatType === 'exclusive') {
            subtotal = disposalProceeds;
        }

        let withholdingTaxAmount = 0;

        if (withholdingTaxEnabled && withholdingTaxRate > 0) {
            if (withholdingTaxType === 'percentage') {
                withholdingTaxAmount = subtotal * (withholdingTaxRate / 100);
            } else if (withholdingTaxType === 'fixed') {
                withholdingTaxAmount = withholdingTaxRate;
            }
        }

        $('#withholding_tax').val(withholdingTaxAmount.toFixed(2));
    }

    // Toggle withholding tax fields
    function toggleWithholdingTaxFields() {
        if ($('#withholding_tax_enabled').is(':checked')) {
            $('#withholding-tax-fields').show();
            calculateWithholdingTax();
        } else {
            $('#withholding-tax-fields').hide();
            $('#withholding_tax').val(0);
        }
    }

    // Show/hide bank account field based on amount paid
    function toggleBankAccountField() {
        const amountPaid = parseFloat($('#amount_paid').val()) || 0;
        if (amountPaid > 0) {
            $('#bank-account-field').show();
            $('#bank_account_id').prop('required', true);
        } else {
            $('#bank-account-field').hide();
            $('#bank_account_id').prop('required', false);
            $('#bank_account_id').val('');
        }
    }

    // Calculate and display balance (disposal proceeds - amount paid)
    function updateBalance() {
        const disposalProceeds = parseFloat($('#disposal_proceeds').val()) || 0;
        const amountPaid = parseFloat($('#amount_paid').val()) || 0;
        const balance = disposalProceeds - amountPaid;
        
        if (disposalProceeds > 0 && amountPaid > 0) {
            if (balance > 0) {
                $('#nbv-display').html(`
                    <strong>Net Book Value (NBV):</strong> 
                    <span id="nbv-amount">${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat($('#nbv-amount').text().replace(/,/g, '')) || 0)}</span>
                    <br>
                    <strong>Balance (Receivable):</strong> 
                    <span class="text-warning">${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(balance)}</span>
                    <br>
                    <small>NBV = Cost - Accumulated Depreciation - Accumulated Impairment</small>
                `);
            } else if (balance < 0) {
                alert('Amount paid cannot exceed disposal proceeds');
                $('#amount_paid').val(disposalProceeds);
                toggleBankAccountField();
            } else {
                $('#nbv-display').html(`
                    <strong>Net Book Value (NBV):</strong> 
                    <span id="nbv-amount">${new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(parseFloat($('#nbv-amount').text().replace(/,/g, '')) || 0)}</span>
                    <br>
                    <small>NBV = Cost - Accumulated Depreciation - Accumulated Impairment</small>
                `);
            }
        }
    }

    // Show/hide partial disposal fields
    function togglePartialFields() {
        if ($('#is_partial_disposal').is(':checked')) {
            $('#partial-percentage-field, #partial-description-field').show();
        } else {
            $('#partial-percentage-field, #partial-description-field').hide();
        }
    }

    // Calculate and display NBV when asset is selected
    $('#asset_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
            const nbv = parseFloat(selectedOption.data('nbv')) || 0;
            $('#nbv-amount').text(new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(nbv));
            $('#nbv-display').show();
        } else {
            $('#nbv-display').hide();
        }
    });

    // Initial toggle
    $('#disposal_type').on('change', toggleFieldsByType);
    $('#is_partial_disposal').on('change', togglePartialFields);
    $('#amount_paid').on('input', function() {
        toggleBankAccountField();
        updateBalance();
    });
    $('#disposal_proceeds').on('input', function() {
        calculateVAT();
        updateBalance();
    });
    $('#vat_type, #vat_rate').on('change input', calculateVAT);
    $('#withholding_tax_enabled').on('change', toggleWithholdingTaxFields);
    $('#withholding_tax_rate, #withholding_tax_type').on('input change', calculateWithholdingTax);
    toggleFieldsByType();
    togglePartialFields();
    toggleWithholdingTaxFields();

    // Trigger change if asset is pre-selected
    if ($('#asset_id').val()) {
        $('#asset_id').trigger('change');
    }

    // Auto-fill buyer information when customer is selected
    $('#customer_id').on('change', function() {
        const selectedOption = $('#customer_id option:selected');
        const customerId = $(this).val();
        
        if (customerId) {
            // Auto-fill buyer information from customer data
            const customerName = selectedOption.data('name') || '';
            const customerPhone = selectedOption.data('phone') || '';
            const customerAddress = selectedOption.data('address') || '';
            
            $('#buyer_name').val(customerName);
            $('#buyer_contact').val(customerPhone);
            $('#buyer_address').val(customerAddress);
            
            // Show success feedback
            if (customerName) {
                Swal.fire({
                    icon: 'success',
                    title: 'Customer Selected',
                    text: `Buyer information has been auto-filled from ${customerName}`,
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }
        } else {
            // Clear fields if no customer selected
            $('#buyer_name').val('');
            $('#buyer_contact').val('');
            $('#buyer_address').val('');
        }
    });

    // Clear customer button
    $('#clear-customer-btn').on('click', function() {
        $('#customer_id').val('').trigger('change');
    });

    // If customer is pre-selected, trigger change to auto-fill
    if ($('#customer_id').val()) {
        $('#customer_id').trigger('change');
    }
});
</script>
@endpush

