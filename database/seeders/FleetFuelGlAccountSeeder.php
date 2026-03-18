<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartAccount;
use App\Models\AccountClassGroup;

class FleetFuelGlAccountSeeder extends Seeder
{
    /**
     * Create or update GL accounts for fleet fuel (Diesel and Petrol) so fuel log can filter by fuel type.
     */
    public function run(): void
    {
        $companyId = 1;

        $expenseGroup = AccountClassGroup::where('company_id', $companyId)
            ->whereHas('accountClass', function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%expense%']);
            })
            ->first();

        if (!$expenseGroup) {
            $this->command->warn('FleetFuelGlAccountSeeder: No expense account class group found for company ' . $companyId . '.');
            return;
        }

        ChartAccount::firstOrCreate(
            [
                'account_class_group_id' => $expenseGroup->id,
                'account_code' => '5310',
            ],
            [
                'account_name' => 'Fuel - Diesel',
                'fuel_type' => 'diesel',
                'account_type' => 'parent',
                'parent_id' => null,
                'has_cash_flow' => true,
                'has_equity' => false,
            ]
        );

        $petrol = ChartAccount::firstOrCreate(
            [
                'account_class_group_id' => $expenseGroup->id,
                'account_code' => '5311',
            ],
            [
                'account_name' => 'Fuel - Petrol',
                'fuel_type' => 'petrol',
                'account_type' => 'parent',
                'parent_id' => null,
                'has_cash_flow' => true,
                'has_equity' => false,
            ]
        );

        // Ensure fuel_type is set if accounts already existed
        ChartAccount::whereIn('account_code', ['5310', '5311'])->get()->each(function ($acc) {
            if ($acc->account_code === '5310' && $acc->fuel_type !== 'diesel') {
                $acc->update(['fuel_type' => 'diesel', 'account_name' => 'Fuel - Diesel']);
            }
            if ($acc->account_code === '5311' && $acc->fuel_type !== 'petrol') {
                $acc->update(['fuel_type' => 'petrol', 'account_name' => 'Fuel - Petrol']);
            }
        });

        $this->command->info('Fleet fuel GL accounts (Diesel, Petrol) created/updated.');
    }
}
