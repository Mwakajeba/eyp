<?php

namespace App\Http\Controllers\Investment;

use App\Http\Controllers\Controller;
use App\Models\Investment\InvestmentMaster;
use App\Models\Investment\InvestmentTrade;
use App\Services\Investment\EclCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvestmentEclReportController extends Controller
{
    protected $eclService;

    public function __construct(EclCalculationService $eclService)
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
        $this->eclService = $eclService;
    }

    /**
     * Display ECL Summary Report
     */
    public function summary(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        // Get ECL summary by stage
        $summary = $this->eclService->getEclSummaryByStage($companyId);

        // Get detailed ECL data
        $trades = InvestmentTrade::with(['investment'])
            ->whereHas('investment', function($query) use ($companyId, $branchId) {
                $query->where('company_id', $companyId)
                      ->whereIn('status', ['ACTIVE']);
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
            })
            ->where('trade_type', 'PURCHASE')
            ->whereNotNull('ecl_amount')
            ->get();

        // Group by stage
        $byStage = [
            1 => ['count' => 0, 'total_ecl' => 0, 'total_ead' => 0, 'investments' => []],
            2 => ['count' => 0, 'total_ecl' => 0, 'total_ead' => 0, 'investments' => []],
            3 => ['count' => 0, 'total_ecl' => 0, 'total_ead' => 0, 'investments' => []],
        ];

        foreach ($trades as $trade) {
            $stage = $trade->stage ?? 1;
            if (isset($byStage[$stage])) {
                $byStage[$stage]['count']++;
                $byStage[$stage]['total_ecl'] += $trade->ecl_amount ?? 0;
                $byStage[$stage]['total_ead'] += $trade->ead ?? 0;
                $byStage[$stage]['investments'][] = $trade;
            }
        }

        // Calculate totals
        $totalEcl = array_sum(array_column($byStage, 'total_ecl'));
        $totalEad = array_sum(array_column($byStage, 'total_ead'));

        return view('investments.reports.ecl-summary', compact(
            'summary',
            'byStage',
            'totalEcl',
            'totalEad'
        ));
    }

    /**
     * Display ECL Detail Report by Investment
     */
    public function detail(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $query = InvestmentTrade::with(['investment'])
            ->whereHas('investment', function($q) use ($companyId, $branchId) {
                $q->where('company_id', $companyId)
                  ->whereIn('status', ['ACTIVE']);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->where('trade_type', 'PURCHASE')
            ->whereNotNull('ecl_amount');

        // Filters
        if ($request->has('stage') && $request->stage) {
            $query->where('stage', $request->stage);
        }

        if ($request->has('instrument_type') && $request->instrument_type) {
            $query->whereHas('investment', function($q) use ($request) {
                $q->where('instrument_type', $request->instrument_type);
            });
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->where('trade_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('trade_date', '<=', $request->date_to);
        }

        $trades = $query->orderBy('trade_date', 'desc')->paginate(50);

        return view('investments.reports.ecl-detail', compact('trades'));
    }

    /**
     * Display ECL Trend Report
     */
    public function trend(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        // Get ECL data grouped by month
        $trendData = InvestmentTrade::with(['investment'])
            ->whereHas('investment', function($query) use ($companyId) {
                $query->where('company_id', $companyId)
                      ->whereIn('status', ['ACTIVE']);
            })
            ->where('trade_type', 'PURCHASE')
            ->whereNotNull('ecl_amount')
            ->select(
                DB::raw('DATE_FORMAT(trade_date, "%Y-%m") as month'),
                DB::raw('SUM(ecl_amount) as total_ecl'),
                DB::raw('SUM(ead) as total_ead'),
                DB::raw('AVG(pd) as avg_pd'),
                DB::raw('AVG(lgd) as avg_lgd'),
                DB::raw('COUNT(*) as investment_count')
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        return view('investments.reports.ecl-trend', compact('trendData'));
    }

    /**
     * Export ECL Report to Excel/PDF
     */
    public function export(Request $request)
    {
        // TODO: Implement export functionality
        return response()->json(['message' => 'Export functionality coming soon']);
    }
}
