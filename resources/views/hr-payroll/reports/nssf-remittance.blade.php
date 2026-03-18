@extends('layouts.main')

@section('title', 'NSSF Remittance Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Payroll Reports', 'url' => route('hr.payroll-reports.index'), 'icon' => 'bx bx-money'],
                ['label' => 'NSSF Remittance', 'url' => '#', 'icon' => 'bx bx-receipt']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-receipt me-2"></i>NSSF CONTRIBUTION SCHEDULE
                            </h4>
                            <div>
                                <form action="{{ route('hr.payroll-reports.nssf-remittance.export-excel') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bx bx-download me-1"></i>Export Excel
                                    </button>
                                </form>
                                <form action="{{ route('hr.payroll-reports.nssf-remittance.export-pdf') }}" method="POST" class="d-inline" target="_blank">
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
                                        <h5 class="card-title text-primary">Employees</h5>
                                        <h3 class="mb-0">{{ count($reportData) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Employee Total</h5>
                                        <h3 class="mb-0">{{ number_format($totalEmployee, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Employer Total</h5>
                                        <h3 class="mb-0">{{ number_format($totalEmployer, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Grand Total</h5>
                                        <h3 class="mb-0">{{ number_format($totalEmployee + $totalEmployer, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>S/N</th>
                                        <th>Employee No</th>
                                        <th>Employee Name</th>
                                        <th>NSSF No</th>
                                        <th class="text-end">Pensionable Salary</th>
                                        <th class="text-end">Employee 10%</th>
                                        <th class="text-end">Employer 10%</th>
                                        <th class="text-end">Total Contribution</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $data)
                                        <tr>
                                            <td>{{ $data['sn'] }}</td>
                                            <td>{{ $data['employee_number'] }}</td>
                                            <td>{{ $data['employee_name'] }}</td>
                                            <td>{{ $data['nssf_number'] }}</td>
                                            <td class="text-end">{{ number_format($data['pensionable_salary'], 2) }}</td>
                                            <td class="text-end">{{ number_format($data['employee_contribution'], 2) }}</td>
                                            <td class="text-end">{{ number_format($data['employer_contribution'], 2) }}</td>
                                            <td class="text-end">{{ number_format($data['total_contribution'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No NSSF data available for the selected period</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4">Summary</th>
                                        <th class="text-end">{{ number_format(count($reportData)) }}</th>
                                        <th class="text-end">{{ number_format($totalEmployee, 2) }}</th>
                                        <th class="text-end">{{ number_format($totalEmployer, 2) }}</th>
                                        <th class="text-end">{{ number_format($totalEmployee + $totalEmployer, 2) }}</th>
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

