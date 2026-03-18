<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetImpairment;
use App\Models\Assets\AssetDepreciation;
use App\Services\Assets\ImpairmentService;
use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;

class AssetImpairmentController extends Controller
{
    protected $impairmentService;

    public function __construct(ImpairmentService $impairmentService)
    {
        $this->impairmentService = $impairmentService;
    }

    /**
     * Display a listing of impairments
     */
    public function index(Request $request)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('view asset impairments');

        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $query = AssetImpairment::with(['asset', 'company', 'branch'])
            ->where('company_id', $user->company_id);

        // Filters
        if ($request->filled('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_reversal')) {
            $query->where('is_reversal', $request->is_reversal);
        }

        if ($request->filled('date_from')) {
            $query->where('impairment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('impairment_date', '<=', $request->date_to);
        }

        $impairments = $query->orderBy('impairment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $assets = Asset::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('assets.impairments.index', compact('impairments', 'assets'));
    }

    /**
     * Show the form for creating a new impairment
     */
    public function create(Request $request)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('create asset impairments');

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

        // Get chart accounts for impairment loss (expense accounts)
        $impairmentLossAccounts = ChartAccount::whereHas('accountClassGroup', function($q) {
                $q->where('company_id', Auth::user()->company_id)
                  ->whereHas('accountClass', function($subQ) {
                      $subQ->whereRaw('LOWER(name) LIKE ?', ['%expense%']);
                  });
            })
            ->where(function($q) {
                $q->whereRaw('LOWER(account_name) LIKE ?', ['%impairment%'])
                  ->orWhereRaw('LOWER(account_name) LIKE ?', ['%loss%']);
            })
            ->orderBy('account_name')
            ->get();

        return view('assets.impairments.create', compact('assets', 'asset', 'impairmentLossAccounts'));
    }

