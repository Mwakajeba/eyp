@extends('layouts.main')

@section('title', 'Edit Salary Advance')

@push('styles')
<style>
    .alert-sm {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
    
    .bg-opacity-10 {
        --bs-bg-opacity: 0.1;
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
            ['label' => 'Salary Advances', 'url' => route('hr.salary-advances.index'), 'icon' => 'bx bx-credit-card'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

            <div class="row">
                <div class="col-lg-8">
                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-0 text-uppercase">EDIT SALARY ADVANCE</h4>
                            <p class="text-muted mb-0">{{ $salaryAdvance->reference }}</p>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bx bx-edit me-2"></i>Edit Salary Advance Request
                            </h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('hr.salary-advances.update', $salaryAdvance) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="row">
                                    <!-- Employee Selection -->
                                    <div class="col-md-6 mb-3">
                                        <label for="employee_id" class="form-label">Employee <span
                                                class="text-danger">*</span></label>
                                        <select name="employee_id" id="employee_id"
                                            class="form-select select2-single @error('employee_id') is-invalid @enderror" required>
                                            <option value="">Select Employee</option>
                                            @foreach($employees as $employee)
                                                <option value="{{ $employee->id }}"
                                                    {{ old('employee_id', $salaryAdvance->employee_id) == $employee->id ? 'selected' : '' }}>
                                                    {{ $employee->full_name }} ({{ $employee->employee_number }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('employee_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Bank Account -->
                                    <div class="col-md-6 mb-3">
                                        <label for="bank_account_id" class="form-label">Bank Account <span
                                                class="text-danger">*</span></label>
                                        <select name="bank_account_id" id="bank_account_id"
                                            class="form-select select2-single @error('bank_account_id') is-invalid @enderror"
                                            required>
                                            <option value="">Select Bank Account</option>
                                            @foreach($bankAccounts as $bankAccount)
                                                <option value="{{ $bankAccount->id }}"
                                                    {{ old('bank_account_id', $salaryAdvance->bank_account_id) == $bankAccount->id ? 'selected' : '' }}>
                                                    {{ $bankAccount->name }} ({{ $bankAccount->account_number }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('bank_account_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Date -->
                                    <div class="col-md-6 mb-3">
                                        <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                                        <input type="date" name="date" id="date"
                                            class="form-control @error('date') is-invalid @enderror"
                                            value="{{ old('date', $salaryAdvance->date->format('Y-m-d')) }}" required>
                                        @error('date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Branch -->
                                    <div class="col-md-6 mb-3">
                                        <label for="branch_id" class="form-label">Branch</label>
                                        <select name="branch_id" id="branch_id"
                                            class="form-select select2-single @error('branch_id') is-invalid @enderror">
                                            <option value="">Select Branch</option>
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}"
                                                    {{ old('branch_id', $salaryAdvance->branch_id) == $branch->id ? 'selected' : '' }}>
                                                    {{ $branch->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('branch_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Amount -->
                                    <div class="col-md-6 mb-3">
                                        <label for="amount" class="form-label">Amount (TZS) <span
                                                class="text-danger">*</span></label>
                                        <input type="number" name="amount" id="amount"
                                            class="form-control @error('amount') is-invalid @enderror"
                                            value="{{ old('amount', $salaryAdvance->amount) }}"
                                            required>
                                        @error('amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Monthly Deduction -->
                                    <div class="col-md-6 mb-3">
                                        <label for="monthly_deduction" class="form-label">Monthly Deduction (TZS) <span
                                                class="text-danger">*</span></label>
                                        <input type="number" name="monthly_deduction" id="monthly_deduction"
                                            class="form-control @error('monthly_deduction') is-invalid @enderror"
                                            value="{{ old('monthly_deduction', $salaryAdvance->monthly_deduction) }}"
                                           required>
                                        @error('monthly_deduction')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Repayment Type -->
                                    <div class="col-md-6 mb-3">
                                        <label for="repayment_type" class="form-label">Repayment Type <span class="text-danger">*</span></label>
                                        <select name="repayment_type" id="repayment_type" class="form-select @error('repayment_type') is-invalid @enderror" required>
                                            <option value="payroll" {{ old('repayment_type', $salaryAdvance->repayment_type) == 'payroll' ? 'selected' : '' }}>Automatic Payroll Deduction</option>
                                            <option value="manual" {{ old('repayment_type', $salaryAdvance->repayment_type) == 'manual' ? 'selected' : '' }}>Manual Payment Only</option>
                                            <option value="both" {{ old('repayment_type', $salaryAdvance->repayment_type) == 'both' ? 'selected' : '' }}>Both (Payroll & Manual)</option>
                                        </select>
                                        @error('repayment_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Reason -->
                                    <div class="col-12 mb-3">
                                        <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                                        <textarea name="reason" id="reason"
                                            class="form-control @error('reason') is-invalid @enderror" rows="4"
                                            placeholder="Please provide a detailed reason for the salary advance..."
                                            required>{{ old('reason', $salaryAdvance->reason) }}</textarea>
                                        @error('reason')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="row">
                                    <div class="col-12">
                                        <hr>
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('hr.salary-advances.show', $salaryAdvance) }}" class="btn btn-secondary">Cancel</a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bx bx-save me-1"></i>Update Salary Advance
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Current Details Card -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="bx bx-info-circle text-info me-2"></i>Current Advance Details
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Employee:</strong><br>
                                <span class="text-muted">{{ $salaryAdvance->employee->full_name ?? 'N/A' }}</span><br>
                                <small class="text-muted">{{ $salaryAdvance->employee->employee_number ?? '' }}</small>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Reference:</strong><br>
                                <span class="badge bg-primary">{{ $salaryAdvance->reference }}</span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Current Amount:</strong><br>
                                <span class="h6 text-success">TZS {{ number_format($salaryAdvance->amount, 2) }}</span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Monthly Deduction:</strong><br>
                                <span class="text-info">TZS {{ number_format($salaryAdvance->monthly_deduction, 2) }}</span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Status:</strong>
                                <span class="badge {{ $salaryAdvance->status === 'active' ? 'bg-success' : ($salaryAdvance->status === 'paid' ? 'bg-info' : 'bg-secondary') }} ms-2">
                                    {{ ucfirst($salaryAdvance->status) }}
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Remaining Balance:</strong><br>
                                <span class="h6 text-warning">TZS {{ number_format($salaryAdvance->remaining_balance ?? $salaryAdvance->amount, 2) }}</span>
                            </div>

                            @if($salaryAdvance->created_at)
                                <div class="mb-0">
                                    <small class="text-muted">
                                        <strong>Created:</strong> {{ $salaryAdvance->created_at->format('M d, Y') }}
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Edit Guidelines Card -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="bx bx-help-circle text-info me-2"></i>Editing Guidelines
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6 class="text-primary mb-2">
                                    <i class="bx bx-edit me-1"></i>What You Can Edit
                                </h6>
                                <ul class="list-unstyled small">
                                    <li class="mb-2">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Change the advance amount
                                    </li>
                                    <li class="mb-2">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Update monthly deduction
                                    </li>
                                    <li class="mb-2">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Modify effective date
                                    </li>
                                    <li class="mb-2">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Update reason/description
                                    </li>
                                    <li class="mb-2">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Change bank account
                                    </li>
                                </ul>
                            </div>

                            <div class="mb-4">
                                <h6 class="text-warning mb-2">
                                    <i class="bx bx-error-circle me-1"></i>Important Notes
                                </h6>
                                <ul class="list-unstyled small">
                                    <li class="mb-2">
                                        <i class="bx bx-info-circle text-info me-1"></i>
                                        Amount changes affect repayment schedule
                                    </li>
                                    <li class="mb-2">
                                        <i class="bx bx-info-circle text-info me-1"></i>
                                        Deduction changes affect payroll calculations
                                    </li>
                                    <li class="mb-0">
                                        <i class="bx bx-info-circle text-info me-1"></i>
                                        Past deductions remain unchanged
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Repayment Schedule Card -->
                    <div class="card">
                        <div class="card-header bg-info bg-opacity-10">
                            <h6 class="mb-0 text-info">
                                <i class="bx bx-calendar-check me-2"></i>Repayment Impact
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="small">
                                <p class="mb-2">
                                    <strong>Current Schedule:</strong> {{ ceil($salaryAdvance->amount / $salaryAdvance->monthly_deduction) }} months
                                </p>
                                <p class="mb-2">
                                    <strong>Payroll Deduction:</strong> Automatic monthly deduction from salary
                                </p>
                                <p class="mb-0">
                                    <strong>Next Deduction:</strong> Will be included in next payroll run
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Change Impact Warning Card -->
                    <div class="card border-warning">
                        <div class="card-header bg-warning">
                            <h6 class="mb-0 text-white">
                                <i class="bx bx-error-circle me-2"></i>Change Impact
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning alert-sm mb-0">
                                <small>
                                    <i class="bx bx-info-circle me-1"></i>
                                    <strong>Review Carefully:</strong> Changes to this salary advance will affect future payroll deductions and repayment schedules. Ensure all modifications are accurate before saving.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


