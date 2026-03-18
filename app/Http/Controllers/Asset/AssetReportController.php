<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetCategory;
use App\Models\Assets\AssetDepreciation;
use App\Models\Assets\AssetRevaluation;
use App\Models\Assets\AssetImpairment;
use App\Exports\FixedAssetRegisterExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class AssetReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    /**
     * Display the asset reports index page
     */
    public function index()
    {
        return view('assets.reports.index');
    }

    /**
     * Display Fixed Asset Register
     */
    public function register()
    {
        $categories = AssetCategory::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();

        return view('assets.reports.register', compact('categories'));
    }

    /**
     * Get Fixed Asset Register data
     */
    public function registerData(Request $request)
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', Carbon::now()->format('Y-m-d')));
        $categoryId = $request->input('asset_category_id');
        $status = $request->input('status');

        $assets = Asset::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->when($categoryId, fn($q) => $q->where('asset_category_id', $categoryId))
            ->when($status, fn($q) => $q->where('status', $status))
            ->with(['category', 'custodian'])
            ->get();

        $data = [];
        $summary = [
            'total_cost' => 0,
            'total_accumulated_dep' => 0,
            'total_nbv' => 0,
            'count' => 0
        ];

        foreach ($assets as $asset) {
            // Get accumulated depreciation as of date
            $accumDep = AssetDepreciation::where('asset_id', $asset->id)
                ->where('depreciation_type', 'book')
                ->where('depreciation_date', '<=', $asOfDate)
                ->sum('depreciation_amount');

            // Get impairment amount
            $impairment = $asset->impairments()
                ->where('impairment_date', '<=', $asOfDate)
                ->sum('impairment_loss') ?? 0;

            // Calculate carrying amount
            $carryingAmount = $asset->purchase_cost - $accumDep - $impairment;

            $data[] = [
                'id' => $asset->id,
                'code' => $asset->code,
                'name' => $asset->name,
                'category' => $asset->category,
                'location' => $asset->location,
                'custodian' => $asset->custodian,
                'serial_number' => $asset->serial_number,
                'purchase_date' => $asset->purchase_date ? $asset->purchase_date->format('Y-m-d') : null,
                'capitalization_date' => $asset->capitalization_date ? $asset->capitalization_date->format('Y-m-d') : null,
                'purchase_cost' => $asset->purchase_cost,
                'useful_life' => $asset->category->useful_life ?? null,
                'depreciation_method_display' => $asset->category->depreciation_method ?? 'N/A',
                'accumulated_depreciation' => $accumDep,
                'impairment_amount' => $impairment,
                'carrying_amount' => $carryingAmount,
                'status' => $asset->status,
                'status_display' => ucwords(str_replace('_', ' ', $asset->status ?? 'N/A'))
            ];

            $summary['total_cost'] += $asset->purchase_cost;
            $summary['total_accumulated_dep'] += $accumDep;
            $summary['total_nbv'] += $carryingAmount;
            $summary['count']++;
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => $summary,
            'as_of_date' => $asOfDate->format('Y-m-d'),
        ]);
    }

    /**
     * Export Fixed Asset Register to Excel
     */
    public function registerExport(Request $request)
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', Carbon::now()->format('Y-m-d')));
        $categoryId = $request->input('asset_category_id');
        $status = $request->input('status');

        // Get data using same logic as registerData
        $assets = Asset::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->when($categoryId, fn($q) => $q->where('asset_category_id', $categoryId))
            ->when($status, fn($q) => $q->where('status', $status))
            ->with(['category', 'custodian'])
            ->get();

        $data = [];
        foreach ($assets as $asset) {
            $accumDep = AssetDepreciation::where('asset_id', $asset->id)
                ->where('depreciation_type', 'book')
                ->where('depreciation_date', '<=', $asOfDate)
                ->sum('depreciation_amount');

            $impairment = $asset->impairments()
                ->where('impairment_date', '<=', $asOfDate)
                ->sum('impairment_loss') ?? 0;

            $carryingAmount = $asset->purchase_cost - $accumDep - $impairment;

            $data[] = [
                'code' => $asset->code,
                'name' => $asset->name,
                'category' => $asset->category->name ?? 'N/A',
                'location' => $asset->location,
                'custodian' => $asset->custodian->name ?? '-',
                'serial_number' => $asset->serial_number,
                'purchase_date' => $asset->purchase_date ? $asset->purchase_date->format('Y-m-d') : '-',
                'capitalization_date' => $asset->capitalization_date ? $asset->capitalization_date->format('Y-m-d') : '-',
                'purchase_cost' => $asset->purchase_cost,
                'useful_life' => $asset->category->useful_life ?? 0,
                'depreciation_method' => $asset->category->depreciation_method ?? 'N/A',
                'accumulated_depreciation' => $accumDep,
                'impairment_amount' => $impairment,
                'carrying_amount' => $carryingAmount,
                'status' => ucwords(str_replace('_', ' ', $asset->status ?? 'N/A'))
            ];
        }

        if (empty($data)) {
            return back()->with('error', 'No data available for the selected criteria.');
        }

        $fileName = 'Fixed_Asset_Register_' . $asOfDate->format('Ymd') . '_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new FixedAssetRegisterExport($data, $asOfDate->format('Y-m-d')), $fileName);
    }

    /**
     * Export Fixed Asset Register to PDF
     */
    public function registerExportPdf(Request $request)
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', Carbon::now()->format('Y-m-d')));
        $categoryId = $request->input('asset_category_id');
        $status = $request->input('status');

        // Get data using same logic as registerData
        $assets = Asset::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->when($categoryId, fn($q) => $q->where('asset_category_id', $categoryId))
            ->when($status, fn($q) => $q->where('status', $status))
            ->with(['category', 'custodian'])
            ->get();

        $data = [];
        $summary = [
            'total_cost' => 0,
            'total_accumulated_dep' => 0,
            'total_nbv' => 0,
            'count' => 0
        ];

        foreach ($assets as $asset) {
            $accumDep = AssetDepreciation::where('asset_id', $asset->id)
                ->where('depreciation_type', 'book')
                ->where('depreciation_date', '<=', $asOfDate)
                ->sum('depreciation_amount');

            $impairment = $asset->impairments()
                ->where('impairment_date', '<=', $asOfDate)
                ->sum('impairment_loss') ?? 0;

            $carryingAmount = $asset->purchase_cost - $accumDep - $impairment;

            $data[] = [
                'code' => $asset->code,
                'name' => $asset->name,
                'category' => $asset->category->name ?? 'N/A',
                'location' => $asset->location,
                'custodian' => $asset->custodian->name ?? '-',
                'serial_number' => $asset->serial_number,
                'purchase_date' => $asset->purchase_date ? $asset->purchase_date->format('Y-m-d') : '-',
                'capitalization_date' => $asset->capitalization_date ? $asset->capitalization_date->format('Y-m-d') : '-',
                'purchase_cost' => $asset->purchase_cost,
                'useful_life' => $asset->category->useful_life ?? 0,
                'depreciation_method' => $asset->category->depreciation_method ?? 'N/A',
                'accumulated_depreciation' => $accumDep,
                'impairment_amount' => $impairment,
                'carrying_amount' => $carryingAmount,
                'status' => ucwords(str_replace('_', ' ', $asset->status ?? 'N/A'))
            ];

            $summary['total_cost'] += $asset->purchase_cost;
            $summary['total_accumulated_dep'] += $accumDep;
            $summary['total_nbv'] += $carryingAmount;
            $summary['count']++;
        }

        if (empty($data)) {
            return back()->with('error', 'No data available for the selected criteria.');
        }

        $company = Auth::user()->company;
        $branch = Auth::user()->branch;
        $preparedBy = Auth::user()->name;
        $generatedDate = Carbon::now()->format('Y-m-d H:i:s');

        $pdf = Pdf::loadView('assets.reports.exports.register-pdf', compact(
            'data', 'summary', 'asOfDate', 'company', 'branch', 'preparedBy', 'generatedDate'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('fixed-asset-register-' . $asOfDate->format('Y-m-d') . '-' . Carbon::now()->format('YmdHis') . '.pdf');
    }

    /**
     * Display Asset Movement Schedule
     */
    public function movementSchedule()
    {
        $categories = AssetCategory::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();

        return view('assets.reports.movement-schedule', compact('categories'));
    }

    /**
     * Get Asset Movement Schedule data
     */
    public function movementScheduleData(Request $request)
    {
        $fromDate = Carbon::parse($request->input('from_date'));
        $toDate = Carbon::parse($request->input('to_date'));
        $categoryId = $request->input('asset_category_id');

        $categories = AssetCategory::where('company_id', Auth::user()->company_id)
            ->when($categoryId, fn($q) => $q->where('id', $categoryId))
            ->orderBy('name')
            ->get();

        $data = [];

        foreach ($categories as $category) {
            // Get all assets in this category
            $assets = Asset::where('company_id', Auth::user()->company_id)
                ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
                ->where('asset_category_id', $category->id)
                ->get();

            if ($assets->isEmpty()) {
                continue;
            }

            // Calculate opening balances (as of day before fromDate)
            $openingDate = $fromDate->copy()->subDay();
            $opening_cost = 0;
            $opening_accum_dep = 0;

            foreach ($assets as $asset) {
                if ($asset->capitalization_date && $asset->capitalization_date->lte($openingDate)) {
                    $opening_cost += $asset->purchase_cost;
                    
                    $accumDep = AssetDepreciation::where('asset_id', $asset->id)
                        ->where('depreciation_type', 'book')
                        ->where('depreciation_date', '<=', $openingDate)
                        ->sum('depreciation_amount');
                    $opening_accum_dep += $accumDep;
                }
            }

            // Additions during period
            $additions = Asset::where('company_id', Auth::user()->company_id)
                ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
                ->where('asset_category_id', $category->id)
                ->whereBetween('capitalization_date', [$fromDate, $toDate])
                ->sum('purchase_cost');

            // Disposals during period
            $disposals = 0;
            $disposal_dep_removed = 0;
            $disposedAssets = Asset::where('company_id', Auth::user()->company_id)
                ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
                ->where('asset_category_id', $category->id)
                ->where('status', 'disposed')
                ->with('disposals')
                ->get();

            foreach ($disposedAssets as $asset) {
                $disposal = $asset->disposals()
                    ->whereBetween('actual_disposal_date', [$fromDate, $toDate])
                    ->first();
                
                if ($disposal) {
                    $disposals += $asset->purchase_cost;
                    
                    $accumDep = AssetDepreciation::where('asset_id', $asset->id)
                        ->where('depreciation_type', 'book')
                        ->where('depreciation_date', '<=', $disposal->actual_disposal_date)
                        ->sum('depreciation_amount');
                    $disposal_dep_removed += $accumDep;
                }
            }

            // Depreciation charge during period
            $depreciation_charge = AssetDepreciation::whereHas('asset', function($q) use ($category) {
                    $q->where('company_id', Auth::user()->company_id)
                        ->when(Auth::user()->branch_id, fn($q2) => $q2->where('branch_id', Auth::user()->branch_id))
                        ->where('asset_category_id', $category->id);
                })
                ->where('depreciation_type', 'book')
                ->whereBetween('depreciation_date', [$fromDate, $toDate])
                ->sum('depreciation_amount');

            // Transfers (placeholder - implement based on your transfer logic)
            $transfers = 0;

            // Revaluation (placeholder - implement based on your revaluation logic)
            $revaluation = 0;

            // Impairment during period
            $impairment = 0;
            foreach ($assets as $asset) {
                $impairment += $asset->impairments()
                    ->whereBetween('impairment_date', [$fromDate, $toDate])
                    ->sum('impairment_loss');
            }

            // Calculate closing balances
            $closing_cost = $opening_cost + $additions - $disposals + $transfers + $revaluation;
            $closing_accum_dep = $opening_accum_dep + $depreciation_charge - $disposal_dep_removed + $impairment;
            $closing_nbv = $closing_cost - $closing_accum_dep;

            $data[] = [
                'category_name' => $category->name,
                'opening_cost' => $opening_cost,
                'additions' => $additions,
                'disposals' => $disposals,
                'transfers' => $transfers,
                'revaluation' => $revaluation,
                'closing_cost' => $closing_cost,
                'opening_accum_dep' => $opening_accum_dep,
                'depreciation_charge' => $depreciation_charge,
                'disposal_dep_removed' => $disposal_dep_removed,
                'impairment' => $impairment,
                'closing_accum_dep' => $closing_accum_dep,
                'closing_nbv' => $closing_nbv,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Export Asset Movement Schedule to Excel
     */
    public function movementScheduleExport(Request $request)
    {
        // Reuse the data logic
        $fromDate = Carbon::parse($request->input('from_date'));
        $toDate = Carbon::parse($request->input('to_date'));
        
        // Get data (simplified - in production, extract this to a shared method)
        $response = $this->movementScheduleData($request);
        $data = json_decode($response->getContent(), true)['data'];

        if (empty($data)) {
            return back()->with('error', 'No data available for the selected period.');
        }

        $fileName = 'Asset_Movement_Schedule_' . $fromDate->format('Ymd') . '_to_' . $toDate->format('Ymd') . '_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new \App\Exports\AssetMovementScheduleExport($data, $fromDate->format('Y-m-d'), $toDate->format('Y-m-d')), $fileName);
    }

    /**
     * Export Asset Movement Schedule to PDF
     */
    public function movementScheduleExportPdf(Request $request)
    {
        $fromDate = Carbon::parse($request->input('from_date'));
        $toDate = Carbon::parse($request->input('to_date'));
        
        $response = $this->movementScheduleData($request);
        $data = json_decode($response->getContent(), true)['data'];

        if (empty($data)) {
            return back()->with('error', 'No data available for the selected period.');
        }

        $company = Auth::user()->company;
        $branch = Auth::user()->branch;
        $preparedBy = Auth::user()->name;
        $generatedDate = Carbon::now()->format('Y-m-d H:i:s');

        $pdf = Pdf::loadView('assets.reports.exports.movement-schedule-pdf', compact(
            'data', 'fromDate', 'toDate', 'company', 'branch', 'preparedBy', 'generatedDate'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('asset-movement-schedule-' . $fromDate->format('Ymd') . '-to-' . $toDate->format('Ymd') . '-' . Carbon::now()->format('YmdHis') . '.pdf');
    }

    /**
     * Display GL Reconciliation Report
     */
    public function glReconciliation()
    {
        $categories = AssetCategory::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();

        return view('assets.reports.gl-reconciliation', compact('categories'));
    }

    /**
     * Get GL Reconciliation data
     */
    public function glReconciliationData(Request $request)
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', Carbon::now()->format('Y-m-d')));
        $categoryId = $request->input('category_id');

        // Get all assets
        $assets = Asset::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->when($categoryId, fn($q) => $q->where('asset_category_id', $categoryId))
            ->with(['category'])
            ->get();

        // Calculate subledger balances by GL account
        $subledgerBalances = [];
        
        foreach ($assets as $asset) {
            $category = $asset->category;
            if (!$category) continue;

            // Asset Cost Account
            if ($category->asset_account_id) {
                if (!isset($subledgerBalances[$category->asset_account_id])) {
                    $subledgerBalances[$category->asset_account_id] = [
                        'gl_account_id' => $category->asset_account_id,
                        'account_type' => 'Asset Cost',
                        'balance' => 0
                    ];
                }
                $subledgerBalances[$category->asset_account_id]['balance'] += $asset->purchase_cost;
            }

            // Accumulated Depreciation Account
            if ($category->accumulated_depreciation_account_id) {
                if (!isset($subledgerBalances[$category->accumulated_depreciation_account_id])) {
                    $subledgerBalances[$category->accumulated_depreciation_account_id] = [
                        'gl_account_id' => $category->accumulated_depreciation_account_id,
                        'account_type' => 'Accumulated Depreciation',
                        'balance' => 0
                    ];
                }
                
                // Get accumulated depreciation for this asset
                $accumDep = AssetDepreciation::where('asset_id', $asset->id)
                    ->where('depreciation_type', 'book')
                    ->where('depreciation_date', '<=', $asOfDate)
                    ->sum('depreciation_amount');
                
                $subledgerBalances[$category->accumulated_depreciation_account_id]['balance'] += $accumDep;
            }
        }

        $data = [];
        $summary = [
            'total_gl' => 0,
            'total_subledger' => 0,
            'total_variance' => 0
        ];

        foreach ($subledgerBalances as $accountId => $subledger) {
            $glAccount = \App\Models\ChartAccount::find($accountId);
            if (!$glAccount) continue;

            // Get GL balance - this would ideally come from journal entries
            // For now, we'll use the chart account's opening balance + transactions
            $glBalance = $this->getGLAccountBalance($accountId, $asOfDate);

            $difference = $glBalance - $subledger['balance'];

            $data[] = [
                'gl_account_code' => $glAccount->code,
                'gl_account_name' => $glAccount->name,
                'account_type' => $subledger['account_type'],
                'gl_balance' => $glBalance,
                'subledger_balance' => $subledger['balance'],
                'difference' => $difference,
            ];

            $summary['total_gl'] += $glBalance;
            $summary['total_subledger'] += $subledger['balance'];
            $summary['total_variance'] += $difference;
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => $summary,
            'as_of_date' => $asOfDate->format('Y-m-d'),
        ]);
    }

    /**
     * Get GL Account Balance as of date
     */
    private function getGLAccountBalance($accountId, $asOfDate)
    {
        $account = \App\Models\ChartAccount::find($accountId);
        if (!$account) return 0;

        // Get opening balance
        $balance = $account->opening_balance ?? 0;

        // Add all GL transactions up to the date
        $glTransactions = \App\Models\GlTransaction::where('chart_account_id', $accountId)
            ->where('date', '<=', $asOfDate)
            ->get();

        foreach ($glTransactions as $transaction) {
            if ($transaction->nature == 'debit') {
                // Debit increases asset/expense accounts, decreases liability/equity/income accounts
                if (in_array($account->account_type_side, ['debit', 'asset', 'expense'])) {
                    $balance += $transaction->amount;
                } else {
                    $balance -= $transaction->amount;
                }
            } else {
                // Credit increases liability/equity/income accounts, decreases asset/expense accounts
                if (in_array($account->account_type_side, ['credit', 'liability', 'equity', 'income'])) {
                    $balance += $transaction->amount;
                } else {
                    $balance -= $transaction->amount;
                }
            }
        }

        return $balance;
    }

    /**
     * Export GL Reconciliation to Excel
     */
    public function glReconciliationExport(Request $request)
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', Carbon::now()->format('Y-m-d')));
        $categoryId = $request->input('category_id');

        $response = $this->glReconciliationData($request);
        $responseData = json_decode($response->getContent(), true);
        $data = $responseData['data'];
        $summary = $responseData['summary'];

        if (empty($data)) {
            return back()->with('error', 'No data available for the selected criteria.');
        }

        $fileName = 'GL_Reconciliation_' . $asOfDate->format('Ymd') . '_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new \App\Exports\GLReconciliationExport($data, $summary, $asOfDate->format('Y-m-d')), $fileName);
    }

    /**
     * Export GL Reconciliation to PDF
     */
    public function glReconciliationExportPdf(Request $request)
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', Carbon::now()->format('Y-m-d')));
        $categoryId = $request->input('category_id');

        $response = $this->glReconciliationData($request);
        $responseData = json_decode($response->getContent(), true);
        $data = $responseData['data'];
        $summary = $responseData['summary'];

        if (empty($data)) {
            return back()->with('error', 'No data available for the selected criteria.');
        }

        $company = Auth::user()->company;
        $branch = Auth::user()->branch;
        $preparedBy = Auth::user()->name;
        $generatedDate = Carbon::now()->format('Y-m-d H:i:s');

        $pdf = Pdf::loadView('assets.reports.exports.gl-reconciliation-pdf', compact(
            'data', 'summary', 'asOfDate', 'company', 'branch', 'preparedBy', 'generatedDate'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('gl-reconciliation-' . $asOfDate->format('Ymd') . '-' . Carbon::now()->format('YmdHis') . '.pdf');
    }

    /**
     * Display Depreciation Expense Report
     */
    public function depreciationExpense()
    {
        $categories = AssetCategory::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();

        return view('assets.reports.depreciation-expense', compact('categories'));
    }

    /**
     * Get Depreciation Expense data
     */
    public function depreciationExpenseData(Request $request)
    {
        $fromDate = Carbon::parse($request->input('from_date'));
        $toDate = Carbon::parse($request->input('to_date'));
        $categoryId = $request->input('category_id');

        // Get all assets
        $assets = Asset::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->when($categoryId, fn($q) => $q->where('asset_category_id', $categoryId))
            ->with(['category'])
            ->get();

        $data = [];
        $summary = [
            'total_cost' => 0,
            'total_opening_nbv' => 0,
            'total_depreciation' => 0,
            'total_accumulated' => 0,
            'total_closing_nbv' => 0,
            'asset_count' => 0,
            'avg_per_asset' => 0,
            'monthly_avg' => 0
        ];

        foreach ($assets as $asset) {
            // Get depreciation for this period
            $periodDepreciation = AssetDepreciation::where('asset_id', $asset->id)
                ->where('depreciation_type', 'book')
                ->whereBetween('depreciation_date', [$fromDate, $toDate])
                ->sum('depreciation_amount');

            // Skip if no depreciation in period
            if ($periodDepreciation <= 0) {
                continue;
            }

            // Opening NBV (as of day before from_date)
            $openingDate = $fromDate->copy()->subDay();
            $accumDepBeforePeriod = AssetDepreciation::where('asset_id', $asset->id)
                ->where('depreciation_type', 'book')
                ->where('depreciation_date', '<=', $openingDate)
                ->sum('depreciation_amount');
            
            $openingNbv = $asset->purchase_cost - $accumDepBeforePeriod;

            // Total accumulated depreciation (as of to_date)
            $totalAccumDep = AssetDepreciation::where('asset_id', $asset->id)
                ->where('depreciation_type', 'book')
                ->where('depreciation_date', '<=', $toDate)
                ->sum('depreciation_amount');

            // Closing NBV
            $closingNbv = $asset->purchase_cost - $totalAccumDep;

            // Calculate depreciation rate
            $depreciationRate = 0;
            if ($asset->category && $asset->category->depreciation_method == 'straight_line' && $asset->category->useful_life > 0) {
                $depreciationRate = (100 / $asset->category->useful_life);
            } elseif ($asset->category && $asset->category->depreciation_method == 'declining_balance') {
                $depreciationRate = $asset->category->depreciation_rate ?? 0;
            }

            $data[] = [
                'asset_code' => $asset->code,
                'asset_name' => $asset->name,
                'category_name' => $asset->category->name ?? 'N/A',
                'cost' => $asset->purchase_cost,
                'opening_nbv' => $openingNbv,
                'depreciation_rate' => round($depreciationRate, 2),
                'period_depreciation' => $periodDepreciation,
                'accumulated_depreciation' => $totalAccumDep,
                'closing_nbv' => $closingNbv,
            ];

            $summary['total_cost'] += $asset->purchase_cost;
            $summary['total_opening_nbv'] += $openingNbv;
            $summary['total_depreciation'] += $periodDepreciation;
            $summary['total_accumulated'] += $totalAccumDep;
            $summary['total_closing_nbv'] += $closingNbv;
            $summary['asset_count']++;
        }

        // Calculate averages
        if ($summary['asset_count'] > 0) {
            $summary['avg_per_asset'] = $summary['total_depreciation'] / $summary['asset_count'];
        }

        // Calculate monthly average
        $months = $fromDate->diffInMonths($toDate) + 1;
        if ($months > 0) {
            $summary['monthly_avg'] = $summary['total_depreciation'] / $months;
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => $summary,
        ]);
    }

    /**
     * Export Depreciation Expense to Excel
     */
    public function depreciationExpenseExport(Request $request)
    {
        $fromDate = Carbon::parse($request->input('from_date'));
        $toDate = Carbon::parse($request->input('to_date'));
        
        $response = $this->depreciationExpenseData($request);
        $responseData = json_decode($response->getContent(), true);
        $data = $responseData['data'];
        $summary = $responseData['summary'];

        if (empty($data)) {
            return back()->with('error', 'No depreciation data available for the selected period.');
        }

        $fileName = 'Depreciation_Expense_' . $fromDate->format('Ymd') . '_to_' . $toDate->format('Ymd') . '_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new \App\Exports\DepreciationExpenseExport($data, $summary, $fromDate->format('Y-m-d'), $toDate->format('Y-m-d')), $fileName);
    }

    /**
     * Export Depreciation Expense to PDF
     */
    public function depreciationExpenseExportPdf(Request $request)
    {
        $fromDate = Carbon::parse($request->input('from_date'));
        $toDate = Carbon::parse($request->input('to_date'));
        
        $response = $this->depreciationExpenseData($request);
        $responseData = json_decode($response->getContent(), true);
        $data = $responseData['data'];
        $summary = $responseData['summary'];

        if (empty($data)) {
            return back()->with('error', 'No depreciation data available for the selected period.');
        }

        $company = Auth::user()->company;
        $branch = Auth::user()->branch;
        $preparedBy = Auth::user()->name;
        $generatedDate = Carbon::now()->format('Y-m-d H:i:s');

        $pdf = Pdf::loadView('assets.reports.exports.depreciation-expense-pdf', compact(
            'data', 'summary', 'fromDate', 'toDate', 'company', 'branch', 'preparedBy', 'generatedDate'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('depreciation-expense-' . $fromDate->format('Ymd') . '-to-' . $toDate->format('Ymd') . '-' . Carbon::now()->format('YmdHis') . '.pdf');
    }

    /**
     * Display Depreciation Schedule Report
     */
    public function depreciationSchedule()
    {
        $assets = Asset::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('assets.reports.depreciation-schedule', compact('assets'));
    }

    /**
     * Get Depreciation Schedule data
     */
    public function depreciationScheduleData(Request $request)
    {
        $assetId = $request->input('asset_id');
        $fromDate = Carbon::parse($request->input('from_date', Carbon::now()->startOfYear()->format('Y-m-d')));
        $toDate = Carbon::parse($request->input('to_date', Carbon::now()->format('Y-m-d')));

        $asset = Asset::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->with(['category', 'depreciations', 'impairments', 'revaluations'])
            ->findOrFail($assetId);

        $assetDetails = [
            'code' => $asset->code,
            'name' => $asset->name,
            'category' => $asset->category->name ?? 'N/A',
            'cost' => $asset->purchase_cost,
            'salvage_value' => $asset->salvage_value ?? 0,
            'useful_life' => $asset->category->useful_life ?? 0,
            'depreciation_method' => ucwords(str_replace('_', ' ', $asset->category->depreciation_method ?? 'N/A')),
            'purchase_date' => $asset->purchase_date ? $asset->purchase_date->format('Y-m-d') : 'N/A',
            'capitalization_date' => $asset->capitalization_date ? $asset->capitalization_date->format('Y-m-d') : 'N/A',
        ];

        $schedule = [];
        $currentDate = Carbon::parse($asset->capitalization_date ?? $asset->purchase_date);
        $endDate = Carbon::now();

        $usefulLifeMonths = ($asset->category->useful_life ?? 1) * 12;
        $endOfLife = $currentDate->copy()->addMonths($usefulLifeMonths);

        if ($toDate->lt($endOfLife)) {
            $endDate = $toDate;
        } else {
            $endDate = $endOfLife;
        }

        if ($fromDate->gt($currentDate)) {
            $currentDate = $fromDate->copy()->startOfMonth();
        }

        $runningNBV = $asset->purchase_cost;
        $totalDepreciation = 0;
        $totalRevaluation = 0;
        $totalImpairment = 0;

        while ($currentDate->lte($endDate)) {
            $monthStart = $currentDate->copy()->startOfMonth();
            $monthEnd = $currentDate->copy()->endOfMonth();

            $depreciation = AssetDepreciation::where('asset_id', $asset->id)
                ->where('depreciation_type', 'book')
                ->whereBetween('depreciation_date', [$monthStart, $monthEnd])
                ->sum('depreciation_amount') ?? 0;

            $revaluationIncrease = $asset->revaluations()
                ->whereBetween('revaluation_date', [$monthStart, $monthEnd])
                ->sum('revaluation_increase') ?? 0;
            
            $revaluationDecrease = $asset->revaluations()
                ->whereBetween('revaluation_date', [$monthStart, $monthEnd])
                ->sum('revaluation_decrease') ?? 0;
            
            $revaluation = $revaluationIncrease - $revaluationDecrease;

            $impairment = $asset->impairments()
                ->whereBetween('impairment_date', [$monthStart, $monthEnd])
                ->sum('impairment_loss') ?? 0;

            $openingNBV = $runningNBV;
            $closingNBV = $openingNBV - $depreciation + $revaluation - $impairment;

            if ($closingNBV < 0) {
                $closingNBV = 0;
            }

            $schedule[] = [
                'date' => $currentDate->format('M Y'),
                'opening_nbv' => $openingNBV,
                'depreciation' => $depreciation,
                'revaluation' => $revaluation,
                'impairment' => $impairment,
                'closing_nbv' => $closingNBV,
            ];

            $totalDepreciation += $depreciation;
            $totalRevaluation += $revaluation;
            $totalImpairment += $impairment;
            $runningNBV = $closingNBV;

            $currentDate->addMonth();

            if (count($schedule) > 600) {
                break;
            }
        }

        $remainingMonths = max(0, $usefulLifeMonths - ceil($endDate->diffInMonths($endOfLife)));

        $summary = [
            'total_depreciation' => $totalDepreciation,
            'total_revaluation' => $totalRevaluation,
            'total_impairment' => $totalImpairment,
            'current_nbv' => $runningNBV,
            'period_count' => count($schedule),
            'remaining_months' => $remainingMonths,
        ];

        return response()->json([
            'success' => true,
            'data' => $schedule,
            'asset_details' => $assetDetails,
            'summary' => $summary
        ]);
    }

    /**
     * Export Depreciation Schedule to Excel
     */
    public function depreciationScheduleExport(Request $request)
    {
        $response = $this->depreciationScheduleData($request);
        $responseData = $response->getData(true);

        if (!$responseData['success']) {
            return back()->with('error', 'Failed to generate schedule data.');
        }

        $data = $responseData['data'];
        $assetDetails = $responseData['asset_details'];
        $summary = $responseData['summary'];

        return Excel::download(
            new \App\Exports\DepreciationScheduleExport($data, $assetDetails, $summary),
            'depreciation-schedule-' . str_replace(' ', '-', $assetDetails['code']) . '-' . Carbon::now()->format('YmdHis') . '.xlsx'
        );
    }

    /**
     * Export Depreciation Schedule to PDF
     */
    public function depreciationScheduleExportPdf(Request $request)
    {
        $response = $this->depreciationScheduleData($request);
        $responseData = $response->getData(true);

        if (!$responseData['success']) {
            return back()->with('error', 'Failed to generate schedule data.');
        }

        $data = $responseData['data'];
        $assetDetails = $responseData['asset_details'];
        $summary = $responseData['summary'];
        $user = Auth::user();
        $company = $user->company;
        $branch = $user->branch;

        $pdf = Pdf::loadView('assets.reports.exports.depreciation-schedule-pdf', compact(
            'data',
            'assetDetails',
            'summary',
            'user',
            'company',
            'branch'
        ))
        ->setPaper('a4', 'landscape')
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif'
        ]);

        return $pdf->download('depreciation-schedule-' . str_replace(' ', '-', $assetDetails['code']) . '-' . Carbon::now()->format('YmdHis') . '.pdf');
    }

    /**
     * Display Asset Additions Report
     */
    public function additions()
    {
        $categories = AssetCategory::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();

        return view('assets.reports.additions', compact('categories'));
    }

    /**
     * Get Asset Additions data
     */
    public function additionsData(Request $request)
    {
        $fromDate = Carbon::parse($request->input('from_date', Carbon::now()->startOfYear()->format('Y-m-d')));
        $toDate = Carbon::parse($request->input('to_date', Carbon::now()->format('Y-m-d')));
        $categoryId = $request->input('category_id');

        $assets = Asset::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->when($categoryId, fn($q) => $q->where('asset_category_id', $categoryId))
            ->whereBetween('capitalization_date', [$fromDate, $toDate])
            ->with(['category', 'createdBy'])
            ->orderBy('capitalization_date', 'desc')
            ->get();

        $data = [];
        $summary = [
            'count' => $assets->count(),
            'total_amount' => 0,
            'average_amount' => 0
        ];

        foreach ($assets as $asset) {
            $data[] = [
                'asset_code' => $asset->code,
                'asset_name' => $asset->name,
                'category' => $asset->category->name ?? 'N/A',
                'invoice_no' => $asset->invoice_number ?? 'N/A',
                'vendor' => $asset->vendor_name ?? 'N/A',
                'purchase_date' => $asset->purchase_date ? $asset->purchase_date->format('Y-m-d') : 'N/A',
                'capitalized_date' => $asset->capitalization_date ? $asset->capitalization_date->format('Y-m-d') : 'N/A',
                'amount' => $asset->purchase_cost,
                'approved_by' => $asset->createdBy->name ?? 'N/A',
            ];

            $summary['total_amount'] += $asset->purchase_cost;
        }

        $summary['average_amount'] = $summary['count'] > 0 ? $summary['total_amount'] / $summary['count'] : 0;

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => $summary
        ]);
    }

    /**
     * Export Asset Additions to Excel
     */
    public function additionsExport(Request $request)
    {
        $response = $this->additionsData($request);
        $responseData = $response->getData(true);

        if (!$responseData['success']) {
            return back()->with('error', 'Failed to generate report data.');
        }

        $data = $responseData['data'];
        $summary = $responseData['summary'];
        $fromDate = Carbon::parse($request->input('from_date'));
        $toDate = Carbon::parse($request->input('to_date'));

        return Excel::download(
            new \App\Exports\AssetAdditionsExport($data, $summary, $fromDate, $toDate),
            'asset-additions-' . $fromDate->format('Ymd') . '-to-' . $toDate->format('Ymd') . '-' . Carbon::now()->format('YmdHis') . '.xlsx'
        );
    }

    /**
     * Export Asset Additions to PDF
     */
    public function additionsExportPdf(Request $request)
    {
        $response = $this->additionsData($request);
        $responseData = $response->getData(true);

        if (!$responseData['success']) {
            return back()->with('error', 'Failed to generate report data.');
        }

        $data = $responseData['data'];
        $summary = $responseData['summary'];
        $fromDate = Carbon::parse($request->input('from_date'));
        $toDate = Carbon::parse($request->input('to_date'));
        $user = Auth::user();
        $company = $user->company;
        $branch = $user->branch;

        $pdf = Pdf::loadView('assets.reports.exports.additions-pdf', compact(
            'data',
            'summary',
            'fromDate',
            'toDate',
            'user',
            'company',
            'branch'
        ))
        ->setPaper('a4', 'landscape')
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif'
        ]);

        return $pdf->download('asset-additions-' . $fromDate->format('Ymd') . '-to-' . $toDate->format('Ymd') . '-' . Carbon::now()->format('YmdHis') . '.pdf');
    }

    /**
     * Display Asset Disposals Report
     */
    public function disposals()
    {
        $categories = AssetCategory::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();

        return view('assets.reports.disposals', compact('categories'));
    }

    /**
     * Get Asset Disposals data
     */
    public function disposalsData(Request $request)
    {
        $fromDate = Carbon::parse($request->input('from_date', Carbon::now()->startOfYear()->format('Y-m-d')));
        $toDate = Carbon::parse($request->input('to_date', Carbon::now()->format('Y-m-d')));
        $categoryId = $request->input('category_id');

        $disposals = \App\Models\Assets\AssetDisposal::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->whereBetween('actual_disposal_date', [$fromDate, $toDate])
            ->whereNotNull('actual_disposal_date')
            ->with(['asset.category', 'approvedBy'])
            ->when($categoryId, function($q) use ($categoryId) {
                $q->whereHas('asset', fn($query) => $query->where('asset_category_id', $categoryId));
            })
            ->orderBy('actual_disposal_date', 'desc')
            ->get();

        $data = [];
        $summary = [
            'count' => $disposals->count(),
            'total_cost' => 0,
            'total_accumulated_dep' => 0,
            'total_carrying_amount' => 0,
            'total_proceeds' => 0,
            'net_gain_loss' => 0
        ];

        foreach ($disposals as $disposal) {
            $data[] = [
                'asset_code' => $disposal->asset->code ?? 'N/A',
                'asset_name' => $disposal->asset->name ?? 'N/A',
                'disposal_date' => $disposal->actual_disposal_date ? Carbon::parse($disposal->actual_disposal_date)->format('Y-m-d') : 'N/A',
                'disposal_method' => ucwords(str_replace('_', ' ', $disposal->disposal_type ?? 'N/A')),
                'cost' => $disposal->asset_cost ?? 0,
                'accumulated_depreciation' => $disposal->accumulated_depreciation ?? 0,
                'carrying_amount' => $disposal->net_book_value ?? 0,
                'proceeds' => $disposal->disposal_proceeds ?? 0,
                'gain_loss' => $disposal->gain_loss ?? 0,
                'approved_by' => $disposal->approvedBy->name ?? 'N/A',
            ];

            $summary['total_cost'] += $disposal->asset_cost ?? 0;
            $summary['total_accumulated_dep'] += $disposal->accumulated_depreciation ?? 0;
            $summary['total_carrying_amount'] += $disposal->net_book_value ?? 0;
            $summary['total_proceeds'] += $disposal->disposal_proceeds ?? 0;
            $summary['net_gain_loss'] += $disposal->gain_loss ?? 0;
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => $summary
        ]);
    }

    /**
     * Export Asset Disposals to Excel
     */
    public function disposalsExport(Request $request)
    {
        $response = $this->disposalsData($request);
        $responseData = $response->getData(true);

        if (!$responseData['success']) {
            return back()->with('error', 'Failed to generate report data.');
        }

        $data = $responseData['data'];
        $summary = $responseData['summary'];
        $fromDate = Carbon::parse($request->input('from_date'));
        $toDate = Carbon::parse($request->input('to_date'));

        return Excel::download(
            new \App\Exports\AssetDisposalsExport($data, $summary, $fromDate, $toDate),
            'asset-disposals-' . $fromDate->format('Ymd') . '-to-' . $toDate->format('Ymd') . '-' . Carbon::now()->format('YmdHis') . '.xlsx'
        );
    }

    /**
     * Export Asset Disposals to PDF
     */
    public function disposalsExportPdf(Request $request)
    {
        $response = $this->disposalsData($request);
        $responseData = $response->getData(true);

        if (!$responseData['success']) {
            return back()->with('error', 'Failed to generate report data.');
        }

        $data = $responseData['data'];
        $summary = $responseData['summary'];
        $fromDate = Carbon::parse($request->input('from_date'));
        $toDate = Carbon::parse($request->input('to_date'));
        $user = Auth::user();
        $company = $user->company;
        $branch = $user->branch;

        $pdf = Pdf::loadView('assets.reports.exports.disposals-pdf', compact(
            'data',
            'summary',
            'fromDate',
            'toDate',
            'user',
            'company',
            'branch'
        ))
        ->setPaper('a4', 'landscape')
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif'
        ]);

        return $pdf->download('asset-disposals-' . $fromDate->format('Ymd') . '-to-' . $toDate->format('Ymd') . '-' . Carbon::now()->format('YmdHis') . '.pdf');
    }

    /**
     * Asset Transfer Report
     */
    public function transfers()
    {
        return view('assets.reports.transfers');
    }

    public function transfersData(Request $request)
    {
        $fromDate = Carbon::parse($request->input('from_date', Carbon::now()->startOfYear()->format('Y-m-d')));
        $toDate = Carbon::parse($request->input('to_date', Carbon::now()->format('Y-m-d')));

        $movements = \App\Models\Assets\AssetMovement::where('company_id', Auth::user()->company_id)
            ->whereBetween('completed_at', [$fromDate, $toDate])
            ->whereNotNull('completed_at')
            ->where('status', 'completed')
            ->with(['asset', 'fromBranch', 'toBranch', 'approvedBy'])
            ->orderBy('completed_at', 'desc')
            ->get();

        $data = [];
        foreach ($movements as $movement) {
            $fromLocation = $movement->fromBranch ? $movement->fromBranch->name : 'N/A';
            $toLocation = $movement->toBranch ? $movement->toBranch->name : 'N/A';

            $data[] = [
                'asset_code' => $movement->asset->code ?? 'N/A',
                'from_location' => $fromLocation,
                'to_location' => $toLocation,
                'transfer_date' => $movement->completed_at ? $movement->completed_at->format('Y-m-d') : 'N/A',
                'approved_by' => $movement->approvedBy->name ?? 'N/A',
                'remarks' => $movement->notes ?? $movement->reason ?? '',
            ];
        }

        $summary = ['count' => $movements->count()];

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => $summary
        ]);
    }

    public function transfersExport(Request $request)
    {
        $response = $this->transfersData($request);
        $responseData = $response->getData(true);

        if (!$responseData['success']) {
            return back()->with('error', 'Failed to generate report data.');
        }

        $data = $responseData['data'];
        $summary = $responseData['summary'];
        $fromDate = Carbon::parse($request->input('from_date'));
        $toDate = Carbon::parse($request->input('to_date'));

        return Excel::download(
            new \App\Exports\AssetTransfersExport($data, $summary, $fromDate, $toDate),
            'asset-transfers-' . $fromDate->format('Ymd') . '-to-' . $toDate->format('Ymd') . '-' . Carbon::now()->format('YmdHis') . '.xlsx'
        );
    }

    public function transfersExportPdf(Request $request)
    {
        $response = $this->transfersData($request);
        $responseData = $response->getData(true);

        if (!$responseData['success']) {
            return back()->with('error', 'Failed to generate report data.');
        }

        $data = $responseData['data'];
        $summary = $responseData['summary'];
        $fromDate = Carbon::parse($request->input('from_date'));
        $toDate = Carbon::parse($request->input('to_date'));
        $user = Auth::user();
        $company = $user->company;
        $branch = $user->branch;

        $pdf = Pdf::loadView('assets.reports.exports.transfers-pdf', compact(
            'data',
            'summary',
            'fromDate',
            'toDate',
            'user',
            'company',
            'branch'
        ))
        ->setPaper('a4', 'landscape')
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif'
        ]);

        return $pdf->download('asset-transfers-' . $fromDate->format('Ymd') . '-to-' . $toDate->format('Ymd') . '-' . Carbon::now()->format('YmdHis') . '.pdf');
    }

    /**
     * Revaluation Report (IFRS)
     */
    public function revaluation()
    {
        return view('assets.reports.revaluations');
    }

    public function revaluationData(Request $request)
    {
        $fromDate = Carbon::parse($request->input('from_date', Carbon::now()->startOfYear()->format('Y-m-d')));
        $toDate = Carbon::parse($request->input('to_date', Carbon::now()->format('Y-m-d')));

        $revaluations = AssetRevaluation::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->whereBetween('revaluation_date', [$fromDate, $toDate])
            ->with(['asset', 'valuer'])
            ->orderBy('revaluation_date', 'desc')
            ->get();

        $data = [];
        $summary = [
            'count' => $revaluations->count(),
            'total_increase' => 0,
            'total_decrease' => 0,
            'net_movement' => 0
        ];

        foreach ($revaluations as $revaluation) {
            $oldAmount = $revaluation->carrying_amount_before ?? 0;
            $revaluedAmount = $revaluation->revalued_carrying_amount ?? 0;
            $surplusDeficit = $revaluedAmount - $oldAmount;
            $reserveMovement = $revaluation->revaluation_increase - $revaluation->revaluation_decrease;

            $data[] = [
                'asset_code' => $revaluation->asset->code ?? 'N/A',
                'old_carrying_amount' => $oldAmount,
                'revalued_amount' => $revaluedAmount,
                'surplus_deficit' => $surplusDeficit,
                'revaluation_reserve_movement' => $reserveMovement,
                'valuer' => $revaluation->valuer->name ?? $revaluation->external_valuer_name ?? 'N/A',
                'valuation_date' => $revaluation->revaluation_date ? Carbon::parse($revaluation->revaluation_date)->format('Y-m-d') : 'N/A',
            ];

            if ($surplusDeficit > 0) {
                $summary['total_increase'] += $surplusDeficit;
            } else {
                $summary['total_decrease'] += abs($surplusDeficit);
            }
        }

        $summary['net_movement'] = $summary['total_increase'] - $summary['total_decrease'];

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => $summary
        ]);
    }

    public function revaluationExport(Request $request)
    {
        $response = $this->revaluationData($request);
        $responseData = $response->getData(true);

        if (!$responseData['success']) {
            return back()->with('error', 'Failed to generate report data.');
        }

        $data = $responseData['data'];
        $summary = $responseData['summary'];
        $fromDate = Carbon::parse($request->input('from_date'));
        $toDate = Carbon::parse($request->input('to_date'));

        return Excel::download(
            new \App\Exports\AssetRevaluationsExport($data, $summary, $fromDate, $toDate),
            'asset-revaluations-' . $fromDate->format('Ymd') . '-to-' . $toDate->format('Ymd') . '-' . Carbon::now()->format('YmdHis') . '.xlsx'
        );
    }

    public function revaluationExportPdf(Request $request)
    {
        $response = $this->revaluationData($request);
        $responseData = $response->getData(true);

        if (!$responseData['success']) {
            return back()->with('error', 'Failed to generate report data.');
        }

        $data = $responseData['data'];
        $summary = $responseData['summary'];
        $fromDate = Carbon::parse($request->input('from_date'));
        $toDate = Carbon::parse($request->input('to_date'));
        $user = Auth::user();
        $company = $user->company;
        $branch = $user->branch;

        $pdf = Pdf::loadView('assets.reports.exports.revaluations-pdf', compact(
            'data',
            'summary',
            'fromDate',
            'toDate',
            'user',
            'company',
            'branch'
        ))
        ->setPaper('a4', 'landscape')
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif'
        ]);

        return $pdf->download('asset-revaluations-' . $fromDate->format('Ymd') . '-to-' . $toDate->format('Ymd') . '-' . Carbon::now()->format('YmdHis') . '.pdf');
    }

    /**
     * Display Impairment Report (IAS 36)
     */
    public function impairment()
    {
        return view('assets.reports.impairments');
    }

    /**
     * Get Impairment Report data
     */
    public function impairmentData(Request $request)
    {
        $fromDate = Carbon::parse($request->input('from_date', Carbon::now()->startOfYear()->format('Y-m-d')));
        $toDate = Carbon::parse($request->input('to_date', Carbon::now()->format('Y-m-d')));
        $type = $request->input('type');

        $impairments = AssetImpairment::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->whereBetween('impairment_date', [$fromDate, $toDate])
            ->when($type === 'impairment', fn($q) => $q->where('is_reversal', false))
            ->when($type === 'reversal', fn($q) => $q->where('is_reversal', true))
            ->with(['asset', 'cgu'])
            ->orderBy('impairment_date', 'desc')
            ->get();

        $data = [];
        $summary = [
            'count' => $impairments->count(),
            'total_loss' => 0,
            'total_reversals' => 0,
            'net_impact' => 0,
            'total_carrying_after' => 0
        ];

        foreach ($impairments as $impairment) {
            $lossAmount = $impairment->is_reversal ? 0 : ($impairment->impairment_loss ?? 0);
            $reversalAmount = $impairment->is_reversal ? ($impairment->reversal_amount ?? 0) : 0;

            $data[] = [
                'asset_code' => $impairment->asset->code ?? 'N/A',
                'cgu' => $impairment->cgu->name ?? 'N/A',
                'carrying_amount_before' => $impairment->carrying_amount ?? 0,
                'recoverable_amount' => $impairment->recoverable_amount ?? 0,
                'impairment_loss' => $lossAmount,
                'reversal' => $reversalAmount,
                'carrying_amount_after' => $impairment->carrying_amount_after ?? 0,
            ];

            $summary['total_loss'] += $lossAmount;
            $summary['total_reversals'] += $reversalAmount;
            $summary['total_carrying_after'] += ($impairment->carrying_amount_after ?? 0);
        }

        $summary['net_impact'] = $summary['total_reversals'] - $summary['total_loss'];

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => $summary
        ]);
    }

    /**
     * Export Impairment Report to Excel
     */
    public function impairmentExport(Request $request)
    {
        $response = $this->impairmentData($request);
        $responseData = $response->getData(true);

        if (!$responseData['success']) {
            return back()->with('error', 'Failed to generate report data.');
        }

        $data = $responseData['data'];
        $summary = $responseData['summary'];
        $fromDate = Carbon::parse($request->input('from_date'));
        $toDate = Carbon::parse($request->input('to_date'));

        return Excel::download(
            new \App\Exports\AssetImpairmentsExport($data, $summary, $fromDate, $toDate),
            'asset-impairments-' . $fromDate->format('Ymd') . '-to-' . $toDate->format('Ymd') . '-' . Carbon::now()->format('YmdHis') . '.xlsx'
        );
    }

    /**
     * Export Impairment Report to PDF
     */
    public function impairmentExportPdf(Request $request)
    {
        $response = $this->impairmentData($request);
        $responseData = $response->getData(true);

        if (!$responseData['success']) {
            return back()->with('error', 'Failed to generate report data.');
        }

        $data = $responseData['data'];
        $summary = $responseData['summary'];
        $fromDate = Carbon::parse($request->input('from_date'));
        $toDate = Carbon::parse($request->input('to_date'));
        $user = Auth::user();
        $company = $user->company;
        $branch = $user->branch;

        $pdf = Pdf::loadView('assets.reports.exports.impairments-pdf', compact(
            'data',
            'summary',
            'fromDate',
            'toDate',
            'user',
            'company',
            'branch'
        ))
        ->setPaper('a4', 'landscape')
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif'
        ]);

        return $pdf->download('asset-impairments-' . $fromDate->format('Ymd') . '-to-' . $toDate->format('Ymd') . '-' . Carbon::now()->format('YmdHis') . '.pdf');
    }

    /**
     * Assets by Location Report
     */
    public function byLocation() {
        $categories = AssetCategory::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();

        return view('assets.reports.by-location', compact('categories'));
    }

    public function byLocationData(Request $request)
    {
        $categoryId = $request->input('category_id');
        $status = $request->input('status', 'active');

        $assets = Asset::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($categoryId, fn($q) => $q->where('asset_category_id', $categoryId))
            ->with(['category'])
            ->orderBy('location')
            ->get();

        $grouped = $assets->groupBy('location');
        $data = [];
        $summary = ['total_cost' => 0, 'total_nbv' => 0, 'count' => 0];

        foreach ($grouped as $location => $items) {
            foreach ($items as $asset) {
                $data[] = [
                    'location' => $location ?: 'Unassigned',
                    'asset_code' => $asset->code,
                    'asset_name' => $asset->name,
                    'category' => $asset->category->name ?? 'N/A',
                    'cost' => $asset->purchase_cost,
                    'nbv' => $asset->current_nbv,
                ];
                $summary['total_cost'] += $asset->purchase_cost;
                $summary['total_nbv'] += $asset->current_nbv;
                $summary['count']++;
            }
        }

        return response()->json(['success' => true, 'data' => $data, 'summary' => $summary]);
    }

    public function byLocationExport(Request $request)
    {
        $response = $this->byLocationData($request);
        $data = $response->getData(true);
        return Excel::download(new \App\Exports\AssetsByLocationExport($data['data'], $data['summary']), 'assets-by-location-' . Carbon::now()->format('YmdHis') . '.xlsx');
    }

    public function byLocationExportPdf(Request $request)
    {
        $response = $this->byLocationData($request);
        $data = $response->getData(true)['data'];
        $summary = $response->getData(true)['summary'];
        $pdf = Pdf::loadView('assets.reports.exports.by-location-pdf', compact('data', 'summary', ))->setPaper('a4', 'landscape');
        return $pdf->download('assets-by-location-' . Carbon::now()->format('YmdHis') . '.pdf');
    }

    /**
     * Assets by Category Summary
     */
    public function byCategory() {
        return view('assets.reports.by-category');
    }

    public function byCategoryData(Request $request)
    {
        $status = $request->input('status', 'active');

        $categories = AssetCategory::where('company_id', Auth::user()->company_id)->with(['assets' => function($q) use ($status) {
            if ($status) {
                $q->where('status', $status);
            }
            if (Auth::user()->branch_id) {
                $q->where('branch_id', Auth::user()->branch_id);
            }
        }])->get();

        $data = [];
        $summary = ['total_cost' => 0, 'total_depreciation' => 0, 'total_impairment' => 0, 'total_nbv' => 0];

        foreach ($categories as $category) {
            $totalCost = $category->assets->sum('purchase_cost');
            $totalDep = $category->assets->sum(fn($a) => $a->purchase_cost - $a->current_nbv);
            $totalImpairment = $category->assets->sum(fn($a) => $a->accumulated_impairment ?? 0);
            $totalNbv = $category->assets->sum('current_nbv');

            if ($category->assets->count() > 0) {
                $data[] = [
                    'category' => $category->name,
                    'total_cost' => $totalCost,
                    'total_accumulated_depreciation' => $totalDep,
                    'total_impairment' => $totalImpairment,
                    'net_book_value' => $totalNbv,
                ];

                $summary['total_cost'] += $totalCost;
                $summary['total_depreciation'] += $totalDep;
                $summary['total_impairment'] += $totalImpairment;
                $summary['total_nbv'] += $totalNbv;
            }
        }

        return response()->json(['success' => true, 'data' => $data, 'summary' => $summary]);
    }

    public function byCategoryExport(Request $request)
    {
        $response = $this->byCategoryData($request);
        $data = $response->getData(true);
        return Excel::download(new \App\Exports\AssetsByCategoryExport($data['data'], $data['summary']), 'assets-by-category-' . Carbon::now()->format('YmdHis') . '.xlsx');
    }

    public function byCategoryExportPdf(Request $request)
    {
        $response = $this->byCategoryData($request);
        $data = $response->getData(true)['data'];
        $summary = $response->getData(true)['summary'];
        $pdf = Pdf::loadView('assets.reports.exports.by-category-pdf', compact('data', 'summary'))->setPaper('a4');
        return $pdf->download('assets-by-category-' . Carbon::now()->format('YmdHis') . '.pdf');
    }

    /**
     * Physical Verification Report
     */
    public function physicalVerification() {
        $categories = AssetCategory::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();

        return view('assets.reports.physical-verification', compact('categories'));
    }

    public function physicalVerificationData(Request $request)
    {
        $categoryId = $request->input('category_id');
        $status = $request->input('status', 'active');

        $assets = Asset::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($categoryId, fn($q) => $q->where('asset_category_id', $categoryId))
            ->with(['category'])
            ->get();

        $data = $assets->map(function($asset) {
            return [
                'asset_code' => $asset->code,
                'asset_name' => $asset->name,
                'location' => $asset->location ?: 'N/A',
                'system_status' => ucfirst($asset->status),
                'physical_status' => 'Pending Verification',
                'variance' => 'N/A',
                'verified_by' => 'N/A',
                'verification_date' => 'N/A',
            ];
        });

        return response()->json(['success' => true, 'data' => $data, 'summary' => ['count' => $assets->count()]]);
    }

    public function physicalVerificationExport(Request $request)
    {
        $response = $this->physicalVerificationData($request);
        $data = $response->getData(true);
        return Excel::download(new \App\Exports\PhysicalVerificationExport($data['data']), 'physical-verification-' . Carbon::now()->format('YmdHis') . '.xlsx');
    }

    public function physicalVerificationExportPdf(Request $request)
    {
        $response = $this->physicalVerificationData($request);
        $data = $response->getData(true)['data'];
        $pdf = Pdf::loadView('assets.reports.exports.physical-verification-pdf', compact('data'))->setPaper('a4', 'landscape');
        return $pdf->download('physical-verification-' . Carbon::now()->format('YmdHis') . '.pdf');
    }

    /**
     * CWIP Report
     */
    public function cwip() {
        $categories = AssetCategory::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();

        return view('assets.reports.cwip', compact('categories'));
    }

    public function cwipData(Request $request)
    {
        $categoryId = $request->input('category_id');
        $status = $request->input('status', 'under_construction');

        $assets = Asset::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($categoryId, fn($q) => $q->where('asset_category_id', $categoryId))
            ->with(['category'])
            ->get();

        $data = $assets->map(function($asset) {
            return [
                'project_code' => $asset->code,
                'project_name' => $asset->name,
                'opening_balance' => $asset->purchase_cost,
                'additions' => 0,
                'capitalized' => 0,
                'closing_balance' => $asset->current_nbv,
                'expected_completion_date' => $asset->expected_completion_date ?? 'N/A',
            ];
        });

        $summary = ['total_balance' => $assets->sum('current_nbv'), 'count' => $assets->count()];

        return response()->json(['success' => true, 'data' => $data, 'summary' => $summary]);
    }

    public function cwipExport(Request $request)
    {
        $response = $this->cwipData($request);
        $data = $response->getData(true);
        return Excel::download(new \App\Exports\CwipExport($data['data'], $data['summary']), 'cwip-' . Carbon::now()->format('YmdHis') . '.xlsx');
    }

    public function cwipExportPdf(Request $request)
    {
        $response = $this->cwipData($request);
        $data = $response->getData(true)['data'];
        $summary = $response->getData(true)['summary'];
        $pdf = Pdf::loadView('assets.reports.exports.cwip-pdf', compact('data', 'summary'))->setPaper('a4', 'landscape');
        return $pdf->download('cwip-' . Carbon::now()->format('YmdHis') . '.pdf');
    }

    /**
     * Leasehold Improvements Report
     */
    public function leaseholdImprovements() {
        $categories = AssetCategory::where('company_id', Auth::user()->company_id)
            ->where('name', 'LIKE', '%leasehold%')
            ->orderBy('name')
            ->get();

        return view('assets.reports.leasehold', compact('categories'));
    }

    public function leaseholdData(Request $request)
    {
        $categoryId = $request->input('category_id');
        $status = $request->input('status', 'active');

        $assets = Asset::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->when($categoryId, fn($q) => $q->where('asset_category_id', $categoryId), 
                  fn($q) => $q->whereHas('category', fn($query) => $query->where('name', 'LIKE', '%leasehold%')))
            ->when($status, fn($q) => $q->where('status', $status))
            ->with(['category', 'depreciationMethod'])
            ->get();

        $data = $assets->map(function($asset) {
            return [
                'asset_code' => $asset->code,
                'lease_term' => $asset->lease_term ?? 'N/A',
                'improvement_cost' => $asset->purchase_cost,
                'depreciation_method' => $asset->depreciationMethod->name ?? 'Straight Line',
                'remaining_lease_period' => $asset->remaining_lease_period ?? 'N/A',
                'nbv' => $asset->current_nbv,
            ];
        });

        $summary = ['total_cost' => $assets->sum('purchase_cost'), 'total_nbv' => $assets->sum('current_nbv')];

        return response()->json(['success' => true, 'data' => $data, 'summary' => $summary]);
    }

    public function leaseholdExport(Request $request)
    {
        $response = $this->leaseholdData($request);
        $data = $response->getData(true);
        return Excel::download(new \App\Exports\LeaseholdExport($data['data'], $data['summary']), 'leasehold-' . Carbon::now()->format('YmdHis') . '.xlsx');
    }

    public function leaseholdExportPdf(Request $request)
    {
        $response = $this->leaseholdData($request);
        $data = $response->getData(true)['data'];
        $summary = $response->getData(true)['summary'];
        $pdf = Pdf::loadView('assets.reports.exports.leasehold-pdf', compact('data', 'summary'))->setPaper('a4');
        return $pdf->download('leasehold-' . Carbon::now()->format('YmdHis') . '.pdf');
    }

    /**
     * Financial Statement Disclosure Note
     */
    public function fsDisclosure() {
        $categories = AssetCategory::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get();

        return view('assets.reports.fs-disclosure', compact('categories'));
    }

    public function fsDisclosureData(Request $request)
    {
        $fromDate = Carbon::parse($request->input('from_date', Carbon::now()->startOfYear()->format('Y-m-d')));
        $toDate = Carbon::parse($request->input('to_date', Carbon::now()->format('Y-m-d')));
        $categoryId = $request->input('category_id');

        $categoriesQuery = AssetCategory::where('company_id', Auth::user()->company_id)
            ->when($categoryId, fn($q) => $q->where('id', $categoryId))
            ->with(['assets']);

        $categories = $categoriesQuery->get();
        $data = [];

        foreach ($categories as $category) {
            $assets = $category->assets->when(Auth::user()->branch_id, fn($c) => $c->where('branch_id', Auth::user()->branch_id));
            
            $opening = $assets->sum('purchase_cost');
            $additions = 0;
            $disposals = 0;
            $revaluations = 0;
            $depreciation = $assets->sum(fn($a) => $a->purchase_cost - $a->current_nbv);
            $closing = $assets->sum('current_nbv');

            if ($assets->count() > 0) {
                $data[] = [
                    'category' => $category->name,
                    'opening_balance' => $opening,
                    'additions' => $additions,
                    'disposals' => $disposals,
                    'revaluations' => $revaluations,
                    'depreciation' => $depreciation,
                    'closing_balance' => $closing,
                ];
            }
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function fsDisclosureExport(Request $request)
    {
        $response = $this->fsDisclosureData($request);
        $data = $response->getData(true)['data'];
        return Excel::download(new \App\Exports\FsDisclosureExport($data), 'fs-disclosure-' . Carbon::now()->format('YmdHis') . '.xlsx');
    }

    public function fsDisclosureExportPdf(Request $request)
    {
        $response = $this->fsDisclosureData($request);
        $data = $response->getData(true)['data'];
        $pdf = Pdf::loadView('assets.reports.exports.fs-disclosure-pdf', compact('data'))->setPaper('a4', 'landscape');
        return $pdf->download('fs-disclosure-' . Carbon::now()->format('YmdHis') . '.pdf');
    }
}
