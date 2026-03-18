@extends('layouts.main')

@section('title', 'Store Returns')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Returns', 'url' => '#', 'icon' => 'bx bx-undo']
        ]" />

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-primary">Store Returns Management</h5>
                <small class="text-muted">Track and manage store item returns</small>
            </div>
            <div>
                <a href="{{ route('store-returns.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i> New Return
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-gradient-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Total Returns</h6>
                                <h4 class="mb-0" id="totalReturns">{{ $statistics['total'] ?? 0 }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="bx bx-undo fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-gradient-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Processed</h6>
                                <h4 class="mb-0" id="processedReturns">{{ $statistics['processed'] ?? 0 }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="bx bx-check-circle fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-gradient-warning text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0">Pending</h6>
                                <h4 class="mb-0" id="pendingReturns">{{ $statistics['pending'] ?? 0 }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="bx bx-time-five fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-gradient-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0">This Month</h6>
                                <h4 class="mb-0" id="monthlyReturns">{{ $statistics['monthly'] ?? 0 }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="bx bx-calendar fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="filterBranch" class="form-label">Branch</label>
                        <select id="filterBranch" class="form-select">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filterStatus" class="form-label">Status</label>
                        <select id="filterStatus" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="processed">Processed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filterDateFrom" class="form-label">Date From</label>
                        <input type="date" id="filterDateFrom" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="filterDateTo" class="form-label">Date To</label>
                        <input type="date" id="filterDateTo" class="form-control">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" onclick="applyFilters()">
                            <i class="bx bx-search me-1"></i> Apply Filters
                        </button>
                        <button type="button" class="btn btn-light" onclick="clearFilters()">
                            <i class="bx bx-refresh me-1"></i> Clear
                        </button>
                        <button type="button" class="btn btn-success" onclick="exportData()">
                            <i class="bx bx-download me-1"></i> Export
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Returns Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="returnsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Return Number</th>
                                <th>Store Issue</th>
                                <th>Branch</th>
                                <th>Returned By</th>
                                <th>Return Date</th>
                                <th>Items Count</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via DataTables -->
                        </tbody>
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
    // Initialize DataTable
    const table = $('#returnsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('store-returns.data') }}",
            data: function(d) {
                d.branch_id = $('#filterBranch').val();
                d.status = $('#filterStatus').val();
                d.date_from = $('#filterDateFrom').val();
                d.date_to = $('#filterDateTo').val();
            }
        },
        columns: [
            { 
                data: 'return_number', 
                name: 'return_number',
                render: function(data, type, row) {
                    return `<a href="/store-returns/${row.id}" class="text-primary fw-medium">${data}</a>`;
                }
            },
            { 
                data: 'issue_number', 
                name: 'store_issue.issue_number',
                render: function(data, type, row) {
                    return `<a href="/store-issues/${row.store_issue_id}" class="text-info">${data}</a>`;
                }
            },
            { data: 'branch_name', name: 'branch.name' },
            { data: 'returned_by_name', name: 'returnedBy.name' },
            { 
                data: 'return_date', 
                name: 'return_date',
                render: function(data) {
                    return new Date(data).toLocaleDateString();
                }
            },
            { 
                data: 'items_count', 
                name: 'items_count',
                className: 'text-center'
            },
            { 
                data: 'status', 
                name: 'status',
                render: function(data) {
                    const badges = {
                        'pending': '<span class="badge bg-warning">Pending</span>',
                        'processed': '<span class="badge bg-success">Processed</span>'
                    };
                    return badges[data] || `<span class="badge bg-secondary">${data}</span>`;
                }
            },
            { 
                data: 'actions', 
                name: 'actions', 
                orderable: false, 
                searchable: false,
                render: function(data, type, row) {
                    let actions = `
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bx bx-dots-horizontal-rounded"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/store-returns/${row.id}">
                                    <i class="bx bx-show me-2"></i>View Details
                                </a></li>
                                <li><a class="dropdown-item" href="/store-returns/${row.id}/print" target="_blank">
                                    <i class="bx bx-printer me-2"></i>Print
                                </a></li>
                    `;
                    
                    if (row.status === 'pending') {
                        actions += `
                                <li><a class="dropdown-item" href="/store-returns/${row.id}/edit">
                                    <i class="bx bx-edit me-2"></i>Edit Return
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-success" href="#" onclick="processReturn(${row.id})">
                                    <i class="bx bx-check me-2"></i>Process Return
                                </a></li>
                        `;
                    }
                    
                    actions += `
                            </ul>
                        </div>
                    `;
                    return actions;
                }
            }
        ],
        order: [[4, 'desc']], // Order by return date desc
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading...',
            emptyTable: "No store returns found",
            zeroRecords: "No matching store returns found"
        }
    });

    // Auto-refresh every 30 seconds
    setInterval(function() {
        table.ajax.reload(null, false);
    }, 30000);
});

function applyFilters() {
    $('#returnsTable').DataTable().ajax.reload();
}

function clearFilters() {
    $('#filterBranch').val('');
    $('#filterStatus').val('');
    $('#filterDateFrom').val('');
    $('#filterDateTo').val('');
    $('#returnsTable').DataTable().ajax.reload();
}

function exportData() {
    const params = new URLSearchParams({
        branch_id: $('#filterBranch').val() || '',
        status: $('#filterStatus').val() || '',
        date_from: $('#filterDateFrom').val() || '',
        date_to: $('#filterDateTo').val() || ''
    });
    
    window.open(`{{ route('store-returns.export') }}?${params.toString()}`, '_blank');
}

function processReturn(returnId) {
    Swal.fire({
        title: 'Process Return?',
        text: "This will mark the return as processed and update inventory levels.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, process it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/store-returns/${returnId}/process`,
                type: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Processed!', response.message, 'success').then(() => {
                            $('#returnsTable').DataTable().ajax.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error!', 'Failed to process return.', 'error');
                }
            });
        }
    });
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+N for new return
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        window.location.href = "{{ route('store-returns.create') }}";
    }
    
    // Ctrl+R for refresh
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        $('#returnsTable').DataTable().ajax.reload();
    }
});
</script>
@endpush