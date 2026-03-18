<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeslbRepayment extends Model
{
    use LogsActivity;

    protected $table = 'hr_heslb_repayments';

    protected $fillable = [
        'company_id',
        'employee_id',
        'heslb_loan_id',
        'payroll_id',
        'payroll_employee_id',
        'amount',
        'balance_before',
        'balance_after',
        'repayment_date',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'repayment_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(HeslbLoan::class, 'heslb_loan_id');
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Payroll::class);
    }

    public function payrollEmployee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PayrollEmployee::class);
    }
}
