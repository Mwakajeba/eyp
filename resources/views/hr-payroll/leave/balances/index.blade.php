@extends('layouts.main')

@section('title', 'Leave Balances')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Leave Management', 'url' => route('hr.leave.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Leave Balances', 'url' => '#', 'icon' => 'bx bx-bar-chart']
        ]" />
            <h6 class="mb-0 text-uppercase">LEAVE BALANCES</h6>
            <hr />

            <!-- Action Buttons -->
            <div class="row mb-3">
                <div class="col-12 text-end">
                    <a href="{{ route('hr.leave.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back"></i> Back
                    </a>
                </div>
            </div>

            <!-- Employees List -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-group me-2"></i>Employee Leave Balances</h5>
                        </div>
                        <div class="card-body">
                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bx bx-check-circle me-2"></i>
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Employee</th>
                                            <th>Employee Number</th>
                                            <th>Department</th>
                                            <th>Position</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($employees as $employee)
                                            <tr>
                                                <td>{{ $loop->iteration + ($employees->currentPage() - 1) * $employees->perPage() }}
                                                </td>
                                                <td>
                                                    <strong>{{ $employee->full_name }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $employee->email }}</small>
                                                </td>
                                                <td>{{ $employee->employee_number }}</td>
                                                <td>{{ $employee->department->name ?? 'N/A' }}</td>
                                                <td>{{ $employee->position->name ?? 'N/A' }}</td>
                                                <td>
                                                    <span
                                                        class="badge bg-{{ $employee->status === 'active' ? 'success' : 'secondary' }}">
                                                        {{ ucfirst($employee->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('hr.leave.balances.show', $employee->id) }}"
                                                        class="btn btn-sm btn-info">
                                                        <i class="bx bx-show"></i> View Balance
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">
                                                    No active employees found
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="mt-3">
                                {{ $employees->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection