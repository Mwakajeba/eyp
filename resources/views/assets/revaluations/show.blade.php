@extends('layouts.main')

@section('title', 'Revaluation Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Revaluations', 'url' => route('assets.revaluations.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1 text-primary">Revaluation Details</h5>
                <p class="text-muted mb-0">{{ $revaluation->revaluation_number }}</p>
            </div>
            <div class="d-flex gap-2">
                @if($revaluation->status == 'draft')
                    <a href="{{ route('assets.revaluations.edit', \Vinkla\Hashids\Facades\Hashids::encode($revaluation->id)) }}" 
                       class="btn btn-info">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    @if(!$hasRequiredApprovalLevels)
                        <button type="button" class="btn btn-warning" disabled title="No approval levels configured">
                            <i class="bx bx-send me-1"></i>Submit for Approval
                        </button>
                    @else
                        <form action="{{ route('assets.revaluations.submit', \Vinkla\Hashids\Facades\Hashids::encode($revaluation->id)) }}" 
                              method="POST" class="d-inline" id="submitForApprovalForm">
                            @csrf
                            <button type="button" class="btn btn-warning" id="submitForApprovalBtn">
                                <i class="bx bx-send me-1"></i>Submit for Approval
                            </button>
                        </form>
                    @endif
                @endif
                @if($revaluation->status == 'pending_approval' && auth()->user()->can('approve asset revaluations'))
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                        <i class="bx bx-check me-1"></i>Approve
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bx bx-x me-1"></i>Reject
                    </button>
                @endif
                @if($revaluation->status == 'approved' && !$revaluation->gl_posted)
                    <form action="{{ route('assets.revaluations.post-gl', \Vinkla\Hashids\Facades\Hashids::encode($revaluation->id)) }}" 
                          method="POST" class="d-inline" id="postGlForm">
                        @csrf
                        <button type="submit" class="btn btn-primary" id="postGlBtn">
                            <i class="bx bx-book me-1"></i>Post to GL
                        </button>
                    </form>
                @endif
                <a href="{{ route('assets.revaluations.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(!$hasRequiredApprovalLevels && $revaluation->status == 'draft')
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                <strong>Warning:</strong> No approval levels have been configured for asset revaluations. 
                You cannot submit this revaluation for approval until approval levels are configured in the system settings.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Approval Actions Section -->
        @if($revaluation->status === 'pending_approval' && $canApprove && $currentLevel)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-check-circle me-2"></i>Approval Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">You are authorized to approve or reject this revaluation at the current level (<strong>{{ $currentLevel->level_name }}</strong>).</p>
                        
                        <div class="mb-3">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="bx bx-check-circle me-2"></i>Approve
                            </button>
                        </div>
                        
                        <div>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bx bx-x-circle me-2"></i>Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="row">
            <!-- Main Information -->
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Revaluation Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>Revaluation Number:</strong><br>
                                <span class="badge bg-light text-dark">{{ $revaluation->revaluation_number }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Revaluation Date:</strong><br>
                                {{ $revaluation->revaluation_date->format('d M Y') }}
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong><br>
                                @php
                                    $statusColors = [
                                        'draft' => 'secondary',
                                        'pending_approval' => 'warning',
                                        'approved' => 'info',
                                        'posted' => 'success',
                                        'rejected' => 'danger'
                                    ];
                                    $color = $statusColors[$revaluation->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }}">
                                    {{ ucfirst(str_replace('_', ' ', $revaluation->status)) }}
                                </span>
                                @if($revaluation->status === 'pending_approval' && $currentLevel)
                                    <br><small class="text-muted">
                                        <i class="bx bx-user me-1"></i>
                                        Level {{ $revaluation->current_approval_level }} - {{ $currentLevel->level_name }}
                                    </small>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <strong>Valuation Model:</strong><br>
                                {{ ucfirst($revaluation->valuation_model) }} Model
                            </div>
                            <div class="col-md-12">
                                <strong>Reason:</strong><br>
                                {{ $revaluation->reason }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Financial Details</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <td width="50%"><strong>Carrying Amount Before:</strong></td>
                                <td class="text-end">{{ number_format($revaluation->carrying_amount_before ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Accumulated Depreciation Before:</strong></td>
                                <td class="text-end">{{ number_format($revaluation->accumulated_depreciation_before ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Fair Value:</strong></td>
                                <td class="text-end"><strong>{{ number_format($revaluation->fair_value ?? 0, 2) }}</strong></td>
                            </tr>
                            <tr class="table-{{ $revaluation->revaluation_increase > 0 ? 'success' : ($revaluation->revaluation_decrease > 0 ? 'danger' : '') }}">
                                <td><strong>Revaluation Increase:</strong></td>
                                <td class="text-end text-success">
                                    @if($revaluation->revaluation_increase > 0)
                                        +{{ number_format($revaluation->revaluation_increase, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr class="table-{{ $revaluation->revaluation_decrease > 0 ? 'danger' : '' }}">
                                <td><strong>Revaluation Decrease:</strong></td>
                                <td class="text-end text-danger">
                                    @if($revaluation->revaluation_decrease > 0)
                                        -{{ number_format($revaluation->revaluation_decrease, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Carrying Amount After:</strong></td>
                                <td class="text-end"><strong>{{ number_format($revaluation->carrying_amount_after ?? 0, 2) }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-user me-2"></i>Valuer Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <strong>Valuer Name:</strong><br>
                                {{ $revaluation->valuer_name ?? '-' }}
                            </div>
                            <div class="col-md-4">
                                <strong>Valuer License:</strong><br>
                                {{ $revaluation->valuer_license ?? '-' }}
                            </div>
                            <div class="col-md-4">
                                <strong>Valuer Company:</strong><br>
                                {{ $revaluation->valuer_company ?? '-' }}
                            </div>
                            <div class="col-md-6">
                                <strong>Report Reference:</strong><br>
                                {{ $revaluation->valuation_report_ref ?? '-' }}
                            </div>
                            <div class="col-md-6">
                                <strong>Valuation Report:</strong><br>
                                @if($revaluation->valuation_report_path)
                                    <a href="{{ Storage::url($revaluation->valuation_report_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bx bx-file me-1"></i>View Report
                                    </a>
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if($revaluation->journal)
                <div class="card mb-3">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bx bx-book me-2"></i>General Ledger Entry</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>Journal Number:</strong><br>
                                {{ $revaluation->journal->reference_number ?? '-' }}
                            </div>
                            <div class="col-md-6">
                                <strong>Posted Date:</strong><br>
                                {{ $revaluation->gl_posted_at ? $revaluation->gl_posted_at->format('d M Y H:i') : '-' }}
                            </div>
                            @if($revaluation->journal->items && $revaluation->journal->items->count() > 0)
                            <div class="col-12">
                                <strong>Journal Entries:</strong>
                                <table class="table table-sm table-bordered mt-2">
                                    <thead>
                                        <tr>
                                            <th>Account</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($revaluation->journal->items as $item)
                                        <tr>
                                            <td>{{ $item->chartAccount->account_code ?? '' }} - {{ $item->chartAccount->account_name ?? '' }}</td>
                                            <td class="text-end">
                                                @if($item->nature == 'debit')
                                                    {{ number_format($item->amount, 2) }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($item->nature == 'credit')
                                                    {{ number_format($item->amount, 2) }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-package me-2"></i>Asset Information</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Code:</strong><br>{{ $revaluation->asset->code }}</p>
                        <p><strong>Name:</strong><br>{{ $revaluation->asset->name }}</p>
                        <p><strong>Category:</strong><br>{{ $revaluation->asset->category->name ?? '-' }}</p>
                        <p><strong>Purchase Cost:</strong><br>{{ number_format($revaluation->asset->purchase_cost ?? 0, 2) }}</p>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-time me-2"></i>Approval Workflow</h6>
                    </div>
                    <div class="card-body">
                        @if($revaluation->submitted_by)
                            <p><strong>Submitted By:</strong><br>
                                {{ $revaluation->submittedBy->name ?? 'N/A' }}
                                <small class="text-muted">({{ $revaluation->submitted_at ? $revaluation->submitted_at->format('M d, Y H:i') : 'N/A' }})</small>
                            </p>
                        @endif
                        @if($revaluation->status === 'pending_approval' && $currentLevel)
                            <p><strong>Current Level:</strong><br>
                                <span class="badge bg-warning">Level {{ $revaluation->current_approval_level }} - {{ $currentLevel->level_name }}</span>
                            </p>
                            @if($currentApprovers->count() > 0)
                                <p><strong>Current Approvers:</strong><br>
                                    {{ $currentApprovers->pluck('name')->join(', ') }}
                                </p>
                            @endif
                        @endif
                        @if($revaluation->approved_by)
                            <p><strong>Approved By:</strong><br>
                                {{ $revaluation->approvedBy->name ?? 'N/A' }}
                                <small class="text-muted">({{ $revaluation->approved_at ? $revaluation->approved_at->format('M d, Y H:i') : 'N/A' }})</small>
                            </p>
                        @endif
                        @if($revaluation->rejected_by)
                            <p><strong>Rejected By:</strong><br>
                                {{ $revaluation->rejectedBy->name ?? 'N/A' }}
                                <small class="text-muted">({{ $revaluation->rejected_at ? $revaluation->rejected_at->format('M d, Y H:i') : 'N/A' }})</small>
                                @if($revaluation->rejection_reason)
                                    <br><small class="text-danger"><strong>Reason:</strong> {{ $revaluation->rejection_reason }}</small>
                                @endif
                            </p>
                        @endif
                    </div>
                </div>

                @if($approvalHistory && $approvalHistory->count() > 0)
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-history me-2"></i>Approval History</h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            @foreach($approvalHistory as $history)
                            <div class="timeline-item mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        @if($history->action === 'submitted')
                                            <i class="bx bx-send text-info fs-4"></i>
                                        @elseif($history->action === 'approved')
                                            <i class="bx bx-check-circle text-success fs-4"></i>
                                        @elseif($history->action === 'rejected')
                                            <i class="bx bx-x-circle text-danger fs-4"></i>
                                        @else
                                            <i class="bx bx-info-circle text-secondary fs-4"></i>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1">
                                            {{ ucfirst($history->action) }} at {{ $history->approvalLevel->level_name ?? 'N/A' }}
                                        </h6>
                                        <p class="mb-1 text-muted small">
                                            By: {{ $history->approver->name ?? 'System' }}
                                            <span class="ms-2">{{ $history->created_at->format('M d, Y H:i') }}</span>
                                        </p>
                                        @if($history->comments)
                                            <p class="mb-0 small">{{ $history->comments }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                @if($revaluation->attachments && count($revaluation->attachments) > 0)
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-paperclip me-2"></i>Attachments</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            @foreach($revaluation->attachments as $attachment)
                                <li class="mb-2">
                                    <a href="{{ Storage::url($attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                                        <i class="bx bx-file me-1"></i>{{ basename($attachment) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
@if($revaluation->status == 'pending_approval')
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('assets.revaluations.approve', \Vinkla\Hashids\Facades\Hashids::encode($revaluation->id)) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Revaluation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Approval Notes (Optional)</label>
                        <textarea name="approval_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('assets.revaluations.reject', \Vinkla\Hashids\Facades\Hashids::encode($revaluation->id)) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Revaluation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="approval_notes" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Handle Submit for Approval button click with SweetAlert
    $('#submitForApprovalBtn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const form = document.getElementById('submitForApprovalForm');
        
        // Debug: Check if form exists
        if (!form) {
            console.error('Form not found: submitForApprovalForm');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Form not found. Please refresh the page and try again.'
            });
            return;
        }
        
        const revaluationNumber = '{{ $revaluation->revaluation_number }}';
        const assetName = '{{ $revaluation->asset->name ?? "N/A" }}';
        const fairValue = '{{ number_format($revaluation->fair_value ?? 0, 2) }}';
        
        console.log('Form action:', form.action);
        console.log('Form method:', form.method);
        
        Swal.fire({
            title: 'Submit for Approval?',
            html: `
                <div class="text-start">
                    <p>Are you sure you want to submit this revaluation for approval?</p>
                    <div class="mt-3">
                        <strong>Revaluation Number:</strong> ${revaluationNumber}<br>
                        <strong>Asset:</strong> ${assetName}<br>
                        <strong>Fair Value:</strong> ${fairValue}
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        <small><i class="bx bx-info-circle"></i> Once submitted, you will not be able to edit this revaluation until it is approved or rejected.</small>
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Submit for Approval',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            allowOutsideClick: false,
            allowEscapeKey: true
        }).then((result) => {
            if (result.isConfirmed) {
                console.log('User confirmed submission');
                
                // Show loading state
                const btn = $('#submitForApprovalBtn');
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Submitting...');
                
                // Use requestSubmit() if available (modern browsers), otherwise use submit()
                if (form.requestSubmit) {
                    console.log('Using requestSubmit()');
                    form.requestSubmit();
                } else {
                    console.log('Using fallback submit method');
                    // Fallback for older browsers - create a temporary submit button
                    const submitBtn = document.createElement('button');
                    submitBtn.type = 'submit';
                    submitBtn.style.display = 'none';
                    form.appendChild(submitBtn);
                    submitBtn.click();
                    form.removeChild(submitBtn);
                }
            } else {
                console.log('User cancelled submission');
            }
        });
    });

    // Handle Post to GL form submission with SweetAlert
    $('#postGlForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const revaluationNumber = '{{ $revaluation->revaluation_number }}';
        const revaluationAmount = '{{ number_format($revaluation->revaluation_increase > 0 ? $revaluation->revaluation_increase : $revaluation->revaluation_decrease, 2) }}';
        const isIncrease = {{ $revaluation->revaluation_increase > 0 ? 'true' : 'false' }};
        
        Swal.fire({
            title: 'Post to General Ledger?',
            html: `
                <div class="text-start">
                    <p>Are you sure you want to post this revaluation to the General Ledger?</p>
                    <div class="mt-3">
                        <strong>Revaluation Number:</strong> ${revaluationNumber}<br>
                        <strong>Revaluation ${isIncrease ? 'Increase' : 'Decrease'}:</strong> ${revaluationAmount}<br>
                        <strong>Fair Value:</strong> {{ number_format($revaluation->fair_value ?? 0, 2) }}
                    </div>
                    <div class="alert alert-warning mt-3 mb-0">
                        <small><i class="bx bx-info-circle"></i> This action will create journal entries and cannot be easily reversed.</small>
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Post to GL',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            allowOutsideClick: false,
            allowEscapeKey: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                const btn = $('#postGlBtn');
                const originalHtml = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Posting...');
                
                // Submit the form
                form.submit();
            }
        });
    });
});
</script>
@endpush

@endsection

