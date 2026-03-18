@extends('layouts.main')

@section('title', 'Create Impairment')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Impairments', 'url' => route('assets.impairments.index'), 'icon' => 'bx bx-error-circle'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-plus me-2"></i>Create Asset Impairment</h6>
            </div>
            <div class="card-body">
                @if($errors->any())
                <div class="alert alert-danger">
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('assets.impairments.store') }}" enctype="multipart/form-data" id="impairment-form">
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
                                    @php
                                        $currentNbv = $a->getCurrentCarryingAmount() ?? $a->current_nbv ?? 0;
                                    @endphp
                                    <option value="{{ $a->id }}" 
                                        {{ (old('asset_id') == $a->id || ($asset && $asset->id == $a->id)) ? 'selected' : '' }}
                                        data-nbv="{{ number_format($currentNbv, 2, '.', '') }}"
                                        data-carrying-amount="{{ number_format($currentNbv, 2, '.', '') }}">
                                        {{ $a->code }} - {{ $a->name }} - {{ number_format($currentNbv, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('asset_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Current Carrying Amount (NBV)</label>
                            <input type="text" id="current-carrying-amount-display" class="form-control bg-light border-start border-primary border-3" readonly value="0.00" style="font-weight: 600; color: #495057; font-size: 1.05rem;">
                            <div class="form-text small text-muted">Auto-filled when asset is selected</div>
                        </div>
                    </div>

                    <!-- Impairment Details -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Impairment Details</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Impairment Date <span class="text-danger">*</span></label>
                            <input type="date" name="impairment_date" class="form-control" 
                                value="{{ old('impairment_date', date('Y-m-d')) }}" required>
                            @error('impairment_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Impairment Type <span class="text-danger">*</span></label>
                            <select name="impairment_type" class="form-select" required>
                                <option value="individual" {{ old('impairment_type') == 'individual' ? 'selected' : '' }}>Individual Asset</option>
                                <option value="cgu" {{ old('impairment_type') == 'cgu' ? 'selected' : '' }}>Cash Generating Unit (CGU)</option>
                            </select>
                            @error('impairment_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Impairment Loss Account</label>
                            <select name="impairment_loss_account_id" class="form-select select2-single">
                                <option value="">Use Category Default</option>
                                @foreach($impairmentLossAccounts as $account)
                                    <option value="{{ $account->id }}" {{ old('impairment_loss_account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->account_code ?? $account->code }} - {{ $account->account_name ?? $account->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('impairment_loss_account_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Impairment Indicators -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Impairment Indicators</h6>
                            <div class="form-text mb-2">Select all indicators that apply:</div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="indicator_physical_damage" 
                                    id="indicator_physical_damage" value="1" {{ old('indicator_physical_damage') ? 'checked' : '' }}>
                                <label class="form-check-label" for="indicator_physical_damage">
                                    Physical Damage
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="indicator_obsolescence" 
                                    id="indicator_obsolescence" value="1" {{ old('indicator_obsolescence') ? 'checked' : '' }}>
                                <label class="form-check-label" for="indicator_obsolescence">
                                    Obsolescence
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="indicator_technological_change" 
                                    id="indicator_technological_change" value="1" {{ old('indicator_technological_change') ? 'checked' : '' }}>
                                <label class="form-check-label" for="indicator_technological_change">
                                    Technological Change
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="indicator_idle_asset" 
                                    id="indicator_idle_asset" value="1" {{ old('indicator_idle_asset') ? 'checked' : '' }}>
                                <label class="form-check-label" for="indicator_idle_asset">
                                    Idle Asset
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="indicator_market_decline" 
                                    id="indicator_market_decline" value="1" {{ old('indicator_market_decline') ? 'checked' : '' }}>
                                <label class="form-check-label" for="indicator_market_decline">
                                    Market Decline
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="indicator_legal_regulatory" 
                                    id="indicator_legal_regulatory" value="1" {{ old('indicator_legal_regulatory') ? 'checked' : '' }}>
                                <label class="form-check-label" for="indicator_legal_regulatory">
                                    Legal/Regulatory Changes
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Other Indicators</label>
                            <textarea name="other_indicators" class="form-control" rows="2">{{ old('other_indicators') }}</textarea>
                            @error('other_indicators')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Recoverable Amount -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Recoverable Amount Calculation</h6>
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>
                                <strong>Recoverable Amount = Higher of:</strong> Fair Value Less Costs to Sell OR Value in Use
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fair Value Less Costs to Sell</label>
                            <input type="number" name="fair_value_less_costs" class="form-control" 
                                step="0.01" min="0" value="{{ old('fair_value_less_costs') }}" 
                                id="fair_value_less_costs">
                            <div class="form-text">Market value minus disposal costs</div>
                            @error('fair_value_less_costs')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Value in Use (Manual Entry)</label>
                            <input type="number" name="value_in_use" class="form-control" 
                                step="0.01" min="0" value="{{ old('value_in_use') }}" 
                                id="value_in_use_manual">
                            <div class="form-text">Or calculate using cash flow projections below</div>
                            @error('value_in_use')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Value in Use Calculation -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Value in Use Calculation (Optional)</h6>
                            <div class="form-text mb-2">Enter future cash flow projections to calculate value in use automatically</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Discount Rate (%)</label>
                            <input type="number" name="discount_rate" class="form-control" 
                                step="0.01" min="0" max="100" value="{{ old('discount_rate') }}" 
                                id="discount_rate">
                            <div class="form-text">Discount rate for present value calculation</div>
                            @error('discount_rate')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Cash Flow Projections (Annual)</label>
                            <div id="cash-flow-container">
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Year 1</span>
                                    <input type="number" name="cash_flow_projections[]" class="form-control cash-flow-input" 
                                        step="0.01" min="0" placeholder="Cash flow amount">
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Year 2</span>
                                    <input type="number" name="cash_flow_projections[]" class="form-control cash-flow-input" 
                                        step="0.01" min="0" placeholder="Cash flow amount">
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Year 3</span>
                                    <input type="number" name="cash_flow_projections[]" class="form-control cash-flow-input" 
                                        step="0.01" min="0" placeholder="Cash flow amount">
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-cash-flow-year">
                                <i class="bx bx-plus me-1"></i>Add Year
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info mt-2" id="calculate-value-in-use">
                                <i class="bx bx-calculator me-1"></i>Calculate Value in Use
                            </button>
                            <div id="calculated-value-in-use" class="mt-2 alert alert-success" style="display: none;">
                                <strong>Calculated Value in Use:</strong> <span id="calculated-amount"></span>
                            </div>
                            @error('cash_flow_projections.*')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Asset Adjustments -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Asset Adjustments (Optional)</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Useful Life After (Months)</label>
                            <input type="number" name="useful_life_after" class="form-control" 
                                min="1" value="{{ old('useful_life_after') }}">
                            @error('useful_life_after')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Residual Value After</label>
                            <input type="number" name="residual_value_after" class="form-control" 
                                step="0.01" min="0" value="{{ old('residual_value_after') }}">
                            @error('residual_value_after')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Documentation -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Documentation</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Impairment Test Report</label>
                            <input type="file" name="impairment_test_report" class="form-control" accept=".pdf,.doc,.docx">
                            <div class="form-text">Upload impairment test report</div>
                            @error('impairment_test_report')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Additional Attachments</label>
                            <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <div class="form-text">Upload supporting documents</div>
                            @error('attachments.*')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Calculation Preview -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="alert alert-info" id="calculation-preview" style="display: none;">
                                <h6 class="alert-heading">Impairment Calculation Preview</h6>
                                <div id="preview-content"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('assets.impairments.index') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Create Impairment
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
        width: '100%'
    });

    let yearCount = 3;

    // Add cash flow year
    $('#add-cash-flow-year').on('click', function() {
        yearCount++;
        const newInput = `
            <div class="input-group mb-2">
                <span class="input-group-text">Year ${yearCount}</span>
                <input type="number" name="cash_flow_projections[]" class="form-control cash-flow-input" 
                    step="0.01" min="0" placeholder="Cash flow amount">
            </div>
        `;
        $('#cash-flow-container').append(newInput);
    });

    // Calculate value in use
    $('#calculate-value-in-use').on('click', function() {
        const discountRate = parseFloat($('#discount_rate').val()) || 0;
        const cashFlows = [];
        
        $('.cash-flow-input').each(function() {
            const value = parseFloat($(this).val()) || 0;
            if (value > 0) {
                cashFlows.push(value);
            }
        });

        if (discountRate <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please enter a valid discount rate'
            });
            return;
        }

        if (cashFlows.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please enter at least one cash flow projection'
            });
            return;
        }

        let valueInUse = 0;
        cashFlows.forEach((cashFlow, index) => {
            const year = index + 1;
            const pv = cashFlow / Math.pow(1 + (discountRate / 100), year);
            valueInUse += pv;
        });

        $('#calculated-amount').text(valueInUse.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#value_in_use_manual').val(valueInUse.toFixed(2));
        $('#calculated-value-in-use').show();
    });

    // Calculate impairment preview
    function calculatePreview() {
        const carryingAmount = parseFloat($('#current-carrying-amount').text().replace(/,/g, '')) || 0;
        const fairValueLessCosts = parseFloat($('#fair_value_less_costs').val()) || 0;
        const valueInUse = parseFloat($('#value_in_use_manual').val()) || 0;
        
        if (carryingAmount > 0 && (fairValueLessCosts > 0 || valueInUse > 0)) {
            const recoverableAmount = Math.max(fairValueLessCosts, valueInUse);
            const impairmentLoss = Math.max(0, carryingAmount - recoverableAmount);
            
            const preview = $('#calculation-preview');
            const content = $('#preview-content');
            
            let html = '<table class="table table-sm table-borderless mb-0">';
            html += '<tr><td>Carrying Amount:</td><td class="text-end"><strong>' + carryingAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></td></tr>';
            html += '<tr><td>Fair Value Less Costs:</td><td class="text-end">' + (fairValueLessCosts > 0 ? fairValueLessCosts.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-') + '</td></tr>';
            html += '<tr><td>Value in Use:</td><td class="text-end">' + (valueInUse > 0 ? valueInUse.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-') + '</td></tr>';
            html += '<tr><td><strong>Recoverable Amount:</strong></td><td class="text-end"><strong>' + recoverableAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></td></tr>';
            
            if (impairmentLoss > 0) {
                html += '<tr class="table-danger"><td><strong>Impairment Loss:</strong></td><td class="text-end text-danger"><strong>' + impairmentLoss.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></td></tr>';
            } else {
                html += '<tr><td><strong>Impairment Loss:</strong></td><td class="text-end text-success"><strong>No Impairment</strong></td></tr>';
            }
            
            html += '</table>';
            content.html(html);
            preview.show();
        } else {
            $('#calculation-preview').hide();
        }
    }

    $('#fair_value_less_costs, #value_in_use_manual').on('input', calculatePreview);

    // Update carrying amount when asset changes
    function updateCarryingAmount() {
        const selectedOption = $('#asset_id').find('option:selected');
        const assetId = selectedOption.val();
        const carryingAmount = parseFloat(selectedOption.attr('data-carrying-amount')) || 0;
        
        if (assetId && carryingAmount > 0) {
            $('#current-carrying-amount-display').val(carryingAmount.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
            calculatePreview();
        } else {
            $('#current-carrying-amount-display').val('0.00');
        }
    }

    // Listen to both Select2 and standard change events
    $('#asset_id').on('select2:select', function(e) {
        updateCarryingAmount();
    });

    $('#asset_id').on('change', function() {
        updateCarryingAmount();
    });

    // Update on page load if asset is pre-selected
    setTimeout(function() {
        if ($('#asset_id').val()) {
            updateCarryingAmount();
        }
    }, 200);
});
</script>
@endpush

