<?php

namespace App\Services\Leave;

use App\Events\Leave\LeaveApproved;
use App\Events\Leave\LeaveCancelled;
use App\Events\Leave\LeaveRejected;
use App\Events\Leave\LeaveRequested;
use App\Events\Leave\LeaveReturned;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveApproval;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\LeaveSegment;
use App\Models\Hr\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LeaveRequestService
{
    public function __construct(
        protected LeavePolicyService $policyService,
        protected BalanceService $balanceService
    ) {}

    /**
     * Create a draft leave request
     */
    public function createDraft(User $user, array $data): LeaveRequest
    {
        return DB::transaction(function () use ($user, $data) {
            // Ensure employee relationship is loaded
            if (!$user->relationLoaded('employee')) {
                $user->load('employee');
            }
            
            $employeeId = $data['employee_id'] ?? $user->employee_id;
            
            if (!$employeeId) {
                throw new \Exception('Employee ID is required. Please select an employee or ensure your user account is linked to an employee.');
            }
            
            $employee = Employee::findOrFail($employeeId);
            $leaveType = LeaveType::findOrFail($data['leave_type_id']);

            // Generate request number
            $requestNumber = LeaveRequest::generateRequestNumber($employee->company_id);

            // Create leave request
            $leaveRequest = LeaveRequest::create([
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'request_number' => $requestNumber,
                'status' => 'draft',
                'reason' => $data['reason'] ?? null,
                'reliever_id' => $data['reliever_id'] ?? null,
                'policy_version' => $this->policyService->resolvePolicyVersion($employee, $leaveType, Carbon::now()),
                'meta' => [
                    'timezone' => config('app.timezone'),
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ],
            ]);

            // Create segments
            $totalDays = 0;
            foreach ($data['segments'] as $segmentData) {
                $start = Carbon::parse($segmentData['start_at']);
                $end = Carbon::parse($segmentData['end_at']);
                $granularity = $segmentData['granularity'] ?? 'full_day';

                $calculation = $this->policyService->computeDaysEquivalent(
                    $employee,
                    $leaveType,
                    $start,
                    $end,
                    $granularity
                );

                LeaveSegment::create([
                    'leave_request_id' => $leaveRequest->id,
                    'start_at' => $start,
                    'end_at' => $end,
                    'granularity' => $granularity,
                    'days_equivalent' => $calculation['days_equivalent'],
                    'calculation' => $calculation['breakdown'],
                ]);

                $totalDays += $calculation['days_equivalent'];
            }

            // Update total days
            $leaveRequest->update(['total_days' => $totalDays]);

            // Check if document is required
            if ($this->policyService->mustProvideDocument($leaveType, $totalDays)) {
                $leaveRequest->update(['requires_doc' => true]);
            }

            // Handle attachments
            if (isset($data['attachments']) && is_array($data['attachments'])) {
                $this->handleAttachments($leaveRequest, $data['attachments']);
            }

            return $leaveRequest->fresh(['segments', 'leaveType', 'employee']);
        });
    }

    /**
     * Update draft leave request
     */
    public function updateDraft(LeaveRequest $leaveRequest, array $data): LeaveRequest
    {
        if ($leaveRequest->status !== 'draft') {
            throw new \Exception('Only draft requests can be updated.');
        }

        return DB::transaction(function () use ($leaveRequest, $data) {
            $employee = $leaveRequest->employee;
            $leaveType = LeaveType::findOrFail($data['leave_type_id'] ?? $leaveRequest->leave_type_id);

            // Update basic fields
            $leaveRequest->update([
                'leave_type_id' => $leaveType->id,
                'reason' => $data['reason'] ?? $leaveRequest->reason,
                'reliever_id' => $data['reliever_id'] ?? $leaveRequest->reliever_id,
            ]);

            // Delete existing segments and create new ones
            if (isset($data['segments'])) {
                $leaveRequest->segments()->delete();

                $totalDays = 0;
                foreach ($data['segments'] as $segmentData) {
                    $start = Carbon::parse($segmentData['start_at']);
                    $end = Carbon::parse($segmentData['end_at']);
                    $granularity = $segmentData['granularity'] ?? 'full_day';

                    $calculation = $this->policyService->computeDaysEquivalent(
                        $employee,
                        $leaveType,
                        $start,
                        $end,
                        $granularity
                    );

                    LeaveSegment::create([
                        'leave_request_id' => $leaveRequest->id,
                        'start_at' => $start,
                        'end_at' => $end,
                        'granularity' => $granularity,
                        'days_equivalent' => $calculation['days_equivalent'],
                        'calculation' => $calculation['breakdown'],
                    ]);

                    $totalDays += $calculation['days_equivalent'];
                }

                $leaveRequest->update(['total_days' => $totalDays]);
            }

            // Handle attachments
            if (isset($data['attachments']) && is_array($data['attachments'])) {
                $this->handleAttachments($leaveRequest, $data['attachments']);
            }

            return $leaveRequest->fresh(['segments', 'leaveType', 'employee']);
        });
    }

    /**
     * Submit leave request for approval
     */
    public function submit(LeaveRequest $leaveRequest): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest) {
            $employee = $leaveRequest->employee;
            $leaveType = $leaveRequest->leaveType;
            $totalDays = $leaveRequest->total_days;

            // Validate eligibility
            $eligibility = $this->policyService->isEligible($employee, $leaveType);
            if (!$eligibility['eligible']) {
                throw new \Exception(implode(' ', $eligibility['errors']));
            }

            // Validate balance
            if (!$this->balanceService->hasEnoughBalance($employee, $leaveType, $totalDays)) {
                throw new \Exception('Insufficient leave balance.');
            }

            // Place hold on balance
            $this->balanceService->placeHold($employee, $leaveType, $totalDays);

            // Update status
            $leaveRequest->update([
                'status' => 'pending_manager',
                'requested_at' => Carbon::now(),
            ]);

            // Create approval chain
            $this->createApprovalChain($leaveRequest);

            // Dispatch event
            event(new LeaveRequested($leaveRequest));

            return $leaveRequest->fresh(['approvals', 'segments']);
        });
    }

    /**
     * Approve leave request
     */
    public function approve(LeaveRequest $leaveRequest, Employee $approver, string $comment = null): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $comment) {
            $approval = $this->findPendingApproval($leaveRequest, $approver);

            if (!$approval) {
                throw new \Exception('You are not authorized to approve this request.');
            }

            // Mark approval as approved
            $approval->update([
                'decision' => 'approved',
                'comment' => $comment,
                'decided_at' => Carbon::now(),
            ]);

            // Check if this is the final approval
            if ($this->isFinalApproval($leaveRequest)) {
                $employee = $leaveRequest->employee;
                $leaveType = $leaveRequest->leaveType;
                $totalDays = $leaveRequest->total_days;

                // Release hold and mark as taken
                $this->balanceService->releaseHold($employee, $leaveType, $totalDays);
                $this->balanceService->markTaken($employee, $leaveType, $totalDays);

                // Update request status
                $leaveRequest->update([
                    'status' => 'approved',
                    'decision_at' => Carbon::now(),
                    'decided_by' => $approver->id,
                ]);

                // Dispatch event
                event(new LeaveApproved($leaveRequest));
            } else {
                // Move to next approval step (HR)
                $leaveRequest->update(['status' => 'pending_hr']);
            }

            return $leaveRequest->fresh(['approvals', 'segments']);
        });
    }

    /**
     * Reject leave request
     */
    public function reject(LeaveRequest $leaveRequest, Employee $approver, string $comment): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $comment) {
            $approval = $this->findPendingApproval($leaveRequest, $approver);

            if (!$approval) {
                throw new \Exception('You are not authorized to reject this request.');
            }

            // Mark approval as rejected
            $approval->update([
                'decision' => 'rejected',
                'comment' => $comment,
                'decided_at' => Carbon::now(),
            ]);

            // Release hold
            $employee = $leaveRequest->employee;
            $leaveType = $leaveRequest->leaveType;
            $totalDays = $leaveRequest->total_days;

            $this->balanceService->releaseHold($employee, $leaveType, $totalDays);

            // Update request status
            $leaveRequest->update([
                'status' => 'rejected',
                'decision_at' => Carbon::now(),
                'decided_by' => $approver->id,
                'rejection_reason' => $comment,
            ]);

            // Dispatch event
            event(new LeaveRejected($leaveRequest));

            return $leaveRequest->fresh(['approvals', 'segments']);
        });
    }

    /**
     * Return request for editing
     */
    public function returnForEdit(LeaveRequest $leaveRequest, Employee $approver, string $comment): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $comment) {
            $approval = $this->findPendingApproval($leaveRequest, $approver);

            if (!$approval) {
                throw new \Exception('You are not authorized to return this request.');
            }

            // Mark approval as returned
            $approval->update([
                'decision' => 'returned',
                'comment' => $comment,
                'decided_at' => Carbon::now(),
            ]);

            // Release hold
            $employee = $leaveRequest->employee;
            $leaveType = $leaveRequest->leaveType;
            $totalDays = $leaveRequest->total_days;

            $this->balanceService->releaseHold($employee, $leaveType, $totalDays);

            // Update request status back to draft
            $leaveRequest->update(['status' => 'draft']);

            // Dispatch event
            event(new LeaveReturned($leaveRequest));

            return $leaveRequest->fresh(['approvals', 'segments']);
        });
    }

    /**
     * Cancel leave request
     */
    public function cancel(LeaveRequest $leaveRequest, Employee $employee, string $reason = null): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $employee, $reason) {
            if (!$leaveRequest->isCancellable()) {
                throw new \Exception('This request cannot be cancelled.');
            }

            $leaveType = $leaveRequest->leaveType;
            $totalDays = $leaveRequest->total_days;

            // If already approved, reverse taken days
            if ($leaveRequest->status === 'approved') {
                $this->balanceService->reverseTaken($employee, $leaveType, $totalDays);
            } else {
                // Release hold
                $this->balanceService->releaseHold($employee, $leaveType, $totalDays);
            }

            // Update status
            $leaveRequest->update([
                'status' => 'cancelled',
                'rejection_reason' => $reason,
            ]);

            // Dispatch event
            event(new LeaveCancelled($leaveRequest));

            return $leaveRequest->fresh();
        });
    }

    /**
     * Create approval chain for request
     */
    protected function createApprovalChain(LeaveRequest $leaveRequest): void
    {
        $employee = $leaveRequest->employee;

        // Create manager approval
        if ($employee->reports_to) {
            LeaveApproval::create([
                'leave_request_id' => $leaveRequest->id,
                'approver_id' => $employee->reports_to,
                'step' => 'manager',
                'decision' => 'pending',
            ]);
        }

        // Create HR approval - get first HR user
        $hrEmployee = Employee::where('company_id', $employee->company_id)
            ->whereHas('user', function ($q) {
                $q->whereHas('roles', function ($rq) {
                    $rq->where('name', 'HR');
                });
            })
            ->first();

        if ($hrEmployee) {
            LeaveApproval::create([
                'leave_request_id' => $leaveRequest->id,
                'approver_id' => $hrEmployee->id,
                'step' => 'hr',
                'decision' => 'pending',
            ]);
        }
    }

    /**
     * Find pending approval for approver
     */
    protected function findPendingApproval(LeaveRequest $leaveRequest, Employee $approver): ?LeaveApproval
    {
        return $leaveRequest->approvals()
            ->where('approver_id', $approver->id)
            ->where('decision', 'pending')
            ->first();
    }

    /**
     * Check if all approvals are complete
     */
    protected function isFinalApproval(LeaveRequest $leaveRequest): bool
    {
        $pendingCount = $leaveRequest->approvals()
            ->where('decision', 'pending')
            ->count();

        return $pendingCount === 0;
    }

    /**
     * Handle file attachments
     */
    protected function handleAttachments(LeaveRequest $leaveRequest, array $files): void
    {
        foreach ($files as $file) {
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $path = $file->store('leave-attachments', 'public');

                $leaveRequest->attachments()->create([
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'type' => 'document',
                    'size_kb' => round($file->getSize() / 1024, 2),
                    'mime_type' => $file->getMimeType(),
                ]);
            }
        }
    }
}

