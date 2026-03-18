@extends('layouts.main')

@section('title', 'Depreciation History')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Assets', 'url' => route('assets.index'), 'icon' => 'bx bx-cabinet'],
            ['label' => 'Registry', 'url' => route('assets.registry.index'), 'icon' => 'bx bx-list-ul'],
            ['label' => $asset->name, 'url' => route('assets.registry.show', $encodedId), 'icon' => 'bx bx-show'],
            ['label' => 'Depreciation History', 'url' => '#', 'icon' => 'bx bx-history']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Depreciation History - {{ $asset->name }}</h4>
                    <div class="page-title-right">
                        <a href="{{ route('assets.registry.show', $encodedId) }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Asset
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asset Summary -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-muted small">Asset Code</div>
                                <div class="fw-semibold">{{ $asset->code }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted small">Original Cost</div>
                                <div class="fw-semibold">TZS {{ number_format($asset->purchase_cost, 2) }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted small">Current NBV</div>
                                <div class="fw-semibold text-primary">
                                    @php
                                        $currentNBV = \App\Models\Assets\AssetDepreciation::getCurrentBookValue($asset->id, null, $asset->company_id);
                                        $currentNBV = $currentNBV ?? $asset->current_nbv ?? $asset->purchase_cost;
                                    @endphp
                                    TZS {{ number_format($currentNBV, 2) }}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted small">Accumulated Depreciation</div>
                                <div class="fw-semibold text-danger">
                                    @php
                                        $accumDepr = \App\Models\Assets\AssetDepreciation::getAccumulatedDepreciation($asset->id, null, $asset->company_id);
                                        $accumDepr = $accumDepr > 0 ? $accumDepr : ($asset->purchase_cost - $currentNBV);
                                    @endphp
                                    TZS {{ number_format($accumDepr, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Summary Statistics -->
        @if(isset($summary) && ($summary['opening_balances'] > 0 || $summary['regular_depreciations'] > 0))
        <div class="row">
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <div class="text-muted small mb-1">Total Depreciation</div>
                        <div class="fs-4 fw-bold text-danger">
                            TZS {{ number_format($summary['total_depreciation'], 2) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <div class="text-muted small mb-1">Opening Balances</div>
                        <div class="fs-4 fw-bold text-info">
                            {{ $summary['opening_balances'] }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <div class="text-muted small mb-1">Regular Depreciations</div>
                        <div class="fs-4 fw-bold text-success">
                            {{ $summary['regular_depreciations'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <!-- Depreciation History Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-history me-2"></i>Depreciation History
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="depreciation-history-table" class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th class="text-end">Book Value Before</th>
                                        <th class="text-end">Depreciation Amount</th>
                                        <th class="text-end">Accumulated Depreciation</th>
                                        <th class="text-end">Book Value After</th>
                                        <th>GL Posted</th>
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


    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(function(){
    const table = $('#depreciation-history-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('assets.registry.depreciation-history-data', $encodedId) }}',
            type: 'GET'
        },
        columns: [
            { data: 'depreciation_date', name: 'depreciation_date' },
            { data: 'type', name: 'type', orderable: false },
            { data: 'description', name: 'description', orderable: false },
            { data: 'book_value_before', name: 'book_value_before', className: 'text-end' },
            { data: 'depreciation_amount', name: 'depreciation_amount', className: 'text-end', orderable: false },
            { data: 'accumulated_depreciation', name: 'accumulated_depreciation', className: 'text-end' },
            { data: 'book_value_after', name: 'book_value_after', className: 'text-end' },
            { data: 'gl_posted', name: 'gl_posted', className: 'text-center', orderable: false }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        language: {
            emptyTable: '<div class="alert alert-info m-0"><i class="bx bx-info-circle me-1"></i>No depreciation history found for this asset. Depreciation entries will appear here once they are recorded.</div>'
        }
    });
});
</script>
@endpush

