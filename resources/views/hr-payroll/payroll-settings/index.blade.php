@extends('layouts.main')

@section('title', 'Payroll Settings')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Payroll Settings', 'url' => '#', 'icon' => 'bx bx-cog']
        ]" />
            <h6 class="mb-0 text-uppercase">Payroll Settings</h6>
            <hr />

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <div class="row g-4">
                                <!-- Approval Settings -->
                                <div class="col-md-6 col-lg-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-shield-check text-warning" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Payroll Approval Settings</h5>
                                            <p class="card-text">Configure multi-level approval workflow for payroll processing.</p>
                                            @if($approvalSettings)
                                                <div class="mb-2">
                                                    <span class="badge {{ $approvalSettings->approval_required ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ $approvalSettings->approval_required ? 'Enabled' : 'Disabled' }}
                                                    </span>
                                                    @if($approvalSettings->approval_required)
                                                        <span class="badge bg-primary ms-1">{{ $approvalSettings->approval_levels }} Level(s)</span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="mb-2">
                                                    <span class="badge bg-secondary">Not Configured</span>
                                                </div>
                                            @endif
                                            <a href="{{ route('hr-payroll.approval-settings.index') }}" class="btn btn-warning">
                                                <i class="bx bx-cog me-1"></i>Configure Approvals
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Approval Settings -->
                                <div class="col-md-6 col-lg-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-credit-card text-success" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Payment Approval Settings</h5>
                                            <p class="card-text">Configure multi-level approval workflow for payroll payment processing.</p>
                                            @if($paymentApprovalSettings)
                                                <div class="mb-2">
                                                    <span class="badge {{ $paymentApprovalSettings->payment_approval_required ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ $paymentApprovalSettings->payment_approval_required ? 'Enabled' : 'Disabled' }}
                                                    </span>
                                                    @if($paymentApprovalSettings->payment_approval_required)
                                                        <span class="badge bg-primary ms-1">{{ $paymentApprovalSettings->payment_approval_levels }} Level(s)</span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="mb-2">
                                                    <span class="badge bg-secondary">Not Configured</span>
                                                </div>
                                            @endif
                                            <a href="{{ route('hr-payroll.payment-approval-settings.index') }}" class="btn btn-success">
                                                <i class="bx bx-cog me-1"></i>Configure Payment Approvals
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Overtime Approval Settings -->
                                <div class="col-md-6 col-lg-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-time-five text-warning" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Overtime Approval Settings</h5>
                                            <p class="card-text">Configure multi-level approval workflow for overtime requests.</p>
                                            @if($overtimeApprovalSettings)
                                                <div class="mb-2">
                                                    <span class="badge {{ $overtimeApprovalSettings->approval_required ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ $overtimeApprovalSettings->approval_required ? 'Enabled' : 'Disabled' }}
                                                    </span>
                                                    @if($overtimeApprovalSettings->approval_required)
                                                        <span class="badge bg-primary ms-1">{{ $overtimeApprovalSettings->approval_levels }} Level(s)</span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="mb-2">
                                                    <span class="badge bg-secondary">Not Configured</span>
                                                </div>
                                            @endif
                                            <a href="{{ route('hr-payroll.overtime-approval-settings.index') }}" class="btn btn-warning">
                                                <i class="bx bx-cog me-1"></i>Configure Overtime Approvals
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Timesheet Approval Settings -->
                                <div class="col-md-6 col-lg-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-time text-info" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Timesheet Approval Settings</h5>
                                            <p class="card-text">Configure who can approve or reject employee timesheets.</p>
                                            @if($timesheetApprovalSettings)
                                                <div class="mb-2">
                                                    <span class="badge {{ $timesheetApprovalSettings->approval_required ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ $timesheetApprovalSettings->approval_required ? 'Enabled' : 'Disabled' }}
                                                    </span>
                                                </div>
                                            @else
                                                <div class="mb-2">
                                                    <span class="badge bg-secondary">Not Configured</span>
                                                </div>
                                            @endif
                                            <a href="{{ route('hr-payroll.timesheet-approval-settings.index') }}" class="btn btn-info">
                                                <i class="bx bx-cog me-1"></i>Configure Timesheet Approvals
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vacancy Requisition Approval Settings -->
                                <div class="col-md-6 col-lg-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-file-blank text-primary" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Vacancy Requisition Approval Settings</h5>
                                            <p class="card-text">Configure multi-level approval workflow for vacancy requisitions.</p>
                                            @if($vacancyRequisitionApprovalSettings)
                                                <div class="mb-2">
                                                    <span class="badge {{ $vacancyRequisitionApprovalSettings->approval_required ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ $vacancyRequisitionApprovalSettings->approval_required ? 'Enabled' : 'Disabled' }}
                                                    </span>
                                                    @if($vacancyRequisitionApprovalSettings->approval_required)
                                                        <span class="badge bg-primary ms-1">{{ $vacancyRequisitionApprovalSettings->approval_levels }} Level(s)</span>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="mb-2">
                                                    <span class="badge bg-secondary">Not Configured</span>
                                                </div>
                                            @endif
                                            <a href="{{ route('hr-payroll.vacancy-requisition-approval-settings.index') }}" class="btn btn-primary">
                                                <i class="bx bx-cog me-1"></i>Configure Vacancy Requisition Approvals
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Trade Union Management -->
                                <div class="col-md-6 col-lg-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-group text-primary" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Trade Union Management</h5>
                                            <p class="card-text">Manage trade unions and their configurations for payroll
                                                processing.</p>
                                            <a href="{{ route('hr.trade-unions.index') }}" class="btn btn-primary">
                                                <i class="bx bx-group me-1"></i>Manage Trade Unions
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- File Type Management -->
                                <div class="col-md-6 col-lg-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-file text-info" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">File Type Management</h5>
                                            <p class="card-text">Configure file types for HR documents and employee file
                                                uploads.</p>
                                            <a href="{{ route('hr.file-types.index') }}" class="btn btn-info">
                                                <i class="bx bx-file me-1"></i>Manage File Types
                                            </a>
                                        </div>
                                    </div>
                                </div>


                                <!-- Salary Advance Management -->
                                {{-- <div class="col-md-6 col-lg-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-credit-card text-warning" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Salary Advance Management</h5>
                                            <p class="card-text">Manage salary advances and loan deductions for employees.
                                            </p>
                                            <button class="btn btn-warning" disabled>
                                                <i class="bx bx-credit-card me-1"></i>Coming Soon
                                            </button>
                                        </div>
                                    </div>
                                </div> --}}

                                <!-- External Loan Management -->
                                {{-- <div class="col-md-6 col-lg-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-credit-card-alt text-info" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">External Loan Management</h5>
                                            <p class="card-text">Manage external loans and their payroll deductions.</p>
                                            <button class="btn btn-info" disabled>
                                                <i class="bx bx-credit-card-alt me-1"></i>Coming Soon
                                            </button>
                                        </div>
                                    </div>
                                </div> --}}

                                <!-- Payroll Chart Accounts -->
                                <div class="col-md-6 col-lg-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-calculator text-danger" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Payroll Chart Accounts</h5>
                                            <p class="card-text">Map payroll transactions to chart accounts for accounting.
                                            </p>
                                            <a href="{{ route('hr.payroll.chart-accounts.index') }}" class="btn btn-danger">
                                                <i class="bx bx-calculator me-1"></i>Configure Accounts
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payroll Periods -->
                                {{-- <div class="col-md-6 col-lg-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-calendar text-secondary" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Payroll Periods</h5>
                                            <p class="card-text">Define and manage payroll periods and processing schedules.
                                            </p>
                                            <button class="btn btn-secondary" disabled>
                                                <i class="bx bx-calendar me-1"></i>Coming Soon
                                            </button>
                                        </div>
                                    </div>
                                </div> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
@endsection
