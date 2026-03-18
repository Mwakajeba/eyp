<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Traits\Fleet\HasFleetSettings;
use App\Models\Fleet\FleetTrip;
use App\Models\Fleet\FleetDriver;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetCategory;
use App\Models\Customer;
use App\Models\Hr\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class FleetTripController extends Controller
{
    use HasFleetSettings;
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        // Calculate dashboard statistics
        $tripQuery = FleetTrip::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        $totalTrips = $tripQuery->count();
        $plannedTrips = (clone $tripQuery)->where('status', 'planned')->count();
        $activeTrips = (clone $tripQuery)->whereIn('status', ['dispatched', 'in_progress'])->count();
        $completedTrips = (clone $tripQuery)->where('status', 'completed')->count();

        return view('fleet.trips.index', compact('totalTrips', 'plannedTrips', 'activeTrips', 'completedTrips'));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $query = FleetTrip::query()
            ->where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['vehicle', 'driver', 'customer', 'department']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('trip_type')) {
            $query->where('trip_type', $request->trip_type);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        return DataTables::of($query)
            ->editColumn('planned_start_date', fn($t) => optional($t->planned_start_date)->format('Y-m-d H:i'))
            ->editColumn('actual_start_date', fn($t) => optional($t->actual_start_date)->format('Y-m-d H:i'))
            ->addColumn('status_display', function($t) {
                $statusColors = [
                    'planned' => 'secondary',
                    'dispatched' => 'info',
                    'in_progress' => 'primary',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                ];
                $color = $statusColors[$t->status] ?? 'secondary';
                return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $t->status)) . '</span>';
            })
            ->addColumn('vehicle_display', function($t) {
                if ($t->vehicle) {
                    return $t->vehicle->name . ' (' . ($t->vehicle->registration_number ?? 'N/A') . ')';
                }
                return '<span class="text-muted">Not Assigned</span>';
            })
            ->addColumn('driver_display', function($t) {
                return $t->driver ? $t->driver->full_name : '<span class="text-muted">Not Assigned</span>';
            })
            ->addColumn('route_display', function($t) {
                return ($t->origin_location ?: '—') . ' → ' . ($t->destination_location ?: '—');
            })
            ->addColumn('revenue_display', function($t) {
                // Total revenue from created invoices only (do not use planned_revenue)
                $directInvoiceIds = \App\Models\Fleet\FleetInvoice::where('trip_id', $t->id)->pluck('id');
                $itemInvoiceIds = \App\Models\Fleet\FleetInvoiceItem::where('trip_id', $t->id)
                    ->distinct()
                    ->pluck('fleet_invoice_id');
                $allInvoiceIds = $directInvoiceIds->merge($itemInvoiceIds)->unique()->filter();
                $totalRevenue = $allInvoiceIds->count() > 0
                    ? \App\Models\Fleet\FleetInvoice::whereIn('id', $allInvoiceIds)->sum('total_amount')
                    : 0;
                return '<span class="' . ($totalRevenue > 0 ? 'text-success' : 'text-muted') . '">' . number_format($totalRevenue, 2) . ' TZS</span>';
            })
            ->addColumn('costs_display', function($t) {
                return number_format($t->total_costs, 2) . ' TZS';
            })
            ->addColumn('profit_display', function($t) {
                // Calculate profit/loss dynamically using actual revenue from invoices and invoice items
                $directInvoiceIds = \App\Models\Fleet\FleetInvoice::where('trip_id', $t->id)->pluck('id');
                $itemInvoiceIds = \App\Models\Fleet\FleetInvoiceItem::where('trip_id', $t->id)
                    ->distinct()
                    ->pluck('fleet_invoice_id');
                
                // Combine both (avoid double counting)
                $allInvoiceIds = $directInvoiceIds->merge($itemInvoiceIds)->unique()->filter();
                $actualRevenue = $allInvoiceIds->count() > 0 
                    ? \App\Models\Fleet\FleetInvoice::whereIn('id', $allInvoiceIds)->sum('total_amount')
                    : 0;
                
                $revenue = $actualRevenue > 0 ? $actualRevenue : 0;
                $profit = $revenue - $t->total_costs;
                $color = $profit >= 0 ? 'success' : 'danger';
                return '<span class="text-' . $color . '">' . number_format($profit, 2) . ' TZS</span>';
            })
            ->addColumn('actions', function($t) {
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<a href="' . route('fleet.trips.show', $t->hash_id) . '" class="btn btn-sm btn-outline-info" title="View"><i class="bx bx-show"></i></a>';
                
                if (in_array($t->status, ['planned', 'dispatched'])) {
                    $actions .= '<a href="' . route('fleet.trips.edit', $t->hash_id) . '" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                }
                
                if ($t->status == 'planned') {
                    $actions .= '<a href="' . route('fleet.trips.dispatch', $t->hash_id) . '" class="btn btn-sm btn-outline-success" title="Dispatch"><i class="bx bx-send"></i></a>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status_display', 'vehicle_display', 'driver_display', 'route_display', 'revenue_display', 'costs_display', 'profit_display', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        // Get only fleet vehicles (Motor Vehicles category FA04), same as fleet/vehicles index
        $vehicles = Asset::where('company_id', $user->company_id)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->where('operational_status', 'available')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['assignedDriver' => function($q) {
                $q->select('id', 'assigned_vehicle_id', 'full_name', 'license_number', 'status');
            }])
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number', 'operational_status', 'capacity_volume']);

        // Get available drivers
        $drivers = FleetDriver::where('company_id', $user->company_id)
            ->where('status', 'active')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'license_number']);

        // Get customers (with phone for trip form)
        $customers = Customer::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('fleet.trips.create', compact('vehicles', 'drivers', 'customers'));
    }

    /**
     * Proxy: geocode origin + destination via Nominatim, then get driving distance via OSRM.
     * Falls back to straight-line (Haversine) distance if OSRM is unavailable.
     * Returns JSON { distance_km [, approximate: true] } or { error: "message" }.
     */
    public function calculateDistance(Request $request)
    {
        $request->validate([
            'origin' => 'required|string|max:500',
            'destination' => 'required|string|max:500',
        ]);
        $origin = trim($request->origin);
        $destination = trim($request->destination);

        $userAgent = 'SmartAccountingFleet/1.0 (Trip Planning; https://github.com/smartaccounting)';

        try {
            Log::info('Fleet calculateDistance: start', ['origin' => $origin, 'destination' => $destination]);

            $originResponse = Http::timeout(15)
                ->withHeaders(['User-Agent' => $userAgent])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $origin,
                    'format' => 'json',
                    'limit' => 1,
                ]);
            if (!$originResponse->successful()) {
                Log::warning('Fleet calculateDistance: Nominatim origin failed', ['status' => $originResponse->status()]);
                return response()->json(['error' => 'Could not find origin address (service returned ' . $originResponse->status() . ').'], 400);
            }
            $originData = $originResponse->json();
            if (empty($originData) || !isset($originData[0]['lat'], $originData[0]['lon'])) {
                return response()->json(['error' => 'Origin address not found on map.'], 400);
            }
            $lat1 = (float) $originData[0]['lat'];
            $lon1 = (float) $originData[0]['lon'];

            sleep(1);

            $destResponse = Http::timeout(15)
                ->withHeaders(['User-Agent' => $userAgent])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $destination,
                    'format' => 'json',
                    'limit' => 1,
                ]);
            if (!$destResponse->successful()) {
                return response()->json(['error' => 'Could not find destination address.'], 400);
            }
            $destData = $destResponse->json();
            if (empty($destData) || !isset($destData[0]['lat'], $destData[0]['lon'])) {
                return response()->json(['error' => 'Destination address not found on map.'], 400);
            }
            $lat2 = (float) $destData[0]['lat'];
            $lon2 = (float) $destData[0]['lon'];

            $coords = "{$lon1},{$lat1};{$lon2},{$lat2}";
            $osrmResponse = Http::timeout(15)
                ->get("https://router.project-osrm.org/route/v1/driving/{$coords}", [
                    'overview' => 'full',
                    'geometries' => 'geojson',
                ]);

            if ($osrmResponse->successful()) {
                $osrm = $osrmResponse->json();
                if (!empty($osrm['routes']) && isset($osrm['routes'][0]['distance'])) {
                    $meters = (float) $osrm['routes'][0]['distance'];
                    $distance_km = round($meters / 1000 * 100) / 100;
                    $routeViaRoads = $this->extractRouteRoadNames($osrm);
                    $districtsAlongRoute = $this->getDistrictsAlongRoute($osrm, $userAgent);
                    return response()->json([
                        'distance_km' => $distance_km,
                        'source' => 'driving',
                        'route_via_roads' => $routeViaRoads,
                        'route_via_districts' => $districtsAlongRoute,
                    ]);
                }
            }

            $distance_km = $this->haversineKm($lat1, $lon1, $lat2, $lon2);
            $distance_km = round($distance_km * 100) / 100;
            return response()->json([
                'distance_km' => $distance_km,
                'approximate' => true,
                'source' => 'straight_line',
                'route_via_roads' => [],
                'route_via_districts' => [],
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Fleet calculateDistance: connection error', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'Cannot reach map service. Check that the server has internet access.',
            ], 502);
        } catch (\Exception $e) {
            Log::error('Fleet calculateDistance: exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => 'Could not calculate distance. ' . $e->getMessage(),
            ], 500);
        }
    }

    /** Straight-line distance in km (Haversine). */
    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }

    /** Extract road/street names from OSRM route response (steps with names). */
    private function extractRouteRoadNames(array $osrm): array
    {
        $roads = [];
        if (empty($osrm['routes'][0]['legs'])) {
            return $roads;
        }
        foreach ($osrm['routes'][0]['legs'] as $leg) {
            foreach ($leg['steps'] ?? [] as $step) {
                $name = $step['name'] ?? null;
                if (is_string($name) && trim($name) !== '' && !in_array($name, $roads, true)) {
                    $roads[] = trim($name);
                }
            }
        }
        return $roads;
    }

    /**
     * Get districts/regions (wilaya, mikoa) along the route by sampling points and reverse geocoding.
     * Returns unique list in order from origin to destination.
     */
    private function getDistrictsAlongRoute(array $osrm, string $userAgent): array
    {
        $coords = $this->extractRouteCoordinates($osrm);
        if (empty($coords)) {
            return [];
        }
        $maxPoints = 8;
        $step = max(1, (int) floor(count($coords) / ($maxPoints - 1)));
        $sampleIndices = [0];
        for ($i = $step; $i < count($coords) - 1; $i += $step) {
            $sampleIndices[] = $i;
        }
        if (count($coords) > 1) {
            $sampleIndices[] = count($coords) - 1;
        }
        $sampleIndices = array_unique($sampleIndices);
        sort($sampleIndices);

        $districts = [];
        foreach ($sampleIndices as $idx) {
            $c = $coords[$idx];
            $lat = $c[1];
            $lon = $c[0];
            $name = $this->reverseGeocodeDistrict($lat, $lon, $userAgent);
            if ($name !== '' && !in_array($name, $districts, true)) {
                $districts[] = $name;
            }
            usleep(1100000); // 1.1 sec between Nominatim calls (usage policy)
        }
        return $districts;
    }

    /** Extract [lon, lat] coordinates from OSRM route geometry (GeoJSON). */
    private function extractRouteCoordinates(array $osrm): array
    {
        if (empty($osrm['routes'][0]['geometry']['coordinates'])) {
            return [];
        }
        return $osrm['routes'][0]['geometry']['coordinates'];
    }

    /** Reverse geocode one point to get district/county/state (region) name. */
    private function reverseGeocodeDistrict(float $lat, float $lon, string $userAgent): string
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => $userAgent])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $lat,
                    'lon' => $lon,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'zoom' => 8,
                ]);
            if (!$response->successful()) {
                return '';
            }
            $data = $response->json();
            $addr = $data['address'] ?? [];
            // Prefer county (district), then state (region), then town
            if (!empty($addr['county'])) {
                return trim((string) $addr['county']);
            }
            if (!empty($addr['state'])) {
                return trim((string) $addr['state']);
            }
            if (!empty($addr['state_district'])) {
                return trim((string) $addr['state_district']);
            }
            if (!empty($addr['town'])) {
                return trim((string) $addr['town']);
            }
            if (!empty($addr['municipality'])) {
                return trim((string) $addr['municipality']);
            }
            if (!empty($addr['village'])) {
                return trim((string) $addr['village']);
            }
            return '';
        } catch (\Exception $e) {
            Log::debug('Fleet reverseGeocodeDistrict failed', ['lat' => $lat, 'lon' => $lon, 'message' => $e->getMessage()]);
            return '';
        }
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:assets,id',
            'driver_id' => 'nullable|exists:fleet_drivers,id',
            'customer_id' => 'nullable|exists:customers,id',
            'trip_type' => 'required|in:delivery,pickup,service,transport,other',
            'cargo_description' => 'nullable|string',
            'origin_location' => 'nullable|string|max:255',
            'destination_location' => 'nullable|string|max:255',
            'planned_start_date' => 'nullable|date',
            'planned_end_date' => 'nullable|date|after_or_equal:planned_start_date',
            'planned_distance_km' => 'nullable|numeric|min:0',
            'planned_fuel_consumption_liters' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Auto-generate trip number using prefix from settings
        $prefix = $this->getTripNumberPrefix() ?: 'TRP-';
        $tripNumber = $prefix . date('Y') . '-' . strtoupper(Str::random(6));

        // Get default trip status from settings
        $defaultStatus = $this->getDefaultTripStatus();
        $approvalStatus = $this->isTripApprovalRequired() ? 'pending' : 'approved';

        $trip = FleetTrip::create(array_merge($validated, [
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'trip_number' => $tripNumber,
            'status' => $defaultStatus,
            'approval_status' => $approvalStatus,
            'created_by' => $user->id,
        ]));

        return redirect()->route('fleet.trips.index')->with('success', 'Trip created successfully.');
    }

    public function show(FleetTrip $trip)
    {
        $user = Auth::user();

        if ($trip->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this trip.');
        }

        $trip->load(['vehicle', 'driver', 'customer', 'department', 'costs.costCategory', 'createdBy', 'updatedBy']);

        // Calculate actual revenue from invoice items that reference this trip
        // First check invoices with direct trip_id relationship
        $directInvoiceIds = \App\Models\Fleet\FleetInvoice::where('trip_id', $trip->id)->pluck('id');
        
        // Then check invoice items that reference this trip
        $itemInvoiceIds = \App\Models\Fleet\FleetInvoiceItem::where('trip_id', $trip->id)
            ->distinct()
            ->pluck('fleet_invoice_id');
        
        // Combine both (avoid double counting if invoice has both trip_id and items with trip_id)
        $allInvoiceIds = $directInvoiceIds->merge($itemInvoiceIds)->unique()->filter();
        
        if ($allInvoiceIds->count() > 0) {
            $actualRevenue = \App\Models\Fleet\FleetInvoice::whereIn('id', $allInvoiceIds)->sum('total_amount');
            $paidAmount = \App\Models\Fleet\FleetInvoice::whereIn('id', $allInvoiceIds)->sum('paid_amount');
        } else {
            $actualRevenue = 0;
            $paidAmount = 0;
        }

        $approvalRequired = $this->isTripApprovalRequired();
        return view('fleet.trips.show', compact('trip', 'actualRevenue', 'paidAmount', 'approvalRequired'));
    }

    public function edit(FleetTrip $trip)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        if ($trip->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this trip.');
        }

        // Only allow editing if trip is in planned or dispatched status
        if (!in_array($trip->status, ['planned', 'dispatched'])) {
            return redirect()->route('fleet.trips.show', $trip->hash_id)
                ->with('error', 'Cannot edit trip in ' . $trip->status . ' status.');
        }

        // Get only fleet vehicles (Motor Vehicles category FA04), same as fleet/vehicles index
        $vehicles = Asset::where('company_id', $user->company_id)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->where(function($q) use ($trip) {
                $q->where('operational_status', 'available')
                  ->orWhere('id', $trip->vehicle_id);
            })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['assignedDriver' => function($q) {
                $q->select('id', 'assigned_vehicle_id', 'full_name', 'license_number', 'status');
            }])
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number', 'operational_status', 'capacity_volume']);

        // Get available drivers
        $drivers = FleetDriver::where('company_id', $user->company_id)
            ->where('status', 'active')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'license_number']);

        // Get customers (with phone)
        $customers = Customer::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('fleet.trips.edit', compact('trip', 'vehicles', 'drivers', 'customers'));
    }

    public function update(Request $request, FleetTrip $trip)
    {
        $user = Auth::user();

        if ($trip->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this trip.');
        }

        // Only allow editing if trip is in planned or dispatched status
        if (!in_array($trip->status, ['planned', 'dispatched'])) {
            return redirect()->route('fleet.trips.show', $trip->hash_id)
                ->with('error', 'Cannot edit trip in ' . $trip->status . ' status.');
        }

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:assets,id',
            'driver_id' => 'nullable|exists:fleet_drivers,id',
            'customer_id' => 'nullable|exists:customers,id',
            'trip_type' => 'required|in:delivery,pickup,service,transport,other',
            'cargo_description' => 'nullable|string',
            'origin_location' => 'nullable|string|max:255',
            'destination_location' => 'nullable|string|max:255',
            'planned_start_date' => 'nullable|date',
            'planned_end_date' => 'nullable|date|after_or_equal:planned_start_date',
            'planned_distance_km' => 'nullable|numeric|min:0',
            'planned_fuel_consumption_liters' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $trip->update(array_merge($validated, [
            'updated_by' => $user->id,
        ]));

        return redirect()->route('fleet.trips.show', $trip->hash_id)->with('success', 'Trip updated successfully.');
    }

    public function approve(Request $request, FleetTrip $trip)
    {
        $user = Auth::user();
        if ($trip->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this trip.');
        }
        if ($trip->approval_status !== 'pending') {
            return redirect()->route('fleet.trips.show', $trip->hash_id)
                ->with('error', 'Trip is not pending approval.');
        }
        $trip->update([
            'approval_status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'updated_by' => $user->id,
        ]);
        return redirect()->route('fleet.trips.show', $trip->hash_id)->with('success', 'Trip approved.');
    }

    public function reject(Request $request, FleetTrip $trip)
    {
        $user = Auth::user();
        if ($trip->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this trip.');
        }
        if ($trip->approval_status !== 'pending') {
            return redirect()->route('fleet.trips.show', $trip->hash_id)
                ->with('error', 'Trip is not pending approval.');
        }
        $trip->update([
            'approval_status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'updated_by' => $user->id,
        ]);
        return redirect()->route('fleet.trips.show', $trip->hash_id)->with('success', 'Trip rejected.');
    }

    public function dispatch(FleetTrip $trip)
    {
        $user = Auth::user();

        if ($trip->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this trip.');
        }

        if ($trip->status !== 'planned') {
            return redirect()->route('fleet.trips.show', $trip->hash_id)
                ->with('error', 'Only planned trips can be dispatched.');
        }
        if ($this->isTripApprovalRequired() && $trip->approval_status !== 'approved') {
            return redirect()->route('fleet.trips.show', $trip->hash_id)
                ->with('error', 'Trip must be approved before dispatch.');
        }

        return view('fleet.trips.dispatch', compact('trip'));
    }

    public function dispatchStore(Request $request, FleetTrip $trip)
    {
        $user = Auth::user();

        if ($trip->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this trip.');
        }

        if ($trip->status !== 'planned') {
            return redirect()->route('fleet.trips.show', $trip->hash_id)
                ->with('error', 'Only planned trips can be dispatched.');
        }
        if ($this->isTripApprovalRequired() && $trip->approval_status !== 'approved') {
            return redirect()->route('fleet.trips.show', $trip->hash_id)
                ->with('error', 'Trip must be approved before dispatch.');
        }

        $validated = $request->validate([
            'start_odometer' => 'nullable|numeric|min:0',
            'start_fuel_level' => 'nullable|numeric|min:0',
            'actual_start_date' => 'required|date',
        ]);

        $trip->update(array_merge($validated, [
            'status' => 'dispatched',
            'actual_start_date' => $request->actual_start_date,
            'updated_by' => $user->id,
        ]));

        // Update vehicle status to assigned
        if ($trip->vehicle) {
            $trip->vehicle->update(['operational_status' => 'assigned']);
        }

        return redirect()->route('fleet.trips.show', $trip->hash_id)->with('success', 'Trip dispatched successfully.');
    }

    public function complete(FleetTrip $trip)
    {
        $user = Auth::user();

        if ($trip->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this trip.');
        }

        if (!in_array($trip->status, ['dispatched', 'in_progress'])) {
            return redirect()->route('fleet.trips.show', $trip->hash_id)
                ->with('error', 'Only dispatched or in-progress trips can be completed.');
        }

        return view('fleet.trips.complete', compact('trip'));
    }

    public function completeStore(Request $request, FleetTrip $trip)
    {
        $user = Auth::user();

        if ($trip->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this trip.');
        }

        if (!in_array($trip->status, ['dispatched', 'in_progress'])) {
            return redirect()->route('fleet.trips.show', $trip->hash_id)
                ->with('error', 'Only dispatched or in-progress trips can be completed.');
        }

        $validated = $request->validate([
            'end_odometer' => 'required|numeric|min:0',
            'actual_end_date' => 'required|date|after_or_equal:actual_start_date',
            'actual_distance_km' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:5000',
        ]);

        // Calculate actual distance if not provided
        if (empty($validated['actual_distance_km']) && $trip->start_odometer !== null && isset($validated['end_odometer'])) {
            $validated['actual_distance_km'] = $validated['end_odometer'] - (float) $trip->start_odometer;
        }

        // Calculate total costs
        $totalCosts = $trip->costs()->sum('amount');
        $variableCosts = $trip->costs()->where('cost_type', '!=', 'insurance')->sum('amount');
        $fixedCosts = $trip->costs()->where('cost_type', 'insurance')->sum('amount');

        // Use planned revenue for actual revenue on completion (revenue can be updated via invoices)
        $actualRevenue = $trip->planned_revenue ?? 0;
        $profitLoss = $actualRevenue - $totalCosts;

        $trip->update(array_merge($validated, [
            'status' => 'completed',
            'is_completed' => true,
            'completed_at' => now(),
            'completed_by' => $user->id,
            'total_costs' => $totalCosts,
            'variable_costs' => $variableCosts,
            'fixed_costs_allocated' => $fixedCosts,
            'actual_revenue' => $actualRevenue,
            'profit_loss' => $profitLoss,
            'updated_by' => $user->id,
        ]));

        // Update vehicle status back to available
        if ($trip->vehicle) {
            $trip->vehicle->update(['operational_status' => 'available']);
        }

        return redirect()->route('fleet.trips.show', $trip->hash_id)->with('success', 'Trip completed successfully.');
    }

    public function destroy(FleetTrip $trip)
    {
        $user = Auth::user();

        if ($trip->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this trip.');
        }

        // Only allow deletion if trip is in planned or cancelled status
        if (!in_array($trip->status, ['planned', 'cancelled'])) {
            return redirect()->route('fleet.trips.index')
                ->with('error', 'Cannot delete trip in ' . $trip->status . ' status.');
        }

        $trip->delete();

        return redirect()->route('fleet.trips.index')->with('success', 'Trip deleted successfully.');
    }
}
