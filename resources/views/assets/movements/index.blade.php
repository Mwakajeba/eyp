@extends('layouts.main')

@section('title', 'Asset Movements')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-archive'],
            ['label' => 'Movements', 'url' => '#', 'icon' => 'bx bx-transfer-alt']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1 text-primary">Asset Movements</h5>
                <p class="text-muted mb-0">Track and manage asset transfers between branches, departments, and users</p>
            </div>
            @can('create asset movements')
            <a href="{{ route('assets.movements.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i>Initiate Movement
            </a>
            @endcan
        </div>

        <div class="card border-top border-0 border-4 border-primary">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="movementsTable" class="table table-hover align-middle w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Voucher</th>
                                <th>Asset</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Status</th>
                                <th>Initiated</th>
                                <th>Actions</th>
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
@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('#movementsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('assets.movements.data') }}",
            type: 'GET'
        },
        columns: [
            {
                data: 'movement_voucher',
                name: 'movement_voucher'
            },
            {
                data: 'asset_info',
                name: 'asset.name',
                orderable: false,
                searchable: true
            },
            {
                data: 'from_location',
                name: 'from_location',
                orderable: false,
                searchable: false
            },
            {
                data: 'to_location',
                name: 'to_location',
                orderable: false,
                searchable: false
            },
            {
                data: 'status',
                name: 'status',
                orderable: true,
                searchable: true
            },
            {
                data: 'initiated_at',
                name: 'initiated_at',
                orderable: true,
                searchable: false
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[5, 'desc']], // Order by initiated_at (newest first)
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: '<div class="text-center py-5"><i class="bx bx-transfer fs-1 text-muted d-block mb-2"></i><div class="text-muted">No movements found</div><a href="{{ route('assets.movements.create') }}" class="btn btn-sm btn-primary mt-2"><i class="bx bx-plus me-1"></i>Initiate First Movement</a></div>',
            zeroRecords: '<div class="text-center py-5"><i class="bx bx-transfer fs-1 text-muted d-block mb-2"></i><div class="text-muted">No movements found</div></div>'
        },
        drawCallback: function(settings) {
            // Re-initialize tooltips if needed
            if (typeof $('[data-bs-toggle="tooltip"]').tooltip === 'function') {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        }
    });
});
</script>
@endpush
@endsection
