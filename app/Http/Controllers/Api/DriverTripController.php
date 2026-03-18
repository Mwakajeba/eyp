<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetTrip;
use App\Models\Fleet\FleetTripCost;
use App\Models\Fleet\FleetFuelLog;
use App\Models\Fleet\FleetApprovalSettings;
use App\Models\ChartAccount;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DriverTripController extends Controller
{
    /**
     * Get all trips assigned to the authenticated driver
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $fleetDriver = $user->fleetDriver;

        if (!$fleetDriver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        $trips = $fleetDriver->trips()
            ->with(['vehicle', 'route', 'customer'])
            ->orderByDesc('planned_start_date')
            ->get()
            ->map(function ($trip) {
                return $this->formatTripData($trip);
            });

        return response()->json([
            'success' => true,
            'data' => $trips,
        ]);
    }

    /**
     * Get upcoming trips (pending or scheduled trips)
     */
    public function upcoming(Request $request): JsonResponse
    {
        $user = $request->user();
        $fleetDriver = $user->fleetDriver;

        if (!$fleetDriver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        // Get trips that are pending or scheduled
        $trips = $fleetDriver->trips()
            ->whereIn('status', ['pending', 'scheduled', 'planned'])
            ->where(function ($query) {
                // Include trips with future start dates or no start date yet
                $query->where('planned_start_date', '>=', now()->startOfDay())
                    ->orWhereNull('planned_start_date');
            })
            ->with(['vehicle', 'route', 'customer'])
            ->orderBy('planned_start_date', 'asc')
            ->get()
            ->map(function ($trip) {
                return $this->formatTripData($trip);
            });

        return response()->json([
            'success' => true,
            'data' => $trips,
        ]);
    }

    /**
     * Get active/current trip (in progress)
     */
    public function active(Request $request): JsonResponse
    {
        $user = $request->user();
        $fleetDriver = $user->fleetDriver;

        if (!$fleetDriver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        $trip = $fleetDriver->trips()
            ->whereIn('status', ['dispatched', 'in_progress'])
            ->with(['vehicle', 'route', 'customer'])
            ->orderByDesc('planned_start_date')
            ->first();

        if (!$trip) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No active trip',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatTripData($trip),
        ]);
    }

    /**
     * Get specific trip details
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $fleetDriver = $user->fleetDriver;

        if (!$fleetDriver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        $trip = $fleetDriver->trips()
            ->with(['vehicle', 'route', 'customer', 'costs'])
            ->find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatTripData($trip, true),
        ]);
    }

    /**
     * Start a trip
     */
    public function start(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $fleetDriver = $user->fleetDriver;

        if (!$fleetDriver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        $trip = $fleetDriver->trips()->find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found',
            ], 404);
        }

        if (!in_array($trip->status, ['pending', 'scheduled', 'planned', 'dispatched'])) {
            return response()->json([
                'success' => false,
                'message' => 'Trip cannot be started from current status',
            ], 400);
        }

        $approvalRequired = $this->isTripApprovalRequiredForCompany($trip->company_id, $trip->branch_id);
        if ($approvalRequired && $trip->approval_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Trip is pending approval and cannot be started yet',
            ], 400);
        }

        $request->validate([
            'odometer_start' => 'nullable|numeric',
            'location_latitude' => 'nullable|numeric',
            'location_longitude' => 'nullable|numeric',
            'location_name' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
        ]);

        $updateData = [
            'status' => 'in_progress',
            'actual_start_date' => now(),
            'start_odometer' => $request->odometer_start ?? $trip->start_odometer,
        ];
        if ($request->filled('location_latitude') && $request->filled('location_longitude')) {
            $updateData['start_latitude'] = $request->location_latitude;
            $updateData['start_longitude'] = $request->location_longitude;
            if ($request->filled('location_name')) {
                $updateData['start_location_name'] = $request->location_name;
            }
        }
        $trip->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Trip started successfully',
            'data' => $this->formatTripData($trip),
        ]);
    }

    /**
     * Update trip location
     */
    public function updateLocation(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $fleetDriver = $user->fleetDriver;

        if (!$fleetDriver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        $trip = $fleetDriver->trips()->find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found',
            ], 404);
        }

        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'location_name' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
        ]);

        $updates = [
            'last_location_lat' => $request->latitude,
            'last_location_lng' => $request->longitude,
            'last_location_at' => now(),
            'last_location_name' => $request->location_name,
        ];
        if ($request->filled('notes')) {
            $updates['notes'] = trim(($trip->notes ?? '') . "\n[Location update " . now()->format('Y-m-d H:i') . '] ' . $request->notes);
        }
        $trip->update($updates);

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
        ]);
    }

    /**
     * Report delay on a trip
     */
    public function reportDelay(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $fleetDriver = $user->fleetDriver;
        if (!$fleetDriver) {
            return response()->json(['success' => false, 'message' => 'Driver profile not found'], 404);
        }
        $trip = $fleetDriver->trips()->find($id);
        if (!$trip) {
            return response()->json(['success' => false, 'message' => 'Trip not found'], 404);
        }
        $request->validate([
            'reason' => 'required|string|max:500',
            'estimated_delay_minutes' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);
        $append = '[DELAY] ' . $request->reason;
        if ($request->estimated_delay_minutes) {
            $append .= ' (est. ' . $request->estimated_delay_minutes . ' min)';
        }
        if ($request->notes) {
            $append .= ' - ' . $request->notes;
        }
        $trip->update([
            'notes' => trim(($trip->notes ?? '') . "\n" . $append),
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Delay reported successfully',
            'data' => $this->formatTripData($trip),
        ]);
    }

    /**
     * Log fuel for a trip. Optional GL: when gl_account_id and paid_from_account_id are sent,
     * creates GL transactions (debit expense, credit bank) like the web fuel form.
     */
    public function logFuel(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $fleetDriver = $user->fleetDriver;
        if (!$fleetDriver) {
            return response()->json(['success' => false, 'message' => 'Driver profile not found'], 404);
        }
        $trip = $fleetDriver->trips()->with('vehicle')->find($id);
        if (!$trip) {
            return response()->json(['success' => false, 'message' => 'Trip not found'], 404);
        }
        $request->validate([
            'liters_filled' => 'required|numeric|min:0',
            'cost_per_liter' => 'nullable|numeric|min:0',
            'total_cost' => 'nullable|numeric|min:0',
            'odometer_reading' => 'nullable|numeric|min:0',
            'previous_odometer' => 'nullable|numeric|min:0',
            'fuel_station' => 'nullable|string|max:255',
            'fuel_type' => 'nullable|string|max:50',
            'gl_account_id' => 'nullable|exists:chart_accounts,id',
            'paid_from_account_id' => 'nullable|exists:bank_accounts,id',
            'fuel_card_used' => 'nullable|boolean',
            'fuel_card_number' => 'nullable|string|max:50',
            'fuel_card_type' => 'nullable|string|max:50',
            'receipt_number' => 'nullable|string|max:100',
            'date_filled' => 'nullable|date',
            'time_filled' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ]);

        $totalCost = $request->total_cost ?? (($request->liters_filled ?? 0) * ($request->cost_per_liter ?? 0));
        $dateFilled = $request->date_filled ? Carbon::parse($request->date_filled)->toDateString() : now()->toDateString();
        $timeFilled = $request->time_filled ? Carbon::parse($request->date_filled . ' ' . $request->time_filled) : now();

        $previousOdometer = $request->previous_odometer;
        if ($previousOdometer === null && $request->odometer_reading !== null) {
            $lastLog = FleetFuelLog::where('company_id', $trip->company_id)
                ->where('vehicle_id', $trip->vehicle_id)
                ->orderBy('odometer_reading', 'desc')
                ->first();
            $previousOdometer = $lastLog ? (float) $lastLog->odometer_reading : null;
        }

        $glAccountId = $request->gl_account_id ? (int) $request->gl_account_id : null;
        $paidFromAccountId = $request->paid_from_account_id ? (int) $request->paid_from_account_id : null;
        if ($glAccountId && !$paidFromAccountId) {
            $defaultBank = BankAccount::whereHas('chartAccount.accountClassGroup', fn ($q) => $q->where('company_id', $trip->company_id))
                ->orderBy('name')->first();
            $paidFromAccountId = $defaultBank ? $defaultBank->id : null;
        }
        $postToGl = $glAccountId && $paidFromAccountId && $totalCost > 0;

        $log = FleetFuelLog::create([
            'company_id' => $trip->company_id,
            'branch_id' => $trip->branch_id,
            'trip_id' => $trip->id,
            'vehicle_id' => $trip->vehicle_id,
            'fuel_station' => $request->fuel_station,
            'fuel_type' => $request->fuel_type,
            'liters_filled' => $request->liters_filled,
            'cost_per_liter' => $request->cost_per_liter,
            'total_cost' => $totalCost,
            'odometer_reading' => $request->odometer_reading,
            'previous_odometer' => $previousOdometer,
            'fuel_card_used' => (bool) $request->fuel_card_used,
            'fuel_card_number' => $request->fuel_card_number,
            'fuel_card_type' => $request->fuel_card_type,
            'receipt_number' => $request->receipt_number,
            'date_filled' => $dateFilled,
            'time_filled' => $timeFilled,
            'gl_account_id' => $glAccountId,
            'notes' => $request->notes,
            'approval_status' => 'pending',
            'created_by' => $user->id,
        ]);

        if ($postToGl) {
            $branchId = $trip->branch_id ?? $user->branch_id;
            \App\Models\GlTransaction::create([
                'branch_id' => $branchId,
                'chart_account_id' => $glAccountId,
                'amount' => $totalCost,
                'nature' => 'debit',
                'transaction_id' => $log->id,
                'transaction_type' => 'fleet_fuel_log',
                'date' => $dateFilled,
                'description' => 'Fuel Log - ' . ($trip->vehicle->name ?? 'Vehicle') . ($request->fuel_station ? ' - ' . $request->fuel_station : ''),
                'user_id' => $user->id,
            ]);
            $bankAccount = BankAccount::findOrFail($paidFromAccountId);
            \App\Models\GlTransaction::create([
                'branch_id' => $branchId,
                'chart_account_id' => $bankAccount->chart_account_id,
                'amount' => $totalCost,
                'nature' => 'credit',
                'transaction_id' => $log->id,
                'transaction_type' => 'fleet_fuel_log',
                'date' => $dateFilled,
                'description' => 'Payment from Bank Account - Fuel Log',
                'user_id' => $user->id,
            ]);
        }

        $log->calculateFuelEfficiency();
        $trip->recalculateTotalCosts();

        return response()->json([
            'success' => true,
            'message' => 'Fuel logged successfully',
            'data' => ['id' => $log->id],
        ]);
    }

    /**
     * Add expense to a trip
     */
    public function addExpense(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $fleetDriver = $user->fleetDriver;
        if (!$fleetDriver) {
            return response()->json(['success' => false, 'message' => 'Driver profile not found'], 404);
        }
        $trip = $fleetDriver->trips()->find($id);
        if (!$trip) {
            return response()->json(['success' => false, 'message' => 'Trip not found'], 404);
        }
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'cost_type' => 'required|string|max:100',
            'description' => 'required|string|max:500',
            'date_incurred' => 'nullable|date',
        ]);
        $cost = FleetTripCost::create([
            'company_id' => $trip->company_id,
            'branch_id' => $trip->branch_id,
            'trip_id' => $trip->id,
            'vehicle_id' => $trip->vehicle_id,
            'cost_type' => $request->cost_type,
            'amount' => $request->amount,
            'description' => $request->description,
            'date_incurred' => $request->date_incurred ? Carbon::parse($request->date_incurred) : now(),
            'approval_status' => 'pending',
            'notes' => $request->notes ?? null,
            'created_by' => $user->id,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Expense added successfully',
            'data' => ['id' => $cost->id],
        ]);
    }

    /**
     * Report incident on a trip
     */
    public function reportIncident(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $fleetDriver = $user->fleetDriver;
        if (!$fleetDriver) {
            return response()->json(['success' => false, 'message' => 'Driver profile not found'], 404);
        }
        $trip = $fleetDriver->trips()->find($id);
        if (!$trip) {
            return response()->json(['success' => false, 'message' => 'Trip not found'], 404);
        }
        $request->validate([
            'description' => 'required|string|max:1000',
            'severity' => 'nullable|string|in:low,medium,high,critical',
        ]);
        $append = '[INCIDENT' . ($request->severity ? ' ' . strtoupper($request->severity) : '') . '] ' . $request->description;
        $trip->update([
            'notes' => trim(($trip->notes ?? '') . "\n" . $append),
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Incident reported successfully',
            'data' => $this->formatTripData($trip),
        ]);
    }

    /**
     * Get fuel options for driver app: GL accounts (by fuel type) and bank accounts (paid from).
     * Optional trip_id: when provided, returns previous_odometer for that trip's vehicle (for read-only display).
     */
    public function fuelOptions(Request $request): JsonResponse
    {
        $user = $request->user();
        $fleetDriver = $user->fleetDriver;
        if (!$fleetDriver) {
            return response()->json(['success' => false, 'message' => 'Driver profile not found'], 404);
        }
        $companyId = $fleetDriver->company_id;
        if (!$companyId) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }

        $previousOdometer = null;
        $tripId = $request->query('trip_id');
        if ($tripId) {
            $trip = $fleetDriver->trips()->with('vehicle')->find($tripId);
            if ($trip && $trip->vehicle_id) {
                $lastLog = FleetFuelLog::where('company_id', $companyId)
                    ->where('vehicle_id', $trip->vehicle_id)
                    ->orderBy('odometer_reading', 'desc')
                    ->first();
                if ($lastLog) {
                    $previousOdometer = (float) $lastLog->odometer_reading;
                } else {
                    $vehicle = $trip->vehicle;
                    $previousOdometer = $trip->start_odometer ?? ($vehicle ? (float) ($vehicle->current_odometer ?? 0) : null);
                }
            }
        }

        $baseGlQuery = ChartAccount::whereHas('accountClassGroup', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
                ->whereHas('accountClass', function ($classQ) {
                    $classQ->whereRaw('LOWER(name) LIKE ?', ['%expense%']);
                });
        });
        $glDiesel = (clone $baseGlQuery)->where('fuel_type', 'diesel')->orderBy('account_name')->get(['id', 'account_code', 'account_name', 'fuel_type']);
        $glPetrol = (clone $baseGlQuery)->where('fuel_type', 'petrol')->orderBy('account_name')->get(['id', 'account_code', 'account_name', 'fuel_type']);
        if ($glDiesel->isEmpty() || $glPetrol->isEmpty()) {
            $fallback = ChartAccount::whereHas('accountClassGroup', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->whereHas('accountClass', function ($classQ) {
                        $classQ->whereRaw('LOWER(name) LIKE ?', ['%expense%']);
                    });
            })->orderBy('account_name')->get(['id', 'account_code', 'account_name', 'fuel_type']);
            if ($glDiesel->isEmpty()) {
                $glDiesel = $fallback;
            }
            if ($glPetrol->isEmpty()) {
                $glPetrol = $fallback;
            }
        }

        $paymentByCard = false;
        $driverCard = null;
        $bankAccounts = collect();
        $defaultBankId = null;

        if ($fleetDriver->fuel_card_bank_account_id) {
            $assignedCard = BankAccount::where('id', $fleetDriver->fuel_card_bank_account_id)
                ->where('company_id', $companyId)
                ->where('account_nature', 'card')
                ->first(['id', 'name', 'account_number', 'currency']);
            if ($assignedCard) {
                $paymentByCard = true;
                $driverCard = ['id' => $assignedCard->id, 'name' => $assignedCard->name, 'account_number' => $assignedCard->account_number, 'currency' => $assignedCard->currency];
                $defaultBankId = $assignedCard->id;
            }
        }

        if (! $paymentByCard) {
            $bankAccounts = BankAccount::with('chartAccount')
                ->whereHas('chartAccount.accountClassGroup', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->where('account_nature', 'bank')
                ->orderBy('name')
                ->get(['id', 'name', 'account_number', 'currency']);
            $defaultBankId = $bankAccounts->isNotEmpty() ? $bankAccounts->first()->id : null;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'gl_accounts_diesel' => $glDiesel->map(fn ($a) => ['id' => $a->id, 'account_code' => $a->account_code, 'account_name' => $a->account_name]),
                'gl_accounts_petrol' => $glPetrol->map(fn ($a) => ['id' => $a->id, 'account_code' => $a->account_code, 'account_name' => $a->account_name]),
                'bank_accounts' => $bankAccounts->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'account_number' => $b->account_number, 'currency' => $b->currency]),
                'default_bank_account_id' => $defaultBankId,
                'payment_by_card' => $paymentByCard,
                'driver_card' => $driverCard,
                'previous_odometer' => $previousOdometer,
            ],
        ]);
    }

    /**
     * Complete a trip
     */
    public function complete(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $fleetDriver = $user->fleetDriver;

        if (!$fleetDriver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found',
            ], 404);
        }

        $trip = $fleetDriver->trips()->find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'Trip not found',
            ], 404);
        }

        if ($trip->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Only trips in progress can be completed',
            ], 400);
        }

        $request->validate([
            'odometer_end' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $trip->update([
            'status' => 'completed',
            'actual_end_date' => now(),
            'end_odometer' => $request->odometer_end ?? $trip->end_odometer,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip completed successfully',
            'data' => $this->formatTripData($trip),
        ]);
    }

    /**
     * Check if trip approval is required for the given company/branch.
     * When fleet/approval-settings has "Enable Approval System" off, returns false.
     */
    private function isTripApprovalRequiredForCompany(?int $companyId, $branchId = null): bool
    {
        if (!$companyId) {
            return false;
        }
        $settings = FleetApprovalSettings::getSettingsForCompany($companyId, $branchId);
        if ($settings !== null) {
            return (bool) $settings->approval_required;
        }
        return false;
    }

    /**
     * Format trip data for API response.
     * When approval is not required, approval_status is returned as 'approved' so driver app shows "Approved (Auto)".
     */
    private function formatTripData($trip, $detailed = false): array
    {
        $approvalRequired = $this->isTripApprovalRequiredForCompany($trip->company_id, $trip->branch_id);
        $effectiveApprovalStatus = $approvalRequired ? $trip->approval_status : 'approved';

        $data = [
            'id' => $trip->id,
            'trip_number' => $trip->trip_number,
            'status' => $trip->status,
            'trip_type' => $trip->trip_type,
            'approval_status' => $effectiveApprovalStatus,
            'approval_required' => $approvalRequired,
            'origin_location' => $trip->origin_location,
            'destination_location' => $trip->destination_location,
            'planned_start_date' => $trip->planned_start_date?->toIso8601String(),
            'planned_end_date' => $trip->planned_end_date?->toIso8601String(),
            'actual_start_date' => $trip->actual_start_date?->toIso8601String(),
            'actual_end_date' => $trip->actual_end_date?->toIso8601String(),
            'start_latitude' => $trip->start_latitude,
            'start_longitude' => $trip->start_longitude,
            'start_location_name' => $trip->start_location_name,
            'last_location_lat' => $trip->last_location_lat,
            'last_location_lng' => $trip->last_location_lng,
            'last_location_at' => $trip->last_location_at?->toIso8601String(),
            'last_location_name' => $trip->last_location_name,
            'cargo_description' => $trip->cargo_description,
            'customer' => $trip->customer ? [
                'id' => $trip->customer->id,
                'name' => $trip->customer->name,
            ] : null,
            'vehicle' => $trip->vehicle ? [
                'id' => $trip->vehicle->id,
                'name' => $trip->vehicle->name,
                'registration_number' => $trip->vehicle->registration_number,
                'code' => $trip->vehicle->code,
            ] : null,
        ];

        if ($detailed) {
            $data['notes'] = $trip->notes;
            $data['odometer_start'] = $trip->odometer_start;
            $data['odometer_end'] = $trip->odometer_end;
            $data['planned_distance_km'] = $trip->planned_distance_km;
            $data['total_costs'] = $trip->total_costs;
            
            // Add costs if available
            if ($trip->relationLoaded('costs')) {
                $data['costs'] = $trip->costs->map(function ($cost) {
                    return [
                        'id' => $cost->id,
                        'cost_category' => $cost->costCategory?->name ?? 'Other',
                        'amount' => $cost->amount,
                        'description' => $cost->description,
                        'date' => $cost->date?->toIso8601String(),
                    ];
                });
            }
        }

        return $data;
    }
}
