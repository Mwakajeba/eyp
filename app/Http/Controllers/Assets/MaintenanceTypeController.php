<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Assets\MaintenanceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class MaintenanceTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    /**
     * Display a listing of maintenance types
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $user = Auth::user();
            $companyId = $user->company_id;
            $branchId = session('branch_id') ?? $user->branch_id;

            $types = MaintenanceType::forCompany($companyId)
                ->when($branchId, fn($q) => $q->forBranch($branchId))
                ->latest();

            return DataTables::of($types)
                ->addColumn('type_badge', function ($type) {
                    $badges = [
                        'preventive' => '<span class="badge bg-info">Preventive</span>',
                        'corrective' => '<span class="badge bg-warning">Corrective</span>',
                        'major_overhaul' => '<span class="badge bg-danger">Major Overhaul</span>',
                    ];
                    return $badges[$type->type] ?? '<span class="badge bg-secondary">' . ucfirst($type->type) . '</span>';
                })
                ->addColumn('status_badge', function ($type) {
                    return $type->is_active 
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-secondary">Inactive</span>';
                })
                ->addColumn('actions', function ($type) {
                    $actions = '';
                    $encodedId = Hashids::encode($type->id);
                    
                    if (auth()->user()->can('edit maintenance types')) {
                        $actions .= '<a href="' . route('assets.maintenance.types.edit', $encodedId) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    }
                    
                    if (auth()->user()->can('delete maintenance types')) {
                        $actions .= '<button class="btn btn-sm btn-outline-danger delete-type" data-id="' . $encodedId . '"><i class="bx bx-trash"></i></button>';
                    }
                    
                    return $actions;
                })
                ->rawColumns(['type_badge', 'status_badge', 'actions'])
                ->make(true);
        }

        return view('assets.maintenance.types.index');
    }

    /**
     * Show the form for creating a new maintenance type
     */
    public function create()
    {
        return view('assets.maintenance.types.create');
    }

    /**
     * Store a newly created maintenance type
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:maintenance_types,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:preventive,corrective,major_overhaul',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $branchId = session('branch_id') ?? $user->branch_id;

        MaintenanceType::create([
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $user->id,
        ]);

        return redirect()->route('assets.maintenance.types.index')
            ->with('success', 'Maintenance type created successfully.');
    }

    /**
     * Show the form for editing the specified maintenance type
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $maintenanceType = MaintenanceType::findOrFail($id);

        return view('assets.maintenance.types.edit', compact('maintenanceType', 'encodedId'));
    }

    /**
     * Update the specified maintenance type
     */
    public function update(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $maintenanceType = MaintenanceType::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:maintenance_types,code,' . $id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:preventive,corrective,major_overhaul',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $maintenanceType->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('assets.maintenance.types.index')
            ->with('success', 'Maintenance type updated successfully.');
    }

    /**
     * Remove the specified maintenance type
     */
    public function destroy($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid maintenance type ID'], 404);
        }

        $maintenanceType = MaintenanceType::findOrFail($id);

        // Check if used in any requests or work orders
        if ($maintenanceType->maintenanceRequests()->count() > 0 || $maintenanceType->workOrders()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete maintenance type that is used in requests or work orders.'
            ], 400);
        }

        $maintenanceType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance type deleted successfully.'
        ]);
    }
}
