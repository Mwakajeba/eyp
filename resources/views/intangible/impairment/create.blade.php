@extends('layouts.main')

@section('title', 'Intangible Impairment Test')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Intangible Assets', 'url' => route('assets.intangible.index'), 'icon' => 'bx bx-brain'],
            ['label' => 'Impairment Test', 'url' => '#', 'icon' => 'bx bx-trending-down']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1"><i class="bx bx-trending-down me-2 text-danger"></i>Intangible Impairment Test</h5>
                        <p class="text-muted mb-0 small">Compare carrying amount vs recoverable amount and recognise IAS 36 impairment losses.</p>
                    </div>
                    <span class="badge bg-light text-danger border small">
                        <i class="bx bx-check-shield me-1"></i>IAS 36 Compliant
                    </span>
                </div>
            </div>
            <div class="card-body border-top">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="bg-light rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center mb-1">
                                <div class="avatar-sm flex-shrink-0 me-2">
                                    <span class="avatar-title bg-primary text-white rounded-2">
                                        <i class="bx bx-line-chart-down"></i>
                                    </span>
                                </div>
                                <div>
                                    <div class="small fw-semibold text-dark">Trigger-based Tests</div>
                                    <div class="text-muted extra-small">Use when indicators exist: obsolescence, poor performance, legal or regulatory changes.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center mb-1">
                                <div class="avatar-sm flex-shrink-0 me-2">
                                    <span class="avatar-title bg-info text-white rounded-2">
                                        <i class="bx bx-calendar-check"></i>
                                    </span>
                                </div>
                                <div>
                                    <div class="small fw-semibold text-dark">Annual Tests</div>
                                    <div class="text-muted extra-small">Mandatory at least annually for goodwill and indefinite-life intangibles.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center mb-1">
                                <div class="avatar-sm flex-shrink-0 me-2">
                                    <span class="avatar-title bg-warning text-white rounded-2">
                                        <i class="bx bx-analyse"></i>
                                    </span>
                                </div>
                                <div>
                                    <div class="small fw-semibold text-dark">Recoverable Amount</div>
                                    <div class="text-muted extra-small">Higher of Value in Use and Fair Value Less Costs of Disposal.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <div class="card-body">
                <form method="POST" action="{{ route('assets.intangible.impairments.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small">Intangible Asset<span class="text-danger">*</span></label>
                            <select name="intangible_asset_id" id="intangible_asset_id" class="form-select form-select-sm select2-single @error('intangible_asset_id') is-invalid @enderror" data-placeholder="Select intangible asset">
                                <option value=""></option>
                                @foreach($assets as $asset)
                                    @php
                                        $nbv = $asset->nbv ?? 0;
                                        if ($nbv === null || $nbv === '') {
                                            $asset->recalculateNbv();
                                            $nbv = $asset->nbv ?? 0;
                                        }
                                    @endphp
                                    <option value="{{ $asset->id }}" 
                                        data-nbv="{{ number_format($nbv, 2, '.', '') }}"
                                        {{ (old('intangible_asset_id', $selectedAssetId) == $asset->id ? 'selected' : '') }}>
                                        {{ $asset->code }} - {{ $asset->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('intangible_asset_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Current Carrying Amount (NBV)</label>
                            <input type="text" id="current-carrying-amount-display" class="form-control form-control-sm" readonly value="0.00" style="background-color: #f8f9fa; font-weight: 600; color: #495057;">
                            <div class="form-text small">Auto-filled when asset is selected</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Impairment Date<span class="text-danger">*</span></label>
                            <input type="date" name="impairment_date" value="{{ old('impairment_date', now()->toDateString()) }}" class="form-control form-control-sm @error('impairment_date') is-invalid @enderror">
                            @error('impairment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small">Method<span class="text-danger">*</span></label>
                            <select name="method" class="form-select form-select-sm select2-single @error('method') is-invalid @enderror">
                                <option value="value_in_use" {{ old('method') === 'value_in_use' ? 'selected' : '' }}>Value in Use</option>
                                <option value="fair_value_less_costs" {{ old('method') === 'fair_value_less_costs' ? 'selected' : '' }}>Fair Value Less Costs of Disposal</option>
                            </select>
                            @error('method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small">Recoverable Amount<span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="recoverable_amount" value="{{ old('recoverable_amount') }}" class="form-control form-control-sm @error('recoverable_amount') is-invalid @enderror">
                            @error('recoverable_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text small">Higher of Value in Use and Fair Value Less Costs of Disposal.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small">Key Assumptions / Notes</label>
                            <textarea name="assumptions" rows="3" class="form-control form-control-sm @error('assumptions') is-invalid @enderror">{{ old('assumptions') }}</textarea>
                            @error('assumptions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text small">Document discount rate, cash flow forecasts, market data, or other key inputs used.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="{{ route('assets.intangible.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bx bx-arrow-back me-1"></i>Back to Register
                        </a>
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bx bx-save me-1"></i>Record Impairment
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
    // Function to update carrying amount
    function updateCarryingAmount() {
        const select = $('#intangible_asset_id');
        const assetId = select.val();
        
        if (!assetId) {
            $('#current-carrying-amount-display').val('0.00');
            return;
        }
        
        // Get the option element
        const selectedOption = select.find('option[value="' + assetId + '"]');
        
        // Try both data attribute methods
        let carryingAmount = selectedOption.attr('data-nbv') || selectedOption.data('nbv') || '0';
        carryingAmount = parseFloat(carryingAmount) || 0;
        
        // Format and display
        if (carryingAmount >= 0) {
            $('#current-carrying-amount-display').val(carryingAmount.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
        } else {
            $('#current-carrying-amount-display').val('0.00');
        }
    }

    // Initialize Select2 for asset select first
    $('#intangible_asset_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select intangible asset'
    });

    // Update carrying amount when asset is selected (Select2 event)
    $('#intangible_asset_id').on('select2:select', function(e) {
        updateCarryingAmount();
    });

    // Also listen to standard change event as fallback
    $('#intangible_asset_id').on('change', function() {
        updateCarryingAmount();
    });

    // Initialize other Select2 fields
    $('.select2-single').not('#intangible_asset_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: function(){
            return $(this).data('placeholder') || '';
        }
    });

    // Trigger on page load if asset is pre-selected
    setTimeout(function() {
        updateCarryingAmount();
    }, 400);
});
</script>
@endpush