    /**
     * Store a newly created impairment
     */
    public function store(Request $request)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('create asset impairments');

        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'impairment_date' => 'required|date',
            'impairment_type' => 'required|in:individual,cgu',
            'indicator_physical_damage' => 'boolean',
            'indicator_obsolescence' => 'boolean',
            'indicator_technological_change' => 'boolean',
            'indicator_idle_asset' => 'boolean',
            'indicator_market_decline' => 'boolean',
            'indicator_legal_regulatory' => 'boolean',
            'other_indicators' => 'nullable|string',
            'fair_value_less_costs' => 'nullable|numeric|min:0',
            'value_in_use' => 'nullable|numeric|min:0',
            'discount_rate' => 'nullable|numeric|min:0|max:100',
            'cash_flow_projections' => 'nullable|array',
            'cash_flow_projections.*' => 'numeric|min:0',
            'useful_life_after' => 'nullable|integer|min:1',
            'residual_value_after' => 'nullable|numeric|min:0',
            'impairment_loss_account_id' => 'nullable|exists:chart_accounts,id',
            'impairment_test_report' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120',
            'notes' => 'nullable|string',
        ]);

        // Validate that at least one recoverable amount method is provided
        if (empty($validated['fair_value_less_costs']) && empty($validated['value_in_use']) && empty($validated['cash_flow_projections'])) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['fair_value_less_costs' => 'Either fair value less costs, value in use, or cash flow projections must be provided']);
        }

        $asset = Asset::findOrFail($validated['asset_id']);

        if ($asset->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to asset');
        }

        // Generate impairment number
        $impairmentNumber = 'IMP-' . date('Y') . '-' . str_pad(
            AssetImpairment::whereYear('created_at', now()->year)->count() + 1,
            5,
            '0',
            STR_PAD_LEFT
        );

        DB::beginTransaction();
        try {
            // Handle file uploads
            $testReportPath = null;
            if ($request->hasFile('impairment_test_report')) {
                $file = $request->file('impairment_test_report');
                $testReportPath = $file->store('impairments/reports', 'public');
            }

            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $attachments[] = $file->store('impairments/attachments', 'public');
                }
            }

            $impairment = AssetImpairment::create([
                'company_id' => Auth::user()->company_id,
                'branch_id' => session('branch_id') ?? $asset->branch_id,
                'asset_id' => $asset->id,
                'impairment_number' => $impairmentNumber,
                'impairment_date' => $validated['impairment_date'],
                'impairment_type' => $validated['impairment_type'],
                'indicator_physical_damage' => $validated['indicator_physical_damage'] ?? false,
                'indicator_obsolescence' => $validated['indicator_obsolescence'] ?? false,
                'indicator_technological_change' => $validated['indicator_technological_change'] ?? false,
                'indicator_idle_asset' => $validated['indicator_idle_asset'] ?? false,
                'indicator_market_decline' => $validated['indicator_market_decline'] ?? false,
                'indicator_legal_regulatory' => $validated['indicator_legal_regulatory'] ?? false,
                'other_indicators' => $validated['other_indicators'] ?? null,
                'fair_value_less_costs' => $validated['fair_value_less_costs'] ?? null,
                'value_in_use' => $validated['value_in_use'] ?? null,
                'discount_rate' => $validated['discount_rate'] ?? null,
                'cash_flow_projections' => $validated['cash_flow_projections'] ?? null,
                'useful_life_after' => $validated['useful_life_after'] ?? null,
                'residual_value_after' => $validated['residual_value_after'] ?? null,
                'impairment_loss_account_id' => $validated['impairment_loss_account_id'] ?? $asset->category->impairment_loss_account_id ?? null,
                'impairment_test_report_path' => $testReportPath,
                'attachments' => $attachments,
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
                'prepared_by' => Auth::id(),
                'created_by' => Auth::id(),
            ]);

            // Process impairment calculations
            $result = $this->impairmentService->processImpairment($impairment, $validated);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            DB::commit();

            return redirect()->route('assets.impairments.show', Hashids::encode($impairment->id))
                ->with('success', 'Impairment created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Impairment creation error: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create impairment: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified impairment
     */
    public function show($encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('view asset impairments');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $impairment = AssetImpairment::with([
            'asset.category',
            'asset.depreciations',
            'company',
            'branch',
            'preparedBy',
            'financeManager',
            'cfoApprover',
            'journal.items.chartAccount',
            'originalImpairment',
            'reversals',
            'submittedBy',
            'approvedBy'
        ])->findOrFail($id);

        if ($impairment->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        // Get approval service data
        $user = Auth::user();
        $approvalService = app(\App\Services\ApprovalService::class);
        $canSubmit = $approvalService->canUserSubmit($impairment, $user->id);
        $canApprove = $approvalService->canUserApprove($impairment, $user->id);
        $currentApprovers = $approvalService->getCurrentApprovers($impairment);
        $currentLevel = $approvalService->getCurrentApprovalLevel($impairment);
        $approvalHistory = \App\Models\ApprovalHistory::where('approvable_type', get_class($impairment))
            ->where('approvable_id', $impairment->id)
            ->with(['approvalLevel', 'approver'])
            ->orderBy('created_at')
            ->get();

        // Check if approval levels are configured
        $approvalLevels = $approvalService->getApprovalLevels($impairment);
        $hasRequiredApprovalLevels = $approvalLevels->where('is_required', true)->isNotEmpty();

        return view('assets.impairments.show', compact(
            'impairment',
            'canSubmit',
            'canApprove',
            'currentApprovers',
            'currentLevel',
            'approvalHistory',
            'hasRequiredApprovalLevels'
        ));
    }

    /**
     * Create impairment reversal
     */
    public function createReversal($encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('create asset impairments');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $originalImpairment = AssetImpairment::with(['asset'])->findOrFail($id);

        if (!$originalImpairment->canBeReversed()) {
            return redirect()->back()->with('error', 'This impairment cannot be reversed');
        }

        // Get chart accounts for impairment reversal (income accounts)
        $impairmentReversalAccounts = ChartAccount::whereHas('accountClassGroup', function($q) {
                $q->where('company_id', Auth::user()->company_id)
                  ->whereHas('accountClass', function($subQ) {
                      $subQ->whereRaw('LOWER(name) IN (?, ?)', ['income', 'revenue']);
                  });
            })
            ->where(function($q) {
                $q->whereRaw('LOWER(account_name) LIKE ?', ['%impairment%'])
                  ->orWhereRaw('LOWER(account_name) LIKE ?', ['%reversal%']);
            })
            ->orderBy('account_name')
            ->get();

        return view('assets.impairments.create-reversal', compact('originalImpairment', 'impairmentReversalAccounts'));
    }

    /**
     * Store impairment reversal
     */
    public function storeReversal(Request $request, $encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('create asset impairments');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $originalImpairment = AssetImpairment::findOrFail($id);

        if (!$originalImpairment->canBeReversed()) {
            return redirect()->back()->with('error', 'This impairment cannot be reversed');
        }

        $validated = $request->validate([
            'reversal_date' => 'required|date',
            'reversal_amount' => 'required|numeric|min:0.01',
            'impairment_reversal_account_id' => 'nullable|exists:chart_accounts,id',
            'notes' => 'nullable|string',
        ]);

        $maxReversible = $originalImpairment->remaining_reversible_amount;
        if ($validated['reversal_amount'] > $maxReversible) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['reversal_amount' => "Reversal amount cannot exceed {$maxReversible}"]);
        }

        DB::beginTransaction();
        try {
            $reversalNumber = 'IMP-REV-' . date('Y') . '-' . str_pad(
                AssetImpairment::whereYear('created_at', now()->year)->count() + 1,
                5,
                '0',
                STR_PAD_LEFT
            );

            $reversal = AssetImpairment::create([
                'company_id' => $originalImpairment->company_id,
                'branch_id' => $originalImpairment->branch_id,
                'asset_id' => $originalImpairment->asset_id,
                'impairment_number' => $reversalNumber,
                'impairment_date' => $validated['reversal_date'],
                'impairment_type' => $originalImpairment->impairment_type,
                'is_reversal' => true,
                'original_impairment_id' => $originalImpairment->id,
                'reversal_amount' => $validated['reversal_amount'],
                'reversal_date' => $validated['reversal_date'],
                'impairment_reversal_account_id' => $validated['impairment_reversal_account_id'] ?? $originalImpairment->impairment_reversal_account_id,
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
                'prepared_by' => Auth::id(),
                'created_by' => Auth::id(),
            ]);

            $result = $this->impairmentService->processReversal($originalImpairment, $reversal, $validated);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            DB::commit();

            return redirect()->route('assets.impairments.show', Hashids::encode($reversal->id))
                ->with('success', 'Impairment reversal created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Impairment reversal error: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create reversal: ' . $e->getMessage());
        }
    }

    /**
     * Submit for approval
     */
    public function submitForApproval($encodedId)
    {
        // TODO: Add proper authorization policy
        // Note: Submission should be allowed for creator or users with submit permission
        // $this->authorize('submit asset impairments');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $impairment = AssetImpairment::findOrFail($id);

        // Check if impairment belongs to user's company
        if ($impairment->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to impairment');
        }

        if ($impairment->status !== 'draft') {
            return redirect()->back()->with('error', 'Only draft impairments can be submitted for approval');
        }

        // Check if approval levels are configured
        $approvalService = app(\App\Services\ApprovalService::class);
        $approvalLevels = $approvalService->getApprovalLevels($impairment);
        $firstLevel = $approvalLevels->where('is_required', true)->first();
        
        if (!$firstLevel) {
            return redirect()->back()->with('error', 
                'Cannot submit for approval: No approval levels have been configured for asset impairments. ' .
                'Please contact your system administrator to configure approval levels in the system settings before submitting impairments for approval.'
            );
        }

        try {
            $approvalService->submitForApproval($impairment, Auth::id());

            return redirect()->back()->with('success', 'Impairment submitted for approval');
        } catch (\Exception $e) {
            Log::error('Impairment submission error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to submit for approval: ' . $e->getMessage());
        }
    }

    /**
     * Approve impairment
     */
    public function approve(Request $request, $encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('approve asset impairments');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $impairment = AssetImpairment::findOrFail($id);

        if (!$impairment->canBeApproved()) {
            return redirect()->back()->with('error', 'Impairment cannot be approved in current status');
        }

        $validated = $request->validate([
            'approval_notes' => 'nullable|string',
        ]);

        try {
            $approvalService = app(\App\Services\ApprovalService::class);
            
            // Get current approval level
            $currentLevel = $approvalService->getCurrentApprovalLevel($impairment);
            if (!$currentLevel) {
                return redirect()->back()->with('error', 'No approval level found');
            }
            
            // Approve using ApprovalService
            $approvalService->approve($impairment, $currentLevel->id, Auth::id(), $validated['approval_notes'] ?? null);
            
            // Refresh model to get updated status
            $impairment->refresh();
            
            // If fully approved, post to GL if auto-posting is enabled
            if ($impairment->status === 'approved' && config('assets.auto_post_impairments', false) && $impairment->impairment_loss > 0) {
                $this->postToGL($encodedId);
            }

            return redirect()->back()->with('success', 'Impairment approved successfully');

        } catch (\Exception $e) {
            Log::error('Impairment approval error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to approve impairment: ' . $e->getMessage());
        }
    }

    /**
     * Reject impairment
     */
    public function reject(Request $request, $encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('approve asset impairments');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $impairment = AssetImpairment::findOrFail($id);

        if ($impairment->status !== 'pending_approval') {
            return redirect()->back()->with('error', 'Only pending impairments can be rejected');
        }

        $validated = $request->validate([
            'approval_notes' => 'required|string',
        ]);

        try {
            $approvalService = app(\App\Services\ApprovalService::class);
            
            // Get current approval level
            $currentLevel = $approvalService->getCurrentApprovalLevel($impairment);
            if (!$currentLevel) {
                return redirect()->back()->with('error', 'No approval level found');
            }
            
            // Reject using ApprovalService
            $approvalService->reject($impairment, $currentLevel->id, Auth::id(), $validated['approval_notes']);

            return redirect()->back()->with('success', 'Impairment rejected successfully');

        } catch (\Exception $e) {
            Log::error('Impairment rejection error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to reject impairment: ' . $e->getMessage());
        }
    }

    /**
     * Post impairment to General Ledger
     */
    public function postToGL($encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('post asset impairments');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $impairment = AssetImpairment::findOrFail($id);

        if (!$impairment->canBePosted()) {
            return redirect()->back()->with('error', 'Impairment cannot be posted in current status');
        }

        try {
            if ($impairment->is_reversal) {
                // Process reversal
                $original = $impairment->originalImpairment;
                $this->impairmentService->processReversal($original, $impairment, [
                    'reversal_amount' => $impairment->reversal_amount
                ]);
                
                // Log activity
                $impairment->asset->logActivity('post', "Posted Asset Impairment Reversal to GL - {$impairment->asset->code}", [
                    'impairment_id' => $impairment->id,
                    'reversal_amount' => number_format($impairment->reversal_amount ?? 0, 2)
                ]);
            } else {
                // Process regular impairment
                $this->impairmentService->createJournalEntries($impairment);
                
                // Log activity
                $impairment->asset->logActivity('post', "Posted Asset Impairment to GL - {$impairment->asset->code}", [
                    'impairment_id' => $impairment->id,
                    'impairment_loss' => number_format($impairment->impairment_loss ?? 0, 2)
                ]);
            }

            return redirect()->back()->with('success', 'Impairment posted to General Ledger successfully');

        } catch (\Exception $e) {
            Log::error('GL posting error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Failed to post to GL: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified impairment
     */
    public function destroy($encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('delete asset impairments');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $impairment = AssetImpairment::findOrFail($id);

        if ($impairment->status !== 'draft') {
            return redirect()->back()->with('error', 'Only draft impairments can be deleted');
        }

        if ($impairment->gl_posted) {
            return redirect()->back()->with('error', 'Posted impairments cannot be deleted');
        }

        $impairment->delete();

        return redirect()->route('assets.impairments.index')
            ->with('success', 'Impairment deleted successfully');
    }
}
