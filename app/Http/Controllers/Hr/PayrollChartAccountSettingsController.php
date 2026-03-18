<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\PayrollChartAccount;
use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollChartAccountSettingsController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $settings = PayrollChartAccount::firstOrCreate(['company_id' => $companyId]);

        // Load available chart accounts for selects
        $chartAccounts = ChartAccount::orderBy('account_code')->get(['id', 'account_code', 'account_name']);

        return view('hr-payroll.payroll-settings.chart-accounts', compact('settings', 'chartAccounts'));
    }

    public function update(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $settings = PayrollChartAccount::firstOrCreate(['company_id' => $companyId]);

        $request->validate([
            'salary_advance_receivable_account_id' => 'nullable|exists:chart_accounts,id',
            'external_loan_payable_account_id' => 'nullable|exists:chart_accounts,id',
            'salary_payable_account_id' => 'nullable|exists:chart_accounts,id',
            'salary_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'allowance_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'heslb_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'heslb_payable_account_id' => 'nullable|exists:chart_accounts,id',
            'pension_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'pension_payable_account_id' => 'nullable|exists:chart_accounts,id',
            'payee_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'payee_payable_account_id' => 'nullable|exists:chart_accounts,id',
            'insurance_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'insurance_payable_account_id' => 'nullable|exists:chart_accounts,id',
            'wcf_payable_account_id' => 'nullable|exists:chart_accounts,id',
            'wcf_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'trade_union_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'trade_union_payable_account_id' => 'nullable|exists:chart_accounts,id',
            'sdl_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'sdl_payable_account_id' => 'nullable|exists:chart_accounts,id',
            'other_payable_account_id' => 'nullable|exists:chart_accounts,id',
        ]);

        $settings->update($request->only([
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
        ]));

        return redirect()->route('hr.payroll.chart-accounts.index')
            ->with('success', 'Payroll chart account settings saved successfully.');
    }
}


