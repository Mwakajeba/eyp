@extends('layouts.main')

@section('title', 'Investment Trades')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Investment Management', 'url' => route('investments.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Investment Trades', 'url' => '#', 'icon' => 'bx bx-transfer']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">INVESTMENT TRADES</h6>
            <a href="{{ route('investments.trades.create') }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> New Trade
            </a>
        </div>
        <hr />

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Trade Type</label>
                        <select id="filter_trade_type" class="form-select select2-single">
                            <option value="all">All Types</option>
                            <option value="PURCHASE">Purchase</option>
                            <option value="SALE">Sale</option>
                            <option value="MATURITY">Maturity</option>
                            <option value="COUPON">Coupon</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Settlement Status</label>
                        <select id="filter_settlement_status" class="form-select select2-single">
                            <option value="all">All Statuses</option>
                            <option value="PENDING">Pending</option>
                            <option value="INSTRUCTED">Instructed</option>
                            <option value="SETTLED">Settled</option>
                            <option value="FAILED">Failed</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" id="btn-filter" class="btn btn-primary me-2">
                            <i class="bx bx-search"></i> Filter
                        </button>
                        <button type="button" id="btn-reset" class="btn btn-secondary">
                            <i class="bx bx-refresh"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trades Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tradesTable" class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Trade Date</th>
                                <th>Type</th>
                                <th>Investment</th>
                                <th>Price</th>
                                <th>Units</th>
                                <th class="text-end">Gross Amount</th>
                                <th>Settlement Status</th>
                                <th>Journal</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this -->
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
    // Initialize Select2 for filters
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Initialize DataTable
    const table = $('#tradesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("investments.trades.data") }}',
            data: function(d) {
                d.trade_type = $('#filter_trade_type').val();
                d.settlement_status = $('#filter_settlement_status').val();
            },
            error: function(xhr, status, error) {
                console.error('DataTables error:', error);
                console.error('Response:', xhr.responseText);
            }
        },
        columns: [
            { data: 'trade_date', name: 'trade_date' },
            { data: 'trade_type', name: 'trade_type', orderable: false },
            { data: 'investment', name: 'investment.instrument_code', orderable: false },
            { data: 'trade_price', name: 'trade_price' },
            { data: 'trade_units', name: 'trade_units' },
            { data: 'gross_amount', name: 'gross_amount', orderable: false },
            { data: 'settlement_status', name: 'settlement_status', orderable: false },
            { data: 'journal', name: 'journal', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[0, 'desc']],
        responsive: true,
        language: {
            processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>'
        },
        initComplete: function() {
            $('.dataTables_filter input').addClass('form-control form-control-sm');
        }
    });

    // Filter button
    $('#btn-filter').on('click', function() {
        table.ajax.reload();
    });

    // Reset button
    $('#btn-reset').on('click', function() {
        $('#filter_trade_type').val('all').trigger('change');
        $('#filter_settlement_status').val('all').trigger('change');
        table.ajax.reload();
    });
});
</script>
@endpush
