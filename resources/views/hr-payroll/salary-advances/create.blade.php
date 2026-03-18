@extends('layouts.main')

@section('title', 'Create Salary Advance')

@push('styles')
<style>
    .alert-sm {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
    
    .bg-opacity-10 {
        --bs-bg-opacity: 0.1;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }
    
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: box-shadow 0.3s ease-in-out;
    }
    
    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    .step-number {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #0d6efd;
        color: white;
        font-size: 13px;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .calculation-box {
        background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
        border: 2px solid #e3e6f0;
        border-radius: 8px;
        padding: 15px;
    }
</style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Salary Advances', 'url' => route('hr.salary-advances.index'), 'icon' => 'bx bx-credit-card'],
                ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
            ]" />

            <form action="{{ route('hr.salary-advances.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-flex align-items-center justify-content-between mb-4">
                            <h4 class="mb-0 text-uppercase">CREATE SALARY ADVANCE</h4>
                            <div class="page-title-right">
                                <a href="{{ route('hr.salary-advances.index') }}" class="btn btn-secondary me-1">
                                    <i class="bx bx-arrow-back me-1"></i>Back to List
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Header -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="text-primary mb-3">Advance Information</h5>
                                        <div class="mb-3">
                                            <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                                            <input type="date" name="date" id="date"
                                                class="form-control @error('date') is-invalid @enderror"
                                                value="{{ old('date', date('Y-m-d')) }}" required>
                                            @error('date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="branch_id" class="form-label">Branch</label>
                                            <select name="branch_id" id="branch_id"
                                                class="form-select select2-single @error('branch_id') is-invalid @enderror">
                                                <option value="">Select Branch</option>
                                                @foreach($branches as $branch)
                                                    <option value="{{ $branch->id }}" 
                                                        {{ old('branch_id', session('branch_id') ?? auth()->user()->branch_id) == $branch->id ? 'selected' : '' }}>
                                                        {{ $branch->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('branch_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="bank_account_id" class="form-label">Bank Account <span class="text-danger">*</span></label>
                                            <select name="bank_account_id" id="bank_account_id"
                                                class="form-select select2-single @error('bank_account_id') is-invalid @enderror"
                                                required>
                                                <option value="">Select Bank Account</option>
                                                @foreach($bankAccounts as $bankAccount)
                                                    <option value="{{ $bankAccount->id }}" {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                                        {{ $bankAccount->name }} ({{ $bankAccount->account_number }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('bank_account_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="text-primary mb-3">Employee Information</h5>
                                        <div class="mb-3">
                                            <label for="employee_id" class="form-label">Employee <span class="text-danger">*</span></label>
                                            <select name="employee_id" id="employee_id"
                                                class="form-select select2-single @error('employee_id') is-invalid @enderror" required>
                                                <option value="">Select Employee</option>
                                                @foreach($employees as $employee)
                                                    <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                                        {{ $employee->full_name }} ({{ $employee->employee_number }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('employee_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="alert alert-info bg-opacity-10 border-start border-info">
                                            <small>
                                                <i class="bx bx-info-circle me-1"></i>
                                                Select an employee to see their current salary and eligibility details.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <!-- Advance Details Card -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bx bx-list-ul me-2"></i>Advance Details
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="amount" class="form-label">Amount (TZS) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="amount" id="amount"
                                                class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}"
                                                required placeholder="0.00">
                                        </div>
                                        @error('amount')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="monthly_deduction" class="form-label">Monthly Deduction (TZS) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" name="monthly_deduction" id="monthly_deduction"
                                                class="form-control @error('monthly_deduction') is-invalid @enderror"
                                                value="{{ old('monthly_deduction') }}" required placeholder="0.00">
                                        </div>
                                        @error('monthly_deduction')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="repayment_type" class="form-label">Repayment Type <span class="text-danger">*</span></label>
                                        <select name="repayment_type" id="repayment_type" class="form-select @error('repayment_type') is-invalid @enderror" required>
                                            <option value="payroll" {{ old('repayment_type') == 'payroll' ? 'selected' : '' }}>Automatic Payroll Deduction</option>
                                            <option value="manual" {{ old('repayment_type') == 'manual' ? 'selected' : '' }}>Manual Payment Only</option>
                                            <option value="both" {{ old('repayment_type') == 'both' ? 'selected' : '' }}>Both (Payroll & Manual)</option>
                                        </select>
                                        @error('repayment_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                                        <textarea name="reason" id="reason"
                                            class="form-control @error('reason') is-invalid @enderror" rows="4"
                                            placeholder="Please provide a detailed reason for the salary advance..."
                                            required>{{ old('reason') }}</textarea>
                                        @error('reason')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Summary/Calculation Card -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bx bx-calculator me-2"></i>Repayment Summary
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="calculation-box mb-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Advance Amount:</span>
                                        <strong id="summary-amount">TZS 0.00</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Monthly Deduction:</span>
                                        <strong id="summary-deduction">TZS 0.00</strong>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <strong>Repayment Period:</strong>
                                        <strong class="text-primary" id="summary-period">0 months</strong>
                                    </div>
                                </div>

                                <div class="alert alert-warning alert-sm mb-0">
                                    <small>
                                        <i class="bx bx-error-circle me-1"></i>
                                        <strong>Note:</strong> Final approval is subject to HR policy compliance.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Guidelines Card -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="bx bx-info-circle text-info me-2"></i>Quick Guidelines
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush small">
                                    <li class="list-group-item"><i class="bx bx-check text-success me-2"></i>Max advance: 50% of monthly salary</li>
                                    <li class="list-group-item"><i class="bx bx-check text-success me-2"></i>Min employment: 6 months</li>
                                    <li class="list-group-item"><i class="bx bx-check text-success me-2"></i>One active advance at a time</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="row">
                    <div class="col-12">
                        <hr>
                        <div class="d-flex justify-content-end gap-2 mb-4">
                            <a href="{{ route('hr.salary-advances.index') }}" class="btn btn-secondary">
                                <i class="bx bx-x me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Save Advance
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        function updateSummary() {
            const amount = parseFloat($('#amount').val()) || 0;
            const deduction = parseFloat($('#monthly_deduction').val()) || 0;
            
            $('#summary-amount').text('TZS ' + amount.toLocaleString(undefined, {minimumFractionDigits: 2}));
            $('#summary-deduction').text('TZS ' + deduction.toLocaleString(undefined, {minimumFractionDigits: 2}));
            
            if (amount > 0 && deduction > 0) {
                const period = Math.ceil(amount / deduction);
                $('#summary-period').text(period + ' months');
            } else {
                $('#summary-period').text('0 months');
            }
        }

        $('#amount, #monthly_deduction').on('input', updateSummary);
        updateSummary();
    });
</script>
@endpush

