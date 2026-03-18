@extends('layouts.main')

@section('title', 'Pending Multi-Level Approvals')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Imprest Management', 'url' => route('imprest.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Pending Approvals', 'url' => '#', 'icon' => 'bx bx-check-circle']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-primary">
                <i class="bx bx-check-circle me-2"></i>Pending Multi-Level Approvals
            </h5>
            <div>
                <a href="{{ route('imprest.requests.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Requests
                </a>
            </div>
        </div>

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

        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="bx bx-list-ul me-2"></i>Requests Awaiting Your Approval
                    <span class="badge bg-warning ms-2">{{ $pendingApprovals->count() }}</span>
                </h6>
            </div>
            <div class="card-body">
                @if($pendingApprovals->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Request #</th>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Purpose</th>
                                    <th>Amount</th>
                                    <th>Level</th>
                                    <th>Requested Date</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingApprovals as $approval)
                                    <tr>
                                        <td>
                                            <strong class="text-primary">{{ $approval->imprestRequest->request_number }}</strong>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm bg-primary text-white rounded-circle me-2">
                                                    {{ strtoupper(substr($approval->imprestRequest->employee->name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $approval->imprestRequest->employee->name }}</div>
                                                    <small class="text-muted">{{ $approval->imprestRequest->employee->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $approval->imprestRequest->department->name }}</td>
                                        <td>
                                            <span class="text-truncate" style="max-width: 200px; display: inline-block;" 
                                                  title="{{ $approval->imprestRequest->purpose }}">
                                                {{ $approval->imprestRequest->purpose }}
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="text-success">
                                                {{ number_format($approval->imprestRequest->amount_requested, 2) }}
                                            </strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">Level {{ $approval->approval_level }}</span>
                                        </td>
                                        <td>
                                            <small>{{ $approval->created_at->format('M d, Y H:i') }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewRequest('{{ $approval->imprestRequest->id }}')" 
                                                        title="View Details">
                                                    <i class="bx bx-show"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-success" 
                                                        onclick="showApprovalModal('{{ $approval->id }}', 'approve')" 
                                                        title="Approve">
                                                    <i class="bx bx-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="showApprovalModal('{{ $approval->id }}', 'reject')" 
                                                        title="Reject">
                                                    <i class="bx bx-x"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bx bx-check-circle display-1 text-muted"></i>
                        <h5 class="mt-3 text-muted">No Pending Approvals</h5>
                        <p class="text-muted">You don't have any imprest requests waiting for your approval.</p>
                        <a href="{{ route('imprest.requests.index') }}" class="btn btn-primary">
                            <i class="bx bx-arrow-back me-1"></i> Back to Imprest Requests
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalLabel">
                    <span id="modalIcon"></span>
                    <span id="modalTitle"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approvalForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="comments" class="form-label">Comments <span id="commentsRequired"></span></label>
                        <textarea class="form-control" id="comments" name="comments" rows="3" 
                                  placeholder="Enter your comments..."></textarea>
                        <div class="form-text">Provide reason for your decision (required for rejection).</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" id="submitBtn">
                        <span id="submitBtnText"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function viewRequest(requestId) {
    const baseUrl = '{{ route("imprest.requests.show", ":id") }}';
    const url = baseUrl.replace(':id', requestId);
    window.open(url, '_blank');
}

function showApprovalModal(approvalId, action) {
    const modal = document.getElementById('approvalModal');
    const form = document.getElementById('approvalForm');
    const modalTitle = document.getElementById('modalTitle');
    const modalIcon = document.getElementById('modalIcon');
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const commentsRequired = document.getElementById('commentsRequired');
    const commentsField = document.getElementById('comments');
    
    if (action === 'approve') {
        const approveUrl = '{{ route("imprest.multi-approvals.approve", ":approvalId") }}';
        form.action = approveUrl.replace(':approvalId', approvalId);
        modalTitle.textContent = 'Approve Request';
        modalIcon.innerHTML = '<i class="bx bx-check-circle text-success me-2"></i>';
        submitBtn.className = 'btn btn-success';
        submitBtnText.innerHTML = '<i class="bx bx-check me-1"></i> Approve';
        commentsRequired.textContent = '(Optional)';
        commentsField.required = false;
        commentsField.placeholder = 'Optional approval comments...';
    } else {
        const rejectUrl = '{{ route("imprest.multi-approvals.reject", ":approvalId") }}';
        form.action = rejectUrl.replace(':approvalId', approvalId);
        modalTitle.textContent = 'Reject Request';
        modalIcon.innerHTML = '<i class="bx bx-x-circle text-danger me-2"></i>';
        submitBtn.className = 'btn btn-danger';
        submitBtnText.innerHTML = '<i class="bx bx-x me-1"></i> Reject';
        commentsRequired.textContent = '(Required)';
        commentsField.required = true;
        commentsField.placeholder = 'Please provide reason for rejection...';
    }
    
    // Clear previous comments
    commentsField.value = '';
    
    // Show modal
    new bootstrap.Modal(modal).show();
}
</script>
@endpush