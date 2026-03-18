<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\VacancyRequisition;
use App\Models\Hr\Position;
use App\Models\Hr\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class VacancyRequisitionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $requisitions = VacancyRequisition::where('company_id', current_company_id())
                ->with(['position', 'department', 'requestedByUser'])
                ->orderBy('created_at', 'desc');

            return DataTables::of($requisitions)
                ->addIndexColumn()
                ->addColumn('position_name', function ($req) {
                    return $req->position->title ?? 'N/A';
                })
                ->addColumn('department_name', function ($req) {
                    return $req->department->name ?? 'N/A';
                })
                ->addColumn('requested_by_name', function ($req) {
                    return $req->requestedByUser->name ?? 'N/A';
                })
                ->addColumn('salary_range', function ($req) {
                    if ($req->budgeted_salary_min && $req->budgeted_salary_max) {
                        return number_format($req->budgeted_salary_min, 2) . ' - ' . number_format($req->budgeted_salary_max, 2);
                    }
                    return 'N/A';
                })
                ->addColumn('status_badge', function ($req) {
                    $badges = [
                        'draft' => 'secondary',
                        'pending_approval' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'closed' => 'dark',
                        'filled' => 'info',
                    ];
                    $badge = $badges[$req->status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst(str_replace('_', ' ', $req->status)) . '</span>';
                })
                ->addColumn('applicants_count', function ($req) {
                    return $req->applicants()->count();
                })
                ->addColumn('action', function ($req) {
                    $viewBtn = '<a href="' . route('hr.vacancy-requisitions.show', $req->hash_id) . '" class="btn btn-sm btn-outline-info me-1" title="View"><i class="bx bx-show"></i></a>';
                    
                    // Only allow edit and delete for draft or rejected status
                    $canModify = in_array($req->status, ['draft', 'rejected']);
                    
                    if ($canModify) {
                        $editBtn = '<a href="' . route('hr.vacancy-requisitions.edit', $req->hash_id) . '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bx bx-edit"></i></a>';
                        $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $req->hash_id . '" data-name="' . $req->job_title . '" title="Delete"><i class="bx bx-trash"></i></button>';
                    } else {
                        $editBtn = '<button class="btn btn-sm btn-outline-secondary me-1" disabled title="Cannot edit ' . $req->status . ' requisition"><i class="bx bx-edit"></i></button>';
                        $deleteBtn = '<button class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete ' . $req->status . ' requisition"><i class="bx bx-trash"></i></button>';
                    }
                    
                    return $viewBtn . $editBtn . $deleteBtn;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.lifecycle.vacancy-requisitions.index');
    }

    public function create()
    {
        $positions = Position::where('company_id', current_company_id())
            ->where('status', 'approved')
            ->orderBy('title')
            ->get();
        
        $userBranchId = session('branch_id');
        $departments = Department::where('company_id', current_company_id())
            ->where(function($query) use ($userBranchId) {
                if ($userBranchId) {
                    $query->where('branch_id', $userBranchId);
                } else {
                    $query->whereNull('branch_id');
                }
            })
            ->orderBy('name')
            ->get();

        // Get active budgets and budget lines for financial control
        $currentYear = date('Y');
        $budget = \App\Models\Budget::where('company_id', current_company_id())
            ->where('year', $currentYear)
            ->where('status', 'approved')
            ->first();
        
        $budgetLines = collect();
        if ($budget) {
            $budgetLines = \App\Models\BudgetLine::where('budget_id', $budget->id)
                ->with('account')
                ->get();
        }

        return view('hr-payroll.lifecycle.vacancy-requisitions.create', compact('positions', 'departments', 'budgetLines'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'position_id' => 'required|exists:hr_positions,id',
            'department_id' => 'nullable|exists:hr_departments,id',
            'job_title' => 'required|string|max:200',
            'job_description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'number_of_positions' => 'required|integer|min:1',
            'budgeted_salary_min' => 'nullable|numeric|min:0',
            'budgeted_salary_max' => 'nullable|numeric|min:0|gte:budgeted_salary_min',
            'opening_date' => 'nullable|date',
            'closing_date' => 'nullable|date|after:opening_date',
            'status' => 'required|in:draft,pending_approval,approved,rejected,closed,filled',
            // Blueprint enhancement fields
            'hiring_justification' => 'nullable|string|max:2000',
            'budget_line_id' => 'nullable|exists:budget_lines,id',
            'project_grant_code' => 'nullable|string|max:100',
            'contract_period_months' => 'nullable|integer|min:1',
            'recruitment_type' => 'nullable|in:internal,external,both',
            'is_publicly_posted' => 'nullable|boolean',
            'posting_start_date' => 'nullable|date',
            'posting_end_date' => 'nullable|date|after:posting_start_date',
        ]);

        DB::beginTransaction();
        try {
            // Generate requisition number
            $count = VacancyRequisition::where('company_id', current_company_id())->count() + 1;
            $requisitionNumber = 'REQ-' . str_pad($count, 6, '0', STR_PAD_LEFT);

            $data = array_merge($validated, [
                'company_id' => current_company_id(),
                'requisition_number' => $requisitionNumber,
                'requested_by' => auth()->id(),
                'is_publicly_posted' => $request->has('is_publicly_posted') && $request->is_publicly_posted == '1',
            ]);
            
            $vacancyRequisition = VacancyRequisition::create($data);

            // Save eligibility rules
            if ($request->has('rules') && is_array($request->rules)) {
                foreach ($request->rules as $ruleData) {
                    $vacancyRequisition->eligibilityRules()->create([
                        'company_id' => current_company_id(),
                        'rule_type' => $ruleData['rule_type'],
                        'rule_operator' => $ruleData['rule_operator'],
                        'rule_value' => $ruleData['rule_value'],
                        'is_mandatory' => isset($ruleData['is_mandatory']) && $ruleData['is_mandatory'] == '1',
                        'weight' => $ruleData['weight'] ?? 0,
                        'applies_to' => $ruleData['applies_to'] ?? 'all',
                        'is_active' => true,
                    ]);
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vacancy requisition created successfully.'
                ]);
            }

            return redirect()->route('hr.vacancy-requisitions.index')
                ->with('success', 'Vacancy requisition created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create vacancy requisition: ' . $e->getMessage()]);
        }
    }

    public function show(VacancyRequisition $vacancyRequisition)
    {
        if ($vacancyRequisition->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $vacancyRequisition->load(['position', 'department', 'costCenter', 'budgetLine.account', 'requestedByUser', 'approvedByUser', 'applicants', 'eligibilityRules']);
        
        $approvalHistory = \App\Models\Hr\VacancyRequisitionApprovalHistory::where('vacancy_requisition_id', $vacancyRequisition->id)
            ->with(['approver', 'approvalLevel'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Check if current user can approve
        $canApprove = false;
        $currentLevel = null;
        $currentApprovers = collect();
        $approvalSummary = null;
        
        if ($vacancyRequisition->status === 'pending_approval') {
            $user = auth()->user();
            $approvalSettings = \App\Models\VacancyRequisitionApprovalSettings::getSettingsForCompany(
                $vacancyRequisition->company_id,
                $user->branch_id
            );
            
            if ($approvalSettings && $approvalSettings->approval_required) {
                // For vacancy requisitions, check all levels since there's no amount threshold
                // Check if user is an approver at any level
                for ($level = 1; $level <= $approvalSettings->approval_levels; $level++) {
                    if ($approvalSettings->canUserApproveAtLevel($user->id, $level)) {
                        $canApprove = true;
                        $currentLevel = (object)[
                            'level' => $level,
                            'level_name' => "Level {$level}"
                        ];
                        $approvers = $approvalSettings->getApproversForLevel($level);
                        $currentApprovers = \App\Models\User::whereIn('id', $approvers)->get();
                        break; // Use first level where user can approve
                    }
                }
                
                // Build approval summary
                $approvalSummary = [
                    'total_levels' => $approvalSettings->approval_levels,
                    'approved_levels' => 0, // This would need to be tracked in approval history
                    'pending_levels' => $approvalSettings->approval_levels,
                ];
            }
        }
        
        return view('hr-payroll.lifecycle.vacancy-requisitions.show', compact(
            'vacancyRequisition',
            'canApprove',
            'currentLevel',
            'currentApprovers',
            'approvalSummary',
            'approvalHistory'
        ));
    }

    public function edit(VacancyRequisition $vacancyRequisition)
    {
        if ($vacancyRequisition->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!in_array($vacancyRequisition->status, ['draft', 'rejected'])) {
            return redirect()->route('hr.vacancy-requisitions.index')
                ->with('error', 'Only draft or rejected requisitions can be edited.');
        }

        $positions = Position::where('company_id', current_company_id())
            ->where('status', 'approved')
            ->orderBy('title')
            ->get();
        
        $userBranchId = session('branch_id');
        $departments = Department::where('company_id', current_company_id())
            ->where(function($query) use ($userBranchId) {
                if ($userBranchId) {
                    $query->where('branch_id', $userBranchId);
                } else {
                    $query->whereNull('branch_id');
                }
            })
            ->orderBy('name')
            ->get();

        // Get active budgets and budget lines for financial control
        $currentYear = date('Y');
        $budget = \App\Models\Budget::where('company_id', current_company_id())
            ->where('year', $currentYear)
            ->where('status', 'approved')
            ->first();
        
        $budgetLines = collect();
        if ($budget) {
            $budgetLines = \App\Models\BudgetLine::where('budget_id', $budget->id)
                ->with('account')
                ->get();
        }

        $vacancyRequisition->load('eligibilityRules');

        return view('hr-payroll.lifecycle.vacancy-requisitions.edit', compact('vacancyRequisition', 'positions', 'departments', 'budgetLines'));
    }

    public function update(Request $request, VacancyRequisition $vacancyRequisition)
    {
        if ($vacancyRequisition->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!in_array($vacancyRequisition->status, ['draft', 'rejected'])) {
            return redirect()->route('hr.vacancy-requisitions.index')
                ->with('error', 'Only draft or rejected requisitions can be updated.');
        }

        $validated = $request->validate([
            'position_id' => 'required|exists:hr_positions,id',
            'department_id' => 'nullable|exists:hr_departments,id',
            'job_title' => 'required|string|max:200',
            'job_description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'number_of_positions' => 'required|integer|min:1',
            'budgeted_salary_min' => 'nullable|numeric|min:0',
            'budgeted_salary_max' => 'nullable|numeric|min:0|gte:budgeted_salary_min',
            'opening_date' => 'nullable|date',
            'closing_date' => 'nullable|date|after:opening_date',
            'status' => 'required|in:draft,pending_approval,approved,rejected,closed,filled',
            'approval_notes' => 'nullable|string',
            'rejection_reason' => 'nullable|string',
            // Blueprint enhancement fields
            'hiring_justification' => 'nullable|string|max:2000',
            'budget_line_id' => 'nullable|exists:budget_lines,id',
            'project_grant_code' => 'nullable|string|max:100',
            'contract_period_months' => 'nullable|integer|min:1',
            'recruitment_type' => 'nullable|in:internal,external,both',
            'is_publicly_posted' => 'nullable|boolean',
            'posting_start_date' => 'nullable|date',
            'posting_end_date' => 'nullable|date|after:posting_start_date',
        ]);

        DB::beginTransaction();
        try {
            // Handle approval
            if ($validated['status'] === 'approved' && $vacancyRequisition->status !== 'approved') {
                $validated['approved_by'] = auth()->id();
                $validated['approved_at'] = now();
            } elseif ($validated['status'] !== 'approved' && $vacancyRequisition->approved_by) {
                $validated['approved_by'] = null;
                $validated['approved_at'] = null;
            }

            // Handle boolean field
            $validated['is_publicly_posted'] = $request->has('is_publicly_posted') && $request->is_publicly_posted == '1';

            $vacancyRequisition->update($validated);

            // Update eligibility rules (only if not published)
            if (!$vacancyRequisition->published_to_portal) {
                if ($request->has('rules') && is_array($request->rules)) {
                    $keepRuleIds = [];
                    foreach ($request->rules as $ruleData) {
                        $rule = null;
                        if (isset($ruleData['id'])) {
                            $rule = $vacancyRequisition->eligibilityRules()->find($ruleData['id']);
                        }

                        if ($rule) {
                            $rule->update([
                                'rule_type' => $ruleData['rule_type'],
                                'rule_operator' => $ruleData['rule_operator'],
                                'rule_value' => $ruleData['rule_value'],
                                'is_mandatory' => isset($ruleData['is_mandatory']) && $ruleData['is_mandatory'] == '1',
                                'weight' => $ruleData['weight'] ?? 0,
                                'applies_to' => $ruleData['applies_to'] ?? 'all',
                            ]);
                            $keepRuleIds[] = $rule->id;
                        } else {
                            $newRule = $vacancyRequisition->eligibilityRules()->create([
                                'company_id' => current_company_id(),
                                'rule_type' => $ruleData['rule_type'],
                                'rule_operator' => $ruleData['rule_operator'],
                                'rule_value' => $ruleData['rule_value'],
                                'is_mandatory' => isset($ruleData['is_mandatory']) && $ruleData['is_mandatory'] == '1',
                                'weight' => $ruleData['weight'] ?? 0,
                                'applies_to' => $ruleData['applies_to'] ?? 'all',
                                'is_active' => true,
                            ]);
                            $keepRuleIds[] = $newRule->id;
                        }
                    }
                    // Delete rules that were removed from the form
                    $vacancyRequisition->eligibilityRules()->whereNotIn('id', $keepRuleIds)->delete();
                } else {
                    // No rules in request, delete all existing rules for this requisition
                    $vacancyRequisition->eligibilityRules()->delete();
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vacancy requisition updated successfully.'
                ]);
            }

            return redirect()->route('hr.vacancy-requisitions.index')
                ->with('success', 'Vacancy requisition updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update vacancy requisition: ' . $e->getMessage()]);
        }
    }

    public function destroy(VacancyRequisition $vacancyRequisition)
    {
        if ($vacancyRequisition->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!in_array($vacancyRequisition->status, ['draft', 'rejected'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft or rejected requisitions can be deleted.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            if ($vacancyRequisition->applicants()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete vacancy requisition. It has associated applicants.'
                ], 422);
            }

            $vacancyRequisition->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vacancy requisition deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete vacancy requisition: ' . $e->getMessage()
            ], 500);
        }
    }

    public function submit(Request $request, VacancyRequisition $vacancyRequisition)
    {
        if ($vacancyRequisition->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!in_array($vacancyRequisition->status, ['draft', 'rejected'])) {
            return back()->with('error', 'Only draft or rejected requisitions can be submitted for approval.');
        }

        if ($vacancyRequisition->requested_by !== auth()->id()) {
            return back()->with('error', 'Only the requester can submit for approval.');
        }

        DB::beginTransaction();
        try {
            $vacancyRequisition->update([
                'status' => 'pending_approval',
            ]);

            // Record approval history
            \App\Models\Hr\VacancyRequisitionApprovalHistory::create([
                'vacancy_requisition_id' => $vacancyRequisition->id,
                'action' => 'submitted',
                'approver_id' => auth()->id(),
                'comments' => 'Requisition submitted for approval',
                'action_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('hr.vacancy-requisitions.show', $vacancyRequisition->hash_id)
                ->with('success', 'Vacancy requisition submitted for approval successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to submit for approval: ' . $e->getMessage());
        }
    }

    public function approve(Request $request, VacancyRequisition $vacancyRequisition)
    {
        if ($vacancyRequisition->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if ($vacancyRequisition->status !== 'pending_approval') {
            return back()->with('error', 'Only requisitions pending approval can be approved.');
        }

        $validated = $request->validate([
            'comments' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $vacancyRequisition->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $validated['comments'] ?? null,
            ]);

            // Record approval history
            \App\Models\Hr\VacancyRequisitionApprovalHistory::create([
                'vacancy_requisition_id' => $vacancyRequisition->id,
                'action' => 'approved',
                'approver_id' => auth()->id(),
                'comments' => $validated['comments'] ?? 'Requisition approved',
                'action_at' => now(),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vacancy requisition approved successfully.'
                ]);
            }

            return redirect()->route('hr.vacancy-requisitions.show', $vacancyRequisition->hash_id)
                ->with('success', 'Vacancy requisition approved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to approve: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to approve: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, VacancyRequisition $vacancyRequisition)
    {
        if ($vacancyRequisition->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if ($vacancyRequisition->status !== 'pending_approval') {
            return back()->with('error', 'Only requisitions pending approval can be rejected.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $vacancyRequisition->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['reason'],
            ]);

            // Record approval history
            \App\Models\Hr\VacancyRequisitionApprovalHistory::create([
                'vacancy_requisition_id' => $vacancyRequisition->id,
                'action' => 'rejected',
                'approver_id' => auth()->id(),
                'comments' => $validated['reason'],
                'action_at' => now(),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vacancy requisition rejected successfully.'
                ]);
            }

            return redirect()->route('hr.vacancy-requisitions.show', $vacancyRequisition->hash_id)
                ->with('success', 'Vacancy requisition rejected successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reject: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to reject: ' . $e->getMessage());
        }
    }

    public function publish(Request $request, VacancyRequisition $vacancyRequisition)
    {
        if ($vacancyRequisition->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        if ($vacancyRequisition->status !== 'approved') {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved requisitions can be published to the job portal.'
                ], 400);
            }
            return back()->with('error', 'Only approved requisitions can be published to the job portal.');
        }

        DB::beginTransaction();
        try {
            // Generate unique slug if not exists
            if (!$vacancyRequisition->public_slug) {
                $slug = Str::slug($vacancyRequisition->job_title . '-' . $vacancyRequisition->id);
                // Ensure uniqueness
                $count = VacancyRequisition::where('public_slug', $slug)->count();
                if ($count > 0) {
                    $slug .= '-' . time();
                }
                $vacancyRequisition->public_slug = $slug;
            }

            $vacancyRequisition->update([
                'published_to_portal' => true,
                'published_at' => now(),
                'is_publicly_posted' => true,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vacancy requisition published to job portal successfully.'
                ]);
            }

            return redirect()->route('hr.vacancy-requisitions.show', $vacancyRequisition->hash_id)
                ->with('success', 'Vacancy requisition published to job portal successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to publish: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to publish: ' . $e->getMessage());
        }
    }

    public function unpublish(Request $request, VacancyRequisition $vacancyRequisition)
    {
        if ($vacancyRequisition->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            $vacancyRequisition->update([
                'published_to_portal' => false,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vacancy requisition removed from job portal successfully.'
                ]);
            }

            return redirect()->route('hr.vacancy-requisitions.show', $vacancyRequisition->hash_id)
                ->with('success', 'Vacancy requisition removed from job portal successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to unpublish: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to unpublish: ' . $e->getMessage());
        }
    }
}
