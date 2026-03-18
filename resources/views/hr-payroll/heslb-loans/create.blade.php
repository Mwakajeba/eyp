@extends('layouts.main')

@section('title', 'Create HESLB Loan')

@push('styles')
<style>
    .alert-sm {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
    
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: box-shadow 0.3s ease-in-out;
    }
    
    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
</style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'HESLB Loans', 'url' => route('hr.heslb-loans.index'), 'icon' => 'bx bx-book'],
                ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
            ]" />

            <div class="row">
                <div class="col-lg-8">
                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-0 text-uppercase">CREATE HESLB LOAN</h4>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bx bx-plus me-2"></i>New HESLB Loan Record
                            </h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('hr.heslb-loans.store') }}" method="POST">
                                @csrf

                                <div class="row">
                                    <!-- Employee Selection -->
                                    <div class="col-md-6 mb-3">
                                        <label for="employee_id" class="form-label">Employee <span class="text-danger">*</span></label>
                                        <select name="employee_id" id="employee_id" class="form-select select2-single @error('employee_id') is-invalid @enderror" required>
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

                                    <!-- Loan Number -->
                                    <div class="col-md-6 mb-3">
                                        <label for="loan_number" class="form-label">Loan Number/Reference</label>
                                        <input type="text" name="loan_number" id="loan_number" class="form-control @error('loan_number') is-invalid @enderror" value="{{ old('loan_number') }}" placeholder="HESLB loan reference number">
                                        @error('loan_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Optional: HESLB loan reference number</small>
                                    </div>

                                    <!-- Original Loan Amount -->
                                    <div class="col-md-6 mb-3">
                                        <label for="original_loan_amount" class="form-label">Original Loan Amount (TZS) <span class="text-danger">*</span></label>
                                        <input type="number" name="original_loan_amount" id="original_loan_amount" step="0.01" min="0.01" class="form-control @error('original_loan_amount') is-invalid @enderror" value="{{ old('original_loan_amount') }}" required>
                                        @error('original_loan_amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Outstanding Balance -->
                                    <div class="col-md-6 mb-3">
                                        <label for="outstanding_balance" class="form-label">Outstanding Balance (TZS) <span class="text-danger">*</span></label>
                                        <input type="number" name="outstanding_balance" id="outstanding_balance" step="0.01" min="0" class="form-control @error('outstanding_balance') is-invalid @enderror" value="{{ old('outstanding_balance') }}" required>
                                        @error('outstanding_balance')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Must be less than or equal to original loan amount</small>
                                    </div>

                                    <!-- Deduction Percentage -->
                                    <div class="col-md-6 mb-3">
                                        <label for="deduction_percent" class="form-label">Deduction Percentage (%)</label>
                                        <input type="number" name="deduction_percent" id="deduction_percent" step="0.01" min="0" max="100" class="form-control @error('deduction_percent') is-invalid @enderror" value="{{ old('deduction_percent', 5) }}" placeholder="e.g., 5.00 for 5%">
                                        @error('deduction_percent')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Percentage of gross salary to deduct (e.g., 5.00 for 5%). If not set, will use statutory rule or employee setting.</small>
                                    </div>

                                    <!-- Loan Start Date -->
                                    <div class="col-md-6 mb-3">
                                        <label for="loan_start_date" class="form-label">Loan Start Date <span class="text-danger">*</span></label>
                                        <input type="date" name="loan_start_date" id="loan_start_date" class="form-control @error('loan_start_date') is-invalid @enderror" value="{{ old('loan_start_date') }}" required>
                                        @error('loan_start_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Loan End Date -->
                                    <div class="col-md-6 mb-3">
                                        <label for="loan_end_date" class="form-label">Expected Completion Date</label>
                                        <input type="date" name="loan_end_date" id="loan_end_date" class="form-control @error('loan_end_date') is-invalid @enderror" value="{{ old('loan_end_date') }}">
                                        @error('loan_end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Optional: Expected loan completion date</small>
                                    </div>

                                    <!-- Active Status -->
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active">
                                                Active Loan
                                            </label>
                                        </div>
                                        <small class="text-muted">Check if this loan is currently active and should be deducted from payroll</small>
                                    </div>

                                    <!-- Notes -->
                                    <div class="col-12 mb-3">
                                        <label for="notes" class="form-label">Notes</label>
                                        <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="Additional notes about this loan...">{{ old('notes') }}</textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="row">
                                    <div class="col-12">
                                        <hr>
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('hr.heslb-loans.index') }}" class="btn btn-secondary">Cancel</a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bx bx-save me-1"></i>Create HESLB Loan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Alternative Option Alert -->
                    <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="bx bx-info-circle me-2 mt-1" style="font-size: 1.25rem;"></i>
                            <div class="flex-grow-1">
                                <strong>Alternative Option:</strong>
                                <p class="mb-2 small">You can also create HESLB loans through <strong>External Loan Management</strong> for more flexibility and better tracking.</p>
                                <a href="{{ route('hr.external-loans.create') }}" class="btn btn-sm btn-outline-info">
                                    <i class="bx bx-credit-card me-1"></i>Create via External Loans
                                </a>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>

                    <!-- Info Card -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="bx bx-info-circle text-info me-2"></i>HESLB Loan Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="text-primary mb-2">
                                    <i class="bx bx-help-circle me-1"></i>What is HESLB?
                                </h6>
                                <p class="small text-muted">
                                    The Higher Education Students' Loans Board (HESLB) provides loans to students for higher education. Employees who received HESLB loans are required to repay them through payroll deductions.
                                </p>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-primary mb-2">
                                    <i class="bx bx-list-check me-1"></i>Important Notes
                                </h6>
                                <ul class="small text-muted mb-0">
                                    <li>Outstanding balance cannot exceed original loan amount</li>
                                    <li>Active loans will be automatically deducted from payroll</li>
                                    <li>Deductions are calculated based on statutory rules</li>
                                    <li>Loan balance is automatically updated after each payroll</li>
                                    <li>Loan is auto-deactivated when balance reaches zero</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-success mb-2">
                                    <i class="bx bx-link-external me-1"></i>External Loan Management
                                </h6>
                                <p class="small text-muted mb-2">
                                    For more advanced features like institution management, reference numbers, and flexible deduction types (fixed or percentage), consider using External Loan Management.
                                </p>
                                <a href="{{ route('hr.external-loans.index') }}" class="btn btn-sm btn-outline-success w-100">
                                    <i class="bx bx-credit-card me-1"></i>Go to External Loans
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // Initialize Select2
        $('.select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });

        // Auto-set outstanding balance to original amount if not set
        $('#original_loan_amount').on('blur', function() {
            const originalAmount = parseFloat($(this).val()) || 0;
            const outstandingBalance = parseFloat($('#outstanding_balance').val()) || 0;
            
            if (outstandingBalance === 0 && originalAmount > 0) {
                $('#outstanding_balance').val(originalAmount.toFixed(2));
            }
        });

        // Validate outstanding balance doesn't exceed original amount
        $('#outstanding_balance').on('blur', function() {
            const originalAmount = parseFloat($('#original_loan_amount').val()) || 0;
            const outstandingBalance = parseFloat($(this).val()) || 0;
            
            if (outstandingBalance > originalAmount) {
                alert('Outstanding balance cannot exceed original loan amount.');
                $(this).val(originalAmount.toFixed(2));
            }
        });
    });
</script>
@endpush

