<div class="row mb-4">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0">
                    <i class="bx bx-time-five me-2"></i>
                    Multi-Level Approval Required
                    @if($payroll->requires_approval)
                        <span class="badge bg-info ms-2">Level {{ $payroll->current_approval_level ?? 1 }}</span>
                    @endif
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <i class="bx bx-info-circle me-2"></i>
                    @if($payroll->requires_approval)
                        This payroll requires approval at level {{ $payroll->current_approval_level ?? 1 }}.
                        @php
                            $pendingApprovals = App\Models\PayrollApproval::where('payroll_id', $payroll->id)
                                ->where('approval_level', $payroll->current_approval_level ?? 1)
                                ->where('status', 'pending')
                                ->with('approver')
                                ->get();
                        @endphp
                        Waiting for approval from:
                        @foreach($pendingApprovals as $approval)
                            <strong>{{ $approval->approver->name }}</strong>@if(!$loop->last), @endif
                        @endforeach
                    @else
                        This payroll has been processed and is waiting for approval.
                    @endif
                </div>

                @if($payroll->requires_approval)
                    <div class="mb-3">
                        <h6>Approval Progress:</h6>
                        @php
                            $allApprovals = App\Models\PayrollApproval::where('payroll_id', $payroll->id)
                                ->orderBy('approval_level')
                                ->with('approver')
                                ->get()
                                ->groupBy('approval_level');
                        @endphp

                        @foreach($allApprovals as $level => $approvals)
                            @php
                                $levelStatus = 'pending';
                                $allApproved = $approvals->every(fn($a) => $a->status === 'approved');
                                $hasRejected = $approvals->contains(fn($a) => $a->status === 'rejected');
                                $isCurrent = $level == ($payroll->current_approval_level ?? 1);

                                if ($hasRejected) {
                                    $levelStatus = 'rejected';
                                } elseif ($allApproved) {
                                    $levelStatus = 'completed';
                                } elseif ($isCurrent) {
                                    $levelStatus = 'current';
                                }
                            @endphp

                            <div class="approval-level {{ $levelStatus }} mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>Level {{ $level }}:</strong>
                                    @if($levelStatus === 'completed')
                                        <i class="bx bx-check-circle text-success"></i>
                                    @elseif($levelStatus === 'rejected')
                                        <i class="bx bx-x-circle text-danger"></i>
                                    @elseif($levelStatus === 'current')
                                        <i class="bx bx-time-five text-warning"></i>
                                    @else
                                        <i class="bx bx-circle text-secondary"></i>
                                    @endif
                                </div>
                                <div class="ms-3 mt-1">
                                    @foreach($approvals as $approval)
                                        <div class="d-inline-block me-2 mb-1">
                                            <span class="badge
                                                @if($approval->status === 'approved') bg-success
                                                @elseif($approval->status === 'rejected') bg-danger
                                                @else bg-warning text-dark
                                                @endif">
                                                <i class="
                                                    @if($approval->status === 'approved') bx bx-check
                                                    @elseif($approval->status === 'rejected') bx bx-x
                                                    @else bx bx-time-five
                                                    @endif me-1"></i>
                                                {{ $approval->approver->name }}
                                                @if($approval->approved_at)
                                                    <small class="ms-1">({{ $approval->approved_at->format('M d, H:i') }})</small>
                                                @endif
                                            </span>
                                            @if($approval->remarks)
                                                <br><small class="text-muted">{{ $approval->remarks }}</small>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @php
                    $canCurrentUserApprove = Auth::check() && App\Models\PayrollApproval::where('payroll_id', $payroll->id)
                        ->where('approver_id', Auth::id())
                        ->where('approval_level', $payroll->current_approval_level ?? 1)
                        ->where('status', 'pending')
                        ->exists();
                @endphp

                @if($canCurrentUserApprove)
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success" onclick="approvePayroll('{{ $payroll->hash_id }}')">
                            <i class="bx bx-check me-1"></i>Approve
                        </button>
                        <button type="button" class="btn btn-danger" onclick="rejectPayroll('{{ $payroll->hash_id }}')">
                            <i class="bx bx-x me-1"></i>Reject
                        </button>
                    </div>
                @else
                    <div class="alert alert-warning mb-0">
                        <i class="bx bx-info-circle me-2"></i>
                        You are not authorized to approve this payroll at the current level.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .approval-level {
        padding: 0.5rem;
        background-color: #f8f9fa;
        border-left: 4px solid #0d6efd;
        margin-bottom: 0.5rem;
        border-radius: 0.25rem;
    }
    .approval-level.current {
        background-color: #fff3cd;
        border-left-color: #ffc107;
    }
    .approval-level.completed {
        background-color: #d1edff;
        border-left-color: #198754;
    }
    .approval-level.rejected {
        background-color: #f8d7da;
        border-left-color: #dc3545;
    }
</style>

