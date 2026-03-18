@extends('layouts.main')

@section('title', 'Maintenance Requests')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Maintenance', 'url' => route('assets.maintenance.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Requests', 'url' => '#', 'icon' => 'bx bx-file']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1 text-primary">Maintenance Requests</h5>
                <p class="text-muted mb-0">Create and manage maintenance requests for assets</p>
            </div>
            @can('create maintenance requests')
            <a href="{{ route('assets.maintenance.requests.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i>New Request
            </a>
            @endcan
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="requestsTable" class="table table-striped table-hover align-middle" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Request #</th>
                                <th>Asset</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Requested By</th>
                                <th>Requested Date</th>
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
    $('#requestsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('assets.maintenance.requests.data') }}'
        },
        columns: [
            { data: 'request_number', name: 'request_number' },
            { data: 'asset_name', name: 'asset.name' },
            { data: 'maintenance_type_name', name: 'maintenanceType.name' },
            { data: 'priority', name: 'priority' },
            { data: 'requested_by_name', name: 'requestedBy.name' },
            { data: 'requested_date', name: 'requested_date' },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[5, 'desc']],
        pageLength: 25
    });

    // Approve request
    $(document).on('click', '.approve-request', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Approve this request?',
            text: 'This will mark the request as approved.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, approve',
            cancelButtonText: 'Cancel',
            input: 'textarea',
            inputLabel: 'Supervisor Notes (Optional)',
            inputPlaceholder: 'Add any notes...',
            inputAttributes: {
                'aria-label': 'Supervisor notes'
            },
            showCancelButton: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url('asset-management/maintenance/requests') }}/' + id + '/approve',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        supervisor_notes: result.value || ''
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Approved!', response.message, 'success');
                            $('#requestsTable').DataTable().ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Failed to approve request';
                        Swal.fire('Error!', message, 'error');
                    }
                });
            }
        });
    });

    // Reject request
    $(document).on('click', '.reject-request', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Reject this request?',
            text: 'Please provide a reason for rejection.',
            icon: 'warning',
            input: 'textarea',
            inputLabel: 'Rejection Reason',
            inputPlaceholder: 'Enter reason for rejection...',
            inputAttributes: {
                'aria-label': 'Rejection reason'
            },
            inputValidator: (value) => {
                if (!value) {
                    return 'You need to provide a reason!'
                }
            },
            showCancelButton: true,
            confirmButtonText: 'Yes, reject',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url('asset-management/maintenance/requests') }}/' + id + '/reject',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        supervisor_notes: result.value
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Rejected!', response.message, 'success');
                            $('#requestsTable').DataTable().ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Failed to reject request';
                        Swal.fire('Error!', message, 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush

