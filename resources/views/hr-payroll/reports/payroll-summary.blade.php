@extends('layouts.main')

@section('title', 'Payroll Summary Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Payroll Reports', 'url' => route('hr.payroll-reports.index'), 'icon' => 'bx bx-money'],
                ['label' => 'Payroll Summary', 'url' => '#', 'icon' => 'bx bx-bar-chart']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-bar-chart me-2"></i>Payroll Summary Report (Executive & Audit Critical)
                            </h4>
                            <div>
                                <form action="{{ route('hr.payroll-reports.payroll-summary.export-excel') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="start_month" value="{{ $startMonth }}">
                                    <input type="hidden" name="end_month" value="{{ $endMonth }}">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bx bx-download me-1"></i>Export Excel
                                    </button>
                                </form>
                                <form action="{{ route('hr.payroll-reports.payroll-summary.export-pdf') }}" method="POST" class="d-inline" target="_blank">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="start_month" value="{{ $startMonth }}">
                                    <input type="hidden" name="end_month" value="{{ $endMonth }}">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bx bx-file-pdf me-1"></i>Export PDF
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Year</label>
                                <select class="form-select" name="year">
                                    @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Start Month</label>
                                <select class="form-select" name="start_month">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ $startMonth == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Month</label>
                                <select class="form-select" name="end_month">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ $endMonth == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        <!-- Summary Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Employees Paid</h5>
                                        <h3 class="mb-0">{{ number_format($totalEmployees) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Gross Pay</h5>
                                        <h3 class="mb-0">{{ number_format($totalGrossPay, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Total Net Pay</h5>
                                        <h3 class="mb-0">{{ number_format($totalNetPay, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Total Employer Statutory</h5>
                                        <h3 class="mb-0">{{ number_format($totalEmployerStatutory, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Total Deductions</h5>
                                        <h3 class="mb-0">{{ number_format($totalDeductions, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-secondary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-secondary">Payrolls Processed</h5>
                                        <h3 class="mb-0">{{ count($summaryData) }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Payroll Period</th>
                                        <th class="text-end">Employees Paid</th>
                                        <th class="text-end">Gross Pay (TZS)</th>
                                        <th class="text-end">Total Deductions (TZS)</th>
                                        <th class="text-end">Net Pay (TZS)</th>
                                        <th class="text-end">Employer Statutory (TZS)</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($summaryData as $data)
                                        <tr>
                                            <td>
                                                <strong>{{ $data['period_label'] }}</strong>
                                                @if(!empty($data['payroll_reference']))
                                                    <br><small class="text-muted">{{ $data['payroll_reference'] }}</small>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ number_format($data['employees_paid']) }}</td>
                                            <td class="text-end">{{ number_format($data['gross_pay'], 2) }}</td>
                                            <td class="text-end">{{ number_format($data['total_deductions'], 2) }}</td>
                                            <td class="text-end">{{ number_format($data['net_pay'], 2) }}</td>
                                            <td class="text-end">{{ number_format($data['employer_statutory'], 2) }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ 
                                                    $data['status'] == 'approved' || $data['status'] == 'completed' ? 'success' : (
                                                    $data['status'] == 'paid' ? 'info' : (
                                                    $data['status'] == 'processing' ? 'primary' : (
                                                    $data['status'] == 'draft' ? 'secondary' : (
                                                    $data['status'] == 'cancelled' ? 'danger' : 'warning'
                                                    )))) 
                                                }}">
                                                    {{ ucfirst($data['status'] ?? 'N/A') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No payroll data available for the selected period</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>Total</th>
                                        <th class="text-end">{{ number_format($totalEmployees) }}</th>
                                        <th class="text-end">{{ number_format($totalGrossPay, 2) }}</th>
                                        <th class="text-end">{{ number_format($totalDeductions, 2) }}</th>
                                        <th class="text-end">{{ number_format($totalNetPay, 2) }}</th>
                                        <th class="text-end">{{ number_format($totalEmployerStatutory, 2) }}</th>
                                        <th>—</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

