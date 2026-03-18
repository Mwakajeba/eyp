@php($required = '<span class="text-danger">*</span>')

<style>
    .form-section {
        background: #fff;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border-left: 4px solid #696cff;
    }
    .form-section-header {
        display: flex;
        align-items: center;
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #f0f0f0;
    }
    .form-section-header i {
        font-size: 1.25rem;
        margin-right: 0.75rem;
        color: #696cff;
    }
    .form-section-header h6 {
        margin: 0;
        font-weight: 600;
        color: #566a7f;
    }
    .form-label {
        font-weight: 500;
        color: #566a7f;
        margin-bottom: 0.5rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: #696cff;
        box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.25);
    }
    .benefit-card {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid #e9ecef;
    }
    .benefit-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }
    .benefit-title {
        font-weight: 600;
        color: #566a7f;
        margin: 0;
        display: flex;
        align-items: center;
    }
    .benefit-title i {
        margin-right: 0.5rem;
        color: #696cff;
    }
</style>

<div class="row g-3">
    <!-- Basic Information Section -->
    <div class="col-12">
        <div class="form-section">
            <div class="form-section-header">
                <i class="bx bx-user"></i>
                <h6>Basic Information</h6>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Employee Number {!! $required !!}</label>
                    @if(isset($employee))
                        <input type="text" name="employee_number" class="form-control"
                            value="{{ old('employee_number', $employee->employee_number ?? '') }}" required>
                    @else
                        <input type="text" name="employee_number" class="form-control"
                            value="{{ old('employee_number', $nextEmployeeNumber ?? '') }}" readonly style="background-color: #f8f9fa;">
                        <small class="text-muted">Auto-generated employee number</small>
                    @endif
                    @error('employee_number')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">First Name {!! $required !!}</label>
                    <input type="text" name="first_name" class="form-control"
                        value="{{ old('first_name', $employee->first_name ?? '') }}" required>
                    @error('first_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control"
                        value="{{ old('middle_name', $employee->middle_name ?? '') }}">
                    @error('middle_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Last Name {!! $required !!}</label>
                    <input type="text" name="last_name" class="form-control"
                        value="{{ old('last_name', $employee->last_name ?? '') }}" required>
                    @error('last_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date of Birth {!! $required !!}</label>
                    <input type="date" name="date_of_birth" class="form-control"
                        value="{{ old('date_of_birth', isset($employee) ? optional($employee->date_of_birth)->format('Y-m-d') : '') }}"
                        required>
                    @error('date_of_birth')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gender {!! $required !!}</label>
                    <select name="gender" class="form-select select2-single" required>
                        @php($g = old('gender', $employee->gender ?? ''))
                        <option value="">-- Select --</option>
                        <option value="male" @selected($g === 'male')>Male</option>
                        <option value="female" @selected($g === 'female')>Female</option>
                        <option value="other" @selected($g === 'other')>Other</option>
                    </select>
                    @error('gender')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Marital Status {!! $required !!}</label>
                    <select name="marital_status" class="form-select select2-single" required>
                        @php($m = old('marital_status', $employee->marital_status ?? ''))
                        <option value="">-- Select --</option>
                        <option value="single" @selected($m === 'single')>Single</option>
                        <option value="married" @selected($m === 'married')>Married</option>
                        <option value="divorced" @selected($m === 'divorced')>Divorced</option>
                        <option value="widowed" @selected($m === 'widowed')>Widowed</option>
                    </select>
                    @error('marital_status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Location Information Section -->
    <div class="col-12">
        <div class="form-section">
            <div class="form-section-header">
                <i class="bx bx-map"></i>
                <h6>Location Information</h6>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Country {!! $required !!}</label>
                    <select name="country" class="form-select select2-single" id="country_select" required>
                        @foreach($countries ?? [] as $key => $country)
                            <option value="{{ $country }}" @selected(old('country', $employee->country ?? 'Tanzania') === $country)>{{ $country }}</option>
                        @endforeach
                    </select>
                    @error('country')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Region {!! $required !!}</label>
                    <!-- Dropdown for Tanzania -->
                    <select name="region" class="form-select select2-single" id="region_select" style="display: none;">
                        <option value="">-- Select Region --</option>
                        @foreach($tanzaniaRegions ?? [] as $region)
                            <option value="{{ $region }}" @selected(old('region', $employee->region ?? '') === $region)>{{ $region }}</option>
                        @endforeach
                    </select>
                    <!-- Text input for other countries -->
                    <input type="text" name="region" class="form-control" id="region_input" 
                        value="{{ old('region', $employee->region ?? '') }}" 
                        placeholder="Enter region/state/province">
                    @error('region')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">District {!! $required !!}</label>
                    <!-- Dropdown for Tanzania -->
                    <select name="district" class="form-select select2-single" id="district_select" style="display: none;">
                        <option value="">-- Select District --</option>
                        @foreach($tanzaniaDistricts ?? [] as $regionName => $districts)
                            <optgroup label="{{ $regionName }}" data-region="{{ $regionName }}">
                                @foreach($districts as $district)
                                    <option value="{{ $district }}" 
                                        data-region="{{ $regionName }}"
                                        @selected(old('district', $employee->district ?? '') === $district)>
                                        {{ $district }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <!-- Store districts data in JavaScript for dynamic filtering -->
                    <script type="text/javascript">
                        window.tanzaniaDistrictsData = @json($tanzaniaDistricts ?? []);
                    </script>
                    <!-- Text input for other countries -->
                    <input type="text" name="district" class="form-control" id="district_input" 
                        value="{{ old('district', $employee->district ?? '') }}" 
                        placeholder="Enter district/city">
                    @error('district')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Current Physical Location {!! $required !!}</label>
                    <textarea name="current_physical_location" class="form-control" rows="3" 
                        placeholder="Enter detailed physical address (e.g., Street name, Building number, Ward, etc.)" required>{{ old('current_physical_location', $employee->current_physical_location ?? '') }}</textarea>
                    @error('current_physical_location')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Information Section -->
    <div class="col-12">
        <div class="form-section">
            <div class="form-section-header">
                <i class="bx bx-phone"></i>
                <h6>Contact Information</h6>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $employee->email ?? '') }}">
                    @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone Number {!! $required !!}</label>
                    <input type="text" name="phone_number" class="form-control"
                        value="{{ old('phone_number', $employee->phone_number ?? '') }}" required>
                    @error('phone_number')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Employment Information Section -->
    <div class="col-12">
        <div class="form-section">
            <div class="form-section-header">
                <i class="bx bx-briefcase"></i>
                <h6>Employment Information</h6>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">
                        Basic Salary {!! $required !!}
                        <i class="bx bx-help-circle text-muted" data-bs-toggle="tooltip" 
                           title="Initial/default salary. Payroll uses: 1) Salary Structure (if exists), 2) Contract salary, 3) This basic salary."></i>
                    </label>
                    <input type="number" step="0.01" name="basic_salary" class="form-control" id="basic_salary_input"
                        value="{{ old('basic_salary', $employee->basic_salary ?? '') }}" required>
                    @error('basic_salary')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    <small class="text-muted">
                        <i class="bx bx-info-circle me-1"></i>
                        <strong>Priority:</strong> Salary Structure → Contract → Basic Salary
                    </small>
                    <div id="grade_salary_info" class="mt-2" style="display: none;">
                        <small class="text-info">
                            <i class="bx bx-info-circle me-1"></i>
                            <span id="grade_salary_text"></span>
                        </small>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Identity Document Type {!! $required !!}</label>
                    <input type="text" name="identity_document_type" class="form-control"
                        value="{{ old('identity_document_type', $employee->identity_document_type ?? '') }}" required>
                    @error('identity_document_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Identity Number {!! $required !!}</label>
                    <input type="text" name="identity_number" class="form-control"
                        value="{{ old('identity_number', $employee->identity_number ?? '') }}" required>
                    @error('identity_number')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Employment Type {!! $required !!}</label>
                    <select name="employment_type" class="form-select select2-single" required>
                        @php($et = old('employment_type', $employee->employment_type ?? ''))
                        <option value="">-- Select --</option>
                        <option value="full_time" @selected($et === 'full_time')>Full Time</option>
                        <option value="part_time" @selected($et === 'part_time')>Part Time</option>
                        <option value="contract" @selected($et === 'contract')>Contract</option>
                        <option value="casual" @selected($et === 'casual')>Casual workers</option>
                        <option value="intern" @selected($et === 'intern')>Intern</option>
                    </select>
                    @error('employment_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date of Employment {!! $required !!}</label>
                    <input type="date" name="date_of_employment" class="form-control"
                        value="{{ old('date_of_employment', isset($employee) ? optional($employee->date_of_employment)->format('Y-m-d') : '') }}"
                        required>
                    @error('date_of_employment')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select select2-single" id="branch_select">
                        <option value="">-- None --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" @selected(old('branch_id', $employee->branch_id ?? ($currentBranchId ?? '')) == $b->id)>{{ $b->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('branch_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select select2-single" id="department_select">
                        <option value="">-- None --</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" 
                                data-branch-id="{{ $d->branch_id ?? '' }}"
                                @selected(old('department_id', $employee->department_id ?? '') == $d->id)>
                                {{ $d->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Position</label>
                    <select name="position_id" class="form-select select2-single" id="position_select">
                        <option value="">-- None --</option>
                        @foreach($positions as $p)
                            <option value="{{ $p->id }}" 
                                    data-grade-id="{{ $p->grade_id ?? '' }}"
                                    data-grade-code="{{ $p->grade ? $p->grade->grade_code : '' }}"
                                    data-grade-name="{{ $p->grade ? $p->grade->grade_name : '' }}"
                                    data-grade-min="{{ $p->grade && $p->grade->minimum_salary ? $p->grade->minimum_salary : '' }}"
                                    data-grade-max="{{ $p->grade && $p->grade->maximum_salary ? $p->grade->maximum_salary : '' }}"
                                    data-budgeted-salary="{{ $p->budgeted_salary ?? '' }}"
                                    @selected(old('position_id', $employee->position_id ?? '') == $p->id)>
                                {{ $p->title }}
                                @if($p->grade)
                                    ({{ $p->grade->grade_code }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('position_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    <div id="position_grade_info" class="mt-2" style="display: none;">
                        <small class="text-muted">
                            <i class="bx bx-info-circle me-1"></i>
                            <span id="position_grade_text"></span>
                        </small>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status {!! $required !!}</label>
                    <select name="status" class="form-select select2-single" required>
                        @php($s = old('status', $employee->status ?? 'active'))
                        <option value="active" @selected($s === 'active')>Active</option>
                        <option value="inactive" @selected($s === 'inactive')>Inactive</option>
                        <option value="terminated" @selected($s === 'terminated')>Terminated</option>
                        <option value="on_leave" @selected($s === 'on_leave')>On Leave</option>
                    </select>
                    @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" id="include_in_payroll" name="include_in_payroll" value="1"
                            {{ old('include_in_payroll', $employee->include_in_payroll ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="include_in_payroll">Include in Payroll</label>
                    </div>
                    @error('include_in_payroll')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            
            @if(!isset($employee))
            <div class="alert alert-info mt-3 mb-0">
                <i class="bx bx-info-circle me-2"></i>
                <strong>After creating this employee:</strong>
                <ul class="mb-0 mt-2">
                    <li>Assign a <strong>Salary Structure</strong> to define detailed salary components (recommended for accurate payroll)</li>
                    <li>Create a <strong>Contract</strong> if the employee has a contract with specific terms</li>
                    <li>Set up <strong>HESLB Loan</strong> if the employee has a student loan</li>
                </ul>
            </div>
            @endif
        </div>
    </div>

    <!-- Banking Information Section -->
    <div class="col-12">
        <div class="form-section">
            <div class="form-section-header">
                <i class="bx bx-credit-card"></i>
                <h6>Banking & Tax Information</h6>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">TIN</label>
                    <input type="text" name="tin" class="form-control" value="{{ old('tin', $employee->tin ?? '') }}">
                    @error('tin')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="bank_name" class="form-control"
                        value="{{ old('bank_name', $employee->bank_name ?? '') }}">
                    @error('bank_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Bank Account Number</label>
                    <input type="text" name="bank_account_number" class="form-control"
                        value="{{ old('bank_account_number', $employee->bank_account_number ?? '') }}">
                    @error('bank_account_number')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

</div>

<script nonce="{{ $cspNonce ?? '' }}">
    // Benefits & Deductions section removed - trade union functions no longer needed

    // Function to toggle fields based on checkbox state
    function setupToggle(checkboxId, fieldsId) {
        const cb = document.getElementById(checkboxId);
        const fields = document.getElementById(fieldsId);
        if (!cb || !fields) return;
        
        // Handler function to show/hide and enable/disable fields
        const handler = () => {
            const inputs = fields.querySelectorAll('input, select, textarea');
            if (cb.checked) {
                fields.classList.remove('d-none');
                inputs.forEach(input => {
                    input.disabled = false;
                    input.removeAttribute('readonly');
                });
            } else {
                fields.classList.add('d-none');
                inputs.forEach(input => {
                    input.disabled = true;
                });
            }
        };
        
        // Add event listener for change events
        cb.addEventListener('change', handler);
        
        // Set initial state
        handler();
    }

    // Trade union refresh function removed - Benefits & Deductions section removed

    // Function to filter departments by branch
    function filterDepartmentsByBranch() {
        const branchSelect = document.getElementById('branch_select');
        const departmentSelect = document.getElementById('department_select');
        
        if (!branchSelect || !departmentSelect) return;
        
        // Get current branch ID (from session/config) or selected branch
        const currentBranchId = @json(current_branch_id());
        const selectedBranchId = branchSelect.value || currentBranchId;
        const allOptions = departmentSelect.querySelectorAll('option');
        const currentValue = departmentSelect.value;
        
        // Show/hide options based on branch selection
        allOptions.forEach(option => {
            if (option.value === '') {
                // Always show the "-- None --" option
                option.style.display = '';
            } else {
                const optionBranchId = option.getAttribute('data-branch-id');
                // Show if: no branch selected, or department belongs to selected branch, or department has no branch (null)
                if (!selectedBranchId || optionBranchId === selectedBranchId || !optionBranchId || optionBranchId === '') {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
        });
        
        // If current selected department doesn't belong to selected branch, clear it
        if (currentValue) {
            const selectedOption = departmentSelect.querySelector(`option[value="${currentValue}"]`);
            if (selectedOption && selectedOption.style.display === 'none') {
                departmentSelect.value = '';
                // Update Select2 if initialized
                if ($(departmentSelect).hasClass('select2-hidden-accessible')) {
                    $(departmentSelect).trigger('change');
                }
            }
        }
        
        // Update Select2 dropdown
        if ($(departmentSelect).hasClass('select2-hidden-accessible')) {
            $(departmentSelect).trigger('change.select2');
        }
    }

    // Function to update position and grade information
    function updatePositionInfo() {
        const positionSelect = document.getElementById('position_select');
        const gradeInfo = document.getElementById('position_grade_info');
        const gradeText = document.getElementById('position_grade_text');
        const salaryInput = document.getElementById('basic_salary_input');
        const gradeSalaryInfo = document.getElementById('grade_salary_info');
        const gradeSalaryText = document.getElementById('grade_salary_text');
        
        if (!positionSelect || !gradeInfo || !gradeText) return;
        
        const selectedOption = positionSelect.options[positionSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            gradeInfo.style.display = 'none';
            gradeSalaryInfo.style.display = 'none';
            return;
        }
        
        const gradeCode = selectedOption.getAttribute('data-grade-code');
        const gradeName = selectedOption.getAttribute('data-grade-name');
        const gradeMin = selectedOption.getAttribute('data-grade-min');
        const gradeMax = selectedOption.getAttribute('data-grade-max');
        const budgetedSalary = selectedOption.getAttribute('data-budgeted-salary');
        
        let infoText = '';
        if (gradeCode && gradeName) {
            infoText = `<strong>Job Grade:</strong> ${gradeCode} - ${gradeName}`;
            if (gradeMin || gradeMax) {
                const min = gradeMin ? parseFloat(gradeMin).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : 'N/A';
                const max = gradeMax ? parseFloat(gradeMax).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : 'N/A';
                infoText += ` | <strong>Salary Range:</strong> ${min} - ${max} TZS`;
            }
            gradeInfo.style.display = 'block';
            gradeText.innerHTML = infoText;
            
            // Show salary range info
            if (gradeMin || gradeMax) {
                const min = gradeMin ? parseFloat(gradeMin) : null;
                const max = gradeMax ? parseFloat(gradeMax) : null;
                let salaryInfo = '';
                if (min && max) {
                    salaryInfo = `Recommended salary range for this grade: ${min.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} - ${max.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} TZS`;
                } else if (min) {
                    salaryInfo = `Minimum salary for this grade: ${min.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} TZS`;
                } else if (max) {
                    salaryInfo = `Maximum salary for this grade: ${max.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} TZS`;
                }
                if (salaryInfo) {
                    gradeSalaryInfo.style.display = 'block';
                    gradeSalaryText.textContent = salaryInfo;
                } else {
                    gradeSalaryInfo.style.display = 'none';
                }
            } else {
                gradeSalaryInfo.style.display = 'none';
            }
            
            // Show budgeted salary if available
            if (budgetedSalary) {
                const budgeted = parseFloat(budgetedSalary);
                if (budgeted > 0) {
                    const budgetedText = `Budgeted salary for this position: ${budgeted.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} TZS`;
                    if (gradeSalaryInfo.style.display === 'none') {
                        gradeSalaryInfo.style.display = 'block';
                        gradeSalaryText.textContent = budgetedText;
                    } else {
                        gradeSalaryText.textContent += ` | ${budgetedText}`;
                    }
                }
            }
        } else {
            gradeInfo.style.display = 'none';
            gradeSalaryInfo.style.display = 'none';
        }
    }

    // Function to handle country change and toggle region/district fields
    function handleCountryChange() {
        const countrySelect = document.getElementById('country_select');
        const regionSelect = document.getElementById('region_select');
        const regionInput = document.getElementById('region_input');
        const districtSelect = document.getElementById('district_select');
        const districtInput = document.getElementById('district_input');
        
        if (!countrySelect) return;
        
        // Get value from Select2 if it's initialized, otherwise from native select
        const selectedCountry = $(countrySelect).hasClass('select2-hidden-accessible') 
            ? $(countrySelect).val() 
            : countrySelect.value;
        const isTanzania = selectedCountry === 'Tanzania';
        
        if (isTanzania) {
            // Show dropdowns, hide text inputs
            if (regionSelect) {
                regionSelect.style.display = '';
                regionSelect.setAttribute('required', 'required');
                regionSelect.setAttribute('name', 'region');
            }
            if (regionInput) {
                regionInput.style.display = 'none';
                regionInput.removeAttribute('required');
                regionInput.removeAttribute('name');
            }
            
            if (districtSelect) {
                districtSelect.style.display = '';
                districtSelect.setAttribute('required', 'required');
                districtSelect.setAttribute('name', 'district');
            }
            if (districtInput) {
                districtInput.style.display = 'none';
                districtInput.removeAttribute('required');
                districtInput.removeAttribute('name');
            }
            
            // Initialize Select2 for region and district if not already initialized
            if (regionSelect) {
                if (!$(regionSelect).hasClass('select2-hidden-accessible')) {
                    $(regionSelect).select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        placeholder: '-- Select Region --',
                        allowClear: true
                    });
                }
                
                // Always bind change event for region to filter districts (remove old handlers first to avoid duplicates)
                // Listen to both regular change and Select2 specific events
                $(regionSelect).off('change.regionFilter select2:select.regionFilter')
                    .on('change.regionFilter select2:select.regionFilter', function(e) {
                        console.log('Region change event fired!', $(this).val());
                        // Use setTimeout to ensure Select2 value is updated
                        setTimeout(function() {
                            filterDistrictsByRegion();
                        }, 100);
                    });
            }
            
            if (districtSelect && !$(districtSelect).hasClass('select2-hidden-accessible')) {
                $(districtSelect).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: '-- Select District --',
                    allowClear: true
                });
            }
            
            // Filter districts based on selected region
            filterDistrictsByRegion();
        } else {
            // Show text inputs, hide dropdowns
            if (regionSelect) {
                regionSelect.style.display = 'none';
                regionSelect.removeAttribute('required');
                regionSelect.removeAttribute('name');
                // Destroy Select2 if initialized
                if ($(regionSelect).hasClass('select2-hidden-accessible')) {
                    $(regionSelect).select2('destroy');
                }
            }
            if (regionInput) {
                regionInput.style.display = '';
                regionInput.setAttribute('required', 'required');
                regionInput.setAttribute('name', 'region');
            }
            
            if (districtSelect) {
                districtSelect.style.display = 'none';
                districtSelect.removeAttribute('required');
                districtSelect.removeAttribute('name');
                // Destroy Select2 if initialized
                if ($(districtSelect).hasClass('select2-hidden-accessible')) {
                    $(districtSelect).select2('destroy');
                }
            }
            if (districtInput) {
                districtInput.style.display = '';
                districtInput.setAttribute('required', 'required');
                districtInput.setAttribute('name', 'district');
            }
        }
    }
    
    // Function to filter districts by selected region
    function filterDistrictsByRegion() {
        console.log('=== filterDistrictsByRegion START ===');
        const regionSelect = document.getElementById('region_select');
        const districtSelect = document.getElementById('district_select');
        
        if (!regionSelect || !districtSelect) {
            console.error('Missing regionSelect or districtSelect');
            return;
        }
        
        // Get value from Select2 if initialized, otherwise from native select
        let selectedRegion = '';
        if ($(regionSelect).hasClass('select2-hidden-accessible')) {
            selectedRegion = $(regionSelect).val() || '';
        } else {
            selectedRegion = regionSelect.value || '';
        }
        
        console.log('Selected Region:', selectedRegion);
        
        // Get current district value before rebuilding
        const currentDistrictValue = $(districtSelect).hasClass('select2-hidden-accessible') 
            ? $(districtSelect).val() 
            : districtSelect.value;
        
        // Check if we have districts data
        if (typeof window.tanzaniaDistrictsData === 'undefined') {
            console.error('tanzaniaDistrictsData not found');
            return;
        }
        
        // Clear all existing options except the first one
        $(districtSelect).find('option:not(:first)').remove();
        $(districtSelect).find('optgroup').remove();
        
        if (!selectedRegion || selectedRegion === '') {
            console.log('No region selected - showing all districts');
            // If no region selected, show all districts
            Object.keys(window.tanzaniaDistrictsData).forEach(regionName => {
                const districts = window.tanzaniaDistrictsData[regionName];
                const optgroup = $('<optgroup>').attr('label', regionName).attr('data-region', regionName);
                districts.forEach(district => {
                    const option = $('<option>').val(district).text(district).attr('data-region', regionName);
                    optgroup.append(option);
                });
                $(districtSelect).append(optgroup);
            });
        } else {
            console.log('Filtering for region:', selectedRegion);
            // Only show districts for the selected region
            if (window.tanzaniaDistrictsData[selectedRegion]) {
                const districts = window.tanzaniaDistrictsData[selectedRegion];
                const optgroup = $('<optgroup>').attr('label', selectedRegion).attr('data-region', selectedRegion);
                districts.forEach(district => {
                    const option = $('<option>').val(district).text(district).attr('data-region', selectedRegion);
                    if (district === currentDistrictValue) {
                        option.prop('selected', true);
                    }
                    optgroup.append(option);
                });
                $(districtSelect).append(optgroup);
                console.log(`Added ${districts.length} districts for region ${selectedRegion}`);
            } else {
                console.warn('No districts found for region:', selectedRegion);
            }
        }
        
        // Reinitialize Select2 to refresh the options
        if ($(districtSelect).hasClass('select2-hidden-accessible')) {
            console.log('Reinitializing Select2 for district dropdown');
            $(districtSelect).select2('destroy');
            $(districtSelect).select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Select District --',
                allowClear: true
            });
            // Restore value if it was valid
            if (currentDistrictValue) {
                $(districtSelect).val(currentDistrictValue).trigger('change');
            }
            console.log('Select2 reinitialized');
        }
        console.log('=== filterDistrictsByRegion END ===');
    }

    // Initialize everything on page load
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Select2 for all select2-single elements (except region/district which will be initialized conditionally)
        $('.select2-single').not('#region_select, #district_select').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: function() {
                return $(this).data('placeholder') || '-- Select --';
            },
            allowClear: true
        });
        
        // Setup country change listener
        const countrySelect = document.getElementById('country_select');
        if (countrySelect) {
            // Use jQuery change event for Select2 compatibility
            $(countrySelect).on('change select2:select', function() {
                handleCountryChange();
            });
            // Initialize on page load based on current selection
            handleCountryChange();
        }
        
        // Also bind region change listener directly (will work even if Select2 is already initialized)
        const regionSelect = document.getElementById('region_select');
        if (regionSelect) {
            $(regionSelect).on('change select2:select', function() {
                setTimeout(function() {
                    filterDistrictsByRegion();
                }, 100);
            });
        }
        
        // Setup toggle functionality for checkboxes
        setupToggle('has_nhif', 'nhif_fields');
        setupToggle('has_pension', 'pension_fields');
        setupToggle('has_trade_union', 'union_fields');
        setupToggle('has_wcf', 'wcf_fields');
        setupToggle('has_heslb', 'heslb_fields');
        setupToggle('has_sdl', 'sdl_fields');
        
        // Setup position change listener
        const positionSelect = document.getElementById('position_select');
        if (positionSelect) {
            positionSelect.addEventListener('change', updatePositionInfo);
            updatePositionInfo(); // Initial call
        }
        
        // Setup branch change listener to filter departments
        const branchSelect = document.getElementById('branch_select');
        if (branchSelect) {
            branchSelect.addEventListener('change', function() {
                filterDepartmentsByBranch();
                // Trigger Select2 update after filtering
                $('#department_select').trigger('change');
            });
            // Filter on initial load based on current branch
            filterDepartmentsByBranch();
        }
        
        // Trade union setup removed - Benefits & Deductions section removed

        // Email validation
        const emailInput = document.querySelector('input[name="email"]');
        if (emailInput) {
            let emailTimeout;
            emailInput.addEventListener('blur', function() {
                clearTimeout(emailTimeout);
                emailTimeout = setTimeout(() => checkEmailUnique(this), 500);
            });
        }

        // Phone validation
        const phoneInput = document.querySelector('input[name="phone_number"]');
        if (phoneInput) {
            let phoneTimeout;
            phoneInput.addEventListener('blur', function() {
                clearTimeout(phoneTimeout);
                phoneTimeout = setTimeout(() => checkPhoneUnique(this), 500);
            });
        }
    });

    // Function to check email uniqueness
    function checkEmailUnique(input) {
        const email = input.value.trim();
        if (!email) return;

        const employeeId = @json(isset($employee) ? $employee->id : null);
        
        fetch('{{ route("hr.employees.check-email") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                email: email,
                employee_id: employeeId
            })
        })
        .then(response => response.json())
        .then(data => {
            removeValidationMessage(input);
            if (!data.available) {
                showValidationError(input, data.message);
            }
        })
        .catch(error => {
            console.error('Email validation error:', error);
        });
    }

    // Function to check phone uniqueness  
    function checkPhoneUnique(input) {
        const phone = input.value.trim();
        if (!phone) return;

        const employeeId = @json(isset($employee) ? $employee->id : null);
        
        fetch('{{ route("hr.employees.check-phone") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                phone: phone,
                employee_id: employeeId
            })
        })
        .then(response => response.json())
        .then(data => {
            removeValidationMessage(input);
            if (!data.available) {
                showValidationError(input, data.message);
            }
        })
        .catch(error => {
            console.error('Phone validation error:', error);
        });
    }

    // Function to show validation error
    function showValidationError(input, message) {
        input.classList.add('is-invalid');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-danger small validation-message';
        errorDiv.textContent = message;
        input.parentNode.appendChild(errorDiv);
    }

    // Function to remove validation message
    function removeValidationMessage(input) {
        input.classList.remove('is-invalid');
        const existingError = input.parentNode.querySelector('.validation-message');
        if (existingError) {
            existingError.remove();
        }
    }
</script>