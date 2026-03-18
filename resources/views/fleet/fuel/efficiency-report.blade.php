@extends('layouts.main')

@section('title', 'Fuel Efficiency Report - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fuel Management', 'url' => route('fleet.fuel.index'), 'icon' => 'bx bx-gas-pump'],
            ['label' => 'Efficiency Report', 'url' => '#', 'icon' => 'bx bx-line-chart']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-orange text-white border-0">
                <h5 class="mb-0"><i class="bx bx-line-chart me-2"></i>Fuel Efficiency Report</h5>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Vehicle</label>
                        <select class="form-select" id="vehicle-filter">
                            <option value="">All Vehicles</option>
                            @foreach($vehicles as $v)
                                <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->registration_number ?? 'N/A' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" class="form-control" id="date-from-filter" value="{{ date('Y-m-01') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" class="form-control" id="date-to-filter" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="button" class="btn btn-orange w-100" id="apply-filters" style="background-color: #fd7e14; border-color: #fd7e14; color: #fff;">
                            <i class="bx bx-filter me-1"></i>Apply
                        </button>
                        <button type="button" class="btn btn-success w-100" id="export-report" style="background-color: #28a745; border-color: #28a745; color: #fff;">
                            <i class="bx bx-export me-1"></i>Export
                        </button>
                    </div>
                </div>

                <!-- Report Table -->
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="efficiency-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Vehicle</th>
                                <th>Total Liters</th>
                                <th>Total Cost</th>
                                <th>Total Distance (km)</th>
                                <th>Avg Efficiency (km/L)</th>
                                <th>Cost per km (TZS)</th>
                                <th>Fill Count</th>
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
    const table = $('#efficiency-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("fleet.fuel.efficiency-data") }}',
            data: function(d) {
                d.vehicle_id = $('#vehicle-filter').val();
                d.date_from = $('#date-from-filter').val();
                d.date_to = $('#date-to-filter').val();
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
            { data: 'vehicle_display', name: 'vehicle_display', orderable: false },
            { data: 'total_liters', name: 'total_liters' },
            { data: 'total_cost_display', name: 'total_cost', orderable: false },
            { data: 'total_distance', name: 'total_distance' },
            { data: 'avg_efficiency', name: 'avg_efficiency' },
            { data: 'cost_per_km_display', name: 'cost_per_km', orderable: false },
            { data: 'fill_count', name: 'fill_count' }
        ],
        order: [[5, 'desc']], // Sort by efficiency descending
        pageLength: 25
    });

    // Apply filters
    $('#apply-filters').on('click', function() {
        table.ajax.reload();
    });

    // Auto-apply on filter change
    $('#vehicle-filter, #date-from-filter, #date-to-filter').on('change', function() {
        table.ajax.reload();
    });

    // Export report
    $('#export-report').on('click', function() {
        const vehicleId = $('#vehicle-filter').val();
        const dateFrom = $('#date-from-filter').val();
        const dateTo = $('#date-to-filter').val();
        
        let url = '{{ route("fleet.fuel.efficiency-export") }}?';
        if (vehicleId) url += 'vehicle_id=' + vehicleId + '&';
        if (dateFrom) url += 'date_from=' + dateFrom + '&';
        if (dateTo) url += 'date_to=' + dateTo;
        
        window.open(url, '_blank');
    });
});
</script>
@endpush
