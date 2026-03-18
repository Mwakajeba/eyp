@extends('layouts.main')

@section('title', 'Retirement Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Imprest Management', 'url' => route('imprest.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Request Details', 'url' => route('imprest.requests.show', $retirement->imprest_request_id), 'icon' => 'bx bx-show'],
            ['label' => 'Retirement Details', 'url' => '#', 'icon' => 'bx bx-receipt']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-primary">Retirement - {{ $retirement->retirement_number }}</h5>
            <span class="{{ $retirement->getStatusBadgeClass() }}">{{ $retirement->getStatusLabel() }}</span>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Imprest Request Info -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Related Imprest Request</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Request Number</label>
                                <div class="form-control-plaintext">
                                    <a href="{{ route('imprest.requests.show', $retirement->imprestRequest->id) }}" class="text-primary">
                                        {{ $retirement->imprestRequest->request_number }}
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Employee</label>
                                <div class="form-control-plaintext">{{ $retirement->imprestRequest->employee->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Department</label>
                                <div class="form-control-plaintext">{{ $retirement->imprestRequest->department->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Amount Disbursed</label>
                                <div class="form-control-plaintext fw-bold text-success">
                                    TZS {{ number_format($retirement->imprestRequest->disbursed_amount ?? 0, 2) }}
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">Purpose</label>
                                <div class="form-control-plaintext">{{ $retirement->imprestRequest->purpose }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Retirement Items -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Expenditure Breakdown</h6>
                    </div>
                    <div class="card-body">
                        @if($retirement->retirementItems && $retirement->retirementItems->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Chart Account</th>
                                        <th>Description</th>
                                        <th class="text-end">Requested</th>
                                        <th class="text-end">Actual</th>
                                        <th class="text-end">Variance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($retirement->retirementItems as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->chartAccount->account_code ?? 'N/A' }}</strong><br>
                                            <small class="text-muted">{{ $item->chartAccount->account_name ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $item->description }}</div>
                                            @if($item->notes)
                                            <small class="text-muted"><em>{{ $item->notes }}</em></small>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format($item->requested_amount, 2) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($item->actual_amount, 2) }}</td>
                                        <td class="text-end fw-bold
                                            @if($item->variance > 0) text-danger
                                            @elseif($item->variance < 0) text-success
                                            @else text-muted @endif">
                                            {{ number_format($item->variance, 2) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-info">
                                    <tr>
                                        <th colspan="2">Totals:</th>
                                        <th class="text-end">{{ number_format($retirement->retirementItems->sum('requested_amount'), 2) }}</th>
                                        <th class="text-end">{{ number_format($retirement->retirementItems->sum('actual_amount'), 2) }}</th>
                                        <th class="text-end
                                            @if($retirement->total_variance > 0) text-danger
                                            @elseif($retirement->total_variance < 0) text-success
                                            @else text-muted @endif">
                                            {{ number_format($retirement->total_variance, 2) }}
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @else
                        <div class="alert alert-warning">
                            <i class="bx bx-info-circle me-1"></i>
                            No retirement items found for this retirement.
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Supporting Document & Notes -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bx bx-file me-2"></i>Supporting Information</h6>
                    </div>
                    <div class="card-body">
                        @if($retirement->supporting_document)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Supporting Document</label>
                            <div>
                                <a href="{{ Storage::url($retirement->supporting_document) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="bx bx-download me-1"></i>
                                    Download Document
                                </a>
                                <small class="text-muted ms-2">{{ basename($retirement->supporting_document) }}</small>
                            </div>
                        </div>
                        @endif

                        @if($retirement->retirement_notes)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Retirement Notes</label>
                            <div class="form-control-plaintext border rounded p-2 bg-light">
                                {{ $retirement->retirement_notes }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Status History -->
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-history me-2"></i>Approval History</h6>
                    </div>
                    <div class="card-body">
                        @if($retirement->approvals && $retirement->approvals->count() > 0)
                            <div class="timeline">
                                <!-- Submitted -->
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Retirement Submitted</h6>
                                        <p class="mb-0 text-muted">{{ $retirement->created_at->format('M d, Y H:i') }}</p>
                                        <small class="text-muted">By: {{ $retirement->employee->name ?? 'N/A' }}</small>
                                    </div>
                                </div>

                                <!-- Multi-level Approvals -->
                                @foreach($retirement->approvals->sortBy('approval_level') as $approval)
                                    <div class="timeline-item">
                                        @php
                                            $markerClass = match($approval->status) {
                                                'approved' => 'bg-success',
                                                'rejected' => 'bg-danger',
                                                'pending' => 'bg-warning',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <div class="timeline-marker {{ $markerClass }}"></div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">Level {{ $approval->approval_level }} - {{ ucfirst($approval->status) }}</h6>
                                            <p class="mb-0 text-muted">
                                                {{ $approval->approver->name ?? 'Pending Assignment' }}
                                                @if($approval->approved_at || $approval->rejected_at)
                                                    - {{ ($approval->approved_at ?: $approval->rejected_at)->format('M d, Y H:i') }}
                                                @endif
                                            </p>
                                            @if($approval->comments)
                                                <div class="mt-1">
                                                    <small><strong>Comments:</strong> {{ $approval->comments }}</small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <!-- Single-level approval (Legacy/Fallback) -->
                            <div class="timeline">
                                <!-- Submitted -->
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Retirement Submitted</h6>
                                        <p class="mb-0 text-muted">{{ $retirement->submitted_at ? $retirement->submitted_at->format('M d, Y H:i') : $retirement->created_at->format('M d, Y H:i') }}</p>
                                        <small class="text-muted">By: {{ $retirement->submitter->name ?? $retirement->employee->name ?? 'N/A' }}</small>
                                    </div>
                                </div>

                                <!-- Checked -->
                                @if($retirement->checked_at)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-info"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Retirement Checked</h6>
                                        <p class="mb-0 text-muted">{{ $retirement->checked_at->format('M d, Y H:i') }}</p>
                                        <small class="text-muted">By: {{ $retirement->checker->name ?? 'N/A' }}</small>
                                        @if($retirement->check_comments)
                                        <div class="mt-1">
                                            <small><strong>Comments:</strong> {{ $retirement->check_comments }}</small>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                <!-- Approved -->
                                @if($retirement->approved_at)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-success"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Retirement Approved</h6>
                                        <p class="mb-0 text-muted">{{ $retirement->approved_at->format('M d, Y H:i') }}</p>
                                        <small class="text-muted">By: {{ $retirement->approver->name ?? 'N/A' }}</small>
                                        @if($retirement->approval_comments)
                                        <div class="mt-1">
                                            <small><strong>Comments:</strong> {{ $retirement->approval_comments }}</small>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                <!-- Rejected -->
                                @if($retirement->rejected_at)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-danger"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Retirement Rejected</h6>
                                        <p class="mb-0 text-muted">{{ $retirement->rejected_at->format('M d, Y H:i') }}</p>
                                        <small class="text-muted">By: {{ $retirement->rejecter->name ?? 'N/A' }}</small>
                                        @if($retirement->rejection_reason)
                                        <div class="mt-1">
                                            <small><strong>Reason:</strong> {{ $retirement->rejection_reason }}</small>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Actions Card -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0"><i class="bx bx-cog me-2"></i>Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('imprest.requests.show', $retirement->imprest_request_id) }}" class="btn btn-outline-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back to Imprest
                            </a>
                            
                            <div class="dropdown-divider"></div>

                            @if($retirement->approvals && $retirement->approvals->count() > 0)
                                <!-- Multi-level approval actions -->
                                @php
                                    $userPendingApproval = $retirement->approvals()->where('approver_id', Auth::id())->where('status', 'pending')->first();
                                @endphp

                                @if($userPendingApproval)
                                    <div class="alert alert-info alert-sm mb-3 border border-info">
                                        <div class="d-flex align-items-center">
                                            <i class="bx bx-info-circle fs-5 me-2"></i>
                                            <div>
                                                <strong>Action Required</strong><br>
                                                <small>You have a pending approval for Level {{ $userPendingApproval->approval_level }}</small>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="{{ route('imprest.retirement-multi-approvals.pending') }}" class="btn btn-warning btn-sm">
                                        <i class="bx bx-clock me-1"></i> View Pending Approvals
                                    </a>
                                @endif

                                <a href="{{ route('imprest.retirement-multi-approvals.history', $retirement->id) }}" class="btn btn-outline-info btn-sm">
                                    <i class="bx bx-history me-1"></i> View Approval History
                                </a>
                            @else
                                <!-- Legacy single-level approval actions -->
                                @if($canUserCheck ?? false)
                                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#checkModal">
                                    <i class="bx bx-check-circle me-1"></i> Check Retirement
                                </button>
                                @elseif($retirement->canBeChecked())
                                <div class="alert alert-warning alert-sm mb-3 border border-warning">
                                    <div class="d-flex align-items-center">
                                        <i class="bx bx-info-circle fs-5 me-2"></i>
                                        <small>You don't have permission to check this retirement.</small>
                                    </div>
                                </div>
                                @endif

                                @if($canUserApprove ?? false)
                                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                                    <i class="bx bx-check-double me-1"></i> Approve Retirement
                                </button>
                                @elseif($retirement->canBeApproved())
                                <div class="alert alert-warning alert-sm mb-3 border border-warning">
                                    <div class="d-flex align-items-center">
                                        <i class="bx bx-info-circle fs-5 me-2"></i>
                                        <small>You don't have permission to approve this retirement.</small>
                                    </div>
                                </div>
                                @endif
                            @endif

                            <div class="dropdown-divider"></div>
                            
                            @if($retirement->status === 'approved')
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#journalModal">
                                <i class="bx bx-book me-1"></i> Create Journal Entry
                            </button>
                            @endif

                            @if($retirement->status === 'pending' && $retirement->submitted_by === Auth::id())
                            <a href="{{ route('imprest.retirement.edit', $retirement->id) }}" class="btn btn-warning btn-sm">
                                <i class="bx bx-edit me-1"></i> Edit Retirement
                            </a>
                            @endif

                            @if($retirement->status === 'closed' && $retirement->journal)
                            <a href="{{ route('accounting.journals.show', $retirement->journal_id) }}" class="btn btn-outline-success btn-sm">
                                <i class="bx bx-book me-1"></i> View Journal Entry
                            </a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="card mt-3">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Financial Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-12 mb-2">
                                <small class="text-muted">Amount Disbursed</small>
                                <div class="fw-bold">TZS {{ number_format($retirement->imprestRequest->disbursed_amount ?? 0, 2) }}</div>
                            </div>
                            <div class="col-12 mb-2">
                                <small class="text-muted">Amount Spent</small>
                                <div class="fw-bold text-primary">TZS {{ number_format($retirement->total_amount_used, 2) }}</div>
                            </div>
                            <div class="col-12 mb-2">
                                <small class="text-muted">Balance</small>
                                <div class="fw-bold {{ $retirement->remaining_balance > 0 ? 'text-success' : ($retirement->remaining_balance < 0 ? 'text-danger' : 'text-muted') }}">
                                    TZS {{ number_format($retirement->remaining_balance, 2) }}
                                </div>
                            </div>
                            @if($retirement->remaining_balance != 0)
                            <div class="col-12">
                                <small class="text-muted">
                                    {{ $retirement->remaining_balance > 0 ? 'Amount to Return' : 'Additional Amount Required' }}
                                </small>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Check Modal -->
<div class="modal fade" id="checkModal" tabindex="-1" aria-labelledby="checkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="checkModalLabel">
                    <i class="bx bx-check-circle me-2"></i>Check Retirement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="checkForm" method="POST" action="{{ route('imprest.retirement.check', $retirement->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-1"></i>
                        <strong>Retirement:</strong> {{ $retirement->retirement_number }}<br>
                        <strong>Total Amount:</strong> TZS {{ number_format($retirement->total_amount_used, 2) }}
                    </div>

                    <div class="mb-3">
                        <label for="checkAction" class="form-label">Action <span class="text-danger">*</span></label>
                        <select class="form-select" id="checkAction" name="action" required>
                            <option value="">Select Action</option>
                            <option value="approve">Forward for Approval</option>
                            <option value="reject">Reject Retirement</option>
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

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveModalLabel">
                    <i class="bx bx-check-double me-2"></i>Approve Retirement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approveForm" method="POST" action="{{ route('imprest.retirement.approve', $retirement->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-1"></i>
                        <strong>Retirement:</strong> {{ $retirement->retirement_number }}<br>
                        <strong>Total Amount:</strong> TZS {{ number_format($retirement->total_amount_used, 2) }}
                    </div>

                    <div class="mb-3">
                        <label for="approveAction" class="form-label">Action <span class="text-danger">*</span></label>
                        <select class="form-select" id="approveAction" name="action" required>
                            <option value="">Select Action</option>
                            <option value="approve">Approve Retirement</option>
                            <option value="reject">Reject Retirement</option>
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

<!-- Journal Modal -->
<div class="modal fade" id="journalModal" tabindex="-1" aria-labelledby="journalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="journalModalLabel">
                    <i class="bx bx-book me-2"></i>Create Journal Entry
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="journalForm" method="POST" action="{{ route('imprest.retirement.create-journal', $retirement->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-1"></i>
                        <strong>Retirement:</strong> {{ $retirement->retirement_number }}<br>
                        <strong>Total Amount:</strong> TZS {{ number_format($retirement->total_amount_used, 2) }}<br>
                        <small class="text-muted">The imprest receivables account from settings will be credited automatically.</small>
                    </div>

                    <div class="mb-3">
                        <label for="journalDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="journalDescription" name="description" rows="2"
                                  placeholder="Journal entry description (optional)">Journal entry for retirement {{ $retirement->retirement_number }}</textarea>
                    </div>

                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Journal Entries Preview</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Account</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($retirement->retirementItems as $item)
                                        <tr>
                                            <td>
                                                <small><strong>{{ $item->chartAccount->account_code }}</strong></small><br>
                                                <small class="text-muted">{{ $item->chartAccount->account_name }}</small>
                                            </td>
                                            <td class="text-end">{{ number_format($item->actual_amount, 2) }}</td>
                                            <td class="text-end">-</td>
                                        </tr>
                                        @endforeach
                                        <tr class="table-info">
                                            <td><strong>Imprest Receivables (from settings)</strong></td>
                                            <td class="text-end">-</td>
                                            <td class="text-end"><strong>{{ number_format($retirement->total_amount_used, 2) }}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-book me-1"></i>Create Journal Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
.alert-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.375rem;
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

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
    border-radius: 0.5rem;
}

.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    border-radius: 0.5rem 0.5rem 0 0 !important;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.375rem;
}

.d-grid .btn {
    font-weight: 500;
}

.dropdown-divider {
    margin: 0.5rem 0;
    border-top: 1px solid rgba(0, 0, 0, 0.15);
}

.table-striped > tbody > tr:nth-of-type(odd) > td {
    background-color: rgba(0, 0, 0, 0.025);
}

.form-control-plaintext {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Handle check form submission
    $('#checkForm').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        const action = $('#checkAction').val();
        const comments = $('#checkComments').val();

        // Validation
        if (!action) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Selection',
                text: 'Please select an action.',
                confirmButtonText: 'OK'
            });
            return;
        }

        if (action === 'reject' && comments.trim().length < 5) {
            Swal.fire({
                icon: 'warning',
                title: 'Comments Required',
                text: 'Please provide a reason for rejection (minimum 5 characters).',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Show loading state
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Processing...');

        // AJAX submission
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                console.log('Check success response:', response);

                if (response.success) {
                    $('#checkModal').modal('hide');

                    const actionText = action === 'approve' ? 'forwarded for approval' : 'rejected';

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: `Retirement ${actionText} successfully!`,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload(); // Reload to update status
                    });
                } else {
                    submitBtn.prop('disabled', false).html(originalText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'An error occurred.',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.log('Check error:', xhr, status, error);
                submitBtn.prop('disabled', false).html(originalText);

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors || {};
                    let errorMessage = 'Please fix the following errors:\n';
                    Object.keys(errors).forEach(field => {
                        errorMessage += `• ${errors[field].join(', ')}\n`;
                    });

                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Errors',
                        text: errorMessage,
                        confirmButtonText: 'OK'
                    });
                } else {
                    const message = xhr.responseJSON?.message || 'An error occurred while processing the retirement.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: message,
                        confirmButtonText: 'OK'
                    });
                }
            }
        });
    });

    // Handle approve form submission
    $('#approveForm').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        const action = $('#approveAction').val();
        const comments = $('#approveComments').val();

        // Validation
        if (!action) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Selection',
                text: 'Please select an action.',
                confirmButtonText: 'OK'
            });
            return;
        }

        if (action === 'reject' && comments.trim().length < 5) {
            Swal.fire({
                icon: 'warning',
                title: 'Comments Required',
                text: 'Please provide a reason for rejection (minimum 5 characters).',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Show loading state
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Processing...');

        // AJAX submission
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                console.log('Approve success response:', response);

                if (response.success) {
                    $('#approveModal').modal('hide');

                    const actionText = action === 'approve' ? 'approved' : 'rejected';

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: `Retirement ${actionText} successfully!`,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload(); // Reload to update status
                    });
                } else {
                    submitBtn.prop('disabled', false).html(originalText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'An error occurred.',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.log('Approve error:', xhr, status, error);
                submitBtn.prop('disabled', false).html(originalText);

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors || {};
                    let errorMessage = 'Please fix the following errors:\n';
                    Object.keys(errors).forEach(field => {
                        errorMessage += `• ${errors[field].join(', ')}\n`;
                    });

                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Errors',
                        text: errorMessage,
                        confirmButtonText: 'OK'
                    });
                } else {
                    const message = xhr.responseJSON?.message || 'An error occurred while processing the retirement.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: message,
                        confirmButtonText: 'OK'
                    });
                }
            }
        });
    });

    // Handle journal form submission
    $('#journalForm').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();

        // Show loading state
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Creating...');

        // AJAX submission
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                console.log('Journal success response:', response);

                if (response.success) {
                    $('#journalModal').modal('hide');

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Redirect to retirement index as specified
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            window.location.href = '{{ route("imprest.retirement.index") }}';
                        }
                    });
                } else {
                    submitBtn.prop('disabled', false).html(originalText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'An error occurred.',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.log('Journal error:', xhr, status, error);
                submitBtn.prop('disabled', false).html(originalText);

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors || {};
                    let errorMessage = 'Please fix the following errors:\n';
                    Object.keys(errors).forEach(field => {
                        errorMessage += `• ${errors[field].join(', ')}\n`;
                    });

                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Errors',
                        text: errorMessage,
                        confirmButtonText: 'OK'
                    });
                } else {
                    const message = xhr.responseJSON?.message || 'An error occurred while creating the journal entry.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: message,
                        confirmButtonText: 'OK'
                    });
                }
            }
        });
    });

    // Dynamic form validation helpers
    $('#checkAction, #approveAction').on('change', function() {
        const action = $(this).val();
        const commentsField = $(this).closest('form').find('textarea');
        const commentsLabel = commentsField.prev('label');

        if (action === 'reject') {
            commentsLabel.html('Rejection Reason <span class="text-danger">*</span>');
            commentsField.attr('required', true);
            commentsField.attr('placeholder', 'Please provide a reason for rejection (required)');
        } else {
            commentsLabel.html('Comments');
            commentsField.removeAttr('required');
            commentsField.attr('placeholder', 'Add your comments (optional)');
        }
});
</script>
@endpush
