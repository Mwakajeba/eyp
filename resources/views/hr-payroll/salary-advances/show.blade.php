@extends('layouts.main')

@section('title', 'Salary Advance Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Salary Advances', 'url' => route('hr.salary-advances.index'), 'icon' => 'bx bx-credit-card'],
                ['label' => 'Advance Details', 'url' => '#', 'icon' => 'bx bx-show']
            ]" />

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0 text-uppercase">SALARY ADVANCE DETAILS</h4>
                        <div class="page-title-right d-flex gap-2">
                            <a href="{{ route('hr.salary-advances.statement-pdf', $salaryAdvance) }}" class="btn btn-outline-info" target="_blank">
                                <i class="bx bx-file-blank me-1"></i>Employee Statement (PDF)
                            </a>
                            @if($salaryAdvance->is_active && $salaryAdvance->remaining_balance > 0)
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#manualRepaymentModal">
                                    <i class="bx bx-money me-1"></i>Record Repayment
                                </button>
                            @endif
                            @can('edit salary advances')
                                <a href="{{ route('hr.salary-advances.edit', $salaryAdvance) }}" class="btn btn-primary">
                                    <i class="bx bx-edit me-1"></i>Edit Advance
                                </a>
                            @endcan
                            <a href="{{ route('hr.salary-advances.index') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i>Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advance Header -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3">Advance Information</h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="180"><strong>Reference:</strong></td>
                                            <td><span class="badge bg-light-primary text-primary">{{ $salaryAdvance->reference }}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Date:</strong></td>
                                            <td>{{ $salaryAdvance->formatted_date }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Branch:</strong></td>
                                            <td>{{ $salaryAdvance->branch->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Bank Account:</strong></td>
                                            <td>
                                                {{ $salaryAdvance->bankAccount->name ?? 'N/A' }}
                                                @if($salaryAdvance->bankAccount)
                                                    <br><small class="text-muted">{{ $salaryAdvance->bankAccount->account_number }}</small>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3">Employee Information</h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="180"><strong>Employee:</strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h6 class="mb-0">{{ $salaryAdvance->employee->full_name ?? 'N/A' }}</h6>
                                                        <small class="text-muted">{{ $salaryAdvance->employee->employee_number ?? '' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Department:</strong></td>
                                            <td>{{ $salaryAdvance->employee->department->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Designation:</strong></td>
                                            <td>{{ $salaryAdvance->employee->position->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created By:</strong></td>
                                            <td>
                                                {{ $salaryAdvance->user->name ?? 'N/A' }}<br>
                                                <small class="text-muted">{{ $salaryAdvance->created_at->format('M d, Y H:i') }}</small>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <!-- Reason Card -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-note me-2"></i>Reason for Advance
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">{{ $salaryAdvance->reason }}</p>
                        </div>
                    </div>

                    <!-- Repayment History -->
                    <div class="card mt-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-history me-2"></i>Repayment History
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($repayments && $repayments->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="repaymentHistoryTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Reference</th>
                                                <th class="text-end">Amount</th>
                                                <th>Recorded By</th>
                                                <th class="text-center">Details</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($repayments as $repayment)
                                                <tr>
                                                    <td>{{ $repayment->date->format('M d, Y') }}</td>
                                                    <td>
                                                        @if($repayment->type === 'payroll')
                                                            <span class="badge bg-info">Payroll Deduction</span>
                                                        @else
                                                            <span class="badge bg-success">Manual Payment</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">{{ $repayment->reference }}</small>
                                                    </td>
                                                    <td class="text-end">
                                                        <strong class="{{ $repayment->type === 'payroll' ? 'text-danger' : 'text-success' }}">
                                                            TZS {{ number_format($repayment->amount, 2) }}
                                                        </strong>
                                                    </td>
                                                    <td>{{ $repayment->user->name ?? 'System' }}</td>
                                                    <td class="text-center">
                                                        @if($repayment->payroll_id)
                                                            <a href="{{ route('hr.payrolls.show', $repayment->payroll->hash_id ?? '') }}" 
                                                               class="btn btn-sm btn-outline-primary" title="View Payroll">
                                                                <i class="bx bx-show"></i>
                                                            </a>
                                                        @elseif($repayment->notes)
                                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                                    data-bs-toggle="tooltip" title="{{ $repayment->notes }}">
                                                                <i class="bx bx-info-circle"></i>
                                                            </button>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info mb-0">
                                    <i class="bx bx-info-circle me-2"></i>No repayment history found for this salary advance.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Summary Card -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-calculator me-2"></i>Advance Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Total Advance:</span>
                                <span class="fw-bold fs-5 text-primary">TZS {{ number_format($salaryAdvance->amount, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Monthly Deduction:</span>
                                <span class="fw-bold text-info">TZS {{ number_format($salaryAdvance->monthly_deduction, 2) }}</span>
                            </div>
                            
                            @php
                                $totalDeducted = $salaryAdvance->total_deductions;
                                $balance = $salaryAdvance->remaining_balance;
                                $progress = $salaryAdvance->amount > 0 ? ($totalDeducted / $salaryAdvance->amount) * 100 : 0;
                            @endphp

                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Total Deducted:</span>
                                <span class="fw-bold text-success">TZS {{ number_format($totalDeducted, 2) }}</span>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-bold">Remaining Balance:</span>
                                <span class="fw-bold text-danger fs-5">TZS {{ number_format($balance, 2) }}</span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mt-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Repayment Progress</span>
                                    <span class="text-muted small fw-bold">{{ round($progress, 1) }}%</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: {{ $progress }}%" 
                                         aria-valuenow="{{ $progress }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-top">
                                <div class="d-flex align-items-center text-muted small">
                                    <i class="bx bx-info-circle me-2"></i>
                                    <span>Estimated repayment period: {{ ceil($salaryAdvance->amount / $salaryAdvance->monthly_deduction) }} months</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        var table = $('#repaymentHistoryTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="bx bx-spreadsheet"></i> Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'Salary Advance Repayment History - {{ $salaryAdvance->reference }}',
                },
                {
                    extend: 'pdf',
                    text: '<i class="bx bx-file"></i> PDF',
                    className: 'btn btn-danger btn-sm',
                    title: 'Salary Advance Repayment History - {{ $salaryAdvance->reference }}',
                }
            ]
        });
    });
</script>
@endpush

    </div>

    <!-- Manual Repayment Modal -->
    <div class="modal fade" id="manualRepaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Manual Repayment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('hr.salary-advances.record-manual-repayment', $salaryAdvance) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Remaining Balance</label>
                            <div class="form-control bg-light">TZS {{ number_format($salaryAdvance->remaining_balance, 2) }}</div>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Repayment Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">TZS</span>
                                <input type="number" name="amount" id="manualRepaymentAmount" class="form-control" 
                                       step="0.01" min="0.01" max="{{ $salaryAdvance->remaining_balance }}" 
                                       value="{{ old('amount', min($salaryAdvance->remaining_balance, $salaryAdvance->monthly_deduction)) }}" 
                                       placeholder="Enter any amount up to remaining balance" required>
                            </div>
                            <div class="form-text">You can enter any amount up to the remaining balance (default is one monthly deduction).</div>
                        </div>
                        <div class="mb-3">
                            <label for="date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" id="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="bank_account_id" class="form-label">Receive Into (Bank/Cash) <span class="text-danger">*</span></label>
                            <select name="bank_account_id" id="bank_account_id" class="form-select select2-single" required>
                                <option value="">Select Account</option>
                                @foreach($bankAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->account_number }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Optional payment details..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Repayment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
