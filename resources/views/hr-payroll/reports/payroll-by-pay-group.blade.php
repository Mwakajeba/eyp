@extends('layouts.main')

@section('title', 'Payroll by Pay Group Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Payroll Reports', 'url' => route('hr.payroll-reports.index'), 'icon' => 'bx bx-money'],
                ['label' => 'Payroll by Pay Group', 'url' => '#', 'icon' => 'bx bx-group']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-group me-2"></i>Payroll by Pay Group Report
                            </h4>
                            <div>
                                <form action="{{ route('hr.payroll-reports.payroll-by-pay-group.export-excel') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bx bx-download me-1"></i>Export Excel
                                    </button>
                                </form>
                                <form action="{{ route('hr.payroll-reports.payroll-by-pay-group.export-pdf') }}" method="POST" class="d-inline" target="_blank">
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

                        <!-- Summary Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Employees</h5>
                                        <h3 class="mb-0">{{ number_format($totals['total_employees']) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Gross Salary</h5>
                                        <h3 class="mb-0">{{ number_format($totals['total_gross'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Total Deductions</h5>
                                        <h3 class="mb-0">{{ number_format($totals['total_deductions'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Total Net Pay</h5>
                                        <h3 class="mb-0">{{ number_format($totals['total_net'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pay Group Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Pay Group</th>
                                        <th>Frequency</th>
                                        <th class="text-end">Employees</th>
                                        <th class="text-end">Gross Salary</th>
                                        <th class="text-end">Deductions</th>
                                        <th class="text-end">Net Pay</th>
                                        <th class="text-end">Avg. Salary</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($payGroupData as $data)
                                        <tr>
                                            <td><strong>{{ $data['pay_group']->pay_group_code }}</strong><br><small>{{ $data['pay_group']->pay_group_name }}</small></td>
                                            <td>{{ ucfirst($data['pay_group']->payment_frequency ?? 'N/A') }}</td>
                                            <td class="text-end">{{ number_format($data['employee_count']) }}</td>
                                            <td class="text-end">{{ number_format($data['total_gross'], 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($data['total_deductions'], 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($data['total_net'], 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($data['average_salary'], 2) }} TZS</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No payroll data found for the selected period.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td colspan="2">Total</td>
                                        <td class="text-end">{{ number_format($totals['total_employees']) }}</td>
                                        <td class="text-end">{{ number_format($totals['total_gross'], 2) }} TZS</td>
                                        <td class="text-end">{{ number_format($totals['total_deductions'], 2) }} TZS</td>
                                        <td class="text-end">{{ number_format($totals['total_net'], 2) }} TZS</td>
                                        <td class="text-end">{{ $totals['total_employees'] > 0 ? number_format($totals['total_net'] / $totals['total_employees'], 2) : '0.00' }} TZS</td>
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

