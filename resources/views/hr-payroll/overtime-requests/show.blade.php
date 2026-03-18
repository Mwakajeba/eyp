@extends('layouts.main')

@section('title', 'Overtime Request Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Overtime Requests', 'url' => route('hr.overtime-requests.index'), 'icon' => 'bx bx-time'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1">
                    <i class="bx bx-time text-primary me-2"></i>Overtime Request Details
                </h5>
                <p class="text-muted mb-0">View and manage overtime request information</p>
            </div>
            <div>
                <a href="{{ route('hr.overtime-requests.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to List
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Request Information Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom">
                        <h6 class="mb-0">
                            <i class="bx bx-info-circle me-2"></i>Request Information
                        </h6>
                    </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">Employee</label>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2">
                                        <div class="avatar-title bg-light-primary text-primary rounded-circle">
                                            <i class="bx bx-user"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $overtimeRequest->employee->full_name }}</div>
                                        <small class="text-muted">{{ $overtimeRequest->employee->employee_number }}</small>
                                    </div>
                                </div>
                    </div>
                    <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">Overtime Date</label>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2">
                                        <div class="avatar-title bg-light-info text-info rounded-circle">
                                            <i class="bx bx-calendar"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $overtimeRequest->overtime_date->format('d M Y') }}</div>
                                        <small class="text-muted">{{ $overtimeRequest->overtime_date->format('l') }}</small>
                                    </div>
                                </div>
                    </div>
                    <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">Total Overtime Hours</label>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2">
                                        <div class="avatar-title bg-light-success text-success rounded-circle">
                                            <i class="bx bx-time"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-bold fs-5">{{ number_format($overtimeRequest->total_overtime_hours, 2) }} hrs</div>
                                    </div>
                    </div>
                    </div>
                    <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">Status</label>
                                <div>
                                    @if($overtimeRequest->status == 'approved')
                                        <span class="badge bg-success fs-6 px-3 py-2">
                                            <i class="bx bx-check-circle me-1"></i>Approved
                                        </span>
                                    @elseif($overtimeRequest->status == 'rejected')
                                        <span class="badge bg-danger fs-6 px-3 py-2">
                                            <i class="bx bx-x-circle me-1"></i>Rejected
                                        </span>
                                    @else
                                        <span class="badge bg-warning fs-6 px-3 py-2">
                                            <i class="bx bx-time-five me-1"></i>Pending
                        </span>
                                    @endif
                                </div>
                    </div>
                    @if($overtimeRequest->approved_by)
                    <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">Approved By</label>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2">
                                        <div class="avatar-title bg-light-primary text-primary rounded-circle">
                                            <i class="bx bx-user-check"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $overtimeRequest->approver->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($overtimeRequest->approved_at)
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">Approved At</label>
                                <div>
                                    <i class="bx bx-calendar-check me-1 text-muted"></i>
                                    <span class="fw-semibold">{{ $overtimeRequest->approved_at->format('d M Y H:i') }}</span>
                                </div>
                    </div>
                    @endif
                    @if($overtimeRequest->reason)
                    <div class="col-md-12">
                                <label class="form-label fw-semibold text-muted small">Reason</label>
                                <div class="alert alert-light border">
                                    <i class="bx bx-info-circle me-2"></i>{{ $overtimeRequest->reason }}
                                </div>
                    </div>
                    @endif
                    @if($overtimeRequest->rejection_reason)
                    <div class="col-md-12">
                                <label class="form-label fw-semibold text-muted small">Rejection Reason</label>
                                <div class="alert alert-danger border-danger">
                                    <i class="bx bx-error-circle me-2"></i>{{ $overtimeRequest->rejection_reason }}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Overtime Lines Breakdown -->
                <div class="card shadow-sm">
                    <div class="card-header bg-transparent border-bottom">
                        <h6 class="mb-0">
                            <i class="bx bx-list-ul me-2"></i>Overtime Breakdown
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="25%">
                                            <i class="bx bx-time me-1"></i>Overtime Hours
                                        </th>
                                        <th width="20%">
                                            <i class="bx bx-calendar me-1"></i>Day Type
                                        </th>
                                        <th width="20%">
                                            <i class="bx bx-trending-up me-1"></i>Rate
                                        </th>
                                        <th width="30%" class="text-end">
                                            <i class="bx bx-calculator me-1"></i>Amount
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($overtimeRequest->lines as $index => $line)
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge bg-light-primary text-primary">{{ $index + 1 }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ number_format($line->overtime_hours, 2) }}</span>
                                            <small class="text-muted d-block">hours</small>
                                        </td>
                                        <td>
                                            @if($line->day_type == 'weekday')
                                                <span class="badge bg-info">
                                                    <i class="bx bx-calendar me-1"></i>Weekday
                                                </span>
                                            @elseif($line->day_type == 'weekend')
                                                <span class="badge bg-primary">
                                                    <i class="bx bx-calendar-week me-1"></i>Weekend
                                                </span>
                                            @else
                                                <span class="badge bg-warning">
                                                    <i class="bx bx-calendar-event me-1"></i>Holiday
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $line->overtime_rate }}x</span>
                                            <small class="text-muted d-block">multiplier</small>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold text-success">{{ number_format($line->overtime_hours * $line->overtime_rate, 2) }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                    <tr class="table-active">
                                        <td colspan="2" class="fw-bold">
                                            <i class="bx bx-calculator me-1"></i>Total
                                        </td>
                                        <td colspan="2" class="text-end fw-semibold">
                                            {{ number_format($overtimeRequest->total_overtime_hours, 2) }} hrs
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold fs-5 text-success">{{ number_format($overtimeRequest->overtime_amount, 2) }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Quick Actions -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom">
                        <h6 class="mb-0">
                            <i class="bx bx-cog me-2"></i>Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('hr.overtime-requests.index') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-arrow-back me-1"></i>Back to List
                            </a>
                            @if($overtimeRequest->status === 'pending')
                                <a href="{{ route('hr.overtime-requests.edit', $overtimeRequest->hash_id) }}" class="btn btn-outline-primary">
                                    <i class="bx bx-edit me-1"></i>Edit Request
                                </a>
                            @endif
                            @if($overtimeRequest->employee)
                                <a href="{{ route('hr.employees.show', $overtimeRequest->employee->hash_id) }}" class="btn btn-outline-info">
                                    <i class="bx bx-user me-1"></i>View Employee
                                </a>
                    @endif
                        </div>
                    </div>
                </div>

                <!-- Approval Actions -->
                @if($overtimeRequest->status === 'pending' && $canApprove)
                <div class="card shadow-sm border-warning">
                    <div class="card-header bg-warning bg-opacity-10 border-bottom border-warning">
                        <h6 class="mb-0 text-warning">
                            <i class="bx bx-check-circle me-2"></i>Approval Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success" id="approveBtn" data-id="{{ $overtimeRequest->hash_id }}">
                                <i class="bx bx-check me-1"></i>Approve Request
                            </button>
                            <button type="button" class="btn btn-danger" id="rejectBtn" data-id="{{ $overtimeRequest->hash_id }}">
                                <i class="bx bx-x me-1"></i>Reject Request
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Request Summary -->
                <div class="card shadow-sm">
                    <div class="card-header bg-transparent border-bottom">
                        <h6 class="mb-0">
                            <i class="bx bx-stats me-2"></i>Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Total Entries</span>
                            <span class="fw-bold">{{ $overtimeRequest->lines->count() }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Total Hours</span>
                            <span class="fw-bold text-primary">{{ number_format($overtimeRequest->total_overtime_hours, 2) }} hrs</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted">Total Amount</span>
                            <span class="fw-bold text-success">{{ number_format($overtimeRequest->overtime_amount, 2) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Created</span>
                            <small class="text-muted">{{ $overtimeRequest->created_at->format('d M Y H:i') }}</small>
                        </div>
                        @if($overtimeRequest->updated_at != $overtimeRequest->created_at)
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="text-muted">Last Updated</span>
                            <small class="text-muted">{{ $overtimeRequest->updated_at->format('d M Y H:i') }}</small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bx bx-x-circle me-2"></i>Reject Overtime Request
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectForm">
                <div class="modal-body">
                    <input type="hidden" id="rejectRequestId" name="request_id">
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="4" required placeholder="Please provide a reason for rejecting this overtime request..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bx bx-x me-1"></i>Reject Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar-sm {
        width: 40px;
        height: 40px;
    }
    
    .avatar-title {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
    }
    
    .table th {
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge {
        font-weight: 500;
        padding: 0.5em 0.75em;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Approve button
    $('#approveBtn').on('click', function() {
        let id = $(this).data('id');
        
        Swal.fire({
            title: 'Approve Overtime Request?',
            text: 'Are you sure you want to approve this overtime request?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/hr-payroll/overtime-requests/${id}/approve`,
                    type: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Approved!',
                                text: response.message || 'Overtime request approved successfully.',
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
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'Something went wrong. Please try again.'
                        });
                    }
                });
            }
        });
    });

    // Reject button
    $('#rejectBtn').on('click', function() {
        let id = $(this).data('id');
        $('#rejectRequestId').val(id);
        $('#rejection_reason').val('');
        $('#rejectModal').modal('show');
    });

    // Reject form submission
    $('#rejectForm').on('submit', function(e) {
        e.preventDefault();
        let id = $('#rejectRequestId').val();
        let reason = $('#rejection_reason').val();
        
        $.ajax({
            url: `/hr-payroll/overtime-requests/${id}/reject`,
            type: 'POST',
            data: {
                rejection_reason: reason
            },
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            beforeSend: function() {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                if (response.success) {
                    $('#rejectModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Rejected!',
                        text: response.message || 'Overtime request rejected successfully.',
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
                    title: 'Error!',
                    text: xhr.responseJSON?.message || 'Something went wrong. Please try again.'
                });
            }
        });
    });
});
</script>
@endpush
