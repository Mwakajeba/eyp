<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\SalaryComponent;
use App\Models\Hr\EmployeeSalaryStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class EmployeeSalaryStructureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $companyId = current_company_id();

            $employees = Employee::where('company_id', $companyId)
                ->withCount(['salaryStructures' => function ($q) {
                    $q->where('effective_date', '<=', now())
                      ->where(function ($query) {
                          $query->whereNull('end_date')
                                ->orWhere('end_date', '>=', now());
                      });
                }])
                ->orderBy('first_name')
                ->orderBy('last_name');

            return DataTables::of($employees)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($employee) {
                    return $employee->full_name;
                })
                ->addColumn('employee_number', function ($employee) {
                    return $employee->employee_number ?? '-';
                })
                ->addColumn('basic_salary', function ($employee) {
                    return number_format($employee->basic_salary ?? 0, 2);
                })
                ->addColumn('structure_status', function ($employee) {
                    $count = $employee->salary_structures_count ?? 0;
                    if ($count > 0) {
                        return '<span class="badge bg-success">' . $count . ' Component(s)</span>';
                    }
                    return '<span class="badge bg-warning">No Structure</span>';
                })
                ->addColumn('action', function ($employee) {
                    $viewRoute = route('hr.employee-salary-structure.show', $employee->id);
                    $editRoute = route('hr.employee-salary-structure.create', ['employee_id' => $employee->id]);
                    $viewBtn = '<a href="' . $viewRoute . '" class="btn btn-sm btn-outline-info me-1" '
                        . 'title="View Structure"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . $editRoute . '" class="btn btn-sm btn-outline-primary me-1" '
                        . 'title="Manage Structure"><i class="bx bx-edit"></i></a>';
                    return $viewBtn . $editBtn;
                })
                ->rawColumns(['structure_status', 'action'])
                ->make(true);
        }

        // Dashboard statistics
        $companyId = current_company_id();
        $today = now();

        $stats = [
            'total_employees' => Employee::where('company_id', $companyId)->count(),
            'with_structure' => Employee::where('company_id', $companyId)
                ->whereHas('salaryStructures', function ($q) use ($today) {
                    $q->where('effective_date', '<=', $today)
                      ->where(function ($query) use ($today) {
                          $query->whereNull('end_date')
                                ->orWhere('end_date', '>=', $today);
                      });
                })
                ->count(),
            'without_structure' => Employee::where('company_id', $companyId)
                ->whereDoesntHave('salaryStructures', function ($q) use ($today) {
                    $q->where('effective_date', '<=', $today)
                      ->where(function ($query) use ($today) {
                          $query->whereNull('end_date')
                                ->orWhere('end_date', '>=', $today);
                      });
                })
                ->count(),
            'total_components' => SalaryComponent::where('company_id', $companyId)
                ->where('is_active', true)
                ->count(),
        ];

        return view('hr-payroll.employee-salary-structure.index', compact('stats'));
    }

    /**
     * Show bulk assignment form
     */
    public function bulkAssignForm()
    {
        $employees = Employee::where('company_id', current_company_id())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $components = SalaryComponent::where('company_id', current_company_id())
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('component_name')
            ->get();

        return view('hr-payroll.employee-salary-structure.bulk-assign', compact('employees', 'components'));
    }

    /**
     * Show template application form
     */
    public function applyTemplateForm()
    {
        $employees = Employee::where('company_id', current_company_id())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $templates = \App\Models\Hr\SalaryStructureTemplate::where('company_id', current_company_id())
            ->where('is_active', true)
            ->orderBy('template_name')
            ->get();

        return view('hr-payroll.employee-salary-structure.apply-template', compact('employees', 'templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $employeeId = $request->employee_id;
        $employee = null;
        $existingStructures = collect();

        if ($employeeId) {
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($employeeId);

            // Get existing active structures
            $existingStructures = EmployeeSalaryStructure::where('employee_id', $employee->id)
                ->where('effective_date', '<=', now())
                ->where(function ($q) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', now());
                })
                ->with('component')
                ->get();
        }

        // Get all active salary components
        $components = SalaryComponent::where('company_id', current_company_id())
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('component_name')
            ->get();

        // Get all employees for selection
        $employees = Employee::where('company_id', current_company_id())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('hr-payroll.employee-salary-structure.create', compact(
            'employee',
            'employees',
            'components',
            'existingStructures'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'components' => 'required|array|min:1',
            'components.*.component_id' => 'required|exists:hr_salary_components,id',
            'components.*.amount' => 'nullable|numeric|min:0',
            'components.*.percentage' => 'nullable|numeric|min:0|max:100',
            'components.*.effective_date' => 'required|date',
            'components.*.end_date' => 'nullable|date|after:components.*.effective_date',
            'components.*.notes' => 'nullable|string|max:500',
        ], [
            'employee_id.required' => 'Please select an employee.',
            'employee_id.exists' => 'Selected employee does not exist.',
            'components.required' => 'Please add at least one salary component.',
            'components.min' => 'Please add at least one salary component.',
            'components.*.component_id.required' => 'Component selection is required.',
            'components.*.component_id.exists' => 'Selected component does not exist.',
            'components.*.amount.numeric' => 'Amount must be a valid number.',
            'components.*.amount.min' => 'Amount cannot be negative.',
            'components.*.percentage.numeric' => 'Percentage must be a valid number.',
            'components.*.percentage.min' => 'Percentage cannot be negative.',
            'components.*.percentage.max' => 'Percentage cannot exceed 100%.',
            'components.*.effective_date.required' => 'Effective date is required.',
            'components.*.effective_date.date' => 'Effective date must be a valid date.',
            'components.*.end_date.date' => 'End date must be a valid date.',
            'components.*.end_date.after' => 'End date must be after effective date.',
        ]);

        $employee = Employee::where('company_id', current_company_id())
            ->with('position.grade')
            ->findOrFail($validated['employee_id']);

        // Validate that at least one BASIC_SALARY component exists
        $hasBasicSalary = false;
        $totalBasicSalary = 0;
        foreach ($validated['components'] as $comp) {
            $component = SalaryComponent::find($comp['component_id']);
            if ($component && str_contains(strtolower($component->component_code), 'basic')) {
                $hasBasicSalary = true;
                // Calculate the basic salary amount
                if ($component->calculation_type === 'fixed' && !empty($comp['amount'])) {
                    $totalBasicSalary += $comp['amount'];
                } elseif ($component->calculation_type === 'percentage' && !empty($comp['percentage'])) {
                    // For percentage, we'd need the base amount, but for now just track that we have a basic salary component
                    $totalBasicSalary += $comp['amount'] ?? 0;
                }
                break;
            }
        }

        if (!$hasBasicSalary) {
            $errorMsg = 'At least one Basic Salary component must be included in the structure.';
            return redirect()->back()
                ->withInput()
                ->withErrors(['components' => $errorMsg]);
        }
        
        // Validate total basic salary against position's grade if employee has a position with grade
        if ($employee->position && $employee->position->grade && $totalBasicSalary > 0) {
            if (!$employee->position->grade->isSalaryInRange($totalBasicSalary)) {
                $min = $employee->position->grade->minimum_salary ? number_format($employee->position->grade->minimum_salary, 2) : 'N/A';
                $max = $employee->position->grade->maximum_salary ? number_format($employee->position->grade->maximum_salary, 2) : 'N/A';
                return redirect()->back()
                    ->withInput()
                    ->withErrors([
                        'components' => "Total basic salary ({$totalBasicSalary}) is outside the acceptable range for position's grade ({$min} - {$max})."
                    ]);
            }
        }

        DB::beginTransaction();
        try {
            // Get the earliest effective date from new components
            $newEffectiveDate = Carbon::parse(min(array_column($validated['components'], 'effective_date')));
            $endDate = $newEffectiveDate->copy()->subDay();

            // End all currently active structures that would overlap with the new effective date
            // This prevents duplicates when editing with the same or different effective dates
            EmployeeSalaryStructure::where('employee_id', $employee->id)
                ->where('effective_date', '<=', $newEffectiveDate)
                ->where(function ($q) use ($newEffectiveDate) {
                    // Structures that are still active (haven't ended before new effective date)
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', $newEffectiveDate);
                })
                ->update(['end_date' => $endDate]);

            // Create new structures
            foreach ($validated['components'] as $comp) {
                $component = SalaryComponent::where('company_id', current_company_id())
                    ->findOrFail($comp['component_id']);

                // Validate calculation type matches input
                if ($component->calculation_type === 'fixed' && empty($comp['amount'])) {
                    $msg = "Component '{$component->component_name}' requires an amount (fixed type).";
                    throw new \Exception($msg);
                }
                if ($component->calculation_type === 'percentage' && empty($comp['percentage'])) {
                    $msg = "Component '{$component->component_name}' requires a percentage (percentage type).";
                    throw new \Exception($msg);
                }

                EmployeeSalaryStructure::create([
                    'employee_id' => $employee->id,
                    'component_id' => $comp['component_id'],
                    'amount' => $comp['amount'] ?? null,
                    'percentage' => $comp['percentage'] ?? null,
                    'effective_date' => $comp['effective_date'],
                    'end_date' => $comp['end_date'] ?? null,
                    'notes' => $comp['notes'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('hr.employee-salary-structure.show', $employee->id)
                ->with('success', 'Salary structure assigned successfully.');
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
    public function show($employeeId)
    {
        $employee = Employee::where('company_id', current_company_id())
            ->findOrFail($employeeId);

        // Get current structure
        $currentStructures = EmployeeSalaryStructure::where('hr_employee_salary_structure.employee_id', $employee->id)
            ->where('hr_employee_salary_structure.effective_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('hr_employee_salary_structure.end_date')
                  ->orWhere('hr_employee_salary_structure.end_date', '>=', now());
            })
            ->join('hr_salary_components', 'hr_employee_salary_structure.component_id', '=', 'hr_salary_components.id')
            ->select('hr_employee_salary_structure.*')
            ->orderBy('hr_salary_components.component_type')
            ->orderBy('hr_salary_components.display_order')
            ->with('component')
            ->get();

        // Get historical structures
        $historicalStructures = EmployeeSalaryStructure::where('employee_id', $employee->id)
            ->where(function ($q) {
                $q->where('end_date', '<', now())
                  ->orWhere(function ($query) {
                      $query->whereNotNull('end_date')
                            ->where('effective_date', '<', now());
                  });
            })
            ->with('component')
            ->orderBy('effective_date', 'desc')
            ->get()
            ->groupBy(function ($item) {
                return $item->effective_date->format('Y-m');
            });

        // Calculate totals
        $earnings = $currentStructures->filter(function ($s) {
            return $s->component->component_type === 'earning';
        });
        $deductions = $currentStructures->filter(function ($s) {
            return $s->component->component_type === 'deduction';
        });

        $totalEarnings = 0;
        $totalDeductions = 0;

        foreach ($earnings as $earning) {
            $baseAmount = $employee->basic_salary ?? 0;
            $amount = $earning->component->calculateAmount($baseAmount, $earning);
            $totalEarnings += $amount;
        }

        foreach ($deductions as $deduction) {
            $baseAmount = $totalEarnings;
            $amount = $deduction->component->calculateAmount($baseAmount, $deduction);
            $totalDeductions += $amount;
        }

        return view('hr-payroll.employee-salary-structure.show', compact(
            'employee',
            'currentStructures',
            'historicalStructures',
            'totalEarnings',
            'totalDeductions'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($employeeId)
    {
        return $this->create(request()->merge(['employee_id' => $employeeId]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $employeeId)
    {
        return $this->store($request);
    }

    /**
     * Remove a specific component from employee structure
     */
    public function destroy($employeeId, $structureId)
    {
        $employee = Employee::where('company_id', current_company_id())
            ->findOrFail($employeeId);

        $structure = EmployeeSalaryStructure::where('employee_id', $employee->id)
            ->findOrFail($structureId);

        // End the structure instead of deleting (for audit trail)
        $structure->update([
            'end_date' => now()->subDay()
        ]);

        return redirect()->route('hr.employee-salary-structure.show', $employeeId)
            ->with('success', 'Component removed from salary structure.');
    }

    /**
     * Bulk assign salary structure to multiple employees
     */
    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|exists:hr_employees,id',
            'components' => 'required|array|min:1',
            'components.*.component_id' => 'required|exists:hr_salary_components,id',
            'components.*.amount' => 'nullable|numeric|min:0',
            'components.*.percentage' => 'nullable|numeric|min:0|max:100',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after:effective_date',
            'notes' => 'nullable|string|max:500',
        ], [
            'employee_ids.required' => 'Please select at least one employee.',
            'employee_ids.min' => 'Please select at least one employee.',
            'components.required' => 'Please add at least one salary component.',
            'components.min' => 'Please add at least one salary component.',
        ]);

        $companyId = current_company_id();
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($validated['employee_ids'] as $employeeId) {
                try {
                    $employee = Employee::where('company_id', $companyId)
                        ->with('position.grade')
                        ->findOrFail($employeeId);

                    // Validate basic salary requirement
                    $hasBasicSalary = false;
                    foreach ($validated['components'] as $comp) {
                        $component = SalaryComponent::find($comp['component_id']);
                        if ($component && str_contains(strtolower($component->component_code), 'basic')) {
                            $hasBasicSalary = true;
                            break;
                        }
                    }

                    if (!$hasBasicSalary) {
                        $errors[] = "Employee {$employee->full_name}: At least one Basic Salary component must be included.";
                        $errorCount++;
                        continue;
                    }

                    // End existing structures
                    $endDate = Carbon::parse($validated['effective_date'])->subDay();
                    EmployeeSalaryStructure::where('employee_id', $employee->id)
                        ->where('effective_date', '<=', $validated['effective_date'])
                        ->where(function ($q) use ($validated) {
                            $q->whereNull('end_date')
                              ->orWhere('end_date', '>=', $validated['effective_date']);
                        })
                        ->update(['end_date' => $endDate]);

                    // Create new structures
                    foreach ($validated['components'] as $comp) {
                        $component = SalaryComponent::where('company_id', $companyId)
                            ->findOrFail($comp['component_id']);

                        // Validate calculation type matches input
                        if ($component->calculation_type === 'fixed' && empty($comp['amount'])) {
                            throw new \Exception("Component '{$component->component_name}' requires an amount.");
                        }
                        if ($component->calculation_type === 'percentage' && empty($comp['percentage'])) {
                            throw new \Exception("Component '{$component->component_name}' requires a percentage.");
                        }

                        EmployeeSalaryStructure::create([
                            'employee_id' => $employee->id,
                            'component_id' => $comp['component_id'],
                            'amount' => $comp['amount'] ?? null,
                            'percentage' => $comp['percentage'] ?? null,
                            'effective_date' => $validated['effective_date'],
                            'end_date' => $validated['end_date'] ?? null,
                            'notes' => $validated['notes'] ?? null,
                        ]);
                    }

                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "Employee ID {$employeeId}: " . $e->getMessage();
                    $errorCount++;
                }
            }

            DB::commit();

            $message = "Successfully assigned salary structure to {$successCount} employee(s).";
            if ($errorCount > 0) {
                $message .= " {$errorCount} employee(s) failed.";
            }

            return redirect()->route('hr.employee-salary-structure.index')
                ->with('success', $message)
                ->with('bulk_errors', $errors);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Bulk assignment failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Apply template to employee(s)
     */
    public function applyTemplate(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:hr_salary_structure_templates,id',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|exists:hr_employees,id',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after:effective_date',
        ]);

        $companyId = current_company_id();
        $template = \App\Models\Hr\SalaryStructureTemplate::where('company_id', $companyId)
            ->where('is_active', true)
            ->findOrFail($validated['template_id']);

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($validated['employee_ids'] as $employeeId) {
                try {
                    $employee = Employee::where('company_id', $companyId)
                        ->findOrFail($employeeId);

                    // End existing structures
                    $endDate = Carbon::parse($validated['effective_date'])->subDay();
                    EmployeeSalaryStructure::where('employee_id', $employee->id)
                        ->where('effective_date', '<=', $validated['effective_date'])
                        ->where(function ($q) use ($validated) {
                            $q->whereNull('end_date')
                              ->orWhere('end_date', '>=', $validated['effective_date']);
                        })
                        ->update(['end_date' => $endDate]);

                    // Apply template
                    $template->applyToEmployee(
                        $employee->id,
                        $validated['effective_date'],
                        $validated['end_date'] ?? null
                    );

                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "Employee ID {$employeeId}: " . $e->getMessage();
                    $errorCount++;
                }
            }

            DB::commit();

            $message = "Successfully applied template '{$template->template_name}' to {$successCount} employee(s).";
            if ($errorCount > 0) {
                $message .= " {$errorCount} employee(s) failed.";
            }

            return redirect()->route('hr.employee-salary-structure.index')
                ->with('success', $message)
                ->with('bulk_errors', $errors);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Template application failed: ' . $e->getMessage()]);
        }
    }
}
