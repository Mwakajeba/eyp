<?php

namespace App\Http\Controllers\Investment;

use App\Http\Controllers\Controller;
use App\Models\Investment\InvestmentMaster;
use App\Models\Investment\EclCalc;
use App\Services\Investment\EclCalculationService;
use App\Services\Investment\EclJournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;
use Carbon\Carbon;

class EclCalculationController extends Controller
{
    protected $eclCalculationService;
    protected $eclJournalService;

    public function __construct(
        EclCalculationService $eclCalculationService,
        EclJournalService $eclJournalService
    ) {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
        $this->eclCalculationService = $eclCalculationService;
        $this->eclJournalService = $eclJournalService;
    }

    /**
     * Display ECL calculation dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        // Get summary statistics
        $totalCalculations = EclCalc::where('company_id', $companyId)->count();
        $pendingPostings = EclCalc::where('company_id', $companyId)
            ->where('is_posted', false)
            ->where('status', 'CALCULATED')
            ->count();
        $totalEclAmount = EclCalc::where('company_id', $companyId)
            ->where('is_posted', true)
            ->sum('ecl_amount');

        return view('investments.ecl.index', compact('totalCalculations', 'pendingPostings', 'totalEclAmount'));
    }

    /**
     * Get ECL calculations data for DataTables
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $query = EclCalc::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['investment', 'creator']);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by stage
        if ($request->has('stage') && $request->stage) {
            $query->where('stage', $request->stage);
        }

        // Filter by calculation date
        if ($request->has('calculation_date') && $request->calculation_date) {
            $query->where('calculation_date', $request->calculation_date);
        }

        $totalRecords = $query->count();

        // Search
        if ($request->has('search') && isset($request->search['value']) && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->whereHas('investment', function($subQ) use ($search) {
                    $subQ->where('instrument_code', 'like', "%{$search}%")
                         ->orWhere('issuer', 'like', "%{$search}%");
                })
                ->orWhere('calculation_run_id', 'like', "%{$search}%");
            });
        }

        $filteredRecords = $query->count();

        // Ordering
        if ($request->has('order')) {
            $orderColumn = $request->order[0]['column'];
            $orderDir = $request->order[0]['dir'];
            $columns = ['calculation_date', 'investment_id', 'stage', 'ecl_amount', 'status'];
            if (isset($columns[$orderColumn])) {
                $query->orderBy($columns[$orderColumn], $orderDir);
            }
        } else {
            $query->latest('calculation_date');
        }

        $calculations = $query->skip($request->start ?? 0)->take($request->length ?? 25)->get();

        $data = $calculations->map(function ($calc) {
            $encodedId = Hashids::encode($calc->id);
            $stageBadge = match($calc->stage) {
                1 => '<span class="badge bg-success">Stage 1</span>',
                2 => '<span class="badge bg-warning">Stage 2</span>',
                3 => '<span class="badge bg-danger">Stage 3</span>',
                default => '<span class="badge bg-secondary">Stage ' . $calc->stage . '</span>',
            };

            $statusBadge = match($calc->status) {
                'CALCULATED' => '<span class="badge bg-info">Calculated</span>',
                'REVIEWED' => '<span class="badge bg-primary">Reviewed</span>',
                'APPROVED' => '<span class="badge bg-success">Approved</span>',
                'POSTED' => '<span class="badge bg-success">Posted</span>',
                default => '<span class="badge bg-secondary">' . $calc->status . '</span>',
            };

            $actions = '<div class="btn-group">';
            $actions .= '<a href="' . route('investments.ecl.show', $encodedId) . '" class="btn btn-sm btn-primary" title="View"><i class="bx bx-show"></i></a>';
            if (!$calc->is_posted) {
                $actions .= '<a href="' . route('investments.ecl.post', $encodedId) . '" class="btn btn-sm btn-success" title="Post Journal" onclick="return confirm(\'Post ECL allowance journal?\')"><i class="bx bx-check"></i></a>';
            }
            $actions .= '</div>';

            return [
                'calculation_date' => $calc->calculation_date->format('M d, Y'),
                'investment' => $calc->investment ? '<a href="' . route('investments.master.show', Hashids::encode($calc->investment_id)) . '">' . $calc->investment->instrument_code . '</a>' : 'N/A',
                'stage' => $stageBadge,
                'ecl_amount' => 'TZS ' . number_format($calc->ecl_amount, 2),
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
     * Show the form for running a new ECL calculation
     */
    public function create()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        // Get investments eligible for ECL calculation
        $investments = InvestmentMaster::where('company_id', $companyId)
            ->where('status', 'ACTIVE')
            ->whereIn('instrument_type', ['T_BOND', 'T_BILL', 'FIXED_DEPOSIT', 'CORP_BOND', 'COMMERCIAL_PAPER'])
            ->get();

