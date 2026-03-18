@extends('layouts.main')

@section('title', 'Exit Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Discipline', 'url' => '#', 'icon' => 'bx bx-shield-quarter'],
                ['label' => 'Exit Management', 'url' => route('hr.exits.index'), 'icon' => 'bx bx-log-out'],
                ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show'],
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0"><i class="bx bx-log-out me-2"></i>Exit Details</h5>
                    <p class="mb-0 text-muted">Exit Number: {{ $exit->exit_number }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.exits.edit', $exit->id) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <a href="{{ route('hr.exits.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back
                    </a>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Exit Information</h6>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Exit Number:</strong>
                                    <p class="text-muted">{{ $exit->exit_number }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Exit Type:</strong>
                                    <p>
                                        @php
                                            $typeBadges = [
                                                'resignation' => 'info',
                                                'termination' => 'danger',
                                                'retirement' => 'success',
                                                'contract_expiry' => 'warning',
                                                'redundancy' => 'secondary',
                                            ];
                                            $badge = $typeBadges[$exit->exit_type] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $badge }}">{{ ucfirst(str_replace('_', ' ', $exit->exit_type)) }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Employee:</strong>
                                    <p class="text-muted">
                                        {{ $exit->employee->employee_number }} - {{ $exit->employee->full_name }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Effective Date:</strong>
                                    <p class="text-muted">{{ optional($exit->effective_date)->format('F d, Y') ?? 'N/A' }}</p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                @if($exit->resignation_date)
                                    <div class="col-md-6">
                                        <strong>Resignation Date:</strong>
                                        <p class="text-muted">{{ optional($exit->resignation_date)->format('F d, Y') }}</p>
                                    </div>
                                @endif
                                @if(!is_null($exit->notice_period_days))
                                    <div class="col-md-6">
                                        <strong>Notice Period:</strong>
                                        <p class="text-muted">{{ $exit->notice_period_days }} days</p>
                                    </div>
                                @endif
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Clearance Status:</strong>
                                    <p>
                                        @php
                                            $clearBadges = [
                                                'pending' => 'secondary',
                                                'in_progress' => 'warning',
                                                'completed' => 'success',
                                            ];
                                            $badge = $clearBadges[$exit->clearance_status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $badge }}">{{ ucfirst(str_replace('_', ' ', $exit->clearance_status)) }}</span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Final Pay Status:</strong>
                                    <p>
                                        @php
                                            $payBadges = [
                                                'pending' => 'secondary',
                                                'calculated' => 'info',
                                                'approved' => 'warning',
                                                'paid' => 'success',
                                            ];
                                            $badge = $payBadges[$exit->final_pay_status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $badge }}">{{ ucfirst($exit->final_pay_status) }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Final Pay Amount:</strong>
                                    <p class="text-muted">
                                        {{ $exit->final_pay_amount ? number_format($exit->final_pay_amount, 2) : 'N/A' }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Exit Interview Conducted:</strong>
                                    <p class="text-muted">
                                        @if($exit->exit_interview_conducted)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if($exit->exit_reason)
                                <div class="mb-3">
                                    <strong>Exit Reason:</strong>
                                    <p class="text-muted">{{ $exit->exit_reason }}</p>
                                </div>
                            @endif

                            @if($exit->exit_interview_notes)
                                <div class="mb-3">
                                    <strong>Exit Interview Notes:</strong>
                                    <p class="text-muted">{{ $exit->exit_interview_notes }}</p>
                                </div>
                            @endif

                            @if($exit->final_pay_notes)
                                <div class="mb-3">
                                    <strong>Final Pay Notes:</strong>
                                    <p class="text-muted">{{ $exit->final_pay_notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Clearance Items</h6>

                            @php
                                $total = $exit->clearanceItems->count();
                                $completed = $exit->clearanceItems->where('status', 'completed')->count();
                                $percent = $total > 0 ? round(($completed / $total) * 100) : null;
                            @endphp

                            @if($total > 0)
                                <p class="mb-2">
                                    <strong>Progress:</strong>
                                    {{ $completed }} / {{ $total }} items
                                    @if(!is_null($percent))
                                        ({{ $percent }}%)
                                    @endif
                                </p>

                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Item</th>
                                                <th>Department</th>
                                                <th>Status</th>
                                                <th>Completed By</th>
                                                <th>Completed At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($exit->clearanceItems as $index => $item)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $item->clearance_item }}</td>
                                                    <td>{{ $item->department ?? '-' }}</td>
                                                    <td>
                                                        @php
                                                            $statusBadges = [
                                                                'pending' => 'secondary',
                                                                'completed' => 'success',
                                                                'waived' => 'warning',
                                                            ];
                                                            $badge = $statusBadges[$item->status] ?? 'secondary';
                                                        @endphp
                                                        <span class="badge bg-{{ $badge }}">{{ ucfirst($item->status) }}</span>
                                                    </td>
                                                    <td>{{ $item->completedByUser->name ?? '-' }}</td>
                                                    <td>{{ optional($item->completed_at)->format('F d, Y g:i A') ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted mb-0">No clearance items defined for this exit.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Quick Actions</h6>

                            <a href="{{ route('hr.exits.edit', $exit->id) }}" class="btn btn-primary w-100 mb-2">
                                <i class="bx bx-edit me-1"></i>Edit Exit
                            </a>

                            <a href="{{ route('hr.exits.index') }}" class="btn btn-secondary w-100 mb-2">
                                <i class="bx bx-arrow-back me-1"></i>Back to List
                            </a>

                            <a href="{{ route('hr.employees.show', $exit->employee_id) }}" class="btn btn-info w-100">
                                <i class="bx bx-user me-1"></i>View Employee
                            </a>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Employee Information</h6>

                            <div class="mb-3">
                                <strong>Employee Number:</strong>
                                <p class="text-muted">{{ $exit->employee->employee_number }}</p>
                            </div>

                            <div class="mb-3">
                                <strong>Full Name:</strong>
                                <p class="text-muted">{{ $exit->employee->full_name }}</p>
                            </div>

                            @if($exit->employee->department)
                                <div class="mb-3">
                                    <strong>Department:</strong>
                                    <p class="text-muted">{{ $exit->employee->department->name }}</p>
                                </div>
                            @endif

                            @if($exit->employee->position)
                                <div class="mb-3">
                                    <strong>Position:</strong>
                                    <p class="text-muted">{{ $exit->employee->position->title }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

