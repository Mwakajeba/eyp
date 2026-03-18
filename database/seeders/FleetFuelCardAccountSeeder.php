<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\ChartAccount;
use App\Models\Company;
use Illuminate\Database\Seeder;

class FleetFuelCardAccountSeeder extends Seeder
{
    /**
     * Seed a default "Fuel Card" bank account (account_nature = card) for fleet fuel payments.
     * Run after account_nature migration and ChartAccountSeeder.
     */
    public function run(): void
    {
        $company = Company::first();
        if (!$company) {
            return;
        }

        // Use the dedicated Card / Payment Card chart account (seeded by CardChartAccountSeeder)
        $chartAccount = ChartAccount::whereHas('accountClassGroup', fn ($q) => $q->where('company_id', $company->id))
            ->where('account_code', '1007')
            ->where('account_name', 'Card / Payment Card')
            ->first();

        // Fallback: any Cash & Cash Equivalents account if Card account not yet seeded
        if (!$chartAccount) {
            $chartAccount = ChartAccount::whereHas('accountClassGroup', fn ($q) => $q->where('company_id', $company->id))
                ->whereHas('accountClassGroup', fn ($q) => $q->where('name', 'Cash & Cash Equivalents (IAS 7)'))
                ->orderBy('id')
                ->first();
        }

        if (!$chartAccount) {
            return;
        }

        $uniqueNumber = 'CARD-FUEL-' . str_pad((string) (BankAccount::where('company_id', $company->id)->count() + 1), 3, '0', STR_PAD_LEFT);
        BankAccount::updateOrCreate(
            [
                'company_id' => $company->id,
                'account_nature' => 'card',
            ],
            [
                'chart_account_id' => $chartAccount->id,
                'name' => 'Fuel Card',
                'account_number' => $uniqueNumber,
                'is_all_branches' => true,
                'branch_id' => null,
                'currency' => $company->functional_currency ?? 'TZS',
                'revaluation_required' => false,
            ]
        );
    }
}
