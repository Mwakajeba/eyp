@extends('layouts.main')

@section('title', 'Retirement Approval History')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Retirement Management', 'url' => route('imprest.retirement.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Approval History', 'url' => '#', 'icon' => 'bx bx-history']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-primary">
                <i class="bx bx-history me-2"></i>Retirement Approval History
            </h5>
            <div>
                <a href="{{ route('retirement.multi-approvals.pending') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Pending
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="bx bx-file-blank me-2"></i>Retirement Request #{{ $retirement->retirement_number }}
                </h6>
            </div>
            <div class="card-body">
                <!-- Request Details -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-md bg-primary text-white rounded-circle me-3">
                                {{ strtoupper(substr($retirement->employee->name, 0, 1)) }}
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $retirement->employee->name }}</h6>
                                <small class="text-muted">{{ $retirement->employee->email }}</small>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <strong>Department:</strong> {{ $retirement->department->name }}
                        </div>
                        <div class="mb-2">
                            <strong>Purpose:</strong> {{ $retirement->purpose }}
                        </div>
                        <div class="mb-2">
                            <strong>Description:</strong>
                            <p class="mt-1 text-muted">{{ $retirement->description ?: 'No description provided' }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-2">
                            <strong>Amount:</strong> 
                            <span class="text-success fs-5">{{ number_format($retirement->total_retirement_amount, 2) }}</span>
                        </div>
                        <div class="mb-2">
                            <strong>Current Status:</strong>
                            @php
                                $statusClass = match($retirement->status) {
                                    'pending' => 'bg-warning',
                                    'approved' => 'bg-success',
                                    'rejected' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                            @endphp
                            <span class="badge {{ $statusClass }}">{{ ucfirst($retirement->status) }}</span>
                        </div>
                        <div class="mb-2">
                            <strong>Requested Date:</strong> {{ $retirement->created_at->format('M d, Y H:i') }}
                        </div>
                        @if($retirement->approved_at)
                            <div class="mb-2">
                                <strong>Approved Date:</strong> {{ $retirement->approved_at->format('M d, Y H:i') }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Approval Timeline -->
                <div class="card border-0 bg-light">
                    <div class="card-header bg-transparent">
                        <h6 class="mb-0">
                            <i class="bx bx-time me-2"></i>Approval Timeline
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($retirement->approvals->count() > 0)
                            <div class="timeline">
                                @foreach($retirement->approvals->sortBy('approval_level') as $approval)
                                    <div class="timeline-item {{ $approval->status === 'approved' ? 'timeline-success' : ($approval->status === 'rejected' ? 'timeline-danger' : 'timeline-warning') }}">
                                        <div class="timeline-marker">
                                            @if($approval->status === 'approved')
                                                <i class="bx bx-check"></i>
                                            @elseif($approval->status === 'rejected')
                                                <i class="bx bx-x"></i>
                                            @else
                                                <i class="bx bx-time"></i>
                                            @endif
                                        </div>
                                        <div class="timeline-content">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1">Level {{ $approval->approval_level }} - {{ $approval->approver->name }}</h6>
                                                    <p class="text-muted mb-1">{{ $approval->approver->email }}</p>
                                                    @if($approval->comments)
                                                        <div class="mt-2">
                                                            <small class="text-muted">Comments:</small>
                                                            <p class="mb-0">{{ $approval->comments }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="text-end">
                                                    @php
                                                        $statusClass = match($approval->status) {
                                                            'approved' => 'bg-success',
                                                            'rejected' => 'bg-danger',
                                                            'pending' => 'bg-warning',
                                                            default => 'bg-secondary'
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $statusClass }}">{{ ucfirst($approval->status) }}</span>
                                                    @if($approval->approved_at || $approval->rejected_at)
                                                        <div class="text-muted small mt-1">
                                                            {{ ($approval->approved_at ?: $approval->rejected_at)->format('M d, Y H:i') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="bx bx-info-circle text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2 mb-0">No approval actions recorded yet.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -38px;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
}

.timeline-success .timeline-marker {
    background: #198754;
}

.timeline-danger .timeline-marker {
    background: #dc3545;
}

.timeline-warning .timeline-marker {
    background: #ffc107;
    color: #000;
}

.timeline-content {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-success .timeline-content {
    border-left: 4px solid #198754;
}

.timeline-danger .timeline-content {
    border-left: 4px solid #dc3545;
}

.timeline-warning .timeline-content {
    border-left: 4px solid #ffc107;
}
</style>
@endsection