@extends('layouts.main')

@section('title', 'Investment Master')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Investment Management', 'url' => route('investments.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Investment Master', 'url' => '#', 'icon' => 'bx bx-package']
        ]" />
        
        <h6 class="mb-0 text-uppercase">
            INVESTMENT MASTER
            @php
                $hasAmortization = request('has_amortization');
                $showBadge = false;
                if ($hasAmortization) {
                    try {
                        $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hasAmortization);
                        $showBadge = !empty($decoded) && $decoded[0] == 1;
                    } catch (\Exception $e) {
                        $showBadge = ($hasAmortization == 1 || $hasAmortization == '1');
                    }
                }
            @endphp
            @if($showBadge)
                <span class="badge bg-warning text-dark ms-2">With Amortization Schedules</span>
            @endif
        </h6>
        <hr />

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="filter_status" class="form-select select2-single">
                            <option value="">All Statuses</option>
                            <option value="DRAFT">Draft</option>
                            <option value="ACTIVE">Active</option>
                            <option value="MATURED">Matured</option>
                            <option value="DISPOSED">Disposed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Instrument Type</label>
                        <select id="filter_instrument_type" class="form-select select2-single">
                            <option value="">All Types</option>
                            <option value="T_BILL">T-Bill</option>
                            <option value="T_BOND">T-Bond</option>
                            <option value="FIXED_DEPOSIT">Fixed Deposit</option>
                            <option value="CORP_BOND">Corporate Bond</option>
                            <option value="EQUITY">Equity</option>
                            <option value="MMF">Money Market Fund</option>
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

        <!-- Investments Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="investmentsTable" class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Instrument Code</th>
                                <th>Type</th>
                                <th>Issuer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Maturity Date</th>
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
    const table = $('#investmentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("investments.master.data") }}',
            data: function(d) {
                d.status = $('#filter_status').val();
                d.instrument_type = $('#filter_instrument_type').val();
                @php
                    $hasAmortization = request('has_amortization');
                    if ($hasAmortization) {
                        try {
                            $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hasAmortization);
                            if (!empty($decoded) && $decoded[0] == 1) {
                                echo "d.has_amortization = '" . $hasAmortization . "';";
                            }
                        } catch (\Exception $e) {
                            if ($hasAmortization == 1 || $hasAmortization == '1') {
                                echo "d.has_amortization = '" . \Vinkla\Hashids\Facades\Hashids::encode(1) . "';";
                            }
                        }
                    }
                @endphp
            },
            error: function(xhr, status, error) {
                console.error('DataTables error:', error);
                console.error('Response:', xhr.responseText);
            }
        },
        columns: [
            { data: 'instrument_code', name: 'instrument_code' },
            { data: 'instrument_type', name: 'instrument_type' },
            { data: 'issuer', name: 'issuer' },
            { data: 'carrying_amount', name: 'carrying_amount', className: 'text-end' },
            { data: 'status', name: 'status', orderable: false },
            { data: 'maturity_date', name: 'maturity_date' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[0, 'asc']],
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
        $('#filter_status').val('').trigger('change');
        $('#filter_instrument_type').val('').trigger('change');
        table.ajax.reload();
    });
});
</script>
@endpush
