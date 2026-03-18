@extends('layouts.main')

@section('title', 'Payroll Variance Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Payroll Reports', 'url' => route('hr.payroll-reports.index'), 'icon' => 'bx bx-money'],
                ['label' => 'Payroll Variance', 'url' => '#', 'icon' => 'bx bx-trending-up']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-trending-up me-2"></i>Payroll Variance Report
                            </h4>
                            <div>
                                <form action="{{ route('hr.payroll-reports.payroll-variance.export-excel') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="current_month" value="{{ $currentMonth }}">
                                    <input type="hidden" name="compare_month" value="{{ $compareMonth }}">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bx bx-download me-1"></i>Export Excel
                                    </button>
                                </form>
                                <form action="{{ route('hr.payroll-reports.payroll-variance.export-pdf') }}" method="POST" class="d-inline" target="_blank">
                                    @csrf
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="current_month" value="{{ $currentMonth }}">
                                    <input type="hidden" name="compare_month" value="{{ $compareMonth }}">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bx bx-file-pdf me-1"></i>Export PDF
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Year</label>
                                <select class="form-select" name="year">
                                    @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Current Month</label>
                                <select class="form-select" name="current_month">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ $currentMonth == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Compare Month</label>
                                <select class="form-select" name="compare_month">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ $compareMonth == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        <!-- Variance Summary -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Current Period</h5>
                                        <p class="mb-1"><strong>{{ \Carbon\Carbon::create($year, $currentMonth, 1)->format('F Y') }}</strong></p>
                                        <h4 class="mb-0">{{ number_format($variances['net_pay']['current'], 2) }} TZS</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Previous Period</h5>
                                        <p class="mb-1"><strong>{{ \Carbon\Carbon::create($year, $compareMonth, 1)->format('F Y') }}</strong></p>
                                        <h4 class="mb-0">{{ number_format($variances['net_pay']['compare'], 2) }} TZS</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-{{ $variances['net_pay']['variance'] >= 0 ? 'success' : 'danger' }}">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-{{ $variances['net_pay']['variance'] >= 0 ? 'success' : 'danger' }}">Variance</h5>
                                        <p class="mb-1"><strong>{{ number_format($variances['net_pay']['variance'], 2) }} TZS</strong></p>
                                        <h4 class="mb-0">{{ number_format($variances['net_pay']['variance_percent'], 2) }}%</h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Variance Details Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Metric</th>
                                        <th class="text-end">Current Period</th>
                                        <th class="text-end">Previous Period</th>
                                        <th class="text-end">Variance</th>
                                        <th class="text-end">Variance %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($variances as $key => $variance)
                                        <tr>
                                            <td><strong>{{ ucwords(str_replace('_', ' ', $key)) }}</strong></td>
                                            <td class="text-end">{{ number_format($variance['current'], 2) }} TZS</td>
                                            <td class="text-end">{{ number_format($variance['compare'], 2) }} TZS</td>
                                            <td class="text-end {{ $variance['variance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $variance['variance'] >= 0 ? '+' : '' }}{{ number_format($variance['variance'], 2) }} TZS
                                            </td>
                                            <td class="text-end {{ $variance['variance_percent'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $variance['variance_percent'] >= 0 ? '+' : '' }}{{ number_format($variance['variance_percent'], 2) }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

