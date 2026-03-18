@extends('layouts.main')

@section('title', 'Fuel Management - Fleet Management')

@push('styles')
<style>
    .widgets-icons-2 { width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; background-color: #ededed; font-size: 27px; }
    .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0a58ca) !important; }
    .bg-gradient-success { background: linear-gradient(45deg, #198754, #146c43) !important; }
    .bg-gradient-info { background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important; }
    .bg-gradient-warning { background: linear-gradient(45deg, #ffc107, #ffb300) !important; }
    .card { transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
    .card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
    .radius-10 { border-radius: 10px; }
    .border-start { border-left-width: 3px !important; }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fuel Management', 'url' => '#', 'icon' => 'bx bx-gas-pump']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-gas-pump me-2"></i>Fuel Management</h5>
                                <p class="mb-0 text-muted">Track fuel consumption, efficiency, and manage fuel card integrations</p>
                            </div>
                            <div>
                                <a href="{{ route('fleet.fuel.efficiency-report') }}" class="btn btn-outline-primary me-2">
                                    <i class="bx bx-line-chart me-1"></i>Efficiency Report
                                </a>
                                <a href="{{ route('fleet.fuel.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Add Fuel Log
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
                                <p class="mb-0 text-secondary">Total Logs</p>
                                <h4 class="my-1 text-primary">{{ number_format($totalLogs) }}</h4>
                                <p class="mb-0 font-13"><span class="text-primary"><i class="bx bx-list-ul align-middle"></i> Records</span></p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-list-ul"></i>
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
                                <p class="mb-0 text-secondary">Total Liters</p>
                                <h4 class="my-1 text-info">{{ number_format($totalLiters, 2) }}</h4>
                                <p class="mb-0 font-13"><span class="text-info"><i class="bx bx-gas-pump align-middle"></i> Consumed</span></p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-gas-pump"></i>
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
                                <p class="mb-0 text-secondary">Total Cost</p>
                                <h4 class="my-1 text-warning">TZS {{ number_format($totalCost, 2) }}</h4>
                                <p class="mb-0 font-13"><span class="text-warning"><i class="bx bx-money align-middle"></i> Spent</span></p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-money"></i>
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
                                <p class="mb-0 text-secondary">Avg Efficiency</p>
                                <h4 class="my-1 text-success">{{ number_format($avgEfficiency ?? 0, 2) }} km/L</h4>
                                <p class="mb-0 font-13"><span class="text-success"><i class="bx bx-trending-up align-middle"></i> Fleet average</span></p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-trending-up"></i>
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
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="fuel-logs-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Trip</th>
                                <th>Fuel Station</th>
                                <th>Liters / Rate</th>
                                <th>Total Cost</th>
                                <th>Efficiency</th>
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
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('#fuel-logs-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("fleet.fuel.data") }}',
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
            { data: 'date_display', name: 'date_filled' },
            { data: 'vehicle_display', name: 'vehicle_display', orderable: false },
            { data: 'trip_display', name: 'trip_display', orderable: false },
            { data: 'fuel_station', name: 'fuel_station' },
            { data: 'fuel_details', name: 'fuel_details', orderable: false },
            { data: 'cost_display', name: 'total_cost' },
            { data: 'efficiency_display', name: 'efficiency_display', orderable: false },
            { data: 'approval_status_display', name: 'approval_status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']],
        pageLength: 25
    });
});
</script>
@endpush
