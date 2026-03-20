@extends('layouts.main')

@section('title', 'Disburse Imprest Funds')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Imprest Management', 'url' => route('imprest.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Request Details', 'url' => route('imprest.requests.show', $imprestRequest->id), 'icon' => 'bx bx-show'],
            ['label' => 'Disburse Funds', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1 text-primary">
                    <i class="bx bx-money me-2"></i>Disburse Imprest Funds
                </h5>
                <small class="text-muted">Request #{{ $imprestRequest->request_number }}</small>
            </div>
            <div class="d-flex gap-2">
                <span class="{{ $imprestRequest->getStatusBadgeClass() }}">{{ $imprestRequest->getStatusLabel() }}</span>
                @if(isset($imprestSettings) && $imprestSettings->retirement_enabled)
                    <span class="badge bg-info">With Retirement</span>
                @else
                    <span class="badge bg-success">Direct Expense</span>
                @endif
            </div>
        </div>

        @if(isset($imprestSettings) && $imprestSettings->retirement_enabled)
            <div class="alert alert-info alert-dismissible fade show">
                <i class="bx bx-info-circle me-2"></i>
                <strong>Retirement Mode:</strong> Disbursement will be posted to imprest receivables account. Expenses will be posted when liquidated.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @else
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bx bx-check-circle me-2"></i>
                <strong>Direct Expense Mode:</strong> Disbursement will directly post expenses. No liquidation required.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <!-- Form Section - Left -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-gradient py-3">
                        <h6 class="mb-0">
                            <i class="bx bx-edit me-2"></i>Disbursement Details
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('imprest.disbursed.disburse', $imprestRequest->id) }}" method="POST" id="disbursementForm" novalidate>
                            @csrf
                            
                            @if(isset($imprestSettings) && $imprestSettings->retirement_enabled)
                                <!-- Retirement Mode Form -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="amount" class="form-label fw-bold mb-2">
                                                <i class="bx bx-money text-success me-2"></i>Amount to Disburse
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text bg-light border-0">
                                                    <i class="bx bx-money-alt text-primary"></i>
                                                </span>
                                                <input type="number" class="form-control border-start-0 @error('amount') is-invalid @enderror" 
                                                       id="amount" name="amount" placeholder="0.00" step="0.01" min="0.01" 
                                                       max="{{ $imprestRequest->amount_requested }}" 
                                                       value="{{ old('amount', $imprestRequest->amount_requested) }}" required>
                                            </div>
                                            <small class="text-muted d-block mt-2">
                                                <i class="bx bx-info-circle me-1"></i>Maximum: <strong>TZS {{ number_format($imprestRequest->amount_requested, 2) }}</strong>
                                            </small>
                                            @error('amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="bank_account_id" class="form-label fw-bold mb-2">
                                                <i class="bx bx-building text-info me-2"></i>Bank/Cash Account
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select form-select-lg @error('bank_account_id') is-invalid @enderror" 
                                                    id="bank_account_id" name="bank_account_id" required>
                                                <option value="">-- Select Account --</option>
                                                @foreach($bankAccounts as $account)
                                                <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>
                                                    {{ $account->name }} 
                                                    @if($account->chartAccount)
                                                        - {{ $account->chartAccount->account_name }}
                                                    @endif
                                                </option>
                                                @endforeach
                                            </select>
                                            @error('bank_account_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Imprest Account Section -->
                                <div class="mb-4">
                                    <div class="form-group">
                                        <label class="form-label fw-bold mb-2">
                                            <i class="bx bx-receipt text-warning me-2"></i>Imprest Receivable Account
                                        </label>
                                        @if($imprestSettings && $imprestSettings->isRetirementConfigured())
                                            <input type="hidden" name="imprest_account_id" value="{{ $imprestSettings->receivablesAccount->id }}">
                                            <div class="alert alert-light border-2 border-success mb-0">
                                                <div class="fw-bold text-dark">{{ $imprestSettings->receivablesAccount->account_code }}</div>
                                                <div class="text-dark">{{ $imprestSettings->receivablesAccount->account_name }}</div>
                                                <small class="text-success d-block mt-1">
                                                    <i class="bx bx-check-circle me-1"></i>Auto-selected from settings
                                                </small>
                                            </div>
                                        @else
                                            <div class="alert alert-warning mb-0">
                                                <i class="bx bx-error-circle me-2"></i>
                                                <strong>No imprest receivables account configured.</strong><br>
                                                Please configure settings before disbursing.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <!-- Direct Expense Mode Form -->
                                <div class="mb-4">
                                    <div class="form-group">
                                        <label for="bank_account_id" class="form-label fw-bold mb-2">
                                            <i class="bx bx-building text-info me-2"></i>Bank/Cash Account
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select form-select-lg @error('bank_account_id') is-invalid @enderror" 
                                                id="bank_account_id" name="bank_account_id" required>
                                            <option value="">-- Select Account --</option>
                                            @foreach($bankAccounts as $account)
                                            <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->name }} 
                                                @if($account->chartAccount)
                                                    - {{ $account->chartAccount->account_name }}
                                                @endif
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('bank_account_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <!-- Total Amount Display -->
                                <div class="mb-4">
                                    <div class="form-group">
                                        <label class="form-label fw-bold mb-2">
                                            <i class="bx bx-money text-success me-2"></i>Total Amount to Disburse
                                        </label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-light border-0">
                                                <i class="bx bx-money-alt text-success"></i>
                                            </span>
                                            <input type="text" class="form-control border-start-0 fw-bold text-success" 
                                                   value="{{ number_format($imprestRequest->amount_requested, 2) }}" readonly>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            <i class="bx bx-info-circle me-1"></i>Full amount will be disbursed as direct expenses
                                        </small>
                                    </div>
                                </div>

                                <!-- Expense Breakdown -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold mb-3">
                                        <i class="bx bx-calculator text-primary me-2"></i>Expense Accounts Breakdown
                                    </label>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm border rounded">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="text-dark fw-bold">Account Code</th>
                                                    <th class="text-dark fw-bold">Account Name</th>
                                                    <th class="text-end text-dark fw-bold">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($imprestRequest->imprestItems as $item)
                                                <tr>
                                                    <td><span class="badge bg-light text-dark">{{ $item->chartAccount->account_code }}</span></td>
                                                    <td>{{ $item->chartAccount->account_name }}</td>
                                                    <td class="text-end fw-bold text-success">TZS {{ number_format($item->amount, 2) }}</td>
                                                </tr>
                                                @endforeach
                                                <tr class="bg-light">
                                                    <th colspan="2" class="text-dark">Total</th>
                                                    <th class="text-end text-dark fw-bold">TZS {{ number_format($imprestRequest->amount_requested, 2) }}</th>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        <i class="bx bx-info-circle me-1"></i>These accounts will be debited when funds are disbursed.
                                    </small>
                                </div>
                            @endif

                            <!-- Retirement Days -->
                            <div class="mb-4">
                                <div class="form-group">
                                    <label for="retirement_days" class="form-label fw-bold mb-2">
                                        <i class="bx bx-calendar text-warning me-2"></i>Retirement Days
                                        @if(isset($imprestSettings) && $imprestSettings->retirement_enabled)
                                            <span class="text-danger">*</span>
                                        @endif
                                    </label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-light border-0">
                                            <i class="bx bx-time-five"></i>
                                        </span>
                                        <input type="number"
                                               class="form-control border-start-0 @error('retirement_days') is-invalid @enderror"
                                               id="retirement_days"
                                               name="retirement_days"
                                               min="1"
                                               step="1"
                                               value="{{ old('retirement_days', (isset($imprestSettings) && $imprestSettings->retirement_enabled) ? ($imprestSettings->retirement_period_days ?? 30) : '') }}"
                                               placeholder="Enter number of days"
                                               @if(isset($imprestSettings) && $imprestSettings->retirement_enabled) required @endif>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        <i class="bx bx-info-circle me-1"></i>
                                        Retirement end date will be calculated from disbursement date plus these days.
                                    </small>
                                    @error('retirement_days')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <!-- Reference Number -->
                            <div class="mb-4">
                                <div class="form-group">
                                    <label for="reference" class="form-label fw-bold mb-2">
                                        <i class="bx bx-link text-secondary me-2"></i>Reference Number
                                    </label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-light border-0">
                                            <i class="bx bx-link"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0 @error('reference') is-invalid @enderror" 
                                               id="reference" name="reference" 
                                               value="{{ old('reference', 'IMP-DISB-' . $imprestRequest->request_number) }}" 
                                               placeholder="Payment reference">
                                    </div>
                                    @error('reference')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mb-4">
                                <div class="form-group">
                                    <label for="description" class="form-label fw-bold mb-2">
                                        <i class="bx bx-notepad text-secondary me-2"></i>Description/Notes
                                    </label>
                                    <textarea class="form-control form-control-lg @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="4" 
                                              placeholder="Additional notes or description...">{{ old('description', "Imprest disbursement for: {$imprestRequest->purpose}") }}</textarea>
                                    @error('description')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="d-flex gap-2 pt-3 border-top">
                                <a href="{{ route('imprest.requests.show', $imprestRequest->id) }}" class="btn btn-outline-secondary btn-lg flex-grow-1">
                                    <i class="bx bx-arrow-back me-2"></i>Cancel
                                </a>
                                @if(isset($imprestSettings) && $imprestSettings->retirement_enabled && (!$imprestSettings->isRetirementConfigured()))
                                    <button type="button" class="btn btn-secondary btn-lg flex-grow-1" disabled>
                                        <i class="bx bx-lock me-2"></i>Configure Settings First
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-success btn-lg flex-grow-1" id="submitBtn">
                                        <i class="bx bx-check-circle me-2"></i>Disburse Funds
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Summary & Impact Section - Right -->
            <div class="col-lg-4">
                <!-- Request Summary Card -->
                <div class="card shadow-sm mb-3 border-left-primary">
                    <div class="card-header bg-gradient py-3">
                        <h6 class="mb-0">
                            <i class="bx bx-user-circle me-2"></i>Request Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Request Number</small>
                            <div class="fw-bold h6 mb-0 text-primary">{{ $imprestRequest->request_number }}</div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Employee</small>
                            <div class="fw-bold">{{ $imprestRequest->employee->name ?? 'N/A' }}</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Department</small>
                            <div>{{ $imprestRequest->department->name ?? 'N/A' }}</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Purpose</small>
                            <div class="text-break">{{ $imprestRequest->purpose }}</div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Requested Amount</small>
                            <div class="h5 mb-0 text-success fw-bold">TZS {{ number_format($imprestRequest->amount_requested, 2) }}</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Date Required</small>
                            <div>{{ $imprestRequest->date_required ? $imprestRequest->date_required->format('M d, Y') : 'N/A' }}</div>
                        </div>
                        <div>
                            <small class="text-muted d-block mb-1">Status</small>
                            <span class="{{ $imprestRequest->getStatusBadgeClass() }}">{{ $imprestRequest->getStatusLabel() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Accounting Impact Card -->
                <div class="card shadow-sm border-left-success">
                    <div class="card-header bg-gradient py-3">
                        <h6 class="mb-0">
                            <i class="bx bx-calculator me-2"></i>Accounting Impact
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="d-block mb-2 text-uppercase text-muted fw-bold">Journal Entry</small>
                            
                            <div class="bg-light p-3 rounded mb-3 border-left-danger" style="border-left: 4px solid #dc3545;">
                                <span class="badge bg-danger mb-2">DEBIT</span>
                                <div class="fw-bold text-dark">
                                    @if(isset($imprestSettings) && $imprestSettings->retirement_enabled)
                                        @if($imprestSettings->isRetirementConfigured())
                                            {{ $imprestSettings->receivablesAccount->account_name }}
                                        @else
                                            Imprest Receivable Account
                                        @endif
                                    @else
                                        Multiple Expense Accounts
                                    @endif
                                </div>
                                <small class="text-muted d-block mt-1">
                                    @if(isset($imprestSettings) && $imprestSettings->retirement_enabled)
                                        Increases asset (money advanced)
                                    @else
                                        Direct posting to expenses
                                    @endif
                                </small>
                            </div>

                            <div class="bg-light p-3 rounded border-left-success" style="border-left: 4px solid #198754;">
                                <span class="badge bg-success mb-2">CREDIT</span>
                                <div class="fw-bold text-dark">Bank/Cash Account</div>
                                <small class="text-muted d-block mt-1">Decreases cash (money paid out)</small>
                            </div>
                        </div>

                        <hr>

                        <div class="alert alert-info alert-sm mb-0">
                            <i class="bx bx-check-circle me-2"></i>
                            <small><strong>Auto-Approved:</strong> Automatically approved since request is already approved.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.bg-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white;
}

.card {
    border: none;
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1) !important;
}

.form-control, .form-select {
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
}

.input-group-text {
    border: 2px solid #e9ecef !important;
}

.form-control.border-start-0, .form-select {
    border-left: 0 !important;
}

.input-group-lg .form-control,
.input-group-lg .form-select {
    padding: 0.75rem 1rem;
    font-size: 1rem;
}

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-weight: 600;
}

.border-left-primary {
    border-left: 4px solid #667eea !important;
}

.border-left-success {
    border-left: 4px solid #198754 !important;
}

.form-label {
    color: #2c3e50;
    margin-bottom: 0.75rem;
}

.alert-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    border-radius: 0.5rem;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

.badge {
    font-weight: 600;
    padding: 0.5rem 0.75rem;
}
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Form submission with loading state
    $('#disbursementForm').on('submit', function(e) {
        const submitBtn = $('#submitBtn');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>Processing...');
    });
    
    // Real-time amount validation
    $('#amount').on('input', function() {
        const amount = parseFloat($(this).val()) || 0;
        const maxAmount = parseFloat($(this).attr('max')) || 0;
        const formGroup = $(this).closest('.form-group');
        
        if (amount > maxAmount) {
            $(this).addClass('is-invalid');
            formGroup.find('.text-danger').remove();
            formGroup.append('<div class="text-danger small mt-1">Amount cannot exceed TZS ' + maxAmount.toLocaleString() + '</div>');
        } else if (amount > 0) {
            $(this).removeClass('is-invalid');
            formGroup.find('.text-danger').remove();
        }
    });
});
</script>
@endpush
