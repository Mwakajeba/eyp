@extends('layouts.main')

@section('title', 'Create File Type')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'File Types', 'url' => route('hr.file-types.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">Create File Type</h6>
            <a href="{{ route('hr.file-types.index') }}" class="btn btn-secondary">
                <i class="bx bx-arrow-back me-1"></i>Back to List
            </a>
        </div>

        <div class="row">
            <!-- Form Section -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="bx bx-plus-circle me-2"></i>New File Type Details
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('hr.file-types.store') }}">
                            @csrf
                            @include('hr-payroll.file-types._form')
                            <hr class="my-4">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>Create File Type
                                </button>
                                <a href="{{ route('hr.file-types.index') }}" class="btn btn-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Information Panel -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="bx bx-help-circle me-2"></i>Creating File Types
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Field Guidelines -->
                        <div class="mb-4">
                            <h6 class="text-dark mb-3">
                                <i class="bx bx-edit me-2 text-primary"></i>Field Guidelines
                            </h6>
                            <div class="small text-muted">
                                <div class="mb-3">
                                    <strong class="text-dark">Name:</strong><br>
                                    Use clear, descriptive names that employees will understand.<br>
                                    <em>Examples: "Employment Contract", "ID Copy", "Medical Certificate"</em>
                                </div>
                                
                                <div class="mb-3">
                                    <strong class="text-dark">Code:</strong><br>
                                    Optional short code for system identification.<br>
                                    <em>Examples: "EMP_CONTRACT", "ID_COPY", "MED_CERT"</em>
                                </div>
                                
                                <div class="mb-3">
                                    <strong class="text-dark">Extensions:</strong><br>
                                    Limit file types for security and consistency.<br>
                                    <em>Common: pdf, doc, docx, jpg, png, jpeg</em>
                                </div>
                            </div>
                        </div>

                        <!-- Security Considerations -->
                        <div class="mb-4">
                            <h6 class="text-dark mb-3">
                                <i class="bx bx-shield me-2 text-warning"></i>Security Tips
                            </h6>
                            <ul class="list-unstyled small text-muted">
                                <li class="mb-2">
                                    <i class="bx bx-check text-success me-2"></i>
                                    Always specify allowed extensions
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-check text-success me-2"></i>
                                    Set reasonable file size limits (1-5MB)
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-x text-danger me-2"></i>
                                    Avoid executable files (.exe, .bat, .sh)
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-x text-danger me-2"></i>
                                    Don't allow script files (.js, .php)
                                </li>
                            </ul>
                        </div>

                        <!-- File Size Reference -->
                        <div class="mb-4">
                            <h6 class="text-dark mb-3">
                                <i class="bx bx-hdd me-2 text-info"></i>Size Reference
                            </h6>
                            <div class="small text-muted">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Small document:</span>
                                    <span class="text-dark">512 KB</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Standard document:</span>
                                    <span class="text-dark">2 MB</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>High-res scan:</span>
                                    <span class="text-dark">5 MB</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Large file:</span>
                                    <span class="text-dark">10 MB</span>
                                </div>
                            </div>
                        </div>

                        <!-- Document Categories -->
                        <div>
                            <h6 class="text-dark mb-3">
                                <i class="bx bx-category me-2 text-primary"></i>Common Categories
                            </h6>
                            <div class="row g-2 small">
                                <div class="col-12">
                                    <div class="border rounded p-2">
                                        <strong class="text-primary">Identity Documents</strong><br>
                                        <span class="text-muted">ID Copy, Passport, Driver's License</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="border rounded p-2">
                                        <strong class="text-success">Employment</strong><br>
                                        <span class="text-muted">Contract, Job Description, Offer Letter</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="border rounded p-2">
                                        <strong class="text-info">Education</strong><br>
                                        <span class="text-muted">Certificates, Transcripts, Training</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="border rounded p-2">
                                        <strong class="text-warning">Medical</strong><br>
                                        <span class="text-muted">Health Reports, Fitness Certificates</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
