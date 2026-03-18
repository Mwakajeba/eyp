@extends('layouts.main')

@section('title', 'Maintenance Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Maintenance', 'url' => route('assets.maintenance.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Settings', 'url' => '#', 'icon' => 'bx bx-cog']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1 text-primary">Maintenance Settings</h5>
                <p class="text-muted mb-0">Configure GL accounts and capitalization thresholds</p>
            </div>
            <a href="{{ route('assets.maintenance.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i>Back
            </a>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <form method="POST" action="{{ route('assets.maintenance.settings.update') }}">
            @csrf
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bx bx-cog me-2"></i>GL Accounts Configuration</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Maintenance Expense Account <span class="text-danger">*</span></label>
                            <select name="maintenance_expense_account" class="form-select select2-single" required>
                                <option value="">Select Account</option>
                                @foreach($chartAccounts as $account)
                                    <option value="{{ $account->id }}" 
                                        {{ ($settings['maintenance_expense_account']->setting_value ?? null) == $account->id ? 'selected' : '' }}>
                                        {{ $account->account_code }} - {{ $account->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">GL account for routine maintenance expenses</div>
                            @error('maintenance_expense_account')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Maintenance WIP Account <span class="text-danger">*</span></label>
                            <select name="maintenance_wip_account" class="form-select select2-single" required>
                                <option value="">Select Account</option>
                                @foreach($chartAccounts as $account)
                                    <option value="{{ $account->id }}" 
                                        {{ ($settings['maintenance_wip_account']->setting_value ?? null) == $account->id ? 'selected' : '' }}>
                                        {{ $account->account_code }} - {{ $account->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">GL account for maintenance work-in-progress</div>
                            @error('maintenance_wip_account')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Asset Capitalization Account <span class="text-danger">*</span></label>
                            <select name="asset_capitalization_account" class="form-select select2-single" required>
                                <option value="">Select Account</option>
                                @foreach($chartAccounts as $account)
                                    <option value="{{ $account->id }}" 
                                        {{ ($settings['asset_capitalization_account']->setting_value ?? null) == $account->id ? 'selected' : '' }}>
                                        {{ $account->account_code }} - {{ $account->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">GL account for capitalized maintenance costs</div>
                            @error('asset_capitalization_account')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bx bx-slider me-2"></i>Capitalization Thresholds</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Capitalization Threshold Amount (TZS) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="capitalization_threshold_amount" 
                                class="form-control" 
                                value="{{ $settings['capitalization_threshold_amount']->setting_value ?? '2000000' }}" 
                                required>
                            <div class="form-text">Minimum maintenance cost to qualify for capitalization</div>
                            @error('capitalization_threshold_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Life Extension Threshold (Months) <span class="text-danger">*</span></label>
                            <input type="number" min="0" name="capitalization_life_extension_months" 
                                class="form-control" 
                                value="{{ $settings['capitalization_life_extension_months']->setting_value ?? '12' }}" 
                                required>
                            <div class="form-text">Minimum life extension in months to qualify for capitalization</div>
                            @error('capitalization_life_extension_months')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-save me-1"></i>Save Settings
                </button>
                <a href="{{ route('assets.maintenance.index') }}" class="btn btn-secondary">
                    <i class="bx bx-x me-1"></i>Cancel
                </a>
            </div>
        </form>
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
});
</script>
@endpush

