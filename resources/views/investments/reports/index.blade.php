@extends('layouts.main')

@section('title', 'Investment Reports')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Investment Reports', 'url' => '#', 'icon' => 'bx bx-trending-up']
        ]" />
        <h6 class="mb-0 text-uppercase">INVESTMENT REPORTS</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bx bx-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        <div class="row">
                            <!-- ECL Reports -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-shield fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">ECL Summary Report</h5>
                                        <p class="card-text">
                                            View Expected Credit Loss summary by stage (Stage 1, 2, 3) with total ECL and EAD breakdown.
                                        </p>
                                        <a href="{{ route('investments.reports.ecl.summary') }}" class="btn btn-danger">
                                            <i class="bx bx-shield me-1"></i> View Summary
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-list-ul fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">ECL Detail Report</h5>
                                        <p class="card-text">
                                            Detailed ECL report by investment with PD, LGD, EAD, and ECL amounts. Filterable by stage, type, and date.
                                        </p>
                                        <a href="{{ route('investments.reports.ecl.detail') }}" class="btn btn-warning">
                                            <i class="bx bx-list-ul me-1"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-line-chart fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">ECL Trend Report</h5>
                                        <p class="card-text">
                                            Monthly ECL trend analysis showing ECL movement over the last 12 months with average PD and LGD.
                                        </p>
                                        <a href="{{ route('investments.reports.ecl.trend') }}" class="btn btn-info">
                                            <i class="bx bx-line-chart me-1"></i> View Trend
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- Portfolio Summary Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-pie-chart-alt-2 fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Portfolio Summary</h5>
                                        <p class="card-text">View portfolio overview, holdings, and valuations.</p>
                                        <a href="#" class="btn btn-primary" onclick="alert('Coming in Phase 5'); return false;">
                                            <i class="bx bx-bar-chart me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Maturity Ladder Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-calendar fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Maturity Ladder</h5>
                                        <p class="card-text">View investments by maturity buckets (0-3m, 3-6m, 6-12m, >12m).</p>
                                        <a href="#" class="btn btn-success" onclick="alert('Coming in Phase 5'); return false;">
                                            <i class="bx bx-list-ul me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- IFRS 9 Classification Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-category fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">IFRS 9 Classification</h5>
                                        <p class="card-text">List investments by accounting classification (Amortized Cost, FVOCI, FVPL).</p>
                                        <a href="#" class="btn btn-info" onclick="alert('Coming in Phase 5'); return false;">
                                            <i class="bx bx-file me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Amortization & Accrual Schedule -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-calendar-check fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Amortization Schedule</h5>
                                        <p class="card-text">View EIR amortization schedules with journal references.</p>
                                        <a href="#" class="btn btn-warning" onclick="alert('Coming in Phase 5'); return false;">
                                            <i class="bx bx-table me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- ECL Movement Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-trending-up fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">ECL Movement</h5>
                                        <p class="card-text">Track ECL allowance movements: opening, new provisions, releases, write-offs.</p>
                                        <a href="#" class="btn btn-danger" onclick="alert('Coming in Phase 5'); return false;">
                                            <i class="bx bx-line-chart me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Fair Value Hierarchy Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-layer fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">Fair Value Hierarchy</h5>
                                        <p class="card-text">View instruments by Level 1/2/3 with valuation methods.</p>
                                        <a href="#" class="btn btn-purple" onclick="alert('Coming in Phase 5'); return false;">
                                            <i class="bx bx-list-ul me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Deferred Tax Reconciliation -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-calculator fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Deferred Tax Reconciliation</h5>
                                        <p class="card-text">Book vs tax values, temporary differences, deferred tax movements.</p>
                                        <a href="#" class="btn btn-secondary" onclick="alert('Coming in Phase 5'); return false;">
                                            <i class="bx bx-file me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Compliance & Limits Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-dark position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-shield fs-1 text-dark"></i>
                                        </div>
                                        <h5 class="card-title">Compliance & Limits</h5>
                                        <p class="card-text">Counterparty exposure vs policy limits and compliance checks.</p>
                                        <a href="#" class="btn btn-dark" onclick="alert('Coming in Phase 5'); return false;">
                                            <i class="bx bx-check-shield me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Audit Pack Export -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-archive fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Audit Pack</h5>
                                        <p class="card-text">Export comprehensive audit pack with all inputs, calculations, and journals.</p>
                                        <a href="#" class="btn btn-primary" onclick="alert('Coming in Phase 5'); return false;">
                                            <i class="bx bx-download me-1"></i> Export Audit Pack
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Implementation Status Notice -->
                        <div class="alert alert-info mt-4">
                            <h6 class="alert-heading"><i class="bx bx-info-circle me-2"></i>Implementation Status</h6>
                            <p class="mb-0">
                                Investment Reports will be available in <strong>Phase 5</strong> (ECL Engine & Advanced Features).
                                All reports will include export capabilities (PDF, Excel, CSV) and will be audit-ready.
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end page wrapper -->
<!--start overlay-->
<div class="overlay toggle-icon"></div>
<!--end overlay-->
<!--Start Back To Top Button--> <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
<!--End Back To Top Button-->
<footer class="page-footer">
    <p class="mb-0">Copyright © {{ date('Y') }}. All right reserved. -- By SAFCO FINTECH</p>
</footer>

@endsection

@push('styles')
<style>
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .fs-1 {
        font-size: 3rem !important;
    }

    .border-primary { border-color: #0d6efd !important; }
    .border-success { border-color: #198754 !important; }
    .border-warning { border-color: #ffc107 !important; }
    .border-info { border-color: #0dcaf0 !important; }
    .border-danger { border-color: #dc3545 !important; }
    .border-secondary { border-color: #6c757d !important; }
    .border-purple { border-color: #6f42c1 !important; }
    .border-dark { border-color: #212529 !important; }
    
    .text-purple { color: #6f42c1 !important; }
    .bg-purple { background-color: #6f42c1 !important; }
    .btn-purple { 
        background-color: #6f42c1; 
        border-color: #6f42c1; 
        color: white; 
    }
    .btn-purple:hover { 
        background-color: #5a32a3; 
        border-color: #5a32a3; 
        color: white; 
    }
</style>
@endpush

