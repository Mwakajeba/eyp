@extends('layouts.main')

@section('title', 'Valuation Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Investment Management', 'url' => route('investments.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Valuations', 'url' => route('investments.valuations.index'), 'icon' => 'bx bx-line-chart'],
            ['label' => 'Valuation Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">VALUATION DETAILS</h6>
            <div class="btn-group">
                <a href="{{ route('investments.valuations.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back"></i> Back to List
                </a>
                @if($valuation->isPendingApproval() && Auth::user()->can('approve', $valuation))
                <a href="{{ route('investments.valuations.approve', $valuation->hash_id) }}" class="btn btn-success">
                    <i class="bx bx-check"></i> Approve
                </a>
                @endif
                @if($valuation->isApproved() && !$valuation->isPosted())
                <a href="{{ route('investments.valuations.preview', $valuation->hash_id) }}" class="btn btn-info">
                    <i class="bx bx-search"></i> Preview Revaluation
                </a>
                @endif
            </div>
        </div>
        <hr />

        <div class="row">
            <div class="col-lg-8">
                <!-- Valuation Information -->
                <div class="card mb-3">
                    <div class="card-header bg-primary bg-gradient text-white">
                        <h5 class="mb-0">
                            <i class="bx bx-info-circle me-2"></i>Valuation Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">Investment</label>
                                <div>
                                    <a href="{{ route('investments.master.show', $valuation->investment->hash_id) }}" class="text-primary fw-bold">
                                        {{ $valuation->investment->instrument_code }}
                                    </a>
                                    <span class="badge bg-info ms-2">{{ $valuation->investment->accounting_class }}</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">Valuation Date</label>
                                <div class="fw-bold">{{ $valuation->valuation_date->format('M d, Y') }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">Valuation Level</label>
                                <div>
                                    @if($valuation->valuation_level == 1)
                                        <span class="badge bg-success">Level 1 - Quoted Prices</span>
                                    @elseif($valuation->valuation_level == 2)
                                        <span class="badge bg-info">Level 2 - Observable Inputs</span>
                                    @else
                                        <span class="badge bg-warning">Level 3 - Unobservable Inputs</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">Valuation Method</label>
                                <div class="fw-bold">{{ str_replace('_', ' ', $valuation->valuation_method) }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">Status</label>
                                <div>
                                    @if($valuation->status == 'DRAFT')
                                        <span class="badge bg-secondary">Draft</span>
                                    @elseif($valuation->status == 'PENDING_APPROVAL')
                                        <span class="badge bg-warning">Pending Approval</span>
                                    @elseif($valuation->status == 'APPROVED')
                                        <span class="badge bg-success">Approved</span>
                                    @elseif($valuation->status == 'POSTED')
                                        <span class="badge bg-info">Posted</span>
                                    @else
                                        <span class="badge bg-danger">{{ $valuation->status }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">Price Source</label>
                                <div class="fw-bold">{{ $valuation->price_source ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fair Value Details -->
                <div class="card mb-3">
                    <div class="card-header bg-success bg-gradient text-white">
                        <h5 class="mb-0">
                            <i class="bx bx-calculator me-2"></i>Fair Value Calculation
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold text-muted">Fair Value per Unit</label>
                                <div class="h5 text-primary fw-bold">TZS {{ number_format($valuation->fair_value_per_unit, 6) }}</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold text-muted">Units</label>
                                <div class="h5 text-primary fw-bold">{{ number_format($valuation->units, 6) }}</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold text-muted">Total Fair Value</label>
                                <div class="h5 text-success fw-bold">TZS {{ number_format($valuation->total_fair_value, 2) }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">Carrying Amount (Before)</label>
                                <div class="fw-bold">TZS {{ number_format($valuation->carrying_amount_before, 2) }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">Carrying Amount (After)</label>
                                <div class="fw-bold">TZS {{ number_format($valuation->carrying_amount_after, 2) }}</div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold text-muted">Unrealized Gain/Loss</label>
                                <div class="h5 fw-bold {{ $valuation->unrealized_gain_loss >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $valuation->unrealized_gain_loss >= 0 ? '+' : '' }}TZS {{ number_format($valuation->unrealized_gain_loss, 2) }}
                                </div>
                            </div>
                            @if($valuation->investment->accounting_class == 'FVOCI')
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold text-muted">FVOCI Reserve Change</label>
                                <div class="fw-bold {{ $valuation->fvoci_reserve_change >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $valuation->fvoci_reserve_change >= 0 ? '+' : '' }}TZS {{ number_format($valuation->fvoci_reserve_change, 2) }}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Valuation Inputs (Level 2 & 3) -->
                @if($valuation->valuation_level > 1)
                <div class="card mb-3">
                    <div class="card-header bg-info bg-gradient text-white">
                        <h5 class="mb-0">
                            <i class="bx bx-slider me-2"></i>Valuation Inputs
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @if($valuation->yield_rate)
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">Yield Rate</label>
                                <div class="fw-bold">{{ number_format($valuation->yield_rate, 6) }}%</div>
                            </div>
                            @endif
                            @if($valuation->discount_rate)
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-muted">Discount Rate</label>
                                <div class="fw-bold">{{ number_format($valuation->discount_rate, 6) }}%</div>
                            </div>
                            @endif
                            @if($valuation->valuation_assumptions)
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold text-muted">Valuation Assumptions</label>
                                <div class="border rounded p-3 bg-light">{{ $valuation->valuation_assumptions }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- Journal Information -->
                @if($valuation->isPosted() && $valuation->journal)
                <div class="card mb-3">
                    <div class="card-header bg-info bg-gradient text-white">
                        <h5 class="mb-0">
                            <i class="bx bx-book me-2"></i>Posted Journal
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('accounting.journals.show', $valuation->journal->hash_id) }}" class="text-primary fw-bold">
                                    {{ $valuation->journal->journal_number }}
                                </a>
                                <div class="text-muted small">Posted on {{ $valuation->posted_at->format('M d, Y H:i') }}</div>
                            </div>
                            <span class="badge bg-success">Posted</span>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-info">
                        <h6 class="mb-0 text-white">
                            <i class="bx bx-info-circle me-1"></i>Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($valuation->isPendingApproval() && Auth::user()->can('approve', $valuation))
                        <a href="{{ route('investments.valuations.approve', $valuation->hash_id) }}" class="btn btn-success w-100 mb-2">
                            <i class="bx bx-check me-1"></i> Approve Valuation
                        </a>
                        @endif
                        @if($valuation->isApproved() && !$valuation->isPosted())
                        <a href="{{ route('investments.valuations.preview', $valuation->hash_id) }}" class="btn btn-info w-100 mb-2">
                            <i class="bx bx-search me-1"></i> Preview Revaluation
                        </a>
                        @endif
                        <a href="{{ route('investments.valuations.index') }}" class="btn btn-secondary w-100">
                            <i class="bx bx-arrow-back me-1"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

