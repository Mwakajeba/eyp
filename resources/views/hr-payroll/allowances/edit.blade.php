@extends('layouts.main')

@section('title', 'Edit Employee Allowance')

@push('styles')
<style>
    .alert-sm {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
    
    .bg-opacity-10 {
        --bs-bg-opacity: 0.1;
    }
    
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: box-shadow 0.3s ease-in-out;
    }
    
    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
</style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Allowances', 'url' => route('hr.allowances.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bx bx-edit me-2"></i>Edit Employee Allowance
                            </h6>
                        </div>
                        <div class="card-body">
                            @include('hr-payroll.allowances._form', ['allowance' => $allowance])
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Current Information Card -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="bx bx-info-circle text-info me-2"></i>Current Allowance Details
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Employee:</strong><br>
                                <span class="text-muted">{{ $allowance->employee->full_name ?? 'N/A' }}</span><br>
                                <small class="text-muted">{{ $allowance->employee->employee_number ?? '' }}</small>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Allowance Type:</strong><br>
                                <span class="badge bg-primary">{{ $allowance->allowanceType->name ?? 'N/A' }}</span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Current Amount:</strong><br>
                                <span class="h6 text-success">TZS {{ number_format($allowance->amount, 2) }}</span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Status:</strong>
                                <span class="badge {{ $allowance->is_active ? 'bg-success' : 'bg-secondary' }} ms-2">
                                    {{ $allowance->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Effective Date:</strong><br>
                                <span class="text-muted">{{ $allowance->date?->format('M d, Y') ?? 'N/A' }}</span>
                            </div>

                            @if($allowance->created_at)
                                <div class="mb-0">
                                    <small class="text-muted">
                                        <strong>Created:</strong> {{ $allowance->created_at->format('M d, Y') }}
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Edit Guidelines Card -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="bx bx-help-circle text-info me-2"></i>Editing Guidelines
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6 class="text-primary mb-2">
                                    <i class="bx bx-edit me-1"></i>What You Can Edit
                                </h6>
                                <ul class="list-unstyled small">
                                    <li class="mb-2">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Change the amount value
                                    </li>
                                    <li class="mb-2">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Update effective date
                                    </li>
                                    <li class="mb-2">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Modify description
                                    </li>
                                    <li class="mb-2">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Activate/deactivate status
                                    </li>
                                    <li class="mb-0">
                                        <i class="bx bx-x text-danger me-1"></i>
                                        <span class="text-muted">Employee and type are fixed</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="mb-4">
                                <h6 class="text-warning mb-2">
                                    <i class="bx bx-error-circle me-1"></i>Important Notes
                                </h6>
                                <ul class="list-unstyled small">
                                    <li class="mb-2">
                                        <i class="bx bx-info-circle text-info me-1"></i>
                                        Amount changes affect future payrolls
                                    </li>
                                    <li class="mb-2">
                                        <i class="bx bx-info-circle text-info me-1"></i>
                                        Date changes affect payroll inclusion
                                    </li>
                                    <li class="mb-0">
                                        <i class="bx bx-info-circle text-info me-1"></i>
                                        Past payrolls remain unchanged
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Payroll Impact Card -->
                    <div class="card">
                        <div class="card-header bg-info bg-opacity-10">
                            <h6 class="mb-0 text-info">
                                <i class="bx bx-calendar-check me-2"></i>Payroll Impact
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="small">
                                <p class="mb-2">
                                    <strong>Next Payroll:</strong> Changes will be reflected in the next payroll run for the effective period.
                                </p>
                                <p class="mb-2">
                                    <strong>Tax Treatment:</strong> Based on the allowance type's tax settings ({{ $allowance->allowanceType->is_taxable ? 'Taxable' : 'Non-Taxable' }}).
                                </p>
                                <p class="mb-0">
                                    <strong>Calculation:</strong> {{ ucfirst($allowance->allowanceType->type ?? 'Fixed') }} amount type.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Change Impact Warning Card -->
                    <div class="card border-warning">
                        <div class="card-header bg-warning bg-opacity-10">
                            <h6 class="mb-0 text-warning">
                                <i class="bx bx-error-circle me-2"></i>Change Impact
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning alert-sm mb-0">
                                <small>
                                    <i class="bx bx-info-circle me-1"></i>
                                    <strong>Review Carefully:</strong> Changes to this allowance will affect the employee's future payroll calculations. Ensure all modifications are accurate before saving.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection