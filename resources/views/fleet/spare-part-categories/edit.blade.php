@extends('layouts.main')

@section('title', 'Edit Spare Part Category - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Spare Parts Master (Vipuri)', 'url' => route('fleet.spare-part-categories.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Edit: ' . $category->name, 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-purple text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit: {{ $category->name }}</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.spare-part-categories.update', $category) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}" required maxlength="100">
                            @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $category->code) }}" maxlength="50">
                            @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expected lifespan (km)</label>
                            <input type="number" name="expected_lifespan_km" class="form-control" value="{{ old('expected_lifespan_km', $category->expected_lifespan_km) }}" min="0" step="0.01">
                            @error('expected_lifespan_km')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expected lifespan (months)</label>
                            <input type="number" name="expected_lifespan_months" class="form-control" value="{{ old('expected_lifespan_months', $category->expected_lifespan_months) }}" min="0">
                            @error('expected_lifespan_months')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Min replacement interval (km)</label>
                            <input type="number" name="min_replacement_interval_km" class="form-control" value="{{ old('min_replacement_interval_km', $category->min_replacement_interval_km) }}" min="0" step="0.01">
                            @error('min_replacement_interval_km')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Min replacement interval (months)</label>
                            <input type="number" name="min_replacement_interval_months" class="form-control" value="{{ old('min_replacement_interval_months', $category->min_replacement_interval_months) }}" min="0">
                            @error('min_replacement_interval_months')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Standard cost min</label>
                            <input type="number" name="standard_cost_min" class="form-control" value="{{ old('standard_cost_min', $category->standard_cost_min) }}" min="0" step="0.01">
                            @error('standard_cost_min')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Standard cost max</label>
                            <input type="number" name="standard_cost_max" class="form-control" value="{{ old('standard_cost_max', $category->standard_cost_max) }}" min="0" step="0.01">
                            @error('standard_cost_max')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Approval threshold</label>
                            <input type="number" name="approval_threshold" class="form-control" value="{{ old('approval_threshold', $category->approval_threshold) }}" min="0" step="0.01">
                            @error('approval_threshold')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2" maxlength="1000">{{ old('description', $category->description) }}</textarea>
                            @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-purple"><i class="bx bx-save me-1"></i>Update</button>
                            <a href="{{ route('fleet.spare-part-categories.show', $category) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>.bg-purple { background-color: #6f42c1 !important; } .btn-purple { background-color: #6f42c1; border-color: #6f42c1; color: #fff; } .btn-purple:hover { color: #fff; background-color: #5a32a3; border-color: #5a32a3; }</style>
@endpush
