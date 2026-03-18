<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Fleet\FleetTyre;
use App\Models\Fleet\FleetTyreInstallation;
use App\Models\Fleet\FleetTyrePosition;
use App\Models\Fleet\FleetTyreReplacementRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FleetTyreReplacementRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $query = FleetTyreReplacementRequest::with(['vehicle', 'tyrePosition', 'currentTyre'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $requests = $query->paginate(15)->withQueryString();
        return view('fleet.tyre-replacement-requests.index', compact('requests'));
    }

    public function create()
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $vehicles = Asset::where('company_id', $companyId)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number']);

        $positions = FleetTyrePosition::where('company_id', $companyId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('position_name')
            ->get(['id', 'position_code', 'position_name']);

        return view('fleet.tyre-replacement-requests.create', compact('vehicles', 'positions'));
    }

    public function installationDetails(Request $request)
    {
        $user = Auth::user();
        $vehicleId = $request->input('vehicle_id');
        $positionId = $request->input('tyre_position_id');
        if (!$vehicleId || !$positionId) {
            return response()->json(['installation' => null]);
        }
        $installation = FleetTyreInstallation::with(['tyre', 'vehicle', 'tyrePosition'])
            ->where('company_id', $user->company_id)
            ->where('vehicle_id', $vehicleId)
            ->where('tyre_position_id', $positionId)
            ->latest('installed_at')
            ->first();
        if (!$installation) {
            return response()->json(['installation' => null]);
        }
        return response()->json([
            'installation' => [
                'id' => $installation->id,
                'tyre_serial' => $installation->tyre?->tyre_serial,
                'tyre_brand' => $installation->tyre?->brand,
                'tyre_expected_lifespan_km' => $installation->tyre?->expected_lifespan_km,
                'installed_at' => $installation->installed_at?->format('d/m/Y'),
                'odometer_at_install' => $installation->odometer_at_install ? (float) $installation->odometer_at_install : null,
                'installer_name' => $installation->installer_name,
                'installer_type' => $installation->installer_type,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:assets,id',
            'tyre_position_id' => 'required|exists:fleet_tyre_positions,id',
            'current_tyre_id' => 'nullable|exists:fleet_tyres,id',
            'reason' => 'required|in:worn_out,burst,side_cut,other',
            'mileage_at_request' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);
        $validated['company_id'] = $user->company_id;
        $validated['branch_id'] = session('branch_id') ?? $user->branch_id;
        $validated['requested_by'] = $user->id;
        $validated['status'] = FleetTyreReplacementRequest::STATUS_PENDING;

        $installation = FleetTyreInstallation::where('vehicle_id', $validated['vehicle_id'])
            ->where('tyre_position_id', $validated['tyre_position_id'])
            ->latest('installed_at')
            ->first();
        if ($installation) {
            $validated['current_installation_id'] = $installation->id;
            if (empty($validated['current_tyre_id'])) {
                $validated['current_tyre_id'] = $installation->tyre_id;
            }
        }

        $req = FleetTyreReplacementRequest::create($validated);
        return redirect()->route('fleet.tyre-replacement-requests.show', $req)->with('success', 'Replacement request submitted.');
    }

    public function show(FleetTyreReplacementRequest $tyreReplacementRequest)
    {
        $this->authorizeCompany($tyreReplacementRequest);
        $tyreReplacementRequest->load(['vehicle', 'tyrePosition', 'currentTyre', 'currentInstallation', 'requestedBy', 'approvedBy']);
        return view('fleet.tyre-replacement-requests.show', compact('tyreReplacementRequest'));
    }

    public function approve(Request $request, FleetTyreReplacementRequest $tyreReplacementRequest)
    {
        $this->authorizeCompany($tyreReplacementRequest);
        if ($tyreReplacementRequest->status !== FleetTyreReplacementRequest::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Request is no longer pending.');
        }
        $tyreReplacementRequest->update([
            'status' => FleetTyreReplacementRequest::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Request approved.');
    }

    public function reject(Request $request, FleetTyreReplacementRequest $tyreReplacementRequest)
    {
        $this->authorizeCompany($tyreReplacementRequest);
        if ($tyreReplacementRequest->status !== FleetTyreReplacementRequest::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Request is no longer pending.');
        }
        $validated = $request->validate(['rejection_reason' => 'nullable|string|max:1000']);
        $tyreReplacementRequest->update([
            'status' => FleetTyreReplacementRequest::STATUS_REJECTED,
            'rejection_reason' => $validated['rejection_reason'] ?? null,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Request rejected.');
    }

    private function authorizeCompany(FleetTyreReplacementRequest $tyreReplacementRequest): void
    {
        if ($tyreReplacementRequest->company_id !== Auth::user()->company_id) {
            abort(403);
        }
    }
}
