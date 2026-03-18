<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Assets\MaintenanceRequest;
use App\Models\Assets\MaintenanceType;
use App\Models\Assets\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class MaintenanceRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    /**
     * Display a listing of maintenance requests
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $user = Auth::user();
            $companyId = $user->company_id;
            $branchId = session('branch_id') ?? $user->branch_id;

            $requests = MaintenanceRequest::forCompany($companyId)
                ->when($branchId, fn($q) => $q->forBranch($branchId))
                ->with(['asset', 'maintenanceType', 'requestedBy', 'workOrder'])
                ->latest();

            return DataTables::of($requests)
                ->editColumn('requested_date', function ($request) {
                    return $request->requested_date
                        ? Carbon::parse($request->requested_date)->timezone(config('app.timezone'))->format('M d, Y')
                        : '';
                })
                ->addColumn('asset_name', function ($request) {
                    return $request->asset ? $request->asset->name : 'N/A';
                })
                ->addColumn('maintenance_type_name', function ($request) {
                    return $request->maintenanceType ? $request->maintenanceType->name : 'N/A';
                })
                ->addColumn('requested_by_name', function ($request) {
                    return $request->requestedBy ? $request->requestedBy->name : 'N/A';
                })
                ->addColumn('status_badge', function ($request) {
                    $badges = [
                        'pending' => '<span class="badge bg-warning">Pending</span>',
                        'approved' => '<span class="badge bg-success">Approved</span>',
                        'rejected' => '<span class="badge bg-danger">Rejected</span>',
                        'converted_to_wo' => '<span class="badge bg-info">Converted to WO</span>',
                        'cancelled' => '<span class="badge bg-secondary">Cancelled</span>',
                    ];
                    return $badges[$request->status] ?? '<span class="badge bg-secondary">' . ucfirst($request->status) . '</span>';
                })
                ->addColumn('actions', function ($request) {
                    $actions = '';
                    $encodedId = Hashids::encode($request->id);
                    
                    if (auth()->user()->can('view maintenance requests')) {
                        $actions .= '<a href="' . route('assets.maintenance.requests.show', $encodedId) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    }
                    
                    if ($request->status === 'pending' && auth()->user()->can('approve maintenance requests')) {
                        $actions .= '<button class="btn btn-sm btn-outline-success me-1 approve-request" data-id="' . $encodedId . '"><i class="bx bx-check"></i></button>';
                        $actions .= '<button class="btn btn-sm btn-outline-danger me-1 reject-request" data-id="' . $encodedId . '"><i class="bx bx-x"></i></button>';
                    }
                    
                    if ($request->status === 'approved' && auth()->user()->can('create work orders')) {
                        $actions .= '<a href="' . route('assets.maintenance.work-orders.create', ['request_id' => $encodedId]) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-wrench"></i> Create WO</a>';
                    }
                    
                    if (auth()->user()->can('edit maintenance requests') && $request->status === 'pending') {
                        $actions .= '<a href="' . route('assets.maintenance.requests.edit', $encodedId) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    }
                    
                    return $actions;
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        return view('assets.maintenance.requests.index');
    }

    /**
     * Show the form for creating a new maintenance request
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $assets = Asset::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->where('status', '!=', 'disposed')
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $maintenanceTypes = MaintenanceType::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->active()
            ->orderBy('name')
            ->get();

        $selectedAssetId = $request->get('asset_id');
        $defaultRequestedDate = Carbon::now(config('app.timezone'))->format('Y-m-d');

        return view('assets.maintenance.requests.create', compact('assets', 'maintenanceTypes', 'selectedAssetId', 'defaultRequestedDate'));
    }

    /**
     * Store a newly created maintenance request
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'maintenance_type_id' => 'required|exists:maintenance_types,id',
            'trigger_type' => 'required|in:preventive,corrective,planned_improvement',
            'priority' => 'required|in:low,medium,high,urgent',
            'description' => 'required|string',
            'issue_details' => 'nullable|string',
            'requested_date' => 'required|date',
            'preferred_start_date' => 'nullable|date|after_or_equal:requested_date',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120',
            'notes' => 'nullable|string',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);

        // Generate request number
        $requestNumber = 'MR-' . date('Y') . '-' . str_pad(
            MaintenanceRequest::whereYear('created_at', now()->year)->count() + 1,
            5,
            '0',
            STR_PAD_LEFT
        );

        DB::beginTransaction();
        try {
            $maintenanceRequest = MaintenanceRequest::create([
                'company_id' => $user->company_id,
                'branch_id' => $asset->branch_id,
                'request_number' => $requestNumber,
                'asset_id' => $validated['asset_id'],
                'maintenance_type_id' => $validated['maintenance_type_id'],
                'trigger_type' => $validated['trigger_type'],
                'priority' => $validated['priority'],
                'description' => $validated['description'],
                'issue_details' => $validated['issue_details'] ?? null,
                'requested_date' => $validated['requested_date'],
                'preferred_start_date' => $validated['preferred_start_date'] ?? null,
                'requested_by' => $user->id,
                'custodian_user_id' => $asset->custodian_user_id,
                'department_id' => $asset->department_id,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            // Handle file uploads
            if ($request->hasFile('attachments')) {
                $attachments = [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('maintenance-requests', 'public');
                    $attachments[] = $path;
                }
                $maintenanceRequest->update(['attachments' => $attachments]);
            }

            DB::commit();

            return redirect()->route('assets.maintenance.requests.index')
                ->with('success', 'Maintenance request created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating maintenance request: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create maintenance request: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified maintenance request
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $maintenanceRequest = MaintenanceRequest::with([
            'asset',
            'maintenanceType',
            'requestedBy',
            'custodian',
            'department',
            'supervisorApprovedBy',
            'workOrder'
        ])->findOrFail($id);

        return view('assets.maintenance.requests.show', compact('maintenanceRequest', 'encodedId'));
    }

    /**
     * Show the form for editing the specified maintenance request
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $maintenanceRequest = MaintenanceRequest::findOrFail($id);
        
        if ($maintenanceRequest->status !== 'pending') {
            return redirect()->route('assets.maintenance.requests.show', $encodedId)
                ->with('error', 'Only pending requests can be edited.');
        }

        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $assets = Asset::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $maintenanceTypes = MaintenanceType::forCompany($companyId)
            ->when($branchId, fn($q) => $q->forBranch($branchId))
            ->active()
            ->orderBy('name')
            ->get();

        return view('assets.maintenance.requests.edit', compact('maintenanceRequest', 'assets', 'maintenanceTypes', 'encodedId'));
    }

    /**
     * Update the specified maintenance request
     */
    public function update(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $maintenanceRequest = MaintenanceRequest::findOrFail($id);
        
        if ($maintenanceRequest->status !== 'pending') {
            return redirect()->route('assets.maintenance.requests.show', $encodedId)
                ->with('error', 'Only pending requests can be edited.');
        }

        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'maintenance_type_id' => 'required|exists:maintenance_types,id',
            'trigger_type' => 'required|in:preventive,corrective,planned_improvement',
            'priority' => 'required|in:low,medium,high,urgent',
            'description' => 'required|string',
            'issue_details' => 'nullable|string',
            'requested_date' => 'required|date',
            'preferred_start_date' => 'nullable|date|after_or_equal:requested_date',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $maintenanceRequest->update($validated);
            $maintenanceRequest->update(['updated_by' => Auth::id()]);

            // Handle file uploads
            if ($request->hasFile('attachments')) {
                $existingAttachments = $maintenanceRequest->attachments ?? [];
                $newAttachments = [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('maintenance-requests', 'public');
                    $newAttachments[] = $path;
                }
                $maintenanceRequest->update(['attachments' => array_merge($existingAttachments, $newAttachments)]);
            }

            DB::commit();

            return redirect()->route('assets.maintenance.requests.show', $encodedId)
                ->with('success', 'Maintenance request updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating maintenance request: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update maintenance request: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Approve maintenance request
     */
    public function approve(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid request ID'], 404);
        }

        $maintenanceRequest = MaintenanceRequest::findOrFail($id);
        
        if ($maintenanceRequest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Request is not pending'], 400);
        }

        $validated = $request->validate([
            'supervisor_notes' => 'nullable|string',
        ]);

        $maintenanceRequest->update([
            'status' => 'approved',
            'supervisor_approved_by' => Auth::id(),
            'supervisor_approved_at' => now(),
            'supervisor_notes' => $validated['supervisor_notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance request approved successfully.'
        ]);
    }

    /**
     * Reject maintenance request
     */
    public function reject(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid request ID'], 404);
        }

        $maintenanceRequest = MaintenanceRequest::findOrFail($id);
        
        if ($maintenanceRequest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Request is not pending'], 400);
        }

        $validated = $request->validate([
            'supervisor_notes' => 'required|string',
        ]);

        $maintenanceRequest->update([
            'status' => 'rejected',
            'supervisor_approved_by' => Auth::id(),
            'supervisor_approved_at' => now(),
            'supervisor_notes' => $validated['supervisor_notes'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance request rejected.'
        ]);
    }

    /**
     * Remove the specified maintenance request
     */
    public function destroy($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $maintenanceRequest = MaintenanceRequest::findOrFail($id);
        
        if ($maintenanceRequest->status !== 'pending') {
            return redirect()->route('assets.maintenance.requests.index')
                ->with('error', 'Only pending requests can be deleted.');
        }

        $maintenanceRequest->delete();

        return redirect()->route('assets.maintenance.requests.index')
            ->with('success', 'Maintenance request deleted successfully.');
    }
}
