<?php

namespace App\Http\Controllers\Investment;

use App\Http\Controllers\Controller;
use App\Models\Investment\InvestmentMaster;
use App\Services\Investment\InvestmentMasterService;
use App\Services\Investment\EirCalculatorService;
use App\Services\Investment\InvestmentAmortizationService;
use App\Services\Investment\InvestmentAccrualService;
use App\Services\Investment\CouponPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;
use Carbon\Carbon;

class InvestmentMasterController extends Controller
{
    protected $masterService;

    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
        $this->masterService = app(InvestmentMasterService::class);
    }

    /**
     * Display a listing of investments
     */
    public function index(Request $request)
    {
        return view('investments.master.index');
    }

    /**
     * Get investments data for DataTables
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;

        $filters = [
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
        ];

        $query = $this->masterService->getInvestments($filters);
        $query->with(['company', 'branch', 'creator']);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by instrument type
        if ($request->has('instrument_type') && $request->instrument_type) {
            $query->where('instrument_type', $request->instrument_type);
        }

        // Filter by investments with amortization schedules
        if ($request->has('has_amortization') && $request->has_amortization) {
            // Decode hashed value if needed
            $hasAmortization = $request->has_amortization;
            try {
                $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hasAmortization);
                if (!empty($decoded) && $decoded[0] == 1) {
                    $query->whereHas('amortizationLines');
                }
            } catch (\Exception $e) {
                // If decoding fails, check if it's a plain "1"
                if ($hasAmortization == 1 || $hasAmortization == '1') {
                    $query->whereHas('amortizationLines');
                }
            }
        }

        // Get total records before search
        $totalRecords = $query->count();

        // Search
        if ($request->has('search') && isset($request->search['value']) && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->where('instrument_code', 'like', "%{$search}%")
                  ->orWhere('issuer', 'like', "%{$search}%")
                  ->orWhere('isin', 'like', "%{$search}%")
                  ->orWhere('instrument_type', 'like', "%{$search}%");
            });
        }

        // Get filtered records count
        $filteredRecords = $query->count();

        // DataTables ordering
        if ($request->has('order')) {
            $orderColumn = $request->order[0]['column'];
            $orderDir = $request->order[0]['dir'];
            $columns = ['instrument_code', 'instrument_type', 'issuer', 'carrying_amount', 'status', 'maturity_date'];
            if (isset($columns[$orderColumn])) {
                $query->orderBy($columns[$orderColumn], $orderDir);
            }
        } else {
            $query->latest();
        }

        $investments = $query->skip($request->start ?? 0)->take($request->length ?? 25)->get();

        $data = $investments->map(function ($investment) {
            $statusBadge = match($investment->status) {
                'DRAFT' => '<span class="badge bg-secondary">Draft</span>',
                'ACTIVE' => '<span class="badge bg-success">Active</span>',
                'MATURED' => '<span class="badge bg-warning">Matured</span>',
                'DISPOSED' => '<span class="badge bg-dark">Disposed</span>',
                default => '<span class="badge bg-secondary">' . $investment->status . '</span>',
            };

            $encodedId = Hashids::encode($investment->id);
            $actions = '<div class="btn-group">';
            $actions .= '<a href="' . route('investments.master.show', $encodedId) . '" class="btn btn-sm btn-primary" title="View"><i class="bx bx-show"></i></a>';
            if ($investment->status == 'DRAFT') {
                $actions .= '<a href="' . route('investments.master.edit', $encodedId) . '" class="btn btn-sm btn-warning" title="Edit"><i class="bx bx-edit"></i></a>';
            }
            $actions .= '</div>';

            return [
                'instrument_code' => '<a href="' . route('investments.master.show', $encodedId) . '" class="text-primary fw-bold">' . $investment->instrument_code . '</a>',
                'instrument_type' => str_replace('_', ' ', $investment->instrument_type),
                'issuer' => $investment->issuer ?? 'N/A',
                'carrying_amount' => 'TZS ' . number_format($investment->carrying_amount, 2),
                'status' => $statusBadge,
                'maturity_date' => $investment->maturity_date ? $investment->maturity_date->format('M d, Y') : 'N/A',
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
     * Display the specified investment
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $user = Auth::user();
        $master = InvestmentMaster::byCompany($user->company_id)
            ->with(['company', 'branch', 'creator', 'updater', 'trades', 'investmentAttachments', 'amortizationLines.journal'])
            ->findOrFail($id);

        // Load amortization lines
        $amortizationLines = $master->amortizationLines()
            ->orderBy('period_end', 'asc')
            ->get();

        // Get latest purchase trade for ECL and fair value info
        $latestTrade = $master->trades()
            ->where('trade_type', 'PURCHASE')
            ->latest('trade_date')
            ->first();

        // Calculate days to maturity
        $daysToMaturity = $master->maturity_date 
            ? now()->diffInDays($master->maturity_date, false) 
            : null;

        return view('investments.master.show', compact('master', 'amortizationLines', 'latestTrade', 'daysToMaturity'));
    }

    /**
     * Show the form for editing the specified investment
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $user = Auth::user();
        $master = InvestmentMaster::byCompany($user->company_id)->findOrFail($id);

        if ($master->status !== 'ACTIVE' && $master->status !== 'DRAFT') {
            return redirect()->route('investments.master.show', $encodedId)
                ->with('error', 'Only DRAFT investments can be edited');
        }

        // Get the proposal that was converted to this investment (if any)
        $sourceProposal = \App\Models\Investment\InvestmentProposal::where('converted_to_investment_id', $id)->first();

        return view('investments.master.edit', compact('master', 'sourceProposal'));
    }

    /**
     * Update the specified investment
     */
    public function update(Request $request, $encodedId)
    {
        $validated = $request->validate([
            'issuer' => 'nullable|string|max:200',
            'isin' => 'nullable|string|max:50',
            'maturity_date' => 'nullable|date',
            'currency' => 'required|string|max:10',
            'day_count' => 'nullable|string|max:20',
            'coupon_rate' => 'nullable|numeric|min:0|max:100',
            'coupon_freq' => 'nullable|integer|min:1',
            'tax_class' => 'nullable|string|max:50',
            'tax_class_custom' => 'nullable|string|max:50',
            'nominal_amount' => 'nullable|numeric|min:0',
        ]);

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $user = Auth::user();
        $master = InvestmentMaster::byCompany($user->company_id)->findOrFail($id);

        try {
            // Convert coupon_rate from percentage to decimal if provided
            if (isset($validated['coupon_rate']) && $validated['coupon_rate'] !== null) {
                $validated['coupon_rate'] = $validated['coupon_rate'] / 100;
            }

            // Handle custom tax class - if tax_class is "OTHER" and tax_class_custom is provided, use the custom value
            if (isset($validated['tax_class']) && $validated['tax_class'] === 'OTHER' && isset($validated['tax_class_custom']) && !empty($validated['tax_class_custom'])) {
                $validated['tax_class'] = $validated['tax_class_custom'];
            }
            // Remove tax_class_custom from validated data as it's not a database field
            unset($validated['tax_class_custom']);

            $master = $this->masterService->update($master, $validated, Auth::user());

            return redirect()->route('investments.master.show', $encodedId)
                ->with('success', 'Investment updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update investment', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update investment: ' . $e->getMessage());
        }
    }

    /**
     * Recalculate EIR for investment
     */
    public function recalculateEir($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $user = Auth::user();
        $master = InvestmentMaster::byCompany($user->company_id)->findOrFail($id);

        try {
            $eirService = app(EirCalculatorService::class);
            $result = $eirService->recalculateEir($master);

            return redirect()->route('investments.master.show', $encodedId)
                ->with('success', "EIR recalculated: {$result['eir']}% ({$result['iterations']} iterations, " . ($result['converged'] ? 'converged' : 'not converged') . ")");
        } catch (\Exception $e) {
            Log::error('Failed to recalculate EIR', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to recalculate EIR: ' . $e->getMessage());
        }
    }

    /**
     * Generate/regenerate amortization schedule
     */
    public function generateAmortization($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $user = Auth::user();
        $master = InvestmentMaster::byCompany($user->company_id)->findOrFail($id);

        try {
            $amortService = app(InvestmentAmortizationService::class);
            
            if ($request->has('recompute') && $request->recompute) {
                $lines = $amortService->recomputeAmortizationSchedule($master);
                $message = "Amortization schedule recomputed. {$lines->count()} lines generated.";
            } else {
                $lines = $amortService->saveAmortizationSchedule($master);
                $message = "Amortization schedule generated. {$lines->count()} lines created.";
            }

            return redirect()->route('investments.master.show', $encodedId)
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to generate amortization', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to generate amortization schedule: ' . $e->getMessage());
        }
    }

    /**
     * View amortization schedule
     */
    public function amortizationSchedule($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $user = Auth::user();
        $master = InvestmentMaster::byCompany($user->company_id)
            ->with(['amortizationLines.journal'])
            ->findOrFail($id);

        $amortizationLines = $master->amortizationLines()
            ->orderBy('period_end', 'asc')
            ->get();

        return view('investments.master.amortization', compact('master', 'amortizationLines'));
    }

    /**
     * Post accrual for investment
     */
    public function postAccrual($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $user = Auth::user();
        $master = InvestmentMaster::byCompany($user->company_id)->findOrFail($id);

        $request->validate([
            'accrual_date' => 'required|date',
        ]);

        try {
            $accrualService = app(InvestmentAccrualService::class);
            $accrualDate = Carbon::parse($request->accrual_date);
            $journal = $accrualService->accrueInterest($master, $accrualDate, $user);

            if ($journal) {
                return redirect()->route('investments.master.show', $encodedId)
                    ->with('success', "Interest accrued successfully. Journal #{$journal->id} created.");
            } else {
                return redirect()->back()->with('warning', 'No pending accrual found for this period.');
            }
        } catch (\Exception $e) {
            Log::error('Failed to post accrual', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to post accrual: ' . $e->getMessage());
        }
    }

    /**
     * Process coupon payment
     */
    public function processCouponPayment($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $user = Auth::user();
        $master = InvestmentMaster::byCompany($user->company_id)->findOrFail($id);

        $request->validate([
            'coupon_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'bank_ref' => 'nullable|string|max:100',
        ]);

        try {
            $couponService = app(CouponPaymentService::class);
            $paymentDate = Carbon::parse($request->payment_date);
            $result = $couponService->processCouponPayment(
                $master,
                $request->coupon_amount,
                $paymentDate,
                $request->bank_ref,
                $user
            );

            return redirect()->route('investments.master.show', $encodedId)
                ->with('success', "Coupon payment processed successfully. Journal #{$result['journal']->id} created.");
        } catch (\Exception $e) {
            Log::error('Failed to process coupon payment', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to process coupon payment: ' . $e->getMessage());
        }
    }
}

