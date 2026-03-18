<?php

namespace Database\Seeders;

use App\Models\AccountClassGroup;
use App\Models\ChartAccount;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CardChartAccountSeeder extends Seeder
{
    /**
     * Create a chart account "Card / Payment Card" under Cash & Cash Equivalents for each company.
     * Used by bank-accounts when creating card-type accounts (e.g. Fuel Card).
     */
    public function run(): void
    {
        $companies = Company::all();
        if ($companies->isEmpty()) {
            return;
        }

        foreach ($companies as $company) {
            $group = AccountClassGroup::where('company_id', $company->id)
                ->where('name', 'Cash & Cash Equivalents (IAS 7)')
                ->first();

            if (!$group) {
                continue;
            }

            ChartAccount::firstOrCreate(
                [
                    'account_class_group_id' => $group->id,
                    'account_code' => '1007',
                ],
                [
                    'account_name' => 'Card / Payment Card',
                    'account_type' => 'parent',
                    'parent_id' => null,
                    'has_cash_flow' => true,
                    'has_equity' => false,
                    'cash_flow_category_id' => null,
                    'equity_category_id' => null,
                ]
            );
        }

        $this->command->info('Card chart account(s) seeded.');
    }
}
