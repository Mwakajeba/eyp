<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Fleet\FleetTyre;
use App\Models\Fleet\FleetTyreInstallation;
use App\Models\Fleet\FleetTyrePosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FleetTyreInstallationController extends Controller
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

        $query = FleetTyreInstallation::with(['tyre', 'vehicle', 'tyrePosition'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('installed_at');

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        $installations = $query->paginate(15)->withQueryString();
        return view('fleet.tyre-installations.index', compact('installations'));
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

        $tyres = FleetTyre::where('company_id', $companyId)
            ->whereIn('status', ['new', 'removed'])
            ->orderBy('tyre_serial')
            ->get(['id', 'tyre_serial', 'brand', 'status']);

        $positions = FleetTyrePosition::where('company_id', $companyId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('position_name')
            ->get(['id', 'position_code', 'position_name']);

        return view('fleet.tyre-installations.create', compact('vehicles', 'tyres', 'positions'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'tyre_id' => 'required|exists:fleet_tyres,id',
            'vehicle_id' => 'required|exists:assets,id',
            'tyre_position_id' => 'required|exists:fleet_tyre_positions,id',
            'installed_at' => 'required|date',
            'odometer_at_install' => 'nullable|numeric|min:0',
            'installer_type' => 'nullable|string|max:50',
            'installer_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);
        $validated['company_id'] = $user->company_id;
        $validated['branch_id'] = session('branch_id') ?? $user->branch_id;
        $validated['created_by'] = $user->id;

        $tyre = FleetTyre::findOrFail($validated['tyre_id']);
        if ($tyre->company_id !== $user->company_id) {
            abort(403);
        }

        $installation = FleetTyreInstallation::create($validated);
        $tyre->update(['status' => FleetTyre::STATUS_IN_USE]);

        return redirect()->route('fleet.tyre-installations.show', $installation)->with('success', 'Tyre installed successfully.');
    }

    public function show(FleetTyreInstallation $installation)
    {
        $this->authorizeCompany($installation);
        $installation->load(['tyre', 'vehicle', 'tyrePosition']);
        return view('fleet.tyre-installations.show', compact('installation'));
    }

    public function edit(FleetTyreInstallation $installation)
    {
        $this->authorizeCompany($installation);
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $vehicles = Asset::where('company_id', $companyId)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number']);

        $tyres = FleetTyre::where('company_id', $companyId)
            ->where(function ($q) use ($installation) {
                $q->whereIn('status', ['new', 'removed'])->orWhere('id', $installation->tyre_id);
            })
            ->orderBy('tyre_serial')
            ->get(['id', 'tyre_serial', 'brand', 'status']);

        $positions = FleetTyrePosition::where('company_id', $companyId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('position_name')
            ->get(['id', 'position_code', 'position_name']);

        return view('fleet.tyre-installations.edit', compact('installation', 'vehicles', 'tyres', 'positions'));
    }

    public function update(Request $request, FleetTyreInstallation $installation)
    {
        $this->authorizeCompany($installation);
        $validated = $request->validate([
            'tyre_id' => 'required|exists:fleet_tyres,id',
            'vehicle_id' => 'required|exists:assets,id',
            'tyre_position_id' => 'required|exists:fleet_tyre_positions,id',
            'installed_at' => 'required|date',
            'odometer_at_install' => 'nullable|numeric|min:0',
            'installer_type' => 'nullable|string|max:50',
            'installer_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);
        $installation->update($validated);
        return redirect()->route('fleet.tyre-installations.show', $installation)->with('success', 'Installation updated.');
    }

    public function destroy(FleetTyreInstallation $installation)
    {
        $this->authorizeCompany($installation);
        $tyre = $installation->tyre;
        $installation->delete();
        if ($tyre && !$tyre->installations()->exists()) {
            $tyre->update(['status' => FleetTyre::STATUS_REMOVED]);
        }
        return redirect()->route('fleet.tyre-installations.index')->with('success', 'Installation record removed.');
    }

    private function authorizeCompany(FleetTyreInstallation $installation): void
    {
        if ($installation->company_id !== Auth::user()->company_id) {
            abort(403);
        }
    }
}
