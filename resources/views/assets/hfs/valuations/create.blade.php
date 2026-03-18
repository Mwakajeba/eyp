@extends('layouts.main')

@section('title', 'Record HFS Valuation')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Held for Sale', 'url' => route('assets.hfs.requests.index'), 'icon' => 'bx bx-transfer'],
            ['label' => 'Valuation', 'url' => '#', 'icon' => 'bx bx-calculator']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Record HFS Valuation - {{ $hfsRequest->request_no }}</h6>
            </div>
            <div class="card-body">
                <!-- Current Status -->
                <div class="alert alert-info mb-4">
                    <h6 class="mb-2">Current Status</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Carrying Amount at Classification:</strong><br>
                            {{ number_format($hfsRequest->total_carrying_amount, 2) }}
                        </div>
                        <div class="col-md-4">
                            <strong>Current Carrying Amount:</strong><br>
                            {{ number_format($hfsRequest->current_total_carrying_amount, 2) }}
                        </div>
                        @if($hfsRequest->latestValuation)
                        <div class="col-md-4">
                            <strong>Last Valuation Date:</strong><br>
                            {{ $hfsRequest->latestValuation->valuation_date->format('d M Y') }}
                        </div>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('assets.hfs.valuations.store', $encodedId) }}" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Valuation Date <span class="text-danger">*</span></label>
                            <input type="date" name="valuation_date" class="form-control" 
                                value="{{ old('valuation_date', date('Y-m-d')) }}" required>
                            @error('valuation_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Current Carrying Amount</label>
                            <input type="text" class="form-control" 
                                value="{{ number_format($hfsRequest->current_total_carrying_amount, 2) }}" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Fair Value <span class="text-danger">*</span></label>
                            <input type="number" name="fair_value" id="fair_value" class="form-control" 
                                step="0.01" min="0" value="{{ old('fair_value', $hfsRequest->expected_fair_value ?? 0) }}" required>
                            @error('fair_value')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Costs to Sell <span class="text-danger">*</span></label>
                            <input type="number" name="costs_to_sell" id="costs_to_sell" class="form-control" 
                                step="0.01" min="0" value="{{ old('costs_to_sell', $hfsRequest->expected_costs_to_sell ?? 0) }}" required>
                            @error('costs_to_sell')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">FV Less Costs (Calculated)</label>
                            <input type="text" id="fv_less_costs" class="form-control" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Suggested Impairment</label>
                            <input type="text" id="suggested_impairment" class="form-control" readonly>
                        </div>

                        <!-- Valuator Information -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mt-3">Valuator Information</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Valuator Name</label>
                            <input type="text" name="valuator_name" class="form-control" value="{{ old('valuator_name') }}">
                            @error('valuator_name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Valuator License</label>
                            <input type="text" name="valuator_license" class="form-control" value="{{ old('valuator_license') }}">
                            @error('valuator_license')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Valuator Company</label>
                            <input type="text" name="valuator_company" class="form-control" value="{{ old('valuator_company') }}">
                            @error('valuator_company')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Report Reference</label>
                            <input type="text" name="report_ref" class="form-control" value="{{ old('report_ref') }}">
                            @error('report_ref')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valuation Report (File)</label>
                            <input type="file" name="valuation_report_path" class="form-control" accept=".pdf,.doc,.docx">
                            @error('valuation_report_path')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <!-- Override Option -->
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_override" id="is_override" value="1">
                                <label class="form-check-label" for="is_override">
                                    Override calculated impairment (requires approval)
                                </label>
                            </div>
                        </div>
                        <div class="col-12" id="override-reason-field" style="display: none;">
                            <label class="form-label">Override Reason <span class="text-danger">*</span></label>
                            <textarea name="override_reason" class="form-control" rows="3"></textarea>
                            @error('override_reason')<div class="text-danger small">{{ $message }}</div>@enderror
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
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Record Valuation & Post Impairment
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
    const carryingAmount = {{ $hfsRequest->current_total_carrying_amount }};

    function calculateImpairment() {
        const fairValue = parseFloat($('#fair_value').val()) || 0;
        const costsToSell = parseFloat($('#costs_to_sell').val()) || 0;
        const fvLessCosts = fairValue - costsToSell;
        
        $('#fv_less_costs').val(formatNumber(fvLessCosts));

        if (fvLessCosts < carryingAmount) {
            const impairment = carryingAmount - fvLessCosts;
            $('#suggested_impairment').val(formatNumber(impairment)).addClass('text-danger');
        } else {
            $('#suggested_impairment').val('0.00 (No impairment)').removeClass('text-danger');
        }
    }

    $('#fair_value, #costs_to_sell').on('input', calculateImpairment);

    $('#is_override').on('change', function() {
        if ($(this).is(':checked')) {
            $('#override-reason-field').show();
            $('textarea[name="override_reason"]').prop('required', true);
        } else {
            $('#override-reason-field').hide();
            $('textarea[name="override_reason"]').prop('required', false);
        }
    });

    function formatNumber(num) {
        return parseFloat(num).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
});
</script>
@endpush

