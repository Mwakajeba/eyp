@extends('layouts.main')

@section('title', 'HESLB Loan Details')

@push('styles')
<style>
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ededed;
        font-size: 27px;
    }
    
    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #146c43) !important;
    }
    
    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
    }
    
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffb300) !important;
    }
    
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }
    
    .progress {
        height: 25px;
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
                ['label' => $loan->loan_number ?? 'Loan Details', 'url' => '#', 'icon' => 'bx bx-show']
            ]" />

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0 text-uppercase">HESLB LOAN DETAILS</h4>
                    <p class="text-muted mb-0">{{ $loan->loan_number ?? 'Loan #' . $loan->id }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.heslb-loans.edit', $loan->id) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <a href="{{ route('hr.heslb-loans.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back to List
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Statistics Cards -->
                <div class="col-md-12 mb-4">
                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="card radius-10 border-start border-0 border-3 border-primary">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <p class="mb-0 text-secondary">Original Loan Amount</p>
                                            <h4 class="my-1 text-primary">TZS {{ number_format($loan->original_loan_amount, 2) }}</h4>
                                            <p class="mb-0 font-13">
                                                <span class="text-primary"><i class="bx bx-money align-middle"></i> Total loan</span>
                                            </p>
                                        </div>
                                        <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                            <i class="bx bx-money"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card radius-10 border-start border-0 border-3 border-warning">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <p class="mb-0 text-secondary">Outstanding Balance</p>
                                            <h4 class="my-1 text-warning">TZS {{ number_format($loan->outstanding_balance, 2) }}</h4>
                                            <p class="mb-0 font-13">
                                                <span class="text-warning"><i class="bx bx-time align-middle"></i> Remaining</span>
                                            </p>
                                        </div>
                                        <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                            <i class="bx bx-time"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card radius-10 border-start border-0 border-3 border-success">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <p class="mb-0 text-secondary">Total Repaid</p>
                                            <h4 class="my-1 text-success">TZS {{ number_format($totalRepaid, 2) }}</h4>
                                            <p class="mb-0 font-13">
                                                <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Paid</span>
                                            </p>
                                        </div>
                                        <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                            <i class="bx bx-check-circle"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card radius-10 border-start border-0 border-3 border-info">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <p class="mb-0 text-secondary">Repayment Progress</p>
                                            <h4 class="my-1 text-info">{{ number_format($repaymentPercentage, 1) }}%</h4>
                                            <p class="mb-0 font-13">
                                                <span class="text-info"><i class="bx bx-trending-up align-middle"></i> Completed</span>
                                            </p>
                                        </div>
                                        <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                            <i class="bx bx-trending-up"></i>
                                        </div>
                                    </div>
                                    <div class="progress mt-2" style="height: 8px;">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $repaymentPercentage }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loan Information -->
                <div class="col-md-8">
                    <div class="card radius-10">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bx bx-info-circle me-2"></i>Loan Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-muted">Employee</label>
                                    <p class="mb-0 fs-5">{{ $loan->employee ? $loan->employee->full_name : 'N/A' }}</p>
                                    @if($loan->employee)
                                        <small class="text-muted">{{ $loan->employee->employee_number }}</small>
                                    @endif
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-muted">Loan Number</label>
                                    <p class="mb-0">{{ $loan->loan_number ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-muted">Loan Start Date</label>
                                    <p class="mb-0">{{ $loan->loan_start_date ? $loan->loan_start_date->format('M d, Y') : 'N/A' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-muted">Expected Completion Date</label>
                                    <p class="mb-0">{{ $loan->loan_end_date ? $loan->loan_end_date->format('M d, Y') : 'N/A' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-muted">Status</label>
                                    <p class="mb-0">
                                        @if(!$loan->is_active)
                                            <span class="badge bg-secondary">Inactive</span>
                                        @elseif($loan->outstanding_balance <= 0)
                                            <span class="badge bg-success">Paid Off</span>
                                        @else
                                            <span class="badge bg-primary">Active</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-muted">Monthly Deduction Percentage (%)</label>
                                    <p class="mb-0">
                                        @if($loan->deduction_percent)
                                            <span class="badge bg-info fs-6">{{ number_format($loan->deduction_percent, 2) }}%</span>
                                        @else
                                            <span class="text-muted">Not Set</span>
                                            <small class="d-block text-muted mt-1">Will use statutory rule or employee setting</small>
                                        @endif
                                    </p>
                                </div>
                                @if($loan->notes)
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold text-muted">Notes</label>
                                    <p class="mb-0">{{ $loan->notes }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Repayment History -->
                    <div class="card radius-10 mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bx bx-history me-2"></i>Repayment History
                                <span class="badge bg-primary ms-2">{{ $loan->repayments->count() }}</span>
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($loan->repayments->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Amount</th>
                                                <th>Balance Before</th>
                                                <th>Balance After</th>
                                                <th>Payment Method</th>
                                                <th>Payroll</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($loan->repayments as $repayment)
                                                <tr>
                                                    <td>{{ $repayment->repayment_date ? $repayment->repayment_date->format('M d, Y') : 'N/A' }}</td>
                                                    <td class="fw-bold text-success">TZS {{ number_format($repayment->amount, 2) }}</td>
                                                    <td>TZS {{ number_format($repayment->balance_before, 2) }}</td>
                                                    <td class="fw-bold">TZS {{ number_format($repayment->balance_after, 2) }}</td>
                                                    <td>
                                                        <span class="badge bg-info">{{ ucfirst($repayment->payment_method ?? 'Payroll') }}</span>
                                                    </td>
                                                    <td>
                                                        @if($repayment->payroll)
                                                            <a href="{{ route('hr.payrolls.show', $repayment->payroll->id) }}" class="text-decoration-none">
                                                                {{ $repayment->payroll->year }}/{{ str_pad($repayment->payroll->month, 2, '0', STR_PAD_LEFT) }}
                                                            </a>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="bx bx-info-circle text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2">No repayment records yet.</p>
                                    <small class="text-muted">Repayments will be automatically recorded when payroll is processed.</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bx bx-cog me-2"></i>Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('hr.heslb-loans.edit', $loan->id) }}" class="btn btn-primary">
                                    <i class="bx bx-edit me-1"></i>Edit Loan
                                </a>
                                @if($loan->employee)
                                <a href="{{ route('hr.employees.show', $loan->employee->id) }}" class="btn btn-outline-info">
                                    <i class="bx bx-user me-1"></i>View Employee
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Loan Summary -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bx bx-calculator me-2"></i>Loan Summary
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted d-block">Original Amount</small>
                                <strong class="fs-5">TZS {{ number_format($loan->original_loan_amount, 2) }}</strong>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <small class="text-muted d-block">Total Repaid</small>
                                <strong class="fs-5 text-success">TZS {{ number_format($totalRepaid, 2) }}</strong>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <small class="text-muted d-block">Outstanding Balance</small>
                                <strong class="fs-5 text-warning">TZS {{ number_format($loan->outstanding_balance, 2) }}</strong>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <small class="text-muted d-block">Monthly Deduction Percentage</small>
                                <strong class="fs-5">
                                    @if($loan->deduction_percent)
                                        {{ number_format($loan->deduction_percent, 2) }}%
                                    @else
                                        <span class="text-muted">Not Set</span>
                                        <small class="d-block text-muted mt-1">Uses statutory/employee setting</small>
                                    @endif
                                </strong>
                            </div>
                            <hr>
                            <div class="mb-0">
                                <small class="text-muted d-block">Remaining Percentage</small>
                                <div class="progress mt-2" style="height: 20px;">
                                    @php
                                        $remaining = 100 - $repaymentPercentage;
                                    @endphp
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $remaining }}%">
                                        {{ number_format($remaining, 1) }}%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Created/Updated Info -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bx bx-time me-2"></i>Record Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <small class="text-muted d-block">Created</small>
                                <strong>{{ $loan->created_at->format('M d, Y H:i') }}</strong>
                            </div>
                            <div>
                                <small class="text-muted d-block">Last Updated</small>
                                <strong>{{ $loan->updated_at->format('M d, Y H:i') }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

