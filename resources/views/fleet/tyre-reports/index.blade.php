@extends('layouts.main')

@section('title', 'Tyre & Spare Reports - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Tyre & Spare Reports', 'url' => '#', 'icon' => 'bx bx-bar-chart-alt']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-secondary text-white border-0">
                <h5 class="mb-1"><i class="bx bx-bar-chart-alt me-2"></i>Tyre & Spare Reports</h5>
                <div class="text-white-50">Tyre abuse, cost per km, warranty recovery, and forensic audit reports for owners and auditors.</div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                        <div class="card border h-100">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bx bx-error-circle me-2 text-danger"></i>Tyre Abuse Report</h6>
                                <p class="card-text small text-muted">Tyres replaced before 50% of expected life, by driver and by truck.</p>
                                <span class="badge bg-light text-dark">Coming soon</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border h-100">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bx bx-trending-down me-2 text-warning"></i>Cost Leakage Report</h6>
                                <p class="card-text small text-muted">Tyre & spare costs per km; benchmark against fleet average.</p>
                                <span class="badge bg-light text-dark">Coming soon</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border h-100">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bx bx-shield-plus me-2 text-info"></i>Warranty Recovery Report</h6>
                                <p class="card-text small text-muted">Tyres under warranty but not claimed; value lost.</p>
                                <span class="badge bg-light text-dark">Coming soon</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border h-100">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bx bx-history me-2 text-secondary"></i>Audit & Forensic</h6>
                                <p class="card-text small text-muted">Full tyre lifecycle history, approvals, photos, mileage justification.</p>
                                <span class="badge bg-light text-dark">Coming soon</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
