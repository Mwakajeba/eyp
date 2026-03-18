@extends('layouts.main')

@section('title', 'Profit & Loss Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => route('fleet.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Profit & Loss Report', 'url' => '#', 'icon' => 'bx bx-line-chart']
        ]" />

        <h6 class="mb-0 text-uppercase">PROFIT & LOSS REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('fleet.reports.profit-loss') }}" class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from', \Carbon\Carbon::today()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to', \Carbon\Carbon::today()->format('Y-m-d')) }}">
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
                    <form method="POST" action="{{ route('fleet.reports.profit-loss.export-excel') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </button>
                    </form>
                    <form method="POST" action="{{ route('fleet.reports.profit-loss.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bxs-file-pdf me-1"></i> Export PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Report Data -->
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="card-title">Revenue</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Total Revenue</strong></td>
                                <td class="text-end"><strong class="text-success">{{ number_format($summary['total_revenue'], 2) }}</strong></td>
                            </tr>
                        </table>

                        <h5 class="card-title mt-4">Expenses</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td>Maintenance Cost</td>
                                <td class="text-end text-danger">{{ number_format($summary['maintenance_cost'], 2) }}</td>
                            </tr>
                            <tr>
                                <td>Fuel Cost</td>
                                <td class="text-end text-danger">{{ number_format($summary['fuel_cost'], 2) }}</td>
                            </tr>
                            <tr>
                                <td>Trip Cost</td>
                                <td class="text-end text-danger">{{ number_format($summary['trip_cost'], 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Total Expenses</strong></td>
                                <td class="text-end"><strong class="text-danger">{{ number_format($summary['total_expenses'], 2) }}</strong></td>
                            </tr>
                        </table>

                        <hr>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Net Profit / Loss</strong></td>
                                <td class="text-end">
                                    <strong class="fs-5 {{ $summary['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($summary['net_profit'], 2) }}
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Profit Margin</strong></td>
                                <td class="text-end">
                                    <strong class="{{ $summary['profit_margin'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($summary['profit_margin'], 2) }}%
                                    </strong>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-{{ $summary['net_profit'] >= 0 ? 'success' : 'danger' }}">
                            <div class="card-body text-center p-5">
                                <h6 class="text-muted">Net Profit / Loss</h6>
                                <h1 class="{{ $summary['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($summary['net_profit'], 2) }}
                                </h1>
                                <p class="mb-0">Profit Margin: {{ number_format($summary['profit_margin'], 2) }}%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
