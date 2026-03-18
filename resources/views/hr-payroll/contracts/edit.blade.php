@extends('layouts.main')

@section('title', 'Edit Contract')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Contracts', 'url' => route('hr.contracts.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">Edit Contract</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.contracts.update', $contract->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Employee</label>
                            <input type="text" class="form-control" value="{{ $contract->employee->full_name }} ({{ $contract->employee->employee_number }})" disabled />
                            <input type="hidden" name="employee_id" value="{{ $contract->employee_id }}" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Contract Type <span class="text-danger">*</span></label>
                            <select name="contract_type" class="form-select @error('contract_type') is-invalid @enderror" required>
                                <option value="">-- Select Type --</option>
                                <option value="permanent" {{ old('contract_type', $contract->contract_type) == 'permanent' ? 'selected' : '' }}>Permanent</option>
                                <option value="fixed_term" {{ old('contract_type', $contract->contract_type) == 'fixed_term' ? 'selected' : '' }}>Fixed Term</option>
                                <option value="probation" {{ old('contract_type', $contract->contract_type) == 'probation' ? 'selected' : '' }}>Probation</option>
                                <option value="contractor" {{ old('contract_type', $contract->contract_type) == 'contractor' ? 'selected' : '' }}>Contractor</option>
                                <option value="intern" {{ old('contract_type', $contract->contract_type) == 'intern' ? 'selected' : '' }}>Intern</option>
                            </select>
                            @error('contract_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" 
                                   value="{{ old('start_date', $contract->start_date->format('Y-m-d')) }}" required />
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" 
                                   value="{{ old('end_date', $contract->end_date ? $contract->end_date->format('Y-m-d') : '') }}" />
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Working Hours per Week</label>
                            <input type="number" name="working_hours_per_week" min="1" max="168" 
                                   class="form-control @error('working_hours_per_week') is-invalid @enderror" 
                                   value="{{ old('working_hours_per_week', $contract->working_hours_per_week) }}" />
                            @error('working_hours_per_week')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Contract Salary
                                <i class="bx bx-help-circle text-muted" data-bs-toggle="tooltip" 
                                   title="Salary amount for this contract. If set, this will be used in payroll calculations instead of employee's basic salary."></i>
                            </label>
                            <input type="number" name="salary" step="0.01" min="0" 
                                   class="form-control @error('salary') is-invalid @enderror" 
                                   value="{{ old('salary', $contract->salary) }}" 
                                   placeholder="0.00" />
                            @error('salary')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                @if($contract->employee->basic_salary)
                                    <i class="bx bx-info-circle me-1"></i>
                                    Employee's basic salary: <strong>{{ number_format($contract->employee->basic_salary, 2) }} TZS</strong>
                                    <br><small class="text-muted">Leave blank to use this amount, or enter a different amount for this contract.</small>
                                @else
                                    <i class="bx bx-info-circle me-1"></i>
                                    If left blank, employee's basic salary will be used in payroll calculations.
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', $contract->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="expired" {{ old('status', $contract->status) == 'expired' ? 'selected' : '' }}>Expired</option>
                                <option value="terminated" {{ old('status', $contract->status) == 'terminated' ? 'selected' : '' }}>Terminated</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" name="renewal_flag" id="renewal_flag" 
                                       value="1" {{ old('renewal_flag', $contract->renewal_flag) ? 'checked' : '' }}>
                                <label class="form-check-label" for="renewal_flag">
                                    Renewal Required
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Update Contract
                        </button>
                        <a href="{{ route('hr.contracts.show', $contract->id) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

