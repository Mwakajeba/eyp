@extends('layouts.main')

@section('title', 'ECL Detail Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Investment Reports', 'url' => route('investments.reports.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'ECL Detail', 'url' => '#', 'icon' => 'bx bx-shield']
        ]" />
        <h6 class="mb-0 text-uppercase">IFRS 9 EXPECTED CREDIT LOSS (ECL) DETAIL REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('investments.reports.ecl.detail') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Stage</label>
                        <select name="stage" class="form-select select2-single">
                            <option value="">All Stages</option>
                            <option value="1" {{ request('stage') == '1' ? 'selected' : '' }}>Stage 1 - Performing</option>
                            <option value="2" {{ request('stage') == '2' ? 'selected' : '' }}>Stage 2 - Underperforming</option>
                            <option value="3" {{ request('stage') == '3' ? 'selected' : '' }}>Stage 3 - Non-performing</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Instrument Type</label>
                        <select name="instrument_type" class="form-select select2-single">
                            <option value="">All Types</option>
                            <option value="T_BOND" {{ request('instrument_type') == 'T_BOND' ? 'selected' : '' }}>T-Bond</option>
                            <option value="T_BILL" {{ request('instrument_type') == 'T_BILL' ? 'selected' : '' }}>T-Bill</option>
                            <option value="FIXED_DEPOSIT" {{ request('instrument_type') == 'FIXED_DEPOSIT' ? 'selected' : '' }}>Fixed Deposit</option>
                            <option value="CORP_BOND" {{ request('instrument_type') == 'CORP_BOND' ? 'selected' : '' }}>Corporate Bond</option>
                            <option value="COMMERCIAL_PAPER" {{ request('instrument_type') == 'COMMERCIAL_PAPER' ? 'selected' : '' }}>Commercial Paper</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-filter me-1"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Table -->
        <div class="card">
            <div class="card-header bg-primary bg-gradient text-white">
                <h5 class="mb-0">
                    <i class="bx bx-table me-2"></i>ECL Details
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Investment</th>
                                <th>Type</th>
                                <th>Stage</th>
                                <th class="text-end">PD (%)</th>
                                <th class="text-end">LGD (%)</th>
                                <th class="text-end">EAD</th>
                                <th class="text-end">ECL Amount</th>
                                <th>Trade Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trades as $trade)
                            <tr>
                                <td>
                                    @if($trade->investment)
                                        <a href="{{ route('investments.master.show', \Vinkla\Hashids\Facades\Hashids::encode($trade->investment_id)) }}">
                                            {{ $trade->investment->instrument_code }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    @if($trade->investment)
                                        {{ str_replace('_', ' ', $trade->investment->instrument_type) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $trade->stage == 1 ? 'primary' : ($trade->stage == 2 ? 'warning' : 'danger') }}">
                                        Stage {{ $trade->stage ?? 1 }}
                                    </span>
                                </td>
                                <td class="text-end">{{ number_format($trade->pd ?? 0, 6) }}%</td>
                                <td class="text-end">{{ number_format($trade->lgd ?? 0, 6) }}%</td>
                                <td class="text-end">{{ number_format($trade->ead ?? 0, 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($trade->ecl_amount ?? 0, 2) }}</td>
                                <td>{{ $trade->trade_date->format('Y-m-d') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="bx bx-info-circle font-24 text-muted"></i>
                                    <p class="text-muted mt-2">No ECL data found</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($trades->count() > 0)
                        <tfoot class="table-info">
                            <tr>
                                <th colspan="6" class="text-end">Total ECL:</th>
                                <th class="text-end">{{ number_format($trades->sum('ecl_amount'), 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    {{ $trades->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
});
</script>
@endpush
@endsection

