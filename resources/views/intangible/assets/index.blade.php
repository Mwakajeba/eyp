@extends('layouts.main')

@section('title', 'Intangible Assets')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Intangible Assets', 'url' => '#', 'icon' => 'bx bx-brain']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-brain me-2"></i>Intangible Assets Register</h5>
                    <div class="text-muted">Track cost, amortisation, impairment and NBV for all intangible assets</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('assets.intangible.categories.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bx bx-category-alt me-1"></i>Categories
                    </a>
                    <a href="{{ route('assets.intangible.create') }}" class="btn btn-primary btn-sm">
                        <i class="bx bx-plus me-1"></i>New Intangible Asset
                    </a>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0" id="intangible-assets-table" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th class="text-end">Cost</th>
                                <th class="text-end">Accum. Amortisation</th>
                                <th class="text-end">Accum. Impairment</th>
                                <th class="text-end">NBV</th>
                                <th>Useful Life (months)</th>
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
    const table = $('#intangible-assets-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("assets.intangible.data") }}',
            error: function(xhr) {
                console.error('Intangible assets DT error:', xhr.status, xhr.responseText);
                let msg = 'Failed to load intangible assets. ' + (xhr.status ? 'HTTP ' + xhr.status : '');
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
            { data: 'category_name', name: 'category_name' },
            { data: 'cost', name: 'cost', className: 'text-end' },
            { data: 'accumulated_amortisation', name: 'accumulated_amortisation', className: 'text-end' },
            { data: 'accumulated_impairment', name: 'accumulated_impairment', className: 'text-end' },
            { data: 'nbv', name: 'nbv', className: 'text-end' },
            { data: 'useful_life_display', name: 'useful_life_display' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<i class="bx bx-loader bx-spin"></i> Loading...',
            emptyTable: '<div class="text-center text-muted py-4"><i class="bx bx-info-circle me-2"></i>No intangible assets found</div>',
            zeroRecords: '<div class="text-center text-muted py-4"><i class="bx bx-info-circle me-2"></i>No matching intangible assets found</div>'
        }
    });
});
</script>
@endpush


