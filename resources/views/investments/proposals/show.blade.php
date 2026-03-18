@extends('layouts.main')

@section('title', 'Investment Proposal Details')

@push('css')
<style>
    .card-header.bg-gradient {
        background: linear-gradient(135deg, var(--bs-primary) 0%, #0056b3 100%);
    }
    .card-header.bg-info.bg-gradient {
        background: linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%);
    }
    .card-header.bg-success.bg-gradient {
        background: linear-gradient(135deg, #198754 0%, #146c43 100%);
    }
    .card-header.bg-warning.bg-gradient {
        background: linear-gradient(135deg, #ffc107 0%, #cc9a06 100%);
    }
    .card-header.bg-danger.bg-gradient {
        background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
    }
    .info-item {
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .info-item:last-child {
        border-bottom: none;
    }
    .info-label {
        font-size: 0.85rem;
        color: #6c757d;
        font-weight: 500;
        margin-bottom: 0.25rem;
    }
    .info-value {
        font-size: 1rem;
        color: #212529;
        font-weight: 600;
    }
    .timeline-item {
        position: relative;
        padding-left: 2rem;
        padding-bottom: 1.5rem;
    }
    .timeline-item:last-child {
        padding-bottom: 0;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 0.5rem;
        top: 0.5rem;
        bottom: -0.5rem;
        width: 2px;
        background: #dee2e6;
    }
    .timeline-item:last-child::before {
        display: none;
    }
    .timeline-icon {
        position: absolute;
        left: 0;
        top: 0.25rem;
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        z-index: 1;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Investment Management', 'url' => route('investments.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Proposals', 'url' => route('investments.proposals.index'), 'icon' => 'bx bx-file'],
            ['label' => $proposal->proposal_number, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="mb-0 text-uppercase">PROPOSAL: {{ $proposal->proposal_number }}</h6>
                <small class="text-muted">Created {{ $proposal->created_at->format('M d, Y') }} by {{ $proposal->creator->name ?? 'N/A' }}</small>
            </div>
            <div class="btn-group">
                @if($proposal->canBeConverted())
                <form action="{{ route('investments.proposals.convert', $proposal->hash_id) }}" method="POST" class="d-inline" id="convertForm">
                    @csrf
                    <button type="button" class="btn btn-success" onclick="confirmConvert()">
                        <i class="bx bx-transfer"></i> Convert to Investment
                    </button>
                </form>
                @endif
            </div>
        </div>
        <hr />

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Status Overview -->
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="info-item border-0">
                                    <div class="info-label">Status</div>
                                    <div class="info-value">
                                        @if($proposal->status == 'DRAFT')
                                            <span class="badge bg-secondary fs-6">Draft</span>
                                        @elseif($proposal->status == 'SUBMITTED' || $proposal->status == 'IN_REVIEW')
                                            <span class="badge bg-warning fs-6">Pending Approval</span>
                                        @elseif($proposal->status == 'APPROVED')
                                            <span class="badge bg-success fs-6">Approved</span>
                                        @elseif($proposal->status == 'REJECTED')
                                            <span class="badge bg-danger fs-6">Rejected</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-item border-0">
                                    <div class="info-label">Proposed Amount</div>
                                    <div class="info-value text-success">TZS {{ number_format($proposal->proposed_amount, 2) }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-item border-0">
                                    <div class="info-label">Expected Yield</div>
                                    <div class="info-value">
                                        {{ $proposal->expected_yield ? number_format($proposal->expected_yield, 2) . '%' : 'N/A' }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-item border-0">
                                    <div class="info-label">Current Level</div>
                                    <div class="info-value">Level {{ $proposal->current_approval_level }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Investment Instrument Details -->
                <div class="card mb-3">
                    <div class="card-header bg-primary bg-gradient text-white">
                        <h5 class="mb-0">
                            <i class="bx bx-package me-2"></i>Investment Instrument Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 info-item">
                                <div class="info-label">
                                    <i class="bx bx-category me-1"></i> Instrument Type
                                </div>
                                <div class="info-value">{{ str_replace('_', ' ', $proposal->instrument_type) }}</div>
                            </div>
                            <div class="col-md-6 info-item">
                                <div class="info-label">
                                    <i class="bx bx-building me-1"></i> Issuer
                                </div>
                                <div class="info-value">{{ $proposal->issuer ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6 info-item">
                                <div class="info-label">
                                    <i class="bx bx-shield-quarter me-1"></i> Risk Rating
                                </div>
                                <div class="info-value">
                                    @if($proposal->risk_rating)
                                        <span class="badge bg-{{ $proposal->risk_rating == 'LOW' ? 'success' : ($proposal->risk_rating == 'MEDIUM' ? 'warning' : 'danger') }}">
                                            {{ $proposal->risk_rating }}
                                        </span>
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 info-item">
                                <div class="info-label">
                                    <i class="bx bx-calendar me-1"></i> Tenor
                                </div>
                                <div class="info-value">
                                    @php
                                        $tenorDays = $proposal->tenor_days ?? 0;
                                        $isBondType = in_array($proposal->instrument_type, ['T_BOND', 'CORP_BOND']);
                                        $yearsValue = $tenorDays > 0 ? round($tenorDays / 365, 2) : 0;
                                        $isMultipleOf365 = $tenorDays > 0 && abs($tenorDays % 365) < 1;
                                        
                                        if ($isBondType && $isMultipleOf365 && $yearsValue > 0 && $yearsValue <= 50) {
                                            echo number_format($yearsValue, 2) . ' years (' . number_format($tenorDays) . ' days)';
                                        } else {
                                            echo $tenorDays > 0 ? number_format($tenorDays) . ' days' : 'N/A';
                                        }
                                    @endphp
                                </div>
                            </div>
                            <div class="col-md-6 info-item">
                                <div class="info-label">
                                    <i class="bx bx-book me-1"></i> Accounting Classification
                                </div>
                                <div class="info-value">
                                    <span class="badge bg-info">{{ str_replace('_', ' ', $proposal->proposed_accounting_class) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Details -->
                <div class="card mb-3">
                    <div class="card-header bg-success bg-gradient text-white">
                        <h5 class="mb-0">
                            <i class="bx bx-dollar me-2"></i>Financial Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 info-item">
                                <div class="info-label">
                                    <i class="bx bx-money me-1"></i> Proposed Amount
                                </div>
                                <div class="info-value text-success fs-5">TZS {{ number_format($proposal->proposed_amount, 2) }}</div>
                            </div>
                            <div class="col-md-6 info-item">
                                <div class="info-label">
                                    <i class="bx bx-trending-up me-1"></i> Expected Yield
                                </div>
                                <div class="info-value">
                                    @if($proposal->expected_yield)
                                        <span class="badge bg-success fs-6">{{ number_format($proposal->expected_yield, 2) }}%</span>
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description & Rationale -->
                <div class="card mb-3">
                    <div class="card-header bg-info bg-gradient text-white">
                        <h5 class="mb-0">
                            <i class="bx bx-file-blank me-2"></i>Description & Rationale
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="info-label mb-2">
                                <i class="bx bx-detail me-1"></i> Description
                            </div>
                            <div class="info-value">
                                <div class="bg-light p-3 rounded">
                                    {{ $proposal->description ? nl2br(e($proposal->description)) : '<span class="text-muted">No description provided</span>' }}
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="info-label mb-2">
                                <i class="bx bx-bulb me-1"></i> Rationale <span class="text-muted">(Why this investment?)</span>
                            </div>
                            <div class="info-value">
                                <div class="bg-light p-3 rounded">
                                    {{ $proposal->rationale ? nl2br(e($proposal->rationale)) : '<span class="text-muted">No rationale provided</span>' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Approval History -->
                @if($proposal->approvals->count() > 0)
                <div class="card mb-3">
                    <div class="card-header bg-warning bg-gradient text-white">
                        <h5 class="mb-0">
                            <i class="bx bx-history me-2"></i>Approval History
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            @foreach($proposal->approvals->sortBy('approval_level') as $approval)
                            <div class="timeline-item">
                                <div class="timeline-icon bg-{{ $approval->status == 'approved' ? 'success' : ($approval->status == 'rejected' ? 'danger' : 'warning') }} text-white">
                                    @if($approval->status == 'approved')
                                        <i class="bx bx-check"></i>
                                    @elseif($approval->status == 'rejected')
                                        <i class="bx bx-x"></i>
                                    @else
                                        <i class="bx bx-time"></i>
                                    @endif
                                </div>
                                <div class="ms-4">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1">Level {{ $approval->approval_level }} - {{ $approval->approver_name ?? 'Pending' }}</h6>
                                            <span class="badge bg-{{ $approval->status == 'approved' ? 'success' : ($approval->status == 'rejected' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($approval->status) }}
                                            </span>
                                        </div>
                                        <small class="text-muted">
                                            {{ $approval->approved_at ? $approval->approved_at->format('M d, Y H:i') : 'Pending' }}
                                        </small>
                                    </div>
                                    @if($approval->comments)
                                    <div class="bg-light p-2 rounded small">
                                        <strong>Comments:</strong> {{ $approval->comments }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Approval Actions -->
                @if($canApprove && $proposal->isPending())
                <div class="card mb-3 border-warning">
                    <div class="card-header bg-warning bg-gradient text-dark">
                        <h6 class="mb-0">
                            <i class="bx bx-check-circle me-2"></i>Approval Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('investments.proposals.approve', $proposal->hash_id) }}" method="POST" class="mb-3">
                            @csrf
                            <input type="hidden" name="approval_level" value="{{ $proposal->current_approval_level }}">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Comments</label>
                                <textarea name="comments" class="form-control" rows="3" placeholder="Add your approval comments..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bx bx-check"></i> Approve Proposal
                            </button>
                        </form>

                        <form action="{{ route('investments.proposals.reject', $proposal->hash_id) }}" method="POST" id="rejectForm">
                            @csrf
                            <input type="hidden" name="approval_level" value="{{ $proposal->current_approval_level }}">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Rejection Reason <span class="text-danger">*</span></label>
                                <textarea name="reason" id="rejection_reason" class="form-control" rows="3" required placeholder="Please provide a reason for rejection..."></textarea>
                            </div>
                            <button type="button" class="btn btn-danger w-100" onclick="confirmReject()">
                                <i class="bx bx-x"></i> Reject Proposal
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                <!-- Proposal Information -->
                <div class="card mb-3">
                    <div class="card-header bg-primary bg-gradient text-white">
                        <h6 class="mb-0">
                            <i class="bx bx-info-circle me-2"></i>Proposal Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="info-item">
                            <div class="info-label">Proposal Number</div>
                            <div class="info-value">{{ $proposal->proposal_number }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Created By</div>
                            <div class="info-value">{{ $proposal->creator->name ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Created At</div>
                            <div class="info-value">{{ $proposal->created_at->format('M d, Y H:i A') }}</div>
                        </div>
                        @if($proposal->updated_at != $proposal->created_at)
                        <div class="info-item">
                            <div class="info-label">Last Updated</div>
                            <div class="info-value">{{ $proposal->updated_at->format('M d, Y H:i A') }}</div>
                        </div>
                        @endif
                        @if($proposal->recommender)
                        <div class="info-item">
                            <div class="info-label">Recommended By</div>
                            <div class="info-value">{{ $proposal->recommender->name }}</div>
                        </div>
                        @endif
                        @if($proposal->approved_at)
                        <div class="info-item">
                            <div class="info-label">Approved At</div>
                            <div class="info-value text-success">{{ $proposal->approved_at->format('M d, Y H:i A') }}</div>
                        </div>
                        @if($proposal->approver)
                        <div class="info-item">
                            <div class="info-label">Approved By</div>
                            <div class="info-value">{{ $proposal->approver->name }}</div>
                        </div>
                        @endif
                        @endif
                        @if($proposal->rejected_at)
                        <div class="info-item">
                            <div class="info-label">Rejected At</div>
                            <div class="info-value text-danger">{{ $proposal->rejected_at->format('M d, Y H:i A') }}</div>
                        </div>
                        @if($proposal->rejector)
                        <div class="info-item">
                            <div class="info-label">Rejected By</div>
                            <div class="info-value">{{ $proposal->rejector->name }}</div>
                        </div>
                        @endif
                        @if($proposal->rejection_reason)
                        <div class="info-item">
                            <div class="info-label">Rejection Reason</div>
                            <div class="info-value">
                                <div class="bg-light p-2 rounded small text-danger">
                                    {{ $proposal->rejection_reason }}
                                </div>
                            </div>
                        </div>
                        @endif
                        @endif
                        @if($proposal->converted_to_investment_id)
                        <div class="info-item">
                            <div class="info-label">Converted To Investment</div>
                            <div class="info-value">
                                <a href="{{ route('investments.master.show', \Vinkla\Hashids\Facades\Hashids::encode($proposal->converted_to_investment_id)) }}" class="btn btn-sm btn-info">
                                    <i class="bx bx-link"></i> View Investment
                                </a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header bg-secondary bg-gradient text-white">
                        <h6 class="mb-0">
                            <i class="bx bx-cog me-2"></i>Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('investments.proposals.index') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-arrow-back"></i> Back to Proposals
                            </a>
                            @if($proposal->isDraft() || $proposal->isRejected())
                            <a href="{{ route('investments.proposals.edit', $proposal->hash_id) }}" class="btn btn-outline-warning">
                                <i class="bx bx-edit"></i> Edit Proposal
                            </a>
                            @endif
                            @if($proposal->isDraft())
                            <form action="{{ route('investments.proposals.submit', $proposal->hash_id) }}" method="POST" class="d-grid" id="submitForm">
                                @csrf
                                <button type="button" class="btn btn-outline-primary" onclick="confirmSubmit()">
                                    <i class="bx bx-send"></i> Submit for Approval
                                </button>
                            </form>
                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete('{{ $proposal->hash_id }}', '{{ addslashes($proposal->proposal_number) }}')">
                                <i class="bx bx-trash"></i> Delete Proposal
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
// Function to confirm and submit proposal for approval
function confirmSubmit() {
    Swal.fire({
        title: 'Submit for Approval?',
        text: 'Are you sure you want to submit this proposal for approval? Once submitted, you will not be able to edit it.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Submit',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('submitForm').submit();
        }
    });
}

// Function to confirm and convert proposal to investment
function confirmConvert() {
    Swal.fire({
        title: 'Convert to Investment?',
        text: 'This will convert the approved proposal into an investment master record. This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Convert',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('convertForm').submit();
        }
    });
}

// Function to confirm and reject proposal
function confirmReject() {
    const reason = document.getElementById('rejection_reason').value.trim();
    
    if (!reason) {
        Swal.fire({
            title: 'Rejection Reason Required',
            text: 'Please provide a reason for rejecting this proposal.',
            icon: 'warning',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK'
        });
        return;
    }

    Swal.fire({
        title: 'Reject Proposal?',
        text: 'Are you sure you want to reject this proposal? The rejection reason will be recorded.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Reject',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('rejectForm').submit();
        }
    });
}

// Function to confirm and delete draft proposal
window.confirmDelete = function(proposalHashId, proposalNumber) {
    Swal.fire({
        title: 'Delete Proposal?',
        html: `Are you sure you want to delete proposal <strong>${proposalNumber}</strong>?<br><br>This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Create and submit delete form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/investments/proposals/${proposalHashId}`;
            
            // Add CSRF token
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            // Add method override
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            form.appendChild(methodField);
            
            // Submit the form
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@endpush
