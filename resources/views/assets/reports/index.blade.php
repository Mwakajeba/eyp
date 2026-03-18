@extends('layouts.main')

@section('title', 'Asset Reports')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Reports', 'url' => '#', 'icon' => 'bx bx-line-chart']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-cabinet me-2"></i>Asset Reports
                            </h4>
                        </div>
                        
                        <p class="text-muted mb-4">
                            Comprehensive fixed asset reporting tools for financial statements, audit compliance, 
                            tax reconciliation, and operational asset management.
                        </p>

                        <div class="row">
                            <!-- Fixed Asset Register -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-book fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Fixed Asset Register</h5>
                                        <p class="card-text">Master audit document - must reconcile to GL</p>
                                        <span class="badge bg-danger mb-2">Required by Auditors</span>
                                        <a href="{{ route('assets.reports.register') }}" class="btn btn-primary">
                                            <i class="bx bx-book me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Asset Movement Schedule -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-transfer-alt fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Asset Movement Schedule</h5>
                                        <p class="card-text">IFRS IAS 16 roll-forward reconciliation</p>
                                        <span class="badge bg-danger mb-2">IFRS Required</span>
                                        <a href="{{ route('assets.reports.movement-schedule') }}" class="btn btn-info">
                                            <i class="bx bx-transfer-alt me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- GL Reconciliation -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-shuffle fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">GL Reconciliation</h5>
                                        <p class="card-text">Subledger to General Ledger reconciliation</p>
                                        <span class="badge bg-warning mb-2">Month-End</span>
                                        <a href="{{ route('assets.reports.gl-reconciliation') }}" class="btn btn-success">
                                            <i class="bx bx-shuffle me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Depreciation Expense Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-calculator fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Depreciation Expense</h5>
                                        <p class="card-text">Period depreciation charge for P&L</p>
                                        <a href="{{ route('assets.reports.depreciation-expense') }}" class="btn btn-warning">
                                            <i class="bx bx-calculator me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Depreciation Schedule -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-calendar fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Depreciation Schedule</h5>
                                        <p class="card-text">Lifetime amortization per asset</p>
                                        <a href="{{ route('assets.reports.depreciation-schedule') }}" class="btn btn-primary">
                                            <i class="bx bx-calendar me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- TRA Tax Depreciation -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-receipt fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">TRA Tax Depreciation</h5>
                                        <p class="card-text">Tax authority depreciation schedule</p>
                                        <span class="badge bg-success mb-2">Active</span>
                                        <a href="{{ route('assets.tax-depreciation.reports.tra-schedule') }}" class="btn btn-danger">
                                            <i class="bx bx-receipt me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Book vs Tax Reconciliation -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-git-compare fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Book vs Tax Reconciliation</h5>
                                        <p class="card-text">Deferred tax calculation support</p>
                                        <span class="badge bg-success mb-2">Active</span>
                                        <a href="{{ route('assets.tax-depreciation.reports.book-tax-reconciliation') }}" class="btn btn-info">
                                            <i class="bx bx-git-compare me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Asset Additions -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-plus-circle fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Asset Additions</h5>
                                        <p class="card-text">New capitalized assets in period</p>
                                        <a href="{{ route('assets.reports.additions') }}" class="btn btn-success">
                                            <i class="bx bx-plus-circle me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Asset Disposals -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-trash fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Asset Disposals</h5>
                                        <p class="card-text">Sold, scrapped, or written-off assets</p>
                                        <a href="{{ route('assets.reports.disposals') }}" class="btn btn-danger">
                                            <i class="bx bx-trash me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Asset Transfers -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-move fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Asset Transfers</h5>
                                        <p class="card-text">Movement between departments/locations</p>
                                        <a href="{{ route('assets.reports.transfers') }}" class="btn btn-warning">
                                            <i class="bx bx-move me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Revaluation Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-trending-up fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Revaluation Report</h5>
                                        <p class="card-text">Assets under revaluation model (IFRS)</p>
                                        <a href="{{ route('assets.reports.revaluation') }}" class="btn btn-primary">
                                            <i class="bx bx-trending-up me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Impairment Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-trending-down fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Impairment Report (IAS 36)</h5>
                                        <p class="card-text">Impairment testing results</p>
                                        <a href="{{ route('assets.reports.impairment') }}" class="btn btn-danger">
                                            <i class="bx bx-trending-down me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Assets by Location -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-map fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Assets by Location</h5>
                                        <p class="card-text">Operational visibility by branch/location</p>
                                        <a href="{{ route('assets.reports.by-location') }}" class="btn btn-info">
                                            <i class="bx bx-map me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Assets by Category -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-category fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Assets by Category</h5>
                                        <p class="card-text">Management overview summary</p>
                                        <a href="{{ route('assets.reports.by-category') }}" class="btn btn-success">
                                            <i class="bx bx-category me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Physical Verification -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-search-alt fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Physical Verification</h5>
                                        <p class="card-text">Annual asset count and verification</p>
                                        <a href="{{ route('assets.reports.physical-verification') }}" class="btn btn-warning">
                                            <i class="bx bx-search-alt me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- CWIP Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-construction fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Capital Work in Progress</h5>
                                        <p class="card-text">Projects before capitalization</p>
                                        <a href="{{ route('assets.reports.cwip') }}" class="btn btn-primary">
                                            <i class="bx bx-construction me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Leasehold Improvements -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-home fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Leasehold Improvements</h5>
                                        <p class="card-text">Separate leasehold reporting</p>
                                        <a href="{{ route('assets.reports.leasehold-improvements') }}" class="btn btn-info">
                                            <i class="bx bx-home me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Statement Disclosure -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-file-blank fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Financial Statement Disclosure</h5>
                                        <p class="card-text">Ready-to-export IFRS format note</p>
                                        <a href="{{ route('assets.reports.fs-disclosure') }}" class="btn btn-success">
                                            <i class="bx bx-file-blank me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
