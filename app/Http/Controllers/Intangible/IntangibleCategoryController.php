<?php

namespace App\Http\Controllers\Intangible;

use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\Intangible\IntangibleAssetCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class IntangibleCategoryController extends Controller
{
    /**
     * Show list view of intangible asset categories.
     */
    public function index()
    {
        return view('intangible.categories.index');
    }

    /**
     * Data for DataTables (server-side).
     */
    public function data()
    {
        $user = Auth::user();

        $query = IntangibleAssetCategory::where('company_id', $user->company_id)
            ->with([
                'costAccount',
                'accumulatedAmortisationAccount',
                'accumulatedImpairmentAccount',
                'amortisationExpenseAccount',
                'impairmentLossAccount',
                'disposalGainLossAccount'
            ])
            ->orderBy('name');

        return DataTables::of($query)
            ->addColumn('type_label', function (IntangibleAssetCategory $category) {
                return ucfirst(str_replace('_', ' ', $category->type));
            })
            ->addColumn('is_goodwill_label', function (IntangibleAssetCategory $category) {
                if ($category->is_goodwill) {
                    return '<span class="badge bg-warning text-dark">Yes</span>';
                }
                return '<span class="badge bg-light text-muted">No</span>';
            })
            ->addColumn('is_indefinite_label', function (IntangibleAssetCategory $category) {
                if ($category->is_indefinite_life) {
                    return '<span class="badge bg-info text-dark">Yes</span>';
                }
                return '<span class="badge bg-light text-muted">No</span>';
            })
            ->addColumn('cost_account_name', function (IntangibleAssetCategory $category) {
                if ($category->costAccount) {
                    return $category->costAccount->account_code . ' - ' . $category->costAccount->account_name;
                }
                return '-';
            })
            ->addColumn('accumulated_amortisation_account_name', function (IntangibleAssetCategory $category) {
                if ($category->accumulatedAmortisationAccount) {
                    return $category->accumulatedAmortisationAccount->account_code . ' - ' . $category->accumulatedAmortisationAccount->account_name;
                }
                return '-';
            })
            ->addColumn('accumulated_impairment_account_name', function (IntangibleAssetCategory $category) {
                if ($category->accumulatedImpairmentAccount) {
                    return $category->accumulatedImpairmentAccount->account_code . ' - ' . $category->accumulatedImpairmentAccount->account_name;
                }
                return '-';
            })
            ->addColumn('amortisation_expense_account_name', function (IntangibleAssetCategory $category) {
                if ($category->amortisationExpenseAccount) {
                    return $category->amortisationExpenseAccount->account_code . ' - ' . $category->amortisationExpenseAccount->account_name;
                }
                return '-';
            })
            ->addColumn('impairment_loss_account_name', function (IntangibleAssetCategory $category) {
                if ($category->impairmentLossAccount) {
                    return $category->impairmentLossAccount->account_code . ' - ' . $category->impairmentLossAccount->account_name;
                }
                return '-';
            })
            ->addColumn('disposal_gain_loss_account_name', function (IntangibleAssetCategory $category) {
                if ($category->disposalGainLossAccount) {
                    return $category->disposalGainLossAccount->account_code . ' - ' . $category->disposalGainLossAccount->account_name;
                }
                return '-';
            })
            ->rawColumns(['is_goodwill_label', 'is_indefinite_label'])
            ->make(true);
    }

    /**
     * Show form to create a new intangible asset category.
     */
    public function create()
    {
        // Chart accounts are shared; filter by structure instead of company_id
        $accounts = ChartAccount::orderBy('account_code')->get();

        return view('intangible.categories.create', compact('accounts'));
    }

    /**
     * Store a new intangible asset category.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'type' => 'required|in:purchased,internally_developed,goodwill,indefinite_life',
            'is_goodwill' => 'sometimes|boolean',
            'is_indefinite_life' => 'sometimes|boolean',
            'cost_account_id' => 'required|exists:chart_accounts,id',
            'accumulated_amortisation_account_id' => 'nullable|exists:chart_accounts,id',
            'accumulated_impairment_account_id' => 'nullable|exists:chart_accounts,id',
            'amortisation_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'impairment_loss_account_id' => 'nullable|exists:chart_accounts,id',
            'disposal_gain_loss_account_id' => 'nullable|exists:chart_accounts,id',
        ]);

        // Remove flags from category - they will be set at asset level
        unset($validated['is_goodwill'], $validated['is_indefinite_life']);

        IntangibleAssetCategory::create(array_merge($validated, [
            'company_id' => $user->company_id,
        ]));

        return redirect()
            ->route('assets.intangible.categories.index')
            ->with('success', 'Intangible asset category created successfully.');
    }
}


