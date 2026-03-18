<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Traits\Fleet\HasFleetSettings;
use App\Models\Fleet\FleetMaintenanceWorkOrder;
use App\Models\Fleet\FleetMaintenanceWorkOrderCost;
use App\Models\Fleet\FleetMaintenanceSchedule;
use App\Models\Assets\Asset;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class FleetMaintenanceController extends Controller
{
    use HasFleetSettings;
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    // ============ Work Orders ============

    public function workOrdersIndex()
    {
        return view('fleet.maintenance.work-orders.index');
    }

    public function workOrdersData(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $query = FleetMaintenanceWorkOrder::query()
            ->where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['vehicle', 'assignedTechnician', 'vendor']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('maintenance_type')) {
            $query->where('maintenance_type', $request->maintenance_type);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        return DataTables::of($query)
            ->addColumn('vehicle_display', function($wo) {
                return $wo->vehicle ? $wo->vehicle->name . ' (' . ($wo->vehicle->registration_number ?? 'N/A') . ')' : 'N/A';
            })
            ->addColumn('status_display', function($wo) {
                $colors = [
                    'draft' => 'secondary',
                    'scheduled' => 'info',
                    'in_progress' => 'warning',
                    'on_hold' => 'danger',
                    'completed' => 'success',
                    'cancelled' => 'dark',
                ];
                $color = $colors[$wo->status] ?? 'secondary';
                return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $wo->status)) . '</span>';
            })
            ->addColumn('type_display', function($wo) {
                return '<span class="badge bg-primary">' . ucfirst($wo->maintenance_type) . '</span>';
            })
            ->addColumn('scheduled_date_display', function($wo) {
                return $wo->scheduled_date ? $wo->scheduled_date->format('Y-m-d H:i') : 'N/A';
            })
            ->addColumn('cost_display', function($wo) {
                return number_format($wo->actual_cost > 0 ? $wo->actual_cost : $wo->estimated_cost, 2);
            })
            ->addColumn('actions', function($wo) {
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<a href="' . route('fleet.maintenance.work-orders.show', $wo->hash_id) . '" class="btn btn-sm btn-outline-info" title="View"><i class="bx bx-show"></i></a>';
                if (in_array($wo->status, ['draft', 'scheduled', 'in_progress'])) {
                    $actions .= '<a href="' . route('fleet.maintenance.work-orders.show', $wo->hash_id) . '#add-cost" class="btn btn-sm btn-outline-success" title="Add Cost"><i class="bx bx-money"></i></a>';
                }
                $actions .= '<a href="' . route('fleet.maintenance.work-orders.edit', $wo->hash_id) . '" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status_display', 'type_display', 'actions'])
            ->make(true);
    }

    public function workOrderCreate()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $vehicles = Asset::where('company_id', $user->company_id)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number']);

        $vendors = Supplier::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $technicians = User::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $schedules = FleetMaintenanceSchedule::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with('vehicle')
            ->get();

        // Get cost categories for maintenance category dropdown
        $costCategories = \App\Models\Fleet\FleetCostCategory::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category_type']);

        return view('fleet.maintenance.work-orders.create', compact('vehicles', 'vendors', 'technicians', 'schedules', 'costCategories'));
    }

    // API endpoint to get maintenance schedule details for auto-population
    public function getScheduleDetails(Request $request)
    {
        $user = Auth::user();
        $scheduleId = $request->input('schedule_id');

        $schedule = FleetMaintenanceSchedule::where('company_id', $user->company_id)
            ->where('id', $scheduleId)
            ->with('vehicle')
            ->first();

        if (!$schedule) {
            return response()->json(['error' => 'Schedule not found'], 404);
        }

        return response()->json([
            'id' => $schedule->id,
            'schedule_name' => $schedule->schedule_name,
            'vehicle_id' => $schedule->vehicle_id,
            'vehicle_name' => $schedule->vehicle->name ?? null,
            'maintenance_category' => $schedule->maintenance_category,
            'description' => $schedule->description,
            'estimated_cost' => $schedule->estimated_cost,
            'schedule_type' => $schedule->schedule_type,
            'next_due_date' => $schedule->next_due_date ? $schedule->next_due_date->format('Y-m-d') : null,
        ]);
    }

    public function workOrderStore(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:assets,id',
            'maintenance_schedule_id' => 'nullable|exists:fleet_maintenance_schedules,id',
            'maintenance_type' => 'required|in:preventive,corrective,major_overhaul',
            'maintenance_category' => 'nullable|string|max:100',
            'execution_type' => 'required|in:in_house,external_vendor,mixed',
            'vendor_id' => 'nullable|required_if:execution_type,external_vendor,mixed|exists:suppliers,id',
            'assigned_technician_id' => 'nullable|exists:users,id',
            'scheduled_date' => 'nullable|date',
            'estimated_start_date' => 'nullable|date',
            'estimated_completion_date' => 'nullable|date|after_or_equal:estimated_start_date',
            'estimated_cost' => 'nullable|numeric|min:0',
            'priority' => 'required|in:low,medium,high,urgent',
            'work_description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $woNumber = $this->generateWONumber();

        $workOrder = FleetMaintenanceWorkOrder::create(array_merge($validated, [
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'wo_number' => $woNumber,
            'status' => 'draft',
            'created_by' => $user->id,
        ]));

        return redirect()->route('fleet.maintenance.work-orders.index')->with('success', 'Work order created successfully.');
    }

    public function workOrderShow(FleetMaintenanceWorkOrder $workOrder)
    {
        $user = Auth::user();

        if ($workOrder->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $workOrder->load(['vehicle', 'maintenanceSchedule', 'vendor', 'assignedTechnician', 'approvedBy', 'completedBy', 'costs']);

        return view('fleet.maintenance.work-orders.show', compact('workOrder'));
    }

    public function workOrderEdit(FleetMaintenanceWorkOrder $workOrder)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        if ($workOrder->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $vehicles = Asset::where('company_id', $user->company_id)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number']);

        $vendors = Supplier::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $technicians = User::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get cost categories for maintenance category dropdown
        $costCategories = \App\Models\Fleet\FleetCostCategory::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category_type']);

        return view('fleet.maintenance.work-orders.edit', compact('workOrder', 'vehicles', 'vendors', 'technicians', 'costCategories'));
    }

    public function workOrderUpdate(Request $request, FleetMaintenanceWorkOrder $workOrder)
    {
        $user = Auth::user();

        if ($workOrder->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'maintenance_type' => 'required|in:preventive,corrective,major_overhaul',
            'maintenance_category' => 'nullable|string|max:100',
            'execution_type' => 'required|in:in_house,external_vendor,mixed',
            'vendor_id' => 'nullable|exists:suppliers,id',
            'assigned_technician_id' => 'nullable|exists:users,id',
            'scheduled_date' => 'nullable|date',
            'estimated_start_date' => 'nullable|date',
            'estimated_completion_date' => 'nullable|date',
            'estimated_cost' => 'nullable|numeric|min:0',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:draft,scheduled,in_progress,on_hold,completed,cancelled',
            'work_description' => 'nullable|string',
            'work_performed' => 'nullable|string',
            'actual_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $workOrder->update(array_merge($validated, [
            'updated_by' => $user->id,
        ]));

        // Update vehicle status if completed
        if ($validated['status'] === 'completed' && !$workOrder->completed_at) {
            $workOrder->update([
                'completed_at' => now(),
                'completed_by' => $user->id,
            ]);
        }

        return redirect()->route('fleet.maintenance.work-orders.show', $workOrder->hash_id)->with('success', 'Work order updated successfully.');
    }

    public function workOrderStart(FleetMaintenanceWorkOrder $workOrder)
    {
        $user = Auth::user();

        if ($workOrder->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        if ($workOrder->status !== 'draft' && $workOrder->status !== 'scheduled') {
            return redirect()->route('fleet.maintenance.work-orders.show', $workOrder->hash_id)
                ->with('error', 'Can only start draft or scheduled work orders.');
        }

        // Update vehicle status to in_repair (operational_status is on Asset model)
        if ($workOrder->vehicle) {
            $workOrder->vehicle->update([
                'operational_status' => 'in_repair',
            ]);
        }

        $workOrder->update([
            'status' => 'in_progress',
            'actual_start_date' => now(),
            'updated_by' => $user->id,
        ]);

        return redirect()->route('fleet.maintenance.work-orders.show', $workOrder->hash_id)
            ->with('success', 'Work order started successfully.');
    }

    public function workOrderComplete(Request $request, FleetMaintenanceWorkOrder $workOrder)
    {
        $user = Auth::user();

        if ($workOrder->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        if ($workOrder->status !== 'in_progress') {
            return redirect()->route('fleet.maintenance.work-orders.show', $workOrder->hash_id)
                ->with('error', 'Can only complete work orders in progress.');
        }

        $validated = $request->validate([
            'actual_cost' => 'required|numeric|min:0',
            'work_performed' => 'required|string',
            'technician_notes' => 'nullable|string',
        ]);

        // Update vehicle status back to available (operational_status is on Asset model)
        if ($workOrder->vehicle) {
            // Only set to available if no other active work orders
            $hasActiveWorkOrders = FleetMaintenanceWorkOrder::where('vehicle_id', $workOrder->vehicle_id)
                ->where('id', '!=', $workOrder->id)
                ->whereIn('status', ['in_progress', 'scheduled'])
                ->exists();
            
            if (!$hasActiveWorkOrders) {
                $workOrder->vehicle->update([
                    'operational_status' => 'available',
                ]);
            }
        }

        // Calculate downtime if applicable
        $downtimeHours = 0;
        if ($workOrder->downtime_start && $workOrder->downtime_end) {
            $downtimeHours = $workOrder->downtime_start->diffInHours($workOrder->downtime_end);
        } elseif ($workOrder->actual_start_date) {
            $downtimeHours = $workOrder->actual_start_date->diffInHours(now());
        }

        $workOrder->update(array_merge($validated, [
            'status' => 'completed',
            'actual_completion_date' => now(),
            'actual_downtime_hours' => $downtimeHours,
            'completed_at' => now(),
            'completed_by' => $user->id,
            'updated_by' => $user->id,
        ]));

        return redirect()->route('fleet.maintenance.work-orders.show', $workOrder->hash_id)
            ->with('success', 'Work order completed successfully.');
    }

    public function workOrderAddCost(Request $request, FleetMaintenanceWorkOrder $workOrder)
    {
        $user = Auth::user();

        if ($workOrder->company_id !== $user->company_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        if (!in_array($workOrder->status, ['draft', 'scheduled', 'in_progress'])) {
            return response()->json(['success' => false, 'message' => 'Work order is not in executable status'], 400);
        }

        $validated = $request->validate([
            'cost_type' => 'required|in:material,labor,other',
            'description' => 'required|string',
            'inventory_item_id' => 'nullable|exists:inventory_items,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'employee_id' => 'nullable|exists:users,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'nullable|string|max:50',
            'unit_cost' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'cost_date' => 'required|date',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        DB::beginTransaction();
        try {
            $totalCost = ($validated['quantity'] * $validated['unit_cost']) + ($validated['tax_amount'] ?? 0);

            // Handle file uploads
            $attachmentPaths = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('fleet-work-order-cost-attachments', 'public');
                    $attachmentPaths[] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'uploaded_at' => now()->toDateTimeString(),
                    ];
                }
            }

            $cost = FleetMaintenanceWorkOrderCost::create([
                'work_order_id' => $workOrder->id,
                'cost_type' => $validated['cost_type'],
                'description' => $validated['description'],
                'inventory_item_id' => $validated['inventory_item_id'] ?? null,
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'purchase_invoice_id' => $validated['purchase_invoice_id'] ?? null,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'employee_id' => $validated['employee_id'] ?? null,
                'quantity' => $validated['quantity'],
                'unit' => $validated['unit'] ?? null,
                'unit_cost' => $validated['unit_cost'],
                'total_cost' => $validated['quantity'] * $validated['unit_cost'],
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'total_with_tax' => $totalCost,
                'cost_date' => $validated['cost_date'],
                'status' => 'actual',
                'attachments' => !empty($attachmentPaths) ? $attachmentPaths : null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            // Update work order costs summary
            $this->updateWorkOrderCosts($workOrder);

            // Update status to in_progress if not already
            if (in_array($workOrder->status, ['draft', 'scheduled'])) {
                $workOrder->update([
                    'status' => 'in_progress',
                    'actual_start_date' => $workOrder->actual_start_date ?? now(),
                    'updated_by' => $user->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cost added successfully.',
                'cost' => $cost
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error adding cost to work order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add cost: ' . $e->getMessage()
            ], 500);
        }
    }

    private function updateWorkOrderCosts(FleetMaintenanceWorkOrder $workOrder)
    {
        $costs = $workOrder->costs()->where('status', 'actual')->get();
        
        $actualLaborCost = $costs->where('cost_type', 'labor')->sum('total_with_tax');
        $actualMaterialCost = $costs->where('cost_type', 'material')->sum('total_with_tax');
        $actualOtherCost = $costs->where('cost_type', 'other')->sum('total_with_tax');
        $actualCost = $actualLaborCost + $actualMaterialCost + $actualOtherCost;

        $workOrder->update([
            'actual_labor_cost' => $actualLaborCost,
            'actual_material_cost' => $actualMaterialCost,
            'actual_other_cost' => $actualOtherCost,
            'actual_cost' => $actualCost,
        ]);
    }

    // ============ Maintenance Schedules ============

    public function schedulesIndex()
    {
        return view('fleet.maintenance.schedules.index');
    }

    public function schedulesData(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $query = FleetMaintenanceSchedule::query()
            ->where('company_id', $user->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with('vehicle');

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active == '1');
        }

        return DataTables::of($query)
            ->addColumn('vehicle_display', function($s) {
                return $s->vehicle ? $s->vehicle->name : 'N/A';
            })
            ->addColumn('status_display', function($s) {
                $colors = [
                    'up_to_date' => 'success',
                    'due_soon' => 'warning',
                    'overdue' => 'danger',
                    'completed' => 'info',
                ];
                $color = $colors[$s->current_status] ?? 'secondary';
                return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $s->current_status)) . '</span>';
            })
            ->addColumn('next_due_display', function($s) {
                if ($s->schedule_type === 'mileage_based' || $s->schedule_type === 'both') {
                    return number_format($s->next_due_odometer ?? 0, 0) . ' km';
                }
                return $s->next_due_date ? $s->next_due_date->format('Y-m-d') : 'N/A';
            })
            ->addColumn('actions', function($s) {
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<a href="' . route('fleet.maintenance.schedules.show', $s->hash_id) . '" class="btn btn-sm btn-outline-info" title="View"><i class="bx bx-show"></i></a>';
                $actions .= '<a href="' . route('fleet.maintenance.schedules.edit', $s->hash_id) . '" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status_display', 'actions'])
            ->make(true);
    }

    public function scheduleCreate()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $vehicles = Asset::where('company_id', $user->company_id)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number']);

        // Get cost categories for maintenance category dropdown
        $costCategories = \App\Models\Fleet\FleetCostCategory::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category_type']);

        return view('fleet.maintenance.schedules.create', compact('vehicles', 'costCategories'));
    }

    public function scheduleStore(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:assets,id',
            'schedule_name' => 'required|string|max:255',
            'schedule_type' => 'required|in:time_based,mileage_based,both',
            'maintenance_category' => 'required|string|max:100',
            'description' => 'nullable|string',
            'interval_days' => 'nullable|integer|min:1',
            'interval_months' => 'nullable|integer|min:1',
            'interval_km' => 'nullable|numeric|min:0',
            'alert_days_before' => 'nullable|integer|min:0',
            'alert_km_before' => 'nullable|numeric|min:0',
            'estimated_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Calculate next due date/odometer
        $vehicle = Asset::find($validated['vehicle_id']);
        $nextDueDate = null;
        $nextDueOdometer = null;

        if (in_array($validated['schedule_type'], ['time_based', 'both'])) {
            if ($validated['interval_months']) {
                $nextDueDate = now()->addMonths($validated['interval_months'])->toDateString();
            } elseif ($validated['interval_days']) {
                $nextDueDate = now()->addDays($validated['interval_days'])->toDateString();
            }
        }

        if (in_array($validated['schedule_type'], ['mileage_based', 'both'])) {
            $currentOdometer = $vehicle->current_odometer ?? 0;
            $nextDueOdometer = $currentOdometer + ($validated['interval_km'] ?? 0);
        }

        $schedule = FleetMaintenanceSchedule::create(array_merge($validated, [
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'next_due_date' => $nextDueDate,
            'next_due_odometer' => $nextDueOdometer,
            'current_status' => 'up_to_date',
            'is_active' => true,
            'created_by' => $user->id,
        ]));

        return redirect()->route('fleet.maintenance.schedules.index')->with('success', 'Maintenance schedule created successfully.');
    }

    public function scheduleShow(FleetMaintenanceSchedule $schedule)
    {
        $user = Auth::user();

        if ($schedule->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $schedule->load(['vehicle', 'workOrders']);
        $schedule->updateStatus();

        return view('fleet.maintenance.schedules.show', compact('schedule'));
    }

    public function scheduleEdit(FleetMaintenanceSchedule $schedule)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        if ($schedule->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $vehicles = Asset::where('company_id', $user->company_id)
            ->whereHas('category', fn($q) => $q->where('code', 'FA04'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number']);

        // Get cost categories for maintenance category dropdown
        $costCategories = \App\Models\Fleet\FleetCostCategory::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category_type']);

        return view('fleet.maintenance.schedules.edit', compact('schedule', 'vehicles', 'costCategories'));
    }

    public function scheduleUpdate(Request $request, FleetMaintenanceSchedule $schedule)
    {
        $user = Auth::user();

        if ($schedule->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'schedule_name' => 'required|string|max:255',
            'schedule_type' => 'required|in:time_based,mileage_based,both',
            'maintenance_category' => 'required|string|max:100',
            'description' => 'nullable|string',
            'interval_days' => 'nullable|integer|min:1',
            'interval_months' => 'nullable|integer|min:1',
            'interval_km' => 'nullable|numeric|min:0',
            'alert_days_before' => 'nullable|integer|min:0',
            'alert_km_before' => 'nullable|numeric|min:0',
            'estimated_cost' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $schedule->update(array_merge($validated, [
            'updated_by' => $user->id,
        ]));

        $schedule->updateStatus();

        return redirect()->route('fleet.maintenance.schedules.show', $schedule->hash_id)->with('success', 'Schedule updated successfully.');
    }

    private function generateWONumber()
    {
        // Use prefix from settings or default
        $prefix = $this->getWorkOrderNumberPrefix() ?: 'FLEET-WO';
        $date = date('Ymd');

        $lastWO = FleetMaintenanceWorkOrder::where('wo_number', 'like', $prefix . '-' . $date . '%')
            ->orderBy('wo_number', 'desc')
            ->first();

        if ($lastWO) {
            $lastNumber = (int) substr($lastWO->wo_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . $date . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
