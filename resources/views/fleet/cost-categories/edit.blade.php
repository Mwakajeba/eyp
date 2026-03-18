@extends('layouts.main')

@section('title', 'Edit Cost Category - Fleet Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Fleet Management', 'url' => route('fleet.index'), 'icon' => 'bx bx-car'],
            ['label' => 'Cost Categories', 'url' => route('fleet.cost-categories.index'), 'icon' => 'bx bx-category'],
            ['label' => 'Edit: ' . $category->name, 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Cost Category: {{ $category->name }}</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('fleet.cost-categories.update', $category->id) }}">
                    @csrf
                    @method('PUT')

                    <h6 class="text-info mb-3"><i class="bx bx-category me-2"></i>Category Details</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}" required maxlength="255">
                            @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category Type <span class="text-danger">*</span></label>
                            <select name="category_type" class="form-select" required>
                                @foreach(\App\Models\Fleet\FleetCostCategory::getCategoryTypeOptions() as $val => $label)
                                    <option value="{{ $val }}" {{ old('category_type', $category->category_type) == $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit of Measure</label>
                            <select name="unit_of_measure" class="form-select">
                                <option value="">—</option>
                                @foreach(\App\Models\Fleet\FleetCostCategory::getUnitOfMeasureOptions() as $val => $label)
                                    <option value="{{ $val }}" {{ old('unit_of_measure', $category->unit_of_measure) == $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Active</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label">Category is active and available in trip costs</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" maxlength="500">{{ old('description', $category->description) }}</textarea>
                            @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-info"><i class="bx bx-save me-1"></i>Update</button>
                            <a href="{{ route('fleet.cost-categories.show', $category->id) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
