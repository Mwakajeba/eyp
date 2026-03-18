@extends('layouts.main')

@section('title', 'Statutory Compliance Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Payroll Reports', 'url' => route('hr.payroll-reports.index'), 'icon' => 'bx bx-money'],
                ['label' => 'Statutory Compliance', 'url' => '#', 'icon' => 'bx bx-check-circle']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-check-circle me-2"></i>Statutory Compliance Report
                            </h4>
                            <div>
                                <form action="{{ route('hr.payroll-reports.statutory-compliance.export-excel') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bx bx-download me-1"></i>Export Excel
                                    </button>
                                </form>
                                <form action="{{ route('hr.payroll-reports.statutory-compliance.export-pdf') }}" method="POST" class="d-inline" target="_blank">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bx bx-file-pdf me-1"></i>Export PDF
                                    </button>
                                </form>
                                <a href="{{ route('hr.payroll-reports.statutory-compliance-enhanced', request()->query()) }}" class="btn btn-warning btn-sm">
                                    <i class="bx bx-shield me-1"></i> Enhanced
                                </a>
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
                            <div class="col-md-4">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Employee Contributions</h5>
                                        <h3 class="mb-0">{{ number_format($totals['total_employee_contributions'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Employer Contributions</h5>
                                        <h3 class="mb-0">{{ number_format($totals['total_employer_contributions'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Grand Total</h5>
                                        <h3 class="mb-0">{{ number_format($totals['grand_total'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statutory Breakdown Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Statutory Type</th>
                                        <th class="text-end">Employee Contribution</th>
                                        <th class="text-end">Employer Contribution</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-center">Compliance Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($statutoryData as $key => $data)
                                        <tr>
                                            <td><strong>{{ $data['name'] }}</strong></td>
                                            <td class="text-end">{{ number_format($data['employee_total'], 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($data['employer_total'], 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($data['employee_total'] + $data['employer_total'], 2) }} TZS</td>
                                            <td class="text-center">
                                                <span class="badge bg-success">{{ number_format($data['compliance_rate'], 1) }}%</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td>Total</td>
                                        <td class="text-end">{{ number_format($totals['total_employee_contributions'], 2) }} TZS</td>
                                        <td class="text-end">{{ number_format($totals['total_employer_contributions'], 2) }} TZS</td>
                                        <td class="text-end">{{ number_format($totals['grand_total'], 2) }} TZS</td>
                                        <td class="text-center">
                                            <span class="badge bg-success">100%</span>
                                        </td>
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

