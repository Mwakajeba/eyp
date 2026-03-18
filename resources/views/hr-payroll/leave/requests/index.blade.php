@extends('layouts.main')

@section('title', 'Leave Requests')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Leave Management', 'url' => route('hr.leave.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Leave Requests', 'url' => '#', 'icon' => 'bx bx-file']
        ]" />
            <h6 class="mb-0 text-uppercase">LEAVE REQUESTS</h6>
            <hr />

            <!-- Action Buttons -->
            <div class="row mb-3">
                <div class="col-12 text-end">
                    <a href="{{ route('hr.leave.index') }}" class="btn btn-secondary me-2">
                        <i class="bx bx-arrow-back"></i> Back
                    </a>
                    <a href="{{ route('hr.leave.requests.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus"></i> New Request
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form id="filterForm" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" id="statusFilter" class="form-select">
                                        <option value="">All Statuses</option>
                                        <option value="draft">Draft</option>
                                        <option value="pending_manager">Pending Manager</option>
                                        <option value="pending_hr">Pending HR</option>
                                        <option value="approved">Approved</option>
                                        <option value="taken">Taken</option>
                                        <option value="cancelled">Cancelled</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Leave Type</label>
                                    <select name="leave_type_id" id="leaveTypeFilter" class="form-select">
                                        <option value="">All Leave Types</option>
                                        @foreach($leaveTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @if(auth()->user()->hasRole(['HR', 'Admin']))
                                    <div class="col-md-3">
                                        <label class="form-label">Employee</label>
                                        <select name="employee_id" id="employeeFilter" class="form-select">
                                            <option value="">All Employees</option>
                                            @foreach($employees as $emp)
                                                <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                                        <i class="bx bx-reset"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leave Requests Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="leaveRequestsTable" class="table table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Request #</th>
                                            <th>Employee</th>
                                            <th>Leave Type</th>
                                            <th>Date Range</th>
                                            <th>Days</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        $(document).ready(function () {
            var table = $('#leaveRequestsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('hr.leave.requests.index') }}',
                    data: function (d) {
                        d.status = $('#statusFilter').val();
                        d.leave_type_id = $('#leaveTypeFilter').val();
                        d.employee_id = $('#employeeFilter').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'request_number', name: 'request_number' },
                    { data: 'employee_name', name: 'employee.first_name' },
                    { data: 'leave_type_name', name: 'leaveType.name' },
                    { data: 'date_range', name: 'date_range', orderable: false },
                    { data: 'total_days', name: 'total_days' },
                    { data: 'status_badge', name: 'status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[1, 'desc']]
            });

            // Filter change events
            $('#statusFilter, #leaveTypeFilter, #employeeFilter').change(function () {
                table.draw();
            });
        });

        function resetFilters() {
            $('#statusFilter').val('');
            $('#leaveTypeFilter').val('');
            $('#employeeFilter').val('');
            $('#leaveRequestsTable').DataTable().draw();
        }
    </script>
@endpush