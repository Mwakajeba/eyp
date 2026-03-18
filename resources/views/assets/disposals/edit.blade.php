@extends('layouts.main')

@section('title', 'Edit Asset Disposal')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Disposals', 'url' => route('assets.disposals.index'), 'icon' => 'bx bx-trash'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Asset Disposal</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('assets.disposals.update', \Vinkla\Hashids\Facades\Hashids::encode($disposal->id)) }}" enctype="multipart/form-data" id="disposal-form">
                    @csrf
                    @method('PUT')

                    <!-- Asset Information (Read-only) -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Asset Information</h6>
                        </div>
                        <div class="col-md-12">
                            <div class="alert alert-info mb-0">
                                <strong>Asset:</strong> {{ $disposal->asset->code }} - {{ $disposal->asset->name }}<br>
                                <strong>Current NBV:</strong> {{ number_format($disposal->net_book_value ?? 0, 2) }}
                            </div>
                        </div>
                    </div>

                    <!-- Disposal Details -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Disposal Details</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Disposal Type <span class="text-danger">*</span></label>
                            <select name="disposal_type" id="disposal_type" class="form-select" required>
                                <option value="sale" {{ old('disposal_type', $disposal->disposal_type) == 'sale' ? 'selected' : '' }}>Sale</option>
                                <option value="scrap" {{ old('disposal_type', $disposal->disposal_type) == 'scrap' ? 'selected' : '' }}>Scrap</option>
                                <option value="write_off" {{ old('disposal_type', $disposal->disposal_type) == 'write_off' ? 'selected' : '' }}>Write-off</option>
                                <option value="donation" {{ old('disposal_type', $disposal->disposal_type) == 'donation' ? 'selected' : '' }}>Donation</option>
                                <option value="loss" {{ old('disposal_type', $disposal->disposal_type) == 'loss' ? 'selected' : '' }}>Loss/Theft</option>
                            </select>
                            @error('disposal_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reason Code</label>
                            <select name="disposal_reason_code_id" id="disposal_reason_code_id" class="form-select select2-single">
                                <option value="">Select Reason Code</option>
                                @foreach($reasonCodes as $code)
                                    <option value="{{ $code->id }}" {{ old('disposal_reason_code_id', $disposal->disposal_reason_code_id) == $code->id ? 'selected' : '' }}>
                                        {{ $code->code }} - {{ $code->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('disposal_reason_code_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Proposed Disposal Date <span class="text-danger">*</span></label>
                            <input type="date" name="proposed_disposal_date" class="form-control" 
                                value="{{ old('proposed_disposal_date', $disposal->proposed_disposal_date->format('Y-m-d')) }}" required>
                            @error('proposed_disposal_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Disposal Reason <span class="text-danger">*</span></label>
                            <textarea name="disposal_reason" class="form-control" rows="2" required>{{ old('disposal_reason', $disposal->disposal_reason) }}</textarea>
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
                                step="0.01" min="0" value="{{ old('disposal_proceeds', $disposal->disposal_proceeds) }}">
                            @error('disposal_proceeds')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4" id="fair-value-field" style="display: none;">
                            <label class="form-label">Fair Value</label>
                            <input type="number" name="fair_value" id="fair_value" class="form-control" 
                                step="0.01" min="0" value="{{ old('fair_value', $disposal->fair_value) }}">
                            @error('fair_value')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4" id="vat-field" style="display: none;">
                            <label class="form-label">VAT Amount</label>
                            <input type="number" name="vat_amount" id="vat_amount" class="form-control" 
                                step="0.01" min="0" value="{{ old('vat_amount', $disposal->vat_amount ?? 0) }}">
                            @error('vat_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4" id="withholding-tax-field" style="display: none;">
                            <label class="form-label">Withholding Tax</label>
                            <input type="number" name="withholding_tax" id="withholding_tax" class="form-control" 
                                step="0.01" min="0" value="{{ old('withholding_tax', $disposal->withholding_tax ?? 0) }}">
                            @error('withholding_tax')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Buyer/Recipient Information -->
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
                                                    {{ old('customer_id', $disposal->customer_id) == $customer->id ? 'selected' : '' }}>
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
                            <input type="text" name="buyer_name" id="buyer_name" class="form-control" value="{{ old('buyer_name', $disposal->buyer_name) }}">
                            @error('buyer_name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contact</label>
                            <input type="text" name="buyer_contact" id="buyer_contact" class="form-control" value="{{ old('buyer_contact', $disposal->buyer_contact) }}">
                            @error('buyer_contact')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Address</label>
                            <input type="text" name="buyer_address" id="buyer_address" class="form-control" value="{{ old('buyer_address', $disposal->buyer_address) }}">
                            @error('buyer_address')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Invoice Number</label>
                            <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number', $disposal->invoice_number) }}">
                            @error('invoice_number')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Receipt Number</label>
                            <input type="text" name="receipt_number" class="form-control" value="{{ old('receipt_number', $disposal->receipt_number) }}">
                            @error('receipt_number')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Insurance Recovery -->
                    <div class="row g-3 mb-4" id="insurance-section" style="display: none;">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Insurance Recovery</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Recovery Amount</label>
                            <input type="number" name="insurance_recovery_amount" class="form-control" 
                                step="0.01" min="0" value="{{ old('insurance_recovery_amount', $disposal->insurance_recovery_amount ?? 0) }}">
                            @error('insurance_recovery_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Claim Number</label>
                            <input type="text" name="insurance_claim_number" class="form-control" value="{{ old('insurance_claim_number', $disposal->insurance_claim_number) }}">
                            @error('insurance_claim_number')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Recovery Date</label>
                            <input type="date" name="insurance_recovery_date" class="form-control" 
                                value="{{ old('insurance_recovery_date', $disposal->insurance_recovery_date ? $disposal->insurance_recovery_date->format('Y-m-d') : '') }}">
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
                                <input class="form-check-input" type="checkbox" name="is_partial_disposal" id="is_partial_disposal" value="1" 
                                    {{ old('is_partial_disposal', $disposal->is_partial_disposal) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_partial_disposal">
                                    This is a partial disposal
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6" id="partial-percentage-field" style="display: none;">
                            <label class="form-label">Disposal Percentage (%)</label>
                            <input type="number" name="partial_disposal_percentage" class="form-control" 
                                step="0.01" min="0" max="100" value="{{ old('partial_disposal_percentage', $disposal->partial_disposal_percentage) }}">
                            @error('partial_disposal_percentage')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" id="partial-description-field" style="display: none;">
                            <label class="form-label">Description</label>
                            <input type="text" name="partial_disposal_description" class="form-control" value="{{ old('partial_disposal_description', $disposal->partial_disposal_description) }}">
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
                            @if($disposal->valuation_report_path)
                                <div class="form-text">Current: <a href="{{ Storage::url($disposal->valuation_report_path) }}" target="_blank">View Report</a></div>
                            @endif
                            @error('valuation_report')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Additional Attachments</label>
                            <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            @if($disposal->attachments && count($disposal->attachments) > 0)
                                <div class="form-text">Current: {{ count($disposal->attachments) }} file(s) attached</div>
                            @endif
                            @error('attachments.*')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label">Additional Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $disposal->notes) }}</textarea>
                            @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('assets.disposals.show', \Vinkla\Hashids\Facades\Hashids::encode($disposal->id)) }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>Update Disposal
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
        $('#sale-proceeds-field, #fair-value-field, #vat-field, #withholding-tax-field, #buyer-info-section, #insurance-section').hide();
        
        if (type === 'sale') {
            $('#sale-proceeds-field, #vat-field, #withholding-tax-field, #buyer-info-section').show();
        } else if (type === 'donation') {
            $('#fair-value-field, #buyer-info-section').show();
        } else if (type === 'write_off' || type === 'scrap') {
            $('#fair-value-field').show();
        } else if (type === 'loss') {
            $('#insurance-section').show();
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

    // Initial toggle
    $('#disposal_type').on('change', toggleFieldsByType);
    $('#is_partial_disposal').on('change', togglePartialFields);
    toggleFieldsByType();
    togglePartialFields();

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

