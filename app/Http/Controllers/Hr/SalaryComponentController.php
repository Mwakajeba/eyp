<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\SalaryComponent;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SalaryComponentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $components = SalaryComponent::where('company_id', current_company_id())
                ->orderBy('display_order')
                ->orderBy('component_name');

            return DataTables::of($components)
                ->addIndexColumn()
                ->addColumn('type_badge', function ($component) {
                    return $component->component_type == 'earning'
                        ? '<span class="badge bg-success">Earning</span>'
                        : '<span class="badge bg-danger">Deduction</span>';
                })
                ->addColumn('calculation_info', function ($component) {
                    $info = ucfirst($component->calculation_type);
                    if ($component->calculation_type == 'percentage' && $component->employeeStructures()->count() > 0) {
                        $info .= ' (varies by employee)';
                    }
                    return $info;
                })
                ->addColumn('status_badge', function ($component) {
                    return $component->is_active
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-secondary">Inactive</span>';
                })
                ->addColumn('action', function ($component) {
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('hr.salary-components.show', $component->id) . '" class="btn btn-sm btn-outline-info"><i class="bx bx-show"></i></a>';
                    $actions .= '<a href="' . route('hr.salary-components.edit', $component->id) . '" class="btn btn-sm btn-outline-primary"><i class="bx bx-edit"></i></a>';
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['type_badge', 'status_badge', 'action'])
                ->make(true);
        }

        // Dashboard statistics
        $companyId = current_company_id();

        $stats = [
            'total' => SalaryComponent::where('company_id', $companyId)->count(),
            'earnings' => SalaryComponent::where('company_id', $companyId)
                ->where('component_type', 'earning')
                ->where('is_active', true)
                ->count(),
            'deductions' => SalaryComponent::where('company_id', $companyId)
                ->where('component_type', 'deduction')
                ->where('is_active', true)
                ->count(),
            'active' => SalaryComponent::where('company_id', $companyId)
                ->where('is_active', true)
                ->count(),
        ];

        return view('hr-payroll.salary-components.index', compact('stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('hr-payroll.salary-components.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'component_code' => 'required|string|max:50',
            'component_name' => 'required|string|max:200',
            'component_type' => 'required|in:earning,deduction',
            'description' => 'nullable|string',
            'is_taxable' => 'boolean',
            'is_pensionable' => 'boolean',
            'is_nhif_applicable' => 'boolean',
            'calculation_type' => 'required|in:fixed,formula,percentage',
            'calculation_formula' => 'nullable|string',
            'ceiling_amount' => 'nullable|numeric|min:0',
            'floor_amount' => 'nullable|numeric|min:0',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Check for duplicate code
        $exists = SalaryComponent::where('company_id', current_company_id())
            ->where('component_code', $validated['component_code'])
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['component_code' => 'A salary component with this code already exists.']);
        }

        $validated['company_id'] = current_company_id();
        $validated['is_taxable'] = $request->has('is_taxable');
        $validated['is_pensionable'] = $request->has('is_pensionable');
        $validated['is_nhif_applicable'] = $request->has('is_nhif_applicable');
        $validated['is_active'] = $request->has('is_active');

        SalaryComponent::create($validated);

        return redirect()->route('hr.salary-components.index')
            ->with('success', 'Salary component created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SalaryComponent $salaryComponent)
    {
        $employeeCount = $salaryComponent->employeeStructures()
            ->where('effective_date', '<=', now())
            ->where(function($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->distinct('employee_id')
            ->count('employee_id');

        return view('hr-payroll.salary-components.show', compact('salaryComponent', 'employeeCount'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SalaryComponent $salaryComponent)
    {
        return view('hr-payroll.salary-components.edit', compact('salaryComponent'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SalaryComponent $salaryComponent)
    {
        $validated = $request->validate([
            'component_name' => 'required|string|max:200',
            'component_type' => 'required|in:earning,deduction',
            'description' => 'nullable|string',
            'is_taxable' => 'boolean',
            'is_pensionable' => 'boolean',
            'is_nhif_applicable' => 'boolean',
            'calculation_type' => 'required|in:fixed,formula,percentage',
            'calculation_formula' => 'nullable|string',
            'ceiling_amount' => 'nullable|numeric|min:0',
            'floor_amount' => 'nullable|numeric|min:0',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_taxable'] = $request->has('is_taxable');
        $validated['is_pensionable'] = $request->has('is_pensionable');
        $validated['is_nhif_applicable'] = $request->has('is_nhif_applicable');
        $validated['is_active'] = $request->has('is_active');

        $salaryComponent->update($validated);

        return redirect()->route('hr.salary-components.index')
            ->with('success', 'Salary component updated successfully.');
    }
}

