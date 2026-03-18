<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ChartAccount;
use App\Models\SystemSetting;

class AssetSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Seeding default Asset Management settings...');

        // Prefer exact account_code matches; fallback to name-based search
        $findByCode = function (array $codes): ?int {
            if (empty($codes)) return null;
            $acc = ChartAccount::whereIn('account_code', $codes)->orderBy('id')->first();
            return $acc ? (int) $acc->id : null;
        };

        $findByName = function (array $patterns): ?int {
            foreach ($patterns as $pattern) {
                $acc = ChartAccount::where('account_name', 'like', $pattern)
                    ->orWhere('account_code', 'like', $pattern)
                    ->first();
                if ($acc) return (int) $acc->id;
            }
            return null;
        };

        // Configure preferred account codes here (leave empty to let seeder fallback)
        $preferredCodes = [
            'asset_default_asset_account' => ['PPE', 'ASSET-PPE'],
            'asset_default_accumulated_depreciation_account' => ['ACC-DEPR', 'ACCUM-DEPR'],
            'asset_default_depreciation_expense_account' => ['DEPR-EXP'],
            'asset_default_gain_disposal_account' => ['GAIN-DISP'],
            'asset_default_loss_disposal_account' => ['LOSS-DISP'],
            'asset_default_revaluation_gain_account' => ['REVAL-GAIN', 'REVAL-RES'],
            'asset_default_revaluation_loss_account' => ['REVAL-LOSS'],
            // Optional tax/deferred accounts if you maintain codes
            'asset_tax_accum_depr_account' => ['TAX-ACC-DEPR'],
            'asset_tax_depr_expense_account' => ['TAX-DEPR-EXP'],
            'asset_deferred_tax_expense_account' => ['DEF-TAX-EXP'],
            'asset_deferred_tax_asset_account' => ['DEF-TAX-ASSET'],
            'asset_deferred_tax_liability_account' => ['DEF-TAX-LIAB'],
        ];

        // Core (non-account) behavior defaults
        $defaults = [
            'asset_default_depreciation_method' => ['straight_line', 'Default Depreciation Method'],
            'asset_default_useful_life_months' => [60, 'Default Useful Life (months)'], // 5 years
            'asset_default_depreciation_rate' => [0, 'Default Depreciation Rate (%)'],
            'asset_depreciation_convention' => ['monthly_prorata', 'Depreciation Convention'],
            'asset_depreciation_frequency' => ['monthly', 'Depreciation Frequency'],
            'asset_capitalization_threshold' => [0, 'Capitalization Threshold (TZS)'],
            'asset_code_format' => ['AST-{YYYY}-{SEQ}', 'Asset Code Format'],

            // Multi-book & dual depreciation
            'asset_books_enabled' => [1, 'Enable Multi-Book (Accounting + Tax)'],
            'asset_accounting_book_code' => ['FIN', 'Accounting Book Code'],
            'asset_tax_book_code' => ['TAX', 'Tax Book Code'],
            'asset_dual_depreciation_enabled' => [1, 'Enable Dual Depreciation'],
            'asset_dual_post_to_gl' => [1, 'Post Both Depreciations to GL'],

            // Deferred tax defaults
            'asset_deferred_tax_enabled' => [1, 'Enable Deferred Tax'],
            'asset_tax_rate_percent' => [30, 'Corporate Tax Rate (%)'],
            'asset_deferred_tax_auto_journal' => [1, 'Auto Journal Deferred Tax'],
            'asset_deferred_tax_recon_report' => [1, 'Produce Deferred Tax Reconciliation Report'],

            // Tax module flag
            'tax_module_enabled' => [1, 'Enable Tax Computation Module'],
        ];

        foreach ($defaults as $key => [$value, $label]) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'label' => $label, 'group' => 'assets', 'type' => is_int($value) ? 'integer' : (is_bool($value) ? 'boolean' : 'string')]
            );
        }

        // Accounts seeded with deterministic fixed IDs like in HotelSettingsSeeder
        // Use IDs aligned with existing defaults in SystemSetting model (adjust to your seed data if needed)
        $assetAccountId = DB::table('chart_accounts')->where('id', 600)->value('id');
        $accumDeprAccountId = DB::table('chart_accounts')->where('id', 601)->value('id');
        $deprExpenseAccountId = DB::table('chart_accounts')->where('id', 73)->value('id');
        $gainDisposalAccountId = DB::table('chart_accounts')->where('id', 747)->value('id');
        $lossDisposalAccountId = DB::table('chart_accounts')->where('id', 748)->value('id');
        $revalGainAccountId = DB::table('chart_accounts')->where('id', 602)->value('id');
        $revalLossAccountId = DB::table('chart_accounts')->where('id', 637)->value('id');
        $hfsAccountId = DB::table('chart_accounts')->where('id', 723)->value('id');
        $impairmentLossAccountId = DB::table('chart_accounts')->where('id', 603)->value('id');

        // Optional: tax and deferred tax accounts (set to null if not available)
        $taxAccumDeprId = DB::table('chart_accounts')->where('id', 217)->value('id');
        $taxDeprExpenseId = DB::table('chart_accounts')->where('id', 216)->value('id');
        $deferredTaxExpenseId = DB::table('chart_accounts')->where('id', 218)->value('id');
        $deferredTaxAssetId = DB::table('chart_accounts')->where('id', 219)->value('id');
        $deferredTaxLiabilityId = DB::table('chart_accounts')->where('id', 220)->value('id');

        $accountSettings = [
            'asset_default_asset_account' => [
                'value' => $assetAccountId ?: null,
                'type' => 'integer',
                'group' => 'assets',
                'label' => 'Default Asset Account (Asset)',
                'description' => 'Default chart account for asset cost (PPE)'
            ],
            'asset_default_accumulated_depreciation_account' => [
                'value' => $accumDeprAccountId ?: null,
                'type' => 'integer',
                'group' => 'assets',
                'label' => 'Default Accumulated Depreciation Account (Contra Asset)',
                'description' => 'Default chart account for accumulated depreciation'
            ],
            'asset_default_depreciation_expense_account' => [
                'value' => $deprExpenseAccountId ?: null,
                'type' => 'integer',
                'group' => 'assets',
                'label' => 'Default Depreciation Expense Account (Expense)',
                'description' => 'Default chart account for depreciation expense'
            ],
            'asset_default_gain_disposal_account' => [
                'value' => $gainDisposalAccountId ?: null,
                'type' => 'integer',
                'group' => 'assets',
                'label' => 'Default Asset Gain Disposal Account (Revenue)',
                'description' => 'Default chart account for asset disposal gains'
            ],
            'asset_default_loss_disposal_account' => [
                'value' => $lossDisposalAccountId ?: null,
                'type' => 'integer',
                'group' => 'assets',
                'label' => 'Default Asset Loss Disposal Account (Expense)',
                'description' => 'Default chart account for asset disposal losses'
            ],
            'asset_default_revaluation_gain_account' => [
                'value' => $revalGainAccountId ?: null,
                'type' => 'integer',
                'group' => 'assets',
                'label' => 'Default Asset Revaluation Gain Account (Equity)',
                'description' => 'Default chart account for revaluation gains'
            ],
            'asset_default_revaluation_loss_account' => [
                'value' => $revalLossAccountId ?: null,
                'type' => 'integer',
                'group' => 'assets',
                'label' => 'Default Asset Revaluation Loss Account (Expense)',
                'description' => 'Default chart account for revaluation losses'
            ],
            'asset_default_hfs_account' => [
                'value' => $hfsAccountId ?: null,
                'type' => 'integer',
                'group' => 'assets',
                'label' => 'Default Held for Sale Account (Asset)',
                'description' => 'Default chart account for assets held for sale'
            ],
            'asset_default_impairment_loss_account' => [
                'value' => $impairmentLossAccountId ?: null,
                'type' => 'integer',
                'group' => 'assets',
                'label' => 'Default Impairment Loss Account (Expense)',
                'description' => 'Default chart account for impairment losses'
            ],

            // Optional tax and deferred tax accounts (only set if IDs exist)
            'asset_tax_accum_depr_account' => [
                'value' => $taxAccumDeprId ?: null,
                'type' => 'integer',
                'group' => 'assets',
                'label' => 'Tax Accumulated Depreciation Account',
                'description' => 'Account for accumulated depreciation (Tax Book)'
            ],
            'asset_tax_depr_expense_account' => [
                'value' => $taxDeprExpenseId ?: null,
                'type' => 'integer',
                'group' => 'assets',
                'label' => 'Tax Depreciation Expense Account',
                'description' => 'Depreciation expense (Tax Book)'
            ],
            'asset_deferred_tax_expense_account' => [
                'value' => $deferredTaxExpenseId ?: null,
                'type' => 'integer',
                'group' => 'assets',
                'label' => 'Deferred Tax Expense Account',
                'description' => 'Expense for deferred tax movement'
            ],
            'asset_deferred_tax_asset_account' => [
                'value' => $deferredTaxAssetId ?: null,
                'type' => 'integer',
                'group' => 'assets',
                'label' => 'Deferred Tax Asset Account',
                'description' => 'Balance sheet account for deferred tax asset'
            ],
            'asset_deferred_tax_liability_account' => [
                'value' => $deferredTaxLiabilityId ?: null,
                'type' => 'integer',
                'group' => 'assets',
                'label' => 'Deferred Tax Liability Account',
                'description' => 'Balance sheet account for deferred tax liability'
            ],
        ];

        foreach ($accountSettings as $key => $settingData) {
            SystemSetting::updateOrCreate(['key' => $key], $settingData);
        }

        // Seed default Tax Pools (TRA classes) JSON
        $defaultPools = [
            // Per TRA (TZ) classes and rates (2025)
            [ 'class' => 'Class 1', 'name' => 'Computers, small vehicles (<30 seats), construction & earth-moving equipment', 'rate' => 37.5, 'method' => 'reducing_balance', 'book_code' => 'TAX', 'notes' => 'Reducing balance'] ,
            [ 'class' => 'Class 2', 'name' => 'Heavy vehicles (≥30 seats), aircraft, vessels, manufacturing/agricultural machinery', 'rate' => 25.0, 'method' => 'reducing_balance', 'book_code' => 'TAX', 'notes' => '50% allowance (first two years) if used in manufacturing/tourism/fish farming' ],
            [ 'class' => 'Class 3', 'name' => 'Office furniture, fixtures, and equipment; any asset not in another class', 'rate' => 12.5, 'method' => 'reducing_balance', 'book_code' => 'TAX', 'notes' => 'Reducing balance' ],
            [ 'class' => 'Class 5', 'name' => 'Agricultural permanent structures (dams, fences, reservoirs, etc.)', 'rate' => 20.0, 'method' => 'straight_line', 'book_code' => 'TAX', 'notes' => '5 years write-off' ],
            [ 'class' => 'Class 6', 'name' => 'Other buildings & permanent structures', 'rate' => 5.0, 'method' => 'straight_line', 'book_code' => 'TAX', 'notes' => '' ],
            [ 'class' => 'Class 7', 'name' => 'Intangible assets', 'rate' => null, 'method' => 'useful_life', 'book_code' => 'TAX', 'notes' => '1 ÷ useful life (round down to nearest half year)' ],
            [ 'class' => 'Class 8', 'name' => 'Agricultural plant & machinery, EFDs for non-VAT traders', 'rate' => 100.0, 'method' => 'immediate_write_off', 'book_code' => 'TAX', 'notes' => 'Immediate write-off' ],
        ];
        SystemSetting::updateOrCreate(
            ['key' => 'asset_tax_pools'],
            ['value' => json_encode($defaultPools), 'label' => 'Asset Tax Pools (TRA Classes JSON)', 'group' => 'assets', 'type' => 'string']
        );

        $this->command?->info('Asset Management settings seeded.');
    }
}


