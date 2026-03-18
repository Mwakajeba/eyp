<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Assets\AssetCategory;
use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RevaluationSettingsController extends Controller
{
    /**
     * Display revaluation and impairment settings
     */
    public function index()
    {
        // TODO: Add proper authorization policy
        // $this->authorize('view asset settings');

        $companyId = Auth::user()->company_id;
        
        // Get all asset categories
        $categories = AssetCategory::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        // Get chart accounts for dropdowns by joining with account classes
        $equityAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereHas('accountClass', function($subQ) {
                      $subQ->whereRaw('LOWER(name) LIKE ?', ['%equity%']);
                  });
            })
            ->orWhere(function($q) use ($companyId) {
                $q->where('has_equity', true)
                  ->whereHas('accountClassGroup', function($subQ) use ($companyId) {
                      $subQ->where('company_id', $companyId);
                  });
            })
            ->orderBy('account_name')
            ->get();

        $expenseAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereHas('accountClass', function($subQ) {
                      $subQ->whereRaw('LOWER(name) LIKE ?', ['%expense%']);
                  });
            })
            ->orderBy('account_name')
            ->get();

        $incomeAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereHas('accountClass', function($subQ) {
                      $subQ->whereRaw('LOWER(name) IN (?, ?)', ['income', 'revenue']);
                  });
            })
            ->orderBy('account_name')
            ->get();

        // Get accounts that might be used for impairment
        $impairmentLossAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereHas('accountClass', function($subQ) {
                      $subQ->whereRaw('LOWER(name) LIKE ?', ['%expense%']);
                  });
            })
            ->where(function($q) {
                $q->whereRaw('LOWER(account_name) LIKE ?', ['%impairment%'])
                  ->orWhereRaw('LOWER(account_name) LIKE ?', ['%loss%']);
            })
            ->orderBy('account_name')
            ->get();

        $impairmentReversalAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereHas('accountClass', function($subQ) {
                      $subQ->whereRaw('LOWER(name) IN (?, ?)', ['income', 'revenue']);
                  });
            })
            ->where(function($q) {
                $q->whereRaw('LOWER(account_name) LIKE ?', ['%impairment%'])
                  ->orWhereRaw('LOWER(account_name) LIKE ?', ['%reversal%']);
            })
            ->orderBy('account_name')
            ->get();

        // Get accounts for revaluation loss (expense accounts, typically for revaluation decreases)
        $revaluationLossAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereHas('accountClass', function($subQ) {
                      $subQ->whereRaw('LOWER(name) LIKE ?', ['%expense%']);
                  });
            })
            ->where(function($q) {
                $q->whereRaw('LOWER(account_name) LIKE ?', ['%revaluation%'])
                  ->orWhereRaw('LOWER(account_name) LIKE ?', ['%revaluation loss%'])
                  ->orWhereRaw('LOWER(account_name) LIKE ?', ['%loss%']);
            })
            ->orderBy('account_name')
            ->get();

        return view('assets.revaluations.settings', compact(
            'categories',
            'equityAccounts',
            'expenseAccounts',
            'incomeAccounts',
            'impairmentLossAccounts',
            'impairmentReversalAccounts',
            'revaluationLossAccounts'
        ));
    }

    /**
     * Update revaluation settings for a category
     */
    public function updateCategory(Request $request, $categoryId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('edit asset settings');

        $category = AssetCategory::where('company_id', Auth::user()->company_id)
            ->findOrFail($categoryId);

        $validated = $request->validate([
            'default_valuation_model' => 'required|in:cost,revaluation',
            'revaluation_frequency' => 'nullable|in:annual,biennial,ad_hoc',
            'revaluation_interval_years' => 'nullable|integer|min:1|max:10',
            'revaluation_reserve_account_id' => 'nullable|exists:chart_accounts,id',
            'revaluation_loss_account_id' => 'nullable|exists:chart_accounts,id',
            'impairment_loss_account_id' => 'nullable|exists:chart_accounts,id',
            'impairment_reversal_account_id' => 'nullable|exists:chart_accounts,id',
            'accumulated_impairment_account_id' => 'nullable|exists:chart_accounts,id',
            'require_valuation_report' => 'nullable|boolean',
            'require_approval' => 'nullable|boolean',
            'min_approval_levels' => 'nullable|integer|min:1|max:2',
        ]);

        // Handle checkbox values (they may come as 1/0 or true/false)
        if (isset($validated['require_valuation_report'])) {
            $validated['require_valuation_report'] = filter_var($validated['require_valuation_report'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($validated['require_approval'])) {
            $validated['require_approval'] = filter_var($validated['require_approval'], FILTER_VALIDATE_BOOLEAN);
        }

        DB::beginTransaction();
        try {
            // Use existing value as default if not provided
            $minApprovalLevels = $validated['min_approval_levels'] ?? $category->min_approval_levels ?? 2;
            
            $category->update([
                'default_valuation_model' => $validated['default_valuation_model'],
                'revaluation_frequency' => $validated['revaluation_frequency'] ?? null,
                'revaluation_interval_years' => $validated['revaluation_interval_years'] ?? null,
                'revaluation_reserve_account_id' => $validated['revaluation_reserve_account_id'] ?? null,
                'revaluation_loss_account_id' => $validated['revaluation_loss_account_id'] ?? null,
                'impairment_loss_account_id' => $validated['impairment_loss_account_id'] ?? null,
                'impairment_reversal_account_id' => $validated['impairment_reversal_account_id'] ?? null,
                'accumulated_impairment_account_id' => $validated['accumulated_impairment_account_id'] ?? null,
                'require_valuation_report' => $validated['require_valuation_report'] ?? false,
                'require_approval' => $validated['require_approval'] ?? true,
                'min_approval_levels' => $minApprovalLevels,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Settings updated successfully'
                ]);
            }

            return redirect()->route('assets.revaluations.settings')
                ->with('success', 'Revaluation settings updated for ' . $category->name);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update settings: ' . $e->getMessage()
                ], 422);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }

    /**
     * Update settings for multiple categories at once
     */
    public function updateBulk(Request $request)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('edit asset settings');

        // Normalize boolean values before validation
        // Handle cases where booleans might be sent as strings, integers, or actual booleans
        $categories = $request->input('categories', []);
        foreach ($categories as $index => $category) {
            // Normalize require_valuation_report
            if (isset($category['require_valuation_report'])) {
                $value = $category['require_valuation_report'];
                if (is_string($value)) {
                    $value = strtolower($value);
                    $categories[$index]['require_valuation_report'] = in_array($value, ['true', '1', 'yes', 'on'], true);
                } elseif (is_numeric($value)) {
                    $categories[$index]['require_valuation_report'] = (bool) $value;
                } else {
                    $categories[$index]['require_valuation_report'] = (bool) $value;
                }
            }
            
            // Normalize require_approval
            if (isset($category['require_approval'])) {
                $value = $category['require_approval'];
                if (is_string($value)) {
                    $value = strtolower($value);
                    $categories[$index]['require_approval'] = in_array($value, ['true', '1', 'yes', 'on'], true);
                } elseif (is_numeric($value)) {
                    $categories[$index]['require_approval'] = (bool) $value;
                } else {
                    $categories[$index]['require_approval'] = (bool) $value;
                }
            }
        }
        $request->merge(['categories' => $categories]);

        // Validate after normalization (booleans are now proper booleans)
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:asset_categories,id',
            'categories.*.default_valuation_model' => 'required|in:cost,revaluation',
            'categories.*.revaluation_frequency' => 'nullable|in:annual,biennial,ad_hoc',
            'categories.*.revaluation_interval_years' => 'nullable|integer|min:1|max:10',
            'categories.*.revaluation_reserve_account_id' => 'nullable|exists:chart_accounts,id',
            'categories.*.revaluation_loss_account_id' => 'nullable|exists:chart_accounts,id',
            'categories.*.impairment_loss_account_id' => 'nullable|exists:chart_accounts,id',
            'categories.*.impairment_reversal_account_id' => 'nullable|exists:chart_accounts,id',
            'categories.*.accumulated_impairment_account_id' => 'nullable|exists:chart_accounts,id',
            'categories.*.require_valuation_report' => 'nullable|boolean',
            'categories.*.require_approval' => 'nullable|boolean',
            'categories.*.min_approval_levels' => 'nullable|integer|min:1|max:2',
        ]);

        DB::beginTransaction();
        try {
            $companyId = Auth::user()->company_id;
            $updated = 0;

            foreach ($validated['categories'] as $categoryData) {
                $category = AssetCategory::where('company_id', $companyId)
                    ->findOrFail($categoryData['id']);

                // Use existing value as default if not provided
                $minApprovalLevels = $categoryData['min_approval_levels'] ?? $category->min_approval_levels ?? 2;

                // Handle boolean values - convert string/numbers to proper booleans
                $requireValuationReport = isset($categoryData['require_valuation_report']) 
                    ? filter_var($categoryData['require_valuation_report'], FILTER_VALIDATE_BOOLEAN)
                    : ($category->require_valuation_report ?? false);
                
                $requireApproval = isset($categoryData['require_approval'])
                    ? filter_var($categoryData['require_approval'], FILTER_VALIDATE_BOOLEAN)
                    : ($category->require_approval ?? true);

                $category->update([
                    'default_valuation_model' => $categoryData['default_valuation_model'],
                    'revaluation_frequency' => $categoryData['revaluation_frequency'] ?? null,
                    'revaluation_interval_years' => $categoryData['revaluation_interval_years'] ?? null,
                    'revaluation_reserve_account_id' => $categoryData['revaluation_reserve_account_id'] ?? null,
                    'revaluation_loss_account_id' => $categoryData['revaluation_loss_account_id'] ?? null,
                    'impairment_loss_account_id' => $categoryData['impairment_loss_account_id'] ?? null,
                    'impairment_reversal_account_id' => $categoryData['impairment_reversal_account_id'] ?? null,
                    'accumulated_impairment_account_id' => $categoryData['accumulated_impairment_account_id'] ?? null,
                    'require_valuation_report' => $requireValuationReport,
                    'require_approval' => $requireApproval,
                    'min_approval_levels' => $minApprovalLevels,
                    'updated_by' => Auth::id(),
                ]);

                $updated++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Settings updated for {$updated} categories"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 422);
        }
    }
}
