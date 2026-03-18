@extends('layouts.main')

@section('title', 'Employee Details')

@push('styles')
<style>
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ededed;
        font-size: 27px;
    }
    
    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #146c43) !important;
    }
    
    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
    }
    
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffb300) !important;
    }
    
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .table-light {
        background-color: #f8f9fa;
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }
</style>
@endpush

@section('content')
<style>
.employee-profile-card {
    background: none; /* rely on bg-primary utility on the element */
    border: none;
    border-radius: 0; /* square cards */
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    overflow: hidden;
}

.employee-avatar {
    width: 140px;
    height: 140px;
    background: rgba(255,255,255,0.08);
    border: 3px solid rgba(255,255,255,0.18);
    transition: all 0.3s ease;
    border-radius: 0; /* square avatar */
    overflow: hidden;
}

.employee-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.employee-avatar:hover {
    transform: scale(1.02);
    border-color: rgba(255,255,255,0.35);
}

.info-card {
    border: none;
    border-radius: 0; /* square */
    box-shadow: 0 5px 20px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    background: #fff;
}

.info-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.info-card .card-header {
    background: var(--bs-secondary); /* use bootstrap secondary color */
    color: #fff;
    border: none;
    border-radius: 0;
    padding: 1.25rem 1.5rem;
}

