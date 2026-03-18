@extends('layouts.main')

@section('title', 'Contract Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Contracts', 'url' => route('hr.contracts.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">Contract Details</h6>
        <hr />
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Contract Information</h5>
                            <a href="{{ route('hr.contracts.edit', $contract->id) }}" class="btn btn-sm btn-primary">
                                <i class="bx bx-edit me-1"></i>Edit
                            </a>
                        </div>
                        <hr />
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Employee</label>
                                <p class="mb-0">{{ $contract->employee->full_name }} ({{ $contract->employee->employee_number }})</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contract Type</label>
                                <p class="mb-0"><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $contract->contract_type)) }}</span></p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Start Date</label>
                                <p class="mb-0">{{ $contract->start_date->format('d M Y') }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">End Date</label>
                                <p class="mb-0">{{ $contract->end_date ? $contract->end_date->format('d M Y') : 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Status</label>
                                <p class="mb-0">
                                    @if($contract->status == 'active')
                                        <span class="badge bg-success">Active</span>
                                    @elseif($contract->status == 'expired')
                                        <span class="badge bg-warning">Expired</span>
                                    @else
                                        <span class="badge bg-danger">Terminated</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Working Hours per Week</label>
                                <p class="mb-0">{{ $contract->working_hours_per_week }} hours</p>
                            </div>
                            @if($contract->salary)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contract Salary</label>
                                <p class="mb-0">
                                    <strong>{{ number_format($contract->salary, 2) }} TZS</strong>
                                    @if($contract->employee->basic_salary && $contract->salary != $contract->employee->basic_salary)
                                        <br><small class="text-muted">
                                            (Employee's basic salary: {{ number_format($contract->employee->basic_salary, 2) }} TZS)
                                        </small>
                                    @endif
                                </p>
                            </div>
                            @else
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contract Salary</label>
                                <p class="mb-0">
                                    <span class="text-muted">Not set</span>
                                    @if($contract->employee->basic_salary)
                                        <br><small class="text-muted">
                                            (Using employee's basic salary: {{ number_format($contract->employee->basic_salary, 2) }} TZS)
                                        </small>
                                    @endif
                                </p>
                            </div>
                            @endif
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Renewal Required</label>
                                <p class="mb-0">
                                    @if($contract->renewal_flag)
                                        <span class="badge bg-info">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                @if($contract->amendments->count() > 0)
                <div class="card mt-3">
                    <div class="card-body">
                        <h5 class="card-title">Contract Amendments</h5>
                        <hr />
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Changes</th>
                                        <th>Approved By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contract->amendments as $amendment)
                                    <tr>
                                        <td>{{ $amendment->effective_date->format('d M Y') }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $amendment->amendment_type)) }}</td>
                                        <td>
                                            @if($amendment->old_value && $amendment->new_value)
                                                <small class="text-muted">Changed</small>
                                            @endif
                                        </td>
                                        <td>{{ $amendment->approvedBy ? $amendment->approvedBy->name : 'N/A' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Contract Attachments -->
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Contract Documents</h5>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadAttachmentModal">
                                <i class="bx bx-upload me-1"></i>Upload Document
                            </button>
                        </div>
                        <hr />
                        @if($contract->attachments->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Document Name</th>
                                            <th>Type</th>
                                            <th>Size</th>
                                            <th>Uploaded By</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($contract->attachments as $attachment)
                                        <tr>
                                            <td>
                                                <i class="bx bx-file me-1"></i>
                                                {{ $attachment->original_name }}
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ strtoupper($attachment->document_type ?? 'Other') }}</span>
                                            </td>
                                            <td>{{ $attachment->formatted_size }}</td>
                                            <td>{{ $attachment->uploader ? $attachment->uploader->name : 'N/A' }}</td>
                                            <td>{{ $attachment->created_at->format('d M Y') }}</td>
                                            <td>
                                                <a href="{{ $attachment->url }}" target="_blank" class="btn btn-sm btn-outline-primary" title="View">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <a href="{{ $attachment->url }}" download class="btn btn-sm btn-outline-success" title="Download">
                                                    <i class="bx bx-download"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-attachment" 
                                                        data-id="{{ $attachment->id }}" data-name="{{ $attachment->original_name }}" title="Delete">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info mb-0">
                                <i class="bx bx-info-circle me-2"></i>No documents attached to this contract yet.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Upload Attachment Modal -->
                <div class="modal fade" id="uploadAttachmentModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="bx bx-upload me-2"></i>Upload Contract Document
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="uploadAttachmentForm" enctype="multipart/form-data">
                                @csrf
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Document Type</label>
                                        <select name="document_type" class="form-select" required>
                                            <option value="">-- Select Type --</option>
                                            <option value="signed_contract">Signed Contract</option>
                                            <option value="amendment">Amendment Document</option>
                                            <option value="renewal">Renewal Letter</option>
                                            <option value="termination">Termination Letter</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">File</label>
                                        <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                        <div class="form-text">Accepted formats: PDF, DOC, DOCX, JPG, PNG (Max: 10MB)</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description (Optional)</label>
                                        <textarea name="description" class="form-control" rows="3" placeholder="Add a description for this document..."></textarea>
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
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Quick Actions</h6>
                        <hr />
                        <div class="d-grid gap-2">
                            <a href="{{ route('hr.employees.show', $contract->employee->hash_id) }}" class="btn btn-outline-primary">
                                <i class="bx bx-user me-1"></i>View Employee
                            </a>
                            <a href="{{ route('hr.contracts.edit', $contract->id) }}" class="btn btn-outline-info">
                                <i class="bx bx-edit me-1"></i>Edit Contract
                            </a>
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
$(document).ready(function() {
    // Upload Attachment Form
    $('#uploadAttachmentForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let submitBtn = $(this).find('button[type="submit"]');
        let originalText = submitBtn.html();
        
        submitBtn.html('<i class="bx bx-loader-alt bx-spin me-1"></i>Uploading...');
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: "{{ route('hr.contracts.attachments.store', $contract->id) }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#uploadAttachmentModal').modal('hide');
                    $('#uploadAttachmentForm')[0].reset();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                let message = 'Failed to upload document. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    let errors = Object.values(xhr.responseJSON.errors).flat();
                    message = errors.join('<br>');
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    html: message
                });
            },
            complete: function() {
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        });
    });

    // Delete Attachment
    $(document).on('click', '.delete-attachment', function() {
        let attachmentId = $(this).data('id');
        let attachmentName = $(this).data('name');
        let contractId = {{ $contract->id }};
        
        Swal.fire({
            title: 'Delete Document',
            text: `Are you sure you want to delete "${attachmentName}"? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/hr-payroll/contracts/${contractId}/attachments/${attachmentId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = 'Failed to delete document. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message
                        });
                    }
                });
            }
        });
    });

    // Reset form on modal close
    $('#uploadAttachmentModal').on('hidden.bs.modal', function() {
        $('#uploadAttachmentForm')[0].reset();
    });
});
</script>
@endpush

