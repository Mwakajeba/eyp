<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPaymentApproval extends Model
{
    use HasFactory;

    protected $table = 'payroll_payment_approvals';

    protected $fillable = [
        'payroll_id',
        'approval_level',
        'approver_id',
        'status',
        'approved_at',
        'remarks',
        'amount_at_approval'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'amount_at_approval' => 'decimal:2',
    ];

    // Relationships
    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForLevel($query, $level)
    {
        return $query->where('approval_level', $level);
    }

    public function scopeForApprover($query, $approverId)
    {
        return $query->where('approver_id', $approverId);
    }

    // Helper methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function approve($remarks = null)
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'remarks' => $remarks,
            'amount_at_approval' => $this->payroll->payrollEmployees->sum(function($employee) {
                return $employee->basic_salary + $employee->allowance + $employee->other_allowances 
                     - ($employee->paye + $employee->pension + $employee->insurance + $employee->salary_advance 
                      + $employee->loans + $employee->trade_union + $employee->sdl + $employee->wcf 
                      + $employee->heslb + $employee->other_deductions);
            })
        ]);

        return $this;
    }

    public function reject($remarks = null)
    {
        $this->update([
            'status' => 'rejected',
            'approved_at' => now(),
            'remarks' => $remarks,
            'amount_at_approval' => $this->payroll->payrollEmployees->sum(function($employee) {
                return $employee->basic_salary + $employee->allowance + $employee->other_allowances 
                     - ($employee->paye + $employee->pension + $employee->insurance + $employee->salary_advance 
                      + $employee->loans + $employee->trade_union + $employee->sdl + $employee->wcf 
                      + $employee->heslb + $employee->other_deductions);
            })
        ]);

        return $this;
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'pending' => '<span class="badge bg-warning">Pending</span>',
            'approved' => '<span class="badge bg-success">Approved</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>',
            default => '<span class="badge bg-secondary">Unknown</span>'
        };
    }
}
