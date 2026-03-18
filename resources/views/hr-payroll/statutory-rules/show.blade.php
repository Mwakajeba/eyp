@extends('layouts.main')

@section('title', 'Statutory Rule Details')

@push('styles')
<style>
    .rule-header-card {
        background: #667eea;
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: #667eea;
        position: relative;
        overflow: hidden;
    }

    .rule-header-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 400px;
        height: 400px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .rule-header-content {
        position: relative;
        z-index: 1;
        color: white;
    }

    .info-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    .info-item {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .info-value {
        font-size: 1rem;
        font-weight: 500;
        color: #212529;
    }

    .config-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
        margin-top: 25px;
    }

    .bracket-table {
        background: white;
        border-radius: 8px;
        overflow: hidden;
    }

    .bracket-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .bracket-table thead th {
        border: none;
        padding: 15px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }

    .bracket-table tbody td {
        padding: 15px;
        vertical-align: middle;
    }

    .bracket-table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        border: 2px solid #f0f0f0;
        transition: all 0.3s;
    }

    .stat-card:hover {
        border-color: #667eea;
        transform: translateY(-3px);
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #667eea;
        margin: 10px 0;
    }

    .stat-label {
        font-size: 0.875rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
</style>
@endpush

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'HR & Payroll', 'url' => route('hr-payroll.index'), 'icon' => 'bx bx-user'],
                ['label' => 'Statutory Rules', 'url' => route('hr.statutory-rules.index'), 'icon' => 'bx bx-shield'],
                ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
            ]" />

            <!-- Header Card -->
            <div class="rule-header-card">
                <div class="rule-header-content">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h3 class="mb-2 text-white">
                                <i class="bx bx-shield me-2"></i>{{ $statutoryRule->rule_name }}
                            </h3>
                            <p class="mb-0 text-white-50">
                                <i class="bx bx-info-circle me-1"></i>
                                @php
                                    $descriptions = [
                                        'paye' => 'Pay As You Earn - Income Tax',
                                        'nhif' => 'National Health Insurance Fund',
                                        'pension' => 'Pension Scheme (NSSF/PSSSF)',
                                        'wcf' => 'Workers Compensation Fund',
                                        'sdl' => 'Skills Development Levy',
                                        'heslb' => 'Higher Education Students\' Loans Board',
                                    ];
                                @endphp
                                {{ $descriptions[$statutoryRule->rule_type] ?? 'Statutory Compliance Rule' }}
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            @php
                                $isEffective = $statutoryRule->is_active 
                                    && $statutoryRule->effective_from <= now() 
                                    && (!$statutoryRule->effective_to || $statutoryRule->effective_to >= now());
                            @endphp
                            @if($isEffective)
                                <span class="badge bg-success bg-opacity-25 text-white border border-white border-opacity-50 px-3 py-2">
                                    <i class="bx bx-check-circle me-1"></i>Active
                                </span>
                            @else
                                <span class="badge bg-secondary bg-opacity-25 text-white border border-white border-opacity-50 px-3 py-2">
                                    <i class="bx bx-x-circle me-1"></i>Inactive
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Rule Information Card -->
                    <div class="card info-card mb-4">
                        <div class="card-header bg-transparent border-bottom">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-3">
                                    <div class="avatar-title bg-primary bg-opacity-10 rounded">
                                        <i class="bx bx-info-circle text-primary fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-0">Rule Information</h6>
                                    <small class="text-muted">Basic rule details and configuration</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bx bx-category me-1"></i>Rule Type
                                        </div>
                                        <div class="info-value">
                                            @php
                                                $badges = [
                                                    'paye' => ['bg-danger', 'bx-money'],
                                                    'nhif' => ['bg-info', 'bx-heart'],
                                                    'pension' => ['bg-primary', 'bx-wallet'],
                                                    'wcf' => ['bg-warning', 'bx-shield-alt'],
                                                    'sdl' => ['bg-success', 'bx-book'],
                                                    'heslb' => ['bg-secondary', 'bx-graduation'],
                                                ];
                                                $badge = $badges[$statutoryRule->rule_type] ?? ['bg-secondary', 'bx-shield'];
                                            @endphp
                                            <span class="badge {{ $badge[0] }} px-3 py-2">
                                                <i class="bx {{ $badge[1] }} me-1"></i>{{ strtoupper($statutoryRule->rule_type) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bx bx-calendar me-1"></i>Effective Period
                                        </div>
                                        <div class="info-value">
                                            <i class="bx bx-calendar-check me-1 text-primary"></i>
                                            {{ $statutoryRule->effective_from->format('d M Y') }}
                                            @if($statutoryRule->effective_to)
                                                <span class="text-muted">to</span> {{ $statutoryRule->effective_to->format('d M Y') }}
                                            @else
                                                <span class="badge bg-success ms-2">Ongoing</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if($statutoryRule->description)
                                <div class="col-12">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bx bx-file-blank me-1"></i>Description
                                        </div>
                                        <div class="info-value">
                                            {{ $statutoryRule->description }}
                                        </div>
                                    </div>
                                </div>
                                @endif
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="bx bx-group me-1"></i>Employee Scope
                                        </div>
                                        <div class="info-value">
                                            @if($statutoryRule->apply_to_all_employees)
                                                <span class="badge bg-success">
                                                    <i class="bx bx-check-circle me-1"></i>All Employees
                                                </span>
                                            @else
                                                <span class="badge bg-info">
                                                    <i class="bx bx-filter me-1"></i>Category-Specific
                                                </span>
                                                @if($statutoryRule->category_name)
                                                    <br><small class="text-muted mt-1 d-block">{{ $statutoryRule->category_name }}</small>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Employee Categories Section -->
                    @if(!$statutoryRule->apply_to_all_employees && $statutoryRule->employeeCategories && $statutoryRule->employeeCategories->count() > 0)
                    <div class="card info-card mb-4">
                        <div class="card-header bg-transparent border-bottom">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-3">
                                    <div class="avatar-title bg-info bg-opacity-10 rounded">
                                        <i class="bx bx-group text-info fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-0">Employee Categories</h6>
                                    <small class="text-muted">This rule applies to employees matching these categories</small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Category Type</th>
                                            <th>Value</th>
                                            <th>Label</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($statutoryRule->employeeCategories as $category)
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        {{ ucfirst(str_replace('_', ' ', $category->category_type)) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <code>{{ $category->category_value }}</code>
                                                </td>
                                                <td>{{ $category->category_label ?? 'N/A' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($statutoryRule->category_description)
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <strong>Description:</strong> {{ $statutoryRule->category_description }}
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Configuration Section -->
                    <div class="config-section">
                        @if($statutoryRule->rule_type == 'paye')
                            <!-- PAYE Configuration -->
                            <div class="d-flex align-items-center mb-4">
                                <div class="avatar-sm me-3">
                                    <div class="avatar-title bg-danger bg-opacity-10 rounded">
                                        <i class="bx bx-money text-danger fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-0">PAYE (Income Tax) Configuration</h5>
                                    <small class="text-muted">Progressive tax brackets and relief settings</small>
                                </div>
                            </div>

                            @if($statutoryRule->paye_tax_relief)
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <label class="info-label">Tax Relief Amount</label>
                                            <h4 class="mb-0 text-danger">{{ number_format($statutoryRule->paye_tax_relief, 2) }} TZS</h4>
                                        </div>
                                        <div class="avatar-lg">
                                            <div class="avatar-title bg-danger bg-opacity-10 rounded-circle">
                                                <i class="bx bx-money text-danger fs-2"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if($statutoryRule->paye_brackets && is_array($statutoryRule->paye_brackets) && count($statutoryRule->paye_brackets) > 0)
                            <div class="card bracket-table mb-0">
                                <div class="card-header bg-transparent border-bottom">
                                    <h6 class="mb-0">
                                        <i class="bx bx-table me-2"></i>Tax Brackets
                                    </h6>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Income Range</th>
                                                <th>Over (TZS)</th>
                                                <th>Base Amount (TZS)</th>
                                                <th>Rate (%)</th>
                                                <th>Calculation Formula</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($statutoryRule->paye_brackets as $index => $bracket)
                                                @php
                                                    $nextBracket = $statutoryRule->paye_brackets[$index + 1] ?? null;
                                                    $notOver = $nextBracket ? number_format($nextBracket['threshold'] ?? 0, 0) : 'And above';
                                                    $over = number_format($bracket['threshold'] ?? 0, 0);
                                                    $baseAmount = number_format($bracket['base_amount'] ?? 0, 2);
                                                    $rate = number_format($bracket['rate'] ?? 0, 2);
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <strong class="text-primary">{{ $over }}</strong>
                                                        <span class="text-muted">&rarr;</span>
                                                        <strong class="text-secondary">{{ $notOver }}</strong>
                                                    </td>
                                                    <td><strong>{{ $over }}</strong></td>
                                                    <td>{{ $baseAmount }}</td>
                                                    <td>
                                                        <span class="badge bg-info">{{ $rate }}%</span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted font-monospace">
                                                            {{ $baseAmount }} + (Income - {{ $over }}) × {{ $rate }}%
                                                        </small>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @else
                            <div class="alert alert-warning">
                                <i class="bx bx-info-circle me-2"></i>No tax brackets configured for this rule.
                            </div>
                            @endif

                        @elseif($statutoryRule->rule_type == 'nhif')
                            <!-- NHIF Configuration -->
                            <div class="d-flex align-items-center mb-4">
                                <div class="avatar-sm me-3">
                                    <div class="avatar-title bg-info bg-opacity-10 rounded">
                                        <i class="bx bx-heart text-info fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-0">NHIF (Health Insurance) Configuration</h5>
                                    <small class="text-muted">Health insurance contribution rates and ceilings</small>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="stat-card">
                                        <div class="stat-label">
                                            <i class="bx bx-user me-1"></i>Employee Contribution
                                        </div>
                                        <div class="stat-value text-info">
                                            {{ $statutoryRule->nhif_employee_percent ? number_format($statutoryRule->nhif_employee_percent, 2) . '%' : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-card">
                                        <div class="stat-label">
                                            <i class="bx bx-building me-1"></i>Employer Contribution
                                        </div>
                                        <div class="stat-value text-info">
                                            {{ $statutoryRule->nhif_employer_percent ? number_format($statutoryRule->nhif_employer_percent, 2) . '%' : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-card">
                                        <div class="stat-label">
                                            <i class="bx bx-trending-up me-1"></i>Contribution Ceiling
                                        </div>
                                        <div class="stat-value text-info">
                                            {{ $statutoryRule->nhif_ceiling ? number_format($statutoryRule->nhif_ceiling, 0) . ' TZS' : 'No Limit' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                        @elseif($statutoryRule->rule_type == 'pension')
                            <!-- Pension Configuration -->
                            <div class="d-flex align-items-center mb-4">
                                <div class="avatar-sm me-3">
                                    <div class="avatar-title bg-primary bg-opacity-10 rounded">
                                        <i class="bx bx-wallet text-primary fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-0">Pension Configuration</h5>
                                    <small class="text-muted">Pension scheme contribution rates and settings</small>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="info-label">
                                                <i class="bx bx-building me-1"></i>Scheme Type
                                            </div>
                                            <div class="info-value mt-2">
                                                @if($statutoryRule->pension_scheme_type)
                                                    <span class="badge bg-primary px-3 py-2 fs-6">
                                                        {{ strtoupper($statutoryRule->pension_scheme_type) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">Not specified</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="info-label">
                                                <i class="bx bx-trending-up me-1"></i>Contribution Ceiling
                                            </div>
                                            <div class="info-value mt-2">
                                                @if($statutoryRule->pension_ceiling)
                                                    <h5 class="mb-0 text-primary">{{ number_format($statutoryRule->pension_ceiling, 0) }} TZS</h5>
                                                @else
                                                    <span class="text-muted">No ceiling limit</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="stat-card">
                                        <div class="stat-label">
                                            <i class="bx bx-user me-1"></i>Employee Contribution
                                        </div>
                                        <div class="stat-value text-primary">
                                            {{ $statutoryRule->pension_employee_percent ? number_format($statutoryRule->pension_employee_percent, 2) . '%' : 'N/A' }}
                                        </div>
                                        <small class="text-muted">Of pensionable salary</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-card">
                                        <div class="stat-label">
                                            <i class="bx bx-building me-1"></i>Employer Contribution
                                        </div>
                                        <div class="stat-value text-primary">
                                            {{ $statutoryRule->pension_employer_percent ? number_format($statutoryRule->pension_employer_percent, 2) . '%' : 'N/A' }}
                                        </div>
                                        <small class="text-muted">Of pensionable salary</small>
                                    </div>
                                </div>
                            </div>

                        @elseif($statutoryRule->rule_type == 'wcf')
                            <!-- WCF Configuration -->
                            <div class="d-flex align-items-center mb-4">
                                <div class="avatar-sm me-3">
                                    <div class="avatar-title bg-warning bg-opacity-10 rounded">
                                        <i class="bx bx-shield-alt text-warning fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-0">WCF (Workers Compensation Fund) Configuration</h5>
                                    <small class="text-muted">Workers compensation contribution settings</small>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="stat-card">
                                        <div class="stat-label">
                                            <i class="bx bx-building me-1"></i>Employer Contribution
                                        </div>
                                        <div class="stat-value text-warning">
                                            {{ $statutoryRule->wcf_employer_percent ? number_format($statutoryRule->wcf_employer_percent, 2) . '%' : 'N/A' }}
                                        </div>
                                        <small class="text-muted">Of total payroll</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="info-label">
                                                <i class="bx bx-briefcase me-1"></i>Industry Type
                                            </div>
                                            <div class="info-value mt-2">
                                                {{ $statutoryRule->industry_type ?: 'Not specified' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        @elseif($statutoryRule->rule_type == 'sdl')
                            <!-- SDL Configuration -->
                            <div class="d-flex align-items-center mb-4">
                                <div class="avatar-sm me-3">
                                    <div class="avatar-title bg-success bg-opacity-10 rounded">
                                        <i class="bx bx-book text-success fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-0">SDL (Skills Development Levy) Configuration</h5>
                                    <small class="text-muted">Skills development levy settings</small>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="stat-card">
                                        <div class="stat-label">
                                            <i class="bx bx-building me-1"></i>Employer Contribution
                                        </div>
                                        <div class="stat-value text-success">
                                            {{ $statutoryRule->sdl_employer_percent ? number_format($statutoryRule->sdl_employer_percent, 2) . '%' : 'N/A' }}
                                        </div>
                                        <small class="text-muted">Of total gross emoluments</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-card">
                                        <div class="stat-label">
                                            <i class="bx bx-group me-1"></i>Minimum Employees
                                        </div>
                                        <div class="stat-value text-primary">
                                            {{ $statutoryRule->sdl_min_employees ?? 10 }} employees
                                        </div>
                                        <small class="text-muted">SDL applies only to employers with {{ $statutoryRule->sdl_min_employees ?? 10 }} or more employees</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-card">
                                        <div class="stat-label">
                                            <i class="bx bx-trending-up me-1"></i>Payroll Threshold
                                        </div>
                                        <div class="stat-value">
                                            @if($statutoryRule->sdl_threshold)
                                                <span class="text-success">{{ number_format($statutoryRule->sdl_threshold, 0) }} TZS</span>
                                            @else
                                                <span class="text-muted">Not set</span>
                                            @endif
                                        </div>
                                        <small class="text-muted">Optional: Additional threshold based on payroll amount</small>
                                    </div>
                                </div>
                            </div>

                        @elseif($statutoryRule->rule_type == 'heslb')
                            <!-- HESLB Configuration -->
                            <div class="d-flex align-items-center mb-4">
                                <div class="avatar-sm me-3">
                                    <div class="avatar-title bg-secondary bg-opacity-10 rounded">
                                        <i class="bx bx-graduation text-secondary fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-0">HESLB (Student Loans) Configuration</h5>
                                    <small class="text-muted">Employee-specific statutory deduction for student loan recovery</small>
                                </div>
                            </div>

                            <div class="alert alert-info mb-4">
                                <i class="bx bx-info-circle me-2"></i>
                                <strong>Note:</strong> HESLB deductions apply only to employees with active loan balances. Deductions are calculated on gross pay, automatically capped to the outstanding balance, and stop when the balance reaches zero. Each repayment is recorded in a transaction ledger.
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="stat-card">
                                        <div class="stat-label">
                                            <i class="bx bx-percent me-1"></i>Deduction Rate
                                        </div>
                                        <div class="stat-value text-secondary">
                                            {{ $statutoryRule->heslb_percent ? number_format($statutoryRule->heslb_percent, 2) . '%' : 'N/A' }}
                                        </div>
                                        <small class="text-muted">Of gross pay</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-card">
                                        <div class="stat-label">
                                            <i class="bx bx-trending-up me-1"></i>Deduction Ceiling
                                        </div>
                                        <div class="stat-value">
                                            @if($statutoryRule->heslb_ceiling)
                                                <span class="text-secondary">{{ number_format($statutoryRule->heslb_ceiling, 0) }} TZS</span>
                                            @else
                                                <span class="text-muted">Not set</span>
                                            @endif
                                        </div>
                                        <small class="text-muted">Maximum deduction per month (optional)</small>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Quick Actions Card -->
                    <div class="card info-card mb-4">
                        <div class="card-header bg-transparent border-bottom">
                            <h6 class="mb-0">
                                <i class="bx bx-menu me-2"></i>Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <a href="{{ route('hr.statutory-rules.edit', $statutoryRule->hash_id) }}" class="btn btn-primary w-100 mb-2">
                                <i class="bx bx-edit me-1"></i>Edit Rule
                            </a>
                            <a href="{{ route('hr.statutory-rules.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="bx bx-arrow-back me-1"></i>Back to List
                            </a>
                        </div>
                    </div>

                    <!-- Rule Status Card -->
                    <div class="card info-card mb-4">
                        <div class="card-header bg-transparent border-bottom">
                            <h6 class="mb-0">
                                <i class="bx bx-info-circle me-2"></i>Rule Status
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="info-item">
                                <div class="info-label">Active Status</div>
                                <div class="info-value">
                                    @if($statutoryRule->is_active)
                                        <span class="badge bg-success px-3 py-2">
                                            <i class="bx bx-check-circle me-1"></i>Active
                                        </span>
                                    @else
                                        <span class="badge bg-secondary px-3 py-2">
                                            <i class="bx bx-x-circle me-1"></i>Inactive
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Effective Status</div>
                                <div class="info-value">
                                    @if($isEffective)
                                        <span class="badge bg-success px-3 py-2">
                                            <i class="bx bx-check-circle me-1"></i>Currently Effective
                                        </span>
                                    @else
                                        <span class="badge bg-warning px-3 py-2">
                                            <i class="bx bx-time me-1"></i>Not Currently Effective
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Metadata Card -->
                    <div class="card info-card">
                        <div class="card-header bg-transparent border-bottom">
                            <h6 class="mb-0">
                                <i class="bx bx-time me-2"></i>Metadata
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="info-item">
                                <div class="info-label">Created At</div>
                                <div class="info-value">
                                    <i class="bx bx-calendar me-1 text-muted"></i>
                                    {{ $statutoryRule->created_at->format('d M Y, h:i A') }}
                                </div>
                            </div>
                            @if($statutoryRule->updated_at && $statutoryRule->updated_at != $statutoryRule->created_at)
                            <div class="info-item">
                                <div class="info-label">Last Updated</div>
                                <div class="info-value">
                                    <i class="bx bx-edit me-1 text-muted"></i>
                                    {{ $statutoryRule->updated_at->format('d M Y, h:i A') }}
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
