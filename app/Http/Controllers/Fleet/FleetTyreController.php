<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetCategory;
use App\Models\Fleet\FleetTyre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FleetTyreController extends Controller
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

        $query = FleetTyre::with(['company', 'branch'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('tyre_serial');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('tyre_serial', 'like', $s)
                    ->orWhere('dot_number', 'like', $s)
                    ->orWhere('brand', 'like', $s)
                    ->orWhere('model', 'like', $s);
            });
        }

        $tyres = $query->paginate(15)->withQueryString();
        return view('fleet.tyres.index', compact('tyres'));
    }

    public function create()
    {
        return view('fleet.tyres.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'dot_number' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'tyre_size' => 'nullable|string|max:50',
            'supplier' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_type' => 'nullable|in:distance,time',
            'warranty_limit_value' => 'nullable|numeric|min:0',
            'expected_lifespan_km' => 'nullable|numeric|min:0',
            'status' => 'required|in:new,in_use,removed,under_warranty_claim,scrapped',
            'notes' => 'nullable|string|max:2000',
        ]);
        $validated['company_id'] = $user->company_id;
        $validated['branch_id'] = session('branch_id') ?? $user->branch_id;
        $validated['created_by'] = $user->id;
        $validated['updated_by'] = $user->id;

        $tyre = FleetTyre::create($validated);

        // Create a linked Asset so the tyre appears in the asset registry (like vehicles)
        $category = AssetCategory::where('company_id', $user->company_id)->where('code', 'FA05')->first()
            ?? AssetCategory::where('company_id', $user->company_id)->first();
        if (!$category) {
            return redirect()->route('fleet.tyres.show', $tyre)->with('success', 'Tyre registered successfully.');
        }
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $assetCode = 'AST-TYR-' . str_pad((string) $tyre->id, 6, '0', STR_PAD_LEFT);
        Asset::create([
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'asset_category_id' => $category->id,
            'code' => $assetCode,
            'name' => $tyre->tyre_serial . ($tyre->brand ? ' - ' . $tyre->brand : ''),
            'purchase_date' => $tyre->purchase_date,
            'purchase_cost' => $tyre->purchase_cost ?? 0,
            'serial_number' => $tyre->dot_number,
            'status' => 'active',
            'source_type' => 'fleet_tyre',
            'source_id' => $tyre->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return redirect()->route('fleet.tyres.show', $tyre)->with('success', 'Tyre registered successfully.');
    }

    public function show(FleetTyre $tyre)
    {
        $this->authorizeCompany($tyre);
        $tyre->load(['installations.vehicle', 'installations.tyrePosition']);
        return view('fleet.tyres.show', compact('tyre'));
    }

    public function edit(FleetTyre $tyre)
    {
        $this->authorizeCompany($tyre);
        return view('fleet.tyres.edit', compact('tyre'));
    }

    public function update(Request $request, FleetTyre $tyre)
    {
        $this->authorizeCompany($tyre);
        $validated = $request->validate([
            'dot_number' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'tyre_size' => 'nullable|string|max:50',
            'supplier' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_type' => 'nullable|in:distance,time',
            'warranty_limit_value' => 'nullable|numeric|min:0',
            'expected_lifespan_km' => 'nullable|numeric|min:0',
            'status' => 'required|in:new,in_use,removed,under_warranty_claim,scrapped',
            'notes' => 'nullable|string|max:2000',
        ]);
        $validated['updated_by'] = Auth::id();
        $tyre->update($validated);
        return redirect()->route('fleet.tyres.show', $tyre)->with('success', 'Tyre updated successfully.');
    }

    public function destroy(FleetTyre $tyre)
    {
        $this->authorizeCompany($tyre);
        if ($tyre->installations()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete tyre: it has installation history. Remove or scrap instead.');
        }
        $tyre->delete();
        return redirect()->route('fleet.tyres.index')->with('success', 'Tyre deleted.');
    }

    private function authorizeCompany(FleetTyre $tyre): void
    {
        if ($tyre->company_id !== Auth::user()->company_id) {
            abort(403);
        }
    }
}
