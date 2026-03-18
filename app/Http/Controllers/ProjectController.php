<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * Display the Project Management dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('projects.index');
    }

    public function donorProjectsIndex()
    {
        $companyId = (int) Auth::user()->company_id;

        $donorProjects = Project::forCompany($companyId)
            ->where('type', 'DONOR')
            ->with(['donors'])
            ->latest()
            ->get();

        return view('projects.donor-projects.index', compact('donorProjects'));
    }

    public function donorProjectsCreate()
    {
        return view('projects.donor-projects.create');
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

    public function storeDonorProject(Request $request): RedirectResponse
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
            'type' => 'DONOR',
            'status' => $validated['status'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'currency_code' => strtoupper($validated['currency_code']),
            'budget_total' => $validated['budget_total'],
            'description' => $validated['description'] ?? null,
            'created_by' => $user->id,
        ]);

        return redirect()->route('projects.donor-projects.index')->with('success', 'Donor project created successfully.');
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

        return redirect()->route('projects.donor-projects.index')->with('success', 'Donor assigned to project successfully.');
    }
}



