<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetRevaluation;
use App\Models\Assets\AssetDepreciation;
use App\Models\Assets\RevaluationBatch;
use App\Services\Assets\RevaluationService;
use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class AssetRevaluationController extends Controller
{
    protected $revaluationService;

    public function __construct(RevaluationService $revaluationService)
    {
        $this->revaluationService = $revaluationService;
    }

    /**
     * Display a listing of revaluations
     */
    public function index(Request $request)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('view asset revaluations');

        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $assets = Asset::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('assets.revaluations.index', compact('assets'));
    }

    /**
     * Get revaluations data for DataTables
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $query = AssetRevaluation::query()
            ->where('asset_revaluations.company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('asset_revaluations.branch_id', $branchId))
            ->leftJoin('assets', 'assets.id', '=', 'asset_revaluations.asset_id')
            ->select('asset_revaluations.*', 'assets.code as asset_code', 'assets.name as asset_name');

        // Apply filters
        if ($request->filled('asset_id')) {
            $query->where('asset_revaluations.asset_id', $request->asset_id);
        }

        if ($request->filled('status')) {
            $query->where('asset_revaluations.status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('asset_revaluations.revaluation_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('asset_revaluations.revaluation_date', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->editColumn('revaluation_date', function($revaluation) {
                return $revaluation->revaluation_date ? $revaluation->revaluation_date->format('d M Y') : '-';
            })
            ->editColumn('carrying_amount_before', function($revaluation) {
                return number_format($revaluation->carrying_amount_before ?? 0, 2);
            })
            ->editColumn('fair_value', function($revaluation) {
                return number_format($revaluation->fair_value ?? 0, 2);
            })
            ->addColumn('revaluation_increase_display', function($revaluation) {
                if ($revaluation->revaluation_increase > 0) {
                    return '<span class="text-success">+' . number_format($revaluation->revaluation_increase, 2) . '</span>';
                }
                return '-';
            })
            ->addColumn('revaluation_decrease_display', function($revaluation) {
                if ($revaluation->revaluation_decrease > 0) {
                    return '<span class="text-danger">-' . number_format($revaluation->revaluation_decrease, 2) . '</span>';
                }
                return '-';
            })
            ->editColumn('status', function($revaluation) {
                $statusColors = [
                    'draft' => 'secondary',
                    'pending_approval' => 'warning',
                    'approved' => 'info',
                    'posted' => 'success',
                    'rejected' => 'danger'
                ];
                $color = $statusColors[$revaluation->status] ?? 'secondary';
                $statusLabel = ucfirst(str_replace('_', ' ', $revaluation->status));
                return '<span class="badge bg-' . $color . '">' . $statusLabel . '</span>';
            })
            ->addColumn('id_hashed', function($revaluation) {
                return Hashids::encode($revaluation->id);
            })
            ->addColumn('actions', function($revaluation) {
                $encodedId = Hashids::encode($revaluation->id);
                $actions = '<div class="btn-group btn-group-sm">';
                $actions .= '<a href="' . route('assets.revaluations.show', $encodedId) . '" class="btn btn-outline-primary" title="View"><i class="bx bx-show"></i></a>';
                if ($revaluation->status == 'draft') {
                    $actions .= '<a href="' . route('assets.revaluations.edit', $encodedId) . '" class="btn btn-outline-info" title="Edit"><i class="bx bx-edit"></i></a>';
                }
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status', 'revaluation_increase_display', 'revaluation_decrease_display', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new revaluation
     */
    public function create(Request $request)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('create asset revaluations');

        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $assetId = $request->asset_id;
        $asset = null;

        if ($assetId) {
            $asset = Asset::with(['category', 'depreciations'])
                ->where('company_id', $user->company_id)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->findOrFail($assetId);
        }

        $assets = Asset::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Get chart accounts for revaluation reserve (equity accounts)
        $companyId = Auth::user()->company_id;
        $reserveAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereHas('accountClass', function($classQ) {
                      $classQ->whereRaw('LOWER(name) LIKE ?', ['%equity%']);
                  });
            })
            ->orWhere(function($q) use ($companyId) {
                $q->where('has_equity', true)
                  ->whereHas('accountClassGroup', function($subQ) use ($companyId) {
                      $subQ->where('company_id', $companyId);
                  });
            })
            ->where(function($q) {
                $q->whereRaw('LOWER(account_name) LIKE ?', ['%revaluation%'])
                  ->orWhereRaw('LOWER(account_name) LIKE ?', ['%reserve%']);
            })
            ->orderBy('account_name')
            ->get();

        return view('assets.revaluations.create', compact('assets', 'asset', 'reserveAccounts'));
    }

    /**
     * Store a newly created revaluation
     */
    public function store(Request $request)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('create asset revaluations');

        $validated = $request->validate([
            'assets' => 'required|array|min:1',
            'assets.*.asset_id' => 'required|exists:assets,id',
            'assets.*.fair_value' => 'required|numeric|min:0',
            'assets.*.carrying_amount' => 'required|numeric|min:0',
            'revaluation_date' => 'required|date',
            'valuation_model' => 'required|in:cost,revaluation',
            'valuer_name' => 'nullable|string|max:255',
            'valuer_license' => 'nullable|string|max:255',
            'valuer_company' => 'nullable|string|max:255',
            'valuation_report_ref' => 'nullable|string|max:255',
            'valuation_report' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'reason' => 'required|string',
            'useful_life_after' => 'nullable|integer|min:1',
            'residual_value_after' => 'nullable|numeric|min:0',
            'revaluation_reserve_account_id' => 'nullable|exists:chart_accounts,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120',
        ]);

        // Validate all assets belong to user's company
        $assetIds = collect($validated['assets'])->pluck('asset_id')->unique();
        $assets = Asset::with('category')
            ->whereIn('id', $assetIds)
            ->where('company_id', Auth::user()->company_id)
            ->get();

        if ($assets->count() !== $assetIds->count()) {
            abort(403, 'Unauthorized access to one or more assets');
        }

        // Handle file uploads (shared across all revaluations)
        $valuationReportPath = null;
        if ($request->hasFile('valuation_report')) {
            $file = $request->file('valuation_report');
            $valuationReportPath = $file->store('revaluations/reports', 'public');
        }

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachments[] = $file->store('revaluations/attachments', 'public');
            }
        }

        DB::beginTransaction();
        try {
            // Create batch for multiple revaluations
            $batchNumber = 'BATCH-REV-' . date('Y') . '-' . str_pad(
                RevaluationBatch::whereYear('created_at', now()->year)->count() + 1,
                5,
                '0',
                STR_PAD_LEFT
            );

            $batch = RevaluationBatch::create([
                'company_id' => Auth::user()->company_id,
                'branch_id' => session('branch_id') ?? $assets->first()->branch_id,
                'batch_number' => $batchNumber,
                'revaluation_date' => $validated['revaluation_date'],
                'valuation_model' => $validated['valuation_model'],
                'valuer_name' => $validated['valuer_name'] ?? null,
                'valuer_license' => $validated['valuer_license'] ?? null,
                'valuer_company' => $validated['valuer_company'] ?? null,
                'valuation_report_ref' => $validated['valuation_report_ref'] ?? null,
                'valuation_report_path' => $valuationReportPath,
                'reason' => $validated['reason'],
                'status' => 'draft',
                'attachments' => $attachments,
                'created_by' => Auth::id(),
            ]);

            $createdRevaluations = [];
            $baseRevaluationNumber = AssetRevaluation::whereYear('created_at', now()->year)->count();

            foreach ($validated['assets'] as $index => $assetData) {
                $asset = $assets->firstWhere('id', $assetData['asset_id']);
                
                if (!$asset) {
                    throw new \Exception("Asset not found: {$assetData['asset_id']}");
                }

                // Generate unique revaluation number for each asset
                $revaluationNumber = 'REV-' . date('Y') . '-' . str_pad(
                    $baseRevaluationNumber + $index + 1,
                    5,
                    '0',
                    STR_PAD_LEFT
                );

                $revaluation = AssetRevaluation::create([
                    'batch_id' => $batch->id,
                    'company_id' => Auth::user()->company_id,
                    'branch_id' => session('branch_id') ?? $asset->branch_id,
                    'asset_id' => $asset->id,
                    'revaluation_number' => $revaluationNumber,
                    'revaluation_date' => $validated['revaluation_date'],
                    'valuation_model' => $validated['valuation_model'],
                    'valuer_name' => $validated['valuer_name'] ?? null,
                    'valuer_license' => $validated['valuer_license'] ?? null,
                    'valuer_company' => $validated['valuer_company'] ?? null,
                    'valuation_report_ref' => $validated['valuation_report_ref'] ?? null,
                    'valuation_report_path' => $valuationReportPath,
                    'reason' => $validated['reason'],
                    'fair_value' => $assetData['fair_value'],
                    'useful_life_after' => $validated['useful_life_after'] ?? null,
                    'residual_value_after' => $validated['residual_value_after'] ?? null,
                    'revaluation_reserve_account_id' => $validated['revaluation_reserve_account_id'] ?? $asset->category->revaluation_reserve_account_id ?? null,
                    'status' => 'draft',
                    'attachments' => $attachments,
                    'created_by' => Auth::id(),
                ]);

                // Process revaluation calculations
                $result = $this->revaluationService->processRevaluation($revaluation, [
                    'fair_value' => $assetData['fair_value'],
                    'useful_life_after' => $validated['useful_life_after'] ?? null,
                    'residual_value_after' => $validated['residual_value_after'] ?? null,
                ]);

                if (!$result['success']) {
                    throw new \Exception($result['message']);
                }

                $createdRevaluations[] = $revaluation;
            }

            DB::commit();

            $message = count($createdRevaluations) === 1 
                ? 'Revaluation created successfully'
                : count($createdRevaluations) . ' revaluations created successfully in batch ' . $batchNumber;

            // Redirect to batch show page or first revaluation
            if (count($createdRevaluations) > 1) {
                return redirect()->route('assets.revaluations.batch.show', Hashids::encode($batch->id))
                    ->with('success', $message);
            } else {
                return redirect()->route('assets.revaluations.show', Hashids::encode($createdRevaluations[0]->id))
                    ->with('success', $message);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Revaluation creation error: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create revaluation: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified revaluation
     */
    public function show($encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('view asset revaluations');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $revaluation = AssetRevaluation::with([
            'asset.category',
            'asset.depreciations',
            'company',
            'branch',
            'valuer',
            'financeManager',
            'cfoApprover',
            'journal.items.chartAccount',
            'revaluationReserves',
            'submittedBy',
            'approvedBy'
        ])->findOrFail($id);

        // Check authorization
        if ($revaluation->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        // Get approval service data
        $user = Auth::user();
        $approvalService = app(\App\Services\ApprovalService::class);
        $canSubmit = $approvalService->canUserSubmit($revaluation, $user->id);
        $canApprove = $approvalService->canUserApprove($revaluation, $user->id);
        $currentApprovers = $approvalService->getCurrentApprovers($revaluation);
        $currentLevel = $approvalService->getCurrentApprovalLevel($revaluation);
        $approvalHistory = \App\Models\ApprovalHistory::where('approvable_type', get_class($revaluation))
            ->where('approvable_id', $revaluation->id)
            ->with(['approvalLevel', 'approver'])
            ->orderBy('created_at')
            ->get();

        // Check if approval levels are configured
        $approvalLevels = $approvalService->getApprovalLevels($revaluation);
        $hasRequiredApprovalLevels = $approvalLevels->where('is_required', true)->isNotEmpty();

        return view('assets.revaluations.show', compact(
            'revaluation',
            'canSubmit',
            'canApprove',
            'currentApprovers',
            'currentLevel',
            'approvalHistory',
            'hasRequiredApprovalLevels'
        ));
    }

    /**
     * Show the form for editing the specified revaluation
     */
    public function edit($encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('edit asset revaluations');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $revaluation = AssetRevaluation::with(['asset.category'])->findOrFail($id);

        if ($revaluation->status !== 'draft') {
            return redirect()->route('assets.revaluations.show', $encodedId)
                ->with('error', 'Only draft revaluations can be edited');
        }

        // Get chart accounts for revaluation reserve (equity accounts)
        $companyId = Auth::user()->company_id;
        $reserveAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->whereHas('accountClass', function($classQ) {
                      $classQ->whereRaw('LOWER(name) LIKE ?', ['%equity%']);
                  });
            })
            ->orWhere(function($q) use ($companyId) {
                $q->where('has_equity', true)
                  ->whereHas('accountClassGroup', function($subQ) use ($companyId) {
                      $subQ->where('company_id', $companyId);
                  });
            })
            ->where(function($q) {
                $q->whereRaw('LOWER(account_name) LIKE ?', ['%revaluation%'])
                  ->orWhereRaw('LOWER(account_name) LIKE ?', ['%reserve%']);
            })
            ->orderBy('account_name')
            ->get();

        return view('assets.revaluations.edit', compact('revaluation', 'reserveAccounts'));
    }

    /**
     * Update the specified revaluation
     */
    public function update(Request $request, $encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('edit asset revaluations');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $revaluation = AssetRevaluation::findOrFail($id);

        if ($revaluation->status !== 'draft') {
            return redirect()->route('assets.revaluations.show', $encodedId)
                ->with('error', 'Only draft revaluations can be edited');
        }

        $validated = $request->validate([
            'revaluation_date' => 'required|date',
            'valuer_name' => 'nullable|string|max:255',
            'valuer_license' => 'nullable|string|max:255',
            'valuer_company' => 'nullable|string|max:255',
            'valuation_report_ref' => 'nullable|string|max:255',
            'valuation_report' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'reason' => 'required|string',
            'fair_value' => 'required|numeric|min:0',
            'useful_life_after' => 'nullable|integer|min:1',
            'residual_value_after' => 'nullable|numeric|min:0',
            'revaluation_reserve_account_id' => 'nullable|exists:chart_accounts,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120',
        ]);

        DB::beginTransaction();
        try {
            // Handle file uploads
            if ($request->hasFile('valuation_report')) {
                $file = $request->file('valuation_report');
                $revaluation->valuation_report_path = $file->store('revaluations/reports', 'public');
            }

            if ($request->hasFile('attachments')) {
                $attachments = [];
                foreach ($request->file('attachments') as $file) {
                    $attachments[] = $file->store('revaluations/attachments', 'public');
                }
                $revaluation->attachments = array_merge($revaluation->attachments ?? [], $attachments);
            }

            $revaluation->update([
                'revaluation_date' => $validated['revaluation_date'],
                'valuer_name' => $validated['valuer_name'] ?? null,
                'valuer_license' => $validated['valuer_license'] ?? null,
                'valuer_company' => $validated['valuer_company'] ?? null,
                'valuation_report_ref' => $validated['valuation_report_ref'] ?? null,
                'reason' => $validated['reason'],
                'fair_value' => $validated['fair_value'],
                'useful_life_after' => $validated['useful_life_after'] ?? null,
                'residual_value_after' => $validated['residual_value_after'] ?? null,
                'revaluation_reserve_account_id' => $validated['revaluation_reserve_account_id'] ?? $revaluation->asset->category->revaluation_reserve_account_id ?? null,
                'updated_by' => Auth::id(),
            ]);

            // Recalculate
            $result = $this->revaluationService->processRevaluation($revaluation, [
                'fair_value' => $validated['fair_value'],
                'useful_life_after' => $validated['useful_life_after'] ?? null,
                'residual_value_after' => $validated['residual_value_after'] ?? null,
            ]);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            DB::commit();

            return redirect()->route('assets.revaluations.show', $encodedId)
                ->with('success', 'Revaluation updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Revaluation update error: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update revaluation: ' . $e->getMessage());
        }
    }

    /**
     * Submit for approval
     */
    public function submitForApproval($encodedId)
    {
        // TODO: Add proper authorization policy
        // Note: Submission should be allowed for creator or users with submit permission
        // $this->authorize('submit asset revaluations');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $revaluation = AssetRevaluation::findOrFail($id);

        // Check if revaluation belongs to user's company
        if ($revaluation->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to revaluation');
        }

        if ($revaluation->status !== 'draft') {
            return redirect()->back()->with('error', 'Only draft revaluations can be submitted for approval');
        }

        // Validate revaluation is complete
        if (!$revaluation->asset_id) {
            return redirect()->back()->with('error', 'Revaluation must have an asset assigned before submission');
        }

        if (!$revaluation->fair_value || $revaluation->fair_value <= 0) {
            return redirect()->back()->with('error', 'Revaluation must have a valid fair value before submission');
        }

        // Check if approval levels are configured
        $approvalService = app(\App\Services\ApprovalService::class);
        $approvalLevels = $approvalService->getApprovalLevels($revaluation);
        $firstLevel = $approvalLevels->where('is_required', true)->first();
        
        if (!$firstLevel) {
            return redirect()->back()->with('error', 
                'Cannot submit for approval: No approval levels have been configured for asset revaluations. ' .
                'Please contact your system administrator to configure approval levels in the system settings before submitting revaluations for approval.'
            );
        }

        try {
            $approvalService->submitForApproval($revaluation, Auth::id());
            
            // Set valuer_user_id if not already set
            if (!$revaluation->valuer_user_id) {
                $revaluation->valuer_user_id = Auth::id();
                $revaluation->save();
            }

            return redirect()->back()->with('success', 'Revaluation submitted for approval');
        } catch (\Exception $e) {
            Log::error('Revaluation submission error', [
                'revaluation_id' => $revaluation->id,
                'revaluation_number' => $revaluation->revaluation_number,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to submit for approval: ' . $e->getMessage());
        }
    }

    /**
     * Approve revaluation
     */
    public function approve(Request $request, $encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('approve asset revaluations');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $revaluation = AssetRevaluation::findOrFail($id);

        if (!$revaluation->canBeApproved()) {
            return redirect()->back()->with('error', 'Revaluation cannot be approved in current status');
        }

        $validated = $request->validate([
            'approval_notes' => 'nullable|string',
        ]);

        try {
            $approvalService = app(\App\Services\ApprovalService::class);
            
            // Get current approval level
            $currentLevel = $approvalService->getCurrentApprovalLevel($revaluation);
            if (!$currentLevel) {
                return redirect()->back()->with('error', 'No approval level found');
            }
            
            // Approve using ApprovalService
            $approvalService->approve($revaluation, $currentLevel->id, Auth::id(), $validated['approval_notes'] ?? null);
            
            // Refresh model to get updated status
            $revaluation->refresh();
            
            // If fully approved, post to GL if auto-posting is enabled
            if ($revaluation->status === 'approved' && config('assets.auto_post_revaluations', false)) {
                $this->postToGL($encodedId);
            }

            return redirect()->back()->with('success', 'Revaluation approved successfully');

        } catch (\Exception $e) {
            Log::error('Revaluation approval error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to approve revaluation: ' . $e->getMessage());
        }
    }

    /**
     * Reject revaluation
     */
    public function reject(Request $request, $encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('approve asset revaluations');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $revaluation = AssetRevaluation::findOrFail($id);

        if ($revaluation->status !== 'pending_approval') {
            return redirect()->back()->with('error', 'Only pending revaluations can be rejected');
        }

        $validated = $request->validate([
            'approval_notes' => 'required|string',
        ]);

        try {
            $approvalService = app(\App\Services\ApprovalService::class);
            
            // Get current approval level
            $currentLevel = $approvalService->getCurrentApprovalLevel($revaluation);
            if (!$currentLevel) {
                return redirect()->back()->with('error', 'No approval level found');
            }
            
            // Reject using ApprovalService
            $approvalService->reject($revaluation, $currentLevel->id, Auth::id(), $validated['approval_notes']);

            return redirect()->back()->with('success', 'Revaluation rejected');

        } catch (\Exception $e) {
            Log::error('Revaluation rejection error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to reject revaluation: ' . $e->getMessage());
        }
    }

    /**
     * Post revaluation to General Ledger
     */
    public function postToGL($encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('post asset revaluations');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $revaluation = AssetRevaluation::findOrFail($id);

        if (!$revaluation->canBePosted()) {
            return redirect()->back()->with('error', 'Revaluation cannot be posted in current status');
        }

        try {
            $this->revaluationService->createJournalEntries($revaluation);
            
            // Log activity
            $revaluation->asset->logActivity('post', "Posted Asset Revaluation to GL - {$revaluation->asset->code}", [
                'revaluation_id' => $revaluation->id,
                'revaluation_amount' => number_format($revaluation->revaluation_amount ?? 0, 2)
            ]);

            return redirect()->back()->with('success', 'Revaluation posted to General Ledger successfully');

        } catch (\Exception $e) {
            Log::error('GL posting error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Failed to post to GL: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified revaluation
     */
    public function destroy($encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('delete asset revaluations');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $revaluation = AssetRevaluation::findOrFail($id);

        if ($revaluation->status !== 'draft') {
            return redirect()->back()->with('error', 'Only draft revaluations can be deleted');
        }

        if ($revaluation->gl_posted) {
            return redirect()->back()->with('error', 'Posted revaluations cannot be deleted');
        }

        $revaluation->delete();

        return redirect()->route('assets.revaluations.index')
            ->with('success', 'Revaluation deleted successfully');
    }

    /**
     * Show batch details
     */
    public function showBatch($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $batch = RevaluationBatch::with([
            'revaluations.asset.category',
            'revaluations.asset.depreciations',
            'company',
            'branch',
            'valuer',
            'financeManager',
            'cfoApprover'
        ])->findOrFail($id);

        if ($batch->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        return view('assets.revaluations.batch-show', compact('batch'));
    }

    /**
     * Submit batch for approval
     */
    public function submitBatchForApproval($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $batch = RevaluationBatch::with('revaluations')->findOrFail($id);

        if ($batch->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to batch');
        }

        if (!$batch->canBeSubmitted()) {
            return redirect()->back()->with('error', 'Batch cannot be submitted. Ensure all revaluations are in draft status.');
        }

        DB::beginTransaction();
        try {
            // Update batch status
            $batch->status = 'pending_approval';
            $batch->valuer_user_id = Auth::id();
            $batch->save();

            // Update all revaluations in batch
            $batch->revaluations()->update([
                'status' => 'pending_approval',
                'valuer_user_id' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Batch submitted for approval successfully. All ' . $batch->revaluations()->count() . ' revaluations are now pending approval.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch submission error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to submit batch: ' . $e->getMessage());
        }
    }

    /**
     * Approve batch
     */
    public function approveBatch(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $batch = RevaluationBatch::with('revaluations')->findOrFail($id);

        if (!$batch->canBeApproved()) {
            return redirect()->back()->with('error', 'Batch cannot be approved in current status');
        }

        $validated = $request->validate([
            'approval_notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Determine approver role
            $user = Auth::user();
            if ($user->hasRole('finance_manager') || $user->hasRole('accountant')) {
                $batch->finance_manager_id = $user->id;
            } elseif ($user->hasRole('cfo') || $user->hasRole('director')) {
                $batch->cfo_approver_id = $user->id;
            }

            $batch->status = 'approved';
            $batch->approved_at = now();
            $batch->approval_notes = $validated['approval_notes'] ?? null;
            $batch->save();

            // Approve all revaluations in batch
            $batch->revaluations()->update([
                'status' => 'approved',
                'finance_manager_id' => $batch->finance_manager_id,
                'cfo_approver_id' => $batch->cfo_approver_id,
                'approved_at' => now(),
                'approval_notes' => $validated['approval_notes'] ?? null,
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Batch approved successfully. All ' . $batch->revaluations()->count() . ' revaluations have been approved.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch approval error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to approve batch: ' . $e->getMessage());
        }
    }

    /**
     * Reject batch
     */
    public function rejectBatch(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $batch = RevaluationBatch::with('revaluations')->findOrFail($id);

        if ($batch->status !== 'pending_approval') {
            return redirect()->back()->with('error', 'Only pending batches can be rejected');
        }

        $validated = $request->validate([
            'approval_notes' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $batch->status = 'rejected';
            $batch->approval_notes = $validated['approval_notes'];
            $batch->save();

            // Reject all revaluations in batch
            $batch->revaluations()->update([
                'status' => 'rejected',
                'approval_notes' => $validated['approval_notes'],
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Batch rejected. All ' . $batch->revaluations()->count() . ' revaluations have been rejected.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch rejection error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to reject batch: ' . $e->getMessage());
        }
    }
}
