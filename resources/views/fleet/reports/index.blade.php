@extends('layouts.main')

@section('title', 'Fleet Reports')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Fleet Reports', 'url' => '#', 'icon' => 'bx bx-file']
        ]" />

        <h6 class="mb-0 text-uppercase">FLEET REPORTS</h6>
        <hr />

        <!-- Fleet Reports Operations -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-file me-1 font-22 text-primary"></i></div>
                            <h5 class="mb-0 text-primary">Fleet Reports & Analytics</h5>
                        </div>
                        <hr>
                        <div class="row">
                            {{-- Report #1: Trip Revenue Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-money fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Trip Revenue Report</h5>
                                        <p class="card-text">Revenue generated from trips with breakdown by date, route, and vehicle</p>
                                        <a href="{{ route('fleet.reports.trip-revenue') }}" class="btn btn-primary">
                                            <i class="bx bx-money me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #2: Invoice Summary Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-receipt fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Invoice Summary Report</h5>
                                        <p class="card-text">Summary of all invoices issued with totals, payments, and outstanding amounts</p>
                                        <a href="{{ route('fleet.reports.invoice-summary') }}" class="btn btn-success">
                                            <i class="bx bx-receipt me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #3: Payment Collection Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-credit-card fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Payment Collection Report</h5>
                                        <p class="card-text">Collections received from customers with payment methods and dates</p>
                                        <a href="{{ route('fleet.reports.payment-collection') }}" class="btn btn-info">
                                            <i class="bx bx-credit-card me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #4: Outstanding Receivables Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-hourglass fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Outstanding Receivables Report</h5>
                                        <p class="card-text">Unpaid invoices and receivables with aging analysis and customer details</p>
                                        <a href="{{ route('fleet.reports.outstanding-receivables') }}" class="btn btn-warning">
                                            <i class="bx bx-hourglass me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #5: Revenue by Vehicle Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-car fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Revenue by Vehicle Report</h5>
                                        <p class="card-text">Revenue generated per vehicle with comparison and performance metrics</p>
                                        <a href="{{ route('fleet.reports.revenue-by-vehicle') }}" class="btn btn-danger">
                                            <i class="bx bx-car me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #6: Revenue by Driver Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-user fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Revenue by Driver Report</h5>
                                        <p class="card-text">Revenue contribution per driver with trip count and average revenue</p>
                                        <a href="{{ route('fleet.reports.revenue-by-driver') }}" class="btn btn-primary">
                                            <i class="bx bx-user me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #7: Route / Trip Type Revenue Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-map fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Route / Trip Type Revenue Report</h5>
                                        <p class="card-text">Revenue breakdown by route and trip type with performance analysis</p>
                                        <a href="{{ route('fleet.reports.route-revenue') }}" class="btn btn-success">
                                            <i class="bx bx-map me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #8: Profit & Loss (P&L) Report per Vehicle --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-trending-up fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Profit & Loss (P&L) Report per Vehicle</h5>
                                        <p class="card-text">Revenue, costs, and profitability analysis for each vehicle</p>
                                        <a href="{{ route('fleet.reports.profit-loss') }}" class="btn btn-info">
                                            <i class="bx bx-trending-up me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #9: Fuel Consumption Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-gas-pump fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Fuel Consumption Report</h5>
                                        <p class="card-text">Fuel usage by vehicle, trip, and period with efficiency metrics</p>
                                        <a href="{{ route('fleet.reports.fuel-consumption') }}" class="btn btn-warning">
                                            <i class="bx bx-gas-pump me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #10: Fuel Cost per vehicle Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-dollar fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Fuel Cost per Vehicle Report</h5>
                                        <p class="card-text">Fuel expenses per vehicle with cost per kilometer and trend analysis</p>
                                        <a href="{{ route('fleet.reports.fuel-cost') }}" class="btn btn-info">
                                            <i class="bx bx-dollar me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #11: Maintenance & Repair Cost Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-wrench fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Maintenance & Repair Cost Report</h5>
                                        <p class="card-text">Maintenance and repair expenses by vehicle, type, and time period</p>
                                        <a href="{{ route('fleet.reports.maintenance-cost') }}" class="btn btn-secondary">
                                            <i class="bx bx-wrench me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #12: Vehicle Operating Cost Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-dark position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-calculator fs-1 text-dark"></i>
                                        </div>
                                        <h5 class="card-title">Vehicle Operating Cost Report</h5>
                                        <p class="card-text">Total operating costs including fuel, maintenance, insurance, and other expenses</p>
                                        <a href="{{ route('fleet.reports.operating-cost') }}" class="btn btn-dark">
                                            <i class="bx bx-calculator me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #13: Driver Trip Activity Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-list-check fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Driver Trip Activity Report</h5>
                                        <p class="card-text">Trip details, counts, and activity summary for each driver</p>
                                        <a href="{{ route('fleet.reports.driver-activity') }}" class="btn btn-primary">
                                            <i class="bx bx-list-check me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #14: Driver Collection vs Trip Revenue Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-bar-chart-alt-2 fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Driver Collection vs Trip Revenue Report</h5>
                                        <p class="card-text">Comparison of collections received vs revenue generated by drivers</p>
                                        <a href="{{ route('fleet.reports.driver-collection') }}" class="btn btn-success">
                                            <i class="bx bx-bar-chart-alt-2 me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #15: Driver Outstanding Balance Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-time-five fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Driver Outstanding Balance Report</h5>
                                        <p class="card-text">Unpaid amounts and outstanding balances for each driver</p>
                                        <a href="{{ route('fleet.reports.driver-outstanding') }}" class="btn btn-warning">
                                            <i class="bx bx-time-five me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #16: Vehicle Utilization Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-chart fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Vehicle Utilization Report</h5>
                                        <p class="card-text">Vehicle usage statistics, idle time, and utilization percentages</p>
                                        <a href="{{ route('fleet.reports.vehicle-utilization') }}" class="btn btn-info">
                                            <i class="bx bx-chart me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #17: Dispatch Efficiency Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-timer fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Dispatch Efficiency Report</h5>
                                        <p class="card-text">Dispatch performance metrics including response time and efficiency rates</p>
                                        <a href="{{ route('fleet.reports.dispatch-efficiency') }}" class="btn btn-primary">
                                            <i class="bx bx-timer me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #18: Insurance & License Expiry Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-shield-check fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Insurance & License Expiry Report</h5>
                                        <p class="card-text">Compliance status with expiry dates for insurance and licenses</p>
                                        <a href="{{ route('fleet.reports.insurance-expiry') }}" class="btn btn-danger">
                                            <i class="bx bx-shield-check me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #19: Monthly Performance Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-calendar-check fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Monthly Performance Report</h5>
                                        <p class="card-text">Monthly summary of revenue, costs, trips, and key performance indicators</p>
                                        <a href="{{ route('fleet.reports.monthly-performance') }}" class="btn btn-success">
                                            <i class="bx bx-calendar-check me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #20: Missing Trip / Missing Invoice Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-error-circle fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Missing Trip / Missing Invoice Report</h5>
                                        <p class="card-text">Identify trips without invoices or invoices without corresponding trips</p>
                                        <a href="{{ route('fleet.reports.missing-trip-invoice') }}" class="btn btn-warning">
                                            <i class="bx bx-error-circle me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #21: Vehicle Replacement Analysis Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-refresh fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Vehicle Replacement Analysis Report</h5>
                                        <p class="card-text">Analysis of vehicle age, maintenance costs, and replacement recommendations</p>
                                        <a href="{{ route('fleet.reports.vehicle-replacement') }}" class="btn btn-secondary">
                                            <i class="bx bx-refresh me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #22: Alert and Notifications Reports --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-dark position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-bell fs-1 text-dark"></i>
                                        </div>
                                        <h5 class="card-title">Alert and Notifications Reports</h5>
                                        <p class="card-text">Summary of system alerts, notifications, and compliance reminders</p>
                                        <a href="{{ route('fleet.reports.alerts') }}" class="btn btn-dark">
                                            <i class="bx bx-bell me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #23: Tyre Performance & Lifespan Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-circle fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">Tyre Performance & Lifespan</h5>
                                        <p class="card-text">Track tyre usage, lifespan, kilometers covered, and performance metrics</p>
                                        <a href="{{ route('fleet.reports.tyre-performance') }}" class="btn btn-purple">
                                            <i class="bx bx-circle me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #24: Tyre Cost & Efficiency Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-dollar-circle fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">Tyre Cost & Efficiency</h5>
                                        <p class="card-text">Cost per kilometer analysis, replacement costs, and efficiency trends</p>
                                        <a href="{{ route('fleet.reports.tyre-cost-efficiency') }}" class="btn btn-purple">
                                            <i class="bx bx-dollar-circle me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #25: Tyre Abuse & Warranty Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-error-alt fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Tyre Abuse & Warranty</h5>
                                        <p class="card-text">Identify premature tyre failures, abuse patterns, and warranty claims</p>
                                        <a href="{{ route('fleet.reports.tyre-abuse-warranty') }}" class="btn btn-danger">
                                            <i class="bx bx-error-alt me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #26: Spare Parts Replacement History --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-orange position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-package fs-1 text-orange"></i>
                                        </div>
                                        <h5 class="card-title">Spare Parts Replacement History</h5>
                                        <p class="card-text">Track all spare part replacements with costs, frequencies, and vehicle analysis</p>
                                        <a href="{{ route('fleet.reports.spare-parts-history') }}" class="btn btn-orange">
                                            <i class="bx bx-package me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #27: Spare Parts Cost Analysis --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-orange position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-line-chart fs-1 text-orange"></i>
                                        </div>
                                        <h5 class="card-title">Spare Parts Cost Analysis</h5>
                                        <p class="card-text">Analyze spare parts costs per vehicle, category, and time period</p>
                                        <a href="{{ route('fleet.reports.spare-parts-cost-analysis') }}" class="btn btn-orange">
                                            <i class="bx bx-line-chart me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #28: Tyre Installation & Removal Log --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-transfer fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Tyre Installation & Removal Log</h5>
                                        <p class="card-text">Complete history of tyre installations, removals, and position changes</p>
                                        <a href="{{ route('fleet.reports.tyre-installation-log') }}" class="btn btn-info">
                                            <i class="bx bx-transfer me-1"></i> View Report
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

    .border-primary {
        border-color: #0d6efd !important;
    }

    .border-success {
        border-color: #198754 !important;
    }

    .border-warning {
        border-color: #ffc107 !important;
    }

    .border-info {
        border-color: #0dcaf0 !important;
    }

    .border-danger {
        border-color: #dc3545 !important;
    }

    .border-secondary {
        border-color: #6c757d !important;
    }

    .border-dark {
        border-color: #212529 !important;
    }

    .border-purple {
        border-color: #6f42c1 !important;
    }

    .text-purple {
        color: #6f42c1 !important;
    }

    .btn-purple {
        background-color: #6f42c1;
        border-color: #6f42c1;
        color: #fff;
    }

    .btn-purple:hover {
        background-color: #5a32a3;
        border-color: #5a32a3;
        color: #fff;
    }

    .border-orange {
        border-color: #fd7e14 !important;
    }

    .text-orange {
        color: #fd7e14 !important;
    }

    .btn-orange {
        background-color: #fd7e14;
        border-color: #fd7e14;
        color: #fff;
    }

    .btn-orange:hover {
        background-color: #dc6502;
        border-color: #dc6502;
        color: #fff;
    }
</style>
@endpush