.section-title {
    color: #495057;
    font-weight: 600;
    font-size: 1.1rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-item {
    padding: 1rem;
    border-bottom: 1px solid #f8f9fa;
    transition: all 0.2s ease;
}

.info-item:hover {
    background: #f8f9fa;
    padding-left: 1.5rem;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    color: #6c757d;
    font-weight: 500;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.25rem;
}

.info-value {
    color: #212529;
    font-weight: 600;
    font-size: 1rem;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.benefit-item {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 0.5rem;
    border-left: 4px solid #007bff;
    transition: all 0.2s ease;
}

.benefit-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.benefit-label {
    color: #495057;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.benefit-value {
    color: #212529;
    font-size: 0.95rem;
}

.action-buttons {
    position: sticky;
    top: 20px;
    z-index: 10;
}

.btn-modern {
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.btn-primary-modern {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.btn-secondary-modern {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
}

.btn-success-modern {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
}

.document-card {
    border: none;
    border-radius: 0;
    box-shadow: 0 5px 20px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
}

.document-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.document-table {
    border-radius: 10px;
    overflow: hidden;
}

.document-table thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.document-table th {
    border: none;
    font-weight: 600;
    color: #495057;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    padding: 1rem;
}

.document-table td {
    border: none;
    padding: 1rem;
    vertical-align: middle;
}

.document-table tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #f8f9fa;
}

.document-table tbody tr:hover {
    background: #f8f9fa;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
    border-radius: 0;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.04);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.5rem;
    color: white;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}

.fade-in-up-delay-1 {
    animation: fadeInUp 0.6s ease-out 0.1s both;
}

.fade-in-up-delay-2 {
    animation: fadeInUp 0.6s ease-out 0.2s both;
}

.fade-in-up-delay-3 {
    animation: fadeInUp 0.6s ease-out 0.3s both;
}
</style>

<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Employees', 'url' => route('hr.employees.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Show', 'url' => '#', 'icon' => 'bx bx-id-card']
        ]" />

        <!-- Dashboard Stats -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Age</p>
                                <h4 class="my-1 text-primary">{{ $employee->age }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-calendar align-middle"></i> Years old</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-calendar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Experience</p>
                                <h4 class="my-1 text-success">{{ round(\Carbon\Carbon::parse($employee->date_of_employment)->floatDiffInYears(), 1) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-briefcase align-middle"></i> Years</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-briefcase"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Basic Salary</p>
                                <h4 class="my-1 text-info">TZS {{ number_format((float)$employee->basic_salary, 0) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-dollar align-middle"></i> Monthly</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Documents</p>
                                <h4 class="my-1 text-warning">{{ $employee->documents->count() }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-file align-middle"></i> Total files</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-file"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Employee Profile Card -->
            <div class="col-lg-4">
                <div class="card radius-10 mb-4">
                    <div class="card-body">
                        <div class="text-center">
                            <div class="avatar-lg mx-auto mb-4">
                                @if(!empty($employee->profile_photo_url))
                                    <img src="{{ $employee->profile_photo_url }}" alt="{{ $employee->full_name }}" class="rounded-circle p-1 bg-primary" width="110" />
                                @elseif(!empty($employee->photo))
                                    <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->full_name }}" class="rounded-circle p-1 bg-primary" width="110" />
                                @else
                                    <img src="{{ asset('assets/images/avatars/default.png') }}" alt="{{ $employee->full_name }}" class="rounded-circle p-1 bg-primary" width="110" />
                                @endif
                            </div>
                            <h5 class="font-size-16 mb-1 text-truncate">{{ $employee->full_name }}</h5>
                            <p class="text-muted text-truncate mb-3">{{ $employee->designation }}</p>
                        </div>

                        <hr class="my-4">

                        <div class="text-muted">
                            <!-- Basic Information -->
                            <h6 class="text-uppercase mb-3 font-weight-bold text-dark">Basic Information</h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <th scope="row">Employee No :</th>
                                            <td>{{ $employee->employee_number }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Gender :</th>
                                            <td class="text-capitalize">{{ $employee->gender }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Date of Birth :</th>
                                            <td>{{ $employee->date_of_birth?->format('M d, Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Age :</th>
                                            <td>{{ $employee->age }} years</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Marital Status :</th>
                                            <td class="text-capitalize">{{ str_replace('_', ' ', $employee->marital_status) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Employment Information -->
                            <h6 class="text-uppercase mb-3 font-weight-bold text-dark">Employment Information</h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <th scope="row">Department :</th>
                                            <td>{{ optional($employee->department)->name ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Position :</th>
                                            <td>{{ optional($employee->position)->title ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Branch :</th>
                                            <td>{{ optional($employee->branch)->name ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Employment Type :</th>
                                            <td class="text-capitalize">{{ str_replace('_', ' ', $employee->employment_type) }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Date of Employment :</th>
                                            <td>{{ $employee->date_of_employment?->format('M d, Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Basic Salary :</th>
                                            <td>TZS {{ number_format((float)$employee->basic_salary, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Status :</th>
                                            <td>
                                                <span class="badge bg-{{ $employee->status==='active'?'success':($employee->status==='on_leave'?'warning':'secondary') }}">
                                                    {{ ucfirst(str_replace('_', ' ', $employee->status)) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Contact Information -->
                            <h6 class="text-uppercase mb-3 font-weight-bold text-dark">Contact Information</h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <th scope="row">Email :</th>
                                            <td>{{ $employee->email ?: 'No email provided' }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Phone :</th>
                                            <td>{{ $employee->phone_number }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Location :</th>
                                            <td>{{ $employee->current_physical_location }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">District :</th>
                                            <td>{{ $employee->district }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Region :</th>
                                            <td>{{ $employee->region }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Country :</th>
                                            <td>{{ $employee->country }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Identity Information -->
                            <h6 class="text-uppercase mb-3 font-weight-bold text-dark">Identity Information</h6>
                            <div class="table-responsive">
                                <table class="table table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <th scope="row">Document Type :</th>
                                            <td>{{ $employee->identity_document_type }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Document Number :</th>
                                            <td>{{ $employee->identity_number }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">TIN Number :</th>
                                            <td>{{ $employee->tin }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Bank :</th>
                                            <td>{{ $employee->bank_name }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Account Number :</th>
                                            <td>{{ $employee->bank_account_number }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-4 d-flex flex-wrap gap-2">
                            <a href="{{ route('hr.employees.edit', $employee) }}" class="btn btn-sm btn-warning flex-fill">
                                <i class="bx bx-edit"></i> Edit
                            </a>
                            <button class="btn btn-sm btn-primary flex-fill" data-bs-toggle="modal" data-bs-target="#documentModal">
                                <i class="bx bx-file"></i> Add Document
                            </button>
                        </div>

                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons mt-4 fade-in-up-delay-2">
                    <div class="d-grid gap-2">
                        <a href="{{ route('hr-payroll.index') }}" class="btn btn-success-modern btn-modern text-white">
                            <i class="bx bx-dollar me-2"></i>Process Payroll
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Payroll Summary Table -->
                <div class="card radius-10 mb-4">
                    <div class="card-header border-bottom-0">
                        <h5 class="mb-0"><i class="bx bx-calculator me-2"></i>Payroll History</h5>
                        <p class="mb-0 text-muted">Employee payroll records and history</p>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Period</th>
                                        <th>Basic Salary</th>
                                        <th>Allowances</th>
                                        <th>Gross Pay</th>
                                        <th>Deductions</th>
                                        <th>Net Pay</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $payrollRecords = \App\Models\PayrollEmployee::whereHas('payroll', function($q) {
                                            $q->where('company_id', auth()->user()->company_id);
                                        })
                                        ->where('employee_id', $employee->id)
                                        ->with(['payroll'])
                                        ->orderBy('created_at', 'desc')
                                        ->limit(12)
                                        ->get();
                                    @endphp
                                    
                                    @forelse($payrollRecords as $payrollRecord)
                                    <tr>
                                        <td>
                                            <strong>{{ $payrollRecord->payroll->month_name }} {{ $payrollRecord->payroll->year }}</strong>
                                        </td>
                                        <td>TZS {{ number_format($payrollRecord->basic_salary, 2) }}</td>
                                        <td>TZS {{ number_format(($payrollRecord->allowance + $payrollRecord->other_allowances), 2) }}</td>
                                        <td>TZS {{ number_format($payrollRecord->gross_salary, 2) }}</td>
                                        <td>TZS {{ number_format($payrollRecord->total_deductions, 2) }}</td>
                                        <td><strong>TZS {{ number_format($payrollRecord->net_salary, 2) }}</strong></td>
                                        <td>
                                            @php
                                                $statusConfig = [
                                                    'completed' => ['class' => 'success', 'text' => 'Completed'],
                                                    'processing' => ['class' => 'info', 'text' => 'Processing'],
                                                    'draft' => ['class' => 'warning', 'text' => 'Draft'],
                                                    'cancelled' => ['class' => 'danger', 'text' => 'Cancelled']
                                                ];
                                                $status = $payrollRecord->payroll->status;
                                                $config = $statusConfig[$status] ?? ['class' => 'secondary', 'text' => ucfirst($status)];
                                            @endphp
                                            <span class="badge bg-{{ $config['class'] }}">
                                                {{ $config['text'] }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($payrollRecord->payroll->status === 'completed')
                                                <a href="{{ route('hr.payrolls.slip.pdf', ['payroll' => $payrollRecord->payroll->hash_id, 'employee' => $payrollRecord->hash_id]) }}" 
                                                   class="btn btn-sm btn-primary" 
                                                   target="_blank"
                                                   title="Generate Payslip">
                                                    <i class="bx bx-download"></i> Payslip
                                                </a>
                                            @elseif($payrollRecord->payroll->status === 'processing')
                                                <button class="btn btn-sm btn-info" disabled title="Payroll is being processed">
                                                    <i class="bx bx-loader-alt bx-spin"></i> Processing
                                                </button>
                                            @elseif($payrollRecord->payroll->status === 'draft')
                                                <span class="text-muted">
                                                    <i class="bx bx-edit"></i> Pending
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bx bx-calendar-x" style="font-size: 3rem; opacity: 0.3;"></i>
                                                <p class="mt-2 mb-0">No payroll records found</p>
                                                <small>Payroll records will appear here after processing payroll for this employee</small>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        @if($payrollRecords->count() > 0)
                        <div class="mt-3 text-center">
                            <small class="text-muted">Showing last 12 payroll records</small>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- HR Documents -->
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <h5 class="mb-0"><i class="bx bx-folder me-2"></i>HR Documents</h5>
                        <p class="mb-0 text-muted">Employee documents and files</p>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Size</th>
                                        <th>Upload Date</th>
                                        <th>Expiry</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($employee->documents as $doc)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bx bx-file me-2 text-primary"></i>
                                                <strong>{{ $doc->title }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                {{ $doc->fileType->name ?? $doc->document_type ?? 'Unknown' }}
                                            </span>
                                        </td>
                                        <td>{{ $doc->file_size_human }}</td>
                                        <td>{{ $doc->created_at->format('M d, Y') }}</td>
                                        <td>
                                            @if($doc->expiry_date)
                                                {{ $doc->expiry_date->format('M d, Y') }}
                                            @else
                                                <span class="text-muted">No expiry</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($doc->expiry_date)
                                                @if($doc->is_expired)
                                                    <span class="badge bg-danger">Expired</span>
                                                @elseif($doc->expiry_date->diffInDays(now()) <= 30)
                                                    <span class="badge bg-warning">Expiring Soon</span>
                                                @else
                                                    <span class="badge bg-success">Valid</span>
                                                @endif
                                            @else
                                                <span class="badge bg-info">Permanent</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('hr.documents.download', $doc) }}" 
                                                   class="btn btn-sm btn-primary" 
                                                   title="Download">
                                                    <i class="bx bx-download"></i>
                                                </a>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="deleteDocument({{ $doc->id }}, '{{ $doc->title }}')" 
                                                        title="Delete">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bx bx-folder-open" style="font-size: 3rem; opacity: 0.3;"></i>
                                                <p class="mt-2 mb-0">No documents uploaded yet</p>
                                                <small>Click "Add Document" to upload HR documents</small>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        @if($employee->documents->count() > 0)
                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <small class="text-muted">Total documents: {{ $employee->documents->count() }}</small>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#documentModal">
                                <i class="bx bx-plus me-1"></i> Add Document
                            </button>
                        </div>
                        @else
                        <div class="mt-3 text-center">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#documentModal">
                                <i class="bx bx-plus me-2"></i> Add Document
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Document Modal -->
<div class="modal fade" id="documentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="bx bx-file-plus me-2"></i>
                    Upload HR Document
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Upload Instructions -->
                <div class="alert alert-info border-0 mb-4">
                    <div class="d-flex align-items-start">
                        <i class="bx bx-info-circle me-3 text-info" style="font-size: 1.2rem;"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Document Upload Guidelines</h6>
                            <small class="mb-0">Select the document type, choose your file, and set expiry date if applicable. File will be validated based on the selected document type settings.</small>
                        </div>
                    </div>
                </div>

                <!-- Upload Form -->
                <form id="documentForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                    
                    <div class="row g-4">
                        <!-- Document Type -->
                        <div class="col-12">
                            <label class="form-label fw-bold">
                                <i class="bx bx-category me-1 text-primary"></i>
                                Document Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="file_type_id" id="file_type_id" required>
                                <option value="">-- Select Document Type --</option>
                                @foreach($fileTypes as $fileType)
                                    <option value="{{ $fileType->id }}" 
                                            data-extensions="{{ $fileType->allowed_extensions_string }}"
                                            data-max-size="{{ $fileType->max_file_size }}"
                                            data-max-size-human="{{ $fileType->max_file_size_human }}">
                                        {{ $fileType->name }}
                                        @if($fileType->description)
                                            - {{ $fileType->description }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                <i class="bx bx-help-circle me-1"></i>
                                Choose the category that best describes your document
                            </div>
                            <!-- File type restrictions display -->
                            <div id="file-restrictions" class="mt-2" style="display: none;">
                                <div class="card border-warning bg-warning bg-opacity-10">
                                    <div class="card-body p-3">
                                        <h6 class="card-title text-warning mb-2">
                                            <i class="bx bx-shield me-1"></i>File Restrictions
                                        </h6>
                                        <div id="allowed-extensions" class="mb-2"></div>
                                        <div id="max-file-size"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div class="col-12">
                            <label class="form-label fw-bold">
                                <i class="bx bx-upload me-1 text-success"></i>
                                Select File <span class="text-danger">*</span>
                            </label>
                            <div class="position-relative">
                                <input type="file" class="form-control" name="file" id="file_input" required>
                                <div id="file-preview" class="mt-3" style="display: none;">
                                    <div class="card border-success bg-success bg-opacity-10">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center">
                                                <i class="bx bx-file text-success me-3" style="font-size: 2rem;"></i>
                                                <div>
                                                    <h6 class="mb-1" id="file-name"></h6>
                                                    <small class="text-muted" id="file-info"></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-text">
                                <i class="bx bx-info-circle me-1"></i>
                                Supported formats will be shown based on selected document type
                            </div>
                        </div>

                        <!-- Expiry Date -->
                        <div class="col-12">
                            <label class="form-label fw-bold">
                                <i class="bx bx-calendar me-1 text-warning"></i>
                                Expiry Date <span class="text-muted">(Optional)</span>
                            </label>
                            <input type="date" class="form-control" name="expiry_date" id="expiry_date">
                            <div class="form-text">
                                <i class="bx bx-time me-1"></i>
                                Leave blank if document doesn't expire or has no expiration date
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-0 p-3">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="submit" form="documentForm" class="btn btn-primary" id="upload-btn">
                    <span class="upload-text">
                        <i class="bx bx-upload me-2"></i>Upload Document
                    </span>
                    <span class="loading-text d-none">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        Uploading...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script nonce="{{ $cspNonce ?? '' }}">
// Document upload functionality
document.addEventListener('DOMContentLoaded', function() {
    const documentForm = document.getElementById('documentForm');
    const uploadBtn = document.getElementById('upload-btn');
    const fileTypeSelect = document.getElementById('file_type_id');
    const fileInput = document.getElementById('file_input');
    const fileRestrictions = document.getElementById('file-restrictions');
    const allowedExtensions = document.getElementById('allowed-extensions');
    const maxFileSize = document.getElementById('max-file-size');
    const filePreview = document.getElementById('file-preview');
    const fileName = document.getElementById('file-name');
    const fileInfo = document.getElementById('file-info');

    // Show file restrictions when document type is selected
    fileTypeSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (this.value) {
            const extensions = selectedOption.dataset.extensions;
            const maxSize = selectedOption.dataset.maxSizeHuman;
            
            if (extensions || maxSize) {
                let restrictionsHtml = '';
                
                if (extensions) {
                    restrictionsHtml += `<div class="mb-1"><strong>Allowed formats:</strong> <span class="badge bg-primary">${extensions}</span></div>`;
                }
                
                if (maxSize) {
                    restrictionsHtml += `<div><strong>Maximum size:</strong> <span class="badge bg-warning text-dark">${maxSize}</span></div>`;
                } else {
                    restrictionsHtml += `<div><strong>Maximum size:</strong> <span class="badge bg-success">No limit</span></div>`;
                }
                
                allowedExtensions.innerHTML = '';
                maxFileSize.innerHTML = restrictionsHtml;
                fileRestrictions.style.display = 'block';
            } else {
                fileRestrictions.style.display = 'none';
            }
        } else {
            fileRestrictions.style.display = 'none';
        }
        
        // Clear file input when document type changes
        fileInput.value = '';
        filePreview.style.display = 'none';
    });

    // Show file preview when file is selected
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            
            fileName.textContent = file.name;
            fileInfo.textContent = `${fileSize} MB • ${file.type || 'Unknown type'}`;
            filePreview.style.display = 'block';
            
            // Validate file against selected document type
            validateFile(file);
        } else {
            filePreview.style.display = 'none';
        }
    });

    // File validation function
    function validateFile(file) {
        const selectedOption = fileTypeSelect.options[fileTypeSelect.selectedIndex];
        
        if (!fileTypeSelect.value) {
            showValidationError('Please select a document type first');
            return false;
        }

        // Check file extension
        const allowedExts = selectedOption.dataset.extensions;
        if (allowedExts) {
            const fileExt = file.name.split('.').pop().toLowerCase();
            const allowedExtArray = allowedExts.toLowerCase().split(', ');
            
            if (!allowedExtArray.includes(fileExt)) {
                showValidationError(`File type .${fileExt} is not allowed. Allowed types: ${allowedExts}`);
                return false;
            }
        }

        // Check file size
        const maxSize = parseInt(selectedOption.dataset.maxSize);
        if (maxSize && (file.size / 1024) > maxSize) {
            const maxSizeHuman = selectedOption.dataset.maxSizeHuman;
            showValidationError(`File size exceeds maximum allowed size of ${maxSizeHuman}`);
            return false;
        }

        return true;
    }

    // Show validation error
    function showValidationError(message) {
        fileInput.value = '';
        filePreview.style.display = 'none';
        
        Swal.fire({
            icon: 'error',
            title: 'File Validation Error',
            text: message,
            confirmButtonColor: '#dc3545',
            timer: 5000,
            timerProgressBar: true
        });
    }

    // Handle form submission
    documentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!fileTypeSelect.value) {
            showValidationError('Please select a document type');
            return;
        }
        
        if (!fileInput.files.length) {
            showValidationError('Please select a file to upload');
            return;
        }
        
        // Validate file again before upload
        if (!validateFile(fileInput.files[0])) {
            return;
        }
        
        // Show loading state
        showLoadingState(true);
        
        // Create FormData
        const formData = new FormData();
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        formData.append('file_type_id', fileTypeSelect.value);
        formData.append('file', fileInput.files[0]);
        
        const expiryDate = document.getElementById('expiry_date').value;
        formData.append('expiry_date', expiryDate || ''); // Always append, even if empty
        
        // Submit via AJAX
        fetch('{{ route("hr.employees.documents.store", $employee) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
        })
        .then(data => {
            showLoadingState(false);
            
            // Success notification
            Swal.fire({
                icon: 'success',
                title: 'Document Uploaded!',
                text: 'Your document has been uploaded successfully.',
                confirmButtonColor: '#28a745',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });
            
            // Close modal and reload page
            const modal = bootstrap.Modal.getInstance(document.getElementById('documentModal'));
            modal.hide();
            
            // Reload the page to show new document
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        })
        .catch(error => {
            showLoadingState(false);
            
            let errorMessage = 'An error occurred while uploading the document.';
            
            if (error.errors) {
                // Laravel validation errors
                const firstError = Object.values(error.errors)[0];
                errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Upload Failed',
                text: errorMessage,
                confirmButtonColor: '#dc3545'
            });
        });
    });

    // Show/hide loading state
    function showLoadingState(loading) {
        const uploadText = uploadBtn.querySelector('.upload-text');
        const loadingText = uploadBtn.querySelector('.loading-text');
        
        if (loading) {
            uploadText.classList.add('d-none');
            loadingText.classList.remove('d-none');
            uploadBtn.disabled = true;
        } else {
            uploadText.classList.remove('d-none');
            loadingText.classList.add('d-none');
            uploadBtn.disabled = false;
        }
    }

    // Reset form when modal is hidden
    document.getElementById('documentModal').addEventListener('hidden.bs.modal', function() {
        documentForm.reset();
        fileRestrictions.style.display = 'none';
        filePreview.style.display = 'none';
        showLoadingState(false);
    });
});

// Delete document function
function deleteDocument(documentId, documentTitle) {
    Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete the document "${documentTitle}". This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('delete-form');
            form.action = `/hr-payroll/documents/${documentId}`;
            form.submit();
        }
    });
}
</script>
@endsection
