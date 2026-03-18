@extends('layouts.main')

@section('title', 'Asset Revaluations')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Revaluations', 'url' => '#', 'icon' => 'bx bx-trending-up']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-trending-up me-2"></i>Asset Revaluations & Impairments</h5>
                    <div class="text-muted">Manage asset revaluations, impairment tests and fair value adjustments</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('assets.revaluations.settings') }}" class="btn btn-info">
                        <i class="bx bx-cog me-1"></i>Settings
                    </a>
                    <a href="{{ route('assets.impairments.create') }}" class="btn btn-outline-danger">
                        <i class="bx bx-down-arrow-alt me-1"></i>New Impairment
                    </a>
                    <a href="{{ route('assets.revaluations.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>New Revaluation
                    </a>
                </div>
            </div>
            <div class="card-body pt-0">
                <!-- Filters -->
                <div class="row g-2 mb-3" id="filters-row">
                    <div class="col-md-3">
                        <label class="form-label small">Asset</label>
                        <select name="asset_id" id="filter_asset_id" class="form-select form-select-sm select2-single">
                            <option value="">All Assets</option>
                            @foreach($assets as $asset)
                                <option value="{{ $asset->id }}">{{ $asset->code }} - {{ $asset->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Status</label>
                        <select name="status" id="filter_status" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="pending_approval">Pending Approval</option>
                            <option value="approved">Approved</option>
                            <option value="posted">Posted</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Date From</label>
                        <input type="date" name="date_from" id="filter_date_from" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Date To</label>
                        <input type="date" name="date_to" id="filter_date_to" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="btn-filter" class="btn btn-sm btn-primary">
                            <i class="bx bx-search me-1"></i>Filter
                        </button>
                        <button type="button" id="btn-reset" class="btn btn-sm btn-outline-secondary">
                            <i class="bx bx-refresh me-1"></i>Reset
                        </button>
                    </div>
                </div>

                <!-- Revaluations Table -->
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="revaluations-table" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Revaluation #</th>
                                <th>Asset</th>
                                <th>Date</th>
                                <th class="text-end">Carrying Amount Before</th>
                                <th class="text-end">Fair Value</th>
                                <th class="text-end">Increase</th>
                                <th class="text-end">Decrease</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
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
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    const baseUrl = '{{ route("assets.revaluations.index") }}';
    const table = $('#revaluations-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("assets.revaluations.data") }}',
            data: function(d) {
                d.asset_id = $('#filter_asset_id').val();
                d.status = $('#filter_status').val();
                d.date_from = $('#filter_date_from').val();
                d.date_to = $('#filter_date_to').val();
            },
            error: function(xhr) {
                console.error('Revaluations DT error:', xhr.status, xhr.responseText);
                let msg = 'Failed to load revaluations. ' + (xhr.status ? 'HTTP ' + xhr.status : '');
                try {
                    const j = JSON.parse(xhr.responseText);
                    if (j.message) msg += ' - ' + j.message;
                } catch(e) {}
                Swal.fire({
                    icon: 'error',
                    title: 'Load Error',
                    text: msg
                });
            }
        },
        order: [[2, 'desc']], // Sort by date descending
        columns: [
            {
                data: 'revaluation_number',
                name: 'revaluation_number',
                render: function(d) {
                    return '<strong>' + (d || '-') + '</strong>';
                }
            },
            {
                data: null,
                name: 'asset_name',
                render: function(d, type, row) {
                    const code = row.asset_code || 'N/A';
                    const name = row.asset_name || 'N/A';
                    return '<div>' + code + '</div><small class="text-muted">' + name + '</small>';
                }
            },
            {
                data: 'revaluation_date',
                name: 'revaluation_date'
            },
            {
                data: 'carrying_amount_before',
                name: 'carrying_amount_before',
                className: 'text-end'
            },
            {
                data: 'fair_value',
                name: 'fair_value',
                className: 'text-end'
            },
            {
                data: 'revaluation_increase_display',
                name: 'revaluation_increase',
                className: 'text-end',
                orderable: false,
                searchable: false
            },
            {
                data: 'revaluation_decrease_display',
                name: 'revaluation_decrease',
                className: 'text-end',
                orderable: false,
                searchable: false
            },
            {
                data: 'status',
                name: 'status'
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<i class="bx bx-loader bx-spin"></i> Loading...',
            emptyTable: '<div class="text-center text-muted py-4"><i class="bx bx-info-circle me-2"></i>No revaluations found</div>',
            zeroRecords: '<div class="text-center text-muted py-4"><i class="bx bx-info-circle me-2"></i>No matching revaluations found</div>'
        }
    });

    // Filter button
    $('#btn-filter').on('click', function() {
        table.ajax.reload();
    });

    // Reset button
    $('#btn-reset').on('click', function() {
        $('#filter_asset_id').val('').trigger('change');
        $('#filter_status').val('');
        $('#filter_date_from').val('');
        $('#filter_date_to').val('');
        table.ajax.reload();
    });

    // Auto-reload on filter change
    $('#filter_asset_id, #filter_status, #filter_date_from, #filter_date_to').on('change', function() {
        // Optional: Auto-reload on change, or keep manual filter button
        // table.ajax.reload();
    });
});
</script>
@endpush
