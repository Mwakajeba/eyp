@extends('layouts.main')

@section('title', 'Payroll Cost Analysis Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Payroll Reports', 'url' => route('hr.payroll-reports.index'), 'icon' => 'bx bx-money'],
                ['label' => 'Payroll Cost Analysis', 'url' => '#', 'icon' => 'bx bx-pie-chart']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-pie-chart me-2"></i>Payroll Cost Analysis Report
                            </h4>
                            <div>
                                <form action="{{ route('hr.payroll-reports.payroll-cost-analysis.export-excel') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bx bx-download me-1"></i>Export Excel
                                    </button>
                                </form>
                                <form action="{{ route('hr.payroll-reports.payroll-cost-analysis.export-pdf') }}" method="POST" class="d-inline" target="_blank">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bx bx-file-pdf me-1"></i>Export PDF
                                    </button>
                                </form>
                            </div>
                        </div>

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

                        <!-- Cost Breakdown Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Basic Salary</h5>
                                        <h3 class="mb-0">{{ number_format($costBreakdown['basic_salary'], 2) }} TZS</h3>
                                        <small class="text-muted">{{ $costBreakdown['total_cost'] > 0 ? number_format(($costBreakdown['basic_salary'] / $costBreakdown['total_cost']) * 100, 1) : 0 }}%</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Allowances</h5>
                                        <h3 class="mb-0">{{ number_format($costBreakdown['allowances'], 2) }} TZS</h3>
                                        <small class="text-muted">{{ $costBreakdown['total_cost'] > 0 ? number_format(($costBreakdown['allowances'] / $costBreakdown['total_cost']) * 100, 1) : 0 }}%</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Overtime</h5>
                                        <h3 class="mb-0">{{ number_format($costBreakdown['overtime'], 2) }} TZS</h3>
                                        <small class="text-muted">{{ $costBreakdown['total_cost'] > 0 ? number_format(($costBreakdown['overtime'] / $costBreakdown['total_cost']) * 100, 1) : 0 }}%</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Employer Contributions</h5>
                                        <h3 class="mb-0">{{ number_format($costBreakdown['employer_contributions'], 2) }} TZS</h3>
                                        <small class="text-muted">{{ $costBreakdown['total_cost'] > 0 ? number_format(($costBreakdown['employer_contributions'] / $costBreakdown['total_cost']) * 100, 1) : 0 }}%</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cost Analysis Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Cost Category</th>
                                        <th class="text-end">Amount (TZS)</th>
                                        <th class="text-end">Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Basic Salary</strong></td>
                                        <td class="text-end">{{ number_format($costBreakdown['basic_salary'], 2) }}</td>
                                        <td class="text-end">{{ $costBreakdown['total_cost'] > 0 ? number_format(($costBreakdown['basic_salary'] / $costBreakdown['total_cost']) * 100, 2) : 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Allowances</strong></td>
                                        <td class="text-end">{{ number_format($costBreakdown['allowances'], 2) }}</td>
                                        <td class="text-end">{{ $costBreakdown['total_cost'] > 0 ? number_format(($costBreakdown['allowances'] / $costBreakdown['total_cost']) * 100, 2) : 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Overtime</strong></td>
                                        <td class="text-end">{{ number_format($costBreakdown['overtime'], 2) }}</td>
                                        <td class="text-end">{{ $costBreakdown['total_cost'] > 0 ? number_format(($costBreakdown['overtime'] / $costBreakdown['total_cost']) * 100, 2) : 0 }}%</td>
                                    </tr>
                                    <tr class="table-info">
                                        <td><strong>Gross Salary</strong></td>
                                        <td class="text-end">{{ number_format($costBreakdown['gross_salary'], 2) }}</td>
                                        <td class="text-end">{{ $costBreakdown['total_cost'] > 0 ? number_format(($costBreakdown['gross_salary'] / $costBreakdown['total_cost']) * 100, 2) : 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Statutory Deductions</strong></td>
                                        <td class="text-end">{{ number_format($costBreakdown['statutory_deductions'], 2) }}</td>
                                        <td class="text-end">{{ $costBreakdown['total_cost'] > 0 ? number_format(($costBreakdown['statutory_deductions'] / $costBreakdown['total_cost']) * 100, 2) : 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Other Deductions</strong></td>
                                        <td class="text-end">{{ number_format($costBreakdown['other_deductions'], 2) }}</td>
                                        <td class="text-end">{{ $costBreakdown['total_cost'] > 0 ? number_format(($costBreakdown['other_deductions'] / $costBreakdown['total_cost']) * 100, 2) : 0 }}%</td>
                                    </tr>
                                    <tr class="table-success">
                                        <td><strong>Net Pay</strong></td>
                                        <td class="text-end">{{ number_format($costBreakdown['net_pay'], 2) }}</td>
                                        <td class="text-end">{{ $costBreakdown['total_cost'] > 0 ? number_format(($costBreakdown['net_pay'] / $costBreakdown['total_cost']) * 100, 2) : 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Employer Contributions</strong></td>
                                        <td class="text-end">{{ number_format($costBreakdown['employer_contributions'], 2) }}</td>
                                        <td class="text-end">{{ $costBreakdown['total_cost'] > 0 ? number_format(($costBreakdown['employer_contributions'] / $costBreakdown['total_cost']) * 100, 2) : 0 }}%</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold table-primary">
                                        <td>Total Cost</td>
                                        <td class="text-end">{{ number_format($costBreakdown['total_cost'], 2) }} TZS</td>
                                        <td class="text-end">100.00%</td>
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

