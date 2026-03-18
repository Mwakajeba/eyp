@extends('layouts.main')

@section('title', 'Combined Statutory Remittance Control Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Payroll Reports', 'url' => route('hr.payroll-reports.index'), 'icon' => 'bx bx-money'],
                ['label' => 'Combined Statutory Remittance', 'url' => '#', 'icon' => 'bx bx-shield']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-shield me-2"></i>COMBINED STATUTORY REMITTANCE CONTROL REPORT
                            </h4>
                            <div>
                                <form action="{{ route('hr.payroll-reports.combined-statutory-remittance.export-excel') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bx bx-download me-1"></i>Export Excel
                                    </button>
                                </form>
                                <form action="{{ route('hr.payroll-reports.combined-statutory-remittance.export-pdf') }}" method="POST" class="d-inline" target="_blank">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bx bx-file-pdf me-1"></i>Export PDF
                                    </button>
                                </form>
                            </div>
                        </div>
                        <p class="text-muted mb-4">
                            <i class="bx bx-info-circle me-1"></i>One-page CFO & Auditor view. Confirms all statutory obligations.
                        </p>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Year</label>
                                <select class="form-select" name="year">
                                    @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Month</label>
                                <select class="form-select" name="month">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        <!-- Summary Card -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Statutory Obligations</h5>
                                        <h3 class="mb-0">{{ number_format($totalAmount, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Statutory</th>
                                        <th class="text-end">Amount Payable (TZS)</th>
                                        <th class="text-center">Due Date</th>
                                        <th class="text-center">Paid Date</th>
                                        <th>Reference No</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($statutoryData as $data)
                                        <tr>
                                            <td><strong>{{ $data['statutory'] }}</strong></td>
                                            <td class="text-end">{{ number_format($data['amount_payable'], 2) }}</td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($data['due_date'])->format('d-M-y') }}</td>
                                            <td class="text-center">
                                                @if($data['paid_date'])
                                                    {{ \Carbon\Carbon::parse($data['paid_date'])->format('d-M-y') }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $data['control_number'] }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ 
                                                    $data['status'] == 'Paid' ? 'success' : (
                                                    $data['status'] == 'Approved' ? 'info' : 'warning'
                                                    ) 
                                                }}">
                                                    {{ $data['status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No payroll data available for the selected period</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer Notes -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="bx bx-check-circle me-2"></i><strong>Auditors check this first</strong><br>
                                    <i class="bx bx-check-circle me-2"></i><strong>Management approval reference</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

