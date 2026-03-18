@extends('layouts.main')

@section('title', 'Payroll Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Payrolls', 'url' => route('hr.payrolls.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

            <!-- Header Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h4 class="mb-1">
                                <i class="bx bx-money me-2"></i>
                                Payroll Details - {{ $payroll->month_name }} {{ $payroll->year }}
                            </h4>
                            <p class="text-muted mb-0">
                                <i class="bx bx-calendar me-1"></i>
                                Created: {{ $payroll->created_at->format('M d, Y h:i A') }}
                                @if($payroll->creator)
                                    by {{ $payroll->creator->name }}
                                @endif
                            </p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap mt-2">
                    @if($payroll->status === 'draft')
                                <button type="button" class="btn btn-success" onclick="processPayroll('{{ $payroll->hash_id }}')">
                            <i class="bx bx-calculator me-1"></i>Process Payroll
                        </button>
                                <a href="{{ route('hr.payrolls.edit', $payroll->hash_id) }}" class="btn btn-warning">
                            <i class="bx bx-edit me-1"></i>Edit
                        </a>
                                <button type="button" class="btn btn-danger" onclick="deletePayroll('{{ $payroll->hash_id }}')">
                            <i class="bx bx-trash me-1"></i>Delete
                        </button>
                    @elseif($payroll->status === 'processing')
                                <button type="button" class="btn btn-primary" onclick="approvePayroll('{{ $payroll->hash_id }}')">
                            <i class="bx bx-check me-1"></i>Approve
                        </button>
                                <button type="button" class="btn btn-secondary" onclick="rejectPayroll('{{ $payroll->hash_id }}')">
                            <i class="bx bx-x me-1"></i>Reject
                        </button>
                                <button type="button" class="btn btn-danger" onclick="deletePayroll('{{ $payroll->hash_id }}')">
                            <i class="bx bx-trash me-1"></i>Delete
                        </button>
                    @elseif($payroll->status === 'completed')
                        @if($payroll->payment_status === 'pending')
                                    <a href="{{ route('hr.payrolls.payment', $payroll->hash_id) }}" class="btn btn-success">
                                <i class="bx bx-credit-card me-1"></i>Process Payment
                            </a>
                        @else
                            <span class="btn btn-success disabled">
                                <i class="bx bx-check-double me-1"></i>Payment Completed
                            </span>
                        @endif
                    @elseif($payroll->status === 'paid')
                        <span class="btn btn-success disabled">
                            <i class="bx bx-check-double me-1"></i>Paid
                        </span>
                    @elseif($payroll->status === 'cancelled')
                                <button type="button" class="btn btn-danger" onclick="deletePayroll('{{ $payroll->hash_id }}')">
                            <i class="bx bx-trash me-1"></i>Delete
                        </button>
                    @endif
                    <a href="{{ route('hr.payrolls.index') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i>Back
                    </a>
                </div>
            </div>
                </div>
            </div>

            <!-- Status Badge -->
            <div class="row mb-4">
                <div class="col-12">
                    @php
                        $statusConfig = match ($payroll->status) {
                            'draft' => ['class' => 'bg-danger', 'icon' => 'bx bx-edit-alt', 'text' => 'Draft'],
                            'processing' => ['class' => 'bg-warning', 'icon' => 'bx bx-time-five', 'text' => 'Processing'],
                            'completed' => ['class' => 'bg-success', 'icon' => 'bx bx-check-circle', 'text' => 'Completed'],
                            'cancelled' => ['class' => 'bg-secondary', 'icon' => 'bx bx-x-circle', 'text' => 'Cancelled'],
                            'paid' => ['class' => 'bg-primary', 'icon' => 'bx bx-money', 'text' => 'Paid'],
                            default => ['class' => 'bg-secondary', 'icon' => 'bx bx-question-mark', 'text' => 'Unknown']
                        };
                                    @endphp
                    <div class="card border-{{ str_replace('bg-', '', $statusConfig['class']) }}">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <span class="badge {{ $statusConfig['class'] }} fs-6 px-3 py-2 me-3">
                                    <i class="{{ $statusConfig['icon'] }} me-1"></i>
                                    {{ $statusConfig['text'] }}
                                                        </span>
                                @if($payroll->status === 'completed')
                                    <span class="badge {{ $payroll->payment_status === 'paid' ? 'bg-success' : 'bg-warning' }} fs-6 px-3 py-2">
                                        <i class="{{ $payroll->payment_status === 'paid' ? 'bx bx-check-double' : 'bx bx-credit-card' }} me-1"></i>
                                        Payment {{ ucfirst($payroll->payment_status) }}
                                    </span>
                                                        @endif
                                                    </div>
                                            </div>
                                        </div>
                                </div>
                                </div>

            <!-- Approval Status Section -->
            @if($payroll->status === 'processing')
                @include('hr-payroll.payrolls.partials.approval-status', ['payroll' => $payroll])
            @elseif($payroll->status === 'completed')
                @include('hr-payroll.payrolls.partials.completed-status', ['payroll' => $payroll])
            @elseif($payroll->status === 'paid')
                @include('hr-payroll.payrolls.partials.paid-status', ['payroll' => $payroll])
            @elseif($payroll->status === 'cancelled')
                @include('hr-payroll.payrolls.partials.cancelled-status', ['payroll' => $payroll])
            @endif

            <!-- Summary Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Gross Pay
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        TZS {{ number_format($payroll->total_gross_pay ?? 0, 2) }}
                                    </div>
                            </div>
                                <div class="col-auto">
                                    <i class="bx bx-trending-up bx-lg text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
                        </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Total Deductions
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        TZS {{ number_format($payroll->total_deductions ?? 0, 2) }}
                                    </div>
                            </div>
                                <div class="col-auto">
                                    <i class="bx bx-trending-down bx-lg text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
                        </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Net Pay
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        TZS {{ number_format($payroll->net_pay ?? 0, 2) }}
                                    </div>
                            </div>
                                <div class="col-auto">
                                    <i class="bx bx-money bx-lg text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
                        </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Employees Count
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $payroll->payrollEmployees->count() ?? 0 }}
                                </div>
                                    </div>
                                <div class="col-auto">
                                    <i class="bx bx-group bx-lg text-info"></i>
                                </div>
                                    </div>
                                </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Breakdown -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="bx bx-trending-up me-2"></i>Earnings Breakdown</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        @php
                                            $earnings = [];
                                            $totalEarnings = 0;
                                            
                                            $basicSalary = $payroll->total_salary ?? 0;
                                            if ($basicSalary > 0.01) {
                                                $earnings['Basic Salary'] = $basicSalary;
                                                $totalEarnings += $basicSalary;
                                            }
                                            
                                            $allowances = $payroll->total_allowance ?? 0;
                                            if ($allowances > 0.01) {
                                                $earnings['Allowances'] = $allowances;
                                                $totalEarnings += $allowances;
                                            }
                                            
                                            $overtime = $payroll->payrollEmployees->sum('overtime') ?? 0;
                                            if ($overtime > 0.01) {
                                                $earnings['Overtime'] = $overtime;
                                                $totalEarnings += $overtime;
                                            }
                                        @endphp
                                        
                                        @forelse($earnings as $label => $amount)
                                        <tr>
                                            <td class="fw-bold">{{ $label }}:</td>
                                            <td class="text-end">TZS {{ number_format($amount, 2) }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="2" class="text-muted text-center">No earnings recorded</td>
                                        </tr>
                                        @endforelse
                                        
                                        <tr class="table-success">
                                            <td class="fw-bold fs-6">Total Gross Pay:</td>
                                            <td class="text-end fw-bold fs-6">TZS {{ number_format($payroll->total_gross_pay ?? 0, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                </div>

                            <!-- Employer Contributions Section -->
                            <hr class="my-3">
                            <h6 class="mb-3 text-info"><i class="bx bx-building me-2"></i>Employer Contributions</h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        @php
                                            $employerContributions = [];
                                            $totalEmployerContributions = 0;
                                            
                                            if (($payroll->total_nhif_employer ?? 0) > 0.01) {
                                                $employerContributions['NHIF (Employer)'] = $payroll->total_nhif_employer;
                                                $totalEmployerContributions += $payroll->total_nhif_employer;
                                            }
                                            if (($payroll->total_pension_employer ?? 0) > 0.01) {
                                                $employerContributions['Pension (Employer)'] = $payroll->total_pension_employer;
                                                $totalEmployerContributions += $payroll->total_pension_employer;
                                            }
                                            if (($payroll->total_wcf ?? 0) > 0.01) {
                                                $employerContributions['WCF'] = $payroll->total_wcf;
                                                $totalEmployerContributions += $payroll->total_wcf;
                                            }
                                            if (($payroll->total_sdl ?? 0) > 0.01) {
                                                $employerContributions['SDL'] = $payroll->total_sdl;
                                                $totalEmployerContributions += $payroll->total_sdl;
                                            }
                                        @endphp
                                        
                                        @forelse($employerContributions as $label => $amount)
                                        <tr>
                                            <td class="fw-bold">{{ $label }}:</td>
                                            <td class="text-end">TZS {{ number_format($amount, 2) }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="2" class="text-muted text-center">No employer contributions</td>
                                        </tr>
                                        @endforelse
                                        
                                        @if($totalEmployerContributions > 0.01)
                                        <tr class="table-info">
                                            <td class="fw-bold fs-6">Total Employer Contributions:</td>
                                            <td class="text-end fw-bold fs-6">TZS {{ number_format($totalEmployerContributions, 2) }}</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                                    </div>
                                    </div>
                </div>
            </div>

                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0"><i class="bx bx-trending-down me-2"></i>Deductions Breakdown</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        @php
                                            $deductions = [];
                                            $totalDeductions = 0;
                                            
                                            if (($payroll->total_nhif_employee ?? 0) > 0.01) {
                                                $deductions['NHIF (Employee)'] = $payroll->total_nhif_employee;
                                                $totalDeductions += $payroll->total_nhif_employee;
                                            }
                                            if (($payroll->total_pension_employee ?? 0) > 0.01) {
                                                $deductions['Pension (Employee)'] = $payroll->total_pension_employee;
                                                $totalDeductions += $payroll->total_pension_employee;
                                            }
                                            if (($payroll->total_payee ?? 0) > 0.01) {
                                                $deductions['PAYE'] = $payroll->total_payee;
                                                $totalDeductions += $payroll->total_payee;
                                            }
                                            if (($payroll->total_heslb ?? 0) > 0.01) {
                                                $deductions['HESLB'] = $payroll->total_heslb;
                                                $totalDeductions += $payroll->total_heslb;
                                            }
                                            if (($payroll->total_trade_union ?? 0) > 0.01) {
                                                $deductions['Trade Union'] = $payroll->total_trade_union;
                                                $totalDeductions += $payroll->total_trade_union;
                                            }
                                            if (($payroll->total_salary_advance_paid ?? 0) > 0.01) {
                                                $deductions['Salary Advance'] = $payroll->total_salary_advance_paid;
                                                $totalDeductions += $payroll->total_salary_advance_paid;
                                            }
                                            if (($payroll->total_external_loan_paid ?? 0) > 0.01) {
                                                $deductions['External Loan'] = $payroll->total_external_loan_paid;
                                                $totalDeductions += $payroll->total_external_loan_paid;
                                            }
                                        @endphp
                                        
                                        @forelse($deductions as $label => $amount)
                                        <tr>
                                            <td class="fw-bold">{{ $label }}:</td>
                                            <td class="text-end">TZS {{ number_format($amount, 2) }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="2" class="text-muted text-center">No deductions recorded</td>
                                        </tr>
                                        @endforelse
                                        
                                        <tr class="table-danger">
                                            <td class="fw-bold fs-6">Total Deductions:</td>
                                            <td class="text-end fw-bold fs-6">TZS {{ number_format($payroll->total_deductions ?? 0, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                    </div>
                                </div>
                    </div>
                </div>
            </div>

            <!-- Employee Details Table -->
            @if($payroll->status !== 'draft')
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="bx bx-group me-2"></i>Employee Payroll Details</h6>
                                <div>
                                    <button type="button" class="btn btn-light btn-sm" onclick="exportAllSlips('{{ $payroll->hash_id }}')">
                                        <i class="bx bx-download me-1"></i>Export All Slips
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover table-bordered" id="employeesTable" style="width:100%">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Employee</th>
                                                <th>Employee ID</th>
                                                <th>Department</th>
                                                <th>Position</th>
                                                <th class="text-end">Basic Salary</th>
                                                <th class="text-end">Allowances</th>
                                                <th class="text-end">Overtime</th>
                                                <th class="text-end">Gross Salary</th>
                                                <th class="text-end">Deductions</th>
                                                <th class="text-end">Net Salary</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <style>
        .border-left-primary {
            border-left: 4px solid #0d6efd !important;
        }
        .border-left-danger {
            border-left: 4px solid #dc3545 !important;
        }
        .border-left-success {
            border-left: 4px solid #198754 !important;
        }
        .border-left-info {
            border-left: 4px solid #0dcaf0 !important;
        }
        .text-xs {
            font-size: 0.7rem;
        }
        .text-gray-800 {
            color: #5a5c69 !important;
        }
        #employeesTable_wrapper .dataTables_filter input {
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
            padding: 0.375rem 0.75rem;
        }
        #employeesTable_wrapper .dataTables_length select {
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
            padding: 0.375rem 2rem 0.375rem 0.75rem;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script nonce="{{ $cspNonce ?? '' }}">
        // Process payroll function
        function processPayroll(payrollId) {
            Swal.fire({
                title: 'Process Payroll?',
                text: "This will calculate salaries for all employees. This may take a few minutes for large payrolls. Continue?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, process it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Processing Payroll...',
                        html: `
                                <div class="progress mb-3">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                </div>
                                <p id="progress-text">Initializing payroll processing...</p>
                            `,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    let progress = 0;
                    const progressBar = document.querySelector('.progress-bar');
                    const progressText = document.getElementById('progress-text');

                    const progressInterval = setInterval(() => {
                        progress += Math.random() * 15;
                        if (progress > 90) progress = 90;
                        progressBar.style.width = progress + '%';

                        if (progress < 30) {
                            progressText.textContent = 'Loading employee data...';
                        } else if (progress < 60) {
                            progressText.textContent = 'Calculating salaries and deductions...';
                        } else if (progress < 90) {
                            progressText.textContent = 'Updating payroll totals...';
                        } else {
                            progressText.textContent = 'Finalizing payroll...';
                        }
                    }, 500);

                    $.ajax({
                        url: "{{ route('hr.payrolls.process', $payroll->hash_id) }}",
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            clearInterval(progressInterval);
                            progressBar.style.width = '100%';
                            progressText.textContent = 'Payroll processed successfully!';

                            setTimeout(() => {
                                Swal.fire({
                                    title: 'Success!',
                                    text: response.message || 'Payroll processed successfully!',
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    window.location.reload();
                                });
                            }, 1000);
                        },
                        error: function (xhr) {
                            clearInterval(progressInterval);
                            const response = xhr.responseJSON;
                            Swal.fire('Error!', response.message || 'An error occurred while processing the payroll.', 'error');
                        }
                    });
                }
            });
        }

        // Delete payroll function
        function deletePayroll(payrollId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the payroll and all related employee data. This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait while we delete the payroll.',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: `{{ url('hr-payroll/payrolls') }}/${payrollId}`,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE'
                        },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    window.location.href = "{{ route('hr.payrolls.index') }}";
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function (xhr) {
                            const response = xhr.responseJSON;
                            Swal.fire('Error!', response.message || 'An error occurred while deleting the payroll.', 'error');
                        }
                    });
                }
            });
        }

        // Export all slips function
        function exportAllSlips(payrollId) {
            Swal.fire({
                title: 'Export All Payroll Slips?',
                text: "This will generate a PDF with all employee payroll slips. Continue?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, export!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Generating PDF...',
                        text: 'Please wait while we generate the payroll slips.',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    const link = document.createElement('a');
                    link.href = "{{ route('hr.payrolls.export-all-slips', $payroll->hash_id) }}";
                    link.download = '';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    setTimeout(() => {
                        Swal.close();
                    }, 2000);
                }
            });
        }

        // Approve payroll function
        function approvePayroll(payrollId) {
            Swal.fire({
                title: 'Approve Payroll?',
                input: 'textarea',
                inputLabel: 'Remarks (optional)',
                inputPlaceholder: 'Enter any remarks for this approval...',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Approve',
                cancelButtonText: 'Cancel',
                inputValidator: (value) => {
                    if (value && value.length > 500) {
                        return 'Remarks cannot exceed 500 characters';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Approving...',
                        text: 'Please wait while we approve the payroll.',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "{{ route('hr.payrolls.approve', $payroll->hash_id) }}",
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            remarks: result.value || ''
                        },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Approved!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function (xhr) {
                            const response = xhr.responseJSON;
                            Swal.fire('Error!', response.message || 'An error occurred while approving the payroll.', 'error');
                        }
                    });
                }
            });
        }

        // Reject payroll function
        function rejectPayroll(payrollId) {
            Swal.fire({
                title: 'Reject Payroll?',
                input: 'textarea',
                inputLabel: 'Rejection reason (required)',
                inputPlaceholder: 'Enter the reason for rejecting this payroll...',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Reject',
                cancelButtonText: 'Cancel',
                inputValidator: (value) => {
                    if (!value || value.trim() === '') {
                        return 'Please provide a reason for rejection';
                    }
                    if (value.length > 500) {
                        return 'Reason cannot exceed 500 characters';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Rejecting...',
                        text: 'Please wait while we reject the payroll.',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "{{ route('hr.payrolls.reject', $payroll->hash_id) }}",
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            remarks: result.value
                        },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Rejected!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function (xhr) {
                            const response = xhr.responseJSON;
                            Swal.fire('Error!', response.message || 'An error occurred while rejecting the payroll.', 'error');
                        }
                    });
                }
            });
        }

        // Approve payment function
        function approvePayment(payrollId) {
            @php
                $netSalary = $payroll->payrollEmployees->sum(function($employee) {
                    return $employee->basic_salary + $employee->allowance + $employee->other_allowances 
                         - ($employee->paye + $employee->pension + $employee->insurance + $employee->salary_advance 
                          + $employee->loans + $employee->trade_union + $employee->sdl + $employee->wcf 
                          + $employee->heslb + $employee->other_deductions);
                });
                $grossSalary = $payroll->total_gross_pay ?? 0;
                $totalDeductions = $payroll->total_deductions ?? 0;
                $employeeCount = $payroll->payrollEmployees->count();
                
                // Get bank account info
                $bankAccount = null;
                $bankAccountName = 'Not Selected';
                if ($payroll->payment_bank_account_id) {
                    $bankAccount = \App\Models\BankAccount::find($payroll->payment_bank_account_id);
                    $bankAccountName = $bankAccount ? $bankAccount->account_name . ' (' . $bankAccount->account_code . ')' : 'Not Found';
                } elseif ($payroll->payment_chart_account_id) {
                    $chartAccount = \App\Models\ChartAccount::find($payroll->payment_chart_account_id);
                    $bankAccountName = $chartAccount ? $chartAccount->account_name . ' (' . $chartAccount->account_code . ')' : 'Not Found';
                }
                
                // Get submitter info
                $submitter = $payroll->paymentSubmittedBy ?? null;
                $submitterName = $submitter ? $submitter->name : 'N/A';
                $submittedAt = $payroll->payment_submitted_at ? $payroll->payment_submitted_at->format('M d, Y h:i A') : 'N/A';
            @endphp
            
            const paymentSummary = `
                <div class="text-start mb-3">
                    <div class="card border-primary mb-3">
                        <div class="card-header bg-white">
                            <h6 class="mb-0 text-primary">
                                <i class="bx bx-info-circle me-2"></i>
                                Payment Verification Summary
                            </h6>
                        </div>
                        <div class="card-body bg-white">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong class="text-muted d-block mb-1">Payroll Period</strong>
                                    <span class="h6 mb-0">{{ $payroll->month_name }} {{ $payroll->year }}</span>
                                    <br>
                                    <small class="text-muted">{{ $payroll->reference }}</small>
                                    @if($payroll->payrollCalendar)
                                        <br>
                                        <small class="text-info">
                                            <i class="bx bx-calendar me-1"></i>Calendar: {{ $payroll->payrollCalendar->period_label }}
                                        </small>
                                    @endif
                                    @if($payroll->payGroup)
                                        <br>
                                        <small class="text-info">
                                            <i class="bx bx-group me-1"></i>Pay Group: {{ $payroll->payGroup->pay_group_code }} - {{ $payroll->payGroup->pay_group_name }}
                                        </small>
                                    @endif
                                </div>
                                <div class="col-6">
                                    <strong class="text-muted d-block mb-1">Employees</strong>
                                    <span class="h6 mb-0">{{ $employeeCount }} employee(s)</span>
                                </div>
                            </div>
                            @if($payroll->payrollCalendar)
                            <div class="row mb-3 border-top pt-3">
                                <div class="col-6">
                                    <strong class="text-muted d-block mb-1">
                                        <i class="bx bx-calendar-check me-1"></i>Cut-off Date
                                    </strong>
                                    <span class="h6 mb-0">{{ $payroll->payrollCalendar->cut_off_date->format('M d, Y') }}</span>
                                    <br>
                                    <small class="text-muted">Last date for payroll data inclusion</small>
                                </div>
                                <div class="col-6">
                                    <strong class="text-muted d-block mb-1">
                                        <i class="bx bx-money me-1"></i>Pay Date
                                    </strong>
                                    <span class="h6 mb-0">{{ $payroll->payrollCalendar->pay_date->format('M d, Y') }}</span>
                                    <br>
                                    <small class="text-muted">Actual payment date</small>
                                </div>
                            </div>
                            @elseif($payroll->payGroup)
                            <div class="row mb-3 border-top pt-3">
                                <div class="col-6">
                                    <strong class="text-muted d-block mb-1">
                                        <i class="bx bx-calendar-check me-1"></i>Cut-off Date
                                    </strong>
                                    <span class="h6 mb-0">{{ $payroll->payGroup->calculateCutOffDate($payroll->year, $payroll->month)->format('M d, Y') }}</span>
                                    <br>
                                    <small class="text-muted">Calculated from Pay Group settings</small>
                                </div>
                                <div class="col-6">
                                    <strong class="text-muted d-block mb-1">
                                        <i class="bx bx-money me-1"></i>Pay Date
                                    </strong>
                                    <span class="h6 mb-0">{{ $payroll->payGroup->calculatePayDate($payroll->year, $payroll->month)->format('M d, Y') }}</span>
                                    <br>
                                    <small class="text-muted">Calculated from Pay Group settings</small>
                                </div>
                            </div>
                            @endif
                            @if($payroll->payment_submitted_by || $payroll->payment_chart_account_id)
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong class="text-muted d-block mb-1">Submitted By</strong>
                                    <span class="h6 mb-0">{{ $submitterName }}</span>
                                    <br>
                                    <small class="text-muted">{{ $submittedAt }}</small>
                                </div>
                                <div class="col-6">
                                    <strong class="text-muted d-block mb-1">Bank Account</strong>
                                    <span class="h6 mb-0">{{ $bankAccountName }}</span>
                                    @if($payroll->payment_date)
                                    <br>
                                    <small class="text-muted">Payment Date: {{ \Carbon\Carbon::parse($payroll->payment_date)->format('M d, Y') }}</small>
                                    @endif
                                </div>
                            </div>
                            @endif
                            <hr>
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <td class="ps-0 text-muted">Gross Salary</td>
                                        <td class="text-end pe-0 fw-semibold">
                                            TZS {{ number_format($grossSalary, 2) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="ps-0 text-muted">Total Deductions</td>
                                        <td class="text-end pe-0 text-danger fw-semibold">
                                            - TZS {{ number_format($totalDeductions, 2) }}
                                        </td>
                                    </tr>
                                    <tr class="border-top">
                                        <td class="ps-0 pt-2">
                                            <strong>Net Payment Amount</strong>
                                        </td>
                                        <td class="text-end pe-0 pt-2">
                                            <h5 class="mb-0 text-success fw-bold">
                                                TZS {{ number_format($netSalary, 2) }}
                                            </h5>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            
            Swal.fire({
                title: 'Approve Payment?',
                html: paymentSummary + '<div class="text-start mt-3"><label class="form-label">Remarks (optional)</label></div>',
                input: 'textarea',
                inputLabel: '',
                inputPlaceholder: 'Enter any remarks for this payment approval...',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Approve Payment',
                cancelButtonText: 'Cancel',
                width: '600px',
                inputValidator: (value) => {
                    if (value && value.length > 500) {
                        return 'Remarks cannot exceed 500 characters';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Approving Payment...',
                        text: 'Please wait while we approve the payment.',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "{{ route('hr.payrolls.approve-payment', $payroll->hash_id) }}",
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            remarks: result.value || ''
                        },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Payment Approved!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function (xhr) {
                            const response = xhr.responseJSON;
                            Swal.fire('Error!', response?.message || 'An error occurred while approving the payment.', 'error');
                        }
                    });
                }
            });
        }

        // Reject payment function
        function rejectPayment(payrollId) {
            @php
                $netSalary = $payroll->payrollEmployees->sum(function($employee) {
                    return $employee->basic_salary + $employee->allowance + $employee->other_allowances 
                         - ($employee->paye + $employee->pension + $employee->insurance + $employee->salary_advance 
                          + $employee->loans + $employee->trade_union + $employee->sdl + $employee->wcf 
                          + $employee->heslb + $employee->other_deductions);
                });
                $grossSalary = $payroll->total_gross_pay ?? 0;
                $totalDeductions = $payroll->total_deductions ?? 0;
                $employeeCount = $payroll->payrollEmployees->count();
                
                // Get bank account info
                $bankAccountReject = null;
                $bankAccountNameReject = 'Not Selected';
                if ($payroll->payment_bank_account_id) {
                    $bankAccountReject = \App\Models\BankAccount::find($payroll->payment_bank_account_id);
                    $bankAccountNameReject = $bankAccountReject ? $bankAccountReject->account_name . ' (' . $bankAccountReject->account_code . ')' : 'Not Found';
                } elseif ($payroll->payment_chart_account_id) {
                    $chartAccountReject = \App\Models\ChartAccount::find($payroll->payment_chart_account_id);
                    $bankAccountNameReject = $chartAccountReject ? $chartAccountReject->account_name . ' (' . $chartAccountReject->account_code . ')' : 'Not Found';
                }
                
                // Get submitter info
                $submitterReject = $payroll->paymentSubmittedBy ?? null;
                $submitterNameReject = $submitterReject ? $submitterReject->name : 'N/A';
                $submittedAtReject = $payroll->payment_submitted_at ? $payroll->payment_submitted_at->format('M d, Y h:i A') : 'N/A';
            @endphp
            
            const paymentSummary = `
                <div class="text-start mb-3">
                    <div class="card border-primary mb-3">
                        <div class="card-header bg-white">
                            <h6 class="mb-0 text-primary">
                                <i class="bx bx-info-circle me-2"></i>
                                Payment Verification Summary
                            </h6>
                        </div>
                        <div class="card-body bg-white">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong class="text-muted d-block mb-1">Payroll Period</strong>
                                    <span class="h6 mb-0">{{ $payroll->month_name }} {{ $payroll->year }}</span>
                                    <br>
                                    <small class="text-muted">{{ $payroll->reference }}</small>
                                    @if($payroll->payrollCalendar)
                                        <br>
                                        <small class="text-info">
                                            <i class="bx bx-calendar me-1"></i>Calendar: {{ $payroll->payrollCalendar->period_label }}
                                        </small>
                                    @endif
                                    @if($payroll->payGroup)
                                        <br>
                                        <small class="text-info">
                                            <i class="bx bx-group me-1"></i>Pay Group: {{ $payroll->payGroup->pay_group_code }} - {{ $payroll->payGroup->pay_group_name }}
                                        </small>
                                    @endif
                                </div>
                                <div class="col-6">
                                    <strong class="text-muted d-block mb-1">Employees</strong>
                                    <span class="h6 mb-0">{{ $employeeCount }} employee(s)</span>
                                </div>
                            </div>
                            @if($payroll->payrollCalendar)
                            <div class="row mb-3 border-top pt-3">
                                <div class="col-6">
                                    <strong class="text-muted d-block mb-1">
                                        <i class="bx bx-calendar-check me-1"></i>Cut-off Date
                                    </strong>
                                    <span class="h6 mb-0">{{ $payroll->payrollCalendar->cut_off_date->format('M d, Y') }}</span>
                                    <br>
                                    <small class="text-muted">Last date for payroll data inclusion</small>
                                </div>
                                <div class="col-6">
                                    <strong class="text-muted d-block mb-1">
                                        <i class="bx bx-money me-1"></i>Pay Date
                                    </strong>
                                    <span class="h6 mb-0">{{ $payroll->payrollCalendar->pay_date->format('M d, Y') }}</span>
                                    <br>
                                    <small class="text-muted">Actual payment date</small>
                                </div>
                            </div>
                            @elseif($payroll->payGroup)
                            <div class="row mb-3 border-top pt-3">
                                <div class="col-6">
                                    <strong class="text-muted d-block mb-1">
                                        <i class="bx bx-calendar-check me-1"></i>Cut-off Date
                                    </strong>
                                    <span class="h6 mb-0">{{ $payroll->payGroup->calculateCutOffDate($payroll->year, $payroll->month)->format('M d, Y') }}</span>
                                    <br>
                                    <small class="text-muted">Calculated from Pay Group settings</small>
                                </div>
                                <div class="col-6">
                                    <strong class="text-muted d-block mb-1">
                                        <i class="bx bx-money me-1"></i>Pay Date
                                    </strong>
                                    <span class="h6 mb-0">{{ $payroll->payGroup->calculatePayDate($payroll->year, $payroll->month)->format('M d, Y') }}</span>
                                    <br>
                                    <small class="text-muted">Calculated from Pay Group settings</small>
                                </div>
                            </div>
                            @endif
                            @if($payroll->payment_submitted_by || $payroll->payment_chart_account_id)
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong class="text-muted d-block mb-1">Submitted By</strong>
                                    <span class="h6 mb-0">{{ $submitterName }}</span>
                                    <br>
                                    <small class="text-muted">{{ $submittedAt }}</small>
                                </div>
                                <div class="col-6">
                                    <strong class="text-muted d-block mb-1">Bank Account</strong>
                                    <span class="h6 mb-0">{{ $bankAccountName }}</span>
                                    @if($payroll->payment_date)
                                    <br>
                                    <small class="text-muted">Payment Date: {{ \Carbon\Carbon::parse($payroll->payment_date)->format('M d, Y') }}</small>
                                    @endif
                                </div>
                            </div>
                            @endif
                            <hr>
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <td class="ps-0 text-muted">Gross Salary</td>
                                        <td class="text-end pe-0 fw-semibold">
                                            TZS {{ number_format($grossSalary, 2) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="ps-0 text-muted">Total Deductions</td>
                                        <td class="text-end pe-0 text-danger fw-semibold">
                                            - TZS {{ number_format($totalDeductions, 2) }}
                                        </td>
                                    </tr>
                                    <tr class="border-top">
                                        <td class="ps-0 pt-2">
                                            <strong>Net Payment Amount</strong>
                                        </td>
                                        <td class="text-end pe-0 pt-2">
                                            <h5 class="mb-0 text-success fw-bold">
                                                TZS {{ number_format($netSalary, 2) }}
                                            </h5>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            
            Swal.fire({
                title: 'Reject Payment?',
                html: paymentSummary + '<div class="text-start mt-3"><label class="form-label">Rejection reason (required)</label></div>',
                input: 'textarea',
                inputLabel: '',
                inputPlaceholder: 'Enter the reason for rejecting this payment...',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Reject Payment',
                cancelButtonText: 'Cancel',
                width: '600px',
                inputValidator: (value) => {
                    if (!value || value.trim() === '') {
                        return 'Please provide a reason for rejection';
                    }
                    if (value.length > 500) {
                        return 'Reason cannot exceed 500 characters';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Rejecting Payment...',
                        text: 'Please wait while we reject the payment.',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "{{ route('hr.payrolls.reject-payment', $payroll->hash_id) }}",
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            remarks: result.value
                        },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Payment Rejected!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function (xhr) {
                            const response = xhr.responseJSON;
                            Swal.fire('Error!', response?.message || 'An error occurred while rejecting the payment.', 'error');
                        }
                    });
                }
            });
        }

        // Initialize DataTables
        $(document).ready(function () {
            if ($('#employeesTable').length) {
                $('#employeesTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('hr.payrolls.employees', $payroll->hash_id) }}",
                        type: 'GET'
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '5%' },
                        { data: 'employee_name', name: 'employee_name', width: '15%' },
                        { data: 'employee_id', name: 'employee_id', width: '10%' },
                        { data: 'department', name: 'department', width: '12%' },
                        { data: 'position', name: 'position', width: '12%' },
                        { data: 'basic_salary', name: 'basic_salary', className: 'text-end', width: '10%' },
                        { data: 'allowance', name: 'allowance', className: 'text-end', width: '10%' },
                        { 
                            data: 'overtime', 
                            name: 'overtime', 
                            className: 'text-end', 
                            width: '8%',
                            render: function(data, type, row) {
                                if (type === 'display' || type === 'type') {
                                    // Remove commas from formatted number before parsing
                                    const cleanData = String(data).replace(/,/g, '');
                                    const amount = parseFloat(cleanData) || 0;
                                    return 'TZS ' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                                return data || 0;
                            }
                        },
                        { data: 'gross_salary', name: 'gross_salary', className: 'text-end', width: '10%' },
                        { data: 'total_deductions', name: 'total_deductions', className: 'text-end', width: '10%' },
                        { data: 'net_salary', name: 'net_salary', className: 'text-end', width: '10%' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center', width: '8%' }
                    ],
                    order: [[1, 'asc']],
                    pageLength: 25,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    responsive: true,
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
                    buttons: [
                        {
                            extend: 'excel',
                            text: '<i class="bx bx-spreadsheet"></i> Excel',
                            className: 'btn btn-success btn-sm',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                            }
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="bx bx-file"></i> PDF',
                            className: 'btn btn-danger btn-sm',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="bx bx-printer"></i> Print',
                            className: 'btn btn-info btn-sm'
                        }
                    ],
                    language: {
                        processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                        emptyTable: "No employees found in this payroll",
                        zeroRecords: "No matching employees found",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "Showing 0 to 0 of 0 entries",
                        infoFiltered: "(filtered from _MAX_ total entries)",
                        search: "Search:",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    },
                    drawCallback: function() {
                        // Format currency columns
                        $('.text-end').each(function() {
                            const text = $(this).text();
                            if (text && !text.includes('TZS') && !isNaN(parseFloat(text.replace(/,/g, '')))) {
                                $(this).text('TZS ' + text);
                            }
                        });
                    }
                });
            }
        });
    </script>
@endpush
