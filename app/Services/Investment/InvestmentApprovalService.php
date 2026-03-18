<?php

namespace App\Services\Investment;

use App\Models\Investment\InvestmentProposal;
use App\Models\Investment\InvestmentApproval;
use App\Models\Investment\InvestmentProposalApprovalSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class InvestmentApprovalService
{
    /**
     * Initialize approval workflow for a proposal
     */
    public function initializeApprovalWorkflow(InvestmentProposal $proposal): void
    {
        // Get approval settings from database
        $settings = InvestmentProposalApprovalSetting::where('company_id', $proposal->company_id)->first();
        
        // If no settings exist, use default fallback
        if (!$settings) {
            Log::warning('No approval settings found for company', ['company_id' => $proposal->company_id]);
            $settings = $this->getDefaultSettings($proposal->company_id);
        }

        // Check if approval is required
        $requiredLevels = $settings->getRequiredApprovalLevel($proposal->proposed_amount);
        if ($requiredLevels === 0) {
            // No approval required, mark as approved
            $proposal->status = 'APPROVED';
            $proposal->is_fully_approved = true;
            $proposal->save();
            return;
        }

        // Initialize approval records for each level
        for ($level = 1; $level <= $requiredLevels; $level++) {
            $levelConfig = $settings->getLevelConfig($level);
            
            if (!$levelConfig) {
                Log::warning("No configuration found for approval level {$level}", [
                    'proposal_id' => $proposal->id,
                    'company_id' => $proposal->company_id,
                ]);
                continue;
            }

            if ($levelConfig['type'] === 'role') {
                // Extract role names from approvers array
                $roleNames = [];
                foreach ($levelConfig['approvers'] as $approver) {
                    if (strpos($approver, 'role_') === 0) {
                        $roleNames[] = substr($approver, 5); // Remove 'role_' prefix
                    }
                }

                // Get all users with these roles
                foreach ($roleNames as $roleName) {
                    $role = Role::where('name', $roleName)->first();
                    if ($role) {
                        $users = $role->users()->where('company_id', $proposal->company_id)->get();
                        foreach ($users as $user) {
                            InvestmentApproval::create([
                                'proposal_id' => $proposal->id,
                                'approval_level' => $level,
                                'approver_type' => 'role',
                                'approver_id' => $user->id,
                                'approver_name' => $roleName,
                                'status' => 'pending',
                                'created_by' => auth()->id(),
                            ]);
                        }
                    }
                }
            } elseif ($levelConfig['type'] === 'user') {
                // Extract user IDs from approvers array
                $userIds = [];
                foreach ($levelConfig['approvers'] as $approver) {
                    if (strpos($approver, 'user_') === 0) {
                        $userIds[] = (int) substr($approver, 5); // Remove 'user_' prefix
                    }
                }

                // Create approval records for each user
                foreach ($userIds as $userId) {
                    $user = User::find($userId);
                    if ($user && $user->company_id === $proposal->company_id) {
                        InvestmentApproval::create([
                            'proposal_id' => $proposal->id,
                            'approval_level' => $level,
                            'approver_type' => 'user',
                            'approver_id' => $user->id,
                            'approver_name' => $user->name,
                            'status' => 'pending',
                            'created_by' => auth()->id(),
                        ]);
                    }
                }
            }
        }

        // Set initial approval level
        $proposal->current_approval_level = 1;
        $proposal->status = 'SUBMITTED';
        $proposal->save();
    }

    /**
     * Get default approval settings (fallback when no settings exist)
     */
    protected function getDefaultSettings(int $companyId): InvestmentProposalApprovalSetting
    {
        // Create a temporary settings object with defaults
        $settings = new InvestmentProposalApprovalSetting();
        $settings->company_id = $companyId;
        $settings->approval_levels = 3;
        $settings->require_approval_for_all = true;
        $settings->level1_approval_type = 'role';
        $settings->level1_approvers = ['role_treasury_manager'];
        $settings->level2_approval_type = 'role';
        $settings->level2_approvers = ['role_cfo'];
        $settings->level3_approval_type = 'role';
        $settings->level3_approvers = ['role_ceo'];
        
        return $settings;
    }

    /**
     * Approve a proposal at a specific level
     */
    public function approve(InvestmentProposal $proposal, int $approvalLevel, User $approver, ?string $comments = null): array
    {
        DB::beginTransaction();
        try {
            // Find the approval record
            $approval = InvestmentApproval::where('proposal_id', $proposal->id)
                ->where('approval_level', $approvalLevel)
                ->where('approver_id', $approver->id)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                throw new \Exception('Approval record not found or already processed');
            }

            // Update approval
            $approval->status = 'approved';
            $approval->comments = $comments;
            $approval->approved_at = now();
            $approval->save();

            // Check if all approvals at this level are complete
            $allLevelApprovals = InvestmentApproval::where('proposal_id', $proposal->id)
                ->where('approval_level', $approvalLevel)
                ->get();

            $allApproved = $allLevelApprovals->every(fn($a) => $a->status === 'approved');

            if ($allApproved) {
                // Move to next level or mark as fully approved
                $nextLevel = $approvalLevel + 1;
                $hasNextLevel = InvestmentApproval::where('proposal_id', $proposal->id)
                    ->where('approval_level', $nextLevel)
                    ->exists();

                if ($hasNextLevel) {
                    $proposal->current_approval_level = $nextLevel;
                    $proposal->status = 'IN_REVIEW';
                } else {
                    // All levels approved
                    $proposal->status = 'APPROVED';
                    $proposal->is_fully_approved = true;
                    $proposal->approved_by = $approver->id;
                    $proposal->approved_at = now();
                }
                $proposal->save();
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Proposal approved',
                'proposal' => $proposal->fresh(),
                'approval' => $approval,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve proposal', [
                'proposal_id' => $proposal->id,
                'approver_id' => $approver->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reject a proposal
     */
    public function reject(InvestmentProposal $proposal, int $approvalLevel, User $rejector, string $reason): array
    {
        DB::beginTransaction();
        try {
            // Find the approval record
            $approval = InvestmentApproval::where('proposal_id', $proposal->id)
                ->where('approval_level', $approvalLevel)
                ->where('approver_id', $rejector->id)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                throw new \Exception('Approval record not found or already processed');
            }

            // Update approval
            $approval->status = 'rejected';
            $approval->rejection_reason = $reason;
            $approval->rejected_at = now();
            $approval->save();

            // Update proposal status
            $proposal->status = 'REJECTED';
            $proposal->rejected_by = $rejector->id;
            $proposal->rejected_at = now();
            $proposal->rejection_reason = $reason;
            $proposal->save();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Proposal rejected',
                'proposal' => $proposal->fresh(),
                'approval' => $approval,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reject proposal', [
                'proposal_id' => $proposal->id,
                'rejector_id' => $rejector->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Request additional information
     */
    public function requestInfo(InvestmentProposal $proposal, int $approvalLevel, User $requester, string $infoRequest): array
    {
        DB::beginTransaction();
        try {
            $approval = InvestmentApproval::where('proposal_id', $proposal->id)
                ->where('approval_level', $approvalLevel)
                ->where('approver_id', $requester->id)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                throw new \Exception('Approval record not found or already processed');
            }

            $approval->status = 'requested_info';
            $approval->info_request = $infoRequest;
            $approval->info_requested_at = now();
            $approval->save();

            // Proposal status remains IN_REVIEW but with info request
            $proposal->status = 'IN_REVIEW';
            $proposal->save();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Information requested',
                'proposal' => $proposal->fresh(),
                'approval' => $approval,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to request info for proposal', [
                'proposal_id' => $proposal->id,
                'requester_id' => $requester->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if user can approve at current level
     */
    public function canUserApprove(InvestmentProposal $proposal, User $user): bool
    {
        // First check if user has a pending approval record
        $hasPendingApproval = InvestmentApproval::where('proposal_id', $proposal->id)
            ->where('approval_level', $proposal->current_approval_level)
            ->where('approver_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingApproval) {
            return true;
        }

        // Also check against settings to see if user has permission at this level
        $settings = InvestmentProposalApprovalSetting::where('company_id', $proposal->company_id)->first();
        if ($settings) {
            return $settings->canUserApproveAtLevel($user, $proposal->current_approval_level);
        }

        return false;
    }
}

