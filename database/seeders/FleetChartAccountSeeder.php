<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartAccount;
use App\Models\AccountClassGroup;
use App\Models\Fleet\FleetSystemSetting;

class FleetChartAccountSeeder extends Seeder
{
    /**
     * Create fleet chart accounts (Income, Receivable, Opening Balance) and set fleet settings.
     */
    public function run(): void
    {
        $companyId = 1;

        $salesRevenueGroup = AccountClassGroup::where('company_id', $companyId)
            ->where('name', 'Sales Revenue')
            ->first();

        $receivablesGroup = AccountClassGroup::where('company_id', $companyId)
            ->where('name', 'Trade & Other Receivables')
            ->first();

        if (!$salesRevenueGroup || !$receivablesGroup) {
            $this->command->warn('FleetChartAccountSeeder: Sales Revenue or Trade & Other Receivables group not found for company ' . $companyId . '. Run AccountClassGroupSeeder and ChartAccountSeeder first.');
            return;
        }

        $fleetIncome = ChartAccount::firstOrCreate(
            [
                'account_class_group_id' => $salesRevenueGroup->id,
                'account_code' => '4210',
            ],
            [
                'account_name' => 'Fleet Income',
                'account_type' => 'parent',
                'parent_id' => null,
                'has_cash_flow' => 1,
                'has_equity' => 0,
                'cash_flow_category_id' => 1,
                'equity_category_id' => null,
            ]
        );

        // Use codes 1248/1249 to avoid conflict with ChartAccountSeeder (1105, 1106, 1110, 1111 are used)
        $fleetReceivable = ChartAccount::firstOrCreate(
            [
                'account_class_group_id' => $receivablesGroup->id,
                'account_code' => '1248',
            ],
            [
                'account_name' => 'Fleet / Trip Receivables',
                'account_type' => 'parent',
                'parent_id' => null,
                'has_cash_flow' => 1,
                'has_equity' => 0,
                'cash_flow_category_id' => 1,
                'equity_category_id' => null,
            ]
        );

        $fleetOpeningBalance = ChartAccount::firstOrCreate(
            [
                'account_class_group_id' => $receivablesGroup->id,
                'account_code' => '1249',
            ],
            [
                'account_name' => 'Fleet Opening Balance',
                'account_type' => 'parent',
                'parent_id' => null,
                'has_cash_flow' => 1,
                'has_equity' => 0,
                'cash_flow_category_id' => 1,
                'equity_category_id' => null,
            ]
        );

        FleetSystemSetting::setSetting($companyId, 'fleet_income_chart_account_id', (string) $fleetIncome->id, 1);
        FleetSystemSetting::setSetting($companyId, 'fleet_receivable_chart_account_id', (string) $fleetReceivable->id, 1);
        FleetSystemSetting::setSetting($companyId, 'fleet_opening_balance_chart_account_id', (string) $fleetOpeningBalance->id, 1);

        $this->command->info('Fleet chart accounts created/updated and settings saved for company ' . $companyId . '.');
    }
}
