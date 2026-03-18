@extends('layouts.main')

@section('title', 'Intangible Asset Categories')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Intangible Assets', 'url' => route('assets.intangible.index'), 'icon' => 'bx bx-brain'],
            ['label' => 'Categories', 'url' => '#', 'icon' => 'bx bx-category-alt']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-category-alt me-2"></i>Intangible Asset Categories</h5>
                    <div class="text-muted small">Define types of intangible assets and map their GL accounts (cost, amortisation, impairment, disposal).</div>
                </div>
                <div>
                    <a href="{{ route('assets.intangible.categories.create') }}" class="btn btn-primary btn-sm">
                        <i class="bx bx-plus me-1"></i>New Category
                    </a>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0" id="intangible-categories-table" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Goodwill</th>
                                <th>Indefinite Life</th>
                                <th>Cost Account</th>
                                <th>Accum. Amort Account</th>
                                <th>Accum. Impairment Account</th>
                                <th>Amort Expense Account</th>
                                <th>Impairment Loss Account</th>
                                <th>Gain/Loss Account</th>
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
    const table = $('#intangible-categories-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("assets.intangible.categories.data") }}',
            error: function(xhr) {
                console.error('Intangible categories DT error:', xhr.status, xhr.responseText);
                let msg = 'Failed to load intangible categories. ' + (xhr.status ? 'HTTP ' + xhr.status : '');
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
        order: [[1, 'asc']],
        columns: [
            { data: 'code', name: 'code' },
            { data: 'name', name: 'name' },
            { data: 'type_label', name: 'type' },
            { data: 'is_goodwill_label', name: 'is_goodwill', orderable: false, searchable: false },
            { data: 'is_indefinite_label', name: 'is_indefinite_life', orderable: false, searchable: false },
            { data: 'cost_account_name', name: 'cost_account_name' },
            { data: 'accumulated_amortisation_account_name', name: 'accumulated_amortisation_account_name' },
            { data: 'accumulated_impairment_account_name', name: 'accumulated_impairment_account_name' },
            { data: 'amortisation_expense_account_name', name: 'amortisation_expense_account_name' },
            { data: 'impairment_loss_account_name', name: 'impairment_loss_account_name' },
            { data: 'disposal_gain_loss_account_name', name: 'disposal_gain_loss_account_name' },
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<i class="bx bx-loader bx-spin"></i> Loading...',
            emptyTable: '<div class="text-center text-muted py-4"><i class="bx bx-info-circle me-2"></i>No intangible categories found</div>',
            zeroRecords: '<div class="text-center text-muted py-4"><i class="bx bx-info-circle me-2"></i>No matching intangible categories found</div>'
        }
    });
});
</script>
@endpush
