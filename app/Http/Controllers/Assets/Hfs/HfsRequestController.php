<?php

namespace App\Http\Controllers\Assets\Hfs;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Assets\HfsRequest;
use App\Models\Customer;
use App\Services\Assets\Hfs\HfsService;
use App\Services\Assets\Hfs\HfsApprovalService;
use App\Services\Assets\Hfs\HfsValidationService;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class HfsRequestController extends Controller
{
    protected $hfsService;
    protected $approvalService;
    protected $validationService;
    protected $systemApprovalService;

    public function __construct(
        HfsService $hfsService,
        HfsApprovalService $approvalService,
        HfsValidationService $validationService,
        ApprovalService $systemApprovalService
    ) {
        $this->hfsService = $hfsService;
        $this->approvalService = $approvalService;
        $this->validationService = $validationService;
        $this->systemApprovalService = $systemApprovalService;
    }

    /**
     * Display a listing of HFS requests
     */
    public function index(Request $request)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('view hfs requests');

        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $assets = Asset::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('assets.hfs.requests.index', compact('assets'));
    }

    /**
     * Get HFS requests data for DataTables
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $query = HfsRequest::where('hfs_requests.company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('hfs_requests.branch_id', $branchId))
            ->with(['initiator', 'hfsAssets.asset']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('hfs_requests.status', $request->status);
        }

        if ($request->filled('overdue')) {
            $query->overdue();
        }

        if ($request->filled('date_from')) {
            $query->where('hfs_requests.intended_sale_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('hfs_requests.intended_sale_date', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->editColumn('request_no', function($hfs) {
                return '<a href="' . route('assets.hfs.requests.show', Hashids::encode($hfs->id)) . '">' . $hfs->request_no . '</a>';
            })
            ->editColumn('intended_sale_date', function($hfs) {
                return $hfs->intended_sale_date ? $hfs->intended_sale_date->format('d M Y') : '-';
            })
            ->addColumn('total_carrying_amount', function($hfs) {
                return number_format($hfs->total_carrying_amount, 2);
            })
            ->addColumn('asset_count', function($hfs) {
                return $hfs->hfsAssets->count();
            })
            ->addColumn('asset_codes', function($hfs) {
                return $hfs->hfsAssets->map(function($hfsAsset) {
                    return $hfsAsset->asset->code ?? 'N/A';
                })->implode(', ');
            })
            ->editColumn('status', function($hfs) {
                $statusColors = [
                    'draft' => 'secondary',
                    'in_review' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'cancelled' => 'dark',
                    'sold' => 'info'
                ];
                $color = $statusColors[$hfs->status] ?? 'secondary';
                $statusLabel = ucfirst(str_replace('_', ' ', $hfs->status));
                return '<span class="badge bg-' . $color . '">' . $statusLabel . '</span>';
            })
            ->addColumn('is_overdue', function($hfs) {
                return $hfs->isOverdue() ? '<span class="badge bg-danger">Overdue</span>' : '';
            })
            ->addColumn('id_hashed', function($hfs) {
                return Hashids::encode($hfs->id);
            })
            ->addColumn('actions', function($hfs) {
                $encodedId = Hashids::encode($hfs->id);
                $actions = '<div class="btn-group btn-group-sm">';
                $actions .= '<a href="' . route('assets.hfs.requests.show', $encodedId) . '" class="btn btn-outline-primary" title="View"><i class="bx bx-show"></i></a>';
                
                if ($hfs->status == 'draft') {
                    $actions .= '<a href="' . route('assets.hfs.requests.edit', $encodedId) . '" class="btn btn-outline-info" title="Edit"><i class="bx bx-edit"></i></a>';
                }
                
                if ($hfs->status == 'in_review') {
                    // Show approval actions if user has permission
                    $actions .= '<button type="button" class="btn btn-outline-success" onclick="approveHfs(\'' . $encodedId . '\')" title="Approve"><i class="bx bx-check"></i></button>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['request_no', 'status', 'is_overdue', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new HFS request
     */
    public function create(Request $request)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('create hfs requests');

        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        // Get eligible assets (not already HFS, not disposed)
        $assets = Asset::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('hfs_status')
                  ->orWhere('hfs_status', 'none');
            })
            ->orderBy('name')
            ->get();

        // Get customers for buyer selection (in HFS, we're selling assets, so we need buyers/customers)
        $customers = Customer::where('company_id', $user->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('assets.hfs.requests.create', compact('assets', 'customers'));
    }

    /**
     * Store a newly created HFS request
     */
    public function store(Request $request)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('create hfs requests');

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'required|exists:assets,id',
            'intended_sale_date' => 'required|date',
            'expected_close_date' => 'nullable|date|after_or_equal:intended_sale_date',
            'customer_id' => 'nullable|exists:customers,id',
            'buyer_name' => 'nullable|string|max:255',
            'buyer_contact' => 'nullable|string|max:255',
            'buyer_address' => 'nullable|string',
            'justification' => 'required|string',
            'expected_costs_to_sell' => 'nullable|numeric|min:0',
            'expected_fair_value' => 'nullable|numeric|min:0',
            'probability_pct' => 'nullable|numeric|min:0|max:100',
            'marketing_actions' => 'required|string',
            'sale_price_range' => 'nullable|string',
            'management_committed' => 'nullable|boolean',
            'management_commitment_date' => 'nullable|date|required_if:management_committed,true',
            'exceeds_12_months' => 'nullable|boolean',
            'extension_justification' => 'nullable|string|required_if:exceeds_12_months,true',
            'is_disposal_group' => 'nullable|boolean',
            'disposal_group_description' => 'nullable|string|required_if:is_disposal_group,true',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // 10MB max per file
        ]);

        try {
            // Handle file uploads
            $attachmentPaths = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('hfs-attachments', 'public');
                    $attachmentPaths[] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'uploaded_at' => now()->toDateTimeString(),
                    ];
                }
            }
            
            // Add attachment paths to validated data
            if (!empty($attachmentPaths)) {
                $validated['attachments'] = $attachmentPaths;
            } else {
                $validated['attachments'] = null;
            }

            $hfsRequest = $this->hfsService->createHfsRequest($validated);

            return redirect()
                ->route('assets.hfs.requests.show', Hashids::encode($hfsRequest->id))
                ->with('success', 'HFS request created successfully.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create HFS request: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified HFS request
     */
    public function show($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $hfsRequest = HfsRequest::with([
            'hfsAssets.asset.category',
            'valuations.impairmentJournal.items.chartAccount',
            'disposal.journal.items.chartAccount',
            'disposal.bankAccount',
            'discontinuedFlag',
            'approvals.approver',
            'auditLogs.user',
            'initiator',
            'extensionApprover',
            'rejectedBy',
            'submittedBy'
        ])->findOrFail($decodedId);

        $encodedId = Hashids::encode($hfsRequest->id);

        // Get approval service data
        $user = Auth::user();
        $approvalService = app(\App\Services\ApprovalService::class);
        $canSubmit = $approvalService->canUserSubmit($hfsRequest, $user->id);
        $canApprove = $approvalService->canUserApprove($hfsRequest, $user->id);
        $currentApprovers = $approvalService->getCurrentApprovers($hfsRequest);
        $currentLevel = $approvalService->getCurrentApprovalLevel($hfsRequest);
        $approvalHistory = \App\Models\ApprovalHistory::where('approvable_type', get_class($hfsRequest))
            ->where('approvable_id', $hfsRequest->id)
            ->with(['approvalLevel', 'approver'])
            ->orderBy('created_at')
            ->get();

        return view('assets.hfs.requests.show', compact(
            'hfsRequest',
            'encodedId',
            'canSubmit',
            'canApprove',
            'currentApprovers',
            'currentLevel',
            'approvalHistory'
        ));
    }

    /**
     * Show the form for editing the specified HFS request
     */
    public function edit($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $hfsRequest = HfsRequest::with(['hfsAssets.asset'])->findOrFail($decodedId);

        if ($hfsRequest->status !== 'draft') {
            return redirect()
                ->route('assets.hfs.requests.show', Hashids::encode($hfsRequest->id))
                ->with('error', 'Only draft HFS requests can be edited.');
        }

        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $assets = Asset::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Get customers for buyer selection (in HFS, we're selling assets, so we need buyers/customers)
        $customers = Customer::where('company_id', $user->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $encodedId = Hashids::encode($hfsRequest->id);

        return view('assets.hfs.requests.edit', compact('hfsRequest', 'assets', 'customers', 'encodedId'));
    }

    /**
     * Update the specified HFS request
     */
    public function update(Request $request, $id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $hfsRequest = HfsRequest::findOrFail($decodedId);

        if ($hfsRequest->status !== 'draft') {
            return redirect()
                ->back()
                ->with('error', 'Only draft HFS requests can be updated.');
        }

        $validated = $request->validate([
            'intended_sale_date' => 'required|date',
            'expected_close_date' => 'nullable|date|after_or_equal:intended_sale_date',
            'customer_id' => 'nullable|exists:customers,id',
            'buyer_name' => 'nullable|string|max:255',
            'buyer_contact' => 'nullable|string|max:255',
            'buyer_address' => 'nullable|string',
            'justification' => 'required|string',
            'expected_costs_to_sell' => 'nullable|numeric|min:0',
            'expected_fair_value' => 'nullable|numeric|min:0',
            'probability_pct' => 'nullable|numeric|min:0|max:100',
            'marketing_actions' => 'required|string',
            'sale_price_range' => 'nullable|string',
            'management_committed' => 'nullable|boolean',
            'management_commitment_date' => 'nullable|date|required_if:management_committed,true',
            'exceeds_12_months' => 'nullable|boolean',
            'extension_justification' => 'nullable|string|required_if:exceeds_12_months,true',
            'is_disposal_group' => 'nullable|boolean',
            'disposal_group_description' => 'nullable|string|required_if:is_disposal_group,true',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // 10MB max per file
        ]);

        try {
            DB::beginTransaction();

            // If customer_id is provided, populate buyer fields from customer
            if (!empty($validated['customer_id'])) {
                $customer = Customer::find($validated['customer_id']);
                if ($customer) {
                    $validated['buyer_name'] = $validated['buyer_name'] ?? $customer->name;
                    $validated['buyer_contact'] = $validated['buyer_contact'] ?? $customer->phone;
                    $validated['buyer_address'] = $validated['buyer_address'] ?? $customer->company_address;
                }
            }

            // Handle file uploads
            $attachmentPaths = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('hfs-attachments', 'public');
                    $attachmentPaths[] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'uploaded_at' => now()->toDateTimeString(),
                    ];
                }
            }
            
            // Handle attachments - merge new with existing or keep existing
            $existingAttachments = $hfsRequest->attachments ?? [];
            if (!empty($attachmentPaths)) {
                // Merge new attachments with existing ones
                $validated['attachments'] = array_merge($existingAttachments, $attachmentPaths);
            } else {
                // No new attachments uploaded - keep existing ones
                // Don't set attachments in validated if there are existing ones (to preserve them)
                // Only set to null if we explicitly want to clear them (which we don't in update)
                if (empty($existingAttachments)) {
                    $validated['attachments'] = null;
                }
                // If existing attachments exist, don't include 'attachments' in validated
                // so the update won't overwrite them
            }

            $hfsRequest->update($validated);
            $hfsRequest->updated_by = Auth::id();
            $hfsRequest->save();

            DB::commit();

            return redirect()
                ->route('assets.hfs.requests.show', Hashids::encode($hfsRequest->id))
                ->with('success', 'HFS request updated successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('HFS Request update error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update HFS request: ' . $e->getMessage());
        }
    }

    /**
     * Submit HFS request for approval
     */
    public function submitForApproval(Request $request, $id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $hfsRequest = HfsRequest::findOrFail($decodedId);

        if ($hfsRequest->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft HFS requests can be submitted for approval.'
            ], 400);
        }

        try {
            // Validate before submission using HFS validation service
            $validation = $this->validationService->validateForApproval($hfsRequest);
            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $validation['errors'])
                ], 400);
            }

            // Use system ApprovalService for submission
            $this->systemApprovalService->submitForApproval($hfsRequest, Auth::id());
            
            // Update status to 'in_review' (HFS uses 'in_review' instead of 'pending_approval')
            $hfsRequest->status = 'in_review';
            $hfsRequest->save();

            return response()->json([
                'success' => true,
                'message' => 'HFS request submitted for approval successfully.',
                'hfs_request' => $hfsRequest->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit for approval: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Approve HFS request at specific level
     */
    public function approve(Request $request, $id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $hfsRequest = HfsRequest::findOrFail($decodedId);

        $validated = $request->validate([
            'approval_notes' => 'nullable|string',
        ]);

        try {
            // Get current approval level
            $currentLevel = $this->systemApprovalService->getCurrentApprovalLevel($hfsRequest);
            if (!$currentLevel) {
                return response()->json([
                    'success' => false,
                    'message' => 'No approval level found'
                ], 400);
            }
            
            // Approve using system ApprovalService
            $this->systemApprovalService->approve($hfsRequest, $currentLevel->id, Auth::id(), $validated['approval_notes'] ?? null);
            
            // Refresh model to get updated status
            $hfsRequest->refresh();
            
            // If fully approved, trigger HFS reclassification
            // ApprovalService sets status to 'approved' when all levels are complete
            if ($hfsRequest->status === 'approved') {
                // All approvals complete - trigger reclassification
                $this->hfsService->approveHfsRequest($hfsRequest, 'final', Auth::id(), $validated['approval_notes'] ?? null);
            }

            return response()->json([
                'success' => true,
                'message' => 'HFS request approved successfully.',
                'hfs_request' => $hfsRequest->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Reject HFS request
     */
    public function reject(Request $request, $id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $hfsRequest = HfsRequest::findOrFail($decodedId);

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        try {
            // Get current approval level
            $currentLevel = $this->systemApprovalService->getCurrentApprovalLevel($hfsRequest);
            if (!$currentLevel) {
                return response()->json([
                    'success' => false,
                    'message' => 'No approval level found'
                ], 400);
            }
            
            // Reject using system ApprovalService
            $this->systemApprovalService->reject($hfsRequest, $currentLevel->id, Auth::id(), $validated['rejection_reason']);

            return response()->json([
                'success' => true,
                'message' => 'HFS request rejected.',
                'hfs_request' => $hfsRequest->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel HFS request
     */
    public function cancel(Request $request, $id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $hfsRequest = HfsRequest::findOrFail($decodedId);

        if (!in_array($hfsRequest->status, ['draft', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'HFS request cannot be cancelled in current status.'
            ], 400);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string',
        ]);

        try {
            $result = $this->hfsService->cancelHfs($hfsRequest, $validated['reason'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'HFS request cancelled successfully.',
                'hfs_request' => $hfsRequest->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Validate HFS request (for AJAX calls)
     */
    public function validateHfsRequest(Request $request, $id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $hfsRequest = HfsRequest::with(['hfsAssets.asset'])->findOrFail($decodedId);

        $validation = $this->validationService->validateForApproval($hfsRequest);

        return response()->json($validation);
    }
}
