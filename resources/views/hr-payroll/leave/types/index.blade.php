@extends('layouts.main')

@section('title', 'Leave Types')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
            ['label' => 'Leave Management', 'url' => route('hr.leave.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Leave Types', 'url' => '#', 'icon' => 'bx bx-cog']
        ]" />
            <h6 class="mb-0 text-uppercase">LEAVE TYPES MANAGEMENT</h6>
            <hr />

            <!-- Action Buttons -->
            <div class="row mb-3">
                <div class="col-12 text-end">
                    <a href="{{ route('hr.leave.index') }}" class="btn btn-secondary me-2">
                        <i class="bx bx-arrow-back"></i> Back
                    </a>
                    @can('create', App\Models\Hr\LeaveType::class)
                        <a href="{{ route('hr.leave.types.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus"></i> New Leave Type
                        </a>
                    @elsecan('create leave type')
                        <a href="{{ route('hr.leave.types.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus"></i> New Leave Type
                        </a>
                    @endcan
                </div>
            </div>

            <!-- Leave Types Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bx bx-list-ul me-2"></i>Leave Types</h5>
                        </div>
                        <div class="card-body">
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

                            <div class="table-responsive">
                                <table id="leaveTypesTable" class="table table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Code</th>
                                            <th>Annual Entitlement</th>
                                            <th>Accrual Type</th>
                                            <th>Paid/Unpaid</th>
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
            var table = $('#leaveTypesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('hr.leave.types.index') }}',
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
                    { data: 'code', name: 'code' },
                    { data: 'annual_entitlement', name: 'annual_entitlement' },
                    { data: 'accrual_type', name: 'accrual_type' },
                    { data: 'is_paid_badge', name: 'is_paid' },
                    { data: 'is_active_badge', name: 'is_active' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[1, 'asc']]
            });

            // Delete button handler
            $(document).on('click', '.delete-btn', function() {
                var leaveTypeId = $(this).data('id');
                var leaveTypeName = $(this).data('name');
                
                Swal.fire({
                    title: 'Are you sure?',
                    html: `You are about to delete the leave type <strong>"${leaveTypeName}"</strong>.<br><br>This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Create delete form
                        var form = $('<form>', {
                            'method': 'POST',
                            'action': '{{ url('hr-payroll/leave/types') }}/' + leaveTypeId
                        });
                        
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_token',
                            'value': '{{ csrf_token() }}'
                        }));
                        
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_method',
                            'value': 'DELETE'
                        }));
                        
                        $('body').append(form);
                        
                        // Submit form via AJAX
                        $.ajax({
                            url: form.attr('action'),
                            type: 'POST',
                            data: form.serialize(),
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: response.message || 'Leave type has been deleted.',
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        table.ajax.reload(null, false);
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: response.message || 'Failed to delete leave type.',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            },
                            error: function(xhr) {
                                var message = 'Failed to delete leave type.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                Swal.fire({
                                    title: 'Error!',
                                    text: message,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            },
                            complete: function() {
                                form.remove();
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush