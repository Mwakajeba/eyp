<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\EmployeeExit;
use App\Models\Hr\ExitClearanceItem;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class ExitController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $exits = EmployeeExit::whereHas('employee', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with(['employee', 'clearanceItems', 'initiatedByUser', 'approvedByUser'])
            ->orderBy('created_at', 'desc');

            return DataTables::of($exits)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($exit) {
                    return $exit->employee->full_name;
                })
                ->addColumn('employee_number', function ($exit) {
                    return $exit->employee->employee_number;
                })
                ->addColumn('exit_type_badge', function ($exit) {
                    $badges = [
                        'resignation' => 'info',
                        'termination' => 'danger',
                        'retirement' => 'success',
                        'contract_expiry' => 'warning',
                        'redundancy' => 'secondary',
                    ];
                    $badge = $badges[$exit->exit_type] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst(str_replace('_', ' ', $exit->exit_type)) . '</span>';
                })
                ->addColumn('clearance_status_badge', function ($exit) {
                    $badges = [
                        'pending' => 'secondary',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                    ];
                    $badge = $badges[$exit->clearance_status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst(str_replace('_', ' ', $exit->clearance_status)) . '</span>';
                })
                ->addColumn('final_pay_status_badge', function ($exit) {
                    $badges = [
                        'pending' => 'secondary',
                        'calculated' => 'info',
                        'approved' => 'warning',
                        'paid' => 'success',
                    ];
                    $badge = $badges[$exit->final_pay_status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($exit->final_pay_status) . '</span>';
                })
                ->addColumn('final_pay_display', function ($exit) {
                    return $exit->final_pay_amount ? number_format($exit->final_pay_amount, 2) : '-';
                })
                ->addColumn('clearance_progress', function ($exit) {
                    $total = $exit->clearanceItems()->count();
                    $completed = $exit->clearanceItems()->where('status', 'completed')->count();
                    if ($total > 0) {
                        $percent = round(($completed / $total) * 100);
                        return $completed . ' / ' . $total . ' (' . $percent . '%)';
                    }
                    return 'N/A';
                })
                ->addColumn('action', function ($exit) {
                    $viewBtn = '<a href="' . route('hr.exits.show', $exit->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.exits.edit', $exit->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    return $viewBtn . $editBtn;
                })
                ->rawColumns(['exit_type_badge', 'clearance_status_badge', 'final_pay_status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.discipline.exits.index');
    }

    public function create(Request $request)
    {
        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $employeeId = $request->get('employee_id');

        return view('hr-payroll.discipline.exits.create', compact('employees', 'employeeId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'exit_type' => 'required|in:resignation,termination,retirement,contract_expiry,redundancy',
            'resignation_date' => 'nullable|date|required_if:exit_type,resignation',
            'effective_date' => 'required|date',
            'notice_period_days' => 'nullable|integer|min:0',
            'exit_reason' => 'nullable|string',
            'clearance_items' => 'nullable|array',
            'clearance_items.*.clearance_item' => 'required|string|max:200',
            'clearance_items.*.department' => 'nullable|string|max:100',
            'clearance_items.*.sequence_order' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($validated['employee_id']);

            // Generate exit number
            $count = EmployeeExit::whereHas('employee', function($q) {
                $q->where('company_id', current_company_id());
            })->count() + 1;
            $exitNumber = 'EXT-' . str_pad($count, 6, '0', STR_PAD_LEFT);

            $exit = EmployeeExit::create([
                'employee_id' => $validated['employee_id'],
                'exit_number' => $exitNumber,
                'exit_type' => $validated['exit_type'],
                'resignation_date' => $validated['resignation_date'] ?? null,
                'effective_date' => $validated['effective_date'],
                'notice_period_days' => $validated['notice_period_days'] ?? null,
                'exit_reason' => $validated['exit_reason'] ?? null,
                'initiated_by' => auth()->id(),
            ]);

            // Create clearance items
            if (!empty($validated['clearance_items'])) {
                foreach ($validated['clearance_items'] as $index => $item) {
                    $exit->clearanceItems()->create([
                        'clearance_item' => $item['clearance_item'],
                        'department' => $item['department'] ?? null,
                        'sequence_order' => $item['sequence_order'] ?? $index,
                        'status' => 'pending',
                    ]);
                }
            }

            // Update employee status to 'exited'
            $employee->update(['status' => 'exited']);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Exit record created successfully.'
                ]);
            }

            return redirect()->route('hr.exits.index')
                ->with('success', 'Exit record created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create exit record: ' . $e->getMessage()]);
        }
    }

    public function show(EmployeeExit $exit)
    {
        if ($exit->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $exit->load(['employee', 'clearanceItems.completedByUser', 'initiatedByUser', 'approvedByUser']);
        return view('hr-payroll.discipline.exits.show', compact('exit'));
    }

    public function edit(EmployeeExit $exit)
    {
        if ($exit->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->orderBy('first_name')
            ->get();

        $exit->load('clearanceItems');

        return view('hr-payroll.discipline.exits.edit', compact('exit', 'employees'));
    }

    public function update(Request $request, EmployeeExit $exit)
    {
        if ($exit->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'exit_type' => 'required|in:resignation,termination,retirement,contract_expiry,redundancy',
            'resignation_date' => 'nullable|date|required_if:exit_type,resignation',
            'effective_date' => 'required|date',
            'notice_period_days' => 'nullable|integer|min:0',
            'exit_reason' => 'nullable|string',
            'exit_interview_notes' => 'nullable|string',
            'exit_interview_conducted' => 'boolean',
            'clearance_status' => 'required|in:pending,in_progress,completed',
            'final_pay_status' => 'required|in:pending,calculated,approved,paid',
            'final_pay_amount' => 'nullable|numeric|min:0',
            'final_pay_notes' => 'nullable|string',
            'clearance_items' => 'nullable|array',
            'clearance_items.*.id' => 'nullable|exists:hr_exit_clearance_items,id',
            'clearance_items.*.clearance_item' => 'required|string|max:200',
            'clearance_items.*.department' => 'nullable|string|max:100',
            'clearance_items.*.status' => 'required|in:pending,completed,waived',
            'clearance_items.*.notes' => 'nullable|string',
            'clearance_items.*.sequence_order' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $exit->update($validated);

            // Update clearance items
            if (isset($validated['clearance_items'])) {
                $existingItemIds = [];
                foreach ($validated['clearance_items'] as $index => $item) {
                    if (!empty($item['id'])) {
                        // Update existing
                        $clearanceItem = $exit->clearanceItems()->find($item['id']);
                        if ($clearanceItem) {
                            $clearanceItem->update([
                                'clearance_item' => $item['clearance_item'],
                                'department' => $item['department'] ?? null,
                                'status' => $item['status'],
                                'notes' => $item['notes'] ?? null,
                                'sequence_order' => $item['sequence_order'] ?? $index,
                            ]);

                            if ($item['status'] === 'completed' && !$clearanceItem->completed_at) {
                                $clearanceItem->markCompleted();
                            }

                            $existingItemIds[] = $clearanceItem->id;
                        }
                    } else {
                        // Create new
                        $newItem = $exit->clearanceItems()->create([
                            'clearance_item' => $item['clearance_item'],
                            'department' => $item['department'] ?? null,
                            'status' => $item['status'],
                            'notes' => $item['notes'] ?? null,
                            'sequence_order' => $item['sequence_order'] ?? $index,
                        ]);
                        $existingItemIds[] = $newItem->id;
                    }
                }

                // Delete removed items
                $exit->clearanceItems()->whereNotIn('id', $existingItemIds)->delete();
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Exit record updated successfully.'
                ]);
            }

            return redirect()->route('hr.exits.index')
                ->with('success', 'Exit record updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update exit record: ' . $e->getMessage()]);
        }
    }

    public function destroy(EmployeeExit $exit)
    {
        if ($exit->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            $exit->clearanceItems()->delete();
            $exit->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Exit record deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete exit record: ' . $e->getMessage()
            ], 500);
        }
    }
}
