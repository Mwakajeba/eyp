<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Hr\StatutoryRule;
use App\Models\Company;
use Carbon\Carbon;

class StatutoryRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder populates statutory rules for all companies
     * with predefined payroll statutory deduction rules.
     */
    public function run(): void
    {
        // Get all companies
        $companies = Company::all();

        if ($companies->isEmpty()) {
            $this->command->warn('No companies found. Please seed companies first.');
            return;
        }

        foreach ($companies as $company) {
            $companyId = $company->id;

            // 1. PAYE Rule
            $payeRule = StatutoryRule::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'rule_type' => StatutoryRule::TYPE_PAYE,
                    'rule_name' => 'PAYE',
                ],
                [
                    'description' => 'This is used for PAYE Calculation Logics',
                    'paye_brackets' => [
                        ['rate' => 0, 'threshold' => 0, 'base_amount' => 0],
                        ['rate' => 8, 'threshold' => 270000, 'base_amount' => 0],
                        ['rate' => 20, 'threshold' => 520000, 'base_amount' => 20000],
                        ['rate' => 25, 'threshold' => 760000, 'base_amount' => 68000],
                        ['rate' => 30, 'threshold' => 1000000, 'base_amount' => 128000],
                    ],
                    'paye_tax_relief' => 5.00,
                    'effective_from' => '2025-07-01',
                    'effective_to' => null,
                    'is_active' => true,
                    'apply_to_all_employees' => true,
                ]
            );

            // 2. NHIF Rule
            $nhifRule = StatutoryRule::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'rule_type' => StatutoryRule::TYPE_NHIF,
                    'rule_name' => 'NHIF',
                ],
                [
                    'description' => 'This is a Calculation Logic used for Health Contribution Fund',
                    'nhif_employee_percent' => 3.00,
                    'nhif_employer_percent' => 3.00,
                    'nhif_ceiling' => null,
                    'effective_from' => '2025-07-01',
                    'effective_to' => null,
                    'is_active' => true,
                    'apply_to_all_employees' => true,
                ]
            );

            // 3. NSSF (Pension) Rule
            $pensionRule = StatutoryRule::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'rule_type' => StatutoryRule::TYPE_PENSION,
                    'rule_name' => 'NSSF',
                ],
                [
                    'description' => 'This is a calculation Logic for National Social Security Fund',
                    'pension_employee_percent' => 10.00,
                    'pension_employer_percent' => 10.00,
                    'pension_ceiling' => null,
                    'pension_scheme_type' => 'nssf',
                    'effective_from' => '2025-07-01',
                    'effective_to' => null,
                    'is_active' => true,
                    'apply_to_all_employees' => true,
                ]
            );

            // 4. WCF Rule
            $wcfRule = StatutoryRule::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'rule_type' => StatutoryRule::TYPE_WCF,
                    'rule_name' => 'WCF',
                ],
                [
                    'description' => 'This is a Calculation logic for Workers Compensation Fund',
                    'wcf_employer_percent' => null,
                    'industry_type' => 'Technology',
                    'effective_from' => '2025-07-01',
                    'effective_to' => null,
                    'is_active' => true,
                    'apply_to_all_employees' => true,
                ]
            );

            // 5. HESLB Rule
            $heslbRule = StatutoryRule::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'rule_type' => StatutoryRule::TYPE_HESLB,
                    'rule_name' => 'HESLB',
                ],
                [
                    'description' => null,
                    'heslb_percent' => 3.50,
                    'heslb_ceiling' => 1000000.00,
                    'effective_from' => '2025-12-01',
                    'effective_to' => null,
                    'is_active' => true,
                    'apply_to_all_employees' => true,
                ]
            );

            // 6. AAR Health Insurance (NHIF variant)
            $aarHealthRule = StatutoryRule::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'rule_type' => StatutoryRule::TYPE_NHIF,
                    'rule_name' => 'AAR HEALTH INSURANCE',
                ],
                [
                    'description' => null,
                    'nhif_employee_percent' => 2.50,
                    'nhif_employer_percent' => 3.00,
                    'nhif_ceiling' => 800000.00,
                    'heslb_percent' => 3.50,
                    'effective_from' => '2025-12-01',
                    'effective_to' => '2026-11-30',
                    'is_active' => true,
                    'apply_to_all_employees' => true,
                ]
            );

            $this->command->info("Seeded statutory rules for company: {$company->name} (ID: {$companyId})");
        }

        $this->command->info('Statutory rules seeding completed successfully!');
    }
}

