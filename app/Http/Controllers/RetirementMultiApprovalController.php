<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Retirement;
use App\Models\RetirementApproval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RetirementMultiApprovalController extends Controller
{
    public function pending()
    {
        $user = Auth::user();
        
        $pendingApprovals = RetirementApproval::with([
            'retirement.employee',
            'retirement.imprestRequest.department'
        ])
        ->where('approver_id', $user->id)
        ->where('status', 'pending')
        ->latest()
        ->paginate(15);

        return view('retirement.multi-approvals.pending', compact('pendingApprovals'));
    }

    public function approve(Request $request, $approvalId)
    {
        $request->validate([
            'comments' => 'nullable|string|max:500'
        ]);

        $user = Auth::user();
        $approval = RetirementApproval::with(['retirement.approvals', 'retirement.employee'])
            ->findOrFail($approvalId);

        // Check if user is authorized to approve
        if ($approval->approver_id !== $user->id) {
            return back()->withErrors(['error' => 'You are not authorized to approve this request.']);
        }

        // Check if already processed
        if (!$approval->isPending()) {
            return back()->withErrors(['error' => 'This approval has already been processed.']);
        }

        DB::beginTransaction();
        try {
            // Approve this level
            $approval->approve($request->comments);

            // Check if all required approvals are completed
            if ($approval->retirement->isFullyApproved()) {
                // Update retirement status to approved
                $approval->retirement->update([
                    'status' => 'approved',
                    'approved_by' => $user->id,
                    'approved_at' => now()
                ]);
            }

            DB::commit();
            
            return redirect()->route('imprest.retirement-multi-approvals.pending')
                ->with('success', 'Request approved successfully.');

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
        $approval = RetirementApproval::with('retirement.employee')
            ->findOrFail($approvalId);

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

            // Update retirement status to rejected
            $approval->retirement->update([
                'status' => 'rejected',
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => 'Rejected at Level ' . $approval->approval_level . ': ' . $request->comments
            ]);

            DB::commit();
            
            return redirect()->route('imprest.retirement-multi-approvals.pending')
                ->with('success', 'Request rejected successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to reject request: ' . $e->getMessage()]);
        }
    }

    public function approvalHistory($requestId)
    {
        $retirement = Retirement::with([
            'approvals.approver',
            'employee',
            'imprestRequest.department'
        ])->findOrFail($requestId);

        return view('retirement.multi-approvals.history', compact('retirement'));
    }
}
