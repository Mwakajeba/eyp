@extends('layouts.main')

@section('title', 'Store Issues')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Issues', 'url' => '#', 'icon' => 'bx bx-package']
        ]" />

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-primary">Store Issues Management</h5>
                <small class="text-muted">Track and manage store item issues</small>
            </div>
            <div>
                <a href="{{ route('store-issues.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i> New Issue
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
                                <h6 class="mb-0">Total Issues</h6>
                                <h4 class="mb-0" id="totalIssues">{{ $statistics['total'] ?? 0 }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="bx bx-package fs-2"></i>
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
                                <h6 class="mb-0">Completed</h6>
                                <h4 class="mb-0" id="completedIssues">{{ $statistics['completed'] ?? 0 }}</h4>
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
                                <h6 class="mb-0">Partial</h6>
                                <h4 class="mb-0" id="partialIssues">{{ $statistics['partial'] ?? 0 }}</h4>
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
                                <h4 class="mb-0" id="monthlyIssues">{{ $statistics['monthly'] ?? 0 }}</h4>
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
                            <option value="partial">Partial</option>
                            <option value="completed">Completed</option>
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

        <!-- Issues Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="issuesTable">
                        <thead class="table-light">
                            <tr>
                                <th>Issue Number</th>
                                <th>Requisition</th>
                                <th>Branch</th>
                                <th>Issued By</th>
                                <th>Issue Date</th>
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
    const table = $('#issuesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('store-issues.data') }}",
            data: function(d) {
                d.branch_id = $('#filterBranch').val();
                d.status = $('#filterStatus').val();
                d.date_from = $('#filterDateFrom').val();
                d.date_to = $('#filterDateTo').val();
            }
        },
        columns: [
            { 
                data: 'issue_number', 
                name: 'issue_number',
                render: function(data, type, row) {
                    return `<a href="/store-issues/${row.id}" class="text-primary fw-medium">${data}</a>`;
                }
            },
            { 
                data: 'requisition_number', 
                name: 'store_requisition.requisition_number',
                render: function(data, type, row) {
                    return `<a href="/store-requisitions/${row.store_requisition_id}" class="text-info">${data}</a>`;
                }
            },
            { data: 'branch_name', name: 'branch.name' },
            { data: 'issued_by_name', name: 'issuedBy.name' },
            { 
                data: 'issue_date', 
                name: 'issue_date',
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
                        'partial': '<span class="badge bg-warning">Partial</span>',
                        'completed': '<span class="badge bg-success">Completed</span>'
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
                                <li><a class="dropdown-item" href="/store-issues/${row.id}">
                                    <i class="bx bx-show me-2"></i>View Details
                                </a></li>
                                <li><a class="dropdown-item" href="/store-issues/${row.id}/print" target="_blank">
                                    <i class="bx bx-printer me-2"></i>Print
                                </a></li>
                    `;
                    
                    if (row.status === 'partial') {
                        actions += `
                                <li><a class="dropdown-item" href="/store-issues/${row.id}/edit">
                                    <i class="bx bx-edit me-2"></i>Continue Issue
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
        order: [[4, 'desc']], // Order by issue date desc
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading...',
            emptyTable: "No store issues found",
            zeroRecords: "No matching store issues found"
        }
    });

    // Auto-refresh every 30 seconds
    setInterval(function() {
        table.ajax.reload(null, false);
    }, 30000);
});

function applyFilters() {
    $('#issuesTable').DataTable().ajax.reload();
}

function clearFilters() {
    $('#filterBranch').val('');
    $('#filterStatus').val('');
    $('#filterDateFrom').val('');
    $('#filterDateTo').val('');
    $('#issuesTable').DataTable().ajax.reload();
}

function exportData() {
    const params = new URLSearchParams({
        branch_id: $('#filterBranch').val() || '',
        status: $('#filterStatus').val() || '',
        date_from: $('#filterDateFrom').val() || '',
        date_to: $('#filterDateTo').val() || ''
    });
    
    window.open(`{{ route('store-issues.export') }}?${params.toString()}`, '_blank');
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+N for new issue
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        window.location.href = "{{ route('store-issues.create') }}";
    }
    
    // Ctrl+R for refresh
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        $('#issuesTable').DataTable().ajax.reload();
    }
});
</script>
@endpush