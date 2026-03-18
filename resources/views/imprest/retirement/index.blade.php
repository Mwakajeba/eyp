@extends('layouts.main')

@section('title', 'Retirement Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Imprest Management', 'url' => route('imprest.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Retirement Management', 'url' => '#', 'icon' => 'bx bx-receipt']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-primary">Retirement Management</h5>
            @can('create', App\Models\Retirement::class)
            <a href="{{ route('imprest.index') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i>New Retirement
            </a>
            @endcan
        </div>

        <!-- Filter Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="bx bx-time-five display-4"></i>
                        <h4 class="mt-2" id="pendingCount">{{ $stats['pending'] }}</h4>
                        <p class="mb-0">Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="bx bx-check-circle display-4"></i>
                        <h4 class="mt-2" id="checkedCount">{{ $stats['checked'] }}</h4>
                        <p class="mb-0">Checked</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="bx bx-check-double display-4"></i>
                        <h4 class="mt-2" id="approvedCount">{{ $stats['approved'] }}</h4>
                        <p class="mb-0">Approved</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <i class="bx bx-x-circle display-4"></i>
                        <h4 class="mt-2" id="rejectedCount">{{ $stats['rejected'] }}</h4>
                        <p class="mb-0">Rejected</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label">Status</label>
                        <select id="statusFilter" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="checked">Checked</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="branchFilter" class="form-label">Branch</label>
                        <select id="branchFilter" class="form-select">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="employeeFilter" class="form-label">Employee</label>
                        <select id="employeeFilter" class="form-select">
                            <option value="">All Employees</option>
                            @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="dateFilter" class="form-label">Date Range</label>
                        <select id="dateFilter" class="form-select">
                            <option value="">All Dates</option>
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3" id="customDateRange" style="display: none;">
                    <div class="col-md-3">
                        <label for="startDate" class="form-label">Start Date</label>
                        <input type="date" id="startDate" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="endDate" class="form-label">End Date</label>
                        <input type="date" id="endDate" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" id="applyDateFilter" class="btn btn-primary d-block">
                            <i class="bx bx-search me-1"></i>Apply
                        </button>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="button" id="clearFilters" class="btn btn-outline-secondary">
                            <i class="bx bx-refresh me-1"></i>Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Retirements Table -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Retirement Records</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="retirementsTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>Retirement #</th>
                                <th>Imprest Request</th>
                                <th>Employee</th>
                                <th>Branch</th>
                                <th>Disbursed Amount</th>
                                <th>Amount Used</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Submitted Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Action Modals -->
@include('imprest.retirement.modals.quick-actions')
@endsection

@push('styles')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.badge {
    font-size: 0.75em;
}

.table th {
    font-weight: 600;
    background-color: #f8f9fa;
}

.btn-xs {
    padding: 0.25rem 0.4rem;
    font-size: 0.75rem;
    line-height: 1.2;
    border-radius: 0.2rem;
}

.amount-positive {
    color: #198754;
    font-weight: 600;
}

.amount-negative {
    color: #dc3545;
    font-weight: 600;
}

.amount-zero {
    color: #6c757d;
    font-weight: 600;
}

.status-badge {
    font-weight: 500;
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>

<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#retirementsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('imprest.retirement.data') }}",
            data: function(d) {
                d.status = $('#statusFilter').val();
                d.branch_id = $('#branchFilter').val();
                d.employee_id = $('#employeeFilter').val();
                d.date_filter = $('#dateFilter').val();
                d.start_date = $('#startDate').val();
                d.end_date = $('#endDate').val();
            }
        },
        columns: [
            { 
                data: 'retirement_number', 
                name: 'retirement_number',
                render: function(data, type, row) {
                    return `<strong>${data}</strong>`;
                }
            },
            { 
                data: 'imprest_request_number', 
                name: 'imprest_requests.request_number',
                render: function(data, type, row) {
                    return `<a href="/imprest/requests/${row.imprest_request_id}" class="text-primary">${data}</a>`;
                }
            },
            { 
                data: 'employee_name', 
                name: 'employees.name'
            },
            { 
                data: 'branch_name', 
                name: 'branches.name'
            },
            { 
                data: 'disbursed_amount', 
                name: 'disbursed_amount',
                render: function(data) {
                    return `TZS ${parseFloat(data || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                },
                className: 'text-end'
            },
            { 
                data: 'total_amount_used', 
                name: 'total_amount_used',
                render: function(data) {
                    return `TZS ${parseFloat(data || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                },
                className: 'text-end'
            },
            { 
                data: 'remaining_balance', 
                name: 'remaining_balance',
                render: function(data) {
                    const balance = parseFloat(data || 0);
                    const formatted = `TZS ${balance.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                    
                    if (balance > 0) {
                        return `<span class="amount-positive">${formatted}</span>`;
                    } else if (balance < 0) {
                        return `<span class="amount-negative">${formatted}</span>`;
                    } else {
                        return `<span class="amount-zero">${formatted}</span>`;
                    }
                },
                className: 'text-end'
            },
            { 
                data: 'status', 
                name: 'status',
                render: function(data, type, row) {
                    const statusConfig = {
                        'pending': { class: 'bg-warning text-dark', label: 'Pending' },
                        'checked': { class: 'bg-info text-white', label: 'Checked' },
                        'approved': { class: 'bg-success text-white', label: 'Approved' },
                        'rejected': { class: 'bg-danger text-white', label: 'Rejected' }
                    };
                    
                    const config = statusConfig[data] || { class: 'bg-secondary text-white', label: data };
                    return `<span class="badge ${config.class} status-badge">${config.label}</span>`;
                }
            },
            { 
                data: 'submitted_at', 
                name: 'submitted_at',
                render: function(data) {
                    return new Date(data).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            },
            { 
                data: 'actions', 
                name: 'actions',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    let actions = `<div class="btn-group" role="group">`;
                    
                    // View action
                    actions += `<a href="/imprest/retirement/${row.id}" class="btn btn-outline-primary btn-xs" title="View Details">
                                   <i class="bx bx-show"></i>
                               </a>`;
                    
                    // Edit action (only for pending status and if user is submitter)
                    if (row.status === 'pending' && row.can_edit) {
                        actions += `<a href="/imprest/retirement/${row.id}/edit" class="btn btn-outline-warning btn-xs" title="Edit">
                                       <i class="bx bx-edit"></i>
                                   </a>`;
                    }
                    
                    // Check action
                    if (row.can_check) {
                        actions += `<button class="btn btn-outline-info btn-xs check-retirement" 
                                          data-id="${row.id}" 
                                          data-number="${row.retirement_number}" 
                                          title="Check">
                                       <i class="bx bx-check-circle"></i>
                                   </button>`;
                    }
                    
                    // Approve action  
                    if (row.can_approve) {
                        actions += `<button class="btn btn-outline-success btn-xs approve-retirement" 
                                          data-id="${row.id}" 
                                          data-number="${row.retirement_number}" 
                                          title="Approve">
                                       <i class="bx bx-check-double"></i>
                                   </button>`;
                    }
                    
                    actions += `</div>`;
                    return actions;
                }
            }
        ],
        order: [[8, 'desc']], // Order by submitted date descending
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: "Loading retirement records...",
            emptyTable: "No retirement records found",
            zeroRecords: "No matching retirement records found"
        },
        drawCallback: function() {
            // Initialize tooltips
            $('[title]').tooltip();
        }
    });

    // Filter handlers
    $('#statusFilter, #branchFilter, #employeeFilter').change(function() {
        table.draw();
    });

    $('#dateFilter').change(function() {
        if ($(this).val() === 'custom') {
            $('#customDateRange').show();
        } else {
            $('#customDateRange').hide();
            table.draw();
        }
    });

    $('#applyDateFilter').click(function() {
        table.draw();
    });

    $('#clearFilters').click(function() {
        $('#statusFilter, #branchFilter, #employeeFilter, #dateFilter').val('');
        $('#startDate, #endDate').val('');
        $('#customDateRange').hide();
        table.draw();
    });

    // Quick action handlers
    $(document).on('click', '.check-retirement', function() {
        const id = $(this).data('id');
        const number = $(this).data('number');
        showCheckModal(id, number);
    });

    $(document).on('click', '.approve-retirement', function() {
        const id = $(this).data('id');
        const number = $(this).data('number');
        showApproveModal(id, number);
    });

    // Modal functions (will be implemented with quick-actions modal file)
    function showCheckModal(id, number) {
        $('#checkModal').modal('show');
        $('#checkModalLabel').html(`<i class="bx bx-check-circle me-2"></i>Check Retirement - ${number}`);
        $('#checkForm').attr('action', `/imprest/retirement/${id}/check`);
    }

    function showApproveModal(id, number) {
        $('#approveModal').modal('show');
        $('#approveModalLabel').html(`<i class="bx bx-check-double me-2"></i>Approve Retirement - ${number}`);
        $('#approveForm').attr('action', `/imprest/retirement/${id}/approve`);
    }

    // Auto-refresh every 30 seconds
    setInterval(function() {
        table.ajax.reload(null, false);
    }, 30000);
});
</script>
@endpush