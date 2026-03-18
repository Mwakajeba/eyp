@extends('layouts.main')

@section('title', 'View Confirmation Request')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Confirmation', 'url' => '#', 'icon' => 'bx bx-check-circle'],
                ['label' => 'Confirmation Requests', 'url' => route('hr.confirmation-requests.index'), 'icon' => 'bx bx-file'],
                ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-check-circle me-1"></i>Confirmation Request Details</h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.confirmation-requests.edit', $confirmationRequest->id) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <a href="{{ route('hr.confirmation-requests.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back
                    </a>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Request Information</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Employee:</strong></div>
                                <div class="col-md-8">{{ $confirmationRequest->employee->full_name }} ({{ $confirmationRequest->employee->employee_number }})</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Probation Period:</strong></div>
                                <div class="col-md-8">
                                    {{ $confirmationRequest->probation_start_date->format('d M Y') }} - 
                                    {{ $confirmationRequest->probation_end_date->format('d M Y') }}
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Status:</strong></div>
                                <div class="col-md-8">
                                    @php
                                        $badges = [
                                            'pending' => 'secondary',
                                            'manager_review' => 'info',
                                            'hr_review' => 'primary',
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            'extended' => 'warning',
                                        ];
                                        $badge = $badges[$confirmationRequest->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">{{ ucfirst(str_replace('_', ' ', $confirmationRequest->status)) }}</span>
                                </div>
                            </div>

                            @if($confirmationRequest->recommendation_type)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Recommendation:</strong></div>
                                <div class="col-md-8">
                                    @php
                                        $badges = [
                                            'confirm' => 'success',
                                            'extend' => 'warning',
                                            'terminate' => 'danger',
                                        ];
                                        $badge = $badges[$confirmationRequest->recommendation_type] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">{{ ucfirst($confirmationRequest->recommendation_type) }}</span>
                                </div>
                            </div>
                            @endif

                            @if($confirmationRequest->performance_summary)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Performance Summary:</strong></div>
                                <div class="col-md-8">{!! nl2br(e($confirmationRequest->performance_summary)) !!}</div>
                            </div>
                            @endif

                            @if($confirmationRequest->recommendation)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Recommendation Notes:</strong></div>
                                <div class="col-md-8">{!! nl2br(e($confirmationRequest->recommendation)) !!}</div>
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
                                <div class="col-md-7">{{ $confirmationRequest->requestedByUser->name ?? 'N/A' }}</div>
                            </div>

                            @if($confirmationRequest->reviewed_by_manager)
                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Manager Reviewer:</strong></div>
                                <div class="col-md-7">{{ $confirmationRequest->managerReviewer->name ?? 'N/A' }}</div>
                            </div>
                            @endif

                            @if($confirmationRequest->reviewed_by_hr)
                            <div class="row mb-3">
                                <div class="col-md-5"><strong>HR Reviewer:</strong></div>
                                <div class="col-md-7">{{ $confirmationRequest->hrReviewer->name ?? 'N/A' }}</div>
                            </div>
                            @endif

                            @if($confirmationRequest->approved_by)
                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Approved By:</strong></div>
                                <div class="col-md-7">{{ $confirmationRequest->approver->name ?? 'N/A' }}</div>
                            </div>
                            @endif

                            @if($confirmationRequest->salary_adjustment_amount)
                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Salary Adjustment:</strong></div>
                                <div class="col-md-7">{{ number_format($confirmationRequest->salary_adjustment_amount, 2) }}</div>
                            </div>
                            @endif

                            @if($confirmationRequest->confirmation_bonus)
                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Confirmation Bonus:</strong></div>
                                <div class="col-md-7">{{ number_format($confirmationRequest->confirmation_bonus, 2) }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

