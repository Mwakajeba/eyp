@extends('layouts.main')

@section('title', 'HFS Valuation Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Held for Sale', 'url' => route('assets.hfs.requests.index'), 'icon' => 'bx bx-transfer'],
            ['label' => 'Valuation Details', 'url' => '#', 'icon' => 'bx bx-line-chart']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-line-chart me-2"></i>HFS Valuation Details</h5>
                    <div class="text-muted">Detailed valuation history for all HFS assets</div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i>Print
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="exportToExcel()">
                        <i class="bx bx-file me-1"></i>Export Excel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="valuation-details-table">
                        <thead class="table-light">
                            <tr>
                                <th>HFS Request #</th>
                                <th>Asset Codes</th>
                                <th>Date Classified</th>
                                <th class="text-end">Carrying at Classification</th>
                                <th>Valuation Date</th>
                                <th class="text-end">Fair Value</th>
                                <th class="text-end">Costs to Sell</th>
                                <th class="text-end">FV Less Costs</th>
                                <th>Impairment Posted</th>
                                <th>Journal Ref</th>
                                <th>Valuator</th>
                                <th>Report Ref</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($valuations as $valuation)
                            <tr>
                                <td>{{ $valuation['hfs_request_no'] }}</td>
                                <td>{{ $valuation['asset_codes'] }}</td>
                                <td>{{ $valuation['date_classified'] ? \Carbon\Carbon::parse($valuation['date_classified'])->format('d M Y') : '-' }}</td>
                                <td class="text-end">{{ number_format($valuation['carrying_at_classification'], 2) }}</td>
                                <td>{{ \Carbon\Carbon::parse($valuation['valuation_date'])->format('d M Y') }}</td>
                                <td class="text-end">{{ number_format($valuation['fair_value'], 2) }}</td>
                                <td class="text-end">{{ number_format($valuation['costs_to_sell'], 2) }}</td>
                                <td class="text-end">{{ number_format($valuation['fv_less_costs'], 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $valuation['impairment_posted'] == 'Yes' ? 'danger' : 'success' }}">
                                        {{ $valuation['impairment_posted'] }}
                                    </span>
                                </td>
                                <td>{{ $valuation['journal_ref'] }}</td>
                                <td>{{ $valuation['valuator'] ?? '-' }}</td>
                                <td>{{ $valuation['report_ref'] ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted">No valuation data available</td>
                            </tr>
                            @endforelse
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
function exportToExcel() {
    window.location.href = '{{ route("assets.hfs.reports.valuation-details") }}?export=excel';
}
</script>
@endpush

