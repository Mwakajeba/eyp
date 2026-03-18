@extends('layouts.main')

@section('title', 'Year-to-Date Summary Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Payroll Reports', 'url' => route('hr.payroll-reports.index'), 'icon' => 'bx bx-money'],
                ['label' => 'Year-to-Date Summary', 'url' => '#', 'icon' => 'bx bx-calendar-check']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-calendar-check me-2"></i>Year-to-Date Summary Report - {{ $year }}
                            </h4>
                            <div>
                                <form action="{{ route('hr.payroll-reports.year-to-date-summary.export-excel') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bx bx-download me-1"></i>Export Excel
                                    </button>
                                </form>
                                <form action="{{ route('hr.payroll-reports.year-to-date-summary.export-pdf') }}" method="POST" class="d-inline" target="_blank">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
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
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        <!-- YTD Summary Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Payrolls</h5>
                                        <h3 class="mb-0">{{ number_format($ytdTotals['total_payrolls']) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Employees</h5>
                                        <h3 class="mb-0">{{ number_format($ytdTotals['total_employees']) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Gross Salary</h5>
                                        <h3 class="mb-0">{{ number_format($ytdTotals['total_gross_salary'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Net Pay</h5>
                                        <h3 class="mb-0">{{ number_format($ytdTotals['total_net_pay'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- YTD Totals Table -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Year-to-Date Totals</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>Total Gross Salary:</strong></td>
                                                <td class="text-end">{{ number_format($ytdTotals['total_gross_salary'], 2) }} TZS</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total Allowances:</strong></td>
                                                <td class="text-end">{{ number_format($ytdTotals['total_allowances'], 2) }} TZS</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total Overtime:</strong></td>
                                                <td class="text-end">{{ number_format($ytdTotals['total_overtime'], 2) }} TZS</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total PAYE:</strong></td>
                                                <td class="text-end">{{ number_format($ytdTotals['total_paye'], 2) }} TZS</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total NHIF (Employee):</strong></td>
                                                <td class="text-end">{{ number_format($ytdTotals['total_nhif_employee'], 2) }} TZS</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total NHIF (Employer):</strong></td>
                                                <td class="text-end">{{ number_format($ytdTotals['total_nhif_employer'], 2) }} TZS</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>Total Pension (Employee):</strong></td>
                                                <td class="text-end">{{ number_format($ytdTotals['total_pension_employee'], 2) }} TZS</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total Pension (Employer):</strong></td>
                                                <td class="text-end">{{ number_format($ytdTotals['total_pension_employer'], 2) }} TZS</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total WCF:</strong></td>
                                                <td class="text-end">{{ number_format($ytdTotals['total_wcf'], 2) }} TZS</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total SDL:</strong></td>
                                                <td class="text-end">{{ number_format($ytdTotals['total_sdl'], 2) }} TZS</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total HESLB:</strong></td>
                                                <td class="text-end">{{ number_format($ytdTotals['total_heslb'], 2) }} TZS</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total Deductions:</strong></td>
                                                <td class="text-end">{{ number_format($ytdTotals['total_deductions'], 2) }} TZS</td>
                                            </tr>
                                            <tr class="table-success">
                                                <td><strong>Total Net Pay:</strong></td>
                                                <td class="text-end"><strong>{{ number_format($ytdTotals['total_net_pay'], 2) }} TZS</strong></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Monthly Breakdown Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th class="text-end">Payrolls</th>
                                        <th class="text-end">Employees</th>
                                        <th class="text-end">Gross Salary</th>
                                        <th class="text-end">Net Pay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($monthlyBreakdown as $monthData)
                                        <tr>
                                            <td><strong>{{ $monthData['month'] }}</strong></td>
                                            <td class="text-end">{{ number_format($monthData['payroll_count']) }}</td>
                                            <td class="text-end">{{ number_format($monthData['employee_count']) }}</td>
                                            <td class="text-end">{{ number_format($monthData['gross_salary'], 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($monthData['net_pay'], 2) }} TZS</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td>Total</td>
                                        <td class="text-end">{{ number_format($ytdTotals['total_payrolls']) }}</td>
                                        <td class="text-end">{{ number_format($ytdTotals['total_employees']) }}</td>
                                        <td class="text-end">{{ number_format($ytdTotals['total_gross_salary'], 2) }} TZS</td>
                                        <td class="text-end">{{ number_format($ytdTotals['total_net_pay'], 2) }} TZS</td>
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