        return view('investments.ecl.create', compact('investments'));
    }

    /**
     * Run ECL calculation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'calculation_date' => 'required|date',
            'investment_ids' => 'nullable|array',
            'investment_ids.*' => 'exists:investment_master,id',
            'forward_looking_info' => 'nullable|array',
        ]);

        try {
            $user = Auth::user();
            $companyId = $user->company_id;
            $calculationDate = Carbon::parse($validated['calculation_date']);
            $calculationRunId = 'ECL-RUN-' . $calculationDate->format('Ymd') . '-' . uniqid();

            // Get investments to calculate
            if (isset($validated['investment_ids']) && !empty($validated['investment_ids'])) {
                $investments = InvestmentMaster::where('company_id', $companyId)
                    ->whereIn('id', $validated['investment_ids'])
                    ->get();
            } else {
                // Calculate for all eligible investments
                $investments = InvestmentMaster::where('company_id', $companyId)
                    ->where('status', 'ACTIVE')
                    ->whereIn('instrument_type', ['T_BOND', 'T_BILL', 'FIXED_DEPOSIT', 'CORP_BOND', 'COMMERCIAL_PAPER'])
                    ->get();
            }

            $forwardLookingInfo = $validated['forward_looking_info'] ?? [];

            $results = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($investments as $investment) {
                try {
                    $eclCalc = $this->eclCalculationService->calculateEcl(
                        $investment,
                        $calculationDate,
                        $forwardLookingInfo,
                        $calculationRunId
                    );
                    $results[] = $eclCalc;
                    $successCount++;
                } catch (\Exception $e) {
                    Log::error('ECL calculation failed for investment', [
                        'investment_id' => $investment->id,
                        'error' => $e->getMessage(),
                    ]);
                    $errorCount++;
                }
            }

            $message = "ECL calculation completed. Success: {$successCount}, Errors: {$errorCount}";
            
            return redirect()->route('investments.ecl.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to run ECL calculation', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to run ECL calculation: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified ECL calculation
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $user = Auth::user();
        $eclCalc = EclCalc::where('company_id', $user->company_id)
            ->with(['investment', 'eclInput', 'eclScenario', 'journal', 'creator'])
            ->findOrFail($id);

        return view('investments.ecl.show', compact('eclCalc'));
    }

    /**
     * Post ECL allowance journal
     */
    public function post($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $user = Auth::user();
        $eclCalc = EclCalc::where('company_id', $user->company_id)
            ->findOrFail($id);

        if ($eclCalc->is_posted) {
            return redirect()->back()
                ->with('error', 'ECL allowance journal already posted');
        }

        try {
            $journal = $this->eclJournalService->postEclAllowance($eclCalc, $user);

            return redirect()->route('investments.ecl.show', $encodedId)
                ->with('success', 'ECL allowance journal posted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to post ECL allowance journal', [
                'ecl_calc_id' => $eclCalc->id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()
                ->with('error', 'Failed to post ECL allowance journal: ' . $e->getMessage());
        }
    }
}

