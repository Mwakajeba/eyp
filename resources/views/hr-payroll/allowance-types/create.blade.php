@extends('layouts.main')

@section('title', 'Create Allowance Type')

@push('styles')
<style>
    .alert-sm {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
    
    .bg-opacity-10 {
        --bs-bg-opacity: 0.1;
    }
    
    .border-start {
        border-left-width: 3px !important;
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
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bx bx-plus me-2"></i>Create Allowance Type
                            </h6>
                        </div>
                        <div class="card-body">
                            @include('hr-payroll.allowance-types._form', ['allowanceType' => null])
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Guidelines Card -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="bx bx-info-circle text-info me-2"></i>Guidelines
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6 class="text-primary mb-2">
                                    <i class="bx bx-help-circle me-1"></i>What are Allowance Types?
                                </h6>
                                <p class="small text-muted">
                                    Allowance types define different categories of additional compensation that can be added to employee salaries, such as transport, housing, or meal allowances.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h6 class="text-primary mb-2">
                                    <i class="bx bx-list-ul me-1"></i>Field Descriptions
                                </h6>
                                <ul class="list-unstyled small">
                                    <li class="mb-2">
                                        <strong>Name:</strong> <span class="text-muted">Display name for the allowance (e.g., "Transport Allowance")</span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Code:</strong> <span class="text-muted">Optional short code for reports (e.g., "TRANS")</span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Description:</strong> <span class="text-muted">Detailed explanation of the allowance purpose</span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Type:</strong> <span class="text-muted">Fixed amount or percentage of basic salary</span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Taxable:</strong> <span class="text-muted">Whether this allowance is subject to income tax</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="mb-4">
                                <h6 class="text-warning mb-2">
                                    <i class="bx bx-lightbulb me-1"></i>Best Practices
                                </h6>
                                <ul class="list-unstyled small">
                                    <li class="mb-2">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Use clear, descriptive names
                                    </li>
                                    <li class="mb-2">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Keep codes short and memorable
                                    </li>
                                    <li class="mb-2">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Set appropriate tax settings
                                    </li>
                                    <li class="mb-2">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Document calculation methods
                                    </li>
                                </ul>
                            </div>

                            <div class="mb-4">
                                <h6 class="text-success mb-2">
                                    <i class="bx bx-bookmark me-1"></i>Common Examples
                                </h6>
                                <div class="small">
                                    <div class="border-start border-3 border-primary ps-3 mb-3">
                                        <strong>Transport Allowance</strong><br>
                                        <span class="text-muted">Fixed monthly transport compensation</span>
                                    </div>
                                    <div class="border-start border-3 border-info ps-3 mb-3">
                                        <strong>Housing Allowance</strong><br>
                                        <span class="text-muted">Percentage of basic salary for accommodation</span>
                                    </div>
                                    <div class="border-start border-3 border-success ps-3 mb-3">
                                        <strong>Meal Allowance</strong><br>
                                        <span class="text-muted">Daily meal compensation (usually non-taxable)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tax Information Card -->
                    <div class="card border-warning">
                        <div class="card-header bg-warning bg-opacity-10">
                            <h6 class="mb-0 text-warning">
                                <i class="bx bx-error-circle me-2"></i>Tax Considerations
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning alert-sm mb-0">
                                <small>
                                    <i class="bx bx-info-circle me-1"></i>
                                    <strong>Important:</strong> Consult with your tax advisor to determine which allowances are taxable according to local tax laws. Some allowances like meals or transport may have specific tax treatments.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection