<?php

namespace App\Http\Controllers;

use App\Models\ImprestRequest;
use App\Models\ImprestLiquidation;
use App\Models\ImprestLiquidationItem;
use App\Models\ImprestJournalEntry;
use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ImprestLiquidationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the form for creating a liquidation
     */
    public function create($imprestRequestId)
    {
        $imprestRequest = ImprestRequest::with(['disbursement', 'employee', 'department'])
            ->findOrFail($imprestRequestId);

        if (!$imprestRequest->canBeLiquidated()) {
            return redirect()->route('imprest.requests.show', $imprestRequestId)
                ->withErrors(['error' => 'This imprest cannot be liquidated at this time.']);
        }

        // Get all chart accounts for the dropdown (user can select appropriate expense accounts)
        $expenseAccounts = ChartAccount::whereHas('accountClassGroup', function($q) {
            $q->where('company_id', Auth::user()->company_id);
        })
        ->orderBy('account_name')
        ->get();

        return view('imprest.liquidation.create', compact('imprestRequest', 'expenseAccounts'));
    }

    /**
     * Store the liquidation
     */
    public function store(Request $request, $imprestRequestId)
    {
        $imprestRequest = ImprestRequest::with('disbursement')->findOrFail($imprestRequestId);

        if (!$imprestRequest->canBeLiquidated()) {
            return response()->json(['error' => 'This imprest cannot be liquidated at this time.'], 400);
        }

        $request->validate([
            'liquidation_date' => 'required|date|before_or_equal:today',
            'liquidation_notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.expense_category' => 'required|string|max:200',
            'items.*.description' => 'required|string|max:500',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.expense_date' => 'required|date|before_or_equal:today',
            'items.*.receipt_number' => 'nullable|string|max:100',
            'items.*.supplier_name' => 'nullable|string|max:200',
            'items.*.chart_account_id' => 'required|exists:chart_accounts,id',
        ]);

        // Validate total expenses don't exceed disbursed amount
        $totalExpenses = collect($request->items)->sum('amount');
        $disbursedAmount = $imprestRequest->disbursement->amount_issued;

        if ($totalExpenses > $disbursedAmount) {
            return response()->json([
                'error' => 'Total expenses (' . number_format($totalExpenses, 2) . ') cannot exceed disbursed amount (' . number_format($disbursedAmount, 2) . ').'
            ], 400);
        }

        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            // Create liquidation
            $liquidation = ImprestLiquidation::create([
                'imprest_request_id' => $imprestRequest->id,
                'liquidation_number' => ImprestLiquidation::generateLiquidationNumber(),
                'total_spent' => $totalExpenses,
                'balance_returned' => $disbursedAmount - $totalExpenses,
                'liquidation_date' => $request->liquidation_date,
                'liquidation_notes' => $request->liquidation_notes,
                'submitted_by' => $user->id,
                'submitted_at' => now(),
            ]);

            // Create liquidation items
            foreach ($request->items as $item) {
                ImprestLiquidationItem::create([
                    'imprest_liquidation_id' => $liquidation->id,
                    'expense_category' => $item['expense_category'],
                    'description' => $item['description'],
                    'amount' => $item['amount'],
                    'expense_date' => $item['expense_date'],
                    'receipt_number' => $item['receipt_number'],
                    'supplier_name' => $item['supplier_name'],
                    'chart_account_id' => $item['chart_account_id'],
                ]);
            }

            // Update imprest request status
            $imprestRequest->update(['status' => 'liquidated']);

            DB::commit();

            return response()->json([
                'success' => 'Liquidation submitted successfully.',
                'redirect' => route('imprest.requests.show', $imprestRequest->id)
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to submit liquidation.'], 500);
        }
    }

    /**
     * Show liquidation details
     */
    public function show($id)
    {
        $liquidation = ImprestLiquidation::with([
            'imprestRequest.employee', 'imprestRequest.department',
            'liquidationItems.chartAccount',
            'submitter', 'verifier', 'approver',
            'documents.uploader'
        ])->findOrFail($id);

        return view('imprest.liquidation.show', compact('liquidation'));
    }

    /**
     * Verify liquidation (Manager action)
     */
    public function verify(Request $request, $id)
    {
        $liquidation = ImprestLiquidation::findOrFail($id);
        
        if (!$liquidation->canBeVerified()) {
            return response()->json(['error' => 'This liquidation cannot be verified at this time.'], 400);
        }

        $request->validate([
            'action' => 'required|in:verify,reject',
            'verification_notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            if ($request->action === 'verify') {
                $liquidation->update([
                    'status' => 'verified',
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                    'verification_notes' => $request->verification_notes,
                ]);
                $message = 'Liquidation verified successfully.';
            } else {
                $liquidation->update([
                    'status' => 'rejected',
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                    'verification_notes' => $request->verification_notes,
                ]);
                
                // Revert imprest status back to disbursed
                $liquidation->imprestRequest->update(['status' => 'disbursed']);
                
                $message = 'Liquidation rejected.';
            }

            DB::commit();

            return response()->json(['success' => $message]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to process verification.'], 500);
        }
    }

    /**
     * Approve liquidation (Finance action)
     */
    public function approveLiquidation(Request $request, $id)
    {
        $liquidation = ImprestLiquidation::with('imprestRequest', 'liquidationItems.chartAccount')
            ->findOrFail($id);
        
        if (!$liquidation->canBeApproved()) {
            return response()->json(['error' => 'This liquidation cannot be approved at this time.'], 400);
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            if ($request->action === 'approve') {
                $liquidation->update([
                    'status' => 'approved',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'approval_notes' => $request->approval_notes,
                ]);

                // Create journal entries for liquidation
                $this->createLiquidationJournalEntries($liquidation);
                
                $message = 'Liquidation approved successfully.';
            } else {
                $liquidation->update([
                    'status' => 'rejected',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'approval_notes' => $request->approval_notes,
                ]);
                
                // Revert imprest status back to disbursed
                $liquidation->imprestRequest->update(['status' => 'disbursed']);
                
                $message = 'Liquidation rejected.';
            }

            DB::commit();

            return response()->json(['success' => $message]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to process approval.'], 500);
        }
    }

    /**
     * Create journal entries for approved liquidation
     */
    private function createLiquidationJournalEntries(ImprestLiquidation $liquidation)
    {
        $imprestRequest = $liquidation->imprestRequest;
        $companyId = $imprestRequest->company_id;

        // Get Staff Imprest account (credit)
        $staffImprestAccount = ChartAccount::where('company_id', $companyId)
            ->where('name', 'LIKE', '%Staff Imprest%')
            ->first();

        if (!$staffImprestAccount) {
            throw new \Exception('Staff Imprest account not found');
        }

        // Create journal entries for each expense category
        foreach ($liquidation->liquidationItems as $item) {
            ImprestJournalEntry::create([
                'imprest_request_id' => $imprestRequest->id,
                'journal_number' => ImprestJournalEntry::generateJournalNumber(),
                'entry_type' => 'liquidation',
                'debit_account_id' => $item->chart_account_id, // Expense account
                'credit_account_id' => $staffImprestAccount->id, // Staff Imprest
                'amount' => $item->amount,
                'description' => 'Liquidation: ' . $item->description,
                'transaction_date' => $liquidation->liquidation_date,
                'reference_number' => $liquidation->liquidation_number,
                'created_by' => Auth::id(),
            ]);
        }

        // If there's a balance return, create entry for cash return
        if ($liquidation->balance_returned > 0) {
            $cashAccount = ChartAccount::where('company_id', $companyId)
                ->where('name', 'LIKE', '%Cash%')
                ->where('account_type', 'asset')
                ->first();

            if ($cashAccount) {
                ImprestJournalEntry::create([
                    'imprest_request_id' => $imprestRequest->id,
                    'journal_number' => ImprestJournalEntry::generateJournalNumber(),
                    'entry_type' => 'balance_return',
                    'debit_account_id' => $cashAccount->id, // Cash
                    'credit_account_id' => $staffImprestAccount->id, // Staff Imprest
                    'amount' => $liquidation->balance_returned,
                    'description' => 'Balance return from imprest liquidation',
                    'transaction_date' => $liquidation->liquidation_date,
                    'reference_number' => $liquidation->liquidation_number,
                    'created_by' => Auth::id(),
                ]);
            }
        }
    }
}