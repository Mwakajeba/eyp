@extends('layouts.main')

@section('title', 'View Employee Transfer')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Transfers', 'url' => '#', 'icon' => 'bx bx-transfer'],
                ['label' => 'Employee Transfers', 'url' => route('hr.employee-transfers.index'), 'icon' => 'bx bx-file'],
                ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-transfer me-1"></i>Employee Transfer Details</h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.employee-transfers.edit', $employeeTransfer->id) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <a href="{{ route('hr.employee-transfers.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back
                    </a>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Transfer Information</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Transfer Number:</strong></div>
                                <div class="col-md-8">{{ $employeeTransfer->transfer_number }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Employee:</strong></div>
                                <div class="col-md-8">{{ $employeeTransfer->employee->full_name }} ({{ $employeeTransfer->employee->employee_number }})</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Transfer Type:</strong></div>
                                <div class="col-md-8">
                                    <span class="badge bg-info">{{ ucfirst($employeeTransfer->transfer_type) }}</span>
                                </div>
                            </div>

                            @if($employeeTransfer->fromDepartment || $employeeTransfer->toDepartment)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Department:</strong></div>
                                <div class="col-md-8">
                                    {{ $employeeTransfer->fromDepartment->name ?? 'N/A' }} → {{ $employeeTransfer->toDepartment->name ?? 'N/A' }}
                                </div>
                            </div>
                            @endif

                            @if($employeeTransfer->fromPosition || $employeeTransfer->toPosition)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Position:</strong></div>
                                <div class="col-md-8">
                                    {{ $employeeTransfer->fromPosition->title ?? 'N/A' }} → {{ $employeeTransfer->toPosition->title ?? 'N/A' }}
                                </div>
                            </div>
                            @endif

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Transfer Date:</strong></div>
                                <div class="col-md-8">{{ $employeeTransfer->transfer_date->format('d M Y') }}</div>
                            </div>

                            @if($employeeTransfer->effective_date)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Effective Date:</strong></div>
                                <div class="col-md-8">{{ $employeeTransfer->effective_date->format('d M Y') }}</div>
                            </div>
                            @endif

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Status:</strong></div>
                                <div class="col-md-8">
                                    @php
                                        $badges = [
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            'completed' => 'info',
                                        ];
                                        $badge = $badges[$employeeTransfer->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">{{ ucfirst($employeeTransfer->status) }}</span>
                                </div>
                            </div>

                            @if($employeeTransfer->reason)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Reason:</strong></div>
                                <div class="col-md-8">{!! nl2br(e($employeeTransfer->reason)) !!}</div>
                            </div>
                            @endif

                            @if($employeeTransfer->notes)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Notes:</strong></div>
                                <div class="col-md-8">{!! nl2br(e($employeeTransfer->notes)) !!}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Additional Information</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Requested By:</strong></div>
                                <div class="col-md-7">{{ $employeeTransfer->requestedByUser->name ?? 'N/A' }}</div>
                            </div>

                            @if($employeeTransfer->approved_by)
                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Approved By:</strong></div>
                                <div class="col-md-7">{{ $employeeTransfer->approvedByUser->name ?? 'N/A' }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Approved At:</strong></div>
                                <div class="col-md-7">{{ $employeeTransfer->approved_at?->format('d M Y H:i') ?? 'N/A' }}</div>
                            </div>
                            @endif

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Created:</strong></div>
                                <div class="col-md-7">{{ $employeeTransfer->created_at->format('d M Y H:i') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

