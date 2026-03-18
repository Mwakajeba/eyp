<?php

namespace App\Http\Controllers\Investment;

use App\Http\Controllers\Controller;
use App\Models\Investment\InvestmentProposal;
use App\Services\Investment\InvestmentProposalService;
use App\Services\Investment\InvestmentApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;

class InvestmentProposalController extends Controller
{
    protected $proposalService;
    protected $approvalService;

    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
        $this->proposalService = app(InvestmentProposalService::class);
        $this->approvalService = app(InvestmentApprovalService::class);
    }

    /**
     * Display a listing of proposals
     */
    public function index(Request $request)
    {
        return view('investments.proposals.index');
    }

    /**
     * Get proposals data for DataTables
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;

        $query = InvestmentProposal::with(['creator', 'approver', 'recommender'])
            ->byCompany($user->company_id);

        if ($branchId) {
            $query->byBranch($branchId);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->byStatus($request->status);
        }

        // Get total records before search
        $totalRecords = $query->count();

        // Search
        if ($request->has('search') && isset($request->search['value']) && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->where('proposal_number', 'like', "%{$search}%")
                  ->orWhere('issuer', 'like', "%{$search}%")
                  ->orWhere('instrument_type', 'like', "%{$search}%");
            });
        }

        // Get filtered records count
        $filteredRecords = $query->count();

        // DataTables ordering
        if ($request->has('order')) {
            $orderColumn = $request->order[0]['column'];
            $orderDir = $request->order[0]['dir'];
            $columns = ['proposal_number', 'instrument_type', 'issuer', 'proposed_amount', 'status', 'created_at'];
            if (isset($columns[$orderColumn])) {
                $query->orderBy($columns[$orderColumn], $orderDir);
            }
        } else {
            $query->latest();
        }

        $proposals = $query->skip($request->start ?? 0)->take($request->length ?? 25)->get();

        $data = $proposals->map(function ($proposal) {
            $statusBadge = match($proposal->status) {
                'DRAFT' => '<span class="badge bg-secondary">Draft</span>',
                'SUBMITTED', 'IN_REVIEW' => '<span class="badge bg-warning">Pending</span>',
                'APPROVED' => '<span class="badge bg-success">Approved</span>',
                'REJECTED' => '<span class="badge bg-danger">Rejected</span>',
                default => '<span class="badge bg-secondary">' . $proposal->status . '</span>',
            };

            $hashId = Hashids::encode($proposal->id);
            $actions = '<div class="btn-group">';
            $actions .= '<a href="' . route('investments.proposals.show', $hashId) . '" class="btn btn-sm btn-primary" title="View"><i class="bx bx-show"></i></a>';
            if ($proposal->isDraft() || $proposal->isRejected()) {
                $actions .= '<a href="' . route('investments.proposals.edit', $hashId) . '" class="btn btn-sm btn-warning" title="Edit"><i class="bx bx-edit"></i></a>';
            }
            if ($proposal->isDraft()) {
                $actions .= '<button type="button" class="btn btn-sm btn-danger delete-proposal-btn" data-id="' . $hashId . '" data-name="' . addslashes($proposal->proposal_number) . '" title="Delete"><i class="bx bx-trash"></i></button>';
            }
            $actions .= '</div>';

            return [
                'proposal_number' => '<a href="' . route('investments.proposals.show', $hashId) . '" class="text-primary fw-bold">' . $proposal->proposal_number . '</a>',
                'instrument_type' => str_replace('_', ' ', $proposal->instrument_type),
                'issuer' => $proposal->issuer ?? 'N/A',
                'proposed_amount' => 'TZS ' . number_format($proposal->proposed_amount, 2),
                'status' => $statusBadge,
                'created_at' => $proposal->created_at->format('M d, Y'),
                'actions' => $actions,
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Show the form for creating a new proposal
     */
    public function create()
    {
        return view('investments.proposals.create');
    }

    /**
     * Store a newly created proposal
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'instrument_type' => 'required|in:T_BILL,T_BOND,FIXED_DEPOSIT,CORP_BOND,EQUITY,MMF,OTHER',
            'issuer' => 'nullable|string|max:200',
            'proposed_amount' => 'required|numeric|min:0',
            'expected_yield' => 'nullable|numeric|min:0|max:100',
            'risk_rating' => 'nullable|string|max:50',
            'tenor_days' => 'nullable|integer|min:1',
            'proposed_accounting_class' => 'required|in:AMORTISED_COST,FVOCI,FVPL',
            'description' => 'nullable|string',
            'rationale' => 'nullable|string',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        try {
            $proposal = $this->proposalService->create($validated, Auth::user());

            return redirect()->route('investments.proposals.show', $proposal)
                ->with('success', 'Investment proposal created successfully');
        } catch (\Exception $e) {
            Log::error('Failed to create proposal', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create proposal: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified proposal
     */
    public function show(InvestmentProposal $proposal)
    {
        $proposal->load(['creator', 'approver', 'rejector', 'recommender', 'approvals.approver', 'investmentAttachments']);
        
        $canApprove = $this->approvalService->canUserApprove($proposal, Auth::user());

        return view('investments.proposals.show', compact('proposal', 'canApprove'));
    }

    /**
     * Show the form for editing the specified proposal
     */
    public function edit(InvestmentProposal $proposal)
    {
        if (!$proposal->isDraft() && !$proposal->isRejected()) {
            return redirect()->route('investments.proposals.show', $proposal)
                ->with('error', 'Only DRAFT or REJECTED proposals can be edited');
        }

        return view('investments.proposals.edit', compact('proposal'));
    }

    /**
     * Update the specified proposal
     */
    public function update(Request $request, InvestmentProposal $proposal)
    {
        $validated = $request->validate([
            'instrument_type' => 'required|in:T_BILL,T_BOND,FIXED_DEPOSIT,CORP_BOND,EQUITY,MMF,OTHER',
            'issuer' => 'nullable|string|max:200',
            'proposed_amount' => 'required|numeric|min:0',
            'expected_yield' => 'nullable|numeric|min:0|max:100',
            'risk_rating' => 'nullable|string|max:50',
            'tenor_days' => 'nullable|integer|min:1',
            'proposed_accounting_class' => 'required|in:AMORTISED_COST,FVOCI,FVPL',
            'description' => 'nullable|string',
            'rationale' => 'nullable|string',
        ]);

        try {
            $proposal = $this->proposalService->update($proposal, $validated, Auth::user());

            return redirect()->route('investments.proposals.show', $proposal)
                ->with('success', 'Proposal updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update proposal', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update proposal: ' . $e->getMessage());
        }
    }

    /**
     * Submit proposal for approval
     */
    public function submitForApproval(InvestmentProposal $proposal)
    {
        try {
            $result = $this->proposalService->submitForApproval($proposal, Auth::user());

            return redirect()->route('investments.proposals.show', $proposal)
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            Log::error('Failed to submit proposal', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to submit proposal: ' . $e->getMessage());
        }
    }

    /**
     * Approve proposal
     */
    public function approve(Request $request, InvestmentProposal $proposal)
    {
        $validated = $request->validate([
            'approval_level' => 'required|integer|min:1',
            'comments' => 'nullable|string',
        ]);

        try {
            if (!$this->approvalService->canUserApprove($proposal, Auth::user())) {
                return redirect()->back()
                    ->with('error', 'You do not have permission to approve this proposal');
            }

            $result = $this->approvalService->approve(
                $proposal,
                $validated['approval_level'],
                Auth::user(),
                $validated['comments'] ?? null
            );

            return redirect()->route('investments.proposals.show', $proposal)
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            Log::error('Failed to approve proposal', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to approve proposal: ' . $e->getMessage());
        }
    }

    /**
     * Reject proposal
     */
    public function reject(Request $request, InvestmentProposal $proposal)
    {
        $validated = $request->validate([
            'approval_level' => 'required|integer|min:1',
            'reason' => 'required|string',
        ]);

        try {
            if (!$this->approvalService->canUserApprove($proposal, Auth::user())) {
                return redirect()->back()
                    ->with('error', 'You do not have permission to reject this proposal');
            }

            $result = $this->approvalService->reject(
                $proposal,
                $validated['approval_level'],
                Auth::user(),
                $validated['reason']
            );

            return redirect()->route('investments.proposals.show', $proposal)
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            Log::error('Failed to reject proposal', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to reject proposal: ' . $e->getMessage());
        }
    }

    /**
     * Convert approved proposal to investment
     */
    public function convertToInvestment(InvestmentProposal $proposal)
    {
        try {
            $result = $this->proposalService->convertToInvestment($proposal, Auth::user());

            return redirect()->route('investments.master.show', $result['investment'])
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            Log::error('Failed to convert proposal', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to convert proposal: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified proposal from storage (soft delete)
     */
    public function destroy(InvestmentProposal $proposal)
    {
        // Only allow deletion of DRAFT proposals
        if (!$proposal->isDraft()) {
            return redirect()->route('investments.proposals.show', $proposal)
                ->with('error', 'Only DRAFT proposals can be deleted.');
        }

        // Check if user has permission (same company)
        if ($proposal->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $proposalNumber = $proposal->proposal_number;
            $proposal->delete(); // Soft delete

            return redirect()->route('investments.proposals.index')
                ->with('success', "Proposal {$proposalNumber} has been deleted successfully.");
        } catch (\Exception $e) {
            Log::error('Failed to delete proposal', ['error' => $e->getMessage(), 'proposal_id' => $proposal->id]);
            return redirect()->back()
                ->with('error', 'Failed to delete proposal: ' . $e->getMessage());
        }
    }
}

