<?php

namespace App\Http\Controllers\Investment;

use App\Http\Controllers\Controller;
use App\Models\Investment\InvestmentMaster;
use App\Models\Investment\InvestmentTrade;
use App\Services\Investment\InvestmentTradeService;
use App\Services\Investment\InvestmentJournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Vinkla\Hashids\Facades\Hashids;

class InvestmentTradeController extends Controller
{
    protected $tradeService;
    protected $journalService;

    public function __construct(InvestmentTradeService $tradeService, InvestmentJournalService $journalService)
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
        $this->tradeService = $tradeService;
        $this->journalService = $journalService;
    }

    /**
     * Display a listing of investment trades.
     */
    public function index(Request $request)
    {
        return view('investments.trades.index');
    }

    /**
     * Get trades data for DataTables
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $filters = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
        ];

        if ($request->has('investment_id') && $request->investment_id) {
            $filters['investment_id'] = Hashids::decode($request->investment_id)[0] ?? null;
        }

        if ($request->has('trade_type') && $request->trade_type && $request->trade_type !== 'all') {
            $filters['trade_type'] = $request->trade_type;
        }

        if ($request->has('settlement_status') && $request->settlement_status && $request->settlement_status !== 'all') {
            $filters['settlement_status'] = $request->settlement_status;
        }

        $query = $this->tradeService->getTrades($filters);
        $query->with(['investment', 'journal']);

        // Get total records before search
        $totalRecords = $query->count();

        // Search
        if ($request->has('search') && isset($request->search['value']) && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->whereHas('investment', function($subQ) use ($search) {
                    $subQ->where('instrument_code', 'like', "%{$search}%")
                         ->orWhere('issuer', 'like', "%{$search}%");
                })
                ->orWhere('bank_ref', 'like', "%{$search}%");
            });
        }

        // Get filtered records count
        $filteredRecords = $query->count();

        // DataTables ordering
        if ($request->has('order')) {
            $orderColumn = $request->order[0]['column'];
            $orderDir = $request->order[0]['dir'];
            $columns = ['trade_date', 'trade_type', 'investment_id', 'trade_price', 'trade_units', 'gross_amount', 'settlement_status', 'posted_journal_id'];
            if (isset($columns[$orderColumn])) {
                $query->orderBy($columns[$orderColumn], $orderDir);
            }
        } else {
            $query->latest('trade_date');
        }

        $trades = $query->skip($request->start ?? 0)->take($request->length ?? 25)->get();

        $data = $trades->map(function ($trade) {
            $tradeTypeBadge = match($trade->trade_type) {
                'PURCHASE' => '<span class="badge bg-success">PURCHASE</span>',
                'SALE' => '<span class="badge bg-danger">SALE</span>',
                default => '<span class="badge bg-info">' . $trade->trade_type . '</span>',
            };

            $settlementBadge = match($trade->settlement_status) {
                'SETTLED' => '<span class="badge bg-success">SETTLED</span>',
                'FAILED' => '<span class="badge bg-danger">FAILED</span>',
                default => '<span class="badge bg-warning">' . $trade->settlement_status . '</span>',
            };

            $investmentLink = $trade->investment 
                ? '<a href="' . route('investments.master.show', Hashids::encode($trade->investment_id)) . '">' . $trade->investment->instrument_code . '</a>'
                : '<span class="text-muted">N/A</span>';

            $journalBadge = $trade->posted_journal_id
                ? '<a href="' . route('accounting.journals.show', Hashids::encode($trade->posted_journal_id)) . '" class="badge bg-info">Posted</a>'
                : '<span class="badge bg-secondary">Not Posted</span>';

            $encodedId = Hashids::encode($trade->trade_id);
            $actions = '<a href="' . route('investments.trades.show', $encodedId) . '" class="btn btn-sm btn-primary" title="View"><i class="bx bx-show"></i></a>';

            return [
                'trade_date' => $trade->trade_date->format('Y-m-d'),
                'trade_type' => $tradeTypeBadge,
                'investment' => $investmentLink,
                'trade_price' => number_format($trade->trade_price, 6),
                'trade_units' => number_format($trade->trade_units, 6),
                'gross_amount' => '<div class="text-end">' . number_format($trade->gross_amount, 2) . '</div>',
                'settlement_status' => $settlementBadge,
                'journal' => $journalBadge,
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
     * Show the form for creating a new investment trade.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        // Get investments for dropdown
        $investments = InvestmentMaster::byCompany($companyId)
            ->whereIn('status', ['DRAFT', 'ACTIVE'])
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

        // Get bank accounts for GL posting
        // Filter by chart account's company through account class group
        $bankAccounts = \App\Models\BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->orderBy('name')
            ->get();

        $tradeTypes = ['PURCHASE', 'SALE', 'MATURITY', 'COUPON'];
        $settlementStatuses = ['PENDING', 'INSTRUCTED', 'SETTLED', 'FAILED'];

        // Pass investments with their instrument_type for frontend filtering
        $investmentsData = $investments->map(function($inv) {
            return [
                'id' => $inv->id,
                'instrument_code' => $inv->instrument_code,
                'issuer' => $inv->issuer,
                'instrument_type' => $inv->instrument_type,
                'display' => $inv->instrument_code . ' - ' . ($inv->issuer ?? 'N/A') . ' (' . $inv->instrument_type . ')'
            ];
        });

        return view('investments.trades.create', compact('investments', 'investment', 'bankAccounts', 'tradeTypes', 'settlementStatuses', 'investmentsData'));
    }

    /**
     * Get investment details for AJAX request
     */
    public function getInvestmentDetails($id)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $investment = InvestmentMaster::byCompany($companyId)->find($id);
        
        if (!$investment) {
            return response()->json(['error' => 'Investment not found'], 404);
        }

        return response()->json([
            'id' => $investment->id,
            'instrument_code' => $investment->instrument_code,
            'instrument_type' => $investment->instrument_type,
            'issuer' => $investment->issuer,
            'isin' => $investment->isin,
            'coupon_rate' => $investment->coupon_rate,
            'coupon_freq' => $investment->coupon_freq,
            'maturity_date' => $investment->maturity_date?->format('Y-m-d'),
            'currency' => $investment->currency,
        ]);
    }

    /**
     * Store a newly created investment trade.
     */
    public function store(Request $request)
    {
        // Get investment to determine instrument type for category-specific validation
        $investment = null;
        if ($request->investment_id) {
            $investmentId = is_numeric($request->investment_id) 
                ? $request->investment_id 
                : (Hashids::decode($request->investment_id)[0] ?? null);
            if ($investmentId) {
                $investment = InvestmentMaster::find($investmentId);
            }
        }

        // Base validation rules
        $rules = [
            'investment_id' => 'nullable|exists:investment_master,id',
            'trade_type' => 'required|in:PURCHASE,SALE,MATURITY,COUPON',
            'trade_date' => 'required|date',
            'settlement_date' => 'required|date|after_or_equal:trade_date',
            'trade_price' => 'required|numeric|min:0',
            'trade_units' => 'required|numeric|min:0.000001',
            'gross_amount' => 'nullable|numeric|min:0',
            'fees' => 'nullable|numeric|min:0',
            'tax_withheld' => 'nullable|numeric|min:0',
            'bank_ref' => 'nullable|string|max:100',
            'post_to_gl' => 'nullable|boolean',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
        ];

        // Add category-specific validation rules based on instrument type
        if ($investment) {
            $instrumentType = $investment->instrument_type;
            
            switch ($instrumentType) {
                case 'T_BOND':
                case 'CORP_BOND':
                    $rules = array_merge($rules, [
                        'coupon_rate' => 'nullable|numeric|min:0|max:100',
                        'coupon_frequency' => 'nullable|in:ANNUAL,SEMI_ANNUAL,QUARTERLY,MONTHLY',
                        'yield_to_maturity' => 'nullable|numeric|min:0|max:100',
                        'accrued_coupon_at_purchase' => 'nullable|numeric|min:0',
                        'premium_discount' => 'nullable|numeric',
                        'fair_value_source' => 'nullable|string|max:100',
                        'fair_value' => 'nullable|numeric|min:0',
                        'benchmark' => 'nullable|string|max:100',
                        'credit_risk_grade' => 'nullable|string|max:50',
                        'counterparty' => 'nullable|string|max:200',
                        'tax_withholding_rate' => 'nullable|numeric|min:0|max:100',
                    ]);
                    // BOT Required Fields for T-Bonds only
                    if ($instrumentType === 'T_BOND') {
                        $rules = array_merge($rules, [
                            'auction_no' => 'required|string|max:100',
                            'auction_date' => 'required|date',
                            'bond_type' => 'required|string|max:50',
                            'bond_price' => 'required|numeric|min:0',
                            'bond_type_other' => 'nullable|string|max:50',
                        ]);
                    }
                    if ($instrumentType === 'CORP_BOND') {
                        $rules = array_merge($rules, [
                            'issuer_name' => 'nullable|string|max:200',
                            'sector' => 'nullable|string|max:100',
                            'credit_rating' => 'nullable|string|max:50',
                            'credit_spread' => 'nullable|numeric|min:0|max:100',
                            'fair_value_method' => 'nullable|in:MARKET_PRICE,DCF',
                            'impairment_override_reason' => 'nullable|string',
                            'counterparty_broker' => 'nullable|string|max:200',
                        ]);
                    }
                    break;

                case 'T_BILL':
                    $rules = array_merge($rules, [
                        'discount_rate' => 'nullable|numeric|min:0|max:100',
                        'yield_rate' => 'nullable|numeric|min:0|max:100',
                        'maturity_days' => 'nullable|in:91,182,364',
                        'fair_value_source' => 'nullable|string|max:100',
                        'fair_value' => 'nullable|numeric|min:0',
                        'benchmark' => 'nullable|string|max:100',
                        'counterparty' => 'nullable|string|max:200',
                        // BOT Required Fields for T-Bills
                        'auction_no' => 'required|string|max:100',
                        'auction_date' => 'required|date',
                        'tbill_type' => 'required|string|max:50',
                        'tbill_price' => 'required|numeric|min:0',
                        'tbill_type_other' => 'nullable|string|max:50',
                    ]);
                    break;

                case 'FIXED_DEPOSIT':
                    $rules = array_merge($rules, [
                        'fd_reference_no' => 'nullable|string|max:100',
                        'bank_name' => 'nullable|string|max:200',
                        'branch' => 'nullable|string|max:100',
                        'interest_computation_method' => 'nullable|in:SIMPLE,COMPOUND',
                        'payout_frequency' => 'nullable|in:MONTHLY,QUARTERLY,END_MATURITY',
                        'expected_interest' => 'nullable|numeric|min:0',
                        'collateral_flag' => 'nullable|boolean',
                        'rollover_option' => 'nullable|boolean',
                        'premature_withdrawal_penalty' => 'nullable|numeric|min:0',
                        'tax_withholding_rate' => 'nullable|numeric|min:0|max:100',
                        'credit_risk_grade' => 'nullable|string|max:50',
                    ]);
                    break;

                case 'EQUITY':
                    $rules = array_merge($rules, [
                        'ticker_symbol' => 'nullable|string|max:50',
                        'company_name' => 'nullable|string|max:200',
                        'number_of_shares' => 'nullable|numeric|min:0',
                        'purchase_price_per_share' => 'nullable|numeric|min:0',
                        'fair_value' => 'nullable|numeric|min:0',
                        'fair_value_source' => 'nullable|string|max:100',
                        'dividend_rate' => 'nullable|numeric|min:0|max:100',
                        'dividend_tax_rate' => 'nullable|numeric|min:0|max:100',
                        'sector' => 'nullable|string|max:100',
                        'country' => 'nullable|string|max:100',
                        'exchange_rate' => 'nullable|numeric|min:0',
                        'impairment_indicator' => 'nullable|boolean',
                        'ecl_not_applicable_flag' => 'nullable|boolean',
                    ]);
                    break;

                case 'MMF':
                    $rules = array_merge($rules, [
                        'fund_name' => 'nullable|string|max:200',
                        'fund_manager' => 'nullable|string|max:200',
                        'units_purchased' => 'nullable|numeric|min:0',
                        'unit_price' => 'nullable|numeric|min:0',
                        'nav_price' => 'nullable|numeric|min:0',
                        'fair_value' => 'nullable|numeric|min:0',
                        'distribution_rate' => 'nullable|numeric|min:0|max:100',
                        'risk_class' => 'nullable|in:LOW,MEDIUM,HIGH',
                    ]);
                    break;

                case 'COMMERCIAL_PAPER':
                    $rules = array_merge($rules, [
                        'issuer' => 'nullable|string|max:200',
                        'discount_rate' => 'nullable|numeric|min:0|max:100',
                        'yield_rate' => 'nullable|numeric|min:0|max:100',
                        'credit_rating' => 'nullable|string|max:50',
                        'fair_value' => 'nullable|numeric|min:0',
                        'counterparty' => 'nullable|string|max:200',
                    ]);
                    break;
            }

            // IFRS 9 ECL fields (common to most categories except Equity and MMF)
            if (!in_array($instrumentType, ['EQUITY', 'MMF'])) {
                $rules = array_merge($rules, [
                    'stage' => 'nullable|integer|in:1,2,3',
                    'pd' => 'nullable|numeric|min:0|max:100',
                    'lgd' => 'nullable|numeric|min:0|max:100',
                    'ead' => 'nullable|numeric|min:0',
                    'ecl_amount' => 'nullable|numeric|min:0',
                ]);
            }
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $data = $request->all();
            
            // Decode investment_id if provided as hashid
            if (isset($data['investment_id']) && !is_numeric($data['investment_id'])) {
                $decoded = Hashids::decode($data['investment_id']);
                $data['investment_id'] = $decoded[0] ?? null;
            }

            // Handle custom bond type - if bond_type is "OTHER" and bond_type_other is provided, use the custom value
            if (isset($data['bond_type']) && $data['bond_type'] === 'OTHER' && isset($data['bond_type_other']) && !empty($data['bond_type_other'])) {
                $data['bond_type'] = $data['bond_type_other'];
            }
            // Remove bond_type_other from data as it's not a database field
            unset($data['bond_type_other']);

            // Handle custom T-Bill type - if tbill_type is "OTHER" and tbill_type_other is provided, use the custom value
            if (isset($data['tbill_type']) && $data['tbill_type'] === 'OTHER' && isset($data['tbill_type_other']) && !empty($data['tbill_type_other'])) {
                $data['tbill_type'] = $data['tbill_type_other'];
            }
            // Remove tbill_type_other from data as it's not a database field
            unset($data['tbill_type_other']);

            // Calculate gross amount if not provided
            if (empty($data['gross_amount']) || $data['gross_amount'] == 0) {
                $data['gross_amount'] = $data['trade_units'] * $data['trade_price'];
            }

            $trade = $this->tradeService->create($data, Auth::user());

            // Post to GL if requested
            if ($request->has('post_to_gl') && $request->post_to_gl && $trade->investment_id) {
                $investment = InvestmentMaster::find($trade->investment_id);
                if ($investment) {
                    $bankAccountId = $request->bank_account_id ? 
                        \App\Models\BankAccount::find($request->bank_account_id)?->chart_account_id : null;
                    
                    try {
                        $journal = $this->journalService->postPurchaseJournal($trade, $investment, Auth::user(), $bankAccountId);
                        return redirect()->route('investments.trades.show', Hashids::encode($trade->trade_id))
                            ->with('success', 'Trade created and journal posted successfully.');
                    } catch (\Exception $e) {
                        Log::error('Failed to post journal for trade', [
                            'trade_id' => $trade->trade_id,
                            'error' => $e->getMessage()
                        ]);
                        return redirect()->route('investments.trades.show', Hashids::encode($trade->trade_id))
                            ->with('warning', 'Trade created but journal posting failed: ' . $e->getMessage());
                    }
                }
            }

            return redirect()->route('investments.trades.show', Hashids::encode($trade->trade_id))
                ->with('success', 'Investment trade created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating investment trade: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create investment trade: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified investment trade.
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $trade = InvestmentTrade::byCompany($companyId)
            ->byBranch($branchId)
            ->with(['investment', 'journal.items.chartAccount', 'creator'])
            ->findOrFail($id);

        // Preview journal if not posted
        $journalPreview = null;
        if (!$trade->posted_journal_id && $trade->investment_id && $trade->trade_type === 'PURCHASE') {
            try {
                $investment = $trade->investment;
                $journalPreview = $this->journalService->previewPurchaseJournal($trade, $investment);
            } catch (\Exception $e) {
                Log::warning('Failed to preview journal', ['error' => $e->getMessage()]);
            }
        }

        return view('investments.trades.show', compact('trade', 'journalPreview'));
    }

    /**
     * Preview journal entry before posting
     */
    public function previewJournal(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return response()->json(['error' => 'Invalid trade ID'], 404);
        }

        $trade = InvestmentTrade::byCompany(Auth::user()->company_id)->findOrFail($id);
        
        if (!$trade->investment_id) {
            return response()->json(['error' => 'Trade must be linked to an investment'], 400);
        }

        if ($trade->trade_type !== 'PURCHASE') {
            return response()->json(['error' => 'Journal preview only available for purchase trades'], 400);
        }

        try {
            $investment = $trade->investment;
            $preview = $this->journalService->previewPurchaseJournal($trade, $investment);
            return response()->json($preview);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Post journal entry for trade
     */
    public function postJournal(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return redirect()->back()->with('error', 'Invalid trade ID');
        }

        $trade = InvestmentTrade::byCompany(Auth::user()->company_id)->findOrFail($id);

        if ($trade->posted_journal_id) {
            return redirect()->back()->with('error', 'Journal already posted for this trade');
        }

        if (!$trade->investment_id) {
            return redirect()->back()->with('error', 'Trade must be linked to an investment');
        }

        if ($trade->trade_type !== 'PURCHASE') {
            return redirect()->back()->with('error', 'Journal posting only available for purchase trades');
        }

        try {
            $investment = $trade->investment;
            $bankAccountId = $request->bank_account_id ? 
                \App\Models\BankAccount::find($request->bank_account_id)?->chart_account_id : null;
            
            $journal = $this->journalService->postPurchaseJournal($trade, $investment, Auth::user(), $bankAccountId);
            
            return redirect()->route('investments.trades.show', $encodedId)
                ->with('success', 'Journal posted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to post journal', [
                'trade_id' => $trade->trade_id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Failed to post journal: ' . $e->getMessage());
        }
    }

    /**
     * Update settlement status
     */
    public function updateSettlement(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return redirect()->back()->with('error', 'Invalid trade ID');
        }

        $validator = Validator::make($request->all(), [
            'settlement_status' => 'required|in:PENDING,INSTRUCTED,SETTLED,FAILED',
            'bank_ref' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        try {
            $trade = InvestmentTrade::byCompany(Auth::user()->company_id)->findOrFail($id);
            $trade = $this->tradeService->updateSettlementStatus(
                $trade,
                $request->settlement_status,
                $request->bank_ref
            );

            return redirect()->route('investments.trades.show', $encodedId)
                ->with('success', 'Settlement status updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update settlement status', [
                'trade_id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Failed to update settlement status: ' . $e->getMessage());
        }
    }
}

