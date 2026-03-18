<?php

namespace App\Models\Hr;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ChartAccount;

class PayrollChartAccount extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'hr_payroll_chart_accounts';

    protected $fillable = [
        'company_id',
        'salary_advance_receivable_account_id',
        'external_loan_payable_account_id',
        'salary_payable_account_id',
        'salary_expense_account_id',
        'allowance_expense_account_id',
        'heslb_expense_account_id',
        'heslb_payable_account_id',
        'pension_expense_account_id',
        'pension_payable_account_id',
        'payee_expense_account_id',
        'payee_payable_account_id',
        'insurance_expense_account_id',
        'insurance_payable_account_id',
        'wcf_payable_account_id',
        'wcf_expense_account_id',
        'trade_union_expense_account_id',
        'trade_union_payable_account_id',
        'sdl_expense_account_id',
        'sdl_payable_account_id',
        'other_payable_account_id',
    ];

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    // Relationships to ChartAccount for each field
    public function salaryAdvanceReceivableAccount() { return $this->belongsTo(ChartAccount::class, 'salary_advance_receivable_account_id'); }
    public function externalLoanPayableAccount() { return $this->belongsTo(ChartAccount::class, 'external_loan_payable_account_id'); }
    public function salaryPayableAccount() { return $this->belongsTo(ChartAccount::class, 'salary_payable_account_id'); }
    public function salaryExpenseAccount() { return $this->belongsTo(ChartAccount::class, 'salary_expense_account_id'); }
    public function allowanceExpenseAccount() { return $this->belongsTo(ChartAccount::class, 'allowance_expense_account_id'); }
    public function heslbExpenseAccount() { return $this->belongsTo(ChartAccount::class, 'heslb_expense_account_id'); }
    public function heslbPayableAccount() { return $this->belongsTo(ChartAccount::class, 'heslb_payable_account_id'); }
    public function pensionExpenseAccount() { return $this->belongsTo(ChartAccount::class, 'pension_expense_account_id'); }
    public function pensionPayableAccount() { return $this->belongsTo(ChartAccount::class, 'pension_payable_account_id'); }
    public function payeeExpenseAccount() { return $this->belongsTo(ChartAccount::class, 'payee_expense_account_id'); }
    public function payeePayableAccount() { return $this->belongsTo(ChartAccount::class, 'payee_payable_account_id'); }
    public function insuranceExpenseAccount() { return $this->belongsTo(ChartAccount::class, 'insurance_expense_account_id'); }
    public function insurancePayableAccount() { return $this->belongsTo(ChartAccount::class, 'insurance_payable_account_id'); }
    public function wcfPayableAccount() { return $this->belongsTo(ChartAccount::class, 'wcf_payable_account_id'); }
    public function wcfExpenseAccount() { return $this->belongsTo(ChartAccount::class, 'wcf_expense_account_id'); }
    public function tradeUnionExpenseAccount() { return $this->belongsTo(ChartAccount::class, 'trade_union_expense_account_id'); }
    public function tradeUnionPayableAccount() { return $this->belongsTo(ChartAccount::class, 'trade_union_payable_account_id'); }
    public function sdlExpenseAccount() { return $this->belongsTo(ChartAccount::class, 'sdl_expense_account_id'); }
    public function sdlPayableAccount() { return $this->belongsTo(ChartAccount::class, 'sdl_payable_account_id'); }
    public function otherPayableAccount() { return $this->belongsTo(ChartAccount::class, 'other_payable_account_id'); }
}


