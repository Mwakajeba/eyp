@extends('layouts.main')

@section('title', 'Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => '#', 'icon' => 'bx bx-car']
        ]" />
        <h6 class="mb-0 text-uppercase">FLEET MANAGEMENT</h6>
        <hr />

        <!-- Fleet Statistics -->
        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="card border-top border-0 border-4 border-primary">
                    <div class="card-body p-5">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-car me-1 font-22 text-primary"></i></div>
                            <h5 class="mb-0 text-primary">Fleet Statistics</h5>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card radius-10 bg-primary">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Total Vehicles</p>
                                                <h4 class="text-white">{{ $vehicleCount ?? 0 }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-car"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card radius-10 bg-success">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Active Vehicles</p>
                                                <h4 class="text-white">{{ $activeVehiclesCount ?? 0 }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-check-circle"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card radius-10 bg-info">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Active Trips</p>
                                                <h4 class="text-white">{{ $activeTripsCount ?? 0 }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-trip"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card radius-10 bg-warning">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">In Maintenance</p>
                                                <h4 class="text-white">{{ $inMaintenanceCount ?? 0 }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-wrench"></i></div>
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
                            <div><i class="bx bx-trending-up me-1 font-22 text-success"></i></div>
                            <h5 class="mb-0 text-success">Fleet Analytics</h5>
                        </div>
                        <hr>
                        <div class="d-grid gap-3">
                            <div class="row text-center">
                                <div class="col-12">
                                    <h4 class="text-success mb-1">{{ $tripsThisMonth ?? 0 }}</h4>
                                    <small class="text-muted">Trips This Month</small>
                                </div>
                            </div>
                            <div class="row text-center">
                                <div class="col-6">
                                    <h5 class="text-primary mb-1">{{ number_format($totalRevenueThisMonth ?? 0, 0) }}</h5>
                                    <small class="text-muted">Revenue (TZS)</small>
                                </div>
                                <div class="col-6">
                                    <h5 class="text-danger mb-1">{{ number_format($totalCostsThisMonth ?? 0, 0) }}</h5>
                                    <small class="text-muted">Costs (TZS)</small>
                                </div>
                            </div>
                            <div class="row text-center">
                                <div class="col-12">
                                    @php
                                        $profitThisMonth = ($totalRevenueThisMonth ?? 0) - ($totalCostsThisMonth ?? 0);
                                    @endphp
                                    <h5 class="mb-1 {{ $profitThisMonth >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($profitThisMonth, 0) }}</h5>
                                    <small class="text-muted">Profit (TZS)</small>
                                </div>
                            </div>
                            <div class="row text-center">
                                <div class="col-12">
                                    <h5 class="text-info mb-1">{{ $avgFuelEfficiency ?? 0 }} km/L</h5>
                                    <small class="text-muted">Avg Fuel Efficiency</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fleet Management Operations -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-grid me-1 font-22 text-primary"></i></div>
                            <h5 class="mb-0 text-primary">Fleet Management Operations</h5>
                        </div>
                        <hr>
                        <div class="row">
                            <!-- 1. Trip Planning & Dispatch -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ ($plannedTripsCount ?? 0) + ($activeTripsCount ?? 0) }}
                                            <span class="visually-hidden">trips (planned + active)</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-trip fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Trip Planning & Dispatch</h5>
                                        <p class="card-text">Create, plan, and dispatch trips. Monitor trip status and performance.</p>
                                        <a href="{{ route('fleet.trips.index') }}" class="btn btn-info">
                                            <i class="bx bx-list-ul me-1"></i> Manage Trips
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Vehicle Master -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ $vehicleCount ?? 0 }}
                                            <span class="visually-hidden">vehicles count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-car fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Vehicle Master</h5>
                                        <p class="card-text">Manage fleet vehicles, registration, ownership, and specifications.</p>
                                        <a href="{{ route('fleet.vehicles.index') }}" class="btn btn-primary">
                                            <i class="bx bx-list-ul me-1"></i> Manage Vehicles
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Driver Master -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ $driverCount ?? 0 }}
                                            <span class="visually-hidden">drivers count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-user fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Driver Master</h5>
                                        <p class="card-text">Manage drivers, licenses, assignments, and compliance records.</p>
                                        <a href="{{ route('fleet.drivers.index') }}" class="btn btn-success">
                                            <i class="bx bx-list-ul me-1"></i> Manage Drivers
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Revenue & Billing -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ $invoiceCount ?? 0 }}
                                            <span class="visually-hidden">invoices count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-receipt fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Revenue & Billing</h5>
                                        <p class="card-text">Generate invoices, track revenue, and manage client billing.</p>
                                        <a href="{{ route('fleet.invoices.index') }}" class="btn btn-success">
                                            <i class="bx bx-receipt me-1"></i> Manage Billing
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 5. Cost Management -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ $costCount ?? 0 }}
                                            <span class="visually-hidden">costs count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-money fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Cost Management</h5>
                                        <p class="card-text">Track fuel, maintenance, driver costs, and other trip expenses.</p>
                                        <a href="{{ route('fleet.trip-costs.index') }}" class="btn btn-danger">
                                            <i class="bx bx-list-ul me-1"></i> Manage Costs
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 6. Cost Categories -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary">
                                            {{ $costCategoryCount ?? 0 }}
                                            <span class="visually-hidden">categories</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-category fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Cost Categories</h5>
                                        <p class="card-text">Create and manage cost categories used when recording trip costs.</p>
                                        <a href="{{ route('fleet.cost-categories.index') }}" class="btn btn-secondary">
                                            <i class="bx bx-category me-1"></i> Manage Categories
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 7. Fuel Management -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-orange position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-orange">
                                            {{ $fuelCount ?? 0 }}
                                            <span class="visually-hidden">fuel logs count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-gas-pump fs-1 text-orange"></i>
                                        </div>
                                        <h5 class="card-title">Fuel Management</h5>
                                        <p class="card-text">Track fuel consumption, efficiency, and manage fuel card integrations.</p>
                                        <a href="{{ route('fleet.fuel.index') }}" class="btn btn-orange">
                                            <i class="bx bx-gas-pump me-1"></i> Manage Fuel
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 8. Compliance & Safety -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                            {{ $complianceCount ?? 0 }}
                                            <span class="visually-hidden">compliance count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-shield-check fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Compliance & Safety</h5>
                                        <p class="card-text">Manage insurance, licenses, inspections, and safety compliance.</p>
                                        <a href="{{ route('fleet.compliance.index') }}" class="btn btn-warning">
                                            <i class="bx bx-shield-check me-1"></i> Manage Compliance
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 9. Maintenance Work Orders -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-purple">
                                            {{ $maintenanceWorkOrderCount ?? 0 }}
                                            <span class="visually-hidden">work orders</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-wrench fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">Maintenance Work Orders</h5>
                                        <p class="card-text">Schedule preventive maintenance, manage work orders, and track repairs.</p>
                                        <a href="{{ route('fleet.maintenance.work-orders.index') }}" class="btn btn-purple">
                                            <i class="bx bx-wrench me-1"></i> Work Orders
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 10. Maintenance Schedules -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-purple">
                                            {{ $maintenanceScheduleCount ?? 0 }}
                                            <span class="visually-hidden">schedules</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-calendar fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">Maintenance Schedules</h5>
                                        <p class="card-text">Schedule and track preventive maintenance for fleet vehicles.</p>
                                        <a href="{{ route('fleet.maintenance.schedules.index') }}" class="btn btn-purple">
                                            <i class="bx bx-calendar me-1"></i> View Schedules
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 11. Tyre Master Register -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ $tyreCount ?? 0 }}
                                            <span class="visually-hidden">tyres</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-circle fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Tyre Master Register</h5>
                                        <p class="card-text">Register every tyre with identity, DOT/serial, warranty, and expected lifespan. Tyres are assets with a life cycle.</p>
                                        <a href="{{ route('fleet.tyres.index') }}" class="btn btn-primary">
                                            <i class="bx bx-list-ul me-1"></i> Manage Tyres
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ $tyrePositionCount ?? 0 }}
                                            <span class="visually-hidden">positions</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-grid-alt fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Truck Tyre Configuration</h5>
                                        <p class="card-text">Define tyre positions per truck (Front Left, Rear Axle, etc.) so you track which tyre is on which position.</p>
                                        <a href="{{ route('fleet.tyre-positions.index') }}" class="btn btn-info">
                                            <i class="bx bx-cog me-1"></i> Configure Positions
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-purple">
                                            {{ $sparePartCategoryCount ?? 0 }}
                                            <span class="visually-hidden">categories</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-package fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">Spare Parts Master (Vipuri)</h5>
                                        <p class="card-text">Define spare part categories with expected lifespan, replacement intervals, and approval thresholds.</p>
                                        <a href="{{ route('fleet.spare-part-categories.index') }}" class="btn btn-purple">
                                            <i class="bx bx-category me-1"></i> Manage Vipuri
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ $tyreInstallationCount ?? 0 }}
                                            <span class="visually-hidden">installations</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-wrench fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Tyre Installation</h5>
                                        <p class="card-text">Assign tyres to trucks and positions. Record installation date, odometer, and installer. Cool-down rules apply.</p>
                                        <a href="{{ route('fleet.tyre-installations.index') }}" class="btn btn-success">
                                            <i class="bx bx-plus-circle me-1"></i> Install Tyres
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ $tyreReplacementRequestCount ?? 0 }}
                                            <span class="visually-hidden">requests</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-error-circle fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Tyre Replacement Requests</h5>
                                        <p class="card-text">Driver requests with photos; system validates mileage, warranty, and risk. Multi-level approval workflow.</p>
                                        <a href="{{ route('fleet.tyre-replacement-requests.index') }}" class="btn btn-danger">
                                            <i class="bx bx-list-check me-1"></i> View Requests
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 15. Spare Parts (Vipuri) Replacement -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-orange position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-orange">
                                            {{ $sparePartReplacementCount ?? 0 }}
                                            <span class="visually-hidden">replacements</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-refresh fs-1 text-orange"></i>
                                        </div>
                                        <h5 class="card-title">Spare Parts (Vipuri) Replacement</h5>
                                        <p class="card-text">Record and approve spare part replacements. System checks last replacement and expected lifespan.</p>
                                        <a href="{{ route('fleet.spare-part-replacements.index') }}" class="btn btn-orange">
                                            <i class="bx bx-refresh me-1"></i> Replacements
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 16. Fleet Reports -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-bar-chart-alt fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Fleet Reports</h5>
                                        <p class="card-text">Comprehensive reports for trips, costs, revenue, maintenance, and performance analysis.</p>
                                        <a href="{{ route('fleet.reports.index') }}" class="btn btn-secondary">
                                            <i class="bx bx-bar-chart me-1"></i> View Reports
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 17. Fleet Settings -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-dark position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-cog fs-1 text-dark"></i>
                                        </div>
                                        <h5 class="card-title">Fleet Settings</h5>
                                        <p class="card-text">Configure fleet management preferences, defaults, and workflow settings.</p>
                                        <a href="{{ route('fleet.settings.index') }}" class="btn btn-dark">
                                            <i class="bx bx-cog me-1"></i> Manage Settings
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

    .border-purple {
        border-color: #6f42c1 !important;
    }

    .text-purple {
        color: #6f42c1 !important;
    }

    .bg-purple {
        background-color: #6f42c1 !important;
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

    .bg-orange {
        background-color: #fd7e14 !important;
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

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // Add any JavaScript functionality here
        console.log('Fleet Management module loaded');
    });
</script>
@endpush