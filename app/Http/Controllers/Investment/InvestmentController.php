<?php

namespace App\Http\Controllers\Investment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestmentController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    /**
     * Display the Investment Management dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $companyId = $user->company_id;

        // Get actual counts from database
        try {
            $totalInvestments = \App\Models\Investment\InvestmentMaster::where('company_id', $companyId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->count();
            
            $activeInvestments = \App\Models\Investment\InvestmentMaster::where('company_id', $companyId)
                ->where('status', 'ACTIVE')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->count();
            
            $totalProposals = \App\Models\Investment\InvestmentProposal::where('company_id', $companyId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->count();
            
            $pendingProposals = \App\Models\Investment\InvestmentProposal::where('company_id', $companyId)
                ->whereIn('status', ['SUBMITTED', 'IN_REVIEW'])
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->count();
            
            $totalPortfolioValue = \App\Models\Investment\InvestmentMaster::where('company_id', $companyId)
                ->where('status', 'ACTIVE')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->get()
                ->sum(fn($inv) => $inv->carrying_amount ?? 0);
            
            $maturedInvestments = \App\Models\Investment\InvestmentMaster::where('company_id', $companyId)
                ->where('status', 'MATURED')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->count();
            
            $disposedInvestments = \App\Models\Investment\InvestmentMaster::where('company_id', $companyId)
                ->where('status', 'DISPOSED')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->count();
            
            $thisMonthInvestments = \App\Models\Investment\InvestmentMaster::where('company_id', $companyId)
                ->whereMonth('purchase_date', now()->month)
                ->whereYear('purchase_date', now()->year)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->count();
            
            $pendingSettlements = \App\Models\Investment\InvestmentTrade::where('company_id', $companyId)
                ->where('settlement_status', 'PENDING')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->count();
            
            $pendingValuations = \App\Models\Investment\InvestmentValuation::where('company_id', $companyId)
                ->whereIn('status', ['DRAFT', 'PENDING_APPROVAL'])
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->count();
            
            $investmentsWithAmortization = \App\Models\Investment\InvestmentMaster::where('company_id', $companyId)
                ->where('status', 'ACTIVE')
                ->whereHas('amortizationLines')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->count();
        } catch (\Exception $e) {
            // If tables don't exist yet, use placeholder values
            $totalInvestments = 0;
            $activeInvestments = 0;
            $totalProposals = 0;
            $pendingProposals = 0;
            $totalPortfolioValue = 0;
            $maturedInvestments = 0;
            $disposedInvestments = 0;
            $thisMonthInvestments = 0;
            $pendingSettlements = 0;
            $pendingValuations = 0;
            $investmentsWithAmortization = 0;
        }

        return view('investments.index', compact(
            'totalInvestments',
            'activeInvestments',
            'totalProposals',
            'pendingProposals',
            'totalPortfolioValue',
            'maturedInvestments',
            'disposedInvestments',
            'thisMonthInvestments',
            'pendingSettlements',
            'pendingValuations',
            'investmentsWithAmortization'
        ));
    }
}

