<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetCostCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class FleetCostCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index()
    {
        $user = Auth::user();
        
        // Calculate dashboard statistics
        $categoryQuery = FleetCostCategory::where('company_id', $user->company_id);
        
        $totalCategories = $categoryQuery->count();
        $activeCategories = (clone $categoryQuery)->where('is_active', true)->count();
        $fuelCategories = (clone $categoryQuery)->where('category_type', 'fuel')->count();
        $maintenanceCategories = (clone $categoryQuery)->where('category_type', 'maintenance')->count();
        
        return view('fleet.cost-categories.index', compact('totalCategories', 'activeCategories', 'fuelCategories', 'maintenanceCategories'));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $query = FleetCostCategory::where('company_id', $user->company_id)
            ->orderBy('category_type')
            ->orderBy('name');

        if ($request->filled('category_type')) {
            $query->where('category_type', $request->category_type);
        }
        if ($request->filled('is_active')) {
            if ($request->is_active === '1') {
                $query->where('is_active', true);
            } elseif ($request->is_active === '0') {
                $query->where('is_active', false);
            }
        }

        return DataTables::of($query)
            ->addColumn('type_display', function ($cat) {
                return '<span class="badge bg-secondary">' . ucfirst(str_replace('_', ' ', $cat->category_type)) . '</span>';
            })
            ->addColumn('active_display', function ($cat) {
                return $cat->is_active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>';
            })
            ->addColumn('actions', function ($cat) {
                $actions = '<div class="btn-group btn-group-sm" role="group" style="white-space: nowrap;">';
                $actions .= '<a href="' . route('fleet.cost-categories.show', $cat->id) . '" class="btn btn-outline-info" title="View"><i class="bx bx-show"></i></a>';
                $actions .= '<a href="' . route('fleet.cost-categories.edit', $cat->id) . '" class="btn btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                $actions .= '<button type="button" class="btn btn-outline-danger delete-category-btn" title="Delete" data-category-id="' . $cat->id . '" data-category-name="' . htmlspecialchars($cat->name, ENT_QUOTES) . '"><i class="bx bx-trash"></i></button>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['type_display', 'active_display', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('fleet.cost-categories.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_type' => 'required|in:fuel,maintenance,insurance,driver_cost,toll,other',
            'description' => 'nullable|string|max:500',
            'unit_of_measure' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);
        $validated['company_id'] = $user->company_id;
        $validated['is_active'] = $request->boolean('is_active', false);
        $validated['created_by'] = $user->id;

        $category = FleetCostCategory::create($validated);

        return redirect()->route('fleet.cost-categories.show', $category->id)->with('success', 'Cost category created.');
    }

    public function show(FleetCostCategory $category)
    {
        $user = Auth::user();
        if ($category->company_id !== $user->company_id) {
            abort(403);
        }
        return view('fleet.cost-categories.show', compact('category'));
    }

    public function edit(FleetCostCategory $category)
    {
        $user = Auth::user();
        if ($category->company_id !== $user->company_id) {
            abort(403);
        }
        return view('fleet.cost-categories.edit', compact('category'));
    }

    public function update(Request $request, FleetCostCategory $category)
    {
        $user = Auth::user();
        if ($category->company_id !== $user->company_id) {
            abort(403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_type' => 'required|in:fuel,maintenance,insurance,driver_cost,toll,other',
            'description' => 'nullable|string|max:500',
            'unit_of_measure' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', false);
        $validated['updated_by'] = $user->id;

        $category->update($validated);

        return redirect()->route('fleet.cost-categories.show', $category->id)->with('success', 'Cost category updated.');
    }

    public function destroy(FleetCostCategory $category)
    {
        $user = Auth::user();
        if ($category->company_id !== $user->company_id) {
            abort(403);
        }
        $category->delete();
        return redirect()->route('fleet.cost-categories.index')->with('success', 'Cost category deleted.');
    }
}
