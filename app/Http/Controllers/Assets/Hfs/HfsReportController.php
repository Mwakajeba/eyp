<?php

namespace App\Http\Controllers\Assets\Hfs;

use App\Http\Controllers\Controller;
use App\Models\Assets\HfsRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HfsReportController extends Controller
{
    /**
     * Generate IFRS 5 movement schedule
     */
    public function movementSchedule(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $periodStart = $request->input('period_start', now()->startOfYear());
        $periodEnd = $request->input('period_end', now()->endOfYear());

        $hfsRequests = HfsRequest::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->with(['hfsAssets', 'valuations', 'disposal'])
            ->get();

        // Calculate movement schedule data
        $schedule = [];
        foreach ($hfsRequests as $hfs) {
            $carryingAtStart = 0; // Would need to calculate from previous period
            $classifiedDuringPeriod = $hfs->total_carrying_amount;
            $impairments = $hfs->valuations->where('is_reversal', false)->sum('impairment_amount');
            $reversals = $hfs->valuations->where('is_reversal', true)->sum('impairment_amount');
            $disposals = $hfs->disposal ? $hfs->disposal->sale_proceeds : 0;
            $carryingAtEnd = $hfs->current_total_carrying_amount;

            $schedule[] = [
                'asset_group' => $hfs->request_no,
                'carrying_at_start' => $carryingAtStart,
                'classified_during_period' => $classifiedDuringPeriod,
                'impairments' => $impairments,
                'reversals' => $reversals,
                'disposals' => $disposals,
                'carrying_at_end' => $carryingAtEnd,
            ];
        }

        if ($request->wantsJson()) {
            return response()->json($schedule);
        }

        return view('assets.hfs.reports.movement-schedule', compact('schedule', 'periodStart', 'periodEnd'));
    }

    /**
     * Generate valuation details report
     */
    public function valuationDetails(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $hfsRequests = HfsRequest::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['hfsAssets.asset', 'valuations'])
            ->get();

        $valuations = [];
        foreach ($hfsRequests as $hfs) {
            foreach ($hfs->valuations as $valuation) {
                $valuations[] = [
                    'hfs_id' => $hfs->id,
                    'hfs_request_no' => $hfs->request_no,
                    'asset_codes' => $hfs->hfsAssets->map(fn($a) => $a->asset->code ?? 'N/A')->implode(', '),
                    'date_classified' => $hfs->hfsAssets->first()->reclassified_date ?? null,
                    'carrying_at_classification' => $hfs->hfsAssets->sum('carrying_amount_at_reclass'),
                    'valuation_date' => $valuation->valuation_date,
                    'fair_value' => $valuation->fair_value,
                    'costs_to_sell' => $valuation->costs_to_sell,
                    'fv_less_costs' => $valuation->fv_less_costs,
                    'impairment_posted' => $valuation->impairment_amount > 0 ? 'Yes' : 'No',
                    'journal_ref' => $valuation->impairment_journal_id ? 'J-' . $valuation->impairment_journal_id : 'N/A',
                    'valuator' => $valuation->valuator_name,
                    'report_ref' => $valuation->report_ref,
                ];
            }
        }

        if ($request->wantsJson()) {
            return response()->json($valuations);
        }

        return view('assets.hfs.reports.valuation-details', compact('valuations'));
    }

    /**
     * Generate discontinued operations note
     */
    public function discontinuedOpsNote(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $currentYear = $request->input('year', now()->year);
        $priorYear = $currentYear - 1;

        $currentYearHfs = HfsRequest::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereYear('created_at', $currentYear)
            ->whereHas('discontinuedFlag', fn($q) => $q->where('is_discontinued', true))
            ->with(['discontinuedFlag', 'disposal'])
            ->get();

        $priorYearHfs = HfsRequest::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereYear('created_at', $priorYear)
            ->whereHas('discontinuedFlag', fn($q) => $q->where('is_discontinued', true))
            ->with(['discontinuedFlag', 'disposal'])
            ->get();

        // Aggregate P&L effects
        $currentYearEffects = $this->aggregatePnLEffects($currentYearHfs);
        $priorYearEffects = $this->aggregatePnLEffects($priorYearHfs);

        if ($request->wantsJson()) {
            return response()->json([
                'current_year' => $currentYearEffects,
                'prior_year' => $priorYearEffects,
            ]);
        }

        return view('assets.hfs.reports.discontinued-ops-note', compact(
            'currentYearEffects',
            'priorYearEffects',
            'currentYear',
            'priorYear'
        ));
    }

    /**
     * Generate overdue HFS report
     */
    public function overdueReport(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $overdueHfs = HfsRequest::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->overdue()
            ->with(['hfsAssets.asset', 'approvals.approver'])
            ->get();

        $overdueData = [];
        foreach ($overdueHfs as $hfs) {
            $monthsOverdue = $hfs->intended_sale_date->diffInMonths(now());
            $overdueData[] = [
                'request_no' => $hfs->request_no,
                'asset_codes' => $hfs->hfsAssets->map(fn($a) => $a->asset->code ?? 'N/A')->implode(', '),
                'intended_sale_date' => $hfs->intended_sale_date,
                'months_overdue' => $monthsOverdue,
                'extension_justification' => $hfs->extension_justification,
                'extension_approved_by' => $hfs->extensionApprover->name ?? 'N/A',
                'buyer_name' => $hfs->buyer_name,
                'marketing_actions' => $hfs->marketing_actions,
            ];
        }

        if ($request->wantsJson()) {
            return response()->json($overdueData);
        }

        return view('assets.hfs.reports.overdue', compact('overdueData'));
    }

    /**
     * Export audit trail
     */
    public function auditTrail(Request $request, $hfsId = null)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $query = \App\Models\Assets\HfsAuditLog::query()
            ->whereHas('hfsRequest', function($q) use ($user, $branchId) {
                $q->where('company_id', $user->company_id);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->with(['hfsRequest', 'user']);

        if ($hfsId) {
            $decodedId = \Vinkla\Hashids\Facades\Hashids::decode($hfsId)[0] ?? $hfsId;
            $query->where('hfs_id', $decodedId);
        }

        $auditLogs = $query->orderBy('action_date', 'desc')->get();

        if ($request->wantsJson()) {
            return response()->json($auditLogs);
        }

        return view('assets.hfs.reports.audit-trail', compact('auditLogs'));
    }

    /**
     * Aggregate P&L effects for discontinued operations
     */
    protected function aggregatePnLEffects($hfsRequests)
    {
        $revenue = 0;
        $expenses = 0;
        $preTaxProfit = 0;
        $tax = 0;
        $postTaxProfit = 0;
        $gainLossOnDisposal = 0;
        $totalImpact = 0;

        foreach ($hfsRequests as $hfs) {
            if ($hfs->discontinuedFlag && $hfs->discontinuedFlag->effects_on_pnl) {
                $effects = $hfs->discontinuedFlag->effects_on_pnl;
                $revenue += $effects['revenue'] ?? 0;
                $expenses += $effects['expenses'] ?? 0;
                $preTaxProfit += $effects['pre_tax_profit'] ?? 0;
                $tax += $effects['tax'] ?? 0;
                $postTaxProfit += $effects['post_tax_profit'] ?? 0;
                $gainLossOnDisposal += $effects['gain_loss_on_disposal'] ?? 0;
                $totalImpact += $effects['total_impact'] ?? 0;
            }
        }

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'pre_tax_profit' => $preTaxProfit,
            'tax' => $tax,
            'post_tax_profit' => $postTaxProfit,
            'gain_loss_on_disposal' => $gainLossOnDisposal,
            'total_impact' => $totalImpact,
        ];
    }
}
