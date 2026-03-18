@extends('layouts.main')

@section('title', 'Record HFS Disposal')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Held for Sale', 'url' => route('assets.hfs.requests.index'), 'icon' => 'bx bx-transfer'],
            ['label' => 'Record Disposal', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bx bx-money me-2"></i>Record HFS Disposal - {{ $hfsRequest->request_no }}</h6>
            </div>
            <div class="card-body">
                <!-- Current Status -->
                <div class="alert alert-info mb-4">
                    <h6 class="mb-2">Current Status</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Current Carrying Amount:</strong><br>
                            {{ number_format($hfsRequest->current_total_carrying_amount, 2) }}
                        </div>
                        <div class="col-md-4">
                            <strong>Expected Fair Value:</strong><br>
                            {{ number_format($hfsRequest->expected_fair_value, 2) }}
                        </div>
                        <div class="col-md-4">
                            <strong>Assets Count:</strong><br>
                            {{ $hfsRequest->hfsAssets->count() }}
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('assets.hfs.disposals.store', $encodedId) }}" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Disposal Date <span class="text-danger">*</span></label>
                            <input type="date" name="disposal_date" class="form-control" 
                                value="{{ old('disposal_date', date('Y-m-d')) }}" required>
                            @error('disposal_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Current Carrying Amount</label>
                            <input type="text" id="carrying_amount_display" class="form-control" 
                                value="{{ number_format($hfsRequest->current_total_carrying_amount, 2) }}" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Sale Proceeds <span class="text-danger">*</span></label>
                            <input type="number" name="sale_proceeds" id="sale_proceeds" class="form-control" 
                                step="0.01" min="0" value="{{ old('sale_proceeds', $hfsRequest->expected_fair_value ?? 0) }}" required>
                            @error('sale_proceeds')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Costs Sold</label>
                            <input type="number" name="costs_sold" id="costs_sold" class="form-control" 
                                step="0.01" min="0" value="{{ old('costs_sold', $hfsRequest->expected_costs_to_sell ?? 0) }}">
                            @error('costs_sold')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Sale Currency</label>
                            @php
                                $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
                                $defaultCurrency = old('sale_currency', $functionalCurrency);
                            @endphp
                            <select name="sale_currency" class="form-select select2-single">
                                <option value="TZS" {{ $defaultCurrency == 'TZS' ? 'selected' : '' }}>TZS</option>
                                <option value="USD" {{ $defaultCurrency == 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="EUR" {{ $defaultCurrency == 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="GBP" {{ $defaultCurrency == 'GBP' ? 'selected' : '' }}>GBP</option>
                                <!-- Add more currencies as needed -->
                            </select>
                            @error('sale_currency')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Currency Rate</label>
                            <input type="number" name="currency_rate" class="form-control" 
                                step="0.000001" min="0" value="{{ old('currency_rate', 1) }}">
                            @error('currency_rate')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Bank Account</label>
                            <select name="bank_account_id" class="form-select select2-single">
                                <option value="">Select Bank Account</option>
                                @foreach($bankAccounts as $bank)
                                    <option value="{{ $bank->id }}" {{ old('bank_account_id') == $bank->id ? 'selected' : '' }}>
                                        {{ $bank->name }}@if($bank->account_number) - {{ $bank->account_number }}@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('bank_account_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Calculated Gain/Loss</label>
                            <input type="text" id="gain_loss_display" class="form-control" readonly>
                        </div>

                        <!-- VAT Section -->
                        <div class="col-md-4" id="vat-type-field">
                            <label class="form-label">VAT Type</label>
                            <select name="vat_type" id="vat_type" class="form-select">
                                <option value="no_vat" {{ old('vat_type', 'no_vat') == 'no_vat' ? 'selected' : '' }}>No VAT</option>
                                <option value="exclusive" {{ old('vat_type') == 'exclusive' ? 'selected' : '' }}>VAT Exclusive</option>
                                <option value="inclusive" {{ old('vat_type') == 'inclusive' ? 'selected' : '' }}>VAT Inclusive</option>
                            </select>
                            @error('vat_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4" id="vat-rate-field">
                            <label class="form-label">VAT Rate (%)</label>
                            <input type="number" name="vat_rate" id="vat_rate" class="form-control" 
                                step="0.01" min="0" max="100" value="{{ old('vat_rate', get_default_vat_rate()) }}">
                            @error('vat_rate')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4" id="vat-amount-field">
                            <label class="form-label">VAT Amount</label>
                            <input type="number" name="vat_amount" id="vat_amount" class="form-control" 
                                step="0.01" min="0" value="{{ old('vat_amount', 0) }}" readonly>
                            <div class="form-text">Calculated automatically</div>
                            @error('vat_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <!-- Withholding Tax Section -->
                        <div class="col-md-12" id="withholding-tax-section">
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

                        <!-- Buyer Information -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mt-3">Buyer Information</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Buyer Name</label>
                            <input type="text" name="buyer_name" class="form-control" 
                                value="{{ old('buyer_name', $hfsRequest->buyer_name) }}">
                            @error('buyer_name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Buyer Contact</label>
                            <input type="text" name="buyer_contact" class="form-control" 
                                value="{{ old('buyer_contact', $hfsRequest->buyer_contact) }}">
                            @error('buyer_contact')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Invoice Number</label>
                            <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number') }}">
                            @error('invoice_number')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Receipt Number</label>
                            <input type="text" name="receipt_number" class="form-control" value="{{ old('receipt_number') }}">
                            @error('receipt_number')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Settlement Reference</label>
                            <input type="text" name="settlement_reference" class="form-control" value="{{ old('settlement_reference') }}">
                            @error('settlement_reference')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Buyer Address</label>
                            <textarea name="buyer_address" class="form-control" rows="2">{{ old('buyer_address', $hfsRequest->buyer_address) }}</textarea>
                            @error('buyer_address')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <!-- Partial Sale -->
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_partial_sale" id="is_partial_sale" value="1">
                                <label class="form-check-label" for="is_partial_sale">
                                    This is a partial sale
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6" id="partial-sale-percentage-field" style="display: none;">
                            <label class="form-label">Partial Sale Percentage (%)</label>
                            <input type="number" name="partial_sale_percentage" class="form-control" 
                                step="0.01" min="0" max="100" value="{{ old('partial_sale_percentage') }}">
                            @error('partial_sale_percentage')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('assets.hfs.requests.show', $encodedId) }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-save me-1"></i>Record Disposal & Post Journal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for all select elements
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    const carryingAmount = {{ $hfsRequest->current_total_carrying_amount }};

    // Calculate VAT amount based on sale proceeds, VAT type, and VAT rate
    function calculateVAT() {
        const saleProceeds = parseFloat($('#sale_proceeds').val()) || 0;
        const vatType = $('#vat_type').val();
        const vatRate = parseFloat($('#vat_rate').val()) || 0;
        let vatAmount = 0;

        if (vatType === 'exclusive' && vatRate > 0) {
            // VAT Exclusive: VAT = proceeds * (rate / 100)
            vatAmount = saleProceeds * (vatRate / 100);
        } else if (vatType === 'inclusive' && vatRate > 0) {
            // VAT Inclusive: VAT = proceeds * (rate / (100 + rate))
            vatAmount = saleProceeds * (vatRate / (100 + vatRate));
        } else {
            // No VAT
            vatAmount = 0;
        }

        $('#vat_amount').val(vatAmount.toFixed(2));
        calculateWithholdingTax();
        calculateGainLoss();
    }

    // Calculate Withholding Tax
    function calculateWithholdingTax() {
        const withholdingTaxEnabled = $('#withholding_tax_enabled').is(':checked');
        const withholdingTaxRate = parseFloat($('#withholding_tax_rate').val()) || 0;
        const withholdingTaxType = $('#withholding_tax_type').val();
        const saleProceeds = parseFloat($('#sale_proceeds').val()) || 0;
        const vatAmount = parseFloat($('#vat_amount').val()) || 0;
        
        // Subtotal = sale proceeds - VAT (if VAT is inclusive, proceeds already includes VAT)
        const vatType = $('#vat_type').val();
        let subtotal = saleProceeds;
        if (vatType === 'inclusive') {
            subtotal = saleProceeds - vatAmount;
        } else if (vatType === 'exclusive') {
            subtotal = saleProceeds;
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

    function calculateGainLoss() {
        const saleProceeds = parseFloat($('#sale_proceeds').val()) || 0;
        const costsSold = parseFloat($('#costs_sold').val()) || 0;
        const vatAmount = parseFloat($('#vat_amount').val()) || 0;
        const withholdingTax = parseFloat($('#withholding_tax').val()) || 0;
        
        // Net proceeds = sale proceeds - VAT (if exclusive) - withholding tax
        const vatType = $('#vat_type').val();
        let netProceeds = saleProceeds;
        if (vatType === 'exclusive') {
            netProceeds = saleProceeds - vatAmount;
        }
        netProceeds = netProceeds - withholdingTax;
        
        const gainLoss = netProceeds - carryingAmount - costsSold;
        
        $('#gain_loss_display').val(formatNumber(gainLoss));
        if (gainLoss >= 0) {
            $('#gain_loss_display').removeClass('text-danger').addClass('text-success');
        } else {
            $('#gain_loss_display').removeClass('text-success').addClass('text-danger');
        }
    }

    // Event handlers
    $('#sale_proceeds, #costs_sold').on('input', function() {
        calculateVAT();
    });

    $('#vat_type, #vat_rate').on('change input', calculateVAT);
    $('#withholding_tax_enabled').on('change', toggleWithholdingTaxFields);
    $('#withholding_tax_rate, #withholding_tax_type').on('input change', calculateWithholdingTax);

    $('#is_partial_sale').on('change', function() {
        if ($(this).is(':checked')) {
            $('#partial-sale-percentage-field').show();
        } else {
            $('#partial-sale-percentage-field').hide();
        }
    });

    // Initialize
    toggleWithholdingTaxFields();
    calculateVAT();

    function formatNumber(num) {
        return parseFloat(num).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
});
</script>
@endpush

