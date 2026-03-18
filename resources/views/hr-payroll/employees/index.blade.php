@extends('layouts.main')

@section('title', 'Employees')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Employees', 'url' => '#', 'icon' => 'bx bx-group']
        ]" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0"><i class="bx bx-group me-2"></i>Employees</h5>
                <p class="mb-0 text-muted">Manage and track all employees</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('hr.employees.import') }}" class="btn btn-outline-success">
                    <i class="bx bx-upload me-1"></i>Import Employees
                </a>
                <a href="{{ route('hr.employees.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>New Employee
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
                                <h4 class="my-1 text-primary">{{ number_format($stats['total']) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-group align-middle"></i> All employees</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-group"></i>
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
                                <p class="mb-0 text-secondary">Active Employees</p>
                                <h4 class="my-1 text-success">{{ number_format($stats['active']) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Currently active</span>
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
                                <p class="mb-0 text-secondary">Inactive Employees</p>
                                <h4 class="my-1 text-warning">{{ number_format($stats['inactive']) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-x-circle align-middle"></i> Not active</span>
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
                                <p class="mb-0 text-secondary">New This Month</p>
                                <h4 class="my-1 text-info">{{ number_format($stats['new_this_month']) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-user-plus align-middle"></i> Hired this month</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-user-plus"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bx bx-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <div class="table-responsive">
                    <table id="employees-table" class="table table-striped table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Employee No.</th>
                                <th>Full Name</th>
                                <th>Gender</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Branch</th>
                                <th>Status</th>
                                <th>Date Employed</th>
                                <th>Basic Salary</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employees as $index => $emp)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $emp->employee_number }}</td>
                                <td>{{ $emp->full_name }}</td>
                                <td class="text-capitalize">{{ $emp->gender }}</td>
                                <td>{{ optional($emp->department)->name ?? '-' }}</td>
                                <td>{{ optional($emp->position)->title ?? '-' }}</td>
                                <td>{{ optional($emp->branch)->name ?? '-' }}</td>
                                <td><span class="badge bg-{{ $emp->status === 'active' ? 'success' : ($emp->status === 'on_leave' ? 'warning' : 'secondary') }} text-capitalize">{{ $emp->status }}</span></td>
                                <td>{{ $emp->date_of_employment?->format('Y-m-d') }}</td>
                                <td>{{ number_format((float)$emp->basic_salary, 2) }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('hr.employees.show', $emp) }}" class="btn btn-sm btn-info"><i class="bx bx-show"></i></a>
                                    <a href="{{ route('hr.employees.edit', $emp) }}" class="btn btn-sm btn-secondary"><i class="bx bx-edit"></i></a>
                                    <button class="btn btn-sm btn-danger" onclick="deleteEmployee({{ $emp->id }}, '{{ $emp->full_name }}')"><i class="bx bx-trash"></i></button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <form id="delete-form" method="POST" style="display:none">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

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
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }
</style>
@endpush

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function(){
    $('#employees-table').DataTable({
        order: [[0, 'asc']],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        stateSave: true
    });
});

function deleteEmployee(id, name){
    Swal.fire({
        title: 'Delete employee? ',
        html: `You are about to delete <strong>${name}</strong>. This cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then((res) => {
        if(res.isConfirmed){
            const form = document.getElementById('delete-form');
            form.action = `/hr-payroll/employees/${id}`;
            form.submit();
        }
    });
}
</script>
@endpush
