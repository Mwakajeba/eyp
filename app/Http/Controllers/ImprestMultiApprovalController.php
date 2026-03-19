<?php

namespace App\Http\Controllers;

use App\Helpers\SmsHelper;
use App\Models\ImprestRequest;
use App\Models\ImprestApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ImprestMultiApprovalController extends Controller
{
    public function pendingApprovals()
    {
        $user = Auth::user();

        // Get all pending approvals where current user is an approver
        // Also filter by imprest request status to exclude rejected requests
        $allPendingApprovals = ImprestApproval::with([
            'imprestRequest.employee',
            'imprestRequest.department',
            'imprestRequest.approvals' // Eager load approvals for getCurrentApprovalLevel()
        ])
            ->where('approver_id', $user->id)
            ->where('status', ImprestApproval::STATUS_PENDING)
            ->whereHas('imprestRequest', function ($query) {
                $query->where('status', '!=', 'rejected');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Filter to only show approvals at the current level that needs approval
        $pendingApprovals = $allPendingApprovals->filter(function ($approval) {
            $imprestRequest = $approval->imprestRequest;

            // Skip if request is rejected
            if ($imprestRequest->status === 'rejected') {
                return false;
            }

            // Skip if request has rejected approvals
            if ($imprestRequest->hasRejectedApprovals()) {
                return false;
            }

            $currentLevel = $imprestRequest->getCurrentApprovalLevel();

            // Only show if this approval is at the current level that needs approval
            return $currentLevel !== null && $approval->approval_level === $currentLevel;
        });

        return view('imprest.multi-approvals.pending', compact('pendingApprovals'));
    }

    public function approve(Request $request, $approvalId)
    {
        $request->validate([
            'comments' => 'nullable|string|max:500'
        ]);

        $user = Auth::user();
        $approval = ImprestApproval::with('imprestRequest.employee')->findOrFail($approvalId);

        // Check if user is authorized to approve
        if ($approval->approver_id !== $user->id) {
            return back()->withErrors(['error' => 'You are not authorized to approve this request.']);
        }

        // Check if already processed
        if (!$approval->isPending()) {
            return back()->withErrors(['error' => 'This approval has already been processed.']);
        }

        // Check if this is the current level that needs approval
        $imprestRequest = $approval->imprestRequest;
        $currentLevel = $imprestRequest->getCurrentApprovalLevel();

        if ($currentLevel && $approval->approval_level > $currentLevel) {
            return back()->withErrors(['error' => 'Previous approval levels must be completed first.']);
        }

        DB::beginTransaction();
        try {
            // Approve this request
            $approval->approve($request->comments);

            // Check if all required approvals are complete
            if ($imprestRequest->isFullyApproved()) {
                // Update imprest request status if all levels are approved
                $imprestRequest->update([
                    'status' => 'approved',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'approval_comments' => 'Multi-level approval completed'
                ]);

                // Log activity
                $imprestRequest->logActivity('approve', "Approved Imprest Request - {$imprestRequest->request_number}", [
                    'approval_level' => $approval->approval_level,
                    'comments' => $request->comments
                ]);

                $message = 'Request approved successfully. All approval levels completed - ready for disbursement.';
            } else {
                // Log partial approval
                $imprestRequest->logActivity('approve', "Partially Approved Imprest Request - {$imprestRequest->request_number}", [
                    'approval_level' => $approval->approval_level,
                    'comments' => $request->comments
                ]);

                $message = 'Request approved successfully for Level ' . $approval->approval_level . '. Waiting for remaining approvals.';
            }

            DB::commit();

            // Notify the request owner only after a successful commit.
            $requestOwner = $imprestRequest->employee;
            if ($requestOwner && !empty($requestOwner->phone)) {
                $ownerName = $requestOwner->name ?? 'Mfanyakazi';
                $smsMessage = "Ndugu {$ownerName}, ombi lako limeidhinishwa katika ngazi hii. Tafadhali subiri hatua inayofuata ya uthibitisho.";

                try {
                    SmsHelper::send($requestOwner->phone, $smsMessage);
                } catch (\Exception $smsException) {
                    // Do not fail approval when SMS delivery fails.
                    \Log::warning('Failed to send imprest approval SMS', [
                        'approval_id' => $approval->id,
                        'request_id' => $imprestRequest->id,
                        'employee_id' => $requestOwner->id,
                        'error' => $smsException->getMessage(),
                    ]);
                }
            } else {
                \Log::info('Skipped imprest approval SMS: missing recipient or phone', [
                    'approval_id' => $approval->id,
                    'request_id' => $imprestRequest->id,
                    'employee_id' => $requestOwner?->id,
                    'has_request_owner' => (bool) $requestOwner,
                    'has_phone' => (bool) ($requestOwner?->phone),
                ]);
            }

            return redirect()->route('imprest.multi-approvals.pending')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to approve request: ' . $e->getMessage()]);
        }
    }

    public function reject(Request $request, $approvalId)
    {
        $request->validate([
            'comments' => 'required|string|max:500'
        ]);

        $user = Auth::user();
        $approval = ImprestApproval::with('imprestRequest')->findOrFail($approvalId);

        // Check if user is authorized to reject
        if ($approval->approver_id !== $user->id) {
            return back()->withErrors(['error' => 'You are not authorized to reject this request.']);
        }

        // Check if already processed
        if (!$approval->isPending()) {
            return back()->withErrors(['error' => 'This approval has already been processed.']);
        }

        DB::beginTransaction();
        try {
            // Reject this approval
            $approval->reject($request->comments);

            // Log activity
            $approval->imprestRequest->logActivity('reject', "Rejected Imprest Request - {$approval->imprestRequest->request_number}", [
                'approval_level' => $approval->approval_level,
                'reason' => $request->comments
            ]);

            // Update imprest request status to rejected
            $approval->imprestRequest->update([
                'status' => 'rejected',
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => 'Rejected at Level ' . $approval->approval_level . ': ' . $request->comments
            ]);

            DB::commit();

            return redirect()->route('imprest.multi-approvals.pending')
                ->with('success', 'Request rejected successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to reject request: ' . $e->getMessage()]);
        }
    }

    public function approvalHistory($requestId)
    {
        $imprestRequest = ImprestRequest::with([
            'approvals.approver',
            'employee',
            'department'
        ])->findOrFail($requestId);

        return view('imprest.multi-approvals.history', compact('imprestRequest'));
    }
}
