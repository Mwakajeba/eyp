@extends('layouts.main')

@section('title', 'Create Employee Allowance')

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
    
    .step-number {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #0d6efd;
        color: white;
        font-size: 12px;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
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
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bx bx-plus me-2"></i>Create Employee Allowance
                            </h6>
                        </div>
                        <div class="card-body">
                            @include('hr-payroll.allowances._form', ['allowance' => null])
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Guidelines Card -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="bx bx-info-circle text-info me-2"></i>How to Create Allowances
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6 class="text-primary mb-3">
                                    <i class="bx bx-help-circle me-1"></i>What are Employee Allowances?
                                </h6>
                                <p class="small text-muted">
                                    Employee allowances are additional monetary benefits assigned to specific employees beyond their basic salary. These can be one-time payments or recurring additions.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h6 class="text-primary mb-3">
                                    <i class="bx bx-list-ol me-1"></i>Step-by-Step Guide
                                </h6>
                                <div class="small">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="step-number me-3 flex-shrink-0">1</div>
                                        <div>
                                            <strong>Select Employee:</strong> Choose the employee who will receive this allowance
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="step-number me-3 flex-shrink-0">2</div>
                                        <div>
                                            <strong>Choose Allowance Type:</strong> Select from predefined allowance types (transport, housing, etc.)
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="step-number me-3 flex-shrink-0">3</div>
                                        <div>
                                            <strong>Set Date:</strong> When this allowance becomes effective
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="step-number me-3 flex-shrink-0">4</div>
                                        <div>
                                            <strong>Enter Amount:</strong> Specific amount in Tanzanian Shillings
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-start">
                                        <div class="step-number me-3 flex-shrink-0">5</div>
                                        <div>
                                            <strong>Add Description:</strong> Optional notes about this allowance
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6 class="text-success mb-2">
                                    <i class="bx bx-bookmark me-1"></i>Field Explanations
                                </h6>
                                <ul class="list-unstyled small">
                                    <li class="mb-2">
                                        <strong>Employee:</strong> <span class="text-muted">The recipient of this allowance</span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Allowance Type:</strong> <span class="text-muted">Category that determines tax treatment and calculations</span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Date:</strong> <span class="text-muted">Effective date for payroll calculations</span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Amount:</strong> <span class="text-muted">Fixed amount regardless of allowance type setting</span>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Description:</strong> <span class="text-muted">Purpose or reason for this allowance</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Best Practices Card -->
                    <div class="card">
                        <div class="card-header bg-success bg-opacity-10">
                            <h6 class="mb-0 text-success">
                                <i class="bx bx-lightbulb me-2"></i>Best Practices
                            </h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled small mb-0">
                                <li class="mb-2">
                                    <i class="bx bx-check text-success me-2"></i>
                                    Set effective dates accurately for payroll timing
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-check text-success me-2"></i>
                                    Use clear descriptions for audit trails
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-check text-success me-2"></i>
                                    Verify allowance type tax implications
                                </li>
                                <li class="mb-2">
                                    <i class="bx bx-check text-success me-2"></i>
                                    Double-check amount calculations
                                </li>
                                <li class="mb-0">
                                    <i class="bx bx-check text-success me-2"></i>
                                    Keep supporting documentation
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Common Examples Card -->
                    <div class="card">
                        <div class="card-header bg-info bg-opacity-10">
                            <h6 class="mb-0 text-info">
                                <i class="bx bx-star me-2"></i>Common Examples
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="small">
                                <div class="border-start border-3 border-primary ps-3 mb-3">
                                    <strong>Transport Allowance</strong><br>
                                    <span class="text-muted">Monthly transport compensation</span><br>
                                    <small class="text-primary">Amount: TZS 50,000 - 150,000</small>
                                </div>
                                <div class="border-start border-3 border-info ps-3 mb-3">
                                    <strong>Housing Allowance</strong><br>
                                    <span class="text-muted">Accommodation support</span><br>
                                    <small class="text-info">Amount: TZS 100,000 - 500,000</small>
                                </div>
                                <div class="border-start border-3 border-success ps-3 mb-3">
                                    <strong>Performance Bonus</strong><br>
                                    <span class="text-muted">One-time achievement reward</span><br>
                                    <small class="text-success">Amount: Variable based on performance</small>
                                </div>
                                <div class="border-start border-3 border-warning ps-3">
                                    <strong>Medical Allowance</strong><br>
                                    <span class="text-muted">Healthcare support</span><br>
                                    <small class="text-warning">Amount: TZS 25,000 - 100,000</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Important Notes Card -->
                    <div class="card border-warning">
                        <div class="card-header bg-warning bg-opacity-10">
                            <h6 class="mb-0 text-warning">
                                <i class="bx bx-error-circle me-2"></i>Important Notes
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning alert-sm mb-3">
                                <small>
                                    <i class="bx bx-info-circle me-1"></i>
                                    <strong>Payroll Integration:</strong> This allowance will be included in the next payroll run based on the effective date.
                                </small>
                            </div>
                            <div class="alert alert-info alert-sm mb-0">
                                <small>
                                    <i class="bx bx-shield me-1"></i>
                                    <strong>Tax Compliance:</strong> Tax treatment depends on the selected allowance type. Consult tax regulations for proper categorization.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection