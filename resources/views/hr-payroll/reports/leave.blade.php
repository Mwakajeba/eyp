@extends('layouts.main')

@section('title', 'Leave Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Payroll Reports', 'url' => route('hr.payroll-reports.index'), 'icon' => 'bx bx-money'],
                ['label' => 'Leave Report', 'url' => '#', 'icon' => 'bx bx-calendar']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-calendar me-2"></i>Leave Report (Payroll Dependency)
                            </h4>
                            <div>
                                <form action="{{ route('hr.payroll-reports.leave.export-excel') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <input type="hidden" name="department_id" value="{{ $departmentId }}">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bx bx-download me-1"></i>Export Excel
                                    </button>
                                </form>
                                <form action="{{ route('hr.payroll-reports.leave.export-pdf') }}" method="POST" class="d-inline" target="_blank">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <input type="hidden" name="department_id" value="{{ $departmentId }}">
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
                                <label class="form-label">Month</label>
                                <select class="form-select" name="month">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Department</label>
                                <select class="form-select select2-single" name="department_id" data-placeholder="All Departments">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
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
                                        <h5 class="card-title text-primary">Total Days Taken</h5>
                                        <h3 class="mb-0">{{ number_format($totalDaysTaken, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Paid Days</h5>
                                        <h3 class="mb-0">{{ number_format($totalPaidDays, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Unpaid Days</h5>
                                        <h3 class="mb-0">{{ number_format($totalUnpaidDays, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Employees on Leave</h5>
                                        <h3 class="mb-0">{{ count(array_unique(array_column($leaveData, 'employee_id'))) }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee</th>
                                        <th>Leave Type</th>
                                        <th class="text-end">Days Taken</th>
                                        <th class="text-end">Paid</th>
                                        <th class="text-end">Unpaid</th>
                                        <th class="text-end">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($leaveData as $data)
                                        <tr>
                                            <td>
                                                <strong>{{ $data['employee_name'] }}</strong>
                                                @if(!empty($data['employee_number']))
                                                    <br><small class="text-muted">{{ $data['employee_number'] }}</small>
                                                @endif
                                                <br><small class="text-muted">{{ $data['department'] }}</small>
                                            </td>
                                            <td>
                                                {{ $data['leave_type'] }}
                                                @if($data['is_paid'])
                                                    <span class="badge bg-success ms-1">Paid</span>
                                                @else
                                                    <span class="badge bg-danger ms-1">Unpaid</span>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ number_format($data['days_taken'], 2) }}</td>
                                            <td class="text-end">
                                                @if($data['paid_days'] > 0)
                                                    <span class="text-success">{{ number_format($data['paid_days'], 2) }}</span>
                                                @else
                                                    <span class="text-muted">0</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($data['unpaid_days'] > 0)
                                                    <span class="text-danger">{{ number_format($data['unpaid_days'], 2) }}</span>
                                                @else
                                                    <span class="text-muted">0</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($data['balance'] !== null)
                                                    {{ number_format($data['balance'], 2) }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No leave data available for the selected period</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>Total</th>
                                        <th>—</th>
                                        <th class="text-end">{{ number_format($totalDaysTaken, 2) }}</th>
                                        <th class="text-end">{{ number_format($totalPaidDays, 2) }}</th>
                                        <th class="text-end">{{ number_format($totalUnpaidDays, 2) }}</th>
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

