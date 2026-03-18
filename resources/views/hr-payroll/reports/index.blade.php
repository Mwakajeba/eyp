@extends('layouts.main')

@section('title', 'Payroll Reports')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Payroll Reports', 'url' => '#', 'icon' => 'bx bx-money']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-money me-2"></i>Payroll Reports
                            </h4>
                        </div>
                        
                        <p class="text-muted mb-4">
                            Comprehensive payroll analysis and reporting tools to track payroll processing, 
                            analyze costs, monitor compliance, and make data-driven HR decisions.
                        </p>

                        <div class="row">
                            <!-- Payroll Summary Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-bar-chart-alt-2 fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Payroll Summary</h5>
                                        <p class="card-text">High-level payroll control, approval, and comparison across periods. Executive & Audit Critical.</p>
                                        <a href="{{ route('hr.payroll-reports.payroll-summary') }}" class="btn btn-primary">
                                            <i class="bx bx-bar-chart-alt-2 me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Payroll by Department Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-buildings fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Payroll by Department</h5>
                                        <p class="card-text">Department-wise payroll costs, employee counts, and average salaries</p>
                                        <a href="{{ route('hr.payroll-reports.payroll-by-department') }}" class="btn btn-success">
                                            <i class="bx bx-buildings me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Payroll by Pay Group Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-group fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Payroll by Pay Group</h5>
                                        <p class="card-text">Pay group-wise payroll analysis and comparison</p>
                                        <a href="{{ route('hr.payroll-reports.payroll-by-pay-group') }}" class="btn btn-info">
                                            <i class="bx bx-group me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Statutory Compliance Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-check-circle fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Statutory Compliance</h5>
                                        <p class="card-text">NHIF, NSSF, PAYE, WCF, SDL, HESLB deductions and compliance status</p>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a href="{{ route('hr.payroll-reports.statutory-compliance') }}" class="btn btn-warning btn-sm">
                                                <i class="bx bx-check-circle me-1"></i> Basic
                                            </a>
                                            <a href="{{ route('hr.payroll-reports.statutory-compliance-enhanced') }}" class="btn btn-warning btn-sm">
                                                <i class="bx bx-shield me-1"></i> Enhanced
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Employee Payroll History Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-user fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Employee Payroll History</h5>
                                        <p class="card-text">Individual employee payroll history, trends, and year-to-date totals</p>
                                        <a href="{{ route('hr.payroll-reports.employee-payroll-history') }}" class="btn btn-danger">
                                            <i class="bx bx-user me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Payroll Cost Analysis Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-pie-chart fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Payroll Cost Analysis</h5>
                                        <p class="card-text">Breakdown of payroll costs: basic salary, allowances, deductions, and net pay</p>
                                        <a href="{{ route('hr.payroll-reports.payroll-cost-analysis') }}" class="btn btn-primary">
                                            <i class="bx bx-pie-chart me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Overtime Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-time fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Overtime Report</h5>
                                        <p class="card-text">Overtime hours, costs, and trends by employee, department, or period. Detect abuse, ensure compliance.</p>
                                        <a href="{{ route('hr.payroll-reports.overtime') }}" class="btn btn-success">
                                            <i class="bx bx-time me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Leave Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-calendar fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Leave Report</h5>
                                        <p class="card-text">Leave affects payroll directly. Track paid and unpaid leave days by employee and leave type.</p>
                                        <a href="{{ route('hr.payroll-reports.leave') }}" class="btn btn-info">
                                            <i class="bx bx-calendar me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Payroll Audit Trail Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-history fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Payroll Audit Trail</h5>
                                        <p class="card-text">Complete audit log of all payroll changes, approvals, and modifications</p>
                                        <a href="{{ route('hr.payroll-reports.payroll-audit-trail') }}" class="btn btn-warning">
                                            <i class="bx bx-history me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Year-to-Date Summary Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-calendar-check fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Year-to-Date Summary</h5>
                                        <p class="card-text">Cumulative payroll totals, deductions, and tax summaries for the fiscal year</p>
                                        <a href="{{ route('hr.payroll-reports.year-to-date-summary') }}" class="btn btn-danger">
                                            <i class="bx bx-calendar-check me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Payroll Variance Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-trending-up fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Payroll Variance Report</h5>
                                        <p class="card-text">Compare payroll periods to identify variances and trends</p>
                                        <a href="{{ route('hr.payroll-reports.payroll-variance') }}" class="btn btn-primary">
                                            <i class="bx bx-trending-up me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Bank Payment Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-credit-card fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Bank Payment Report</h5>
                                        <p class="card-text">Payment details by bank account, payment date, and payment status</p>
                                        <a href="{{ route('hr.payroll-reports.bank-payment') }}" class="btn btn-success">
                                            <i class="bx bx-credit-card me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- PAYE Remittance Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-receipt fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">PAYE Remittance (TRA)</h5>
                                        <p class="card-text">Monthly PAYE declaration. Basis for TRA payment & audit. Supports P10/annual reconciliation.</p>
                                        <a href="{{ route('hr.payroll-reports.paye-remittance') }}" class="btn btn-danger">
                                            <i class="bx bx-receipt me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- NSSF Remittance Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-receipt fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">NSSF Remittance</h5>
                                        <p class="card-text">Employee & employer pension contributions. Mandatory for NSSF compliance.</p>
                                        <a href="{{ route('hr.payroll-reports.nssf-remittance') }}" class="btn btn-primary">
                                            <i class="bx bx-receipt me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- NHIF Remittance Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-receipt fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">NHIF Remittance</h5>
                                        <p class="card-text">Health insurance contribution. Employer-funded (most cases).</p>
                                        <a href="{{ route('hr.payroll-reports.nhif-remittance') }}" class="btn btn-info">
                                            <i class="bx bx-receipt me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- WCF Remittance Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-receipt fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">WCF Remittance</h5>
                                        <p class="card-text">Workers' Compensation Fund (employer only). Based on gross salary.</p>
                                        <a href="{{ route('hr.payroll-reports.wcf-remittance') }}" class="btn btn-warning">
                                            <i class="bx bx-receipt me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- SDL Remittance Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-receipt fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">SDL Remittance</h5>
                                        <p class="card-text">Skills Development Levy (employer only). Submitted monthly to TRA.</p>
                                        <a href="{{ route('hr.payroll-reports.sdl-remittance') }}" class="btn btn-secondary">
                                            <i class="bx bx-receipt me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- HESLB Remittance Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-receipt fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">HESLB Loan Repayment</h5>
                                        <p class="card-text">Student loan recovery. Employee-specific deductions.</p>
                                        <a href="{{ route('hr.payroll-reports.heslb-remittance') }}" class="btn btn-danger">
                                            <i class="bx bx-receipt me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Combined Statutory Remittance Control Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-dark position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-shield fs-1 text-dark"></i>
                                        </div>
                                        <h5 class="card-title">Combined Statutory Remittance</h5>
                                        <p class="card-text">One-page CFO & Auditor view. Confirms all statutory obligations. Very Important.</p>
                                        <a href="{{ route('hr.payroll-reports.combined-statutory-remittance') }}" class="btn btn-dark">
                                            <i class="bx bx-shield me-1"></i> View Report
                                        </a>
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

