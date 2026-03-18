<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Hr\Employee;
use App\Models\User;
use App\Models\Company;
use App\Models\BankAccount;
use App\Traits\LogsActivity;
use Vinkla\Hashids\Facades\Hashids;

class Payroll extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'reference',
        'year',
        'month',
        'total_salary',
        'total_allowance',
        'total_nhif_employee',
        'total_nhif_employer',
        'total_pension_employee',
        'total_pension_employer',
        'total_wcf',
        'total_sdl',
        'total_heslb',
        'total_trade_union',
        'total_payee',
        'total_salary_advance_paid',
        'total_external_loan_paid',
        'status',
        'notes',
        'created_by',
        'company_id',
        'payroll_calendar_id',
        'pay_group_id',
        // Approval workflow fields
        'requires_approval',
        'current_approval_level',
        'is_fully_approved',
        'approved_by',
        'approved_at',
        'approval_remarks',
        'rejected_by',
        'rejected_at',
        'rejection_remarks',
        'paid_by',
        'paid_at',
        'payment_remarks',
        'cancelled_at',
        'cancellation_reason',
        // Journal and payment tracking fields
        'journal_reference',
        'payment_status',
        'payment_reference', 
        'payment_journal_reference',
        'payment_id',
        // Locking & Audit fields
        'is_locked',
        'locked_at',
        'locked_by',
        'lock_reason',
        'can_be_reversed',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
        // Payment approval workflow fields
        'requires_payment_approval',
        'current_payment_approval_level',
        'is_payment_fully_approved',
        'payment_approved_at',
        'payment_approved_by',
        'payment_approval_remarks',
        'payment_rejected_by',
        'payment_rejected_at',
        'payment_rejection_remarks',
        // Payment submission tracking
        'payment_submitted_by',
        'payment_submitted_at',
        'payment_bank_account_id',
        'payment_chart_account_id',
        'payment_date',
    ];

    protected $casts = [
        'year' => 'integer',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
        'can_be_reversed' => 'boolean',
        'reversed_at' => 'datetime',
        'month' => 'integer',
        'total_salary' => 'decimal:2',
        'total_allowance' => 'decimal:2',
        'total_nhif_employee' => 'decimal:2',
        'total_nhif_employer' => 'decimal:2',
        'total_pension_employee' => 'decimal:2',
        'total_pension_employer' => 'decimal:2',
        'total_wcf' => 'decimal:2',
        'total_sdl' => 'decimal:2',
        'total_heslb' => 'decimal:2',
        'total_trade_union' => 'decimal:2',
        'total_payee' => 'decimal:2',
        'total_salary_advance_paid' => 'decimal:2',
        'total_external_loan_paid' => 'decimal:2',
        // Approval workflow casts
        'requires_approval' => 'boolean',
        'current_approval_level' => 'integer',
        'is_fully_approved' => 'boolean',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
        'can_be_reversed' => 'boolean',
        'reversed_at' => 'datetime',
        // Payment approval workflow casts
        'requires_payment_approval' => 'boolean',
        'current_payment_approval_level' => 'integer',
        'is_payment_fully_approved' => 'boolean',
        'payment_approved_at' => 'datetime',
        'payment_rejected_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function payrollEmployees(): HasMany
    {
        return $this->hasMany(PayrollEmployee::class);
    }

    /**
     * Alias for payrollEmployees() for route model binding
     * This allows Laravel to resolve nested route parameters like payrolls/{payroll}/slip/{employee}
     */
    public function employees(): HasMany
    {
        return $this->payrollEmployees();
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function payrollCalendar(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Hr\PayrollCalendar::class, 'payroll_calendar_id');
    }

    public function payGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Hr\PayGroup::class, 'pay_group_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PayrollAuditLog::class);
    }

    /**
     * Check if payroll can be edited (not locked and not processed/paid)
     */
    public function canBeEdited(): bool
    {
        // Cannot edit if locked
        if ($this->is_locked) {
            return false;
        }

        // Cannot edit if already paid
        if ($this->status === 'paid' || $this->payment_status === 'paid') {
            return false;
        }

        // Cannot edit if reversed
        if ($this->reversed_at) {
            return false;
        }

        return true;
    }

    /**
     * Check if payroll can be locked
     */
    public function canBeLocked(): bool
    {
        return !$this->is_locked && in_array($this->status, ['completed', 'approved']);
    }

    /**
     * Check if payroll can be reversed
     */
    public function canBeReversed(): bool
    {
        // Must allow reversal
        if (!$this->can_be_reversed) {
            return false;
        }

        // Cannot reverse if already reversed
        if ($this->reversed_at) {
            return false;
        }

        // Cannot reverse if locked
        if ($this->is_locked) {
            return false;
        }

        // Can reverse if processed, approved, or paid
        return in_array($this->status, ['completed', 'approved', 'paid']);
    }

    /**
     * Lock the payroll
     */
    public function lock($userId = null, $reason = null): bool
    {
        if (!$this->canBeLocked()) {
            return false;
        }

        $this->update([
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => $userId ?? auth()->id(),
            'lock_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Unlock the payroll (requires permission)
     */
    public function unlock($userId = null, $reason = null): bool
    {
        if (!$this->is_locked) {
            return false;
        }

        $this->update([
            'is_locked' => false,
            'locked_at' => null,
            'locked_by' => null,
            'lock_reason' => null,
        ]);

        // Log the unlock action
        $this->logAudit('unlocked', null, true, false, 
            'Payroll unlocked', $reason, $userId);

        return true;
    }

    /**
     * Reverse the payroll
     */
    public function reverse($userId = null, $reason = null): bool
    {
        if (!$this->canBeReversed()) {
            return false;
        }

        $oldStatus = $this->status;
        
        $this->update([
            'reversed_at' => now(),
            'reversed_by' => $userId ?? auth()->id(),
            'reversal_reason' => $reason,
            'status' => 'cancelled',
        ]);

        // Log the reversal
        $this->logAudit('reversed', null, $oldStatus, 'cancelled', 
            "Payroll reversed. Previous status: {$oldStatus}", 
            $reason, $userId);

        return true;
    }

    /**
     * Log audit trail
     */
    public function logAudit(
        string $action,
        ?string $fieldName = null,
        $oldValue = null,
        $newValue = null,
        ?string $description = null,
        ?string $remarks = null,
        $userId = null,
        array $metadata = []
    ): PayrollAuditLog {
        return PayrollAuditLog::create([
            'payroll_id' => $this->id,
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'field_name' => $fieldName,
            'old_value' => $oldValue !== null ? json_encode($oldValue) : null,
            'new_value' => $newValue !== null ? json_encode($newValue) : null,
            'description' => $description,
            'remarks' => $remarks,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PayrollApproval::class);
    }

    public function paymentApprovals(): HasMany
    {
        return $this->hasMany(PayrollPaymentApproval::class);
    }

    public function approvalSettings()
    {
        return PayrollApprovalSettings::where('company_id', $this->company_id)
            ->where('branch_id', auth()->user()->branch_id)
            ->first();
    }

    public function paymentApprovalSettings()
    {
        return PayrollPaymentApprovalSettings::getSettingsForCompany(
            $this->company_id,
            auth()->user()->branch_id ?? null
        );
    }

    public function paymentApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_approved_by');
    }

    public function paymentRejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_rejected_by');
    }

    public function paymentSubmittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_submitted_by');
    }

    public function paymentBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'payment_bank_account_id');
    }

    /**
     * Accessors
     */
    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }

    public function getReferenceAttribute($value): string
    {
        if ($value) {
            return $value;
        }
        
        // Generate reference if not set
        return "PAY-{$this->year}-{$this->month}-" . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getTotalGrossPayAttribute(): float
    {
        return ($this->total_salary ?? 0) + ($this->total_allowance ?? 0);
    }

    public function getTotalDeductionsAttribute(): float
    {
        // WCF and SDL are employer contributions, not employee deductions
        return ($this->total_nhif_employee ?? 0) + 
               ($this->total_pension_employee ?? 0) + 
               ($this->total_payee ?? 0) + 
               ($this->total_salary_advance_paid ?? 0) + 
               ($this->total_external_loan_paid ?? 0) +
               ($this->total_trade_union ?? 0) +
               ($this->total_heslb ?? 0);
    }

    public function getTotalEmployerContributionsAttribute(): float
    {
        return ($this->total_nhif_employer ?? 0) + 
               ($this->total_pension_employer ?? 0) + 
               ($this->total_wcf ?? 0) +
               ($this->total_sdl ?? 0);
    }

    public function getNetPayAttribute(): float
    {
        return $this->total_gross_pay - $this->total_deductions;
    }

    public function getFormattedPeriodAttribute(): string
    {
        return $this->month_name . ' ' . $this->year;
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForPeriod($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Approval workflow methods
     */
    public function requiresApproval()
    {
        $settings = $this->approvalSettings();
        if (!$settings || !$settings->approval_required) {
            return false;
        }

        $requiredApprovals = $settings->getRequiredApprovalsForAmount($this->total_gross_pay);
        return count($requiredApprovals) > 0;
    }

    public function createApprovalRequests()
    {
        $settings = $this->approvalSettings();
        if (!$settings) {
            return;
        }

        $requiredApprovals = $settings->getRequiredApprovalsForAmount($this->total_gross_pay);
        
        foreach ($requiredApprovals as $approval) {
            foreach ($approval['approvers'] as $approverId) {
                PayrollApproval::create([
                    'payroll_id' => $this->id,
                    'approval_level' => $approval['level'],
                    'approver_id' => $approverId,
                    'status' => 'pending'
                ]);
            }
        }

        $this->update([
            'requires_approval' => true,
            'status' => 'processing',
            'current_approval_level' => 1
        ]);
    }

    public function getCurrentApprovalLevel()
    {
        if (!$this->requires_approval) {
            return 0;
        }

        // Find the lowest level that still has pending approvals
        $pendingLevel = $this->approvals()
            ->where('status', 'pending')
            ->min('approval_level');

        return $pendingLevel ?? $this->current_approval_level;
    }

    public function isFullyApproved()
    {
        if (!$this->requires_approval) {
            return true;
        }

        $pendingCount = $this->approvals()->where('status', 'pending')->count();
        return $pendingCount === 0 && !$this->hasRejectedApprovals();
    }

    public function hasRejectedApprovals()
    {
        return $this->approvals()->where('status', 'rejected')->exists();
    }

    public function canUserApproveAtLevel($user, $level)
    {
        $settings = $this->approvalSettings();
        if (!$settings) {
            return false;
        }

        return $settings->canUserApproveAtLevel($user->id, $level);
    }

    public function canBePaid()
    {
        return $this->status === 'processing' && 
               $this->isFullyApproved() && 
               !$this->hasRejectedApprovals();
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['draft', 'processing']);
    }

    public function canBeDeleted()
    {
        return in_array($this->status, ['draft', 'cancelled']);
    }

    public function getPendingApprovals()
    {
        return $this->approvals()
            ->where('status', 'pending')
            ->with('approver')
            ->orderBy('approval_level')
            ->get();
    }

    public function getCompletedApprovals()
    {
        return $this->approvals()
            ->whereIn('status', ['approved', 'rejected'])
            ->with('approver')
            ->orderBy('approval_level')
            ->orderBy('approved_at')
            ->get();
    }

    public function getRequiredApprovalLevels()
    {
        $settings = $this->approvalSettings();
        if (!$settings) {
            return [];
        }

        return $settings->getRequiredApprovalsForAmount($this->total_gross_pay);
    }



    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason
        ]);
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'draft' => '<span class="badge bg-secondary">Draft</span>',
            'processing' => '<span class="badge bg-warning">Processing</span>',
            'paid' => '<span class="badge bg-success">Paid</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
            default => '<span class="badge bg-light text-dark">' . ucfirst($this->status) . '</span>'
        };
    }

    /**
     * Get the hash ID for the payroll
     *
     * @return string
     */
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the route key for the model
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'hash_id';
    }

    /**
     * Resolve the model from the route parameter
     *
     * @param string $value
     * @param string|null $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Try to decode the hash ID first
        $decoded = Hashids::decode($value);
        
        if (!empty($decoded) && isset($decoded[0])) {
            $payroll = static::where('id', $decoded[0])->first();
            if ($payroll) {
                return $payroll;
            }
        }
        
        // Fallback to regular ID lookup (in case it's a numeric ID)
        if (is_numeric($value)) {
            return static::where('id', $value)->first();
        }
        
        // If neither hash ID nor numeric ID works, return null (will trigger 404)
        return null;
    }

    /**
     * Get the route key for the model
     *
     * @return string
     */
    public function getRouteKey()
    {
        return $this->hash_id;
    }
}