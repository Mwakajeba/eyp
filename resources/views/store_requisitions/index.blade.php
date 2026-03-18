@extends('layouts.main')

@section('title', 'Store Requisition Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Store Requisition Management', 'url' => '#', 'icon' => 'bx bx-package']
        ]" />
        
        <h6 class="mb-0 text-uppercase">STORE REQUISITION MANAGEMENT SYSTEM</h6>
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
                            <!-- All Store Requisitions -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ $stats['total_requisitions'] }}
                                            <span class="visually-hidden">total requisitions</span>
                                        </span>
                                        
                                        <div class="card-icon mb-3">
                                            <i class="bx bx-package text-primary" style="font-size: 2.5rem;"></i>
                                        </div>
                                        <h5 class="card-title mb-2">All Store Requisitions</h5>
                                        <p class="card-text text-muted">View and manage all store requisitions</p>
                                        <a href="{{ route('store-requisitions.requisitions.index') }}" class="btn btn-primary">
                                            <i class="bx bx-list-ul me-1"></i>View All
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Pending Requisitions -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">
                                            {{ $stats['pending_requisitions'] }}
                                            <span class="visually-hidden">pending requisitions</span>
                                        </span>
                                        
                                        <div class="card-icon mb-3">
                                            <i class="bx bx-time-five text-warning" style="font-size: 2.5rem;"></i>
                                        </div>
                                        <h5 class="card-title mb-2">Pending Approval</h5>
                                        <p class="card-text text-muted">Requisitions awaiting approval</p>
                                        <a href="{{ route('store-requisitions.requisitions.index', ['status' => 'pending']) }}" class="btn btn-warning">
                                            <i class="bx bx-check-circle me-1"></i>Review
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Approved Requisitions -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ $stats['approved_requisitions'] }}
                                            <span class="visually-hidden">approved requisitions</span>
                                        </span>
                                        
                                        <div class="card-icon mb-3">
                                            <i class="bx bx-check-circle text-success" style="font-size: 2.5rem;"></i>
                                        </div>
                                        <h5 class="card-title mb-2">Approved</h5>
                                        <p class="card-text text-muted">Ready for store issue</p>
                                        <a href="{{ route('store-requisitions.requisitions.index', ['status' => 'approved']) }}" class="btn btn-success">
                                            <i class="bx bx-package me-1"></i>Issue Items
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Issued Requisitions -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ $stats['issued_requisitions'] }}
                                            <span class="visually-hidden">issued requisitions</span>
                                        </span>
                                        
                                        <div class="card-icon mb-3">
                                            <i class="bx bx-export text-info" style="font-size: 2.5rem;"></i>
                                        </div>
                                        <h5 class="card-title mb-2">Issued Items</h5>
                                        <p class="card-text text-muted">Items have been issued</p>
                                        <a href="{{ route('store-issues.index') }}" class="btn btn-info">
                                            <i class="bx bx-show me-1"></i>View Issues
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Returned Items -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary">
                                            {{ $stats['returned_requisitions'] }}
                                            <span class="visually-hidden">returned requisitions</span>
                                        </span>
                                        
                                        <div class="card-icon mb-3">
                                            <i class="bx bx-undo text-secondary" style="font-size: 2.5rem;"></i>
                                        </div>
                                        <h5 class="card-title mb-2">Returned Items</h5>
                                        <p class="card-text text-muted">Items have been returned</p>
                                        <a href="{{ route('store-returns.index') }}" class="btn btn-secondary">
                                            <i class="bx bx-show me-1"></i>View Returns
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Rejected Requisitions -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ $stats['rejected_requisitions'] }}
                                            <span class="visually-hidden">rejected requisitions</span>
                                        </span>
                                        
                                        <div class="card-icon mb-3">
                                            <i class="bx bx-x-circle text-danger" style="font-size: 2.5rem;"></i>
                                        </div>
                                        <h5 class="card-title mb-2">Rejected</h5>
                                        <p class="card-text text-muted">Requisitions that were rejected</p>
                                        <a href="{{ route('store-requisitions.requisitions.index', ['status' => 'rejected']) }}" class="btn btn-danger">
                                            <i class="bx bx-show me-1"></i>View Rejected
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">
                                            <i class="bx bx-cog me-2"></i>Quick Actions
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <a href="{{ route('store-requisitions.requisitions.create') }}" class="btn btn-primary w-100">
                                                    <i class="bx bx-plus me-2"></i>New Requisition
                                                </a>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <a href="{{ route('store-requisitions.approval-settings.index') }}" class="btn btn-secondary w-100">
                                                    <i class="bx bx-cog me-2"></i>Approval Settings
                                                </a>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <a href="{{ route('store-issues.index') }}" class="btn btn-info w-100">
                                                    <i class="bx bx-package me-2"></i>Store Issues
                                                </a>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <a href="{{ route('store-returns.index') }}" class="btn btn-warning w-100">
                                                    <i class="bx bx-undo me-2"></i>Store Returns
                                                </a>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <a href="{{ route('manuals.index') }}" class="btn btn-info w-100">
                                                    <i class="bx bx-book me-2"></i>User Manual
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Approval Settings Configuration -->
                        @if($approvalSettings)
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">
                                            <i class="bx bx-shield-check me-2"></i>Current Approval Configuration
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @for($level = 1; $level <= 5; $level++)
                                                @if($approvalSettings->{"level_{$level}_enabled"})
                                                <div class="col-md-6 col-lg-4 mb-3">
                                                    <div class="d-flex align-items-center p-3 border rounded">
                                                        <div class="flex-shrink-0">
                                                            <div class="badge bg-primary rounded-circle p-2">
                                                                {{ $level }}
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <h6 class="mb-1">Level {{ $level }}</h6>
                                                            <small class="text-muted">
                                                                @if($approvalSettings->{"level{$level}User"})
                                                                    {{ $approvalSettings->{"level{$level}User"}->name }}
                                                                @elseif($approvalSettings->{"level{$level}Role"})
                                                                    Role: {{ $approvalSettings->{"level{$level}Role"}->name }}
                                                                @else
                                                                    Not configured
                                                                @endif
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                            @endfor
                                        </div>
                                        @if(!$approvalSettings->level_1_enabled && !$approvalSettings->level_2_enabled && !$approvalSettings->level_3_enabled && !$approvalSettings->level_4_enabled && !$approvalSettings->level_5_enabled)
                                        <div class="text-center py-4">
                                            <i class="bx bx-info-circle text-info me-2"></i>
                                            <span class="text-muted">No approval levels configured. Requisitions will be auto-approved.</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert-dismissible').fadeOut('slow');
        }, 5000);
    });
</script>
@endsection