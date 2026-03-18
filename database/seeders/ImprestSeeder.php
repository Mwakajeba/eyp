<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\Branch;
use App\Models\User;

class ImprestSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get the first company and branch for seeding
        $company = Company::first();
        $branch = Branch::first();
        
        if (!$company || !$branch) {
            $this->command->info('No company or branch found. Please create them first.');
            return;
        }

        // Create sample departments
        $departments = [
            ['name' => 'Human Resources', 'code' => 'HR001'],
            ['name' => 'Finance & Accounting', 'code' => 'FIN001'],
            ['name' => 'Information Technology', 'code' => 'IT001'],
            ['name' => 'Sales & Marketing', 'code' => 'SM001'],
            ['name' => 'Operations', 'code' => 'OPS001'],
            ['name' => 'Procurement', 'code' => 'PROC001'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['code' => $dept['code']],
                [
                    'name' => $dept['name'],
                    'code' => $dept['code'],
                    'description' => 'Department for ' . $dept['name'],
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                ]
            );
        }

        // Create essential chart accounts for imprest management
        $accounts = [
            // Asset Accounts
            [
                'name' => 'Staff Imprest',
                'code' => 'SIMP001',
                'account_type' => 'asset',
                'description' => 'Advances given to staff for official duties',
            ],
            [
                'name' => 'Cash in Hand',
                'code' => 'CASH001',
                'account_type' => 'asset',
                'description' => 'Cash available in hand',
            ],
            [
                'name' => 'Bank Account - Current',
                'code' => 'BANK001',
                'account_type' => 'asset',
                'description' => 'Main bank account for transactions',
            ],
            [
                'name' => 'Mobile Money Account',
                'code' => 'MMONEY001',
                'account_type' => 'asset',
                'description' => 'Mobile money wallet for transactions',
            ],

            // Expense Accounts
            [
                'name' => 'Travel & Transportation',
                'code' => 'EXP001',
                'account_type' => 'expense',
                'description' => 'Travel and transportation expenses',
            ],
            [
                'name' => 'Office Supplies',
                'code' => 'EXP002',
                'account_type' => 'expense',
                'description' => 'Office supplies and stationery',
            ],
            [
                'name' => 'Communications',
                'code' => 'EXP003',
                'account_type' => 'expense',
                'description' => 'Communication and internet expenses',
            ],
            [
                'name' => 'Meals & Entertainment',
                'code' => 'EXP004',
                'account_type' => 'expense',
                'description' => 'Business meals and entertainment',
            ],
            [
                'name' => 'Fuel & Vehicle Expenses',
                'code' => 'EXP005',
                'account_type' => 'expense',
                'description' => 'Fuel and vehicle maintenance',
            ],
            [
                'name' => 'Training & Development',
                'code' => 'EXP006',
                'account_type' => 'expense',
                'description' => 'Staff training and development costs',
            ],
            [
                'name' => 'Miscellaneous Expenses',
                'code' => 'EXP007',
                'account_type' => 'expense',
                'description' => 'Other miscellaneous expenses',
            ],
        ];

        foreach ($accounts as $account) {
            ChartAccount::firstOrCreate(
                ['account_code' => $account['code']],
                [
                    'account_name' => $account['name'],
                    'account_code' => $account['code'],
                    'account_class_group_id' => 1, // Default to first account class group
                    'has_cash_flow' => false,
                    'has_equity' => false,
                ]
            );
        }

        $this->command->info('Imprest management seeder completed successfully!');
    }
}