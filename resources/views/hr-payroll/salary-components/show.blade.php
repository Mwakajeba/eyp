@extends('layouts.main')

@section('title', 'Salary Component Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Salary Components', 'url' => route('hr.salary-components.index'), 'icon' => 'bx bx-calculator'],
                ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0"><i class="bx bx-calculator me-2"></i>Salary Component Details</h5>
                    <p class="mb-0 text-muted">{{ $salaryComponent->component_name }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.salary-components.edit', $salaryComponent->id) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <a href="{{ route('hr.salary-components.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back
                    </a>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Component Information</h6>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Code:</strong>
                                    <p class="text-muted">{{ $salaryComponent->component_code }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Name:</strong>
                                    <p class="text-muted">{{ $salaryComponent->component_name }}</p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Type:</strong>
                                    <p>
                                        @if($salaryComponent->component_type == 'earning')
                                            <span class="badge bg-success">Earning</span>
                                        @else
                                            <span class="badge bg-danger">Deduction</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Calculation Type:</strong>
                                    <p class="text-muted text-capitalize">{{ $salaryComponent->calculation_type }}</p>
                                </div>
                            </div>

                            @if($salaryComponent->calculation_formula)
                                <div class="mb-3">
                                    <strong>Formula:</strong>
                                    <p class="text-muted"><code>{{ $salaryComponent->calculation_formula }}</code></p>
                                </div>
                            @endif

                            <div class="row mb-3">
                                @if($salaryComponent->floor_amount)
                                    <div class="col-md-6">
                                        <strong>Minimum Amount:</strong>
                                        <p class="text-muted">{{ number_format($salaryComponent->floor_amount, 2) }} TZS</p>
                                    </div>
                                @endif
                                @if($salaryComponent->ceiling_amount)
                                    <div class="col-md-6">
                                        <strong>Maximum Amount:</strong>
                                        <p class="text-muted">{{ number_format($salaryComponent->ceiling_amount, 2) }} TZS</p>
                                    </div>
                                @endif
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong>Taxable:</strong>
                                    <p>
                                        @if($salaryComponent->is_taxable)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <strong>Pensionable:</strong>
                                    <p>
                                        @if($salaryComponent->is_pensionable)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <strong>NHIF Applicable:</strong>
                                    <p>
                                        @if($salaryComponent->is_nhif_applicable)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Status:</strong>
                                    <p>
                                        @if($salaryComponent->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Display Order:</strong>
                                    <p class="text-muted">{{ $salaryComponent->display_order }}</p>
                                </div>
                            </div>

                            @if($salaryComponent->description)
                                <div class="mb-3">
                                    <strong>Description:</strong>
                                    <p class="text-muted">{{ $salaryComponent->description }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Assigned Employees ({{ $employeeCount }})</h6>
                            <p class="text-muted mb-0">This component is currently assigned to {{ $employeeCount }} employee(s).</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-4">Quick Actions</h6>
                            
                            <a href="{{ route('hr.salary-components.edit', $salaryComponent->id) }}" class="btn btn-primary w-100 mb-2">
                                <i class="bx bx-edit me-1"></i>Edit Component
                            </a>

                            <a href="{{ route('hr.salary-components.index') }}" class="btn btn-secondary w-100">
                                <i class="bx bx-arrow-back me-1"></i>Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

