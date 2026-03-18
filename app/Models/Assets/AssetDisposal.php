<?php

namespace App\Models\Assets;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetDisposal extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'asset_id',
        'customer_id',
        'disposal_number',
        'disposal_type',
        'disposal_reason_code_id',
        'disposal_reason',
        'proposed_disposal_date',
        'actual_disposal_date',
        'net_book_value',
        'accumulated_depreciation',
        'accumulated_impairment',
        'asset_cost',
        'disposal_proceeds',
        'amount_paid',
        'bank_account_id',
        'fair_value',
        'gain_loss',
        'vat_type',
        'vat_rate',
        'vat_amount',
        'withholding_tax_enabled',
        'withholding_tax_rate',
        'withholding_tax_type',
        'withholding_tax',
        'buyer_name',
        'buyer_contact',
        'buyer_address',
        'invoice_number',
        'receipt_number',
        'insurance_recovery_amount',
        'insurance_claim_number',
        'insurance_recovery_date',
        'revaluation_reserve_transferred',
        'reserve_transferred_to_retained_earnings',
        'is_partial_disposal',
        'partial_disposal_percentage',
        'partial_disposal_description',
        'status',
        'current_approval_level',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'journal_id',
        'gl_posted',
        'gl_posted_at',
        'accumulated_depreciation_account_id',
        'disposal_proceeds_account_id',
        'gain_loss_account_id',
        'donation_expense_account_id',
        'loss_account_id',
        'insurance_recovery_account_id',
        'retained_earnings_account_id',
        'vat_account_id',
        'valuation_report_path',
        'attachments',
        'notes',
        'initiated_by',
        'initiated_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'proposed_disposal_date' => 'date',
        'actual_disposal_date' => 'date',
        'insurance_recovery_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'gl_posted_at' => 'datetime',
        'initiated_at' => 'datetime',
        'net_book_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'accumulated_impairment' => 'decimal:2',
        'asset_cost' => 'decimal:2',
        'disposal_proceeds' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'fair_value' => 'decimal:2',
        'gain_loss' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'withholding_tax_rate' => 'decimal:2',
        'withholding_tax' => 'decimal:2',
        'withholding_tax_enabled' => 'boolean',
        'insurance_recovery_amount' => 'decimal:2',
        'revaluation_reserve_transferred' => 'decimal:2',
        'partial_disposal_percentage' => 'decimal:2',
        'gl_posted' => 'boolean',
        'is_partial_disposal' => 'boolean',
        'reserve_transferred_to_retained_earnings' => 'boolean',
        'attachments' => 'array',
    ];

    // Relationships
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function reasonCode()
    {
        return $this->belongsTo(DisposalReasonCode::class, 'disposal_reason_code_id');
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(\App\Models\BankAccount::class);
    }

    public function journal()
    {
        return $this->belongsTo(\App\Models\Journal::class);
    }

    public function approvals()
    {
        return $this->hasMany(DisposalApproval::class, 'disposal_id');
    }

    public function initiatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'initiated_by');
    }

    public function submittedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'submitted_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'rejected_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // Helper Methods
    public function canBeApproved()
    {
        return in_array($this->status, ['draft', 'pending_approval']);
    }

    public function canBePosted()
    {
        return $this->status === 'approved' && !$this->gl_posted;
    }

    public function canBeCompleted()
    {
        return $this->status === 'approved' && $this->gl_posted;
    }

    public function getGainLossAttribute($value)
    {
        // Calculate if not set
        if ($value === null || $value == 0) {
            $proceeds = $this->disposal_proceeds ?? $this->fair_value ?? 0;
            return $proceeds - $this->net_book_value;
        }
        return $value;
    }
}
