<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetDepreciation;
use App\Models\Assets\TaxDepreciationClass;
use App\Exports\TraTaxDepreciationScheduleExport;
use App\Exports\BookTaxReconciliationExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Vinkla\Hashids\Facades\Hashids;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class TaxDepreciationReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    /**
     * Display TRA Tax Depreciation Schedule report
     */
    public function traSchedule(Request $request)
    {
        $taxYear = $request->input('tax_year', Carbon::now()->year);
        $taxClassId = $request->input('tax_class_id');

        $taxClasses = TaxDepreciationClass::active()
            ->orderBy('sort_order')
            ->get();

        return view('assets.tax-depreciation.reports.tra-schedule', compact('taxYear', 'taxClassId', 'taxClasses'));
    }

    /**
     * Get TRA Tax Depreciation Schedule data
     */
    public function traScheduleData(Request $request)
    {
        $taxYear = $request->input('tax_year', Carbon::now()->year);
        $taxClassId = $request->input('tax_class_id');

        $schedule = $this->getTraScheduleData($taxYear, $taxClassId);

        return response()->json([
            'success' => true,
            'data' => $schedule,
            'tax_year' => $taxYear,
        ]);
    }

    /**
     * Export TRA Tax Depreciation Schedule to Excel
     */
    public function traScheduleExport(Request $request)
    {
        $taxYear = $request->input('tax_year', Carbon::now()->year);
        $taxClassId = $request->input('tax_class_id');

        $schedule = $this->getTraScheduleData($taxYear, $taxClassId);

        if (empty($schedule)) {
            return back()->with('error', 'No data available for the selected period.');
        }

        $fileName = 'TRA_Tax_Depreciation_Schedule_' . $taxYear . '_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new TraTaxDepreciationScheduleExport($schedule, $taxYear), $fileName);
    }

    /**
     * Export TRA Tax Depreciation Schedule to PDF
     */
    public function traScheduleExportPdf(Request $request)
    {
        $taxYear = $request->input('tax_year', Carbon::now()->year);
        $taxClassId = $request->input('tax_class_id');

        $schedule = $this->getTraScheduleData($taxYear, $taxClassId);

        if (empty($schedule)) {
            return back()->with('error', 'No data available for the selected period.');
        }

        $user = Auth::user();
        $company = $user->company;
        $branch = $user->branch;

        $pdf = Pdf::loadView('assets.tax-depreciation.reports.exports.tra-schedule-pdf', compact(
            'schedule',
            'taxYear',
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

        $fileName = 'TRA_Tax_Depreciation_Schedule_' . $taxYear . '_' . Carbon::now()->format('Ymd_His') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Get TRA Tax Depreciation Schedule data (shared method)
     */
    private function getTraScheduleData($taxYear, $taxClassId = null)
    {
        $startDate = Carbon::create($taxYear, 1, 1)->startOfDay();
        $endDate = Carbon::create($taxYear, 12, 31)->endOfDay();

        // Group by tax class
        $taxClasses = TaxDepreciationClass::active()
            ->when($taxClassId, fn($q) => $q->where('id', $taxClassId))
            ->orderBy('sort_order')
            ->get();

        $schedule = [];

        foreach ($taxClasses as $taxClass) {
            // Get assets with this tax class
            $assets = Asset::where('tax_class_id', $taxClass->id)
                ->where('company_id', Auth::user()->company_id)
                ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
                ->with(['category'])
                ->get();

            $classData = [
                'tax_class' => $taxClass,
                'categories' => [],
                'total_opening_wdv' => 0,
                'total_additions' => 0,
                'total_disposals' => 0,
                'total_tax_depreciation' => 0,
                'total_closing_wdv' => 0,
            ];

            // Group assets by category
            $assetsByCategory = $assets->groupBy('asset_category_id');

            foreach ($assetsByCategory as $categoryId => $categoryAssets) {
                $category = $categoryAssets->first()->category;
                
                $categoryData = [
                    'category' => $category,
                    'opening_wdv' => 0,
                    'additions' => 0,
                    'disposals' => 0,
                    'tax_depreciation' => 0,
                    'closing_wdv' => 0,
                ];

            foreach ($categoryAssets as $asset) {
                // Opening WDV (beginning of year)
                $openingWdv = AssetDepreciation::getCurrentTaxWdv(
                    $asset->id,
                    $startDate->copy()->subDay(),
                    $asset->company_id
                ) ?? ($asset->tax_value_opening ?? $asset->purchase_cost);

                // Tax depreciation for the year
                $yearDepreciations = AssetDepreciation::where('asset_id', $asset->id)
                    ->where('depreciation_type', 'tax')
                    ->whereBetween('depreciation_date', [$startDate, $endDate])
                    ->sum('depreciation_amount');

                // Closing WDV (end of year)
                $closingWdv = AssetDepreciation::getCurrentTaxWdv(
                    $asset->id,
                    $endDate,
                    $asset->company_id
                ) ?? $openingWdv;

                // Additions (assets capitalized during the year)
                $additions = 0;
                if ($asset->capitalization_date && $asset->capitalization_date->between($startDate, $endDate)) {
                    $additions = $asset->purchase_cost;
                }

                // Disposals (check if disposed during the year)
                $disposals = 0;
                if ($asset->status === 'disposed') {
                    $disposal = $asset->disposals()->whereBetween('actual_disposal_date', [$startDate, $endDate])->first();
                    if ($disposal) {
                        $disposals = $disposal->asset_cost ?? $asset->purchase_cost;
                    }
                }

                // Accumulate by category
                $categoryData['opening_wdv'] += $openingWdv;
                $categoryData['additions'] += $additions;
                $categoryData['disposals'] += $disposals;
                $categoryData['tax_depreciation'] += $yearDepreciations;
                $categoryData['closing_wdv'] += $closingWdv;

                $classData['total_opening_wdv'] += $openingWdv;
                $classData['total_additions'] += $additions;
                $classData['total_disposals'] += $disposals;
                $classData['total_tax_depreciation'] += $yearDepreciations;
                $classData['total_closing_wdv'] += $closingWdv;
            }

                // Add category data
                $classData['categories'][] = $categoryData;
            }

            if (count($classData['categories']) > 0) {
                $schedule[] = $classData;
            }
        }

        return $schedule;
    }

    /**
     * Display Book vs Tax Reconciliation report
     */
    public function bookTaxReconciliation(Request $request)
    {
        $asOfDate = $request->input('as_of_date', Carbon::now()->format('Y-m-d'));

        return view('assets.tax-depreciation.reports.book-tax-reconciliation', compact('asOfDate'));
    }

    /**
     * Get Book vs Tax Reconciliation data
     */
    public function bookTaxReconciliationData(Request $request)
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', Carbon::now()->format('Y-m-d')));

        $reconciliation = $this->getBookTaxReconciliationData($asOfDate);

        return response()->json([
            'success' => true,
            'data' => $reconciliation,
            'as_of_date' => $asOfDate->format('Y-m-d'),
        ]);
    }

    /**
     * Export Book vs Tax Reconciliation to Excel
     */
    public function bookTaxReconciliationExport(Request $request)
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', Carbon::now()->format('Y-m-d')));
        $taxRate = $request->input('tax_rate', 30);

        $reconciliation = $this->getBookTaxReconciliationData($asOfDate);

        if (empty($reconciliation)) {
            return back()->with('error', 'No data available for the selected date.');
        }

        $fileName = 'Book_vs_Tax_Reconciliation_' . $asOfDate->format('Ymd') . '_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new BookTaxReconciliationExport($reconciliation, $asOfDate->format('Y-m-d'), $taxRate), $fileName);
    }

    /**
     * Export Book vs Tax Reconciliation to PDF
     */
    public function bookTaxReconciliationExportPdf(Request $request)
    {
        $asOfDate = Carbon::parse($request->input('as_of_date', Carbon::now()->format('Y-m-d')));
        $taxRate = $request->input('tax_rate', 30);

        $reconciliation = $this->getBookTaxReconciliationData($asOfDate);

        if (empty($reconciliation)) {
            return back()->with('error', 'No data available for the selected date.');
        }

        $user = Auth::user();
        $company = $user->company;
        $branch = $user->branch;

        $pdf = Pdf::loadView('assets.tax-depreciation.reports.exports.book-tax-reconciliation-pdf', compact(
            'reconciliation',
            'asOfDate',
            'taxRate',
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

        $fileName = 'Book_vs_Tax_Reconciliation_' . $asOfDate->format('Ymd') . '_' . Carbon::now()->format('Ymd_His') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Get Book vs Tax Reconciliation data (shared method)
     */
    private function getBookTaxReconciliationData($asOfDate)
    {
        $assets = Asset::whereNotNull('tax_class_id')
            ->where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->with(['category', 'taxClass'])
            ->get();

        $reconciliation = [];

        foreach ($assets as $asset) {
            // Book values
            $bookNBV = AssetDepreciation::getCurrentBookValue($asset->id, $asOfDate, $asset->company_id)
                ?? $asset->current_nbv
                ?? $asset->purchase_cost;

            $bookAccumDep = AssetDepreciation::getAccumulatedDepreciation($asset->id, $asOfDate, $asset->company_id);

            // Tax values
            $taxWDV = AssetDepreciation::getCurrentTaxWdv($asset->id, $asOfDate, $asset->company_id)
                ?? ($asset->current_tax_wdv ?? $asset->purchase_cost);

            $taxAccumDep = AssetDepreciation::getAccumulatedTaxDepreciation($asset->id, $asOfDate, $asset->company_id);

            // Differences
            $temporaryDifference = $bookNBV - $taxWDV;
            $depreciationDifference = $taxAccumDep - $bookAccumDep;

            $reconciliation[] = [
                'asset' => $asset,
                'book_cost' => $asset->purchase_cost,
                'book_accum_dep' => $bookAccumDep,
                'book_nbv' => $bookNBV,
                'tax_cost' => $asset->purchase_cost,
                'tax_accum_dep' => $taxAccumDep,
                'tax_wdv' => $taxWDV,
                'temporary_difference' => $temporaryDifference,
                'depreciation_difference' => $depreciationDifference,
            ];
        }

        return $reconciliation;
    }
}
