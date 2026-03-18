@extends('layouts.main')

@section('title', 'Leave Management')

@push('styles')
<style>
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ededed;
        font-size: 27px;
    }
    
    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #146c43) !important;
    }
    
    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
    }
    
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffb300) !important;
    }
    
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }
</style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Leave Management', 'url' => '#', 'icon' => 'bx bx-calendar-check']
        ]" />
            <h6 class="mb-0 text-uppercase">LEAVE MANAGEMENT</h6>
            <hr />

            <!-- Dashboard Stats -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card radius-10 border-start border-0 border-3 border-primary">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">Leave Types</p>
                                    <h4 class="my-1 text-primary">{{ number_format(count($balances)) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-primary"><i class="bx bx-calendar align-middle"></i> Active types</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                    <i class="bx bx-calendar"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card radius-10 border-start border-0 border-3 border-warning">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">Pending Approvals</p>
                                    <h4 class="my-1 text-warning">{{ number_format($pendingApprovalsCount) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-warning"><i class="bx bx-time-five align-middle"></i> Awaiting approval</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                    <i class="bx bx-time-five"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card radius-10 border-start border-0 border-3 border-success">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">Approved Requests</p>
                                    <h4 class="my-1 text-success">{{ number_format($recentRequests->where('status', 'approved')->count()) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Approved requests</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                    <i class="bx bx-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card radius-10 border-start border-0 border-3 border-info">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">Total Requests</p>
                                    <h4 class="my-1 text-info">{{ number_format($recentRequests->count()) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-info"><i class="bx bx-file align-middle"></i> All requests</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                    <i class="bx bx-file"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leave Balances -->
            @if(count($balances) > 0)
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bx bx-bar-chart me-2"></i>My Leave Balances</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Leave Type</th>
                                                <th>Opening</th>
                                                <th>Accrued</th>
                                                <th>Taken</th>
                                                <th>Pending</th>
                                                <th>Available</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($balances as $balanceInfo)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $balanceInfo['leave_type']->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $balanceInfo['leave_type']->code }}</small>
                                                    </td>
                                                    <td>{{ number_format($balanceInfo['balance']->opening_days, 1) }} days</td>
                                                    <td>{{ number_format($balanceInfo['balance']->accrued_days, 1) }} days</td>
                                                    <td>{{ number_format($balanceInfo['balance']->taken_days, 1) }} days</td>
                                                    <td>{{ number_format($balanceInfo['balance']->pending_hold_days, 1) }} days</td>
                                                    <td>
                                                        <strong
                                                            class="text-{{ $balanceInfo['available'] > 0 ? 'success' : 'danger' }}">
                                                            {{ number_format($balanceInfo['available'], 1) }} days
                                                        </strong>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="bx bx-plus-circle text-primary mb-3" style="font-size: 3rem;"></i>
                            <h5 class="card-title">Apply for Leave</h5>
                            <p class="card-text">Submit a new leave request</p>
                            <a href="{{ route('hr.leave.requests.create') }}" class="btn btn-primary">
                                <i class="bx bx-plus"></i> New Request
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="bx bx-list-ul text-info mb-3" style="font-size: 3rem;"></i>
                            <h5 class="card-title">My Requests</h5>
                            <p class="card-text">View all your leave requests</p>
                            <a href="{{ route('hr.leave.requests.index') }}" class="btn btn-info">
                                <i class="bx bx-list-ul"></i> View Requests
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="bx bx-bar-chart text-success mb-3" style="font-size: 3rem;"></i>
                            <h5 class="card-title">Leave Balances</h5>
                            <p class="card-text">View detailed leave balances</p>
                            <a href="{{ route('hr.leave.balances.index') }}" class="btn btn-success">
                                <i class="bx bx-bar-chart"></i> View Balances
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="bx bx-cog text-warning mb-3" style="font-size: 3rem;"></i>
                            <h5 class="card-title">Leave Types</h5>
                            <p class="card-text">Manage leave type settings</p>
                            <a href="{{ route('hr.leave.types.index') }}" class="btn btn-warning">
                                <i class="bx bx-cog"></i> Manage Types
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Leave Requests -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bx bx-file me-2"></i>Recent Leave Requests</h5>
                                <a href="{{ route('hr.leave.requests.index') }}" class="btn btn-sm btn-primary">
                                    <i class="bx bx-list-ul me-1"></i>View All
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Request #</th>
                                            <th>Employee</th>
                                            <th>Leave Type</th>
                                            <th>Date Range</th>
                                            <th>Days</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentRequests as $request)
                                            <tr>
                                                <td><strong>{{ $request->request_number }}</strong></td>
                                                <td>{{ $request->employee->full_name ?? 'N/A' }}</td>
                                                <td>{{ $request->leaveType->name ?? 'N/A' }}</td>
                                                <td>
                                                    @if($request->segments->isNotEmpty())
                                                        {{ $request->segments->first()->date_range }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                                <td>{{ number_format($request->total_days, 1) }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $request->status_badge }}">
                                                        {{ $request->status_label }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('hr.leave.requests.show', $request) }}"
                                                        class="btn btn-sm btn-info">
                                                        <i class="bx bx-show"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">
                                                    No leave requests found
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection