@extends('layouts.main')

@section('title', 'Pending Retirement Approvals')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Retirement Management', 'url' => route('imprest.retirement.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Pending Approvals', 'url' => '#', 'icon' => 'bx bx-check-circle']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-primary">
                <i class="bx bx-check-circle me-2"></i>Pending Retirement Approvals
            </h5>
            <div>
                <a href="{{ route('imprest.retirement.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Retirements
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
                    <i class="bx bx-list-ul me-2"></i>Retirements Awaiting Your Approval
                    <span class="badge bg-warning ms-2">{{ $pendingApprovals->count() }}</span>
                </h6>
            </div>
            <div class="card-body">
                @if($pendingApprovals->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Retirement #</th>
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
                                            <strong class="text-primary">{{ $approval->retirement->retirement_number }}</strong>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm bg-primary text-white rounded-circle me-2">
                                                    {{ strtoupper(substr($approval->retirement->employee->name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $approval->retirement->employee->name }}</div>
                                                    <small class="text-muted">{{ $approval->retirement->employee->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $approval->retirement->department->name }}</td>
                                        <td>
                                            <span class="text-truncate" style="max-width: 200px; display: inline-block;" 
                                                  title="{{ $approval->retirement->purpose }}">
                                                {{ $approval->retirement->purpose }}
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="text-success">
                                                {{ number_format($approval->retirement->total_retirement_amount, 2) }}
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
                                                        onclick="viewRequest('{{ $approval->retirement->id }}')" 
                                                        title="View Details">
                                                    <i class="bx bx-show"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-success" 
                                                        onclick="approveRequest('{{ $approval->id }}')" 
                                                        title="Approve">
                                                    <i class="bx bx-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="rejectRequest('{{ $approval->id }}')" 
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

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $pendingApprovals->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="bx bx-check-circle text-muted" style="font-size: 4rem;"></i>
                        </div>
                        <h6 class="text-muted">No Pending Approvals</h6>
                        <p class="text-muted mb-0">You have no retirement requests waiting for your approval.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- View Request Modal -->
<div class="modal fade" id="viewRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Retirement Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="requestDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Request Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="approveComments" class="form-label">Comments (Optional)</label>
                        <textarea class="form-control" id="approveComments" name="comments" rows="3" 
                                  placeholder="Add any comments..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bx bx-check me-1"></i>Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Request Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejectComments" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectComments" name="comments" rows="3" 
                                  placeholder="Please provide a reason for rejection..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bx bx-x me-1"></i>Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function viewRequest(requestId) {
    // Show loading state
    document.getElementById('requestDetails').innerHTML = '<div class="text-center"><i class="bx bx-loader-alt bx-spin"></i> Loading...</div>';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('viewRequestModal'));
    modal.show();
    
    // Fetch request details
    fetch(`/imprest/retirement/${requestId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            document.getElementById('requestDetails').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <strong>Retirement Number:</strong> ${data.retirement_number}<br>
                        <strong>Employee:</strong> ${data.employee.name}<br>
                        <strong>Department:</strong> ${data.department.name}<br>
                        <strong>Purpose:</strong> ${data.purpose}<br>
                    </div>
                    <div class="col-md-6">
                        <strong>Amount:</strong> ${new Intl.NumberFormat().format(data.total_retirement_amount)}<br>
                        <strong>Status:</strong> <span class="badge bg-warning">${data.status}</span><br>
                        <strong>Requested Date:</strong> ${new Date(data.created_at).toLocaleDateString()}<br>
                    </div>
                </div>
                <hr>
                <div class="mt-3">
                    <strong>Description:</strong><br>
                    <p class="mt-2">${data.description || 'No description provided'}</p>
                </div>
            `;
        })
        .catch(error => {
            document.getElementById('requestDetails').innerHTML = '<div class="alert alert-danger">Error loading request details</div>';
        });
}

function approveRequest(approvalId) {
    document.getElementById('approveForm').action = `{{ url('/imprest/retirement-multi-approvals') }}/${approvalId}/approve`;
    const modal = new bootstrap.Modal(document.getElementById('approveModal'));
    modal.show();
}

function rejectRequest(approvalId) {
    document.getElementById('rejectForm').action = `{{ url('/imprest/retirement-multi-approvals') }}/${approvalId}/reject`;
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}
</script>
@endpush
@endsection