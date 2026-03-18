@extends('layouts.main')

@section('title', 'Assets Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Assets Management', 'url' => '#', 'icon' => 'bx bx-building']
        ]" />
        <h6 class="mb-0 text-uppercase">ASSETS MANAGEMENT</h6>
        <hr />

        <!-- Assets Statistics -->
        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="card border-top border-0 border-4 border-primary">
                    <div class="card-body p-5">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-building me-1 font-22 text-primary"></i></div>
                            <h5 class="mb-0 text-primary">Assets Statistics</h5>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card radius-10 bg-primary">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Total Assets</p>
                                                @php
                                                    $branchId = session('branch_id') ?? auth()->user()->branch_id ?? null;
                                                    $assetCount = class_exists('App\\Models\\Assets\\Asset') ? \App\Models\Assets\Asset::forBranch($branchId)->count() : 0;
                                                @endphp
                                                <h4 class="text-white">{{ $assetCount }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-cabinet"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card radius-10 bg-success">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Purchased</p>
                                                @php
                                                    $branchId = session('branch_id') ?? auth()->user()->branch_id ?? null;
                                                    $purchasedAssetCount = class_exists('App\\Models\\Assets\\Asset') ? \App\Models\Assets\Asset::forBranch($branchId)->where('status', 'purchased')->count() : 0;
                                                @endphp
                                                <h4 class="text-white">{{ $purchasedAssetCount }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-cart"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card radius-10 bg-info">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Assigned</p>
                                                @php
                                                    $branchId = session('branch_id') ?? auth()->user()->branch_id ?? null;
                                                    $assignedAssetCount = class_exists('App\\Models\\Assets\\Asset') ? \App\Models\Assets\Asset::forBranch($branchId)->where('status', 'assigned')->count() : 0;
                                                @endphp
                                                <h4 class="text-white">{{ $assignedAssetCount }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-user-check"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card radius-10 bg-warning">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Maintenance</p>
                                                @php
                                                    $branchId = session('branch_id') ?? auth()->user()->branch_id ?? null;
                                                    $maintenanceCount = class_exists('App\\Models\\Assets\\Asset') ? \App\Models\Assets\Asset::forBranch($branchId)->where('status', 'maintenance')->count() : 0;
                                                @endphp
                                                <h4 class="text-white">{{ $maintenanceCount }}</h4>
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
                            <h5 class="mb-0 text-success">Assets Analytics</h5>
                        </div>
                        <hr>
                        <div class="d-grid gap-2">
                            <div class="row text-center mb-3">
                                <div class="col-12">
                                    <h4 class="text-success mb-1">
                                        @php $branchId = session('branch_id') ?? auth()->user()->branch_id ?? null; @endphp
                                        {{ class_exists('App\\Models\\Assets\\Asset') ? \App\Models\Assets\Asset::forBranch($branchId)->whereMonth('created_at', now()->month)->count() : 0 }}
                                    </h4>
                                    <small class="text-muted">New Assets This Month</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asset Modules -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-grid me-1 font-22 text-primary"></i></div>
                            <h5 class="mb-0 text-primary">Asset Flow Management</h5>
                        </div>
                        <hr>
                        <div class="row">
                            <!-- 1. Asset Categories -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ class_exists('App\\Models\\Assets\\AssetCategory') ? \App\Models\Assets\AssetCategory::forCompany(auth()->user()->company_id)->count() : 0 }}
                                            <span class="visually-hidden">assets category count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-category fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Asset Categories</h5>
                                        <p class="card-text">Define categories with default accounts, methods, and useful life.</p>
                                        <a href="{{ route('assets.categories.index') }}" class="btn btn-primary">
                                            <i class="bx bx-list-ul me-1"></i> Manage Categories
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Assets Registry -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ class_exists('App\\Models\\Assets\\Asset') ? \App\Models\Assets\Asset::forCompany(auth()->user()->company_id)->count() : 0 }}
                                            <span class="visually-hidden">assets count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-cabinet fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Assets Registry</h5>
                                        <p class="card-text">Master data, categories, locations, custodians.</p>
                                        <a href="{{ route('assets.registry.index') }}" class="btn btn-primary">
                                            <i class="bx bx-list-ul me-1"></i> Open Registry
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- 9. Opening Assets -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-dark position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark">
                                            @php $branchId = session('branch_id') ?? auth()->user()->branch_id ?? null; @endphp
                                            {{ class_exists('App\\Models\\Assets\\AssetOpening') ? \App\Models\Assets\AssetOpening::forBranch($branchId)->count() : 0 }}
                                            <span class="visually-hidden">openings count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-book-open fs-1 text-dark"></i>
                                        </div>
                                        <h5 class="card-title">Opening Assets</h5>
                                        <p class="card-text">Import/opening balances for existing assets.</p>
                                        <a href="{{ route('assets.openings.index') }}" class="btn btn-dark">
                                            <i class="bx bx-list-ul me-1"></i> Open Opening Assets
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Capitalization -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            @php $branchId = session('branch_id') ?? auth()->user()->branch_id ?? null; @endphp
                                            {{ class_exists('App\\Models\\Assets\\Asset') ? \App\Models\Assets\Asset::forBranch($branchId)->where('status','purchased')->count() : 0 }}
                                            <span class="visually-hidden">capitalizations count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-cart fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Capitalization</h5>
                                        <p class="card-text">From purchases or manual journals.</p>
                                        <a href="#" class="btn btn-success disabled" aria-disabled="true">
                                            <i class="bx bx-list-ul me-1"></i> Open Capitalization
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Book Depreciation (IFRS) -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            @php
                                                $deprCount = class_exists('App\\Models\\Assets\\AssetDepreciation') 
                                                    ? \App\Models\Assets\AssetDepreciation::where('company_id', auth()->user()->company_id)
                                                        ->when(session('branch_id') ?? auth()->user()->branch_id, fn($q) => $q->where('branch_id', session('branch_id') ?? auth()->user()->branch_id))
                                                        ->where('type', 'depreciation')
                                                        ->where(function($q) {
                                                            $q->whereNull('depreciation_type')->orWhere('depreciation_type', 'book');
                                                        })
                                                        ->count() 
                                                    : 0;
                                            @endphp
                                            {{ $deprCount }}
                                            <span class="visually-hidden">book depreciations count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-calculator fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Book Depreciation (IFRS)</h5>
                                        <p class="card-text">Accounting depreciation - Monthly scheduled, multiple methods.</p>
                                        <a href="{{ route('assets.depreciation.index') }}" class="btn btn-info">
                                            <i class="bx bx-calculator me-1"></i> Open Book Depreciation
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 4a. Tax Depreciation (TRA) -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            @php
                                                $taxDeprCount = class_exists('App\\Models\\Assets\\AssetDepreciation') 
                                                    ? \App\Models\Assets\AssetDepreciation::where('company_id', auth()->user()->company_id)
                                                        ->when(session('branch_id') ?? auth()->user()->branch_id, fn($q) => $q->where('branch_id', session('branch_id') ?? auth()->user()->branch_id))
                                                        ->where('type', 'depreciation')
                                                        ->where('depreciation_type', 'tax')
                                                        ->count() 
                                                    : 0;
                                            @endphp
                                            {{ $taxDeprCount }}
                                            <span class="visually-hidden">tax depreciations count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-calculator fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Tax Depreciation (TRA)</h5>
                                        <p class="card-text">TRA-compliant tax depreciation for tax computation.</p>
                                        <a href="{{ route('assets.tax-depreciation.index') }}" class="btn btn-success">
                                            <i class="bx bx-calculator me-1"></i> Open Tax Depreciation
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 4b. Deferred Tax -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                            @php
                                                $deferredTaxCount = class_exists('App\\Models\\Assets\\AssetDeferredTax') 
                                                    ? \App\Models\Assets\AssetDeferredTax::where('company_id', auth()->user()->company_id)
                                                        ->when(session('branch_id') ?? auth()->user()->branch_id, fn($q) => $q->where('branch_id', session('branch_id') ?? auth()->user()->branch_id))
                                                        ->where('tax_year', now()->year)
                                                        ->count() 
                                                    : 0;
                                            @endphp
                                            {{ $deferredTaxCount }}
                                            <span class="visually-hidden">deferred tax entries count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-money fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Deferred Tax</h5>
                                        <p class="card-text">Calculate and track deferred tax from book vs tax differences.</p>
                                        <a href="{{ route('assets.deferred-tax.index') }}" class="btn btn-warning">
                                            <i class="bx bx-money me-1"></i> Open Deferred Tax
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 5. Movements -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                            @php
                                                $movementCount = 0;
                                                if (class_exists('App\\Models\\Assets\\AssetMovement')) {
                                                    $branchId = session('branch_id') ?? auth()->user()->branch_id ?? null;
                                                    $movementCount = \App\Models\Assets\AssetMovement::where('company_id', auth()->user()->company_id)
                                                        ->when($branchId, function($q) use ($branchId){
                                                            $q->where(function($qq) use ($branchId){
                                                                $qq->where('from_branch_id', $branchId)
                                                                   ->orWhere('to_branch_id', $branchId);
                                                            });
                                                        })
                                                        ->count();
                                                }
                                            @endphp
                                            {{ $movementCount }}
                                            <span class="visually-hidden">movements count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-transfer fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Movements</h5>
                                        <p class="card-text">Transfers between branches/departments/users.</p>
                                        <a href="{{ route('assets.movements.index') }}" class="btn btn-warning">
                                            <i class="bx bx-list-ul me-1"></i> Open Movements
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 6. Revaluation & Impairment -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            @php 
                                                $branchId = session('branch_id') ?? auth()->user()->branch_id ?? null;
                                                $revaluationCount = 0;
                                                if (class_exists('App\\Models\\Assets\\AssetRevaluation')) {
                                                    $revaluationCount = \App\Models\Assets\AssetRevaluation::where('company_id', auth()->user()->company_id)
                                                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                                                        ->count();
                                                }
                                            @endphp
                                            {{ $revaluationCount }}
                                            <span class="visually-hidden">revaluations count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-trending-up fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Revaluation & Impairment</h5>
                                        <p class="card-text">Adjust carrying amount and depreciation basis.</p>
                                        <a href="{{ route('assets.revaluations.index') }}" class="btn btn-danger">
                                            <i class="bx bx-trending-up me-1"></i> Open Revaluation
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 7. Disposal / Retirement -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary">
                                            @php 
                                                $branchId = session('branch_id') ?? auth()->user()->branch_id ?? null;
                                                $disposalCount = 0;
                                                if (class_exists('App\\Models\\Assets\\AssetDisposal')) {
                                                    $disposalCount = \App\Models\Assets\AssetDisposal::where('company_id', auth()->user()->company_id)
                                                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                                                        ->count();
                                                }
                                            @endphp
                                            {{ $disposalCount }}
                                            <span class="visually-hidden">disposals count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-trash fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Disposal / Retirement</h5>
                                        <p class="card-text">Sale/scrap/write-off with gain/loss posting.</p>
                                        <a href="{{ route('assets.disposals.index') }}" class="btn btn-secondary">
                                            <i class="bx bx-list-ul me-1"></i> Open Disposal
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 8. Maintenance -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-purple">
                                            @php 
                                                $branchId = session('branch_id') ?? auth()->user()->branch_id ?? null;
                                                $companyId = auth()->user()->company_id ?? null;
                                                $maintenanceCount = 0;
                                                if (class_exists('App\\Models\\Assets\\WorkOrder')) {
                                                    $maintenanceCount = \App\Models\Assets\WorkOrder::where('company_id', $companyId)
                                                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                                                        ->whereIn('status', ['approved', 'in_progress'])
                                                        ->count();
                                                }
                                            @endphp
                                            {{ $maintenanceCount }}
                                            <span class="visually-hidden">active work orders count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-wrench fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">Maintenance</h5>
                                        <p class="card-text">Schedules, work orders, costs, and maintenance management.</p>
                                        <a href="{{ route('assets.maintenance.index') }}" class="btn btn-purple">
                                            <i class="bx bx-wrench me-1"></i> Open Maintenance
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 8a. Held for Sale (HFS) -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-orange position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-orange">
                                            @php 
                                                $branchId = session('branch_id') ?? auth()->user()->branch_id ?? null;
                                                $companyId = auth()->user()->company_id ?? null;
                                                $hfsCount = 0;
                                                if (class_exists('App\\Models\\Assets\\HfsRequest')) {
                                                    $hfsCount = \App\Models\Assets\HfsRequest::where('company_id', $companyId)
                                                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                                                        ->whereIn('status', ['draft', 'in_review', 'approved'])
                                                        ->count();
                                                }
                                            @endphp
                                            {{ $hfsCount }}
                                            <span class="visually-hidden">active HFS requests count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-transfer fs-1 text-orange"></i>
                                        </div>
                                        <h5 class="card-title">Held for Sale (HFS)</h5>
                                        <p class="card-text">IFRS 5 compliance: Classify, measure, and dispose assets held for sale.</p>
                                        <a href="{{ route('assets.hfs.requests.index') }}" class="btn btn-orange">
                                            <i class="bx bx-transfer me-1"></i> Open HFS
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 10. Audit & Attachments -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            @php $branchId = session('branch_id') ?? auth()->user()->branch_id ?? null; @endphp
                                            {{ class_exists('App\\Models\\Assets\\AssetLog') ? \App\Models\Assets\AssetLog::forBranch($branchId)->count() : 0 }}
                                            <span class="visually-hidden">intangible assets count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-clipboard fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Intangible Assets</h5>
                                        <p class="card-text">Intangible assets are assets that lack physical substance but have economic value, such as goodwill, patents, and trademarks.</p>
                                        <a href="{{ route('assets.intangible.index') }}" class="btn btn-info">
                                            <i class="bx bx-list-ul me-1"></i> Manage Intangible Assets
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Tyre Master Register (Fleet) -->
                            @if(class_exists('App\\Models\\Fleet\\FleetTyre'))
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative h-100">
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                        {{ \App\Models\Fleet\FleetTyre::where('company_id', auth()->user()->company_id)->count() }}
                                        <span class="visually-hidden">tyres count</span>
                                    </span>
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-circle fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Tyre Master Register</h5>
                                        <p class="card-text">Register every tyre with identity, DOT/serial, warranty, and expected lifespan. Tyres are assets with a life cycle.</p>
                                        <a href="{{ route('fleet.tyres.index') }}" class="btn btn-info">
                                            <i class="bx bx-list-ul me-1"></i> Manage Tyres
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Spare Parts Master (Vipuri) (Fleet) -->
                            @if(class_exists('App\\Models\\Fleet\\FleetSparePartCategory'))
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative h-100">
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                        {{ \App\Models\Fleet\FleetSparePartCategory::where('company_id', auth()->user()->company_id)->count() }}
                                        <span class="visually-hidden">spare part categories count</span>
                                    </span>
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-package fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Spare Parts Master (Vipuri)</h5>
                                        <p class="card-text">Define spare part categories with expected lifespan, replacement intervals, and approval thresholds.</p>
                                        <a href="{{ route('fleet.spare-part-categories.index') }}" class="btn btn-warning text-dark">
                                            <i class="bx bx-list-ul me-1"></i> Manage Categories
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- 11. Settings -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-cog fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Settings</h5>
                                        <p class="card-text">Defaults, accounts, methods, thresholds, useful life.</p>
                                        <a href="{{ route('assets.settings.index') }}" class="btn btn-outline-primary">
                                            <i class="bx bx-cog me-1"></i> Open Settings
                                        </a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tax Depreciation Reports Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-file-blank me-1 font-22 text-danger"></i></div>
                            <h5 class="mb-0 text-danger">Tax Depreciation Reports (TRA Compliance)</h5>
                        </div>
                        <hr>
                        <div class="row">
                            <!-- TRA Tax Depreciation Schedule -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-table fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">TRA Tax Depreciation Schedule</h5>
                                        <p class="card-text">Grouped by TRA class: additions, disposals, tax depreciation for tax filing.</p>
                                        <a href="{{ route('assets.tax-depreciation.reports.tra-schedule') }}" class="btn btn-danger">
                                            <i class="bx bx-table me-1"></i> View TRA Schedule
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Book vs Tax Reconciliation -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-line-chart fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Book vs Tax Reconciliation</h5>
                                        <p class="card-text">Reconcile differences between book (IFRS) and tax (TRA) depreciation values.</p>
                                        <a href="{{ route('assets.tax-depreciation.reports.book-tax-reconciliation') }}" class="btn btn-danger">
                                            <i class="bx bx-line-chart me-1"></i> View Reconciliation
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Deferred Tax Schedule -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative h-100">
                                    <div class="card-body text-center">
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                            @php
                                                $deferredTaxYearCount = class_exists('App\\Models\\Assets\\AssetDeferredTax') 
                                                    ? \App\Models\Assets\AssetDeferredTax::where('company_id', auth()->user()->company_id)
                                                        ->when(session('branch_id') ?? auth()->user()->branch_id, fn($q) => $q->where('branch_id', session('branch_id') ?? auth()->user()->branch_id))
                                                        ->where('tax_year', now()->year)
                                                        ->sum('closing_balance') 
                                                    : 0;
                                            @endphp
                                            {{ number_format(abs($deferredTaxYearCount), 2) }}
                                            <span class="visually-hidden">deferred tax amount</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-money fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Deferred Tax Schedule</h5>
                                        <p class="card-text">View deferred tax schedule with movements and balances by asset.</p>
                                        <a href="{{ route('assets.deferred-tax.schedule') }}" class="btn btn-warning">
                                            <i class="bx bx-table me-1"></i> View Schedule
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Assets (optional simple list) -->
        @php
            $recentAssets = class_exists('App\\Models\\Assets\\Asset')
                ? \App\Models\Assets\Asset::forBranch(session('branch_id') ?? auth()->user()->branch_id ?? null)->orderBy('created_at','desc')->limit(10)->get()
                : collect();
        @endphp

        @if($recentAssets->count() > 0)
        <div class="card">
            <div class="card-body">
                <div class="card-title d-flex align-items-center justify-content-between">
                    <div>
                        <i class="bx bx-time-five me-1 font-22 text-primary"></i>
                        <h5 class="mb-0 text-primary">Recent Assets</h5>
                    </div>
                </div>
                <hr>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Asset Code</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentAssets as $index => $asset)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $asset->code ?? '-' }}</td>
                                <td>{{ $asset->name ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ ucfirst($asset->status ?? 'n/a') }}</span>
                                </td>
                                <td>{{ optional($asset->created_at)->format('M d, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
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

    .border-dark {
        border-color: #212529 !important;
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
        // Initialize DataTable for recent proformas
        if ($('#recent-proformas-table').length) {
            $('#recent-proformas-table').DataTable({
                responsive: true
                , order: [
                    [3, 'desc']
                ], // Sort by date descending
                pageLength: 5
                , searching: false
                , lengthChange: false
                , info: false
                , language: {
                    paginate: {
                        first: "First"
                        , last: "Last"
                        , next: "Next"
                        , previous: "Previous"
                    }
                }
            });
        }
    });

</script>
@endpush
