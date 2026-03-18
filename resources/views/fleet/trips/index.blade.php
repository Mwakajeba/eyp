@extends('layouts.main')

@section('title', 'Trip Planning - Fleet Management')

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
    
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Trip Planning', 'url' => '#', 'icon' => 'bx bx-trip']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-trip me-2"></i>Trip Planning & Dispatch</h5>
                                <p class="mb-0 text-muted">Create and manage trip requests, assignments, and scheduling</p>
                            </div>
                            <div>
                                <a href="{{ route('fleet.trips.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Create Trip
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Trips</p>
                                <h4 class="my-1 text-primary">{{ number_format($totalTrips) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-trip align-middle"></i> All trips</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-trip"></i>
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
                                <p class="mb-0 text-secondary">Planned</p>
                                <h4 class="my-1 text-warning">{{ number_format($plannedTrips) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-calendar align-middle"></i> Scheduled</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-calendar"></i>
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
                                <p class="mb-0 text-secondary">Active</p>
                                <h4 class="my-1 text-info">{{ number_format($activeTrips) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-run align-middle"></i> In progress</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-run"></i>
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
                                <h4 class="my-1 text-success">{{ number_format($completedTrips) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Finished</span>
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

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label small">Status</label>
                        <select id="filter-status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="planned">Planned</option>
                            <option value="dispatched">Dispatched</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Trip Type</label>
                        <select id="filter-trip-type" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="delivery">Delivery</option>
                            <option value="pickup">Pickup</option>
                            <option value="service">Service</option>
                            <option value="transport">Transport</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-filters">
                            <i class="bx bx-x me-1"></i>Clear Filters
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="trips-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Trip Number</th>
                                <th>Vehicle</th>
                                <th>Driver</th>
                                <th>Route</th>
                                <th>Planned Start</th>
                                <th>Status</th>
                                <th>Total Revenue</th>
                                <th>Total Costs</th>
                                <th>Profit/Loss</th>
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
    .badge {
        font-size: 0.75rem;
        padding: 0.5em 0.75em;
    }

    .table th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
    }

    .card-header {
        border-radius: 0.375rem 0.375rem 0 0 !important;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    const tripsTable = $('#trips-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("fleet.trips.data") }}',
            data: function(d) {
                d.status = $('#filter-status').val();
                d.trip_type = $('#filter-trip-type').val();
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
            { data: 'trip_number', name: 'trip_number' },
            { data: 'vehicle_display', name: 'vehicle_display', orderable: false, searchable: false },
            { data: 'driver_display', name: 'driver_display', orderable: false, searchable: false },
            { data: 'route_display', name: 'route_display', orderable: false, searchable: false },
            { data: 'planned_start_date', name: 'planned_start_date' },
            { data: 'status_display', name: 'status', orderable: false },
            { data: 'revenue_display', name: 'revenue_display', orderable: false, searchable: false },
            { data: 'costs_display', name: 'total_costs', orderable: false },
            { data: 'profit_display', name: 'profit_loss', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<div class="text-center"><i class="bx bx-loader-alt bx-spin bx-lg"></i> Loading trips...</div>'
        }
    });

    $('#filter-status, #filter-trip-type').on('change', function() {
        tripsTable.ajax.reload();
    });

    $('#clear-filters').on('click', function() {
        $('#filter-status, #filter-trip-type').val('');
        tripsTable.ajax.reload();
    });
});
</script>
@endpush
