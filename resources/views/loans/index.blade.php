@extends('layouts.main')

@section('title', 'Loan Management')

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

    .radius-10 {
        border-radius: 10px;
    }

    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Loan Management', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card radius-10 mb-3">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0">
                                <i class="bx bx-money me-2"></i>Loan Management
                                </h5>
                                <p class="mb-0 text-muted">Manage and monitor all loan facilities and their status</p>
                            </div>
                            <div>
                                <a href="{{ route('loans.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>New Loan
                                </a>
                            </div>
                        </div>
                            </div>
                        </div>

                <!-- Statistics Cards (redesigned similar to Sales Invoices) -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card radius-10 border-start border-0 border-3 border-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <p class="mb-0 text-secondary">Total Loans</p>
                                        <h4 class="my-1 text-primary">{{ number_format($stats['total']) }}</h4>
                                        <p class="mb-0 font-13">
                                            <span class="text-primary">
                                                <i class="bx bx-receipt align-middle"></i> All facilities
                                            </span>
                                        </p>
                                    </div>
                                    <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                        <i class="bx bx-receipt"></i>
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
                                        <p class="mb-0 text-secondary">Active</p>
                                        <h4 class="my-1 text-success">{{ number_format($stats['active']) }}</h4>
                                        <p class="mb-0 font-13">
                                            <span class="text-success">
                                                <i class="bx bx-check-circle align-middle"></i> Currently running
                                            </span>
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
                                        <p class="mb-0 text-secondary">Draft</p>
                                        <h4 class="my-1 text-warning">{{ number_format($stats['draft']) }}</h4>
                                        <p class="mb-0 font-13">
                                            <span class="text-warning">
                                                <i class="bx bx-time align-middle"></i> In setup / pending approval
                                            </span>
                                        </p>
                                    </div>
                                    <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                        <i class="bx bx-time"></i>
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
                                        <p class="mb-0 text-secondary">Disbursed</p>
                                        <h4 class="my-1 text-info">{{ number_format($stats['disbursed']) }}</h4>
                                        <p class="mb-0 font-13">
                                            <span class="text-info">
                                                <i class="bx bx-export align-middle"></i> Funds released
                                            </span>
                                        </p>
                                    </div>
                                    <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                        <i class="bx bx-export"></i>
                                    </div>
                                </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="card radius-10 mb-4">
                            <div class="card-body">
                                <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Branch</label>
                                <select class="form-select" id="branchFilter">
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="all" {{ $status == 'all' ? 'selected' : '' }}>All Status</option>
                                    <option value="draft" {{ $status == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="approved" {{ $status == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="disbursed" {{ $status == 'disbursed' ? 'selected' : '' }}>Disbursed</option>
                                    <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="closed" {{ $status == 'closed' ? 'selected' : '' }}>Closed</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-primary d-block w-100" id="filterBtn">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                                </div>
                            </div>
                        </div>

                        <!-- Loans Table -->
                        <div class="table-responsive">
                            <table id="loansTable" class="table table-bordered table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Loan Number</th>
                                        <th>Bank</th>
                                        <th>Principal Amount</th>
                                        <th>Interest Rate</th>
                                        <th>Term (Months)</th>
                                        <th>Outstanding</th>
                                        <th>Status</th>
                                        <th>Branch</th>
                                        <th>Actions</th>
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
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#loansTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('loans.data') }}",
            data: function(d) {
                d.branch_id = $('#branchFilter').val();
                d.status = $('#statusFilter').val();
            }
        },
        columns: [
            { data: 'loan_number_link', name: 'loan_number', orderable: true, searchable: true },
            { data: 'bank_name_display', name: 'bank_name', orderable: true, searchable: true },
            { data: 'principal_amount_formatted', name: 'principal_amount', orderable: true, searchable: false },
            { data: 'interest_rate_formatted', name: 'interest_rate', orderable: true, searchable: false },
            { data: 'term_months', name: 'term_months', orderable: true, searchable: false },
            { data: 'outstanding_principal_formatted', name: 'outstanding_principal', orderable: true, searchable: false },
            { data: 'status_badge', name: 'status', orderable: true, searchable: true },
            { data: 'branch_name', name: 'branch.name', orderable: true, searchable: true },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 20,
        lengthMenu: [[10, 20, 50, 100], [10, 20, 50, 100]],
        language: {
            processing: '<i class="bx bx-loader bx-spin"></i> Loading...',
            emptyTable: 'No loans found',
            zeroRecords: 'No matching loans found'
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        drawCallback: function(settings) {
            // Re-initialize tooltips if needed
            if (typeof $('[data-bs-toggle="tooltip"]').tooltip === 'function') {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        }
    });

    // Filter button click
    $('#filterBtn').on('click', function() {
        table.ajax.reload();
    });

    // Auto-reload on filter change (optional - remove if you want manual filter button only)
    // $('#branchFilter, #statusFilter').on('change', function() {
    //     table.ajax.reload();
    // });
});
</script>
@endpush
@endsection

