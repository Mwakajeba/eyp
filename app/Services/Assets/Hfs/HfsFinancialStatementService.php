<?php

namespace App\Services\Assets\Hfs;

use App\Models\Assets\HfsRequest;
use App\Models\Assets\HfsDiscontinuedFlag;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service for integrating HFS data into financial statements
 * - Balance Sheet: HFS assets line item
 * - Income Statement: Discontinued operations lines
 * - Cash Flow: Discontinued operations cash flows
 */
class HfsFinancialStatementService
{
    /**
     * Get HFS assets balance for balance sheet
     * Returns total carrying amount of assets classified as HFS
     */
    public function getHfsAssetsBalance($companyId, $asOfDate, $branchId = null): float
    {
        $query = DB::table('hfs_assets')
            ->join('hfs_requests', 'hfs_assets.hfs_id', '=', 'hfs_requests.hfs_id')
            ->where('hfs_requests.company_id', $companyId)
            ->where('hfs_requests.status', '!=', 'CANCELLED')
            ->where('hfs_requests.status', '!=', 'SOLD')
            ->whereDate('hfs_assets.reclassified_date', '<=', $asOfDate);

        if ($branchId && $branchId !== 'all') {
            $query->where('hfs_requests.branch_id', $branchId);
        }

        // Get current carrying amount (after impairments/reversals)
        // This is a simplified calculation - in production, you'd need to track
        // the current carrying amount after all valuations
        $total = $query->sum('hfs_assets.carrying_amount_at_reclass');

        // Subtract total impairments and add reversals
        $impairments = DB::table('hfs_valuations')
            ->join('hfs_requests', 'hfs_valuations.hfs_id', '=', 'hfs_requests.hfs_id')
            ->where('hfs_requests.company_id', $companyId)
            ->where('hfs_valuations.impairment_amount', '>', 0)
            ->whereDate('hfs_valuations.valuation_date', '<=', $asOfDate)
            ->when($branchId && $branchId !== 'all', function($q) use ($branchId) {
                $q->where('hfs_requests.branch_id', $branchId);
            })
            ->sum('hfs_valuations.impairment_amount');

        // Note: Reversals are stored as negative impairment_amount in this simplified model
        // In production, you'd have a separate reversal_amount field
        $reversals = 0; // TODO: Calculate from reversal journals if tracked separately

        return max(0, $total - $impairments + $reversals);
    }

    /**
     * Get discontinued operations P&L data for income statement
     * Returns revenue, expenses, profit/(loss), gain/(loss) on disposal, tax, net impact
     */
    public function getDiscontinuedOperationsData($companyId, $startDate, $endDate, $branchId = null): array
    {
        // Get all discontinued operations in the period
        $query = DB::table('hfs_discontinued_flags')
            ->join('hfs_requests', 'hfs_discontinued_flags.hfs_id', '=', 'hfs_requests.hfs_id')
            ->where('hfs_discontinued_flags.is_discontinued', true)
            ->where('hfs_requests.company_id', $companyId)
            ->whereDate('hfs_discontinued_flags.discontinued_date', '>=', $startDate)
            ->whereDate('hfs_discontinued_flags.discontinued_date', '<=', $endDate);

        if ($branchId && $branchId !== 'all') {
            $query->where('hfs_requests.branch_id', $branchId);
        }

        $discontinuedOps = $query->get();

        $totalRevenue = 0;
        $totalExpenses = 0;
        $totalGainLossOnDisposal = 0;
        $totalTax = 0;

        foreach ($discontinuedOps as $op) {
            // Parse effects_on_pnl_json if available
            $effects = json_decode($op->effects_on_pnl_json ?? '{}', true);
            
            $totalRevenue += ($effects['revenue'] ?? 0);
            $totalExpenses += ($effects['expenses'] ?? 0);
            
            // Get gain/loss on disposal if sold
            $disposal = DB::table('hfs_disposals')
                ->where('hfs_id', $op->hfs_id)
                ->whereDate('disposal_date', '>=', $startDate)
                ->whereDate('disposal_date', '<=', $endDate)
                ->first();
            
            if ($disposal) {
                $totalGainLossOnDisposal += ($disposal->gain_loss_amount ?? 0);
            }
            
            // Tax would be calculated separately or stored in effects_on_pnl_json
            $totalTax += ($effects['tax'] ?? 0);
        }

        $profitLoss = $totalRevenue - $totalExpenses;
        $netImpact = $profitLoss + $totalGainLossOnDisposal - $totalTax;

        return [
            'revenue' => $totalRevenue,
            'expenses' => $totalExpenses,
            'profit_loss' => $profitLoss,
            'gain_loss_on_disposal' => $totalGainLossOnDisposal,
            'tax' => $totalTax,
            'net_impact' => $netImpact,
        ];
    }

    /**
     * Get comparative discontinued operations data for prior period
     */
    public function getDiscontinuedOperationsComparative($companyId, $startDate, $endDate, $branchId = null): array
    {
        // Same logic but for prior period
        return $this->getDiscontinuedOperationsData($companyId, $startDate, $endDate, $branchId);
    }

    /**
     * Get HFS movement schedule data for IFRS 5 note
     */
    public function getHfsMovementSchedule($companyId, $startDate, $endDate, $branchId = null): array
    {
        $query = DB::table('hfs_assets')
            ->join('hfs_requests', 'hfs_assets.hfs_id', '=', 'hfs_requests.hfs_id')
            ->leftJoin('assets', 'hfs_assets.asset_id', '=', 'assets.id')
            ->leftJoin('asset_categories', 'assets.asset_category_id', '=', 'asset_categories.id')
            ->where('hfs_requests.company_id', $companyId)
            ->where(function($q) use ($startDate, $endDate) {
                // Assets classified during period or still active at end date
                $q->whereBetween('hfs_assets.reclassified_date', [$startDate, $endDate])
                  ->orWhere(function($q2) use ($endDate) {
                      $q2->where('hfs_requests.status', '!=', 'SOLD')
                         ->where('hfs_requests.status', '!=', 'CANCELLED')
                         ->whereDate('hfs_assets.reclassified_date', '<=', $endDate);
                  });
            });

        if ($branchId && $branchId !== 'all') {
            $query->where('hfs_requests.branch_id', $branchId);
        }

        return $query->select(
            'hfs_assets.hfs_asset_id',
            'hfs_requests.request_no',
            'assets.name as asset_name',
            'assets.code as asset_code',
            'asset_categories.name as category_name',
            'hfs_assets.carrying_amount_at_reclass',
            'hfs_assets.reclassified_date',
            'hfs_requests.status',
            'hfs_requests.expected_close_date'
        )->get()->toArray();
    }

    /**
     * Get gains/(losses) on sale of HFS assets in period
     */
    public function getHfsGainsLossesOnSale($companyId, $startDate, $endDate, $branchId = null): float
    {
        $query = DB::table('hfs_disposals')
            ->join('hfs_requests', 'hfs_disposals.hfs_id', '=', 'hfs_requests.hfs_id')
            ->where('hfs_requests.company_id', $companyId)
            ->whereDate('hfs_disposals.disposal_date', '>=', $startDate)
            ->whereDate('hfs_disposals.disposal_date', '<=', $endDate);

        if ($branchId && $branchId !== 'all') {
            $query->where('hfs_requests.branch_id', $branchId);
        }

        return (float) $query->sum('hfs_disposals.gain_loss_amount');
    }
}

