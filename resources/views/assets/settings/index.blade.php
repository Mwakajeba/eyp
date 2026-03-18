@extends('layouts.main')

@section('title', 'Asset Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-cabinet'],
            ['label' => 'Settings', 'url' => route('assets.settings.index'), 'icon' => 'bx bx-cog']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-primary">Asset Settings</h5>
        </div>

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

        @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-body">
                <form id="asset-settings-form" action="{{ route('assets.settings.update') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Default Depreciation Method</label>
                            <select name="default_depreciation_method" class="form-select select2-single">
                                <option value="straight_line" {{ ($settings['default_depreciation_method'] ?? '') == 'straight_line' ? 'selected' : '' }}>Straight Line</option>
                                <option value="declining_balance" {{ ($settings['default_depreciation_method'] ?? '') == 'declining_balance' ? 'selected' : '' }}>Declining Balance</option>
                                <option value="syd" {{ ($settings['default_depreciation_method'] ?? '') == 'syd' ? 'selected' : '' }}>Sum-of-the-Years’-Digits</option>
                                <option value="units" {{ ($settings['default_depreciation_method'] ?? '') == 'units' ? 'selected' : '' }}>Units of Production</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default Useful Life (months)</label>
                            <input type="number" name="default_useful_life_months" class="form-control" min="1" value="{{ $settings['default_useful_life_months'] ?? 60 }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default Depreciation Rate (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="default_depreciation_rate" class="form-control" value="{{ number_format($settings['default_depreciation_rate'] ?? 0, 2, '.', '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Depreciation Convention</label>
                            <select name="depreciation_convention" class="form-select select2-single">
                                <option value="monthly_prorata" {{ ($settings['depreciation_convention'] ?? '') == 'monthly_prorata' ? 'selected' : '' }}>Monthly Prorata</option>
                                <option value="mid_month" {{ ($settings['depreciation_convention'] ?? '') == 'mid_month' ? 'selected' : '' }}>Mid-Month</option>
                                <option value="full_month" {{ ($settings['depreciation_convention'] ?? '') == 'full_month' ? 'selected' : '' }}>Full Month</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Depreciation Frequency</label>
                            <select name="depreciation_frequency" class="form-select select2-single">
                                <option value="monthly" {{ ($settings['depreciation_frequency'] ?? 'monthly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="quarterly" {{ ($settings['depreciation_frequency'] ?? '') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                <option value="annually" {{ ($settings['depreciation_frequency'] ?? '') == 'annually' ? 'selected' : '' }}>Annually</option>
                            </select>
                            <small class="text-muted">Frequency for scheduled depreciation processing</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Capitalization Threshold (TZS)</label>
                            <input type="number" step="0.01" min="0" name="capitalization_threshold" class="form-control" value="{{ number_format($settings['capitalization_threshold'] ?? 0, 2, '.', '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Asset Code Format</label>
                            <input type="text" name="asset_code_format" class="form-control" value="{{ $settings['asset_code_format'] ?? 'AST-{YYYY}-{SEQ}' }}" placeholder="AST-{YYYY}-{SEQ}">
                        </div>
                    </div>

                    <hr class="my-4">
                    
                    <h6 class="mb-2">A. Fixed Asset Module Enhancements</h6>
                    <div class="border rounded p-3 mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" role="switch" id="books_enabled" name="books[enabled]" {{ ($settings['books']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="books_enabled">Enable Multi-Book (Accounting + Tax)</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Accounting Book Code (IAS 16)</label>
                                <input type="text" class="form-control" name="books[accounting_book_code]" value="{{ $settings['books']['accounting_book_code'] ?? 'FIN' }}" placeholder="FIN">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tax Book Code (TRA)</label>
                                <input type="text" class="form-control" name="books[tax_book_code]" value="{{ $settings['books']['tax_book_code'] ?? 'TAX' }}" placeholder="TAX">
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" role="switch" id="dual_depreciation_enabled" name="books[dual_depreciation_enabled]" {{ ($settings['books']['dual_depreciation_enabled'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="dual_depreciation_enabled">Dual Depreciation (Financial + Tax)</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="dual_post_to_gl" name="books[dual_post_to_gl]" {{ ($settings['books']['dual_post_to_gl'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="dual_post_to_gl">Post Both Depreciations to GL</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">A.2. Tax Pool Configuration (TRA Classes)</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-tax-pool-row"><i class="bx bx-plus me-1"></i>Add Pool</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="tax-pools-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 10%">Class</th>
                                        <th style="width: 20%">Pool Name</th>
                                        <th style="width: 12%">Rate %</th>
                                        <th style="width: 20%">Method</th>
                                        <th style="width: 23%">Notes</th>
                                        <th style="width: 15%">Book Code</th>
                                        <th style="width: 20%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $taxPools = $settings['tax_pools'] ?? []; @endphp
                                    @forelse($taxPools as $i => $pool)
                                    <tr>
                                        <td><input type="text" class="form-control pool-class" value="{{ $pool['class'] ?? '' }}" placeholder="Class 1"></td>
                                        <td><input type="text" class="form-control pool-name" value="{{ $pool['name'] ?? '' }}" placeholder="Machinery"></td>
                                        <td><input type="number" step="0.01" min="0" max="100" class="form-control pool-rate" value="{{ $pool['rate'] ?? '' }}"></td>
                                        <td>
                                            <select class="form-select pool-method">
                                                <option value="straight_line" {{ ($pool['method'] ?? '') == 'straight_line' ? 'selected' : '' }}>Straight Line</option>
                                                <option value="reducing_balance" {{ ($pool['method'] ?? '') == 'reducing_balance' ? 'selected' : '' }}>Reducing Balance</option>
                                                <option value="useful_life" {{ ($pool['method'] ?? '') == 'useful_life' ? 'selected' : '' }}>Useful Life (Intangibles)</option>
                                                <option value="immediate_write_off" {{ ($pool['method'] ?? '') == 'immediate_write_off' ? 'selected' : '' }}>Immediate Write-Off</option>
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control pool-notes" value="{{ $pool['notes'] ?? '' }}" placeholder="Optional notes"></td>
                                        <td><input type="text" class="form-control pool-book" value="{{ $pool['book_code'] ?? 'TAX' }}" placeholder="TAX"></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-tax-pool-row"><i class="bx bx-trash"></i></button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td><input type="text" class="form-control pool-class" value="Class 1"></td>
                                        <td><input type="text" class="form-control pool-name" value="Computers, small vehicles, earth-moving equipment"></td>
                                        <td><input type="number" step="0.01" min="0" max="100" class="form-control pool-rate" value="37.5"></td>
                                        <td>
                                            <select class="form-select pool-method">
                                                <option value="reducing_balance" selected>Reducing Balance</option>
                                                <option value="straight_line">Straight Line</option>
                                                <option value="useful_life">Useful Life (Intangibles)</option>
                                                <option value="immediate_write_off">Immediate Write-Off</option>
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control pool-notes" value="" placeholder="Optional notes"></td>
                                        <td><input type="text" class="form-control pool-book" value="TAX"></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-tax-pool-row"><i class="bx bx-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="text" class="form-control pool-class" value="Class 2"></td>
                                        <td><input type="text" class="form-control pool-name" value="Buses (≥30 seats), heavy trucks, transport, plant & machinery (agri/manufacturing), utilities"></td>
                                        <td><input type="number" step="0.01" min="0" max="100" class="form-control pool-rate" value="25"></td>
                                        <td>
                                            <select class="form-select pool-method">
                                                <option value="reducing_balance" selected>Reducing Balance</option>
                                                <option value="straight_line">Straight Line</option>
                                                <option value="useful_life">Useful Life (Intangibles)</option>
                                                <option value="immediate_write_off">Immediate Write-Off</option>
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control pool-notes" value="50% allowance (first two years) if used in manufacturing/tourism/fish farming" placeholder="Optional notes"></td>
                                        <td><input type="text" class="form-control pool-book" value="TAX"></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-tax-pool-row"><i class="bx bx-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="text" class="form-control pool-class" value="Class 3"></td>
                                        <td><input type="text" class="form-control pool-name" value="Office furniture, fixtures, equipment; other NEC"></td>
                                        <td><input type="number" step="0.01" min="0" max="100" class="form-control pool-rate" value="12.5"></td>
                                        <td>
                                            <select class="form-select pool-method">
                                                <option value="reducing_balance" selected>Reducing Balance</option>
                                                <option value="straight_line">Straight Line</option>
                                                <option value="useful_life">Useful Life (Intangibles)</option>
                                                <option value="immediate_write_off">Immediate Write-Off</option>
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control pool-notes" value="" placeholder="Optional notes"></td>
                                        <td><input type="text" class="form-control pool-book" value="TAX"></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-tax-pool-row"><i class="bx bx-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="text" class="form-control pool-class" value="Class 5"></td>
                                        <td><input type="text" class="form-control pool-name" value="Permanent works used in agriculture/livestock/fish farming"></td>
                                        <td><input type="number" step="0.01" min="0" max="100" class="form-control pool-rate" value="20"></td>
                                        <td>
                                            <select class="form-select pool-method">
                                                <option value="straight_line" selected>Straight Line</option>
                                                <option value="reducing_balance">Reducing Balance</option>
                                                <option value="useful_life">Useful Life (Intangibles)</option>
                                                <option value="immediate_write_off">Immediate Write-Off</option>
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control pool-notes" value="5 years write-off" placeholder="Optional notes"></td>
                                        <td><input type="text" class="form-control pool-book" value="TAX"></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-tax-pool-row"><i class="bx bx-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="text" class="form-control pool-class" value="Class 6"></td>
                                        <td><input type="text" class="form-control pool-name" value="Buildings & permanent works (other than Class 5)"></td>
                                        <td><input type="number" step="0.01" min="0" max="100" class="form-control pool-rate" value="5"></td>
                                        <td>
                                            <select class="form-select pool-method">
                                                <option value="straight_line" selected>Straight Line</option>
                                                <option value="reducing_balance">Reducing Balance</option>
                                                <option value="useful_life">Useful Life (Intangibles)</option>
                                                <option value="immediate_write_off">Immediate Write-Off</option>
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control pool-notes" value="" placeholder="Optional notes"></td>
                                        <td><input type="text" class="form-control pool-book" value="TAX"></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-tax-pool-row"><i class="bx bx-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="text" class="form-control pool-class" value="Class 7"></td>
                                        <td><input type="text" class="form-control pool-name" value="Intangible assets (amortize over useful life)"></td>
                                        <td><input type="number" step="0.01" min="0" max="100" class="form-control pool-rate" value=""></td>
                                        <td>
                                            <select class="form-select pool-method">
                                                <option value="useful_life" selected>Useful Life (Intangibles)</option>
                                                <option value="straight_line">Straight Line</option>
                                                <option value="reducing_balance">Reducing Balance</option>
                                                <option value="immediate_write_off">Immediate Write-Off</option>
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control pool-notes" value="1 ÷ useful life (round down to nearest half year)" placeholder="Optional notes"></td>
                                        <td><input type="text" class="form-control pool-book" value="TAX"></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-tax-pool-row"><i class="bx bx-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="text" class="form-control pool-class" value="Class 8"></td>
                                        <td><input type="text" class="form-control pool-name" value="Agriculture plant & machinery; EFDs (non-VAT) — immediate write-off"></td>
                                        <td><input type="number" step="0.01" min="0" max="100" class="form-control pool-rate" value="100"></td>
                                        <td>
                                            <select class="form-select pool-method">
                                                <option value="immediate_write_off" selected>Immediate Write-Off</option>
                                                <option value="straight_line">Straight Line</option>
                                                <option value="reducing_balance">Reducing Balance</option>
                                                <option value="useful_life">Useful Life (Intangibles)</option>
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control pool-notes" value="Immediate write-off" placeholder="Optional notes"></td>
                                        <td><input type="text" class="form-control pool-book" value="TAX"></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-tax-pool-row"><i class="bx bx-trash"></i></button>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <input type="hidden" name="tax_pools_json" id="tax_pools_json">
                    </div>

                    <div class="border rounded p-3 mb-4">
                        <h6 class="mb-2">B. Deferred Tax Logic</h6>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="deferred_tax_enabled" name="deferred_tax[enabled]" {{ ($settings['deferred_tax']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="deferred_tax_enabled">Enable Deferred Tax</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Corporate Tax Rate (%)</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" name="deferred_tax[tax_rate_percent]" value="{{ number_format($settings['deferred_tax']['tax_rate_percent'] ?? 30, 2, '.', '') }}">
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" role="switch" id="deferred_tax_auto" name="deferred_tax[auto_generate_journal]" {{ ($settings['deferred_tax']['auto_generate_journal'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="deferred_tax_auto">Auto Generate Adjustment</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" role="switch" id="deferred_tax_report" name="deferred_tax[produce_reconciliation_report]" {{ ($settings['deferred_tax']['produce_reconciliation_report'] ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="deferred_tax_report">Produce Reconciliation Report</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="mb-3">Default Accounts</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Asset Account</label>
                            <select name="accounts[asset]" class="form-select select2-single">
                                <option value="">-- Select --</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($settings['accounts']['asset'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Accumulated Depreciation</label>
                            <select name="accounts[accum_depr]" class="form-select select2-single">
                                <option value="">-- Select --</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($settings['accounts']['accum_depr'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Depreciation Expense</label>
                            <select name="accounts[depr_expense]" class="form-select select2-single">
                                <option value="">-- Select --</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($settings['accounts']['depr_expense'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tax Depreciation Expense</label>
                            <select name="accounts[tax_depr_expense]" class="form-select select2-single">
                                <option value="">-- Select --</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($settings['accounts']['tax_depr_expense'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tax Accumulated Depreciation</label>
                            <select name="accounts[tax_accum_depr]" class="form-select select2-single">
                                <option value="">-- Select --</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($settings['accounts']['tax_accum_depr'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gain on Disposal</label>
                            <select name="accounts[gain_on_disposal]" class="form-select select2-single">
                                <option value="">-- Select --</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($settings['accounts']['gain_on_disposal'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Loss on Disposal</label>
                            <select name="accounts[loss_on_disposal]" class="form-select select2-single">
                                <option value="">-- Select --</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($settings['accounts']['loss_on_disposal'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Revaluation Reserve</label>
                            <select name="accounts[revaluation_reserve]" class="form-select select2-single">
                                <option value="">-- Select --</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($settings['accounts']['revaluation_reserve'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                         <div class="col-md-4">
                            <label class="form-label">Revaluation Decrease (Loss)</label>
                            <select name="accounts[revaluation_loss]" class="form-select select2-single">
                                <option value="">-- Select --</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($settings['accounts']['revaluation_loss'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Deferred Tax Expense</label>
                            <select name="accounts[deferred_tax_expense]" class="form-select select2-single">
                                <option value="">-- Select --</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($settings['accounts']['deferred_tax_expense'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Deferred Tax Asset</label>
                            <select name="accounts[deferred_tax_asset]" class="form-select select2-single">
                                <option value="">-- Select --</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($settings['accounts']['deferred_tax_asset'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Deferred Tax Liability</label>
                            <select name="accounts[deferred_tax_liability]" class="form-select select2-single">
                                <option value="">-- Select --</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($settings['accounts']['deferred_tax_liability'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Held for Sale Account</label>
                            <select name="accounts[hfs_account]" class="form-select select2-single">
                                <option value="">-- Select --</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($settings['accounts']['hfs_account'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Account for assets classified as Held for Sale (IFRS 5)</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Impairment Loss Account</label>
                            <select name="accounts[impairment_loss]" class="form-select select2-single">
                                <option value="">-- Select --</option>
                                @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ ($settings['accounts']['impairment_loss'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->account_code }} - {{ $acc->account_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Account for impairment losses on HFS assets</small>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="border rounded p-3 mb-4">
                        <h6 class="mb-2">C. Tax Computation Module</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="tax_computation_enabled" name="tax_computation_enabled" {{ ($settings['tax_computation_enabled'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="tax_computation_enabled">Enable Tax Computation</label>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="alert alert-info py-2 mb-0">
                                    Inputs: PBT from GL, non-deductible/allowable adjustments, capital allowances (Tax Book), non-taxable income, deferred tax movement. Outputs: Taxable Income, CIT @30%, Deferred Tax Movement, Tax Payable, TRA Schedules.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i> Save Settings
                        </button>
                        <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary ms-2">Back</a>
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
    function serializeTaxPools(){
        const rows = [];
        $('#tax-pools-table tbody tr').each(function(){
            const rateVal = $(this).find('.pool-rate').val();
            const row = {
                class: $(this).find('.pool-class').val() || '',
                name: $(this).find('.pool-name').val() || '',
                rate: rateVal === '' ? null : parseFloat(rateVal),
                method: $(this).find('.pool-method').val() || 'straight_line',
                book_code: $(this).find('.pool-book').val() || 'TAX',
                notes: $(this).find('.pool-notes').val() || ''
            };
            if (row.class || row.name) { rows.push(row); }
        });
        $('#tax_pools_json').val(JSON.stringify(rows));
    }

    $('#add-tax-pool-row').on('click', function(){
        $('#tax-pools-table tbody').append(`
            <tr>
                <td><input type="text" class="form-control pool-class" placeholder="Class"></td>
                <td><input type="text" class="form-control pool-name" placeholder="Pool name"></td>
                <td><input type="number" step="0.01" min="0" max="100" class="form-control pool-rate"></td>
                <td>
                    <select class="form-select pool-method">
                        <option value="straight_line">Straight Line</option>
                        <option value="reducing_balance">Reducing Balance</option>
                        <option value="useful_life">Useful Life (Intangibles)</option>
                        <option value="immediate_write_off">Immediate Write-Off</option>
                    </select>
                </td>
                <td><input type="text" class="form-control pool-notes" placeholder="Optional notes"></td>
                <td><input type="text" class="form-control pool-book" value="TAX"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-tax-pool-row"><i class="bx bx-trash"></i></button></td>
            </tr>
        `);
    });

    $(document).on('click', '.remove-tax-pool-row', function(){
        $(this).closest('tr').remove();
    });

    $('#asset-settings-form').on('submit', function(){
        serializeTaxPools();
    });
});
</script>
@endpush


