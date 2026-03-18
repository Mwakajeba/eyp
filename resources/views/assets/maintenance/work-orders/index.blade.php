@extends('layouts.main')

@section('title', 'Work Orders')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Maintenance', 'url' => route('assets.maintenance.index'), 'icon' => 'bx bx-wrench'],
            ['label' => 'Work Orders', 'url' => '#', 'icon' => 'bx bx-wrench']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1 text-primary">Work Orders</h5>
                <p class="text-muted mb-0">Manage work orders and track maintenance execution</p>
            </div>
            @can('create work orders')
            <a href="{{ route('assets.maintenance.work-orders.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i>New Work Order
            </a>
            @endcan
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="workOrdersTable" class="table table-striped table-hover align-middle" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>WO Number</th>
                                <th>Asset</th>
                                <th>Type</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Classification</th>
                                <th>Total Cost</th>
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
    $('#workOrdersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('assets.maintenance.work-orders.data') }}'
        },
        columns: [
            { data: 'wo_number_link', name: 'wo_number' },
            { data: 'asset_name', name: 'asset.name' },
            { data: 'maintenance_type_name', name: 'maintenanceType.name' },
            { data: 'assigned_to', name: 'assigned_to', orderable: false },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'cost_classification_badge', name: 'cost_classification', orderable: false },
            { data: 'total_cost', name: 'total_cost', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25
    });

    // Approve work order
    $(document).on('click', '.approve-wo', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Approve this work order?',
            text: 'This will mark the work order as approved and ready for execution.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, approve',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url('asset-management/maintenance/work-orders') }}/' + id + '/approve',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Approved!', response.message, 'success');
                            $('#workOrdersTable').DataTable().ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Failed to approve work order';
                        Swal.fire('Error!', message, 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush

