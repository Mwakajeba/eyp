<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Services\Asset\DepreciationService;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetDepreciation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class DepreciationController extends Controller
{
    protected $depreciationService;

    public function __construct(DepreciationService $depreciationService)
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
        $this->depreciationService = $depreciationService;
    }

    public function index()
    {
        return view('assets.depreciation.index');
    }

    public function process(Request $request)
    {
        // Normalize checkbox boolean before validation
        $postToGLValue = $request->input('post_to_gl');
        if ($postToGLValue === '1' || $postToGLValue === 'true' || $postToGLValue === true) {
            $postToGLValue = true;
        } elseif ($postToGLValue === '0' || $postToGLValue === 'false' || $postToGLValue === false || $postToGLValue === null) {
            $postToGLValue = false;
        } else {
            $postToGLValue = (bool) $postToGLValue;
        }
        
        $request->merge(['post_to_gl' => $postToGLValue]);

        $request->validate([
            'period_date' => 'required|date',
            'post_to_gl' => 'required|boolean',
        ]);

        $periodDate = Carbon::parse($request->period_date);
        $postToGL = (bool) $request->input('post_to_gl', true);
        $companyId = Auth::user()->company_id;
        $branchId = Auth::user()->branch_id;

        try {
            $result = $this->depreciationService->processDepreciation(
                $periodDate,
                $companyId,
                $branchId,
                $postToGL
            );

            $message = "Processed {$result['total_processed']} assets.";
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
                'message' => 'Depreciation processing failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function forecast($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $asset = Asset::findOrFail($decodedId);

        $forecast = $this->depreciationService->forecastDepreciation($asset, 12);

        return response()->json([
            'success' => true,
            'asset' => [
                'id' => $asset->id,
                'name' => $asset->name,
                'code' => $asset->code,
            ],
            'forecast' => $forecast,
        ]);
    }

    public function history(Request $request)
    {
        // Default to current month if no month filter is provided
        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));
        
        return view('assets.depreciation.history', compact('selectedMonth'));
    }

    public function historyData(Request $request)
    {
        $query = AssetDepreciation::where('type', 'depreciation')
            ->where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->with(['asset']);

        // Filter by month (default to current month)
        $selectedMonth = $request->input('month', Carbon::now()->format('Y-m'));
        if ($selectedMonth) {
            $startDate = Carbon::parse($selectedMonth . '-01')->startOfMonth();
            $endDate = Carbon::parse($selectedMonth . '-01')->endOfMonth();
            $query->whereBetween('depreciation_date', [$startDate, $endDate]);
        }

        if ($request->has('asset_id') && $request->asset_id) {
            $query->where('asset_id', $request->asset_id);
        }

        return DataTables::of($query)
            ->addColumn('date_formatted', function ($depr) {
                return Carbon::parse($depr->depreciation_date)->format('d M Y');
            })
            ->addColumn('asset_name', function ($depr) {
                if ($depr->asset) {
                    $encodedId = Hashids::encode($depr->asset->id);
                    return '<a href="' . route('assets.registry.show', $encodedId) . '" class="text-primary">' 
                        . e($depr->asset->name) . '</a>'
                        . '<br><small class="text-muted">' . e($depr->asset->code) . '</small>';
                }
                return '<span class="text-muted">Asset not found</span>';
            })
            ->addColumn('book_value_before_formatted', function ($depr) {
                return 'TZS ' . number_format($depr->book_value_before, 2);
            })
            ->addColumn('depreciation_amount_formatted', function ($depr) {
                return '<span class="text-danger fw-semibold">-' . number_format($depr->depreciation_amount, 2) . '</span>';
            })
            ->addColumn('accumulated_depreciation_formatted', function ($depr) {
                return '<span class="fw-semibold text-danger">' . number_format($depr->accumulated_depreciation, 2) . '</span>';
            })
            ->addColumn('book_value_after_formatted', function ($depr) {
                return '<span class="fw-semibold text-primary">' . number_format($depr->book_value_after, 2) . '</span>';
            })
            ->addColumn('gl_posted_badge', function ($depr) {
                if ($depr->gl_posted) {
                    return '<span class="badge bg-success">Posted</span>';
                }
                return '<span class="badge bg-secondary">Not Posted</span>';
            })
            ->addColumn('actions', function ($depr) {
                if ($depr->asset) {
                    $encodedId = Hashids::encode($depr->asset->id);
                    return '<a href="' . route('assets.registry.depreciation-history', $encodedId) . '" class="btn btn-sm btn-outline-primary" title="View Asset History">'
                        . '<i class="bx bx-show"></i></a>';
                }
                return '';
            })
            ->rawColumns(['asset_name', 'depreciation_amount_formatted', 'accumulated_depreciation_formatted', 'book_value_after_formatted', 'gl_posted_badge', 'actions'])
            ->make(true);
    }
}
