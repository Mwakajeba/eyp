<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetFuelLog;
use App\Models\Fleet\FleetTrip;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class FleetFuelController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        // Calculate dashboard statistics
        $fuelQuery = FleetFuelLog::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        $totalLogs = $fuelQuery->count();
        $totalLiters = $fuelQuery->sum('liters_filled');
        $totalCost = $fuelQuery->sum('total_cost');
        $avgEfficiency = $fuelQuery->whereNotNull('fuel_efficiency_km_per_liter')
            ->where('fuel_efficiency_km_per_liter', '>', 0)
            ->avg('fuel_efficiency_km_per_liter');

        return view('fleet.fuel.index', compact('totalLogs', 'totalLiters', 'totalCost', 'avgEfficiency'));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $query = FleetFuelLog::query()
            ->where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['vehicle', 'trip']);

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        if ($request->filled('date_from')) {
            $query->where('date_filled', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date_filled', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addColumn('vehicle_display', function($log) {
                return $log->vehicle ? $log->vehicle->name . ' (' . ($log->vehicle->registration_number ?? 'N/A') . ')' : 'N/A';
            })
            ->addColumn('trip_display', function($log) {
                if (!$log->trip) {
                    return 'N/A';
                }
                $trip = $log->trip;
                $startDate = $trip->actual_start_date ?? $trip->planned_start_date;
                $dateStr = $startDate ? $startDate->format('d-M-Y') : '';
                return $dateStr ? $trip->trip_number . ' (' . $dateStr . ')' : $trip->trip_number;
            })
            ->addColumn('date_display', function($log) {
                return $log->date_filled->format('Y-m-d');
            })
            ->addColumn('fuel_details', function($log) {
                return number_format($log->liters_filled, 2) . ' L @ ' . number_format($log->cost_per_liter, 2);
            })
            ->addColumn('cost_display', function($log) {
                return number_format($log->total_cost, 2);
            })
            ->addColumn('efficiency_display', function($log) {
                if ($log->fuel_efficiency_km_per_liter) {
                    return number_format($log->fuel_efficiency_km_per_liter, 2) . ' km/L';
                }
                return 'N/A';
            })
            ->addColumn('approval_status_display', function($log) {
                $colors = [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                ];
                $color = $colors[$log->approval_status] ?? 'secondary';
                return '<span class="badge bg-' . $color . '">' . ucfirst($log->approval_status) . '</span>';
            })
            ->addColumn('actions', function($log) {
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<a href="' . route('fleet.fuel.show', $log->hash_id) . '" class="btn btn-sm btn-outline-info" title="View"><i class="bx bx-show"></i></a>';
                $actions .= '<a href="' . route('fleet.fuel.edit', $log->hash_id) . '" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['approval_status_display', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        // Only fleet vehicles (Motor Vehicles category FA04), same as fleet/vehicles index
        $vehicles = Asset::where('company_id', $user->company_id)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number']);

        // Trips are loaded by vehicle via AJAX (tripsByVehicle) - not loaded here
        $trips = collect();

        // Get GL accounts for fuel by type (diesel / petrol) - show only accounts related to selected fuel type
        $baseGlQuery = \App\Models\ChartAccount::whereHas('accountClassGroup', function($q) use ($user) {
                $q->where('company_id', $user->company_id)
                  ->whereHas('accountClass', function($classQ) {
                      $classQ->whereRaw('LOWER(name) LIKE ?', ['%expense%']);
                  });
            });
        $glAccountsDiesel = (clone $baseGlQuery)->where('fuel_type', 'diesel')->orderBy('account_name')->get(['id', 'account_code', 'account_name', 'fuel_type']);
        $glAccountsPetrol = (clone $baseGlQuery)->where('fuel_type', 'petrol')->orderBy('account_name')->get(['id', 'account_code', 'account_name', 'fuel_type']);
        // If no fuel-type-specific accounts exist (run FleetFuelGlAccountSeeder), use all expense accounts for both
        if ($glAccountsDiesel->isEmpty() || $glAccountsPetrol->isEmpty()) {
            $fallback = \App\Models\ChartAccount::whereHas('accountClassGroup', function($q) use ($user) {
                $q->where('company_id', $user->company_id)
                  ->whereHas('accountClass', function($classQ) {
                      $classQ->whereRaw('LOWER(name) LIKE ?', ['%expense%']);
                  });
            })->orderBy('account_name')->get(['id', 'account_code', 'account_name', 'fuel_type']);
            if ($glAccountsDiesel->isEmpty()) {
                $glAccountsDiesel = $fallback;
            }
            if ($glAccountsPetrol->isEmpty()) {
                $glAccountsPetrol = $fallback;
            }
        }

        // Get bank accounts for "paid from" field
        $bankAccounts = \App\Models\BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();

        $bankAccountsForScript = $bankAccounts->map(function ($b) {
            return [
                'id' => $b->id,
                'name' => $b->name,
                'account_number' => $b->account_number ?? '',
                'currency' => $b->currency ?? '',
            ];
        })->values()->all();

        return view('fleet.fuel.create', compact('vehicles', 'trips', 'glAccountsDiesel', 'glAccountsPetrol', 'bankAccounts', 'bankAccountsForScript'));
    }

    /**
     * Return previous odometer reading for a vehicle (for fuel log create form).
     */
    public function previousOdometer(Request $request)
    {
        $user = Auth::user();
        $vehicleId = $request->get('vehicle_id');
        $tripId = $request->get('trip_id');
        if (!$vehicleId) {
            return response()->json(['previous_odometer' => null]);
        }
        $lastFuelLog = FleetFuelLog::where('company_id', $user->company_id)
            ->where('vehicle_id', $vehicleId)
            ->orderBy('odometer_reading', 'desc')
            ->first();
        if ($lastFuelLog) {
            return response()->json(['previous_odometer' => (float) $lastFuelLog->odometer_reading]);
        }
        $vehicle = Asset::find($vehicleId);
        if ($tripId) {
            $trip = FleetTrip::where('company_id', $user->company_id)->find($tripId);
            $prev = $trip->start_odometer ?? ($vehicle ? (float)($vehicle->current_odometer ?? 0) : null);
            return response()->json(['previous_odometer' => $prev]);
        }
        $prev = $vehicle ? (float)($vehicle->current_odometer ?? 0) : null;
        return response()->json(['previous_odometer' => $prev]);
    }

    /**
     * Return trips for the given vehicle that are assigned to it and not complete (for fuel create dropdown).
     */
    public function tripsByVehicle(Request $request)
    {
        $user = Auth::user();
        $vehicleId = $request->get('vehicle_id');
        if (!$vehicleId) {
            return response()->json(['trips' => []]);
        }
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        // Planned, dispatched, and in_progress = not yet completed
        $trips = FleetTrip::where('company_id', $user->company_id)
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', ['planned', 'dispatched', 'in_progress'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['vehicle', 'route', 'driver.fuelCardAccount'])
            ->orderBy('trip_number', 'desc')
            ->get();
        $list = $trips->map(function ($trip) {
            $plannedLiters = $trip->planned_fuel_consumption_liters ?? ($trip->route->estimated_fuel_consumption_liters ?? null);
            $card = $trip->driver && $trip->driver->fuelCardAccount ? $trip->driver->fuelCardAccount : null;
            return [
                'id' => $trip->id,
                'trip_number' => $trip->trip_number,
                'vehicle_id' => $trip->vehicle_id,
                'planned_fuel_liters' => $plannedLiters !== null ? (float) $plannedLiters : null,
                'driver_id' => $trip->driver_id,
                'driver_fuel_card_bank_account_id' => $card ? $card->id : null,
                'driver_fuel_card_name' => $card ? $card->name : null,
                'driver_fuel_card_account_number' => $card ? $card->account_number : null,
            ];
        })->values()->all();
        return response()->json(['trips' => $list]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:assets,id',
            'trip_id' => 'required|exists:fleet_trips,id',
            'fuel_type' => 'nullable|string|max:50',
            'odometer_reading' => 'required|numeric|min:0',
            'previous_odometer' => 'nullable|numeric|min:0',
            'fuel_card_number' => 'nullable|string|max:50',
            'fuel_card_type' => 'nullable|string|max:50',
            'fuel_card_used' => 'boolean',
            'receipt_number' => 'nullable|string|max:100',
            'date_filled' => 'required|date',
            'time_filled' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string',
            'paid_from_account_id' => 'required|exists:bank_accounts,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
            'cost_lines' => 'required|array|min:1',
            'cost_lines.*.gl_account_id' => 'required|exists:chart_accounts,id',
            'cost_lines.*.liters_filled' => 'required|numeric|min:0',
            'cost_lines.*.cost_per_liter' => 'required|numeric|min:0',
            'cost_lines.*.fuel_station' => 'nullable|string|max:255',
            'cost_lines.*.amount' => 'required|numeric|min:0',
            'cost_lines.*.description' => 'nullable|string',
        ]);

        // Get previous odometer if not provided
        if (empty($validated['previous_odometer'])) {
            $lastFuelLog = FleetFuelLog::where('vehicle_id', $validated['vehicle_id'])
                ->where('odometer_reading', '<', $validated['odometer_reading'])
                ->orderBy('odometer_reading', 'desc')
                ->first();

            if ($lastFuelLog) {
                $validated['previous_odometer'] = $lastFuelLog->odometer_reading;
            } else {
                // Try to get from vehicle's current odometer or trip start odometer
                $vehicle = Asset::find($validated['vehicle_id']);
                if ($validated['trip_id']) {
                    $trip = FleetTrip::find($validated['trip_id']);
                    $validated['previous_odometer'] = $trip->start_odometer ?? ($vehicle->current_odometer ?? 0);
                } else {
                    $validated['previous_odometer'] = $vehicle->current_odometer ?? 0;
                }
            }
        }

        // Handle file uploads
        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('fleet-fuel-attachments', 'public');
                $attachmentPaths[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            }
        }

        // Calculate totals from cost lines
        $totalAmount = 0;
        $totalLiters = 0;
        $totalCostForAvg = 0;
        $firstFuelStation = null;
        
        foreach ($validated['cost_lines'] as $line) {
            $totalAmount += $line['amount'];
            $totalLiters += $line['liters_filled'];
            $totalCostForAvg += $line['amount']; // Amount is already liters * cost_per_liter
            if (!$firstFuelStation && !empty($line['fuel_station'])) {
                $firstFuelStation = $line['fuel_station'];
            }
        }
        
        // Calculate weighted average cost per liter
        $avgCostPerLiter = $totalLiters > 0 ? ($totalCostForAvg / $totalLiters) : 0;

        // Use first line's GL account for main fuel log record (keys may be 1,2,3... from JS)
        $firstLine = reset($validated['cost_lines']);

        $fuelLog = FleetFuelLog::create(array_merge($validated, [
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'gl_account_id' => $firstLine['gl_account_id'],
            'attachments' => !empty($attachmentPaths) ? $attachmentPaths : null,
            'total_cost' => $totalAmount, // Use calculated total from cost lines
            'liters_filled' => $totalLiters, // Sum of all liters from cost lines
            'cost_per_liter' => $avgCostPerLiter, // Weighted average cost per liter
            'fuel_station' => $firstFuelStation, // First fuel station from cost lines
            'created_by' => $user->id,
            'approval_status' => 'pending',
        ]));

        // Calculate efficiency
        $fuelLog->calculateFuelEfficiency();

        // Create GL transactions for each cost line
        foreach ($validated['cost_lines'] as $line) {
            // Debit: Expense account (cost line)
            \App\Models\GlTransaction::create([
                'branch_id' => $branchId,
                'chart_account_id' => $line['gl_account_id'],
                'amount' => $line['amount'],
                'nature' => 'debit',
                'transaction_id' => $fuelLog->id,
                'transaction_type' => 'fleet_fuel_log',
                'date' => $validated['date_filled'],
                'description' => $line['description'] ?? "Fuel Log - Vehicle " . ($fuelLog->vehicle->name ?? 'N/A'),
                'user_id' => $user->id,
            ]);
        }

        // Get bank account for GL posting
        $bankAccount = \App\Models\BankAccount::findOrFail($validated['paid_from_account_id']);

        // Credit: Bank account (paid from)
        \App\Models\GlTransaction::create([
            'branch_id' => $branchId,
            'chart_account_id' => $bankAccount->chart_account_id,
            'amount' => $totalAmount,
            'nature' => 'credit',
            'transaction_id' => $fuelLog->id,
            'transaction_type' => 'fleet_fuel_log',
            'date' => $validated['date_filled'],
            'description' => "Payment from Bank Account - Fuel Log",
            'user_id' => $user->id,
        ]);

        if ($fuelLog->trip_id) {
            $fuelLog->trip->recalculateTotalCosts();
        }
        return redirect()->route('fleet.fuel.index')->with('success', 'Fuel log created successfully.');
    }

    public function show(FleetFuelLog $fuelLog)
    {
        $user = Auth::user();

        if ($fuelLog->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $fuelLog->load(['vehicle', 'trip', 'approvedBy', 'createdBy', 'glAccount']);

        $costLines = \App\Models\GlTransaction::where('transaction_type', 'fleet_fuel_log')
            ->where('transaction_id', $fuelLog->id)
            ->where('nature', 'debit')
            ->with('chartAccount')
            ->orderBy('id')
            ->get();

        $creditGl = \App\Models\GlTransaction::where('transaction_type', 'fleet_fuel_log')
            ->where('transaction_id', $fuelLog->id)
            ->where('nature', 'credit')
            ->first();
        $paidFromAccount = $creditGl ? \App\Models\BankAccount::where('chart_account_id', $creditGl->chart_account_id)->first() : null;

        return view('fleet.fuel.show', compact('fuelLog', 'costLines', 'paidFromAccount'));
    }

    public function print(FleetFuelLog $fuelLog)
    {
        $user = Auth::user();

        if ($fuelLog->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $fuelLog->load(['vehicle', 'trip', 'approvedBy', 'createdBy', 'glAccount', 'company']);

        $costLines = \App\Models\GlTransaction::where('transaction_type', 'fleet_fuel_log')
            ->where('transaction_id', $fuelLog->id)
            ->where('nature', 'debit')
            ->with('chartAccount')
            ->orderBy('id')
            ->get();

        $creditGl = \App\Models\GlTransaction::where('transaction_type', 'fleet_fuel_log')
            ->where('transaction_id', $fuelLog->id)
            ->where('nature', 'credit')
            ->first();
        $paidFromAccount = $creditGl ? \App\Models\BankAccount::where('chart_account_id', $creditGl->chart_account_id)->first() : null;

        return view('fleet.fuel.print', compact('fuelLog', 'costLines', 'paidFromAccount'));
    }

    /**
     * Export fuel log as PDF (download), same design pattern as fleet invoice export.
     */
    public function exportPdf(FleetFuelLog $fuelLog)
    {
        $user = Auth::user();

        if ($fuelLog->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $fuelLog->load(['vehicle', 'trip.route', 'approvedBy', 'createdBy', 'glAccount', 'company']);

        $costLines = \App\Models\GlTransaction::where('transaction_type', 'fleet_fuel_log')
            ->where('transaction_id', $fuelLog->id)
            ->where('nature', 'debit')
            ->with('chartAccount')
            ->orderBy('id')
            ->get();

        $creditGl = \App\Models\GlTransaction::where('transaction_type', 'fleet_fuel_log')
            ->where('transaction_id', $fuelLog->id)
            ->where('nature', 'credit')
            ->first();
        $paidFromAccount = $creditGl ? \App\Models\BankAccount::where('chart_account_id', $creditGl->chart_account_id)->first() : null;

        $pageSize = strtoupper((string) (\App\Models\SystemSetting::getValue('document_page_size', 'A5')));
        $orientation = strtolower((string) (\App\Models\SystemSetting::getValue('document_orientation', 'portrait')));
        $marginTopStr = \App\Models\SystemSetting::getValue('document_margin_top', '2.54cm');
        $marginRightStr = \App\Models\SystemSetting::getValue('document_margin_right', '2.54cm');
        $marginBottomStr = \App\Models\SystemSetting::getValue('document_margin_bottom', '2.54cm');
        $marginLeftStr = \App\Models\SystemSetting::getValue('document_margin_left', '2.54cm');
        $convertToMm = function ($value) {
            if (is_numeric($value)) {
                return (float) $value;
            }
            $numeric = (float) str_replace(['cm', 'mm', 'pt', 'px', 'in'], '', $value);
            if (strpos($value, 'cm') !== false) {
                return $numeric * 10;
            }
            return $numeric;
        };
        $marginTop = $convertToMm($marginTopStr);
        $marginRight = $convertToMm($marginRightStr);
        $marginBottom = $convertToMm($marginBottomStr);
        $marginLeft = $convertToMm($marginLeftStr);

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('fleet.fuel.print', compact('fuelLog', 'costLines', 'paidFromAccount'));
            $pdf->setPaper($pageSize, $orientation);
            $pdf->setOptions([
                'margin-top' => $marginTop,
                'margin-right' => $marginRight,
                'margin-bottom' => $marginBottom,
                'margin-left' => $marginLeft,
            ]);
            $filename = 'FuelLog_' . $fuelLog->hash_id . '_' . ($fuelLog->date_filled ? $fuelLog->date_filled->format('Y-m-d') : date('Y-m-d')) . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Fleet fuel log PDF error: ' . $e->getMessage());
            return redirect()->route('fleet.fuel.show', $fuelLog->hash_id)->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    public function edit(FleetFuelLog $fuelLog)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        if ($fuelLog->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        // Only fleet vehicles (Motor Vehicles category FA04), same as fleet/vehicles index
        $vehicles = Asset::where('company_id', $user->company_id)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number']);

        $trips = FleetTrip::where('company_id', $user->company_id)
            ->whereNotIn('status', ['completed'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with('vehicle')
            ->orderBy('trip_number', 'desc')
            ->get();

        $glAccounts = \App\Models\ChartAccount::whereHas('accountClassGroup', function($q) use ($user) {
                $q->where('company_id', $user->company_id)
                  ->whereHas('accountClass', function($classQ) {
                      $classQ->whereRaw('LOWER(name) LIKE ?', ['%expense%']);
                  });
            })
            ->orderBy('account_name')
            ->get(['id', 'account_code', 'account_name']);

        $bankAccounts = \App\Models\BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();

        $debitGlLines = \App\Models\GlTransaction::where('transaction_type', 'fleet_fuel_log')
            ->where('transaction_id', $fuelLog->id)
            ->where('nature', 'debit')
            ->orderBy('id')
            ->get();

        $costPerLiter = (float) ($fuelLog->cost_per_liter ?? 1);
        if ($costPerLiter <= 0) {
            $costPerLiter = 1;
        }
        $costLines = [];
        foreach ($debitGlLines as $gl) {
            $amount = (float) $gl->amount;
            $costLines[] = [
                'gl_account_id' => $gl->chart_account_id,
                'liters_filled' => $amount / $costPerLiter,
                'cost_per_liter' => $costPerLiter,
                'fuel_station' => $fuelLog->fuel_station,
                'amount' => $amount,
                'description' => $gl->description,
            ];
        }
        if (empty($costLines)) {
            $costLines = [['gl_account_id' => '', 'liters_filled' => '', 'cost_per_liter' => '', 'fuel_station' => $fuelLog->fuel_station ?? '', 'amount' => '', 'description' => '']];
        }

        $creditGl = \App\Models\GlTransaction::where('transaction_type', 'fleet_fuel_log')
            ->where('transaction_id', $fuelLog->id)
            ->where('nature', 'credit')
            ->first();
        $paidFromAccountId = $creditGl ? \App\Models\BankAccount::where('chart_account_id', $creditGl->chart_account_id)->first()?->id : null;

        return view('fleet.fuel.edit', compact('fuelLog', 'vehicles', 'trips', 'glAccounts', 'bankAccounts', 'costLines', 'paidFromAccountId'));
    }

    public function update(Request $request, FleetFuelLog $fuelLog)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        if ($fuelLog->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:assets,id',
            'trip_id' => 'nullable|exists:fleet_trips,id',
            'fuel_type' => 'nullable|string|max:50',
            'odometer_reading' => 'required|numeric|min:0',
            'previous_odometer' => 'nullable|numeric|min:0',
            'fuel_card_number' => 'nullable|string|max:50',
            'fuel_card_type' => 'nullable|string|max:50',
            'fuel_card_used' => 'boolean',
            'receipt_number' => 'nullable|string|max:100',
            'date_filled' => 'required|date',
            'time_filled' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string',
            'paid_from_account_id' => 'required|exists:bank_accounts,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
            'cost_lines' => 'required|array|min:1',
            'cost_lines.*.gl_account_id' => 'required|exists:chart_accounts,id',
            'cost_lines.*.liters_filled' => 'required|numeric|min:0',
            'cost_lines.*.cost_per_liter' => 'required|numeric|min:0',
            'cost_lines.*.fuel_station' => 'nullable|string|max:255',
            'cost_lines.*.amount' => 'required|numeric|min:0',
            'cost_lines.*.description' => 'nullable|string',
        ]);

        // Get previous odometer if not provided
        if (empty($validated['previous_odometer'])) {
            $lastFuelLog = FleetFuelLog::where('vehicle_id', $validated['vehicle_id'])
                ->where('id', '!=', $fuelLog->id)
                ->where('odometer_reading', '<', $validated['odometer_reading'])
                ->orderBy('odometer_reading', 'desc')
                ->first();
            if ($lastFuelLog) {
                $validated['previous_odometer'] = $lastFuelLog->odometer_reading;
            } else {
                $vehicle = Asset::find($validated['vehicle_id']);
                $validated['previous_odometer'] = $vehicle->current_odometer ?? 0;
            }
        }

        // Merge new attachments with existing
        $attachmentPaths = $fuelLog->attachments ?? [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $path = $file->store('fleet-fuel-attachments', 'public');
                    $attachmentPaths[] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'uploaded_at' => now()->toDateTimeString(),
                    ];
                }
            }
        }

        // Delete existing GL transactions for this fuel log
        \App\Models\GlTransaction::where('transaction_type', 'fleet_fuel_log')
            ->where('transaction_id', $fuelLog->id)
            ->delete();

        // Calculate totals from cost lines
        $totalAmount = 0;
        $totalLiters = 0;
        $totalCostForAvg = 0;
        $firstFuelStation = null;
        foreach ($validated['cost_lines'] as $line) {
            $totalAmount += $line['amount'];
            $totalLiters += $line['liters_filled'];
            $totalCostForAvg += $line['amount'];
            if (!$firstFuelStation && !empty($line['fuel_station'])) {
                $firstFuelStation = $line['fuel_station'];
            }
        }
        $avgCostPerLiter = $totalLiters > 0 ? ($totalCostForAvg / $totalLiters) : 0;
        $firstLine = reset($validated['cost_lines']);

        $fuelLog->update([
            'vehicle_id' => $validated['vehicle_id'],
            'trip_id' => $validated['trip_id'],
            'fuel_type' => $validated['fuel_type'],
            'odometer_reading' => $validated['odometer_reading'],
            'previous_odometer' => $validated['previous_odometer'],
            'fuel_card_number' => $validated['fuel_card_number'] ?? null,
            'fuel_card_type' => $validated['fuel_card_type'] ?? null,
            'fuel_card_used' => $validated['fuel_card_used'] ?? false,
            'receipt_number' => $validated['receipt_number'] ?? null,
            'date_filled' => $validated['date_filled'],
            'time_filled' => $validated['time_filled'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'gl_account_id' => $firstLine['gl_account_id'],
            'attachments' => !empty($attachmentPaths) ? $attachmentPaths : null,
            'total_cost' => $totalAmount,
            'liters_filled' => $totalLiters,
            'cost_per_liter' => $avgCostPerLiter,
            'fuel_station' => $firstFuelStation,
            'updated_by' => $user->id,
        ]);

        $fuelLog->calculateFuelEfficiency();

        $vehicleName = Asset::find($validated['vehicle_id'])->name ?? 'N/A';

        // Create GL transactions for each cost line
        foreach ($validated['cost_lines'] as $line) {
            \App\Models\GlTransaction::create([
                'branch_id' => $branchId,
                'chart_account_id' => $line['gl_account_id'],
                'amount' => $line['amount'],
                'nature' => 'debit',
                'transaction_id' => $fuelLog->id,
                'transaction_type' => 'fleet_fuel_log',
                'date' => $validated['date_filled'],
                'description' => $line['description'] ?? "Fuel Log - Vehicle " . $vehicleName,
                'user_id' => $user->id,
            ]);
        }

        $bankAccount = \App\Models\BankAccount::findOrFail($validated['paid_from_account_id']);
        \App\Models\GlTransaction::create([
            'branch_id' => $branchId,
            'chart_account_id' => $bankAccount->chart_account_id,
            'amount' => $totalAmount,
            'nature' => 'credit',
            'transaction_id' => $fuelLog->id,
            'transaction_type' => 'fleet_fuel_log',
            'date' => $validated['date_filled'],
            'description' => "Payment from Bank Account - Fuel Log",
            'user_id' => $user->id,
        ]);

        if ($fuelLog->trip_id) {
            $fuelLog->trip->recalculateTotalCosts();
        }
        return redirect()->route('fleet.fuel.show', $fuelLog->hash_id)->with('success', 'Fuel log updated successfully.');
    }

    public function approve(Request $request, FleetFuelLog $fuelLog)
    {
        $user = Auth::user();

        if ($fuelLog->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $fuelLog->update([
            'approval_status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        if ($fuelLog->trip_id) {
            $fuelLog->trip->recalculateTotalCosts();
        }
        return redirect()->route('fleet.fuel.show', $fuelLog->hash_id)->with('success', 'Fuel log approved successfully.');
    }

    public function efficiencyReport()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $vehicles = Asset::where('company_id', $user->company_id)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number']);

        return view('fleet.fuel.efficiency-report', compact('vehicles'));
    }

    public function efficiencyData(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $vehicleId = $request->get('vehicle_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Build base query
        $baseQuery = FleetFuelLog::query()
            ->where('company_id', $user->company_id)
            ->where('approval_status', 'approved')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($vehicleId, fn($q) => $q->where('vehicle_id', $vehicleId))
            ->when($dateFrom, fn($q) => $q->where('date_filled', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('date_filled', '<=', $dateTo));

        // Group by vehicle and aggregate
        $query = $baseQuery
            ->selectRaw('
                vehicle_id,
                COUNT(*) as fill_count,
                SUM(liters_filled) as total_liters,
                SUM(total_cost) as total_cost,
                SUM(km_since_last_fill) as total_distance,
                AVG(fuel_efficiency_km_per_liter) as avg_efficiency,
                AVG(cost_per_km) as avg_cost_per_km
            ')
            ->groupBy('vehicle_id')
            ->with('vehicle');

        return DataTables::of($query)
            ->addColumn('vehicle_display', function($row) {
                return $row->vehicle ? $row->vehicle->name . ' (' . ($row->vehicle->registration_number ?? 'N/A') . ')' : 'N/A';
            })
            ->addColumn('total_liters', function($row) {
                return number_format($row->total_liters ?? 0, 2);
            })
            ->addColumn('total_cost_display', function($row) {
                return number_format($row->total_cost ?? 0, 2) . ' TZS';
            })
            ->addColumn('total_distance', function($row) {
                return number_format($row->total_distance ?? 0, 2);
            })
            ->addColumn('avg_efficiency', function($row) {
                return $row->avg_efficiency ? number_format($row->avg_efficiency, 2) : 'N/A';
            })
            ->addColumn('cost_per_km_display', function($row) {
                return $row->avg_cost_per_km ? number_format($row->avg_cost_per_km, 2) . ' TZS' : 'N/A';
            })
            ->addColumn('fill_count', function($row) {
                return $row->fill_count ?? 0;
            })
            ->filterColumn('vehicle_display', function($query, $keyword) {
                $query->whereHas('vehicle', function($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                      ->orWhere('registration_number', 'like', "%{$keyword}%");
                });
            })
            ->make(true);
    }

    public function efficiencyReportExport(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $company = $user->company;

        $vehicleId = $request->get('vehicle_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Build query same as efficiencyData
        $baseQuery = FleetFuelLog::query()
            ->where('company_id', $user->company_id)
            ->where('approval_status', 'approved')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($vehicleId, fn($q) => $q->where('vehicle_id', $vehicleId))
            ->when($dateFrom, fn($q) => $q->where('date_filled', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('date_filled', '<=', $dateTo));

        $data = $baseQuery
            ->selectRaw('
                vehicle_id,
                COUNT(*) as fill_count,
                SUM(liters_filled) as total_liters,
                SUM(total_cost) as total_cost,
                SUM(km_since_last_fill) as total_distance,
                AVG(fuel_efficiency_km_per_liter) as avg_efficiency,
                AVG(cost_per_km) as avg_cost_per_km
            ')
            ->groupBy('vehicle_id')
            ->with('vehicle')
            ->get();

        $generatedAt = now();
        $vehicle = $vehicleId ? \App\Models\Assets\Asset::find($vehicleId) : null;

        return view('fleet.fuel.efficiency-report-export', compact('data', 'company', 'generatedAt', 'dateFrom', 'dateTo', 'vehicle'));
    }
}
