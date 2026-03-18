@extends('layouts.main')

@section('title', 'View Timesheet')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Timesheets', 'url' => route('hr.timesheets.index'), 'icon' => 'bx bx-time-five'],
                ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"><i class="bx bx-time-five me-1"></i>Timesheet Details</h6>
                <div class="d-flex gap-2">
                    @if($timesheet->canBeEdited())
                        <a href="{{ route('hr.timesheets.edit', $timesheet->id) }}" class="btn btn-primary">
                            <i class="bx bx-edit me-1"></i>Edit
                        </a>
                    @endif
                    @if($timesheet->canBeSubmitted() && $timesheet->status === 'draft')
                        <form action="{{ route('hr.timesheets.submit', $timesheet->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning">
                                <i class="bx bx-send me-1"></i>Submit
                            </button>
                        </form>
                    @endif
                    @if($timesheet->canBeApproved())
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                            <i class="bx bx-check me-1"></i>Approve
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bx bx-x me-1"></i>Reject
                        </button>
                    @endif
                    <a href="{{ route('hr.timesheets.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back
                    </a>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Timesheet Information</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Employee:</strong></div>
                                <div class="col-md-8">{{ $timesheet->employee->full_name }} ({{ $timesheet->employee->employee_number }})</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Date:</strong></div>
                                <div class="col-md-8">{{ $timesheet->timesheet_date->format('d M Y') }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Department (Project Account):</strong></div>
                                <div class="col-md-8">{{ $timesheet->department->name ?? 'N/A' }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Activity Type:</strong></div>
                                <div class="col-md-8">
                                    <span class="badge bg-{{ $timesheet->activity_type === 'work' ? 'primary' : ($timesheet->activity_type === 'training' ? 'info' : ($timesheet->activity_type === 'meeting' ? 'warning' : ($timesheet->activity_type === 'conference' ? 'success' : 'secondary'))) }}">
                                        {{ $timesheet->activity_type_label }}
                                    </span>
                                </div>
                            </div>

                            @if($timesheet->project_reference)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Project Reference:</strong></div>
                                <div class="col-md-8">{{ $timesheet->project_reference }}</div>
                            </div>
                            @endif

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Hours:</strong></div>
                                <div class="col-md-8">
                                    <strong>{{ number_format($timesheet->normal_hours, 2) }}</strong> normal hours
                                    @if($timesheet->overtime_hours > 0)
                                        + <strong>{{ number_format($timesheet->overtime_hours, 2) }}</strong> overtime hours
                                    @endif
                                    = <strong>{{ number_format($timesheet->total_hours, 2) }}</strong> total hours
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Status:</strong></div>
                                <div class="col-md-8">
                                    <span class="badge bg-{{ $timesheet->status_badge }}">{{ ucfirst($timesheet->status) }}</span>
                                </div>
                            </div>

                            @if($timesheet->description)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Description:</strong></div>
                                <div class="col-md-8">{!! nl2br(e($timesheet->description)) !!}</div>
                            </div>
                            @endif

                            @if($timesheet->priorities)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Priorities:</strong></div>
                                <div class="col-md-8">{!! nl2br(e($timesheet->priorities)) !!}</div>
                            </div>
                            @endif

                            @if($timesheet->achievements)
                            <div class="row mb-3">
                                <div class="col-md-4"><strong>Achievements:</strong></div>
                                <div class="col-md-8">{!! nl2br(e($timesheet->achievements)) !!}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Workflow Information</h5>
                            
                            @if($timesheet->submitted_by)
                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Submitted By:</strong></div>
                                <div class="col-md-7">{{ $timesheet->submittedBy->name ?? 'N/A' }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Submitted At:</strong></div>
                                <div class="col-md-7">{{ $timesheet->submitted_at?->format('d M Y H:i') ?? 'N/A' }}</div>
                            </div>
                            @endif

                            @if($timesheet->approved_by)
                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Approved By:</strong></div>
                                <div class="col-md-7">{{ $timesheet->approvedBy->name ?? 'N/A' }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Approved At:</strong></div>
                                <div class="col-md-7">{{ $timesheet->approved_at?->format('d M Y H:i') ?? 'N/A' }}</div>
                            </div>

                            @if($timesheet->approval_remarks)
                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Remarks:</strong></div>
                                <div class="col-md-7">{!! nl2br(e($timesheet->approval_remarks)) !!}</div>
                            </div>
                            @endif
                            @endif

                            @if($timesheet->rejected_by)
                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Rejected By:</strong></div>
                                <div class="col-md-7">{{ $timesheet->rejectedBy->name ?? 'N/A' }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Rejected At:</strong></div>
                                <div class="col-md-7">{{ $timesheet->rejected_at?->format('d M Y H:i') ?? 'N/A' }}</div>
                            </div>

                            @if($timesheet->rejection_reason)
                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Reason:</strong></div>
                                <div class="col-md-7">{!! nl2br(e($timesheet->rejection_reason)) !!}</div>
                            </div>
                            @endif
                            @endif

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Created:</strong></div>
                                <div class="col-md-7">{{ $timesheet->created_at->format('d M Y H:i') }}</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-5"><strong>Last Updated:</strong></div>
                                <div class="col-md-7">{{ $timesheet->updated_at->format('d M Y H:i') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('hr.timesheets.approve', $timesheet->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Timesheet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="approval_remarks" class="form-label">Approval Remarks (Optional)</label>
                        <textarea name="approval_remarks" id="approval_remarks" class="form-control" rows="3"></textarea>
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
            <form action="{{ route('hr.timesheets.reject', $timesheet->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Timesheet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3" required></textarea>
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

