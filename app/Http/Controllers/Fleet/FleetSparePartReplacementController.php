<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Fleet\FleetSparePartCategory;
use App\Models\Fleet\FleetSparePartReplacement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FleetSparePartReplacementController extends Controller
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

        $query = FleetSparePartReplacement::with(['vehicle', 'sparePartCategory'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('replaced_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $replacements = $query->paginate(15)->withQueryString();
        return view('fleet.spare-part-replacements.index', compact('replacements'));
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

        $categories = FleetSparePartCategory::where('company_id', $companyId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('fleet.spare-part-replacements.create', compact('vehicles', 'categories'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:assets,id',
            'spare_part_category_id' => 'required|exists:fleet_spare_part_categories,id',
            'replaced_at' => 'required|date',
            'odometer_at_replacement' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:1000',
        ]);
        $validated['company_id'] = $user->company_id;
        $validated['branch_id'] = session('branch_id') ?? $user->branch_id;
        $validated['created_by'] = $user->id;
        $validated['status'] = FleetSparePartReplacement::STATUS_PENDING;

        $replacement = FleetSparePartReplacement::create($validated);
        return redirect()->route('fleet.spare-part-replacements.show', $replacement)->with('success', 'Replacement recorded. Pending approval.');
    }

    public function lastReplacementDetails(Request $request)
    {
        $request->validate(['vehicle_id' => 'required|exists:assets,id', 'spare_part_category_id' => 'required|exists:fleet_spare_part_categories,id']);
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $last = FleetSparePartReplacement::with(['sparePartCategory'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('vehicle_id', $request->vehicle_id)
            ->where('spare_part_category_id', $request->spare_part_category_id)
            ->orderByDesc('replaced_at')
            ->first();

        if (!$last) {
            return response()->json(['found' => false, 'message' => 'No previous replacement for this vehicle and category.']);
        }
        return response()->json([
            'found' => true,
            'id' => $last->id,
            'replaced_at' => $last->replaced_at?->format('d/m/Y'),
            'odometer_at_replacement' => $last->odometer_at_replacement ? (string) $last->odometer_at_replacement : null,
            'cost' => $last->cost ? (string) $last->cost : null,
            'reason' => $last->reason,
            'category_name' => $last->sparePartCategory?->name ?? $last->spare_part_category_id,
        ]);
    }

    public function show(FleetSparePartReplacement $replacement)
    {
        $this->authorizeCompany($replacement);
        $replacement->load(['vehicle', 'sparePartCategory', 'createdBy', 'approvedBy']);

        $lastReplacement = FleetSparePartReplacement::with(['createdBy'])
            ->where('company_id', $replacement->company_id)
            ->where('vehicle_id', $replacement->vehicle_id)
            ->where('spare_part_category_id', $replacement->spare_part_category_id)
            ->where('id', '!=', $replacement->id)
            ->orderByDesc('replaced_at')
            ->first();

        return view('fleet.spare-part-replacements.show', compact('replacement', 'lastReplacement'));
    }

    public function approve(Request $request, FleetSparePartReplacement $replacement)
    {
        $this->authorizeCompany($replacement);
        if ($replacement->status !== FleetSparePartReplacement::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Replacement is no longer pending.');
        }
        $replacement->update([
            'status' => FleetSparePartReplacement::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Replacement approved.');
    }

    public function reject(Request $request, FleetSparePartReplacement $replacement)
    {
        $this->authorizeCompany($replacement);
        if ($replacement->status !== FleetSparePartReplacement::STATUS_PENDING) {
            return redirect()->back()->with('error', 'Replacement is no longer pending.');
        }
        $replacement->update([
            'status' => FleetSparePartReplacement::STATUS_REJECTED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Replacement rejected.');
    }

    private function authorizeCompany(FleetSparePartReplacement $replacement): void
    {
        if ($replacement->company_id !== Auth::user()->company_id) {
            abort(403);
        }
    }
}
