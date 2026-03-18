@extends('layouts.main')

@section('title', 'Employee Leave Balance')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Leave Management', 'url' => route('hr.leave.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Leave Balances', 'url' => route('hr.leave.balances.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => $employee->full_name, 'url' => '#', 'icon' => 'bx bx-user']
        ]" />
            <h6 class="mb-0 text-uppercase">EMPLOYEE LEAVE BALANCE</h6>
            <hr />

            <!-- Action Buttons -->
            <div class="row mb-3">
                <div class="col-12 text-end">
                    <a href="{{ route('hr.leave.balances.index') }}" class="btn btn-secondary me-2">
                        <i class="bx bx-arrow-back"></i> Back
                    </a>
                    @can('update', App\Models\Hr\LeaveBalance::class)
                        <a href="{{ route('hr.leave.balances.edit', $employee->id) }}" class="btn btn-warning">
                            <i class="bx bx-edit"></i> Adjust Balance
                        </a>
                    @elsecan('adjust leave balance')
                        <a href="{{ route('hr.leave.balances.edit', $employee->id) }}" class="btn btn-warning">
                            <i class="bx bx-edit"></i> Adjust Balance
                        </a>
                    @endcan
                </div>
            </div>

            <!-- Employee Info -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-user me-2"></i>Employee Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>Name:</strong></p>
                                    <p>{{ $employee->full_name }}</p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>Employee Number:</strong></p>
                                    <p>{{ $employee->employee_number }}</p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>Department:</strong></p>
                                    <p>{{ $employee->department->name ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-2"><strong>Position:</strong></p>
                                    <p>{{ $employee->position->name ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leave Balances -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-bar-chart me-2"></i>Leave Balances Summary</h5>
                        </div>
                        <div class="card-body">
                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bx bx-check-circle me-2"></i>
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Leave Type</th>
                                            <th>Opening Balance</th>
                                            <th>Carried Over</th>
                                            <th>Accrued</th>
                                            <th>Taken</th>
                                            <th>Pending</th>
                                            <th>Expired</th>
                                            <th>Adjusted</th>
                                            <th class="text-end">Available</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($balances as $balanceInfo)
                                            <tr>
                                                <td>
                                                    <strong>{{ $balanceInfo['leave_type']->name }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $balanceInfo['leave_type']->code }}</small>
                                                </td>
                                                <td>{{ number_format($balanceInfo['balance']->opening_days, 2) }}</td>
                                                <td>{{ number_format($balanceInfo['balance']->carried_over_days, 2) }}</td>
                                                <td>{{ number_format($balanceInfo['balance']->accrued_days, 2) }}</td>
                                                <td class="text-danger">
                                                    {{ number_format($balanceInfo['balance']->taken_days, 2) }}</td>
                                                <td class="text-warning">
                                                    {{ number_format($balanceInfo['balance']->pending_hold_days, 2) }}</td>
                                                <td class="text-muted">
                                                    {{ number_format($balanceInfo['balance']->expired_days, 2) }}</td>
                                                <td>{{ number_format($balanceInfo['balance']->adjusted_days, 2) }}</td>
                                                <td class="text-end">
                                                    <strong
                                                        class="text-{{ $balanceInfo['available'] > 0 ? 'success' : 'danger' }}">
                                                        {{ number_format($balanceInfo['available'], 2) }} days
                                                    </strong>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-muted">
                                                    No leave balance records found
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <h6 class="alert-heading">
                                            <i class="bx bx-info-circle me-2"></i>Balance Calculation
                                        </h6>
                                        <p class="mb-0 small">
                                            <strong>Available Balance</strong> = Opening + Carried Over + Accrued + Adjusted
                                            - Taken - Pending - Expired
                                        </p>
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
