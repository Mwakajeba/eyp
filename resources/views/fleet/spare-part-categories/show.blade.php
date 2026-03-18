@extends('layouts.main')

@section('title', 'Spare Part Category: ' . $category->name . ' - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Spare Parts Master (Vipuri)', 'url' => route('fleet.spare-part-categories.index'), 'icon' => 'bx bx-package'],
            ['label' => $category->name, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header bg-purple text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bx bx-package me-2"></i>{{ $category->name }}</h6>
                <div>
                    <a href="{{ route('fleet.spare-part-categories.edit', $category) }}" class="btn btn-light btn-sm"><i class="bx bx-edit me-1"></i>Edit</a>
                    <a href="{{ route('fleet.spare-part-categories.index') }}" class="btn btn-light btn-sm"><i class="bx bx-arrow-back me-1"></i>Back</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Code</label>
                        <p class="mb-0">{{ $category->code ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Expected life (km / months)</label>
                        <p class="mb-0">{{ $category->expected_lifespan_km ? number_format($category->expected_lifespan_km) . ' km' : '—' }} / {{ $category->expected_lifespan_months ? $category->expected_lifespan_months . ' mo' : '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Min replacement interval</label>
                        <p class="mb-0">{{ $category->min_replacement_interval_km ? number_format($category->min_replacement_interval_km) . ' km' : '—' }} / {{ $category->min_replacement_interval_months ? $category->min_replacement_interval_months . ' mo' : '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Cost range</label>
                        <p class="mb-0">{{ ($category->standard_cost_min || $category->standard_cost_max) ? number_format($category->standard_cost_min ?? 0) . ' – ' . number_format($category->standard_cost_max ?? 0) : '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Approval threshold</label>
                        <p class="mb-0">{{ $category->approval_threshold ? number_format($category->approval_threshold) : '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Status</label>
                        <p class="mb-0">@if($category->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Inactive</span>@endif</p>
                    </div>
                    @if($category->description)
                    <div class="col-12">
                        <label class="form-label fw-bold">Description</label>
                        <p class="mb-0">{{ $category->description }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>.bg-purple { background-color: #6f42c1 !important; }</style>
@endpush
