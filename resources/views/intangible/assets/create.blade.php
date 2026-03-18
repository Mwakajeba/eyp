@extends('layouts.main')

@section('title', 'New Intangible Asset')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Intangible Assets', 'url' => route('assets.intangible.index'), 'icon' => 'bx bx-brain'],
            ['label' => 'New Intangible Asset', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1"><i class="bx bx-plus me-2 text-primary"></i>New Intangible Asset</h5>
                        <p class="text-muted mb-0 small">Register software, licences, goodwill and other non-physical assets in compliance with IAS 38.</p>
                    </div>
                    <span class="badge bg-light text-primary border small">
                        <i class="bx bx-check-shield me-1"></i>IAS 38 / IAS 36 aligned
                    </span>
                </div>
            </div>
            <div class="card-body border-top">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="bg-light rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar-sm flex-shrink-0 me-2">
                                    <span class="avatar-title bg-primary text-white rounded-2">
                                        <i class="bx bx-purchase-tag-alt"></i>
                                    </span>
                                </div>
                                <div>
                                    <div class="small fw-semibold text-dark">Purchased & Licences</div>
                                    <div class="text-muted extra-small">Off-the-shelf software, franchises, patents, trademarks.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar-sm flex-shrink-0 me-2">
                                    <span class="avatar-title bg-info text-white rounded-2">
                                        <i class="bx bx-code-alt"></i>
                                    </span>
                                </div>
                                <div>
                                    <div class="small fw-semibold text-dark">Internally Developed</div>
                                    <div class="text-muted extra-small">Capitalised development costs for software and digital platforms.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar-sm flex-shrink-0 me-2">
                                    <span class="avatar-title bg-warning text-white rounded-2">
                                        <i class="bx bx-crown"></i>
                                    </span>
                                </div>
                                <div>
                                    <div class="small fw-semibold text-dark">Goodwill & Brands</div>
                                    <div class="text-muted extra-small">Non-amortising goodwill and indefinite-life brands (impairment only).</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('assets.intangible.store') }}">
                    @csrf

                    <div class="row g-4">
                        <div class="col-lg-6">
                            <h6 class="text-muted text-uppercase small mb-2">Core Details</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small">Code<span class="text-danger">*</span></label>
                                    <input type="text" name="code" value="{{ old('code') }}" class="form-control form-control-sm @error('code') is-invalid @enderror" placeholder="e.g. IA-SW-001">
                                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label small">Name<span class="text-danger">*</span></label>
                                    <input type="text" name="name" value="{{ old('name') }}" class="form-control form-control-sm @error('name') is-invalid @enderror" placeholder="e.g. Core Banking Software Licence">
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small">Category<span class="text-danger">*</span></label>
                                    <select name="category_id" id="assetCategory" class="form-select form-select-sm select2-single @error('category_id') is-invalid @enderror" data-placeholder="Select Category" required>
                                        <option value=""></option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" 
                                                data-type="{{ $category->type }}"
                                                {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->code ? $category->code . ' - ' : '' }}{{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text small mt-1">
                                        <i class="bx bx-info-circle me-1"></i>
                                        <span id="categoryTypeInfo">Select a category to auto-populate the source type.</span>
                                    </div>
                                    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small">Source Type<span class="text-danger">*</span></label>
                                    <select name="source_type" id="sourceType" class="form-select form-select-sm @error('source_type') is-invalid @enderror" required>
                                        <option value="">-- Select Type --</option>
                                        <option value="purchased" {{ old('source_type') === 'purchased' ? 'selected' : '' }}>Purchased</option>
                                        <option value="internally_developed" {{ old('source_type') === 'internally_developed' ? 'selected' : '' }}>Internally Developed</option>
                                        <option value="goodwill" {{ old('source_type') === 'goodwill' ? 'selected' : '' }}>Goodwill</option>
                                        <option value="indefinite_life" {{ old('source_type') === 'indefinite_life' ? 'selected' : '' }}>Indefinite-life Intangible</option>
                                        <option value="other" {{ old('source_type') === 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    <div class="form-text small mt-1">
                                        <i class="bx bx-info-circle me-1"></i>
                                        Auto-populated from selected category type.
                                    </div>
                                    @error('source_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small">Acquisition Date<span class="text-danger">*</span></label>
                                    <input type="date" name="acquisition_date" value="{{ old('acquisition_date', now()->toDateString()) }}" class="form-control form-control-sm @error('acquisition_date') is-invalid @enderror">
                                    @error('acquisition_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small">Total Cost<span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" name="cost" id="totalCost" value="{{ old('cost') }}" class="form-control form-control-sm @error('cost') is-invalid @enderror" placeholder="0.00">
                                    @error('cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6" id="amountPaidSection" style="display: none;">
                                    <label class="form-label small">Amount Paid</label>
                                    <input type="number" step="0.01" min="0" name="amount_paid" id="amountPaid" value="{{ old('amount_paid') }}" class="form-control form-control-sm @error('amount_paid') is-invalid @enderror" placeholder="0.00">
                                    <div class="form-text small">Leave blank if full payment. Remaining balance will be posted to Trade Payable.</div>
                                    @error('amount_paid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>

                                <div class="col-md-6" id="bankAccountSection" style="display: none;">
                                    <label class="form-label small">Bank Account</label>
                                    <select name="bank_account_id" id="bankAccountId" class="form-select form-select-sm select2-single @error('bank_account_id') is-invalid @enderror" data-placeholder="Select Bank Account">
                                        <option value=""></option>
                                        @foreach($bankAccounts as $bankAccount)
                                            <option value="{{ $bankAccount->id }}" {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                                {{ $bankAccount->name }} ({{ $bankAccount->account_number }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text small">Select bank account from which payment was made.</div>
                                    @error('bank_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <h6 class="text-muted text-uppercase small mb-2">Amortisation & Classification</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small">Useful Life (months)</label>
                                    <input type="number" min="1" name="useful_life_months" id="usefulLifeMonths" value="{{ old('useful_life_months') }}" class="form-control form-control-sm @error('useful_life_months') is-invalid @enderror" placeholder="e.g. 60">
                                    @error('useful_life_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="form-text small">Leave blank for goodwill / indefinite-life assets.</div>
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label small d-block">Special Classification Flags</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_indefinite_life" value="1" id="flagIndefinite" {{ old('is_indefinite_life') ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="flagIndefinite">
                                                <i class="bx bx-info-circle me-1 text-info" data-bs-toggle="tooltip" title="No amortisation, subject to annual impairment testing"></i>
                                                Indefinite life (no amortisation, annual impairment)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_goodwill" value="1" id="flagGoodwill" {{ old('is_goodwill') ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="flagGoodwill">
                                                <i class="bx bx-info-circle me-1 text-info" data-bs-toggle="tooltip" title="Goodwill from business combinations, non-amortising"></i>
                                                Goodwill (from business combination)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-text small mt-1">
                                        <i class="bx bx-info-circle me-1"></i>
                                        Check these flags for assets that do not amortise (goodwill, indefinite-life intangibles).
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small">Description / Recognition Rationale</label>
                                    <textarea name="description" rows="3" class="form-control form-control-sm @error('description') is-invalid @enderror" placeholder="Summarise why this meets IAS 38 recognition criteria (identifiable, controlled, future economic benefits, reliable measurement).">{{ old('description') }}</textarea>
                                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recognition Criteria Checks (IAS 38 Compliance) -->
                    <div class="card border-info mt-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="bx bx-check-shield me-2"></i>Recognition Criteria Checks (IAS 38 Compliance)</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="recognition_identifiable" value="1" id="recognitionIdentifiable" {{ old('recognition_identifiable', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="recognitionIdentifiable">
                                            <strong>Identifiable:</strong> Asset is separable or arises from contractual/legal rights
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="recognition_control" value="1" id="recognitionControl" {{ old('recognition_control', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="recognitionControl">
                                            <strong>Control:</strong> Entity has power to obtain future economic benefits
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="recognition_future_benefits" value="1" id="recognitionFutureBenefits" {{ old('recognition_future_benefits', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="recognitionFutureBenefits">
                                            <strong>Future Economic Benefits:</strong> Probable that future economic benefits will flow to the entity
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="recognition_reliable_measurement" value="1" id="recognitionReliableMeasurement" {{ old('recognition_reliable_measurement', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="recognitionReliableMeasurement">
                                            <strong>Reliable Measurement:</strong> Cost can be measured reliably
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-light mt-3 mb-0">
                                <small><i class="bx bx-info-circle me-1"></i>All recognition criteria must be met for an intangible asset to be recognized under IAS 38. These checkboxes are checked by default but can be unchecked if any criterion is not met.</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="{{ route('assets.intangible.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bx bx-arrow-back me-1"></i>Back to Register
                        </a>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bx bx-save me-1"></i>Save Intangible Asset
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
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: function(){
            return $(this).data('placeholder') || '';
        }
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-populate source type based on selected category
    $('#assetCategory').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const categoryType = selectedOption.data('type');
        const categoryName = selectedOption.text().split(' - ').pop(); // Get name without code

        if (categoryType) {
            // Map category type to source type
            const typeMapping = {
                'purchased': 'purchased',
                'internally_developed': 'internally_developed',
                'goodwill': 'goodwill',
                'indefinite_life': 'indefinite_life'
            };

            const sourceType = typeMapping[categoryType] || 'other';
            $('#sourceType').val(sourceType).trigger('change');

            // Update info text
            const typeLabel = categoryType.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
            $('#categoryTypeInfo').html(`<strong>${categoryName}</strong> - Type: <span class="badge bg-primary">${typeLabel}</span>`);

            // Auto-check flags based on category type
            if (categoryType === 'goodwill') {
                $('#flagGoodwill').prop('checked', true);
                $('#flagIndefinite').prop('checked', true);
                $('#flagIndefinite').prop('disabled', true);
                $('#usefulLifeMonths').prop('disabled', true).val('');
            } else if (categoryType === 'indefinite_life') {
                $('#flagIndefinite').prop('checked', true);
                $('#flagGoodwill').prop('checked', false);
                $('#flagGoodwill').prop('disabled', false);
                $('#usefulLifeMonths').prop('disabled', true).val('');
            } else {
                $('#flagIndefinite').prop('checked', false);
                $('#flagIndefinite').prop('disabled', false);
                $('#flagGoodwill').prop('checked', false);
                $('#flagGoodwill').prop('disabled', false);
                $('#usefulLifeMonths').prop('disabled', false);
            }
        } else {
            $('#categoryTypeInfo').text('Select a category to auto-populate the source type.');
            $('#flagIndefinite').prop('disabled', false);
            $('#flagGoodwill').prop('disabled', false);
            $('#usefulLifeMonths').prop('disabled', false);
        }
    });

    // Handle flag changes
    $('#flagIndefinite, #flagGoodwill').on('change', function() {
        if ($('#flagIndefinite').is(':checked') || $('#flagGoodwill').is(':checked')) {
            $('#usefulLifeMonths').prop('disabled', true).val('');
        } else {
            $('#usefulLifeMonths').prop('disabled', false);
        }

        // If goodwill is checked, also check indefinite
        if ($('#flagGoodwill').is(':checked')) {
            $('#flagIndefinite').prop('checked', true);
        }
    });

    // Trigger on load if category is pre-selected
    if ($('#assetCategory').val()) {
        $('#assetCategory').trigger('change');
    }

    // Show/hide bank account and amount paid fields based on source type
    function togglePaymentFields() {
        const sourceType = $('#sourceType').val();
        const showPaymentFields = (sourceType === 'purchased' || sourceType === 'internally_developed');
        
        if (showPaymentFields) {
            $('#bankAccountSection').slideDown();
            $('#amountPaidSection').slideDown();
            $('#bankAccountId').prop('required', false); // Optional field
        } else {
            $('#bankAccountSection').slideUp();
            $('#amountPaidSection').slideUp();
            $('#bankAccountId').val('').trigger('change');
            $('#amountPaid').val('');
        }
    }

    // Listen to source type changes
    $('#sourceType').on('change', function() {
        togglePaymentFields();
    });

    // Calculate remaining balance
    $('#totalCost, #amountPaid').on('input', function() {
        const totalCost = parseFloat($('#totalCost').val()) || 0;
        const amountPaid = parseFloat($('#amountPaid').val()) || 0;
        const remaining = totalCost - amountPaid;
        
        if (remaining > 0 && amountPaid > 0) {
            $('#amountPaidSection .form-text').html(`<span class="text-warning"><i class="bx bx-info-circle"></i> Remaining balance: <strong>TZS ${remaining.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong> will be posted to Trade Payable.</span>`);
        } else if (amountPaid > 0 && remaining <= 0) {
            $('#amountPaidSection .form-text').html(`<span class="text-success"><i class="bx bx-check-circle"></i> Full payment - no trade payable will be created.</span>`);
        } else {
            $('#amountPaidSection .form-text').text('Leave blank if full payment. Remaining balance will be posted to Trade Payable.');
        }
    });

    // Initialize payment fields visibility
    togglePaymentFields();
});
</script>
@endpush
