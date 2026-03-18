<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetCategory;
use App\Models\Assets\TaxDepreciationClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class FleetVehicleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        // Get only Motor Vehicles category
        $vehicleCategory = AssetCategory::where('code', 'FA04')
            ->where('company_id', $user->company_id)
            ->first();

        $categories = collect([$vehicleCategory])->filter();
        $departments = \App\Models\Hr\Department::where('company_id', $user->company_id)
            ->when($branchId, function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->orderBy('name')
            ->get(['id','name']);

        // Calculate dashboard statistics
        $vehicleQuery = Asset::where('company_id', $user->company_id)
            ->where('asset_category_id', $vehicleCategory->id ?? 0)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        $totalVehicles = $vehicleQuery->count();
        $totalCost = $vehicleQuery->sum('purchase_cost');
        $availableVehicles = (clone $vehicleQuery)->where('operational_status', 'available')->count();
        $inRepairVehicles = (clone $vehicleQuery)->where('operational_status', 'in_repair')->count();

        return view('fleet.vehicles.index', compact('categories', 'departments', 'vehicleCategory', 'totalVehicles', 'totalCost', 'availableVehicles', 'inRepairVehicles'));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        \Log::info("Fleet data method: user_id={$user->id}, company_id={$user->company_id}, branchId={$branchId}");

        // Get Motor Vehicles category ID
        $vehicleCategoryId = AssetCategory::where('code', 'FA04')
            ->where('company_id', $user->company_id)
            ->value('id');

        \Log::info("Vehicle category ID: {$vehicleCategoryId}");

        $query = Asset::query()
            ->where('assets.company_id', $user->company_id)
            ->where('assets.asset_category_id', $vehicleCategoryId) // Filter for vehicles only
            ->when($branchId, fn($q) => $q->where('assets.branch_id', $branchId))
            ->leftJoin('asset_categories', 'asset_categories.id', '=', 'assets.asset_category_id')
            ->leftJoin('tax_depreciation_classes', 'tax_depreciation_classes.id', '=', 'assets.tax_class_id')
            ->select('assets.*', 'asset_categories.name as category_name', 'tax_depreciation_classes.class_code as tax_class_code');

        // Apply filters
        if ($request->filled('hfs_status')) {
            $query->where('assets.hfs_status', $request->hfs_status);
        }

        if ($request->filled('depreciation_stopped')) {
            $query->where('assets.depreciation_stopped', $request->depreciation_stopped == '1');
        }

        if ($request->filled('operational_status')) {
            $query->where('assets.operational_status', $request->operational_status);
        }

        $count = $query->count();
        \Log::info("Fleet vehicles query count: {$count}");

        return DataTables::of($query)
            ->addColumn('tax_class_display', function($a) {
                if ($a->tax_class_code) {
                    return '<span class="badge bg-info">' . e($a->tax_class_code) . '</span>';
                }
                return '<span class="badge bg-secondary">N/A</span>';
            })
            ->addColumn('operational_status_display', function($a) {
                $statusColors = [
                    'available' => 'success',
                    'assigned' => 'primary',
                    'in_repair' => 'warning',
                    'retired' => 'secondary',
                ];
                $color = $statusColors[$a->operational_status] ?? 'secondary';
                return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $a->operational_status ?? 'n/a')) . '</span>';
            })
            ->addColumn('hfs_status_display', function($a) {
                if (!$a->hfs_status || $a->hfs_status == 'none') {
                    return '-';
                }
                $colors = [
                    'pending' => 'warning',
                    'classified' => 'info',
                    'sold' => 'success',
                    'cancelled' => 'secondary',
                ];
                $color = $colors[$a->hfs_status] ?? 'secondary';
                return '<span class="badge bg-' . $color . '">' . ucfirst($a->hfs_status) . '</span>';
            })
            ->addColumn('depreciation_display', function($a) {
                if ($a->depreciation_stopped) {
                    return '<span class="badge bg-danger">Stopped</span>';
                }
                return '<span class="badge bg-success">Active</span>';
            })
            ->addColumn('actions', function($a) {
                // Check if vehicle has any trips
                $hasTrips = \App\Models\Fleet\FleetTrip::where('vehicle_id', $a->id)->exists();
                
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<a href="' . route('fleet.vehicles.show', $a->hash_id) . '" class="btn btn-sm btn-outline-info" title="View"><i class="bx bx-show"></i></a>';
                $actions .= '<a href="' . route('fleet.vehicles.edit', $a->hash_id) . '" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                
                // Status change dropdown
                $actions .= '<div class="btn-group" role="group">';
                $actions .= '<button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Change Status"><i class="bx bx-transfer"></i></button>';
                $actions .= '<ul class="dropdown-menu">';
                if ($a->operational_status !== 'available') {
                    $actions .= '<li><a class="dropdown-item change-vehicle-status" href="#" data-vehicle-id="' . $a->hash_id . '" data-status="available"><i class="bx bx-check-circle text-success me-1"></i>Set Available</a></li>';
                }
                if ($a->operational_status !== 'assigned') {
                    $actions .= '<li><a class="dropdown-item change-vehicle-status" href="#" data-vehicle-id="' . $a->hash_id . '" data-status="assigned"><i class="bx bx-user text-primary me-1"></i>Set Assigned</a></li>';
                }
                if ($a->operational_status !== 'in_repair') {
                    $actions .= '<li><a class="dropdown-item change-vehicle-status" href="#" data-vehicle-id="' . $a->hash_id . '" data-status="in_repair"><i class="bx bx-wrench text-warning me-1"></i>Set In Repair</a></li>';
                }
                if ($a->operational_status !== 'retired') {
                    $actions .= '<li><a class="dropdown-item change-vehicle-status" href="#" data-vehicle-id="' . $a->hash_id . '" data-status="retired"><i class="bx bx-x-circle text-secondary me-1"></i>Set Retired</a></li>';
                }
                $actions .= '</ul>';
                $actions .= '</div>';
                
                // Delete button - only enabled if vehicle has no trips
                if (!$hasTrips) {
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-danger delete-vehicle" data-vehicle-id="' . $a->hash_id . '" data-vehicle-name="' . htmlspecialchars($a->name) . '" title="Delete"><i class="bx bx-trash"></i></button>';
                } else {
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete: Vehicle has trips"><i class="bx bx-trash"></i></button>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['tax_class_display', 'operational_status_display', 'hfs_status_display', 'depreciation_display', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        // Get only Motor Vehicles category
        $categories = AssetCategory::where('code', 'FA04')
            ->where('company_id', $user->company_id)
            ->get(['id','name']);

        $taxClasses = $this->getTaxDepreciationClassesForCompany($user->company_id);

        // Load departments filtered by session branch_id
        $departments = \App\Models\Hr\Department::where('company_id', $user->company_id)
            ->when($branchId, function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->orderBy('name')
            ->get(['id','name']);

        return view('fleet.vehicles.create', compact('categories', 'departments', 'taxClasses'));
    }

    public function show(Asset $vehicle)
    {
        $user = Auth::user();

        // Ensure the asset belongs to the user's company and is a vehicle
        if ($vehicle->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this vehicle.');
        }

        $vehicleCategoryId = AssetCategory::where('code', 'FA04')
            ->where('company_id', $user->company_id)
            ->value('id');

        if ($vehicle->asset_category_id !== $vehicleCategoryId) {
            abort(404, 'Vehicle not found.');
        }

        $vehicle->load(['assignedDriver']);

        // Get driver assignment history for this vehicle
        $driverHistory = collect();
        $drivers = collect();
        
        try {
            // Get all drivers who have been assigned to this vehicle from activity logs
            if (\Schema::hasTable('activity_log')) {
                $logs = \DB::table('activity_log')
                    ->where('log_name', 'default')
                    ->where('subject_type', 'App\\Models\\Fleet\\FleetDriver')
                    ->whereNotNull('properties')
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                foreach ($logs as $log) {
                    $properties = json_decode($log->properties, true);
                    $attributes = $properties['attributes'] ?? [];
                    $old = $properties['old'] ?? [];
                    
                    // Check if this log involves our vehicle
                    if ((isset($attributes['assigned_vehicle_id']) && $attributes['assigned_vehicle_id'] == $vehicle->id) ||
                        (isset($old['assigned_vehicle_id']) && $old['assigned_vehicle_id'] == $vehicle->id)) {
                        
                        $driverHistory->push([
                            'driver_id' => $log->subject_id,
                            'vehicle_id' => $attributes['assigned_vehicle_id'] ?? null,
                            'old_vehicle_id' => $old['assigned_vehicle_id'] ?? null,
                            'start_date' => isset($attributes['assignment_start_date']) ? \Carbon\Carbon::parse($attributes['assignment_start_date']) : null,
                            'end_date' => isset($attributes['assignment_end_date']) ? \Carbon\Carbon::parse($attributes['assignment_end_date']) : null,
                            'changed_at' => \Carbon\Carbon::parse($log->created_at),
                            'description' => $log->description ?? 'updated',
                        ]);
                    }
                }
                
                // Load drivers for the history
                $driverIds = $driverHistory->pluck('driver_id')->filter()->unique();
                if ($driverIds->count() > 0) {
                    $drivers = \App\Models\Fleet\FleetDriver::whereIn('id', $driverIds)->get()->keyBy('id');
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Could not fetch driver assignment history for vehicle: ' . $e->getMessage());
        }
        
        // If no history from logs, create a simple entry from current assignment
        if ($driverHistory->isEmpty() && $vehicle->assignedDriver) {
            $driverHistory->push([
                'driver_id' => $vehicle->assignedDriver->id,
                'vehicle_id' => $vehicle->id,
                'old_vehicle_id' => null,
                'start_date' => $vehicle->assignedDriver->assignment_start_date,
                'end_date' => $vehicle->assignedDriver->assignment_end_date,
                'changed_at' => $vehicle->assignedDriver->updated_at,
                'description' => 'Current Assignment',
            ]);
            
            $drivers->put($vehicle->assignedDriver->id, $vehicle->assignedDriver);
        }

        return view('fleet.vehicles.show', compact('vehicle', 'driverHistory', 'drivers'));
    }

    public function edit(Asset $vehicle)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        // Ensure the asset belongs to the user's company and is a vehicle
        if ($vehicle->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this vehicle.');
        }

        $vehicleCategoryId = AssetCategory::where('code', 'FA04')
            ->where('company_id', $user->company_id)
            ->value('id');

        if ($vehicle->asset_category_id !== $vehicleCategoryId) {
            abort(404, 'Vehicle not found.');
        }

        // Get only Motor Vehicles category
        $categories = AssetCategory::where('code', 'FA04')
            ->where('company_id', $user->company_id)
            ->get(['id','name']);

        $taxClasses = $this->getTaxDepreciationClassesForCompany($user->company_id);

        // Load departments filtered by session branch_id
        $departments = \App\Models\Hr\Department::where('company_id', $user->company_id)
            ->when($branchId, function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->orderBy('name')
            ->get(['id','name']);

        return view('fleet.vehicles.edit', compact('vehicle', 'categories', 'departments', 'taxClasses'));
    }

    public function store(Request $request)
    {
        // Force the category to be Motor Vehicles
        $user = Auth::user();
        $vehicleCategoryId = AssetCategory::where('code', 'FA04')
            ->where('company_id', $user->company_id)
            ->value('id');

        $request->merge(['asset_category_id' => $vehicleCategoryId]);

        // Validate the request with all necessary fields including vehicle fields
        $validated = $request->validate([
            'asset_category_id' => 'required|exists:asset_categories,id',
            'tax_class_id' => 'nullable|exists:tax_depreciation_classes,id',
            'code' => 'nullable|string|max:50|unique:assets,code',
            'name' => 'required|string|max:255',
            'model' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'capitalization_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'salvage_value' => 'nullable|numeric|min:0',
            'current_nbv' => 'nullable|numeric|min:0',
            'department_id' => 'nullable|integer|exists:hr_departments,id',
            'custodian_user_id' => 'nullable|integer',
            'location' => 'nullable|string|max:255',
            'building_reference' => 'nullable|string|max:255',
            'gps_lat' => 'nullable|numeric',
            'gps_lng' => 'nullable|numeric',
            'serial_number' => 'nullable|string|max:255',
            'warranty_months' => 'nullable|integer|min:0',
            'warranty_expiry_date' => 'nullable|date',
            'insurance_policy_no' => 'nullable|string|max:100',
            'insured_value' => 'nullable|numeric|min:0',
            'insurance_expiry_date' => 'nullable|date',
            'attachments.*' => 'nullable|file|max:5120',
            'tag' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,under_construction,under_repair,disposed,retired',
            'description' => 'nullable|string',
            // Fleet Management - Vehicle Specific Fields
            'registration_number' => 'nullable|string|max:50',
            'ownership_type' => 'nullable|in:owned,leased,rented',
            'fuel_type' => 'nullable|in:petrol,diesel,electric,hybrid,lpg,cng',
            'capacity_tons' => 'nullable|numeric|min:0',
            'capacity_volume' => 'nullable|numeric|min:0',
            'capacity_passengers' => 'nullable|integer|min:0',
            'license_expiry_date' => 'nullable|date',
            'inspection_expiry_date' => 'nullable|date',
            'operational_status' => 'nullable|in:available,assigned,in_repair,retired',
            'gps_device_id' => 'nullable|string|max:100',
            'current_location' => 'nullable|string|max:255',
        ]);

        // Ensure a non-null code for initial insert (unique temp code)
        $payload = $validated;
        
        // Remove attachments from payload as they will be handled separately
        unset($payload['attachments']);
        
        if (empty($payload['code'])) {
            $payload['code'] = 'TMP-' . Str::uuid()->toString();
        }

        // Set purchase_cost to 0 if not provided or empty
        if (!isset($payload['purchase_cost']) || $payload['purchase_cost'] === null || $payload['purchase_cost'] === '') {
            $payload['purchase_cost'] = 0;
        }

        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $asset = Asset::create(array_merge($payload, [
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'created_by' => $user->id,
        ]));

        // Handle auto code/tag/barcode generation
        $updates = [];
        if (str_starts_with($asset->code, 'TMP-')) {
            $updates['code'] = 'AST-' . str_pad($asset->id, 6, '0', STR_PAD_LEFT);
        }

        if (empty($asset->tag)) {
            $updates['tag'] = $asset->code;
        }

        if (empty($asset->barcode)) {
            $updates['barcode'] = $asset->code;
        }

        if (!empty($updates)) {
            $asset->update($updates);
        }

        // Handle attachments if uploaded
        if ($request->hasFile('attachments')) {
            $paths = [];
            foreach ($request->file('attachments') as $file) {
                $paths[] = $file->store('assets/attachments', 'public');
            }
            $asset->update(['attachments' => json_encode($paths)]);
        }

        return redirect()->route('fleet.vehicles.index')->with('success', 'Vehicle saved successfully.');
    }

    public function update(Request $request, Asset $vehicle)
    {
        $user = Auth::user();

        // Ensure the asset belongs to the user's company and is a vehicle
        if ($vehicle->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this vehicle.');
        }

        $vehicleCategoryId = AssetCategory::where('code', 'FA04')
            ->where('company_id', $user->company_id)
            ->value('id');

        if ($vehicle->asset_category_id !== $vehicleCategoryId) {
            abort(404, 'Vehicle not found.');
        }

        // Force the category to be Motor Vehicles (in case it was changed in the form)
        $request->merge(['asset_category_id' => $vehicleCategoryId]);

        // Validate the request with all necessary fields including vehicle fields
        $validated = $request->validate([
            'asset_category_id' => 'required|exists:asset_categories,id',
            'tax_class_id' => 'nullable|exists:tax_depreciation_classes,id',
            'code' => 'required|string|max:50|unique:assets,code,'.$vehicle->id,
            'name' => 'required|string|max:255',
            'model' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'capitalization_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'salvage_value' => 'nullable|numeric|min:0',
            'current_nbv' => 'nullable|numeric|min:0',
            'department_id' => 'nullable|integer|exists:hr_departments,id',
            'custodian_user_id' => 'nullable|integer',
            'location' => 'nullable|string|max:255',
            'building_reference' => 'nullable|string|max:255',
            'gps_lat' => 'nullable|numeric',
            'gps_lng' => 'nullable|numeric',
            'serial_number' => 'nullable|string|max:255',
            'warranty_months' => 'nullable|integer|min:0',
            'warranty_expiry_date' => 'nullable|date',
            'insurance_policy_no' => 'nullable|string|max:100',
            'insured_value' => 'nullable|numeric|min:0',
            'insurance_expiry_date' => 'nullable|date',
            'attachments.*' => 'nullable|file|max:5120',
            'tag' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,under_construction,under_repair,disposed,retired',
            'description' => 'nullable|string',
            // Fleet Management - Vehicle Specific Fields
            'registration_number' => 'nullable|string|max:50',
            'ownership_type' => 'nullable|in:owned,leased,rented',
            'fuel_type' => 'nullable|in:petrol,diesel,electric,hybrid,lpg,cng',
            'capacity_tons' => 'nullable|numeric|min:0',
            'capacity_volume' => 'nullable|numeric|min:0',
            'capacity_passengers' => 'nullable|integer|min:0',
            'license_expiry_date' => 'nullable|date',
            'inspection_expiry_date' => 'nullable|date',
            'operational_status' => 'nullable|in:available,assigned,in_repair,retired',
            'gps_device_id' => 'nullable|string|max:100',
            'current_location' => 'nullable|string|max:255',
        ]);

        // Remove attachments from validated data as they will be handled separately
        unset($validated['attachments']);

        // Set purchase_cost to 0 if not provided or empty
        if (!isset($validated['purchase_cost']) || $validated['purchase_cost'] === null || $validated['purchase_cost'] === '') {
            $validated['purchase_cost'] = 0;
        }

        // Update the vehicle
        $vehicle->update(array_merge($validated, [ 'updated_by' => $user->id ]));

        // Handle attachments if uploaded
        if ($request->hasFile('attachments')) {
            $paths = [];
            foreach ($request->file('attachments') as $file) {
                $paths[] = $file->store('assets/attachments', 'public');
            }
            $vehicle->update(['attachments' => json_encode($paths)]);
        }

        return redirect()->route('fleet.vehicles.show', $vehicle->hash_id)->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Asset $vehicle)
    {
        $user = Auth::user();

        // Ensure the asset belongs to the user's company and is a vehicle
        if ($vehicle->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this vehicle.');
        }

        $vehicleCategoryId = AssetCategory::where('code', 'FA04')
            ->where('company_id', $user->company_id)
            ->value('id');

        if ($vehicle->asset_category_id !== $vehicleCategoryId) {
            abort(404, 'Vehicle not found.');
        }

        // Check if vehicle has any trips
        $hasTrips = \App\Models\Fleet\FleetTrip::where('vehicle_id', $vehicle->id)->exists();
        if ($hasTrips) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this vehicle because it has associated trips.'
                ], 422);
            }
            return redirect()->route('fleet.vehicles.index')->with('error', 'Cannot delete this vehicle because it has associated trips.');
        }

        // Call the parent destroy method with hashid
        $assetRegistryController = new \App\Http\Controllers\Asset\AssetRegistryController();
        $response = $assetRegistryController->destroy($vehicle->hash_id);

        // If it's a redirect response, change the redirect to fleet vehicles index
        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vehicle deleted successfully.'
                ]);
            }
            return redirect()->route('fleet.vehicles.index')->with('success', 'Vehicle deleted successfully.');
        }

        return $response;
    }

    public function print(Asset $vehicle)
    {
        $user = Auth::user();

        // Ensure the asset belongs to the user's company and is a vehicle
        if ($vehicle->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this vehicle.');
        }

        $vehicleCategoryId = AssetCategory::where('code', 'FA04')
            ->where('company_id', $user->company_id)
            ->value('id');

        if ($vehicle->asset_category_id !== $vehicleCategoryId) {
            abort(404, 'Vehicle not found.');
        }

        $company = \App\Models\Company::find($user->company_id);
        $generatedAt = now();

        return view('fleet.vehicles.print', compact('vehicle', 'company', 'generatedAt'));
    }

    public function downloadSample()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\FleetVehicleSampleExport(),
            'fleet_vehicles_import_sample.xlsx'
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        try {
            $user = Auth::user();
            $branchId = session('branch_id') ?? $user->branch_id ?? null;

            // Force the category to be Motor Vehicles
            $vehicleCategoryId = AssetCategory::where('code', 'FA04')
                ->where('company_id', $user->company_id)
                ->value('id');

            if (!$vehicleCategoryId) {
                return response()->json(['success' => false, 'message' => 'Motor Vehicles category not found.'], 422);
            }

            $uploadedFile = $request->file('import_file');
            if (!$uploadedFile) {
                return response()->json(['success' => false, 'message' => 'No file uploaded.'], 422);
            }

            $extension = strtolower($uploadedFile->getClientOriginalExtension());
            $mimeType = $uploadedFile->getMimeType();

            // Process file based on type
            if (in_array($mimeType, ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])) {
                // Handle Excel files
                try {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($uploadedFile->getRealPath());
                    $worksheet = $spreadsheet->getActiveSheet();
                    $rows = $worksheet->toArray();
                } catch (\Exception $e) {
                    return response()->json(['success' => false, 'message' => 'Error processing Excel file: ' . $e->getMessage()], 422);
                }
            } else {
                // Handle CSV and other text files
                try {
                    $rows = array_map('str_getcsv', file($uploadedFile->getRealPath()));
                } catch (\Exception $e) {
                    return response()->json(['success' => false, 'message' => 'Error processing file: ' . $e->getMessage()], 422);
                }
            }

            if (empty($rows) || count($rows) < 2) {
                return response()->json(['success' => false, 'message' => 'The file is empty or contains no data rows.'], 422);
            }


            $header = array_map(fn($h) => strtolower(trim($h)), array_shift($rows));

            // Check required columns (purchase_cost optional, can be null)
            $required = ['name', 'registration_number'];
            foreach ($required as $col) {
                if (!in_array($col, $header)) {
                    return response()->json(['success' => false, 'message' => "Missing required column: {$col}"], 422);
                }
            }

            $imported = 0;
            $errors = [];
            $createdIds = [];

            foreach ($rows as $rowIndex => $row) {
                if (count($row) !== count($header)) {
                    $row = array_pad($row, count($header), null);
                }
                $data = array_combine($header, $row);

                // Required fields: name, registration_number (purchase_cost is optional, defaults to 0)
                $name = trim($data['name'] ?? '');
                $registrationNumber = trim($data['registration_number'] ?? '');
                $purchaseCostRaw = $data['purchase_cost'] ?? null;

                if ($name === '') {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': Vehicle name is required.';
                    continue;
                }
                if (strlen($name) > 255) {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': Vehicle name must not exceed 255 characters.';
                    continue;
                }
                if ($registrationNumber === '') {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': Registration number is required.';
                    continue;
                }
                if (strlen($registrationNumber) > 50) {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': Registration number must not exceed 50 characters.';
                    continue;
                }
                
                // Check for duplicate registration number in database
                $existingVehicle = Asset::where('company_id', $user->company_id)
                    ->where('registration_number', $registrationNumber)
                    ->first();
                if ($existingVehicle) {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': Vehicle with registration number "' . $registrationNumber . '" already exists. Skipping duplicate.';
                    continue;
                }
                
                if ($purchaseCostRaw !== null && $purchaseCostRaw !== '' && (!is_numeric($purchaseCostRaw) || (float)$purchaseCostRaw < 0)) {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': Purchase cost must be a number greater than or equal to 0.';
                    continue;
                }

                // Optional enum validation (same as create form / store)
                $allowedOwnership = ['owned', 'leased', 'rented'];
                $allowedFuel = ['petrol', 'diesel', 'electric', 'hybrid', 'lpg', 'cng'];
                $allowedOperationalStatus = ['available', 'assigned', 'in_repair', 'retired'];
                $ownershipType = isset($data['ownership_type']) ? strtolower(trim($data['ownership_type'])) : null;
                $fuelType = isset($data['fuel_type']) ? strtolower(trim($data['fuel_type'])) : null;
                $operationalStatus = isset($data['operational_status']) ? strtolower(trim($data['operational_status'])) : 'available';

                if ($ownershipType !== null && $ownershipType !== '' && !in_array($ownershipType, $allowedOwnership)) {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': Invalid ownership_type. Must be one of: ' . implode(', ', $allowedOwnership);
                    continue;
                }
                if ($fuelType !== null && $fuelType !== '' && !in_array($fuelType, $allowedFuel)) {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': Invalid fuel_type. Must be one of: ' . implode(', ', $allowedFuel);
                    continue;
                }
                if (!in_array($operationalStatus, $allowedOperationalStatus)) {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': Invalid operational_status. Must be one of: ' . implode(', ', $allowedOperationalStatus);
                    continue;
                }

                // Optional string length limits (match store validation)
                if (!empty($data['model']) && strlen($data['model']) > 100) {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': Model must not exceed 100 characters.';
                    continue;
                }
                if (!empty($data['manufacturer']) && strlen($data['manufacturer']) > 100) {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': Manufacturer must not exceed 100 characters.';
                    continue;
                }
                if (!empty($data['serial_number']) && strlen($data['serial_number']) > 255) {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': Serial number must not exceed 255 characters.';
                    continue;
                }

                // Parse and validate data
                $purchaseDate = !empty($data['purchase_date']) ? \Carbon\Carbon::parse($data['purchase_date']) : null;
                $capitalizationDate = !empty($data['capitalization_date']) ? \Carbon\Carbon::parse($data['capitalization_date']) : $purchaseDate;
                $licenseExpiryDate = !empty($data['license_expiry_date']) ? \Carbon\Carbon::parse($data['license_expiry_date']) : null;
                $inspectionExpiryDate = !empty($data['inspection_expiry_date']) ? \Carbon\Carbon::parse($data['inspection_expiry_date']) : null;

                // Temporary unique code so INSERT succeeds (assets.code is NOT NULL); replaced with final code after create
                $tempCode = 'AST-IMP-' . ($rowIndex + 2) . '-' . uniqid();

                $purchaseCost = ($purchaseCostRaw !== null && $purchaseCostRaw !== '' && is_numeric($purchaseCostRaw))
                    ? (float) $purchaseCostRaw
                    : 0;

                // Optional tax_class_id (same as create form): column "tax_class_id" or "tax_class" (code)
                $taxClassId = null;
                if (array_key_exists('tax_class_id', $data) && $data['tax_class_id'] !== null && $data['tax_class_id'] !== '') {
                    $tid = is_numeric($data['tax_class_id']) ? (int)$data['tax_class_id'] : null;
                    if ($tid && TaxDepreciationClass::where('id', $tid)->where('company_id', $user->company_id)->exists()) {
                        $taxClassId = $tid;
                    }
                } elseif (array_key_exists('tax_class', $data) && trim($data['tax_class'] ?? '') !== '') {
                    $tc = TaxDepreciationClass::where('company_id', $user->company_id)
                        ->where('class_code', trim($data['tax_class']))
                        ->value('id');
                    if ($tc) {
                        $taxClassId = $tc;
                    }
                }

                // Optional department_id (same as create form)
                $departmentId = null;
                if (array_key_exists('department_id', $data) && is_numeric($data['department_id']) && (int)$data['department_id'] > 0) {
                    $did = (int) $data['department_id'];
                    if (Schema::hasTable('hr_departments') && DB::table('hr_departments')->where('id', $did)->exists()) {
                        $departmentId = $did;
                    }
                }

                // Clean numeric fields by removing commas
                $capacityTonsRaw = isset($data['capacity_tons']) ? str_replace(',', '', trim($data['capacity_tons'])) : null;
                $capacityVolumeRaw = isset($data['capacity_volume']) ? str_replace(',', '', trim($data['capacity_volume'])) : null;
                $capacityPassengersRaw = isset($data['capacity_passengers']) ? str_replace(',', '', trim($data['capacity_passengers'])) : null;

                $validatedData = [
                    'company_id' => $user->company_id,
                    'branch_id' => $branchId,
                    'asset_category_id' => $vehicleCategoryId,
                    'tax_class_id' => $taxClassId,
                    'department_id' => $departmentId,
                    'code' => $tempCode,
                    'name' => $name,
                    'registration_number' => $registrationNumber,
                    'purchase_cost' => $purchaseCost,
                    'model' => isset($data['model']) && strlen($data['model']) <= 100 ? trim($data['model']) : null,
                    'manufacturer' => isset($data['manufacturer']) && strlen($data['manufacturer']) <= 100 ? trim($data['manufacturer']) : null,
                    'serial_number' => isset($data['serial_number']) && strlen($data['serial_number']) <= 255 ? trim($data['serial_number']) : null,
                    'fuel_type' => $fuelType ?: null,
                    'ownership_type' => $ownershipType ?: null,
                    'capacity_tons' => $capacityTonsRaw && is_numeric($capacityTonsRaw) && (float)$capacityTonsRaw >= 0 ? (float)$capacityTonsRaw : null,
                    'capacity_volume' => $capacityVolumeRaw && is_numeric($capacityVolumeRaw) && (float)$capacityVolumeRaw >= 0 ? (int)$capacityVolumeRaw : null,
                    'capacity_passengers' => $capacityPassengersRaw && is_numeric($capacityPassengersRaw) && (int)$capacityPassengersRaw >= 0 ? (int)$capacityPassengersRaw : null,
                    'license_expiry_date' => $licenseExpiryDate,
                    'inspection_expiry_date' => $inspectionExpiryDate,
                    'operational_status' => $operationalStatus,
                    'location' => isset($data['location']) && strlen($data['location']) <= 255 ? trim($data['location']) : null,
                    'description' => $data['description'] ?? null,
                    'purchase_date' => $purchaseDate,
                    'capitalization_date' => $capitalizationDate,
                    'salvage_value' => isset($data['salvage_value']) && is_numeric($data['salvage_value']) && (float)$data['salvage_value'] >= 0 ? (float)$data['salvage_value'] : 0,
                    'status' => 'active',
                    'created_by' => $user->id,
                ];

                try {
                    $asset = Asset::create($validatedData);

                    // Replace temporary code with final code/tag/barcode (AST-000001 style)
                    $finalCode = 'AST-' . str_pad($asset->id, 6, '0', STR_PAD_LEFT);
                    $asset->update([
                        'code' => $finalCode,
                        'tag' => $asset->tag ?? $finalCode,
                        'barcode' => $asset->barcode ?? $finalCode,
                    ]);

                    $imported++;
                    $createdIds[] = $asset->id;
                } catch (\Exception $e) {
                    \Log::error("Error creating asset for row " . ($rowIndex + 2) . ": " . $e->getMessage());
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': ' . $e->getMessage();
                }
            }

            $message = "Successfully imported {$imported} vehicles.";
            if (!empty($errors)) {
                $message .= ' ' . count($errors) . ' errors occurred.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'imported' => $imported,
                'created_ids' => $createdIds,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function changeStatus(Request $request, Asset $vehicle)
    {
        $user = Auth::user();

        // Ensure the asset belongs to the user's company and is a vehicle
        if ($vehicle->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this vehicle.');
        }

        $vehicleCategoryId = AssetCategory::where('code', 'FA04')
            ->where('company_id', $user->company_id)
            ->value('id');

        if ($vehicle->asset_category_id !== $vehicleCategoryId) {
            abort(404, 'Vehicle not found.');
        }

        $validated = $request->validate([
            'operational_status' => 'required|in:available,assigned,in_repair,retired',
        ]);

        $vehicle->update([
            'operational_status' => $validated['operational_status'],
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle status updated to ' . ucfirst(str_replace('_', ' ', $validated['operational_status'])),
        ]);
    }

    /**
     * Get TRA tax depreciation classes for the company (same source as asset-management/tax-depreciation).
     * If the table is empty, runs the seeder so the dropdown matches the Tax Depreciation page.
     */
    private function getTaxDepreciationClassesForCompany(?int $companyId)
    {
        if (!Schema::hasTable('tax_depreciation_classes')) {
            return collect([]);
        }

        $taxClasses = TaxDepreciationClass::active()
            ->forCompany($companyId)
            ->orderBy('sort_order')
            ->orderBy('class_code')
            ->get(['id', 'class_code', 'description']);

        if ($taxClasses->isEmpty()) {
            try {
                if (TaxDepreciationClass::count() === 0) {
                    (new \Database\Seeders\TaxDepreciationClassSeeder())->run();
                }
                $taxClasses = TaxDepreciationClass::active()
                    ->forCompany($companyId)
                    ->orderBy('sort_order')
                    ->orderBy('class_code')
                    ->get(['id', 'class_code', 'description']);
            } catch (\Throwable $e) {
                // If seeder fails, continue with empty or try without company filter
            }
            if ($taxClasses->isEmpty()) {
                $taxClasses = TaxDepreciationClass::active()
                    ->orderBy('sort_order')
                    ->orderBy('class_code')
                    ->get(['id', 'class_code', 'description']);
            }
        }

        return $taxClasses;
    }
}