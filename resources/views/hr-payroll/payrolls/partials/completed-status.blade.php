<div class="row mb-4">
    <div class="col-12">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">
                    <i class="bx bx-check-circle me-2"></i>
                    Approved & Ready for Payment
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    @if($payroll->approved_by)
                        <div class="col-md-6">
                            <strong>Approved By:</strong> {{ $payroll->approvedBy->name ?? 'N/A' }}<br>
                            <strong>Approved At:</strong> {{ $payroll->approved_at?->format('M d, Y h:i A') ?? 'N/A' }}
                        </div>
                    @endif
                    @if($payroll->approval_remarks)
                        <div class="col-md-6">
                            <strong>Approval Remarks:</strong><br>
                            <em>{{ $payroll->approval_remarks }}</em>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .approval-level {
        padding: 0.75rem;
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

@php
    // Check if payment approval is required by settings
    $paymentApprovalSettings = \App\Models\PayrollPaymentApprovalSettings::getSettingsForCompany(
        $payroll->company_id,
        auth()->user()->branch_id ?? null
    );
    $paymentApprovalRequired = $paymentApprovalSettings && $paymentApprovalSettings->payment_approval_required;
@endphp

@if($paymentApprovalRequired || $payroll->requires_payment_approval)
    <!-- Payment Approval Status Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="bx bx-credit-card me-2"></i>
                        Payment Approval Status
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        // Check if payment is actually fully approved
                        // Payment is fully approved only if:
                        // 1. Workflow is initialized AND
                        // 2. All required levels have been approved (no pending approvals) AND
                        // 3. No rejections exist
                        $paymentApprovals = $payroll->paymentApprovals ?? collect();
                        $hasPendingApprovals = $paymentApprovals->where('status', 'pending')->count() > 0;
                        $hasRejectedApprovals = $paymentApprovals->where('status', 'rejected')->count() > 0;
                        $allLevelsApproved = false;
                        
                        // Only check if workflow is initialized
                        if ($payroll->requires_payment_approval && $approvalSettings) {
                            // Check if all required levels have at least one approval
                            $allLevelsApproved = true;
                            for ($level = 1; $level <= $approvalSettings->payment_approval_levels; $level++) {
                                $levelApprovals = $paymentApprovals->where('approval_level', $level);
                                $hasApproved = $levelApprovals->where('status', 'approved')->count() > 0;
                                if (!$hasApproved) {
                                    $allLevelsApproved = false;
                                    break;
                                }
                            }
                        }
                        
                        // Payment is fully approved only if workflow is initialized and all levels approved
                        $isActuallyFullyApproved = $payroll->requires_payment_approval && 
                                                   !$hasPendingApprovals && 
                                                   !$hasRejectedApprovals && 
                                                   $allLevelsApproved;
                    @endphp
                    
                    @if($isActuallyFullyApproved)
                        <div class="alert alert-success mb-0">
                            <i class="bx bx-check-circle me-2"></i>
                            <strong>Payment Fully Approved</strong><br>
                            @if($payroll->payment_approved_by)
                                Approved By: {{ $payroll->paymentApprovedBy->name ?? 'N/A' }}<br>
                                Approved At: {{ $payroll->payment_approved_at?->format('M d, Y h:i A') ?? 'N/A' }}
                            @endif
                        </div>
                    @else
                        <!-- Payment Approval Levels -->
                        @php
                            $approvalSettings = $paymentApprovalSettings ?? \App\Models\PayrollPaymentApprovalSettings::getSettingsForCompany(
                                $payroll->company_id,
                                auth()->user()->branch_id ?? null
                            );
                            $currentPaymentLevel = $payroll->current_payment_approval_level ?? 1;
                            $paymentApprovals = $payroll->paymentApprovals ?? collect();
                            
                            // If workflow not initialized but approval is required, show Level 1 as current
                            if (!$payroll->requires_payment_approval && $paymentApprovalRequired) {
                                $currentPaymentLevel = 1;
                            }
                        @endphp

                        @if($approvalSettings)
                            @if(!$payroll->requires_payment_approval && $paymentApprovalRequired)
                                <div class="alert alert-info mb-3">
                                    <i class="bx bx-info-circle me-2"></i>
                                    <strong>Payment approval is required.</strong> The approval workflow will be initialized when you attempt to process payment, or you can approve now.
                                </div>
                            @endif
                            
                            <div class="mb-3">
                                <strong>Current Approval Level:</strong> {{ $currentPaymentLevel }} of {{ $approvalSettings->payment_approval_levels }}
                            </div>

                            @for($level = 1; $level <= $approvalSettings->payment_approval_levels; $level++)
                                @php
                                    $levelApprovals = $paymentApprovals->where('approval_level', $level);
                                    $levelPending = $levelApprovals->where('status', 'pending')->count();
                                    $levelApproved = $levelApprovals->where('status', 'approved')->count();
                                    $levelRejected = $levelApprovals->where('status', 'rejected')->count();
                                    $isCurrentLevel = $level == $currentPaymentLevel;
                                    $isCompleted = $levelApproved > 0 && $levelPending == 0 && $levelRejected == 0;
                                    $isRejected = $levelRejected > 0;
                                @endphp

                                <div class="approval-level {{ $isCurrentLevel ? 'current' : '' }} {{ $isCompleted ? 'completed' : '' }} {{ $isRejected ? 'rejected' : '' }}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Level {{ $level }}</strong>
                                            @if($approvalSettings->getAmountThresholdForLevel($level))
                                                <small class="text-muted">(Threshold: TZS {{ number_format($approvalSettings->getAmountThresholdForLevel($level), 2) }})</small>
                                            @endif
                                            <br>
                                            @if($isCompleted)
                                                <span class="badge bg-success">Completed</span>
                                            @elseif($isRejected)
                                                <span class="badge bg-danger">Rejected</span>
                                            @elseif($isCurrentLevel)
                                                <span class="badge bg-warning">Pending Approval</span>
                                            @else
                                                <span class="badge bg-secondary">Waiting</span>
                                            @endif
                                        </div>
                                        <div>
                                            @if($isCurrentLevel && !$isCompleted && !$isRejected)
                                                @php
                                                    // Check if current user can approve payment at this level
                                                    $canApprove = false;
                                                    $user = Auth::user();
                                                    
                                                    if ($user && $approvalSettings) {
                                                        // Check if user is super admin - super admins can always approve
                                                        if ($user->hasRole('super-admin') || $user->hasRole('Super Admin') || $user->is_admin) {
                                                            $canApprove = true;
                                                        } else {
                                                            // Check if user is assigned to this level in settings
                                                            $isAssignedInSettings = $approvalSettings->canUserApproveAtLevel($user->id, $level);
                                                            
                                                            if ($isAssignedInSettings) {
                                                                // If workflow is initialized, check for pending approval record
                                                                if ($payroll->requires_payment_approval) {
                                                                    $canApprove = \App\Models\PayrollPaymentApproval::where('payroll_id', $payroll->id)
                                                                        ->where('approver_id', $user->id)
                                                                        ->where('approval_level', $level)
                                                                        ->where('status', 'pending')
                                                                        ->exists();
                                                                } else {
                                                                    // Workflow not initialized yet, but user is assigned in settings, so they can approve
                                                                    $canApprove = true;
                                                                }
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                
                                                @if($canApprove)
                                                    <div class="d-flex gap-2">
                                                        <button type="button" class="btn btn-success btn-sm" onclick="approvePayment('{{ $payroll->hash_id }}')">
                                                            <i class="bx bx-check me-1"></i>Approve Payment
                                                        </button>
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="rejectPayment('{{ $payroll->hash_id }}')">
                                                            <i class="bx bx-x me-1"></i>Reject Payment
                                                        </button>
                                                    </div>
                                                @else
                                                    <div class="alert alert-warning mb-0 py-2 px-3">
                                                        <small>
                                                            <i class="bx bx-info-circle me-1"></i>
                                                            @if($user)
                                                                You are not assigned as an approver for Level {{ $level }}.
                                                            @else
                                                                Please log in to approve.
                                                            @endif
                                                        </small>
                                                    </div>
                                                @endif
                                            @elseif($isCompleted)
                                                <div class="alert alert-success mb-0 py-2 px-3">
                                                    <small>
                                                        <i class="bx bx-check-circle me-1"></i>
                                                        Level {{ $level }} has been completed.
                                                    </small>
                                                </div>
                                            @elseif($isRejected)
                                                <div class="alert alert-danger mb-0 py-2 px-3">
                                                    <small>
                                                        <i class="bx bx-x-circle me-1"></i>
                                                        Level {{ $level }} has been rejected.
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    @if($levelApprovals->count() > 0)
                                        <div class="mt-2">
                                            <small>
                                                <strong>Approvers:</strong>
                                                @foreach($levelApprovals as $approval)
                                                    <span class="badge {{ $approval->status === 'approved' ? 'bg-success' : ($approval->status === 'rejected' ? 'bg-danger' : 'bg-warning') }}">
                                                        {{ $approval->approver->name ?? 'N/A' }} 
                                                        ({{ ucfirst($approval->status) }})
                                                    </span>
                                                @endforeach
                                            </small>
                                        </div>
                                    @endif
                                </div>
                            @endfor
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

