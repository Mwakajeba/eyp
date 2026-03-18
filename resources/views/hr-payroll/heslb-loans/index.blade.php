@extends('layouts.main')

@section('title', 'HESLB Loans')

@push('styles')
<style>
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
    }

    .action-buttons .btn {
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
        transition: all 0.2s ease;
        border-width: 1px;
        min-width: 80px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .action-buttons .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .action-buttons .icon-btn {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        padding: 0;
        border: none;
        background: #f8fafc;
        color: #0d6efd;
        box-shadow: 0 1px 4px rgba(13, 110, 253, 0.08);
        transition: background 0.2s, color 0.2s, box-shadow 0.2s;
    }

    .action-buttons .icon-btn:hover {
        background: #0d6efd;
        color: #fff;
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.18);
    }

    .stat-card {
        border-left: 4px solid;
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
</style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'HESLB Loans', 'url' => '#', 'icon' => 'bx bx-book']
            ]" />

            <div class="row">
                <div class="col-12">
                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-0 text-uppercase">HESLB LOAN MANAGEMENT</h4>
                            <p class="text-muted mb-0">Manage Higher Education Students' Loans Board loans for employees</p>
                        </div>
                        <a href="{{ route('hr.heslb-loans.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i>Add New Loan
                        </a>
                    </div>

                    <!-- Information Alert -->
                    <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="bx bx-info-circle me-2 mt-1" style="font-size: 1.25rem;"></i>
                            <div class="flex-grow-1">
                                <strong>Alternative Option Available:</strong>
                                <p class="mb-2 small">You can also manage HESLB loans through <strong>External Loan Management</strong> which provides additional features like institution management, reference numbers, flexible deduction types (fixed or percentage), and better tracking capabilities.</p>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('hr.external-loans.index') }}" class="btn btn-sm btn-outline-info">
                                        <i class="bx bx-credit-card me-1"></i>View External Loans
                                    </a>
                                    <a href="{{ route('hr.external-loans.create') }}" class="btn btn-sm btn-info">
                                        <i class="bx bx-plus me-1"></i>Create via External Loans
                                    </a>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card border-start border-primary">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-muted mb-1">Total Loans</h6>
                                            <h3 class="mb-0">{{ $stats['total'] }}</h3>
                                        </div>
                                        <div class="text-primary">
                                            <i class="bx bx-book fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card border-start border-success">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-muted mb-1">Active Loans</h6>
                                            <h3 class="mb-0">{{ $stats['active'] }}</h3>
                                        </div>
                                        <div class="text-success">
                                            <i class="bx bx-check-circle fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card border-start border-info">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-muted mb-1">Paid Off</h6>
                                            <h3 class="mb-0">{{ $stats['paid_off'] }}</h3>
                                        </div>
                                        <div class="text-info">
                                            <i class="bx bx-check-double fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card border-start border-warning">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-muted mb-1">Total Outstanding</h6>
                                            <h3 class="mb-0">TZS {{ number_format($stats['total_outstanding'], 2) }}</h3>
                                        </div>
                                        <div class="text-warning">
                                            <i class="bx bx-money fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loans Table -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bx bx-list-ul me-2"></i>All HESLB Loans
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="heslb-loans-table" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Employee Number</th>
                                            <th>Loan Number</th>
                                            <th>Original Amount</th>
                                            <th>Outstanding Balance</th>
                                            <th>Progress</th>
                                            <th>Status</th>
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
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        $('#heslb-loans-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('hr.heslb-loans.index') }}",
            columns: [
                { data: 'employee_name', name: 'employee_name' },
                { data: 'employee_number', name: 'employee_number' },
                { data: 'loan_number', name: 'loan_number' },
                { data: 'original_loan_amount', name: 'original_loan_amount' },
                { data: 'outstanding_balance', name: 'outstanding_balance' },
                { data: 'repayment_progress', name: 'repayment_progress' },
                { data: 'status', name: 'status', orderable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']],
            pageLength: 25,
            language: {
                processing: '<i class="bx bx-loader bx-spin"></i> Loading...'
            }
        });
    });
</script>
@endpush

