<?php

namespace App\Http\Controllers;

use App\Models\Retirement;
use App\Models\ImprestApprovalSetting;
use App\Models\ImprestSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RetirementActionController extends Controller
{
    /**
     * Check a retirement request
     */
    public function check(Request $request, $id)
    {
        $retirement = Retirement::findOrFail($id);
        
        // Validate user can check this retirement
        if (!$retirement->canUserCheck(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to check this retirement.'
            ], 403);
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'comments' => 'nullable|string|max:1000'
        ]);

        // Additional validation for rejection
        if ($request->action === 'reject') {
            $request->validate([
                'comments' => 'required|string|min:5|max:1000'
            ], [
                'comments.required' => 'Comments are required when rejecting a retirement.',
                'comments.min' => 'Rejection reason must be at least 5 characters.'
            ]);
        }

        try {
            DB::beginTransaction();

            if ($request->action === 'approve') {
                $retirement->update([
                    'status' => 'checked',
                    'checked_by' => Auth::id(),
                    'checked_at' => now(),
                    'check_comments' => $request->comments
                ]);

                // Log activity
                $retirement->logActivity('check', "Checked Retirement - {$retirement->retirement_number}", [
                    'comments' => $request->comments
                ]);

                $message = 'Retirement forwarded for approval successfully.';
                Log::info("Retirement {$retirement->retirement_number} checked and forwarded for approval by user " . Auth::id());

            } else { // reject
                $retirement->update([
                    'status' => 'rejected',
                    'rejected_by' => Auth::id(),
                    'rejected_at' => now(),
                    'rejection_reason' => $request->comments
                ]);

                // Log activity
                $retirement->logActivity('reject', "Rejected Retirement - {$retirement->retirement_number}", [
                    'reason' => $request->comments
                ]);

                $message = 'Retirement rejected successfully.';
                Log::info("Retirement {$retirement->retirement_number} rejected by user " . Auth::id());
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $retirement->status
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error checking retirement {$retirement->retirement_number}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the retirement. Please try again.'
            ], 500);
        }
    }

    /**
     * Approve a retirement request
     */
    public function approve(Request $request, $id)
    {
        $retirement = Retirement::findOrFail($id);
        
        // Validate user can approve this retirement
        if (!$retirement->canUserApprove(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to approve this retirement.'
            ], 403);
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'comments' => 'nullable|string|max:1000'
        ]);

        // Additional validation for rejection
        if ($request->action === 'reject') {
            $request->validate([
                'comments' => 'required|string|min:5|max:1000'
            ], [
                'comments.required' => 'Comments are required when rejecting a retirement.',
                'comments.min' => 'Rejection reason must be at least 5 characters.'
            ]);
        }

        try {
            DB::beginTransaction();

            if ($request->action === 'approve') {
                $retirement->update([
                    'status' => 'approved',
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                    'approval_comments' => $request->comments
                ]);

                // Update the imprest request status to 'liquidated' (retirement replaces liquidation)
                $retirement->imprestRequest->update([
                    'status' => 'liquidated'
                ]);

                // Log activity
                $retirement->logActivity('approve', "Approved Retirement - {$retirement->retirement_number}", [
                    'comments' => $request->comments
                ]);

                $message = 'Retirement approved successfully.';
                Log::info("Retirement {$retirement->retirement_number} approved by user " . Auth::id());

                // Create GL transactions for the retirement
                $this->createRetirementGLTransactions($retirement);

            } else { // reject
                $retirement->update([
                    'status' => 'rejected',
                    'rejected_by' => Auth::id(),
                    'rejected_at' => now(),
                    'rejection_reason' => $request->comments
                ]);

                // Log activity
                $retirement->logActivity('reject', "Rejected Retirement - {$retirement->retirement_number}", [
                    'reason' => $request->comments
                ]);

                $message = 'Retirement rejected successfully.';
                Log::info("Retirement {$retirement->retirement_number} rejected by user " . Auth::id());
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $retirement->status
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error approving retirement {$retirement->retirement_number}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the retirement. Please try again.'
            ], 500);
        }
    }

    /**
     * Create GL transactions for approved retirement
     */
    private function createRetirementGLTransactions(Retirement $retirement)
    {
        try {
            $glTransactionService = new \App\Services\GLTransactionService();
            
            // Prepare retirement items data
            $retirementItems = [];
            foreach ($retirement->retirementItems as $item) {
                $retirementItems[] = [
                    'chart_account_id' => $item->chart_account_id,
                    'actual_amount' => $item->actual_amount,
                    'description' => $item->description
                ];
            }

            // Create GL transactions using the service
            $result = $glTransactionService->createRetirementGLTransactions([
                'company_id' => $retirement->company_id,
                'branch_id' => $retirement->branch_id,
                'transaction_date' => now()->toDateString(),
                'reference_number' => $retirement->retirement_number,
                'description' => "GL Transaction for retirement {$retirement->retirement_number}",
                'total_amount' => $retirement->total_amount_used,
                'retirement_items' => $retirementItems,
                'credit_account_id' => null // Will be set when journal is created
            ]);

            if ($result['success']) {
                Log::info("GL transactions created for retirement {$retirement->retirement_number}");
            } else {
                Log::error("Failed to create GL transactions for retirement {$retirement->retirement_number}: " . $result['message']);
            }

        } catch (\Exception $e) {
            Log::error("Error creating GL transactions for retirement {$retirement->retirement_number}: " . $e->getMessage());
            // Don't throw the error since the main retirement approval should succeed
        }
    }

    /**
     * Create journal entry for approved retirement
     */
    public function createJournal(Request $request, $id)
    {
        $retirement = Retirement::with(['retirementItems.chartAccount'])->findOrFail($id);
        
        // Validate retirement can have journal created
        if ($retirement->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Journal can only be created for approved retirements.'
            ], 400);
        }

        // Check if journal already exists
        $existingJournal = \App\Models\Journal::where('reference', $retirement->retirement_number)
            ->where('reference_type', 'retirement')
            ->first();

        if ($existingJournal) {
            return response()->json([
                'success' => false,
                'message' => 'Journal entry already exists for this retirement.'
            ], 400);
        }

        // Get imprest settings to determine the credit account
        $imprestSettings = ImprestSettings::getSettings($retirement->company_id, $retirement->branch_id);
        
        if (!$imprestSettings || !$imprestSettings->imprest_receivables_account) {
            return response()->json([
                'success' => false,
                'message' => 'Imprest receivables account is not configured in settings. Please configure it first.'
            ], 400);
        }

        $request->validate([
            'description' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();

            // Create journal header
            $journal = \App\Models\Journal::create([
                'date' => now(),
                'reference' => $retirement->retirement_number,
                'reference_type' => 'retirement',
                'description' => $request->description ?: "Journal entry for retirement {$retirement->retirement_number}",
                'branch_id' => $retirement->branch_id,
                'user_id' => $user->id,
            ]);

            $totalAmount = $retirement->total_amount_used;

            // Create credit entry using imprest receivables account from settings
            \App\Models\JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $imprestSettings->imprest_receivables_account,
                'amount' => $totalAmount,
                'description' => "Credit to imprest receivables for retirement {$retirement->retirement_number}",
                'nature' => 'credit'
            ]);

            // Create debit entries for each expenditure account
            foreach ($retirement->retirementItems as $item) {
                \App\Models\JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $item->chart_account_id,
                    'amount' => $item->actual_amount,
                    'description' => $item->description ?: "Expenditure - {$item->chartAccount->account_name}",
                    'nature' => 'debit'
                ]);
            }

            // Create GL transactions
            $this->createJournalGLTransactions($journal);

            // Update retirement status to closed
            $retirement->update([
                'status' => 'closed',
                'journal_id' => $journal->id,
                'closed_at' => now(),
                'closed_by' => $user->id
            ]);

            DB::commit();

            Log::info("Journal entry created for retirement {$retirement->retirement_number} by user " . Auth::id());

            // Handle AJAX vs regular requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Journal entry created successfully. Retirement is now closed.',
                    'journal_id' => $journal->id,
                    'redirect' => route('imprest.retirement.index')
                ]);
            }

            return redirect()->route('imprest.retirement.index')
                ->with('success', 'Journal entry created successfully. Retirement is now closed.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error creating journal for retirement {$retirement->retirement_number}: " . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while creating the journal entry. Please try again.'
                ], 500);
            }

            return back()->withErrors(['error' => 'An error occurred while creating the journal entry. Please try again.']);
        }
    }

    /**
     * Create GL transactions for the journal entry
     */
    private function createJournalGLTransactions(\App\Models\Journal $journal)
    {
        try {
            $glTransactionService = new \App\Services\GLTransactionService();
            
            $entries = [];
            
            // Add entries for each journal item
            foreach ($journal->journalItems as $item) {
                $entries[] = [
                    'chart_account_id' => $item->chart_account_id,
                    'description' => $item->description,
                    'debit_amount' => $item->nature === 'debit' ? $item->amount : 0,
                    'credit_amount' => $item->nature === 'credit' ? $item->amount : 0,
                ];
            }

            // Calculate total amount
            $totalAmount = $journal->journalItems->sum('amount') / 2; // Divide by 2 since we have both debit and credit

            $result = $glTransactionService->createTransaction([
                'company_id' => Auth::user()->company_id ?? 1,
                'branch_id' => $journal->branch_id,
                'transaction_date' => $journal->date->toDateString(),
                'reference_number' => $journal->reference,
                'description' => $journal->description,
                'total_amount' => $totalAmount,
                'entries' => $entries
            ]);

            if ($result['success']) {
                Log::info("GL transactions created for journal {$journal->reference}");
            } else {
                Log::error("Failed to create GL transactions for journal {$journal->reference}: " . $result['message']);
            }

        } catch (\Exception $e) {
            Log::error("Error creating GL transactions for journal {$journal->reference}: " . $e->getMessage());
            // Don't throw the error since the main journal creation should succeed
        }
    }
}