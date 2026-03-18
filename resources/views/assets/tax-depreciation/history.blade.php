@extends('layouts.main')

@section('title', 'Tax Depreciation History')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Assets', 'url' => route('assets.index'), 'icon' => 'bx bx-cabinet'],
            ['label' => 'Tax Depreciation', 'url' => route('assets.tax-depreciation.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'History', 'url' => '#', 'icon' => 'bx bx-history']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Tax Depreciation History</h4>
                    <div class="page-title-right">
                        <a href="{{ route('assets.tax-depreciation.index') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Month</label>
                                <input type="month" id="month_filter" name="month" class="form-control" value="{{ $selectedMonth }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Asset</label>
                                <select id="asset_filter" name="asset_id" class="form-select select2-single">
                                    <option value="">All Assets</option>
                                    @foreach(\App\Models\Assets\Asset::where('company_id', auth()->user()->company_id)->orderBy('name')->get() as $asset)
                                        <option value="{{ $asset->id }}">
                                            {{ $asset->code }} - {{ $asset->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tax Class</label>
                                <select id="tax_class_filter" name="tax_class_id" class="form-select select2-single">
                                    <option value="">All Classes</option>
                                    @foreach(\App\Models\Assets\TaxDepreciationClass::active()->orderBy('sort_order')->get() as $taxClass)
                                        <option value="{{ $taxClass->id }}">
                                            {{ $taxClass->class_code }} - {{ $taxClass->description }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" id="reset_filter" class="btn btn-outline-secondary w-100">
                                    <i class="bx bx-refresh me-1"></i>Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tax Depreciation History Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-history me-2"></i>Tax Depreciation Entries (TRA)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tax-depreciation-history-table" class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Asset</th>
                                        <th>Tax Class</th>
                                        <th class="text-end">Tax WDV Before</th>
                                        <th class="text-end">Tax Depreciation</th>
                                        <th class="text-end">Accumulated Tax Depreciation</th>
                                        <th class="text-end">Tax WDV After</th>
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
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Initialize DataTable
    var table = $('#tax-depreciation-history-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("assets.tax-depreciation.history.data") }}',
            type: 'GET',
            data: function(d) {
                d.month = $('#month_filter').val();
                d.asset_id = $('#asset_filter').val();
                d.tax_class_id = $('#tax_class_filter').val();
            }
        },
        columns: [
            { data: 'date_formatted', name: 'depreciation_date', orderable: true },
            { data: 'asset_name', name: 'asset.name', orderable: false },
            { data: 'tax_class', name: 'tax_class', orderable: false },
            { data: 'tax_wdv_before_formatted', name: 'tax_wdv_before', orderable: true, className: 'text-end' },
            { data: 'depreciation_amount_formatted', name: 'depreciation_amount', orderable: true, className: 'text-end' },
            { data: 'accumulated_tax_depreciation_formatted', name: 'accumulated_tax_depreciation', orderable: true, className: 'text-end' },
            { data: 'tax_wdv_after_formatted', name: 'tax_wdv_after', orderable: true, className: 'text-end' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        responsive: true,
        language: {
            processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
            emptyTable: '<div class="text-center p-4"><i class="bx bx-history font-24 text-muted"></i><p class="text-muted mt-2">No tax depreciation entries found for the selected criteria.</p></div>'
        }
    });

    // Filter on change
    $('#month_filter, #asset_filter, #tax_class_filter').on('change', function() {
        table.ajax.reload();
    });

    // Reset filter
    $('#reset_filter').on('click', function() {
        var currentMonth = new Date().toISOString().slice(0, 7);
        $('#month_filter').val(currentMonth);
        $('#asset_filter').val('').trigger('change');
        $('#tax_class_filter').val('').trigger('change');
        table.ajax.reload();
    });
});
</script>
@endpush

