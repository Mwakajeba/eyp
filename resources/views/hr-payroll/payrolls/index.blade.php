@extends('layouts.main')

@section('title', 'Payroll Management')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Payrolls', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0"><i class="bx bx-money me-2"></i>Payroll Management</h5>
                    <p class="mb-0 text-muted">Manage and track all payrolls</p>
                </div>
                <a href="{{ route('hr.payrolls.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Create New Payroll
                </a>
            </div>
            <hr />

            <!-- Dashboard Stats -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card radius-10 border-start border-0 border-3 border-primary">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">Total Payrolls</p>
                                    <h4 class="my-1 text-primary">{{ number_format($stats['total']) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-primary"><i class="bx bx-receipt align-middle"></i> All payrolls</span>
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
                    <div class="card radius-10 border-start border-0 border-3 border-warning">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">Draft</p>
                                    <h4 class="my-1 text-warning">{{ number_format($stats['draft']) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-warning"><i class="bx bx-edit align-middle"></i> Pending creation</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                    <i class="bx bx-edit"></i>
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
                                    <p class="mb-0 text-secondary">Processing</p>
                                    <h4 class="my-1 text-info">{{ number_format($stats['processing']) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-info"><i class="bx bx-time-five align-middle"></i> In progress</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                    <i class="bx bx-time-five"></i>
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
                                    <p class="mb-0 text-secondary">Completed</p>
                                    <h4 class="my-1 text-success">{{ number_format($stats['completed']) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Ready for payment</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                    <i class="bx bx-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Row Stats -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card radius-10 border-start border-0 border-3 border-danger">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">Rejected</p>
                                    <h4 class="my-1 text-danger">{{ number_format($stats['rejected']) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-danger"><i class="bx bx-x-circle align-middle"></i> Cancelled/Rejected</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-danger text-white ms-auto">
                                    <i class="bx bx-x-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="payrolls-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Year</th>
                                    <th>Month</th>
                                    <th>Status</th>
                                    <th>Total Gross Pay</th>
                                    <th>Total Deductions</th>
                                    <th>Net Pay</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
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
    
    .bg-gradient-danger {
        background: linear-gradient(45deg, #dc3545, #bb2d3b) !important;
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }

    .badge {
        font-size: 0.85em;
        padding: 0.5em 0.75em;
        border-radius: 0.375rem;
    }
    .badge i {
        font-size: 0.9em;
    }
    .btn-group .btn {
        border-radius: 0.25rem;
        margin-right: 2px;
    }
    .table td {
        vertical-align: middle;
    }
</style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script nonce="{{ $cspNonce ?? '' }}">
        $(document).ready(function () {
            $('#payrolls-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('hr.payrolls.index') }}",
                    type: 'GET'
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'year', name: 'year' },
                    { data: 'month_name', name: 'month_name' },
                    { data: 'status_badge', name: 'status', orderable: false },
                    { data: 'total_gross_pay', name: 'total_gross_pay', orderable: false },
                    { data: 'total_deductions', name: 'total_deductions', orderable: false },
                    { data: 'net_pay', name: 'net_pay', orderable: false },
                    { data: 'creator.name', name: 'creator.name' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[1, 'desc'], [2, 'desc']], // Order by year desc, then month desc
                pageLength: 25,
                responsive: true,
                language: {
                    processing: "Loading payrolls...",
                    emptyTable: "No payrolls found",
                    zeroRecords: "No matching payrolls found"
                }
            });
        });

        // Process payroll function
        function processPayroll(payrollId) {
            Swal.fire({
                title: 'Process Payroll?',
                text: "This will calculate salaries for all employees. This may take a few minutes for large payrolls. Continue?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, process it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show processing modal
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we process the payroll.',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "{{ route('hr.payrolls.process', ':id') }}".replace(':id', payrollId),
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    $('#payrolls-table').DataTable().ajax.reload();
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function (xhr) {
                            const response = xhr.responseJSON;
                            Swal.fire('Error!', response.message || 'An error occurred while processing the payroll.', 'error');
                        }
                    });
                }
            });
        }

        // Approve payroll function
        function approvePayroll(payrollId) {
            Swal.fire({
                title: 'Approve Payroll?',
                input: 'textarea',
                inputLabel: 'Remarks (optional)',
                inputPlaceholder: 'Enter any remarks for this approval...',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Approve',
                cancelButtonText: 'Cancel',
                inputValidator: (value) => {
                    if (value && value.length > 500) {
                        return 'Remarks cannot exceed 500 characters';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Approving...',
                        text: 'Please wait while we approve the payroll.',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "{{ route('hr.payrolls.approve', ':id') }}".replace(':id', payrollId),
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            remarks: result.value || ''
                        },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Approved!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    $('#payrolls-table').DataTable().ajax.reload();
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function (xhr) {
                            const response = xhr.responseJSON;
                            Swal.fire('Error!', response.message || 'An error occurred while approving the payroll.', 'error');
                        }
                    });
                }
            });
        }

        // Reject payroll function
        function rejectPayroll(payrollId) {
            Swal.fire({
                title: 'Reject Payroll?',
                input: 'textarea',
                inputLabel: 'Rejection reason (required)',
                inputPlaceholder: 'Enter the reason for rejecting this payroll...',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Reject',
                cancelButtonText: 'Cancel',
                inputValidator: (value) => {
                    if (!value || value.trim() === '') {
                        return 'Please provide a reason for rejection';
                    }
                    if (value.length > 500) {
                        return 'Reason cannot exceed 500 characters';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Rejecting...',
                        text: 'Please wait while we reject the payroll.',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "{{ route('hr.payrolls.reject', ':id') }}".replace(':id', payrollId),
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            remarks: result.value
                        },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Rejected!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    $('#payrolls-table').DataTable().ajax.reload();
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function (xhr) {
                            const response = xhr.responseJSON;
                            Swal.fire('Error!', response.message || 'An error occurred while rejecting the payroll.', 'error');
                        }
                    });
                }
            });
        }



        // Delete payroll function with SweetAlert
        function deletePayroll(payrollId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the payroll and all related employee data. This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait while we delete the payroll.',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: `{{ url('hr-payroll/payrolls') }}/${payrollId}`,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE'
                        },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    $('#payrolls-table').DataTable().ajax.reload();
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function (xhr) {
                            const response = xhr.responseJSON;
                            Swal.fire('Error!', response.message || 'An error occurred while deleting the payroll.', 'error');
                        }
                    });
                }
            });
        }
    </script>
@endpush