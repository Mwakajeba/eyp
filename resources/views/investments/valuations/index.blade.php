@extends('layouts.main')

@section('title', 'Investment Valuations')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Investment Management', 'url' => route('investments.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Valuations', 'url' => '#', 'icon' => 'bx bx-line-chart']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">INVESTMENT VALUATIONS</h6>
            <a href="{{ route('investments.valuations.create') }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> New Valuation
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
                <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="filter_status" class="form-select select2-single">
                            <option value="">All Status</option>
                            <option value="DRAFT">Draft</option>
                            <option value="PENDING_APPROVAL">Pending Approval</option>
                            <option value="APPROVED">Approved</option>
                            <option value="POSTED">Posted</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Valuation Level</label>
                        <select id="filter_valuation_level" class="form-select select2-single">
                            <option value="">All Levels</option>
                            <option value="1">Level 1</option>
                            <option value="2">Level 2</option>
                            <option value="3">Level 3</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Investment</label>
                        <select id="filter_investment_id" class="form-select select2-single">
                            <option value="">All Investments</option>
                            @foreach(\App\Models\Investment\InvestmentMaster::where('company_id', Auth::user()->company_id)->whereIn('accounting_class', ['FVPL', 'FVOCI'])->get() as $inv)
                                <option value="{{ $inv->hash_id }}">{{ $inv->instrument_code }}</option>
                            @endforeach
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

        <!-- Valuations Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="valuationsTable" class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Valuation Date</th>
                                <th>Investment</th>
                                <th>Level</th>
                                <th>Fair Value</th>
                                <th>Gain/Loss</th>
                                <th>Status</th>
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
    // Initialize Select2
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select an option',
        allowClear: true
    });

    const table = $('#valuationsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("investments.valuations.data") }}',
            data: function(d) {
                d.status = $('#filter_status').val();
                d.valuation_level = $('#filter_valuation_level').val();
                d.investment_id = $('#filter_investment_id').val();
            },
            error: function(xhr, status, error) {
                console.error('DataTables error:', error);
                console.error('Response:', xhr.responseText);
            }
        },
        columns: [
            { data: 'valuation_date', name: 'valuation_date' },
            { data: 'investment', name: 'investment.instrument_code' },
            { data: 'valuation_level', name: 'valuation_level', orderable: false },
            { data: 'total_fair_value', name: 'total_fair_value', className: 'text-end' },
            { data: 'unrealized_gain_loss', name: 'unrealized_gain_loss', className: 'text-end', orderable: false },
            { data: 'status', name: 'status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[0, 'desc']],
        responsive: true,
        language: {
            processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
            emptyTable: '<div class="text-center p-4"><i class="bx bx-line-chart font-24 text-muted"></i><p class="text-muted mt-2">No valuations found.</p></div>'
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
        $('#filter_valuation_level').val('').trigger('change');
        $('#filter_investment_id').val('').trigger('change');
        table.ajax.reload();
    });
});
</script>
@endpush

