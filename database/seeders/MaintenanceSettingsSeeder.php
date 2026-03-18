<?php

namespace Database\Seeders;

use App\Models\Assets\MaintenanceSetting;
use App\Models\ChartAccount;
use App\Models\Company;
use Illuminate\Database\Seeder;

class MaintenanceSettingsSeeder extends Seeder
{
    /**
     * Seed Maintenance Settings: GL accounts and capitalization thresholds.
     * Configure per company (branch_id = null = company-wide).
     */
    public function run(): void
    {
        $companies = Company::all();

        if ($companies->isEmpty()) {
            $this->command->warn('MaintenanceSettingsSeeder: No companies found. Run CompanySeeder first.');
            return;
        }

        foreach ($companies as $company) {
            $this->seedForCompany($company->id);
        }

        $this->command->info('Maintenance settings seeded for ' . $companies->count() . ' company(ies).');
    }

    /**
     * Seed maintenance settings for one company.
     * Resolves GL accounts by account_code within the company's chart of accounts.
     */
    protected function seedForCompany(int $companyId): void
    {
        $branchId = null; // company-wide

        // Resolve chart account IDs by code (within company's account class groups)
        $maintenanceExpenseId = $this->getChartAccountIdByCode($companyId, '5702'); // Property Maintenance Expenses
        $wipId = $this->getChartAccountIdByCode($companyId, '1150');                 // Work-in-Progress (WIP) / AUC
        $capitalizationId = $this->getChartAccountIdByCode($companyId, '1198');   // Other Fixed Assets

        $settings = [
            [
                'setting_key' => 'maintenance_expense_account',
                'setting_name' => 'Maintenance Expense Account',
                'setting_value' => $maintenanceExpenseId !== null ? (string) $maintenanceExpenseId : null,
                'description' => 'Default GL account for routine maintenance expenses',
                'setting_type' => 'chart_account_id',
            ],
            [
                'setting_key' => 'maintenance_wip_account',
                'setting_name' => 'Maintenance Work-in-Progress Account',
                'setting_value' => $wipId !== null ? (string) $wipId : null,
                'description' => 'GL account for maintenance WIP during execution',
                'setting_type' => 'chart_account_id',
            ],
            [
                'setting_key' => 'asset_capitalization_account',
                'setting_name' => 'Asset Capitalization Account',
                'setting_value' => $capitalizationId !== null ? (string) $capitalizationId : null,
                'description' => 'GL account for capitalized maintenance costs',
                'setting_type' => 'chart_account_id',
            ],
            [
                'setting_key' => 'capitalization_threshold_amount',
                'setting_name' => 'Capitalization Threshold Amount',
                'setting_value' => '2000000',
                'description' => 'Minimum maintenance cost amount (TZS) to qualify for capitalization',
                'setting_type' => 'decimal',
            ],
            [
                'setting_key' => 'capitalization_life_extension_months',
                'setting_name' => 'Capitalization Life Extension Threshold',
                'setting_value' => '12',
                'description' => 'Minimum life extension (months) to qualify for capitalization',
                'setting_type' => 'number',
            ],
        ];

        foreach ($settings as $row) {
            MaintenanceSetting::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'setting_key' => $row['setting_key'],
                ],
                array_merge($row, [
                    'updated_by' => null,
                ])
            );
        }
    }

    /**
     * Get chart account id by account_code for the given company.
     */
    protected function getChartAccountIdByCode(int $companyId, string $accountCode): ?int
    {
        $account = ChartAccount::where('account_code', $accountCode)
            ->whereHas('accountClassGroup', fn ($q) => $q->where('company_id', $companyId))
            ->first();

        return $account?->id;
    }
}
