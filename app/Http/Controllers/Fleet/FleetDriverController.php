<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetDriver;
use App\Models\Assets\Asset;
use App\Models\BankAccount;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class FleetDriverController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $vehiclesForAssign = Asset::where('company_id', $user->company_id)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number', 'operational_status']);

        // Calculate dashboard statistics
        $driverQuery = FleetDriver::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        $totalDrivers = $driverQuery->count();
        $activeDrivers = (clone $driverQuery)->where('status', 'active')->count();
        $assignedDrivers = (clone $driverQuery)->whereNotNull('assigned_vehicle_id')->count();
        $availableDrivers = (clone $driverQuery)->where('status', 'active')->whereNull('assigned_vehicle_id')->count();

        return view('fleet.drivers.index', compact('vehiclesForAssign', 'totalDrivers', 'activeDrivers', 'assignedDrivers', 'availableDrivers'));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $query = FleetDriver::query()
            ->where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['user', 'assignedVehicle', 'fuelCardAccount', 'branch']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }

        if ($request->filled('license_valid')) {
            if ($request->license_valid == '1') {
                $query->where('license_expiry_date', '>', now());
            } else {
                $query->where('license_expiry_date', '<=', now());
            }
        }

        return DataTables::of($query)
            ->editColumn('license_expiry_date', fn($d) => optional($d->license_expiry_date)->format('Y-m-d'))
            ->addColumn('status_display', function($d) {
                $statusColors = [
                    'active' => 'success',
                    'inactive' => 'secondary',
                    'suspended' => 'warning',
                    'terminated' => 'danger',
                ];
                $color = $statusColors[$d->status] ?? 'secondary';
                return '<span class="badge bg-' . $color . '">' . ucfirst($d->status) . '</span>';
            })
            ->addColumn('license_status', function($d) {
                if (!$d->license_expiry_date) {
                    return '<span class="badge bg-secondary">N/A</span>';
                }
                if ($d->license_expiry_date->isPast()) {
                    return '<span class="badge bg-danger">Expired</span>';
                } elseif ($d->license_expiry_date->isBefore(now()->addDays(30))) {
                    return '<span class="badge bg-warning">Expiring Soon</span>';
                }
                return '<span class="badge bg-success">Valid</span>';
            })
            ->addColumn('assigned_vehicle_display', function($d) {
                if ($d->assignedVehicle) {
                    return $d->assignedVehicle->name . ' (' . ($d->assignedVehicle->registration_number ?? 'N/A') . ')';
                }
                return '<span class="text-muted">Not Assigned</span>';
            })
            ->addColumn('assigned_card_display', function($d) {
                if ($d->fuelCardAccount) {
                    $name = e($d->fuelCardAccount->name);
                    $num = $d->fuelCardAccount->account_number ? ' (' . e($d->fuelCardAccount->account_number) . ')' : '';
                    return '<span class="badge bg-info">' . $name . $num . '</span>';
                }
                return '<span class="text-muted">—</span>';
            })
            ->addColumn('actions', function($d) {
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<a href="' . route('fleet.drivers.show', $d->hash_id) . '" class="btn btn-sm btn-outline-info" title="View"><i class="bx bx-show"></i></a>';
                $actions .= '<a href="' . route('fleet.drivers.edit', $d->hash_id) . '" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                $actions .= '<button type="button" class="btn btn-sm btn-outline-success assign-driver-to-vehicle" title="Assign to vehicle" data-driver-id="' . e($d->hash_id) . '" data-driver-name="' . e($d->full_name) . '" data-vehicle-id="' . e($d->assigned_vehicle_id ?? '') . '" data-start="' . ($d->assignment_start_date ? $d->assignment_start_date->format('Y-m-d') : '') . '" data-end="' . ($d->assignment_end_date ? $d->assignment_end_date->format('Y-m-d') : '') . '"><i class="bx bx-car"></i></button>';
                $actions .= '<button type="button" class="btn btn-sm btn-outline-warning assign-driver-card" title="Assign card" data-driver-id="' . e($d->hash_id) . '" data-driver-name="' . e($d->full_name) . '" data-card-id="' . e($d->fuel_card_bank_account_id ?? '') . '"><i class="bx bx-credit-card"></i></button>';
                // Status change dropdown
                $actions .= '<div class="btn-group" role="group">';
                $actions .= '<button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Change Status"><i class="bx bx-transfer"></i></button>';
                $actions .= '<ul class="dropdown-menu">';
                if ($d->status !== 'active') {
                    $actions .= '<li><a class="dropdown-item change-driver-status" href="#" data-driver-id="' . $d->hash_id . '" data-status="active"><i class="bx bx-check-circle text-success me-1"></i>Set Active</a></li>';
                }
                if ($d->status !== 'inactive') {
                    $actions .= '<li><a class="dropdown-item change-driver-status" href="#" data-driver-id="' . $d->hash_id . '" data-status="inactive"><i class="bx bx-x-circle text-secondary me-1"></i>Set Inactive</a></li>';
                }
                if ($d->status !== 'suspended') {
                    $actions .= '<li><a class="dropdown-item change-driver-status" href="#" data-driver-id="' . $d->hash_id . '" data-status="suspended"><i class="bx bx-error text-warning me-1"></i>Set Suspended</a></li>';
                }
                if ($d->status !== 'terminated') {
                    $actions .= '<li><a class="dropdown-item change-driver-status" href="#" data-driver-id="' . $d->hash_id . '" data-status="terminated"><i class="bx bx-block text-danger me-1"></i>Set Terminated</a></li>';
                }
                $actions .= '</ul>';
                $actions .= '</div>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status_display', 'license_status', 'assigned_vehicle_display', 'assigned_card_display', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $users = collect([]); // User/Employee dropdown removed from form

        // Get available vehicles (show all vehicles, not just available ones)
        $vehicles = Asset::where('company_id', $user->company_id)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['assignedDriver' => function($q) {
                $q->select('id', 'assigned_vehicle_id', 'full_name', 'assignment_start_date', 'assignment_end_date', 'status');
            }])
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number', 'operational_status']);

        return view('fleet.drivers.create', compact('users', 'vehicles'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'license_number' => 'required|string|max:100|unique:fleet_drivers,license_number,NULL,id,company_id,' . $user->company_id,
            'license_class' => 'nullable|string|max:50',
            'license_expiry_date' => 'required|date',
            'license_issuing_authority' => 'nullable|string|max:100',
            'employment_type' => 'required|in:employee,contractor',
            'daily_allowance_rate' => 'nullable|numeric|min:0',
            'overtime_rate' => 'nullable|numeric|min:0',
            'salary' => 'nullable|numeric|min:0',
            'phone_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'emergency_contact_relationship' => 'nullable|string',
            'last_training_date' => 'nullable|date',
            'next_training_due_date' => 'nullable|date',
            'assigned_vehicle_id' => 'nullable|exists:assets,id',
            'assignment_start_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,suspended,terminated',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Auto-generate unique driver code
            $validated['driver_code'] = 'DRV-' . strtoupper(Str::random(6));

            $driver = FleetDriver::create(array_merge($validated, [
                'company_id' => $user->company_id,
                'branch_id' => $branchId,
                'created_by' => $user->id,
            ]));

            // Create user account so driver appears in Settings > User list (like employees)
            $driverEmail = !empty($validated['email']) ? trim($validated['email']) : null;
            if ($driverEmail && User::where('company_id', $user->company_id)->where('email', $driverEmail)->exists()) {
                $driverEmail = null; // use placeholder if email already taken
            }
            if (!$driverEmail) {
                $driverEmail = 'driver' . $driver->id . '@company' . $user->company_id . '.local';
            }

            $newUser = User::create([
                'name' => $validated['full_name'],
                'email' => $driverEmail,
                'phone' => $validated['phone_number'] ?? null,
                'password' => Hash::make(Str::random(16)),
                'company_id' => $user->company_id,
                'status' => $validated['status'] === 'active' ? 'active' : 'inactive',
                'is_active' => $validated['status'] === 'active' ? 'yes' : 'no',
            ]);

            $driverRole = Role::where('guard_name', 'web')->where('name', 'driver')->first();
            if (!$driverRole) {
                $driverRole = Role::where('guard_name', 'web')->where('name', 'employee')->first();
            }
            if (!$driverRole) {
                $driverRole = Role::where('guard_name', 'web')->where('name', '!=', 'super-admin')->orderBy('id')->first();
            }
            if ($driverRole) {
                $newUser->assignRole($driverRole);
            }

            if ($branchId) {
                $newUser->branches()->attach($branchId);
            }

            $driver->update(['user_id' => $newUser->id]);

            DB::commit();
            return redirect()->route('fleet.drivers.index')->with('success', 'Driver created successfully. A user account has been created so the driver appears in Settings > Users.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Fleet driver creation failed: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to create driver: ' . $e->getMessage()])->withInput($request->except(['password']));
        }
    }

    public function show(FleetDriver $driver)
    {
        $user = Auth::user();
        $driver->load('fuelCardAccount');

        // Ensure the driver belongs to the user's company
        if ($driver->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this driver.');
        }

        $driver->load(['user', 'assignedVehicle', 'branch', 'createdBy', 'updatedBy']);

        // Get vehicle assignment history from audit logs
        // Check if activity_log table exists and has correct structure
        $assignmentHistory = collect();
        $vehicles = collect();
        
        try {
            // Try to get from activity_log table (Spatie Activity Log)
            if (\Schema::hasTable('activity_log')) {
                $logs = \DB::table('activity_log')
                    ->where('log_name', 'default')
                    ->where('subject_type', 'App\\Models\\Fleet\\FleetDriver')
                    ->where('subject_id', $driver->id)
                    ->whereNotNull('properties')
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                foreach ($logs as $log) {
                    $properties = json_decode($log->properties, true);
                    $attributes = $properties['attributes'] ?? [];
                    $old = $properties['old'] ?? [];
                    
                    // Only include logs where vehicle assignment changed
                    if (isset($attributes['assigned_vehicle_id']) || isset($old['assigned_vehicle_id'])) {
                        $assignmentHistory->push([
                            'vehicle_id' => $attributes['assigned_vehicle_id'] ?? $old['assigned_vehicle_id'] ?? null,
                            'old_vehicle_id' => $old['assigned_vehicle_id'] ?? null,
                            'start_date' => isset($attributes['assignment_start_date']) ? \Carbon\Carbon::parse($attributes['assignment_start_date']) : null,
                            'end_date' => isset($attributes['assignment_end_date']) ? \Carbon\Carbon::parse($attributes['assignment_end_date']) : null,
                            'changed_at' => \Carbon\Carbon::parse($log->created_at),
                            'description' => $log->description ?? 'updated',
                        ]);
                    }
                }
                
                // Load vehicles for the history
                $vehicleIds = $assignmentHistory->pluck('vehicle_id')->filter()->unique();
                if ($vehicleIds->count() > 0) {
                    $vehicles = \App\Models\Assets\Asset::whereIn('id', $vehicleIds)->get()->keyBy('id');
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Could not fetch assignment history: ' . $e->getMessage());
        }
        
        // If no history from logs, create a simple entry from current assignment
        if ($assignmentHistory->isEmpty() && $driver->assigned_vehicle_id) {
            $assignmentHistory->push([
                'vehicle_id' => $driver->assigned_vehicle_id,
                'old_vehicle_id' => null,
                'start_date' => $driver->assignment_start_date,
                'end_date' => $driver->assignment_end_date,
                'changed_at' => $driver->updated_at,
                'description' => 'Current Assignment',
            ]);
            
            if ($driver->assignedVehicle) {
                $vehicles->put($driver->assigned_vehicle_id, $driver->assignedVehicle);
            }
        }

        return view('fleet.drivers.show', compact('driver', 'assignmentHistory', 'vehicles'));
    }

    public function edit(FleetDriver $driver)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        // Ensure the driver belongs to the user's company
        if ($driver->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this driver.');
        }

        // Get available users/employees
        // Note: Users are linked to branches via branch_user pivot table, not direct branch_id
        $users = User::where('company_id', $user->company_id)
            ->when($branchId, function($q) use ($branchId) {
                $q->whereHas('branches', function($query) use ($branchId) {
                    $query->where('branches.id', $branchId);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Get available vehicles (show all vehicles, not just available ones)
        $vehicles = Asset::where('company_id', $user->company_id)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['assignedDriver' => function($q) use ($driver) {
                $q->select('id', 'assigned_vehicle_id', 'full_name', 'assignment_start_date', 'assignment_end_date', 'status')
                  ->where('id', '!=', $driver->id); // Exclude current driver being edited
            }])
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number', 'operational_status']);

        return view('fleet.drivers.edit', compact('driver', 'users', 'vehicles'));
    }

    public function update(Request $request, FleetDriver $driver)
    {
        $user = Auth::user();

        // Ensure the driver belongs to the user's company
        if ($driver->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this driver.');
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'license_number' => 'required|string|max:100|unique:fleet_drivers,license_number,' . $driver->id . ',id,company_id,' . $user->company_id,
            'license_class' => 'nullable|string|max:50',
            'license_expiry_date' => 'required|date',
            'license_issuing_authority' => 'nullable|string|max:100',
            'employment_type' => 'required|in:employee,contractor',
            'daily_allowance_rate' => 'nullable|numeric|min:0',
            'overtime_rate' => 'nullable|numeric|min:0',
            'salary' => 'nullable|numeric|min:0',
            'phone_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'emergency_contact_relationship' => 'nullable|string',
            'last_training_date' => 'nullable|date',
            'next_training_due_date' => 'nullable|date',
            'assigned_vehicle_id' => 'nullable|exists:assets,id',
            'assignment_start_date' => 'nullable|date',
            'assignment_end_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,suspended,terminated',
            'notes' => 'nullable|string',
        ]);

        $driver->update(array_merge($validated, [
            'updated_by' => $user->id,
        ]));

        return redirect()->route('fleet.drivers.show', $driver->hash_id)->with('success', 'Driver updated successfully.');
    }

    public function destroy(FleetDriver $driver)
    {
        $user = Auth::user();

        // Ensure the driver belongs to the user's company
        if ($driver->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this driver.');
        }

        // Check if driver has active trips
        if ($driver->trips()->whereIn('status', ['planned', 'in_progress'])->exists()) {
            return redirect()->route('fleet.drivers.index')
                ->with('error', 'Cannot delete driver with active trips.');
        }

        $driver->delete();

        return redirect()->route('fleet.drivers.index')->with('success', 'Driver deleted successfully.');
    }

    public function changeStatus(Request $request, FleetDriver $driver)
    {
        $user = Auth::user();

        // Ensure the driver belongs to the user's company
        if ($driver->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this driver.');
        }

        $validated = $request->validate([
            'status' => 'required|in:active,inactive,suspended,terminated',
        ]);

        $driver->update([
            'status' => $validated['status'],
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Driver status updated to ' . ucfirst($validated['status']),
        ]);
    }

    public function downloadSample()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\FleetDriverSampleExport(),
            'fleet_drivers_import_sample.xlsx'
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        try {
            $uploadedFile = $request->file('import_file');
            $extension = strtolower($uploadedFile->getClientOriginalExtension());
            $mimeType = $uploadedFile->getMimeType();

            if (in_array($mimeType, ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])) {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($uploadedFile->getRealPath());
                $rows = $spreadsheet->getActiveSheet()->toArray();
            } else {
                $rows = array_map('str_getcsv', file($uploadedFile->getRealPath()));
            }

            if (empty($rows) || count($rows) < 2) {
                return response()->json(['success' => false, 'message' => 'The file is empty or contains no data rows.'], 422);
            }

            $header = array_map(fn($h) => strtolower(trim((string) $h)), array_shift($rows));
            // Same required fields as create form: full_name, license_number, license_expiry_date, employment_type, status
            $required = ['full_name', 'license_number', 'license_expiry_date', 'employment_type', 'status'];
            foreach ($required as $col) {
                if (!in_array($col, $header)) {
                    return response()->json(['success' => false, 'message' => "Missing required column: {$col}"], 422);
                }
            }

            $imported = 0;
            $errors = [];

            foreach ($rows as $rowIndex => $row) {
                $row = array_pad(is_array($row) ? $row : [], count($header), null);
                $data = array_combine($header, $row);
                $fullName = trim($data['full_name'] ?? '');
                if ($fullName === '') {
                    continue;
                }
                $licenseNumber = trim($data['license_number'] ?? '');
                $licenseExpiry = $data['license_expiry_date'] ?? null;
                if ($licenseNumber === '' || $licenseExpiry === '' || $licenseExpiry === null) {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': license_number and license_expiry_date are required.';
                    continue;
                }
                $employmentType = isset($data['employment_type']) ? strtolower(trim($data['employment_type'] ?? '')) : '';
                $status = isset($data['status']) ? strtolower(trim($data['status'] ?? '')) : '';
                if (!in_array($employmentType, ['employee', 'contractor'])) {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': employment_type is required and must be employee or contractor.';
                    continue;
                }
                if (!in_array($status, ['active', 'inactive', 'suspended', 'terminated'])) {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': status is required and must be active, inactive, suspended, or terminated.';
                    continue;
                }
                if (FleetDriver::where('company_id', $user->company_id)->where('license_number', $licenseNumber)->exists()) {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': License number already exists.';
                    continue;
                }

                try {
                    $licenseExpiryDate = \Carbon\Carbon::parse($licenseExpiry);
                } catch (\Exception $e) {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': Invalid license_expiry_date. Use YYYY-MM-DD.';
                    continue;
                }

                DB::beginTransaction();
                try {
                    $driver = FleetDriver::create([
                        'company_id' => $user->company_id,
                        'branch_id' => $branchId,
                        'driver_code' => 'DRV-' . strtoupper(Str::random(6)),
                        'full_name' => $fullName,
                        'license_number' => $licenseNumber,
                        'license_class' => trim($data['license_class'] ?? '') ?: null,
                        'license_expiry_date' => $licenseExpiryDate,
                        'license_issuing_authority' => trim($data['license_issuing_authority'] ?? '') ?: null,
                        'employment_type' => $employmentType,
                        'phone_number' => trim($data['phone_number'] ?? '') ?: null,
                        'email' => trim($data['email'] ?? '') ?: null,
                        'address' => trim($data['address'] ?? '') ?: null,
                        'emergency_contact_name' => trim($data['emergency_contact_name'] ?? '') ?: null,
                        'emergency_contact_phone' => trim($data['emergency_contact_phone'] ?? '') ?: null,
                        'emergency_contact_relationship' => trim($data['emergency_contact_relationship'] ?? '') ?: null,
                        'status' => $status,
                        'created_by' => $user->id,
                    ]);

                    $driverEmail = $driver->email;
                    if ($driverEmail && User::where('company_id', $user->company_id)->where('email', $driverEmail)->exists()) {
                        $driverEmail = null;
                    }
                    if (!$driverEmail) {
                        $driverEmail = 'driver' . $driver->id . '@company' . $user->company_id . '.local';
                    }

                    $newUser = User::create([
                        'name' => $driver->full_name,
                        'email' => $driverEmail,
                        'phone' => $driver->phone_number,
                        'password' => Hash::make(Str::random(16)),
                        'company_id' => $user->company_id,
                        'status' => $status === 'active' ? 'active' : 'inactive',
                        'is_active' => $status === 'active' ? 'yes' : 'no',
                    ]);

                    $driverRole = Role::where('guard_name', 'web')->where('name', 'driver')->first()
                        ?? Role::where('guard_name', 'web')->where('name', 'employee')->first()
                        ?? Role::where('guard_name', 'web')->where('name', '!=', 'super-admin')->orderBy('id')->first();
                    if ($driverRole) {
                        $newUser->assignRole($driverRole);
                    }
                    if ($branchId) {
                        $newUser->branches()->attach($branchId);
                    }
                    $driver->update(['user_id' => $newUser->id]);

                    DB::commit();
                    $imported++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': ' . $e->getMessage();
                }
            }

            $message = "Successfully imported {$imported} driver(s).";
            if (!empty($errors)) {
                $message .= ' ' . count($errors) . ' error(s) occurred.';
            }
            return response()->json([
                'success' => true,
                'message' => $message,
                'imported' => $imported,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    public function assignVehicle(Request $request, FleetDriver $driver)
    {
        $user = Auth::user();
        if ($driver->company_id !== $user->company_id) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'assigned_vehicle_id' => 'nullable|exists:assets,id',
            'assignment_start_date' => 'nullable|date',
            'assignment_end_date' => 'nullable|date|after_or_equal:assignment_start_date',
        ]);

        $assignedVehicleId = $validated['assigned_vehicle_id'] ?? null;
        if ($assignedVehicleId === '' || $assignedVehicleId === false) {
            $assignedVehicleId = null;
        }

        $vehicleCategoryId = \App\Models\Assets\AssetCategory::where('code', 'FA04')->where('company_id', $user->company_id)->value('id');
        if ($assignedVehicleId) {
            $asset = Asset::where('id', $assignedVehicleId)
                ->where('company_id', $user->company_id)
                ->where('asset_category_id', $vehicleCategoryId)
                ->first();
            if (!$asset) {
                return response()->json(['success' => false, 'message' => 'Invalid vehicle.'], 422);
            }
        }

        $driver->update([
            'assigned_vehicle_id' => $assignedVehicleId,
            'assignment_start_date' => $validated['assignment_start_date'] ?? null,
            'assignment_end_date' => $validated['assignment_end_date'] ?? null,
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => $assignedVehicleId
                ? 'Driver assigned to vehicle successfully.'
                : 'Driver unassigned from vehicle.',
        ]);
    }

    /**
     * Return bank accounts with account_nature = card (for Assign card dropdown).
     */
    public function cardAccounts(Request $request)
    {
        $user = Auth::user();
        $accounts = BankAccount::where('company_id', $user->company_id)
            ->where('account_nature', 'card')
            ->orderBy('name')
            ->get(['id', 'name', 'account_number']);
        return response()->json(['success' => true, 'data' => $accounts]);
    }

    /**
     * Assign a fuel card (bank account with nature=card) to the driver.
     */
    public function assignCard(Request $request, FleetDriver $driver)
    {
        $user = Auth::user();
        if ($driver->company_id !== $user->company_id) {
            abort(403, 'Unauthorized.');
        }
        $validated = $request->validate([
            'fuel_card_bank_account_id' => 'nullable|exists:bank_accounts,id',
        ]);
        $accountId = $validated['fuel_card_bank_account_id'] ?? null;
        if ($accountId === '' || $accountId === false) {
            $accountId = null;
        }
        if ($accountId) {
            $account = BankAccount::where('id', $accountId)
                ->where('company_id', $user->company_id)
                ->where('account_nature', 'card')
                ->first();
            if (!$account) {
                return response()->json(['success' => false, 'message' => 'Invalid card account. Must be a card (not bank).'], 422);
            }
            // One card cannot be assigned to two drivers: unassign from any other driver
            FleetDriver::where('company_id', $user->company_id)
                ->where('id', '!=', $driver->id)
                ->where('fuel_card_bank_account_id', $accountId)
                ->update(['fuel_card_bank_account_id' => null, 'updated_by' => $user->id]);
        }
        $driver->update([
            'fuel_card_bank_account_id' => $accountId,
            'updated_by' => $user->id,
        ]);
        return response()->json([
            'success' => true,
            'message' => $accountId ? 'Card assigned to driver.' : 'Card unassigned from driver.',
        ]);
    }
}
