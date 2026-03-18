@extends('layouts.main')

@section('title', 'Pay Group Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Pay Groups', 'url' => route('hr.pay-groups.index'), 'icon' => 'bx bx-group'],
                ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0"><i class="bx bx-group me-2"></i>Pay Group Details</h5>
                    <p class="mb-0 text-muted">{{ $payGroup->pay_group_name }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.pay-groups.edit', $payGroup->id) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <a href="{{ route('hr.pay-groups.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back
                    </a>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Pay Group Information</h6>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Code:</strong>
                                    <p class="text-muted">{{ $payGroup->pay_group_code }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Name:</strong>
                                    <p class="text-muted">{{ $payGroup->pay_group_name }}</p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Payment Frequency:</strong>
                                    <p class="text-muted text-capitalize">{{ $payGroup->payment_frequency }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Status:</strong>
                                    <p>
                                        @if($payGroup->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if($payGroup->cut_off_day || $payGroup->pay_day)
                                <div class="row mb-3">
                                    @if($payGroup->cut_off_day)
                                        <div class="col-md-6">
                                            <strong>Cut-off Day:</strong>
                                            <p class="text-muted">Day {{ $payGroup->cut_off_day }} of month</p>
                                        </div>
                                    @endif
                                    @if($payGroup->pay_day)
                                        <div class="col-md-6">
                                            <strong>Pay Day:</strong>
                                            <p class="text-muted">Day {{ $payGroup->pay_day }} of month</p>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div class="mb-3">
                                <strong>Auto-adjust Weekends/Holidays:</strong>
                                <p class="text-muted">
                                    @if($payGroup->auto_adjust_weekends)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </p>
                            </div>

                            @if($payGroup->description)
                                <div class="mb-3">
                                    <strong>Description:</strong>
                                    <p class="text-muted">{{ $payGroup->description }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Assigned Employees ({{ $employees->count() }})</h6>
                            
                            @if($employees->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Employee Number</th>
                                                <th>Name</th>
                                                <th>Department</th>
                                                <th>Position</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($employees as $employee)
                                                <tr>
                                                    <td>{{ $employee->employee_number }}</td>
                                                    <td>{{ $employee->full_name }}</td>
                                                    <td>{{ $employee->department->name ?? '-' }}</td>
                                                    <td>{{ $employee->position->title ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted mb-0">No employees assigned to this pay group.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Quick Actions</h6>
                            
                            <a href="{{ route('hr.pay-groups.edit', $payGroup->id) }}" class="btn btn-primary w-100 mb-2">
                                <i class="bx bx-edit me-1"></i>Edit Pay Group
                            </a>

                            <a href="{{ route('hr.pay-groups.index') }}" class="btn btn-secondary w-100">
                                <i class="bx bx-arrow-back me-1"></i>Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

