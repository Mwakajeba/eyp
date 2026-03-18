@extends('layouts.main')

@section('title', 'Leave Request Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Leave Management', 'url' => route('hr.leave.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Leave Requests', 'url' => route('hr.leave.requests.index'), 'icon' => 'bx bx-file'],
            ['label' => $request->request_number, 'url' => '#', 'icon' => 'bx bx-detail']
        ]" />
            <h6 class="mb-0 text-uppercase">LEAVE REQUEST DETAILS</h6>
            <hr />

            <!-- Action Buttons -->
            <div class="row mb-3">
                <div class="col-12 text-end">
                    <a href="{{ route('hr.leave.requests.index') }}" class="btn btn-secondary me-2">
                        <i class="bx bx-arrow-back"></i> Back
                    </a>

                    @if($request->status === 'draft')
                        @can('update', $request)
                            <a href="{{ route('hr.leave.requests.edit', $request) }}" class="btn btn-warning me-2">
                                <i class="bx bx-edit"></i> Edit
                            </a>
                        @endcan

                        <form action="{{ route('hr.leave.requests.submit', $request) }}" method="POST" class="d-inline" id="submitLeaveRequestForm">
                            @csrf
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bx bx-send"></i> Submit
                            </button>
                        </form>
                    @endif

                    @if($request->status === 'pending' || $request->status === 'submitted')
                        @can('approve', $request)
                            <button type="button" class="btn btn-success me-2" data-bs-toggle="modal"
                                data-bs-target="#approveModal">
                                <i class="bx bx-check"></i> Approve
                            </button>
                        @endcan

                        @can('reject', $request)
                            <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bx bx-x"></i> Reject
                            </button>
                        @endcan

                        @can('returnForEdit', $request)
                            <form action="{{ route('hr.leave.requests.return', $request) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning me-2"
                                    onclick="return confirm('Return this request for editing?')">
                                    <i class="bx bx-undo"></i> Return
                                </button>
                            </form>
                        @endcan
                    @endif

                    @if(in_array($request->status, ['pending', 'approved']))
                        @can('cancel', $request)
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                <i class="bx bx-x-circle"></i> Cancel
                            </button>
                        @endcan
                    @endif
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bx bx-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bx bx-error me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <!-- Main Details -->
                <div class="col-md-8">
                    <!-- Request Information -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bx bx-info-circle me-2"></i>Request Information
                                <span class="badge bg-{{ $request->status_badge }} float-end">
                                    {{ $request->status_label }}
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Request Number:</strong>
                                    <p>{{ $request->request_number }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Employee:</strong>
                                    <p>{{ $request->employee->full_name ?? 'N/A' }}</p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Leave Type:</strong>
                                    <p>{{ $request->leaveType->name ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Total Days:</strong>
                                    <p>{{ number_format($request->total_days, 1) }} days</p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <strong>Reason:</strong>
                                    <p>{{ $request->reason ?? 'N/A' }}</p>
                                </div>
                            </div>

                            @if($request->reliever)
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <strong>Reliever:</strong>
                                        <p>{{ $request->reliever->full_name }}</p>
                                    </div>
                                </div>
                            @endif

                            @if($request->rejection_reason)
                                <div class="row">
                                    <div class="col-12">
                                        <div class="alert alert-danger">
                                            <strong>Rejection Reason:</strong>
                                            <p class="mb-0">{{ $request->rejection_reason }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Leave Periods -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-calendar me-2"></i>Leave Period(s)</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Type</th>
                                            <th>Days</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($request->segments as $segment)
                                            <tr>
                                                <td>{{ $segment->start_at->format('d M Y') }}</td>
                                                <td>{{ $segment->end_at->format('d M Y') }}</td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        {{ ucfirst(str_replace('_', ' ', $segment->granularity)) }}
                                                    </span>
                                                </td>
                                                <td>{{ number_format($segment->days_count, 1) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">No leave periods found</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bx bx-paperclip me-2"></i>Attachments</h5>
                            @can('update', $request)
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAttachmentModal">
                                    <i class="bx bx-plus me-1"></i>Add Document
                                </button>
                            @endcan
                        </div>
                        <div class="card-body">
                            @if($request->attachments->count() > 0)
                                <div class="list-group" id="attachmentsList">
                                    @foreach($request->attachments as $attachment)
                                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            <a href="{{ Storage::url($attachment->path) }}" target="_blank" class="text-decoration-none flex-grow-1">
                                                <i class="bx bx-file me-2"></i>
                                                {{ $attachment->original_name }}
                                                <span class="badge bg-secondary ms-2">{{ $attachment->formatted_size }}</span>
                                            </a>
                                            @can('update', $request)
                                                <button type="button" class="btn btn-sm btn-danger ms-2" onclick="deleteAttachment({{ $attachment->id }})">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted mb-0">
                                    <i class="bx bx-info-circle me-1"></i>No attachments uploaded yet.
                                    @can('update', $request)
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#addAttachmentModal">Add a document</a>
                                    @endcan
                                </p>
                            @endif
                        </div>
                    </div>

                    <!-- Approval History -->
                    @if($request->approvals->count() > 0)
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bx bx-history me-2"></i>Approval History</h5>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    @foreach($request->approvals as $approval)
                                        <div class="timeline-item mb-3">
                                            <div class="d-flex">
                                                <div class="me-3">
                                                    @if($approval->action === 'approved')
                                                        <i class="bx bx-check-circle text-success" style="font-size: 2rem;"></i>
                                                    @elseif($approval->action === 'rejected')
                                                        <i class="bx bx-x-circle text-danger" style="font-size: 2rem;"></i>
                                                    @else
                                                        <i class="bx bx-info-circle text-info" style="font-size: 2rem;"></i>
                                                    @endif
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">{{ ucfirst($approval->action) }}</h6>
                                                    <p class="mb-1">By: <strong>{{ $approval->approver->name ?? 'System' }}</strong>
                                                    </p>
                                                    <p class="mb-1 small text-muted">
                                                        {{ $approval->created_at->format('d M Y, h:i A') }}</p>
                                                    @if($approval->notes)
                                                        <p class="mb-0 small">Notes: {{ $approval->notes }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Status Card -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Status</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <span class="badge bg-{{ $request->status_badge }}"
                                    style="font-size: 1.2rem; padding: 10px 20px;">
                                    {{ $request->status_label }}
                                </span>
                            </div>
                            <p class="mb-1"><strong>Created:</strong></p>
                            <p>{{ $request->created_at->format('d M Y, h:i A') }}</p>

                            @if($request->requested_at)
                                <p class="mb-1"><strong>Submitted:</strong></p>
                                <p>{{ $request->requested_at->format('d M Y, h:i A') }}</p>
                            @endif

                            @if($request->decision_at)
                                <p class="mb-1"><strong>Decision Date:</strong></p>
                                <p>{{ $request->decision_at->format('d M Y, h:i A') }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Document Requirement -->
                    @if($request->requires_doc)
                        <div class="card border-warning mb-3">
                            <div class="card-body">
                                <div class="alert alert-warning mb-3">
                                    <h6 class="alert-heading">
                                        <i class="bx bx-error me-2"></i>Document Required
                                    </h6>
                                    <p class="small mb-0">
                                        Supporting documents are required for this leave request.
                                    </p>
                                </div>
                                @if(isset($canAddDocument) && $canAddDocument)
                                    <button type="button" class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#addAttachmentModal">
                                        <i class="bx bx-plus me-1"></i>Add Document
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-cog me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('hr.leave.requests.index') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bx bx-list-ul"></i> All Requests
                                </a>
                                <a href="{{ route('hr.leave.requests.create') }}" class="btn btn-outline-success btn-sm">
                                    <i class="bx bx-plus"></i> New Request
                                </a>
                                <a href="{{ route('hr.leave.balances.show', $request->employee_id) }}"
                                    class="btn btn-outline-info btn-sm">
                                    <i class="bx bx-bar-chart"></i> View Balance
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('hr.leave.requests.approve', $request) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Approve Leave Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="approve_notes" class="form-label">Notes (Optional)</label>
                            <textarea name="notes" id="approve_notes" rows="3" class="form-control"
                                placeholder="Add any notes or comments..."></textarea>
                        </div>
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-2"></i>
                            Are you sure you want to approve this leave request?
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
                <form action="{{ route('hr.leave.requests.reject', $request) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Leave Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="reject_reason" class="form-label">Reason for Rejection <span
                                    class="text-danger">*</span></label>
                            <textarea name="reason" id="reject_reason" rows="3" class="form-control"
                                placeholder="Please provide a reason for rejection..." required></textarea>
                        </div>
                        <div class="alert alert-warning">
                            <i class="bx bx-error me-2"></i>
                            This action cannot be undone. The employee will be notified of the rejection.
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

    <!-- Cancel Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('hr.leave.requests.cancel', $request) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Cancel Leave Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="cancel_reason" class="form-label">Reason for Cancellation <span
                                    class="text-danger">*</span></label>
                            <textarea name="reason" id="cancel_reason" rows="3" class="form-control"
                                placeholder="Please provide a reason for cancellation..." required></textarea>
                        </div>
                        <div class="alert alert-warning">
                            <i class="bx bx-error me-2"></i>
                            Are you sure you want to cancel this leave request?
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Attachment Modal -->
    @if(isset($canAddDocument) && $canAddDocument)
    <div class="modal fade" id="addAttachmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bx bx-paperclip me-2"></i>Add Document
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addAttachmentForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="attachment_file" class="form-label">Select File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control @error('file') is-invalid @enderror" 
                                id="attachment_file" name="file" required 
                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                <i class="bx bx-info-circle me-1"></i>Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max 2MB)
                            </small>
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

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // SweetAlert confirmation before submitting leave request for approval
        $('#submitLeaveRequestForm').on('submit', function(e) {
            e.preventDefault();
            const form = this;

            Swal.fire({
                title: 'Submit Leave Request?',
                text: 'Submit this leave request for approval?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, submit it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    const btn = form.querySelector('button[type="submit"]');
                    const originalHtml = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Submitting...';
                    
                    // Submit the form
                    form.submit();
                }
            });
        });

        // Handle attachment upload
        $('#addAttachmentForm').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            // Validate file
            const fileInput = document.getElementById('attachment_file');
            if (!fileInput.files || !fileInput.files[0]) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select a file to upload.'
                });
                return;
            }

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Uploading...';

            $.ajax({
                url: '{{ route('hr.leave.requests.attachments.store', $request) }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        $('#addAttachmentModal').modal('hide');
                        form.reset();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message || 'Document uploaded successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Failed to upload document.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        errorMessage = Object.values(errors).flat().join('<br>');
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: errorMessage
                    });
                },
                complete: function() {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            });
        });

        // Delete attachment function
        window.deleteAttachment = function(attachmentId) {
            Swal.fire({
                title: 'Delete Document?',
                text: 'Are you sure you want to delete this document?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url('hr-payroll/leave/requests') }}/{{ $request->hash_id }}/attachments/${attachmentId}`,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message || 'Document deleted successfully.',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message || 'Failed to delete document.'
                            });
                        }
                    });
                }
            });
        };
    });
</script>
@endpush
