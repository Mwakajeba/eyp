@extends('layouts.main')

@section('title', 'Biometric Devices Management')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Biometric Devices', 'url' => '#', 'icon' => 'bx bx-fingerprint']
            ]" />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-uppercase">
                    <i class="bx bx-fingerprint me-1"></i>Biometric Devices Management
                </h6>
                <a href="{{ route('hr.biometric-devices.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Add Device
                </a>
            </div>
            <hr />

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="biometricDevicesTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Device Code</th>
                                    <th>Device Name</th>
                                    <th>Type</th>
                                    <th>Branch</th>
                                    <th>Connection</th>
                                    <th>Sync Status</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
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
    let table = $('#biometricDevicesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('hr.biometric-devices.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'device_code', name: 'device_code'},
            {data: 'device_name', name: 'device_name'},
            {data: 'device_type', name: 'device_type'},
            {data: 'branch_name', name: 'branch_name'},
            {data: 'connection_info', name: 'connection_info', orderable: false, searchable: false},
            {data: 'sync_status', name: 'sync_status', orderable: false, searchable: false},
            {data: 'status_badge', name: 'is_active', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        pageLength: 25,
        responsive: true,
        order: [[1, 'asc']]
    });

    $(document).on('click', '.sync-btn', function() {
        let id = $(this).data('id');
        $.ajax({
            url: `{{ route('hr.biometric-devices.index') }}/${id}/sync`,
            type: 'POST',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            success: function(response) {
                if (response.success) {
                    table.ajax.reload();
                    Swal.fire({icon: 'success', title: 'Synced!', text: response.message, timer: 3000, showConfirmButton: false});
                }
            },
            error: function(xhr) {
                Swal.fire({icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Something went wrong.'});
            }
        });
    });

    $(document).on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        
        Swal.fire({
            title: 'Delete Biometric Device',
            text: `Are you sure you want to delete "${name}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ route('hr.biometric-devices.index') }}/${id}`,
                    type: 'DELETE',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            Swal.fire({icon: 'success', title: 'Deleted!', text: response.message, timer: 3000, showConfirmButton: false});
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Something went wrong.'});
                    }
                });
            }
        });
    });
});
</script>
@endpush

