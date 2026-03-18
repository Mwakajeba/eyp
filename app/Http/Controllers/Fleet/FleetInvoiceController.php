<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetInvoice;
use App\Models\Fleet\FleetInvoiceItem;
use App\Models\Fleet\FleetInvoicePayment;
use App\Models\Fleet\FleetTrip;
use App\Models\Assets\Asset;
use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetRoute;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class FleetInvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        // Get vehicles for filter dropdown
        $vehicles = Asset::where('company_id', $user->company_id)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number']);

        // Get trips for filter dropdown
        $trips = FleetTrip::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['vehicle', 'driver', 'route'])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        // Count overdue invoices (due date passed, not fully paid)
        $overdueCount = FleetInvoice::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('due_date', '<', now()->startOfDay())
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->where('balance_due', '>', 0)
            ->count();

        // Calculate dashboard statistics
        $invoiceQuery = FleetInvoice::where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        $totalInvoices = $invoiceQuery->count();
        $totalAmount = $invoiceQuery->sum('total_amount');
        $totalPaid = $invoiceQuery->sum('paid_amount');
        $totalOutstanding = $totalAmount - $totalPaid;

        return view('fleet.invoices.index', compact('vehicles', 'trips', 'overdueCount', 'totalInvoices', 'totalAmount', 'totalPaid', 'totalOutstanding'));
    }

    public function data(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $query = FleetInvoice::query()
            ->where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->withCount('payments')
            ->with(['vehicle', 'driver', 'route', 'trip', 'customer', 'items.trip.vehicle', 'items.trip.driver', 'items.trip.route', 'items.trip.customer'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->trip_id);
        }

        if ($request->filled('date_from')) {
            $query->where('invoice_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('invoice_date', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && $request->search['value']) {
                    $search = $request->search['value'];
                    $query->where(function($q) use ($search) {
                        $q->where('invoice_number', 'like', "%{$search}%")
                          ->orWhereHas('vehicle', function($q) use ($search) {
                              $q->where('name', 'like', "%{$search}%")
                                ->orWhere('registration_number', 'like', "%{$search}%");
                          })
                          ->orWhereHas('trip', function($q) use ($search) {
                              $q->where('trip_number', 'like', "%{$search}%");
                          })
                          ->orWhereHas('driver', function($q) use ($search) {
                              $q->where('full_name', 'like', "%{$search}%");
                          })
                          ->orWhereHas('customer', function($q) use ($search) {
                              $q->where('name', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%");
                          });
                    });
                }
            })
            ->addColumn('customer_display', function($inv) {
                if ($inv->customer) {
                    return $inv->customer->name ?? $inv->customer->company_name ?? 'N/A';
                }
                $firstItem = $inv->items->first();
                if ($firstItem && $firstItem->trip && $firstItem->trip->customer) {
                    $c = $firstItem->trip->customer;
                    return $c->name ?? $c->company_name ?? 'N/A';
                }
                return 'N/A';
            })
            ->addColumn('vehicle_display', function($inv) {
                // Try invoice level first, then from first item's trip
                if ($inv->vehicle) {
                    return $inv->vehicle->name . ' (' . ($inv->vehicle->registration_number ?? 'N/A') . ')';
                }
                // Get from first invoice item's trip
                $firstItem = $inv->items->first();
                if ($firstItem && $firstItem->trip && $firstItem->trip->vehicle) {
                    return $firstItem->trip->vehicle->name . ' (' . ($firstItem->trip->vehicle->registration_number ?? 'N/A') . ')';
                }
                return 'N/A';
            })
            ->addColumn('driver_display', function($inv) {
                // Try invoice level first, then from first item's trip
                if ($inv->driver) {
                    return $inv->driver->full_name ?? $inv->driver->name ?? 'N/A';
                }
                // Get from first invoice item's trip
                $firstItem = $inv->items->first();
                if ($firstItem && $firstItem->trip && $firstItem->trip->driver) {
                    return $firstItem->trip->driver->full_name ?? 'N/A';
                }
                return 'N/A';
            })
            ->addColumn('trip_display', function($inv) {
                // Try invoice level first, then from first item's trip
                if ($inv->trip) {
                    return $inv->trip->trip_number;
                }
                // Get from first invoice item's trip
                $firstItem = $inv->items->first();
                if ($firstItem && $firstItem->trip) {
                    return $firstItem->trip->trip_number;
                }
                return 'N/A';
            })
            ->addColumn('route_display', function($inv) {
                if ($inv->route) {
                    return ($inv->route->origin_location ?? '') . ' → ' . ($inv->route->destination_location ?? '');
                }
                return $inv->trip && $inv->trip->route
                    ? ($inv->trip->route->origin_location ?? '') . ' → ' . ($inv->trip->route->destination_location ?? '')
                    : 'N/A';
            })
            ->addColumn('invoice_date_display', function($inv) {
                return $inv->invoice_date->format('Y-m-d');
            })
            ->addColumn('due_date_display', function($inv) {
                return $inv->due_date->format('Y-m-d');
            })
            ->addColumn('status_display', function($inv) {
                $colors = [
                    'draft' => 'secondary',
                    'sent' => 'info',
                    'paid' => 'success',
                    'partially_paid' => 'warning',
                    'overdue' => 'danger',
                    'cancelled' => 'dark',
                ];
                $color = $colors[$inv->status] ?? 'secondary';
                return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $inv->status)) . '</span>';
            })
            ->addColumn('total_amount_display', function($inv) {
                return number_format($inv->total_amount, 2);
            })
            ->addColumn('paid_amount_display', function($inv) {
                return number_format($inv->paid_amount, 2);
            })
            ->addColumn('balance_due_display', function($inv) {
                return number_format($inv->balance_due, 2);
            })
            ->addColumn('actions', function($inv) {
                $actions = '<div class="btn-group btn-group-sm" role="group" style="white-space: nowrap;">';
                $actions .= '<a href="' . route('fleet.invoices.show', $inv->hash_id) . '" class="btn btn-outline-info" title="View"><i class="bx bx-show"></i></a>';
                // Edit button for draft and non-paid invoices
                if (in_array($inv->status, ['draft', 'sent', 'overdue', 'partially_paid'])) {
                    $actions .= '<a href="' . route('fleet.invoices.edit', $inv->hash_id) . '" class="btn btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                }
                // Delete button only when invoice has no payments
                if (($inv->payments_count ?? 0) === 0) {
                    $invoiceId = htmlspecialchars($inv->hash_id, ENT_QUOTES);
                    $invoiceNumber = htmlspecialchars($inv->invoice_number, ENT_QUOTES);
                    $actions .= '<button type="button" class="btn btn-outline-danger delete-invoice-btn" title="Delete" data-invoice-id="' . $invoiceId . '" data-invoice-number="' . $invoiceNumber . '" onclick="return false;"><i class="bx bx-trash"></i></button>';
                }
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status_display', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        // Show trips that are not completed (do not allow selecting completed trips for invoice items)
        $trips = FleetTrip::where('company_id', $user->company_id)
            ->whereNotIn('status', ['completed'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['vehicle', 'driver', 'route'])
            ->orderBy('actual_start_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(200) // Limit to recent 200 trips for performance
            ->get()
            ->map(function($trip) {
                // Format trip date for display
                $tripDate = null;
                if ($trip->actual_start_date) {
                    $tripDate = $trip->actual_start_date->format('d/m/Y');
                } elseif ($trip->planned_start_date) {
                    $tripDate = $trip->planned_start_date->format('d/m/Y');
                } elseif ($trip->created_at) {
                    $tripDate = $trip->created_at->format('d/m/Y');
                }
                $trip->formatted_date = $tripDate;
                return $trip;
            });

        return view('fleet.invoices.create', compact('trips'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $validated = $request->validate([
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'invoice_type' => 'required|in:trip_based,period_based,contract',
            'payment_terms' => 'required|in:immediate,net_15,net_30,net_45,net_60,custom',
            'payment_days' => 'nullable|integer|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.trip_id' => 'required|exists:fleet_trips,id',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_rate' => 'required|numeric|min:0',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        DB::beginTransaction();
        try {
            $invoiceNumber = FleetInvoice::generateInvoiceNumber();

            // Get vehicle, driver, route, and customer from the first invoice item's trip
            $vehicleId = null;
            $driverId = null;
            $routeId = null;
            $tripId = null;
            $customerId = null;
            if (!empty($validated['items']) && isset($validated['items'][0]['trip_id'])) {
                $firstTrip = FleetTrip::find($validated['items'][0]['trip_id']);
                if ($firstTrip) {
                    $vehicleId = $firstTrip->vehicle_id;
                    $driverId = $firstTrip->driver_id;
                    $routeId = $firstTrip->route_id;
                    $tripId = $firstTrip->id;
                    $customerId = $firstTrip->customer_id;
                }
            }

            $revenueAccountId = $this->getDefaultRevenueAccountId($user);
            $receivableAccountId = $this->getDefaultReceivableAccountId($user);

            if (!$revenueAccountId) {
                throw new \Exception('No revenue/income GL account found. Please set up an Income or Revenue account in Chart of Accounts.');
            }
            if (!$receivableAccountId) {
                throw new \Exception('No receivable GL account found. Please set up a Receivable account (e.g., Trip Receivable, Driver Receivable) in Chart of Accounts.');
            }

            $invoice = FleetInvoice::create([
                'company_id' => $user->company_id,
                'branch_id' => $branchId,
                'invoice_number' => $invoiceNumber,
                'trip_id' => $tripId,
                'vehicle_id' => $vehicleId,
                'driver_id' => $driverId,
                'route_id' => $routeId,
                'customer_id' => $customerId,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'invoice_type' => $validated['invoice_type'],
                'payment_terms' => $validated['payment_terms'],
                'payment_days' => $validated['payment_days'] ?? 30,
                'tax_rate' => $validated['tax_rate'] ?? 0,
                'gl_account_id' => $revenueAccountId,
                'status' => 'sent',
                'created_by' => $user->id,
            ]);

            // Create invoice items
            foreach ($validated['items'] as $item) {
                FleetInvoiceItem::create([
                    'fleet_invoice_id' => $invoice->id,
                    'trip_id' => $item['trip_id'],
                    'gl_account_id' => $revenueAccountId, // Use income account from settings
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? null,
                    'unit_rate' => $item['unit_rate'],
                    'amount' => $item['quantity'] * $item['unit_rate'],
                ]);
            }

            // Handle file uploads
            $attachmentPaths = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('fleet-invoice-attachments', 'public');
                    $attachmentPaths[] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'uploaded_at' => now()->toDateTimeString(),
                    ];
                }
            }

            // Update invoice with attachments
            if (!empty($attachmentPaths)) {
                $invoice->update(['attachments' => $attachmentPaths]);
            }

            // Calculate totals
            $invoice->calculateTotals();

            // Refresh invoice to load items relationship
            $invoice->refresh();
            $invoice->load('items');

            // Create double-entry GL transactions
            // Dr: Trip Receivable / Driver Receivable
            // Cr: Trip Revenue / Transport Revenue
            $invoice->createDoubleEntryTransactions($receivableAccountId, $revenueAccountId);

            DB::commit();

            return redirect()->route('fleet.invoices.show', $invoice->hash_id)->with('success', 'Invoice created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error creating invoice: ' . $e->getMessage());
        }
    }

    public function generateFromTrip(FleetTrip $trip)
    {
        $user = Auth::user();

        if ($trip->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        if ($trip->status !== 'completed' || !$trip->is_completed) {
            return redirect()->back()->with('error', 'Can only generate invoice from completed trips.');
        }

        // Check if invoice already exists
        if ($trip->invoices()->exists()) {
            return redirect()->back()->with('error', 'Invoice already exists for this trip.');
        }

        $trip->load(['vehicle', 'driver', 'route']);

        return view('fleet.invoices.generate-from-trip', compact('trip'));
    }

    public function storeFromTrip(Request $request, FleetTrip $trip)
    {
        $user = Auth::user();

        if ($trip->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'payment_terms' => 'required|in:immediate,net_15,net_30,net_45,net_60,custom',
            'payment_days' => 'nullable|integer|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,fixed',
        ]);

        DB::beginTransaction();
        try {
            $invoiceNumber = FleetInvoice::generateInvoiceNumber();

            // Determine revenue model and amount from trip
            $revenueModel = $trip->revenue_model ?? 'per_trip';
            $unitRate = $trip->revenue_rate ?? $trip->actual_revenue ?? 0;
            
            $description = 'Transportation service - Trip ' . $trip->trip_number;
            if ($trip->route) {
                $description .= ' (' . $trip->route->origin_location . ' to ' . $trip->route->destination_location . ')';
            }

            // Calculate quantity based on revenue model
            $quantity = 1;
            $unit = 'trip';
            if ($revenueModel === 'per_km') {
                $quantity = $trip->actual_distance_km ?? $trip->planned_distance_km ?? 1;
                $unit = 'km';
            } elseif ($revenueModel === 'per_hour') {
                $hours = 0;
                if ($trip->actual_start_date && $trip->actual_end_date) {
                    $hours = $trip->actual_start_date->diffInHours($trip->actual_end_date);
                }
                $quantity = $hours > 0 ? $hours : 1;
                $unit = 'hour';
            }

            // Get chart accounts from fleet settings
            $revenueAccountId = \App\Models\Fleet\FleetSystemSetting::getSetting($user->company_id, 'fleet_income_chart_account_id');
            $receivableAccountId = \App\Models\Fleet\FleetSystemSetting::getSetting($user->company_id, 'fleet_receivable_chart_account_id');

            if (!$revenueAccountId) {
                throw new \Exception('No income chart account configured. Please set up the Income Chart Account in Fleet Settings.');
            }
            if (!$receivableAccountId) {
                throw new \Exception('No receivable chart account configured. Please set up the Receivable Chart Account in Fleet Settings.');
            }

            $invoice = FleetInvoice::create([
                'company_id' => $user->company_id,
                'branch_id' => $trip->branch_id,
                'invoice_number' => $invoiceNumber,
                'trip_id' => $trip->id,
                'vehicle_id' => $trip->vehicle_id,
                'driver_id' => $trip->driver_id,
                'route_id' => $trip->route_id,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'invoice_type' => 'trip_based',
                'revenue_model' => $revenueModel,
                'payment_terms' => $validated['payment_terms'],
                'payment_days' => $validated['payment_days'] ?? 30,
                'tax_rate' => $validated['tax_rate'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'discount_type' => $validated['discount_type'] ?? null,
                'gl_account_id' => $revenueAccountId,
                'status' => 'sent',
                'number_of_trips' => 1,
                'total_distance_km' => $trip->actual_distance_km ?? $trip->planned_distance_km,
                'created_by' => $user->id,
            ]);

            // Create invoice item
            FleetInvoiceItem::create([
                'fleet_invoice_id' => $invoice->id,
                'trip_id' => $trip->id,
                'gl_account_id' => $revenueAccountId,
                'description' => $description,
                'quantity' => $quantity,
                'unit' => $unit,
                'unit_rate' => $unitRate,
                'amount' => $quantity * $unitRate,
            ]);

            // Calculate totals
            $invoice->calculateTotals();

            // Refresh invoice to load items relationship
            $invoice->refresh();
            $invoice->load('items');

            // Create double-entry GL transactions
            // Dr: Trip Receivable / Driver Receivable
            // Cr: Trip Revenue / Transport Revenue
            $invoice->createDoubleEntryTransactions($receivableAccountId, $revenueAccountId);

            // Update trip's actual_revenue if not set
            if (!$trip->actual_revenue || $trip->actual_revenue == 0) {
                $trip->update([
                    'actual_revenue' => $invoice->total_amount,
                ]);
            }

            DB::commit();

            return redirect()->route('fleet.invoices.show', $invoice->hash_id)->with('success', 'Invoice generated from trip successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error generating invoice: ' . $e->getMessage());
        }
    }

    public function show(FleetInvoice $invoice)
    {
        $user = Auth::user();

        if ($invoice->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $invoice->load([
            'items.glAccount', 
            'items.trip.vehicle', 
            'items.trip.driver', 
            'items.trip.route',
            'vehicle', 
            'driver', 
            'route', 
            'trip.vehicle', 
            'payments.bankAccount', 
            'createdBy'
        ]);

        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', fn($q) => $q->where('company_id', $user->company_id))
            ->orderBy('name')
            ->get();

        return view('fleet.invoices.show', compact('invoice', 'bankAccounts'));
    }

    public function storePayment(Request $request, FleetInvoice $invoice)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        if ($invoice->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $maxAmount = (float) $invoice->balance_due;
        if ($maxAmount <= 0) {
            return redirect()->route('fleet.invoices.show', $invoice->hash_id)
                ->with('error', 'This invoice is already fully paid.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $maxAmount,
            'payment_date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        DB::beginTransaction();
        try {
            $receivableAccountId = $this->getDefaultReceivableAccountId($user);
            if (!$receivableAccountId) {
                return redirect()->route('fleet.invoices.show', $invoice->hash_id)
                    ->with('error', 'No receivable GL account found. Please set up a Receivable account (e.g., Trip Receivable, Driver Receivable) in Chart of Accounts.');
            }
            $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);

            $attachmentData = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('fleet-invoice-payment-attachments', 'public');
                $attachmentData = [[
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toDateTimeString(),
                ]];
            }

            $payment = FleetInvoicePayment::create([
                'fleet_invoice_id' => $invoice->id,
                'company_id' => $user->company_id,
                'branch_id' => $branchId,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'bank_account_id' => $validated['bank_account_id'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'attachments' => $attachmentData,
                'created_by' => $user->id,
            ]);

            // GL Entry: When payment is received (Driver sends money in evening)
            // Dr: Cash Account or Bank Account
            // Cr: Trip Receivable / Driver Receivable
            \App\Models\GlTransaction::create([
                'branch_id' => $branchId,
                'chart_account_id' => $bankAccount->chart_account_id,
                'amount' => $validated['amount'],
                'nature' => 'debit',
                'transaction_id' => $payment->id,
                'transaction_type' => 'fleet_invoice_payment',
                'date' => $validated['payment_date'],
                'description' => $validated['notes'] ?? "Payment received - Invoice {$invoice->invoice_number}",
                'user_id' => $user->id,
            ]);

            \App\Models\GlTransaction::create([
                'branch_id' => $branchId,
                'chart_account_id' => $receivableAccountId,
                'amount' => $validated['amount'],
                'nature' => 'credit',
                'transaction_id' => $payment->id,
                'transaction_type' => 'fleet_invoice_payment',
                'date' => $validated['payment_date'],
                'description' => $validated['notes'] ?? "Trip Receivable cleared - Invoice {$invoice->invoice_number}",
                'user_id' => $user->id,
            ]);

            $newPaidAmount = $invoice->paid_amount + $validated['amount'];
            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'balance_due' => $invoice->total_amount - $newPaidAmount,
                'status' => $newPaidAmount >= $invoice->total_amount ? 'paid' : 'partially_paid',
                'paid_at' => $newPaidAmount >= $invoice->total_amount ? now() : $invoice->paid_at,
            ]);

            DB::commit();

            return redirect()->route('fleet.invoices.show', $invoice->hash_id)
                ->with('success', 'Payment of ' . number_format($validated['amount'], 2) . ' TZS recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }

    public function editPayment(FleetInvoice $invoice, FleetInvoicePayment $payment)
    {
        $user = Auth::user();
        if ($invoice->company_id !== $user->company_id || $payment->fleet_invoice_id !== $invoice->id) {
            abort(403, 'Unauthorized access.');
        }
        
        // Get bank accounts same way as in show method
        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', fn($q) => $q->where('company_id', $user->company_id))
            ->orderBy('name')
            ->get();
            
        return view('fleet.invoices.edit-payment', compact('invoice', 'payment', 'bankAccounts'));
    }

    public function updatePayment(Request $request, FleetInvoice $invoice, FleetInvoicePayment $payment)
    {
        $user = Auth::user();
        if ($invoice->company_id !== $user->company_id || $payment->fleet_invoice_id !== $invoice->id) {
            abort(403, 'Unauthorized access.');
        }
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);
        DB::beginTransaction();
        try {
            $oldAmount = $payment->amount;
            $receivableAccountId = $this->getDefaultReceivableAccountId($user);
            if (!$receivableAccountId) {
                throw new \Exception('No receivable GL account found.');
            }
            $bankAccount = \App\Models\BankAccount::findOrFail($validated['bank_account_id']);
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('fleet-invoice-payment-attachments', 'public');
                $validated['attachments'] = [[
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toDateTimeString(),
                ]];
            }
            unset($validated['attachment']);
            $payment->update($validated);
            // Delete old GL transactions
            \App\Models\GlTransaction::where('transaction_type', 'fleet_invoice_payment')
                ->where('transaction_id', $payment->id)
                ->delete();
            // Create new GL transactions
            \App\Models\GlTransaction::create([
                'branch_id' => $invoice->branch_id,
                'chart_account_id' => $bankAccount->chart_account_id,
                'amount' => $validated['amount'],
                'nature' => 'debit',
                'transaction_id' => $payment->id,
                'transaction_type' => 'fleet_invoice_payment',
                'date' => $validated['payment_date'],
                'description' => $validated['notes'] ?? "Payment received - Invoice {$invoice->invoice_number}",
                'user_id' => $user->id,
            ]);
            \App\Models\GlTransaction::create([
                'branch_id' => $invoice->branch_id,
                'chart_account_id' => $receivableAccountId,
                'amount' => $validated['amount'],
                'nature' => 'credit',
                'transaction_id' => $payment->id,
                'transaction_type' => 'fleet_invoice_payment',
                'date' => $validated['payment_date'],
                'description' => $validated['notes'] ?? "Trip Receivable cleared - Invoice {$invoice->invoice_number}",
                'user_id' => $user->id,
            ]);
            // Update invoice totals
            $newPaidAmount = $invoice->paid_amount - $oldAmount + $validated['amount'];
            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'balance_due' => $invoice->total_amount - $newPaidAmount,
                'status' => $newPaidAmount >= $invoice->total_amount ? 'paid' : 'partially_paid',
                'paid_at' => $newPaidAmount >= $invoice->total_amount ? now() : $invoice->paid_at,
            ]);
            DB::commit();
            return redirect()->route('fleet.invoices.show', $invoice->hash_id)->with('success', 'Payment updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error updating payment: ' . $e->getMessage());
        }
    }

    public function destroyPayment(FleetInvoice $invoice, FleetInvoicePayment $payment)
    {
        $user = Auth::user();
        if ($invoice->company_id !== $user->company_id || $payment->fleet_invoice_id !== $invoice->id) {
            abort(403, 'Unauthorized access.');
        }
        DB::beginTransaction();
        try {
            // Delete GL transactions
            \App\Models\GlTransaction::where('transaction_type', 'fleet_invoice_payment')
                ->where('transaction_id', $payment->id)
                ->delete();
            $oldAmount = $payment->amount;
            $payment->delete();
            // Update invoice totals
            $newPaidAmount = max(0, $invoice->paid_amount - $oldAmount);
            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'balance_due' => $invoice->total_amount - $newPaidAmount,
                'status' => $newPaidAmount >= $invoice->total_amount ? 'paid' : ($newPaidAmount > 0 ? 'partially_paid' : 'sent'),
                'paid_at' => $newPaidAmount >= $invoice->total_amount ? now() : null,
            ]);
            DB::commit();
            return redirect()->route('fleet.invoices.show', $invoice->hash_id)->with('success', 'Payment deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error deleting payment: ' . $e->getMessage());
        }
    }

    /**
     * Export invoice as PDF
     */
    public function exportPdf($encodedId)
    {
        $invoiceId = \Vinkla\Hashids\Facades\Hashids::decode($encodedId)[0] ?? null;

        if (!$invoiceId) {
            return redirect()->route('fleet.invoices.index')
                ->with('error', 'Invalid invoice ID');
        }

        $invoice = FleetInvoice::with([
            'vehicle',
            'driver',
            'route',
            'trip',
            'branch',
            'company',
            'createdBy',
            'items.trip.vehicle',
            'items.trip.driver',
            'items.trip.route',
            'payments.bankAccount'
        ])->findOrFail($invoiceId);

        // Get bank accounts for payment methods
        $bankAccounts = \App\Models\BankAccount::all();

        // Apply paper size/orientation from settings
        $pageSize = strtoupper((string) (\App\Models\SystemSetting::getValue('document_page_size', 'A5')));
        $orientation = strtolower((string) (\App\Models\SystemSetting::getValue('document_orientation', 'portrait')));
        
        // Get margins from settings and convert cm to mm for dompdf
        $marginTopStr = \App\Models\SystemSetting::getValue('document_margin_top', '2.54cm');
        $marginRightStr = \App\Models\SystemSetting::getValue('document_margin_right', '2.54cm');
        $marginBottomStr = \App\Models\SystemSetting::getValue('document_margin_bottom', '2.54cm');
        $marginLeftStr = \App\Models\SystemSetting::getValue('document_margin_left', '2.54cm');
        
        // Convert cm to mm (dompdf expects mm)
        $convertToMm = function($value) {
            if (is_numeric($value)) {
                return (float) $value; // Assume already in mm
            }
            // Remove 'cm' and convert to mm
            $numeric = (float) str_replace(['cm', 'mm', 'pt', 'px', 'in'], '', $value);
            if (strpos($value, 'cm') !== false) {
                return $numeric * 10; // 1cm = 10mm
            }
            if (strpos($value, 'in') !== false) {
                return $numeric * 25.4; // 1in = 25.4mm
            }
            return $numeric; // Already in mm or unknown unit
        };

        $marginTop = $convertToMm($marginTopStr);
        $marginRight = $convertToMm($marginRightStr);
        $marginBottom = $convertToMm($marginBottomStr);
        $marginLeft = $convertToMm($marginLeftStr);

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('fleet.invoices.pdf', compact('invoice', 'bankAccounts'));
            $pdf->setPaper($pageSize, $orientation);
            $pdf->setOptions([
                'margin-top' => $marginTop,
                'margin-right' => $marginRight,
                'margin-bottom' => $marginBottom,
                'margin-left' => $marginLeft,
            ]);
            
            $filename = 'FleetInvoice_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $invoice->invoice_number) . '_' . date('Y-m-d') . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Fleet invoice PDF export error: ' . $e->getMessage());
            return redirect()->route('fleet.invoices.show', $invoice->hash_id)
                ->with('error', 'Failed to export invoice PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export payment receipt as PDF
     */
    public function exportPaymentPdf($encodedId, $paymentId)
    {
        $invoiceId = \Vinkla\Hashids\Facades\Hashids::decode($encodedId)[0] ?? null;

        if (!$invoiceId) {
            return redirect()->route('fleet.invoices.index')
                ->with('error', 'Invalid invoice ID');
        }

        $invoice = FleetInvoice::with([
            'vehicle',
            'driver',
            'route',
            'trip',
            'branch',
            'company',
            'items.trip.vehicle',
            'items.trip.driver'
        ])->findOrFail($invoiceId);

        $payment = FleetInvoicePayment::with('bankAccount')
            ->where('fleet_invoice_id', $invoice->id)
            ->findOrFail($paymentId);

        $user = Auth::user();
        if ($invoice->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        // Get bank accounts for payment methods
        $bankAccounts = \App\Models\BankAccount::all();

        // Apply paper size/orientation from settings
        $pageSize = strtoupper((string) (\App\Models\SystemSetting::getValue('document_page_size', 'A5')));
        $orientation = strtolower((string) (\App\Models\SystemSetting::getValue('document_orientation', 'portrait')));
        
        // Get margins from settings and convert cm to mm for dompdf
        $marginTopStr = \App\Models\SystemSetting::getValue('document_margin_top', '2.54cm');
        $marginRightStr = \App\Models\SystemSetting::getValue('document_margin_right', '2.54cm');
        $marginBottomStr = \App\Models\SystemSetting::getValue('document_margin_bottom', '2.54cm');
        $marginLeftStr = \App\Models\SystemSetting::getValue('document_margin_left', '2.54cm');
        
        // Convert cm to mm (dompdf expects mm)
        $convertToMm = function($value) {
            if (is_numeric($value)) {
                return (float) $value; // Assume already in mm
            }
            // Remove 'cm' and convert to mm
            $numeric = (float) str_replace(['cm', 'mm', 'pt', 'px', 'in'], '', $value);
            if (strpos($value, 'cm') !== false) {
                return $numeric * 10; // 1cm = 10mm
            }
            if (strpos($value, 'in') !== false) {
                return $numeric * 25.4; // 1in = 25.4mm
            }
            return $numeric; // Already in mm or unknown unit
        };

        $marginTop = $convertToMm($marginTopStr);
        $marginRight = $convertToMm($marginRightStr);
        $marginBottom = $convertToMm($marginBottomStr);
        $marginLeft = $convertToMm($marginLeftStr);

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('fleet.invoices.payment-receipt-pdf', compact('invoice', 'payment', 'bankAccounts'));
            $pdf->setPaper($pageSize, $orientation);
            $pdf->setOptions([
                'margin-top' => $marginTop,
                'margin-right' => $marginRight,
                'margin-bottom' => $marginBottom,
                'margin-left' => $marginLeft,
            ]);
            
            $filename = 'PaymentReceipt_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $invoice->invoice_number) . '_' . $payment->id . '_' . date('Y-m-d') . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Fleet payment receipt PDF export error: ' . $e->getMessage());
            return redirect()->route('fleet.invoices.show', $invoice->hash_id)
                ->with('error', 'Failed to export payment receipt PDF: ' . $e->getMessage());
        }
    }

    private function getDefaultRevenueAccountId($user)
    {
        // Get from fleet settings
        return \App\Models\Fleet\FleetSystemSetting::getSetting($user->company_id, 'fleet_income_chart_account_id');
    }

    private function getDefaultReceivableAccountId($user)
    {
        // Get from fleet settings
        return \App\Models\Fleet\FleetSystemSetting::getSetting($user->company_id, 'fleet_receivable_chart_account_id');
    }

    public function edit(FleetInvoice $invoice)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        if ($invoice->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        if (!in_array($invoice->status, ['draft', 'sent', 'overdue', 'partially_paid'])) {
            return redirect()->route('fleet.invoices.show', $invoice->hash_id)
                ->with('error', 'Only draft, sent, overdue or partially paid invoices can be edited.');
        }

        $invoice->load(['items.trip.vehicle', 'items.trip.driver', 'items.trip.route', 'vehicle', 'driver', 'route']);

        // Get trips for invoice items dropdown (exclude completed)
        $trips = FleetTrip::where('company_id', $user->company_id)
            ->whereNotIn('status', ['completed'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['vehicle', 'driver', 'route'])
            ->orderBy('actual_start_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get()
            ->map(function($trip) {
                $tripDate = null;
                if ($trip->actual_start_date) {
                    $tripDate = $trip->actual_start_date->format('d/m/Y');
                } elseif ($trip->planned_start_date) {
                    $tripDate = $trip->planned_start_date->format('d/m/Y');
                } elseif ($trip->created_at) {
                    $tripDate = $trip->created_at->format('d/m/Y');
                }
                $trip->formatted_date = $tripDate;
                return $trip;
            });

        return view('fleet.invoices.edit', compact('invoice', 'trips'));
    }

    public function update(Request $request, FleetInvoice $invoice)
    {
        $user = Auth::user();

        if ($invoice->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        if (!in_array($invoice->status, ['draft', 'sent', 'overdue', 'partially_paid'])) {
            return redirect()->route('fleet.invoices.show', $invoice->hash_id)
                ->with('error', 'Only draft, sent, overdue or partially paid invoices can be edited.');
        }

        $validated = $request->validate([
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'payment_terms' => 'required|in:immediate,net_15,net_30,net_45,net_60,custom',
            'payment_days' => 'nullable|integer|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:fleet_invoice_items,id',
            'items.*.trip_id' => 'required|exists:fleet_trips,id',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_rate' => 'required|numeric|min:0',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        DB::beginTransaction();
        try {
            $invoice->update([
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'payment_terms' => $validated['payment_terms'],
                'payment_days' => $validated['payment_days'] ?? 30,
                'tax_rate' => $validated['tax_rate'] ?? 0,
                'updated_by' => $user->id,
            ]);

            // Get chart accounts from fleet settings
            $revenueAccountId = \App\Models\Fleet\FleetSystemSetting::getSetting($user->company_id, 'fleet_income_chart_account_id');
            if (!$revenueAccountId) {
                throw new \Exception('No income chart account configured. Please set up the Income Chart Account in Fleet Settings.');
            }

            // Update or create items
            $itemIds = [];
            foreach ($validated['items'] as $itemData) {
                if (isset($itemData['id']) && $itemData['id']) {
                    // Update existing item
                    $item = FleetInvoiceItem::find($itemData['id']);
                    if ($item && $item->fleet_invoice_id === $invoice->id) {
                        $item->update([
                            'trip_id' => $itemData['trip_id'],
                            'gl_account_id' => $revenueAccountId, // Use income account from settings
                            'description' => $itemData['description'],
                            'quantity' => $itemData['quantity'],
                            'unit' => $itemData['unit'] ?? null,
                            'unit_rate' => $itemData['unit_rate'],
                            'amount' => $itemData['quantity'] * $itemData['unit_rate'],
                        ]);
                        $itemIds[] = $item->id;
                    }
                } else {
                    // Create new item
                    $item = FleetInvoiceItem::create([
                        'fleet_invoice_id' => $invoice->id,
                        'trip_id' => $itemData['trip_id'],
                        'gl_account_id' => $revenueAccountId, // Use income account from settings
                        'description' => $itemData['description'],
                        'quantity' => $itemData['quantity'],
                        'unit' => $itemData['unit'] ?? null,
                        'unit_rate' => $itemData['unit_rate'],
                        'amount' => $itemData['quantity'] * $itemData['unit_rate'],
                    ]);
                    $itemIds[] = $item->id;
                }
            }

            // Delete items not in the list
            FleetInvoiceItem::where('fleet_invoice_id', $invoice->id)
                ->whereNotIn('id', $itemIds)
                ->delete();

            // Handle file uploads
            $attachmentPaths = [];
            if ($request->hasFile('attachments')) {
                // Get existing attachments
                $existingAttachments = is_array($invoice->attachments) ? $invoice->attachments : (is_string($invoice->attachments) ? json_decode($invoice->attachments, true) : []);
                $attachmentPaths = $existingAttachments ?: [];
                
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('fleet-invoice-attachments', 'public');
                    $attachmentPaths[] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'uploaded_at' => now()->toDateTimeString(),
                    ];
                }
            } else {
                // Keep existing attachments if no new files uploaded
                $existingAttachments = is_array($invoice->attachments) ? $invoice->attachments : (is_string($invoice->attachments) ? json_decode($invoice->attachments, true) : []);
                $attachmentPaths = $existingAttachments ?: [];
            }

            // Update invoice with attachments
            if (!empty($attachmentPaths)) {
                $invoice->update(['attachments' => $attachmentPaths]);
            }

            // Recalculate totals
            $oldTotal = $invoice->total_amount;
            $invoice->calculateTotals();
            $newTotal = $invoice->total_amount;

            // Refresh invoice to load items relationship
            $invoice->refresh();
            $invoice->load('items');

            // Update GL entries if total changed - recreate all transactions
            if (abs($oldTotal - $newTotal) > 0.01 || true) { // Always update to ensure accuracy
                $receivableAccountId = $this->getDefaultReceivableAccountId($user);
                $revenueAccountId = $this->getDefaultRevenueAccountId($user);
                
                if ($receivableAccountId && $revenueAccountId) {
                    // Recreate double-entry transactions
                    $invoice->createDoubleEntryTransactions($receivableAccountId, $revenueAccountId);
                }
            }

            DB::commit();

            return redirect()->route('fleet.invoices.show', $invoice->hash_id)->with('success', 'Invoice updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error updating invoice: ' . $e->getMessage());
        }
    }

    public function send(Request $request, FleetInvoice $invoice)
    {
        $user = Auth::user();

        if ($invoice->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $invoice->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return redirect()->route('fleet.invoices.show', $invoice->hash_id)->with('success', 'Invoice sent successfully.');
    }

    public function destroy(FleetInvoice $invoice)
    {
        $user = Auth::user();

        if ($invoice->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        if ($invoice->payments()->count() > 0) {
            return redirect()->route('fleet.invoices.index')
                ->with('error', 'Cannot delete an invoice that has payments. Delete all payments first.');
        }

        DB::beginTransaction();
        try {
            // Delete GL transactions
            \App\Models\GlTransaction::where('transaction_type', 'fleet_invoice')
                ->where('transaction_id', $invoice->id)
                ->delete();

            // Delete payments and their GL transactions
            foreach ($invoice->payments as $payment) {
                \App\Models\GlTransaction::where('transaction_type', 'fleet_invoice_payment')
                    ->where('transaction_id', $payment->id)
                    ->delete();
                $payment->delete();
            }

            // Delete invoice items
            $invoice->items()->delete();

            // Delete invoice
            $invoice->delete();

            DB::commit();

            return redirect()->route('fleet.invoices.index')->with('success', 'Invoice deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('fleet.invoices.index')->with('error', 'Error deleting invoice: ' . $e->getMessage());
        }
    }
}
