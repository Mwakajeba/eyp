@extends('layouts.main')

@section('title', 'Maintenance Schedules - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Maintenance Schedules', 'url' => '#', 'icon' => 'bx bx-calendar']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-purple text-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-calendar me-2"></i>Maintenance Schedules</h5>
                    <div class="text-white-50">Schedule preventive maintenance for fleet vehicles</div>
                </div>
                <div>
                    <a href="{{ route('fleet.maintenance.schedules.create') }}" class="btn btn-light">
                        <i class="bx bx-plus me-1"></i>Create Schedule
                    </a>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="schedules-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Schedule Name</th>
                                <th>Vehicle</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Next Due</th>
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
    $('#schedules-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("fleet.maintenance.schedules.data") }}',
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
            { data: 'schedule_name', name: 'schedule_name' },
            { data: 'vehicle_display', name: 'vehicle_display', orderable: false },
            { data: 'maintenance_category', name: 'maintenance_category' },
            { data: 'schedule_type', name: 'schedule_type' },
            { data: 'next_due_display', name: 'next_due_display', orderable: false },
            { data: 'status_display', name: 'current_status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        pageLength: 25
    });
});
</script>
@endpush
