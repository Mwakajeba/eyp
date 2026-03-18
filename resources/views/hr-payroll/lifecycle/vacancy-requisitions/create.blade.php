@extends('layouts.main')

@section('title', 'Create Vacancy Requisition')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Vacancy Requisitions', 'url' => route('hr.vacancy-requisitions.index'), 'icon' => 'bx bx-file-blank'],
                ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
            ]" />
            <h6 class="mb-0 text-uppercase">CREATE VACANCY REQUISITION</h6>
            <hr />
            
            <div class="row">
                <!-- Left: Form -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('hr.vacancy-requisitions.store') }}" method="POST">
                                @csrf

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="position_id" class="form-label">
                                                <i class="bx bx-briefcase me-1"></i>Position <span class="text-danger">*</span>
                                            </label>
                                            <select name="position_id" id="position_id" class="form-select select2-single @error('position_id') is-invalid @enderror" required>
                                                <option value="">Select Position</option>
                                                @foreach($positions as $position)
                                                    <option value="{{ $position->id }}" 
                                                            data-title="{{ $position->title }}"
                                                            {{ old('position_id') == $position->id ? 'selected' : '' }}>
                                                        {{ $position->title }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('position_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Select the position this vacancy is for.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="department_id" class="form-label">
                                                <i class="bx bx-building me-1"></i>Department
                                            </label>
                                            <select name="department_id" id="department_id" class="form-select select2-single @error('department_id') is-invalid @enderror">
                                                <option value="">Select Department</option>
                                                @foreach($departments as $department)
                                                    <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                                        {{ $department->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('department_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Optional: Select the department for this vacancy.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="job_title" class="form-label">
                                                <i class="bx bx-pencil me-1"></i>Job Title <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="job_title" id="job_title" class="form-control @error('job_title') is-invalid @enderror" value="{{ old('job_title') }}" required>
                                            @error('job_title')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Enter the job title for this vacancy requisition.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="number_of_positions" class="form-label">
                                                <i class="bx bx-group me-1"></i>Number of Positions <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" name="number_of_positions" id="number_of_positions" class="form-control @error('number_of_positions') is-invalid @enderror" value="{{ old('number_of_positions', 1) }}" min="1" required>
                                            @error('number_of_positions')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Specify how many positions need to be filled.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">
                                                <i class="bx bx-check-circle me-1"></i>Status <span class="text-danger">*</span>
                                            </label>
                                            <select name="status" id="status" class="form-select select2-single @error('status') is-invalid @enderror" required>
                                                <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                                                <option value="pending_approval" {{ old('status') == 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                                                <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Set the initial status of this requisition.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="budgeted_salary_min" class="form-label">
                                                <i class="bx bx-money me-1"></i>Budgeted Salary (Min)
                                            </label>
                                            <input type="number" name="budgeted_salary_min" id="budgeted_salary_min" class="form-control @error('budgeted_salary_min') is-invalid @enderror" value="{{ old('budgeted_salary_min') }}" step="0.01" min="0" placeholder="0.00">
                                            @error('budgeted_salary_min')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Optional: Minimum salary budget for this position.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="budgeted_salary_max" class="form-label">
                                                <i class="bx bx-money me-1"></i>Budgeted Salary (Max)
                                            </label>
                                            <input type="number" name="budgeted_salary_max" id="budgeted_salary_max" class="form-control @error('budgeted_salary_max') is-invalid @enderror" value="{{ old('budgeted_salary_max') }}" step="0.01" min="0" placeholder="0.00">
                                            @error('budgeted_salary_max')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Optional: Maximum salary budget for this position.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="opening_date" class="form-label">
                                                <i class="bx bx-calendar me-1"></i>Application Opening Date
                                            </label>
                                            <input type="date" name="opening_date" id="opening_date" class="form-control @error('opening_date') is-invalid @enderror" value="{{ old('opening_date') }}">
                                            @error('opening_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Optional: Date when applications will start being accepted (internal application period).
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="closing_date" class="form-label">
                                                <i class="bx bx-calendar-check me-1"></i>Application Closing Date
                                            </label>
                                            <input type="date" name="closing_date" id="closing_date" class="form-control @error('closing_date') is-invalid @enderror" value="{{ old('closing_date') }}">
                                            @error('closing_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Optional: Deadline for accepting applications (last day to apply).
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="job_description" class="form-label">
                                                <i class="bx bx-file me-1"></i>Job Description
                                            </label>
                                            <textarea name="job_description" id="job_description" class="form-control @error('job_description') is-invalid @enderror" rows="4" placeholder="Enter detailed job description...">{{ old('job_description') }}</textarea>
                                            @error('job_description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Optional: Provide a comprehensive description of the role and responsibilities.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="requirements" class="form-label">
                                                <i class="bx bx-list-check me-1"></i>Requirements
                                            </label>
                                            <textarea name="requirements" id="requirements" class="form-control @error('requirements') is-invalid @enderror" rows="4" placeholder="Enter job requirements, qualifications, and skills...">{{ old('requirements') }}</textarea>
                                            @error('requirements')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Optional: List the qualifications, skills, and experience required for this position.
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Blueprint Enhancement Fields -->
                                    <div class="col-12 mt-3">
                                        <hr>
                                        <h6 class="mb-3 text-primary">
                                            <i class="bx bx-info-circle me-1"></i>Additional Information
                                        </h6>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="hiring_justification" class="form-label">
                                                <i class="bx bx-edit me-1"></i>Hiring Justification
                                            </label>
                                            <textarea name="hiring_justification" id="hiring_justification" class="form-control @error('hiring_justification') is-invalid @enderror" rows="3" placeholder="Explain why this position needs to be filled...">{{ old('hiring_justification') }}</textarea>
                                            @error('hiring_justification')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Optional: Provide justification for this hiring request (audit evidence).
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="recruitment_type" class="form-label">
                                                <i class="bx bx-group me-1"></i>Recruitment Type
                                            </label>
                                            <select name="recruitment_type" id="recruitment_type" class="form-select select2-single @error('recruitment_type') is-invalid @enderror">
                                                <option value="external" {{ old('recruitment_type', 'external') == 'external' ? 'selected' : '' }}>External</option>
                                                <option value="internal" {{ old('recruitment_type') == 'internal' ? 'selected' : '' }}>Internal</option>
                                                <option value="both" {{ old('recruitment_type') == 'both' ? 'selected' : '' }}>Both (Internal & External)</option>
                                            </select>
                                            @error('recruitment_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Select whether this vacancy is for internal, external, or both recruitment.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="contract_period_months" class="form-label">
                                                <i class="bx bx-calendar me-1"></i>Contract Period (Months)
                                            </label>
                                            <input type="number" name="contract_period_months" id="contract_period_months" class="form-control @error('contract_period_months') is-invalid @enderror" value="{{ old('contract_period_months') }}" min="1" placeholder="e.g., 12, 24, 36">
                                            @error('contract_period_months')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Optional: Duration of the employment contract in months.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="budget_line_id" class="form-label">
                                                <i class="bx bx-money me-1"></i>Budget Line
                                            </label>
                                            <select name="budget_line_id" id="budget_line_id" class="form-select select2-single @error('budget_line_id') is-invalid @enderror">
                                                <option value="">Select Budget Line</option>
                                                @foreach($budgetLines as $budgetLine)
                                                    <option value="{{ $budgetLine->id }}" {{ old('budget_line_id') == $budgetLine->id ? 'selected' : '' }}>
                                                        {{ $budgetLine->account->account_code ?? 'N/A' }} - {{ $budgetLine->account->account_name ?? 'N/A' }} ({{ number_format($budgetLine->amount, 2) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('budget_line_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Optional: Select the budget line for donor compliance.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="project_grant_code" class="form-label">
                                                <i class="bx bx-code me-1"></i>Project/Grant Code
                                            </label>
                                            <input type="text" name="project_grant_code" id="project_grant_code" class="form-control @error('project_grant_code') is-invalid @enderror" value="{{ old('project_grant_code') }}" placeholder="e.g., PROJ-2025-001">
                                            @error('project_grant_code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Optional: Enter the project or grant code for donor compliance.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check form-switch mt-4">
                                                <input class="form-check-input" type="checkbox" name="is_publicly_posted" id="is_publicly_posted" value="1" {{ old('is_publicly_posted') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_publicly_posted">
                                                    <i class="bx bx-globe me-1"></i>Post Publicly
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                <i class="bx bx-info-circle me-1"></i>Enable to make this vacancy visible on the public job portal.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6" id="posting_dates_section" style="display: {{ old('is_publicly_posted') ? 'block' : 'none' }};">
                                        <div class="mb-3">
                                            <label for="posting_start_date" class="form-label">
                                                <i class="bx bx-calendar me-1"></i>Public Posting Start Date
                                            </label>
                                            <input type="date" name="posting_start_date" id="posting_start_date" class="form-control @error('posting_start_date') is-invalid @enderror" value="{{ old('posting_start_date') }}">
                                            @error('posting_start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Date when job will appear on public job portal.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6" id="posting_end_date_section" style="display: {{ old('is_publicly_posted') ? 'block' : 'none' }};">
                                        <div class="mb-3">
                                            <label for="posting_end_date" class="form-label">
                                                <i class="bx bx-calendar-check me-1"></i>Public Posting End Date
                                            </label>
                                            <input type="date" name="posting_end_date" id="posting_end_date" class="form-control @error('posting_end_date') is-invalid @enderror" value="{{ old('posting_end_date') }}">
                                            @error('posting_end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">
                                                <i class="bx bx-info-circle me-1"></i>Date when job will be removed from public portal.
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Eligibility & Validation Rules Section -->
                                    <div class="col-12 mt-4">
                                        <hr>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="text-primary mb-0">
                                                <i class="bx bx-task me-1"></i>Eligibility & Validation Rules
                                            </h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-rule-btn">
                                                <i class="bx bx-plus me-1"></i>Add Rule
                                            </button>
                                        </div>
                                        <div class="alert alert-light border small py-2 mb-3">
                                            <i class="bx bx-info-circle me-1 text-primary"></i>
                                            <strong>Rule Tips:</strong> Use <strong>Mandatory</strong> for "Hard Stops" (rejection). <strong>Weight</strong> affects candidate ranking. For <strong>IN</strong> or <strong>BETWEEN</strong>, use comma-separated values (e.g., <code>5, 10</code>).
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm" id="rules-table" style="display: none;">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width: 20%;">Rule Category</th>
                                                        <th style="width: 15%;">Operator</th>
                                                        <th style="width: 25%;">Required Value</th>
                                                        <th style="width: 10%;">Mandatory</th>
                                                        <th style="width: 10%;">Weight (%)</th>
                                                        <th style="width: 15%;">Applies To</th>
                                                        <th style="width: 5%;"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="rules-container">
                                                    <!-- Rules will be added here -->
                                                </tbody>
                                            </table>
                                        </div>
                                        <div id="no-rules-msg" class="text-center py-3 bg-light border rounded mb-3">
                                            <p class="text-muted mb-0 small italic">No eligibility rules configured yet. Click "Add Rule" to define criteria for this vacancy.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info border-info">
                                    <i class="bx bx-info-circle me-2"></i>
                                    <strong>Note:</strong> This will create a vacancy requisition with the status you selected. You can update the requisition and proceed with the hiring process after creation.
                                </div>

                                <div class="mt-3 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i>Create Requisition
                                    </button>
                                    <a href="{{ route('hr.vacancy-requisitions.index') }}" class="btn btn-secondary">
                                        <i class="bx bx-x me-1"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right: Guidelines -->
                <div class="col-lg-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="mb-3">
                                <i class="bx bx-help-circle text-primary me-1"></i>How to Create Vacancy Requisition
                            </h6>
                            <hr>
                            
                            <div class="mb-3">
                                <h6 class="text-primary">
                                    <i class="bx bx-briefcase me-1"></i>1. Select Position & Basic Details
                                </h6>
                                <p class="small mb-2">Choose the position and essential information:</p>
                                <ul class="small text-muted mb-0">
                                    <li>Select the position from the dropdown (auto-fills job title)</li>
                                    <li>Choose the department (optional)</li>
                                    <li>Job title is auto-filled but can be edited</li>
                                    <li>Specify number of positions needed</li>
                                    <li>Select initial status (Draft/Pending Approval/Approved)</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-primary">
                                    <i class="bx bx-calendar me-1"></i>2. Set Dates & Budget
                                </h6>
                                <p class="small mb-2">Configure timeline and compensation:</p>
                                <ul class="small text-muted mb-0">
                                    <li><strong>Application Opening/Closing:</strong> Period when candidates can submit applications (internal timeline)</li>
                                    <li><strong>Public Posting Dates:</strong> When job appears on public portal (only if "Post Publicly" is enabled)</li>
                                    <li>Define salary budget range (min/max)</li>
                                    <li>Note: Posting dates typically start before or on the application opening date</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-primary">
                                    <i class="bx bx-file me-1"></i>3. Add Job Details
                                </h6>
                                <p class="small mb-2">Provide comprehensive job information:</p>
                                <ul class="small text-muted mb-0">
                                    <li>Write detailed job description</li>
                                    <li>List required qualifications and requirements</li>
                                    <li>Specify skills and experience needed</li>
                                    <li>Include any special requirements</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-primary">
                                    <i class="bx bx-info-circle me-1"></i>4. Additional Information
                                </h6>
                                <p class="small mb-2">Financial control and compliance:</p>
                                <ul class="small text-muted mb-0">
                                    <li><strong>Hiring Justification:</strong> Explain why this position is needed (audit evidence)</li>
                                    <li><strong>Recruitment Type:</strong> Internal, External, or Both</li>
                                    <li><strong>Contract Period:</strong> Duration in months</li>
                                    <li><strong>Budget Line:</strong> For donor compliance</li>
                                    <li><strong>Project/Grant Code:</strong> Link to donor projects</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-primary">
                                    <i class="bx bx-globe me-1"></i>5. Public Posting (Optional)
                                </h6>
                                <p class="small mb-2">Make vacancy visible on public portal:</p>
                                <ul class="small text-muted mb-0">
                                    <li>Enable "Post Publicly" checkbox to show on job portal</li>
                                    <li><strong>Public Posting Dates:</strong> When job will be visible on public portal (external visibility)</li>
                                    <li><strong>Application Dates:</strong> When candidates can actually apply (may differ from posting dates)</li>
                                    <li>Posting typically starts before or on application opening date</li>
                                    <li>Can be updated later if needed</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-primary">
                                    <i class="bx bx-check-circle me-1"></i>6. Eligibility & Validation Rules
                                </h6>
                                <p class="small mb-2">Define strict criteria for automatic filtering:</p>
                                <ul class="small text-muted mb-0">
                                    <li><strong>Mandatory Rules:</strong> Applicants failing these are automatically rejected.</li>
                                    <li><strong>Optional Rules:</strong> Affect the total score but don't disqualify.</li>
                                    <li><strong>Weight (%):</strong> Assign percentages to rules for automated candidate ranking.</li>
                                    <li><strong>Value Tips:</strong> For <strong>IN</strong> or <strong>BETWEEN</strong>, separate values with commas (e.g., <code>Degree, Diploma</code> or <code>18, 60</code>).</li>
                                    <li><strong>Locking:</strong> Rules cannot be changed once the vacancy is published.</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-primary">
                                    <i class="bx bx-save me-1"></i>7. Review & Submit
                                </h6>
                                <p class="small mb-2">Finalize and create requisition:</p>
                                <ul class="small text-muted mb-0">
                                    <li>Review all entered information and rules</li>
                                    <li>Click "Create Requisition" to save</li>
                                    <li>After creation, submit for approval if needed</li>
                                </ul>
                            </div>

                            <hr>

                            <div class="alert alert-info mb-0">
                                <small>
                                    <i class="bx bx-info-circle text-info me-1"></i>
                                    <strong>Tip:</strong> Use the searchable dropdowns to quickly find positions, departments, and budget lines. The job title will auto-fill when you select a position, but you can edit it if needed.
                                </small>
                            </div>
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
    // Initialize Select2 for all single select dropdowns
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: function() {
            return $(this).data('placeholder') || 'Select an option...';
        }
    });
    
    // Track if job title was auto-filled (so we can update it when position changes)
    let jobTitleAutoFilled = false;
    
    // Set minimum date for closing date based on opening date
    $('#opening_date').on('change', function() {
        if ($(this).val()) {
            $('#closing_date').attr('min', $(this).val());
        }
    });
    
    // Toggle posting dates visibility based on public posting checkbox
    $('#is_publicly_posted').on('change', function() {
        if ($(this).is(':checked')) {
            $('#posting_dates_section, #posting_end_date_section').slideDown();
        } else {
            $('#posting_dates_section, #posting_end_date_section').slideUp();
            $('#posting_start_date, #posting_end_date').val('');
        }
    });
    
    // Set minimum date for posting end date based on posting start date
    $('#posting_start_date').on('change', function() {
        if ($(this).val()) {
            $('#posting_end_date').attr('min', $(this).val());
        }
    });
    
    // Auto-fill job title when position is selected (works with Select2)
    $('#position_id').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const positionTitle = selectedOption.data('title');
        
        if (positionTitle && selectedOption.val()) {
            // Only auto-fill if job title is empty or was previously auto-filled
            const currentJobTitle = $('#job_title').val().trim();
            if (!currentJobTitle || jobTitleAutoFilled) {
                $('#job_title').val(positionTitle);
                jobTitleAutoFilled = true;
            }
        } else {
            // If position is cleared, reset the flag
            if (jobTitleAutoFilled) {
                $('#job_title').val('');
                jobTitleAutoFilled = false;
            }
        }
    });
    
    // Track if user manually edits the job title
    $('#job_title').on('input', function() {
        // If user types something different from the position title, it's manual
        const selectedPosition = $('#position_id option:selected');
        const positionTitle = selectedPosition.data('title');
        const currentJobTitle = $(this).val().trim();
        
        if (positionTitle && currentJobTitle !== positionTitle) {
            jobTitleAutoFilled = false;
        }
    });
    
    // On page load, if position is pre-selected (from old() data), auto-fill job title
    // Wait for Select2 to initialize first
    setTimeout(function() {
        const preselectedPosition = $('#position_id').val();
        if (preselectedPosition) {
            const selectedOption = $('#position_id option:selected');
            const positionTitle = selectedOption.data('title');
            const currentJobTitle = $('#job_title').val().trim();
            
            // Only auto-fill if job title is empty
            if (positionTitle && !currentJobTitle) {
                $('#job_title').val(positionTitle);
                jobTitleAutoFilled = true;
            }
        }
    }, 300);

    // --- Eligibility Rules Logic ---
    let ruleCount = 0;

    function toggleRulesTable() {
        if ($('#rules-container tr').length > 0) {
            $('#rules-table').show();
            $('#no-rules-msg').hide();
        } else {
            $('#rules-table').hide();
            $('#no-rules-msg').show();
        }
    }

    function addRuleRow() {
        const index = ruleCount++;
        const row = `
            <tr id="rule-row-${index}">
                <td>
                    <select name="rules[${index}][rule_type]" class="form-select form-select-sm" required>
                        <option value="education">Education</option>
                        <option value="experience">Experience</option>
                        <option value="certification">Certification</option>
                        <option value="skill">Skill</option>
                        <option value="age">Age</option>
                        <option value="safeguarding">Safeguarding</option>
                        <option value="other">Other</option>
                    </select>
                </td>
                <td>
                    <select name="rules[${index}][rule_operator]" class="form-select form-select-sm" required>
                        <option value="equals">=</option>
                        <option value="greater_than">&ge;</option>
                        <option value="less_than">&le;</option>
                        <option value="contains">CONTAINS</option>
                        <option value="in">IN</option>
                        <option value="between">BETWEEN</option>
                    </select>
                </td>
                <td>
                    <input type="text" name="rules[${index}][rule_value]" class="form-control form-control-sm" placeholder="Value (comma-sep for IN/BETWEEN)" required>
                </td>
                <td class="text-center">
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input" type="checkbox" name="rules[${index}][is_mandatory]" value="1" checked>
                    </div>
                </td>
                <td>
                    <input type="number" name="rules[${index}][weight]" class="form-control form-control-sm" value="0" min="0" max="100">
                </td>
                <td>
                    <select name="rules[${index}][applies_to]" class="form-select form-select-sm">
                        <option value="all">All Applicants</option>
                        <option value="conditional">Conditional</option>
                    </select>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-rule-btn">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#rules-container').append(row);
        toggleRulesTable();
    }

    $('#add-rule-btn').on('click', function() {
        addRuleRow();
    });

    $(document).on('click', '.remove-rule-btn', function() {
        $(this).closest('tr').remove();
        toggleRulesTable();
    });

    // Handle rule type change to provide better value inputs
    $(document).on('change', 'select[name^="rules"][name$="[rule_type]"]', function() {
        const row = $(this).closest('tr');
        const type = $(this).val();
        const valueCell = row.find('td:eq(2)');
        const nameAttr = $(this).attr('name').replace('[rule_type]', '[rule_value]');
        
        if (type === 'education') {
            valueCell.html(`
                <select name="${nameAttr}" class="form-select form-select-sm" required>
                    <option value="">Select Level</option>
                    <option value="PHD / Doctorate">PHD / Doctorate</option>
                    <option value="Master\'s Degree">Master\'s Degree</option>
                    <option value="Bachelor\'s Degree">Bachelor\'s Degree</option>
                    <option value="Advanced Diploma">Advanced Diploma</option>
                    <option value="Diploma">Diploma</option>
                    <option value="Certificate">Certificate</option>
                    <option value="Secondary Education">Secondary Education</option>
                </select>
            `);
        } else if (type === 'age' || type === 'experience') {
            valueCell.html(`
                <input type="number" name="${nameAttr}" class="form-control form-control-sm" placeholder="Years" required min="0">
            `);
        } else if (type === 'safeguarding') {
            valueCell.html(`
                <select name="${nameAttr}" class="form-select form-select-sm" required>
                    <option value="1">Cleared / Required</option>
                    <option value="0">Not Required</option>
                </select>
            `);
        } else {
            valueCell.html(`
                <input type="text" name="${nameAttr}" class="form-control form-control-sm" placeholder="Value (comma-sep for IN/BETWEEN)" required>
            `);
        }
    });

    // Add initial row if empty
    if ($('#rules-container tr').length === 0) {
        // addRuleRow(); // Optionally add one row by default
    }
});
</script>
@endpush

