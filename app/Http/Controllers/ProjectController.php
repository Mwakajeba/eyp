<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

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

        return view('projects.index', compact('projects'));
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
            ->with(['donors'])
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



