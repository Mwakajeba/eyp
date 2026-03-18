@extends('layouts.main')

@section('title', 'View Employee Promotion')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Promotions', 'url' => '#', 'icon' => 'bx bx-trending-up'],
                ['label' => 'Employee Promotions', 'url' => route('hr.employee-promotions.index'), 'icon' => 'bx bx-file'],
                ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-trending-up me-1"></i>Employee Promotion Details</h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.employee-promotions.edit', $employeePromotion->id) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <a href="{{ route('hr.employee-promotions.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back
                    </a>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Promotion Information</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Promotion Number:</strong></div>
                                <div class="col-md-8">{{ $employeePromotion->promotion_number }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Employee:</strong></div>
                                <div class="col-md-8">{{ $employeePromotion->employee->full_name }} ({{ $employeePromotion->employee->employee_number }})</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Job Grade:</strong></div>
                                <div class="col-md-8">
                                    {{ $employeePromotion->fromJobGrade->grade_code ?? 'N/A' }} → {{ $employeePromotion->toJobGrade->grade_code }}
                                </div>
                            </div>

                            @if($employeePromotion->fromPosition || $employeePromotion->toPosition)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Position:</strong></div>
                                <div class="col-md-8">
                                    {{ $employeePromotion->fromPosition->title ?? 'N/A' }} → {{ $employeePromotion->toPosition->title ?? 'N/A' }}
                                </div>
                            </div>
                            @endif

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Promotion Date:</strong></div>
                                <div class="col-md-8">{{ $employeePromotion->promotion_date->format('d M Y') }}</div>
                            </div>

                            @if($employeePromotion->effective_date)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Effective Date:</strong></div>
                                <div class="col-md-8">{{ $employeePromotion->effective_date->format('d M Y') }}</div>
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
                                        $badge = $badges[$employeePromotion->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">{{ ucfirst($employeePromotion->status) }}</span>
                                </div>
                            </div>

                            @if($employeePromotion->salary_adjustment_amount)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Salary Adjustment:</strong></div>
                                <div class="col-md-8">{{ number_format($employeePromotion->salary_adjustment_amount, 2) }}</div>
                            </div>
                            @endif

                            @if($employeePromotion->reason)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Reason:</strong></div>
                                <div class="col-md-8">{!! nl2br(e($employeePromotion->reason)) !!}</div>
                            </div>
                            @endif

                            @if($employeePromotion->notes)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Notes:</strong></div>
                                <div class="col-md-8">{!! nl2br(e($employeePromotion->notes)) !!}</div>
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
                                <div class="col-md-7">{{ $employeePromotion->requestedByUser->name ?? 'N/A' }}</div>
                            </div>

                            @if($employeePromotion->approved_by)
                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Approved By:</strong></div>
                                <div class="col-md-7">{{ $employeePromotion->approvedByUser->name ?? 'N/A' }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Approved At:</strong></div>
                                <div class="col-md-7">{{ $employeePromotion->approved_at?->format('d M Y H:i') ?? 'N/A' }}</div>
                            </div>
                            @endif

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Created:</strong></div>
                                <div class="col-md-7">{{ $employeePromotion->created_at->format('d M Y H:i') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

