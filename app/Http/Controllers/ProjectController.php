<?php

namespace App\Http\Controllers;

use App\Models\ChartAccount;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectActivity;
use App\Models\ProjectSubActivity;
use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class ProjectController extends Controller
{
    /**
     * Display the Project Management dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $companyId = (int) Auth::user()->company_id;

        $projects = Project::forCompany($companyId)
            ->orderBy('project_code')
            ->get(['id', 'project_code', 'name', 'type']);

        $activityCount = ProjectActivity::forCompany($companyId)->count();

        return view('projects.index', compact('projects', 'activityCount'));
    }

    public function activityIndex(Request $request)
    {
        $companyId = (int) Auth::user()->company_id;

        if ($request->ajax()) {
            $projectActivities = ProjectActivity::query()
                ->where('project_activities.company_id', $companyId)
                ->with([
                    'project:id,project_code,name',
                    'creator:id,name',
                    'subActivities:id,project_activity_id,amount',
                ])
                ->select('project_activities.*');

            return DataTables::of($projectActivities)
                ->addIndexColumn()
                ->addColumn('project_display', function ($activity) {
                    $projectCode = e($activity->project->project_code ?? '-');
                    $projectName = e($activity->project->name ?? '-');

                    return '<div class="fw-semibold">' . $projectCode . '</div><small class="text-muted">' . $projectName . '</small>';
                })
                ->addColumn('budget_amount_formatted', fn ($activity) => $activity->budget_amount !== null ? number_format((float) $activity->budget_amount, 2) : '—')
                ->addColumn('sub_activities_total', fn ($activity) => number_format((float) $activity->subActivities->sum('amount'), 2))
                ->addColumn('created_by_name', fn ($activity) => e($activity->creator->name ?? 'System'))
                ->addColumn('created_at_formatted', fn ($activity) => optional($activity->created_at)->format('d M Y'))
                ->addColumn('actions', function ($activity) {
                    $editUrl = route('projects.activities.edit', $activity->id);
                    $deleteUrl = route('projects.activities.destroy', $activity->id);
                    $subActivitiesUrl = route('projects.activities.sub-activities.index', $activity->id);

                    return '<div class="d-flex gap-1">'
                        . '<a href="' . $subActivitiesUrl . '" class="btn btn-sm btn-success" title="Manage Sub Activities"><i class="bx bx-plus"></i></a>'
                        . '<a href="' . $editUrl . '" class="btn btn-sm btn-warning"><i class="bx bx-edit"></i></a>'
                        . '<form method="POST" action="' . $deleteUrl . '" onsubmit="return confirm(\'Delete this project activity?\');">'
                        . csrf_field()
                        . method_field('DELETE')
                        . '<button type="submit" class="btn btn-sm btn-danger"><i class="bx bx-trash"></i></button>'
                        . '</form>'
                        . '</div>';
                })
                ->rawColumns(['project_display', 'actions'])
                ->make(true);
        }

        return view('projects.activities.index');
    }

    public function activityCreate()
    {
        $companyId = (int) Auth::user()->company_id;

        $projects = Project::forCompany($companyId)
            ->orderBy('project_code')
            ->get(['id', 'project_code', 'name', 'type']);

        return view('projects.activities.create', compact('projects'));
    }

    public function projectReceiptsExportPdf(Request $request)
    {
        [$project, $dateFrom, $dateTo] = $this->validateProjectReportFilters($request);
        $receipts = $this->getProjectReceipts($project->id, $dateFrom, $dateTo);

        $pdf = Pdf::loadView('projects.reports.project-cashflow-pdf', [
            'title' => 'Project Receipts Report',
            'reportType' => 'Receipts',
            'project' => $project,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'rows' => $receipts,
            'totalAmount' => (float) $receipts->sum('amount'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download(sprintf(
            'project-receipts-%s-%s-to-%s.pdf',
            $project->project_code ?? $project->id,
            $dateFrom,
            $dateTo
        ));
    }

    public function projectReceiptsExportExcel(Request $request)
    {
        [$project, $dateFrom, $dateTo] = $this->validateProjectReportFilters($request);
        $receipts = $this->getProjectReceipts($project->id, $dateFrom, $dateTo);

        $data = $receipts->map(function ($receipt) {
            return [
                'Reference' => $receipt->reference,
                'Date' => optional($receipt->date)->format('Y-m-d'),
                'Payee' => $receipt->payee_name ?: ($receipt->customer->name ?? '-'),
                'Reference Type' => $receipt->reference_type,
                'Reference Number' => $receipt->reference_number,
                'Currency' => $receipt->currency,
                'Amount' => (float) $receipt->amount,
                'Description' => $receipt->description,
            ];
        })->toArray();

        return Excel::download(
            new \App\Exports\FleetReportExport($data, 'Project Receipts Report', array_keys($data[0] ?? [
                'Reference' => '',
                'Date' => '',
                'Payee' => '',
                'Reference Type' => '',
                'Reference Number' => '',
                'Currency' => '',
                'Amount' => '',
                'Description' => '',
            ])),
            sprintf('project-receipts-%s-%s-to-%s.xlsx', $project->project_code ?? $project->id, $dateFrom, $dateTo)
        );
    }

    public function projectPaymentsExportPdf(Request $request)
    {
        [$project, $dateFrom, $dateTo] = $this->validateProjectReportFilters($request);
        $payments = $this->getProjectPayments($project->id, $dateFrom, $dateTo);

        $pdf = Pdf::loadView('projects.reports.project-cashflow-pdf', [
            'title' => 'Project Payments Report',
            'reportType' => 'Payments',
            'project' => $project,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'rows' => $payments,
            'totalAmount' => (float) $payments->sum('amount'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download(sprintf(
            'project-payments-%s-%s-to-%s.pdf',
            $project->project_code ?? $project->id,
            $dateFrom,
            $dateTo
        ));
    }

    public function projectPaymentsExportExcel(Request $request)
    {
        [$project, $dateFrom, $dateTo] = $this->validateProjectReportFilters($request);
        $payments = $this->getProjectPayments($project->id, $dateFrom, $dateTo);

        $data = $payments->map(function ($payment) {
            return [
                'Reference' => $payment->reference,
                'Date' => optional($payment->date)->format('Y-m-d'),
                'Payee' => $payment->payee_name
                    ?: ($payment->supplier->name ?? ($payment->customer->name ?? '-')),
                'Reference Type' => $payment->reference_type,
                'Reference Number' => $payment->reference_number,
                'Currency' => $payment->currency,
                'Amount' => (float) $payment->amount,
                'Description' => $payment->description,
            ];
        })->toArray();

        return Excel::download(
            new \App\Exports\FleetReportExport($data, 'Project Payments Report', array_keys($data[0] ?? [
                'Reference' => '',
                'Date' => '',
                'Payee' => '',
                'Reference Type' => '',
                'Reference Number' => '',
                'Currency' => '',
                'Amount' => '',
                'Description' => '',
            ])),
            sprintf('project-payments-%s-%s-to-%s.xlsx', $project->project_code ?? $project->id, $dateFrom, $dateTo)
        );
    }

    public function projectIndex()
    {
        $companyId = (int) Auth::user()->company_id;

        $projects = Project::forCompany($companyId)
            ->with([
                'donors',
                'activities' => function ($query) {
                    $query->with(['subActivities:id,project_activity_id,sub_activity_name,amount'])
                        ->orderBy('activity_code');
                },
            ])
            ->latest()
            ->get();

        return view('projects.donor-projects.index', compact('projects'));
    }

    public function projectCreate()
    {
        return view('projects.donor-projects.create');
    }

    public function projectEdit(Project $project)
    {
        $companyId = (int) Auth::user()->company_id;

        abort_if($project->company_id !== $companyId, 403, 'Unauthorized action.');

        return view('projects.donor-projects.edit', compact('project'));
    }

    public function donorAssignmentsCreate()
    {
        $companyId = (int) Auth::user()->company_id;

        $donorProjects = Project::forCompany($companyId)
            ->where('type', 'DONOR')
            ->whereIn('status', ['draft', 'active', 'on_hold'])
            ->orderBy('project_code')
            ->get();

        $donorCustomers = Customer::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('projects.donor-assignments.create', compact('donorProjects', 'donorCustomers'));
    }

    public function projectStore(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $companyId = (int) $user->company_id;

        $validated = $request->validate([
            'project_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('projects')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['INTERNAL', 'DONOR', 'EXTERNAL'])],
            'status' => ['required', Rule::in(['draft', 'active', 'on_hold', 'closed'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'currency_code' => ['required', 'string', 'max:10'],
            'budget_total' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        Project::create([
            'company_id' => $companyId,
            'branch_id' => session('branch_id') ?? $user->branch_id,
            'project_code' => $validated['project_code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'status' => $validated['status'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'currency_code' => strtoupper($validated['currency_code']),
            'budget_total' => $validated['budget_total'],
            'description' => $validated['description'] ?? null,
            'created_by' => $user->id,
        ]);

        return redirect()->route('projects.project.index')->with('success', 'Project created successfully.');
    }

    public function projectUpdate(Request $request, Project $project): RedirectResponse
    {
        $user = Auth::user();
        $companyId = (int) $user->company_id;

        abort_if($project->company_id !== $companyId, 403, 'Unauthorized action.');

        $validated = $request->validate([
            'project_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('projects')->where(fn ($q) => $q->where('company_id', $companyId))->ignore($project->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['INTERNAL', 'DONOR', 'EXTERNAL'])],
            'status' => ['required', Rule::in(['draft', 'active', 'on_hold', 'closed'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'currency_code' => ['required', 'string', 'max:10'],
            'budget_total' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $project->update([
            'project_code' => $validated['project_code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'status' => $validated['status'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'currency_code' => strtoupper($validated['currency_code']),
            'budget_total' => $validated['budget_total'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('projects.project.index')->with('success', 'Project updated successfully.');
    }

    public function projectDestroy(Project $project): RedirectResponse
    {
        $companyId = (int) Auth::user()->company_id;

        abort_if($project->company_id !== $companyId, 403, 'Unauthorized action.');

        $project->delete();

        return redirect()->route('projects.project.index')->with('success', 'Project deleted successfully.');
    }

    public function activityStore(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $companyId = (int) $user->company_id;

        $request->merge([
            'budget_amount' => $request->filled('budget_amount')
                ? $this->normalizeDecimal($request->input('budget_amount'))
                : null,
        ]);

        $validated = $request->validate([
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'activity_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('project_activities')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->where('project_id', $request->input('project_id'))),
            ],
            'description' => ['required', 'string', 'max:5000'],
            'budget_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        ProjectActivity::create([
            'company_id' => $companyId,
            'branch_id' => session('branch_id') ?? $user->branch_id,
            'project_id' => $validated['project_id'],
            'activity_code' => strtoupper(trim($validated['activity_code'])),
            'description' => $validated['description'],
            'budget_amount' => $validated['budget_amount'],
            'created_by' => $user->id,
        ]);

        return redirect()->route('projects.activities.index')->with('success', 'Project activity created successfully.');
    }

    public function activityEdit(ProjectActivity $activity)
    {
        $companyId = (int) Auth::user()->company_id;

        abort_if($activity->company_id !== $companyId, 403, 'Unauthorized action.');

        $projects = Project::forCompany($companyId)
            ->orderBy('project_code')
            ->get(['id', 'project_code', 'name', 'type']);

        return view('projects.activities.edit', compact('activity', 'projects'));
    }

    public function activityUpdate(Request $request, ProjectActivity $activity): RedirectResponse
    {
        $companyId = (int) Auth::user()->company_id;

        abort_if($activity->company_id !== $companyId, 403, 'Unauthorized action.');

        $request->merge([
            'budget_amount' => $request->filled('budget_amount')
                ? $this->normalizeDecimal($request->input('budget_amount'))
                : null,
        ]);

        $validated = $request->validate([
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'activity_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('project_activities')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->where('project_id', $request->input('project_id')))->ignore($activity->id),
            ],
            'description' => ['required', 'string', 'max:5000'],
            'budget_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $activity->update([
            'project_id' => $validated['project_id'],
            'activity_code' => strtoupper(trim($validated['activity_code'])),
            'description' => $validated['description'],
            'budget_amount' => $validated['budget_amount'],
        ]);

        return redirect()->route('projects.activities.index')->with('success', 'Project activity updated successfully.');
    }

    public function activityDestroy(ProjectActivity $activity): RedirectResponse
    {
        $this->authorizeProjectActivity($activity);

        $activity->delete();

        return redirect()->route('projects.activities.index')->with('success', 'Project activity deleted successfully.');
    }

    public function subActivityIndex(ProjectActivity $activity)
    {
        $companyId = (int) Auth::user()->company_id;

        $this->authorizeProjectActivity($activity);

        $activity->load(['project:id,project_code,name']);

        $chartAccounts = $this->companyChartAccounts($companyId)->get(['chart_accounts.id', 'chart_accounts.account_code', 'chart_accounts.account_name']);

        $subActivities = $activity->subActivities()
            ->with(['chartAccount:id,account_code,account_name', 'creator:id,name'])
            ->latest()
            ->get();

        $totalAmount = (float) $subActivities->sum('amount');

        return view('projects.activities.sub-activities.index', compact('activity', 'chartAccounts', 'subActivities', 'totalAmount'));
    }

    public function subActivityStore(Request $request, ProjectActivity $activity): RedirectResponse
    {
        $user = Auth::user();
        $companyId = (int) $user->company_id;

        $this->authorizeProjectActivity($activity);

        $validated = $this->validateSubActivityRequest($request, $companyId);

        $activity->subActivities()->create([
            'company_id' => $companyId,
            'branch_id' => session('branch_id') ?? $user->branch_id,
            'sub_activity_name' => $validated['sub_activity_name'],
            'chart_account_id' => $validated['chart_account_id'],
            'amount' => $validated['amount'],
            'created_by' => $user->id,
        ]);

        return redirect()
            ->route('projects.activities.sub-activities.index', $activity->id)
            ->with('success', 'Sub activity created successfully.');
    }

    public function subActivityUpdate(Request $request, ProjectActivity $activity, ProjectSubActivity $subActivity): RedirectResponse
    {
        $companyId = (int) Auth::user()->company_id;

        $this->authorizeProjectSubActivity($activity, $subActivity);

        $validated = $this->validateSubActivityRequest($request, $companyId);

        $subActivity->update([
            'sub_activity_name' => $validated['sub_activity_name'],
            'chart_account_id' => $validated['chart_account_id'],
            'amount' => $validated['amount'],
        ]);

        return redirect()
            ->route('projects.activities.sub-activities.index', $activity->id)
            ->with('success', 'Sub activity updated successfully.');
    }

    public function subActivityDestroy(ProjectActivity $activity, ProjectSubActivity $subActivity): RedirectResponse
    {
        $this->authorizeProjectSubActivity($activity, $subActivity);

        $subActivity->delete();

        return redirect()
            ->route('projects.activities.sub-activities.index', $activity->id)
            ->with('success', 'Sub activity deleted successfully.');
    }

    public function assignDonor(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $companyId = (int) $user->company_id;

        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
        ]);

        $project = Project::forCompany($companyId)
            ->where('type', 'DONOR')
            ->findOrFail($validated['project_id']);

        $customer = Customer::where('company_id', $companyId)->findOrFail($validated['customer_id']);

        $project->donors()->syncWithoutDetaching([
            $customer->id => [
                'company_id' => $companyId,
                'assigned_by' => $user->id,
            ],
        ]);

        return redirect()->route('projects.project.index')->with('success', 'Donor assigned to project successfully.');
    }

    private function validateProjectReportFilters(Request $request): array
    {
        $companyId = (int) Auth::user()->company_id;

        $validated = $request->validate([
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        $project = Project::forCompany($companyId)->findOrFail((int) $validated['project_id']);

        return [$project, $validated['date_from'], $validated['date_to']];
    }

    private function normalizeDecimal($value): float
    {
        $normalized = preg_replace('/[^0-9.]/', '', (string) $value);

        return $normalized === '' ? 0.0 : (float) $normalized;
    }

    private function authorizeProjectActivity(ProjectActivity $activity): void
    {
        $companyId = (int) Auth::user()->company_id;

        abort_if($activity->company_id !== $companyId, 403, 'Unauthorized action.');
    }

    private function authorizeProjectSubActivity(ProjectActivity $activity, ProjectSubActivity $subActivity): void
    {
        $this->authorizeProjectActivity($activity);

        abort_if(
            $subActivity->company_id !== (int) Auth::user()->company_id || $subActivity->project_activity_id !== $activity->id,
            403,
            'Unauthorized action.'
        );
    }

    private function validateSubActivityRequest(Request $request, int $companyId): array
    {
        $request->merge([
            'amount' => $this->normalizeDecimal($request->input('amount')),
        ]);

        return $request->validate([
            'sub_activity_name' => ['required', 'string', 'max:255'],
            'chart_account_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($companyId) {
                    $exists = $this->companyChartAccounts($companyId)
                        ->where('chart_accounts.id', $value)
                        ->exists();

                    if (! $exists) {
                        $fail('The selected chart account is invalid.');
                    }
                },
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'edit_sub_activity_id' => ['nullable', 'integer'],
        ]);
    }

    private function companyChartAccounts(int $companyId)
    {
        return ChartAccount::query()
            ->whereHas('accountClassGroup', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->orderBy('chart_accounts.account_code');
    }

    private function getProjectReceipts(int $projectId, string $dateFrom, string $dateTo)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;

        return Receipt::with(['customer'])
            ->where('project_id', $projectId)
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->whereHas('branch', fn ($query) => $query->where('company_id', $user->company_id))
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('date')
            ->orderBy('reference')
            ->get();
    }

    private function getProjectPayments(int $projectId, string $dateFrom, string $dateTo)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;

        return Payment::with(['supplier', 'customer'])
            ->where('project_id', $projectId)
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->whereHas('branch', fn ($query) => $query->where('company_id', $user->company_id))
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('date')
            ->orderBy('reference')
            ->get();
    }
}



