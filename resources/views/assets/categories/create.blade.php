@extends('layouts.main')

@section('title', 'New Asset Category')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-cabinet'],
            ['label' => 'Categories', 'url' => route('assets.categories.index'), 'icon' => 'bx bx-category'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Create Asset Category</h5>

                @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('assets.categories.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Method <span class="text-danger">*</span></label>
                            <select name="default_depreciation_method" id="depreciation_method" class="form-select select2-single" required>
                                <option value="straight_line" {{ ($defaults['method'] ?? '')=='straight_line'?'selected':'' }}>Straight Line</option>
                                <option value="declining_balance" {{ ($defaults['method'] ?? '')=='declining_balance'?'selected':'' }}>Declining Balance</option>
                                <option value="syd" {{ ($defaults['method'] ?? '')=='syd'?'selected':'' }}>Sum-of-the-Years'-Digits</option>
                                <option value="units" {{ ($defaults['method'] ?? '')=='units'?'selected':'' }}>Units of Production</option>
                                <option value="no_depreciation" {{ ($defaults['method'] ?? '')=='no_depreciation'?'selected':'' }}>No Depreciation</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="useful_life_months_field">
                            <label class="form-label">Useful Life (months)</label>
                            <input type="number" name="default_useful_life_months" id="default_useful_life_months" class="form-control" min="1" value="{{ $defaults['life'] ?? 60 }}">
                        </div>
                        <div class="col-md-4" id="useful_life_years_field">
                            <label class="form-label">Useful Life (years)</label>
                            <input type="number" name="default_useful_life_years" id="default_useful_life_years" class="form-control" min="1" placeholder="Optional, will convert to months">
                        </div>
                        <div class="col-md-4" id="depreciation_rate_field">
                            <label class="form-label">Depreciation Rate (% per year)</label>
                            <input type="number" name="default_depreciation_rate" id="default_depreciation_rate" class="form-control" step="0.01" min="0" max="100" value="{{ number_format($defaults['rate'] ?? 0, 2, '.', '') }}">
                        </div>
                        <div class="col-md-4" id="depreciation_convention_field">
                            <label class="form-label">Convention</label>
                            <select name="depreciation_convention" id="depreciation_convention" class="form-select select2-single">
                                <option value="monthly_prorata" {{ ($defaults['convention'] ?? '')=='monthly_prorata'?'selected':'' }}>Monthly Prorata</option>
                                <option value="mid_month" {{ ($defaults['convention'] ?? '')=='mid_month'?'selected':'' }}>Mid-Month</option>
                                <option value="full_month" {{ ($defaults['convention'] ?? '')=='full_month'?'selected':'' }}>Full Month</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Capitalization Threshold (TZS)</label>
                            <input type="number" name="capitalization_threshold" class="form-control" step="0.01" min="0" value="{{ number_format($defaults['threshold'] ?? 0, 2, '.', '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Residual Value</label>
                            <input type="number" name="residual_value_percent" class="form-control" step="0.01" min="0" max="100" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">IFRS/IPSAS Reference</label>
                            <input type="text" name="ifrs_reference" class="form-control" placeholder="e.g., IAS 16 / IPSAS 17">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Additional guidance for this category"></textarea>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="mb-3">Default Accounts (Optional)</h6>
                    <div class="row g-3">
                        @php
                        $accountFields = [
                            'asset_account_id' => 'Asset Account',
                            'accum_depr_account_id' => 'Accumulated Depreciation',
                            'depr_expense_account_id' => 'Depreciation Expense',
                            'gain_on_disposal_account_id' => 'Gain on Disposal',
                            'loss_on_disposal_account_id' => 'Loss on Disposal',
                            'revaluation_reserve_account_id' => 'Revaluation Reserve',
                            'impairment_loss_account_id' => 'Impairment Loss Account',
                            'impairment_reversal_account_id' => 'Impairment Reversal Account',
                            'accumulated_impairment_account_id' => 'Accumulated Impairment Account',
                            'hfs_account_id' => 'Held for Sale (HFS) Account',
                        ];
                        @endphp

                        @foreach($accountFields as $field => $label)
                        <div class="col-md-4">
                            <label class="form-label">{{ $label }}</label>
                            <select name="{{ $field }}" class="form-select select2-single">
                                <option value="">-- Select --</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($defaults[$field] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
                                @endforeach
                            </select>
                            @if($field === 'hfs_account_id')
                                <small class="text-muted">Required for IFRS 5 Held for Sale classification</small>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Save</button>
                        <a href="{{ route('assets.categories.index') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(function(){
    $('.select2-single').select2({ theme: 'bootstrap-5', width:'100%' });

    // Show/hide depreciation fields based on method selection
    function toggleDepreciationFields() {
        const method = $('#depreciation_method').val();
        const isNoDepreciation = method === 'no_depreciation';
        
        if (isNoDepreciation) {
            // Hide depreciation-related fields
            $('#useful_life_months_field, #useful_life_years_field, #depreciation_rate_field, #depreciation_convention_field').hide();
            // Make fields optional
            $('#default_useful_life_months, #default_useful_life_years, #default_depreciation_rate, #depreciation_convention').removeAttr('required');
        } else {
            // Show depreciation-related fields
            $('#useful_life_months_field, #useful_life_years_field, #depreciation_rate_field, #depreciation_convention_field').show();
            // Make useful life required
            $('#default_useful_life_months').attr('required', 'required');
            $('#depreciation_convention').attr('required', 'required');
        }
    }

    // Initial toggle
    toggleDepreciationFields();
    
    // Toggle on method change
    $('#depreciation_method').on('change', toggleDepreciationFields);

    // Auto-calculate months from years and vice versa
    const $years = $('input[name="default_useful_life_years"]');
    const $months = $('input[name="default_useful_life_months"]');

    $years.on('input change', function(){
        const y = parseInt($(this).val(), 10);
        if (!isNaN(y) && y > 0) {
            $months.val(y * 12);
        }
    });

    $months.on('input change', function(){
        const m = parseInt($(this).val(), 10);
        if (!isNaN(m) && m > 0) {
            $years.val(Math.round(m / 12));
        }
    });
});
</script>
@endpush


