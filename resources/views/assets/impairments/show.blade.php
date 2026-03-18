@extends('layouts.main')

@section('title', 'Impairment Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Impairments', 'url' => route('assets.impairments.index'), 'icon' => 'bx bx-error-circle'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1 text-primary">Impairment Details</h5>
                <p class="text-muted mb-0">{{ $impairment->impairment_number }}
                    @if($impairment->is_reversal)
                        <span class="badge bg-info">Reversal</span>
                    @endif
                </p>
            </div>
            <div class="d-flex gap-2">
                @if(in_array($impairment->status, ['draft', 'rejected']) && $canSubmit)
                    @if(!$hasRequiredApprovalLevels)
                        <button type="button" class="btn btn-warning" disabled title="No approval levels configured">
                            <i class="bx bx-send me-1"></i>Submit for Approval
                        </button>
                    @else
                        <form action="{{ route('assets.impairments.submit', \Vinkla\Hashids\Facades\Hashids::encode($impairment->id)) }}" 
                              method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning">
                                <i class="bx bx-send me-1"></i>Submit for Approval
                            </button>
                        </form>
                    @endif
                @endif
                @if($impairment->status == 'pending_approval' && $canApprove && $currentLevel)
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                        <i class="bx bx-check me-1"></i>Approve
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bx bx-x me-1"></i>Reject
                    </button>
                @endif
                @if($impairment->status == 'approved' && !$impairment->gl_posted)
                    <form action="{{ route('assets.impairments.post-gl', \Vinkla\Hashids\Facades\Hashids::encode($impairment->id)) }}" 
                          method="POST" class="d-inline" id="post-to-gl-form">
                        @csrf
                        <button type="button" class="btn btn-primary" id="post-to-gl-btn">
                            <i class="bx bx-book me-1"></i>Post to GL
                        </button>
                    </form>
                @endif
                @if(!$impairment->is_reversal && $impairment->status == 'posted' && $impairment->canBeReversed())
                    <a href="{{ route('assets.impairments.create-reversal', \Vinkla\Hashids\Facades\Hashids::encode($impairment->id)) }}" 
                       class="btn btn-success">
                        <i class="bx bx-undo me-1"></i>Create Reversal
                    </a>
                @endif
                <a href="{{ route('assets.impairments.index') }}" class="btn btn-secondary">
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

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(!$hasRequiredApprovalLevels && in_array($impairment->status, ['draft', 'rejected']))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                <strong>Warning:</strong> No approval levels have been configured for asset impairments. 
                You cannot submit this impairment for approval until approval levels are configured in the system settings.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Approval Actions Section -->
        @if($impairment->status === 'pending_approval' && $canApprove && $currentLevel)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-check-circle me-2"></i>Approval Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">You are authorized to approve or reject this impairment at the current level (<strong>{{ $currentLevel->level_name }}</strong>).</p>
                        
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
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Impairment Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>Impairment Number:</strong><br>
                                <span class="badge bg-light text-dark">{{ $impairment->impairment_number }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Impairment Date:</strong><br>
                                {{ $impairment->impairment_date->format('d M Y') }}
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
                                    $color = $statusColors[$impairment->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }}">
                                    {{ ucfirst(str_replace('_', ' ', $impairment->status)) }}
                                </span>
                                @if($impairment->status === 'pending_approval' && $currentLevel)
                                    <br><small class="text-muted">
                                        <i class="bx bx-user me-1"></i>
                                        Level {{ $impairment->current_approval_level }} - {{ $currentLevel->level_name }}
                                    </small>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <strong>Type:</strong><br>
                                @if($impairment->is_reversal)
                                    <span class="badge bg-info">Reversal</span>
                                    @if($impairment->originalImpairment)
                                        <br><small class="text-muted">Reversal of: {{ $impairment->originalImpairment->impairment_number }}</small>
                                    @endif
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($impairment->impairment_type) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Impairment Indicators -->
                @if(!$impairment->is_reversal)
                <div class="card mb-3">
                    <div class="card-header bg-warning text-white">
                        <h6 class="mb-0"><i class="bx bx-error me-2"></i>Impairment Indicators</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled mb-0">
                                    @if($impairment->indicator_physical_damage)
                                        <li><i class="bx bx-check text-success me-2"></i>Physical Damage</li>
                                    @endif
                                    @if($impairment->indicator_obsolescence)
                                        <li><i class="bx bx-check text-success me-2"></i>Obsolescence</li>
                                    @endif
                                    @if($impairment->indicator_technological_change)
                                        <li><i class="bx bx-check text-success me-2"></i>Technological Change</li>
                                    @endif
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled mb-0">
                                    @if($impairment->indicator_idle_asset)
                                        <li><i class="bx bx-check text-success me-2"></i>Idle Asset</li>
                                    @endif
                                    @if($impairment->indicator_market_decline)
                                        <li><i class="bx bx-check text-success me-2"></i>Market Decline</li>
                                    @endif
                                    @if($impairment->indicator_legal_regulatory)
                                        <li><i class="bx bx-check text-success me-2"></i>Legal/Regulatory Changes</li>
                                    @endif
                                </ul>
                            </div>
                            @if($impairment->other_indicators)
                            <div class="col-12 mt-2">
                                <strong>Other Indicators:</strong><br>
                                {{ $impairment->other_indicators }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Financial Details</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <td width="50%"><strong>Carrying Amount:</strong></td>
                                <td class="text-end">{{ number_format($impairment->carrying_amount ?? 0, 2) }}</td>
                            </tr>
                            @if($impairment->fair_value_less_costs)
                            <tr>
                                <td><strong>Fair Value Less Costs:</strong></td>
                                <td class="text-end">{{ number_format($impairment->fair_value_less_costs, 2) }}</td>
                            </tr>
                            @endif
                            @if($impairment->value_in_use)
                            <tr>
                                <td><strong>Value in Use:</strong></td>
                                <td class="text-end">{{ number_format($impairment->value_in_use, 2) }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td><strong>Recoverable Amount:</strong></td>
                                <td class="text-end"><strong>{{ number_format($impairment->recoverable_amount ?? 0, 2) }}</strong></td>
                            </tr>
                            <tr class="table-danger">
                                <td><strong>Impairment Loss:</strong></td>
                                <td class="text-end text-danger">
                                    <strong>
                                        @if($impairment->is_reversal && $impairment->reversal_amount > 0)
                                            +{{ number_format($impairment->reversal_amount, 2) }} (Reversal)
                                        @else
                                            {{ number_format($impairment->impairment_loss ?? 0, 2) }}
                                        @endif
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Carrying Amount After:</strong></td>
                                <td class="text-end"><strong>{{ number_format($impairment->carrying_amount_after ?? 0, 2) }}</strong></td>
                            </tr>
                        </table>

                        @if($impairment->cash_flow_projections && count($impairment->cash_flow_projections) > 0)
                        <div class="mt-3">
                            <strong>Cash Flow Projections:</strong>
                            <table class="table table-sm table-bordered mt-2">
                                <thead>
                                    <tr>
                                        <th>Year</th>
                                        <th class="text-end">Cash Flow</th>
                                        <th class="text-end">Present Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($impairment->cash_flow_projections as $index => $cashFlow)
                                    <tr>
                                        <td>Year {{ $index + 1 }}</td>
                                        <td class="text-end">{{ number_format($cashFlow, 2) }}</td>
                                        <td class="text-end">
                                            @if($impairment->discount_rate)
                                                {{ number_format($cashFlow / pow(1 + ($impairment->discount_rate / 100), $index + 1), 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if($impairment->discount_rate)
                                <div class="text-muted small">
                                    <strong>Discount Rate:</strong> {{ number_format($impairment->discount_rate, 2) }}%
                                </div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>

                @if($impairment->notes)
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-note me-2"></i>Notes</h6>
                    </div>
                    <div class="card-body">
                        {{ $impairment->notes }}
                    </div>
                </div>
                @endif

                @if($impairment->journal)
                <div class="card mb-3">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bx bx-book me-2"></i>General Ledger Entry</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong>Journal Number:</strong><br>
                                {{ $impairment->journal->reference_number ?? '-' }}
                            </div>
                            <div class="col-md-6">
                                <strong>Posted Date:</strong><br>
                                {{ $impairment->gl_posted_at ? $impairment->gl_posted_at->format('d M Y H:i') : '-' }}
                            </div>
                            @if($impairment->journal->items && $impairment->journal->items->count() > 0)
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
                                        @foreach($impairment->journal->items as $item)
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

                @if($impairment->reversals && $impairment->reversals->count() > 0)
                <div class="card mb-3">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bx bx-undo me-2"></i>Reversals</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Reversal #</th>
                                    <th>Date</th>
                                    <th class="text-end">Reversal Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($impairment->reversals as $reversal)
                                <tr>
                                    <td>{{ $reversal->impairment_number }}</td>
                                    <td>{{ $reversal->reversal_date ? $reversal->reversal_date->format('d M Y') : $reversal->impairment_date->format('d M Y') }}</td>
                                    <td class="text-end text-success">+{{ number_format($reversal->reversal_amount, 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $statusColors[$reversal->status] ?? 'secondary' }}">
                                            {{ ucfirst(str_replace('_', ' ', $reversal->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('assets.impairments.show', \Vinkla\Hashids\Facades\Hashids::encode($reversal->id)) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bx bx-show"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-2">
                            <strong>Total Reversed:</strong> 
                            <span class="text-success">{{ number_format($impairment->total_reversals, 2) }}</span> | 
                            <strong>Remaining Reversible:</strong> 
                            <span>{{ number_format($impairment->remaining_reversible_amount, 2) }}</span>
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
                        <p><strong>Code:</strong><br>{{ $impairment->asset->code }}</p>
                        <p><strong>Name:</strong><br>{{ $impairment->asset->name }}</p>
                        <p><strong>Category:</strong><br>{{ $impairment->asset->category->name ?? '-' }}</p>
                        <p><strong>Purchase Cost:</strong><br>{{ number_format($impairment->asset->purchase_cost ?? 0, 2) }}</p>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-time me-2"></i>Approval Workflow</h6>
                    </div>
                    <div class="card-body">
                        @if($impairment->preparedBy)
                            <p><strong>Prepared By:</strong><br>{{ $impairment->preparedBy->name ?? '-' }}</p>
                        @endif
                        @if($impairment->financeManager)
                            <p><strong>Finance Manager:</strong><br>{{ $impairment->financeManager->name ?? '-' }}</p>
                        @endif
                        @if($impairment->cfoApprover)
                            <p><strong>CFO/Board Approver:</strong><br>{{ $impairment->cfoApprover->name ?? '-' }}</p>
                        @endif
                        @if($impairment->approved_at)
                            <p><strong>Approved At:</strong><br>{{ $impairment->approved_at->format('d M Y H:i') }}</p>
                        @endif
                        @if($impairment->approval_notes)
                            <p><strong>Approval Notes:</strong><br>{{ $impairment->approval_notes }}</p>
                        @endif
                    </div>
                </div>

                @if($impairment->impairment_test_report_path)
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-file me-2"></i>Impairment Test Report</h6>
                    </div>
                    <div class="card-body">
                        <a href="{{ Storage::url($impairment->impairment_test_report_path) }}" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bx bx-file me-1"></i>View Report
                        </a>
                    </div>
                </div>
                @endif

                @if($impairment->attachments && count($impairment->attachments) > 0)
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-paperclip me-2"></i>Attachments</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            @foreach($impairment->attachments as $attachment)
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
@if($impairment->status == 'pending_approval')
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('assets.impairments.approve', \Vinkla\Hashids\Facades\Hashids::encode($impairment->id)) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Impairment</h5>
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
            <form action="{{ route('assets.impairments.reject', \Vinkla\Hashids\Facades\Hashids::encode($impairment->id)) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Reject Impairment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bx bx-error-circle me-2"></i>
                        <strong>Warning:</strong> This action will reject the impairment. Please provide a reason for rejection.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="approval_notes" class="form-control" rows="4" required placeholder="Please provide a detailed reason for rejecting this impairment..."></textarea>
                        <div class="form-text">This reason will be recorded and visible to the preparer.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Impairment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // Post to GL confirmation with SweetAlert
        $('#post-to-gl-btn').on('click', function() {
            Swal.fire({
                title: 'Post to General Ledger?',
                html: `
                    <div class="text-start">
                        <p class="mb-2">Are you sure you want to post this impairment to the General Ledger?</p>
                        <p class="mb-0 text-muted small">
                            <i class="bx bx-info-circle me-1"></i>
                            This action will create journal entries and update the asset records. This cannot be undone easily.
                        </p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bx bx-book me-1"></i>Yes, Post to GL',
                cancelButtonText: '<i class="bx bx-x me-1"></i>Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#post-to-gl-form').submit();
                }
            });
        });
    });
</script>
@endpush

@endsection

