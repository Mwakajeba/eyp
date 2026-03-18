@extends('layouts.main')

@section('title', 'IFRS 5 Movement Schedule')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Held for Sale', 'url' => route('assets.hfs.requests.index'), 'icon' => 'bx bx-transfer'],
            ['label' => 'Movement Schedule', 'url' => '#', 'icon' => 'bx bx-table']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-table me-2"></i>IFRS 5 Movement Schedule</h5>
                    <div class="text-muted">Movement in Assets Held for Sale</div>
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
                <!-- Filters -->
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small">Period Start</label>
                        <input type="date" id="period_start" class="form-control form-control-sm" 
                            value="{{ $periodStart }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Period End</label>
                        <input type="date" id="period_end" class="form-control form-control-sm" 
                            value="{{ $periodEnd }}">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-primary" onclick="loadSchedule()">
                            <i class="bx bx-search me-1"></i>Load
                        </button>
                    </div>
                </div>

                <!-- Movement Schedule Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="movement-schedule-table">
                        <thead class="table-light">
                            <tr>
                                <th>Asset/Disposal Group</th>
                                <th class="text-end">Carrying at Start</th>
                                <th class="text-end">Classified During Period</th>
                                <th class="text-end">Impairments</th>
                                <th class="text-end">Reversals</th>
                                <th class="text-end">Transfers</th>
                                <th class="text-end">Disposals</th>
                                <th class="text-end">Carrying at End</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($schedule as $item)
                            <tr>
                                <td>{{ $item['asset_group'] }}</td>
                                <td class="text-end">{{ number_format($item['carrying_at_start'], 2) }}</td>
                                <td class="text-end">{{ number_format($item['classified_during_period'], 2) }}</td>
                                <td class="text-end text-danger">{{ number_format($item['impairments'], 2) }}</td>
                                <td class="text-end text-success">{{ number_format($item['reversals'], 2) }}</td>
                                <td class="text-end">-</td>
                                <td class="text-end">{{ number_format($item['disposals'], 2) }}</td>
                                <td class="text-end"><strong>{{ number_format($item['carrying_at_end'], 2) }}</strong></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No data available for the selected period</td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-primary">
                            <tr>
                                <th>Total</th>
                                <th class="text-end">{{ number_format(collect($schedule)->sum('carrying_at_start'), 2) }}</th>
                                <th class="text-end">{{ number_format(collect($schedule)->sum('classified_during_period'), 2) }}</th>
                                <th class="text-end">{{ number_format(collect($schedule)->sum('impairments'), 2) }}</th>
                                <th class="text-end">{{ number_format(collect($schedule)->sum('reversals'), 2) }}</th>
                                <th class="text-end">-</th>
                                <th class="text-end">{{ number_format(collect($schedule)->sum('disposals'), 2) }}</th>
                                <th class="text-end"><strong>{{ number_format(collect($schedule)->sum('carrying_at_end'), 2) }}</strong></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function loadSchedule() {
    const periodStart = $('#period_start').val();
    const periodEnd = $('#period_end').val();
    window.location.href = '{{ route("assets.hfs.reports.movement-schedule") }}?period_start=' + periodStart + '&period_end=' + periodEnd;
}

function exportToExcel() {
    // Implement Excel export
    window.location.href = '{{ route("assets.hfs.reports.movement-schedule") }}?export=excel&period_start=' + $('#period_start').val() + '&period_end=' + $('#period_end').val();
}
</script>
@endpush

