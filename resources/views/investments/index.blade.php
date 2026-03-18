@extends('layouts.main')

@section('title', 'Investment Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Investment Management', 'url' => '#', 'icon' => 'bx bx-trending-up']
        ]" />
        <h6 class="mb-0 text-uppercase">INVESTMENT MANAGEMENT</h6>
        <hr />

        <!-- Investment Statistics -->
        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="card border-top border-0 border-4 border-primary">
                    <div class="card-body p-5">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-trending-up me-1 font-22 text-primary"></i></div>
                            <h5 class="mb-0 text-primary">Investment Statistics</h5>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="card radius-10 bg-primary">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Total Investments</p>
                                                <h4 class="text-white">{{ number_format($totalInvestments ?? 0) }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-package"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card radius-10 bg-success">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Active Investments</p>
                                                <h4 class="text-white">{{ number_format($activeInvestments ?? 0) }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-check-circle"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card radius-10 bg-warning">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Pending Proposals</p>
                                                <h4 class="text-white">{{ number_format($pendingProposals ?? 0) }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-file"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card radius-10 bg-info">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Portfolio Value</p>
                                                <h4 class="text-white">{{ number_format($totalPortfolioValue ?? 0, 2) }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-dollar"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card radius-10 bg-danger">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Matured</p>
                                                <h4 class="text-white">{{ number_format($maturedInvestments ?? 0) }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-time"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card radius-10 bg-dark">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Disposed</p>
                                                <h4 class="text-white">{{ number_format($disposedInvestments ?? 0) }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-archive"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card radius-10 bg-primary">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">This Month</p>
                                                <h4 class="text-white">{{ number_format($thisMonthInvestments ?? 0) }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-calendar"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card radius-10 bg-warning">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Pending Settlements</p>
                                                <h4 class="text-white">{{ number_format($pendingSettlements ?? 0) }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-transfer"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card border-top border-0 border-4 border-success">
                    <div class="card-body p-5">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-pie-chart-alt-2 me-1 font-22 text-success"></i></div>
                            <h5 class="mb-0 text-success">Portfolio Overview</h5>
                        </div>
                        <hr>
                        <div class="d-grid gap-2">
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <h4 class="text-success mb-1">{{ number_format($activeInvestments ?? 0) }}</h4>
                                    <small class="text-muted">Active</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-primary mb-1">{{ number_format($totalPortfolioValue ?? 0, 2) }}</h4>
                                    <small class="text-muted">Portfolio Value</small>
                                </div>
                            </div>
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <h4 class="text-warning mb-1">{{ number_format($maturedInvestments ?? 0) }}</h4>
                                    <small class="text-muted">Matured</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-danger mb-1">{{ number_format($pendingProposals ?? 0) }}</h4>
                                    <small class="text-muted">Pending</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
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

                        @if(isset($errors) && $errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            Please fix the following errors:
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        <div class="row">
                            <!-- Investment Proposals -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ $totalProposals ?? 0 }}
                                            <span class="visually-hidden">total proposals count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-file fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Investment Proposals</h5>
                                        <p class="card-text">Create and manage investment proposals for approval.</p>
                                        <a href="{{ route('investments.proposals.index') }}" class="btn btn-primary">
                                            <i class="bx bx-list-ul me-1"></i> Manage Proposals
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Trade Capture -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ $activeInvestments ?? 0 }}
                                            <span class="visually-hidden">active investments count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-transfer fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Trade Capture</h5>
                                        <p class="card-text">Capture investment trades and settlements.</p>
                                        <a href="{{ route('investments.trades.create') }}" class="btn btn-success">
                                            <i class="bx bx-plus me-1"></i> New Trade
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Portfolio Summary -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ number_format($totalPortfolioValue ?? 0, 2) }}
                                            <span class="visually-hidden">portfolio value</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-pie-chart-alt-2 fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Portfolio Summary</h5>
                                        <p class="card-text">View portfolio overview, holdings, and investment details.</p>
                                        <a href="{{ route('investments.master.index') }}" class="btn btn-info">
                                            <i class="bx bx-bar-chart me-1"></i> View Portfolio
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Amortization Schedule -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                            {{ $investmentsWithAmortization ?? 0 }}
                                            <span class="visually-hidden">investments with amortization count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-calendar fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Amortization Schedule</h5>
                                        <p class="card-text">
                                            Recompute EIR, Post Accrual for Period, Post Coupon Payment, Re-amortise.
                                        </p>
                                        <a href="{{ route('investments.master.index', ['has_amortization' => \Vinkla\Hashids\Facades\Hashids::encode(1)]) }}" class="btn btn-warning">
                                            <i class="bx bx-calendar-check me-1"></i> Manage Amortization
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Valuation & Revaluation -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-purple">
                                            {{ $pendingValuations ?? 0 }}
                                            <span class="visually-hidden">pending valuations count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-line-chart fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">Valuation & Revaluation</h5>
                                        <p class="card-text">Perform fair value valuations and revaluations.</p>
                                        <a href="{{ route('investments.valuations.index') }}" class="btn btn-purple">
                                            <i class="bx bx-trending-up me-1"></i> Manage Valuations
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- ECL & Impairment -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-shield fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">ECL & Impairment</h5>
                                        <p class="card-text">Calculate Expected Credit Loss (IFRS 9) and impairments.</p>
                                        <a href="{{ route('investments.ecl.index') }}" class="btn btn-danger">
                                            <i class="bx bx-calculator me-1"></i> Calculate ECL
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Coupon Payments -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-money fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Coupon Payments</h5>
                                        <p class="card-text">Record and manage coupon payments and interest receipts.</p>
                                        <a href="{{ route('investments.master.index') }}" class="btn btn-secondary">
                                            <i class="bx bx-receipt me-1"></i> Manage Coupons
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Matured Investments -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ $maturedInvestments ?? 0 }}
                                            <span class="visually-hidden">matured investments count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-time fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Matured Investments</h5>
                                        <p class="card-text">View investments that have reached maturity.</p>
                                        <a href="{{ route('investments.master.index', ['status' => 'REDEEMED']) }}" class="btn btn-info">
                                            <i class="bx bx-list-ul me-1"></i> View Matured
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Disposed Investments -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-dark position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark">
                                            {{ $disposedInvestments ?? 0 }}
                                            <span class="visually-hidden">disposed investments count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-archive fs-1 text-dark"></i>
                                        </div>
                                        <h5 class="card-title">Disposed Investments</h5>
                                        <p class="card-text">View investments that have been disposed or sold.</p>
                                        <a href="{{ route('investments.master.index', ['status' => 'DISPOSED']) }}" class="btn btn-dark">
                                            <i class="bx bx-list-ul me-1"></i> View Disposed
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Implementation Status Notice -->
                        <div class="alert alert-info mt-4">
                            <h6 class="alert-heading"><i class="bx bx-info-circle me-2"></i>Implementation Status</h6>
                            <p class="mb-0">
                                The Investment Management Module is being implemented in phases. 
                                <strong>Phase 1</strong> (Foundation & Core Data Model) is currently in progress.
                                Features will be enabled as each phase is completed.
                            </p>
                            <hr>
                            <p class="mb-0">
                                <small>
                                    <strong>Phase 1:</strong> Proposals & Approvals | 
                                    <strong>Phase 2:</strong> Trade Capture & Settlement | 
                                    <strong>Phase 3:</strong> EIR & Amortization | 
                                    <strong>Phase 4:</strong> Valuation & Revaluation | 
                                    <strong>Phase 5:</strong> ECL Engine & Reporting
                                </small>
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

    .badge {
        font-size: 0.75rem;
        padding: 0.5em 0.75em;
    }

    .fs-1 {
        font-size: 3rem !important;
    }

    /* Notification badge positioning */
    .position-relative .badge {
        z-index: 10;
        font-size: 0.7rem;
        min-width: 1.5rem;
        height: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
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

