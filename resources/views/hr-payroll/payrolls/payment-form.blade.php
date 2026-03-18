@extends('layouts.main')

@section('title', 'Process Salary Payment - ' . $payroll->reference)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Payrolls', 'url' => route('hr.payrolls.index'), 'icon' => 'bx bx-money'],
            ['label' => $payroll->reference, 'url' => route('hr.payrolls.show', $payroll), 'icon' => 'bx bx-file'],
            ['label' => 'Process Payment', 'url' => '#', 'icon' => 'bx bx-credit-card']
        ]" />

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">Process Salary Payment - {{ $payroll->reference }}</h6>
            <a href="{{ route('hr.payrolls.show', $payroll) }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i>Back to Payroll
            </a>
        </div>
        <hr />

        <div class="row">
            <div class="col-lg-8">
                <!-- Payment Form Card -->
                <div class="card">
                    <div class="card-body p-4">
                        <form id="paymentForm">
                            @csrf
                            
                            <!-- Payroll Summary -->
                            <div class="row mb-4 p-3 bg-light rounded">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-2">Payroll Period</h6>
                                    <p class="h5 mb-0">{{ $payroll->month_name }} {{ $payroll->year }}</p>
                                    <small class="text-muted">{{ $payroll->reference }}</small>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <h6 class="text-muted mb-2">Net Salary Amount</h6>
                                    <p class="h4 mb-0 text-success fw-bold">TZS {{ number_format($netSalary, 2) }}</p>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h5 class="mb-3">Payment Details</h5>

                            <div class="row g-3">
                                <!-- Payment Date -->
                                <div class="col-md-6">
                                    <label class="form-label">
                                        Payment Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" name="payment_date" 
                                           value="{{ date('Y-m-d') }}" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Bank Account -->
                                <div class="col-md-6">
                                    <label class="form-label">
                                        Bank Account <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" name="bank_account_id" required>
                                        <option value="">-- Select Bank Account --</option>
                                        @foreach($bankAccounts as $account)
                                            <option value="{{ $account->id }}">
                                                {{ $account->account_name }} ({{ $account->account_code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                    @if($bankAccounts->isEmpty())
                                        <small class="text-danger">
                                            <i class="bx bx-error-circle me-1"></i>No bank accounts configured
                                        </small>
                                    @endif
                                </div>

                                <!-- Payment Reference -->
                                <div class="col-12">
                                    <label class="form-label">
                                        Payment Reference <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" name="payment_reference" 
                                           placeholder="e.g., Bank Transfer REF123456, Cheque #789" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Remarks -->
                                <div class="col-12">
                                    <label class="form-label">Payment Remarks</label>
                                    <textarea class="form-control" name="remarks" rows="3" 
                                              placeholder="Enter any additional notes about this payment..."></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <hr class="my-4">

                            @if($payroll->requires_payment_approval && !$payroll->is_payment_fully_approved)
                                <!-- Payment Approval Required Alert -->
                                <div class="alert alert-warning border-0 mb-4">
                                    <div class="d-flex align-items-start">
                                        <i class="bx bx-info-circle fs-4 me-3 mt-1"></i>
                                        <div>
                                            <strong>Payment Approval Required</strong>
                                            <p class="mb-2 mt-2">
                                                This payment requires approval according to your payment approval settings. 
                                                The payment approval workflow will be automatically initiated when you click "Process Payment".
                                            </p>
                                            <p class="mb-0">
                                                <strong>Current Approval Level:</strong> {{ $payroll->current_payment_approval_level ?? 1 }}<br>
                                                <strong>Status:</strong> Waiting for approvers
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Submit Buttons -->
                            <div class="d-flex justify-content-between gap-2">
                                <a href="{{ route('hr.payrolls.show', $payroll) }}" 
                                   class="btn btn-outline-secondary px-4">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-success px-5">
                                    <i class="bx bx-check-circle me-1"></i>
                                    @if($payroll->requires_payment_approval && !$payroll->is_payment_fully_approved)
                                        Request Payment Approval
                                    @else
                                        Process Payment
                                    @endif
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Payment Summary Card -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0 text-white">
                            <i class="bx bx-calculator me-2"></i>Payment Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="ps-0 text-muted">Gross Salary</td>
                                    <td class="text-end pe-0 fw-semibold">
                                        TZS {{ number_format($payroll->total_gross_pay, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ps-0 text-muted">Total Deductions</td>
                                    <td class="text-end pe-0 text-danger fw-semibold">
                                        - TZS {{ number_format($payroll->total_deductions, 2) }}
                                    </td>
                                </tr>
                                <tr class="border-top">
                                    <td class="ps-0 pt-3">
                                        <strong>Net Payment</strong>
                                    </td>
                                    <td class="text-end pe-0 pt-3">
                                        <h5 class="mb-0 text-success fw-bold">
                                            TZS {{ number_format($netSalary, 2) }}
                                        </h5>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Journal Entry Preview Card -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bx bx-book-content text-info me-2"></i>Journal Entry Preview
                        </h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-bordered mb-3">
                            <thead class="table-light">
                                <tr>
                                    <th>Account</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <small class="text-muted d-block">
                                            {{ $chartAccounts->salaryPayableAccount->account_code ?? '' }}
                                        </small>
                                        <small class="fw-semibold">
                                            {{ $chartAccounts->salaryPayableAccount->account_name ?? 'Salary Payable' }}
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-success">{{ number_format($netSalary, 2) }}</span>
                                    </td>
                                    <td class="text-end text-muted">-</td>
                                </tr>
                                <tr>
                                    <td>
                                        <small class="text-muted d-block">Selected Account</small>
                                        <small class="fw-semibold">Bank/Cash Account</small>
                                    </td>
                                    <td class="text-end text-muted">-</td>
                                    <td class="text-end">
                                        <span class="badge bg-danger">{{ number_format($netSalary, 2) }}</span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th>Total</th>
                                    <th class="text-end">{{ number_format($netSalary, 2) }}</th>
                                    <th class="text-end">{{ number_format($netSalary, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                        <small class="text-muted">
                            <i class="bx bx-info-circle me-1"></i>
                            Double-entry journal will be automatically created
                        </small>
                    </div>
                </div>

                <!-- Important Notice Card -->
                <div class="card border-start border-warning border-4">
                    <div class="card-header bg-warning bg-opacity-10">
                        <h6 class="mb-0 text-warning">
                            <i class="bx bx-error-circle me-2"></i>Important Notice
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0 ps-3 small">
                            @if($payroll->requires_payment_approval && !$payroll->is_payment_fully_approved)
                                <li class="mb-2 text-warning">
                                    <i class="bx bx-info-circle me-1"></i>
                                    <strong>Payment approval is required.</strong> The payment will first be submitted for approval according to your payment approval settings before processing.
                                </li>
                            @endif
                            <li class="mb-2">Ensure sufficient funds in the selected bank account</li>
                            <li class="mb-2">Payment approval workflow will be initiated (if required by settings)</li>
                            <li class="mb-2">Journal entries will be automatically created after approval</li>
                            <li class="mb-2">Payroll status will be updated to "Paid" after full approval</li>
                            <li class="mb-0 text-danger fw-semibold">This action cannot be undone</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.border-4 {
    border-width: 4px !important;
}

.bg-opacity-10 {
    --bs-bg-opacity: 0.1;
}

.table-borderless td {
    padding: 0.75rem 0;
    border: none;
}
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('#paymentForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const submitBtn = $(this).find('button[type="submit"]');
        
        // Show loading state
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader bx-spin me-1"></i>Processing...');
        
        // Clear previous validation errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        $.ajax({
            url: '{{ route("hr.payrolls.process-payment", $payroll) }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Check if payment approval was initiated
                    if (response.requires_approval && response.redirect) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Payment Approval Required',
                            text: response.message,
                            confirmButtonText: 'Go to Payroll Details'
                        }).then(() => {
                            window.location.href = response.redirect;
                        });
                    } else {
                        // Payment processed successfully
                        Swal.fire({
                            icon: 'success',
                            title: 'Payment Processed!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 2000
                        }).then(() => {
                            window.location.href = '{{ route("hr.payrolls.show", $payroll) }}';
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Payment Failed',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        const input = $('[name="' + field + '"]');
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').text(messages[0]);
                    });
                } else {
                    const response = xhr.responseJSON || {};
                    const message = response.message || 'An unexpected error occurred. Please try again.';
                    
                    // If payment approval is required, show info message and redirect
                    if (response.requires_approval && response.redirect) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Payment Approval Required',
                            text: message,
                            confirmButtonText: 'Go to Payroll Details'
                        }).then(() => {
                            window.location.href = response.redirect;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: message
                        });
                    }
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="bx bx-check-circle me-1"></i>Process Payment');
            }
        });
    });
});
</script>
@endpush
@endsection