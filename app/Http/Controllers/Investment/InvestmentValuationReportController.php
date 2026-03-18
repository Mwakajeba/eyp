<?php

namespace App\Http\Controllers\Investment;

use App\Http\Controllers\Controller;
use App\Models\Investment\InvestmentValuation;
use App\Models\Investment\InvestmentMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;

class InvestmentValuationReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    /**
     * Fair Value Hierarchy Report (IFRS 13)
     */
    public function fairValueHierarchy(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $asOfDate = $request->get('as_of_date', date('Y-m-d'));

        // Get latest valuations for each investment
        $valuations = InvestmentValuation::byCompany($companyId)
            ->where('valuation_date', '<=', $asOfDate)
            ->where('status', 'POSTED')
            ->with('investment')
            ->get()
            ->groupBy('investment_id')
            ->map(function ($group) {
                return $group->sortByDesc('valuation_date')->first();
            });

        // Group by valuation level
        $level1 = $valuations->where('valuation_level', 1);
        $level2 = $valuations->where('valuation_level', 2);
        $level3 = $valuations->where('valuation_level', 3);

        $summary = [
            'level1' => [
                'count' => $level1->count(),
                'total_fair_value' => $level1->sum('total_fair_value'),
            ],
            'level2' => [
                'count' => $level2->count(),
                'total_fair_value' => $level2->sum('total_fair_value'),
            ],
            'level3' => [
                'count' => $level3->count(),
                'total_fair_value' => $level3->sum('total_fair_value'),
            ],
            'total' => [
                'count' => $valuations->count(),
                'total_fair_value' => $valuations->sum('total_fair_value'),
            ],
        ];

        return view('investments.reports.valuations.fair-value-hierarchy', compact('valuations', 'summary', 'asOfDate'));
    }

    /**
     * Valuation History Report
     */
    public function valuationHistory(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $investmentId = $request->get('investment_id');
        $startDate = $request->get('start_date', date('Y-m-d', strtotime('-1 year')));
        $endDate = $request->get('end_date', date('Y-m-d'));

        $query = InvestmentValuation::byCompany($companyId)
            ->whereBetween('valuation_date', [$startDate, $endDate])
            ->with('investment')
            ->orderBy('valuation_date', 'desc');

        if ($investmentId) {
            $decodedId = Hashids::decode($investmentId)[0] ?? null;
            if ($decodedId) {
                $query->where('investment_id', $decodedId);
            }
        }

        $valuations = $query->get();

        $investments = InvestmentMaster::byCompany($companyId)
            ->whereIn('accounting_class', ['FVPL', 'FVOCI'])
            ->orderBy('instrument_code')
            ->get();

        return view('investments.reports.valuations.valuation-history', compact('valuations', 'investments', 'startDate', 'endDate', 'investmentId'));
    }
}
