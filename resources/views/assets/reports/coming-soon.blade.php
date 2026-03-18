@extends('layouts.main')

@section('title', $reportName ?? 'Asset Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Assets', 'url' => route('assets.index'), 'icon' => 'bx bx-cabinet'],
            ['label' => 'Asset Reports', 'url' => route('assets.reports.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => $reportName ?? 'Report', 'url' => '#', 'icon' => 'bx bx-file']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bx bx-time-five" style="font-size: 80px; color: #999;"></i>
                        <h3 class="mt-3">{{ $reportName ?? 'This Report' }}</h3>
                        <p class="text-muted">This report is currently under development and will be available soon.</p>
                        <a href="{{ route('assets.reports.index') }}" class="btn btn-primary mt-3">
                            <i class="bx bx-arrow-back me-1"></i>Back to Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
