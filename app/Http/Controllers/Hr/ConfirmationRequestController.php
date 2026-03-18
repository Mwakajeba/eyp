<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\ConfirmationRequest;
use App\Models\Hr\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class ConfirmationRequestController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $requests = ConfirmationRequest::whereHas('employee', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with(['employee', 'requestedByUser'])
            ->orderBy('created_at', 'desc');

            return DataTables::of($requests)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($req) {
                    return $req->employee->full_name;
                })
                ->addColumn('employee_number', function ($req) {
                    return $req->employee->employee_number;
                })
                ->addColumn('probation_period', function ($req) {
                    return $req->probation_start_date->format('d M Y') . ' - ' . $req->probation_end_date->format('d M Y');
                })
                ->addColumn('recommendation_badge', function ($req) {
                    if (!$req->recommendation_type) {
                        return '-';
                    }
                    $badges = [
                        'confirm' => 'success',
                        'extend' => 'warning',
                        'terminate' => 'danger',
                    ];
                    $badge = $badges[$req->recommendation_type] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($req->recommendation_type) . '</span>';
                })
                ->addColumn('status_badge', function ($req) {
                    $badges = [
                        'pending' => 'secondary',
                        'manager_review' => 'info',
                        'hr_review' => 'primary',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'extended' => 'warning',
                    ];
                    $badge = $badges[$req->status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst(str_replace('_', ' ', $req->status)) . '</span>';
                })
                ->addColumn('action', function ($req) {
                    $viewBtn = '<a href="' . route('hr.confirmation-requests.show', $req->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.confirmation-requests.edit', $req->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    return $viewBtn . $editBtn;
                })
                ->rawColumns(['recommendation_badge', 'status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.lifecycle.confirmation-requests.index');
    }

    public function create(Request $request)
    {
        $companyId = current_company_id();
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $employeeId = $request->get('employee_id');

        return view('hr-payroll.lifecycle.confirmation-requests.create', compact('employees', 'employeeId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'probation_start_date' => 'required|date',
            'probation_end_date' => 'required|date|after:probation_start_date',
            'review_date' => 'nullable|date',
            'performance_summary' => 'nullable|string',
            'recommendation' => 'nullable|string',
            'recommendation_type' => 'nullable|in:confirm,extend,terminate',
            'extension_months' => 'nullable|integer|min:1|required_if:recommendation_type,extend',
            'status' => 'required|in:pending,manager_review,hr_review,approved,rejected,extended',
            'salary_adjustment_amount' => 'nullable|numeric|min:0',
            'confirmation_bonus' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($validated['employee_id']);

            ConfirmationRequest::create(array_merge($validated, [
                'requested_by' => auth()->id(),
            ]));

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Confirmation request created successfully.'
                ]);
            }

            return redirect()->route('hr.confirmation-requests.index')
                ->with('success', 'Confirmation request created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create confirmation request: ' . $e->getMessage()]);
        }
    }

    public function show(ConfirmationRequest $confirmationRequest)
    {
        if ($confirmationRequest->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $confirmationRequest->load(['employee', 'requestedByUser', 'managerReviewer', 'hrReviewer', 'approver']);
        return view('hr-payroll.lifecycle.confirmation-requests.show', compact('confirmationRequest'));
    }

    public function edit(ConfirmationRequest $confirmationRequest)
    {
        if ($confirmationRequest->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        return view('hr-payroll.lifecycle.confirmation-requests.edit', compact('confirmationRequest'));
    }

    public function update(Request $request, ConfirmationRequest $confirmationRequest)
    {
        if ($confirmationRequest->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'probation_start_date' => 'required|date',
            'probation_end_date' => 'required|date|after:probation_start_date',
            'review_date' => 'nullable|date',
            'performance_summary' => 'nullable|string',
            'recommendation' => 'nullable|string',
            'recommendation_type' => 'nullable|in:confirm,extend,terminate',
            'extension_months' => 'nullable|integer|min:1|required_if:recommendation_type,extend',
            'status' => 'required|in:pending,manager_review,hr_review,approved,rejected,extended',
            'salary_adjustment_amount' => 'nullable|numeric|min:0',
            'confirmation_bonus' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Handle workflow status changes
            if ($validated['status'] === 'manager_review' && $confirmationRequest->status === 'pending') {
                $validated['reviewed_by_manager'] = auth()->id();
                $validated['manager_reviewed_at'] = now();
            } elseif ($validated['status'] === 'hr_review' && $confirmationRequest->status === 'manager_review') {
                $validated['reviewed_by_hr'] = auth()->id();
                $validated['hr_reviewed_at'] = now();
            } elseif ($validated['status'] === 'approved' && $confirmationRequest->status !== 'approved') {
                $validated['approved_by'] = auth()->id();
                $validated['approved_at'] = now();
                $validated['confirmation_effective_date'] = $validated['confirmation_effective_date'] ?? now();
            }

            $confirmationRequest->update($validated);

            // Apply salary adjustment if approved and not yet applied
            if ($confirmationRequest->status === 'approved' && !$confirmationRequest->salary_adjusted && $confirmationRequest->salary_adjustment_amount) {
                // Update employee salary
                $employee = $confirmationRequest->employee;
                $newSalary = $employee->basic_salary + $confirmationRequest->salary_adjustment_amount;
                $employee->update(['basic_salary' => $newSalary]);
                $confirmationRequest->update(['salary_adjusted' => true]);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Confirmation request updated successfully.'
                ]);
            }

            return redirect()->route('hr.confirmation-requests.index')
                ->with('success', 'Confirmation request updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update confirmation request: ' . $e->getMessage()]);
        }
    }

    public function destroy(ConfirmationRequest $confirmationRequest)
    {
        if ($confirmationRequest->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        DB::beginTransaction();
        try {
            $confirmationRequest->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Confirmation request deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete confirmation request: ' . $e->getMessage()
            ], 500);
        }
    }
}
