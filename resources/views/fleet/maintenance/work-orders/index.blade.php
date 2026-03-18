@extends('layouts.main')

@section('title', 'Maintenance Work Orders - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Maintenance Work Orders', 'url' => '#', 'icon' => 'bx bx-wrench']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-purple text-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-wrench me-2"></i>Maintenance Work Orders</h5>
                    <div class="text-white-50">Schedule preventive maintenance, manage work orders, and track repairs</div>
                </div>
                <div>
                    <a href="{{ route('fleet.maintenance.work-orders.create') }}" class="btn btn-purple">
                        <i class="bx bx-plus me-1"></i>Create Work Order
                    </a>
                </div>
            </div>
            <div class="card-body pt-0">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label small">Status</label>
                        <select id="filter-status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="draft">Draft</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="in_progress">In Progress</option>
                            <option value="on_hold">On Hold</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Maintenance Type</label>
                        <select id="filter-maintenance-type" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="preventive">Preventive</option>
                            <option value="corrective">Corrective</option>
                            <option value="major_overhaul">Major Overhaul</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Priority</label>
                        <select id="filter-priority" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-filters">
                            <i class="bx bx-x me-1"></i>Clear Filters
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="work-orders-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>WO Number</th>
                                <th>Vehicle</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Scheduled Date</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Cost</th>
                                <th>Actions</th>
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
    .btn-purple {
        background-color: #6f42c1;
        border-color: #6f42c1;
        color: #fff;
    }
    .btn-purple:hover {
        background-color: #5a32a3;
        border-color: #5a32a3;
        color: #fff;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    const workOrdersTable = $('#work-orders-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("fleet.maintenance.work-orders.data") }}',
            data: function(d) {
                d.status = $('#filter-status').val();
                d.maintenance_type = $('#filter-maintenance-type').val();
                d.priority = $('#filter-priority').val();
            }
        },
        columns: [
            {
                data: null,
                name: 'serial',
                orderable: false,
                searchable: false,
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'wo_number', name: 'wo_number' },
            { data: 'vehicle_display', name: 'vehicle_display', orderable: false },
            { data: 'type_display', name: 'maintenance_type', orderable: false },
            { data: 'maintenance_category', name: 'maintenance_category' },
            { data: 'scheduled_date_display', name: 'scheduled_date' },
            { data: 'status_display', name: 'status', orderable: false },
            { data: 'priority', name: 'priority' },
            { data: 'cost_display', name: 'cost_display', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<div class="text-center"><i class="bx bx-loader-alt bx-spin bx-lg"></i> Loading work orders...</div>'
        }
    });

    // Filter event handlers
    $('#filter-status, #filter-maintenance-type, #filter-priority').on('change', function() {
        workOrdersTable.ajax.reload();
    });

    // Clear filters
    $('#clear-filters').on('click', function() {
        $('#filter-status, #filter-maintenance-type, #filter-priority').val('');
        workOrdersTable.ajax.reload();
    });
});
</script>
@endpush
