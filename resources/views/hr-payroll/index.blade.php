@extends('layouts.main')

@section('title', 'HR & Payroll')

@push('styles')
<style>
    .module-card {
        position: relative;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .module-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .count-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
        color: white;
        z-index: 10;
    }
</style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => '#', 'icon' => 'bx bx-user']
        ]" />
            <h6 class="mb-0 text-uppercase">HR & PAYROLL MANAGEMENT</h6>
            <hr />

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">HR & Payroll Dashboard</h4>

                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bx bx-check-circle me-2"></i>
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <div class="row">
                                <!-- Employee Management -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-primary">{{ number_format($stats['employees']['total']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-user-plus text-primary" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Employee Management</h5>
                                            <p class="card-text">Manage employee records, personal information, and employment details.</p>
                                            <a href="{{ route('hr.employees.index') }}" class="btn btn-primary">
                                                <i class="bx bx-group me-1"></i>Manage Employees
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payroll Processing -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-success">{{ number_format($stats['payrolls']['total']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-money text-success" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Payroll Processing</h5>
                                            <p class="card-text">Process monthly salaries, calculate deductions, and generate payslips.</p>
                                            <a href="{{ route('hr.payrolls.index') }}" class="btn btn-success">
                                                <i class="bx bx-calculator me-1"></i>Manage Payrolls
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- HR Departments -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-info">{{ number_format($stats['departments']['total']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-buildings text-info" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">HR Departments</h5>
                                            <p class="card-text">Manage organizational departments and departmental structures.</p>
                                            <a href="{{ route('hr.departments.index') }}" class="btn btn-info">
                                                <i class="bx bx-building me-1"></i>Manage Departments
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- HR Positions -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-warning">{{ number_format($stats['positions']['total']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-briefcase text-warning" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">HR Positions</h5>
                                            <p class="card-text">Manage job positions, roles, and responsibilities.</p>
                                            <a href="{{ route('hr.positions.index') }}" class="btn btn-warning">
                                                <i class="bx bx-briefcase me-1"></i>Manage Positions
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Job Grades -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-primary">{{ number_format($stats['job_grades']['total']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-layer text-primary" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Job Grades</h5>
                                            <p class="card-text">Manage job grades with salary bands and grade structures.</p>
                                            <a href="{{ route('hr.job-grades.index') }}" class="btn btn-primary">
                                                <i class="bx bx-layer me-1"></i>Manage Job Grades
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contracts -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-info">{{ number_format($stats['contracts']['total']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-file text-info" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Employee Contracts</h5>
                                            <p class="card-text">Manage employee contracts, amendments, and renewals.</p>
                                            <a href="{{ route('hr.contracts.index') }}" class="btn btn-info">
                                                <i class="bx bx-file me-1"></i>Manage Contracts
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Employee Compliance -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-success">{{ number_format($stats['compliance']['total']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-check-circle text-success" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Employee Compliance</h5>
                                            <p class="card-text">Track PAYE, Pension, NHIF, WCF, and SDL compliance records.</p>
                                            <a href="{{ route('hr.employee-compliance.index') }}" class="btn btn-success">
                                                <i class="bx bx-check-circle me-1"></i>Manage Compliance
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                {{-- External Loan Institutions --}}
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-info">{{ number_format($stats['external_loan_institutions']['total']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-building text-info" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">External Loan Institutions</h5>
                                            <p class="card-text">Manage external loan institutions, banks, and other financial institutions.</p>
                                            <a href="{{ route('hr.external-loan-institutions.index') }}" class="btn btn-info">
                                                <i class="bx bx-building me-1"></i>Manage External Loan Institutions
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Phase 2: Time, Attendance & Leave Enhancement -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3"><i class="bx bx-time-five me-2"></i>Time, Attendance & Leave Management</h5>
                                    <hr>
                                </div>

                                <!-- Work Schedules -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-primary">{{ number_format($stats['work_schedules']['total']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-calendar-week text-primary" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Work Schedules</h5>
                                            <p class="card-text">Define work schedules, weekly patterns, and standard working hours.</p>
                                            <a href="{{ route('hr.work-schedules.index') }}" class="btn btn-primary">
                                                <i class="bx bx-calendar-week me-1"></i>Manage Schedules
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Shifts -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-info">{{ number_format($stats['shifts']['total']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-time-five text-info" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Shifts</h5>
                                            <p class="card-text">Manage work shifts, shift timings, and shift differentials.</p>
                                            <a href="{{ route('hr.shifts.index') }}" class="btn btn-info">
                                                <i class="bx bx-time-five me-1"></i>Manage Shifts
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Employee Schedules -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-success">{{ number_format($stats['employee_schedules']['total']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-user-check text-success" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Employee Schedules</h5>
                                            <p class="card-text">Assign work schedules and shifts to employees with effective dating.</p>
                                            <a href="{{ route('hr.employee-schedules.index') }}" class="btn btn-success">
                                                <i class="bx bx-user-check me-1"></i>Manage Assignments
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Attendance Management -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-warning">{{ number_format($stats['attendance']['this_month']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-time text-warning" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Attendance Management</h5>
                                            <p class="card-text">Track employee attendance, clock in/out, hours worked, and exceptions.</p>
                                            <a href="{{ route('hr.attendance.index') }}" class="btn btn-warning">
                                                <i class="bx bx-calendar me-1"></i>Manage Attendance
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Timesheets -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-info">{{ number_format($stats['timesheets']['this_month'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-time-five text-info" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Timesheets</h5>
                                            <p class="card-text">Track time allocation by department/project, priorities vs achievements, and activity types.</p>
                                            <a href="{{ route('hr.timesheets.index') }}" class="btn btn-info">
                                                <i class="bx bx-time-five me-1"></i>Manage Timesheets
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Overtime Rules -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-secondary">{{ number_format($stats['overtime_rules']['total']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-cog text-secondary" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Overtime Rules</h5>
                                            <p class="card-text">Configure overtime rates, rules, and approval requirements by grade and day type.</p>
                                            <a href="{{ route('hr.overtime-rules.index') }}" class="btn btn-secondary">
                                                <i class="bx bx-cog me-1"></i>Manage Rules
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Overtime Requests -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-danger">{{ number_format($stats['overtime_requests']['total']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-time text-danger" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Overtime Requests</h5>
                                            <p class="card-text">Manage overtime requests, approvals, and track overtime hours.</p>
                                            <a href="{{ route('hr.overtime-requests.index') }}" class="btn btn-danger">
                                                <i class="bx bx-time me-1"></i>Manage Requests
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Holiday Calendars -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-primary">{{ number_format($stats['holiday_calendars']['total']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-calendar-heart text-primary" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Holiday Calendars</h5>
                                            <p class="card-text">Manage public holidays, company holidays, and regional holidays.</p>
                                            <a href="{{ route('hr.holiday-calendars.index') }}" class="btn btn-primary">
                                                <i class="bx bx-calendar-heart me-1"></i>Manage Holidays
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Biometric Devices -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-info">{{ number_format($stats['biometric_devices']['total']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-fingerprint text-info" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Biometric Devices</h5>
                                            <p class="card-text">Configure and manage biometric devices for automatic attendance capture.</p>
                                            <a href="{{ route('hr.biometric-devices.index') }}" class="btn btn-info">
                                                <i class="bx bx-fingerprint me-1"></i>Manage Devices
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Leave Management -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-success">{{ number_format($stats['leave']['total_requests']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-calendar-check text-success" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Leave Management</h5>
                                            <p class="card-text">Manage employee leave requests, approvals, and leave balances.</p>
                                            <a href="{{ route('hr.leave.index') }}" class="btn btn-success">
                                                <i class="bx bx-calendar-plus me-1"></i>Manage Leaves
                                            </a>
                                        </div>
                                    </div>
                                </div>


                                <!-- Salary Advance Management -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-danger">{{ number_format($stats['salary_advances']['total']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-credit-card text-danger" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Salary Advance Management</h5>
                                            <p class="card-text">Process and manage employee salary advance requests and repayments.</p>
                                            <a href="{{ route('hr.salary-advances.index') }}" class="btn btn-danger">
                                                <i class="bx bx-credit-card me-1"></i>Manage Advances
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- External Loan Management -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-primary">{{ number_format($stats['external_loans']['total']) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-credit-card-alt text-primary" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">External Loan Management</h5>
                                            <p class="card-text">Manage external loans, bank loans, and loan deductions from salaries.</p>
                                            <a href="{{ route('hr.external-loans.index') }}" class="btn btn-primary">
                                                <i class="bx bx-credit-card-alt me-1"></i>External Loans
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- HESLB Loan Management -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-info">{{ number_format($stats['heslb_loans']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-book text-info" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">HESLB Loan Management</h5>
                                            <p class="card-text">Manage Higher Education Students' Loans Board loans and track repayments.</p>
                                            <a href="{{ route('hr.heslb-loans.index') }}" class="btn btn-info">
                                                <i class="bx bx-book me-1"></i>HESLB Loans
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Phase 3: Payroll Enhancement & Statutory Compliance -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3"><i class="bx bx-calculator me-2"></i>Payroll Enhancement & Statutory Compliance</h5>
                                    <hr>
                                </div>

                                <!-- Payroll Calendars -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-primary">{{ number_format($stats['payroll_calendars']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-calendar text-primary" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Payroll Calendars</h5>
                                            <p class="card-text">Manage payroll cycles, cut-off dates, and pay dates.</p>
                                            <a href="{{ route('hr.payroll-calendars.index') }}" class="btn btn-primary">
                                                <i class="bx bx-calendar me-1"></i>Manage Calendars
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pay Groups -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-info">{{ number_format($stats['pay_groups']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-group text-info" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Pay Groups</h5>
                                            <p class="card-text">Categorize employees by payment frequency and rules.</p>
                                            <a href="{{ route('hr.pay-groups.index') }}" class="btn btn-info">
                                                <i class="bx bx-group me-1"></i>Manage Pay Groups
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Salary Components -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-success">{{ number_format($stats['salary_components']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-calculator text-success" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Salary Components</h5>
                                            <p class="card-text">Define earnings and deductions components for flexible salary structures.</p>
                                            <a href="{{ route('hr.salary-components.index') }}" class="btn btn-success">
                                                <i class="bx bx-calculator me-1"></i>Manage Components
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Employee Salary Structures -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-info">{{ number_format($stats['employee_salary_structures']['with_structure'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-money text-info" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Employee Salary Structures</h5>
                                            <p class="card-text">Assign salary components to employees and manage their salary structures.</p>
                                            <a href="{{ route('hr.employee-salary-structure.index') }}" class="btn btn-info">
                                                <i class="bx bx-money me-1"></i>Manage Structures
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Statutory Rules -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-danger">{{ number_format($stats['statutory_rules']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-shield text-danger" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Statutory Rules</h5>
                                            <p class="card-text">Configure Tanzania statutory compliance rules (PAYE, NHIF, Pension, WCF, SDL, HESLB).</p>
                                            <a href="{{ route('hr.statutory-rules.index') }}" class="btn btn-danger">
                                                <i class="bx bx-shield me-1"></i>Manage Rules
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payroll Settings -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-dark">-</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-cog text-dark" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">HR & Payroll Settings</h5>
                                            <p class="card-text">Configure payroll settings and HR settings.</p>
                                            <a href="{{ route('hr.payroll-settings.index') }}" class="btn btn-dark">
                                                <i class="bx bx-cog me-1"></i>Payroll Settings
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Phase 5: Performance & Training -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3"><i class="bx bx-trophy me-2"></i>Performance & Training Management</h5>
                                    <hr>
                                </div>

                                <!-- KPIs -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-warning">{{ number_format($stats['kpis']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-target-lock text-warning" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">KPIs</h5>
                                            <p class="card-text">Define and manage Key Performance Indicators for appraisals.</p>
                                            <a href="{{ route('hr.kpis.index') }}" class="btn btn-warning">
                                                <i class="bx bx-target-lock me-1"></i>Manage KPIs
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Appraisal Cycles -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-primary">{{ number_format($stats['appraisal_cycles']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-calendar text-primary" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Appraisal Cycles</h5>
                                            <p class="card-text">Manage annual, quarterly, and probation appraisal cycles.</p>
                                            <a href="{{ route('hr.appraisal-cycles.index') }}" class="btn btn-primary">
                                                <i class="bx bx-calendar me-1"></i>Manage Cycles
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Appraisals -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-success">{{ number_format($stats['appraisals']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-clipboard text-success" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Appraisals</h5>
                                            <p class="card-text">Create and manage employee performance appraisals with KPI scoring.</p>
                                            <a href="{{ route('hr.appraisals.index') }}" class="btn btn-success">
                                                <i class="bx bx-clipboard me-1"></i>Manage Appraisals
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Training Programs -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-info">{{ number_format($stats['training_programs']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-book-open text-info" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Training Programs</h5>
                                            <p class="card-text">Manage internal and external training programs.</p>
                                            <a href="{{ route('hr.training-programs.index') }}" class="btn btn-info">
                                                <i class="bx bx-book-open me-1"></i>Manage Programs
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Training Attendance -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-success">{{ number_format($stats['training_attendance']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-user-check text-success" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Training Attendance</h5>
                                            <p class="card-text">Track employee attendance and completion of training programs.</p>
                                            <a href="{{ route('hr.training-attendance.index') }}" class="btn btn-success">
                                                <i class="bx bx-user-check me-1"></i>Manage Attendance
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Employee Skills -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-primary">{{ number_format($stats['employee_skills']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-certification text-primary" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Employee Skills</h5>
                                            <p class="card-text">Maintain employee skills inventory and certifications.</p>
                                            <a href="{{ route('hr.employee-skills.index') }}" class="btn btn-primary">
                                                <i class="bx bx-certification me-1"></i>Manage Skills
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Training Bonds -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-danger">{{ number_format($stats['training_bonds']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-lock text-danger" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Training Bonds</h5>
                                            <p class="card-text">Manage training bond agreements and track fulfillment.</p>
                                            <a href="{{ route('hr.training-bonds.index') }}" class="btn btn-danger">
                                                <i class="bx bx-lock me-1"></i>Manage Bonds
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Phase 6: Employment Lifecycle Management -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3"><i class="bx bx-briefcase me-2"></i>Employment Lifecycle Management</h5>
                                    <hr>
                                </div>

                                <!-- Vacancy Requisitions -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-primary">{{ number_format($stats['vacancy_requisitions']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-file-blank text-primary" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Vacancy Requisitions</h5>
                                            <p class="card-text">Create and manage job vacancy requisitions with approval workflow.</p>
                                            <a href="{{ route('hr.vacancy-requisitions.index') }}" class="btn btn-primary">
                                                <i class="bx bx-file-blank me-1"></i>Manage Requisitions
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Applicants -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-info">{{ number_format($stats['applicants']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-user-plus text-info" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Applicants</h5>
                                            <p class="card-text">Manage applicant database and track recruitment pipeline.</p>
                                            <a href="{{ route('hr.applicants.index') }}" class="btn btn-info">
                                                <i class="bx bx-user-plus me-1"></i>Manage Applicants
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Interview Records -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-warning">{{ number_format($stats['interviews']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-conversation text-warning" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Interview Records</h5>
                                            <p class="card-text">Schedule interviews and record feedback and scores.</p>
                                            <a href="{{ route('hr.interview-records.index') }}" class="btn btn-warning">
                                                <i class="bx bx-conversation me-1"></i>Manage Interviews
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Offer Letters -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-success">{{ number_format($stats['offer_letters']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-envelope text-success" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Offer Letters</h5>
                                            <p class="card-text">Create and manage employment offer letters with approval workflow.</p>
                                            <a href="{{ route('hr.offer-letters.index') }}" class="btn btn-success">
                                                <i class="bx bx-envelope me-1"></i>Manage Offers
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Onboarding Checklists -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-secondary">{{ number_format($stats['onboarding_checklists']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-list-check text-secondary" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Onboarding Checklists</h5>
                                            <p class="card-text">Create templates for employee onboarding processes.</p>
                                            <a href="{{ route('hr.onboarding-checklists.index') }}" class="btn btn-secondary">
                                                <i class="bx bx-list-check me-1"></i>Manage Checklists
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Onboarding Records -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-primary">{{ number_format($stats['onboarding_records']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-user-check text-primary" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Onboarding Records</h5>
                                            <p class="card-text">Track employee onboarding progress and activate payroll eligibility.</p>
                                            <a href="{{ route('hr.onboarding-records.index') }}" class="btn btn-primary">
                                                <i class="bx bx-user-check me-1"></i>Manage Onboarding
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Confirmation Requests -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-info">{{ number_format($stats['confirmation_requests']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-check-circle text-info" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Confirmation Requests</h5>
                                            <p class="card-text">Manage probation reviews and employee confirmations.</p>
                                            <a href="{{ route('hr.confirmation-requests.index') }}" class="btn btn-info">
                                                <i class="bx bx-check-circle me-1"></i>Manage Confirmations
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Employee Transfers -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-warning">{{ number_format($stats['employee_transfers']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-transfer text-warning" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Employee Transfers</h5>
                                            <p class="card-text">Manage employee transfers between departments, branches, or positions.</p>
                                            <a href="{{ route('hr.employee-transfers.index') }}" class="btn btn-warning">
                                                <i class="bx bx-transfer me-1"></i>Manage Transfers
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Employee Promotions -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-success">{{ number_format($stats['employee_promotions']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-trophy text-success" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Employee Promotions</h5>
                                            <p class="card-text">Manage employee promotions with job grade and salary adjustments.</p>
                                            <a href="{{ route('hr.employee-promotions.index') }}" class="btn btn-success">
                                                <i class="bx bx-trophy me-1"></i>Manage Promotions
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Phase 7: Discipline, Grievance & Exit -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3"><i class="bx bx-shield-quarter me-2"></i>Discipline, Grievance & Exit Management</h5>
                                    <hr>
                                </div>

                                <!-- Disciplinary Cases -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-danger">{{ number_format($stats['disciplinary_cases']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-file text-danger" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Disciplinary Cases</h5>
                                            <p class="card-text">Manage disciplinary cases, investigations, and outcomes.</p>
                                            <a href="{{ route('hr.disciplinary-cases.index') }}" class="btn btn-danger">
                                                <i class="bx bx-file me-1"></i>Manage Cases
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Grievances -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-warning">{{ number_format($stats['grievances']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-error text-warning" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Grievances</h5>
                                            <p class="card-text">Track and manage employee grievances and complaints.</p>
                                            <a href="{{ route('hr.grievances.index') }}" class="btn btn-warning">
                                                <i class="bx bx-error me-1"></i>Manage Grievances
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Exit Management -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card module-card h-100">
                                        <div class="count-badge bg-dark">{{ number_format($stats['exits']['total'] ?? 0) }}</div>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-log-out text-dark" style="font-size: 3rem;"></i>
                                            </div>
                                            <h5 class="card-title">Exit Management</h5>
                                            <p class="card-text">Manage employee exits, clearance checklists, and final payroll.</p>
                                            <a href="{{ route('hr.exits.index') }}" class="btn btn-dark">
                                                <i class="bx bx-log-out me-1"></i>Manage Exits
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <h6 class="alert-heading">
                                            <i class="bx bx-info-circle me-2"></i>HR & Payroll Module
                                        </h6>
                                        <p class="mb-0">
                                            The HR & Payroll module provides comprehensive features including employee management, payroll
                                            processing, attendance tracking, leave management, performance evaluation, and
                                            comprehensive reporting.
                                        </p>
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
