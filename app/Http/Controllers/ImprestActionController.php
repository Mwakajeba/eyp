<?php

namespace App\Http\Controllers;

use App\Models\ImprestRequest;
use App\Models\BankAccount;
use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ImprestActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Check imprest request (Manager action)
     */
    public function check(Request $request, $id)
    {
        $imprestRequest = ImprestRequest::findOrFail($id);
        
        if (!$imprestRequest->canBeChecked()) {
            return response()->json(['error' => 'This request cannot be checked at this time.'], 400);
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'comments' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            if ($request->action === 'approve') {
                $imprestRequest->update([
                    'status' => 'checked',
                    'checked_by' => $user->id,
                    'checked_at' => now(),
                    'check_comments' => $request->comments,
                ]);
                $message = 'Imprest request checked and forwarded for approval.';
            } else {
                $imprestRequest->update([
                    'status' => 'rejected',
                    'rejected_by' => $user->id,
                    'rejected_at' => now(),
                    'rejection_reason' => $request->comments,
                ]);
                $message = 'Imprest request rejected.';
            }

            DB::commit();

            return response()->json(['success' => $message]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to process request.'], 500);
        }
    }

    /**
     * Approve imprest request (Finance Controller action)
     */
    public function approve(Request $request, $id)
    {
        $imprestRequest = ImprestRequest::findOrFail($id);
        
        if (!$imprestRequest->canBeApproved()) {
            return response()->json(['error' => 'This request cannot be approved at this time.'], 400);
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'comments' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            if ($request->action === 'approve') {
                $imprestRequest->update([
                    'status' => 'approved',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'approval_comments' => $request->comments,
                ]);
                $message = 'Imprest request approved and ready for disbursement.';
                
                // Log approval
                if (method_exists($imprestRequest, 'logActivity')) {
                    $employeeName = $imprestRequest->employee ? $imprestRequest->employee->full_name : 'N/A';
                    $imprestRequest->logActivity('approve', "Approved Imprest Request {$imprestRequest->request_number} for Employee: {$employeeName}", [
                        'Request Number' => $imprestRequest->request_number,
                        'Employee' => $employeeName,
                        'Amount' => number_format($imprestRequest->amount ?? 0, 2),
                        'Request Date' => $imprestRequest->request_date ? $imprestRequest->request_date->format('Y-m-d') : 'N/A',
                        'Approved By' => $user->name,
                        'Approved At' => now()->format('Y-m-d H:i:s')
                    ]);
                }
            } else {
                $imprestRequest->update([
                    'status' => 'rejected',
                    'rejected_by' => $user->id,
                    'rejected_at' => now(),
                    'rejection_reason' => $request->comments,
                ]);
                $message = 'Imprest request rejected.';
                
                // Log rejection
                if (method_exists($imprestRequest, 'logActivity')) {
                    $employeeName = $imprestRequest->employee ? $imprestRequest->employee->full_name : 'N/A';
                    $imprestRequest->logActivity('reject', "Rejected Imprest Request {$imprestRequest->request_number} for Employee: {$employeeName}", [
                        'Request Number' => $imprestRequest->request_number,
                        'Employee' => $employeeName,
                        'Amount' => number_format($imprestRequest->amount ?? 0, 2),
                        'Request Date' => $imprestRequest->request_date ? $imprestRequest->request_date->format('Y-m-d') : 'N/A',
                        'Rejected By' => $user->name,
                        'Rejection Reason' => $request->comments ?? 'No reason provided',
                        'Rejected At' => now()->format('Y-m-d H:i:s')
                    ]);
                }
            }

            DB::commit();

            return response()->json(['success' => $message]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to process approval.'], 500);
        }
    }

    /**
     * Show the disbursement form (similar to payment voucher)
     */
    public function showDisburseForm($id)
    {
        $imprestRequest = ImprestRequest::with(['employee', 'department', 'imprestItems.chartAccount'])->findOrFail($id);
        
        if (!$imprestRequest->canBeDisbursed()) {
            return redirect()->route('imprest.requests.show', $id)
                ->withErrors(['error' => 'This request cannot be disbursed at this time.']);
        }

        $user = Auth::user();
        
        // Get imprest settings to determine disbursement approach
        $imprestSettings = \App\Models\ImprestSettings::where('company_id', $imprestRequest->company_id)
            ->where('branch_id', $imprestRequest->branch_id)
            ->with('receivablesAccount') // Load the receivables account relationship
            ->first();
        
        // Get bank accounts for the company
        $bankAccounts = BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($user) {
            $q->where('company_id', $user->company_id);
        })->with('chartAccount')->get();

        // Get all chart accounts for the company
        $imprestAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($user) {
            $q->where('company_id', $user->company_id);
        })
        ->orderBy('account_name')
        ->get();

        return view('imprest.disburse.form', compact('imprestRequest', 'bankAccounts', 'imprestAccounts', 'imprestSettings'));
    }

    /**
     * Disburse imprest funds using payments system
     */
    public function disburse(Request $request, $id)
    {
        $imprestRequest = ImprestRequest::with(['imprestItems.chartAccount'])->findOrFail($id);
        
        if (!$imprestRequest->canBeDisbursed()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'This request cannot be disbursed at this time.'], 400);
            }
            return redirect()->route('imprest.requests.show', $id)
                ->withErrors(['error' => 'This request cannot be disbursed at this time.']);
        }

        // Get imprest settings to determine disbursement approach
        $imprestSettings = \App\Models\ImprestSettings::where('company_id', $imprestRequest->company_id)
            ->where('branch_id', $imprestRequest->branch_id)
            ->with('receivablesAccount') // Load the receivables account relationship
            ->first();

        $hasRetirement = $imprestSettings && $imprestSettings->retirement_enabled;

        if ($hasRetirement) {
            // Retirement enabled - use existing approach (imprest receivables account)
            return $this->disburseWithRetirement($request, $imprestRequest);
        } else {
            // No retirement - debit expense accounts directly
            return $this->disburseWithoutRetirement($request, $imprestRequest);
        }
    }

    /**
     * Disburse with retirement enabled (existing approach - imprest receivables)
     */
    private function disburseWithRetirement(Request $request, $imprestRequest)
    {
        // Get imprest settings to get the receivables account
        $imprestSettings = \App\Models\ImprestSettings::where('company_id', $imprestRequest->company_id)
            ->where('branch_id', $imprestRequest->branch_id)
            ->with('receivablesAccount')
            ->first();
            
        // Validate that imprest receivables account is configured
        if (!$imprestSettings || !$imprestSettings->imprest_receivables_account) {
            return redirect()->back()
                ->withErrors(['error' => 'Imprest receivables account not configured in settings. Please configure before disbursing.'])
                ->withInput();
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $imprestRequest->amount_requested,
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'retirement_days' => 'required|integer|min:1|max:3650',
            'description' => 'nullable|string|max:1000',
            'reference' => 'nullable|string|max:255',
        ]);

        $disbursementDate = now();
        $retirementEndDate = $disbursementDate->copy()->addDays((int) $request->retirement_days)->toDateString();

        // Use the imprest receivables account from settings
        $imprestAccountId = $imprestSettings->imprest_receivables_account;

        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            // Create payment record using the existing payments system
            // Note: Imprest disbursements are auto-approved since the imprest request 
            // has already gone through its own approval workflow
            $payment = \App\Models\Payment::create([
                'reference' => $request->reference ?: 'IMP-DISB-' . $imprestRequest->request_number,
                'reference_type' => 'imprest_request',
                'reference_number' => $imprestRequest->request_number,
                'amount' => $request->amount,
                'date' => $disbursementDate,
                'description' => $request->description ?: "Imprest disbursement for: {$imprestRequest->purpose}",
                'bank_account_id' => $request->bank_account_id,
                'payee_type' => 'other',
                'payee_id' => $imprestRequest->employee_id,
                'payee_name' => $imprestRequest->employee->name ?? 'Employee',
                'branch_id' => $imprestRequest->branch_id,
                'project_id' => $imprestRequest->project_id,
                'user_id' => $user->id,
                'approved' => true, // Always auto-approve - imprest already approved
                'approved_by' => $user->id,
                'approved_at' => $disbursementDate,
            ]);

            // Create payment item for the imprest receivable account (auto-selected from settings)
            \App\Models\PaymentItem::create([
                'payment_id' => $payment->id,
                'chart_account_id' => $imprestAccountId,
                'amount' => $request->amount,
                'description' => "Imprest advance to {$imprestRequest->employee->name}",
            ]);

            // Create GL transactions using the existing payment system approach
            $bankAccount = \App\Models\BankAccount::find($request->bank_account_id);
            
            // Credit bank account (money going out)
            \App\Models\GlTransaction::create([
                'chart_account_id' => $bankAccount->chart_account_id,
                'amount' => $request->amount,
                'nature' => 'credit',
                'transaction_id' => $payment->id,
                'transaction_type' => 'payment',
                'date' => $disbursementDate->toDateString(),
                'description' => "Imprest disbursement: {$imprestRequest->request_number}",
                'branch_id' => $imprestRequest->branch_id,
                'project_id' => $imprestRequest->project_id,
                'user_id' => $user->id,
            ]);

            // Debit imprest receivable account (asset increase)
            \App\Models\GlTransaction::create([
                'chart_account_id' => $imprestAccountId,
                'amount' => $request->amount,
                'nature' => 'debit',
                'transaction_id' => $payment->id,
                'transaction_type' => 'payment',
                'date' => $disbursementDate->toDateString(),
                'description' => "Imprest advance to {$imprestRequest->employee->name}",
                'branch_id' => $imprestRequest->branch_id,
                'project_id' => $imprestRequest->project_id,
                'user_id' => $user->id,
            ]);

            // Update imprest request status
            $imprestRequest->update([
                'status' => 'disbursed',
                'disbursed_at' => $disbursementDate,
                'retirement_end_date' => $retirementEndDate,
                'disbursed_by' => $user->id,
                'disbursed_amount' => $request->amount,
                'payment_id' => $payment->id, // Store reference to payment
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['success' => 'Funds disbursed successfully using imprest receivables account.']);
            }

            return redirect()->route('imprest.requests.show', $imprestRequest->id)
                ->with('success', 'Imprest funds disbursed successfully using imprest receivables account.');
            
        } catch (\Exception $e) {
            DB::rollback();
            
            \Log::error('Imprest disbursement error', [
                'imprest_request_id' => $imprestRequest->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to disburse funds.'], 500);
            }
            
            return redirect()->back()->withInput()
                ->withErrors(['error' => 'Failed to disburse funds: ' . $e->getMessage()]);
        }
    }

    /**
     * Disburse without retirement (new approach - direct expense posting)
     */
    private function disburseWithoutRetirement(Request $request, $imprestRequest)
    {
        $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'retirement_days' => 'nullable|integer|min:1|max:3650',
            'description' => 'nullable|string|max:1000',
            'reference' => 'nullable|string|max:255',
        ]);

        $disbursementDate = now();

        $retirementEndDate = $request->filled('retirement_days')
            ? $disbursementDate->copy()->addDays((int) $request->retirement_days)->toDateString()
            : null;

        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            $branchId = session('branch_id') ?? ($user->branch_id ?? null);
            
            $totalAmount = $imprestRequest->amount_requested;

            // Create payment record
            $payment = \App\Models\Payment::create([
                'reference' => $request->reference ?: 'IMP-EXP-' . $imprestRequest->request_number,
                'reference_type' => 'imprest_request',
                'reference_number' => $imprestRequest->request_number,
                'amount' => $totalAmount,
                'date' => $disbursementDate,
                'description' => $request->description ?: "Imprest expense payment for: {$imprestRequest->purpose}",
                'bank_account_id' => $request->bank_account_id,
                'payee_type' => 'other',
                'payee_id' => $imprestRequest->employee_id,
                'payee_name' => $imprestRequest->employee->name ?? 'Employee',
                'branch_id' => $branchId,
                'project_id' => $imprestRequest->project_id,
                'user_id' => $user->id,
                'approved' => true,
                'approved_by' => $user->id,
                'approved_at' => $disbursementDate,
            ]);

            // Create payment items for each expense account
            foreach ($imprestRequest->imprestItems as $item) {
                \App\Models\PaymentItem::create([
                    'payment_id' => $payment->id,
                    'chart_account_id' => $item->chart_account_id,
                    'amount' => $item->amount,
                    'description' => $item->notes ?: "Imprest expense: {$item->chartAccount->account_name}",
                ]);
            }

            // Create GL transactions
            $bankAccount = \App\Models\BankAccount::find($request->bank_account_id);
            
            // Credit bank account (money going out)
            \App\Models\GlTransaction::create([
                'chart_account_id' => $bankAccount->chart_account_id,
                'amount' => $totalAmount,
                'nature' => 'credit',
                'transaction_id' => $payment->id,
                'transaction_type' => 'payment',
                'date' => $disbursementDate->toDateString(),
                'description' => "Imprest expense payment: {$imprestRequest->request_number}",
                'branch_id' => $branchId,
                'project_id' => $imprestRequest->project_id,
                'user_id' => $user->id,
            ]);

            // Debit each expense account
            foreach ($imprestRequest->imprestItems as $item) {
                \App\Models\GlTransaction::create([
                    'chart_account_id' => $item->chart_account_id,
                    'amount' => $item->amount,
                    'nature' => 'debit',
                    'transaction_id' => $payment->id,
                    'transaction_type' => 'payment',
                    'date' => $disbursementDate->toDateString(),
                    'description' => "Imprest expense: {$item->chartAccount->account_name}",
                    'branch_id' => $branchId,
                    'project_id' => $imprestRequest->project_id,
                    'user_id' => $user->id,
                ]);
            }

            // Update imprest request status
            $imprestRequest->update([
                'status' => 'disbursed',
                'disbursed_at' => $disbursementDate,
                'retirement_end_date' => $retirementEndDate,
                'disbursed_by' => $user->id,
                'disbursed_amount' => $totalAmount,
                'payment_id' => $payment->id,
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['success' => 'Funds disbursed successfully with direct expense posting.']);
            }

            return redirect()->route('imprest.requests.show', $imprestRequest->id)
                ->with('success', 'Imprest funds disbursed successfully with direct expense posting.');
            
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to disburse funds.'], 500);
            }
            
            return redirect()->back()->withInput()
                ->withErrors(['error' => 'Failed to disburse funds: ' . $e->getMessage()]);
        }
    }



    /**
     * Create default mobile money account
     */
    private function createDefaultMobileMoneyAccount($companyId)
    {
        return ChartAccount::create([
            'company_id' => $companyId,
            'name' => 'Mobile Money Account',
            'code' => 'MMONEY001',
            'account_type' => 'asset',
            'parent_id' => null,
        ]);
    }

    /**
     * Close imprest request after successful liquidation
     */
    public function close(Request $request, $id)
    {
        $imprestRequest = ImprestRequest::findOrFail($id);
        
        if (!$imprestRequest->canBeClosed()) {
            return response()->json(['error' => 'This request cannot be closed at this time.'], 400);
        }

        DB::beginTransaction();
        
        try {
            // Update status to closed
            $imprestRequest->update([
                'status' => 'closed'
            ]);

            // Handle balance return if any
            $remainingBalance = $imprestRequest->getRemainingBalance();
            if ($remainingBalance > 0) {
                $this->createBalanceReturnJournalEntry($imprestRequest, $remainingBalance);
            }

            DB::commit();

            return response()->json(['success' => 'Imprest request closed successfully.']);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to close imprest request.'], 500);
        }
    }

    /**
     * Create journal entry for balance return
     */
    private function createBalanceReturnJournalEntry(ImprestRequest $imprestRequest, $amount)
    {
        // Get accounts
        $staffImprestAccount = ChartAccount::where('company_id', $imprestRequest->company_id)
            ->where('name', 'LIKE', '%Staff Imprest%')
            ->first();
            
        $cashAccount = ChartAccount::where('company_id', $imprestRequest->company_id)
            ->where('name', 'LIKE', '%Cash%')
            ->where('account_type', 'asset')
            ->first();

        if ($staffImprestAccount && $cashAccount) {
            ImprestJournalEntry::create([
                'imprest_request_id' => $imprestRequest->id,
                'journal_number' => ImprestJournalEntry::generateJournalNumber(),
                'entry_type' => 'balance_return',
                'debit_account_id' => $cashAccount->id,
                'credit_account_id' => $staffImprestAccount->id,
                'amount' => $amount,
                'description' => 'Balance return for imprest: ' . $imprestRequest->request_number,
                'transaction_date' => now()->toDateString(),
                'reference_number' => $imprestRequest->request_number,
                'created_by' => Auth::id(),
            ]);
        }
    }
}