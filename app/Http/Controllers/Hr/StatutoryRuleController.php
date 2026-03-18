<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\StatutoryRule;
use App\Models\Hr\StatutoryRuleEmployeeCategory;
use App\Models\Hr\Employee;
use App\Models\Hr\Department;
use App\Models\Hr\Position;
use App\Models\Hr\JobGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class StatutoryRuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $rules = StatutoryRule::where('company_id', current_company_id())
                ->orderBy('rule_type')
                ->orderBy('effective_from', 'desc');

            return DataTables::of($rules)
                ->addIndexColumn()
                ->addColumn('type_badge', function ($rule) {
                    $badges = [
                        'paye' => 'bg-danger',
                        'nhif' => 'bg-info',
                        'pension' => 'bg-primary',
                        'wcf' => 'bg-warning',
                        'sdl' => 'bg-success',
                        'heslb' => 'bg-secondary',
                    ];
                    $color = $badges[$rule->rule_type] ?? 'bg-secondary';
                    return '<span class="badge ' . $color . '">' . strtoupper($rule->rule_type) . '</span>';
                })
                ->addColumn('effective_period', function ($rule) {
                    $from = $rule->effective_from->format('d M Y');
                    $to = $rule->effective_to ? $rule->effective_to->format('d M Y') : 'Ongoing';
                    return $from . ' - ' . $to;
                })
                ->addColumn('status_badge', function ($rule) {
                    $isEffective = $rule->is_active
                        && $rule->effective_from <= now()
                        && (!$rule->effective_to || $rule->effective_to >= now());

                    return $isEffective
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-secondary">Inactive</span>';
                })
                ->addColumn('action', function ($rule) {
                    $actions = '<div class="btn-group" role="group">';
                    $showUrl = route('hr.statutory-rules.show', $rule->hash_id);
                    $editUrl = route('hr.statutory-rules.edit', $rule->hash_id);
                    $actions .= '<a href="' . $showUrl . '" class="btn btn-sm btn-outline-info">';
                    $actions .= '<i class="bx bx-show"></i></a>';
                    $actions .= '<a href="' . $editUrl . '" class="btn btn-sm btn-outline-primary">';
                    $actions .= '<i class="bx bx-edit"></i></a>';
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['type_badge', 'status_badge', 'action'])
                ->make(true);
        }

        // Dashboard statistics
        $companyId = current_company_id();
        $today = Carbon::today();

        $ruleTypes = [
            'paye' => ['label' => 'PAYE Rules', 'icon' => 'bx-receipt', 'color' => 'danger', 'description' => 'Tax rules'],
            'nhif' => ['label' => 'NHIF Rules', 'icon' => 'bx-heart', 'color' => 'info', 'description' => 'Health insurance'],
            'pension' => ['label' => 'Pension Rules', 'icon' => 'bx-wallet', 'color' => 'primary', 'description' => 'Pension scheme'],
            'wcf' => ['label' => 'WCF Rules', 'icon' => 'bx-shield-alt', 'color' => 'warning', 'description' => 'Workers compensation'],
            'sdl' => ['label' => 'SDL Rules', 'icon' => 'bx-book', 'color' => 'success', 'description' => 'Skills development'],
            'heslb' => ['label' => 'HESLB Rules', 'icon' => 'bx-graduation', 'color' => 'secondary', 'description' => 'Student loans'],
        ];

        $stats = [
            'total' => StatutoryRule::where('company_id', $companyId)->count(),
            'active' => StatutoryRule::where('company_id', $companyId)
                ->where('is_active', true)
                ->where('effective_from', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('effective_to')->orWhere('effective_to', '>=', $today);
                })
                ->count(),
        ];

        // Calculate stats for each rule type
        foreach ($ruleTypes as $type => $config) {
            $stats[$type] = StatutoryRule::where('company_id', $companyId)
                ->where('rule_type', $type)
                ->where('is_active', true)
                ->effectiveForDate($today)
                ->count();
        }

        return view('hr-payroll.statutory-rules.index', compact('stats', 'ruleTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get available options for categories
        $departments = Department::where('company_id', current_company_id())->get();
        $positions = Position::where('company_id', current_company_id())->get();
        $grades = JobGrade::where('company_id', current_company_id())->where('is_active', true)->get();
        $employmentTypes = Employee::where('company_id', current_company_id())
            ->whereNotNull('employment_type')
            ->distinct()
            ->pluck('employment_type')
            ->unique()
            ->values();

        return view('hr-payroll.statutory-rules.create', compact('departments', 'positions', 'grades', 'employmentTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rule_type' => 'required|in:paye,nhif,pension,wcf,sdl,heslb',
            'rule_name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'is_active' => 'boolean',
            // PAYE fields
            'paye_brackets' => 'nullable|array',
            'paye_tax_relief' => 'nullable|numeric|min:0',
            // NHIF fields
            'nhif_employee_percent' => 'nullable|numeric|min:0|max:100',
            'nhif_employer_percent' => 'nullable|numeric|min:0|max:100',
            'nhif_ceiling' => 'nullable|numeric|min:0',
            // Pension fields
            'pension_employee_percent' => 'nullable|numeric|min:0|max:100',
            'pension_employer_percent' => 'nullable|numeric|min:0|max:100',
            'pension_ceiling' => 'nullable|numeric|min:0',
            'pension_scheme_type' => 'nullable|in:nssf,psssf,other',
            // WCF fields
            'wcf_employer_percent' => 'nullable|numeric|min:0|max:100',
            'industry_type' => 'nullable|string|max:100',
            // SDL fields
            'sdl_employer_percent' => 'nullable|numeric|min:0|max:100',
            'sdl_threshold' => 'nullable|numeric|min:0',
            // HESLB fields
            'heslb_percent' => 'nullable|numeric|min:0|max:100',
            'heslb_ceiling' => 'nullable|numeric|min:0',
        ]);

        $validated['company_id'] = current_company_id();
        $validated['is_active'] = $request->has('is_active');
        $validated['apply_to_all_employees'] = $request->has('apply_to_all_employees') ? true : false;

        // Process PAYE brackets if provided
        if ($request->has('paye_brackets')) {
            if (is_string($request->paye_brackets)) {
                // JSON string from form
                $validated['paye_brackets'] = json_decode($request->paye_brackets, true);
            } elseif (is_array($request->paye_brackets)) {
                // Ensure each bracket has all required fields
                $brackets = [];
                foreach ($request->paye_brackets as $bracket) {
                    if (isset($bracket['threshold']) && isset($bracket['rate'])) {
                        $brackets[] = [
                            'threshold' => (float) $bracket['threshold'],
                            'base_amount' => isset($bracket['base_amount']) ? (float) $bracket['base_amount'] : 0,
                            'rate' => (float) $bracket['rate'],
                        ];
                    }
                }
                $validated['paye_brackets'] = $brackets;
            }
        }

        DB::beginTransaction();
        try {
            $statutoryRule = StatutoryRule::create($validated);

            // Process employee selection if not applying to all employees
            if (!$validated['apply_to_all_employees']) {
                // Sync selected employees
                $employeeIds = $request->input('employee_ids', []);
                $statutoryRule->employees()->sync($employeeIds);
                
                // Process employee categories if provided
                if ($request->has('employee_categories')) {
                    $this->syncEmployeeCategories($statutoryRule, $request->employee_categories);
                }
            }

            DB::commit();
            return redirect()->route('hr.statutory-rules.show', $statutoryRule->hash_id)
                ->with('success', 'Statutory rule created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create statutory rule: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(StatutoryRule $statutoryRule)
    {
        // Verify statutory rule belongs to user's company
        if ($statutoryRule->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access to statutory rule.');
        }

        return view('hr-payroll.statutory-rules.show', compact('statutoryRule'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StatutoryRule $statutoryRule)
    {
        // Verify statutory rule belongs to user's company
        if ($statutoryRule->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access to statutory rule.');
        }

        $statutoryRule->load(['employeeCategories', 'employees']);
        
        // Get available options for categories
        $departments = Department::where('company_id', current_company_id())->get();
        $positions = Position::where('company_id', current_company_id())->get();
        $grades = JobGrade::where('company_id', current_company_id())->where('is_active', true)->get();
        $employmentTypes = Employee::where('company_id', current_company_id())
            ->whereNotNull('employment_type')
            ->distinct()
            ->pluck('employment_type')
            ->unique()
            ->values();

        return view('hr-payroll.statutory-rules.edit', compact('statutoryRule', 'departments', 'positions', 'grades', 'employmentTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StatutoryRule $statutoryRule)
    {
        // Verify statutory rule belongs to user's company
        if ($statutoryRule->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access to statutory rule.');
        }
        $validated = $request->validate([
            'rule_name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'is_active' => 'boolean',
            // PAYE fields
            'paye_brackets' => 'nullable|array',
            'paye_tax_relief' => 'nullable|numeric|min:0',
            // NHIF fields
            'nhif_employee_percent' => 'nullable|numeric|min:0|max:100',
            'nhif_employer_percent' => 'nullable|numeric|min:0|max:100',
            'nhif_ceiling' => 'nullable|numeric|min:0',
            // Pension fields
            'pension_employee_percent' => 'nullable|numeric|min:0|max:100',
            'pension_employer_percent' => 'nullable|numeric|min:0|max:100',
            'pension_ceiling' => 'nullable|numeric|min:0',
            'pension_scheme_type' => 'nullable|in:nssf,psssf,other',
            // WCF fields
            'wcf_employer_percent' => 'nullable|numeric|min:0|max:100',
            'industry_type' => 'nullable|string|max:100',
            // SDL fields
            'sdl_employer_percent' => 'nullable|numeric|min:0|max:100',
            'sdl_threshold' => 'nullable|numeric|min:0',
            // HESLB fields
            'heslb_percent' => 'nullable|numeric|min:0|max:100',
            'heslb_ceiling' => 'nullable|numeric|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['apply_to_all_employees'] = $request->has('apply_to_all_employees') ? true : false;
        $validated['category_name'] = $request->category_name;
        $validated['category_description'] = $request->category_description;

        // Process PAYE brackets if provided
        if ($request->has('paye_brackets')) {
            if (is_string($request->paye_brackets)) {
                // JSON string from form
                $validated['paye_brackets'] = json_decode($request->paye_brackets, true);
            } elseif (is_array($request->paye_brackets)) {
                // Ensure each bracket has all required fields
                $brackets = [];
                foreach ($request->paye_brackets as $bracket) {
                    if (isset($bracket['threshold']) && isset($bracket['rate'])) {
                        $brackets[] = [
                            'threshold' => (float) $bracket['threshold'],
                            'base_amount' => isset($bracket['base_amount']) ? (float) $bracket['base_amount'] : 0,
                            'rate' => (float) $bracket['rate'],
                        ];
                    }
                }
                $validated['paye_brackets'] = $brackets;
            }
        }

        DB::beginTransaction();
        try {
            $statutoryRule->update($validated);

            // Process employee selection if not applying to all employees
            if (!$validated['apply_to_all_employees']) {
                // Sync selected employees
                $employeeIds = $request->input('employee_ids', []);
                $statutoryRule->employees()->sync($employeeIds);
                
                // Process employee categories if provided
                if ($request->has('employee_categories')) {
                    $this->syncEmployeeCategories($statutoryRule, $request->employee_categories);
                }
            } else {
                // Remove all employee selections and categories if applying to all employees
                $statutoryRule->employees()->detach();
                $statutoryRule->employeeCategories()->delete();
            }

            DB::commit();
            return redirect()->route('hr.statutory-rules.show', $statutoryRule->hash_id)
                ->with('success', 'Statutory rule updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update statutory rule: ' . $e->getMessage());
        }
    }

    /**
     * Sync employee categories for a statutory rule
     */
    protected function syncEmployeeCategories(StatutoryRule $rule, array $categories): void
    {
        // Delete existing categories
        $rule->employeeCategories()->delete();

        // Add new categories
        foreach ($categories as $category) {
            if (!empty($category['type']) && !empty($category['value'])) {
                StatutoryRuleEmployeeCategory::create([
                    'statutory_rule_id' => $rule->id,
                    'category_type' => $category['type'],
                    'category_value' => $category['value'],
                    'category_label' => $category['label'] ?? null,
                ]);
            }
        }
    }

    /**
     * Get available employee categories for dropdowns
     */
    public function getCategoryOptions(Request $request)
    {
        $companyId = current_company_id();
        $type = $request->get('type');

        $options = [];

        switch ($type) {
            case 'employment_type':
                // Get unique employment types from employees
                $options = Employee::where('company_id', $companyId)
                    ->whereNotNull('employment_type')
                    ->distinct()
                    ->pluck('employment_type')
                    ->map(function ($type) {
                        return [
                            'value' => $type,
                            'label' => ucfirst($type),
                        ];
                    })
                    ->values()
                    ->toArray();
                break;

            case 'department':
                $options = Department::where('company_id', $companyId)
                    ->get()
                    ->map(function ($dept) {
                        return [
                            'value' => $dept->id,
                            'label' => $dept->name,
                        ];
                    })
                    ->toArray();
                break;

            case 'position':
                $options = Position::where('company_id', $companyId)
                    ->get()
                    ->map(function ($position) {
                        return [
                            'value' => $position->id,
                            'label' => $position->position_title ?? $position->title,
                        ];
                    })
                    ->toArray();
                break;

            case 'grade':
                $options = JobGrade::where('company_id', $companyId)
                    ->where('is_active', true)
                    ->get()
                    ->map(function ($grade) {
                        return [
                            'value' => $grade->id,
                            'label' => $grade->grade_name . ' (' . $grade->grade_code . ')',
                        ];
                    })
                    ->toArray();
                break;
        }

        return response()->json($options);
    }
}
