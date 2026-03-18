<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\OnboardingRecord;
use App\Models\Hr\OnboardingChecklist;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OnboardingRecordController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $records = OnboardingRecord::whereHas('employee', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with(['employee', 'onboardingChecklist'])
            ->orderBy('created_at', 'desc');

            return DataTables::of($records)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($record) {
                    return $record->employee->full_name;
                })
                ->addColumn('employee_number', function ($record) {
                    return $record->employee->employee_number;
                })
                ->addColumn('checklist_name', function ($record) {
                    return $record->onboardingChecklist->checklist_name;
                })
                ->addColumn('progress_display', function ($record) {
                    return '<div class="progress" style="height: 20px;">
                        <div class="progress-bar" role="progressbar" style="width: ' . $record->progress_percent . '%;" aria-valuenow="' . $record->progress_percent . '" aria-valuemin="0" aria-valuemax="100">
                            ' . $record->progress_percent . '%
                        </div>
                    </div>';
                })
                ->addColumn('completion_status', function ($record) {
                    return $record->completed_items . ' / ' . $record->total_items;
                })
                ->addColumn('payroll_eligible_badge', function ($record) {
                    if ($record->payroll_eligible) {
                        return '<span class="badge bg-success"><i class="bx bx-check"></i> Eligible</span>';
                    }
                    return '<span class="badge bg-secondary">Not Eligible</span>';
                })
                ->addColumn('status_badge', function ($record) {
                    $badges = [
                        'in_progress' => 'primary',
                        'completed' => 'success',
                        'on_hold' => 'warning',
                    ];
                    $badge = $badges[$record->status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst(str_replace('_', ' ', $record->status)) . '</span>';
                })
                ->addColumn('action', function ($record) {
                    $viewBtn = '<a href="' . route('hr.onboarding-records.show', $record->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.onboarding-records.edit', $record->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    return $viewBtn . $editBtn;
                })
                ->rawColumns(['progress_display', 'payroll_eligible_badge', 'status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.lifecycle.onboarding-records.index');
    }

    public function create(Request $request)
    {
        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();
        
        $checklists = OnboardingChecklist::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('checklist_name')
            ->get();

        $employeeId = $request->get('employee_id');
        $checklistId = $request->get('checklist_id');

        return view('hr-payroll.lifecycle.onboarding-records.create', compact('employees', 'checklists', 'employeeId', 'checklistId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'onboarding_checklist_id' => 'required|exists:hr_onboarding_checklists,id',
            'start_date' => 'required|date',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($validated['employee_id']);

            $checklist = OnboardingChecklist::where('company_id', current_company_id())
                ->findOrFail($validated['onboarding_checklist_id']);

            // Check if employee already has an active onboarding record
            $existingRecord = OnboardingRecord::where('employee_id', $validated['employee_id'])
                ->where('status', OnboardingRecord::STATUS_IN_PROGRESS)
                ->first();

            if ($existingRecord) {
                return back()->withInput()->withErrors([
                    'error' => 'Employee already has an active onboarding record.'
                ]);
            }

            $record = OnboardingRecord::create([
                'employee_id' => $validated['employee_id'],
                'onboarding_checklist_id' => $validated['onboarding_checklist_id'],
                'start_date' => $validated['start_date'],
                'assigned_to' => $validated['assigned_to'] ?? null,
                'total_items' => $checklist->checklistItems()->count(),
                'completed_items' => 0,
                'progress_percent' => 0,
                'status' => OnboardingRecord::STATUS_IN_PROGRESS,
            ]);

            // Create record items from checklist items
            foreach ($checklist->checklistItems as $item) {
                $record->recordItems()->create([
                    'checklist_item_id' => $item->id,
                    'is_completed' => false,
                ]);
            }

            $record->updateProgress();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Onboarding record created successfully.'
                ]);
            }

            return redirect()->route('hr.onboarding-records.index')
                ->with('success', 'Onboarding record created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create onboarding record: ' . $e->getMessage()]);
        }
    }

    public function show(OnboardingRecord $onboardingRecord)
    {
        if ($onboardingRecord->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $onboardingRecord->load(['employee', 'onboardingChecklist.checklistItems', 'recordItems.checklistItem', 'assignedToUser']);
        return view('hr-payroll.lifecycle.onboarding-records.show', compact('onboardingRecord'));
    }

    public function edit(OnboardingRecord $onboardingRecord)
    {
        if ($onboardingRecord->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if ($onboardingRecord->status === OnboardingRecord::STATUS_COMPLETED) {
            return redirect()->route('hr.onboarding-records.show', $onboardingRecord->id)
                ->with('error', 'Cannot edit completed onboarding record.');
        }

        $onboardingRecord->load(['recordItems.checklistItem']);
        return view('hr-payroll.lifecycle.onboarding-records.edit', compact('onboardingRecord'));
    }

    public function update(Request $request, OnboardingRecord $onboardingRecord)
    {
        if ($onboardingRecord->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'status' => 'required|in:in_progress,completed,on_hold',
            'payroll_eligible' => 'boolean',
            'record_items' => 'nullable|array',
            'record_items.*.id' => 'required|exists:hr_onboarding_record_items,id',
            'record_items.*.is_completed' => 'boolean',
            'record_items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Update record items
            if (isset($validated['record_items'])) {
                foreach ($validated['record_items'] as $item) {
                    $recordItem = $onboardingRecord->recordItems()->find($item['id']);
                    if ($recordItem) {
                        $recordItem->update([
                            'is_completed' => $item['is_completed'] ?? false,
                            'notes' => $item['notes'] ?? null,
                        ]);

                        if ($item['is_completed'] && !$recordItem->completed_at) {
                            $recordItem->markCompleted();
                        }
                    }
                }
            }

            $onboardingRecord->update([
                'status' => $validated['status'],
                'payroll_eligible' => $validated['payroll_eligible'] ?? false,
            ]);

            // Update progress
            $onboardingRecord->updateProgress();

            // Activate payroll if eligible and not already activated
            if ($onboardingRecord->payroll_eligible && !$onboardingRecord->payroll_activated_at) {
                $onboardingRecord->update([
                    'payroll_activated_at' => now(),
                ]);
                // Update employee to include in payroll
                $onboardingRecord->employee->update(['include_in_payroll' => true]);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Onboarding record updated successfully.'
                ]);
            }

            return redirect()->route('hr.onboarding-records.index')
                ->with('success', 'Onboarding record updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update onboarding record: ' . $e->getMessage()]);
        }
    }

    public function destroy(OnboardingRecord $onboardingRecord)
    {
        if ($onboardingRecord->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            $onboardingRecord->recordItems()->delete();
            $onboardingRecord->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Onboarding record deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete onboarding record: ' . $e->getMessage()
            ], 500);
        }
    }
}
