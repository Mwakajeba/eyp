@extends('layouts.main')

@section('title', 'View Asset Category')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Asset Management', 'url' => route('assets.index'), 'icon' => 'bx bx-cabinet'],
            ['label' => 'Categories', 'url' => route('assets.categories.index'), 'icon' => 'bx bx-category'],
            ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1">{{ $category->name }}</h5>
                        <div class="text-muted">Category Code: <span class="badge bg-light text-dark">{{ $category->code }}</span></div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('assets.categories.edit', $category->id) }}" class="btn btn-sm btn-primary"><i class="bx bx-edit me-1"></i>Edit</a>
                        <a href="{{ route('assets.categories.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="small text-muted">Method</div>
                        <div class="badge bg-info text-dark">{{ Str::of($category->default_depreciation_method)->replace('_',' ')->title() }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Convention</div>
                        <div class="badge bg-light text-dark">{{ Str::of($category->depreciation_convention)->replace('_',' ')->title() }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Useful Life</div>
                        <div>{{ $category->default_useful_life_months }} months (≈ {{ round(($category->default_useful_life_months ?? 0)/12) }} yrs)</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Depreciation Rate</div>
                        <div>{{ number_format($category->default_depreciation_rate, 2) }}% / year</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="small text-muted">Capitalization Threshold</div>
                        <div>TZS {{ number_format($category->capitalization_threshold, 2) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Residual Value</div>
                        <div>{{ number_format($category->residual_value_percent ?? 0, 2) }}%</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">IFRS/IPSAS Reference</div>
                        <div>{{ $category->ifrs_reference ?? '-' }}</div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="small text-muted mb-1">Notes</div>
                    <div class="border rounded p-2 bg-light">{{ $category->notes ?? '-' }}</div>
                </div>

                <h6 class="mb-2">Default GL Accounts</h6>
                <div class="row g-3">
                    @php
                        $accountLabels = [
                            'asset_account_id' => 'Asset Account',
                            'accum_depr_account_id' => 'Accumulated Depreciation',
                            'depr_expense_account_id' => 'Depreciation Expense',
                            'gain_on_disposal_account_id' => 'Gain on Disposal',
                            'loss_on_disposal_account_id' => 'Loss on Disposal',
                            'revaluation_reserve_account_id' => 'Revaluation Reserve',
                        ];
                    @endphp
                    @foreach($accountLabels as $key => $label)
                    <div class="col-md-4">
                        <div class="small text-muted">{{ $label }}</div>
                        @php $acc = $accounts[$key] ?? null; @endphp
                        @if($acc)
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-dark">{{ $acc->account_code }}</span>
                                <span>{{ $acc->account_name }}</span>
                            </div>
                        @else
                            <div class="text-muted">-</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


