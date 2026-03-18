<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetCategory;
use App\Models\Assets\AssetDepreciation;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Vinkla\Hashids\Facades\Hashids;

class AssetRegistryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        
        $categories = AssetCategory::orderBy('name')->get(['id','name']);
        
        // Load departments filtered by session branch_id
        $departments = \App\Models\Hr\Department::where('company_id', $user->company_id)
            ->when($branchId, function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->orderBy('name')
            ->get(['id','name']);
        
        return view('assets.registry.index', compact('categories', 'departments'));
    }

    public function create()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $categories = AssetCategory::orderBy('name')->get(['id','name']);
        $taxClasses = $this->getTaxDepreciationClassesForCompany($user->company_id);

        // Load departments filtered by session branch_id
        $departments = \App\Models\Hr\Department::where('company_id', $user->company_id)
            ->when($branchId, function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->orderBy('name')
            ->get(['id','name']);

        return view('assets.registry.create', compact('categories', 'departments', 'taxClasses'));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        
        $query = Asset::query()
            ->where('assets.company_id', $user->company_id)
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

        return DataTables::of($query)
            ->editColumn('purchase_date', fn($a) => optional($a->purchase_date)->format('Y-m-d'))
            ->addColumn('tax_class_display', function($a) {
                if ($a->tax_class_code) {
                    return '<span class="badge bg-info">' . e($a->tax_class_code) . '</span>';
                }
                return '<span class="badge bg-secondary">N/A</span>';
            })
            ->addColumn('hfs_status_display', function($a) {
                if (!$a->hfs_status || $a->hfs_status == 'none') {
                    return '-';
                }
                $colors = [
                    'pending' => 'warning',
                    'classified' => 'info',
                    'sold' => 'success',
                    'cancelled' => 'dark'
                ];
                $color = $colors[$a->hfs_status] ?? 'secondary';
                return '<span class="badge bg-' . $color . '">' . ucfirst($a->hfs_status) . '</span>';
            })
            ->addColumn('depreciation_stopped_display', function($a) {
                if ($a->depreciation_stopped) {
                    return '<span class="badge bg-danger">Stopped</span>';
                }
                return '-';
            })
            ->addColumn('id_hashed', fn($a) => Hashids::encode($a->id))
            ->addColumn('source_view_url', function($a) {
                if ($a->source_type === 'fleet_tyre' && $a->source_id) {
                    return route('fleet.tyres.show', Hashids::encode($a->source_id));
                }
                if ($a->source_type === 'fleet_spare_part_category' && $a->source_id) {
                    return route('fleet.spare-part-categories.show', Hashids::encode($a->source_id));
                }
                return null;
            })
            ->addColumn('source_edit_url', function($a) {
                if ($a->source_type === 'fleet_tyre' && $a->source_id) {
                    return route('fleet.tyres.edit', Hashids::encode($a->source_id));
                }
                if ($a->source_type === 'fleet_spare_part_category' && $a->source_id) {
                    return route('fleet.spare-part-categories.edit', Hashids::encode($a->source_id));
                }
                return null;
            })
            ->rawColumns(['tax_class_display', 'hfs_status_display', 'depreciation_stopped_display'])
            ->make(true);
    }

    public function show($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $asset = Asset::with(['category'])->findOrFail($decodedId);
        $encodedId = Hashids::encode($asset->id);
        return view('assets.registry.show', compact('asset', 'encodedId'));
    }

    public function depreciationHistory($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $asset = Asset::with(['category'])->findOrFail($decodedId);
        
        // Get summary statistics
        $depreciations = AssetDepreciation::where('asset_id', $asset->id)
            ->where('company_id', $asset->company_id)
            ->get();
        
        $summary = [
            'total_depreciation' => $depreciations->sum('depreciation_amount'),
            'opening_balances' => $depreciations->where('type', 'opening_balance')->count(),
            'regular_depreciations' => $depreciations->where('type', 'depreciation')->count(),
        ];
        
        $encodedId = Hashids::encode($asset->id);
        return view('assets.registry.depreciation-history', compact('asset', 'encodedId', 'summary'));
    }

    public function depreciationHistoryData($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $asset = Asset::findOrFail($decodedId);
        
        $query = AssetDepreciation::where('asset_id', $asset->id)
            ->where('company_id', $asset->company_id)
            ->with(['assetOpening'])
            ->orderBy('depreciation_date', 'asc')
            ->orderBy('created_at', 'asc');

        return DataTables::of($query)
            ->editColumn('depreciation_date', function($depr) {
                return optional($depr->depreciation_date)->format('d M Y') ?? '-';
            })
            ->editColumn('type', function($depr) {
                $typeBadge = [
                    'opening_balance' => ['bg-info', 'Opening Balance'],
                    'depreciation' => ['bg-warning text-dark', 'Depreciation'],
                    'adjustment' => ['bg-secondary', 'Adjustment'],
                    'disposal' => ['bg-danger', 'Disposal'],
                ];
                $badge = $typeBadge[$depr->type] ?? ['bg-secondary', ucfirst($depr->type)];
                
                $html = '<span class="badge ' . $badge[0] . '">' . $badge[1] . '</span>';
                
                if ($depr->asset_opening_id && $depr->assetOpening) {
                    $openingId = Hashids::encode($depr->assetOpening->id);
                    $html .= '<br><small class="text-muted">';
                    $html .= '<a href="' . route('assets.openings.show', $openingId) . '" class="text-muted text-decoration-underline">';
                    $html .= 'Opening ID: ' . $depr->assetOpening->id;
                    $html .= '</a></small>';
                }
                
                return $html;
            })
            ->editColumn('description', function($depr) {
                $html = e($depr->description);
                
                if ($depr->asset_opening_id && $depr->assetOpening) {
                    $openingId = Hashids::encode($depr->assetOpening->id);
                    $html .= '<br><small class="text-info">';
                    $html .= '<i class="bx bx-link"></i> ';
                    $html .= '<a href="' . route('assets.openings.show', $openingId) . '" class="text-info text-decoration-underline">';
                    $html .= 'Linked to opening balance';
                    $html .= '</a></small>';
                }
                
                return $html;
            })
            ->editColumn('book_value_before', function($depr) {
                return 'TZS ' . number_format($depr->book_value_before, 2);
            })
            ->editColumn('depreciation_amount', function($depr) {
                if ($depr->depreciation_amount > 0) {
                    return '<span class="text-danger">-' . number_format($depr->depreciation_amount, 2) . '</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->editColumn('accumulated_depreciation', function($depr) {
                return '<span class="fw-semibold text-danger">' . number_format($depr->accumulated_depreciation, 2) . '</span>';
            })
            ->editColumn('book_value_after', function($depr) {
                return '<span class="fw-semibold text-primary">' . number_format($depr->book_value_after, 2) . '</span>';
            })
            ->editColumn('gl_posted', function($depr) {
                if ($depr->gl_posted) {
                    return '<span class="badge bg-success">Posted</span>';
                }
                return '<span class="badge bg-secondary">Not Posted</span>';
            })
            ->rawColumns(['type', 'description', 'depreciation_amount', 'accumulated_depreciation', 'book_value_after', 'gl_posted'])
            ->make(true);
    }

    public function edit($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $asset = Asset::findOrFail($decodedId);
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        
        $categories = AssetCategory::orderBy('name')->get(['id','name']);
        $taxClasses = $this->getTaxDepreciationClassesForCompany($user->company_id);

        // Load departments filtered by session branch_id
        $departments = \App\Models\Hr\Department::where('company_id', $user->company_id)
            ->when($branchId, function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->orderBy('name')
            ->get(['id','name']);

        return view('assets.registry.edit', compact('asset','categories','departments', 'taxClasses'));
    }

    public function update(Request $request, $id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $asset = Asset::findOrFail($decodedId);
        $user = Auth::user();

        $validated = $request->validate([
            'asset_category_id' => 'required|exists:asset_categories,id',
            'tax_class_id' => 'nullable|exists:tax_depreciation_classes,id',
            'code' => 'required|string|max:50|unique:assets,code,'.$asset->id,
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

        $asset->update(array_merge($validated, [ 'updated_by' => $user->id ]));

        if ($request->hasFile('attachments')) {
            $paths = [];
            foreach ($request->file('attachments') as $file) {
                $paths[] = $file->store('assets/attachments', 'public');
            }
            $asset->update(['attachments' => json_encode($paths)]);
        }

        return redirect()->route('assets.registry.show', Hashids::encode($asset->id))->with('success', 'Asset updated successfully.');
    }

    public function destroy($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $asset = Asset::findOrFail($decodedId);
        $asset->delete();
        return redirect()->route('assets.registry.index')->with('success', 'Asset deleted successfully.');
    }
    public function store(Request $request)
    {
        $user = Auth::user();
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
        if (empty($payload['code'])) {
            $payload['code'] = 'TMP-' . Str::uuid()->toString();
        }

        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        
        $asset = Asset::create(array_merge($payload, [
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'tax_class_id' => $request->input('tax_class_id'),
            'created_by' => $user->id,
        ]));

        // Handle auto code/tag/barcode generation
        $updates = [];
        // Set final standardized code after create using Asset Code Format setting
        // Supported tokens: {YYYY}, {YY}, {MM}, {DD}, {SEQ}
        $format = SystemSetting::where('key', 'asset_code_format')->value('value') ?? 'AST-{YYYY}-{SEQ}';
        $seq = str_pad((string) $asset->id, 6, '0', STR_PAD_LEFT);
        $date = $asset->capitalization_date ? Carbon::parse($asset->capitalization_date) : now();
        $replacements = [
            '{YYYY}' => $date->format('Y'),
            '{YY}' => $date->format('y'),
            '{MM}' => $date->format('m'),
            '{DD}' => $date->format('d'),
            '{SEQ}' => $seq,
        ];
        $finalCode = strtr($format, $replacements);
        if (empty($asset->code) || Str::startsWith($asset->code, 'TMP-')) {
            $updates['code'] = $finalCode;
        }
        if (empty($asset->tag)) {
            $updates['tag'] = $updates['code'] ?? ('AST-' . str_pad($asset->id, 6, '0', STR_PAD_LEFT));
        }
        if (empty($asset->barcode)) {
            $updates['barcode'] = $updates['tag'] ?? $asset->tag ?? ('AST-' . str_pad($asset->id, 6, '0', STR_PAD_LEFT));
        }
        if (!empty($updates)) {
            $asset->update($updates);
        }

        // Handle attachments upload (store paths in JSON)
        if ($request->hasFile('attachments')) {
            $paths = [];
            foreach ($request->file('attachments') as $file) {
                $paths[] = $file->store('assets/attachments', 'public');
            }
            $asset->update(['attachments' => json_encode($paths)]);
        }

        // Respond for web form submissions by default
        if (!$request->wantsJson()) {
            return redirect()->route('assets.registry.index')->with('success', 'Asset saved successfully.');
        }
        return response()->json(['success' => true, 'asset' => $asset]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'nullable|file|mimes:csv,txt|max:10240',
            'csv_file' => 'nullable|file|mimes:csv,txt|max:10240',
            'category_id' => 'required|exists:asset_categories,id',
            'department_id' => 'nullable|exists:hr_departments,id',
        ]);

        try {
            $uploaded = $request->file('csv_file') ?: $request->file('file');
            if (!$uploaded) {
                return response()->json(['success' => false, 'message' => 'Please upload a CSV file.'], 422);
            }
            $path = $uploaded->getRealPath();
            $rows = array_map('str_getcsv', file($path));
            if (empty($rows) || count($rows) < 2) {
                return response()->json(['success' => false, 'message' => 'The CSV file is empty.'], 422);
            }
            $header = array_map(fn($h) => strtolower(trim($h)), array_shift($rows));

            $required = ['name'];
            foreach ($required as $col) {
                if (!in_array($col, $header)) {
                    return response()->json(['success' => false, 'message' => "Missing required column: {$col}"], 422);
                }
            }

            $imported = 0;
            $errors = [];
            $user = Auth::user();
            $branchId = session('branch_id') ?? $user->branch_id ?? null;

            // Default category priority: UI-selected > first resolved from CSV > first in DB
            $uiDefaultCategoryId = (int) $request->category_id;
            $dbDefaultCategoryId = AssetCategory::orderBy('id')->value('id');
            $firstResolvedCategoryId = null;

            // Default department priority: UI-selected > first resolved from CSV
            $uiDefaultDepartmentId = $request->filled('department_id') ? (int) $request->department_id : null;
            $firstResolvedDepartmentId = null;

            foreach ($rows as $rowIndex => $row) {
                if (count($row) !== count($header)) {
                    $row = array_pad($row, count($header), null);
                }
                $data = array_combine($header, $row);

                if (empty(trim($data['name'] ?? ''))) {
                    continue;
                }

                // Resolve category by name (first non-empty used as default for subsequent rows)
                $categoryId = null;
                $categoryName = isset($data['category_name']) ? trim($data['category_name']) : '';
                if ($categoryName !== '') {
                    $category = AssetCategory::where('name', $categoryName)->first();
                    if ($category) {
                        $categoryId = $category->id;
                        if (!$firstResolvedCategoryId) {
                            $firstResolvedCategoryId = $categoryId;
                        }
                    }
                }
                if (!$categoryId) {
                    $categoryId = $firstResolvedCategoryId ?: $uiDefaultCategoryId ?: $dbDefaultCategoryId;
                }

                // Resolve department by name (first non-empty used as default for subsequent rows)
                $departmentId = null;
                $departmentName = isset($data['department_name']) ? trim($data['department_name']) : '';
                if ($departmentName !== '') {
                    $department = \App\Models\Hr\Department::where('company_id', $user->company_id)
                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                        ->where('name', $departmentName)
                        ->first();
                    if ($department) {
                        $departmentId = $department->id;
                        if (!$firstResolvedDepartmentId) {
                            $firstResolvedDepartmentId = $departmentId;
                        }
                    }
                }
                if (!$departmentId) {
                    $departmentId = $firstResolvedDepartmentId ?: $uiDefaultDepartmentId;
                }

                // Parse tax class code from CSV (prefer 'tax_pool_class' or 'tax_class_code', fallback to 'class')
                $csvTaxClassCode = trim($data['tax_pool_class'] ?? $data['tax_class_code'] ?? ($data['class'] ?? '')) ?: null;
                
                // Map tax class code to tax_class_id
                $taxClassId = null;
                if ($csvTaxClassCode) {
                    $taxClass = \App\Models\Assets\TaxDepreciationClass::where('class_code', $csvTaxClassCode)->first();
                    if ($taxClass) {
                        $taxClassId = $taxClass->id;
                    }
                }

                // Parse dates and numerics safely
                $purchaseDate = !empty($data['purchase_date']) ? Carbon::parse($data['purchase_date']) : null;
                $capDate = !empty($data['capitalization_date']) ? Carbon::parse($data['capitalization_date']) : null;
                $purchaseCost = isset($data['purchase_cost']) && is_numeric($data['purchase_cost']) ? (float)$data['purchase_cost'] : 0;
                $salvageValue = isset($data['salvage_value']) && is_numeric($data['salvage_value']) ? (float)$data['salvage_value'] : 0;
                $status = !empty($data['status']) ? trim($data['status']) : 'active';

                // Ensure a provisional unique code if missing
                $providedCode = !empty($data['code']) ? trim($data['code']) : null;
                $provisionalCode = $providedCode ?: ('TMP-' . Str::uuid()->toString());

                try {
                    $asset = Asset::create([
                        'company_id' => $user->company_id,
                        'branch_id' => $branchId,
                        'asset_category_id' => $categoryId,
                        'department_id' => $departmentId,
                        'tax_class_id' => $taxClassId,
                        'name' => trim($data['name']),
                        'code' => $provisionalCode,
                        'model' => $data['model'] ?? null,
                        'manufacturer' => $data['manufacturer'] ?? null,
                        'purchase_date' => $purchaseDate,
                        'capitalization_date' => $capDate,
                        'purchase_cost' => $purchaseCost,
                        'salvage_value' => $salvageValue,
                        'serial_number' => $data['serial_number'] ?? null,
                        'location' => $data['location'] ?? null,
                        'status' => $status,
                        'created_by' => $user->id,
                    ]);
                } catch (\Exception $e) {
                    // Retry once with a forced unique TMP code if duplicate code or similar integrity error
                    try {
                        $asset = Asset::create([
                            'company_id' => $user->company_id,
                            'branch_id' => $branchId,
                            'asset_category_id' => $categoryId,
                            'department_id' => $departmentId,
                            'tax_class_id' => $taxClassId,
                            'name' => trim($data['name']),
                            'code' => 'TMP-' . uniqid(),
                            'model' => $data['model'] ?? null,
                            'manufacturer' => $data['manufacturer'] ?? null,
                            'purchase_date' => $purchaseDate,
                            'capitalization_date' => $capDate,
                            'purchase_cost' => $purchaseCost,
                            'salvage_value' => $salvageValue,
                            'serial_number' => $data['serial_number'] ?? null,
                            'location' => $data['location'] ?? null,
                            'status' => $status,
                            'created_by' => $user->id,
                        ]);
                    } catch (\Exception $e2) {
                        $errors[] = 'Row ' . ($rowIndex + 2) . ': ' . $e2->getMessage();
                        continue;
                    }
                }

                $updates = [];
                $format = SystemSetting::where('key', 'asset_code_format')->value('value') ?? 'AST-{YYYY}-{SEQ}';
                $seq = str_pad((string) $asset->id, 6, '0', STR_PAD_LEFT);
                $date = $asset->capitalization_date ? Carbon::parse($asset->capitalization_date) : now();
                $finalCode = strtr($format, [
                    '{YYYY}' => $date->format('Y'),
                    '{YY}' => $date->format('y'),
                    '{MM}' => $date->format('m'),
                    '{DD}' => $date->format('d'),
                    '{SEQ}' => $seq,
                ]);
                if (empty($asset->code) || Str::startsWith($asset->code, 'TMP-')) {
                    $updates['code'] = $finalCode;
                }
                if (empty($asset->tag)) {
                    $updates['tag'] = $updates['code'] ?? ('AST-' . str_pad($asset->id, 6, '0', STR_PAD_LEFT));
                }
                if (empty($asset->barcode)) {
                    $updates['barcode'] = $updates['tag'] ?? $asset->tag ?? ('AST-' . str_pad($asset->id, 6, '0', STR_PAD_LEFT));
                }
                if (!empty($updates)) {
                    $asset->update($updates);
                }

                $imported++;
            }

            $message = "Successfully imported {$imported} assets.";
            if (!empty($errors)) {
                $message .= ' ' . count($errors) . ' errors occurred.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'imported' => $imported,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="assets_template.csv"',
        ];

        $columns = [
            'name',
            'code',
            'category_name',
            'tax_pool_class',
            'model',
            'manufacturer',
            'capitalization_date',
            'salvage_value',
            'serial_number',
            'location',
            'status'
        ];

        $sampleData = [
            ['HP ProBook 450 G8', 'HP-PB450G8', 'Computers', 'Class 1', 'ProBook 450 G8', 'HP', '2025-01-15', '150000', 'SN12345', 'Head Office', 'active'],
            ['Toyota Hilux', 'TY-HILUX-01', 'Motor Vehicles', 'Class 2', 'Hilux', 'Toyota', '2024-11-30', '5000000', 'VIN98765', 'Warehouse', 'active'],
            ['Office Desk', 'OF-DSK-01', 'Furniture', 'Class 3', 'N/A', 'N/A', '2025-02-28', '0', '', 'Main Office', 'active'],
        ];

        $callback = function () use ($columns, $sampleData) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);
            foreach ($sampleData as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get TRA tax depreciation classes for the company (global + company-specific).
     * If the table is empty, runs the seeder directly so the dropdown is usable.
     */
    private function getTaxDepreciationClassesForCompany(?int $companyId)
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('tax_depreciation_classes')) {
            return collect([]);
        }

        $taxClasses = \App\Models\Assets\TaxDepreciationClass::active()
            ->forCompany($companyId)
            ->orderBy('sort_order')
            ->orderBy('class_code')
            ->get(['id', 'class_code', 'description']);

        if ($taxClasses->isEmpty()) {
            try {
                if (\App\Models\Assets\TaxDepreciationClass::count() === 0) {
                    (new \Database\Seeders\TaxDepreciationClassSeeder())->run();
                }
                $taxClasses = \App\Models\Assets\TaxDepreciationClass::active()
                    ->forCompany($companyId)
                    ->orderBy('sort_order')
                    ->orderBy('class_code')
                    ->get(['id', 'class_code', 'description']);
            } catch (\Throwable $e) {
                // If seeder fails, continue with empty or try without company filter
            }
            if ($taxClasses->isEmpty()) {
                $taxClasses = \App\Models\Assets\TaxDepreciationClass::active()
                    ->orderBy('sort_order')
                    ->orderBy('class_code')
                    ->get(['id', 'class_code', 'description']);
            }
        }

        return $taxClasses;
    }
}


