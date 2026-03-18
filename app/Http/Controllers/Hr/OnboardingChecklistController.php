<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\OnboardingChecklist;
use App\Models\Hr\Department;
use App\Models\Hr\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OnboardingChecklistController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $checklists = OnboardingChecklist::where('company_id', current_company_id())
                ->with(['department', 'position'])
                ->orderBy('checklist_name');

            return DataTables::of($checklists)
                ->addIndexColumn()
                ->addColumn('department_name', function ($checklist) {
                    return $checklist->department->name ?? 'All Departments';
                })
                ->addColumn('position_name', function ($checklist) {
                    return $checklist->position->name ?? 'All Positions';
                })
                ->addColumn('applicable_to_badge', function ($checklist) {
                    $badges = [
                        'all' => 'primary',
                        'department' => 'info',
                        'position' => 'warning',
                        'role' => 'success',
                    ];
                    $badge = $badges[$checklist->applicable_to] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($checklist->applicable_to) . '</span>';
                })
                ->addColumn('items_count', function ($checklist) {
                    return $checklist->checklistItems()->count();
                })
                ->addColumn('status_badge', function ($checklist) {
                    $badge = $checklist->is_active ? 'success' : 'secondary';
                    $text = $checklist->is_active ? 'Active' : 'Inactive';
                    return '<span class="badge bg-' . $badge . '">' . $text . '</span>';
                })
                ->addColumn('action', function ($checklist) {
                    $viewBtn = '<a href="' . route('hr.onboarding-checklists.show', $checklist->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.onboarding-checklists.edit', $checklist->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $checklist->id . '" data-name="' . $checklist->checklist_name . '"><i class="bx bx-trash"></i></button>';
                    return $viewBtn . $editBtn . $deleteBtn;
                })
                ->rawColumns(['applicable_to_badge', 'status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.lifecycle.onboarding-checklists.index');
    }

    public function create()
    {
        $departments = Department::where('company_id', current_company_id())
            ->orderBy('name')
            ->get();
        
        // Use approved positions for onboarding checklists
        $positions = Position::where('company_id', current_company_id())
            ->where('status', 'approved')
            ->orderBy('title')
            ->get();

        return view('hr-payroll.lifecycle.onboarding-checklists.create', compact('departments', 'positions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'checklist_name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'applicable_to' => 'required|in:all,department,position,role',
            'department_id' => 'nullable|required_if:applicable_to,department|exists:hr_departments,id',
            'position_id' => 'nullable|required_if:applicable_to,position|exists:hr_positions,id',
            'is_active' => 'boolean',
            'checklist_items' => 'nullable|array',
            'checklist_items.*.item_title' => 'required|string|max:200',
            'checklist_items.*.item_description' => 'nullable|string',
            'checklist_items.*.item_type' => 'required|in:task,document_upload,policy_acknowledgment,system_access',
            'checklist_items.*.is_mandatory' => 'boolean',
            'checklist_items.*.sequence_order' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $checklist = OnboardingChecklist::create(array_merge($validated, [
                'company_id' => current_company_id(),
            ]));

            // Create checklist items
            if (!empty($validated['checklist_items'])) {
                foreach ($validated['checklist_items'] as $index => $item) {
                    $checklist->checklistItems()->create([
                        'item_title' => $item['item_title'],
                        'item_description' => $item['item_description'] ?? null,
                        'item_type' => $item['item_type'],
                        'is_mandatory' => $item['is_mandatory'] ?? true,
                        'sequence_order' => $item['sequence_order'] ?? $index,
                    ]);
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Onboarding checklist created successfully.'
                ]);
            }

            return redirect()->route('hr.onboarding-checklists.index')
                ->with('success', 'Onboarding checklist created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create onboarding checklist: ' . $e->getMessage()]);
        }
    }

    public function show(OnboardingChecklist $onboardingChecklist)
    {
        if ($onboardingChecklist->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $onboardingChecklist->load(['checklistItems', 'onboardingRecords.employee']);
        return view('hr-payroll.lifecycle.onboarding-checklists.show', compact('onboardingChecklist'));
    }

    public function edit(OnboardingChecklist $onboardingChecklist)
    {
        if ($onboardingChecklist->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $departments = Department::where('company_id', current_company_id())
            ->orderBy('name')
            ->get();
        
        // Use approved positions for onboarding checklists
        $positions = Position::where('company_id', current_company_id())
            ->where('status', 'approved')
            ->orderBy('title')
            ->get();

        $onboardingChecklist->load('checklistItems');

        return view('hr-payroll.lifecycle.onboarding-checklists.edit', compact('onboardingChecklist', 'departments', 'positions'));
    }

    public function update(Request $request, OnboardingChecklist $onboardingChecklist)
    {
        if ($onboardingChecklist->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'checklist_name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'applicable_to' => 'required|in:all,department,position,role',
            'department_id' => 'nullable|required_if:applicable_to,department|exists:hr_departments,id',
            'position_id' => 'nullable|required_if:applicable_to,position|exists:hr_positions,id',
            'is_active' => 'boolean',
            'checklist_items' => 'nullable|array',
            'checklist_items.*.id' => 'nullable|exists:hr_onboarding_checklist_items,id',
            'checklist_items.*.item_title' => 'required|string|max:200',
            'checklist_items.*.item_description' => 'nullable|string',
            'checklist_items.*.item_type' => 'required|in:task,document_upload,policy_acknowledgment,system_access',
            'checklist_items.*.is_mandatory' => 'boolean',
            'checklist_items.*.sequence_order' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $checklist->update($validated);

            // Update checklist items
            if (isset($validated['checklist_items'])) {
                $existingItemIds = [];
                foreach ($validated['checklist_items'] as $index => $item) {
                    if (!empty($item['id'])) {
                        // Update existing
                        $existingItem = $checklist->checklistItems()->find($item['id']);
                        if ($existingItem) {
                            $existingItem->update([
                                'item_title' => $item['item_title'],
                                'item_description' => $item['item_description'] ?? null,
                                'item_type' => $item['item_type'],
                                'is_mandatory' => $item['is_mandatory'] ?? true,
                                'sequence_order' => $item['sequence_order'] ?? $index,
                            ]);
                            $existingItemIds[] = $existingItem->id;
                        }
                    } else {
                        // Create new
                        $newItem = $checklist->checklistItems()->create([
                            'item_title' => $item['item_title'],
                            'item_description' => $item['item_description'] ?? null,
                            'item_type' => $item['item_type'],
                            'is_mandatory' => $item['is_mandatory'] ?? true,
                            'sequence_order' => $item['sequence_order'] ?? $index,
                        ]);
                        $existingItemIds[] = $newItem->id;
                    }
                }

                // Delete removed items
                $checklist->checklistItems()->whereNotIn('id', $existingItemIds)->delete();
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Onboarding checklist updated successfully.'
                ]);
            }

            return redirect()->route('hr.onboarding-checklists.index')
                ->with('success', 'Onboarding checklist updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update onboarding checklist: ' . $e->getMessage()]);
        }
    }

    public function destroy(OnboardingChecklist $onboardingChecklist)
    {
        if ($onboardingChecklist->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            if ($onboardingChecklist->onboardingRecords()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete checklist. It is being used in onboarding records.'
                ], 422);
            }

            $onboardingChecklist->checklistItems()->delete();
            $onboardingChecklist->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Onboarding checklist deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete onboarding checklist: ' . $e->getMessage()
            ], 500);
        }
    }
}
