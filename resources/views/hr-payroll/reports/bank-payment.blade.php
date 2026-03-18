@extends('layouts.main')

@section('title', 'Bank Payment Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Payroll Reports', 'url' => route('hr.payroll-reports.index'), 'icon' => 'bx bx-money'],
                ['label' => 'Bank Payment', 'url' => '#', 'icon' => 'bx bx-credit-card']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-credit-card me-2"></i>Bank Payment Report
                            </h4>
                            <div>
                                <form action="{{ route('hr.payroll-reports.bank-payment.export-excel') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                                    <input type="hidden" name="date_to" value="{{ $dateTo }}">
                                    <input type="hidden" name="bank_account_id" value="{{ $bankAccountId ?? '' }}">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bx bx-download me-1"></i>Export Excel
                                    </button>
                                </form>
                                <form action="{{ route('hr.payroll-reports.bank-payment.export-pdf') }}" method="POST" class="d-inline" target="_blank">
                                    @csrf
                                    <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                                    <input type="hidden" name="date_to" value="{{ $dateTo }}">
                                    <input type="hidden" name="bank_account_id" value="{{ $bankAccountId ?? '' }}">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bx bx-file-pdf me-1"></i>Export PDF
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Bank Account</label>
                                <select class="form-select select2-single" name="bank_account_id" data-placeholder="All Bank Accounts">
                                    <option value="">All Bank Accounts</option>
                                    @foreach($bankAccounts as $account)
                                        <option value="{{ $account->id }}" {{ $bankAccountId == $account->id ? 'selected' : '' }}>
                                            {{ $account->name }} - {{ $account->account_number }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="{{ $dateTo }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        <!-- Bank Groups -->
                        @forelse($bankGroups as $bankGroup)
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        @if($bankGroup['bank_account'] && $bankGroup['bank_account_id'])
                                            {{ $bankGroup['bank_account']->name }} - {{ $bankGroup['bank_account']->account_number }}
                                        @elseif($bankGroup['bank_account_id'] && $bankGroup['bank_account_id'] !== 'no_account')
                                            Bank Account #{{ $bankGroup['bank_account_id'] }}
                                        @else
                                            <i class="bx bx-info-circle me-1"></i>Payments Without Bank Account
                                        @endif
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <strong>Payrolls:</strong> {{ $bankGroup['payroll_count'] }}
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Total Amount:</strong> {{ number_format($bankGroup['total_amount'], 2) }} TZS
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Payroll Reference</th>
                                                    <th>Period</th>
                                                    <th>Payment Date</th>
                                                    <th class="text-end">Amount</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($bankGroup['payrolls'] as $payroll)
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('hr.payrolls.show', $payroll->hash_id) }}">
                                                                {{ $payroll->reference }}
                                                            </a>
                                                        </td>
                                                        <td>{{ \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->format('F Y') }}</td>
                                                        <td>{{ $payroll->payment_date ? \Carbon\Carbon::parse($payroll->payment_date)->format('Y-m-d') : 'N/A' }}</td>
                                                        <td class="text-end">{{ number_format($payroll->payrollEmployees->sum('net_salary'), 2) }} TZS</td>
                                                        <td>
                                                            @php
                                                                $status = $payroll->status ?? 'N/A';
                                                                $badgeClass = 'secondary';
                                                                if ($status == 'paid') {
                                                                    $badgeClass = 'success';
                                                                } elseif (in_array($status, ['approved', 'completed'])) {
                                                                    $badgeClass = 'info';
                                                                } elseif ($status == 'processing') {
                                                                    $badgeClass = 'primary';
                                                                } elseif ($status == 'draft') {
                                                                    $badgeClass = 'secondary';
                                                                } elseif ($status == 'cancelled') {
                                                                    $badgeClass = 'danger';
                                                                } else {
                                                                    $badgeClass = 'warning';
                                                                }
                                                            @endphp
                                                            <span class="badge bg-{{ $badgeClass }}">
                                                                {{ ucfirst($status) }}
                                                            </span>
                                                            @if($payroll->payment_status && $payroll->payment_status !== 'pending')
                                                                <br><small class="text-muted">Payment: {{ ucfirst($payroll->payment_status) }}</small>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="alert alert-info">
                                No bank payment records found for the selected criteria.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

