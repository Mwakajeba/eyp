@extends('layouts.main')

@section('title', 'Deferred Tax Schedule')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Assets', 'url' => route('assets.index'), 'icon' => 'bx bx-cabinet'],
            ['label' => 'Deferred Tax', 'url' => route('assets.deferred-tax.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Schedule', 'url' => '#', 'icon' => 'bx bx-table']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Deferred Tax Schedule</h4>
                    <div class="page-title-right">
                        <a href="{{ route('assets.deferred-tax.index') }}" class="btn btn-secondary">
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
                            <div class="col-md-4">
                                <label class="form-label">Tax Year</label>
                                <input type="number" id="tax_year" name="tax_year" class="form-control" value="{{ $taxYear }}" min="2000" max="2100">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" id="apply_filter" class="btn btn-primary w-100">
                                    <i class="bx bx-filter me-1"></i>Apply Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deferred Tax Schedule Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-table me-2"></i>Deferred Tax Entries
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="deferred-tax-table" class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Asset</th>
                                        <th class="text-end">Tax Base (WDV)</th>
                                        <th class="text-end">Accounting Carrying Amount (NBV)</th>
                                        <th class="text-end">Temporary Difference</th>
                                        <th class="text-end">Deferred Tax Liability</th>
                                        <th class="text-end">Deferred Tax Asset</th>
                                        <th class="text-end">Net Deferred Tax</th>
                                        <th class="text-end">Opening Balance</th>
                                        <th class="text-end">Movement</th>
                                        <th class="text-end">Closing Balance</th>
                                        <th class="text-center">Posted</th>
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
    // Initialize DataTable
    var table = $('#deferred-tax-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("assets.deferred-tax.schedule.data") }}',
            type: 'GET',
            data: function(d) {
                d.tax_year = $('#tax_year').val();
            }
        },
        columns: [
            { data: 'asset_name', name: 'asset.name', orderable: false },
            { data: 'tax_base_formatted', name: 'tax_base_carrying_amount', orderable: true, className: 'text-end' },
            { data: 'accounting_carrying_formatted', name: 'accounting_carrying_amount', orderable: true, className: 'text-end' },
            { data: 'temporary_difference_formatted', name: 'temporary_difference', orderable: true, className: 'text-end' },
            { data: 'deferred_tax_liability_formatted', name: 'deferred_tax_liability', orderable: true, className: 'text-end' },
            { data: 'deferred_tax_asset_formatted', name: 'deferred_tax_asset', orderable: true, className: 'text-end' },
            { data: 'net_deferred_tax_formatted', name: 'net_deferred_tax', orderable: true, className: 'text-end' },
            { data: 'opening_balance_formatted', name: 'opening_balance', orderable: true, className: 'text-end' },
            { data: 'movement_formatted', name: 'movement', orderable: true, className: 'text-end' },
            { data: 'closing_balance_formatted', name: 'closing_balance', orderable: true, className: 'text-end' },
            { data: 'is_posted_badge', name: 'is_posted', orderable: true, className: 'text-center' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        responsive: true,
        language: {
            processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
            emptyTable: '<div class="text-center p-4"><i class="bx bx-table font-24 text-muted"></i><p class="text-muted mt-2">No deferred tax entries found for the selected criteria.</p></div>'
        }
    });

    // Apply filter
    $('#apply_filter').on('click', function() {
        table.ajax.reload();
    });
});
</script>
@endpush

