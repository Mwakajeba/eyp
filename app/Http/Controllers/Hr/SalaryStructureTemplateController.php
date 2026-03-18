<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\SalaryStructureTemplate;
use App\Models\Hr\SalaryStructureTemplateComponent;
use App\Models\Hr\SalaryComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SalaryStructureTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $companyId = current_company_id();

            $templates = SalaryStructureTemplate::where('company_id', $companyId)
                ->withCount('templateComponents')
                ->orderBy('template_name');

            return DataTables::of($templates)
                ->addIndexColumn()
                ->addColumn('template_name', function ($template) {
                    return $template->template_name;
                })
                ->addColumn('template_code', function ($template) {
                    return '<code>' . $template->template_code . '</code>';
                })
                ->addColumn('components_count', function ($template) {
                    $count = $template->template_components_count ?? 0;
                    return '<span class="badge bg-info">' . $count . ' Component(s)</span>';
                })
                ->addColumn('status', function ($template) {
                    if ($template->is_active) {
                        return '<span class="badge bg-success">Active</span>';
                    }
                    return '<span class="badge bg-secondary">Inactive</span>';
                })
                ->addColumn('action', function ($template) {
                    $viewRoute = route('hr.salary-structure-templates.show', $template->id);
                    $editRoute = route('hr.salary-structure-templates.edit', $template->id);
                    $deleteRoute = route('hr.salary-structure-templates.destroy', $template->id);
                    
                    $viewBtn = '<a href="' . $viewRoute . '" class="btn btn-sm btn-outline-info me-1" title="View"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . $editRoute . '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteTemplate(' . $template->id . ')" title="Delete"><i class="bx bx-trash"></i></button>';
                    
                    return $viewBtn . $editBtn . $deleteBtn;
                })
                ->rawColumns(['template_code', 'components_count', 'status', 'action'])
                ->make(true);
        }

        $stats = [
            'total_templates' => SalaryStructureTemplate::where('company_id', current_company_id())->count(),
            'active_templates' => SalaryStructureTemplate::where('company_id', current_company_id())
                ->where('is_active', true)
                ->count(),
            'total_components' => SalaryComponent::where('company_id', current_company_id())
                ->where('is_active', true)
                ->count(),
        ];

        return view('hr-payroll.salary-structure-templates.index', compact('stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $components = SalaryComponent::where('company_id', current_company_id())
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('component_name')
            ->get();

        return view('hr-payroll.salary-structure-templates.create', compact('components'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'template_name' => 'required|string|max:255',
            'template_code' => 'required|string|max:50|unique:hr_salary_structure_templates,template_code,NULL,id,company_id,' . current_company_id(),
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'components' => 'required|array|min:1',
            'components.*.component_id' => 'required|exists:hr_salary_components,id',
            'components.*.amount' => 'nullable|numeric|min:0',
            'components.*.percentage' => 'nullable|numeric|min:0|max:100',
            'components.*.notes' => 'nullable|string|max:500',
            'components.*.display_order' => 'nullable|integer|min:0',
        ], [
            'template_name.required' => 'Template name is required.',
            'template_code.required' => 'Template code is required.',
            'template_code.unique' => 'Template code already exists.',
            'components.required' => 'Please add at least one component.',
            'components.min' => 'Please add at least one component.',
        ]);

        DB::beginTransaction();
        try {
            $template = SalaryStructureTemplate::create([
                'company_id' => current_company_id(),
                'template_name' => $validated['template_name'],
                'template_code' => strtoupper($validated['template_code']),
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $order = 0;
            foreach ($validated['components'] as $comp) {
                $component = SalaryComponent::where('company_id', current_company_id())
                    ->findOrFail($comp['component_id']);

                // Validate calculation type matches input
                if ($component->calculation_type === 'fixed' && empty($comp['amount'])) {
                    throw new \Exception("Component '{$component->component_name}' requires an amount (fixed type).");
                }
                if ($component->calculation_type === 'percentage' && empty($comp['percentage'])) {
                    throw new \Exception("Component '{$component->component_name}' requires a percentage (percentage type).");
                }

                SalaryStructureTemplateComponent::create([
                    'template_id' => $template->id,
                    'component_id' => $comp['component_id'],
                    'amount' => $comp['amount'] ?? null,
                    'percentage' => $comp['percentage'] ?? null,
                    'notes' => $comp['notes'] ?? null,
                    'display_order' => $comp['display_order'] ?? $order++,
                ]);
            }

            DB::commit();

            return redirect()->route('hr.salary-structure-templates.index')
                ->with('success', 'Salary structure template created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $template = SalaryStructureTemplate::where('company_id', current_company_id())
            ->with(['templateComponents.component'])
            ->findOrFail($id);

        $earnings = $template->templateComponents->filter(function ($tc) {
            return $tc->component->component_type === 'earning';
        });
        $deductions = $template->templateComponents->filter(function ($tc) {
            return $tc->component->component_type === 'deduction';
        });

        return view('hr-payroll.salary-structure-templates.show', compact('template', 'earnings', 'deductions'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $template = SalaryStructureTemplate::where('company_id', current_company_id())
            ->with(['templateComponents.component'])
            ->findOrFail($id);

        $components = SalaryComponent::where('company_id', current_company_id())
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('component_name')
            ->get();

        return view('hr-payroll.salary-structure-templates.edit', compact('template', 'components'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $template = SalaryStructureTemplate::where('company_id', current_company_id())
            ->findOrFail($id);

        $validated = $request->validate([
            'template_name' => 'required|string|max:255',
            'template_code' => 'required|string|max:50|unique:hr_salary_structure_templates,template_code,' . $id . ',id,company_id,' . current_company_id(),
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'components' => 'required|array|min:1',
            'components.*.component_id' => 'required|exists:hr_salary_components,id',
            'components.*.amount' => 'nullable|numeric|min:0',
            'components.*.percentage' => 'nullable|numeric|min:0|max:100',
            'components.*.notes' => 'nullable|string|max:500',
            'components.*.display_order' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $template->update([
                'template_name' => $validated['template_name'],
                'template_code' => strtoupper($validated['template_code']),
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Delete existing components
            SalaryStructureTemplateComponent::where('template_id', $template->id)->delete();

            // Create new components
            $order = 0;
            foreach ($validated['components'] as $comp) {
                $component = SalaryComponent::where('company_id', current_company_id())
                    ->findOrFail($comp['component_id']);

                if ($component->calculation_type === 'fixed' && empty($comp['amount'])) {
                    throw new \Exception("Component '{$component->component_name}' requires an amount.");
                }
                if ($component->calculation_type === 'percentage' && empty($comp['percentage'])) {
                    throw new \Exception("Component '{$component->component_name}' requires a percentage.");
                }

                SalaryStructureTemplateComponent::create([
                    'template_id' => $template->id,
                    'component_id' => $comp['component_id'],
                    'amount' => $comp['amount'] ?? null,
                    'percentage' => $comp['percentage'] ?? null,
                    'notes' => $comp['notes'] ?? null,
                    'display_order' => $comp['display_order'] ?? $order++,
                ]);
            }

            DB::commit();

            return redirect()->route('hr.salary-structure-templates.index')
                ->with('success', 'Salary structure template updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $template = SalaryStructureTemplate::where('company_id', current_company_id())
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            // Delete components first (cascade should handle this, but being explicit)
            SalaryStructureTemplateComponent::where('template_id', $template->id)->delete();
            
            $template->delete();

            DB::commit();

            return redirect()->route('hr.salary-structure-templates.index')
                ->with('success', 'Salary structure template deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete template: ' . $e->getMessage()]);
        }
    }
}
