<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Traits\Fleet\HasFleetSettings;
use App\Models\Fleet\FleetTripCost;
use App\Models\Fleet\FleetTrip;
use App\Models\Fleet\FleetCostCategory;
use App\Models\Assets\Asset;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class FleetTripCostController extends Controller
{
    use HasFleetSettings;
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        // If trip_id (hashid) is provided, show costs for that trip
        $tripHashId = $request->get('trip_id');
        $trip = null;
        
        if ($tripHashId) {
            // Decode hashid to get actual trip ID
            $trip = FleetTrip::where('company_id', $user->company_id)
                ->where(function($q) use ($tripHashId) {
                    // Try to decode hashid first
                    $decoded = \Vinkla\Hashids\Facades\Hashids::decode($tripHashId);
                    if (!empty($decoded)) {
                        $q->where('id', $decoded[0]);
                    } else {
                        // Fallback to direct ID if hashid decode fails
                        $q->where('id', $tripHashId);
                    }
                })
                ->firstOrFail();
        }

        // Calculate dashboard statistics
        $costQuery = FleetTripCost::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($trip, fn($q) => $q->where('trip_id', $trip->id));

        $totalCosts = $costQuery->count();
        $totalAmount = $costQuery->sum('amount');
        $approvedCosts = (clone $costQuery)->where('approval_status', 'approved')->count();
        $pendingCosts = (clone $costQuery)->where('approval_status', 'pending')->count();

        return view('fleet.trip-costs.index', compact('trip', 'totalCosts', 'totalAmount', 'approvedCosts', 'pendingCosts'));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $query = FleetTripCost::query()
            ->where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['trip', 'vehicle', 'costCategory', 'glAccount']);

        // Filter by trip if provided (can be hashid or numeric ID)
        if ($request->filled('trip_id')) {
            $tripHashId = $request->trip_id;
            $decoded = \Vinkla\Hashids\Facades\Hashids::decode($tripHashId);
            if (!empty($decoded)) {
                $query->where('trip_id', $decoded[0]);
            } else {
                $query->where('trip_id', $tripHashId);
            }
        }

        // Filter by cost type
        if ($request->filled('cost_type')) {
            $query->where('cost_type', $request->cost_type);
        }

        // Filter by approval status
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        // Filter by vehicle
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $costs = $query->get();
        
        if ($costs->isEmpty()) {
            return response()->json([
                'draw' => intval($request->draw ?? 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ], 200, [], JSON_UNESCAPED_SLASHES);
        }
        
        $data = [];
        $filterByTrip = $request->filled('trip_id');
        
        if ($filterByTrip) {
            // When viewing a single trip: show one row per cost so all data is visible; allow approving multiple
            foreach ($costs as $cost) {
                $costIds = [$cost->id];
                $groupCosts = collect([$cost]);
                $hashIds = array_map(function($id) { return \Vinkla\Hashids\Facades\Hashids::encode($id); }, $costIds);
                $costIdsStr = implode(',', $hashIds);
                $isPending = $cost->approval_status === 'pending';
                $checkbox = $isPending
                    ? '<input type="checkbox" class="cost-row-checkbox" data-cost-ids="' . e($costIdsStr) . '" aria-label="Select">'
                    : '<span class="text-muted">—</span>';
                $data[] = [
                    'checkbox' => $checkbox,
                    'date_incurred' => $cost->date_incurred->format('Y-m-d'),
                    'trip_display' => $cost->trip ? '<a href="' . route('fleet.trips.show', $cost->trip->hash_id) . '">' . $cost->trip->trip_number . '</a>' : '<span class="text-muted">N/A</span>',
                    'vehicle_display' => $cost->vehicle ? $cost->vehicle->name . ' (' . ($cost->vehicle->registration_number ?? 'N/A') . ')' : '<span class="text-muted">N/A</span>',
                    'cost_type_display' => '<span class="badge bg-' . ($this->getCostTypeColor($cost->cost_type)) . '">' . ucfirst(str_replace('_', ' ', $cost->cost_type)) . '</span>',
                    'description' => $cost->receipt_number ? 'Receipt: ' . $cost->receipt_number : ($cost->description ?? 'N/A'),
                    'amount_display' => number_format($cost->amount, 2) . ' ' . ($cost->currency ?? 'TZS'),
                    'approval_status_display' => $this->getGroupStatusBadge($groupCosts),
                    'actions' => $this->getGroupActions($costIds, $groupCosts),
                ];
            }
        } else {
            // Group by trip_id + receipt+date+created_at so each trip shows as a separate row
            $grouped = $costs->groupBy(function($cost) {
                $tripId = $cost->trip_id ?? 'no-trip';
                $receipt = $cost->receipt_number ?? 'no-receipt';
                $date = $cost->date_incurred->format('Y-m-d');
                $createdAt = $cost->created_at ? $cost->created_at->format('Y-m-d H:i') : '';
                return $tripId . '-' . $receipt . '-' . $date . '-' . $createdAt;
            });
            foreach ($grouped as $groupKey => $groupCosts) {
                $firstCost = $groupCosts->first();
                $totalAmount = $groupCosts->sum('amount');
                $costCount = $groupCosts->count();
                $costIds = $groupCosts->pluck('id')->toArray();
                $hashIds = array_map(function($id) { return \Vinkla\Hashids\Facades\Hashids::encode($id); }, $costIds);
                $costIdsStr = implode(',', $hashIds);
                $allPending = $groupCosts->every(fn($c) => $c->approval_status === 'pending');
                $checkbox = $allPending
                    ? '<input type="checkbox" class="cost-row-checkbox" data-cost-ids="' . e($costIdsStr) . '" aria-label="Select">'
                    : '<span class="text-muted">—</span>';
                $data[] = [
                    'checkbox' => $checkbox,
                    'date_incurred' => $firstCost->date_incurred->format('Y-m-d'),
                    'trip_display' => $firstCost->trip ? '<a href="' . route('fleet.trips.show', $firstCost->trip->hash_id) . '">' . $firstCost->trip->trip_number . '</a>' : '<span class="text-muted">N/A</span>',
                    'vehicle_display' => $firstCost->vehicle ? $firstCost->vehicle->name . ' (' . ($firstCost->vehicle->registration_number ?? 'N/A') . ')' : '<span class="text-muted">N/A</span>',
                    'cost_type_display' => $costCount > 1 ? '<span class="badge bg-info">Multiple (' . $costCount . ')</span>' : '<span class="badge bg-' . ($this->getCostTypeColor($firstCost->cost_type)) . '">' . ucfirst(str_replace('_', ' ', $firstCost->cost_type)) . '</span>',
                    'description' => $firstCost->receipt_number ? 'Receipt: ' . $firstCost->receipt_number : ($costCount > 1 ? 'Multiple Costs (' . $costCount . ' items)' : ($firstCost->description ?? 'N/A')),
                    'amount_display' => number_format($totalAmount, 2) . ' ' . ($firstCost->currency ?? 'TZS'),
                    'approval_status_display' => $this->getGroupStatusBadge($groupCosts),
                    'actions' => $this->getGroupActions($costIds, $groupCosts),
                ];
            }
        }
        
        // Return as DataTables response with proper formatting
        return response()->json([
            'draw' => intval($request->draw ?? 1),
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'data' => $data
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        // Get trip if provided (can be hashid)
        $trip = null;
        if ($request->filled('trip_id')) {
            $tripHashId = $request->trip_id;
            $trip = FleetTrip::where('company_id', $user->company_id)
                ->where(function($q) use ($tripHashId) {
                    // Try to decode hashid first
                    $decoded = \Vinkla\Hashids\Facades\Hashids::decode($tripHashId);
                    if (!empty($decoded)) {
                        $q->where('id', $decoded[0]);
                    } else {
                        // Fallback to direct ID if hashid decode fails
                        $q->where('id', $tripHashId);
                    }
                })
                ->firstOrFail();
        }

        // Get only active trips (exclude completed so costs are not allocated to same trip twice)
        $trips = FleetTrip::with('vehicle')
            ->where('company_id', $user->company_id)
            ->whereNotIn('status', ['completed'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('trip_number', 'desc')
            ->get(['id', 'trip_number', 'vehicle_id', 'planned_start_date', 'actual_start_date'])
            ->map(function($t) {
                $date = $t->actual_start_date ?? $t->planned_start_date;
                $dateStr = $date ? $date->format('d-M-Y') : '';
                return [
                    'id' => $t->id,
                    'trip_number' => $t->trip_number,
                    'vehicle_name' => $t->vehicle->name ?? 'N/A',
                    'date' => $dateStr
                ];
            })
            ->values();

        // Get bank accounts for "paid from" field (same as bank accounts page)
        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();

        // Get vehicles
        $vehicles = Asset::where('company_id', $user->company_id)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number']);

        // Get cost categories
        $costCategories = FleetCostCategory::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category_type']);

        // Get GL accounts for expenses
        // ChartAccount -> accountClassGroup -> accountClass
        $glAccounts = \App\Models\ChartAccount::whereHas('accountClassGroup', function($q) use ($user) {
                $q->where('company_id', $user->company_id)
                  ->whereHas('accountClass', function($classQ) {
                      $classQ->whereRaw('LOWER(name) LIKE ?', ['%expense%']); // Expense accounts
                  });
            })
            ->orderBy('account_name')
            ->get(['id', 'account_code', 'account_name']);

        return view('fleet.trip-costs.create', compact('trip', 'trips', 'vehicles', 'costCategories', 'glAccounts', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $validated = $request->validate([
            'trip_lines' => 'required|array|min:1',
            'trip_lines.*.trip_id' => 'required|exists:fleet_trips,id',
            'trip_lines.*.date_incurred' => 'required|date',
            'paid_from_account_id' => 'required|exists:bank_accounts,id',
            'receipt_number' => 'nullable|string|max:100',
            'is_billable_to_customer' => 'boolean',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
            'cost_lines' => 'required|array|min:1',
            'cost_lines.*.gl_account_id' => 'required|exists:chart_accounts,id',
            'cost_lines.*.qty' => 'nullable|numeric|min:0',
            'cost_lines.*.amount' => 'required|numeric|min:0',
            'cost_lines.*.cost_category_id' => 'nullable|exists:fleet_cost_categories,id',
            'cost_lines.*.description' => 'nullable|string',
        ]);

        $tripLines = $validated['trip_lines'];
        $numTrips = count($tripLines);

        // Ensure all trips belong to user's company
        $tripIds = array_column($tripLines, 'trip_id');
        $trips = FleetTrip::where('company_id', $user->company_id)->whereIn('id', $tripIds)->get()->keyBy('id');
        if ($trips->count() !== $numTrips) {
            return redirect()->back()->withInput()->with('error', 'One or more trips not found or unauthorized.');
        }

        // Handle file uploads
        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('fleet-trip-cost-attachments', 'public');
                $attachmentPaths[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            }
        }

        $createdCosts = [];
        $totalAmount = 0;

        foreach ($validated['cost_lines'] as $line) {
            $lineTotal = (float) $line['amount'];
            $amountPerTrip = $numTrips > 0 ? round($lineTotal / $numTrips, 2) : 0;
            $remainder = $numTrips > 0 ? round($lineTotal - ($amountPerTrip * $numTrips), 2) : 0;

            $tripIdx = 0;
            foreach ($tripLines as $tripLine) {
                $trip = $trips->get($tripLine['trip_id']);
                if (!$trip) continue;

                // New costs always start as pending so they can be edited, deleted, or approved
                $approvalStatus = 'pending';

                $amt = $amountPerTrip + ($tripIdx === $numTrips - 1 ? $remainder : 0);
                $cost = FleetTripCost::create([
                    'company_id' => $user->company_id,
                    'branch_id' => $branchId,
                    'trip_id' => $trip->id,
                    'vehicle_id' => $trip->vehicle_id,
                    'cost_category_id' => $line['cost_category_id'] ?? null,
                    'cost_type' => 'other',
                    'gl_account_id' => $line['gl_account_id'],
                    'amount' => $amt,
                    'description' => $line['description'] ?? null,
                    'date_incurred' => $tripLine['date_incurred'],
                    'receipt_number' => $validated['receipt_number'] ?? null,
                    'is_billable_to_customer' => $validated['is_billable_to_customer'] ?? false,
                    'attachments' => !empty($attachmentPaths) ? $attachmentPaths : null,
                    'approval_status' => $approvalStatus,
                    'paid_from_bank_account_id' => $validated['paid_from_account_id'] ?? null,
                    'created_by' => $user->id,
                ]);

                $createdCosts[] = $cost;
                $totalAmount += $amt;
                $tripIdx++;
            }
        }

        // Use first cost id as batch transaction_id for ALL GL entries so accounting transaction details show balanced debit = credit
        $batchTransactionId = $createdCosts[0]->id ?? 0;
        $batchDate = (array_values($tripLines)[0] ?? [])['date_incurred'] ?? now()->format('Y-m-d');

        foreach ($createdCosts as $cost) {
            $trip = $trips->get($cost->trip_id);
            \App\Models\GlTransaction::create([
                'branch_id' => $branchId,
                'chart_account_id' => $cost->gl_account_id,
                'amount' => $cost->amount,
                'nature' => 'debit',
                'transaction_id' => $batchTransactionId,
                'transaction_type' => 'fleet_trip_cost',
                'date' => $cost->date_incurred,
                'description' => $cost->description ?? "Fleet Trip Cost - " . ($trip ? $trip->trip_number : ''),
                'user_id' => $user->id,
            ]);
        }

        $bankAccount = BankAccount::findOrFail($validated['paid_from_account_id']);
        \App\Models\GlTransaction::create([
            'branch_id' => $branchId,
            'chart_account_id' => $bankAccount->chart_account_id,
            'amount' => $totalAmount,
            'nature' => 'credit',
            'transaction_id' => $batchTransactionId,
            'transaction_type' => 'fleet_trip_cost',
            'date' => $batchDate,
            'description' => 'Payment for Fleet Trip Costs',
            'user_id' => $user->id,
        ]);

        foreach ($trips as $trip) {
            $this->updateTripCosts($trip);
        }

        $message = count($createdCosts) . ' cost record(s) created and divided across ' . $numTrips . ' trip(s).';
        return redirect()->route('fleet.trip-costs.index')->with('success', $message);
    }

    public function show(FleetTripCost $cost)
    {
        $user = Auth::user();

        if ($cost->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this cost.');
        }

        $cost->load(['trip', 'vehicle', 'costCategory', 'glAccount', 'approvedBy', 'createdBy']);

        return view('fleet.trip-costs.show', compact('cost'));
    }

    public function edit(FleetTripCost $cost)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        if ($cost->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this cost.');
        }

        if ($cost->approval_status !== 'pending') {
            return redirect()->route('fleet.trip-costs.show', $cost->hash_id)
                ->with('error', 'Cannot edit approved or rejected costs.');
        }

        // Get cost categories
        $costCategories = FleetCostCategory::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category_type']);

        // Get GL accounts
        // ChartAccount -> accountClassGroup -> accountClass
        $glAccounts = \App\Models\ChartAccount::whereHas('accountClassGroup', function($q) use ($user) {
                $q->where('company_id', $user->company_id)
                  ->whereHas('accountClass', function($classQ) {
                      $classQ->whereRaw('LOWER(name) LIKE ?', ['%expense%']); // Expense accounts
                  });
            })
            ->orderBy('account_name')
            ->get(['id', 'account_code', 'account_name']);

        // Get bank accounts for "paid from" field (same as bank accounts page)
        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();

        // Get existing cost lines for this cost (if it's a single cost, convert to line format)
        $costLines = [[
            'gl_account_id' => $cost->gl_account_id,
            'qty' => 1,
            'amount' => $cost->amount,
            'cost_category_id' => $cost->cost_category_id,
            'description' => $cost->description,
        ]];

        // Get the paid from bank account from GL transaction (credit entry). Batch may use first cost id as transaction_id.
        $batchCostIds = FleetTripCost::where('company_id', $user->company_id)
            ->where('date_incurred', $cost->date_incurred)
            ->when($cost->receipt_number, fn($q) => $q->where('receipt_number', $cost->receipt_number))
            ->when(!$cost->receipt_number, fn($q) => $q->whereNull('receipt_number'))
            ->pluck('id');
        $paidFromGlTransaction = \App\Models\GlTransaction::where('transaction_type', 'fleet_trip_cost')
            ->whereIn('transaction_id', $batchCostIds->toArray())
            ->where('nature', 'credit')
            ->orderBy('id', 'desc')
            ->first();
        if (!$paidFromGlTransaction) {
            $paidFromGlTransaction = \App\Models\GlTransaction::where('transaction_type', 'fleet_trip_cost')
                ->where('transaction_id', $cost->id)
                ->where('nature', 'credit')
                ->first();
        }
        
        $selectedBankAccountId = null;
        if ($cost->paid_from_bank_account_id) {
            $selectedBankAccount = BankAccount::where('id', $cost->paid_from_bank_account_id)
                ->where('company_id', $user->company_id)
                ->first();
            if ($selectedBankAccount) {
                $selectedBankAccountId = $selectedBankAccount->id;
            }
        }
        if (!$selectedBankAccountId && $paidFromGlTransaction) {
            // Find bank account by chart_account_id
            $selectedBankAccount = BankAccount::where('chart_account_id', $paidFromGlTransaction->chart_account_id)
                ->where('company_id', $user->company_id)
                ->first();
            if ($selectedBankAccount) {
                $selectedBankAccountId = $selectedBankAccount->id;
            }
        }

        return view('fleet.trip-costs.edit', compact('cost', 'costCategories', 'glAccounts', 'bankAccounts', 'costLines', 'selectedBankAccountId'));
    }

    public function update(Request $request, FleetTripCost $cost)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        if ($cost->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this cost.');
        }

        if ($cost->approval_status !== 'pending') {
            return redirect()->route('fleet.trip-costs.show', $cost->hash_id)
                ->with('error', 'Cannot edit approved or rejected costs.');
        }

        $validated = $request->validate([
            'date_incurred' => 'required|date',
            'paid_from_account_id' => 'required|exists:bank_accounts,id',
            'receipt_number' => 'nullable|string|max:100',
            'is_billable_to_customer' => 'boolean',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
            'cost_lines' => 'required|array|min:1',
            'cost_lines.*.gl_account_id' => 'required|exists:chart_accounts,id',
            'cost_lines.*.qty' => 'nullable|numeric|min:0',
            'cost_lines.*.amount' => 'required|numeric|min:0',
            'cost_lines.*.cost_category_id' => 'nullable|exists:fleet_cost_categories,id',
            'cost_lines.*.description' => 'nullable|string',
        ]);

        // Handle file uploads
        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('fleet-trip-cost-attachments', 'public');
                $attachmentPaths[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            }
        } else {
            // Keep existing attachments if no new ones uploaded
            $attachmentPaths = $cost->attachments ?? [];
        }

        // Find batch (same receipt + date) so we delete all GL for the batch and recreate (keeps debit = credit on details)
        $batchCosts = FleetTripCost::where('company_id', $user->company_id)
            ->where('date_incurred', $cost->date_incurred)
            ->when($cost->receipt_number, fn($q) => $q->where('receipt_number', $cost->receipt_number))
            ->when(!$cost->receipt_number, fn($q) => $q->whereNull('receipt_number'))
            ->orderBy('id')
            ->get();
        $batchTransactionId = $batchCosts->first()->id;
        \App\Models\GlTransaction::where('transaction_type', 'fleet_trip_cost')
            ->where('transaction_id', $batchTransactionId)
            ->delete();

        // Update the first cost line (existing cost record)
        $firstLine = $validated['cost_lines'][0];
        $totalAmount = (float) $firstLine['amount'];
        
        $cost->update([
            'cost_category_id' => $firstLine['cost_category_id'] ?? null,
            'cost_type' => 'other',
            'gl_account_id' => $firstLine['gl_account_id'],
            'amount' => $firstLine['amount'],
            'description' => $firstLine['description'] ?? null,
            'date_incurred' => $validated['date_incurred'],
            'receipt_number' => $validated['receipt_number'] ?? null,
            'is_billable_to_customer' => $validated['is_billable_to_customer'] ?? false,
            'attachments' => !empty($attachmentPaths) ? $attachmentPaths : null,
            'paid_from_bank_account_id' => $validated['paid_from_account_id'],
            'updated_by' => $user->id,
        ]);

        // Create GL debits and one credit using batch transaction_id so transaction details balance
        \App\Models\GlTransaction::create([
            'branch_id' => $branchId,
            'chart_account_id' => $cost->gl_account_id,
            'amount' => $cost->amount,
            'nature' => 'debit',
            'transaction_id' => $batchTransactionId,
            'transaction_type' => 'fleet_trip_cost',
            'date' => $validated['date_incurred'],
            'description' => $cost->description ?? "Fleet Trip Cost - {$cost->trip->trip_number}",
            'user_id' => $user->id,
        ]);

        // Create additional cost records and GL for remaining lines (if any)
        $createdCosts = [$cost];
        if (count($validated['cost_lines']) > 1) {
            for ($i = 1; $i < count($validated['cost_lines']); $i++) {
                $line = $validated['cost_lines'][$i];
                $newCost = FleetTripCost::create([
                    'company_id' => $user->company_id,
                    'branch_id' => $branchId,
                    'trip_id' => $cost->trip_id,
                    'vehicle_id' => $cost->vehicle_id,
                    'cost_category_id' => $line['cost_category_id'] ?? null,
                    'cost_type' => 'other',
                    'gl_account_id' => $line['gl_account_id'],
                    'amount' => $line['amount'],
                    'description' => $line['description'] ?? null,
                    'date_incurred' => $validated['date_incurred'],
                    'receipt_number' => $validated['receipt_number'] ?? null,
                    'is_billable_to_customer' => $validated['is_billable_to_customer'] ?? false,
                    'attachments' => !empty($attachmentPaths) ? $attachmentPaths : null,
                    'approval_status' => 'pending',
                    'created_by' => $user->id,
                ]);

                \App\Models\GlTransaction::create([
                    'branch_id' => $branchId,
                    'chart_account_id' => $newCost->gl_account_id,
                    'amount' => $newCost->amount,
                    'nature' => 'debit',
                    'transaction_id' => $batchTransactionId,
                    'transaction_type' => 'fleet_trip_cost',
                    'date' => $validated['date_incurred'],
                    'description' => $newCost->description ?? "Fleet Trip Cost - {$cost->trip->trip_number}",
                    'user_id' => $user->id,
                ]);

                $createdCosts[] = $newCost;
                $totalAmount += (float) $line['amount'];
            }
        }

        // Recreate GL debits for other batch costs (they were deleted above; we only updated current cost from form)
        $otherBatchCosts = $batchCosts->where('id', '!=', $cost->id);
        foreach ($otherBatchCosts as $otherCost) {
            \App\Models\GlTransaction::create([
                'branch_id' => $branchId,
                'chart_account_id' => $otherCost->gl_account_id,
                'amount' => $otherCost->amount,
                'nature' => 'debit',
                'transaction_id' => $batchTransactionId,
                'transaction_type' => 'fleet_trip_cost',
                'date' => $validated['date_incurred'],
                'description' => $otherCost->description ?? "Fleet Trip Cost - " . ($otherCost->trip ? $otherCost->trip->trip_number : ''),
                'user_id' => $user->id,
            ]);
            $totalAmount += (float) $otherCost->amount;
        }

        // Get the bank account to get its chart_account_id
        $bankAccount = BankAccount::findOrFail($validated['paid_from_account_id']);
        
        // Create credit entry for paid from account
        \App\Models\GlTransaction::create([
            'branch_id' => $branchId,
            'chart_account_id' => $bankAccount->chart_account_id,
            'amount' => $totalAmount,
            'nature' => 'credit',
            'transaction_id' => $batchTransactionId,
            'transaction_type' => 'fleet_trip_cost',
            'date' => $validated['date_incurred'],
            'description' => "Payment for Fleet Trip Costs - {$cost->trip->trip_number}",
            'user_id' => $user->id,
        ]);

        // Update trip total costs
        if ($cost->trip) {
            $this->updateTripCosts($cost->trip);
        }

        $trip = $cost->trip;
        $redirectParams = isset($trip) ? ['trip_id' => $trip->hash_id] : [];
        return redirect()->route('fleet.trip-costs.index', $redirectParams)->with('success', 'Cost updated successfully.');
    }

    public function approve(Request $request, FleetTripCost $cost)
    {
        $user = Auth::user();

        if ($cost->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this cost.');
        }

        $cost->update([
            'approval_status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_notes' => $request->input('approval_notes'),
            'updated_by' => $user->id,
        ]);

        return redirect()->route('fleet.trip-costs.show', $cost->hash_id)->with('success', 'Cost approved successfully.');
    }

    /**
     * Approve multiple costs in batch
     */
    public function batchApprove(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'cost_ids' => 'required|string',
            'approval_notes' => 'nullable|string',
        ]);
        
        // Parse cost IDs (hashids)
        $costIdsArray = explode(',', $validated['cost_ids']);
        $costIdsArray = array_filter(array_map('trim', $costIdsArray));
        
        $numericIds = [];
        foreach ($costIdsArray as $id) {
            $decoded = \Vinkla\Hashids\Facades\Hashids::decode($id);
            if (!empty($decoded)) {
                $numericIds[] = $decoded[0];
            } elseif (is_numeric($id)) {
                $numericIds[] = (int)$id;
            }
        }
        
        if (empty($numericIds)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Invalid cost IDs provided.'], 400);
            }
            return redirect()->back()->with('error', 'Invalid cost IDs provided.');
        }
        
        $costs = FleetTripCost::where('company_id', $user->company_id)
            ->whereIn('id', $numericIds)
            ->get();
        
        if ($costs->isEmpty()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Costs not found.'], 404);
            }
            return redirect()->back()->with('error', 'Costs not found.');
        }
        
        // Check if all are pending
        $allPending = $costs->every(function($cost) {
            return $cost->approval_status === 'pending';
        });
        
        if (!$allPending) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Only pending costs can be approved.'], 400);
            }
            return redirect()->back()->with('error', 'Only pending costs can be approved.');
        }
        
        // Approve all costs
        foreach ($costs as $cost) {
            $cost->update([
                'approval_status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'approval_notes' => $validated['approval_notes'] ?? null,
                'updated_by' => $user->id,
            ]);
        }
        
        $successMessage = count($costs) . ' cost(s) approved successfully.';
        
        // Redirect back to the same view: use current_trip_id from request so user stays where they were (trip filter or all costs)
        $redirectTripId = $request->input('current_trip_id');
        $redirectTripId = is_string($redirectTripId) ? trim($redirectTripId) : '';
        $redirectParams = $redirectTripId !== '' ? ['trip_id' => $redirectTripId] : [];
        $redirectUrl = route('fleet.trip-costs.index', $redirectParams);
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'redirect' => $redirectUrl
            ]);
        }
        
        return redirect()->to($redirectUrl)->with('success', $successMessage);
    }

    public function reject(Request $request, FleetTripCost $cost)
    {
        $user = Auth::user();

        if ($cost->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this cost.');
        }

        $request->validate([
            'approval_notes' => 'required|string',
        ]);

        $cost->update([
            'approval_status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_notes' => $request->approval_notes,
            'updated_by' => $user->id,
        ]);

        return redirect()->route('fleet.trip-costs.show', $cost->hash_id)->with('success', 'Cost rejected.');
    }

    /**
     * View grouped costs (all costs with same receipt_number and date)
     */
    public function viewGroup(Request $request)
    {
        $user = Auth::user();
        $costIdsParam = $request->get('cost_ids', '');
        
        if (empty($costIdsParam)) {
            return redirect()->route('fleet.trip-costs.index')->with('error', 'No costs selected.');
        }
        
        // Parse cost_ids - can be comma-separated string of hashids or numeric IDs
        $costIdsArray = is_array($costIdsParam) ? $costIdsParam : explode(',', $costIdsParam);
        $costIdsArray = array_filter(array_map('trim', $costIdsArray));
        
        if (empty($costIdsArray)) {
            return redirect()->route('fleet.trip-costs.index')->with('error', 'No costs selected.');
        }
        
        // Decode hashids to numeric IDs
        $numericIds = [];
        foreach ($costIdsArray as $id) {
            // Try to decode as hashid first
            $decoded = \Vinkla\Hashids\Facades\Hashids::decode($id);
            if (!empty($decoded)) {
                $numericIds[] = $decoded[0];
            } else {
                // If not a hashid, assume it's a numeric ID
                if (is_numeric($id)) {
                    $numericIds[] = (int)$id;
                }
            }
        }
        
        if (empty($numericIds)) {
            return redirect()->route('fleet.trip-costs.index')->with('error', 'Invalid cost IDs provided.');
        }
        
        $costs = FleetTripCost::where('company_id', $user->company_id)
            ->whereIn('id', $numericIds)
            ->with(['trip', 'vehicle', 'costCategory', 'glAccount', 'createdBy'])
            ->orderBy('created_at')
            ->get();
        
        if ($costs->isEmpty()) {
            return redirect()->route('fleet.trip-costs.index')->with('error', 'Costs not found.');
        }
        
        $trip = $costs->first()->trip;
        $totalAmount = $costs->sum('amount');
        
        return view('fleet.trip-costs.view-group', compact('costs', 'trip', 'totalAmount'));
    }

    /**
     * Batch edit form for multiple costs
     */
    public function batchEdit(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        $costIdsParam = $request->get('cost_ids', '');
        
        if (empty($costIdsParam)) {
            return redirect()->route('fleet.trip-costs.index')->with('error', 'No costs selected.');
        }
        
        // Parse cost_ids
        $costIdsArray = is_array($costIdsParam) ? $costIdsParam : explode(',', $costIdsParam);
        $costIdsArray = array_filter(array_map('trim', $costIdsArray));
        
        // Decode hashids to numeric IDs
        $numericIds = [];
        foreach ($costIdsArray as $id) {
            $decoded = \Vinkla\Hashids\Facades\Hashids::decode($id);
            if (!empty($decoded)) {
                $numericIds[] = $decoded[0];
            } elseif (is_numeric($id)) {
                $numericIds[] = (int)$id;
            }
        }
        
        if (empty($numericIds)) {
            return redirect()->route('fleet.trip-costs.index')->with('error', 'Invalid cost IDs provided.');
        }
        
        $costs = FleetTripCost::where('company_id', $user->company_id)
            ->whereIn('id', $numericIds)
            ->with(['trip', 'vehicle', 'costCategory', 'glAccount'])
            ->orderBy('created_at')
            ->get();
        
        if ($costs->isEmpty()) {
            return redirect()->route('fleet.trip-costs.index')->with('error', 'Costs not found.');
        }
        
        // Check if all costs are pending
        $allPending = $costs->every(function($cost) {
            return $cost->approval_status === 'pending';
        });
        
        if (!$allPending) {
            return redirect()->route('fleet.trip-costs.index')->with('error', 'Cannot edit approved or rejected costs.');
        }
        
        $trip = $costs->first()->trip;
        
        // Get cost categories
        $costCategories = FleetCostCategory::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category_type']);
        
        // Get GL accounts
        $glAccounts = \App\Models\ChartAccount::whereHas('accountClassGroup', function($q) use ($user) {
                $q->where('company_id', $user->company_id)
                  ->whereHas('accountClass', function($classQ) {
                      $classQ->whereRaw('LOWER(name) LIKE ?', ['%expense%']);
                  });
            })
            ->orderBy('account_name')
            ->get(['id', 'account_code', 'account_name']);
        
        // Get bank accounts
        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();
        
        // Get paid from account from GL transaction
        // The credit GL transaction references the first cost ID of the batch (from store: $createdCosts[0]->id).
        // When we show one row per trip, "Edit" may pass only one trip's cost IDs, so the credit might be on another trip's cost.
        $firstCostId = $costs->first()->id;
        $costIds = $costs->pluck('id')->toArray();
        
        $paidFromGlTransaction = \App\Models\GlTransaction::where('transaction_type', 'fleet_trip_cost')
            ->whereIn('transaction_id', $costIds)
            ->where('nature', 'credit')
            ->orderBy('id', 'desc')
            ->first();
        
        // If not in current cost IDs, find credit from same batch (same receipt + date)
        if (!$paidFromGlTransaction) {
            $firstCost = $costs->first();
            $batchCostIds = FleetTripCost::where('company_id', $user->company_id)
                ->where('date_incurred', $firstCost->date_incurred->format('Y-m-d'))
                ->when($firstCost->receipt_number, fn($q) => $q->where('receipt_number', $firstCost->receipt_number))
                ->pluck('id')
                ->toArray();
            if (!empty($batchCostIds)) {
                $paidFromGlTransaction = \App\Models\GlTransaction::where('transaction_type', 'fleet_trip_cost')
                    ->whereIn('transaction_id', $batchCostIds)
                    ->where('nature', 'credit')
                    ->orderBy('id', 'desc')
                    ->first();
            }
        }
        
        if (!$paidFromGlTransaction) {
            $paidFromGlTransaction = \App\Models\GlTransaction::where('transaction_type', 'fleet_trip_cost')
                ->where('transaction_id', $firstCostId)
                ->where('nature', 'credit')
                ->first();
        }
        
        $selectedBankAccountId = null;
        $selectedBankAccount = null;

        // Prefer bank account stored on cost(s) when available (so it is always fetched on edit)
        $firstCostWithBank = $costs->first(function ($c) { return !empty($c->paid_from_bank_account_id); });
        if ($firstCostWithBank && $firstCostWithBank->paid_from_bank_account_id) {
            $selectedBankAccount = BankAccount::where('id', $firstCostWithBank->paid_from_bank_account_id)
                ->where('company_id', $user->company_id)
                ->first();
            if ($selectedBankAccount) {
                $selectedBankAccountId = $selectedBankAccount->id;
            }
        }
        
        if (!$selectedBankAccountId && $paidFromGlTransaction && $paidFromGlTransaction->chart_account_id) {
            // Try direct lookup first (company_id must match)
            $selectedBankAccount = BankAccount::where('chart_account_id', $paidFromGlTransaction->chart_account_id)
                ->where('company_id', $user->company_id)
                ->first();
            
            if ($selectedBankAccount) {
                $selectedBankAccountId = $selectedBankAccount->id;
            } else {
                // Try without company_id filter (in case of data inconsistency)
                $selectedBankAccount = BankAccount::where('chart_account_id', $paidFromGlTransaction->chart_account_id)->first();
                if ($selectedBankAccount && (int) $selectedBankAccount->company_id === (int) $user->company_id) {
                    $selectedBankAccountId = $selectedBankAccount->id;
                } else {
                    $selectedBankAccount = null;
                }
            }
        }
        
        // If still not found, try to find by checking all GL transactions for these costs
        if (!$selectedBankAccountId) {
            $allGlTransactions = \App\Models\GlTransaction::where('transaction_type', 'fleet_trip_cost')
                ->whereIn('transaction_id', $costIds)
                ->where('nature', 'credit')
                ->get();
            
            foreach ($allGlTransactions as $glTrans) {
                if ($glTrans->chart_account_id) {
                    $selectedBankAccount = BankAccount::where('chart_account_id', $glTrans->chart_account_id)
                        ->where('company_id', $user->company_id)
                        ->first();
                    if ($selectedBankAccount) {
                        $selectedBankAccountId = $selectedBankAccount->id;
                        break;
                    }
                }
            }
        }
        
        // Ensure we have the model for the view (e.g. when bank is not in filtered list)
        if ($selectedBankAccountId && !$selectedBankAccount) {
            $selectedBankAccount = BankAccount::find($selectedBankAccountId);
        }
        
        // Get existing attachments from all costs (they should be the same for grouped costs)
        $existingAttachments = $costs->first()->attachments ?? [];
        
        // Prepare cost lines from all costs
        $costLines = $costs->map(function($cost) {
            return [
                'id' => $cost->id,
                'hash_id' => $cost->hash_id,
                'gl_account_id' => $cost->gl_account_id,
                'qty' => 1,
                'amount' => $cost->amount,
                'cost_category_id' => $cost->cost_category_id,
                'description' => $cost->description,
            ];
        })->toArray();
        
        $costIds = $costs->pluck('hash_id')->toArray();
        
        return view('fleet.trip-costs.batch-edit', compact('costs', 'trip', 'costCategories', 'glAccounts', 'bankAccounts', 'costLines', 'selectedBankAccountId', 'selectedBankAccount', 'costIds', 'existingAttachments'));
    }

    /**
     * Update multiple costs in batch
     */
    public function batchUpdate(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;
        
        $validated = $request->validate([
            'cost_ids' => 'required|string',
            'date_incurred' => 'required|date',
            'paid_from_account_id' => 'required|exists:bank_accounts,id',
            'receipt_number' => 'nullable|string|max:100',
            'is_billable_to_customer' => 'boolean',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
            'deleted_attachments' => 'nullable|string',
            'cost_lines' => 'required|array|min:1',
            'cost_lines.*.id' => 'required|exists:fleet_trip_costs,id',
            'cost_lines.*.gl_account_id' => 'required|exists:chart_accounts,id',
            'cost_lines.*.qty' => 'nullable|numeric|min:0',
            'cost_lines.*.amount' => 'required|numeric|min:0',
            'cost_lines.*.cost_category_id' => 'nullable|exists:fleet_cost_categories,id',
            'cost_lines.*.description' => 'nullable|string',
        ]);
        
        // Parse cost IDs
        $costIdsArray = explode(',', $validated['cost_ids']);
        $costIdsArray = array_filter(array_map('trim', $costIdsArray));
        
        $numericIds = [];
        foreach ($costIdsArray as $id) {
            $decoded = \Vinkla\Hashids\Facades\Hashids::decode($id);
            if (!empty($decoded)) {
                $numericIds[] = $decoded[0];
            } elseif (is_numeric($id)) {
                $numericIds[] = (int)$id;
            }
        }
        
        $costs = FleetTripCost::where('company_id', $user->company_id)
            ->whereIn('id', $numericIds)
            ->get();
        
        if ($costs->isEmpty()) {
            return redirect()->route('fleet.trip-costs.index')->with('error', 'Costs not found.');
        }
        
        // Handle file uploads
        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('fleet-trip-cost-attachments', 'public');
                $attachmentPaths[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            }
        }
        
        $trip = $costs->first()->trip;
        $totalAmount = 0;
        
        // Parse deleted attachment indices
        $deletedIndices = [];
        if (!empty($validated['deleted_attachments'])) {
            $deletedIndices = array_map('intval', array_filter(explode(',', $validated['deleted_attachments'])));
        }
        
        // Delete existing GL transactions for all costs in this batch (batch uses first cost id as transaction_id)
        \App\Models\GlTransaction::where('transaction_type', 'fleet_trip_cost')
            ->whereIn('transaction_id', $numericIds)
            ->delete();
        
        $batchTransactionId = $costs->first()->id;

        // Update each cost
        foreach ($validated['cost_lines'] as $line) {
            $cost = $costs->firstWhere('id', $line['id']);
            if (!$cost) continue;
            
            // Handle attachments: merge existing (minus deleted) with new uploads
            $existingAttachments = $cost->attachments ?? [];
            if (!empty($deletedIndices)) {
                // Remove deleted attachments by index (use ARRAY_FILTER_USE_BOTH to get index)
                $existingAttachments = array_values(array_filter($existingAttachments, function($attachment, $index) use ($deletedIndices) {
                    return !in_array($index, $deletedIndices);
                }, ARRAY_FILTER_USE_BOTH));
            }
            
            // Merge with new uploads
            $finalAttachments = array_merge($existingAttachments, $attachmentPaths);
            
            $cost->update([
                'cost_category_id' => $line['cost_category_id'] ?? null,
                'cost_type' => 'other',
                'gl_account_id' => $line['gl_account_id'],
                'amount' => $line['amount'],
                'description' => $line['description'] ?? null,
                'date_incurred' => $validated['date_incurred'],
                'receipt_number' => $validated['receipt_number'] ?? null,
                'is_billable_to_customer' => $validated['is_billable_to_customer'] ?? false,
                'attachments' => !empty($finalAttachments) ? $finalAttachments : null,
                'paid_from_bank_account_id' => $validated['paid_from_account_id'],
                'updated_by' => $user->id,
            ]);
            
            // Create GL debit for this cost (use batch id so transaction details show balanced debit = credit)
            \App\Models\GlTransaction::create([
                'branch_id' => $branchId,
                'chart_account_id' => $cost->gl_account_id,
                'amount' => $cost->amount,
                'nature' => 'debit',
                'transaction_id' => $batchTransactionId,
                'transaction_type' => 'fleet_trip_cost',
                'date' => $validated['date_incurred'],
                'description' => $cost->description ?? "Fleet Trip Cost - {$trip->trip_number}",
                'user_id' => $user->id,
            ]);
            
            $totalAmount += $line['amount'];
        }
        
        // Get the bank account
        $bankAccount = BankAccount::findOrFail($validated['paid_from_account_id']);
        
        // Create credit entry for paid from account
        \App\Models\GlTransaction::create([
            'branch_id' => $branchId,
            'chart_account_id' => $bankAccount->chart_account_id,
            'amount' => $totalAmount,
            'nature' => 'credit',
            'transaction_id' => $batchTransactionId,
            'transaction_type' => 'fleet_trip_cost',
            'date' => $validated['date_incurred'],
            'description' => "Payment for Fleet Trip Costs - {$trip->trip_number}",
            'user_id' => $user->id,
        ]);
        
        // Update trip total costs
        if ($trip) {
            $this->updateTripCosts($trip);
        }
        
        $redirectParams = $trip ? ['trip_id' => $trip->hash_id] : [];
        return redirect()->route('fleet.trip-costs.index', $redirectParams)->with('success', count($costs) . ' costs updated successfully.');
    }

    /**
     * Print grouped costs as PDF
     */
    public function print(Request $request)
    {
        $user = Auth::user();
        $costIdsParam = $request->get('cost_ids', '');
        
        if (empty($costIdsParam)) {
            return redirect()->route('fleet.trip-costs.index')->with('error', 'No costs selected.');
        }
        
        // Parse cost_ids
        $costIdsArray = is_array($costIdsParam) ? $costIdsParam : explode(',', $costIdsParam);
        $costIdsArray = array_filter(array_map('trim', $costIdsArray));
        
        // Decode hashids to numeric IDs
        $numericIds = [];
        foreach ($costIdsArray as $id) {
            $decoded = \Vinkla\Hashids\Facades\Hashids::decode($id);
            if (!empty($decoded)) {
                $numericIds[] = $decoded[0];
            } elseif (is_numeric($id)) {
                $numericIds[] = (int)$id;
            }
        }
        
        if (empty($numericIds)) {
            return redirect()->route('fleet.trip-costs.index')->with('error', 'Invalid cost IDs provided.');
        }
        
        $costs = FleetTripCost::where('company_id', $user->company_id)
            ->whereIn('id', $numericIds)
            ->with(['trip', 'vehicle', 'costCategory', 'glAccount', 'createdBy', 'trip.route'])
            ->orderBy('created_at')
            ->get();
        
        if ($costs->isEmpty()) {
            return redirect()->route('fleet.trip-costs.index')->with('error', 'Costs not found.');
        }
        
        $trip = $costs->first()->trip;
        $totalAmount = $costs->sum('amount');
        
        // Get paid from account (check first cost, then any cost in batch)
        $paidFromGlTransaction = \App\Models\GlTransaction::where('transaction_type', 'fleet_trip_cost')
            ->whereIn('transaction_id', $costs->pluck('id')->toArray())
            ->where('nature', 'credit')
            ->with('chartAccount')
            ->orderBy('id', 'desc')
            ->first();
        if (!$paidFromGlTransaction) {
            $paidFromGlTransaction = \App\Models\GlTransaction::where('transaction_type', 'fleet_trip_cost')
                ->where('transaction_id', $costs->first()->id)
                ->where('nature', 'credit')
                ->with('chartAccount')
                ->first();
        }
        
        $company = $user->company;
        $branch = $user->branch ?? ($trip ? $trip->branch : null);
        
        // Use same PDF export pattern as sales invoice: paper size, orientation, margins from system settings
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
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('fleet.trip-costs.pdf', compact('costs', 'trip', 'totalAmount', 'paidFromGlTransaction', 'company', 'branch'));
            $pdf->setPaper($pageSize, $orientation);
            $pdf->setOptions([
                'margin-top' => $marginTop,
                'margin-right' => $marginRight,
                'margin-bottom' => $marginBottom,
                'margin-left' => $marginLeft,
            ]);
            $receiptNumber = $costs->first()->receipt_number ?? 'COST-' . $costs->first()->id;
            $filename = 'TripCost_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $receiptNumber) . '_' . date('Y-m-d') . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Fleet trip-costs PDF error: ' . $e->getMessage());
            return redirect()->route('fleet.trip-costs.index')->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Helper method to get status badge for grouped costs
     */
    private function getGroupStatusBadge($groupCosts)
    {
        $statuses = $groupCosts->pluck('approval_status')->unique();
        if ($statuses->count() == 1) {
            $status = $statuses->first();
            $statusColors = [
                'pending' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
            ];
            $color = $statusColors[$status] ?? 'secondary';
            return '<span class="badge bg-' . $color . '">' . ucfirst($status) . '</span>';
        }
        return '<span class="badge bg-warning">Mixed</span>';
    }

    /**
     * Helper method to get cost type color
     */
    private function getCostTypeColor($costType)
    {
        $typeColors = [
            'fuel' => 'danger',
            'driver_allowance' => 'info',
            'overtime' => 'warning',
            'toll' => 'primary',
            'maintenance' => 'secondary',
            'insurance' => 'success',
            'other' => 'dark',
        ];
        return $typeColors[$costType] ?? 'secondary';
    }

    /**
     * Helper method to get actions for grouped costs
     */
    private function getGroupActions($costIds, $groupCosts = null)
    {
        // Convert numeric IDs to hashids
        $hashIds = [];
        foreach ($costIds as $id) {
            $hashIds[] = \Vinkla\Hashids\Facades\Hashids::encode($id);
        }
        
        $actions = '<div class="btn-group btn-group-sm" role="group" style="white-space: nowrap;">';
        $viewUrl = route('fleet.trip-costs.view-group') . '?cost_ids=' . implode(',', $hashIds);
        $actions .= '<a href="' . $viewUrl . '" class="btn btn-outline-info" title="View"><i class="bx bx-show"></i></a>';

        $costIdsStr = implode(',', $hashIds);
        $costCount = $groupCosts ? $groupCosts->count() : count($costIds);
        $totalAmount = $groupCosts ? $groupCosts->sum('amount') : 0;
        $allPending = $groupCosts ? $groupCosts->every(fn($c) => $c->approval_status === 'pending') : false;

        if ($allPending) {
            $actions .= '<button type="button" class="btn btn-outline-success approve-group-costs-btn" data-cost-ids="' . htmlspecialchars($costIdsStr) . '" data-cost-count="' . $costCount . '" title="Approve"><i class="bx bx-check-circle"></i></button>';
        }

        $editUrl = route('fleet.trip-costs.batch-edit') . '?cost_ids=' . implode(',', $hashIds);
        $actions .= '<a href="' . $editUrl . '" class="btn btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>';

        $printUrl = route('fleet.trip-costs.print') . '?cost_ids=' . implode(',', $hashIds);
        $actions .= '<a href="' . $printUrl . '" target="_blank" class="btn btn-outline-warning" title="Print"><i class="bx bx-printer"></i></a>';

        $actions .= '<button type="button" class="btn btn-outline-danger delete-group-costs-btn" data-cost-ids="' . htmlspecialchars($costIdsStr) . '" data-cost-count="' . $costCount . '" data-total-amount="' . number_format($totalAmount, 2) . '" title="Delete"><i class="bx bx-trash"></i></button>';

        $actions .= '</div>';
        return $actions;
    }

    public function destroy(FleetTripCost $cost)
    {
        $user = Auth::user();

        if ($cost->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this cost.');
        }

        if ($cost->approval_status !== 'pending') {
            return redirect()->route('fleet.trip-costs.index')
                ->with('error', 'Cannot delete approved or rejected costs.');
        }

        $trip = $cost->trip;
        
        // Delete GL transactions
        \App\Models\GlTransaction::where('transaction_type', 'fleet_trip_cost')
            ->where('transaction_id', $cost->id)
            ->delete();
        
        $cost->delete();

        // Update trip total costs
        if ($trip) {
            $this->updateTripCosts($trip);
        }

        $redirectParams = $trip ? ['trip_id' => $trip->hash_id] : [];
        return redirect()->route('fleet.trip-costs.index', $redirectParams)->with('success', 'Cost deleted successfully.');
    }
    
    public function batchDestroy(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'cost_ids' => 'required|string',
        ]);
        
        // Parse cost IDs (hashids)
        $costIdsArray = explode(',', $validated['cost_ids']);
        $costIdsArray = array_filter(array_map('trim', $costIdsArray));
        
        $numericIds = [];
        foreach ($costIdsArray as $id) {
            $decoded = \Vinkla\Hashids\Facades\Hashids::decode($id);
            if (!empty($decoded)) {
                $numericIds[] = $decoded[0];
            } elseif (is_numeric($id)) {
                $numericIds[] = (int)$id;
            }
        }
        
        if (empty($numericIds)) {
            return response()->json(['success' => false, 'message' => 'Invalid cost IDs provided.'], 400);
        }
        
        $costs = FleetTripCost::where('company_id', $user->company_id)
            ->whereIn('id', $numericIds)
            ->get();
        
        if ($costs->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Costs not found.'], 404);
        }
        
        // Check if all are pending
        $allPending = $costs->every(function($cost) {
            return $cost->approval_status === 'pending';
        });
        
        if (!$allPending) {
            return response()->json(['success' => false, 'message' => 'Cannot delete approved or rejected costs.'], 400);
        }
        
        // Get trip before deletion
        $trip = $costs->first()->trip;
        
        // Delete GL transactions
        \App\Models\GlTransaction::where('transaction_type', 'fleet_trip_cost')
            ->whereIn('transaction_id', $numericIds)
            ->delete();
        
        // Delete costs
        $costs->each(function($cost) {
            $cost->delete();
        });
        
        // Update trip total costs
        if ($trip) {
            $this->updateTripCosts($trip);
        }
        
        $redirectParams = $trip ? ['trip_id' => $trip->hash_id] : [];
        return response()->json([
            'success' => true,
            'message' => count($costs) . ' cost(s) deleted successfully.',
            'redirect' => route('fleet.trip-costs.index', $redirectParams)
        ]);
    }

    /**
     * Update trip cost totals (includes trip costs + approved fuel logs for profit calculation).
     */
    private function updateTripCosts(FleetTrip $trip)
    {
        $trip->recalculateTotalCosts();
    }
}
