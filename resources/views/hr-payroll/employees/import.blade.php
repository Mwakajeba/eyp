@extends('layouts.main')

@section('title', 'Import Employees')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Employees', 'url' => route('hr.employees.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Import', 'url' => '#', 'icon' => 'bx bx-upload']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">
                <i class="bx bx-upload me-1"></i>Import Employees
            </h6>
            <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i>Back to Employees
            </a>
        </div>
        <hr />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-x-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('import_errors'))
            <div class="alert alert-danger">
                <h6 class="alert-heading">Import Errors:</h6>
                <ul class="mb-0">
                    @foreach(session('import_errors') as $error)
                        <li><strong>Row {{ $error['row'] }}:</strong> 
                            @foreach($error['errors'] as $errorMsg)
                                {{ $errorMsg }}@if(!$loop->last), @endif
                            @endforeach
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bx bx-upload me-1"></i>Upload Employee Data
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('hr.employees.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-4">
                                <label for="default_branch" class="form-label">Default Branch <span class="text-danger">*</span></label>
                                <select name="default_branch" id="default_branch" class="form-select @error('default_branch') is-invalid @enderror" required>
                                    <option value="">-- Select Default Branch --</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected(old('default_branch') == $branch->id)>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('default_branch')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    This branch will be assigned to all employees if not specified in the import file.
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="file" class="form-label">Excel File <span class="text-danger">*</span></label>
                                <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" 
                                       accept=".xlsx,.xls,.csv" required>
                                @error('file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Accepted formats: .xlsx, .xls, .csv (Max size: 2MB)
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-upload me-1"></i>Import Employees
                                </button>
                                <a href="{{ route('hr.employees.template') }}" class="btn btn-outline-success">
                                    <i class="bx bx-download me-1"></i>Download Template
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bx bx-info-circle me-1"></i>Import Guidelines
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6 class="alert-heading">Instructions</h6>
                            <ol class="mb-0 small">
                                <li>Select a default branch for employees</li>
                                <li>Download the template file first</li>
                                <li>Fill in the employee data following the format</li>
                                <li>Save the file as Excel (.xlsx) or CSV (.csv)</li>
                                <li>Upload the completed file</li>
                            </ol>
                        </div>
                        
                        <div class="mt-3">
                            <h6>Required Fields:</h6>
                            <ul class="small">
                                <li>first_name</li>
                                <li>last_name</li>
                                <li>phone_number</li>
                                <li>date_of_birth</li>
                                <li>gender</li>
                                <li>marital_status</li>
                                <li>country</li>
                                <li>region</li>
                                <li>district</li>
                                <li>current_physical_location</li>
                                <li>basic_salary</li>
                                <li>identity_document_type</li>
                                <li>identity_number</li>
                                <li>employment_type</li>
                                <li>date_of_employment</li>
                            </ul>
                        </div>

                        <div class="mt-3">
                            <h6>Notes:</h6>
                            <ul class="small">
                                <li>Employee numbers will be auto-generated if not provided</li>
                                <li>User accounts will be created automatically for each employee</li>
                                <li>Default password for all new users will be: <code>password123</code></li>
                                <li>Users should change their password on first login</li>
                                <li>Departments and positions will be created if they don't exist</li>
                                <li>Dates should be in YYYY-MM-DD format</li>
                                <li>Phone numbers will be auto-formatted</li>
                                <li>Users will be assigned the 'employee' role automatically</li>
                                <li><strong>Duplicate emails and phone numbers will be rejected</strong></li>
                                <li>All employees will be assigned to the selected default branch</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bx bx-list-check me-1"></i>Valid Values
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <strong>Gender:</strong>
                                <p class="small mb-2">male, female, other</p>
                            </div>
                            
                            <div class="col-12">
                                <strong>Marital Status:</strong>
                                <p class="small mb-2">single, married, divorced, widowed</p>
                            </div>
                            
                            <div class="col-12">
                                <strong>Identity Document:</strong>
                                <p class="small mb-2">national_id, passport, driving_license, voters_id</p>
                            </div>
                            
                            <div class="col-12">
                                <strong>Employment Type:</strong>
                                <p class="small mb-2">full_time, part_time, contract, intern</p>
                                <p class="small text-muted mb-0">
                                    <i class="bx bx-info-circle me-1"></i>
                                    Also accepts: permanent → full_time, temporary → contract, internship → intern
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }

    .form-label {
        font-weight: 600;
        color: #495057;
    }

    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
</style>
@endpush