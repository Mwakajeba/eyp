@extends('layouts.main')

@section('title', 'Asset Disposals')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Disposals', 'url' => '#', 'icon' => 'bx bx-trash']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-trash me-2"></i>Asset Disposals</h5>
                    <div class="text-muted">Manage asset disposals, retirements, and write-offs</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('assets.disposals.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>New Disposal
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
                            <option value="rejected">Rejected</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Disposal Type</label>
                        <select name="disposal_type" id="filter_disposal_type" class="form-select form-select-sm">
                            <option value="">All Types</option>
                            <option value="sale">Sale</option>
                            <option value="scrap">Scrap</option>
                            <option value="write_off">Write-off</option>
                            <option value="donation">Donation</option>
                            <option value="loss">Loss/Theft</option>
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
                    <div class="col-md-1 d-flex align-items-end gap-2">
                        <button type="button" id="btn-filter" class="btn btn-sm btn-primary">
                            <i class="bx bx-search me-1"></i>Filter
                        </button>
                    </div>
                </div>

                <!-- Disposals Table -->
                <div class="table-responsive">
                    <table class="table table-striped align-middle" id="disposals-table" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Disposal #</th>
                                <th>Asset</th>
                                <th>Type</th>
                                <th>Proposed Date</th>
                                <th class="text-end">NBV</th>
                                <th class="text-end">Proceeds</th>
                                <th class="text-end">Gain/Loss</th>
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

    const table = $('#disposals-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("assets.disposals.data") }}',
            data: function(d) {
                d.asset_id = $('#filter_asset_id').val();
                d.status = $('#filter_status').val();
                d.disposal_type = $('#filter_disposal_type').val();
                d.date_from = $('#filter_date_from').val();
                d.date_to = $('#filter_date_to').val();
            },
            error: function(xhr) {
                console.error('Disposals DT error:', xhr.status, xhr.responseText);
                let msg = 'Failed to load disposals. ' + (xhr.status ? 'HTTP ' + xhr.status : '');
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
        order: [[3, 'desc']], // Sort by proposed date descending
        columns: [
            {
                data: 'disposal_number',
                name: 'disposal_number',
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
                data: 'disposal_type',
                name: 'disposal_type'
            },
            {
                data: 'proposed_disposal_date',
                name: 'proposed_disposal_date'
            },
            {
                data: 'net_book_value',
                name: 'net_book_value',
                className: 'text-end'
            },
            {
                data: 'disposal_proceeds',
                name: 'disposal_proceeds',
                className: 'text-end'
            },
            {
                data: 'gain_loss_display',
                name: 'gain_loss',
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
            emptyTable: '<div class="text-center text-muted py-4"><i class="bx bx-info-circle me-2"></i>No disposals found</div>',
            zeroRecords: '<div class="text-center text-muted py-4"><i class="bx bx-info-circle me-2"></i>No matching disposals found</div>'
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
        $('#filter_disposal_type').val('');
        $('#filter_date_from').val('');
        $('#filter_date_to').val('');
        table.ajax.reload();
    });
});
</script>
@endpush

