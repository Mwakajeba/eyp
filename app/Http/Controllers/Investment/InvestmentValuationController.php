<?php

namespace App\Http\Controllers\Investment;

use App\Http\Controllers\Controller;
use App\Models\Investment\InvestmentMaster;
use App\Models\Investment\InvestmentValuation;
use App\Models\Investment\InvestmentMarketPriceHistory;
use App\Services\Investment\InvestmentValuationService;
use App\Services\Investment\InvestmentRevaluationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;
use Carbon\Carbon;

class InvestmentValuationController extends Controller
{
    protected $valuationService;
    protected $revaluationService;

    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
        $this->valuationService = app(InvestmentValuationService::class);
        $this->revaluationService = app(InvestmentRevaluationService::class);
    }

    /**
     * Display a listing of valuations
     */
    public function index()
    {
        return view('investments.valuations.index');
    }

    /**
     * Get valuations data for DataTables
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $query = InvestmentValuation::with(['investment', 'creator', 'approver', 'journal'])
            ->byCompany($companyId);

        if ($branchId) {
            $query->byBranch($branchId);
        }

        // Apply filters
        if ($request->has('status') && $request->status) {
            $query->byStatus($request->status);
        }
        if ($request->has('valuation_level') && $request->valuation_level) {
            $query->byValuationLevel($request->valuation_level);
        }
        if ($request->has('investment_id') && $request->investment_id) {
            $decodedId = Hashids::decode($request->investment_id)[0] ?? null;
            if ($decodedId) {
                $query->where('investment_id', $decodedId);
            }
        }

        // Get total records before search
        $totalRecords = $query->count();

        // Apply search
        if ($request->has('search') && isset($request->search['value']) && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->whereHas('investment', function($subQ) use ($search) {
                    $subQ->where('instrument_code', 'like', '%' . $search . '%')
                        ->orWhere('issuer', 'like', '%' . $search . '%');
                })
                ->orWhere('price_reference', 'like', '%' . $search . '%');
            });
        }

        // Get filtered records count
        $filteredRecords = $query->count();

        // DataTables ordering
        if ($request->has('order')) {
            $orderColumn = $request->order[0]['column'];
            $orderDir = $request->order[0]['dir'];
            $columns = ['valuation_date', 'investment.instrument_code', 'valuation_level', 'total_fair_value', 'status'];
            if (isset($columns[$orderColumn])) {
                if ($columns[$orderColumn] === 'investment.instrument_code') {
                    $query->orderBy(InvestmentMaster::select('instrument_code')->whereColumn('investment_master.id', 'investment_valuations.investment_id'), $orderDir);
                } else {
                    $query->orderBy($columns[$orderColumn], $orderDir);
                }
            }
        } else {
            $query->latest('valuation_date');
        }

        $valuations = $query->skip($request->start ?? 0)->take($request->length ?? 25)->get();

        $data = $valuations->map(function ($valuation) {
            $statusBadge = match($valuation->status) {
                'DRAFT' => '<span class="badge bg-secondary">Draft</span>',
                'PENDING_APPROVAL' => '<span class="badge bg-warning">Pending Approval</span>',
                'APPROVED' => '<span class="badge bg-success">Approved</span>',
                'REJECTED' => '<span class="badge bg-danger">Rejected</span>',
                'POSTED' => '<span class="badge bg-info">Posted</span>',
                default => '<span class="badge bg-secondary">' . $valuation->status . '</span>',
            };

            $levelBadge = match($valuation->valuation_level) {
                1 => '<span class="badge bg-success">Level 1</span>',
                2 => '<span class="badge bg-info">Level 2</span>',
                3 => '<span class="badge bg-warning">Level 3</span>',
                default => '<span class="badge bg-secondary">Level ' . $valuation->valuation_level . '</span>',
            };

            $actions = '<div class="btn-group">';
            $actions .= '<a href="' . route('investments.valuations.show', $valuation->hash_id) . '" class="btn btn-sm btn-primary" title="View"><i class="bx bx-show"></i></a>';
            if ($valuation->isDraft() || $valuation->isPendingApproval()) {
                if (Auth::user()->can('approve', $valuation)) {
                    $actions .= '<a href="' . route('investments.valuations.approve', $valuation->hash_id) . '" class="btn btn-sm btn-success" title="Approve"><i class="bx bx-check"></i></a>';
                }
            }
            if ($valuation->isApproved() && !$valuation->isPosted()) {
                $actions .= '<a href="' . route('investments.valuations.preview', $valuation->hash_id) . '" class="btn btn-sm btn-info" title="Preview Revaluation"><i class="bx bx-search"></i></a>';
            }
            $actions .= '</div>';

            return [
                'valuation_date' => $valuation->valuation_date->format('Y-m-d'),
                'investment' => $valuation->investment ? '<a href="' . route('investments.master.show', Hashids::encode($valuation->investment_id)) . '">' . $valuation->investment->instrument_code . '</a>' : 'N/A',
                'valuation_level' => $levelBadge,
                'total_fair_value' => 'TZS ' . number_format($valuation->total_fair_value, 2),
                'unrealized_gain_loss' => '<span class="' . ($valuation->unrealized_gain_loss >= 0 ? 'text-success' : 'text-danger') . '">' . number_format($valuation->unrealized_gain_loss, 2) . '</span>',
                'status' => $statusBadge,
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
     * Show the form for creating a new valuation
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        // Get investments for dropdown (only FVPL and FVOCI)
        $investments = InvestmentMaster::byCompany($companyId)
            ->whereIn('status', ['ACTIVE'])
            ->whereIn('accounting_class', ['FVPL', 'FVOCI'])
            ->orderBy('instrument_code')
            ->get();

        // Get investment if provided
        $investment = null;
        if ($request->has('investment_id')) {
            $investmentId = Hashids::decode($request->investment_id)[0] ?? null;
            if ($investmentId) {
                $investment = InvestmentMaster::byCompany($companyId)->find($investmentId);
            }
        }

        // Get latest market price if available
        $latestPrice = null;
        if ($investment) {
            $latestPrice = $investment->latestMarketPrice;
        }

        return view('investments.valuations.create', compact('investments', 'investment', 'latestPrice'));
    }

    /**
     * Store a newly created valuation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'investment_id' => 'required|exists:investment_master,id',
            'valuation_date' => 'required|date',
            'valuation_level' => 'required|in:1,2,3',
            'valuation_method' => 'required|in:MARKET_PRICE,YIELD_CURVE,DCF,NAV,BANK_VALUATION,MANUAL',
            'fair_value_per_unit' => 'required|numeric|min:0',
            'units' => 'nullable|numeric|min:0',
            'yield_rate' => 'nullable|numeric|min:0|max:100',
            'discount_rate' => 'nullable|numeric|min:0|max:100',
            'cash_flows' => 'nullable|json',
            'valuation_inputs' => 'nullable|json',
            'valuation_assumptions' => 'nullable|string',
            'price_source' => 'nullable|string|max:200',
            'price_reference' => 'nullable|string|max:200',
            'price_date' => 'nullable|date',
        ]);

        try {
            // Decode investment_id if it's a hash ID
            $investmentId = $validated['investment_id'];
            if (!is_numeric($investmentId)) {
                $decoded = Hashids::decode($investmentId);
                $investmentId = $decoded[0] ?? null;
            }
            
            if (!$investmentId) {
                return redirect()->back()->with('error', 'Invalid investment selected.')->withInput();
            }

            $investment = InvestmentMaster::byCompany(Auth::user()->company_id)->findOrFail($investmentId);
            
            // Validate that investment is FVPL or FVOCI
            if (!in_array($investment->accounting_class, ['FVPL', 'FVOCI'])) {
                return redirect()->back()->with('error', 'Only FVPL and FVOCI investments can be valued.')->withInput();
            }

            // Parse JSON fields
            if (isset($validated['cash_flows'])) {
                $validated['cash_flows'] = json_decode($validated['cash_flows'], true);
            }
            if (isset($validated['valuation_inputs'])) {
                $validated['valuation_inputs'] = json_decode($validated['valuation_inputs'], true);
            }

            $valuation = $this->valuationService->createValuation($investment, $validated, Auth::user());

            return redirect()->route('investments.valuations.show', $valuation->hash_id)
                ->with('success', 'Valuation created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create valuation', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create valuation: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified valuation
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $valuation = InvestmentValuation::byCompany(Auth::user()->company_id)
            ->with(['investment', 'creator', 'approver', 'journal', 'journal.items.chartAccount'])
            ->findOrFail($id);

        // Get preview if not posted
        $preview = null;
        if ($valuation->isApproved() && !$valuation->isPosted()) {
            $preview = $this->revaluationService->previewRevaluation($valuation);
        }

        return view('investments.valuations.show', compact('valuation', 'preview'));
    }

    /**
     * Approve a valuation (for Level 3)
     */
    public function approve(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $valuation = InvestmentValuation::byCompany(Auth::user()->company_id)->findOrFail($id);

        if ($valuation->status !== 'PENDING_APPROVAL') {
            return redirect()->back()->with('error', 'This valuation is not pending approval.');
        }

        $validated = $request->validate([
            'approval_notes' => 'nullable|string',
        ]);

        try {
            $valuation->status = 'APPROVED';
            $valuation->approved_by = Auth::id();
            $valuation->approved_at = Carbon::now();
            $valuation->approval_notes = $validated['approval_notes'] ?? null;
            $valuation->updated_by = Auth::id();
            $valuation->save();

            return redirect()->route('investments.valuations.show', $valuation->hash_id)
                ->with('success', 'Valuation approved successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to approve valuation', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to approve valuation: ' . $e->getMessage());
        }
    }

    /**
     * Preview revaluation journal before posting
     */
    public function preview($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $valuation = InvestmentValuation::byCompany(Auth::user()->company_id)->findOrFail($id);

        if (!$valuation->isApproved()) {
            return redirect()->back()->with('error', 'Valuation must be approved before previewing revaluation.');
        }

        if ($valuation->isPosted()) {
            return redirect()->back()->with('error', 'Revaluation has already been posted.');
        }

        $preview = $this->revaluationService->previewRevaluation($valuation);
        
        return view('investments.valuations.preview', compact('valuation', 'preview'));
    }

    /**
     * Post revaluation journal
     */
    public function post($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $valuation = InvestmentValuation::byCompany(Auth::user()->company_id)->findOrFail($id);

        if (!$valuation->isApproved()) {
            return redirect()->back()->with('error', 'Valuation must be approved before posting.');
        }

        if ($valuation->isPosted()) {
            return redirect()->back()->with('error', 'Revaluation has already been posted.');
        }

        try {
            $journal = $this->revaluationService->processRevaluation($valuation, Auth::user());

            return redirect()->route('investments.valuations.show', $valuation->hash_id)
                ->with('success', 'Revaluation posted successfully. Journal: ' . $journal->journal_number);
        } catch (\Exception $e) {
            Log::error('Failed to post revaluation', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to post revaluation: ' . $e->getMessage());
        }
    }

    /**
     * Store market price
     */
    public function storeMarketPrice(Request $request)
    {
        $validated = $request->validate([
            'investment_id' => 'required|exists:investment_master,id',
            'price_date' => 'required|date',
            'market_price' => 'required|numeric|min:0',
            'bid_price' => 'nullable|numeric|min:0',
            'ask_price' => 'nullable|numeric|min:0',
            'price_source' => 'required|in:BOT,DSE,BLOOMBERG,REUTERS,MANUAL,INTERNAL,OTHER',
            'source_reference' => 'nullable|string|max:200',
            'source_url' => 'nullable|url|max:500',
            'yield_rate' => 'nullable|numeric|min:0|max:100',
            'volume' => 'nullable|numeric|min:0',
        ]);

        try {
            $investmentId = Hashids::decode($validated['investment_id'])[0] ?? null;
            if (!$investmentId) {
                return response()->json(['error' => 'Invalid investment'], 400);
            }

            $investment = InvestmentMaster::byCompany(Auth::user()->company_id)->findOrFail($investmentId);

            $priceHistory = $this->valuationService->storeMarketPrice($investment, $validated, Auth::user());

            return response()->json([
                'success' => true,
                'message' => 'Market price stored successfully',
                'data' => $priceHistory,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store market price', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
