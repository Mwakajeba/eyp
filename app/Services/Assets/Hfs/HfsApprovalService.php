<?php

namespace App\Services\Assets\Hfs;

use App\Models\Assets\HfsRequest;
use App\Models\Assets\HfsApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HfsApprovalService
{
    /**
     * Submit HFS request for approval
     */
    public function submitForApproval(HfsRequest $hfsRequest): array
    {
        DB::beginTransaction();
        try {
            // Validate before submission
            $validationService = app(HfsValidationService::class);
            $validation = $validationService->validateForApproval($hfsRequest);
            
            if (!$validation['valid']) {
                throw new \Exception("Validation failed: " . implode(', ', $validation['errors']));
            }

            // Update request status
            $hfsRequest->status = 'in_review';
            $hfsRequest->submitted_at = now();
            $hfsRequest->current_approval_level = 1; // Start with first level
            $hfsRequest->save();

            // Create approval records for each level
            $this->initializeApprovalWorkflow($hfsRequest);

            // Log submission
            $hfsService = app(HfsService::class);
            $hfsService->logActivity($hfsRequest, 'submitted', 'HFS request submitted for approval', []);

            DB::commit();

            return [
                'success' => true,
                'message' => 'HFS request submitted for approval',
                'hfs_request' => $hfsRequest,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('HFS Approval submission error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initialize approval workflow with required levels
     */
    protected function initializeApprovalWorkflow(HfsRequest $hfsRequest): void
    {
        // Define approval levels (can be made configurable)
        $approvalLevels = [
            'asset_custodian',
            'finance_manager',
            'cfo',
            'board', // Only for high-value or exceptional cases
        ];

        // Create pending approval records for first level
        // In a real system, you'd assign specific approvers based on roles/permissions
        HfsApproval::create([
            'hfs_id' => $hfsRequest->id,
            'approval_level' => 'asset_custodian',
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Approve at a specific level
     */
    public function approve(HfsRequest $hfsRequest, string $approvalLevel, int $approverId, ?string $comments = null, ?array $checksPerformed = null): array
    {
        DB::beginTransaction();
        try {
            // Find or create approval record
            $approval = HfsApproval::where('hfs_id', $hfsRequest->id)
                ->where('approval_level', $approvalLevel)
                ->first();

            if (!$approval) {
                $approval = HfsApproval::create([
                    'hfs_id' => $hfsRequest->id,
                    'approval_level' => $approvalLevel,
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                ]);
            }

            // Update approval
            $approval->status = 'approved';
            $approval->approver_id = $approverId;
            $approval->approved_at = now();
            $approval->comments = $comments;
            $approval->checks_performed = $checksPerformed;
            $approval->save();

            // Check if all required approvals are complete
            $allApproved = $this->checkAllApprovalsComplete($hfsRequest, $approvalLevel);

            if ($allApproved) {
                // All approvals complete - trigger reclassification
                $hfsService = app(HfsService::class);
                $hfsService->approveHfsRequest($hfsRequest, $approvalLevel, $approverId, $comments);
            } else {
                // Move to next approval level
                $this->moveToNextApprovalLevel($hfsRequest, $approvalLevel);
            }

            // Log approval
            $hfsService = app(HfsService::class);
            $hfsService->logActivity($hfsRequest, 'approved', "Approved at level {$approvalLevel}", [
                'approver_id' => $approverId,
                'comments' => $comments,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Approval recorded',
                'all_approved' => $allApproved,
                'hfs_request' => $hfsRequest,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('HFS Approval error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reject at a specific level
     */
    public function reject(HfsRequest $hfsRequest, string $approvalLevel, int $rejectorId, string $rejectionReason): array
    {
        DB::beginTransaction();
        try {
            // Find or create approval record
            $approval = HfsApproval::where('hfs_id', $hfsRequest->id)
                ->where('approval_level', $approvalLevel)
                ->first();

            if (!$approval) {
                $approval = HfsApproval::create([
                    'hfs_id' => $hfsRequest->id,
                    'approval_level' => $approvalLevel,
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                ]);
            }

            // Update approval
            $approval->status = 'rejected';
            $approval->approver_id = $rejectorId;
            $approval->rejected_at = now();
            $approval->rejection_reason = $rejectionReason;
            $approval->save();

            // Update request status
            $hfsRequest->status = 'rejected';
            $hfsRequest->rejected_at = now();
            $hfsRequest->rejected_by = $rejectorId;
            $hfsRequest->rejection_reason = $rejectionReason;
            $hfsRequest->save();

            // Log rejection
            $hfsService = app(HfsService::class);
            $hfsService->logActivity($hfsRequest, 'rejected', "Rejected at level {$approvalLevel}: {$rejectionReason}", [
                'rejector_id' => $rejectorId,
                'rejection_reason' => $rejectionReason,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'HFS request rejected',
                'hfs_request' => $hfsRequest,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('HFS Rejection error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Request modification at a specific level
     */
    public function requestModification(HfsRequest $hfsRequest, string $approvalLevel, int $requesterId, string $modificationRequest): array
    {
        DB::beginTransaction();
        try {
            // Find or create approval record
            $approval = HfsApproval::where('hfs_id', $hfsRequest->id)
                ->where('approval_level', $approvalLevel)
                ->first();

            if (!$approval) {
                $approval = HfsApproval::create([
                    'hfs_id' => $hfsRequest->id,
                    'approval_level' => $approvalLevel,
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                ]);
            }

            // Update approval
            $approval->status = 'requested_modification';
            $approval->approver_id = $requesterId;
            $approval->modification_request = $modificationRequest;
            $approval->save();

            // Update request status back to draft
            $hfsRequest->status = 'draft';
            $hfsRequest->save();

            // Log modification request
            $hfsService = app(HfsService::class);
            $hfsService->logActivity($hfsRequest, 'modification_requested', "Modification requested at level {$approvalLevel}: {$modificationRequest}", [
                'requester_id' => $requesterId,
                'modification_request' => $modificationRequest,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Modification requested',
                'hfs_request' => $hfsRequest,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('HFS Modification request error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if all required approvals are complete
     */
    protected function checkAllApprovalsComplete(HfsRequest $hfsRequest, string $currentLevel): bool
    {
        // For finance_manager or cfo approval, we consider it fully approved
        // This can be made configurable
        if (in_array($currentLevel, ['finance_manager', 'cfo', 'board'])) {
            return true;
        }

        // Otherwise, check if next level exists and is pending
        $nextLevel = $this->getNextApprovalLevel($currentLevel);
        if ($nextLevel) {
            $nextApproval = HfsApproval::where('hfs_id', $hfsRequest->id)
                ->where('approval_level', $nextLevel)
                ->where('status', 'approved')
                ->exists();

            return $nextApproval;
        }

        return false;
    }

    /**
     * Move to next approval level
     */
    protected function moveToNextApprovalLevel(HfsRequest $hfsRequest, string $currentLevel): void
    {
        $nextLevel = $this->getNextApprovalLevel($currentLevel);
        if ($nextLevel) {
            // Create pending approval for next level
            HfsApproval::firstOrCreate(
                [
                    'hfs_id' => $hfsRequest->id,
                    'approval_level' => $nextLevel,
                ],
                [
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                ]
            );

            // Update current approval level
            $hfsRequest->current_approval_level = $this->getApprovalLevelNumber($nextLevel);
            $hfsRequest->save();
        }
    }

    /**
     * Get next approval level
     */
    protected function getNextApprovalLevel(string $currentLevel): ?string
    {
        $levels = [
            'asset_custodian' => 'finance_manager',
            'finance_manager' => 'cfo',
            'cfo' => 'board',
            'board' => null, // Final level
        ];

        return $levels[$currentLevel] ?? null;
    }

    /**
     * Get approval level number
     */
    protected function getApprovalLevelNumber(string $level): int
    {
        $levelNumbers = [
            'asset_custodian' => 1,
            'finance_manager' => 2,
            'cfo' => 3,
            'board' => 4,
        ];

        return $levelNumbers[$level] ?? 1;
    }

    /**
     * Get pending approvals for a user
     */
    public function getPendingApprovalsForUser(int $userId, ?string $approvalLevel = null)
    {
        $query = HfsRequest::where('status', 'in_review')
            ->whereHas('approvals', function($q) use ($userId, $approvalLevel) {
                $q->where('status', 'pending');
                if ($approvalLevel) {
                    $q->where('approval_level', $approvalLevel);
                }
            });

        return $query->get();
    }
}

