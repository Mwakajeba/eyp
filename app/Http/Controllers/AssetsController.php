<?php

namespace App\Http\Controllers;

use App\Models\ChartAccount;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class AssetsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index()
    {
        return view('assets.index');
    }

    public function settings()
    {
        $settings = [
            'default_depreciation_method' => SystemSetting::where('key', 'asset_default_depreciation_method')->value('value') ?? 'straight_line',
            'default_useful_life_months' => (int) (SystemSetting::where('key', 'asset_default_useful_life_months')->value('value') ?? 60),
            'default_depreciation_rate' => (float) (SystemSetting::where('key', 'asset_default_depreciation_rate')->value('value') ?? 0),
            'depreciation_convention' => SystemSetting::where('key', 'asset_depreciation_convention')->value('value') ?? 'monthly_prorata',
            'depreciation_frequency' => SystemSetting::where('key', 'asset_depreciation_frequency')->value('value') ?? 'monthly',
            'capitalization_threshold' => (float) (SystemSetting::where('key', 'asset_capitalization_threshold')->value('value') ?? 0),
            'asset_code_format' => SystemSetting::where('key', 'asset_code_format')->value('value') ?? 'AST-{YYYY}-{SEQ}',
            // Multi-Book & Dual Depreciation
            'books' => [
                'enabled' => (bool) (SystemSetting::where('key', 'asset_books_enabled')->value('value') ?? false),
                'accounting_book_code' => SystemSetting::where('key', 'asset_accounting_book_code')->value('value') ?? 'FIN',
                'tax_book_code' => SystemSetting::where('key', 'asset_tax_book_code')->value('value') ?? 'TAX',
                'dual_depreciation_enabled' => (bool) (SystemSetting::where('key', 'asset_dual_depreciation_enabled')->value('value') ?? true),
                'dual_post_to_gl' => (bool) (SystemSetting::where('key', 'asset_dual_post_to_gl')->value('value') ?? true),
            ],
            // Tax Pools (JSON)
            'tax_pools' => json_decode(SystemSetting::where('key', 'asset_tax_pools')->value('value') ?? '[]', true) ?: [],
            // Deferred Tax
            'deferred_tax' => [
                'enabled' => (bool) (SystemSetting::where('key', 'asset_deferred_tax_enabled')->value('value') ?? true),
                'tax_rate_percent' => (float) (SystemSetting::where('key', 'asset_tax_rate_percent')->value('value') ?? 30),
                'auto_generate_journal' => (bool) (SystemSetting::where('key', 'asset_deferred_tax_auto_journal')->value('value') ?? true),
                'produce_reconciliation_report' => (bool) (SystemSetting::where('key', 'asset_deferred_tax_recon_report')->value('value') ?? true),
            ],
            'approvals' => [
                'enable_global' => (bool) SystemSetting::where('key', 'asset_approvals_enabled')->value('value') ?? false,
                'require_capitalization' => (bool) SystemSetting::where('key', 'asset_require_approval_capitalization')->value('value') ?? false,
                'require_revaluation' => (bool) SystemSetting::where('key', 'asset_require_approval_revaluation')->value('value') ?? false,
                'require_impairment' => (bool) SystemSetting::where('key', 'asset_require_approval_impairment')->value('value') ?? false,
                'require_disposal' => (bool) SystemSetting::where('key', 'asset_require_approval_disposal')->value('value') ?? false,
            ],
            'accounts' => [
                'asset' => SystemSetting::where('key', 'asset_default_asset_account')->value('value'),
                // Prefer canonical keys under "Asset Management Settings" with fallback to prior keys
                'accum_depr' => SystemSetting::where('key', 'asset_default_accumulated_depreciation_account')->value('value')
                    ?? SystemSetting::where('key', 'asset_default_accum_depr_account')->value('value'),
                'depr_expense' => SystemSetting::where('key', 'asset_default_depreciation_expense_account')->value('value')
                    ?? SystemSetting::where('key', 'asset_default_depr_expense_account')->value('value'),
                // Tax-related and deferred tax postings
                'tax_accum_depr' => SystemSetting::where('key', 'asset_tax_accum_depr_account')->value('value'),
                'tax_depr_expense' => SystemSetting::where('key', 'asset_tax_depr_expense_account')->value('value'),
                'deferred_tax_expense' => SystemSetting::where('key', 'asset_deferred_tax_expense_account')->value('value'),
                'deferred_tax_asset' => SystemSetting::where('key', 'asset_deferred_tax_asset_account')->value('value'),
                'deferred_tax_liability' => SystemSetting::where('key', 'asset_deferred_tax_liability_account')->value('value'),
                'gain_on_disposal' => SystemSetting::where('key', 'asset_default_gain_disposal_account')->value('value')
                    ?? SystemSetting::where('key', 'asset_default_gain_on_disposal_account')->value('value'),
                'loss_on_disposal' => SystemSetting::where('key', 'asset_default_loss_disposal_account')->value('value')
                    ?? SystemSetting::where('key', 'asset_default_loss_on_disposal_account')->value('value'),
                // Use revaluation gain as reserve for UI, fallback to older reserve key if present
                'revaluation_reserve' => SystemSetting::where('key', 'asset_default_revaluation_gain_account')->value('value')
                    ?? SystemSetting::where('key', 'asset_default_revaluation_reserve_account')->value('value'),
                'revaluation_loss' => SystemSetting::where('key', 'asset_default_revaluation_loss_account')->value('value'),
                'hfs_account' => SystemSetting::where('key', 'asset_default_hfs_account')->value('value'),
                'impairment_loss' => SystemSetting::where('key', 'asset_default_impairment_loss_account')->value('value'),
            ],
            // Tax computation module flag
            'tax_computation_enabled' => (bool) (SystemSetting::where('key', 'tax_module_enabled')->value('value') ?? false),
        ];

        $accounts = ChartAccount::orderBy('account_code')->get();

        return view('assets.settings.index', compact('settings', 'accounts'));
    }

    public function updateSettings(Request $request)
    {
        // Normalize checkbox booleans to true/false before validation
        $request->merge([
            'books' => array_merge($request->input('books', []), [
                'enabled' => $request->boolean('books.enabled'),
                'dual_depreciation_enabled' => $request->boolean('books.dual_depreciation_enabled'),
                'dual_post_to_gl' => $request->boolean('books.dual_post_to_gl'),
            ]),
            'deferred_tax' => array_merge($request->input('deferred_tax', []), [
                'enabled' => $request->boolean('deferred_tax.enabled'),
                'auto_generate_journal' => $request->boolean('deferred_tax.auto_generate_journal'),
                'produce_reconciliation_report' => $request->boolean('deferred_tax.produce_reconciliation_report'),
            ]),
            'tax_computation_enabled' => $request->boolean('tax_computation_enabled'),
        ]);

        $validated = $request->validate([
            'default_depreciation_method' => 'required|in:straight_line,declining_balance,syd,units',
            'default_useful_life_months' => 'required|integer|min:1',
            'default_depreciation_rate' => 'nullable|numeric|min:0|max:100',
            'depreciation_convention' => 'required|in:monthly_prorata,mid_month,full_month',
            'depreciation_frequency' => 'required|in:monthly,quarterly,annually',
            'capitalization_threshold' => 'required|numeric|min:0',
            'asset_code_format' => 'required|string|max:50',
            // Multi-book & dual depreciation
            'books.enabled' => 'boolean',
            'books.accounting_book_code' => 'nullable|string|max:20',
            'books.tax_book_code' => 'nullable|string|max:20',
            'books.dual_depreciation_enabled' => 'boolean',
            'books.dual_post_to_gl' => 'boolean',
            // Tax pools JSON
            'tax_pools_json' => 'nullable|string',
            // Deferred tax
            'deferred_tax.enabled' => 'boolean',
            'deferred_tax.tax_rate_percent' => 'nullable|numeric|min:0|max:100',
            'deferred_tax.auto_generate_journal' => 'boolean',
            'deferred_tax.produce_reconciliation_report' => 'boolean',
            'approvals.enable_global' => 'nullable|boolean',
            'approvals.require_capitalization' => 'nullable|boolean',
            'approvals.require_revaluation' => 'nullable|boolean',
            'approvals.require_impairment' => 'nullable|boolean',
            'approvals.require_disposal' => 'nullable|boolean',
            'accounts.asset' => 'nullable|integer|exists:chart_accounts,id',
            'accounts.accum_depr' => 'nullable|integer|exists:chart_accounts,id',
            'accounts.depr_expense' => 'nullable|integer|exists:chart_accounts,id',
            'accounts.tax_accum_depr' => 'nullable|integer|exists:chart_accounts,id',
            'accounts.tax_depr_expense' => 'nullable|integer|exists:chart_accounts,id',
            'accounts.deferred_tax_expense' => 'nullable|integer|exists:chart_accounts,id',
            'accounts.deferred_tax_asset' => 'nullable|integer|exists:chart_accounts,id',
            'accounts.deferred_tax_liability' => 'nullable|integer|exists:chart_accounts,id',
            'accounts.gain_on_disposal' => 'nullable|integer|exists:chart_accounts,id',
            'accounts.loss_on_disposal' => 'nullable|integer|exists:chart_accounts,id',
            'accounts.revaluation_reserve' => 'nullable|integer|exists:chart_accounts,id',
            'accounts.revaluation_loss' => 'nullable|integer|exists:chart_accounts,id',
            'accounts.hfs_account' => 'nullable|integer|exists:chart_accounts,id',
            'accounts.impairment_loss' => 'nullable|integer|exists:chart_accounts,id',
            // Tax computation module
            'tax_computation_enabled' => 'boolean',
        ]);

        $map = [
            'asset_default_depreciation_method' => [$validated['default_depreciation_method'], 'Default Depreciation Method'],
            'asset_default_useful_life_months' => [$validated['default_useful_life_months'], 'Default Useful Life (months)'],
            'asset_default_depreciation_rate' => [$validated['default_depreciation_rate'] ?? null, 'Default Depreciation Rate (%)'],
            'asset_depreciation_convention' => [$validated['depreciation_convention'], 'Depreciation Convention'],
            'asset_depreciation_frequency' => [$validated['depreciation_frequency'], 'Depreciation Frequency'],
            'asset_capitalization_threshold' => [$validated['capitalization_threshold'], 'Capitalization Threshold (TZS)'],
            'asset_code_format' => [$validated['asset_code_format'], 'Asset Code Format'],
            // Multi-book & dual depreciation
            'asset_books_enabled' => [(int) ($validated['books']['enabled'] ?? 0), 'Enable Multi-Book (Accounting + Tax)'],
            'asset_accounting_book_code' => [$validated['books']['accounting_book_code'] ?? 'FIN', 'Accounting Book Code'],
            'asset_tax_book_code' => [$validated['books']['tax_book_code'] ?? 'TAX', 'Tax Book Code'],
            'asset_dual_depreciation_enabled' => [(int) ($validated['books']['dual_depreciation_enabled'] ?? 0), 'Enable Dual Depreciation'],
            'asset_dual_post_to_gl' => [(int) ($validated['books']['dual_post_to_gl'] ?? 0), 'Post Both Depreciations to GL'],
            // Deferred tax
            'asset_deferred_tax_enabled' => [(int) ($validated['deferred_tax']['enabled'] ?? 0), 'Enable Deferred Tax'],
            'asset_tax_rate_percent' => [$validated['deferred_tax']['tax_rate_percent'] ?? 30, 'Corporate Tax Rate (%)'],
            'asset_deferred_tax_auto_journal' => [(int) ($validated['deferred_tax']['auto_generate_journal'] ?? 0), 'Auto Journal Deferred Tax'],
            'asset_deferred_tax_recon_report' => [(int) ($validated['deferred_tax']['produce_reconciliation_report'] ?? 0), 'Produce Deferred Tax Reconciliation Report'],
            'asset_approvals_enabled' => [(int) ($validated['approvals']['enable_global'] ?? 0), 'Enable Approvals'],
            'asset_require_approval_capitalization' => [(int) ($validated['approvals']['require_capitalization'] ?? 0), 'Require Approval - Capitalization'],
            'asset_require_approval_revaluation' => [(int) ($validated['approvals']['require_revaluation'] ?? 0), 'Require Approval - Revaluation'],
            'asset_require_approval_impairment' => [(int) ($validated['approvals']['require_impairment'] ?? 0), 'Require Approval - Impairment'],
            'asset_require_approval_disposal' => [(int) ($validated['approvals']['require_disposal'] ?? 0), 'Require Approval - Disposal'],
            // Persist to canonical Asset Management Settings keys
            'asset_default_asset_account' => [$validated['accounts']['asset'] ?? null, 'Default Asset Account (Asset)'],
            'asset_default_accumulated_depreciation_account' => [$validated['accounts']['accum_depr'] ?? null, 'Default Accumulated Depreciation Account (Contra Asset)'],
            'asset_default_depreciation_expense_account' => [$validated['accounts']['depr_expense'] ?? null, 'Default Depreciation Expense Account (Expense)'],
            'asset_tax_accum_depr_account' => [$validated['accounts']['tax_accum_depr'] ?? null, 'Tax Accumulated Depreciation Account'],
            'asset_tax_depr_expense_account' => [$validated['accounts']['tax_depr_expense'] ?? null, 'Tax Depreciation Expense Account'],
            'asset_deferred_tax_expense_account' => [$validated['accounts']['deferred_tax_expense'] ?? null, 'Deferred Tax Expense Account'],
            'asset_deferred_tax_asset_account' => [$validated['accounts']['deferred_tax_asset'] ?? null, 'Deferred Tax Asset Account'],
            'asset_deferred_tax_liability_account' => [$validated['accounts']['deferred_tax_liability'] ?? null, 'Deferred Tax Liability Account'],
            'asset_default_gain_disposal_account' => [$validated['accounts']['gain_on_disposal'] ?? null, 'Default Asset Gain Disposal Account (Revenue)'],
            'asset_default_loss_disposal_account' => [$validated['accounts']['loss_on_disposal'] ?? null, 'Default Asset Loss Disposal Account (Expense)'],
            'asset_default_revaluation_gain_account' => [$validated['accounts']['revaluation_reserve'] ?? null, 'Default Asset Revaluation Gain Account (Equity)'],
            'asset_default_revaluation_loss_account' => [$validated['accounts']['revaluation_loss'] ?? null, 'Default Asset Revaluation Loss Account (Expense)'],
            'asset_default_hfs_account' => [$validated['accounts']['hfs_account'] ?? null, 'Default Held for Sale Account (Asset)'],
            'asset_default_impairment_loss_account' => [$validated['accounts']['impairment_loss'] ?? null, 'Default Impairment Loss Account (Expense)'],
            // Tax computation module
            'tax_module_enabled' => [(int) ($validated['tax_computation_enabled'] ?? 0), 'Enable Tax Computation Module'],
        ];

        foreach ($map as $key => [$value, $label]) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'label' => $label]
            );
        }

        // Save tax pools JSON if provided
        if (!empty($validated['tax_pools_json'])) {
            SystemSetting::updateOrCreate(
                ['key' => 'asset_tax_pools'],
                ['value' => $validated['tax_pools_json'], 'label' => 'Asset Tax Pools (TRA Classes JSON)']
            );
        }

        return redirect()->route('assets.settings.index')->with('success', 'Asset settings updated successfully.');
    }
}


