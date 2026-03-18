@extends('layouts.main')

@section('title', 'Apply Salary Structure Template')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Employee Salary Structures', 'url' => route('hr.employee-salary-structure.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Apply Template', 'url' => '#', 'icon' => 'bx bx-file']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0"><i class="bx bx-file me-2"></i>Apply Salary Structure Template</h5>
                <p class="mb-0 text-muted">Apply a predefined template to multiple employees</p>
            </div>
            <a href="{{ route('hr.employee-salary-structure.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i>Back to List
            </a>
        </div>
        <hr />

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('bulk_errors'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Some applications failed:</strong>
            <ul class="mb-0 mt-2">
                @foreach(session('bulk_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <form method="POST" action="{{ route('hr.employee-salary-structure.apply-template') }}" id="applyTemplateForm">
            @csrf

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="template_id" class="form-label">Template <span class="text-danger">*</span></label>
                                <select class="form-select select2-single @error('template_id') is-invalid @enderror" 
                                    id="template_id" name="template_id" required>
                                    <option value="">Select Template</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}" {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                            {{ $template->template_code }} - {{ $template->template_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('template_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Select a salary structure template to apply.</small>
                            </div>

                            <div id="template-preview" class="mb-3" style="display: none;">
                                <h6>Template Components:</h6>
                                <div id="template-components-list" class="list-group"></div>
                            </div>

                            <hr>

                            <div class="mb-3">
                                <label for="employee_ids" class="form-label">Employees <span class="text-danger">*</span></label>
                                <select class="form-select select2-multiple" id="employee_ids" name="employee_ids[]" multiple required>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->employee_number ?? 'N/A' }})</option>
                                    @endforeach
                                </select>
                                @error('employee_ids')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Select one or more employees to apply the template to.</small>
                            </div>

                            <hr>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="effective_date" class="form-label">Effective Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('effective_date') is-invalid @enderror" 
                                            id="effective_date" name="effective_date" 
                                            value="{{ old('effective_date', date('Y-m-d')) }}" required>
                                        @error('effective_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date (Optional)</label>
                                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                                            id="end_date" name="end_date" value="{{ old('end_date') }}">
                                        @error('end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="mb-3">
                                <i class="bx bx-help-circle text-primary me-1"></i>Template Application Guide
                            </h6>
                            <hr>
                            
                            <div class="mb-3">
                                <h6 class="text-primary"><i class="bx bx-list-ul me-1"></i>Steps</h6>
                                <ol class="small text-muted mb-0">
                                    <li>Select a template</li>
                                    <li>Select employees</li>
                                    <li>Set effective date</li>
                                    <li>Submit to apply</li>
                                </ol>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-primary"><i class="bx bx-info-circle me-1"></i>Benefits</h6>
                                <ul class="small text-muted mb-0">
                                    <li>Consistent structures</li>
                                    <li>Faster assignment</li>
                                    <li>Less errors</li>
                                    <li>Easy updates</li>
                                </ul>
                            </div>

                            <div class="alert alert-info mb-0">
                                <small>
                                    <i class="bx bx-info-circle text-info me-1"></i>
                                    <strong>Tip:</strong> Create templates for common salary structures to speed up assignments.
                                </small>
                            </div>

                            <div class="mt-3">
                                <a href="{{ route('hr.salary-structure-templates.index') }}" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bx bx-template me-1"></i>Manage Templates
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bx bx-check me-1"></i>Apply Template
                </button>
                <a href="{{ route('hr.employee-salary-structure.index') }}" class="btn btn-secondary">
                    <i class="bx bx-x me-1"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select template...'
    });

    $('.select2-multiple').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select employees...',
        allowClear: true
    });

    // Load template preview
    $('#template_id').on('change', function() {
        const templateId = $(this).val();
        if (templateId) {
            // In a real implementation, you'd fetch template details via AJAX
            // For now, we'll just show/hide the preview section
            $('#template-preview').show();
        } else {
            $('#template-preview').hide();
        }
    });
});
</script>
@endpush

