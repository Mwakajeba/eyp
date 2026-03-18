<?php

namespace App\Models\Investment;

use App\Models\Company;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentProposalApprovalSetting extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'investment_proposal_approval_settings';

    protected $fillable = [
        'company_id',
        'approval_levels',
        'auto_approval_limit',
        'escalation_time',
        'require_approval_for_all',
        'level1_approval_type',
        'level1_approvers',
        'level2_approval_type',
        'level2_approvers',
        'level3_approval_type',
        'level3_approvers',
        'level4_approval_type',
        'level4_approvers',
        'level5_approval_type',
        'level5_approvers',
    ];

    protected $casts = [
        'auto_approval_limit' => 'decimal:2',
        'require_approval_for_all' => 'boolean',
        'level1_approvers' => 'array',
        'level2_approvers' => 'array',
        'level3_approvers' => 'array',
        'level4_approvers' => 'array',
        'level5_approvers' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the required approval level for a given amount.
     */
    public function getRequiredApprovalLevel($amount): int
    {
        if (!$this->require_approval_for_all) {
            // If auto approval limit is set and amount is below it, no approval needed
            if ($this->auto_approval_limit && $amount <= $this->auto_approval_limit) {
                return 0;
            }
        }

        return (int) $this->approval_levels;
    }

    /**
     * Check if a user can approve at a specific level.
     */
    public function canUserApproveAtLevel(User $user, int $level): bool
    {
        $approvalType = $this->{"level{$level}_approval_type"};
        $approvers = $this->{"level{$level}_approvers"} ?? [];

        if (empty($approvers)) {
            return false;
        }

        if ($approvalType === 'role') {
            // Extract role names from the approvers array
            $roleNames = array_map(function($item) {
                if (strpos($item, 'role_') === 0) {
                    return substr($item, 5); // Remove 'role_' prefix
                }
                return $item;
            }, $approvers);
            
            return $user->hasAnyRole($roleNames);
        } elseif ($approvalType === 'user') {
            // Extract user IDs from the approvers array
            $userIds = array_map(function($item) {
                if (strpos($item, 'user_') === 0) {
                    return (int) substr($item, 5); // Remove 'user_' prefix
                }
                return (int) $item;
            }, $approvers);
            
            return in_array($user->id, $userIds, true);
        }

        return false;
    }

    /**
     * Get approval configuration for a specific level.
     */
    public function getLevelConfig(int $level): ?array
    {
        $approvalType = $this->{"level{$level}_approval_type"};
        $approvers = $this->{"level{$level}_approvers"} ?? [];

        if (!$approvalType || empty($approvers)) {
            return null;
        }

        return [
            'type' => $approvalType,
            'approvers' => $approvers,
        ];
    }
}

