@extends('layouts.main')

@section('title', 'Create Impairment Reversal')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Impairments', 'url' => route('assets.impairments.index'), 'icon' => 'bx bx-error-circle'],
            ['label' => 'Create Reversal', 'url' => '#', 'icon' => 'bx bx-undo']
        ]" />

        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bx bx-undo me-2"></i>Create Impairment Reversal</h6>
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

                <!-- Original Impairment Info -->
                <div class="alert alert-info mb-4">
                    <h6 class="alert-heading">Original Impairment Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Impairment Number:</strong> {{ $originalImpairment->impairment_number }}<br>
                            <strong>Date:</strong> {{ $originalImpairment->impairment_date->format('d M Y') }}<br>
                            <strong>Original Impairment Loss:</strong> {{ number_format($originalImpairment->impairment_loss, 2) }}
                        </div>
                        <div class="col-md-6">
                            <strong>Total Reversed:</strong> {{ number_format($originalImpairment->total_reversals, 2) }}<br>
                            <strong>Remaining Reversible:</strong> 
                            <span class="text-success">{{ number_format($originalImpairment->remaining_reversible_amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('assets.impairments.store-reversal', \Vinkla\Hashids\Facades\Hashids::encode($originalImpairment->id)) }}" 
                      enctype="multipart/form-data" id="reversal-form">
                    @csrf

                    <!-- Reversal Details -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">Reversal Details</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reversal Date <span class="text-danger">*</span></label>
                            <input type="date" name="reversal_date" class="form-control" 
                                value="{{ old('reversal_date', date('Y-m-d')) }}" required>
                            @error('reversal_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reversal Amount <span class="text-danger">*</span></label>
                            <input type="number" name="reversal_amount" class="form-control" 
                                step="0.01" min="0.01" 
                                max="{{ $originalImpairment->remaining_reversible_amount }}"
                                value="{{ old('reversal_amount') }}" 
                                required
                                id="reversal_amount">
                            <div class="form-text">
                                Maximum reversible: <strong>{{ number_format($originalImpairment->remaining_reversible_amount, 2) }}</strong>
                            </div>
                            @error('reversal_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Impairment Reversal Account</label>
                            <select name="impairment_reversal_account_id" class="form-select select2-single">
                                <option value="">Use Category Default</option>
                                @foreach($impairmentReversalAccounts as $account)
                                    <option value="{{ $account->id }}" {{ old('impairment_reversal_account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->account_code ?? $account->code }} - {{ $account->account_name ?? $account->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('impairment_reversal_account_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                            <div class="form-text">Explain the reason for the reversal (e.g., asset value recovered, improved market conditions)</div>
                            @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Calculation Preview -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="alert alert-success" id="calculation-preview" style="display: none;">
                                <h6 class="alert-heading">Reversal Calculation Preview</h6>
                                <div id="preview-content"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('assets.impairments.show', \Vinkla\Hashids\Facades\Hashids::encode($originalImpairment->id)) }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-save me-1"></i>Create Reversal
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

    const maxReversible = {{ $originalImpairment->remaining_reversible_amount }};
    const originalLoss = {{ $originalImpairment->impairment_loss }};
    const totalReversed = {{ $originalImpairment->total_reversals }};

    // Calculate preview when reversal amount changes
    $('#reversal_amount').on('input', function() {
        const reversalAmount = parseFloat($(this).val()) || 0;
        
        if (reversalAmount > maxReversible) {
            $(this).addClass('is-invalid');
            $('#calculation-preview').hide();
            return;
        } else {
            $(this).removeClass('is-invalid');
        }

        if (reversalAmount > 0) {
            const newTotalReversed = totalReversed + reversalAmount;
            const remainingAfter = maxReversible - reversalAmount;
            
            const preview = $('#calculation-preview');
            const content = $('#preview-content');
            
            let html = '<table class="table table-sm table-borderless mb-0">';
            html += '<tr><td>Original Impairment Loss:</td><td class="text-end"><strong>' + originalLoss.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></td></tr>';
            html += '<tr><td>Previously Reversed:</td><td class="text-end">' + totalReversed.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td></tr>';
            html += '<tr><td>This Reversal:</td><td class="text-end text-success"><strong>+' + reversalAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></td></tr>';
            html += '<tr><td>Total Reversed After:</td><td class="text-end"><strong>' + newTotalReversed.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></td></tr>';
            html += '<tr><td>Remaining Reversible:</td><td class="text-end">' + remainingAfter.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td></tr>';
            html += '</table>';
            content.html(html);
            preview.show();
        } else {
            $('#calculation-preview').hide();
        }
    });

    // Validate on form submit
    $('#reversal-form').on('submit', function(e) {
        const reversalAmount = parseFloat($('#reversal_amount').val()) || 0;
        if (reversalAmount > maxReversible) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Invalid Amount',
                text: 'Reversal amount cannot exceed ' + maxReversible.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})
            });
            return false;
        }
    });
});
</script>
@endpush

