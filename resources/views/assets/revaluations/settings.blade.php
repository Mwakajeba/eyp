@extends('layouts.main')

@section('title', 'Revaluation & Impairment Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Revaluation & Impairment', 'url' => route('assets.revaluations.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Settings', 'url' => '#', 'icon' => 'bx bx-cog']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1 text-primary">Revaluation & Impairment Settings</h5>
                <p class="text-muted mb-0">Configure revaluation models, frequencies, and default chart accounts per asset category</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" id="save-all-settings">
                    <i class="bx bx-save me-1"></i>Save All Settings
                </button>
                <a href="{{ route('assets.revaluations.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to Revaluations
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bx bx-cog me-2"></i>Asset Category Configuration</h6>
                <small class="text-white-50">Configure revaluation model and settings for each asset category</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle" id="category-settings-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 12%">Category</th>
                                <th style="width: 10%">Valuation Model</th>
                                <th style="width: 10%">Frequency</th>
                                <th style="width: 8%">Interval (Years)</th>
                                <th style="width: 13%">Revaluation Reserve Account</th>
                                <th style="width: 13%">Revaluation Loss Account</th>
                                <th style="width: 13%">Impairment Loss Account</th>
                                <th style="width: 13%">Impairment Reversal Account</th>
                                <th style="width: 8%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                            <tr data-category-id="{{ $category->id }}">
                                <td>
                                    <strong>{{ $category->name }}</strong><br>
                                    <small class="text-muted">{{ $category->code }}</small>
                                </td>
                                <td>
                                    <select name="default_valuation_model" class="form-select form-select-sm category-setting" data-field="default_valuation_model">
                                        <option value="cost" {{ $category->default_valuation_model == 'cost' ? 'selected' : '' }}>Cost Model</option>
                                        <option value="revaluation" {{ $category->default_valuation_model == 'revaluation' ? 'selected' : '' }}>Revaluation Model</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="revaluation_frequency" class="form-select form-select-sm category-setting" data-field="revaluation_frequency">
                                        <option value="">Ad Hoc</option>
                                        <option value="annual" {{ $category->revaluation_frequency == 'annual' ? 'selected' : '' }}>Annual</option>
                                        <option value="biennial" {{ $category->revaluation_frequency == 'biennial' ? 'selected' : '' }}>Biennial</option>
                                        <option value="ad_hoc" {{ $category->revaluation_frequency == 'ad_hoc' ? 'selected' : '' }}>Ad Hoc</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="revaluation_interval_years" class="form-control form-control-sm category-setting" 
                                        data-field="revaluation_interval_years" 
                                        value="{{ $category->revaluation_interval_years }}" 
                                        min="1" max="10" 
                                        placeholder="Years">
                                </td>
                                <td>
                                    <select name="revaluation_reserve_account_id" class="form-select form-select-sm select2-category category-setting" 
                                        data-field="revaluation_reserve_account_id" 
                                        data-placeholder="Select Account">
                                        <option value=""></option>
                                        @foreach($equityAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                {{ $category->revaluation_reserve_account_id == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code ?? $account->code }} - {{ $account->account_name ?? $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="revaluation_loss_account_id" class="form-select form-select-sm select2-category category-setting" 
                                        data-field="revaluation_loss_account_id" 
                                        data-placeholder="Select Account">
                                        <option value=""></option>
                                        @foreach($revaluationLossAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                {{ $category->revaluation_loss_account_id == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code ?? $account->code }} - {{ $account->account_name ?? $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="impairment_loss_account_id" class="form-select form-select-sm select2-category category-setting" 
                                        data-field="impairment_loss_account_id" 
                                        data-placeholder="Select Account">
                                        <option value=""></option>
                                        @foreach($impairmentLossAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                {{ $category->impairment_loss_account_id == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code ?? $account->code }} - {{ $account->account_name ?? $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="impairment_reversal_account_id" class="form-select form-select-sm select2-category category-setting" 
                                        data-field="impairment_reversal_account_id" 
                                        data-placeholder="Select Account">
                                        <option value=""></option>
                                        @foreach($impairmentReversalAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                {{ $category->impairment_reversal_account_id == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code ?? $account->code }} - {{ $account->account_name ?? $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <span class="badge bg-light text-muted">Auto-saved</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bx bx-info-circle me-2"></i>No asset categories found. Please create categories first.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bx bx-slider me-2"></i>Approval & Workflow Settings</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 20%">Category</th>
                                <th style="width: 15%">Require Valuation Report</th>
                                <th style="width: 15%">Require Approval</th>
                                <th style="width: 15%">Min Approval Levels</th>
                                <th style="width: 35%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                            <tr data-category-id="{{ $category->id }}">
                                <td>
                                    <strong>{{ $category->name }}</strong><br>
                                    <small class="text-muted">{{ $category->code }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input category-setting" type="checkbox" 
                                            data-field="require_valuation_report"
                                            data-category-id="{{ $category->id }}"
                                            {{ $category->require_valuation_report ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input category-setting" type="checkbox" 
                                            data-field="require_approval"
                                            data-category-id="{{ $category->id }}"
                                            {{ $category->require_approval ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <select name="min_approval_levels" class="form-select form-select-sm category-setting" 
                                        data-field="min_approval_levels"
                                        data-category-id="{{ $category->id }}">
                                        <option value="1" {{ ($category->min_approval_levels ?? 2) == 1 ? 'selected' : '' }}>1 Level (Finance Manager)</option>
                                        <option value="2" {{ ($category->min_approval_levels ?? 2) == 2 ? 'selected' : '' }}>2 Levels (CFO/Board)</option>
                                    </select>
                                </td>
                                <td>
                                    <span class="badge bg-light text-muted">Auto-saved</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No asset categories found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-3">
            <h6 class="alert-heading"><i class="bx bx-info-circle me-2"></i>Configuration Notes</h6>
            <ul class="mb-0">
                <li><strong>Valuation Model:</strong> Choose between Cost Model (IAS 16) or Revaluation Model. Revaluation model requires revaluation reserve account.</li>
                <li><strong>Revaluation Frequency:</strong> Set automatic revaluation schedule (Annual, Biennial) or leave as Ad Hoc for manual revaluations.</li>
                <li><strong>Revaluation Interval:</strong> Number of years between automatic revaluations (only applicable for Annual/Biennial frequency).</li>
                <li><strong>Chart Accounts:</strong> Configure default GL accounts for revaluation reserve, revaluation loss, impairment losses, and reversals per category.</li>
                <li><strong>Approval Levels:</strong> 1 Level = Finance Manager only, 2 Levels = Finance Manager + CFO/Board approval required.</li>
            </ul>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for account dropdowns
    $('.select2-category').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('body')
    });

    // Save all settings at once
    $('#save-all-settings').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<i class="bx bx-loader bx-spin me-1"></i>Saving...').prop('disabled', true);

        // Collect all category data
        const categories = [];
        
        // Get all category rows from both tables
        $('tr[data-category-id]').each(function() {
            const categoryId = $(this).data('category-id');
            
            // Check if we already processed this category
            if (categories.find(c => c.id === categoryId)) {
                return;
            }

            const categoryData = {
                id: categoryId
            };

            // Collect settings from all rows with this category ID
            $('tr[data-category-id="' + categoryId + '"]').find('.category-setting').each(function() {
                const field = $(this).data('field');
                let value = $(this).val();
                
                // Handle checkboxes
                if ($(this).is(':checkbox')) {
                    value = $(this).is(':checked') ? true : false;
                }
                
                // Handle empty values - set defaults where needed
                if (field === 'default_valuation_model' && !value) {
                    value = 'cost'; // Default
                }
                
                if (field === 'min_approval_levels' && (!value || value === '')) {
                    value = 2; // Default to 2 levels
                }
                
                // Set the value (including empty strings for optional fields)
                if (value !== null && value !== undefined) {
                    categoryData[field] = value;
                }
            });

            // Ensure required fields have defaults
            if (!categoryData.default_valuation_model) {
                categoryData.default_valuation_model = 'cost';
            }
            if (!categoryData.min_approval_levels) {
                categoryData.min_approval_levels = 2;
            }
            if (categoryData.require_valuation_report === undefined) {
                categoryData.require_valuation_report = false;
            }
            if (categoryData.require_approval === undefined) {
                categoryData.require_approval = true;
            }

            categories.push(categoryData);
        });

        if (categories.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Categories',
                text: 'No categories found to save.'
            });
            btn.html(originalHtml).prop('disabled', false);
            return;
        }

        // Send bulk update request
        $.ajax({
            url: '{{ route("assets.revaluations.settings.bulk-update") }}',
            method: 'POST',
            data: {
                categories: categories
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message || 'All settings saved successfully',
                        timer: 3000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to save settings'
                    });
                }
            },
            error: function(xhr) {
                let message = 'Failed to save settings';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    message = errors.join(', ');
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            },
            complete: function() {
                btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
});
</script>
@endpush

