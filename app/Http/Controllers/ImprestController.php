<?php

namespace App\Http\Controllers;

use App\Models\ImprestRequest;
use App\Models\ImprestDisbursement;
use App\Models\ImprestLiquidation;
use App\Models\ImprestDocument;
use App\Models\ImprestItem;
use App\Models\ImprestSettings;
use App\Models\Retirement;
use App\Models\RetirementApproval;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\GlTransaction;
use App\Models\Hr\Department;
use App\Models\Branch;
use App\Models\BankAccount;
use App\Models\ChartAccount;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use DataTables;
use Carbon\Carbon;
use Vinkla\Hashids\Facades\Hashids;

class ImprestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the imprest management dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        // Get statistics for dashboard cards
        $stats = [
            'pending_requests' => ImprestRequest::forCompany($companyId)->byStatus('pending')->count(),
            'checked_requests' => ImprestRequest::forCompany($companyId)->byStatus('checked')->count(),
            'approved_requests' => ImprestRequest::forCompany($companyId)->byStatus('approved')->count(),
            'disbursed_requests' => ImprestRequest::forCompany($companyId)->byStatus('disbursed')->count(),
            'liquidated_requests' => ImprestRequest::forCompany($companyId)->byStatus('liquidated')->count(),
            'closed_requests' => ImprestRequest::forCompany($companyId)->byStatus('closed')->count(),
            'pending_retirement_requests' => RetirementApproval::where('approver_id', $user->id)
                ->where('status', 'pending')
                ->count(),
            'total_amount_requested' => ImprestRequest::forCompany($companyId)
                ->whereIn('status', ['approved', 'disbursed', 'liquidated', 'closed'])
                ->sum('amount_requested'),
            'total_amount_disbursed' => ImprestDisbursement::whereHas('imprestRequest', function ($q) use ($companyId) {
                $q->forCompany($companyId);
            })->sum('amount_issued'),
        ];

        // Get imprest settings for modal using the active branch context.
        $branchId = session('branch_id') ?? $user->branch_id;

        // If branch is still missing, fall back to the first company branch.
        if (!$branchId) {
            $branchId = Branch::where('company_id', $companyId)->value('id');
        }

        $imprestSettings = ImprestSettings::where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->first();

        // Get chart accounts for the receivables account dropdown
        $chartAccounts = ChartAccount::orderBy('account_code')->get();

        return view('imprest.index', compact('stats', 'imprestSettings', 'chartAccounts'));
    }

    /**
     * Show all imprest requests (CRUD page)
     */
    public function requests(Request $request)
    {
        if ($request->ajax()) {
            return $this->getRequestsDataTable($request);
        }

        $departments = Department::where('company_id', Auth::user()->company_id)->get();

        return view('imprest.requests.index', compact('departments'));
    }

    /**
     * DataTable for imprest requests
     */
    private function getRequestsDataTable(Request $request)
    {
        $query = ImprestRequest::with(['employee', 'department', 'creator'])
            ->forCompany(Auth::user()->company_id);

        // Apply filters
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('department') && $request->department != '') {
            $query->where('department_id', $request->department);
        }

        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addColumn('employee_name', function ($row) {
                return $row->employee->name ?? 'N/A';
            })
            ->addColumn('department_name', function ($row) {
                return $row->department->name ?? 'N/A';
            })
            ->addColumn('date_required', function ($row) {
                return Carbon::parse($row->date_required)
                    ->timezone(config('app.timezone'))
                    ->format('Y-m-d');
            })
            ->addColumn('status_badge', function ($row) {
                return '<span class="' . $row->getStatusBadgeClass() . '">' . $row->getStatusLabel() . '</span>';
            })
            ->addColumn('amount_formatted', function ($row) {
                return number_format($row->amount_requested, 2);
            })
            ->addColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)
                    ->timezone(config('app.timezone'))
                    ->format('Y-m-d H:i');
            })
            ->addColumn('actions', function ($row) {
                $encodedId = Hashids::encode($row->id);
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<a href="' . route('imprest.requests.show', $encodedId) . '" class="btn btn-sm btn-outline-primary" title="View Details">
                    <i class="bx bx-show"></i>
                </a>';

                if ($row->status === 'pending' && (Auth::id() === $row->created_by || Auth::user()->hasRole('Super Admin'))) {
                    $actions .= '<a href="' . route('imprest.requests.edit', $encodedId) . '" class="btn btn-sm btn-outline-warning" title="Edit">
                        <i class="bx bx-edit"></i>
                    </a>';
                }

                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new imprest request
     */
    public function create()
    {
        $user = Auth::user();

        // Get current user's employee information
        $employee = \App\Models\Hr\Employee::where('user_id', Auth::id())
            ->with(['department'])
            ->first();

        // Only show the department that matches the logged-in user's employee department
        $departments = collect();
        if ($employee && $employee->department_id) {
            $departments = Department::where('company_id', $user->company_id)
                ->where('id', $employee->department_id)
                ->get();
        }

        $chartAccounts = ChartAccount::orderBy('account_code')->get();
        $projects = Project::forCompany($user->company_id)
            ->orderBy('name')
            ->get();

        return view('imprest.requests.create', compact('departments', 'chartAccounts', 'employee', 'projects'));
    }

    /**
     * Store a newly created imprest request
     */
    public function store(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:hr_departments,id',
            'project_id' => [
                'nullable',
                Rule::exists('projects', 'id')->where(fn ($query) => $query->where('company_id', Auth::user()->company_id)),
            ],
            'purpose' => 'required|string|max:500',
            'amount_requested' => 'required|numeric|min:0.01',
            'date_required' => 'required|date|after_or_equal:today',
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.chart_account_id' => 'required|exists:chart_accounts,id',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string',
        ], [
            'items.required' => 'At least one imprest item is required.',
            'items.min' => 'At least one imprest item is required.',
            'items.*.chart_account_id.required' => 'Chart account is required for each item.',
            'items.*.chart_account_id.exists' => 'Selected chart account is invalid.',
            'items.*.amount.required' => 'Amount is required for each item.',
            'items.*.amount.min' => 'Amount must be greater than 0.',
        ]);

        // Budget validation and preparation (moved outside transaction)
        $user = Auth::user();

        // Calculate total from items to validate against amount_requested
        $itemsTotal = collect($request->items)->sum('amount');

        if (abs($itemsTotal - $request->amount_requested) > 0.01) {
            return back()->withInput()->withErrors(['error' => 'Total amount must match the sum of all items.']);
        }

        $branchId = session('branch_id') ?? $user->branch_id;

        // If no branch found, use the first branch in the company
        if (!$branchId) {
            $defaultBranch = Branch::where('company_id', $user->company_id)
                ->first();
            $branchId = $defaultBranch?->id;
        }

        if (!$branchId) {
            return back()->withInput()->withErrors(['error' => 'No branch is configured for your company. Please contact your administrator.']);
        }

        // Check budget validation if enabled in imprest settings (MOVED OUTSIDE TRY BLOCK)
        $imprestSettings = ImprestSettings::where('company_id', $user->company_id)
            ->where('branch_id', $branchId)
            ->first();

        if ($imprestSettings && $imprestSettings->check_budget) {
            \Log::info('Budget validation starting', [
                'company_id' => $user->company_id,
                'branch_id' => $branchId,
                'user_id' => $user->id
            ]);

            // Get current year's budget for validation (only need to check once)
            $currentYear = date('Y');
            $budget = Budget::where('company_id', $user->company_id)
                ->where('branch_id', $branchId)
                ->where('year', $currentYear)
                ->first();

            \Log::info('Budget lookup result', [
                'budget_found' => $budget ? true : false,
                'budget_id' => $budget ? $budget->id : null,
                'year' => $currentYear
            ]);

            if (!$budget) {
                \Log::warning('No budget found for validation');
                return back()->withInput()->withErrors(['error' => 'Budget validation is enabled but no budget found for the current year (' . $currentYear . '). Please create a budget first or contact admin to disable budget checking in imprest settings.']);
            }

            foreach ($request->items as $itemIndex => $item) {
                $chartAccountId = $item['chart_account_id'];
                $requestedAmount = (float) $item['amount'];

                \Log::info('Validating item', [
                    'item_index' => $itemIndex,
                    'chart_account_id' => $chartAccountId,
                    'requested_amount' => $requestedAmount
                ]);

                $budgetLine = BudgetLine::where('budget_id', $budget->id)
                    ->where('account_id', $chartAccountId)
                    ->first();

                \Log::info('Budget line lookup', [
                    'budget_line_found' => $budgetLine ? true : false,
                    'budget_line_id' => $budgetLine ? $budgetLine->id : null,
                    'budget_line_amount' => $budgetLine ? $budgetLine->amount : null
                ]);

                if (!$budgetLine) {
                    $chartAccount = ChartAccount::find($chartAccountId);
                    \Log::warning('No budget line found for account', ['account_id' => $chartAccountId]);
                    return back()->withInput()->withErrors(['error' => 'No budget allocation found for account: ' . ($chartAccount->account_name ?? 'Unknown Account') . ' (ID: ' . $chartAccountId . '). Please add this account to your budget.']);
                }

                if ($budgetLine->amount <= 0) {
                    $chartAccount = ChartAccount::find($chartAccountId);
                    \Log::warning('Budget line has zero or negative amount', ['account_id' => $chartAccountId, 'amount' => $budgetLine->amount]);
                    return back()->withInput()->withErrors(['error' => 'Budget allocation for account: ' . ($chartAccount->account_name ?? 'Unknown Account') . ' has zero or negative amount. Please update your budget.']);
                }

                // Calculate used amount from GL transactions (debit transactions only)
                $usedAmount = GlTransaction::where('chart_account_id', $chartAccountId)
                    ->where('branch_id', $branchId)
                    ->whereYear('date', $currentYear)
                    ->where('nature', 'debit')
                    ->sum('amount');

                $usedAmount = (float) $usedAmount;

                // Calculate remaining budget
                $remainingBudget = $budgetLine->amount - $usedAmount;

                \Log::info('Budget calculation', [
                    'budgeted_amount' => $budgetLine->amount,
                    'used_amount' => $usedAmount,
                    'remaining_budget' => $remainingBudget,
                    'requested_amount' => $requestedAmount
                ]);

                if ($requestedAmount > $remainingBudget) {
                    $chartAccount = ChartAccount::find($chartAccountId);
                    \Log::warning('Insufficient budget', [
                        'account_id' => $chartAccountId,
                        'requested' => $requestedAmount,
                        'available' => $remainingBudget
                    ]);
                    return back()->withInput()->withErrors([
                        'error' =>
                        'Insufficient budget for account: ' . ($chartAccount->account_name ?? 'Unknown Account') . '. ' .
                            'Requested: ' . number_format($requestedAmount, 2) . ', ' .
                            'Available: ' . number_format($remainingBudget, 2) . ', ' .
                            'Budgeted: ' . number_format($budgetLine->amount, 2) . ', ' .
                            'Used: ' . number_format($usedAmount, 2)
                    ]);
                }
            }

            \Log::info('Budget validation passed for all items');
        } else {
            \Log::info('Budget validation skipped', [
                'settings_found' => $imprestSettings ? true : false,
                'check_budget' => $imprestSettings ? $imprestSettings->check_budget : null
            ]);
        }

        // Start database transaction after budget validation
        DB::beginTransaction();

        try {
            $imprestRequest = ImprestRequest::create([
                'request_number' => ImprestRequest::generateRequestNumber(),
                'employee_id' => $user->id,
                'department_id' => $request->department_id,
                'project_id' => $request->project_id,
                'company_id' => $user->company_id,
                'branch_id' => $branchId,
                'purpose' => $request->purpose,
                'amount_requested' => $request->amount_requested,
                'date_required' => $request->date_required,
                'description' => $request->description,
                'created_by' => $user->id,
            ]);

            // Create imprest items
            foreach ($request->items as $item) {
                $imprestRequest->imprestItems()->create([
                    'chart_account_id' => $item['chart_account_id'],
                    'notes' => $item['notes'] ?? null,
                    'amount' => $item['amount'],
                    'company_id' => $user->company_id,
                    'branch_id' => $branchId,
                    'created_by' => $user->id,
                ]);
            }

            // Check if multi-level approval is required
            if ($imprestRequest->requiresApproval()) {
                $imprestRequest->createApprovalRequests();
                $approvalMessage = ' Multi-level approval is required before disbursement.';
            } else {
                $approvalMessage = '';
            }

            DB::commit();

            return redirect()->route('imprest.requests.show', $imprestRequest->id)
                ->with('success', 'Imprest request created successfully with ' . count($request->items) . ' items.' . $approvalMessage);
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->withErrors(['error' => 'Failed to create imprest request: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified imprest request
     */
    public function show($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $imprestRequest = ImprestRequest::with([
            'employee',
            'department',
            'creator',
            'checker',
            'approver',
            'rejecter',
            'disburser',
            'imprestItems.chartAccount',
            'payment.bankAccount',
            'payment.paymentItems.chartAccount',
            'disbursement.bankAccount',
            'disbursement.issuer',
            'liquidation.liquidationItems.chartAccount',
            'liquidation.submitter',
            'liquidation.verifier',
            'liquidation.approver',
            'documents.uploader',
            'journalEntries.debitAccount',
            'journalEntries.creditAccount'
        ])->findOrFail($decodedId);

        // Check if user can view this request
        $user = Auth::user();
        if ($imprestRequest->company_id !== $user->company_id && !$user->hasRole('Super Admin')) {
            abort(403, 'Unauthorized');
        }

        // Check user permissions based on multi-level approval settings
        $requiresApproval = $imprestRequest->requiresApproval();
        $currentApprovalLevel = $imprestRequest->getCurrentApprovalLevel();
        $isFullyApproved = $imprestRequest->isFullyApproved();
        $hasRejectedApprovals = $imprestRequest->hasRejectedApprovals();

        // Check if user can approve at current level
        $canUserApprove = false;
        if ($requiresApproval && $currentApprovalLevel) {
            $canUserApprove = $imprestRequest->canUserApproveAtLevel($user, $currentApprovalLevel);
        }

        // Check if user can disburse (only when fully approved or no approval required)
        $canUserDisburse = (!$requiresApproval || $isFullyApproved) && $imprestRequest->canBeDisbursed();

        // Get pending and completed approvals for display
        $pendingApprovals = $imprestRequest->getPendingApprovals();
        $completedApprovals = $imprestRequest->getCompletedApprovals();
        $requiredApprovalLevels = $imprestRequest->getRequiredApprovalLevelDetails();

        return view('imprest.requests.show', compact(
            'imprestRequest',
            'requiresApproval',
            'currentApprovalLevel',
            'isFullyApproved',
            'hasRejectedApprovals',
            'canUserApprove',
            'canUserDisburse',
            'pendingApprovals',
            'completedApprovals',
            'requiredApprovalLevels'
        ));
    }

    /**
     * Show the form for editing the specified imprest request
     */
    public function edit($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $imprestRequest = ImprestRequest::with('imprestItems.chartAccount')->findOrFail($decodedId);

        // Check permissions
        $user = Auth::user();
        if (!($imprestRequest->status === 'pending' && ($user->id === $imprestRequest->created_by || $user->hasRole('Super Admin')))) {
            return redirect()->route('imprest.requests.show', $id)
                ->withErrors(['error' => 'Cannot edit this imprest request.']);
        }

        // Get current user's employee information to filter departments
        $employee = \App\Models\Hr\Employee::where('user_id', $user->id)
            ->with(['department'])
            ->first();

        // Only show the department that matches the logged-in user's employee department
        $departments = collect();
        if ($employee && $employee->department_id) {
            $departments = Department::where('company_id', $user->company_id)
                ->where('id', $employee->department_id)
                ->get();
        }

        $chartAccounts = ChartAccount::orderBy('account_code')->get();
        $projects = Project::forCompany($user->company_id)
            ->orderBy('name')
            ->get();

        return view('imprest.requests.edit', compact('imprestRequest', 'departments', 'chartAccounts', 'projects'));
    }

    /**
     * Update the specified imprest request
     */
    public function update(Request $request, $id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $imprestRequest = ImprestRequest::findOrFail($decodedId);

        // Check permissions
        $user = Auth::user();
        if (!($imprestRequest->status === 'pending' && ($user->id === $imprestRequest->created_by || $user->hasRole('Super Admin')))) {
            return redirect()->route('imprest.requests.show', $id)
                ->withErrors(['error' => 'Cannot edit this imprest request.']);
        }

        $request->validate([
            'department_id' => 'required|exists:hr_departments,id',
            'project_id' => [
                'nullable',
                Rule::exists('projects', 'id')->where(fn ($query) => $query->where('company_id', Auth::user()->company_id)),
            ],
            'purpose' => 'required|string|max:500',
            'amount_requested' => 'required|numeric|min:0.01',
            'date_required' => 'required|date|after_or_equal:today',
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.chart_account_id' => 'required|exists:chart_accounts,id',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string',
        ], [
            'items.required' => 'At least one imprest item is required.',
            'items.min' => 'At least one imprest item is required.',
        ]);

        DB::beginTransaction();

        try {
            // Calculate total from items to validate against amount_requested
            $itemsTotal = collect($request->items)->sum('amount');

            if (abs($itemsTotal - $request->amount_requested) > 0.01) {
                return back()->withInput()->withErrors(['error' => 'Total amount must match the sum of all items.']);
            }

            $imprestRequest->update([
                'department_id' => $request->department_id,
                'project_id' => $request->project_id,
                'purpose' => $request->purpose,
                'amount_requested' => $request->amount_requested,
                'date_required' => $request->date_required,
                'description' => $request->description,
            ]);

            // Delete existing items and create new ones
            $imprestRequest->imprestItems()->delete();

            foreach ($request->items as $item) {
                $imprestRequest->imprestItems()->create([
                    'chart_account_id' => $item['chart_account_id'],
                    'notes' => $item['notes'] ?? null,
                    'amount' => $item['amount'],
                    'company_id' => $user->company_id,
                    'branch_id' => $user->branch_id,
                    'created_by' => $user->id,
                ]);
            }

            DB::commit();

            return redirect()->route('imprest.requests.show', $id)
                ->with('success', 'Imprest request updated successfully with ' . count($request->items) . ' items.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->withErrors(['error' => 'Failed to update imprest request: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified imprest request
     */
    public function destroy($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $imprestRequest = ImprestRequest::findOrFail($decodedId);

        // Check permissions
        $user = Auth::user();
        if (!($imprestRequest->status === 'pending' && ($user->id === $imprestRequest->created_by || $user->hasRole('Super Admin')))) {
            return redirect()->back()->withErrors(['error' => 'Cannot delete this imprest request. Only pending requests created by you can be deleted.']);
        }

        // Additional business logic checks
        if ($imprestRequest->status !== 'pending') {
            return redirect()->back()->withErrors(['error' => 'Cannot delete imprest request. Only pending requests can be deleted.']);
        }

        // Check if there are any related records that prevent deletion
        if ($imprestRequest->disbursement) {
            return redirect()->back()->withErrors(['error' => 'Cannot delete imprest request that has been disbursed.']);
        }

        if ($imprestRequest->liquidation) {
            return redirect()->back()->withErrors(['error' => 'Cannot delete imprest request that has been liquidated.']);
        }

        if ($imprestRequest->checker_id || $imprestRequest->approver_id) {
            return redirect()->back()->withErrors(['error' => 'Cannot delete imprest request that has been processed by checker or approver.']);
        }

        DB::beginTransaction();

        try {
            // Delete related documents
            foreach ($imprestRequest->documents as $document) {
                if (\Storage::exists($document->file_path)) {
                    \Storage::delete($document->file_path);
                }
                $document->delete();
            }

            // Delete imprest items first (due to foreign key constraints)
            $imprestRequest->imprestItems()->delete();

            // Delete the main imprest request
            $imprestRequest->delete();

            DB::commit();

            return redirect()->route('imprest.requests.index')
                ->with('success', 'Imprest request deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Failed to delete imprest request', [
                'imprest_request_id' => $imprestRequest->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withErrors(['error' => 'Failed to delete imprest request: ' . $e->getMessage()]);
        }
    }

    /**
     * Show requests that need manager check
     */
    public function checkedRequests(Request $request)
    {
        if ($request->ajax()) {
            $query = ImprestRequest::with(['employee', 'department', 'creator'])
                ->forCompany(Auth::user()->company_id)
                ->byStatus('pending');

            return DataTables::of($query)
                ->addColumn('employee_name', function ($row) {
                    return $row->employee->name ?? 'N/A';
                })
                ->addColumn('department_name', function ($row) {
                    return $row->department->name ?? 'N/A';
                })
                ->addColumn('amount_formatted', function ($row) {
                    return number_format($row->amount_requested, 2);
                })
                ->addColumn('actions', function ($row) {
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('imprest.requests.show', $row->id) . '" class="btn btn-sm btn-outline-primary" title="Review">
                        <i class="bx bx-search"></i> Review
                    </a>';
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('imprest.checked.index');
    }

    /**
     * Show requests that need finance approval
     */
    public function approvedRequests(Request $request)
    {
        if ($request->ajax()) {
            $query = ImprestRequest::with(['employee', 'department', 'checker'])
                ->forCompany(Auth::user()->company_id)
                ->byStatus('checked');

            return DataTables::of($query)
                ->addColumn('employee_name', function ($row) {
                    return $row->employee->name ?? 'N/A';
                })
                ->addColumn('department_name', function ($row) {
                    return $row->department->name ?? 'N/A';
                })
                ->addColumn('checker_name', function ($row) {
                    return $row->checker->name ?? 'N/A';
                })
                ->addColumn('amount_formatted', function ($row) {
                    return number_format($row->amount_requested, 2);
                })
                ->addColumn('actions', function ($row) {
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('imprest.requests.show', $row->id) . '" class="btn btn-sm btn-outline-primary" title="Review">
                        <i class="bx bx-check-circle"></i> Review
                    </a>';
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('imprest.approved.index');
    }

    /**
     * Show requests that need disbursement
     */
    public function disbursedRequests(Request $request)
    {
        if ($request->ajax()) {
            $query = ImprestRequest::with(['employee', 'department', 'approver'])
                ->forCompany(Auth::user()->company_id)
                ->byStatus('approved');

            return DataTables::of($query)
                ->addColumn('employee_name', function ($row) {
                    return $row->employee->name ?? 'N/A';
                })
                ->addColumn('department_name', function ($row) {
                    return $row->department->name ?? 'N/A';
                })
                ->addColumn('approver_name', function ($row) {
                    return $row->approver->name ?? 'N/A';
                })
                ->addColumn('amount_formatted', function ($row) {
                    return number_format($row->amount_requested, 2);
                })
                ->addColumn('actions', function ($row) {
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('imprest.requests.show', $row->id) . '" class="btn btn-sm btn-outline-success" title="Disburse">
                        <i class="bx bx-money"></i> Disburse
                    </a>';
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('imprest.disbursed.index');
    }

    /**
     * Show closed imprest requests
     */
    public function closedRequests(Request $request)
    {
        if ($request->ajax()) {
            $query = ImprestRequest::with(['employee', 'department', 'disbursement', 'liquidation'])
                ->forCompany(Auth::user()->company_id)
                ->whereIn('status', ['liquidated', 'closed']);

            // Apply filters
            if ($request->has('status') && $request->status != '') {
                $query->where('status', $request->status);
            }

            if ($request->has('department') && $request->department != '') {
                $query->where('department_id', $request->department);
            }

            if ($request->has('date_from') && $request->date_from != '') {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to != '') {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            return DataTables::of($query)
                ->addColumn('employee_name', function ($row) {
                    return $row->employee->name ?? 'N/A';
                })
                ->addColumn('department_name', function ($row) {
                    return $row->department->name ?? 'N/A';
                })
                ->addColumn('amount_formatted', function ($row) {
                    return number_format($row->amount_requested, 2);
                })
                ->addColumn('disbursed_amount', function ($row) {
                    return $row->disbursement ? number_format($row->disbursement->amount_issued, 2) : '0.00';
                })
                ->addColumn('liquidated_amount', function ($row) {
                    return $row->liquidation ? number_format($row->liquidation->total_spent, 2) : '0.00';
                })
                ->addColumn('status_badge', function ($row) {
                    return '<span class="' . $row->getStatusBadgeClass() . '">' . $row->getStatusLabel() . '</span>';
                })
                ->addColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)
                        ->timezone(config('app.timezone'))
                        ->format('Y-m-d H:i');
                })
                ->addColumn('actions', function ($row) {
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('imprest.requests.show', $row->id) . '" class="btn btn-sm btn-outline-primary" title="View Details">
                        <i class="bx bx-show"></i> View
                    </a>';
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        $departments = Department::where('company_id', Auth::user()->company_id)->get();

        return view('imprest.closed.index', compact('departments'));
    }

    /**
     * Generate PDF for imprest request
     */
    public function print($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $imprestRequest = ImprestRequest::with([
            'employee',
            'department',
            'creator',
            'checker',
            'approver',
            'rejecter',
            'disburser',
            'company',
            'branch',
            'imprestItems.chartAccount',
            'approvals.approver',
            'payment.bankAccount',
            'disbursement.bankAccount'
        ])->findOrFail($decodedId);

        // Check if user can view this request
        $user = Auth::user();
        if ($imprestRequest->company_id !== $user->company_id && !$user->hasRole('Super Admin')) {
            abort(403, 'Unauthorized');
        }

        // Get approval information
        $requiresApproval = $imprestRequest->requiresApproval();
        $isFullyApproved = $imprestRequest->isFullyApproved();
        $completedApprovals = $imprestRequest->getCompletedApprovals();
        $requiredApprovalLevels = $imprestRequest->getRequiredApprovalLevelDetails();

        return view('imprest.requests.print', compact(
            'imprestRequest',
            'requiresApproval',
            'isFullyApproved',
            'completedApprovals',
            'requiredApprovalLevels'
        ));
    }

    /**
     * Store or update imprest system settings
     */
    public function storeSettings(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'retirement_enabled' => 'nullable|boolean',
            'imprest_receivables_account' => 'nullable|exists:chart_accounts,id',
            'retirement_period_days' => 'nullable|integer|min:1|max:365',
            'check_budget' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        // If retirement is enabled, receivables account and period are required
        if ($request->retirement_enabled && !$request->imprest_receivables_account) {
            return response()->json([
                'error' => 'Imprest Receivables Account is required when retirement is enabled.'
            ], 422);
        }

        if ($request->retirement_enabled && !$request->retirement_period_days) {
            return response()->json([
                'error' => 'Retirement Period is required when retirement is enabled.'
            ], 422);
        }

        try {
            $settingsData = [
                'retirement_enabled' => $request->has('retirement_enabled') ? true : false,
                'imprest_receivables_account' => $request->imprest_receivables_account,
                'retirement_period_days' => $request->retirement_period_days,
                'check_budget' => $request->has('check_budget') ? true : false,
                'notes' => $request->notes,
                'updated_by' => $user->id,
            ];

            // Get branch_id from session, user, or first branch for company
            $branchId = session('branch_id') ?? $user->branch_id;

            // If still null, get the first branch for the company
            if (!$branchId) {
                $firstBranch = \App\Models\Branch::where('company_id', $user->company_id)->first();
                if ($firstBranch) {
                    $branchId = $firstBranch->id;
                } else {
                    return response()->json([
                        'error' => 'No branch found for your company. Please contact administrator.'
                    ], 422);
                }
            }

            // Check if settings exist
            $existingSettings = ImprestSettings::where('company_id', $user->company_id)
                ->where('branch_id', $branchId)
                ->first();

            if ($existingSettings) {
                $existingSettings->update($settingsData);
                $message = 'Imprest settings updated successfully.';
            } else {
                $settingsData['company_id'] = $user->company_id;
                $settingsData['branch_id'] = $branchId;
                $settingsData['created_by'] = $user->id;

                ImprestSettings::create($settingsData);
                $message = 'Imprest settings created successfully.';
            }

            return response()->json(['success' => $message]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to save settings: ' . $e->getMessage()
            ], 500);
        }
    }
}
