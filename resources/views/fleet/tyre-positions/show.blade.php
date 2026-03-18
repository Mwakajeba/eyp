@extends('layouts.main')

@section('title', 'Tyre Position: ' . $position->position_name . ' - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Truck Tyre Configuration', 'url' => route('fleet.tyre-positions.index'), 'icon' => 'bx bx-grid-alt'],
            ['label' => $position->position_name, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bx bx-grid-alt me-2"></i>{{ $position->position_name }}</h6>
                <div>
                    <a href="{{ route('fleet.tyre-positions.edit', $position->id) }}" class="btn btn-light btn-sm"><i class="bx bx-edit me-1"></i>Edit</a>
                    <a href="{{ route('fleet.tyre-positions.index') }}" class="btn btn-light btn-sm"><i class="bx bx-arrow-back me-1"></i>Back</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Position code</label>
                        <p class="mb-0"><code>{{ $position->position_code ?? '—' }}</code></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Position name</label>
                        <p class="mb-0">{{ $position->position_name }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Sort order</label>
                        <p class="mb-0">{{ $position->sort_order }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Status</label>
                        <p class="mb-0">@if($position->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Inactive</span>@endif</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
