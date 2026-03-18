@extends('layouts.main')

@section('title', 'Investment Details')

@push('css')
<style>
    .investment-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        min-height: 200px;
        position: relative;
        overflow: hidden;
    }
    .investment-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }
    .investment-header::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: -10%;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
        border-radius: 50%;
    }
    .info-item {
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .info-item:last-child {
        border-bottom: none;
    }
    .info-label {
        font-size: 0.85rem;
        color: #6c757d;
        font-weight: 500;
        margin-bottom: 0.25rem;
    }
    .info-value {
        font-size: 1rem;
        color: #212529;
        font-weight: 600;
    }
    .metric-card {
        background: white;
        border-radius: 10px;
        padding: 1.25rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: transform 0.2s;
    }
    .metric-card:hover {
        transform: translateY(-2px);
    }
    .metric-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 0.75rem;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Investment Management', 'url' => route('investments.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Investment Master', 'url' => route('investments.master.index'), 'icon' => 'bx bx-package'],
            ['label' => $master->instrument_code, 'url' => '#', 'icon' => 'bx bx-show']
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

        @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Investment Header Card -->
        <div class="card border-0 shadow-lg mb-4 investment-header position-relative">
            <div class="card-body text-white p-4 position-relative">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <i class="bx bx-trending-up font-50"></i>
                            </div>
                            <div>
                                <h4 class="mb-1 fw-bold">{{ $master->instrument_code }}</h4>
                                <p class="mb-0 opacity-75">
                                    {{ str_replace('_', ' ', $master->instrument_type) }}
                                    @if($master->issuer)
                                        • {{ $master->issuer }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="info-item border-0">
                                    <div class="info-label text-white opacity-75">Status</div>
                                    <div class="info-value text-white">
                                        @if($master->status == 'DRAFT')
                                            <span class="badge bg-light text-dark fs-6">Draft</span>
                                        @elseif($master->status == 'ACTIVE')
                                            <span class="badge bg-success fs-6">Active</span>
                                        @elseif($master->status == 'MATURED')
                                            <span class="badge bg-warning text-dark fs-6">Matured</span>
                                        @elseif($master->status == 'DISPOSED')
                                            <span class="badge bg-dark fs-6">Disposed</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-item border-0">
                                    <div class="info-label text-white opacity-75">Carrying Amount</div>
                                    <div class="info-value text-white fs-5">TZS {{ number_format($master->carrying_amount, 2) }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-item border-0">
                                    <div class="info-label text-white opacity-75">Days to Maturity</div>
                                    <div class="info-value text-white fs-5">
                                        @if($daysToMaturity !== null)
                                            @if($daysToMaturity > 0)
                                                {{ number_format($daysToMaturity) }} days
                                            @else
                                                <span class="badge bg-warning text-dark">Matured</span>
                                            @endif
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="btn-group-vertical gap-2">
                            @if($master->status == 'DRAFT')
                            <a href="{{ route('investments.master.edit', $master->hash_id) }}" class="btn btn-light">
                                <i class="bx bx-edit"></i> Edit Investment
                            </a>
                            @endif
                            @if($master->status == 'ACTIVE')
                            <a href="{{ route('investments.master.amortization', $master->hash_id) }}" class="btn btn-light">
                                <i class="bx bx-table"></i> Amortization Schedule
                            </a>
                            <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#recalculateEirModal">
                                <i class="bx bx-calculator"></i> Recalculate EIR
                            </button>
                            <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#postAccrualModal">
                                <i class="bx bx-plus-circle"></i> Post Accrual
                            </button>
                            <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#couponPaymentModal">
                                <i class="bx bx-money"></i> Coupon Payment
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bx bx-money"></i>
                    </div>
                    <div class="info-label">Nominal Amount</div>
                    <div class="info-value text-primary">TZS {{ number_format($master->nominal_amount, 2) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-icon bg-success bg-opacity-10 text-success">
                        <i class="bx bx-trending-up"></i>
                    </div>
                    <div class="info-label">EIR Rate</div>
                    <div class="info-value text-success">
                        @if($master->eir_rate)
                            {{ number_format($master->eir_rate, 4) }}%
                        @else
                            <span class="text-muted">Not calculated</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-icon bg-info bg-opacity-10 text-info">
                        <i class="bx bx-percentage"></i>
                    </div>
                    <div class="info-label">Coupon Rate</div>
                    <div class="info-value text-info">
                        @if($master->coupon_rate)
                            {{ number_format($master->coupon_rate * 100, 2) }}%
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bx bx-package"></i>
                    </div>
                    <div class="info-label">Units</div>
                    <div class="info-value text-warning">{{ number_format($master->units, 6) }}</div>
                </div>
            </div>
        </div>

        <!-- Tabbed Content -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#overview" role="tab">
                            <i class="bx bx-info-circle me-1"></i>Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#trades" role="tab">
                            <i class="bx bx-transfer me-1"></i>Trade History
                            @if($master->trades->count() > 0)
                                <span class="badge bg-primary ms-1">{{ $master->trades->count() }}</span>
                            @endif
                        </a>
                    </li>
                    @if($master->status == 'ACTIVE')
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#amortization" role="tab">
                            <i class="bx bx-table me-1"></i>Amortization
                            @if($amortizationLines->count() > 0)
                                <span class="badge bg-info ms-1">{{ $amortizationLines->count() }}</span>
                            @endif
                        </a>
                    </li>
                    @endif
                    @if($latestTrade && ($latestTrade->ecl_amount || $latestTrade->fair_value))
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#valuation" role="tab">
                            <i class="bx bx-shield me-1"></i>Valuation & ECL
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Overview Tab -->
                    <div class="tab-pane fade show active" id="overview" role="tabpanel">
                        <div class="row g-4">
                            <!-- Investment Details -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-primary bg-opacity-10 border-0">
                                        <h6 class="mb-0 text-primary fw-bold">
                                            <i class="bx bx-info-circle me-2"></i>Investment Details
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-borderless mb-0">
                                            <tbody>
                                                <tr>
                                                    <td class="text-muted" style="width: 40%;"><i class="bx bx-code-alt me-1"></i>Instrument Code:</td>
                                                    <td><strong>{{ $master->instrument_code }}</strong></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted"><i class="bx bx-category me-1"></i>Type:</td>
                                                    <td><strong>{{ str_replace('_', ' ', $master->instrument_type) }}</strong></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted"><i class="bx bx-building me-1"></i>Issuer:</td>
                                                    <td><strong>{{ $master->issuer ?? 'N/A' }}</strong></td>
                                                </tr>
                                                @if($master->isin)
                                                <tr>
                                                    <td class="text-muted"><i class="bx bx-barcode me-1"></i>ISIN:</td>
                                                    <td><strong>{{ $master->isin }}</strong></td>
                                                </tr>
                                                @endif
                                                <tr>
                                                    <td class="text-muted"><i class="bx bx-money me-1"></i>Currency:</td>
                                                    <td><strong>{{ $master->currency }}</strong></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted"><i class="bx bx-category me-1"></i>Accounting Class:</td>
                                                    <td>
                                                        <span class="badge bg-info">{{ str_replace('_', ' ', $master->accounting_class) }}</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Dates & Terms -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-success bg-opacity-10 border-0">
                                        <h6 class="mb-0 text-success fw-bold">
                                            <i class="bx bx-calendar me-2"></i>Dates & Terms
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-borderless mb-0">
                                            <tbody>
                                                <tr>
                                                    <td class="text-muted" style="width: 40%;"><i class="bx bx-shopping-bag me-1"></i>Purchase Date:</td>
                                                    <td><strong>{{ $master->purchase_date ? $master->purchase_date->format('M d, Y') : 'N/A' }}</strong></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted"><i class="bx bx-check-circle me-1"></i>Settlement Date:</td>
                                                    <td><strong>{{ $master->settlement_date ? $master->settlement_date->format('M d, Y') : 'N/A' }}</strong></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted"><i class="bx bx-time me-1"></i>Maturity Date:</td>
                                                    <td>
                                                        <strong>{{ $master->maturity_date ? $master->maturity_date->format('M d, Y') : 'N/A' }}</strong>
                                                        @if($daysToMaturity !== null && $daysToMaturity > 0)
                                                            <br><small class="text-muted">{{ number_format($daysToMaturity) }} days remaining</small>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @if($master->coupon_freq)
                                                <tr>
                                                    <td class="text-muted"><i class="bx bx-refresh me-1"></i>Coupon Frequency:</td>
                                                    <td><strong>{{ $master->coupon_freq }} per year</strong></td>
                                                </tr>
                                                @endif
                                                <tr>
                                                    <td class="text-muted"><i class="bx bx-calculator me-1"></i>Day Count:</td>
                                                    <td><strong>{{ $master->day_count }}</strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Summary -->
                            <div class="col-md-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-warning bg-opacity-10 border-0">
                                        <h6 class="mb-0 text-warning fw-bold">
                                            <i class="bx bx-line-chart me-2"></i>Financial Summary
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 text-center">
                                                <div class="info-label">Purchase Price</div>
                                                <div class="info-value text-primary">{{ number_format($master->purchase_price, 6) }} per unit</div>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <div class="info-label">Units</div>
                                                <div class="info-value text-success">{{ number_format($master->units, 6) }}</div>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <div class="info-label">Nominal Amount</div>
                                                <div class="info-value text-info">TZS {{ number_format($master->nominal_amount, 2) }}</div>
                                            </div>
                                            <div class="col-md-3 text-center">
                                                <div class="info-label">Carrying Amount</div>
                                                <div class="info-value text-warning fs-5">TZS {{ number_format($master->carrying_amount, 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trades Tab -->
                    <div class="tab-pane fade" id="trades" role="tabpanel">
                        @if($master->trades->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Trade Date</th>
                                        <th>Type</th>
                                        <th class="text-end">Units</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Gross Amount</th>
                                        <th class="text-end">Fees</th>
                                        <th class="text-end">Net Amount</th>
                                        <th>Settlement Status</th>
                                        <th>Journal</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($master->trades->sortByDesc('trade_date') as $trade)
                                    <tr>
                                        <td>{{ $trade->trade_date->format('M d, Y') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $trade->trade_type == 'PURCHASE' ? 'success' : ($trade->trade_type == 'SALE' ? 'danger' : 'info') }}">
                                                {{ $trade->trade_type }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ number_format($trade->trade_units, 6) }}</td>
                                        <td class="text-end">{{ number_format($trade->trade_price, 6) }}</td>
                                        <td class="text-end fw-bold">TZS {{ number_format($trade->gross_amount, 2) }}</td>
                                        <td class="text-end">TZS {{ number_format($trade->fees ?? 0, 2) }}</td>
                                        <td class="text-end">TZS {{ number_format($trade->net_amount, 2) }}</td>
                                        <td>
                                            @if($trade->settlement_status == 'PENDING')
                                                <span class="badge bg-warning">Pending</span>
                                            @elseif($trade->settlement_status == 'SETTLED')
                                                <span class="badge bg-success">Settled</span>
                                            @elseif($trade->settlement_status == 'INSTRUCTED')
                                                <span class="badge bg-info">Instructed</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $trade->settlement_status }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($trade->posted_journal_id)
                                                <a href="{{ route('accounting.journals.show', \Vinkla\Hashids\Facades\Hashids::encode($trade->posted_journal_id)) }}" class="badge bg-info">
                                                    View Journal
                                                </a>
                                            @else
                                                <span class="badge bg-secondary">Not Posted</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('investments.trades.show', $trade->hash_id) }}" class="btn btn-sm btn-primary">
                                                <i class="bx bx-show"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-5">
                            <i class="bx bx-transfer font-50 text-muted"></i>
                            <p class="text-muted mt-3">No trades recorded for this investment</p>
                            <a href="{{ route('investments.trades.create', ['investment_id' => $master->hash_id]) }}" class="btn btn-primary">
                                <i class="bx bx-plus"></i> Create First Trade
                            </a>
                        </div>
                        @endif
                    </div>

                    <!-- Amortization Tab -->
                    @if($master->status == 'ACTIVE')
                    <div class="tab-pane fade" id="amortization" role="tabpanel">
                        @if($amortizationLines->count() > 0)
                        @php
                            $pendingLines = $amortizationLines->where('posted', false);
                            $postedLines = $amortizationLines->where('posted', true);
                        @endphp
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <div class="info-label">Total Lines</div>
                                        <div class="info-value text-primary fs-4">{{ $amortizationLines->count() }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <div class="info-label">Posted</div>
                                        <div class="info-value text-success fs-4">{{ $postedLines->count() }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <div class="info-label">Pending</div>
                                        <div class="info-value text-warning fs-4">{{ $pendingLines->count() }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if($pendingLines->count() > 0)
                        <div class="alert alert-info">
                            <strong>Next Pending Accrual:</strong>
                            Period: {{ $pendingLines->first()->period_start->format('M d, Y') }} to {{ $pendingLines->first()->period_end->format('M d, Y') }}<br>
                            Amount: TZS {{ number_format($pendingLines->first()->interest_income, 2) }}
                        </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Period Start</th>
                                        <th>Period End</th>
                                        <th class="text-end">Opening Balance</th>
                                        <th class="text-end">Interest Income</th>
                                        <th class="text-end">Closing Balance</th>
                                        <th>Status</th>
                                        <th>Journal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($amortizationLines as $line)
                                    <tr>
                                        <td>{{ $line->period_start->format('M d, Y') }}</td>
                                        <td>{{ $line->period_end->format('M d, Y') }}</td>
                                        <td class="text-end">TZS {{ number_format($line->opening_carrying_amount, 2) }}</td>
                                        <td class="text-end fw-bold text-success">TZS {{ number_format($line->interest_income, 2) }}</td>
                                        <td class="text-end">TZS {{ number_format($line->closing_carrying_amount, 2) }}</td>
                                        <td>
                                            @if($line->posted)
                                                <span class="badge bg-success">Posted</span>
                                            @else
                                                <span class="badge bg-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($line->journal_id)
                                                <a href="{{ route('accounting.journals.show', \Vinkla\Hashids\Facades\Hashids::encode($line->journal_id)) }}" class="badge bg-info">
                                                    View
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-5">
                            <i class="bx bx-table font-50 text-muted"></i>
                            <p class="text-muted mt-3">No amortization schedule generated yet</p>
                            <form action="{{ route('investments.master.generate-amortization', $master->hash_id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary" onclick="return confirm('Generate amortization schedule?')">
                                    <i class="bx bx-refresh"></i> Generate Amortization Schedule
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- Valuation & ECL Tab -->
                    @if($latestTrade && ($latestTrade->ecl_amount || $latestTrade->fair_value))
                    <div class="tab-pane fade" id="valuation" role="tabpanel">
                        <div class="row g-4">
                            <!-- Fair Value Information -->
                            @if($latestTrade->fair_value)
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-info bg-opacity-10 border-0">
                                        <h6 class="mb-0 text-info fw-bold">
                                            <i class="bx bx-line-chart me-2"></i>Fair Value
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="info-item">
                                            <div class="info-label">Fair Value</div>
                                            <div class="info-value text-info fs-4">TZS {{ number_format($latestTrade->fair_value, 2) }}</div>
                                        </div>
                                        @if($latestTrade->fair_value_source)
                                        <div class="info-item">
                                            <div class="info-label">Source</div>
                                            <div class="info-value">{{ $latestTrade->fair_value_source }}</div>
                                        </div>
                                        @endif
                                        @if($latestTrade->fair_value && $master->carrying_amount)
                                        @php
                                            $gainLoss = $latestTrade->fair_value - $master->carrying_amount;
                                            $gainLossPercent = $master->carrying_amount > 0 ? (($gainLoss / $master->carrying_amount) * 100) : 0;
                                        @endphp
                                        <div class="info-item">
                                            <div class="info-label">Unrealized Gain/Loss</div>
                                            <div class="info-value {{ $gainLoss >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $gainLoss >= 0 ? '+' : '' }}{{ number_format($gainLoss, 2) }} 
                                                ({{ $gainLoss >= 0 ? '+' : '' }}{{ number_format($gainLossPercent, 2) }}%)
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- ECL Information -->
                            @if($latestTrade->ecl_amount)
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-danger bg-opacity-10 border-0">
                                        <h6 class="mb-0 text-danger fw-bold">
                                            <i class="bx bx-shield me-2"></i>Expected Credit Loss (ECL)
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="info-item">
                                            <div class="info-label">ECL Amount</div>
                                            <div class="info-value text-danger fs-4">TZS {{ number_format($latestTrade->ecl_amount, 2) }}</div>
                                        </div>
                                        @if($latestTrade->stage)
                                        <div class="info-item">
                                            <div class="info-label">IFRS 9 Stage</div>
                                            <div class="info-value">
                                                <span class="badge bg-{{ $latestTrade->stage == 1 ? 'primary' : ($latestTrade->stage == 2 ? 'warning' : 'danger') }}">
                                                    Stage {{ $latestTrade->stage }}
                                                </span>
                                            </div>
                                        </div>
                                        @endif
                                        @if($latestTrade->pd)
                                        <div class="info-item">
                                            <div class="info-label">PD (Probability of Default)</div>
                                            <div class="info-value">{{ number_format($latestTrade->pd, 6) }}%</div>
                                        </div>
                                        @endif
                                        @if($latestTrade->lgd)
                                        <div class="info-item">
                                            <div class="info-label">LGD (Loss Given Default)</div>
                                            <div class="info-value">{{ number_format($latestTrade->lgd, 6) }}%</div>
                                        </div>
                                        @endif
                                        @if($latestTrade->ead)
                                        <div class="info-item">
                                            <div class="info-label">EAD (Exposure At Default)</div>
                                            <div class="info-value">TZS {{ number_format($latestTrade->ead, 2) }}</div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar Information -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-0">
                        <h6 class="mb-0">
                            <i class="bx bx-info-circle me-2"></i>Additional Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="info-item">
                                    <div class="info-label">Created By</div>
                                    <div class="info-value">{{ $master->creator->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-item">
                                    <div class="info-label">Created At</div>
                                    <div class="info-value">{{ $master->created_at->format('M d, Y H:i A') }}</div>
                                </div>
                            </div>
                            @if($master->updated_by)
                            <div class="col-md-3">
                                <div class="info-item">
                                    <div class="info-label">Updated By</div>
                                    <div class="info-value">{{ $master->updater->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-item">
                                    <div class="info-label">Updated At</div>
                                    <div class="info-value">{{ $master->updated_at->format('M d, Y H:i A') }}</div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recalculate EIR Modal -->
@if($master->status == 'ACTIVE')
@include('investments.master.modals.recalculate-eir', ['master' => $master])
@include('investments.master.modals.post-accrual', ['master' => $master, 'amortizationLines' => $amortizationLines])
@include('investments.master.modals.coupon-payment', ['master' => $master])
@endif
@endsection
