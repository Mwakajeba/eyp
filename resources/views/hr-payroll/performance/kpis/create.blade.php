@extends('layouts.main')

@section('title', 'Create KPI')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Performance', 'url' => '#', 'icon' => 'bx bx-trophy'],
            ['label' => 'KPIs', 'url' => route('hr.kpis.index'), 'icon' => 'bx bx-target-lock'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">Create KPI</h6>
        <hr />
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('hr.kpis.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">KPI Code <span class="text-danger">*</span></label>
                            <input type="text" name="kpi_code" class="form-control @error('kpi_code') is-invalid @enderror" 
                                   value="{{ old('kpi_code') }}" required placeholder="e.g., KPI001, SALES_TARGET" />
                            @error('kpi_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Unique code for this KPI</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">KPI Name <span class="text-danger">*</span></label>
                            <input type="text" name="kpi_name" class="form-control @error('kpi_name') is-invalid @enderror" 
                                   value="{{ old('kpi_name') }}" required placeholder="e.g., Sales Target Achievement" />
                            @error('kpi_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                      rows="3" placeholder="Detailed description of the KPI">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Measurement Criteria</label>
                            <textarea name="measurement_criteria" class="form-control @error('measurement_criteria') is-invalid @enderror" 
                                      rows="3" placeholder="How this KPI is measured">{{ old('measurement_criteria') }}</textarea>
                            @error('measurement_criteria')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Explain how this KPI is measured and evaluated</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Weight Percentage</label>
                            <input type="number" name="weight_percent" step="0.01" min="0" max="100" 
                                   class="form-control @error('weight_percent') is-invalid @enderror" 
                                   value="{{ old('weight_percent') }}" placeholder="0.00" />
                            @error('weight_percent')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Weight in percentage (0-100)</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Target Value</label>
                            <input type="number" name="target_value" step="0.01" min="0" 
                                   class="form-control @error('target_value') is-invalid @enderror" 
                                   value="{{ old('target_value') }}" placeholder="0.00" />
                            @error('target_value')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Target value for this KPI</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Scoring Method <span class="text-danger">*</span></label>
                            <select name="scoring_method" class="form-select @error('scoring_method') is-invalid @enderror" required>
                                <option value="">Select Method</option>
                                <option value="numeric" {{ old('scoring_method') == 'numeric' ? 'selected' : '' }}>Numeric</option>
                                <option value="rating_scale" {{ old('scoring_method') == 'rating_scale' ? 'selected' : '' }}>Rating Scale</option>
                            </select>
                            @error('scoring_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Applicable To <span class="text-danger">*</span></label>
                            <select name="applicable_to" class="form-select @error('applicable_to') is-invalid @enderror" required>
                                <option value="">Select Applicability</option>
                                <option value="company" {{ old('applicable_to') == 'company' ? 'selected' : '' }}>Company</option>
                                <option value="department" {{ old('applicable_to') == 'department' ? 'selected' : '' }}>Department</option>
                                <option value="position" {{ old('applicable_to') == 'position' ? 'selected' : '' }}>Position</option>
                                <option value="individual" {{ old('applicable_to') == 'individual' ? 'selected' : '' }}>Individual</option>
                            </select>
                            @error('applicable_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                       value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                            <div class="form-text">Inactive KPIs cannot be used in appraisals</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Save KPI
                        </button>
                        <a href="{{ route('hr.kpis.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

