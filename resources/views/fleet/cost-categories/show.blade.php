@extends('layouts.main')

@section('title', 'Cost Category: ' . $category->name . ' - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Cost Categories', 'url' => route('fleet.cost-categories.index'), 'icon' => 'bx bx-category'],
            ['label' => $category->name, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bx bx-category me-2"></i>{{ $category->name }}</h6>
                <div>
                    <a href="{{ route('fleet.cost-categories.edit', $category->id) }}" class="btn btn-light btn-sm"><i class="bx bx-edit me-1"></i>Edit</a>
                    <a href="{{ route('fleet.cost-categories.index') }}" class="btn btn-light btn-sm"><i class="bx bx-arrow-back me-1"></i>Back</a>
                </div>
            </div>
            <div class="card-body">
                <h6 class="text-info mb-3"><i class="bx bx-detail me-2"></i>Category Details</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Name</label>
                        <p class="mb-0">{{ $category->name }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Type</label>
                        <p class="mb-0"><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $category->category_type)) }}</span></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Unit of Measure</label>
                        <p class="mb-0">{{ $category->unit_of_measure ?: '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Status</label>
                        <p class="mb-0">
                            @if($category->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </p>
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
