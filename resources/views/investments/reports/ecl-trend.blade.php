@extends('layouts.main')

@section('title', 'ECL Trend Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Investment Reports', 'url' => route('investments.reports.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'ECL Trend', 'url' => '#', 'icon' => 'bx bx-line-chart']
        ]" />
        <h6 class="mb-0 text-uppercase">IFRS 9 EXPECTED CREDIT LOSS (ECL) TREND REPORT</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary bg-gradient text-white">
                        <h5 class="mb-0">
                            <i class="bx bx-line-chart me-2"></i>ECL Trend Analysis (Last 12 Months)
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($trendData->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Month</th>
                                        <th class="text-end">Investment Count</th>
                                        <th class="text-end">Total ECL</th>
                                        <th class="text-end">Total EAD</th>
                                        <th class="text-end">Avg PD (%)</th>
                                        <th class="text-end">Avg LGD (%)</th>
                                        <th class="text-end">ECL Ratio (%)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($trendData as $data)
                                    <tr>
                                        <td>
                                            <strong>{{ \Carbon\Carbon::createFromFormat('Y-m', $data->month)->format('M Y') }}</strong>
                                        </td>
                                        <td class="text-end">{{ number_format($data->investment_count) }}</td>
                                        <td class="text-end">{{ number_format($data->total_ecl, 2) }}</td>
                                        <td class="text-end">{{ number_format($data->total_ead, 2) }}</td>
                                        <td class="text-end">{{ number_format($data->avg_pd ?? 0, 4) }}%</td>
                                        <td class="text-end">{{ number_format($data->avg_lgd ?? 0, 4) }}%</td>
                                        <td class="text-end">
                                            @php
                                                $ratio = $data->total_ead > 0 ? ($data->total_ecl / $data->total_ead) * 100 : 0;
                                            @endphp
                                            {{ number_format($ratio, 4) }}%
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-4">
                            <i class="bx bx-info-circle font-24 text-muted"></i>
                            <p class="text-muted mt-2">No trend data available</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

