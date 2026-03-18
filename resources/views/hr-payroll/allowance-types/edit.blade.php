@extends('layouts.main')

@section('title', 'Edit Allowance Type')

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
            ['label' => 'Payroll Settings', 'url' => route('hr.payroll-settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Allowance Types', 'url' => route('hr.allowance-types.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bx bx-edit me-2"></i>Edit Allowance Type
                            </h6>
                        </div>
                        <div class="card-body">
                            @include('hr-payroll.allowance-types._form', ['allowanceType' => $allowanceType])
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Current Settings Card -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="bx bx-info-circle text-info me-2"></i>Current Settings
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Status:</strong>
                                <span class="badge {{ $allowanceType->is_active ? 'bg-success' : 'bg-secondary' }} ms-2">
                                    {{ $allowanceType->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Tax Status:</strong>
                                <span class="badge {{ $allowanceType->is_taxable ? 'bg-warning' : 'bg-info' }} ms-2">
                                    {{ $allowanceType->is_taxable ? 'Taxable' : 'Non-Taxable' }}
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Type:</strong>
                                <span class="badge bg-primary ms-2">
                                    {{ ucfirst($allowanceType->type) }}
                                </span>
                            </div>

                            @if($allowanceType->created_at)
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <strong>Created:</strong> {{ $allowanceType->created_at->format('M d, Y') }}
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Guidelines Card -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="bx bx-help-circle text-info me-2"></i>Edit Guidelines
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6 class="text-primary mb-2">
                                    <i class="bx bx-edit me-1"></i>Editing Tips
                                </h6>
                                <ul class="list-unstyled small">
                                    <li class="mb-2">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Changes affect new payroll calculations
                                    </li>
                                    <li class="mb-2">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Existing payrolls remain unchanged
                                    </li>
                                    <li class="mb-2">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Test changes before activation
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
                                        Changing tax status affects payroll calculations
                                    </li>
                                    <li class="mb-2">
                                        <i class="bx bx-info-circle text-info me-1"></i>
                                        Deactivating removes from new employee assignments
                                    </li>
                                    <li class="mb-2">
                                        <i class="bx bx-info-circle text-info me-1"></i>
                                        Type changes affect calculation methods
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Impact Warning Card -->
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
                                    <strong>Note:</strong> Changes to this allowance type will affect future payroll calculations. Review all settings carefully before saving.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection