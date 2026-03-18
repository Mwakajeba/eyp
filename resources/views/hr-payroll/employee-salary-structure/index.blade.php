@extends('layouts.main')

@section('title', 'Employee Salary Structures')

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
    
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #cc9a06) !important;
    }
    
    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
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
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Employee Salary Structures', 'url' => '#', 'icon' => 'bx bx-money']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h5 class="mb-0"><i class="bx bx-money me-2"></i>Employee Salary Structures</h5>
                    <p class="mb-0 text-muted">Manage salary component assignments for employees</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.employee-salary-structure.bulk-assign-form') }}" class="btn btn-success">
                        <i class="bx bx-group me-1"></i>Bulk Assign
                    </a>
                    <a href="{{ route('hr.employee-salary-structure.apply-template-form') }}" class="btn btn-info">
                        <i class="bx bx-file me-1"></i>Apply Template
                    </a>
                    <a href="{{ route('hr.salary-structure-templates.index') }}" class="btn btn-outline-primary">
                        <i class="bx bx-template me-1"></i>Templates
                    </a>
                    <a href="{{ route('hr.employee-salary-structure.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>Assign Structure
                    </a>
                </div>
            </div>
            <hr />

            <!-- Dashboard Stats -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card radius-10 border-start border-0 border-3 border-primary">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">Total Employees</p>
                                    <h4 class="my-1 text-primary">{{ number_format($stats['total_employees']) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-primary"><i class="bx bx-user align-middle"></i> All employees</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                    <i class="bx bx-user"></i>
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
                                    <p class="mb-0 text-secondary">With Structure</p>
                                    <h4 class="my-1 text-success">{{ number_format($stats['with_structure']) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Has components</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                    <i class="bx bx-check-circle"></i>
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
                                    <p class="mb-0 text-secondary">Without Structure</p>
                                    <h4 class="my-1 text-warning">{{ number_format($stats['without_structure']) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-warning"><i class="bx bx-x-circle align-middle"></i> Needs setup</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                    <i class="bx bx-x-circle"></i>
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
                                    <p class="mb-0 text-secondary">Available Components</p>
                                    <h4 class="my-1 text-info">{{ number_format($stats['total_components']) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-info"><i class="bx bx-calculator align-middle"></i> Active components</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                    <i class="bx bx-calculator"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="employee-structures-table" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Employee Name</th>
                                    <th>Employee Number</th>
                                    <th>Basic Salary</th>
                                    <th>Structure Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let table = $('#employee-structures-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.employee-salary-structure.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'employee_name', name: 'first_name'},
            {data: 'employee_number', name: 'employee_number'},
            {data: 'basic_salary', name: 'basic_salary'},
            {data: 'structure_status', name: 'structure_status', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true
    });
});
</script>
@endpush

