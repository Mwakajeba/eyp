@extends('layouts.main')

@section('title', 'Salary Advances')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<style>
    .stat-card {
        border-left: 4px solid;
        transition: transform 0.2s, box-shadow 0.2s;
        height: 100%;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .stat-card.border-primary {
        border-left-color: #0d6efd;
    }

    .stat-card.border-success {
        border-left-color: #198754;
    }

    .stat-card.border-secondary {
        border-left-color: #6c757d;
    }

    .stat-card.border-info {
        border-left-color: #0dcaf0;
    }

    .widgets-icons {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 24px;
    }

    .bg-light-primary {
        background-color: rgba(13, 110, 253, 0.1);
    }

    .bg-light-success {
        background-color: rgba(25, 135, 84, 0.1);
    }

    .bg-light-secondary {
        background-color: rgba(108, 117, 125, 0.1);
    }

    .bg-light-info {
        background-color: rgba(13, 202, 240, 0.1);
    }

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
        min-width: auto;
    }

    .action-buttons .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .modern-table {
        border-radius: 8px;
        overflow: hidden;
    }

    .modern-table thead th {
        background-color: #f8fafc;
        color: #374151;
        font-weight: 600;
        font-size: 0.875rem;
        padding: 1rem 0.75rem;
        border: none;
        border-bottom: 2px solid #e5e7eb;
    }

    .modern-table tbody tr {
        transition: background-color 0.2s ease;
        border-bottom: 1px solid #f3f4f6;
    }

    .modern-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .modern-table tbody td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
        border: none;
        font-size: 0.875rem;
    }
</style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Salary Advances', 'url' => '#', 'icon' => 'bx bx-credit-card']
            ]" />

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="mb-1">
                        <i class="bx bx-credit-card text-primary me-2"></i>Salary Advances Management
                    </h5>
                    <p class="text-muted mb-0">Manage and track employee salary advances</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.salary-advances.bulk-repayment') }}" class="btn btn-outline-success">
                        <i class="bx bx-upload me-1"></i>Bulk Repayment
                    </a>
                    <a href="{{ route('hr.salary-advances.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>New Salary Advance
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bx bx-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bx bx-error-circle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bx bx-error-circle me-2"></i>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card border-primary">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Total Advances</p>
                                    <h4 class="mb-0">{{ $statistics['total'] ?? 0 }}</h4>
                                </div>
                                <div class="widgets-icons bg-light-primary text-primary">
                                    <i class="bx bx-list-ul"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-success">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Active Advances</p>
                                    <h4 class="mb-0">{{ $statistics['active'] ?? 0 }}</h4>
                                </div>
                                <div class="widgets-icons bg-light-success text-success">
                                    <i class="bx bx-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-secondary">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Inactive Advances</p>
                                    <h4 class="mb-0">{{ $statistics['inactive'] ?? 0 }}</h4>
                                </div>
                                <div class="widgets-icons bg-light-secondary text-secondary">
                                    <i class="bx bx-x-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-info">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Total Amount</p>
                                    <h4 class="mb-0">TZS {{ number_format($statistics['total_amount'] ?? 0, 0) }}</h4>
                                </div>
                                <div class="widgets-icons bg-light-info text-info">
                                    <i class="bx bx-money"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="bx bx-table me-2"></i>Salary Advances
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="salary-advances-table" class="table table-striped table-hover modern-table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Reference</th>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Bank Account</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Monthly Deduction</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTables will populate this via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="delete-form" method="POST" style="display:none">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        $('#salary-advances-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('hr.salary-advances.index') }}",
                type: 'GET'
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'reference_display', name: 'reference' },
                { data: 'date_display', name: 'date' },
                { data: 'employee_display', name: 'employee_id' },
                { data: 'bank_account_display', name: 'bank_account_id' },
                { data: 'amount_display', name: 'amount', className: 'text-end' },
                { data: 'monthly_deduction_display', name: 'monthly_deduction', className: 'text-end' },
                { data: 'status_badge', name: 'is_active' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[2, 'desc']], // Order by date descending
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            stateSave: true,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="bx bx-spreadsheet"></i> Excel',
                    className: 'btn btn-success btn-sm',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7],
                        format: {
                            body: function(data, row, column, node) {
                                return $(data).text().trim() || data;
                            }
                        }
                    },
                    title: 'Salary Advances Report'
                },
                {
                    extend: 'pdf',
                    text: '<i class="bx bx-file"></i> PDF',
                    className: 'btn btn-danger btn-sm',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7],
                        format: {
                            body: function(data, row, column, node) {
                                return $(data).text().trim() || data;
                            }
                        }
                    },
                    title: 'Salary Advances Report'
                }
            ],
            language: {
                processing: "Loading salary advances...",
                emptyTable: "No salary advances found",
                zeroRecords: "No matching salary advances found"
            }
        });
    });

    function deleteAdvance(id, reference) {
        Swal.fire({
            title: 'Delete Salary Advance?',
            html: `
                <div class="text-center">
                    <i class="bx bx-trash text-danger" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <p class="mb-3">You are about to delete salary advance:</p>
                    <strong class="text-primary">${reference}</strong>
                    <p class="text-muted mt-2">This action cannot be undone!</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="bx bx-trash me-1"></i>Yes, Delete It!',
            cancelButtonText: '<i class="bx bx-x me-1"></i>Cancel',
            buttonsStyling: true,
            customClass: {
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-secondary'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('delete-form');
                form.action = `/hr-payroll/salary-advances/${id}`;
                form.submit();
            }
        });
    }
</script>
@endpush
