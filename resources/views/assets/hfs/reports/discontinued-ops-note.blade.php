@extends('layouts.main')

@section('title', 'Discontinued Operations Note')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Held for Sale', 'url' => route('assets.hfs.requests.index'), 'icon' => 'bx bx-transfer'],
            ['label' => 'Discontinued Operations', 'url' => '#', 'icon' => 'bx bx-error-circle']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-0">
                <div>
                    <h5 class="mb-1"><i class="bx bx-error-circle me-2"></i>Discontinued Operations Note</h5>
                    <div class="text-muted">Profit/(Loss) from Discontinued Operations</div>
                </div>
                <div class="d-flex gap-2">
                    <div class="input-group" style="width: 200px;">
                        <select id="year-select" class="form-select form-select-sm select2-single">
                            @for($y = now()->year; $y >= now()->year - 5; $y--)
                                <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        <button type="button" class="btn btn-sm btn-primary" onclick="loadNote()">
                            <i class="bx bx-search"></i>
                        </button>
                    </div>
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
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th></th>
                                <th class="text-end">{{ $currentYear }}</th>
                                <th class="text-end">{{ $priorYear }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Revenue</strong></td>
                                <td class="text-end">{{ number_format($currentYearEffects['revenue'], 2) }}</td>
                                <td class="text-end">{{ number_format($priorYearEffects['revenue'], 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Expenses</strong></td>
                                <td class="text-end">({{ number_format($currentYearEffects['expenses'], 2) }})</td>
                                <td class="text-end">({{ number_format($priorYearEffects['expenses'], 2) }})</td>
                            </tr>
                            <tr>
                                <td><strong>Pre-tax Profit/(Loss)</strong></td>
                                <td class="text-end">{{ number_format($currentYearEffects['pre_tax_profit'], 2) }}</td>
                                <td class="text-end">{{ number_format($priorYearEffects['pre_tax_profit'], 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Tax</strong></td>
                                <td class="text-end">({{ number_format($currentYearEffects['tax'], 2) }})</td>
                                <td class="text-end">({{ number_format($priorYearEffects['tax'], 2) }})</td>
                            </tr>
                            <tr>
                                <td><strong>Post-tax Profit/(Loss)</strong></td>
                                <td class="text-end">{{ number_format($currentYearEffects['post_tax_profit'], 2) }}</td>
                                <td class="text-end">{{ number_format($priorYearEffects['post_tax_profit'], 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Gain/(Loss) on Disposal</strong></td>
                                <td class="text-end">{{ number_format($currentYearEffects['gain_loss_on_disposal'], 2) }}</td>
                                <td class="text-end">{{ number_format($priorYearEffects['gain_loss_on_disposal'], 2) }}</td>
                            </tr>
                            <tr class="table-primary">
                                <td><strong>Total Impact to Net Profit</strong></td>
                                <td class="text-end"><strong>{{ number_format($currentYearEffects['total_impact'], 2) }}</strong></td>
                                <td class="text-end"><strong>{{ number_format($priorYearEffects['total_impact'], 2) }}</strong></td>
                            </tr>
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
function loadNote() {
    const year = $('#year-select').val();
    window.location.href = '{{ route("assets.hfs.reports.discontinued-ops") }}?year=' + year;
}

function exportToExcel() {
    const year = $('#year-select').val();
    window.location.href = '{{ route("assets.hfs.reports.discontinued-ops") }}?export=excel&year=' + year;
}

$(document).ready(function() {
    // Initialize Select2 for year select
    $('#year-select').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
});
</script>
@endpush

