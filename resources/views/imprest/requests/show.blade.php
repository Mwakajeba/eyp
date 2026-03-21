@extends('layouts.main')

@section('title', 'Imprest Request Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Imprest Management', 'url' => route('imprest.index'), 'icon' => 'bx bx-money'],
            ['label' => 'All Requests', 'url' => route('imprest.requests.index'), 'icon' => 'bx bx-list-ul'],
            ['label' => 'Request Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-primary">Imprest Request - {{ $imprestRequest->request_number }}</h5>
            <span class="{{ $imprestRequest->getStatusBadgeClass() }}">{{ $imprestRequest->getStatusLabel() }}</span>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-1"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error me-1"></i>
            @foreach($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <!-- Request Details Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Request Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Request Number</label>
                                <div class="form-control-plaintext">{{ $imprestRequest->request_number }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Employee</label>
                                <div class="form-control-plaintext">{{ $imprestRequest->employee->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Department</label>
                                <div class="form-control-plaintext">{{ $imprestRequest->department->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Project</label>
                                <div class="form-control-plaintext">
                                    @if($imprestRequest->project)
                                        {{ $imprestRequest->project->project_code ? $imprestRequest->project->project_code . ' - ' : '' }}{{ $imprestRequest->project->name }}
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Activity Description</label>
                                <div class="form-control-plaintext">
                                    @if($imprestRequest->projectActivity)
                                        {{ $imprestRequest->projectActivity->activity_code ? $imprestRequest->projectActivity->activity_code . ' - ' : '' }}{{ $imprestRequest->projectActivity->description }}
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Date Required</label>
                                <div class="form-control-plaintext">{{ $imprestRequest->date_required ? $imprestRequest->date_required->format('M d, Y') : 'N/A' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Total Amount</label>
                                <div class="form-control-plaintext fw-bold text-primary">
                                    TZS {{ number_format($imprestRequest->amount_requested, 2) }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Created Date</label>
                                <div class="form-control-plaintext">{{ $imprestRequest->created_at->format('M d, Y H:i') }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Retirement End Date</label>
                                <div class="form-control-plaintext">
                                    {{ $imprestRequest->retirement_end_date ? $imprestRequest->retirement_end_date->format('M d, Y') : 'N/A' }}
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">Purpose</label>
                                <div class="form-control-plaintext">{{ $imprestRequest->purpose }}</div>
                            </div>
                            @if($imprestRequest->description)
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">Detailed Description</label>
                                <div class="form-control-plaintext">{{ $imprestRequest->description }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Imprest Items Card -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Imprest Items Breakdown</h6>
                    </div>
                    <div class="card-body">
                        @if($imprestRequest->imprestItems && $imprestRequest->imprestItems->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Chart Account</th>
                                        <th>Notes/Description</th>
                                        <th class="text-end">Amount (TZS)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($imprestRequest->imprestItems as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->chartAccount->account_code ?? 'N/A' }}</strong><br>
                                            <small class="text-muted">{{ $item->chartAccount->account_name ?? 'N/A' }}</small>
                                        </td>
                                        <td>{{ $item->notes ?: 'No notes' }}</td>
                                        <td class="text-end fw-bold">{{ number_format($item->amount, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-info">
                                    <tr>
                                        <th colspan="2" class="text-end">Total Amount:</th>
                                        <th class="text-end">{{ number_format($imprestRequest->imprestItems->sum('amount'), 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @else
                        <div class="alert alert-warning">
                            <i class="bx bx-info-circle me-1"></i>
                            No imprest items found for this request.
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Activity Reports Card -->
                @if(in_array($imprestRequest->status, ['disbursed', 'retired', 'closed']))
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-file me-2"></i>Imprest Activity Reports</h6>
                        <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#uploadActivityReportModal">
                            <i class="bx bx-upload me-1"></i> Upload Report
                        </button>
                    </div>
                    <div class="card-body">
                        @if($imprestRequest->activityReports && $imprestRequest->activityReports->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Description</th>
                                        <th>File Name</th>
                                        <th>Type</th>
                                        <th>Uploaded By</th>
                                        <th>Date</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($imprestRequest->activityReports as $index => $report)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $report->description }}</td>
                                        <td>{{ $report->file_name }}</td>
                                        <td><span class="badge bg-{{ $report->file_type === 'pdf' ? 'danger' : 'primary' }}">{{ strtoupper($report->file_type) }}</span></td>
                                        <td>{{ $report->uploader->name ?? 'N/A' }}</td>
                                        <td>{{ $report->created_at->format('M d, Y H:i') }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('imprest.requests.activity-report.download', [Hashids::encode($imprestRequest->id), $report->id]) }}" class="btn btn-sm btn-outline-success" title="Download">
                                                <i class="bx bx-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="alert alert-info mb-0">
                            <i class="bx bx-info-circle me-1"></i>
                            No activity reports uploaded yet.
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Status History Card (if there's status progression) -->
                @if($imprestRequest->checked_at || $imprestRequest->approved_at || $imprestRequest->rejected_at)
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-history me-2"></i>Status History</h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <!-- Created -->
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Request Created</h6>
                                    <p class="mb-0 text-muted">{{ $imprestRequest->created_at->format('M d, Y H:i') }}</p>
                                    <small class="text-muted">By: {{ $imprestRequest->creator->name ?? 'N/A' }}</small>
                                </div>
                            </div>

                            <!-- Checked -->
                            @if($imprestRequest->checked_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Request Checked</h6>
                                    <p class="mb-0 text-muted">{{ $imprestRequest->checked_at->format('M d, Y H:i') }}</p>
                                    <small class="text-muted">By: {{ $imprestRequest->checker->name ?? 'N/A' }}</small>
                                    @if($imprestRequest->check_comments)
                                    <div class="mt-1">
                                        <small><strong>Comments:</strong> {{ $imprestRequest->check_comments }}</small>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Approved -->
                            @if($imprestRequest->approved_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Request Approved</h6>
                                    <p class="mb-0 text-muted">{{ $imprestRequest->approved_at->format('M d, Y H:i') }}</p>
                                    <small class="text-muted">By: {{ $imprestRequest->approver->name ?? 'N/A' }}</small>
                                    @if($imprestRequest->approval_comments)
                                    <div class="mt-1">
                                        <small><strong>Comments:</strong> {{ $imprestRequest->approval_comments }}</small>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- Rejected -->
                            @if($imprestRequest->rejected_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-danger"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Request Rejected</h6>
                                    <p class="mb-0 text-muted">{{ $imprestRequest->rejected_at->format('M d, Y H:i') }}</p>
                                    <small class="text-muted">By: {{ $imprestRequest->rejecter->name ?? 'N/A' }}</small>
                                    @if($imprestRequest->rejection_reason)
                                    <div class="mt-1">
                                        <small><strong>Reason:</strong> {{ $imprestRequest->rejection_reason }}</small>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-md-4">
                <!-- Quick Actions Card -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0"><i class="bx bx-cog me-2"></i>Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('imprest.requests.index') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back to List
                            </a>

                            <!-- Edit Button - Only for pending requests -->
                            @if($imprestRequest->status === 'pending' && (auth()->user()->id === $imprestRequest->created_by || auth()->user()->hasRole('Super Admin')))
                            <a href="{{ route('imprest.requests.edit', $imprestRequest->id) }}" class="btn btn-warning">
                                <i class="bx bx-edit me-1"></i> Edit Request
                            </a>
                            @endif

                            <!-- Delete Button - Only for pending requests that haven't been checked or approved -->
                            @if($imprestRequest->status === 'pending' && !$imprestRequest->checked_at && !$imprestRequest->approved_at && (auth()->user()->id === $imprestRequest->created_by || auth()->user()->hasRole('Super Admin')))
                            <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                <i class="bx bx-trash me-1"></i> Delete Request
                            </button>
                            @endif

                            <!-- Print PDF Button -->
                            <a href="{{ route('imprest.requests.export-pdf', $imprestRequest->id) }}" class="btn btn-info" target="_blank">
                                <i class="bx bx-download me-1"></i> Export PDF
                            </a>

                            <!-- Print Preview Button -->
                            <a href="{{ route('imprest.requests.print', $imprestRequest->id) }}" class="btn btn-info" target="_blank">
                                <i class="bx bx-printer me-1"></i> Print PDF
                            </a>

                            @if($requiresApproval)
                                @if($canUserApprove && $currentApprovalLevel)
                                    <a href="{{ route('imprest.multi-approvals.pending') }}" class="btn btn-success">
                                        <i class="bx bx-check-circle me-1"></i> Approve Level {{ $currentApprovalLevel }}
                                    </a>
                                @elseif($currentApprovalLevel)
                                    <div class="alert alert-info alert-sm mb-2">
                                        <i class="bx bx-info-circle me-1"></i>
                                        <small>Waiting for Level {{ $currentApprovalLevel }} approval. You are not authorized to approve at this level.</small>
                                    </div>
                                @elseif($isFullyApproved)
                                    <div class="alert alert-success alert-sm mb-2">
                                        <i class="bx bx-check-circle me-1"></i>
                                        <small>All approval levels completed. Ready for disbursement.</small>
                                    </div>
                                @elseif($hasRejectedApprovals)
                                    <div class="alert alert-danger alert-sm mb-2">
                                        <i class="bx bx-x-circle me-1"></i>
                                        <small>Request has been rejected in the approval process.</small>
                                    </div>
                                @endif
                            @else
                                <div class="alert alert-info alert-sm mb-2">
                                    <i class="bx bx-info-circle me-1"></i>
                                    <small>No multi-level approval required for this request.</small>
                                </div>
                            @endif

                            @if($canUserDisburse)
                            <a href="{{ route('imprest.disbursed.disburse.form', $imprestRequest->id) }}" class="btn btn-primary">
                                <i class="bx bx-money me-1"></i> Disburse Funds
                            </a>
                            @elseif($imprestRequest->canBeDisbursed())
                            <div class="alert alert-warning alert-sm mb-2">
                                <i class="bx bx-info-circle me-1"></i>
                                <small>You don't have permission to disburse this request. Contact administrator for approval settings.</small>
                            </div>
                            @endif

                            @if($imprestRequest->canBeRetired())
                            <a href="{{ route('imprest.retirement.create', $imprestRequest->id) }}" class="btn btn-warning">
                                <i class="bx bx-receipt me-1"></i> Submit Retirement
                            </a>
                            @endif

                            @if($imprestRequest->retirement)
                            <a href="{{ route('imprest.retirement.show', $imprestRequest->retirement->id) }}" class="btn btn-outline-info">
                                <i class="bx bx-show me-1"></i> View Retirement
                            </a>
                            @endif

                            @if($imprestRequest->canBeClosed())
                            <form action="{{ route('imprest.close', $imprestRequest->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-dark w-100" onclick="return confirm('Are you sure you want to close this imprest request?')">
                                    <i class="bx bx-lock me-1"></i> Close Request
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Financial Summary Card -->
                <div class="card mt-3">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bx bx-money me-2"></i>Financial Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-12 mb-2">
                                <small class="text-muted">Requested Amount</small>
                                <div class="fw-bold">TZS {{ number_format($imprestRequest->amount_requested, 2) }}</div>
                            </div>
                            @if($imprestRequest->disbursed_amount)
                            <div class="col-12 mb-2">
                                <small class="text-muted">Disbursed Amount</small>
                                <div class="fw-bold text-primary">TZS {{ number_format($imprestRequest->disbursed_amount, 2) }}</div>
                            </div>
                            <div class="col-12 mb-2">
                                <small class="text-muted">Retirement End Date</small>
                                <div class="fw-bold text-info">
                                    {{ $imprestRequest->retirement_end_date ? $imprestRequest->retirement_end_date->format('M d, Y') : 'N/A' }}
                                </div>
                            </div>
                            @if($imprestRequest->payment)
                            <div class="col-12 mb-2">
                                <small class="text-muted">Payment Reference</small>
                                <div class="text-info">{{ $imprestRequest->payment->reference }}</div>
                            </div>
                            @endif
                            @elseif($imprestRequest->disbursement)
                            <div class="col-12 mb-2">
                                <small class="text-muted">Disbursed Amount</small>
                                <div class="fw-bold text-primary">TZS {{ number_format($imprestRequest->disbursement->amount_issued, 2) }}</div>
                            </div>
                            @endif
                            @if($imprestRequest->retirement)
                            <div class="col-12 mb-2">
                                <small class="text-muted">Amount Spent</small>
                                <div class="fw-bold text-warning">TZS {{ number_format($imprestRequest->retirement->total_amount_used, 2) }}</div>
                            </div>
                            <div class="col-12 mb-2">
                                <small class="text-muted">Remaining Balance</small>
                                <div class="fw-bold {{ $imprestRequest->retirement->remaining_balance > 0 ? 'text-success' : ($imprestRequest->retirement->remaining_balance < 0 ? 'text-danger' : 'text-muted') }}">
                                    TZS {{ number_format($imprestRequest->retirement->remaining_balance, 2) }}
                                </div>
                            </div>
                            <div class="col-12">
                                <small class="text-muted">Retirement Status</small>
                                <div class="{{ $imprestRequest->retirement->getStatusBadgeClass() }}">{{ $imprestRequest->retirement->getStatusLabel() }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Multi-Level Approval Status -->
                @if($requiresApproval)
                <div class="card mt-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-check-circle me-2"></i>Approval Status</h6>
                    </div>
                    <div class="card-body">
                        @if(count($requiredApprovalLevels) > 0)
                            <div class="row">
                                @foreach($requiredApprovalLevels as $levelData)
                                    @php
                                        $level = $levelData['level'];
                                        $threshold = $levelData['threshold'];
                                        $approvers = $levelData['approvers'];
                                        
                                        // Check if this level has been approved
                                        $levelApproval = $completedApprovals->where('approval_level', $level)->first();
                                        $isPending = $pendingApprovals->where('approval_level', $level)->isNotEmpty();
                                        $isCompleted = $levelApproval && $levelApproval->isApproved();
                                        $isRejected = $levelApproval && $levelApproval->isRejected();
                                    @endphp
                                    
                                    <div class="col-md-6 mb-3">
                                        <div class="border rounded p-3 {{ $isCompleted ? 'border-success' : ($isRejected ? 'border-danger' : ($isPending ? 'border-warning' : 'border-secondary')) }}">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0">Level {{ $level }}</h6>
                                                @if($isCompleted)
                                                    <span class="badge bg-success">
                                                        <i class="bx bx-check"></i> Approved
                                                    </span>
                                                @elseif($isRejected)
                                                    <span class="badge bg-danger">
                                                        <i class="bx bx-x"></i> Rejected
                                                    </span>
                                                @elseif($isPending)
                                                    <span class="badge bg-warning">
                                                        <i class="bx bx-time"></i> Pending
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        <i class="bx bx-minus"></i> Waiting
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            @if($threshold)
                                                <small class="text-muted">Threshold: {{ number_format($threshold, 2) }}</small><br>
                                            @endif
                                            
                                            <small class="text-muted">
                                                Approvers: 
                                                @if(count($approvers) > 0)
                                                    @foreach(\App\Models\User::whereIn('id', $approvers)->get() as $approver)
                                                        {{ $approver->name }}{{ !$loop->last ? ', ' : '' }}
                                                    @endforeach
                                                @else
                                                    None assigned
                                                @endif
                                            </small>
                                            
                                            @if($levelApproval)
                                                <hr>
                                                <small>
                                                    <strong>{{ $levelApproval->isApproved() ? 'Approved' : 'Rejected' }} by:</strong> {{ $levelApproval->approver->name }}<br>
                                                    <strong>Date:</strong> {{ $levelApproval->action_date ? $levelApproval->action_date->format('M d, Y H:i') : 'N/A' }}<br>
                                                    @if($levelApproval->comments)
                                                        <strong>Comments:</strong> {{ $levelApproval->comments }}
                                                    @endif
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-muted">
                                <i class="bx bx-info-circle fs-3"></i>
                                <p class="mb-0">No approval configuration found for this request amount.</p>
                            </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
<!-- Check Request Modal -->
<div class="modal fade" id="checkModal" tabindex="-1" aria-labelledby="checkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="checkModalLabel">
                    <i class="bx bx-check-circle me-2"></i>Check Imprest Request
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="checkForm" method="POST" action="{{ route('imprest.checked.check', $imprestRequest->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-1"></i>
                        <strong>Request:</strong> {{ $imprestRequest->request_number }}<br>
                        <strong>Amount:</strong> TZS {{ number_format($imprestRequest->amount_requested, 2) }}<br>
                        <strong>Employee:</strong> {{ $imprestRequest->employee->name ?? 'N/A' }}
                    </div>
                    
                    <div class="mb-3">
                        <label for="checkAction" class="form-label">Action <span class="text-danger">*</span></label>
                        <select class="form-select" id="checkAction" name="action" required>
                            <option value="">Select Action</option>
                            <option value="approve">Forward for Approval</option>
                            <option value="reject">Reject Request</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="checkComments" class="form-label">Comments</label>
                        <textarea class="form-control" id="checkComments" name="comments" rows="3" 
                                  placeholder="Add your comments (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="bx bx-check me-1"></i>Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Approve Request Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveModalLabel">
                    <i class="bx bx-check-double me-2"></i>Approve Imprest Request
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approveForm" method="POST" action="{{ route('imprest.approved.approve', $imprestRequest->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-1"></i>
                        <strong>Request:</strong> {{ $imprestRequest->request_number }}<br>
                        <strong>Amount:</strong> TZS {{ number_format($imprestRequest->amount_requested, 2) }}<br>
                        <strong>Employee:</strong> {{ $imprestRequest->employee->name ?? 'N/A' }}
                    </div>
                    
                    <div class="mb-3">
                        <label for="approveAction" class="form-label">Action <span class="text-danger">*</span></label>
                        <select class="form-select" id="approveAction" name="action" required>
                            <option value="">Select Action</option>
                            <option value="approve">Approve Request</option>
                            <option value="reject">Reject Request</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="approveComments" class="form-label">Comments</label>
                        <textarea class="form-control" id="approveComments" name="comments" rows="3" 
                                  placeholder="Add your comments (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bx bx-check-double me-1"></i>Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Delete Form -->
<form id="deleteForm" action="{{ route('imprest.requests.destroy', $imprestRequest->id) }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<!-- Upload Activity Report Modal -->
@if(in_array($imprestRequest->status, ['disbursed', 'retired', 'closed']))
<div class="modal fade" id="uploadActivityReportModal" tabindex="-1" aria-labelledby="uploadActivityReportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadActivityReportModalLabel">
                    <i class="bx bx-upload me-2"></i>Upload Activity Report
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('imprest.requests.activity-report.store', Hashids::encode($imprestRequest->id)) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reportDescription" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reportDescription" name="description" rows="3"
                                  placeholder="Describe the activity report..." required maxlength="500"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="reportFile" class="form-label">File (PDF or DOCX) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="reportFile" name="report_file"
                               accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
                        <div class="form-text">Maximum file size: 10MB. Only PDF and DOCX files are allowed.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-upload me-1"></i>Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('styles')
<style>
.alert-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.timeline {
    position: relative;
    padding: 0;
}

.timeline-item {
    position: relative;
    padding-left: 30px;
    padding-bottom: 20px;
}

.timeline-item:not(:last-child):before {
    content: '';
    position: absolute;
    left: 8px;
    top: 20px;
    height: calc(100% - 10px);
    width: 2px;
    background: #dee2e6;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 0;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content h6 {
    margin-bottom: 5px;
    font-size: 14px;
}
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
// Delete confirmation function
function confirmDelete() {
    Swal.fire({
        icon: 'warning',
        title: 'Delete Request?',
        text: 'Are you sure you want to delete this imprest request? This action cannot be undone.',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit delete form directly (no AJAX)
            document.getElementById('deleteForm').submit();
        }
    });
}

$(document).ready(function() {
    // Handle check form submission
    $('#checkForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Disable submit button and show loading
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Processing...');
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                $('#checkModal').modal('hide');
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.success,
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Reload the page to see updated status
                    window.location.reload();
                });
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                
                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.error || 'Failed to process request',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                // Re-enable submit button
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Handle approve form submission
    $('#approveForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Disable submit button and show loading
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Processing...');
        
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                $('#approveModal').modal('hide');
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.success,
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Reload the page to see updated status
                    window.location.reload();
                });
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                
                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.error || 'Failed to process request',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                // Re-enable submit button
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
@endpush