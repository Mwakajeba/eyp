@extends('layouts.main')

@section('title', 'Imprest Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Imprest Management', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />

        <h6 class="mb-0 text-uppercase">IMPREST MANAGEMENT SYSTEM</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bx bx-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        @if(isset($errors) && $errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            Please fix the following errors:
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        <div class="row">
                            <!-- All Imprest Requests -->
                            @can('view imprest requests')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ $stats['pending_requests'] + $stats['checked_requests'] + $stats['approved_requests'] + $stats['disbursed_requests'] + $stats['liquidated_requests'] + $stats['closed_requests'] }}
                                            <span class="visually-hidden">total requests</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-list-ul fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">All Imprest Requests</h5>
                                        <p class="card-text">View, create, edit and manage all imprest requests. Full CRUD operations.</p>
                                        <a href="{{ route('imprest.requests.index') }}" class="btn btn-primary">
                                            <i class="bx bx-list-ul me-1"></i> Manage Requests
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            {{-- <!-- Manager Review -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                            {{ $stats['pending_requests'] }}
                                            <span class="visually-hidden">pending requests</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-user-check fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Manager Review</h5>
                                        <p class="card-text">Review and check imprest requests before forwarding to finance.</p>
                                        <a href="{{ route('imprest.checked.index') }}" class="btn btn-warning">
                                            <i class="bx bx-search me-1"></i> Review Requests
                                        </a>
                                    </div>
                                </div>
                            </div> --}}

                            {{-- <!-- Finance Approval -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ $stats['checked_requests'] }}
                                            <span class="visually-hidden">checked requests</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-check-shield fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Finance Approval</h5>
                                        <p class="card-text">Approve checked imprest requests and authorize disbursement.</p>
                                        <a href="{{ route('imprest.approved.index') }}" class="btn btn-info">
                                            <i class="bx bx-check-circle me-1"></i> Approve Requests
                                        </a>
                                    </div>
                                </div>
                            </div> --}}
                            {{-- <!-- Fund Disbursement -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ $stats['approved_requests'] }}
                                            <span class="visually-hidden">approved requests</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-money fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Fund Disbursement</h5>
                                        <p class="card-text">Disburse approved imprest funds through various payment methods.</p>
                                        <a href="{{ route('imprest.disbursed.index') }}" class="btn btn-success">
                                            <i class="bx bx-money me-1"></i> Disburse Funds
                                        </a>
                                    </div>
                                </div>
                            </div> --}}

                            <!-- Approval Settings -->

                            <!-- Pending Approvals -->
                            @can('approve imprest')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">
                                            {{ $stats['pending_requests'] ?? 0 }}
                                            <span class="visually-hidden">pending approvals</span>
                                        </span>
                                        <!-- Approval Icon -->
                                        <div class="mb-3">
                                            <i class="bx bx-check-circle fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Pending Imprest Approvals</h5>
                                        <p class="card-text">Review and approve imprest requests awaiting your approval.</p>
                                        <a href="{{ route('imprest.multi-approvals.pending') }}" class="btn btn-warning">
                                            <i class="bx bx-check me-1"></i> View Pending
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Closed Imprests -->
                            @can('view imprest requests')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary">
                                            {{ $stats['closed_requests'] + $stats['liquidated_requests'] }}
                                            <span class="visually-hidden">closed requests</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-archive fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Closed Imprests</h5>
                                        <p class="card-text">View completed and closed imprest requests with audit trail.</p>
                                        <a href="{{ route('imprest.closed.index') }}" class="btn btn-secondary">
                                            <i class="bx bx-archive me-1"></i> View Closed
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Pending Retirement Approvals -->
                            @can('approve retirement')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-orange position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">
                                            {{ $stats['pending_retirement_requests'] ?? 0 }}
                                            <span class="visually-hidden">pending retirement approvals</span>
                                        </span>
                                        <!-- Retirement Approval Icon -->
                                        <div class="mb-3">
                                            <i class="bx bx-receipt fs-1 text-orange"></i>
                                        </div>
                                        <h5 class="card-title">Pending Retirement Approvals</h5>
                                        <p class="card-text">Review and approve retirement requests awaiting your approval.</p>
                                        <a href="{{ route('imprest.retirement-multi-approvals.pending') }}" class="btn btn-orange">
                                            <i class="bx bx-check me-1"></i> View Pending
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                             <!-- Liquidation & Retirement -->
                            @can('view imprest requests')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ $stats['disbursed_requests'] }}
                                            <span class="visually-hidden">disbursed requests</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-receipt fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Liquidation & Retirement</h5>
                                        <p class="card-text">Process imprest liquidations with receipts and documents.</p>
                                        <a href="{{ route('imprest.requests.index') }}?status=disbursed" class="btn btn-danger">
                                            <i class="bx bx-receipt me-1"></i> Manage Liquidations
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            @can('view imprest reports')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-bar-chart-alt-2 fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">Reports & Analytics</h5>
                                        <p class="card-text">Generate detailed reports and analytics on imprest performance.</p>
                                        <a href="#" class="btn btn-purple">
                                            <i class="bx bx-chart me-1"></i> View Reports
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                        </div>

                        <!-- Financial Summary -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="bx bx-cogs"></i> Settings
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            @can('manage imprest settings')
                                            <div class="col-md-6 col-lg-4 mb-4">
                                                <div class="card border-info position-relative">
                                                    <div class="card-body text-center">
                                                        <div class="mb-3">
                                                            <i class="bx bx-cog fs-1 text-info"></i>
                                                        </div>
                                                        <h5 class="card-title">Imprest Settings</h5>
                                                        <p class="card-text">Configure imprest system settings, retirement options and budget controls.</p>
                                                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#imprestSettingsModal">
                                                            <i class="bx bx-cog me-1"></i> Open Settings
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            @endcan
                                            <!-- Approval Settings -->
                                            @can('manage imprest approval settings')
                                            <div class="col-md-6 col-lg-4 mb-4">
                                                <div class="card border-dark position-relative">
                                                    <div class="card-body text-center">
                                                        <!-- Settings Icon -->
                                                        <div class="mb-3">
                                                            <i class="bx bx-cog fs-1 text-dark"></i>
                                                        </div>
                                                        <h5 class="card-title">Imprest Approval Settings</h5>
                                                        <p class="card-text">Configure multi-level approval workflows and user permissions.</p>
                                                        <a href="{{ route('imprest.multi-approval-settings.index') }}" class="btn btn-dark">
                                                            <i class="bx bx-cog me-1"></i> Manage Settings
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            @endcan

                                            <!-- Retirement Approval Settings -->
                                            @can('manage retirement approval settings')
                                            <div class="col-md-6 col-lg-4 mb-4">
                                                <div class="card border-info position-relative">
                                                    <div class="card-body text-center">
                                                        <!-- Settings Icon -->
                                                        <div class="mb-3">
                                                            <i class="bx bx-cog fs-1 text-info"></i>
                                                        </div>
                                                        <h5 class="card-title">Retirement Approval Settings</h5>
                                                        <p class="card-text">Configure multi-level approval workflows for retirement processing.</p>
                                                        <a href="{{ route('imprest.retirement-approval-settings.index') }}" class="btn btn-info">
                                                            <i class="bx bx-cog me-1"></i> Manage Settings
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            </div>
                         </div>

                        </div>

                        <!-- Financial Summary -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="bx bx-bar-chart me-2"></i>Financial Summary
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-md-3">
                                                <div class="border-end">
                                                    <h3 class="text-primary mb-1">TZS {{ number_format($stats['total_amount_requested'], 0) }}</h3>
                                                    <p class="mb-0 text-muted">Total Requested</p>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="border-end">
                                                    <h3 class="text-success mb-1">TZS {{ number_format($stats['total_amount_disbursed'], 0) }}</h3>
                                                    <p class="mb-0 text-muted">Total Disbursed</p>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="border-end">
                                                    <h3 class="text-info mb-1">{{ $stats['pending_requests'] + $stats['checked_requests'] + $stats['approved_requests'] }}</h3>
                                                    <p class="mb-0 text-muted">In Progress</p>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div>
                                                    <h3 class="text-secondary mb-1">{{ $stats['closed_requests'] + $stats['liquidated_requests'] }}</h3>
                                                    <p class="mb-0 text-muted">Completed</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-center mt-4">
                                            <a href="{{ route('imprest.requests.create') }}" class="btn btn-primary me-2">
                                                <i class="bx bx-plus-circle me-1"></i> Create New Request
                                            </a>
                                            <a href="{{ route('imprest.requests.index') }}" class="btn btn-outline-primary">
                                                <i class="bx bx-list-ul me-1"></i> View All Requests
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Imprest Settings Modal -->
<div class="modal fade" id="imprestSettingsModal" tabindex="-1" aria-labelledby="imprestSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imprestSettingsModalLabel">
                    <i class="bx bx-cog me-2"></i>Imprest System Settings
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="imprestSettingsForm" action="{{ route('imprest.settings.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>
                                Configure your imprest system settings below. These settings will affect how the imprest module operates.
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="retirement_enabled" name="retirement_enabled" value="1"
                                    {{ old('retirement_enabled', $imprestSettings->retirement_enabled ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="retirement_enabled">
                                    <strong>Enable Retirement</strong>
                                    <small class="d-block text-muted">Allow imprest retirement/liquidation process</small>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="check_budget" name="check_budget" value="1"
                                    {{ old('check_budget', $imprestSettings->check_budget ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="check_budget">
                                    <strong>Check Budget</strong>
                                    <small class="d-block text-muted">Validate against budget before approval</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="receivablesAccountSection" style="display: {{ old('retirement_enabled', $imprestSettings->retirement_enabled ?? false) ? 'block' : 'none' }};">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="imprest_receivables_account" class="form-label">
                                    <strong>Imprest Receivables Account</strong>
                                    <small class="text-muted">*Required when retirement is enabled</small>
                                </label>
                                <select name="imprest_receivables_account" id="imprest_receivables_account" class="form-select">
                                    <option value="">Select Chart Account</option>
                                    @foreach($chartAccounts as $account)
                                    <option value="{{ $account->id }}"
                                        {{ old('imprest_receivables_account', $imprestSettings->imprest_receivables_account ?? '') == $account->id ? 'selected' : '' }}>
                                        {{ $account->account_code }} - {{ $account->account_name }}
                                    </option>
                                    @endforeach
                                </select>
                                <div class="form-text">This account will be used for imprest receivables transactions</div>
                            </div>
                        </div>
                    </div>

                    <!-- Retirement Period Input -->
                    <div class="row" id="retirementPeriodSection" style="display: {{ old('retirement_enabled', $imprestSettings->retirement_enabled ?? false) ? 'block' : 'none' }};">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="retirement_period_days" class="form-label">
                                    <strong>Retirement Period (Days)</strong>
                                    <small class="text-muted">*Required when retirement is enabled</small>
                                </label>
                                <input type="number"
                                       name="retirement_period_days"
                                       id="retirement_period_days"
                                       class="form-control"
                                       min="1"
                                       max="365"
                                       placeholder="Enter number of days (e.g., 30)"
                                       value="{{ old('retirement_period_days', $imprestSettings->retirement_period_days ?? '') }}">
                                <div class="form-text">Maximum number of days allowed for imprest retirement (1-365 days)</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Add any additional notes about these settings...">{{ old('notes', $imprestSettings->notes ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i>Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.5em 0.75em;
    }

    .fs-1 {
        font-size: 3rem !important;
    }

    /* Notification badge positioning */
    .position-relative .badge {
        z-index: 10;
        font-size: 0.7rem;
        min-width: 1.5rem;
        height: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .border-primary { border-color: #0d6efd !important; }
    .border-success { border-color: #198754 !important; }
    .border-warning { border-color: #ffc107 !important; }
    .border-info { border-color: #0dcaf0 !important; }
    .border-danger { border-color: #dc3545 !important; }
    .border-secondary { border-color: #6c757d !important; }
    .border-purple { border-color: #6f42c1 !important; }
    .border-orange { border-color: #fd7e14 !important; }

    .text-purple { color: #6f42c1 !important; }
    .text-orange { color: #fd7e14 !important; }

    .btn-purple {
        background-color: #6f42c1;
        border-color: #6f42c1;
        color: white;
    }
    .btn-purple:hover {
        background-color: #5a2d91;
        border-color: #5a2d91;
        color: white;
    }

    .btn-orange {
        background-color: #fd7e14;
        border-color: #fd7e14;
        color: white;
    }
    .btn-orange:hover {
        background-color: #e8620d;
        border-color: #e8620d;
        color: white;
    }

    .border-end {
        border-right: 1px solid #dee2e6;
    }

    @media (max-width: 768px) {
        .border-end {
            border-right: none;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        .border-end:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for searchable chart accounts dropdown
    $('#imprest_receivables_account').select2({
        dropdownParent: $('#imprestSettingsModal'),
        placeholder: 'Search and select chart account...',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5'
    });

    // Handle retirement checkbox toggle
    function toggleReceivablesAccount() {
        const retirementEnabled = $('#retirement_enabled').is(':checked');
        const receivablesSection = $('#receivablesAccountSection');
        const retirementPeriodSection = $('#retirementPeriodSection');
        const receivablesSelect = $('#imprest_receivables_account');
        const retirementPeriodInput = $('#retirement_period_days');

        if (retirementEnabled) {
            receivablesSection.show();
            retirementPeriodSection.show();
            receivablesSelect.prop('required', true);
            retirementPeriodInput.prop('required', true);
        } else {
            receivablesSection.hide();
            retirementPeriodSection.hide();
            receivablesSelect.prop('required', false);
            retirementPeriodInput.prop('required', false);
            receivablesSelect.val('').trigger('change'); // Clear select2 value
            retirementPeriodInput.val(''); // Clear retirement period value
        }
    }

    // Initialize on page load
    toggleReceivablesAccount();

    // Handle checkbox change
    $('#retirement_enabled').change(function() {
        toggleReceivablesAccount();
    });

    // Reset select2 when modal is shown
    $('#imprestSettingsModal').on('shown.bs.modal', function() {
        $('#imprest_receivables_account').select2({
            dropdownParent: $('#imprestSettingsModal'),
            placeholder: 'Search and select chart account...',
            allowClear: true,
            width: '100%',
            theme: 'bootstrap-5'
        });
        toggleReceivablesAccount();
    });

    // Clean up select2 when modal is hidden
    $('#imprestSettingsModal').on('hidden.bs.modal', function() {
        $('#imprest_receivables_account').select2('destroy');
    });

    // Handle form submission
    $('#imprestSettingsForm').submit(function(e) {
        e.preventDefault();

        const form = $(this);
        const formData = new FormData(form[0]);

        // Show loading state
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...').prop('disabled', true);

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Settings Saved!',
                        text: response.success,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        $('#imprestSettingsModal').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.error || 'Failed to save settings',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr) {
                let message = 'An error occurred while saving settings.';

                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        const errors = Object.values(xhr.responseJSON.errors).flat();
                        message = errors.join('\n');
                    }
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error!',
                    text: message,
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                // Restore button state
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
