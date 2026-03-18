<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Hr\PayrollChartAccount;
use App\Models\Company;
use App\Models\ChartAccount;

class PayrollChartAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder populates payroll chart account settings for all companies
     * using predefined chart account codes.
     */
    public function run(): void
    {
        // Map account codes to their corresponding fields
        $accountMappings = [
            'salary_advance_receivable_account_id' => ['1103', 'Staff Advances / Receivables'],
            'salary_payable_account_id' => ['2103', 'Net Salary Payable'],
            'salary_expense_account_id' => ['5101', 'Salaries and Wages'],
            'allowance_expense_account_id' => ['5146', 'Allowances (Transport, Housing, etc.)'],
            'heslb_payable_account_id' => ['2670', 'HESLB Payable'],
            'pension_expense_account_id' => ['5226', 'Social Security Costs'],
            'pension_payable_account_id' => ['2109', 'Social Security Payable'],
            'payee_payable_account_id' => ['2125', 'PAYE Payable'],
            'insurance_expense_account_id' => ['5466', 'NHIF / Insurance Expenses'],
            'insurance_payable_account_id' => ['2123', 'NHIF Payable'],
            'wcf_expense_account_id' => ['5122', 'WCF Contribution Cost'],
            'wcf_payable_account_id' => ['2146', 'WCF Payable'],
            'sdl_expense_account_id' => ['5124', 'SDL Expenses'],
            'sdl_payable_account_id' => ['2120', 'SDL Payable'],
            'trade_union_payable_account_id' => ['2333', 'Trade Union Payable'],
            'other_payable_account_id' => ['2102', 'Other Accrued Liabilities'],
        ];

        // Find all chart accounts by their codes
        $accountIds = [];
        $warnings = [];

        foreach ($accountMappings as $field => $accountInfo) {
            [$accountCode, $accountName] = $accountInfo;

            $account = ChartAccount::where('account_code', $accountCode)->first();

            if ($account) {
                $accountIds[$field] = $account->id;
                $this->command->info("Found account: {$accountCode} - {$accountName} (ID: {$account->id})");
            } else {
                $warnings[] = "Account not found: {$accountCode} - {$accountName}";
                $this->command->warn("Account not found: {$accountCode} - {$accountName}");
            }
        }

        // Get all companies
        $companies = Company::all();

        if ($companies->isEmpty()) {
            $this->command->warn('No companies found. Please seed companies first.');
            return;
        }

        $updateData = [];
        foreach ($companies as $company) {
            // Get or create payroll chart account settings for this company
            $payrollChartAccount = PayrollChartAccount::firstOrCreate(
                ['company_id' => $company->id],
                []
            );

            // Prepare update data - only include accounts that were found
            $updateData = [];
            foreach ($accountIds as $field => $accountId) {
                // Only update if the field is not already set, or force update by removing this condition
                $updateData[$field] = $accountId;
            }

            // Update payroll chart account settings
            $payrollChartAccount->update($updateData);
                
            $message = "Updated payroll chart account settings for company: ";
            $message .= "{$company->name} (ID: {$company->id})";
            $this->command->info($message);
        }

        $this->command->info('Payroll chart account seeding completed successfully!');

        if (!empty($warnings)) {
            $this->command->warn("\nWarnings:");
            foreach ($warnings as $warning) {
                $this->command->warn("  - {$warning}");
            }
        }
    }
}
