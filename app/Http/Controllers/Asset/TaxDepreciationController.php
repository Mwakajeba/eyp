<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Services\Asset\TaxDepreciationService;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetDepreciation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class TaxDepreciationController extends Controller
{
    protected $taxDepreciationService;

    public function __construct(TaxDepreciationService $taxDepreciationService)
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
        $this->taxDepreciationService = $taxDepreciationService;
    }

    /**
     * Display tax depreciation management page
     */
    public function index()
    {
        return view('assets.tax-depreciation.index');
    }

    /**
     * Process tax depreciation for a period
     */
    public function process(Request $request)
    {
        $request->validate([
            'period_date' => 'required|date',
        ]);

        $periodDate = Carbon::parse($request->period_date);
        $companyId = Auth::user()->company_id;
        $branchId = Auth::user()->branch_id;

        try {
            $result = $this->taxDepreciationService->processTaxDepreciation(
                $periodDate,
                $companyId,
                $branchId
            );

            $message = "Processed tax depreciation for {$result['total_processed']} assets.";
            if ($result['total_errors'] > 0) {
                $message .= " {$result['total_errors']} errors occurred.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tax depreciation processing failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display tax depreciation history
     */
    public function history(Request $request)
    {
        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));

        return view('assets.tax-depreciation.history', compact('selectedMonth'));
    }

    /**
     * Get tax depreciation history data for DataTables
     */
    public function historyData(Request $request)
    {
        $query = AssetDepreciation::where('type', 'depreciation')
            ->where('depreciation_type', 'tax')
            ->where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->with(['asset', 'taxClass']);

        // Filter by month
        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));
        if ($selectedMonth) {
            $startDate = Carbon::parse($selectedMonth . '-01')->startOfMonth();
            $endDate = Carbon::parse($selectedMonth . '-01')->endOfMonth();
            $query->whereBetween('depreciation_date', [$startDate, $endDate]);
        }

        if ($request->has('asset_id') && $request->asset_id) {
            $query->where('asset_id', $request->asset_id);
        }

        if ($request->has('tax_class_id') && $request->tax_class_id) {
            $query->where('tax_class_id', $request->tax_class_id);
        }

        return DataTables::of($query)
            ->addColumn('date_formatted', function ($depr) {
                return Carbon::parse($depr->depreciation_date)->format('d M Y');
            })
            ->addColumn('asset_name', function ($depr) {
                if ($depr->asset) {
                    $encodedId = Hashids::encode($depr->asset->id);
                    return '<a href="' . route('assets.registry.show', $encodedId)
                        . '" class="text-primary">'
                        . e($depr->asset->name) . '</a>'
                        . '<br><small class="text-muted">' . e($depr->asset->code) . '</small>';
                }
                return '<span class="text-muted">Asset not found</span>';
            })
            ->addColumn('tax_class', function ($depr) {
                if ($depr->taxClass) {
                    return '<span class="badge bg-info">' . e($depr->taxClass->class_code) . '</span>';
                }
                return '<span class="text-muted">N/A</span>';
            })
            ->addColumn('tax_wdv_before_formatted', function ($depr) {
                return 'TZS ' . number_format($depr->tax_wdv_before ?? 0, 2);
            })
            ->addColumn('depreciation_amount_formatted', function ($depr) {
                return '<span class="text-danger fw-semibold">-'
                    . number_format($depr->depreciation_amount, 2) . '</span>';
            })
            ->addColumn('accumulated_tax_depreciation_formatted', function ($depr) {
                return '<span class="fw-semibold text-danger">'
                    . number_format($depr->accumulated_tax_depreciation ?? 0, 2) . '</span>';
            })
            ->addColumn('tax_wdv_after_formatted', function ($depr) {
                return '<span class="fw-semibold text-primary">'
                    . number_format($depr->tax_wdv_after ?? 0, 2) . '</span>';
            })
            ->addColumn('actions', function ($depr) {
                if ($depr->asset) {
                    $encodedId = Hashids::encode($depr->asset->id);
                    return '<a href="' . route('assets.registry.show', $encodedId)
                        . '" class="btn btn-sm btn-outline-primary" title="View Asset">'
                        . '<i class="bx bx-show"></i></a>';
                }
                return '';
            })
            ->rawColumns([
                'asset_name',
                'tax_class',
                'depreciation_amount_formatted',
                'accumulated_tax_depreciation_formatted',
                'tax_wdv_after_formatted',
                'actions'
            ])
            ->make(true);
    }
}
