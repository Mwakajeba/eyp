@extends('layouts.main')

@section('title', 'Grievance Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Discipline', 'url' => '#', 'icon' => 'bx bx-shield-quarter'],
                ['label' => 'Grievances', 'url' => route('hr.grievances.index'), 'icon' => 'bx bx-error'],
                ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show'],
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0"><i class="bx bx-error me-2"></i>Grievance Details</h5>
                    <p class="mb-0 text-muted">Grievance Number: {{ $grievance->grievance_number }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.grievances.edit', $grievance->id) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <a href="{{ route('hr.grievances.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back
                    </a>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Grievance Information</h6>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Grievance Number:</strong>
                                    <p class="text-muted">{{ $grievance->grievance_number }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Complaint Type:</strong>
                                    <p>
                                        @php
                                            $complaintBadges = [
                                                'harassment' => 'danger',
                                                'discrimination' => 'warning',
                                                'workplace' => 'info',
                                                'salary' => 'primary',
                                                'other' => 'secondary',
                                            ];
                                            $badge = $complaintBadges[$grievance->complaint_type] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $badge }}">{{ ucfirst($grievance->complaint_type) }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Employee:</strong>
                                    <p class="text-muted">
                                        {{ $grievance->employee->employee_number }} - {{ $grievance->employee->full_name }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Priority:</strong>
                                    <p>
                                        @php
                                            $priorityBadges = [
                                                'low' => 'secondary',
                                                'medium' => 'info',
                                                'high' => 'warning',
                                                'urgent' => 'danger',
                                            ];
                                            $badge = $priorityBadges[$grievance->priority] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $badge }}">{{ ucfirst($grievance->priority) }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Status:</strong>
                                    <p>
                                        @php
                                            $statusBadges = [
                                                'open' => 'secondary',
                                                'investigating' => 'primary',
                                                'resolved' => 'success',
                                                'closed' => 'dark',
                                            ];
                                            $badge = $statusBadges[$grievance->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $badge }}">{{ ucfirst($grievance->status) }}</span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Assigned To:</strong>
                                    <p class="text-muted">
                                        {{ $grievance->assignedToUser->name ?? 'Unassigned' }}
                                    </p>
                                </div>
                            </div>

                            <div class="mb-3">
                                <strong>Description:</strong>
                                <p class="text-muted">{{ $grievance->description }}</p>
                            </div>

                            @if($grievance->investigation_notes)
                                <div class="mb-3">
                                    <strong>Investigation Notes:</strong>
                                    <p class="text-muted">{{ $grievance->investigation_notes }}</p>
                                </div>
                            @endif

                            @if($grievance->resolution)
                                <div class="mb-3">
                                    <strong>Resolution:</strong>
                                    <p class="text-muted">{{ $grievance->resolution }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Grievance Timeline</h6>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Created At:</strong>
                                    <p class="text-muted">
                                        {{ optional($grievance->created_at)->format('F d, Y g:i A') ?? 'N/A' }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Last Updated:</strong>
                                    <p class="text-muted">
                                        {{ optional($grievance->updated_at)->format('F d, Y g:i A') ?? 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            @if($grievance->resolved_by || $grievance->resolved_at)
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong>Resolved By:</strong>
                                        <p class="text-muted">
                                            {{ $grievance->resolvedByUser->name ?? 'N/A' }}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Resolved At:</strong>
                                        <p class="text-muted">
                                            {{ optional($grievance->resolved_at)->format('F d, Y g:i A') ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Quick Actions</h6>

                            <a href="{{ route('hr.grievances.edit', $grievance->id) }}" class="btn btn-primary w-100 mb-2">
                                <i class="bx bx-edit me-1"></i>Edit Grievance
                            </a>

                            <a href="{{ route('hr.grievances.index') }}" class="btn btn-secondary w-100 mb-2">
                                <i class="bx bx-arrow-back me-1"></i>Back to List
                            </a>

                            <a href="{{ route('hr.employees.show', $grievance->employee_id) }}" class="btn btn-info w-100">
                                <i class="bx bx-user me-1"></i>View Employee
                            </a>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Employee Information</h6>

                            <div class="mb-3">
                                <strong>Employee Number:</strong>
                                <p class="text-muted">{{ $grievance->employee->employee_number }}</p>
                            </div>

                            <div class="mb-3">
                                <strong>Full Name:</strong>
                                <p class="text-muted">{{ $grievance->employee->full_name }}</p>
                            </div>

                            @if($grievance->employee->department)
                                <div class="mb-3">
                                    <strong>Department:</strong>
                                    <p class="text-muted">{{ $grievance->employee->department->name }}</p>
                                </div>
                            @endif

                            @if($grievance->employee->position)
                                <div class="mb-3">
                                    <strong>Position:</strong>
                                    <p class="text-muted">{{ $grievance->employee->position->title }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

