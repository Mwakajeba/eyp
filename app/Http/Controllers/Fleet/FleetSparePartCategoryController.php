<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetCategory;
use App\Models\Fleet\FleetSparePartCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FleetSparePartCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $query = FleetSparePartCategory::where('company_id', $companyId)
            ->orderBy('name');

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }
        $categories = $query->paginate(15)->withQueryString();
        return view('fleet.spare-part-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('fleet.spare-part-categories.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50',
            'expected_lifespan_km' => 'nullable|numeric|min:0',
            'expected_lifespan_months' => 'nullable|integer|min:0',
            'min_replacement_interval_km' => 'nullable|numeric|min:0',
            'min_replacement_interval_months' => 'nullable|integer|min:0',
            'standard_cost_min' => 'nullable|numeric|min:0',
            'standard_cost_max' => 'nullable|numeric|min:0',
            'approval_threshold' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ]);
        $validated['company_id'] = $user->company_id;
        $validated['created_by'] = $user->id;
        $validated['is_active'] = $request->boolean('is_active', true);

        $category = FleetSparePartCategory::create($validated);

        // Create a linked Asset so the spare part category appears in the asset registry (like vehicles)
        $assetCat = AssetCategory::where('company_id', $user->company_id)->where('code', 'FA06')->first()
            ?? AssetCategory::where('company_id', $user->company_id)->first();
        if (!$assetCat) {
            return redirect()->route('fleet.spare-part-categories.show', $category)->with('success', 'Spare part category created.');
        }
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $assetCode = 'AST-SPC-' . str_pad((string) $category->id, 6, '0', STR_PAD_LEFT);
        Asset::create([
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'asset_category_id' => $assetCat->id,
            'code' => $assetCode,
            'name' => $category->name,
            'status' => 'active',
            'source_type' => 'fleet_spare_part_category',
            'source_id' => $category->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return redirect()->route('fleet.spare-part-categories.show', $category)->with('success', 'Spare part category created.');
    }

    public function show(FleetSparePartCategory $category)
    {
        $this->authorizeCompany($category);
        return view('fleet.spare-part-categories.show', compact('category'));
    }

    public function edit(FleetSparePartCategory $category)
    {
        $this->authorizeCompany($category);
        return view('fleet.spare-part-categories.edit', compact('category'));
    }

    public function update(Request $request, FleetSparePartCategory $category)
    {
        $this->authorizeCompany($category);
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50',
            'expected_lifespan_km' => 'nullable|numeric|min:0',
            'expected_lifespan_months' => 'nullable|integer|min:0',
            'min_replacement_interval_km' => 'nullable|numeric|min:0',
            'min_replacement_interval_months' => 'nullable|integer|min:0',
            'standard_cost_min' => 'nullable|numeric|min:0',
            'standard_cost_max' => 'nullable|numeric|min:0',
            'approval_threshold' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        $category->update($validated);
        return redirect()->route('fleet.spare-part-categories.show', $category)->with('success', 'Spare part category updated.');
    }

    public function destroy(FleetSparePartCategory $category)
    {
        $this->authorizeCompany($category);
        if ($category->replacements()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete: category has replacement records.');
        }
        $category->delete();
        return redirect()->route('fleet.spare-part-categories.index')->with('success', 'Spare part category deleted.');
    }

    private function authorizeCompany(FleetSparePartCategory $category): void
    {
        if ($category->company_id !== Auth::user()->company_id) {
            abort(403);
        }
    }
}
