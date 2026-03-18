@extends('layouts.main')

@section('title', 'Create Asset Revaluation')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Revaluations', 'url' => route('assets.revaluations.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card border-top border-0 border-4 border-primary">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="bx bx-trending-up fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">Create Asset Revaluation</h5>
                        <small class="text-muted">Revalue multiple assets with their fair market values</small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('assets.revaluations.store') }}" enctype="multipart/form-data" id="revaluation-form">
                    @csrf

                    <!-- Revaluation Header Information -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bx bx-info-circle me-2"></i>Revaluation Information
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Revaluation Date <span class="text-danger">*</span></label>
                            <input type="date" name="revaluation_date" class="form-control" 
                                value="{{ old('revaluation_date', date('Y-m-d')) }}" required>
                            @error('revaluation_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Valuation Model <span class="text-danger">*</span></label>
                            <select name="valuation_model" class="form-select" required>
                                <option value="cost" {{ old('valuation_model') == 'cost' ? 'selected' : '' }}>Cost Model</option>
                                <option value="revaluation" {{ old('valuation_model') == 'revaluation' ? 'selected' : '' }}>Revaluation Model</option>
                            </select>
                            @error('valuation_model')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Reason for Revaluation <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control" rows="2" 
                                placeholder="e.g., Market value adjustment, inflation, asset condition improvement" required>{{ old('reason') }}</textarea>
                            @error('reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Assets Section -->
                    <div class="card border shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold">
                                    <i class="bx bx-building me-2"></i>Assets to Revalue
                                </h6>
                                <button type="button" class="btn btn-primary btn-sm" id="add-asset-btn">
                                    <i class="bx bx-plus me-1"></i>Add Asset
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="assets-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="30%">Asset</th>
                                            <th width="15%">Category</th>
                                            <th width="15%">Current Carrying Amount</th>
                                            <th width="15%">Fair Value <span class="text-danger">*</span></th>
                                            <th width="15%">Revaluation Difference</th>
                                            <th width="10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="assets-tbody">
                                        <tr id="no-assets-row">
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="bx bx-info-circle fs-4 d-block mb-2"></i>
                                                <span>No assets added yet. Click "Add Asset" to begin.</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-light" id="summary-footer" style="display: none;">
                                        <tr>
                                            <td colspan="3" class="text-end fw-bold">Total:</td>
                                            <td class="fw-bold" id="total-fair-value">0.00</td>
                                            <td class="fw-bold" id="total-difference">0.00</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Valuer Information -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bx bx-user me-2"></i>Valuer Information (Optional)
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Valuer Name</label>
                            <input type="text" name="valuer_name" class="form-control" value="{{ old('valuer_name') }}" placeholder="Enter valuer name">
                            @error('valuer_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Valuer License</label>
                            <input type="text" name="valuer_license" class="form-control" value="{{ old('valuer_license') }}" placeholder="License number">
                            @error('valuer_license')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Valuer Company</label>
                            <input type="text" name="valuer_company" class="form-control" value="{{ old('valuer_company') }}" placeholder="Company name">
                            @error('valuer_company')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Valuation Report Reference</label>
                            <input type="text" name="valuation_report_ref" class="form-control" value="{{ old('valuation_report_ref') }}" placeholder="Report reference number">
                            @error('valuation_report_ref')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Valuation Report (PDF/DOC)</label>
                            <input type="file" name="valuation_report" class="form-control" accept=".pdf,.doc,.docx">
                            <div class="form-text">Upload valuation report document (max 10MB)</div>
                            @error('valuation_report')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Asset Adjustments -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bx bx-cog me-2"></i>Asset Adjustments (Optional)
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Useful Life After (Months)</label>
                            <input type="number" name="useful_life_after" class="form-control" 
                                min="1" value="{{ old('useful_life_after') }}" placeholder="Enter months">
                            <div class="form-text">Updated useful life after revaluation</div>
                            @error('useful_life_after')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Residual Value After</label>
                            <input type="number" name="residual_value_after" class="form-control" 
                                step="0.01" min="0" value="{{ old('residual_value_after') }}" placeholder="0.00">
                            <div class="form-text">Updated residual value after revaluation</div>
                            @error('residual_value_after')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Revaluation Reserve Account</label>
                            <select name="revaluation_reserve_account_id" class="form-select select2-single">
                                <option value="">Use Category Default</option>
                                @foreach($reserveAccounts as $account)
                                    <option value="{{ $account->id }}" {{ old('revaluation_reserve_account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->account_code ?? $account->code }} - {{ $account->account_name ?? $account->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('revaluation_reserve_account_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="bx bx-paperclip me-2"></i>Attachments (Optional)
                            </h6>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Additional Documents</label>
                            <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <div class="form-text">Upload supporting documents (multiple files allowed, max 5MB each)</div>
                            @error('attachments.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="{{ route('assets.revaluations.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
                            <i class="bx bx-save me-1"></i>Create Revaluation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Asset Modal -->
<div class="modal fade" id="addAssetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bx bx-plus me-2"></i>Add Asset for Revaluation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="add-asset-form">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Asset <span class="text-danger">*</span></label>
                        <select id="asset-select" class="form-select select2-single" required>
                            <option value="">Choose an asset...</option>
                            @foreach($assets as $a)
                                <option value="{{ $a->id }}" 
                                    data-code="{{ $a->code }}"
                                    data-name="{{ $a->name }}"
                                    data-category="{{ $a->category->name ?? 'N/A' }}"
                                    data-cost="{{ $a->purchase_cost ?? 0 }}"
                                    data-nbv="{{ $a->current_nbv ?? 0 }}"
                                    data-carrying-amount="{{ $a->getCurrentCarryingAmount() ?? $a->purchase_cost ?? 0 }}">
                                    {{ $a->code }} - {{ $a->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Carrying Amount</label>
                        <input type="text" id="current-carrying-amount-display" class="form-control" readonly value="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fair Value <span class="text-danger">*</span></label>
                        <input type="number" id="fair-value-input" class="form-control" 
                            step="0.01" min="0" placeholder="0.00" required>
                        <div class="form-text">Enter the fair market value of the asset</div>
                    </div>
                    <div id="difference-preview" class="alert alert-info mb-0" style="display: none;">
                        <strong>Revaluation Difference:</strong> <span id="difference-amount">0.00</span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="add-asset-confirm">
                    <i class="bx bx-check me-1"></i>Add Asset
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for non-modal selects
    $('.select2-single').not('#asset-select').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    let assetCounter = 0;
    const addedAssetIds = new Set();

    // Initialize Select2 for modal asset select when modal is shown
    $('#addAssetModal').on('shown.bs.modal', function() {
        // Destroy existing Select2 if it exists
        if ($('#asset-select').hasClass('select2-hidden-accessible')) {
            $('#asset-select').select2('destroy');
        }
        
        // Re-initialize Select2 with proper modal settings
        $('#asset-select').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#addAssetModal'),
            placeholder: 'Choose an asset...',
            allowClear: true
        });
    });

    // Open modal when Add Asset button is clicked
    $('#add-asset-btn').on('click', function() {
        $('#addAssetModal').modal('show');
        $('#asset-select').val(null).trigger('change');
        $('#fair-value-input').val('');
        $('#current-carrying-amount-display').val('0.00');
        $('#difference-preview').hide();
    });

    // Update carrying amount when asset is selected
    $('#asset-select').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const assetId = selectedOption.val();
        
        if (assetId && !addedAssetIds.has(assetId)) {
            const carryingAmount = parseFloat(selectedOption.data('carrying-amount')) || 0;
            $('#current-carrying-amount-display').val(carryingAmount.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
        } else if (addedAssetIds.has(assetId)) {
            Swal.fire({
                icon: 'warning',
                title: 'Asset Already Added',
                text: 'This asset has already been added to the revaluation.',
                confirmButtonText: 'OK'
            });
            $(this).val(null).trigger('change');
            $('#current-carrying-amount-display').val('0.00');
        } else {
            $('#current-carrying-amount-display').val('0.00');
        }
    });

    // Calculate difference when fair value changes
    $('#fair-value-input').on('input', function() {
        const fairValue = parseFloat($(this).val()) || 0;
        const carryingAmount = parseFloat($('#current-carrying-amount-display').val().replace(/,/g, '')) || 0;
        
        if (fairValue > 0 && carryingAmount > 0) {
            const difference = fairValue - carryingAmount;
            $('#difference-amount').text(difference.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
            
            if (difference > 0) {
                $('#difference-amount').removeClass('text-danger').addClass('text-success');
                $('#difference-amount').text('+' + $('#difference-amount').text());
            } else if (difference < 0) {
                $('#difference-amount').removeClass('text-success').addClass('text-danger');
            } else {
                $('#difference-amount').removeClass('text-success text-danger');
            }
            
            $('#difference-preview').show();
        } else {
            $('#difference-preview').hide();
        }
    });

    // Add asset to table
    $('#add-asset-confirm').on('click', function() {
        const assetSelect = $('#asset-select');
        const selectedOption = assetSelect.find('option:selected');
        const assetId = selectedOption.val();
        
        if (!assetId) {
            Swal.fire({
                icon: 'warning',
                title: 'Asset Required',
                text: 'Please select an asset.',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        if (addedAssetIds.has(assetId)) {
            Swal.fire({
                icon: 'warning',
                title: 'Asset Already Added',
                text: 'This asset has already been added.',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        const fairValue = parseFloat($('#fair-value-input').val());
        if (!fairValue || fairValue <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Fair Value',
                text: 'Please enter a valid fair value.',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        const assetCode = selectedOption.data('code');
        const assetName = selectedOption.data('name');
        const category = selectedOption.data('category');
        const carryingAmount = parseFloat(selectedOption.data('carrying-amount')) || 0;
        const difference = fairValue - carryingAmount;
        
        // Add to table
        const row = `
            <tr data-asset-id="${assetId}">
                <td>
                    <strong>${assetCode}</strong><br>
                    <small class="text-muted">${assetName}</small>
                    <input type="hidden" name="assets[${assetCounter}][asset_id]" value="${assetId}">
                    <input type="hidden" name="assets[${assetCounter}][carrying_amount]" value="${carryingAmount}">
                </td>
                <td><span class="badge bg-light text-dark">${category}</span></td>
                <td class="text-end">${carryingAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td>
                    <input type="number" 
                           name="assets[${assetCounter}][fair_value]" 
                           class="form-control form-control-sm fair-value-input" 
                           step="0.01" 
                           min="0" 
                           value="${fairValue.toFixed(2)}"
                           data-carrying="${carryingAmount}"
                           required>
                </td>
                <td class="text-end difference-display">
                    ${difference >= 0 ? 
                        '<span class="text-success">+' + difference.toFixed(2) + '</span>' : 
                        '<span class="text-danger">' + difference.toFixed(2) + '</span>'
                    }
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-asset">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#no-assets-row').remove();
        $('#assets-tbody').append(row);
        $('#summary-footer').show();
        addedAssetIds.add(assetId);
        assetCounter++;
        
        updateSummary();
        updateSubmitButton();
        
        // Close modal and reset
        $('#addAssetModal').modal('hide');
        $('#asset-select').val(null).trigger('change');
        $('#fair-value-input').val('');
        $('#current-carrying-amount-display').val('0.00');
        $('#difference-preview').hide();
    });

    // Remove asset from table
    $(document).on('click', '.remove-asset', function() {
        const row = $(this).closest('tr');
        const assetId = row.data('asset-id');
        addedAssetIds.delete(assetId);
        row.remove();
        
        if ($('#assets-tbody tr').length === 0) {
            $('#assets-tbody').append('<tr id="no-assets-row"><td colspan="6" class="text-center text-muted py-4"><i class="bx bx-info-circle fs-4 d-block mb-2"></i><span>No assets added yet. Click "Add Asset" to begin.</span></td></tr>');
            $('#summary-footer').hide();
        }
        
        updateSummary();
        updateSubmitButton();
    });

    // Update difference when fair value changes in table
    $(document).on('input', '.fair-value-input', function() {
        const row = $(this).closest('tr');
        const fairValue = parseFloat($(this).val()) || 0;
        const carryingAmount = parseFloat($(this).data('carrying')) || 0;
        const difference = fairValue - carryingAmount;
        
        const differenceCell = row.find('.difference-display');
        if (difference >= 0) {
            differenceCell.html('<span class="text-success">+' + difference.toFixed(2) + '</span>');
        } else {
            differenceCell.html('<span class="text-danger">' + difference.toFixed(2) + '</span>');
        }
        
        updateSummary();
    });

    // Update summary totals
    function updateSummary() {
        let totalFairValue = 0;
        let totalDifference = 0;
        
        $('#assets-tbody tr').each(function() {
            const fairValue = parseFloat($(this).find('.fair-value-input').val()) || 0;
            const carryingAmount = parseFloat($(this).find('input[name*="[carrying_amount]"]').val()) || 0;
            totalFairValue += fairValue;
            totalDifference += (fairValue - carryingAmount);
        });
        
        $('#total-fair-value').text(totalFairValue.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
        
        const differenceHtml = totalDifference >= 0 ? 
            '<span class="text-success">+' + totalDifference.toFixed(2) + '</span>' : 
            '<span class="text-danger">' + totalDifference.toFixed(2) + '</span>';
        $('#total-difference').html(differenceHtml);
    }

    // Update submit button state
    function updateSubmitButton() {
        const hasAssets = $('#assets-tbody tr').length > 0 && !$('#no-assets-row').length;
        $('#submit-btn').prop('disabled', !hasAssets);
    }

    // Form validation before submit
    $('#revaluation-form').on('submit', function(e) {
        if ($('#assets-tbody tr').length === 0 || $('#no-assets-row').length) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'No Assets Added',
                text: 'Please add at least one asset to revalue.',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        // Validate all fair values
        let isValid = true;
        $('.fair-value-input').each(function() {
            const value = parseFloat($(this).val());
            if (!value || value <= 0) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Invalid Fair Values',
                text: 'Please ensure all assets have valid fair values.',
                confirmButtonText: 'OK'
            });
            return false;
        }
    });
});
</script>
@endpush
