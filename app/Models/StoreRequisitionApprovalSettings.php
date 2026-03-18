<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class StoreRequisitionApprovalSettings extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'store_req_approval_settings';

    protected $fillable = [
        'company_id',
        'level_1_enabled',
        'level_1_user_id',
        'level_1_role_id',
        'level_2_enabled',
        'level_2_user_id',
        'level_2_role_id',
        'level_3_enabled',
        'level_3_user_id',
        'level_3_role_id',
        'level_4_enabled',
        'level_4_user_id',
        'level_4_role_id',
        'level_5_enabled',
        'level_5_user_id',
        'level_5_role_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'level_1_enabled' => 'boolean',
        'level_2_enabled' => 'boolean',
        'level_3_enabled' => 'boolean',
        'level_4_enabled' => 'boolean',
        'level_5_enabled' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function level1User(): BelongsTo
    {
        return $this->belongsTo(User::class, 'level_1_user_id');
    }

    public function level1Role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'level_1_role_id');
    }

    public function level2User(): BelongsTo
    {
        return $this->belongsTo(User::class, 'level_2_user_id');
    }

    public function level2Role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'level_2_role_id');
    }

    public function level3User(): BelongsTo
    {
        return $this->belongsTo(User::class, 'level_3_user_id');
    }

    public function level3Role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'level_3_role_id');
    }

    public function level4User(): BelongsTo
    {
        return $this->belongsTo(User::class, 'level_4_user_id');
    }

    public function level4Role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'level_4_role_id');
    }

    public function level5User(): BelongsTo
    {
        return $this->belongsTo(User::class, 'level_5_user_id');
    }

    public function level5Role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'level_5_role_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getMaxApprovalLevel(): int
    {
        for ($level = 5; $level >= 1; $level--) {
            if ($this->{"level_{$level}_enabled"}) {
                return $level;
            }
        }
        return 0;
    }

    public function getApproversForLevel(int $level): array
    {
        if ($level < 1 || $level > 5 || !$this->{"level_{$level}_enabled"}) {
            return [];
        }

        return $this->{"level_{$level}_approvers"} ?? [];
    }

    public function hasApprovalRequired(): bool
    {
        return $this->approval_required && $this->getMaxApprovalLevel() > 0;
    }

    public function isLevelEnabled(int $level): bool
    {
        if ($level < 1 || $level > 5) {
            return false;
        }
        
        return $this->{"level_{$level}_enabled"};
    }

    public function getAmountLimitForLevel(int $level): ?float
    {
        if ($level < 1 || $level > 5 || !$this->{"level_{$level}_enabled"}) {
            return null;
        }

        return $this->{"level_{$level}_amount_limit"};
    }
}
