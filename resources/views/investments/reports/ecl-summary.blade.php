@extends('layouts.main')

@section('title', 'ECL Summary Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Investment Reports', 'url' => route('investments.reports.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'ECL Summary', 'url' => '#', 'icon' => 'bx bx-shield']
        ]" />
        <h6 class="mb-0 text-uppercase">IFRS 9 EXPECTED CREDIT LOSS (ECL) SUMMARY REPORT</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary bg-gradient text-white">
                        <h5 class="mb-0">
                            <i class="bx bx-shield me-2"></i>ECL Summary by Stage
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Stage 1 - Performing</h6>
                                        <h4 class="text-primary mb-1">{{ number_format($byStage[1]['count'] ?? 0) }}</h4>
                                        <p class="mb-0 small">
                                            ECL: <strong>{{ number_format($byStage[1]['total_ecl'] ?? 0, 2) }}</strong><br>
                                            EAD: <strong>{{ number_format($byStage[1]['total_ead'] ?? 0, 2) }}</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Stage 2 - Underperforming</h6>
                                        <h4 class="text-warning mb-1">{{ number_format($byStage[2]['count'] ?? 0) }}</h4>
                                        <p class="mb-0 small">
                                            ECL: <strong>{{ number_format($byStage[2]['total_ecl'] ?? 0, 2) }}</strong><br>
                                            EAD: <strong>{{ number_format($byStage[2]['total_ead'] ?? 0, 2) }}</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Stage 3 - Non-performing</h6>
                                        <h4 class="text-danger mb-1">{{ number_format($byStage[3]['count'] ?? 0) }}</h4>
                                        <p class="mb-0 small">
                                            ECL: <strong>{{ number_format($byStage[3]['total_ecl'] ?? 0, 2) }}</strong><br>
                                            EAD: <strong>{{ number_format($byStage[3]['total_ead'] ?? 0, 2) }}</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Total</h6>
                                        <h4 class="text-info mb-1">{{ number_format(($byStage[1]['count'] ?? 0) + ($byStage[2]['count'] ?? 0) + ($byStage[3]['count'] ?? 0)) }}</h4>
                                        <p class="mb-0 small">
                                            Total ECL: <strong>{{ number_format($totalEcl ?? 0, 2) }}</strong><br>
                                            Total EAD: <strong>{{ number_format($totalEad ?? 0, 2) }}</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Stage</th>
                                        <th>Investment Count</th>
                                        <th class="text-end">Total ECL</th>
                                        <th class="text-end">Total EAD</th>
                                        <th class="text-end">ECL Ratio (%)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach([1 => 'Stage 1 - Performing', 2 => 'Stage 2 - Underperforming', 3 => 'Stage 3 - Non-performing'] as $stage => $label)
                                    <tr>
                                        <td>
                                            <span class="badge bg-{{ $stage == 1 ? 'primary' : ($stage == 2 ? 'warning' : 'danger') }}">
                                                {{ $label }}
                                            </span>
                                        </td>
                                        <td>{{ number_format($byStage[$stage]['count'] ?? 0) }}</td>
                                        <td class="text-end">{{ number_format($byStage[$stage]['total_ecl'] ?? 0, 2) }}</td>
                                        <td class="text-end">{{ number_format($byStage[$stage]['total_ead'] ?? 0, 2) }}</td>
                                        <td class="text-end">
                                            @php
                                                $ead = $byStage[$stage]['total_ead'] ?? 0;
                                                $ecl = $byStage[$stage]['total_ecl'] ?? 0;
                                                $ratio = $ead > 0 ? ($ecl / $ead) * 100 : 0;
                                            @endphp
                                            {{ number_format($ratio, 4) }}%
                                        </td>
                                    </tr>
                                    @endforeach
                                    <tr class="table-info fw-bold">
                                        <td>Total</td>
                                        <td>{{ number_format(($byStage[1]['count'] ?? 0) + ($byStage[2]['count'] ?? 0) + ($byStage[3]['count'] ?? 0)) }}</td>
                                        <td class="text-end">{{ number_format($totalEcl ?? 0, 2) }}</td>
                                        <td class="text-end">{{ number_format($totalEad ?? 0, 2) }}</td>
                                        <td class="text-end">
                                            @php
                                                $totalRatio = $totalEad > 0 ? ($totalEcl / $totalEad) * 100 : 0;
                                            @endphp
                                            {{ number_format($totalRatio, 4) }}%
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <a href="{{ route('investments.reports.ecl.detail') }}" class="btn btn-primary">
                                <i class="bx bx-list-ul me-1"></i> View Detailed Report
                            </a>
                            <a href="{{ route('investments.reports.ecl.trend') }}" class="btn btn-info">
                                <i class="bx bx-line-chart me-1"></i> View Trend Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

