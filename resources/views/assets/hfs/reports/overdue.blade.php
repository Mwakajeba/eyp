@extends('layouts.main')

@section('title', 'Overdue HFS Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Held for Sale', 'url' => route('assets.hfs.requests.index'), 'icon' => 'bx bx-transfer'],
            ['label' => 'Overdue Report', 'url' => '#', 'icon' => 'bx bx-error-circle']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-error-circle me-2"></i>Overdue HFS Items (>12 months)</h5>
                    <div class="text-muted">HFS items exceeding 12-month timeline</div>
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
                    <table class="table table-bordered table-striped" id="overdue-table">
                        <thead class="table-light">
                            <tr>
                                <th>Request #</th>
                                <th>Assets</th>
                                <th>Intended Sale Date</th>
                                <th>Months Overdue</th>
                                <th>Extension Justification</th>
                                <th>Extension Approved By</th>
                                <th>Buyer</th>
                                <th>Marketing Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($overdueData as $item)
                            <tr>
                                <td>{{ $item['request_no'] }}</td>
                                <td>{{ $item['asset_codes'] }}</td>
                                <td>{{ \Carbon\Carbon::parse($item['intended_sale_date'])->format('d M Y') }}</td>
                                <td>
                                    <span class="badge bg-danger">{{ $item['months_overdue'] }} months</span>
                                </td>
                                <td>{{ $item['extension_justification'] ?? '-' }}</td>
                                <td>{{ $item['extension_approved_by'] }}</td>
                                <td>{{ $item['buyer_name'] ?? '-' }}</td>
                                <td>{{ $item['marketing_actions'] ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No overdue HFS items</td>
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
    window.location.href = '{{ route("assets.hfs.reports.overdue") }}?export=excel';
}
</script>
@endpush

