<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetDisposal;
use App\Models\Assets\DisposalReasonCode;
use App\Models\Assets\DisposalApproval;
use App\Services\Assets\DisposalService;
use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class AssetDisposalController extends Controller
{
    protected $disposalService;

    public function __construct(DisposalService $disposalService)
    {
        $this->disposalService = $disposalService;
    }

    /**
     * Display a listing of disposals
     */
    public function index(Request $request)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('view asset disposals');

        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $assets = Asset::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('assets.disposals.index', compact('assets'));
    }

    /**
     * Get disposals data for DataTables
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $query = AssetDisposal::query()
            ->where('asset_disposals.company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('asset_disposals.branch_id', $branchId))
            ->leftJoin('assets', 'assets.id', '=', 'asset_disposals.asset_id')
            ->select('asset_disposals.*', 'assets.code as asset_code', 'assets.name as asset_name');

        // Apply filters
        if ($request->filled('asset_id')) {
            $query->where('asset_disposals.asset_id', $request->asset_id);
        }

        if ($request->filled('status')) {
            $query->where('asset_disposals.status', $request->status);
        }

        if ($request->filled('disposal_type')) {
            $query->where('asset_disposals.disposal_type', $request->disposal_type);
        }

        if ($request->filled('date_from')) {
            $query->where('asset_disposals.proposed_disposal_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('asset_disposals.proposed_disposal_date', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->editColumn('proposed_disposal_date', function($disposal) {
                return $disposal->proposed_disposal_date ? $disposal->proposed_disposal_date->format('d M Y') : '-';
            })
            ->editColumn('actual_disposal_date', function($disposal) {
                return $disposal->actual_disposal_date ? $disposal->actual_disposal_date->format('d M Y') : '-';
            })
            ->editColumn('net_book_value', function($disposal) {
                return number_format($disposal->net_book_value ?? 0, 2);
            })
            ->editColumn('disposal_proceeds', function($disposal) {
                return number_format($disposal->disposal_proceeds ?? 0, 2);
            })
            ->addColumn('gain_loss_display', function($disposal) {
                $gainLoss = $disposal->gain_loss ?? 0;
                if ($gainLoss > 0) {
                    return '<span class="text-success">+' . number_format($gainLoss, 2) . '</span>';
                } elseif ($gainLoss < 0) {
                    return '<span class="text-danger">' . number_format($gainLoss, 2) . '</span>';
                }
                return '-';
            })
            ->editColumn('disposal_type', function($disposal) {
                $types = [
                    'sale' => 'Sale',
                    'scrap' => 'Scrap',
                    'write_off' => 'Write-off',
                    'donation' => 'Donation',
                    'loss' => 'Loss/Theft'
                ];
                return $types[$disposal->disposal_type] ?? $disposal->disposal_type;
            })
            ->editColumn('status', function($disposal) {
                $statusColors = [
                    'draft' => 'secondary',
                    'pending_approval' => 'warning',
                    'approved' => 'info',
                    'rejected' => 'danger',
                    'completed' => 'success',
                    'cancelled' => 'dark'
                ];
                $color = $statusColors[$disposal->status] ?? 'secondary';
                $statusLabel = ucfirst(str_replace('_', ' ', $disposal->status));
                return '<span class="badge bg-' . $color . '">' . $statusLabel . '</span>';
            })
            ->addColumn('id_hashed', function($disposal) {
                return Hashids::encode($disposal->id);
            })
            ->addColumn('actions', function($disposal) {
                $encodedId = Hashids::encode($disposal->id);
                $actions = '<div class="btn-group btn-group-sm">';
                $actions .= '<a href="' . route('assets.disposals.show', $encodedId) . '" class="btn btn-outline-primary" title="View"><i class="bx bx-show"></i></a>';
                if ($disposal->status == 'draft') {
                    $actions .= '<a href="' . route('assets.disposals.edit', $encodedId) . '" class="btn btn-outline-info" title="Edit"><i class="bx bx-edit"></i></a>';
                }
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status', 'gain_loss_display', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new disposal
     */
    public function create(Request $request)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('create asset disposals');

        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $assetId = $request->asset_id;
        $asset = null;

        if ($assetId) {
            $asset = Asset::with(['category', 'depreciations'])
                ->where('company_id', $user->company_id)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->where('status', 'active')
                ->findOrFail($assetId);
        }

        $assets = Asset::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $reasonCodes = DisposalReasonCode::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Load customers for sale disposals
        $customers = \App\Models\Customer::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Get chart accounts for disposal
        $companyId = Auth::user()->company_id;
        
        // Get accounts for disposal
        $disposalAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->orderBy('account_name')
            ->get();

        // Get bank accounts for payment selection
        $bankAccounts = \App\Models\BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->with('chartAccount')
            ->orderBy('name')
            ->get();

        return view('assets.disposals.create', compact('assets', 'asset', 'reasonCodes', 'disposalAccounts', 'customers', 'bankAccounts'));
    }

    /**
     * Store a newly created disposal
     */
    public function store(Request $request)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('create asset disposals');

        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'disposal_type' => 'required|in:sale,scrap,write_off,donation,loss',
            'customer_id' => 'nullable|exists:customers,id',
            'disposal_reason_code_id' => 'nullable|exists:disposal_reason_codes,id',
            'disposal_reason' => 'required|string',
            'proposed_disposal_date' => 'required|date',
            'disposal_proceeds' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'fair_value' => 'nullable|numeric|min:0',
            'buyer_name' => 'nullable|string|max:255',
            'buyer_contact' => 'nullable|string|max:255',
            'buyer_address' => 'nullable|string',
            'invoice_number' => 'nullable|string|max:255',
            'receipt_number' => 'nullable|string|max:255',
            'vat_type' => 'nullable|in:no_vat,exclusive,inclusive',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'vat_amount' => 'nullable|numeric|min:0',
            'withholding_tax_enabled' => 'nullable|boolean',
            'withholding_tax_rate' => 'nullable|numeric|min:0|max:100',
            'withholding_tax_type' => 'nullable|in:percentage,fixed',
            'withholding_tax' => 'nullable|numeric|min:0',
            'insurance_recovery_amount' => 'nullable|numeric|min:0',
            'insurance_claim_number' => 'nullable|string|max:255',
            'is_partial_disposal' => 'nullable|boolean',
            'partial_disposal_percentage' => 'nullable|numeric|min:0|max:100',
            'partial_disposal_description' => 'nullable|string',
            'valuation_report' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120',
            'notes' => 'nullable|string',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);

        // Check if asset belongs to user's company
        if ($asset->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to asset');
        }

        // Validate: if amount_paid > 0, bank_account_id is required
        if (($validated['amount_paid'] ?? 0) > 0 && empty($validated['bank_account_id'])) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['bank_account_id' => 'Bank account is required when amount paid is greater than 0.']);
        }

        // Validate: amount_paid cannot exceed disposal_proceeds
        if (($validated['disposal_type'] ?? '') === 'sale' && 
            ($validated['amount_paid'] ?? 0) > 0 && 
            ($validated['disposal_proceeds'] ?? 0) > 0 &&
            ($validated['amount_paid'] ?? 0) > ($validated['disposal_proceeds'] ?? 0)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['amount_paid' => 'Amount paid cannot exceed disposal proceeds.']);
        }

        // Generate disposal number
        $disposalNumber = 'DSP-' . date('Y') . '-' . str_pad(
            AssetDisposal::whereYear('created_at', now()->year)->count() + 1,
            5,
            '0',
            STR_PAD_LEFT
        );

        DB::beginTransaction();
        try {
            // Calculate NBV
            $nbvData = $this->disposalService->calculateNBV($asset, $validated['proposed_disposal_date']);

            // Handle file uploads
            $valuationReportPath = null;
            if ($request->hasFile('valuation_report')) {
                $file = $request->file('valuation_report');
                $valuationReportPath = $file->store('disposals/reports', 'public');
            }

            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $attachments[] = $file->store('disposals/attachments', 'public');
                }
            }

            $disposal = AssetDisposal::create([
                'company_id' => Auth::user()->company_id,
                'branch_id' => session('branch_id') ?? $asset->branch_id,
                'asset_id' => $asset->id,
                'customer_id' => $validated['customer_id'] ?? null,
                'disposal_number' => $disposalNumber,
                'disposal_type' => $validated['disposal_type'],
                'disposal_reason_code_id' => $validated['disposal_reason_code_id'] ?? null,
                'disposal_reason' => $validated['disposal_reason'],
                'proposed_disposal_date' => $validated['proposed_disposal_date'],
                'asset_cost' => $nbvData['asset_cost'],
                'accumulated_depreciation' => $nbvData['accumulated_depreciation'],
                'accumulated_impairment' => $nbvData['accumulated_impairment'],
                'net_book_value' => $nbvData['net_book_value'],
                'disposal_proceeds' => $validated['disposal_proceeds'] ?? null,
                'amount_paid' => $validated['amount_paid'] ?? 0,
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'fair_value' => $validated['fair_value'] ?? null,
                'buyer_name' => $validated['buyer_name'] ?? null,
                'buyer_contact' => $validated['buyer_contact'] ?? null,
                'buyer_address' => $validated['buyer_address'] ?? null,
                'invoice_number' => $validated['invoice_number'] ?? null,
                'receipt_number' => $validated['receipt_number'] ?? null,
                'vat_type' => $validated['vat_type'] ?? 'no_vat',
                'vat_rate' => $validated['vat_rate'] ?? 0,
                'vat_amount' => $validated['vat_amount'] ?? 0,
                'withholding_tax_enabled' => $validated['withholding_tax_enabled'] ?? false,
                'withholding_tax_rate' => $validated['withholding_tax_rate'] ?? 0,
                'withholding_tax_type' => $validated['withholding_tax_type'] ?? 'percentage',
                'withholding_tax' => $validated['withholding_tax'] ?? 0,
                'insurance_recovery_amount' => $validated['insurance_recovery_amount'] ?? 0,
                'insurance_claim_number' => $validated['insurance_claim_number'] ?? null,
                'is_partial_disposal' => $validated['is_partial_disposal'] ?? false,
                'partial_disposal_percentage' => $validated['partial_disposal_percentage'] ?? null,
                'partial_disposal_description' => $validated['partial_disposal_description'] ?? null,
                'valuation_report_path' => $valuationReportPath,
                'attachments' => $attachments,
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft',
                'initiated_by' => Auth::id(),
                'initiated_at' => now(),
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('assets.disposals.show', Hashids::encode($disposal->id))
                ->with('success', 'Disposal request created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Disposal creation error: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create disposal: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified disposal
     */
    public function show($encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('view asset disposals');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $disposal = AssetDisposal::with([
            'asset.category',
            'asset.depreciations',
            'company',
            'branch',
            'reasonCode',
            'journal.items.chartAccount',
            'approvals.approver',
            'initiatedBy',
            'submittedBy',
            'approvedBy'
        ])->findOrFail($id);

        // Check authorization
        if ($disposal->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        // Get approval service data
        $user = Auth::user();
        $approvalService = app(\App\Services\ApprovalService::class);
        $canSubmit = $approvalService->canUserSubmit($disposal, $user->id);
        $canApprove = $approvalService->canUserApprove($disposal, $user->id);
        $currentApprovers = $approvalService->getCurrentApprovers($disposal);
        $currentLevel = $approvalService->getCurrentApprovalLevel($disposal);
        $approvalHistory = \App\Models\ApprovalHistory::where('approvable_type', get_class($disposal))
            ->where('approvable_id', $disposal->id)
            ->with(['approvalLevel', 'approver'])
            ->orderBy('created_at')
            ->get();

        // Get all GL transactions related to this disposal
        $glTransactions = collect();
        
        // Get GL transactions from journal (disposal posting)
        if ($disposal->journal_id) {
            $journalGlTransactions = \App\Models\GlTransaction::where('transaction_type', 'journal')
                ->where('transaction_id', $disposal->journal_id)
                ->with('chartAccount')
                ->get();
            $glTransactions = $glTransactions->merge($journalGlTransactions);
        }
        
        // Get GL transactions from receipts (receivable payments)
        $receipts = \App\Models\Receipt::where('reference_type', 'asset_disposal')
            ->where('reference', $disposal->id)
            ->pluck('id');
        
        if ($receipts->count() > 0) {
            $receiptGlTransactions = \App\Models\GlTransaction::where('transaction_type', 'receipt')
                ->whereIn('transaction_id', $receipts)
                ->with('chartAccount')
                ->get();
            $glTransactions = $glTransactions->merge($receiptGlTransactions);
        }
        
        // Sort by date and created_at
        $glTransactions = $glTransactions->sortBy([
            ['date', 'asc'],
            ['created_at', 'asc']
        ])->values();

        return view('assets.disposals.show', compact(
            'disposal',
            'glTransactions',
            'canSubmit',
            'canApprove',
            'currentApprovers',
            'currentLevel',
            'approvalHistory'
        ));
    }

    /**
     * Show the form for editing the specified disposal
     */
    public function edit($encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('edit asset disposals');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $disposal = AssetDisposal::with(['asset'])->findOrFail($id);

        if ($disposal->status !== 'draft') {
            return redirect()->route('assets.disposals.show', $encodedId)
                ->with('error', 'Only draft disposals can be edited');
        }

        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $assets = Asset::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $reasonCodes = DisposalReasonCode::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Load customers for sale disposals
        $customers = \App\Models\Customer::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $disposalAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($user) {
                $q->where('company_id', $user->company_id);
            })
            ->orderBy('account_name')
            ->get();

        return view('assets.disposals.edit', compact('disposal', 'assets', 'reasonCodes', 'disposalAccounts', 'customers'));
    }

    /**
     * Update the specified disposal
     */
    public function update(Request $request, $encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('edit asset disposals');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $disposal = AssetDisposal::findOrFail($id);

        if ($disposal->status !== 'draft') {
            return redirect()->route('assets.disposals.show', $encodedId)
                ->with('error', 'Only draft disposals can be edited');
        }

        $validated = $request->validate([
            'disposal_type' => 'required|in:sale,scrap,write_off,donation,loss',
            'disposal_reason_code_id' => 'nullable|exists:disposal_reason_codes,id',
            'disposal_reason' => 'required|string',
            'proposed_disposal_date' => 'required|date',
            'disposal_proceeds' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'fair_value' => 'nullable|numeric|min:0',
            'buyer_name' => 'nullable|string|max:255',
            'buyer_contact' => 'nullable|string|max:255',
            'buyer_address' => 'nullable|string',
            'invoice_number' => 'nullable|string|max:255',
            'receipt_number' => 'nullable|string|max:255',
            'vat_type' => 'nullable|in:no_vat,exclusive,inclusive',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'vat_amount' => 'nullable|numeric|min:0',
            'withholding_tax_enabled' => 'nullable|boolean',
            'withholding_tax_rate' => 'nullable|numeric|min:0|max:100',
            'withholding_tax_type' => 'nullable|in:percentage,fixed',
            'withholding_tax' => 'nullable|numeric|min:0',
            'insurance_recovery_amount' => 'nullable|numeric|min:0',
            'insurance_claim_number' => 'nullable|string|max:255',
            'is_partial_disposal' => 'nullable|boolean',
            'partial_disposal_percentage' => 'nullable|numeric|min:0|max:100',
            'partial_disposal_description' => 'nullable|string',
            'valuation_report' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Recalculate NBV if date changed
            if ($disposal->proposed_disposal_date != $validated['proposed_disposal_date']) {
                $nbvData = $this->disposalService->calculateNBV($disposal->asset, $validated['proposed_disposal_date']);
                $disposal->asset_cost = $nbvData['asset_cost'];
                $disposal->accumulated_depreciation = $nbvData['accumulated_depreciation'];
                $disposal->accumulated_impairment = $nbvData['accumulated_impairment'];
                $disposal->net_book_value = $nbvData['net_book_value'];
            }

            // Handle file uploads
            if ($request->hasFile('valuation_report')) {
                $file = $request->file('valuation_report');
                $disposal->valuation_report_path = $file->store('disposals/reports', 'public');
            }

            if ($request->hasFile('attachments')) {
                $attachments = $disposal->attachments ?? [];
                foreach ($request->file('attachments') as $file) {
                    $attachments[] = $file->store('disposals/attachments', 'public');
                }
                $disposal->attachments = $attachments;
            }

            $disposal->update([
                'disposal_type' => $validated['disposal_type'],
                'customer_id' => $validated['customer_id'] ?? null,
                'disposal_reason_code_id' => $validated['disposal_reason_code_id'] ?? null,
                'disposal_reason' => $validated['disposal_reason'],
                'proposed_disposal_date' => $validated['proposed_disposal_date'],
                'disposal_proceeds' => $validated['disposal_proceeds'] ?? null,
                'fair_value' => $validated['fair_value'] ?? null,
                'buyer_name' => $validated['buyer_name'] ?? null,
                'buyer_contact' => $validated['buyer_contact'] ?? null,
                'buyer_address' => $validated['buyer_address'] ?? null,
                'invoice_number' => $validated['invoice_number'] ?? null,
                'receipt_number' => $validated['receipt_number'] ?? null,
                'vat_type' => $validated['vat_type'] ?? 'no_vat',
                'vat_rate' => $validated['vat_rate'] ?? 0,
                'vat_amount' => $validated['vat_amount'] ?? 0,
                'withholding_tax_enabled' => $validated['withholding_tax_enabled'] ?? false,
                'withholding_tax_rate' => $validated['withholding_tax_rate'] ?? 0,
                'withholding_tax_type' => $validated['withholding_tax_type'] ?? 'percentage',
                'withholding_tax' => $validated['withholding_tax'] ?? 0,
                'insurance_recovery_amount' => $validated['insurance_recovery_amount'] ?? 0,
                'insurance_claim_number' => $validated['insurance_claim_number'] ?? null,
                'is_partial_disposal' => $validated['is_partial_disposal'] ?? false,
                'partial_disposal_percentage' => $validated['partial_disposal_percentage'] ?? null,
                'partial_disposal_description' => $validated['partial_disposal_description'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            // Recalculate gain/loss
            $disposal->gain_loss = $this->disposalService->calculateGainLoss($disposal);
            $disposal->save();

            DB::commit();

            return redirect()->route('assets.disposals.show', $encodedId)
                ->with('success', 'Disposal updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Disposal update error: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update disposal: ' . $e->getMessage());
        }
    }

    /**
     * Submit disposal for approval
     */
    public function submitForApproval($encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('submit asset disposals');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $disposal = AssetDisposal::findOrFail($id);

        if ($disposal->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to disposal');
        }

        if ($disposal->status !== 'draft') {
            return redirect()->back()->with('error', 'Only draft disposals can be submitted for approval');
        }

        try {
            $approvalService = app(\App\Services\ApprovalService::class);
            $approvalService->submitForApproval($disposal, Auth::id());

            return redirect()->back()->with('success', 'Disposal submitted for approval');
        } catch (\Exception $e) {
            Log::error('Disposal submission error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to submit for approval: ' . $e->getMessage());
        }
    }

    /**
     * Approve disposal
     */
    public function approve(Request $request, $encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('approve asset disposals');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $disposal = AssetDisposal::findOrFail($id);

        if ($disposal->status !== 'pending_approval') {
            return redirect()->back()->with('error', 'Disposal cannot be approved in current status');
        }

        $validated = $request->validate([
            'approval_notes' => 'nullable|string',
        ]);

        try {
            $approvalService = app(\App\Services\ApprovalService::class);
            
            // Get current approval level
            $currentLevel = $approvalService->getCurrentApprovalLevel($disposal);
            if (!$currentLevel) {
                return redirect()->back()->with('error', 'No approval level found');
            }
            
            // Approve using ApprovalService
            $approvalService->approve($disposal, $currentLevel->id, Auth::id(), $validated['approval_notes'] ?? null);
            
            // Refresh model to get updated status
            $disposal->refresh();

            return redirect()->back()->with('success', 'Disposal approved successfully');

        } catch (\Exception $e) {
            Log::error('Disposal approval error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to approve disposal: ' . $e->getMessage());
        }
    }

    /**
     * Reject disposal
     */
    public function reject(Request $request, $encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('approve asset disposals');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $disposal = AssetDisposal::findOrFail($id);

        if ($disposal->status !== 'pending_approval') {
            return redirect()->back()->with('error', 'Only pending disposals can be rejected');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        try {
            $approvalService = app(\App\Services\ApprovalService::class);
            
            // Get current approval level
            $currentLevel = $approvalService->getCurrentApprovalLevel($disposal);
            if (!$currentLevel) {
                return redirect()->back()->with('error', 'No approval level found');
            }
            
            // Reject using ApprovalService
            $approvalService->reject($disposal, $currentLevel->id, Auth::id(), $validated['rejection_reason']);

            return redirect()->back()->with('success', 'Disposal rejected');

        } catch (\Exception $e) {
            Log::error('Disposal rejection error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to reject disposal: ' . $e->getMessage());
        }
    }

    /**
     * Post disposal to General Ledger
     */
    public function postToGL($encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('post asset disposals');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $disposal = AssetDisposal::findOrFail($id);

        if (!$disposal->canBePosted()) {
            return redirect()->back()->with('error', 'Disposal cannot be posted in current status');
        }

        try {
            $result = $this->disposalService->processDisposal($disposal);

            if (!$result['success']) {
                return redirect()->back()->with('error', $result['message']);
            }
            
            // Log activity
            $disposal->asset->logActivity('post', "Posted Asset Disposal to GL - {$disposal->asset->code}", [
                'disposal_id' => $disposal->id,
                'proceeds' => number_format($disposal->proceeds ?? 0, 2)
            ]);

            return redirect()->back()->with('success', 'Disposal posted to General Ledger successfully');

        } catch (\Exception $e) {
            Log::error('GL posting error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Failed to post to GL: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified disposal
     */
    public function destroy($encodedId)
    {
        // TODO: Add proper authorization policy
        // $this->authorize('delete asset disposals');

        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $disposal = AssetDisposal::findOrFail($id);

        if ($disposal->status !== 'draft') {
            return redirect()->back()->with('error', 'Only draft disposals can be deleted');
        }

        if ($disposal->gl_posted) {
            return redirect()->back()->with('error', 'Posted disposals cannot be deleted');
        }

        $disposal->delete();

        return redirect()->route('assets.disposals.index')
            ->with('success', 'Disposal deleted successfully');
    }

    /**
     * Record remaining receivable payment
     */
    public function recordReceivable(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $disposal = AssetDisposal::with(['asset', 'customer'])->findOrFail($id);

        // Check authorization
        if ($disposal->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        // Validate that disposal is posted and has remaining receivable
        if (!$disposal->gl_posted) {
            return redirect()->back()->with('error', 'Disposal must be posted to GL before recording receivable');
        }

        if ($disposal->disposal_type !== 'sale' || $disposal->disposal_proceeds <= 0) {
            return redirect()->back()->with('error', 'Only sale disposals with proceeds can have receivables');
        }

        $netProceeds = $disposal->disposal_proceeds - ($disposal->vat_amount ?? 0);
        $remainingReceivable = $netProceeds - ($disposal->amount_paid ?? 0);

        if ($remainingReceivable <= 0) {
            return redirect()->back()->with('error', 'No remaining receivable to record');
        }

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:0.01|max:' . $remainingReceivable,
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $bankAccount = \App\Models\BankAccount::findOrFail($validated['bank_account_id']);
            $amount = (float) $validated['amount'];
            $description = $validated['description'] ?? "Receipt for remaining balance on disposal {$disposal->disposal_number}";

            // Get receivable account
            $receivableAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_receivable_account')->value('value') ?? 0);
            if (!$receivableAccountId && $disposal->customer_id) {
                $customer = $disposal->customer;
                if ($customer && isset($customer->receivable_account_id) && $customer->receivable_account_id) {
                    $receivableAccountId = $customer->receivable_account_id;
                }
            }

            if (!$receivableAccountId) {
                throw new \Exception('Receivable account not configured. Please set default receivable account in system settings.');
            }

            // Create receipt record
            $receipt = \App\Models\Receipt::create([
                'reference' => $disposal->id,
                'reference_type' => 'asset_disposal',
                'reference_number' => $disposal->disposal_number,
                'amount' => $amount,
                'currency' => $disposal->company->functional_currency ?? 'TZS',
                'exchange_rate' => 1.000000,
                'date' => $validated['payment_date'],
                'description' => $description,
                'user_id' => $user->id,
                'bank_account_id' => $validated['bank_account_id'],
                'payee_type' => $disposal->customer_id ? 'customer' : 'other',
                'payee_id' => $disposal->customer_id,
                'payee_name' => $disposal->customer ? $disposal->customer->name : ($disposal->buyer_name ?? 'Asset Disposal Buyer'),
                'branch_id' => $disposal->branch_id,
                'approved' => true,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            // Create receipt item
            \App\Models\ReceiptItem::create([
                'receipt_id' => $receipt->id,
                'chart_account_id' => $receivableAccountId,
                'amount' => $amount,
                'base_amount' => $amount, // Base amount (no VAT for receivable payment)
                'description' => $description,
            ]);

            // Create GL transactions for the receipt
            // Dr. Bank Account
            \App\Models\GlTransaction::create([
                'chart_account_id' => $bankAccount->chart_account_id,
                'customer_id' => $disposal->customer_id,
                'amount' => $amount,
                'nature' => 'debit',
                'transaction_id' => $receipt->id,
                'transaction_type' => 'receipt',
                'date' => $validated['payment_date'],
                'description' => $description,
                'branch_id' => $disposal->branch_id,
                'user_id' => $user->id,
            ]);

            // Cr. Receivable Account (clearing the receivable)
            \App\Models\GlTransaction::create([
                'chart_account_id' => $receivableAccountId,
                'customer_id' => $disposal->customer_id,
                'amount' => $amount,
                'nature' => 'credit',
                'transaction_id' => $receipt->id,
                'transaction_type' => 'receipt',
                'date' => $validated['payment_date'],
                'description' => $description,
                'branch_id' => $disposal->branch_id,
                'user_id' => $user->id,
            ]);

            // Verify GL transactions were created
            $glTransactionsCount = \App\Models\GlTransaction::where('transaction_type', 'receipt')
                ->where('transaction_id', $receipt->id)
                ->count();
            
            if ($glTransactionsCount < 2) {
                throw new \Exception('Failed to create GL transactions for receipt');
            }

            // Refresh receipt to ensure relationships are loaded
            $receipt->refresh();

            // Update disposal amount_paid
            $disposal->amount_paid = ($disposal->amount_paid ?? 0) + $amount;
            $disposal->save();

            DB::commit();

            return redirect()->back()->with('success', 'Receivable recorded successfully. Receipt #' . $receipt->id . ' created with GL transactions.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error recording disposal receivable: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to record receivable: ' . $e->getMessage())
                ->withInput();
        }
    }
}
