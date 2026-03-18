@extends('layouts.main')

@section('title', 'Edit Statutory Rule')

@push('styles')
<style>
    .rule-type-section {
        display: none;
    }
    .rule-type-section.active {
        display: block;
    }
</style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Statutory Rules', 'url' => route('hr.statutory-rules.index'), 'icon' => 'bx bx-shield'],
                ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0"><i class="bx bx-shield me-2"></i>Edit Statutory Rule</h5>
                    <p class="mb-0 text-muted">{{ $statutoryRule->rule_name }}</p>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('hr.statutory-rules.update', $statutoryRule->hash_id) }}" method="POST" id="statutoryRuleForm">
                                @csrf
                                @method('PUT')

                                <div class="alert alert-info">
                                    <i class="bx bx-info-circle me-2"></i>
                                    <strong>Rule Type:</strong> {{ strtoupper($statutoryRule->rule_type) }} (cannot be changed)
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Rule Type</label>
                                    <input type="text" class="form-control" 
                                        value="{{ strtoupper($statutoryRule->rule_type) }}" disabled>
                                    <input type="hidden" name="rule_type" value="{{ $statutoryRule->rule_type }}">
                                </div>

                                <div class="mb-3">
                                    <label for="rule_name" class="form-label">Rule Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('rule_name') is-invalid @enderror" 
                                        id="rule_name" name="rule_name" 
                                        value="{{ old('rule_name', $statutoryRule->rule_name) }}" required>
                                    @error('rule_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                        id="description" name="description" rows="2">{{ old('description', $statutoryRule->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="effective_from" class="form-label">Effective From <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('effective_from') is-invalid @enderror" 
                                                id="effective_from" name="effective_from" 
                                                value="{{ old('effective_from', $statutoryRule->effective_from->format('Y-m-d')) }}" required>
                                            @error('effective_from')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="effective_to" class="form-label">Effective To</label>
                                            <input type="date" class="form-control @error('effective_to') is-invalid @enderror" 
                                                id="effective_to" name="effective_to" 
                                                value="{{ old('effective_to', $statutoryRule->effective_to ? $statutoryRule->effective_to->format('Y-m-d') : '') }}">
                                            @error('effective_to')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- PAYE Section -->
                                <div class="rule-type-section {{ $statutoryRule->rule_type == 'paye' ? 'active' : '' }}" id="paye_section">
                                    <hr>
                                    <h6 class="mb-3">PAYE (Income Tax) Configuration</h6>
                                    
                                    <div class="alert alert-primary mb-3">
                                        <i class="bx bx-info-circle me-2"></i>
                                        <strong>About PAYE:</strong> PAYE (Pay As You Earn) is a progressive income tax system in Tanzania. Configure tax brackets with thresholds, base amounts (cumulative tax from previous brackets), and tax rates. The system automatically calculates tax based on taxable income using the formula: <strong>Base Amount + (Income - Threshold) × Rate</strong>. Tax relief can be applied to reduce the final tax amount.
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Tax Relief Amount</label>
                                        <input type="number" step="0.01" min="0" 
                                            class="form-control" 
                                            name="paye_tax_relief" 
                                            value="{{ old('paye_tax_relief', $statutoryRule->paye_tax_relief) }}" 
                                            placeholder="0.00">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Tax Brackets</label>
                                        <div class="alert alert-info mb-3">
                                            <i class="bx bx-info-circle me-2"></i>
                                            <strong>Format:</strong> Each bracket shows the income range (Over → Not Over) based on thresholds. The "Not Over" value is automatically calculated from the next bracket's threshold.
                                        </div>
                                        <div id="paye_brackets_container">
                                            @if($statutoryRule->paye_brackets && is_array($statutoryRule->paye_brackets))
                                                @foreach($statutoryRule->paye_brackets as $index => $bracket)
                                                    @php
                                                        $nextBracket = $statutoryRule->paye_brackets[$index + 1] ?? null;
                                                        $notOver = $nextBracket ? number_format($nextBracket['threshold'] ?? 0, 0) : 'And above';
                                                        $threshold = number_format($bracket['threshold'] ?? 0, 0);
                                                    @endphp
                                                    <div class="paye-bracket-item mb-3 border rounded p-3" data-index="{{ $index }}">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <strong class="bracket-range-text">Bracket {{ $index + 1 }}: Over <span class="range-over">{{ $threshold }}</span> &rarr; Not over <span class="range-not-over">{{ $notOver }}</span></strong>
                                                            <button type="button" class="btn btn-sm btn-danger remove-bracket">
                                                                <i class="bx bx-trash me-1"></i>Remove
                                                            </button>
                                                        </div>
                                                        <div class="row g-2">
                                                            <div class="col-md-4">
                                                                <label class="form-label small">Over (Threshold) <span class="text-danger">*</span></label>
                                                                <input type="number" step="0.01" min="0" 
                                                                    class="form-control bracket-threshold" 
                                                                    name="paye_brackets[{{ $index }}][threshold]" 
                                                                    value="{{ $bracket['threshold'] ?? '' }}" 
                                                                    placeholder="e.g., 0"
                                                                    data-index="{{ $index }}">
                                                                <small class="text-muted">Minimum income for this bracket</small>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label small">Tax on Column 1 (Base Amount) <span class="text-danger">*</span></label>
                                                                <input type="number" step="0.01" min="0" 
                                                                    class="form-control" 
                                                                    name="paye_brackets[{{ $index }}][base_amount]" 
                                                                    value="{{ $bracket['base_amount'] ?? 0 }}" 
                                                                    placeholder="e.g., 0">
                                                                <small class="text-muted">Cumulative tax from previous brackets</small>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label small">Tax on Excess (Rate %) <span class="text-danger">*</span></label>
                                                                <input type="number" step="0.01" min="0" max="100" 
                                                                    class="form-control" 
                                                                    name="paye_brackets[{{ $index }}][rate]" 
                                                                    value="{{ $bracket['rate'] ?? '' }}" 
                                                                    placeholder="e.g., 8">
                                                                <small class="text-muted">Tax percentage on excess income</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="paye-bracket-item mb-3 border rounded p-3" data-index="0">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <strong class="bracket-range-text">Bracket 1: Over <span class="range-over">0</span> &rarr; Not over <span class="range-not-over">-</span></strong>
                                                        <button type="button" class="btn btn-sm btn-danger remove-bracket">
                                                            <i class="bx bx-trash me-1"></i>Remove
                                                        </button>
                                                    </div>
                                                    <div class="row g-2">
                                                        <div class="col-md-4">
                                                            <label class="form-label small">Over (Threshold) <span class="text-danger">*</span></label>
                                                            <input type="number" step="0.01" min="0" 
                                                                class="form-control bracket-threshold" 
                                                                name="paye_brackets[0][threshold]" 
                                                                placeholder="e.g., 0"
                                                                data-index="0">
                                                            <small class="text-muted">Minimum income for this bracket</small>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label small">Tax on Column 1 (Base Amount) <span class="text-danger">*</span></label>
                                                            <input type="number" step="0.01" min="0" 
                                                                class="form-control" 
                                                                name="paye_brackets[0][base_amount]" 
                                                                placeholder="e.g., 0">
                                                            <small class="text-muted">Cumulative tax from previous brackets</small>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label small">Tax on Excess (Rate %) <span class="text-danger">*</span></label>
                                                            <input type="number" step="0.01" min="0" max="100" 
                                                                class="form-control" 
                                                                name="paye_brackets[0][rate]" 
                                                                placeholder="e.g., 0">
                                                            <small class="text-muted">Tax percentage on excess income</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <button type="button" class="btn btn-sm btn-primary" id="add_paye_bracket">
                                            <i class="bx bx-plus me-1"></i>Add Bracket
                                        </button>
                                        <small class="text-muted d-block mt-2">
                                            <strong>Note:</strong> Enter brackets in ascending order by "Over" threshold. The last bracket will show "And above" for the range.
                                        </small>
                                    </div>
                                </div>

                                <!-- NHIF Section -->
                                <div class="rule-type-section {{ $statutoryRule->rule_type == 'nhif' ? 'active' : '' }}" id="nhif_section">
                                    <hr>
                                    <h6 class="mb-3">NHIF (Health Insurance) Configuration</h6>
                                    
                                    <div class="alert alert-primary mb-3">
                                        <i class="bx bx-info-circle me-2"></i>
                                        <strong>About NHIF:</strong> NHIF (National Health Insurance Fund) provides health insurance coverage for employees in Tanzania. Both employee and employer contribute a percentage of the employee's salary. The contribution ceiling limits the maximum amount subject to NHIF deduction. Contributions are deducted from employee salary and matched by employer contributions.
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Employee %</label>
                                                <input type="number" step="0.01" min="0" max="100" 
                                                    class="form-control" 
                                                    name="nhif_employee_percent" 
                                                    value="{{ old('nhif_employee_percent', $statutoryRule->nhif_employee_percent) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Employer %</label>
                                                <input type="number" step="0.01" min="0" max="100" 
                                                    class="form-control" 
                                                    name="nhif_employer_percent" 
                                                    value="{{ old('nhif_employer_percent', $statutoryRule->nhif_employer_percent) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Contribution Ceiling</label>
                                                <input type="number" step="0.01" min="0" 
                                                    class="form-control" 
                                                    name="nhif_ceiling" 
                                                    value="{{ old('nhif_ceiling', $statutoryRule->nhif_ceiling) }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pension Section -->
                                <div class="rule-type-section {{ $statutoryRule->rule_type == 'pension' ? 'active' : '' }}" id="pension_section">
                                    <hr>
                                    <h6 class="mb-3">Pension Configuration</h6>
                                    
                                    <div class="alert alert-primary mb-3">
                                        <i class="bx bx-info-circle me-2"></i>
                                        <strong>About Pension:</strong> Pension schemes (NSSF/PSSSF) provide retirement benefits for employees. Both employee and employer contribute a percentage of the employee's salary. The contribution ceiling limits the maximum pensionable salary. Pension contributions are deducted from employee salary and matched by employer contributions. Pension contributions are typically tax-deductible, reducing taxable income for PAYE calculation.
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Scheme Type</label>
                                                <select class="form-select" name="pension_scheme_type">
                                                    <option value="">Select Scheme</option>
                                                    <option value="nssf" {{ old('pension_scheme_type', $statutoryRule->pension_scheme_type) == 'nssf' ? 'selected' : '' }}>NSSF</option>
                                                    <option value="psssf" {{ old('pension_scheme_type', $statutoryRule->pension_scheme_type) == 'psssf' ? 'selected' : '' }}>PSSSF</option>
                                                    <option value="other" {{ old('pension_scheme_type', $statutoryRule->pension_scheme_type) == 'other' ? 'selected' : '' }}>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Employee %</label>
                                                <input type="number" step="0.01" min="0" max="100" 
                                                    class="form-control" 
                                                    name="pension_employee_percent" 
                                                    value="{{ old('pension_employee_percent', $statutoryRule->pension_employee_percent) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Employer %</label>
                                                <input type="number" step="0.01" min="0" max="100" 
                                                    class="form-control" 
                                                    name="pension_employer_percent" 
                                                    value="{{ old('pension_employer_percent', $statutoryRule->pension_employer_percent) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Contribution Ceiling</label>
                                                <input type="number" step="0.01" min="0" 
                                                    class="form-control" 
                                                    name="pension_ceiling" 
                                                    value="{{ old('pension_ceiling', $statutoryRule->pension_ceiling) }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- WCF Section -->
                                <div class="rule-type-section {{ $statutoryRule->rule_type == 'wcf' ? 'active' : '' }}" id="wcf_section">
                                    <hr>
                                    <h6 class="mb-3">WCF (Workers Compensation Fund) Configuration</h6>
                                    
                                    <div class="alert alert-primary mb-3">
                                        <i class="bx bx-info-circle me-2"></i>
                                        <strong>About WCF:</strong> WCF (Workers Compensation Fund) provides compensation for work-related injuries and occupational diseases. This is an employer-only contribution, calculated as a percentage of the total payroll. The contribution rate may vary by industry type. WCF contributions are not deducted from employee salary but are an employer expense.
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Employer %</label>
                                                <input type="number" step="0.01" min="0" max="100" 
                                                    class="form-control" 
                                                    name="wcf_employer_percent" 
                                                    value="{{ old('wcf_employer_percent', $statutoryRule->wcf_employer_percent) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Industry Type</label>
                                                <input type="text" class="form-control" 
                                                    name="industry_type" 
                                                    value="{{ old('industry_type', $statutoryRule->industry_type) }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SDL Section -->
                                <div class="rule-type-section {{ $statutoryRule->rule_type == 'sdl' ? 'active' : '' }}" id="sdl_section">
                                    <hr>
                                    <h6 class="mb-3">SDL (Skills Development Levy) Configuration</h6>
                                    
                                    <div class="alert alert-primary mb-3">
                                        <i class="bx bx-info-circle me-2"></i>
                                        <strong>About SDL:</strong> SDL (Skills Development Levy) is a levy paid by employers to fund skills development and training programs. This is an employer-only contribution, calculated as 3.5% of the total gross emoluments paid to all employees. <strong>SDL applies only to employers with 10 or more employees.</strong> SDL contributions are not deducted from employee salary but are an employer expense.
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Employer % <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" min="0" max="100" 
                                                    class="form-control" 
                                                    name="sdl_employer_percent" 
                                                    value="{{ old('sdl_employer_percent', $statutoryRule->sdl_employer_percent ?? 3.5) }}" required>
                                                <small class="text-muted">Rate for 2025: 3.5% of total gross emoluments</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Minimum Employees <span class="text-danger">*</span></label>
                                                <input type="number" step="1" min="1" 
                                                    class="form-control" 
                                                    name="sdl_min_employees" 
                                                    value="{{ old('sdl_min_employees', $statutoryRule->sdl_min_employees ?? 10) }}" required>
                                                <small class="text-muted">SDL applies only to employers with 10 or more employees</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Minimum Payroll Threshold (Optional)</label>
                                                <input type="number" step="0.01" min="0" 
                                                    class="form-control" 
                                                    name="sdl_threshold" 
                                                    value="{{ old('sdl_threshold', $statutoryRule->sdl_threshold) }}">
                                                <small class="text-muted">Optional: Additional threshold based on payroll amount</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- HESLB Section -->
                                <div class="rule-type-section {{ $statutoryRule->rule_type == 'heslb' ? 'active' : '' }}" id="heslb_section">
                                    <hr>
                                    <h6 class="mb-3">HESLB (Student Loans) Configuration</h6>
                                    
                                    <div class="alert alert-primary mb-3">
                                        <i class="bx bx-info-circle me-2"></i>
                                        <strong>About HESLB:</strong> HESLB (Higher Education Students' Loans Board) manages student loan repayments. This is an employee-specific statutory deduction used to recover government student loans through payroll. The deduction is calculated as a percentage of gross pay, with a maximum ceiling to limit the deduction amount. <strong>HESLB applies only to employees with an active loan balance.</strong> Deductions are automatically capped to the outstanding balance and stop when the balance reaches zero. Each repayment is recorded in a transaction ledger.
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Deduction %</label>
                                                <input type="number" step="0.01" min="0" max="100" 
                                                    class="form-control" 
                                                    name="heslb_percent" 
                                                    value="{{ old('heslb_percent', $statutoryRule->heslb_percent) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Deduction Ceiling</label>
                                                <input type="number" step="0.01" min="0" 
                                                    class="form-control" 
                                                    name="heslb_ceiling" 
                                                    value="{{ old('heslb_ceiling', $statutoryRule->heslb_ceiling) }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Employee Category Selection -->
                                <hr>
                                <h6 class="mb-3">Employee Category Assignment</h6>
                                
                                <div class="alert alert-info mb-3">
                                    <i class="bx bx-info-circle me-2"></i>
                                    <strong>About Categories:</strong> By default, statutory rules apply to all employees. You can restrict a rule to specific employee categories (Employment Type, Department, Position, or Grade) for multi-rate support.
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="apply_to_all_employees" 
                                            id="apply_to_all_employees" value="1" 
                                            {{ old('apply_to_all_employees', $statutoryRule->apply_to_all_employees ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="apply_to_all_employees">
                                            <strong>Apply to All Employees</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">If unchecked, you can assign this rule to specific employee categories</small>
                                </div>

                                <div id="category_selection_section" style="display: {{ old('apply_to_all_employees', $statutoryRule->apply_to_all_employees ?? true) ? 'none' : 'block' }};">
                                    <div class="mb-3">
                                        <label for="employee_ids" class="form-label">Select Employees <span class="text-danger">*</span></label>
                                        <select class="form-select select2-multiple" 
                                            id="employee_ids" 
                                            name="employee_ids[]" 
                                            multiple="multiple" 
                                            data-placeholder="Select employees...">
                                            @php
                                                $selectedEmployeeIds = old('employee_ids', $statutoryRule->employees->pluck('id')->toArray() ?? []);
                                            @endphp
                                            @foreach(\App\Models\Hr\Employee::where('company_id', current_company_id())->where('status', 'active')->orderBy('first_name')->get() as $emp)
                                                <option value="{{ $emp->id }}" {{ in_array($emp->id, $selectedEmployeeIds) ? 'selected' : '' }}>
                                                    {{ $emp->full_name }} ({{ $emp->employee_number ?? 'N/A' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Select specific employees to apply this rule to</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Select Employee Categories (Optional)</label>
                                        <div id="employee_categories_container">
                                            @if($statutoryRule->employeeCategories && $statutoryRule->employeeCategories->count() > 0)
                                                @foreach($statutoryRule->employeeCategories as $index => $category)
                                                    <div class="category-row mb-3 border rounded p-3" data-index="{{ $index }}">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <strong>Category {{ $index + 1 }}</strong>
                                                            <button type="button" class="btn btn-sm btn-danger remove-category">
                                                                <i class="bx bx-trash me-1"></i>Remove
                                                            </button>
                                                        </div>
                                                        <div class="row g-2">
                                                            <div class="col-md-4">
                                                                <label class="form-label small">Category Type</label>
                                                                <select class="form-select category-type-select" 
                                                                    name="employee_categories[{{ $index }}][type]" 
                                                                    data-index="{{ $index }}">
                                                                    <option value="">Select Type</option>
                                                                    <option value="employment_type" {{ $category->category_type == 'employment_type' ? 'selected' : '' }}>Employment Type</option>
                                                                    <option value="department" {{ $category->category_type == 'department' ? 'selected' : '' }}>Department</option>
                                                                    <option value="position" {{ $category->category_type == 'position' ? 'selected' : '' }}>Position</option>
                                                                    <option value="grade" {{ $category->category_type == 'grade' ? 'selected' : '' }}>Job Grade</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-8">
                                                                <label class="form-label small">Category Value</label>
                                                                <select class="form-select category-value-select" 
                                                                    name="employee_categories[{{ $index }}][value]" 
                                                                    data-index="{{ $index }}">
                                                                    <option value="">Select Value</option>
                                                                    <!-- Options will be populated by JavaScript based on type -->
                                                                </select>
                                                                <input type="hidden" name="employee_categories[{{ $index }}][label]" class="category-label-input" value="{{ $category->category_label }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                        <button type="button" class="btn btn-sm btn-primary mt-2" id="add_category">
                                            <i class="bx bx-plus me-1"></i>Add Category
                                        </button>
                                        <small class="text-muted d-block mt-2">
                                            <strong>Note:</strong> An employee must match at least one category for the rule to apply.
                                        </small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" 
                                            id="is_active" value="1" 
                                            {{ old('is_active', $statutoryRule->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('hr.statutory-rules.index') }}" class="btn btn-secondary">
                                        <i class="bx bx-x me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i>Update Rule
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for employee multi-select
    $('.select2-multiple').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select employees...',
        allowClear: true
    });

    let bracketIndex = {{ $statutoryRule->paye_brackets && is_array($statutoryRule->paye_brackets) ? count($statutoryRule->paye_brackets) : 1 }};

    // Function to update bracket ranges
    function updateBracketRanges() {
        const brackets = $('.paye-bracket-item').toArray();
        brackets.forEach(function(bracket, index) {
            const $bracket = $(bracket);
            const threshold = parseFloat($bracket.find('.bracket-threshold').val()) || 0;
            const bracketNumber = index + 1;
            
            // Get next bracket's threshold
            let notOver = '-';
            if (index < brackets.length - 1) {
                const nextThreshold = parseFloat($(brackets[index + 1]).find('.bracket-threshold').val());
                if (nextThreshold) {
                    notOver = nextThreshold.toLocaleString('en-US');
                }
            } else {
                notOver = 'And above';
            }
            
            // Update range display
            const thresholdFormatted = threshold.toLocaleString('en-US');
            $bracket.find('.bracket-range-text').html('<strong>Bracket ' + bracketNumber + ':</strong> Over <span class="range-over">' + thresholdFormatted + '</span> &rarr; Not over <span class="range-not-over">' + notOver + '</span>');
        });
    }

    // Add PAYE bracket
    $('#add_paye_bracket').on('click', function() {
        const bracketNumber = $('.paye-bracket-item').length + 1;
        const html = `
            <div class="paye-bracket-item mb-3 border rounded p-3" data-index="${bracketIndex}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong class="bracket-range-text">Bracket ${bracketNumber}: Over <span class="range-over">0</span> &rarr; Not over <span class="range-not-over">-</span></strong>
                    <button type="button" class="btn btn-sm btn-danger remove-bracket">
                        <i class="bx bx-trash me-1"></i>Remove
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label small">Over (Threshold) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" 
                            class="form-control bracket-threshold" 
                            name="paye_brackets[${bracketIndex}][threshold]" 
                            placeholder="e.g., 270000"
                            data-index="${bracketIndex}">
                        <small class="text-muted">Minimum income for this bracket</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Tax on Column 1 (Base Amount) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" 
                            class="form-control" 
                            name="paye_brackets[${bracketIndex}][base_amount]" 
                            placeholder="e.g., 0">
                        <small class="text-muted">Cumulative tax from previous brackets</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Tax on Excess (Rate %) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" max="100" 
                            class="form-control" 
                            name="paye_brackets[${bracketIndex}][rate]" 
                            placeholder="e.g., 8">
                        <small class="text-muted">Tax percentage on excess income</small>
                    </div>
                </div>
            </div>
        `;
        $('#paye_brackets_container').append(html);
        bracketIndex++;
        updateBracketRanges();
    });

    // Remove PAYE bracket
    $(document).on('click', '.remove-bracket', function() {
        $(this).closest('.paye-bracket-item').remove();
        updateBracketRanges();
    });

    // Update ranges when threshold changes
    $(document).on('input', '.bracket-threshold', function() {
        updateBracketRanges();
    });

    // Initialize ranges on page load
    updateBracketRanges();

    // Employee Category Selection
    let categoryIndex = {{ $statutoryRule->employeeCategories ? $statutoryRule->employeeCategories->count() : 0 }};
    const categoryTypes = {
        'employment_type': 'Employment Type',
        'department': 'Department',
        'position': 'Position',
        'grade': 'Job Grade'
    };

    const categoryOptions = {
        'employment_type': @json($employmentTypes->map(function($type) { return ['value' => $type, 'label' => ucfirst($type)]; })->values()),
        'department': @json($departments->map(function($dept) { return ['value' => $dept->id, 'label' => $dept->name]; })->values()),
        'position': @json($positions->map(function($pos) { return ['value' => $pos->id, 'label' => $pos->position_title ?? $pos->title]; })->values()),
        'grade': @json($grades->map(function($grade) { return ['value' => $grade->id, 'label' => $grade->grade_name . ' (' . $grade->grade_code . ')']; })->values())
    };

    // Toggle category selection section
    $('#apply_to_all_employees').on('change', function() {
        if ($(this).is(':checked')) {
            $('#category_selection_section').slideUp();
            $('#employee_categories_container').empty();
            categoryIndex = 0;
        } else {
            $('#category_selection_section').slideDown();
        }
    });

    // Populate existing category values
    $('.category-type-select').each(function() {
        const $select = $(this);
        const type = $select.val();
        const $row = $select.closest('.category-row');
        const $valueSelect = $row.find('.category-value-select');
        const currentValue = $valueSelect.data('current-value') || '';
        
        if (type && categoryOptions[type]) {
            $valueSelect.empty().append('<option value="">Select Value</option>');
            categoryOptions[type].forEach(function(option) {
                // Compare values (handle both string and number comparisons)
                const selected = (String(option.value) === String(currentValue)) ? 'selected' : '';
                $valueSelect.append(`<option value="${option.value}" data-label="${option.label}" ${selected}>${option.label}</option>`);
            });
            $valueSelect.prop('disabled', false);
        }
    });

    // Add category row
    $('#add_category').on('click', function() {
        const html = `
            <div class="category-row mb-3 border rounded p-3" data-index="${categoryIndex}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>Category ${categoryIndex + 1}</strong>
                    <button type="button" class="btn btn-sm btn-danger remove-category">
                        <i class="bx bx-trash me-1"></i>Remove
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label small">Category Type</label>
                        <select class="form-select category-type-select" 
                            name="employee_categories[${categoryIndex}][type]" 
                            data-index="${categoryIndex}">
                            <option value="">Select Type</option>
                            ${Object.keys(categoryTypes).map(key => 
                                `<option value="${key}">${categoryTypes[key]}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small">Category Value</label>
                        <select class="form-select category-value-select" 
                            name="employee_categories[${categoryIndex}][value]" 
                            data-index="${categoryIndex}" 
                            disabled>
                            <option value="">Select category type first</option>
                        </select>
                        <input type="hidden" name="employee_categories[${categoryIndex}][label]" class="category-label-input">
                    </div>
                </div>
            </div>
        `;
        $('#employee_categories_container').append(html);
        categoryIndex++;
    });

    // Handle category type change
    $(document).on('change', '.category-type-select', function() {
        const $row = $(this).closest('.category-row');
        const type = $(this).val();
        const $valueSelect = $row.find('.category-value-select');
        const $labelInput = $row.find('.category-label-input');
        
        if (type && categoryOptions[type]) {
            $valueSelect.empty().append('<option value="">Select Value</option>');
            categoryOptions[type].forEach(function(option) {
                $valueSelect.append(`<option value="${option.value}" data-label="${option.label}">${option.label}</option>`);
            });
            $valueSelect.prop('disabled', false);
        } else {
            $valueSelect.empty().append('<option value="">Select category type first</option>').prop('disabled', true);
            $labelInput.val('');
        }
    });

    // Handle category value change
    $(document).on('change', '.category-value-select', function() {
        const $row = $(this).closest('.category-row');
        const selectedOption = $(this).find('option:selected');
        const label = selectedOption.data('label') || selectedOption.text();
        $row.find('.category-label-input').val(label);
    });

    // Remove category row
    $(document).on('click', '.remove-category', function() {
        $(this).closest('.category-row').remove();
    });

    // Form submission - convert PAYE brackets to JSON and clean up incomplete categories
    $('#statutoryRuleForm').on('submit', function(e) {
        // Remove incomplete category rows before submission (categories are optional)
        $('.category-row').each(function() {
            const $row = $(this);
            const type = $row.find('.category-type-select').val();
            const value = $row.find('.category-value-select').val();
            
            // If category row is incomplete, remove it (categories are optional)
            if (!type || !value) {
                $row.remove();
            }
        });

        const brackets = [];
        $('.paye-bracket-item').each(function() {
            const threshold = $(this).find('input[name*="[threshold]"]').val();
            const rate = $(this).find('input[name*="[rate]"]').val();
            if (threshold && rate) {
                brackets.push({
                    threshold: parseFloat(threshold),
                    rate: parseFloat(rate)
                });
            }
        });

        // Add hidden input for PAYE brackets
        if (brackets.length > 0) {
            $('<input>').attr({
                type: 'hidden',
                name: 'paye_brackets',
                value: JSON.stringify(brackets)
            }).appendTo(this);
        }
    });
});
</script>
@endpush


