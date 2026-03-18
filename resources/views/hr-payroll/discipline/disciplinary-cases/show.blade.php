@extends('layouts.main')

@section('title', 'Disciplinary Case Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Discipline', 'url' => '#', 'icon' => 'bx bx-shield-quarter'],
                ['label' => 'Disciplinary Cases', 'url' => route('hr.disciplinary-cases.index'), 'icon' => 'bx bx-file'],
                ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show'],
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0"><i class="bx bx-file me-2"></i>Disciplinary Case Details</h5>
                    <p class="mb-0 text-muted">Case Number: {{ $disciplinaryCase->case_number }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.disciplinary-cases.edit', $disciplinaryCase->id) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <a href="{{ route('hr.disciplinary-cases.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back
                    </a>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Case Information</h6>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Case Number:</strong>
                                    <p class="text-muted">{{ $disciplinaryCase->case_number }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Case Category:</strong>
                                    <p>
                                        @php
                                            $badges = [
                                                'misconduct' => 'danger',
                                                'absenteeism' => 'warning',
                                                'performance' => 'info',
                                            ];
                                            $badge = $badges[$disciplinaryCase->case_category] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $badge }}">{{ ucfirst($disciplinaryCase->case_category) }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Employee:</strong>
                                    <p class="text-muted">
                                        {{ $disciplinaryCase->employee->employee_number }} - {{ $disciplinaryCase->employee->full_name }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Incident Date:</strong>
                                    <p class="text-muted">{{ optional($disciplinaryCase->incident_date)->format('F d, Y') ?? 'N/A' }}</p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Status:</strong>
                                    <p>
                                        @php
                                            $badges = [
                                                'open' => 'secondary',
                                                'investigating' => 'primary',
                                                'resolved' => 'success',
                                                'closed' => 'dark',
                                            ];
                                            $badge = $badges[$disciplinaryCase->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $badge }}">{{ ucfirst($disciplinaryCase->status) }}</span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Outcome:</strong>
                                    <p>
                                        @if($disciplinaryCase->outcome)
                                            @php
                                                $badges = [
                                                    'verbal_warning' => 'info',
                                                    'written_warning' => 'warning',
                                                    'suspension' => 'danger',
                                                    'termination' => 'dark',
                                                ];
                                                $badge = $badges[$disciplinaryCase->outcome] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $badge }}">{{ ucfirst(str_replace('_', ' ', $disciplinaryCase->outcome)) }}</span>
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if($disciplinaryCase->outcome_date)
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Outcome Date:</strong>
                                    <p class="text-muted">{{ optional($disciplinaryCase->outcome_date)->format('F d, Y') }}</p>
                                </div>
                            </div>
                            @endif

                            <div class="mb-3">
                                <strong>Description:</strong>
                                <p class="text-muted">{{ $disciplinaryCase->description }}</p>
                            </div>

                            @if($disciplinaryCase->resolution_notes)
                            <div class="mb-3">
                                <strong>Resolution Notes:</strong>
                                <p class="text-muted">{{ $disciplinaryCase->resolution_notes }}</p>
                            </div>
                            @endif

                            @if($disciplinaryCase->payroll_impact && is_array($disciplinaryCase->payroll_impact) && count($disciplinaryCase->payroll_impact) > 0)
                            <div class="mb-3">
                                <strong>Payroll Impact:</strong>
                                <div class="mt-2">
                                    <ul class="list-unstyled">
                                        @foreach($disciplinaryCase->payroll_impact as $key => $value)
                                            <li><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Case Timeline</h6>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Reported By:</strong>
                                    <p class="text-muted">
                                        {{ $disciplinaryCase->reportedByUser->name ?? 'N/A' }}
                                        @if($disciplinaryCase->created_at)
                                            <br><small class="text-muted">on {{ $disciplinaryCase->created_at->format('F d, Y g:i A') }}</small>
                                        @endif
                                    </p>
                                </div>
                                @if($disciplinaryCase->investigated_by)
                                <div class="col-md-6">
                                    <strong>Investigated By:</strong>
                                    <p class="text-muted">
                                        {{ $disciplinaryCase->investigatedByUser->name ?? 'N/A' }}
                                    </p>
                                </div>
                                @endif
                            </div>

                            @if($disciplinaryCase->resolved_by)
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Resolved By:</strong>
                                    <p class="text-muted">
                                        {{ $disciplinaryCase->resolvedByUser->name ?? 'N/A' }}
                                    </p>
                                </div>
                                @if($disciplinaryCase->resolved_at)
                                <div class="col-md-6">
                                    <strong>Resolved At:</strong>
                                    <p class="text-muted">{{ $disciplinaryCase->resolved_at->format('F d, Y g:i A') }}</p>
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Quick Actions</h6>
                            
                            <a href="{{ route('hr.disciplinary-cases.edit', $disciplinaryCase->id) }}" class="btn btn-primary w-100 mb-2">
                                <i class="bx bx-edit me-1"></i>Edit Case
                            </a>

                            <a href="{{ route('hr.disciplinary-cases.index') }}" class="btn btn-secondary w-100 mb-2">
                                <i class="bx bx-arrow-back me-1"></i>Back to List
                            </a>

                            <a href="{{ route('hr.employees.show', $disciplinaryCase->employee_id) }}" class="btn btn-info w-100">
                                <i class="bx bx-user me-1"></i>View Employee
                            </a>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Employee Information</h6>
                            
                            <div class="mb-3">
                                <strong>Employee Number:</strong>
                                <p class="text-muted">{{ $disciplinaryCase->employee->employee_number }}</p>
                            </div>

                            <div class="mb-3">
                                <strong>Full Name:</strong>
                                <p class="text-muted">{{ $disciplinaryCase->employee->full_name }}</p>
                            </div>

                            @if($disciplinaryCase->employee->department)
                            <div class="mb-3">
                                <strong>Department:</strong>
                                <p class="text-muted">{{ $disciplinaryCase->employee->department->name }}</p>
                            </div>
                            @endif

                            @if($disciplinaryCase->employee->position)
                            <div class="mb-3">
                                <strong>Position:</strong>
                                <p class="text-muted">{{ $disciplinaryCase->employee->position->title }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
