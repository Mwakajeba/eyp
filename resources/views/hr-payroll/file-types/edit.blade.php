@extends('layouts.main')

@section('title', 'Edit File Type')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'File Types', 'url' => route('hr.file-types.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">Edit File Type</h6>
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
                            <i class="bx bx-edit me-2"></i>Edit File Type: {{ $fileType->name }}
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('hr.file-types.update', $fileType) }}">
                            @csrf
                            @method('PUT')
                            @include('hr-payroll.file-types._form')
                            <hr class="my-4">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>Update File Type
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
                <!-- Current File Type Info -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="bx bx-info-circle me-2"></i>Current Settings
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <td class="fw-bold text-dark">Name:</td>
                                        <td>{{ $fileType->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-dark">Code:</td>
                                        <td>{{ $fileType->code ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-dark">Extensions:</td>
                                        <td>
                                            @if($fileType->allowed_extensions_string)
                                                {{ $fileType->allowed_extensions_string }}
                                            @else
                                                <span class="text-success">All types allowed</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-dark">Max Size:</td>
                                        <td>
                                            @if($fileType->max_file_size_human)
                                                {{ $fileType->max_file_size_human }}
                                            @else
                                                <span class="text-success">No limit</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-dark">Required:</td>
                                        <td>
                                            <span class="badge bg-{{ $fileType->is_required ? 'warning' : 'secondary' }}">
                                                {{ $fileType->is_required ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-dark">Status:</td>
                                        <td>
                                            <span class="badge bg-{{ $fileType->is_active ? 'success' : 'secondary' }}">
                                                {{ $fileType->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        @if($fileType->description)
                        <div class="mt-3">
                            <strong class="text-dark">Description:</strong>
                            <p class="text-muted small mt-1">{{ $fileType->description }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Usage Information -->
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">
                            <i class="bx bx-notification me-2"></i>Important Notes
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="small text-muted">
                            <div class="mb-3">
                                <div class="d-flex align-items-start mb-2">
                                    <i class="bx bx-info-circle text-info me-2 mt-1"></i>
                                    <div>
                                        Changes will affect how employees can upload documents of this type.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex align-items-start mb-2">
                                    <i class="bx bx-shield text-warning me-2 mt-1"></i>
                                    <div>
                                        Changing allowed extensions may prevent access to existing files.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex align-items-start mb-2">
                                    <i class="bx bx-user-check text-success me-2 mt-1"></i>
                                    <div>
                                        Making a document "Required" will enforce it for new employees.
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="d-flex align-items-start mb-2">
                                    <i class="bx bx-x-circle text-danger me-2 mt-1"></i>
                                    <div>
                                        Deactivating will hide this file type from document uploads.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Editing Guidelines -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="bx bx-help-circle me-2"></i>Editing Guidelines
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h6 class="text-dark mb-3">
                                <i class="bx bx-check-circle me-2 text-success"></i>Safe Changes
                            </h6>
                            <ul class="list-unstyled small text-muted">
                                <li class="mb-2">
                                    <i class="bx bx-check text-success me-2"></i>
                                    Updating description or name
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-check text-success me-2"></i>
                                    Adding more file extensions
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-check text-success me-2"></i>
                                    Increasing file size limits
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-check text-success me-2"></i>
                                    Adding or updating codes
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h6 class="text-dark mb-3">
                                <i class="bx bx-error-circle me-2 text-warning"></i>Use Caution
                            </h6>
                            <ul class="list-unstyled small text-muted">
                                <li class="mb-2">
                                    <i class="bx bx-x text-warning me-2"></i>
                                    Removing file extensions
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-x text-warning me-2"></i>
                                    Reducing file size limits
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-x text-warning me-2"></i>
                                    Changing from optional to required
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-x text-warning me-2"></i>
                                    Deactivating active file types
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
