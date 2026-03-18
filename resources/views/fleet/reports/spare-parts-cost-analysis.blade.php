@extends('layouts.main')

@section('title', 'Spare Parts Cost Analysis Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Spare Parts Cost Analysis', 'url' => '#', 'icon' => 'bx bx-line-chart']
        ]" />

        <h6 class="mb-0 text-uppercase">SPARE PARTS COST ANALYSIS REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.spare-parts-cost-analysis') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from', \Carbon\Carbon::now()->subMonths(12)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to', \Carbon\Carbon::now()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary"><i class="bx bx-search me-1"></i> Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end gap-2">
                    <form method="POST" action="{{ route('fleet.reports.spare-parts-cost-analysis.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-success"><i class="bx bx-file me-1"></i> Export Excel</button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.spare-parts-cost-analysis.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-danger"><i class="bx bx-file-blank me-1"></i> Export PDF</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary -->
        @if($costData->count() > 0)
        <div class="row">
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Cost (TZS)</h6>
                        <h4 class="text-primary">{{ number_format($totalCost ?? 0, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Replacements</h6>
                        <h4 class="text-info">{{ $totalReplacements ?? 0 }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Vehicles</h6>
                        <h4 class="text-success">{{ $costData->count() }}</h4>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Report Data -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Vehicle</th>
                                <th class="text-end">Total Cost (TZS)</th>
                                <th class="text-end">Replacement Count</th>
                                <th class="text-end">Avg Cost per Replacement (TZS)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($costData as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $row->vehicle }}</td>
                                <td class="text-end">{{ number_format($row->total_cost, 0) }}</td>
                                <td class="text-end">{{ $row->replacement_count }}</td>
                                <td class="text-end">{{ number_format($row->avg_cost_per_replacement, 0) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">No spare parts cost data available for the selected period.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($costData->count() > 0)
                        <tfoot>
                            <tr>
                                <th></th>
                                <th class="text-end">TOTAL:</th>
                                <th class="text-end">{{ number_format($costData->sum('total_cost'), 0) }}</th>
                                <th class="text-end">{{ $costData->sum('replacement_count') }}</th>
                                <th class="text-end">{{ number_format($costData->avg('avg_cost_per_replacement'), 0) }}</th>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
