<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Models\Assets\AssetCategory;
use App\Models\ChartAccount;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Vinkla\Hashids\Facades\Hashids;

class AssetCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index()
    {
        return view('assets.categories.index');
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $hasAssetsTable = Schema::hasTable('assets');
        if ($hasAssetsTable) {
            $assetsCountSub = DB::table('assets')
                ->whereColumn('assets.asset_category_id', 'asset_categories.id')
                ->where('company_id', $user->company_id)
                ->when($user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id))
                ->selectRaw('COUNT(*)');
        }

        $query = AssetCategory::forCompany($user->company_id)
            ->when($user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id))
            ->select([
                'id','code','name','default_depreciation_method','default_useful_life_months','default_depreciation_rate','depreciation_convention','created_at'
            ]);

        if ($hasAssetsTable) {
            $query->selectSub($assetsCountSub, 'assets_count');
        } else {
            $query->selectRaw('0 as assets_count');
        }

        return DataTables::of($query->orderBy('created_at','desc'))
            ->addColumn('actions', function($row) {
                $encoded = Hashids::encode($row->id);
                $edit = route('assets.categories.edit', $encoded);
                $delete = route('assets.categories.destroy', $encoded);
                $csrf = csrf_field();
                $method = method_field('DELETE');
                $html = "<div class=\"d-flex gap-2\">";
                $html .= "<a href=\"{$edit}\" class=\"btn btn-sm btn-outline-warning\" title=\"Edit\"><i class=\"bx bx-edit\"></i></a>";
                $html .= "<form action=\"{$delete}\" method=\"POST\" class=\"d-inline category-delete-form\">{$csrf}{$method}<button type=\"button\" class=\"btn btn-sm btn-outline-danger btn-delete-category\" title=\"Delete\"><i class=\"bx bx-trash\"></i></button></form>";
                $html .= "</div>";
                return $html;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function create()
    {
        $accounts = ChartAccount::orderBy('account_code')->get();
        $defaults = [
            'method' => SystemSetting::where('key', 'asset_default_depreciation_method')->value('value') ?? 'straight_line',
            'life' => (int) (SystemSetting::where('key', 'asset_default_useful_life_months')->value('value') ?? 60),
            'rate' => (float) (SystemSetting::where('key', 'asset_default_depreciation_rate')->value('value') ?? 0),
            'convention' => SystemSetting::where('key', 'asset_depreciation_convention')->value('value') ?? 'monthly_prorata',
            'threshold' => (float) (SystemSetting::where('key', 'asset_capitalization_threshold')->value('value') ?? 0),
            'asset_account_id' => SystemSetting::where('key', 'asset_default_asset_account')->value('value'),
            'accum_depr_account_id' => SystemSetting::where('key', 'asset_default_accumulated_depreciation_account')->value('value') ?? SystemSetting::where('key', 'asset_default_accum_depr_account')->value('value'),
            'depr_expense_account_id' => SystemSetting::where('key', 'asset_default_depreciation_expense_account')->value('value') ?? SystemSetting::where('key', 'asset_default_depr_expense_account')->value('value'),
            'gain_on_disposal_account_id' => SystemSetting::where('key', 'asset_default_gain_disposal_account')->value('value') ?? SystemSetting::where('key', 'asset_default_gain_on_disposal_account')->value('value'),
            'loss_on_disposal_account_id' => SystemSetting::where('key', 'asset_default_loss_disposal_account')->value('value') ?? SystemSetting::where('key', 'asset_default_loss_on_disposal_account')->value('value'),
            'revaluation_reserve_account_id' => SystemSetting::where('key', 'asset_default_revaluation_gain_account')->value('value') ?? SystemSetting::where('key', 'asset_default_revaluation_reserve_account')->value('value'),
        ];
        return view('assets.categories.create', compact('accounts','defaults'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:asset_categories,code',
            'name' => 'required|string|max:255',
            
            'default_depreciation_method' => 'required|in:straight_line,declining_balance,syd,units,no_depreciation',
            'default_useful_life_months' => 'nullable|integer|min:1',
            'default_useful_life_years' => 'nullable|integer|min:1',
            'default_depreciation_rate' => 'nullable|numeric|min:0|max:100',
            'depreciation_convention' => 'nullable|in:monthly_prorata,mid_month,full_month',
            'capitalization_threshold' => 'required|numeric|min:0',
            'residual_value_percent' => 'nullable|numeric|min:0|max:100',
            'ifrs_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'asset_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'accum_depr_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'depr_expense_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'gain_on_disposal_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'loss_on_disposal_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'revaluation_reserve_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'impairment_loss_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'impairment_reversal_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'accumulated_impairment_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'hfs_account_id' => 'nullable|integer|exists:chart_accounts,id',
        ]);

        $user = Auth::user();

        // If "no_depreciation" is selected, set depreciation-related fields to NULL
        if ($validated['default_depreciation_method'] === 'no_depreciation') {
            $validated['default_useful_life_months'] = null;
            $validated['default_depreciation_rate'] = null;
            $validated['depreciation_convention'] = null;
        } else {
            // Conditional validation: if not "no_depreciation", require useful life and convention
            if (empty($validated['default_useful_life_months'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['default_useful_life_months' => 'Useful life is required when depreciation method is selected.']);
            }
            if (empty($validated['depreciation_convention'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['depreciation_convention' => 'Depreciation convention is required when depreciation method is selected.']);
            }
        }

        // If years provided, convert to months unless months were manually set to a different value
        if (!empty($validated['default_useful_life_years']) && (int)$validated['default_useful_life_years'] > 0) {
            $validated['default_useful_life_months'] = (int)$validated['default_useful_life_years'] * 12;
        }
        unset($validated['default_useful_life_years']);

        AssetCategory::create(array_merge($validated, [
            'company_id' => $user->company_id,
            'branch_id' => $user->branch_id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]));

        return redirect()->route('assets.categories.index')->with('success', 'Asset category created successfully.');
    }

    public function edit($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $category = AssetCategory::findOrFail($decodedId);
        $accounts = ChartAccount::orderBy('account_code')->get();
        return view('assets.categories.edit', compact('category', 'accounts'));
    }

    public function update(Request $request, $id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $category = AssetCategory::findOrFail($decodedId);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:asset_categories,code,' . $category->id,
            'name' => 'required|string|max:255',
            
            'default_depreciation_method' => 'required|in:straight_line,declining_balance,syd,units,no_depreciation',
            'default_useful_life_months' => 'nullable|integer|min:1',
            'default_useful_life_years' => 'nullable|integer|min:1',
            'default_depreciation_rate' => 'nullable|numeric|min:0|max:100',
            'depreciation_convention' => 'nullable|in:monthly_prorata,mid_month,full_month',
            'capitalization_threshold' => 'required|numeric|min:0',
            'residual_value_percent' => 'nullable|numeric|min:0|max:100',
            'ifrs_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'asset_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'accum_depr_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'depr_expense_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'gain_on_disposal_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'loss_on_disposal_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'revaluation_reserve_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'impairment_loss_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'impairment_reversal_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'accumulated_impairment_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'hfs_account_id' => 'nullable|integer|exists:chart_accounts,id',
        ]);

        // If "no_depreciation" is selected, set depreciation-related fields to NULL
        if ($validated['default_depreciation_method'] === 'no_depreciation') {
            $validated['default_useful_life_months'] = null;
            $validated['default_depreciation_rate'] = null;
            $validated['depreciation_convention'] = null;
        } else {
            // Conditional validation: if not "no_depreciation", require useful life and convention
            if (empty($validated['default_useful_life_months'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['default_useful_life_months' => 'Useful life is required when depreciation method is selected.']);
            }
            if (empty($validated['depreciation_convention'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['depreciation_convention' => 'Depreciation convention is required when depreciation method is selected.']);
            }
        }

        if (!empty($validated['default_useful_life_years']) && (int)$validated['default_useful_life_years'] > 0) {
            $validated['default_useful_life_months'] = (int)$validated['default_useful_life_years'] * 12;
        }
        unset($validated['default_useful_life_years']);

        $category->update(array_merge($validated, [
            'updated_by' => Auth::id(),
        ]));

        return redirect()->route('assets.categories.index')->with('success', 'Asset category updated successfully.');
    }

    public function destroy($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $category = AssetCategory::findOrFail($decodedId);

        // Prevent deleting a category that has assets
        $user = Auth::user();
        $assetsCount = 0;
        if (Schema::hasTable('assets')) {
            $assetsCount = DB::table('assets')
                ->where('asset_category_id', $category->id)
                ->where('company_id', $user->company_id)
                ->count();
        }

        if ($assetsCount > 0) {
            return redirect()->route('assets.categories.index')
                ->with('error', 'Cannot delete this category because it is assigned to '.$assetsCount.' asset(s).');
        }

        $category->delete();
        return redirect()->route('assets.categories.index')->with('success', 'Asset category deleted successfully.');
    }

    public function show($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $category = AssetCategory::findOrFail($decodedId);

        // Resolve default account models for display
        $accountIds = [
            'asset_account_id' => $category->asset_account_id,
            'accum_depr_account_id' => $category->accum_depr_account_id,
            'depr_expense_account_id' => $category->depr_expense_account_id,
            'gain_on_disposal_account_id' => $category->gain_on_disposal_account_id,
            'loss_on_disposal_account_id' => $category->loss_on_disposal_account_id,
            'revaluation_reserve_account_id' => $category->revaluation_reserve_account_id,
        ];

        $accounts = [];
        foreach ($accountIds as $key => $accId) {
            if ($accId) {
                $accounts[$key] = ChartAccount::find($accId);
            } else {
                $accounts[$key] = null;
            }
        }

        return view('assets.categories.show', compact('category', 'accounts'));
    }
}


