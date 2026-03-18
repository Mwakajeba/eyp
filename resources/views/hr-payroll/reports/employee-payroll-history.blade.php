@extends('layouts.main')

@section('title', 'Employee Payroll History Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Payroll Reports', 'url' => route('hr.payroll-reports.index'), 'icon' => 'bx bx-money'],
                ['label' => 'Employee Payroll History', 'url' => '#', 'icon' => 'bx bx-user']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-user me-2"></i>Employee Payroll History Report
                            </h4>
                            <div>
                                <form action="{{ route('hr.payroll-reports.employee-payroll-history.export-excel') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="employee_id" value="{{ $employeeId }}">
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <button type="submit" class="btn btn-success btn-sm" {{ !$employee ? 'disabled' : '' }}>
                                        <i class="bx bx-download me-1"></i>Export Excel
                                    </button>
                                </form>
                                <form action="{{ route('hr.payroll-reports.employee-payroll-history.export-pdf') }}" method="POST" class="d-inline" target="_blank">
                                    @csrf
                                    <input type="hidden" name="employee_id" value="{{ $employeeId }}">
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <button type="submit" class="btn btn-danger btn-sm" {{ !$employee ? 'disabled' : '' }}>
                                        <i class="bx bx-file-pdf me-1"></i>Export PDF
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Employee</label>
                                <select class="form-select select2-single" name="employee_id" data-placeholder="Select Employee">
                                    <option value="">All Employees</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->employee_number }} - {{ $emp->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
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

                        @if($employee)
                            <!-- Employee Info -->
                            <div class="card border-primary mb-4">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5 class="mb-3">Employee Information</h5>
                                            <p><strong>Name:</strong> {{ $employee->full_name }}</p>
                                            <p><strong>Employee Number:</strong> {{ $employee->employee_number }}</p>
                                            <p><strong>Department:</strong> {{ $employee->department->name ?? 'N/A' }}</p>
                                            <p><strong>Position:</strong> {{ $employee->position->name ?? 'N/A' }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h5 class="mb-3">Year-to-Date Totals</h5>
                                            <p><strong>Gross Salary:</strong> {{ number_format($ytdTotals['gross_salary'], 2) }} TZS</p>
                                            <p><strong>Total Deductions:</strong> {{ number_format($ytdTotals['total_deductions'], 2) }} TZS</p>
                                            <p><strong>Net Salary:</strong> {{ number_format($ytdTotals['net_salary'], 2) }} TZS</p>
                                            <p><strong>PAYE:</strong> {{ number_format($ytdTotals['paye'], 2) }} TZS</p>
                                            <p><strong>NHIF:</strong> {{ number_format($ytdTotals['nhif'], 2) }} TZS</p>
                                            <p><strong>Pension:</strong> {{ number_format($ytdTotals['pension'], 2) }} TZS</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Payroll History Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th>Reference</th>
                                        <th class="text-end">Basic Salary</th>
                                        <th class="text-end">Allowances</th>
                                        <th class="text-end">Overtime</th>
                                        <th class="text-end">Gross Salary</th>
                                        <th class="text-end">Deductions</th>
                                        <th class="text-end">Net Salary</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($payrollHistory as $history)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::create($history->payroll->year, $history->payroll->month, 1)->format('F Y') }}</td>
                                            <td>{{ $history->payroll->reference }}</td>
                                            <td class="text-end">{{ number_format($history->basic_salary, 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($history->allowance + $history->other_allowances, 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($history->overtime, 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($history->gross_salary, 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($history->total_deductions, 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($history->net_salary, 2) }} TZS</td>
                                            <td>
                                                <span class="badge bg-{{ $history->payroll->status == 'paid' ? 'success' : ($history->payroll->status == 'approved' ? 'info' : 'warning') }}">
                                                    {{ ucfirst($history->payroll->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center">
                                                @if($employeeId)
                                                    No payroll history found for this employee in {{ $year }}.
                                                @else
                                                    Please select an employee to view payroll history.
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if($payrollHistory->count() > 0)
                                    <tfoot>
                                        <tr class="fw-bold">
                                            <td colspan="2">Total</td>
                                            <td class="text-end">{{ number_format($payrollHistory->sum('basic_salary'), 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($payrollHistory->sum('allowance') + $payrollHistory->sum('other_allowances'), 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($payrollHistory->sum('overtime'), 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($payrollHistory->sum('gross_salary'), 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($payrollHistory->sum('total_deductions'), 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($payrollHistory->sum('net_salary'), 2) }} TZS</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

